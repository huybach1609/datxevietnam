<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MTTF_Shortcode {
	public static function init() {
		add_shortcode( 'mttf_hub', array( __CLASS__, 'render_hub' ) );
		add_shortcode( 'mttf_route', array( __CLASS__, 'render_route_directory' ) );
		add_shortcode( 'mttf_route_directory', array( __CLASS__, 'render_route_directory' ) );
		add_shortcode( 'mttf_route_links', array( __CLASS__, 'render_route_links' ) );
	}

	/**
	 * Danh sách tuyến dạng văn bản + link (sidebar / footer).
	 * CPT không public → link là URL trang hub + ?route=slug .
	 *
	 * Thuộc tính:
	 * - hub_url: URL trang có [mttf_hub] (mặc định home). Có thể path tương đối, ví dụ: dat-xe
	 * - region: bac|nam|trung để chỉ hiện một miền (tuỳ chọn).
	 * - limit: số tuyến tối đa (mặc định 200, tối đa 500).
	 * - class: class trên <ul> (mặc định mttf-route-links).
	 * - title: nhãn <nav aria-label=""> (tuỳ chọn).
	 * - Tự enqueue <code>assets/css/route-links.css</code>: tiêu đề miền + hover link (mũi tên, lệch trái) giống footer site.
	 * - Theo miền giống trang chủ; thứ tự trong từng miền = thống kê search (<code>_mttf_search_count</code>,
	 *   <code>_mttf_last_searched_at</code>) rồi ưu tiên tay, như hub. Có <code>?route=slug</code> trên URL thì
	 *   ưu tuyến/miền tương ứng (giống hub).
	 */
	public static function render_route_links( $atts ) {
		self::enqueue_route_links_styles();

		$atts = shortcode_atts(
			array(
				'hub_url' => '',
				'region'  => '',
				'limit'   => '200',
				'class'   => 'mttf-route-links',
				'title'   => '',
			),
			is_array( $atts ) ? $atts : array(),
			'mttf_route_links'
		);

		$region = strtolower( sanitize_text_field( (string) $atts['region'] ) );
		if ( '' !== $region && ! in_array( $region, array( 'bac', 'nam', 'trung' ), true ) ) {
			$region = '';
		}

		$limit = (int) $atts['limit'];
		if ( $limit < 1 ) {
			$limit = 200;
		}
		$limit = min( 500, $limit );

		$route_boost = isset( $_GET['route'] ) ? sanitize_title( wp_unslash( $_GET['route'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$fetch_cap = min( 500, max( $limit, 200 ) );

		$routes = self::fetch_routes( $region, $fetch_cap );
		$routes = self::sort_routes( $routes, $route_boost );

		if ( empty( $routes ) ) {
			return '';
		}

		$hub_base = self::normalize_hub_page_url_for_links( $atts['hub_url'] );
		$ul_class = sanitize_html_class( (string) $atts['class'] );
		if ( '' === $ul_class ) {
			$ul_class = 'mttf-route-links';
		}

		$aria = trim( (string) sanitize_text_field( $atts['title'] ) );

		$pairs = self::flatten_grouped_route_links(
			self::group_by_region( $routes, $route_boost ),
			$limit
		);

		if ( empty( $pairs ) ) {
			return '';
		}

		ob_start();
		echo '<nav class="mttf-route-links-nav"' . ( '' !== $aria ? ' aria-label="' . esc_attr( $aria ) . '"' : ' aria-label="' . esc_attr__( 'Liên kết nhanh tuyến', 'minh-thang-transport-flow' ) . '"' ) . '>';

		if ( '' !== $region ) {
			echo '<ul class="' . esc_attr( $ul_class ) . '">';
			foreach ( $pairs as $row ) {
				self::echo_route_link_li( $row['post'], $hub_base );
			}
			echo '</ul>';
		} else {
			$open_region = null;
			foreach ( $pairs as $row ) {
				$rkey = (string) $row['region'];
				if ( $open_region !== $rkey ) {
					if ( null !== $open_region ) {
						echo '</ul></section>';
					}
					echo '<section class="mttf-route-links-region" data-mttf-region="' . esc_attr( $rkey ) . '">';
					echo '<h4 class="mttf-route-links-region__title">' . esc_html( self::get_route_links_region_heading( $rkey ) ) . '</h4>';
					echo '<ul class="' . esc_attr( $ul_class ) . '">';
					$open_region = $rkey;
				}
				self::echo_route_link_li( $row['post'], $hub_base );
			}
			echo '</ul></section>';
		}

		echo '</nav>';

		return (string) ob_get_clean();
	}

	/**
	 * Duyệt miền theo thứ tự đã nhóm (giống hub), ghép đủ $limit phần tử.
	 *
	 * @param array<string, array<int,\WP_Post>> $groups Kết quả group_by_region.
	 * @param int                                  $limit  Số dòng tối đa.
	 * @return array<int, array{region:string, post:\WP_Post}>
	 */
	private static function flatten_grouped_route_links( array $groups, $limit ) {
		$limit = (int) $limit;
		if ( $limit < 1 ) {
			return array();
		}

		$out   = array();
		$taken = 0;

		foreach ( $groups as $region_key => $region_posts ) {
			foreach ( $region_posts as $route ) {
				if ( $taken >= $limit ) {
					break 2;
				}

				$out[] = array(
					'region' => (string) $region_key,
					'post'   => $route,
				);

				++$taken;
			}
		}

		return $out;
	}

	/**
	 * @param \WP_Post $route Route post.
	 * @param string   $hub_base URL hub (trailing slash).
	 */
	private static function echo_route_link_li( $route, $hub_base ) {
		$slug = (string) get_post_meta( $route->ID, '_mttf_route_slug', true );
		if ( '' === $slug ) {
			$slug = sanitize_title( (string) $route->post_name );
		}
		if ( '' === $slug ) {
			return;
		}

		$url = add_query_arg( 'route', rawurlencode( $slug ), $hub_base );

		echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $route ) ) . '</a></li>';
	}

	/**
	 * URL trang chứa hub (relative path hoặc full URL).
	 */
	private static function normalize_hub_page_url_for_links( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			$queried_id = get_queried_object_id();
			if ( $queried_id > 0 ) {
				$permalink = get_permalink( $queried_id );
				if ( $permalink ) {
					return trailingslashit( $permalink );
				}
			}

			return trailingslashit( home_url( '/' ) );
		}

		if ( preg_match( '#^https?://#i', $raw ) ) {
			$url = wp_http_validate_url( $raw );

			return $url ? trailingslashit( $url ) : trailingslashit( home_url( '/' ) );
		}

		$path   = preg_replace( '#^/+|/+$#', '', $raw );
		$tailed = '/' . $path . '/';

		return trailingslashit( home_url( $tailed ) );
	}

	public static function render_route_directory( $atts ) {
		$atts = shortcode_atts(
			array(
				'region'   => '',
				'base_url' => '',
			),
			$atts,
			'mttf_route'
		);

		$base_url       = self::get_directory_base_url( (string) $atts['base_url'] );
		$route_slug     = isset( $_GET['route'] ) ? sanitize_title( wp_unslash( $_GET['route'] ) ) : '';
		$operator_slug  = isset( $_GET['operator'] ) ? sanitize_title( wp_unslash( $_GET['operator'] ) ) : '';
		$search_keyword = isset( $_GET['mttf_q'] ) ? sanitize_text_field( wp_unslash( $_GET['mttf_q'] ) ) : '';
		$region         = sanitize_text_field( (string) $atts['region'] );

		if ( '' !== $route_slug ) {
			self::enqueue_assets( 'route-route-detail' );

			$route = self::find_route_by_slug( $route_slug );
			if ( ! $route ) {
				return self::render_directory_not_found(
					'Không tìm thấy tuyến',
					'Tuyến bạn đang tìm hiện chưa có dữ liệu hiển thị.',
					$base_url
				);
			}

			$operator_rows = class_exists( 'MTTF_Route_Operators' ) ? MTTF_Route_Operators::get_route_operator_rows( $route->ID, true ) : array();

			return self::render_route_template(
				'route-detail',
				array(
					'base_url'      => $base_url,
					'operator_rows' => $operator_rows,
					'route'         => $route,
				)
			);
		}

		if ( '' !== $operator_slug ) {
			self::enqueue_assets( 'route-operator-detail' );

			$operator = self::find_operator_by_slug( $operator_slug );
			if ( ! $operator ) {
				return self::render_directory_not_found(
					'Không tìm thấy nhà xe',
					'Nhà xe bạn đang tìm hiện chưa có dữ liệu hiển thị.',
					$base_url
				);
			}

			$routes = class_exists( 'MTTF_Route_Operators' ) ? MTTF_Route_Operators::get_operator_routes( $operator->ID, true ) : array();
			$routes = self::filter_routes_by_region( $routes, $region );
			$routes = self::filter_routes( $routes, $search_keyword );
			$routes = self::sort_routes( $routes, '' );

			return self::render_route_template(
				'operator-detail',
				array(
					'base_url'       => $base_url,
					'operator'       => $operator,
					'routes'         => $routes,
					'search_keyword' => $search_keyword,
				)
			);
		}

		self::enqueue_assets( 'route-directory' );

		$routes = self::fetch_routes( $region, 500 );
		$routes = self::filter_routes( $routes, $search_keyword );
		$routes = self::sort_routes( $routes, '' );
		$car_types = self::collect_car_types( $routes );
		$operators = self::get_directory_operators();

		return self::render_route_template(
			'directory',
			array(
				'base_url'       => $base_url,
				'car_types'      => $car_types,
				'operators'      => $operators,
				'routes'         => $routes,
				'search_keyword' => $search_keyword,
			)
		);
	}

	public static function render_hub( $atts ) {
		$atts = shortcode_atts(
			array(
				'region' => '',
			),
			$atts,
			'mttf_hub'
		);

		self::enqueue_assets();

		$search_keyword = isset( $_GET['mttf_q'] ) ? sanitize_text_field( wp_unslash( $_GET['mttf_q'] ) ) : '';
		$route_priority = isset( $_GET['route'] ) ? sanitize_title( wp_unslash( $_GET['route'] ) ) : '';

		$routes = self::fetch_routes( $atts['region'], 200 );
		$routes = self::filter_routes( $routes, $search_keyword );
		$routes = self::sort_routes( $routes, $route_priority );
		$car_types = self::collect_car_types( $routes );
		$hero_title_1 = (string) MTTF_Settings::get( 'hero_title_1', 'Nền tảng Đặt Vé Limousine toàn Việt Nam' );
		$hero_title_2 = (string) MTTF_Settings::get( 'hero_title_2', 'Nhanh chóng. Minh bạch. Cam kết có chỗ.' );
		$hero_title_3 = (string) MTTF_Settings::get( 'hero_title_3', 'Nhập tỉnh hoặc thành phố để lọc nhanh' );
		$hero_bg_url  = (string) MTTF_Settings::get( 'hero_background_url', 'https://images.pexels.com/photos/120049/pexels-photo-120049.jpeg' );
		$hero_style   = '' !== $hero_bg_url ? ' style="background-image: url(\'' . esc_url( $hero_bg_url ) . '\');"' : '';
		$hero_title_1_html = str_replace(
			'Đặt Vé Limousine',
			'<span class="mttf-title-highlight">Đặt Vé Xe</span>',
			esc_html( $hero_title_1 )
		);
		$hero_title_1_html = str_replace(
			' toàn Việt Nam',
			' <span class="mttf-title-break">toàn Việt Nam</span>',
			$hero_title_1_html
		);

		ob_start();
		?>
		<div class="mttf" data-route-priority="<?php echo esc_attr( $route_priority ); ?>">
			<div class="mttf-hero" aria-label="Giới thiệu đặt vé"<?php echo $hero_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div class="mttf-hero__content">
					<div class="mttf-intro">
						<h1 class="mttf-intro__line mttf-intro__line--1"><?php echo wp_kses( $hero_title_1_html, array( 'span' => array( 'class' => array() ) ) ); ?></h1>
						<p class="mttf-intro__line mttf-intro__line--2"><?php echo esc_html( $hero_title_2 ); ?></p>
						<p class="mttf-intro__line mttf-intro__line--3"><?php echo esc_html( $hero_title_3 ); ?></p>
					</div>
					<form method="get" class="mttf-search" action="" autocomplete="off">
						<div class="mttf-search__input-wrap">
							<span class="mttf-search__icon" aria-hidden="true"><?php echo file_get_contents( MTTF_PATH . 'assets/icons/search.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<input id="mttf-search-input" type="text" name="mttf_q" value="<?php echo esc_attr( $search_keyword ); ?>" placeholder="vé tuyến tràng an" />
						</div>
						<div class="mttf-suggest" hidden>
							<ul class="mttf-suggest__list" role="listbox" aria-label="Gợi ý tuyến"></ul>
						</div>
					</form>
				</div>
			</div>
			<?php if ( MTTF_Activity_Pings::is_enabled() ) : ?>
				<div class="mttf-activity" id="mttf-activity" role="status" aria-live="polite" aria-hidden="true">
					<span class="mttf-activity__pulse" aria-hidden="true"></span>
					<div class="mttf-activity__body">
						<span class="mttf-activity__badge">Hoạt động</span>
						<p class="mttf-activity__text" data-mttf-activity-text>Gần đây có khách đặt chỗ các tuyến hot.</p>
					</div>
				</div>
			<?php endif; ?>
			<div class="mttf-filters-panel">
				<div class="mttf-filters-group mttf-quick-filters" role="group" aria-label="Lọc nhanh theo miền">
					<button type="button" class="mttf-chip is-active" data-region-filter="bac">Miền Bắc</button>
					<button type="button" class="mttf-chip" data-region-filter="nam">Miền Nam</button>
					<button type="button" class="mttf-chip" data-region-filter="trung">Miền Trung</button>
				</div>
				<?php if ( ! empty( $car_types ) ) : ?>
					<div class="mttf-filters-group mttf-car-filters" role="group" aria-label="Lọc theo loại xe">
						<?php foreach ( $car_types as $car_type ) : ?>
							<button type="button" class="mttf-chip" data-car-filter="<?php echo esc_attr( self::normalize_car_type_key( $car_type ) ); ?>">
								<?php echo esc_html( $car_type ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( empty( $routes ) ) : ?>
				<?php echo self::render_fallback_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<?php echo self::render_route_sections( $routes, $route_priority, 'hub', '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<?php echo self::render_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	private static function fetch_routes( $region, $posts_per_page = 200 ) {
		$ppp = absint( $posts_per_page );
		if ( $ppp < 1 ) {
			$ppp = 200;
		}
		if ( $ppp > 500 ) {
			$ppp = 500;
		}

		$args = array(
			'post_type'      => 'tuyen_xe',
			'post_status'    => 'publish',
			'posts_per_page' => $ppp,
			'meta_key'       => '_mttf_priority',
			'orderby'        => array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			),
			'meta_query'     => array(
				array(
					'key'   => '_mttf_is_active',
					'value' => 1,
				),
			),
		);

		if ( ! empty( $region ) ) {
			$args['meta_query'][] = array(
				'key'   => '_mttf_hub_region',
				'value' => sanitize_text_field( $region ),
			);
		}

		return get_posts( $args );
	}

	private static function filter_routes( $routes, $search_keyword ) {
		if ( '' === $search_keyword ) {
			return $routes;
		}

		$needle = self::normalize_text( $search_keyword );

		return array_values(
			array_filter(
				$routes,
				static function( $route ) use ( $needle ) {
					$title = self::normalize_text( $route->post_title );
					$keywords = self::normalize_text( (string) get_post_meta( $route->ID, '_mttf_search_keywords', true ) );

					return false !== strpos( $title, $needle ) || false !== strpos( $keywords, $needle );
				}
			)
		);
	}

	private static function sort_routes( $routes, $route_priority ) {
		usort(
			$routes,
			static function( $a, $b ) use ( $route_priority ) {
				$slug_a = (string) get_post_meta( $a->ID, '_mttf_route_slug', true );
				$slug_b = (string) get_post_meta( $b->ID, '_mttf_route_slug', true );
				$search_count_a = (int) get_post_meta( $a->ID, '_mttf_search_count', true );
				$search_count_b = (int) get_post_meta( $b->ID, '_mttf_search_count', true );
				$manual_priority_a = (int) get_post_meta( $a->ID, '_mttf_priority', true );
				$manual_priority_b = (int) get_post_meta( $b->ID, '_mttf_priority', true );
				$last_search_a = (int) get_post_meta( $a->ID, '_mttf_last_searched_at', true );
				$last_search_b = (int) get_post_meta( $b->ID, '_mttf_last_searched_at', true );

				if ( '' !== $route_priority && $slug_a === $route_priority && $slug_b !== $route_priority ) {
					return -1;
				}

				if ( '' !== $route_priority && $slug_b === $route_priority && $slug_a !== $route_priority ) {
					return 1;
				}

				if ( $search_count_a !== $search_count_b ) {
					return $search_count_b <=> $search_count_a;
				}

				if ( $last_search_a !== $last_search_b ) {
					return $last_search_b <=> $last_search_a;
				}

				if ( $manual_priority_a !== $manual_priority_b ) {
					return $manual_priority_b <=> $manual_priority_a;
				}

				return strtotime( $b->post_date_gmt ) <=> strtotime( $a->post_date_gmt );
			}
		);

		return $routes;
	}

	private static function group_by_region( $routes, $route_priority = '' ) {
		$groups = array();
		$region_order = array( 'bac', 'nam', 'trung', 'khac' );
		$priority_region = self::get_priority_region( $routes, $route_priority );

		foreach ( $routes as $route ) {
			$region = (string) get_post_meta( $route->ID, '_mttf_hub_region', true );
			if ( '' === $region ) {
				$region = 'khac';
			}
			if ( ! isset( $groups[ $region ] ) ) {
				$groups[ $region ] = array();
			}
			$groups[ $region ][] = $route;
		}

		uksort(
			$groups,
			static function( $a, $b ) use ( $region_order, $priority_region ) {
				if ( '' !== $priority_region ) {
					if ( $a === $priority_region && $b !== $priority_region ) {
						return -1;
					}

					if ( $b === $priority_region && $a !== $priority_region ) {
						return 1;
					}
				}

				$pos_a = array_search( $a, $region_order, true );
				$pos_b = array_search( $b, $region_order, true );
				$pos_a = false === $pos_a ? 999 : $pos_a;
				$pos_b = false === $pos_b ? 999 : $pos_b;

				return $pos_a <=> $pos_b;
			}
		);

		return $groups;
	}

	private static function get_priority_region( $routes, $route_priority ) {
		if ( '' === $route_priority ) {
			return '';
		}

		foreach ( $routes as $route ) {
			$route_slug = (string) get_post_meta( $route->ID, '_mttf_route_slug', true );
			if ( $route_slug !== $route_priority ) {
				continue;
			}

			$region = (string) get_post_meta( $route->ID, '_mttf_hub_region', true );
			return '' !== $region ? $region : 'khac';
		}

		return '';
	}

	private static function collect_car_types( $routes ) {
		$types = array();

		foreach ( $routes as $route ) {
			$value = trim( (string) get_post_meta( $route->ID, '_mttf_car_type', true ) );
			if ( '' === $value ) {
				continue;
			}

			$key = self::normalize_car_type_key( $value );
			if ( '' === $key ) {
				continue;
			}

			$types[ $key ] = $value;
		}

		return array_values( $types );
	}

	private static function render_route_sections( $routes, $route_priority, $context, $base_url ) {
		ob_start();

		foreach ( self::group_by_region( $routes, $route_priority ) as $region => $region_routes ) {
			echo '<section class="mttf-hub" data-mttf-region="' . esc_attr( $region ) . '">';
			echo '<h3 class="mttf-hub__title">' . esc_html( self::get_region_title( $region ) ) . '</h3>';
			echo '<div class="mttf-hub__track">';

			foreach ( $region_routes as $route ) {
				echo self::render_card(
					$route,
					array(
						'context'    => $context,
						'detail_url' => 'directory' === $context ? self::build_route_directory_url( $base_url, $route ) : '',
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '</div></section>';
		}

		return (string) ob_get_clean();
	}

	private static function render_directory_route_sections( $routes, $route_priority, $base_url ) {
		ob_start();

		foreach ( self::group_by_region( $routes, $route_priority ) as $region => $region_routes ) {
			echo '<section class="mttf-hub mttf-route-directory-group" data-mttf-region="' . esc_attr( $region ) . '">';
			echo '<h3 class="mttf-hub__title">' . esc_html( self::get_region_title( $region ) ) . '</h3>';
			echo '<div class="mttf-hub__track mttf-route-directory-grid">';

			foreach ( $region_routes as $route ) {
				echo self::render_route_discovery_card( $route, $base_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '</div></section>';
		}

		return (string) ob_get_clean();
	}

	private static function render_operator_route_sections( $routes, $route_priority, $base_url, $operator = null ) {
		ob_start();

		foreach ( self::group_by_region( $routes, $route_priority ) as $region => $region_routes ) {
			echo '<section class="mttf-hub mttf-operator-route-group" data-mttf-region="' . esc_attr( $region ) . '">';
			echo '<div class="mttf-hub__track mttf-operator-route-grid">';

			foreach ( $region_routes as $route ) {
				echo self::render_operator_route_card( $route, $base_url, $operator ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '</div></section>';
		}

		return (string) ob_get_clean();
	}

	private static function normalize_car_type_key( $value ) {
		return sanitize_title( (string) $value );
	}

	private static function render_card( $route, array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'context'    => 'hub',
				'detail_url' => '',
			)
		);
		$post_id       = $route->ID;
		$route_slug    = (string) get_post_meta( $post_id, '_mttf_route_slug', true );
		$price_from    = (int) get_post_meta( $post_id, '_mttf_price_from', true );
		$rating_score  = (string) get_post_meta( $post_id, '_mttf_rating_score', true );
		$review_count  = (string) get_post_meta( $post_id, '_mttf_review_count', true );
		$contact_count = (string) get_post_meta( $post_id, '_mttf_contact_count', true );
		$trip_frequency = (string) get_post_meta( $post_id, '_mttf_trip_frequency', true );
		$car_type      = (string) get_post_meta( $post_id, '_mttf_car_type', true );
		$features      = array_slice( (array) get_post_meta( $post_id, '_mttf_route_features', true ), 0, 3 );
		$hot_badges    = (array) get_post_meta( $post_id, '_mttf_hot_badges', true );
		$search_count  = (int) get_post_meta( $post_id, '_mttf_search_count', true );
		$hotline       = (string) get_post_meta( $post_id, '_mttf_hotline_number', true );
		$zalo_link     = (string) get_post_meta( $post_id, '_mttf_zalo_link', true );
		$region        = (string) get_post_meta( $post_id, '_mttf_hub_region', true );
		$image_url     = get_the_post_thumbnail_url( $post_id, 'medium_large' );
		$gallery_ids   = self::parse_gallery_ids( (string) get_post_meta( $post_id, '_mttf_gallery_ids', true ) );
		$slide_interval = max( 1, (int) get_post_meta( $post_id, '_mttf_slider_interval', true ) );
		$slide_images  = array();
		$call_icon_url = (string) MTTF_Settings::get( 'call_icon_url', '' );
		$zalo_icon_url = (string) MTTF_Settings::get( 'zalo_icon_url', '' );
		$detail_url    = (string) $args['detail_url'];
		$context       = (string) $args['context'];

		if ( $image_url ) {
			$slide_images[] = $image_url;
		}
		foreach ( $gallery_ids as $gallery_id ) {
			$gallery_url = wp_get_attachment_image_url( $gallery_id, 'medium_large' );
			if ( $gallery_url ) {
				$slide_images[] = $gallery_url;
			}
		}
		$slide_images = array_values( array_unique( $slide_images ) );

		if ( $search_count >= 3 && ! in_array( 'tuyen_hot', $hot_badges, true ) ) {
			array_unshift( $hot_badges, 'tuyen_hot' );
		}
		$hot_badges = array_values( array_unique( array_filter( $hot_badges ) ) );

		ob_start();
		?>
		<article class="mttf-card" data-route-id="<?php echo esc_attr( (string) $post_id ); ?>" data-route-title="<?php echo esc_attr( $route->post_title ); ?>" data-route-slug="<?php echo esc_attr( $route_slug ); ?>" data-route-region="<?php echo esc_attr( $region ); ?>" data-route-car-type="<?php echo esc_attr( self::normalize_car_type_key( $car_type ) ); ?>" data-route-image="<?php echo esc_url( (string) $image_url ); ?>">
			<?php if ( ! empty( $slide_images ) ) : ?>
				<div class="mttf-card__media" data-slider-interval="<?php echo esc_attr( (string) $slide_interval ); ?>">
					<?php if ( ! empty( $hot_badges ) ) : ?>
						<div class="mttf-card__badges">
							<?php foreach ( $hot_badges as $badge ) : ?>
								<span class="mttf-badge mttf-badge--<?php echo esc_attr( $badge ); ?>">
									<?php echo esc_html( self::get_hot_badge_label( $badge ) ); ?>
								</span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php foreach ( $slide_images as $index => $slide_image ) : ?>
						<img class="mttf-card__image<?php echo 0 === $index ? ' is-active' : ''; ?>" src="<?php echo esc_url( $slide_image ); ?>" alt="<?php echo esc_attr( $route->post_title ); ?>" loading="lazy" />
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<h4 class="mttf-card__title"><?php echo esc_html( $route->post_title ); ?></h4>
			<?php if ( 'directory' === $context && '' !== $detail_url ) : ?>
				<p class="mttf-card__detail-row">
					<a class="mttf-card__detail-link" href="<?php echo esc_url( $detail_url ); ?>">Xem nhà xe tuyến này</a>
				</p>
			<?php endif; ?>
			<div class="mttf-card__rating-row">
				<span class="mttf-card__rating">
					<?php echo esc_html( $rating_score ); ?>
					<span class="mttf-card__star" aria-hidden="true">★</span>
					(<?php echo esc_html( $review_count ); ?> đánh giá)
				</span>
			</div>
			<div class="mttf-card__meta mttf-card__meta--primary">
				<span class="mttf-card__price">Từ <?php echo esc_html( number_format_i18n( $price_from ) ); ?> VND</span>
				<?php if ( '' !== $contact_count ) : ?>
					<span class="mttf-card__contact-count"><?php echo esc_html( $contact_count ); ?> lượt liên hệ</span>
				<?php endif; ?>
			</div>
			<div class="mttf-card__meta">
				<span class="mttf-card__car-type"><?php echo esc_html( $car_type ); ?></span>
				<span><?php echo esc_html( $trip_frequency ); ?></span>
			</div>
			<?php if ( ! empty( $features ) ) : ?>
				<ul class="mttf-card__features">
					<?php foreach ( $features as $feature ) : ?>
						<li><?php echo esc_html( self::get_feature_label( $feature ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="mttf-card__actions">
				<button type="button" class="mttf-btn mttf-btn--call mttf-open-modal mttf-js-track" data-track-event="book_click">
					<?php if ( '' !== $call_icon_url ) : ?>
						<img class="mttf-btn__icon" src="<?php echo esc_url( $call_icon_url ); ?>" alt="" aria-hidden="true" />
					<?php endif; ?>
					<span>Tư vấn ngay</span>
				</button>
				<a class="mttf-btn mttf-btn--zalo mttf-js-track" href="<?php echo esc_url( $zalo_link ); ?>" target="_blank" rel="noopener" data-track-event="zalo_click">
					<?php if ( '' !== $zalo_icon_url ) : ?>
						<img class="mttf-btn__icon" src="<?php echo esc_url( $zalo_icon_url ); ?>" alt="" aria-hidden="true" />
					<?php endif; ?>
					<span>Zalo</span>
				</a>
			</div>
		</article>
		<?php

		return (string) ob_get_clean();
	}

	private static function get_route_card_data( $route ) {
		$post_id         = (int) $route->ID;
		$route_slug      = (string) get_post_meta( $post_id, '_mttf_route_slug', true );
		$price_from      = (int) get_post_meta( $post_id, '_mttf_price_from', true );
		$trip_frequency  = (string) get_post_meta( $post_id, '_mttf_trip_frequency', true );
		$car_type        = (string) get_post_meta( $post_id, '_mttf_car_type', true );
		$region          = (string) get_post_meta( $post_id, '_mttf_hub_region', true );
		$image_url       = (string) get_the_post_thumbnail_url( $post_id, 'medium_large' );
		$rating_score    = (string) get_post_meta( $post_id, '_mttf_rating_score', true );
		$review_count    = (string) get_post_meta( $post_id, '_mttf_review_count', true );
		$operator_rows   = class_exists( 'MTTF_Route_Operators' ) ? MTTF_Route_Operators::get_route_operator_rows( $post_id, true ) : array();
		$operator_count  = count( $operator_rows );

		if ( '' === $image_url ) {
			$image_url = self::get_default_route_hero_image();
		}

		return array(
			'post_id'         => $post_id,
			'title'           => (string) get_the_title( $route ),
			'route_slug'      => $route_slug,
			'price_from'      => $price_from,
			'trip_frequency'  => $trip_frequency,
			'car_type'        => $car_type,
			'region'          => $region,
			'image_url'       => $image_url,
			'rating_score'    => $rating_score,
			'review_count'    => $review_count,
			'operator_count'  => $operator_count,
		);
	}

	private static function render_route_discovery_card( $route, $base_url ) {
		$data = self::get_route_card_data( $route );
		$data['detail_url'] = self::build_route_directory_url( $base_url, $route );
		$data['region_label'] = self::get_region_title_compact( $data['region'] );

		return self::render_route_partial( 'route-discovery-card', $data );
	}

		private static function render_operator_route_card( $route, $base_url, $operator = null ) {
			$data = self::get_route_card_data( $route );
			$shared_contacts = self::get_shared_contact_details();
			$data['detail_url'] = self::build_route_directory_url( $base_url, $route );
			$data['region_label'] = self::get_region_title_compact( $data['region'] );
			$data['operator_id'] = $operator ? (int) $operator->ID : 0;
			$data['operator_name'] = $operator ? (string) get_the_title( $operator ) : '';
			$data['operator_slug'] = $operator ? (string) get_post_field( 'post_name', $operator->ID ) : '';
			$data['zalo_url'] = (string) ( $shared_contacts['zalo_url'] ?? '' );

			return self::render_route_partial( 'operator-route-card', $data );
		}

	private static function get_related_routes( $route_id, $limit = 3 ) {
		$route_id = absint( $route_id );
		if ( $route_id <= 0 ) {
			return array();
		}

		$route_region   = (string) get_post_meta( $route_id, '_mttf_hub_region', true );
		$route_car_type = (string) get_post_meta( $route_id, '_mttf_car_type', true );
		$routes         = self::fetch_routes( $route_region, 12 );

		$filtered = array_values(
			array_filter(
				$routes,
				static function ( $route ) use ( $route_id ) {
					return (int) $route->ID !== $route_id;
				}
			)
		);

		if ( '' !== $route_car_type ) {
			usort(
				$filtered,
				static function ( $a, $b ) use ( $route_car_type ) {
					$a_score = (string) get_post_meta( $a->ID, '_mttf_car_type', true ) === $route_car_type ? 0 : 1;
					$b_score = (string) get_post_meta( $b->ID, '_mttf_car_type', true ) === $route_car_type ? 0 : 1;
					if ( $a_score === $b_score ) {
						return strcasecmp( (string) $a->post_title, (string) $b->post_title );
					}
					return $a_score - $b_score;
				}
			);
		}

		$limit = max( 1, absint( $limit ) );
		return array_slice( $filtered, 0, $limit );
	}

	private static function render_related_routes( array $routes, $base_url, $title = 'Tuyến liên quan' ) {
		if ( empty( $routes ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="mttf-related-routes" aria-label="<?php echo esc_attr( $title ); ?>">
			<div class="mttf-related-routes__header">
				<h2 class="mttf-related-routes__title"><?php echo esc_html( $title ); ?></h2>
				<a class="mttf-related-routes__link" href="<?php echo esc_url( $base_url ); ?>">Xem tất cả tuyến</a>
			</div>
			<div class="mttf-route-directory-grid mttf-related-routes__grid">
				<?php foreach ( $routes as $route ) : ?>
					<?php echo self::render_route_discovery_card( $route, $base_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	private static function render_route_operator_grid( array $operator_rows, $route, $base_url, array $shared_contacts ) {
		if ( empty( $operator_rows ) ) {
			return '';
		}

		$route_title      = (string) get_the_title( $route );
		$route_post_id    = (int) $route->ID;
		$route_slug       = (string) get_post_meta( $route_post_id, '_mttf_route_slug', true );
		$route_price      = (int) get_post_meta( $route_post_id, '_mttf_price_from', true );
		$route_frequency  = (string) get_post_meta( $route_post_id, '_mttf_trip_frequency', true );
		$route_car_type   = (string) get_post_meta( $route_post_id, '_mttf_car_type', true );
		$route_region     = (string) get_post_meta( $route_post_id, '_mttf_hub_region', true );
		$route_image      = (string) get_the_post_thumbnail_url( $route_post_id, 'medium_large' );
		$route_rating     = (string) get_post_meta( $route_post_id, '_mttf_rating_score', true );
		$route_reviews    = (string) get_post_meta( $route_post_id, '_mttf_review_count', true );

		if ( '' === $route_image ) {
			$route_image = self::get_default_route_hero_image();
		}

		ob_start();
		?>
		<div class="mttf-route-operator-grid">
			<?php foreach ( $operator_rows as $row ) : ?>
				<?php
				$operator_id = (int) $row['operator_id'];
				$defaults    = isset( $row['operator_defaults'] ) && is_array( $row['operator_defaults'] ) ? $row['operator_defaults'] : array();
				$operator_routes = class_exists( 'MTTF_Route_Operators' ) ? MTTF_Route_Operators::get_operator_routes( $operator_id, true ) : array();
				$contact_phone = (string) $shared_contacts['phone'];
				$contact_href  = self::get_phone_href( $contact_phone );
				$contact_zalo  = (string) $shared_contacts['zalo_url'];
				echo self::render_route_partial(
					'route-operator-card',
					array(
						'operator_id'     => $operator_id,
						'operator_name'   => (string) $row['operator_name'],
						'operator_slug'   => (string) get_post_field( 'post_name', $operator_id ),
						'operator_logo'   => (string) get_the_post_thumbnail_url( $operator_id, 'medium' ),
						'route_id'        => $route_post_id,
						'route_title'     => $route_title,
						'route_slug'      => $route_slug,
						'route_count'     => count( $operator_routes ),
						'price_from'      => $route_price,
						'trip_frequency'  => $route_frequency,
						'car_type'        => $route_car_type,
						'route_region'    => $route_region,
						'region_label'    => self::get_region_title_compact( $route_region ),
						'image_url'       => $route_image,
						'rating_score'    => $route_rating,
						'review_count'    => $route_reviews,
						'phone'           => $contact_phone,
						'phone_href'      => $contact_href,
						'zalo_url'        => $contact_zalo,
						'base_url'        => $base_url,
						'operator_url'    => self::build_operator_directory_url( $base_url, (string) get_post_field( 'post_name', $operator_id ) ),
						'initials'        => self::get_operator_initials( (string) $row['operator_name'] ),
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php endforeach; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	private static function render_modal() {
		ob_start();
		?>
		<div class="mttf-modal" id="mttf-modal" hidden>
			<div class="mttf-modal__overlay mttf-close-modal"></div>
			<div class="mttf-modal__content" role="dialog" aria-modal="true" aria-label="Nhập số điện thoại">
				<div class="mttf-modal__hero">
					<img src="" alt="" class="mttf-modal__hero-image" data-mttf-route-image />
				</div>
				<button type="button" class="mttf-modal__close mttf-close-modal" aria-label="Dong">×</button>
				<h3 class="mttf-modal__title" data-mttf-title>Bạn cần xe đi tuyến này?</h3>
				<p class="mttf-modal__subtitle">Chuyên viên Đặt Xe Việt Nam sẽ gọi lại cho bạn trong 1-3 phút</p>
				<p class="mttf-modal__route" data-mttf-route-name></p>
				<form class="mttf-lead-form">
					<input type="hidden" name="route_id" />
					<input type="hidden" name="route_title" />
					<input type="hidden" name="route_slug" />
					<input type="hidden" name="route_region" />
					<input type="hidden" name="operator_id" />
					<input type="hidden" name="operator_name" />
					<input type="hidden" name="operator_slug" />
					<input type="hidden" name="page_type" />
					<div class="mttf-input-wrap">
						<span class="mttf-input-icon" aria-hidden="true"><?php echo file_get_contents( MTTF_PATH . 'assets/icons/phone-incoming.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<input type="tel" name="phone" placeholder="Nhập số điện thoại" required />
					</div>
					<div class="mttf-intl-toggle-row">
						<span class="mttf-intl-toggle-row__label">International customer?</span>
						<button type="button" class="mttf-intl-switch" data-mttf-intl-toggle aria-pressed="false" aria-label="Toggle international contact apps">
							<span class="mttf-intl-switch__thumb" aria-hidden="true"></span>
						</button>
					</div>
					<div class="mttf-intl-fields" data-mttf-intl-fields hidden>
						<p class="mttf-lead-form__intl-note">Contact me via:</p>
						<div class="mttf-contact-apps">
							<label class="mttf-contact-apps__item"><input type="checkbox" name="contact_apps[]" value="WhatsApp" /> WhatsApp</label>
							<label class="mttf-contact-apps__item"><input type="checkbox" name="contact_apps[]" value="Viber" /> Viber</label>
							<label class="mttf-contact-apps__item"><input type="checkbox" name="contact_apps[]" value="WeChat" /> WeChat</label>
							<label class="mttf-contact-apps__item"><input type="checkbox" name="contact_apps[]" value="KakaoTalk" /> KakaoTalk</label>
						</div>
					</div>
					<input type="text" name="website" value="" autocomplete="off" tabindex="-1" class="mttf-honeypot" />
					<button type="submit" class="mttf-btn">Gửi yêu cầu tư vấn tuyến này</button>
					<p class="mttf-lead-form__status" data-mttf-status></p>
				</form>
				<p class="mttf-modal__note">Đặt xe Việt Nam cam kết bảo mật thông tin khách hàng.</p>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	private static function render_fallback_card() {
		$default_hotline  = MTTF_Settings::get( 'fallback_hotline', '' );
		$fallback_hotline = apply_filters( 'mttf_fallback_hotline', $default_hotline );
		if ( '' === $fallback_hotline ) {
			$fallback_hotline = get_option( 'admin_phone', '0900000000' );
		}

		return '<div class="mttf-fallback"><h3>Không tìm thấy tuyến phù hợp</h3><p>Sale sẽ hỗ trợ ngay. Gọi: ' . esc_html( (string) $fallback_hotline ) . '</p></div>';
	}

	private static function render_operator_brand_section( array $operators, $base_url ) {
		ob_start();
		?>
		<section class="mttf-brand-section" aria-label="Danh sách hãng xe">
			<div class="mttf-brand-section__header">
				<div>
					<p class="mttf-brand-section__eyebrow">Hãng xe</p>
					<h2 class="mttf-brand-section__title">Nhà xe đang khai thác</h2>
				</div>
				<a class="mttf-brand-section__hint" href="<?php echo esc_url( $base_url ); ?>">Xem theo từng hãng</a>
			</div>
			<div class="mttf-brand-grid">
				<?php foreach ( $operators as $operator ) : ?>
					<?php echo self::render_route_partial(
						'operator-mini-card',
						array(
							'name'       => (string) $operator['name'],
							'logo'       => (string) $operator['logo'],
							'route_count'=> (int) $operator['route_count'],
							'url'        => self::build_operator_directory_url( $base_url, $operator['slug'] ),
							'initials'   => self::get_operator_initials( $operator['name'] ),
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	private static function render_dynamic_route_header( array $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'eyebrow'         => '',
				'title'           => '',
				'description'     => '',
				'phone'           => '',
				'phone_href'      => '',
				'zalo_url'        => '',
				'zalo_label'      => 'Tư vấn Zalo',
				'email'           => '',
				'base_url'        => '',
				'back_label'      => '',
				'back_url'        => '',
				'modifier_class'  => '',
			)
		);

		return self::render_route_partial( 'dynamic-header', $args );
	}

	private static function render_directory_hero( array $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'eyebrow'       => '',
				'title'         => '',
				'description'   => '',
				'base_url'      => '',
				'back_url'      => '',
				'back_label'    => '',
				'image_url'     => '',
				'image_urls'    => array(),
				'summary_items' => array(),
				'phone'         => '',
				'phone_href'    => '',
				'zalo_url'      => '',
				'email'         => '',
				'modifier_class'=> '',
			)
		);

		if ( '' === (string) $args['image_url'] ) {
			$args['image_url'] = self::get_default_route_hero_image();
		}

		$image_urls = isset( $args['image_urls'] ) && is_array( $args['image_urls'] ) ? $args['image_urls'] : array();
		array_unshift( $image_urls, (string) $args['image_url'] );
		$args['image_urls'] = array_values( array_unique( array_filter( array_map( 'esc_url_raw', $image_urls ) ) ) );

		return self::render_route_partial( 'full-bleed-hero', $args );
	}

	private static function render_directory_not_found( $title, $message, $base_url ) {
		$shared_contacts = self::get_shared_contact_details();
		ob_start();
		?>
		<div class="mttf mttf-directory">
			<?php echo self::render_directory_hero( array(
				'eyebrow'     => 'Route Directory',
				'title'       => $title,
				'description' => $message,
				'base_url'    => $base_url,
			) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo self::render_directory_not_found_body(
				$message,
				array(
					'title'      => $title,
					'phone'      => $shared_contacts['phone'],
					'phone_href' => $shared_contacts['phone_href'],
					'back_url'   => $base_url,
					'back_label' => 'Quay lại tất cả tuyến',
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	private static function render_directory_not_found_body( $message, array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'title'      => 'Chưa có dữ liệu phù hợp',
				'phone'      => '',
				'phone_href' => '',
				'back_url'   => '',
				'back_label' => 'Quay lại danh sách tuyến',
			)
		);

		ob_start();
		?>
		<div class="mttf-directory-empty">
			<h3 class="mttf-directory-empty__title"><?php echo esc_html( (string) $args['title'] ); ?></h3>
			<p><?php echo esc_html( $message ); ?></p>
			<div class="mttf-directory-empty__actions">
				<?php if ( '' !== (string) $args['phone_href'] && '' !== (string) $args['phone'] ) : ?>
					<a class="mttf-btn mttf-btn--call mttf-js-track" href="<?php echo esc_url( (string) $args['phone_href'] ); ?>" data-track-event="call_click" data-track-label="empty_state_call">
						Gọi <?php echo esc_html( (string) $args['phone'] ); ?>
					</a>
				<?php endif; ?>
				<?php if ( '' !== (string) $args['back_url'] ) : ?>
					<a class="mttf-directory-empty__back" href="<?php echo esc_url( (string) $args['back_url'] ); ?>"><?php echo esc_html( (string) $args['back_label'] ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * CSS cho [mttf_route_links] (sidebar / widget — không ép tải toàn bộ hub).
	 */
	private static function enqueue_route_links_styles() {
		$path = MTTF_PATH . 'assets/css/route-links.css';
		wp_enqueue_style(
			'mttf-route-links',
			MTTF_URL . 'assets/css/route-links.css',
			array(),
			file_exists( $path ) ? (string) filemtime( $path ) : MTTF_VERSION
		);
	}

	private static function enqueue_assets( $context = 'hub' ) {
		$token_css_path = MTTF_PATH . 'assets/css/design-token.css';
		$css_path = MTTF_PATH . 'assets/css/frontend.css';
		$js_path  = MTTF_PATH . 'assets/js/frontend.js';
		$token_css_ver = file_exists( $token_css_path ) ? (string) filemtime( $token_css_path ) : MTTF_VERSION;
		$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : MTTF_VERSION;
		$js_ver   = file_exists( $js_path ) ? (string) filemtime( $js_path ) : MTTF_VERSION;

		wp_enqueue_style(
			'mttf-design-tokens',
			MTTF_URL . 'assets/css/design-token.css',
			array(),
			$token_css_ver
		);

		wp_enqueue_style(
			'mttf-frontend',
			MTTF_URL . 'assets/css/frontend.css',
			array( 'mttf-design-tokens' ),
			$css_ver
		);

		if ( 0 === strpos( (string) $context, 'route-' ) ) {
			self::enqueue_route_directory_styles( (string) $context, $css_ver );
		}

		wp_enqueue_script(
			'mttf-frontend',
			MTTF_URL . 'assets/js/frontend.js',
			array(),
			$js_ver,
			true
		);

		$mttf_data = array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'mttf_capture_lead' ),
			'searchNonce'       => wp_create_nonce( 'mttf_live_search' ),
			'trackSearchNonce' => wp_create_nonce( 'mttf_track_route_search' ),
			'activityEnabled'   => MTTF_Activity_Pings::is_enabled() ? 1 : 0,
			'measurement'       => MTTF_Settings::get_frontend_measurement_payload(),
		);

		if ( MTTF_Activity_Pings::is_enabled() ) {
			$poll_sec = absint( MTTF_Settings::get( 'activity_poll_interval', 28 ) );
			if ( $poll_sec < 15 ) {
				$poll_sec = 15;
			}
			if ( $poll_sec > 120 ) {
				$poll_sec = 120;
			}

			$mttf_data['activityNonce']  = wp_create_nonce( 'mttf_activity_pings' );
			$mttf_data['activityPollMs'] = $poll_sec * 1000;
		}

		wp_localize_script( 'mttf-frontend', 'mttfData', $mttf_data );
	}

	private static function enqueue_route_directory_styles( $context, $base_css_version ) {
		$style_map = array(
			'mttf-route-base'            => 'assets/css/route-base.css',
			'mttf-route-directory'       => 'assets/css/route-directory.css',
			'mttf-route-route-detail'    => 'assets/css/route-route-detail.css',
			'mttf-route-operator-detail' => 'assets/css/route-operator-detail.css',
		);

		foreach ( $style_map as $handle => $relative_path ) {
			$path = MTTF_PATH . $relative_path;
			if ( ! file_exists( $path ) ) {
				continue;
			}

			wp_enqueue_style(
				$handle,
				MTTF_URL . $relative_path,
				array( 'mttf-frontend' ),
				(string) filemtime( $path )
			);
		}

		if ( isset( $style_map[ $context ] ) ) {
			return;
		}

		// Keep a stable cache-bust path if a route-specific context was passed without a dedicated file.
		wp_enqueue_style( 'mttf-frontend', MTTF_URL . 'assets/css/frontend.css', array(), $base_css_version );
	}

	private static function render_route_template( $template, array $data ) {
		$template_path = MTTF_PATH . 'templates/route/' . sanitize_file_name( $template ) . '.php';
		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		$render_directory_hero = static function( array $args ) {
			return self::render_directory_hero( $args );
		};
		$render_operator_brand_section = static function( array $operators, $base_url ) {
			return self::render_operator_brand_section( $operators, $base_url );
		};
		$render_directory_route_sections = static function( array $routes, $route_priority, $base_url ) {
			return self::render_directory_route_sections( $routes, $route_priority, $base_url );
		};
		$render_operator_route_sections = static function( array $routes, $route_priority, $base_url, $operator = null ) {
			return self::render_operator_route_sections( $routes, $route_priority, $base_url, $operator );
		};
		$render_route_operator_grid = static function( array $operator_rows, $route, $base_url, array $shared_contacts ) {
			return self::render_route_operator_grid( $operator_rows, $route, $base_url, $shared_contacts );
		};
		$render_route_sections = static function( array $routes, $route_priority, $context, $base_url ) {
			return self::render_route_sections( $routes, $route_priority, $context, $base_url );
		};
		$render_modal = static function() {
			return self::render_modal();
		};
		$render_fallback_card = static function() {
			return self::render_fallback_card();
		};
		$render_directory_not_found_body = static function( $message, array $args = array() ) {
			return self::render_directory_not_found_body( $message, $args );
		};
		$render_related_routes = static function( array $routes, $base_url, $title = 'Tuyến liên quan' ) {
			return self::render_related_routes( $routes, $base_url, $title );
		};
		$render_dynamic_route_header = static function( array $args ) {
			return self::render_dynamic_route_header( $args );
		};
		$normalize_car_type_key = static function( $value ) {
			return self::normalize_car_type_key( $value );
		};
		$build_operator_directory_url = static function( $base_url, $operator_slug ) {
			return self::build_operator_directory_url( $base_url, $operator_slug );
		};
		$get_region_title_compact = static function( $region ) {
			return self::get_region_title_compact( $region );
		};
		$get_shared_contact_details = static function() {
			return self::get_shared_contact_details();
		};
		$get_related_routes = static function( $route_id, $limit = 3 ) {
			return self::get_related_routes( $route_id, $limit );
		};
		$get_route_hero_images = static function( $route_id, $size = 'large' ) {
			return self::get_route_hero_images( $route_id, $size );
		};
		$get_tel_href = static function( $phone ) {
			return self::get_phone_href( $phone );
		};

		extract( $data, EXTR_SKIP );

		ob_start();
		require $template_path;

		return (string) ob_get_clean();
	}

	private static function render_route_partial( $template, array $data ) {
		$template_path = MTTF_PATH . 'templates/route/partials/' . sanitize_file_name( $template ) . '.php';
		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		extract( $data, EXTR_SKIP );

		ob_start();
		require $template_path;

		return (string) ob_get_clean();
	}

	private static function get_shared_contact_details() {
		$phone = (string) MTTF_Settings::get( 'fallback_hotline', '' );
		if ( '' === $phone ) {
			$phone = (string) get_option( 'admin_phone', '19008164' );
		}

		$email = 'datxevietnam.vn@gmail.com';

		$zalo_url = '';
		if ( function_exists( 'dxvn_get_header_settings' ) ) {
			$settings = dxvn_get_header_settings();
			$zalo_url = isset( $settings['contact_zalo_url'] ) ? (string) $settings['contact_zalo_url'] : '';
		}
		if ( '' === $zalo_url || '#' === $zalo_url ) {
			$zalo_url = self::get_phone_href( $phone );
		}

		return array(
			'email'      => $email,
			'phone'      => $phone,
			'phone_href' => self::get_phone_href( $phone ),
			'zalo_url'   => $zalo_url,
		);
	}

	private static function get_default_route_hero_image() {
		return (string) MTTF_Settings::get( 'hero_background_url', 'https://images.pexels.com/photos/120049/pexels-photo-120049.jpeg' );
	}

	private static function get_route_hero_images( $route_id, $size = 'large' ) {
		$route_id = absint( $route_id );
		if ( $route_id <= 0 ) {
			return array( self::get_default_route_hero_image() );
		}

		$images   = array();
		$featured = get_the_post_thumbnail_url( $route_id, $size );
		if ( $featured ) {
			$images[] = (string) $featured;
		}

		$gallery_ids = self::parse_gallery_ids( (string) get_post_meta( $route_id, '_mttf_gallery_ids', true ) );
		foreach ( $gallery_ids as $gallery_id ) {
			$url = wp_get_attachment_image_url( $gallery_id, $size );
			if ( $url ) {
				$images[] = (string) $url;
			}
		}

		if ( empty( $images ) ) {
			$images[] = self::get_default_route_hero_image();
		}

		return array_values( array_unique( $images ) );
	}

	private static function get_phone_href( $phone ) {
		$digits = preg_replace( '/[^0-9+]/', '', (string) $phone );
		return '' !== $digits ? 'tel:' . $digits : '#';
	}

	private static function get_directory_base_url( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' !== $raw ) {
			return self::normalize_hub_page_url_for_links( $raw );
		}

		$queried_id = get_queried_object_id();
		if ( $queried_id > 0 ) {
			$permalink = get_permalink( $queried_id );
			if ( $permalink ) {
				return trailingslashit( $permalink );
			}
		}

		global $wp;
		if ( isset( $wp->request ) && '' !== (string) $wp->request ) {
			return trailingslashit( home_url( '/' . ltrim( (string) $wp->request, '/' ) ) );
		}

		return trailingslashit( home_url( '/' ) );
	}

	private static function build_route_directory_url( $base_url, $route ) {
		$slug = (string) get_post_meta( $route->ID, '_mttf_route_slug', true );
		if ( '' === $slug ) {
			$slug = sanitize_title( (string) $route->post_name );
		}

		return add_query_arg(
			array(
				'route' => rawurlencode( $slug ),
			),
			remove_query_arg( array( 'operator', 'mttf_q' ), $base_url )
		);
	}

	private static function build_operator_directory_url( $base_url, $operator_slug ) {
		return add_query_arg(
			array(
				'operator' => rawurlencode( (string) $operator_slug ),
			),
			remove_query_arg( array( 'route', 'mttf_q' ), $base_url )
		);
	}

	private static function find_route_by_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return null;
		}

		$routes = get_posts(
			array(
				'post_type'      => 'tuyen_xe',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'   => '_mttf_route_slug',
						'value' => $slug,
					),
					array(
						'key'   => '_mttf_is_active',
						'value' => 1,
					),
				),
			)
		);

		if ( ! empty( $routes ) ) {
			return $routes[0];
		}

		$fallback = get_posts(
			array(
				'post_type'      => 'tuyen_xe',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'name'           => $slug,
			)
		);

		return ! empty( $fallback ) ? $fallback[0] : null;
	}

	private static function find_operator_by_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return null;
		}

		$operators = get_posts(
			array(
				'post_type'      => 'mttf_operator',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'name'           => $slug,
			)
		);

		return ! empty( $operators ) ? $operators[0] : null;
	}

	private static function filter_routes_by_region( $routes, $region ) {
		$region = sanitize_text_field( (string) $region );
		if ( '' === $region ) {
			return $routes;
		}

		return array_values(
			array_filter(
				$routes,
				static function ( $route ) use ( $region ) {
					return (string) get_post_meta( $route->ID, '_mttf_hub_region', true ) === $region;
				}
			)
		);
	}

	private static function get_directory_operators() {
		if ( ! class_exists( 'MTTF_Operator' ) || ! class_exists( 'MTTF_Route_Operators' ) ) {
			return array();
		}

		$choices = MTTF_Operator::get_operator_choices( true );
		$items   = array();

		foreach ( $choices as $choice ) {
			$operator_id = (int) $choice['id'];
			$routes      = MTTF_Route_Operators::get_operator_routes( $operator_id, true );
			$route_count = count( $routes );
			if ( $route_count < 1 ) {
				continue;
			}

			$items[] = array(
				'id'         => $operator_id,
				'name'       => get_the_title( $operator_id ),
				'slug'       => (string) get_post_field( 'post_name', $operator_id ),
				'logo'       => (string) get_the_post_thumbnail_url( $operator_id, 'medium' ),
				'route_count'=> $route_count,
				'priority'   => (int) $choice['priority'],
			);
		}

		usort(
			$items,
			static function ( $a, $b ) {
				if ( $a['priority'] === $b['priority'] ) {
					if ( $a['route_count'] === $b['route_count'] ) {
						return strcasecmp( $a['name'], $b['name'] );
					}

					return $b['route_count'] <=> $a['route_count'];
				}

				return $a['priority'] <=> $b['priority'];
			}
		);

		return array_slice( $items, 0, 12 );
	}

	private static function get_operator_initials( $name ) {
		$name = trim( (string) $name );
		if ( '' === $name ) {
			return 'HX';
		}

		$parts = preg_split( '/\s+/', $name );
		$parts = is_array( $parts ) ? array_values( array_filter( $parts, 'strlen' ) ) : array();

		if ( empty( $parts ) ) {
			return 'HX';
		}

		$initials = '';
		foreach ( array_slice( $parts, 0, 2 ) as $part ) {
			$initials .= function_exists( 'mb_substr' ) ? mb_substr( $part, 0, 1 ) : substr( $part, 0, 1 );
		}

		return strtoupper( $initials );
	}

	private static function normalize_text( $text ) {
		$text = remove_accents( strtolower( $text ) );
		return preg_replace( '/\s+/', ' ', trim( $text ) );
	}

	private static function get_feature_label( $feature_key ) {
		$labels = array(
			'don_tra_tan_noi'             => 'Đón trả tận nơi',
			'don_tra_linh_hoat'           => 'Đón trả linh hoạt',
			'mien_phi_nuoc_loc_khan_lanh' => 'Miễn phí nước lọc & khăn lạnh',
			'ghe_massage_boc_da_cao_cap'  => 'Ghế Massage bọc da cao cấp',
			'cabin_rieng_tu'              => 'Cabin riêng tư',
			'wifi_cong_sac_usb'           => 'Wifi tốc độ cao, Cổng sạc USB & Type C',
			'chan_goi_sach_se'            => 'Chăn gối sạch sẽ',
			'chay_cao_toc_100'            => 'Chạy cao tốc 100%',
			'khong_bat_khach_doc_duong'   => 'Không bắt khách dọc đường',
			'xe_doi_moi_2025_2026'        => 'Xe đời mới 2025 - 2026',
			'dung_gio_dung_chuyen'        => 'Đúng giờ - Đúng chuyến',
			'bao_hiem_hanh_khach'         => 'Bảo hiểm hành khách',
			'don_tan_noi'                 => 'Đón tận nơi',
			'chay_cao_toc'                => 'Chạy cao tốc',
			'wifi'                        => 'Wifi tốc độ cao, Cổng sạc USB & Type C',
			'nuoc_uong'                   => 'Nước uống',
			'sac_dien_thoai'              => 'Sạc điện thoại',
		);

		if ( isset( $labels[ $feature_key ] ) ) {
			return $labels[ $feature_key ];
		}

		return ucwords( str_replace( '_', ' ', (string) $feature_key ) );
	}

	private static function get_region_title( $region_key ) {
		$titles = array(
			'bac'   => (string) MTTF_Settings::get( 'route_section_title_bac', 'Gợi ý tuyến miền Bắc cho bạn' ),
			'trung' => (string) MTTF_Settings::get( 'route_section_title_trung', 'Gợi ý tuyến miền Trung cho bạn' ),
			'nam'   => (string) MTTF_Settings::get( 'route_section_title_nam', 'Gợi ý tuyến miền Nam cho bạn' ),
			'khac'  => (string) MTTF_Settings::get( 'route_section_title_default', 'Gợi ý tuyến phù hợp cho bạn' ),
		);

		return $titles[ $region_key ] ?? (string) MTTF_Settings::get( 'route_section_title_default', 'Gợi ý tuyến phù hợp cho bạn' );
	}

	private static function get_region_title_compact( $region_key ) {
		$titles = array(
			'bac'   => 'Miền Bắc',
			'trung' => 'Miền Trung',
			'nam'   => 'Miền Nam',
			'khac'  => 'Khác',
			''      => 'Toàn quốc',
		);

		return $titles[ $region_key ] ?? 'Toàn quốc';
	}

	/**
	 * Tiêu đề miền cho shortcode [mttf_route_links] (khác hub).
	 *
	 * @param string $region_key bac|nam|trung|khac.
	 */
	private static function get_route_links_region_heading( $region_key ) {
		$titles = array(
			'bac'   => 'Tuyến đường phổ biến miền Bắc',
			'nam'   => 'Tuyến đường phổ biến miền Nam',
			'trung' => 'Tuyến đường phổ biến miền Trung',
			'khac'  => 'Tuyến đường phổ biến khác',
		);

		return $titles[ $region_key ] ?? 'Tuyến đường phổ biến khác';
	}

	private static function get_hot_badge_label( $badge_key ) {
		$labels = array(
			'dat_nhieu_hom_nay'         => 'Đặt nhiều hôm nay',
			'sap_het_cho'               => 'Sắp hết chỗ',
			'dat_xe_viet_nam_chon_loc'  => 'Đặt Xe Việt Nam chọn lọc',
			'tuyen_hot'                 => 'Tuyến hot',
		);

		return $labels[ $badge_key ] ?? ucwords( str_replace( '_', ' ', (string) $badge_key ) );
	}

	private static function parse_gallery_ids( $raw_ids ) {
		if ( '' === trim( $raw_ids ) ) {
			return array();
		}

		$parts = array_map( 'trim', explode( ',', $raw_ids ) );
		$parts = array_filter( $parts, 'strlen' );
		$parts = array_map( 'absint', $parts );
		return array_values( array_filter( $parts ) );
	}
}
