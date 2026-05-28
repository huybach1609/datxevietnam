<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performance: conditional assets, query cache, hero preload, image sizes.
 */
class MTTF_Performance {

	const CACHE_TTL = 600;

	/** @var array<string, WP_Post[]> */
	private static $routes_request_cache = array();

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_image_sizes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_public_assets' ), 15 );
		add_action( 'wp_head', array( __CLASS__, 'preload_lcp_resource' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'preload_stylesheets' ), 2 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'defer_public_scripts' ), 10, 3 );

		add_action( 'save_post_tuyen_xe', array( __CLASS__, 'flush_caches' ), 20 );
		add_action( 'delete_post', array( __CLASS__, 'maybe_flush_on_delete' ), 20 );
		add_action( 'edited_mttf_tuyen', array( __CLASS__, 'flush_caches' ) );
		add_action( 'created_mttf_tuyen', array( __CLASS__, 'flush_caches' ) );
		add_action( 'delete_mttf_tuyen', array( __CLASS__, 'flush_caches' ) );
		add_action( 'edited_mttf_nha_xe', array( __CLASS__, 'flush_caches' ) );
		add_action( 'created_mttf_nha_xe', array( __CLASS__, 'flush_caches' ) );
		add_action( 'delete_mttf_nha_xe', array( __CLASS__, 'flush_caches' ) );
	}

	public static function register_image_sizes() {
		add_image_size( 'mttf-card', 600, 400, true );
		add_image_size( 'mttf-card-sm', 400, 267, true );
	}

	public static function is_landing_tax_page() {
		return is_tax( array( MTTF_Landing_Taxonomies::TAX_TUYEN, MTTF_Landing_Taxonomies::TAX_NHA_XE ) );
	}

	public static function should_enqueue_hub_assets() {
		if ( self::is_landing_tax_page() ) {
			return true;
		}

		if ( is_front_page() ) {
			return true;
		}

		if ( is_singular() ) {
			$post = get_post();
			if ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'mttf_hub' ) ) {
				return true;
			}
		}

		return false;
	}

	public static function enqueue_public_assets() {
		if ( ! self::should_enqueue_hub_assets() ) {
			return;
		}

		$context = self::is_landing_tax_page() ? 'landing' : 'hub';
		MTTF_Shortcode::enqueue_frontend_assets( $context );
	}

	/**
	 * Preload LCP: homepage hero bg; landing = first card image (tuyến/nhà xe).
	 */
	public static function preload_lcp_resource() {
		if ( is_front_page() ) {
			$url = trim( (string) MTTF_Settings::get( 'hero_background_url', '' ) );
			if ( '' !== $url ) {
				self::print_preload_link( $url );
			}
			return;
		}

		if ( ! self::is_landing_tax_page() ) {
			return;
		}

		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		// Trang tuyến: LCP thường là H1 — không preload ảnh card (tránh tranh băng thông).
		if ( MTTF_Landing_Taxonomies::TAX_TUYEN === $term->taxonomy ) {
			return;
		}

		$routes = self::get_routes_for_nha_xe( $term );

		if ( empty( $routes[0] ) || ! $routes[0] instanceof WP_Post ) {
			return;
		}

		$thumb_id = (int) get_post_thumbnail_id( $routes[0]->ID );
		if ( $thumb_id <= 0 ) {
			$url = get_the_post_thumbnail_url( $routes[0]->ID, 'mttf-card-sm' );
			if ( is_string( $url ) && '' !== $url ) {
				self::print_preload_link( $url );
			}
			return;
		}

		$src    = wp_get_attachment_image_src( $thumb_id, 'mttf-card-sm' );
		$srcset = wp_get_attachment_image_srcset( $thumb_id, 'mttf-card-sm' );
		$sizes  = '(max-width: 767px) 100vw, 33vw';

		if ( ! is_array( $src ) || empty( $src[0] ) ) {
			return;
		}

		echo '<link rel="preload" as="image" href="' . esc_url( $src[0] ) . '" fetchpriority="high"';
		if ( is_string( $srcset ) && '' !== $srcset ) {
			echo ' imagesrcset="' . esc_attr( $srcset ) . '" imagesizes="' . esc_attr( $sizes ) . '"';
		}
		echo " />\n";
	}

	/**
	 * Preload plugin CSS so layout is stable early (avoids CLS from late styles).
	 */
	public static function preload_stylesheets() {
		if ( ! self::is_landing_tax_page() ) {
			return;
		}

		$styles = array(
			MTTF_PATH . 'assets/css/frontend.css' => MTTF_URL . 'assets/css/frontend.css',
			MTTF_PATH . 'assets/css/landing.css'  => MTTF_URL . 'assets/css/landing.css',
		);

		foreach ( $styles as $path => $url ) {
			$ver = file_exists( $path ) ? (string) filemtime( $path ) : MTTF_VERSION;
			printf(
				'<link rel="preload" href="%s?ver=%s" as="style" />' . "\n",
				esc_url( $url ),
				esc_attr( $ver )
			);
		}
	}

	/**
	 * @param string $url Image URL.
	 */
	private static function print_preload_link( $url ) {
		printf(
			'<link rel="preload" as="image" href="%s" fetchpriority="high" />' . "\n",
			esc_url( $url )
		);
	}

	/**
	 * @param string $tag    Script tag.
	 * @param string $handle Handle.
	 * @param string $src    Src.
	 * @return string
	 */
	public static function defer_public_scripts( $tag, $handle, $src ) {
		unset( $src );
		$defer_handles = array( 'mttf-frontend', 'mttf-landing-lead', 'mttf-landing-nha-xe' );
		if ( ! in_array( $handle, $defer_handles, true ) ) {
			return $tag;
		}
		if ( false !== strpos( $tag, ' defer' ) ) {
			return $tag;
		}

		return str_replace( ' src', ' defer src', $tag );
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public static function maybe_flush_on_delete( $post_id ) {
		if ( 'tuyen_xe' === get_post_type( $post_id ) ) {
			self::flush_caches();
		}
	}

	public static function flush_caches() {
		self::$routes_request_cache = array();
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_mttf_routes_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_mttf_routes_' ) . '%'
			)
		);
	}

	/**
	 * @param string $cache_key Cache key suffix.
	 * @param callable $callback  Returns WP_Post[].
	 * @return WP_Post[]
	 */
	private static function remember_routes( $cache_key, callable $callback ) {
		if ( isset( self::$routes_request_cache[ $cache_key ] ) ) {
			return self::$routes_request_cache[ $cache_key ];
		}

		$transient_key = 'mttf_routes_' . $cache_key;
		$cached        = get_transient( $transient_key );
		if ( is_array( $cached ) ) {
			$routes = array();
			foreach ( $cached as $post_id ) {
				$post = get_post( (int) $post_id );
				if ( $post instanceof WP_Post ) {
					$routes[] = $post;
				}
			}
			self::$routes_request_cache[ $cache_key ] = $routes;
			self::prime_route_meta_cache( $routes );
			return $routes;
		}

		$routes = $callback();
		if ( ! is_array( $routes ) ) {
			$routes = array();
		}

		$post_ids = array();
		foreach ( $routes as $route ) {
			if ( $route instanceof WP_Post ) {
				$post_ids[] = (int) $route->ID;
			}
		}

		set_transient( $transient_key, $post_ids, self::CACHE_TTL );
		self::$routes_request_cache[ $cache_key ] = $routes;
		self::prime_route_meta_cache( $routes );

		return $routes;
	}

	/**
	 * @param WP_Post[] $routes Routes.
	 */
	public static function prime_route_meta_cache( array $routes ) {
		$ids = array();
		foreach ( $routes as $route ) {
			if ( $route instanceof WP_Post ) {
				$ids[] = (int) $route->ID;
			}
		}
		if ( empty( $ids ) ) {
			return;
		}

		update_meta_cache( 'post', $ids );

		// update_post_thumbnail_cache() requires WP_Query, not post ID array.
		$thumb_query = new WP_Query(
			array(
				'post_type'              => 'tuyen_xe',
				'post__in'               => $ids,
				'posts_per_page'         => count( $ids ),
				'orderby'                => 'post__in',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		if ( ! empty( $thumb_query->posts ) ) {
			update_post_thumbnail_cache( $thumb_query );
		}
	}

	/**
	 * @param WP_Post[] $routes Routes.
	 */
	public static function prime_route_term_cache( array $routes ) {
		$ids = array();
		foreach ( $routes as $route ) {
			if ( $route instanceof WP_Post ) {
				$ids[] = (int) $route->ID;
			}
		}
		if ( ! empty( $ids ) ) {
			update_object_term_cache( $ids, 'tuyen_xe' );
		}
	}

	/**
	 * @param WP_Term|int $term Term.
	 * @return WP_Post[]
	 */
	public static function get_routes_for_tuyen( $term ) {
		$term = self::resolve_term( $term, MTTF_Landing_Taxonomies::TAX_TUYEN );
		if ( ! $term ) {
			return array();
		}

		$key = 'tuyen_' . (int) $term->term_id;

		return self::remember_routes(
			$key,
			static function () use ( $term ) {
				return MTTF_Landing_Query::query_routes_for_tax(
					array(
						array(
							'taxonomy' => MTTF_Landing_Taxonomies::TAX_TUYEN,
							'field'    => 'term_id',
							'terms'    => array( (int) $term->term_id ),
						),
					)
				);
			}
		);
	}

	/**
	 * @param WP_Term|int $term Term.
	 * @return WP_Post[]
	 */
	public static function get_routes_for_nha_xe( $term ) {
		$term = self::resolve_term( $term, MTTF_Landing_Taxonomies::TAX_NHA_XE );
		if ( ! $term ) {
			return array();
		}

		$key = 'nha_xe_' . (int) $term->term_id;

		return self::remember_routes(
			$key,
			static function () use ( $term ) {
				return MTTF_Landing_Query::query_routes_for_tax(
					array(
						array(
							'taxonomy' => MTTF_Landing_Taxonomies::TAX_NHA_XE,
							'field'    => 'term_id',
							'terms'    => array( (int) $term->term_id ),
						),
					)
				);
			}
		);
	}

	/**
	 * @param WP_Term|int $term Term.
	 * @return WP_Term|null
	 */
	private static function resolve_term( $term, $taxonomy ) {
		if ( $term instanceof WP_Term ) {
			return $term->taxonomy === $taxonomy ? $term : null;
		}

		$term = get_term( absint( $term ), $taxonomy );

		if ( ! $term instanceof WP_Term || is_wp_error( $term ) ) {
			return null;
		}

		return $term;
	}
}
