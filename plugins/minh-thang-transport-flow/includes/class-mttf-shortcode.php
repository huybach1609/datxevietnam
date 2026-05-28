<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MTTF_Shortcode {
	public static function init() {
		add_shortcode( 'mttf_hub', array( __CLASS__, 'render_hub' ) );
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
		$routes = self::sort_route_posts( $routes, $route_boost );

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

	public static function render_hub( $atts ) {
		$atts = shortcode_atts(
			array(
				'region' => '',
			),
			$atts,
			'mttf_hub'
		);

		self::enqueue_frontend_assets();

		$search_keyword = isset( $_GET['mttf_q'] ) ? sanitize_text_field( wp_unslash( $_GET['mttf_q'] ) ) : '';
		$route_priority = isset( $_GET['route'] ) ? sanitize_title( wp_unslash( $_GET['route'] ) ) : '';

		$routes = self::fetch_routes( $atts['region'], 200 );
		$routes = self::filter_routes( $routes, $search_keyword );
		$routes = self::sort_route_posts( $routes, $route_priority );
		if ( class_exists( 'MTTF_Performance', false ) ) {
			MTTF_Performance::prime_route_meta_cache( $routes );
		}
		$car_types = self::collect_car_types( $routes );
		$hero_badge     = (string) MTTF_Settings::get( 'hero_title_1', 'Nền tảng đặt xe chọn lọc toàn Việt Nam' );
		$hero_h1        = (string) MTTF_Settings::get( 'hero_title_2', 'Đặt xe minh bạch, chọn đúng nhà xe cho hành trình của bạn' );
		$hero_desc      = (string) MTTF_Settings::get( 'hero_title_3', 'Tra cứu tuyến xe, nhà xe, giá tham khảo và tiện ích trước khi đặt. Đặt Xe Việt Nam giúp khách so sánh thông tin rõ ràng, hạn chế rủi ro khi đặt xe qua các nguồn không minh bạch.' );
		$hero_bg_url    = (string) MTTF_Settings::get( 'hero_background_url', 'https://images.pexels.com/photos/120049/pexels-photo-120049.jpeg' );
		$hero_style     = '' !== $hero_bg_url ? ' style="background-image: url(\'' . esc_url( $hero_bg_url ) . '\');"' : '';
		$popular_tuyen_terms = MTTF_Settings::get_hero_popular_tuyen_terms();
		$trust_points   = array(
			array(
				'icon'  => 'curated',
				'label' => 'Nhà xe chọn lọc',
			),
			array(
				'icon'  => 'price',
				'label' => 'Giá tham khảo rõ ràng',
			),
			array(
				'icon'  => 'public',
				'label' => 'Thông tin tuyến công khai',
			),
			array(
				'icon'  => 'support',
				'label' => 'Tư vấn trước khi đặt',
			),
		);

		ob_start();
		?>
		<div class="mttf" data-route-priority="<?php echo esc_attr( $route_priority ); ?>">
			<div class="mttf-hero mttf-hero--home" aria-label="<?php esc_attr_e( 'Tìm và đặt xe', 'minh-thang-transport-flow' ); ?>"<?php echo $hero_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div class="mttf-hero__content mttf-hero__content--centered">
					<p class="mttf-hero__badge"><?php echo esc_html( $hero_badge ); ?></p>
					<h1 class="mttf-hero__title"><?php echo esc_html( $hero_h1 ); ?></h1>
					<p class="mttf-hero__desc"><?php echo esc_html( $hero_desc ); ?></p>
					<form method="get" class="mttf-search mttf-search--hero" action="" autocomplete="off">
						<div class="mttf-search__bar">
							<div class="mttf-search__input-wrap">
								<span class="mttf-search__icon" aria-hidden="true">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
										<path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									</svg>
								</span>
								<input id="mttf-search-input" type="search" name="mttf_q" value="<?php echo esc_attr( $search_keyword ); ?>" placeholder="<?php esc_attr_e( 'Nhập tuyến, tỉnh/thành phố hoặc nhà xe...', 'minh-thang-transport-flow' ); ?>" />
							</div>
							<button type="submit" class="mttf-search__submit"><?php esc_html_e( 'Tìm xe', 'minh-thang-transport-flow' ); ?></button>
						</div>
						<div class="mttf-suggest" hidden>
							<ul class="mttf-suggest__list" role="listbox" aria-label="<?php esc_attr_e( 'Gợi ý tuyến', 'minh-thang-transport-flow' ); ?>"></ul>
						</div>
					</form>
					<?php if ( ! empty( $popular_tuyen_terms ) ) : ?>
						<div class="mttf-hero-routes">
							<span class="mttf-hero-routes__label"><?php esc_html_e( 'Tuyến phổ biến', 'minh-thang-transport-flow' ); ?></span>
							<div class="mttf-hero-routes__chips" role="list">
								<?php foreach ( $popular_tuyen_terms as $tuyen_term ) : ?>
									<?php
									$tuyen_link = get_term_link( $tuyen_term );
									if ( is_wp_error( $tuyen_link ) ) {
										continue;
									}
									?>
									<a
										class="mttf-hero-route-chip"
										role="listitem"
										href="<?php echo esc_url( $tuyen_link ); ?>"
									><?php echo esc_html( $tuyen_term->name ); ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
					<ul class="mttf-hero-trust" role="list">
						<?php foreach ( $trust_points as $point ) : ?>
							<li class="mttf-hero-trust__item">
								<span class="mttf-hero-trust__icon mttf-hero-trust__icon--<?php echo esc_attr( $point['icon'] ); ?>" aria-hidden="true">
									<?php echo self::get_hero_trust_icon_svg( $point['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
								<span class="mttf-hero-trust__text"><?php echo esc_html( $point['label'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
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
				<?php
				$hub_first_card_eager = true;
				foreach ( self::group_by_region( $routes, $route_priority ) as $region => $region_routes ) :
					?>
					<section class="mttf-hub" data-mttf-region="<?php echo esc_attr( $region ); ?>">
						<h3 class="mttf-hub__title"><?php echo esc_html( self::get_region_title( $region ) ); ?></h3>
						<div class="mttf-hub__track">
							<?php foreach ( $region_routes as $route ) : ?>
								<?php
								echo self::render_route_card(
									$route,
									array(
										'eager_image' => $hub_first_card_eager,
									)
								); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$hub_first_card_eager = false;
								?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			<?php endif; ?>

			<?php echo self::render_lead_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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

	/**
	 * Sort route posts (hub search priority, stats, manual priority).
	 *
	 * @param WP_Post[] $routes         Posts.
	 * @param string    $route_priority Route slug boost from ?route=.
	 * @return WP_Post[]
	 */
	public static function sort_route_posts( $routes, $route_priority ) {
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

	private static function normalize_car_type_key( $value ) {
		return sanitize_title( (string) $value );
	}

	/**
	 * Render one service card (HTML + data attributes for lead modal).
	 *
	 * @param WP_Post              $route tuyen_xe post.
	 * @param array<string, mixed> $args  eager_image (bool) for LCP card image.
	 * @return string
	 */
	public static function render_route_card( $route, $args = array() ) {
		$args = wp_parse_args(
			is_array( $args ) ? $args : array(),
			array(
				'eager_image' => false,
			)
		);
		$eager_image    = ! empty( $args['eager_image'] );
		$post_id        = $route->ID;
		$route_slug     = (string) get_post_meta( $post_id, '_mttf_route_slug', true );
		$price_from     = (int) get_post_meta( $post_id, '_mttf_price_from', true );
		$rating_score   = (string) get_post_meta( $post_id, '_mttf_rating_score', true );
		$review_count   = (string) get_post_meta( $post_id, '_mttf_review_count', true );
		$contact_count  = (string) get_post_meta( $post_id, '_mttf_contact_count', true );
		$trip_frequency = (string) get_post_meta( $post_id, '_mttf_trip_frequency', true );
		$car_type       = (string) get_post_meta( $post_id, '_mttf_car_type', true );
		$features       = array_slice( (array) get_post_meta( $post_id, '_mttf_route_features', true ), 0, 3 );
		$hot_badges     = (array) get_post_meta( $post_id, '_mttf_hot_badges', true );
		$search_count   = (int) get_post_meta( $post_id, '_mttf_search_count', true );
		$zalo_link      = (string) get_post_meta( $post_id, '_mttf_zalo_link', true );
		$region         = (string) get_post_meta( $post_id, '_mttf_hub_region', true );
		$thumb_id       = (int) get_post_thumbnail_id( $post_id );
		$image_url      = $thumb_id > 0
			? (string) wp_get_attachment_image_url( $thumb_id, 'mttf-card' )
			: (string) get_the_post_thumbnail_url( $post_id, 'mttf-card' );
		$gallery_ids    = self::parse_gallery_ids( (string) get_post_meta( $post_id, '_mttf_gallery_ids', true ) );
		$slide_interval = max( 1, (int) get_post_meta( $post_id, '_mttf_slider_interval', true ) );
		$slide_attachments = array();
		$call_icon_url  = (string) MTTF_Settings::get( 'call_icon_url', '' );
		$zalo_icon_url  = (string) MTTF_Settings::get( 'zalo_icon_url', '' );

		if ( $thumb_id > 0 ) {
			$slide_attachments[] = $thumb_id;
		}
		foreach ( $gallery_ids as $gallery_id ) {
			if ( $gallery_id > 0 && $gallery_id !== $thumb_id ) {
				$slide_attachments[] = (int) $gallery_id;
			}
		}
		$slide_attachments = array_values( array_unique( $slide_attachments ) );

		if ( $search_count >= 3 && ! in_array( 'tuyen_hot', $hot_badges, true ) ) {
			array_unshift( $hot_badges, 'tuyen_hot' );
		}
		$hot_badges = array_values( array_unique( array_filter( $hot_badges ) ) );

		ob_start();
		?>
		<article id="mttf-route-<?php echo esc_attr( (string) $post_id ); ?>" class="mttf-card" data-route-id="<?php echo esc_attr( (string) $post_id ); ?>" data-route-title="<?php echo esc_attr( $route->post_title ); ?>" data-route-slug="<?php echo esc_attr( $route_slug ); ?>" data-route-region="<?php echo esc_attr( $region ); ?>" data-route-car-type="<?php echo esc_attr( self::normalize_car_type_key( $car_type ) ); ?>" data-route-image="<?php echo esc_url( (string) $image_url ); ?>">
			<?php if ( ! empty( $slide_attachments ) ) : ?>
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
					<?php
					foreach ( $slide_attachments as $index => $attachment_id ) :
						$is_first_slide = 0 === $index;
						$img_attrs        = array(
							'class'    => 'mttf-card__image' . ( $is_first_slide ? ' is-active' : '' ),
							'alt'      => $route->post_title,
							'loading'  => ( $eager_image && $is_first_slide ) ? 'eager' : 'lazy',
							'decoding' => 'async',
							'sizes'    => '(max-width: 767px) 100vw, (max-width: 1023px) 50vw, 33vw',
						);
						if ( $eager_image && $is_first_slide ) {
							$img_attrs['fetchpriority'] = 'high';
						}
						echo wp_get_attachment_image( (int) $attachment_id, 'mttf-card', false, $img_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					endforeach;
					?>
				</div>
			<?php elseif ( '' !== $image_url ) : ?>
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
					<img
						class="mttf-card__image is-active"
						src="<?php echo esc_url( $image_url ); ?>"
						alt="<?php echo esc_attr( $route->post_title ); ?>"
						width="600"
						height="400"
						loading="<?php echo $eager_image ? 'eager' : 'lazy'; ?>"
						decoding="async"
						<?php echo $eager_image ? 'fetchpriority="high"' : ''; ?>
					/>
				</div>
			<?php endif; ?>
			<h4 class="mttf-card__title"><?php echo esc_html( $route->post_title ); ?></h4>
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
						<img class="mttf-btn__icon" src="<?php echo esc_url( $call_icon_url ); ?>" alt="" aria-hidden="true" width="20" height="20" loading="lazy" decoding="async" />
					<?php endif; ?>
					<span>Tư vấn ngay</span>
				</button>
				<a class="mttf-btn mttf-btn--zalo mttf-js-track" href="<?php echo esc_url( $zalo_link ); ?>" target="_blank" rel="noopener" data-track-event="zalo_click">
					<?php if ( '' !== $zalo_icon_url ) : ?>
						<img class="mttf-btn__icon" src="<?php echo esc_url( $zalo_icon_url ); ?>" alt="" aria-hidden="true" width="20" height="20" loading="lazy" decoding="async" />
					<?php endif; ?>
					<span>Zalo</span>
				</a>
			</div>
		</article>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Lead capture modal (shared by hub and landing pages).
	 *
	 * @return string
	 */
	public static function render_lead_modal() {
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
					<div class="mttf-input-wrap">
						<span class="mttf-input-icon" aria-hidden="true">☎</span>
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

	/**
	 * Hub + landing CSS/JS (card slider, lead modal, tracking).
	 *
	 * @param string $context hub|landing — landing omits hub-only AJAX nonces/activity.
	 */
	public static function enqueue_frontend_assets( $context = 'hub' ) {
		$css_path = MTTF_PATH . 'assets/css/frontend.css';
		$js_path  = MTTF_PATH . 'assets/js/frontend.js';
		$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : MTTF_VERSION;
		$js_ver   = file_exists( $js_path ) ? (string) filemtime( $js_path ) : MTTF_VERSION;

		wp_enqueue_style(
			'mttf-frontend',
			MTTF_URL . 'assets/css/frontend.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'mttf-frontend',
			MTTF_URL . 'assets/js/frontend.js',
			array(),
			$js_ver,
			true
		);

		$mttf_data = array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'mttf_capture_lead' ),
			'measurement' => MTTF_Settings::get_frontend_measurement_payload(),
		);

		if ( 'landing' !== $context ) {
			$mttf_data['searchNonce']        = wp_create_nonce( 'mttf_live_search' );
			$mttf_data['trackSearchNonce']   = wp_create_nonce( 'mttf_track_route_search' );
			$mttf_data['activityEnabled']    = MTTF_Activity_Pings::is_enabled() ? 1 : 0;

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
		}

		wp_localize_script( 'mttf-frontend', 'mttfData', $mttf_data );
	}

	private static function normalize_text( $text ) {
		$text = remove_accents( strtolower( $text ) );
		return preg_replace( '/\s+/', ' ', trim( $text ) );
	}

	/**
	 * @param string $icon_key curated|price|public|support
	 * @return string SVG markup.
	 */
	private static function get_hero_trust_icon_svg( $icon_key ) {
		$icons = array(
			'curated' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 3l2.2 4.5 5 .7-3.6 3.5.9 5-4.5-2.4-4.5 2.4.9-5L4.8 8.2l5-.7L12 3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
			'price'   => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 7h16M4 12h10M4 17h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="18" cy="17" r="3" stroke="currentColor" stroke-width="1.6"/></svg>',
			'public'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.6"/><path d="M8 10h8M8 14h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
			'support' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 5h14a2 2 0 012 2v7a2 2 0 01-2 2h-5l-4 3v-3H5a2 2 0 01-2-2V7a2 2 0 012-2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
		);

		return $icons[ $icon_key ] ?? $icons['curated'];
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
			'bac'   => 'Gợi ý tuyến miền Bắc cho bạn',
			'trung' => 'Gợi ý tuyến miền Trung cho bạn',
			'nam'   => 'Gợi ý tuyến miền Nam cho bạn',
			'khac'  => 'Gợi ý tuyến phù hợp cho bạn',
		);

		return $titles[ $region_key ] ?? 'Gợi ý tuyến phù hợp cho bạn';
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
