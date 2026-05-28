<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Automatic SEO for /tuyen/{slug}/ and /nha-xe/{slug}/ landing pages.
 */
class MTTF_Landing_SEO {

	const META_PREFIX = '_mttf_seo_';

	/** @var array<string,mixed>|null */
	private static $context = null;

	public static function init() {
		add_action( 'wp', array( __CLASS__, 'boot_context' ), 5 );
		add_filter( 'document_title_parts', array( __CLASS__, 'filter_document_title_parts' ), 20 );

		if ( self::has_rank_math() ) {
			add_filter( 'rank_math/frontend/title', array( __CLASS__, 'filter_rank_math_title' ), 20 );
			add_filter( 'rank_math/frontend/description', array( __CLASS__, 'filter_rank_math_description' ), 20 );
			add_filter( 'rank_math/frontend/canonical', array( __CLASS__, 'filter_rank_math_canonical' ), 20 );
			add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'filter_rank_math_robots' ), 20 );
			add_filter( 'rank_math/opengraph/facebook/title', array( __CLASS__, 'filter_og_title' ), 20 );
			add_filter( 'rank_math/opengraph/facebook/description', array( __CLASS__, 'filter_og_description' ), 20 );
			add_filter( 'rank_math/opengraph/facebook/image', array( __CLASS__, 'filter_og_image' ), 20 );
			add_filter( 'rank_math/opengraph/twitter/title', array( __CLASS__, 'filter_og_title' ), 20 );
			add_filter( 'rank_math/opengraph/twitter/description', array( __CLASS__, 'filter_og_description' ), 20 );
			add_filter( 'rank_math/opengraph/twitter/image', array( __CLASS__, 'filter_og_image' ), 20 );
			add_filter( 'rank_math/sitemap/term_is_indexable', array( __CLASS__, 'filter_sitemap_term_indexable' ), 10, 2 );
		} else {
			add_action( 'wp_head', array( __CLASS__, 'output_fallback_meta' ), 1 );
		}

		add_action( 'wp_head', array( __CLASS__, 'output_schema_json_ld' ), 20 );
		add_filter( 'wp_robots', array( __CLASS__, 'filter_wp_robots' ), 20 );
		add_filter( 'wp_sitemaps_taxonomies_query_args', array( __CLASS__, 'filter_wp_sitemaps_taxonomies' ), 10, 2 );
	}

	/**
	 * @param array<string,bool|string> $robots Robots directives.
	 * @return array<string,bool|string>
	 */
	public static function filter_wp_robots( $robots ) {
		if ( ! self::is_landing_context() || self::has_rank_math() ) {
			return $robots;
		}

		if ( ! self::should_index() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}

		return $robots;
	}

	public static function has_rank_math() {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath', false );
	}

	public static function boot_context() {
		if ( ! is_tax( array( MTTF_Landing_Taxonomies::TAX_TUYEN, MTTF_Landing_Taxonomies::TAX_NHA_XE ) ) ) {
			self::$context = null;
			return;
		}

		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			self::$context = null;
			return;
		}

		$type = MTTF_Landing_Taxonomies::TAX_TUYEN === $term->taxonomy ? 'tuyen' : 'nha_xe';

		$routes = 'tuyen' === $type
			? MTTF_Landing_Query::get_routes_for_tuyen( $term )
			: MTTF_Landing_Query::get_routes_for_nha_xe( $term );

		$stats = MTTF_Landing_Tuyen::collect_route_stats( $routes );
		$groups = 'nha_xe' === $type ? MTTF_Landing_Query::group_routes_by_tuyen( $routes ) : array();

		$faq = 'tuyen' === $type
			? MTTF_Landing_Tuyen::get_faq_items( $term )
			: MTTF_Landing_Nha_Xe::get_faq_items( $term );

		self::$context = array(
			'type'       => $type,
			'term'       => $term,
			'routes'     => $routes,
			'stats'      => $stats,
			'groups'     => $groups,
			'faq'        => $faq,
			'route_count'=> count( $routes ),
			'tuyen_count'=> 'nha_xe' === $type ? count( $groups ) : 0,
		);
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private static function ctx() {
		return self::$context;
	}

	/**
	 * @return bool
	 */
	public static function is_landing_context() {
		return null !== self::ctx();
	}

	/**
	 * @param WP_Term|int $term Term.
	 * @return bool
	 */
	public static function term_has_active_routes( $term ) {
		if ( ! $term instanceof WP_Term ) {
			$term = get_term( absint( $term ) );
		}
		if ( ! $term instanceof WP_Term || is_wp_error( $term ) ) {
			return false;
		}

		if ( MTTF_Landing_Taxonomies::TAX_TUYEN === $term->taxonomy ) {
			return ! empty( MTTF_Landing_Query::get_routes_for_tuyen( $term ) );
		}

		if ( MTTF_Landing_Taxonomies::TAX_NHA_XE === $term->taxonomy ) {
			return ! empty( MTTF_Landing_Query::get_routes_for_nha_xe( $term ) );
		}

		return false;
	}

	/**
	 * @return array<int,array{loc:string,lastmod:string}>
	 */
	public static function get_indexable_landing_urls() {
		$urls = array();

		foreach ( array( MTTF_Landing_Taxonomies::TAX_TUYEN, MTTF_Landing_Taxonomies::TAX_NHA_XE ) as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( ! $term instanceof WP_Term || ! self::term_has_active_routes( $term ) ) {
					continue;
				}
				$link = get_term_link( $term );
				if ( is_wp_error( $link ) ) {
					continue;
				}
				$urls[] = array(
					'loc'     => $link,
					'lastmod' => gmdate( 'c' ),
				);
			}
		}

		return $urls;
	}

	/**
	 * @param int    $term_id Term ID.
	 * @param string $key     Meta key without prefix (title, description, h1, ...).
	 * @return string
	 */
	private static function get_term_seo_field( $term_id, $key ) {
		$val = trim( (string) get_term_meta( (int) $term_id, self::META_PREFIX . $key, true ) );
		if ( '' !== $val ) {
			return $val;
		}

		if ( self::has_rank_math() && in_array( $key, array( 'title', 'description' ), true ) ) {
			$rm_key = 'title' === $key ? 'rank_math_title' : 'rank_math_description';
			$rm_val = trim( (string) get_term_meta( (int) $term_id, $rm_key, true ) );
			if ( '' !== $rm_val ) {
				return $rm_val;
			}
		}

		return '';
	}

	/**
	 * @return string
	 */
	private static function get_term_name() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return '';
		}

		return trim( (string) $ctx['term']->name );
	}

	/**
	 * @param string $long  Preferred title.
	 * @param string $short Fallback title.
	 * @return string
	 */
	private static function trim_title( $long, $short ) {
		$long  = trim( $long );
		$short = trim( $short );

		if ( function_exists( 'mb_strlen' ) ) {
			return mb_strlen( $long ) <= 60 ? $long : $short;
		}

		return strlen( $long ) <= 60 ? $long : $short;
	}

	public static function get_seo_title() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return '';
		}

		$term = $ctx['term'];
		$custom = self::get_term_seo_field( (int) $term->term_id, 'title' );
		if ( '' !== $custom ) {
			return $custom;
		}

		$name = self::get_term_name();
		if ( 'tuyen' === $ctx['type'] ) {
			return self::trim_title(
				sprintf(
					/* translators: %s: route name */
					__( 'Đặt vé xe %s | So sánh nhà xe, giá vé, loại xe', 'minh-thang-transport-flow' ),
					$name
				),
				sprintf(
					/* translators: %s: route name */
					__( 'Đặt vé xe %s | Đặt Xe Việt Nam', 'minh-thang-transport-flow' ),
					$name
				)
			);
		}

		return self::trim_title(
			sprintf(
				/* translators: %s: operator name */
				__( 'Nhà xe %s | Tuyến xe, giá vé, đặt vé tư vấn', 'minh-thang-transport-flow' ),
				$name
			),
			sprintf(
				/* translators: %s: operator name */
				__( 'Đặt vé nhà xe %s | Đặt Xe Việt Nam', 'minh-thang-transport-flow' ),
				$name
			)
		);
	}

	public static function get_meta_description() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return '';
		}

		$term = $ctx['term'];
		$custom = self::get_term_seo_field( (int) $term->term_id, 'description' );
		if ( '' !== $custom ) {
			return $custom;
		}

		$name = self::get_term_name();
		if ( 'tuyen' === $ctx['type'] ) {
			return sprintf(
				/* translators: %s: route name */
				__( 'Tổng hợp các lựa chọn xe tuyến %s. Xem giá tham khảo, loại xe, tiện ích và để lại số điện thoại để được tư vấn chuyến phù hợp.', 'minh-thang-transport-flow' ),
				$name
			);
		}

		return sprintf(
			/* translators: %s: operator name */
			__( 'Tổng hợp các tuyến đang khai thác bởi nhà xe %s. Xem giá tham khảo, loại xe, tiện ích và để lại số điện thoại để được tư vấn chuyến phù hợp.', 'minh-thang-transport-flow' ),
			$name
		);
	}

	public static function get_h1() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return '';
		}

		$custom = self::get_term_seo_field( (int) $ctx['term']->term_id, 'h1' );
		if ( '' !== $custom ) {
			return $custom;
		}

		$name = self::get_term_name();
		if ( 'tuyen' === $ctx['type'] ) {
			return sprintf(
				/* translators: %s: route name */
				__( 'Xe %s', 'minh-thang-transport-flow' ),
				$name
			);
		}

		return sprintf(
			/* translators: %s: operator name */
			__( 'Nhà xe %s', 'minh-thang-transport-flow' ),
			$name
		);
	}

	public static function get_hero_description() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return '';
		}

		$custom = self::get_term_seo_field( (int) $ctx['term']->term_id, 'hero_description' );
		if ( '' !== $custom ) {
			return $custom;
		}

		$term_desc = trim( (string) $ctx['term']->description );
		if ( '' !== $term_desc ) {
			return wp_strip_all_tags( $term_desc );
		}

		$name = self::get_term_name();
		if ( 'tuyen' === $ctx['type'] ) {
			return sprintf(
				/* translators: %s: route name */
				__( 'Tổng hợp các lựa chọn xe %s theo giá, loại xe và tiện ích. So sánh nhanh các lựa chọn đang khai thác tuyến này và để lại số điện thoại để được tư vấn chuyến phù hợp.', 'minh-thang-transport-flow' ),
				$name
			);
		}

		return sprintf(
			/* translators: %s: operator name */
			__( 'Tổng hợp các tuyến đang được khai thác bởi %s. Khách có thể xem nhanh giá tham khảo, loại xe, tiện ích và để lại số điện thoại để được tư vấn chuyến phù hợp.', 'minh-thang-transport-flow' ),
			$name
		);
	}

	public static function get_services_section_title() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return '';
		}

		$name = self::get_term_name();
		if ( 'tuyen' === $ctx['type'] ) {
			return sprintf(
				/* translators: %s: route name */
				__( 'Các lựa chọn xe tuyến %s', 'minh-thang-transport-flow' ),
				$name
			);
		}

		return sprintf(
			/* translators: %s: operator name */
			__( 'Các tuyến của nhà xe %s', 'minh-thang-transport-flow' ),
			$name
		);
	}

	public static function get_og_title() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return '';
		}

		$custom = self::get_term_seo_field( (int) $ctx['term']->term_id, 'og_title' );
		if ( '' !== $custom ) {
			return $custom;
		}

		$name = self::get_term_name();
		if ( 'tuyen' === $ctx['type'] ) {
			return sprintf(
				/* translators: %s: route name */
				__( 'Đặt vé xe %s', 'minh-thang-transport-flow' ),
				$name
			);
		}

		return sprintf(
			/* translators: %s: operator name */
			__( 'Nhà xe %s', 'minh-thang-transport-flow' ),
			$name
		);
	}

	public static function get_og_description() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return '';
		}

		$custom = self::get_term_seo_field( (int) $ctx['term']->term_id, 'og_description' );
		if ( '' !== $custom ) {
			return $custom;
		}

		$name = self::get_term_name();
		if ( 'tuyen' === $ctx['type'] ) {
			return sprintf(
				/* translators: %s: route name */
				__( 'So sánh các lựa chọn xe tuyến %s, xem giá tham khảo và nhận tư vấn trước khi đặt.', 'minh-thang-transport-flow' ),
				$name
			);
		}

		return sprintf(
			/* translators: %s: operator name */
			__( 'Xem các tuyến của nhà xe %s, giá tham khảo, loại xe và nhận tư vấn trước khi đặt.', 'minh-thang-transport-flow' ),
			$name
		);
	}

	/**
	 * @return string
	 */
	public static function get_og_image_url() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return '';
		}

		$term_id = (int) $ctx['term']->term_id;
		$custom  = self::get_term_seo_field( $term_id, 'og_image' );
		if ( '' !== $custom ) {
			return esc_url( $custom );
		}

		if ( 'nha_xe' === $ctx['type'] ) {
			$logo_id = (int) get_term_meta( $term_id, '_mttf_nha_xe_logo', true );
			if ( $logo_id > 0 ) {
				$url = wp_get_attachment_image_url( $logo_id, 'large' );
				if ( is_string( $url ) && '' !== $url ) {
					return $url;
				}
			}
			$logo_url = trim( (string) get_term_meta( $term_id, '_mttf_nha_xe_logo_url', true ) );
			if ( '' !== $logo_url ) {
				return esc_url( $logo_url );
			}
		}

		$routes = is_array( $ctx['routes'] ) ? $ctx['routes'] : array();
		foreach ( $routes as $route ) {
			if ( ! $route instanceof WP_Post ) {
				continue;
			}
			$thumb_id = (int) get_post_thumbnail_id( $route->ID );
			if ( $thumb_id > 0 ) {
				$url = wp_get_attachment_image_url( $thumb_id, 'large' );
				if ( is_string( $url ) && '' !== $url ) {
					return $url;
				}
			}
		}

		return '';
	}

	public static function get_canonical_url() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return '';
		}

		$link = get_term_link( $ctx['term'] );
		if ( is_wp_error( $link ) ) {
			return '';
		}

		return user_trailingslashit( $link );
	}

	/**
	 * @return bool
	 */
	public static function should_index() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return true;
		}

		return (int) $ctx['route_count'] > 0;
	}

	/**
	 * @param array<string,string> $parts Title parts.
	 * @return array<string,string>
	 */
	public static function filter_document_title_parts( $parts ) {
		if ( ! self::is_landing_context() ) {
			return $parts;
		}

		$title = self::get_seo_title();
		if ( '' !== $title ) {
			$parts['title'] = $title;
		}

		return $parts;
	}

	/**
	 * @param string $title Title.
	 * @return string
	 */
	public static function filter_rank_math_title( $title ) {
		if ( ! self::is_landing_context() ) {
			return $title;
		}

		$custom = self::get_seo_title();

		return '' !== $custom ? $custom : $title;
	}

	/**
	 * @param string $description Description.
	 * @return string
	 */
	public static function filter_rank_math_description( $description ) {
		if ( ! self::is_landing_context() ) {
			return $description;
		}

		$custom = self::get_meta_description();

		return '' !== $custom ? $custom : $description;
	}

	/**
	 * @param string $canonical Canonical.
	 * @return string
	 */
	public static function filter_rank_math_canonical( $canonical ) {
		if ( ! self::is_landing_context() ) {
			return $canonical;
		}

		$url = self::get_canonical_url();

		return '' !== $url ? $url : $canonical;
	}

	/**
	 * @param array<string,string> $robots Robots.
	 * @return array<string,string>
	 */
	public static function filter_rank_math_robots( $robots ) {
		if ( ! self::is_landing_context() ) {
			return $robots;
		}

		if ( self::should_index() ) {
			$robots['index']  = 'index';
			$robots['follow'] = 'follow';
		} else {
			$robots['index']  = 'noindex';
			$robots['follow'] = 'follow';
		}

		return $robots;
	}

	/**
	 * @param string $title OG title.
	 * @return string
	 */
	public static function filter_og_title( $title ) {
		if ( ! self::is_landing_context() ) {
			return $title;
		}

		$custom = self::get_og_title();

		return '' !== $custom ? $custom : $title;
	}

	/**
	 * @param string $description OG description.
	 * @return string
	 */
	public static function filter_og_description( $description ) {
		if ( ! self::is_landing_context() ) {
			return $description;
		}

		$custom = self::get_og_description();

		return '' !== $custom ? $custom : $description;
	}

	/**
	 * @param string $image Image URL.
	 * @return string
	 */
	public static function filter_og_image( $image ) {
		if ( ! self::is_landing_context() ) {
			return $image;
		}

		$custom = self::get_og_image_url();

		return '' !== $custom ? $custom : $image;
	}

	/**
	 * @param bool    $indexable Indexable.
	 * @param WP_Term $term      Term.
	 * @return bool
	 */
	public static function filter_sitemap_term_indexable( $indexable, $term ) {
		if ( ! $term instanceof WP_Term ) {
			return $indexable;
		}

		if ( ! in_array( $term->taxonomy, array( MTTF_Landing_Taxonomies::TAX_TUYEN, MTTF_Landing_Taxonomies::TAX_NHA_XE ), true ) ) {
			return $indexable;
		}

		return self::term_has_active_routes( $term );
	}

	/**
	 * @param array<string,mixed> $args      Query args.
	 * @param string              $taxonomy Taxonomy.
	 * @return array<string,mixed>
	 */
	public static function filter_wp_sitemaps_taxonomies( $args, $taxonomy ) {
		if ( ! in_array( $taxonomy, array( MTTF_Landing_Taxonomies::TAX_TUYEN, MTTF_Landing_Taxonomies::TAX_NHA_XE ), true ) ) {
			return $args;
		}

		$active_ids = array();
		$terms      = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			$args['include'] = array( 0 );
			return $args;
		}

		foreach ( $terms as $term_id ) {
			$term = get_term( (int) $term_id, $taxonomy );
			if ( $term instanceof WP_Term && self::term_has_active_routes( $term ) ) {
				$active_ids[] = (int) $term_id;
			}
		}

		$args['include'] = ! empty( $active_ids ) ? $active_ids : array( 0 );

		return $args;
	}

	public static function output_fallback_meta() {
		if ( ! self::is_landing_context() || self::has_rank_math() ) {
			return;
		}

		$title       = self::get_seo_title();
		$description = self::get_meta_description();
		$canonical   = self::get_canonical_url();
		$og_title    = self::get_og_title();
		$og_desc     = self::get_og_description();
		$og_image    = self::get_og_image_url();
		$robots      = self::should_index() ? 'index, follow' : 'noindex, follow';

		if ( '' !== $description ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
		}

		printf( '<meta name="robots" content="%s" />' . "\n", esc_attr( $robots ) );

		if ( '' !== $canonical ) {
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
		}

		if ( '' !== $og_title ) {
			printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $og_title ) );
			printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $og_title ) );
		}

		if ( '' !== $og_desc ) {
			printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $og_desc ) );
			printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $og_desc ) );
		}

		if ( '' !== $canonical ) {
			printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $canonical ) );
		}

		printf( '<meta property="og:type" content="website" />' . "\n" );

		if ( '' !== $og_image ) {
			printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $og_image ) );
			printf( '<meta name="twitter:card" content="summary_large_image" />' . "\n" );
			printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $og_image ) );
		} else {
			printf( '<meta name="twitter:card" content="summary" />' . "\n" );
		}

		unset( $title );
	}

	public static function output_schema_json_ld() {
		if ( ! self::is_landing_context() ) {
			return;
		}

		$graph = self::build_schema_graph();
		if ( empty( $graph ) ) {
			return;
		}

		$payload = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private static function build_schema_graph() {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return array();
		}

		$canonical = self::get_canonical_url();
		$name      = self::get_term_name();
		$desc      = self::get_meta_description();
		$graph     = array();

		$graph[] = array(
			'@type'       => 'WebPage',
			'@id'         => $canonical . '#webpage',
			'url'         => $canonical,
			'name'        => 'tuyen' === $ctx['type']
				? sprintf(
					/* translators: %s: route name */
					__( 'Đặt vé xe %s', 'minh-thang-transport-flow' ),
					$name
				)
				: sprintf(
					/* translators: %s: operator name */
					__( 'Nhà xe %s', 'minh-thang-transport-flow' ),
					$name
				),
			'description' => $desc,
			'inLanguage'  => get_bloginfo( 'language' ),
			'isPartOf'    => array(
				'@type' => 'WebSite',
				'@id'   => home_url( '/#website' ),
				'url'   => home_url( '/' ),
				'name'  => get_bloginfo( 'name' ),
			),
		);

		$breadcrumb_items = array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => __( 'Trang chủ', 'minh-thang-transport-flow' ),
				'item'     => home_url( '/' ),
			),
		);

		if ( 'tuyen' === $ctx['type'] ) {
			$breadcrumb_items[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => __( 'Tuyến xe', 'minh-thang-transport-flow' ),
				'item'     => home_url( '/tuyen/' ),
			);
			$breadcrumb_items[] = array(
				'@type'    => 'ListItem',
				'position' => 3,
				'name'     => $name,
				'item'     => $canonical,
			);
		} else {
			$breadcrumb_items[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => __( 'Nhà xe', 'minh-thang-transport-flow' ),
				'item'     => home_url( '/nha-xe/' ),
			);
			$breadcrumb_items[] = array(
				'@type'    => 'ListItem',
				'position' => 3,
				'name'     => $name,
				'item'     => $canonical,
			);
		}

		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $canonical . '#breadcrumb',
			'itemListElement' => $breadcrumb_items,
		);

		$list_items = self::build_item_list_elements( $canonical );
		if ( ! empty( $list_items ) ) {
			$graph[] = array(
				'@type'           => 'ItemList',
				'@id'             => $canonical . '#itemlist',
				'name'            => self::get_services_section_title(),
				'numberOfItems'   => count( $list_items ),
				'itemListElement' => $list_items,
			);
		}

		$faq_schema = self::build_faq_schema( $canonical );
		if ( ! empty( $faq_schema ) ) {
			$graph[] = $faq_schema;
		}

		if ( 'nha_xe' === $ctx['type'] ) {
			$org = array(
				'@type'       => 'Organization',
				'@id'         => $canonical . '#organization',
				'name'        => $name,
				'url'         => $canonical,
				'description' => $desc,
			);
			$logo = self::get_og_image_url();
			if ( '' !== $logo ) {
				$org['logo'] = $logo;
			}
			$graph[] = $org;
		}

		return $graph;
	}

	/**
	 * @param string $canonical Canonical URL.
	 * @return array<int,array<string,mixed>>
	 */
	private static function build_item_list_elements( $canonical ) {
		$ctx = self::ctx();
		if ( ! $ctx ) {
			return array();
		}

		$elements = array();
		$position = 1;
		$routes   = is_array( $ctx['routes'] ) ? $ctx['routes'] : array();

		foreach ( $routes as $route ) {
			if ( ! $route instanceof WP_Post ) {
				continue;
			}

			$image = get_the_post_thumbnail_url( $route->ID, 'mttf-card' );
			$item  = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'item'     => array(
					'@type'       => 'Thing',
					'name'        => $route->post_title,
					'url'         => $canonical . '#mttf-route-' . (int) $route->ID,
					'description' => wp_strip_all_tags( (string) get_post_meta( $route->ID, '_mttf_car_type', true ) ),
				),
			);

			if ( is_string( $image ) && '' !== $image ) {
				$item['item']['image'] = $image;
			}

			$elements[] = $item;
			++$position;
		}

		return $elements;
	}

	/**
	 * @param string $canonical Canonical URL.
	 * @return array<string,mixed>|null
	 */
	private static function build_faq_schema( $canonical ) {
		$ctx = self::ctx();
		if ( ! $ctx || empty( $ctx['faq'] ) || ! is_array( $ctx['faq'] ) ) {
			return null;
		}

		$entities = array();
		foreach ( $ctx['faq'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$q = trim( (string) ( $row['q'] ?? '' ) );
			$a = trim( (string) ( $row['a'] ?? '' ) );
			if ( '' === $q || '' === $a ) {
				continue;
			}
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $q,
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $a,
				),
			);
		}

		if ( empty( $entities ) ) {
			return null;
		}

		return array(
			'@type'      => 'FAQPage',
			'@id'        => $canonical . '#faq',
			'mainEntity' => $entities,
		);
	}
}
