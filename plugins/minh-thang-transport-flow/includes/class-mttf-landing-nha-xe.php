<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Ads landing layout for /nha-xe/{slug}/ (presentation only).
 */
class MTTF_Landing_Nha_Xe {

	public static function init() {
		add_action( 'wp_ajax_nopriv_mttf_nha_xe_filter_routes', array( __CLASS__, 'ajax_filter_routes' ) );
		add_action( 'wp_ajax_mttf_nha_xe_filter_routes', array( __CLASS__, 'ajax_filter_routes' ) );
	}

	/**
	 * @param WP_Post[] $routes Routes.
	 * @return string Card HTML.
	 */
	public static function render_route_cards_html( array $routes ) {
		if ( empty( $routes ) ) {
			return '';
		}

		ob_start();
		foreach ( $routes as $card_index => $route ) {
			if ( ! $route instanceof WP_Post ) {
				continue;
			}
			echo MTTF_Shortcode::render_route_card(
				$route,
				array(
					'eager_image' => 0 === $card_index,
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return (string) ob_get_clean();
	}

	public static function ajax_filter_routes() {
		check_ajax_referer( 'mttf_nha_xe_filter', 'nonce' );

		$nha_xe_id   = absint( wp_unslash( $_POST['nha_xe_id'] ?? 0 ) );
		$tuyen_id    = absint( wp_unslash( $_POST['tuyen_id'] ?? 0 ) );
		$nha_xe_term = get_term( $nha_xe_id, MTTF_Landing_Taxonomies::TAX_NHA_XE );

		if ( ! $nha_xe_term instanceof WP_Term || is_wp_error( $nha_xe_term ) ) {
			wp_send_json_error( array( 'message' => 'Invalid operator.' ), 400 );
		}

		$routes  = MTTF_Landing_Query::get_routes_for_nha_xe( $nha_xe_term );
		$routes  = MTTF_Landing_Query::filter_routes_by_tuyen( $routes, $tuyen_id );
		$html    = self::render_route_cards_html( $routes );
		$is_empty = '' === trim( $html );

		wp_send_json_success(
			array(
				'html'  => $is_empty ? '' : $html,
				'count' => count( $routes ),
				'empty' => $is_empty,
			)
		);
	}

	/**
	 * @param WP_Term   $term   Queried nhà xe term.
	 * @param WP_Post[] $routes Active route cards.
	 */
	public static function render_page( $term, array $routes ) {
		$groups    = MTTF_Landing_Query::group_routes_by_tuyen( $routes );
		$stats     = MTTF_Landing_Tuyen::collect_route_stats( $routes );
		$faq_items = self::get_faq_items( $term );
		$zalo_url  = self::get_first_zalo_url( $routes );
		$first_id  = ! empty( $routes[0] ) ? (int) $routes[0]->ID : 0;
		$tuyen_cnt = count( $groups );
		$card_cnt  = count( $routes );

		?>
		<div
			class="mttf mttf-landing mttf-landing--nha-xe"
			data-landing-type="nha-xe"
			<?php if ( $first_id > 0 ) : ?>
				data-landing-first-route-id="<?php echo esc_attr( (string) $first_id ); ?>"
			<?php endif; ?>
		>
			<?php self::render_hero( $term, $stats, $tuyen_cnt, $card_cnt, $routes ); ?>
			<?php self::render_routes( $term, $groups, $routes ); ?>
			<?php self::render_benefits( $term ); ?>
			<?php self::render_personas( $term ); ?>
			<?php self::render_steps( $term ); ?>
			<?php self::render_faq( $term, $faq_items ); ?>
			<?php self::render_final_cta( $term, $zalo_url ); ?>

			<?php echo MTTF_Shortcode::render_lead_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
	}

	/**
	 * @param WP_Term $term Term.
	 * @return string
	 */
	private static function get_operator_name( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return '';
		}

		return trim( (string) $term->name );
	}

	/**
	 * @param WP_Term $term Nhà xe term.
	 * @return string Logo URL or empty.
	 */
	private static function get_operator_logo_url( $term ) {
		$logo_id = (int) get_term_meta( (int) $term->term_id, '_mttf_nha_xe_logo', true );
		if ( $logo_id > 0 ) {
			$url = wp_get_attachment_image_url( $logo_id, 'medium' );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		$url = trim( (string) get_term_meta( (int) $term->term_id, '_mttf_nha_xe_logo_url', true ) );
		if ( '' !== $url ) {
			return esc_url( $url );
		}

		return '';
	}

	/**
	 * Logo → ảnh card đầu tiên → rỗng (dùng icon).
	 *
	 * @param WP_Term   $term   Term.
	 * @param WP_Post[] $routes Routes.
	 * @return string
	 */
	private static function get_operator_visual_url( $term, array $routes ) {
		$logo = self::get_operator_logo_url( $term );
		if ( '' !== $logo ) {
			return $logo;
		}

		return self::get_first_route_image_url( $routes );
	}

	/**
	 * @param WP_Post[] $routes Routes.
	 * @return string
	 */
	private static function get_first_route_image_url( array $routes ) {
		foreach ( $routes as $route ) {
			if ( ! $route instanceof WP_Post ) {
				continue;
			}

			$thumb = get_the_post_thumbnail_url( $route->ID, 'medium_large' );
			if ( is_string( $thumb ) && '' !== $thumb ) {
				return $thumb;
			}

			$raw_ids = trim( (string) get_post_meta( $route->ID, '_mttf_gallery_ids', true ) );
			if ( '' === $raw_ids ) {
				continue;
			}

			$ids = preg_split( '/[\s,]+/', $raw_ids );
			if ( ! is_array( $ids ) ) {
				continue;
			}

			foreach ( $ids as $id ) {
				$id = absint( $id );
				if ( $id <= 0 ) {
					continue;
				}
				$url = wp_get_attachment_image_url( $id, 'medium_large' );
				if ( is_string( $url ) && '' !== $url ) {
					return $url;
				}
			}
		}

		return '';
	}

	/**
	 * @param array<string,mixed> $stats Route stats.
	 * @return string
	 */
	private static function format_hero_car_types_label( array $stats ) {
		$short = trim( (string) ( $stats['car_types_short'] ?? '' ) );
		if ( '' === $short ) {
			return 'Cabin VIP, Limousine';
		}

		$short = str_replace( ' và lựa chọn khác', ' + lựa chọn khác', $short );

		if ( function_exists( 'mb_strlen' ) && mb_strlen( $short ) > 42 ) {
			$parts = array_values( array_filter( array_map( 'trim', explode( ',', $short ) ) ) );
			if ( count( $parts ) > 2 ) {
				return $parts[0] . ', ' . $parts[1] . ' + lựa chọn khác';
			}
		}

		return $short;
	}

	/**
	 * @param WP_Term              $term       Term.
	 * @param array<string,mixed>  $stats      Stats.
	 * @param int                  $tuyen_cnt  Tuyến count.
	 * @param int                  $card_cnt   Card count.
	 * @param WP_Post[]            $routes     Routes (for visual fallback).
	 */
	private static function render_hero( $term, array $stats, $tuyen_cnt, $card_cnt, array $routes = array() ) {
		$name        = self::get_operator_name( $term );
		$h1          = class_exists( 'MTTF_Landing_SEO', false ) ? MTTF_Landing_SEO::get_h1() : sprintf( 'Nhà xe %s', $name );
		$desc        = class_exists( 'MTTF_Landing_SEO', false ) ? MTTF_Landing_SEO::get_hero_description() : trim( (string) $term->description );
		$car_label   = self::format_hero_car_types_label( $stats );
		$visual_url  = self::get_operator_visual_url( $term, $routes );
		$has_logo    = '' !== self::get_operator_logo_url( $term );
		$media_class = 'mttf-landing-hero__overview-media';
		if ( '' !== $visual_url ) {
			$media_class .= $has_logo ? ' mttf-landing-hero__overview-media--logo' : ' mttf-landing-hero__overview-media--photo';
		}
		$tuyen_cnt   = max( 0, (int) $tuyen_cnt );
		$card_cnt    = max( 0, (int) $card_cnt );
		$tuyen_stat  = $tuyen_cnt > 0 ? sprintf( '%d tuyến', $tuyen_cnt ) : 'Đang cập nhật';
		$choice_stat = $card_cnt > 0 ? sprintf( '%d lựa chọn xe', $card_cnt ) : 'Đang cập nhật';

		if ( '' === $desc ) {
			$desc = sprintf(
				'Tổng hợp các tuyến đang được khai thác bởi %1$s. Khách có thể xem nhanh giá tham khảo, loại xe, tiện ích và để lại số điện thoại để được tư vấn chuyến phù hợp.',
				$name
			);
		}
		?>
		<header class="mttf-landing-hero mttf-landing-hero--nha-xe mttf-landing-hero--premium">
			<div class="mttf-landing-hero__shell">
				<div class="mttf-landing-hero__inner">
					<div class="mttf-landing-hero__main">
						<p class="mttf-landing-hero__eyebrow"><?php esc_html_e( 'Nhà xe chọn lọc', 'minh-thang-transport-flow' ); ?></p>
						<h1 class="mttf-landing-hero__title"><?php echo esc_html( $h1 ); ?></h1>
						<p class="mttf-landing-hero__desc"><?php echo esc_html( wp_strip_all_tags( $desc ) ); ?></p>
						<ul class="mttf-landing-hero__highlights" role="list">
							<?php if ( $tuyen_cnt > 0 ) : ?>
								<li><?php echo esc_html( sprintf( '%d tuyến đang khai thác', $tuyen_cnt ) ); ?></li>
							<?php endif; ?>
							<?php if ( $card_cnt > 0 ) : ?>
								<li><?php echo esc_html( sprintf( '%d lựa chọn xe', $card_cnt ) ); ?></li>
							<?php endif; ?>
							<li><?php echo esc_html( sprintf( 'Loại xe nổi bật: %s', $car_label ) ); ?></li>
							<li><?php esc_html_e( 'Hỗ trợ tư vấn điểm đón/trả', 'minh-thang-transport-flow' ); ?></li>
						</ul>
						<div class="mttf-landing-hero__actions">
							<button type="button" class="mttf-landing-btn mttf-landing-btn--primary mttf-landing-trigger-lead" <?php echo $card_cnt > 0 ? '' : 'disabled'; ?>>
								<?php esc_html_e( 'Tư vấn nhà xe này', 'minh-thang-transport-flow' ); ?>
							</button>
							<a class="mttf-landing-btn mttf-landing-btn--ghost" href="#mttf-landing-services">
								<?php esc_html_e( 'Xem các tuyến', 'minh-thang-transport-flow' ); ?>
							</a>
						</div>
					</div>
					<aside class="mttf-landing-hero__aside" aria-label="<?php esc_attr_e( 'Tổng quan nhà xe', 'minh-thang-transport-flow' ); ?>">
						<div class="mttf-landing-hero__overview">
							<p class="mttf-landing-hero__overview-label"><?php esc_html_e( 'Tổng quan nhà xe', 'minh-thang-transport-flow' ); ?></p>
							<div class="<?php echo esc_attr( $media_class ); ?>">
								<?php if ( '' !== $visual_url ) : ?>
									<img src="<?php echo esc_url( $visual_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="320" height="180" loading="lazy" decoding="async" />
								<?php else : ?>
									<span class="mttf-landing-hero__overview-icon" aria-hidden="true">
										<svg width="40" height="40" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M12 30h24v2H12v-2zm3-12h18a2 2 0 012 2v6H13v-6a2 2 0 012-2z" fill="currentColor" opacity=".95"/>
										</svg>
									</span>
								<?php endif; ?>
							</div>
							<h2 class="mttf-landing-hero__overview-title"><?php echo esc_html( 'Nhà xe ' . $name ); ?></h2>
							<p class="mttf-landing-hero__overview-desc">
								<?php esc_html_e( 'Đang được tổng hợp tại Đặt Xe Việt Nam với các tuyến và lựa chọn xe đang khai thác.', 'minh-thang-transport-flow' ); ?>
							</p>
							<dl class="mttf-landing-hero__overview-stats">
								<div class="mttf-landing-hero__overview-stat">
									<dt><?php esc_html_e( 'Tuyến', 'minh-thang-transport-flow' ); ?></dt>
									<dd><?php echo esc_html( $tuyen_stat ); ?></dd>
								</div>
								<div class="mttf-landing-hero__overview-stat">
									<dt><?php esc_html_e( 'Lựa chọn', 'minh-thang-transport-flow' ); ?></dt>
									<dd><?php echo esc_html( $choice_stat ); ?></dd>
								</div>
								<div class="mttf-landing-hero__overview-stat">
									<dt><?php esc_html_e( 'Loại xe', 'minh-thang-transport-flow' ); ?></dt>
									<dd><?php echo esc_html( $car_label ); ?></dd>
								</div>
								<div class="mttf-landing-hero__overview-stat">
									<dt><?php esc_html_e( 'Hỗ trợ', 'minh-thang-transport-flow' ); ?></dt>
									<dd><?php esc_html_e( 'Tư vấn đón/trả', 'minh-thang-transport-flow' ); ?></dd>
								</div>
							</dl>
							<a class="mttf-landing-hero__overview-cta" href="#mttf-landing-services">
								<?php esc_html_e( 'Xem các tuyến', 'minh-thang-transport-flow' ); ?>
							</a>
						</div>
					</aside>
				</div>
			</div>
		</header>
		<?php
	}

	/**
	 * @param WP_Term                                              $term   Term.
	 * @param array<int,array{term:WP_Term,routes:WP_Post[]}>     $groups Grouped routes (for filters).
	 * @param WP_Post[]                                            $routes All routes for nhà xe.
	 */
	private static function render_routes( $term, array $groups, array $routes ) {
		$section_title = class_exists( 'MTTF_Landing_SEO', false )
			? MTTF_Landing_SEO::get_services_section_title()
			: sprintf( 'Các tuyến của nhà xe %s', self::get_operator_name( $term ) );
		$has_routes    = ! empty( $routes );
		?>
		<section
			class="mttf-landing-section mttf-landing-routes"
			id="mttf-landing-services"
			aria-labelledby="mttf-landing-routes-title"
			data-mttf-nha-xe-id="<?php echo esc_attr( (string) $term->term_id ); ?>"
		>
			<div class="mttf-landing-section__head">
				<h2 id="mttf-landing-routes-title" class="mttf-landing-section__title">
					<?php echo esc_html( $section_title ); ?>
				</h2>
			</div>
			<?php if ( ! $has_routes ) : ?>
				<div class="mttf-landing-empty">
					<p><?php esc_html_e( 'Chưa có dịch vụ nào được gán cho nhà xe này.', 'minh-thang-transport-flow' ); ?></p>
				</div>
			<?php else : ?>
				<div class="mttf-landing-route-filter" role="tablist" aria-label="<?php esc_attr_e( 'Lọc theo tuyến', 'minh-thang-transport-flow' ); ?>">
					<button
						type="button"
						class="mttf-landing-route-filter__pill is-active"
						role="tab"
						aria-selected="true"
						data-tuyen-id="0"
					>
						<?php esc_html_e( 'Tất cả', 'minh-thang-transport-flow' ); ?>
					</button>
					<?php foreach ( $groups as $group ) : ?>
						<?php
						$tuyen_term = $group['term'] ?? null;
						if ( ! $tuyen_term instanceof WP_Term || empty( $group['routes'] ) ) {
							continue;
						}
						?>
						<button
							type="button"
							class="mttf-landing-route-filter__pill"
							role="tab"
							aria-selected="false"
							data-tuyen-id="<?php echo esc_attr( (string) $tuyen_term->term_id ); ?>"
						>
							<?php echo esc_html( $tuyen_term->name ); ?>
						</button>
					<?php endforeach; ?>
				</div>
				<div class="mttf-landing-routes__panel" data-mttf-routes-panel>
					<div class="mttf-landing-routes__status" data-mttf-routes-status hidden aria-live="polite"></div>
					<div class="mttf-hub__track mttf-hub__track--landing mttf-landing-grid mttf-landing-routes__grid" data-mttf-routes-grid>
						<?php echo self::render_route_cards_html( $routes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<p class="mttf-landing-routes__empty" data-mttf-routes-empty hidden>
						<?php esc_html_e( 'Chưa có lựa chọn xe phù hợp với tuyến này.', 'minh-thang-transport-flow' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * @param WP_Term $term Term.
	 */
	private static function render_benefits( $term ) {
		$name  = self::get_operator_name( $term );
		$items = array(
			array(
				'icon'  => 'routes',
				'title' => 'Tuyến được tổng hợp rõ ràng',
				'desc'  => sprintf( 'Xem nhanh các tuyến mà %s đang phục vụ.', $name ),
			),
			array(
				'icon'  => 'compare',
				'title' => 'Dễ so sánh lựa chọn',
				'desc'  => 'So sánh giá, loại xe và tiện ích trước khi để lại thông tin.',
			),
			array(
				'icon'  => 'support',
				'title' => 'Tư vấn theo nhu cầu thực tế',
				'desc'  => 'Hỗ trợ chọn tuyến, khung giờ, điểm đón và điểm trả phù hợp.',
			),
			array(
				'icon'  => 'card',
				'title' => 'Đặt xe nhanh qua card',
				'desc'  => 'Chỉ cần bấm vào card hoặc nút tư vấn để gửi thông tin liên hệ.',
			),
		);
		?>
		<section class="mttf-landing-section mttf-landing-benefits" aria-labelledby="mttf-landing-nx-benefits-title">
			<div class="mttf-landing-section__head">
				<h2 id="mttf-landing-nx-benefits-title" class="mttf-landing-section__title">
					<?php echo esc_html( sprintf( 'Vì sao nên chọn nhà xe %s tại Đặt Xe Việt Nam?', $name ) ); ?>
				</h2>
			</div>
			<div class="mttf-landing-benefits__grid">
				<?php foreach ( $items as $item ) : ?>
					<article class="mttf-landing-benefits__card">
						<div class="mttf-landing-benefits__icon" aria-hidden="true">
							<?php echo self::get_benefit_icon_svg( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<h3 class="mttf-landing-benefits__title"><?php echo esc_html( $item['title'] ); ?></h3>
						<p class="mttf-landing-benefits__desc"><?php echo esc_html( $item['desc'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * @param WP_Term $term Term.
	 */
	private static function render_personas( $term ) {
		$operator_name = self::get_operator_name( $term );
		$items         = array(
			array(
				'slug'  => 'travel',
				'badge' => 'Du lịch',
				'title' => 'Khách đi du lịch',
				'desc'  => 'Cần tuyến rõ ràng, giờ chạy phù hợp lịch tham quan và tiện ích ổn trên đường.',
				'hint'  => 'Xem nhóm tuyến du lịch phổ biến bên dưới.',
			),
			array(
				'slug'  => 'business',
				'badge' => 'Công tác',
				'title' => 'Khách đi công tác',
				'desc'  => 'Ưu tiên khung giờ sát lịch làm việc và phương án xe yên tĩnh, linh hoạt.',
				'hint'  => 'So sánh limousine hoặc cabin VIP trên card.',
			),
			array(
				'slug'  => 'family',
				'badge' => 'Gia đình',
				'title' => 'Gia đình / nhóm nhỏ',
				'desc'  => 'Cần chỗ ngồi thoải mái, điểm đón thuận tiện và tư vấn rõ từng tuyến.',
				'hint'  => 'Chọn tuyến rồi bấm tư vấn trên card phù hợp.',
			),
			array(
				'slug'  => 'fast',
				'badge' => 'Nhanh',
				'title' => 'Khách cần tư vấn nhanh',
				'desc'  => 'Chưa rõ nên chọn tuyến nào — để lại số điện thoại để được gợi ý nhanh.',
				'hint'  => 'Bấm «Tư vấn ngay» trên card hoặc CTA cuối trang.',
			),
		);
		?>
		<section class="mttf-landing-section mttf-landing-personas" aria-labelledby="mttf-landing-personas-title">
			<div class="mttf-landing-section__head">
				<h2 id="mttf-landing-personas-title" class="mttf-landing-section__title">
					<?php echo esc_html( sprintf( 'Nhà xe %s phù hợp với ai?', $operator_name ) ); ?>
				</h2>
			</div>
			<div class="mttf-landing-tips__grid mttf-landing-personas__grid">
				<?php foreach ( $items as $item ) : ?>
					<article class="mttf-landing-tips__card mttf-landing-personas__card mttf-landing-tips__card--<?php echo esc_attr( $item['slug'] ); ?>">
						<span class="mttf-landing-tips__badge"><?php echo esc_html( $item['badge'] ); ?></span>
						<div class="mttf-landing-tips__icon" aria-hidden="true">
							<?php echo self::get_persona_icon_svg( $item['slug'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<h3 class="mttf-landing-tips__title"><?php echo esc_html( $item['title'] ); ?></h3>
						<p class="mttf-landing-tips__desc"><?php echo esc_html( $item['desc'] ); ?></p>
						<p class="mttf-landing-tips__hint">
							<span class="mttf-landing-tips__hint-label"><?php esc_html_e( 'Gợi ý', 'minh-thang-transport-flow' ); ?></span>
							<?php echo esc_html( $item['hint'] ); ?>
						</p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * @param WP_Term $term Term.
	 */
	private static function render_steps( $term ) {
		$name  = self::get_operator_name( $term );
		$steps = array(
			array(
				'icon'  => 'route',
				'title' => 'Chọn tuyến phù hợp',
				'desc'  => sprintf( 'Xem các nhóm tuyến mà %s đang khai thác và chọn tuyến phù hợp.', $name ),
			),
			array(
				'icon'  => 'card',
				'title' => 'Bấm tư vấn trên card',
				'desc'  => 'Chọn card trong tuyến, bấm «Tư vấn ngay» hoặc mở Zalo trên card để liên hệ.',
			),
			array(
				'icon'  => 'confirm',
				'title' => 'Nhân viên xác nhận chuyến',
				'desc'  => 'Đội ngũ hỗ trợ xác nhận khung giờ, điểm đón/trả và phương án phù hợp.',
			),
		);
		?>
		<section class="mttf-landing-section mttf-landing-steps" aria-labelledby="mttf-landing-nx-steps-title">
			<div class="mttf-landing-section__head">
				<h2 id="mttf-landing-nx-steps-title" class="mttf-landing-section__title">
					<?php echo esc_html( sprintf( 'Đặt xe %s như thế nào?', $name ) ); ?>
				</h2>
			</div>
			<div class="mttf-landing-steps__track">
				<ol class="mttf-landing-steps__list">
					<?php foreach ( $steps as $index => $step ) : ?>
						<li class="mttf-landing-steps__item">
							<span class="mttf-landing-steps__step-num" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
							<div class="mttf-landing-steps__icon" aria-hidden="true">
								<?php echo self::get_step_icon_svg( $step['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<h3 class="mttf-landing-steps__title"><?php echo esc_html( $step['title'] ); ?></h3>
							<p class="mttf-landing-steps__desc"><?php echo esc_html( $step['desc'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</section>
		<?php
	}

	/**
	 * @param WP_Term $term Term.
	 * @return array<int,array{q:string,a:string}>
	 */
	public static function get_faq_items( $term ) {
		$operator_name = self::get_operator_name( $term );
		$raw           = get_term_meta( (int) $term->term_id, '_mttf_nha_xe_faq', true );

		if ( is_string( $raw ) && '' !== trim( $raw ) ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) && ! empty( $decoded ) ) {
				$items = array();
				foreach ( $decoded as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$q = trim( (string) ( $row['q'] ?? $row['question'] ?? '' ) );
					$a = trim( (string) ( $row['a'] ?? $row['answer'] ?? '' ) );
					if ( '' !== $q && '' !== $a ) {
						$items[] = array( 'q' => $q, 'a' => $a );
					}
				}
				if ( ! empty( $items ) ) {
					return self::apply_operator_tokens_to_faq_items( $items, $operator_name );
				}
			}
		}

		return self::get_default_faq_items( $operator_name );
	}

	/**
	 * @param string $text          FAQ text.
	 * @param string $operator_name Operator name.
	 * @return string
	 */
	private static function apply_operator_tokens( $text, $operator_name ) {
		$search = array( '{Tên nhà xe}', '{tên nhà xe}', '{operator_name}', '{name}' );

		return str_replace( $search, array_fill( 0, count( $search ), (string) $operator_name ), (string) $text );
	}

	/**
	 * @param array<int,array{q:string,a:string}> $items         FAQ rows.
	 * @param string                              $operator_name Operator name.
	 * @return array<int,array{q:string,a:string}>
	 */
	private static function apply_operator_tokens_to_faq_items( array $items, $operator_name ) {
		foreach ( $items as $index => $item ) {
			$items[ $index ]['q'] = self::apply_operator_tokens( $item['q'], $operator_name );
			$items[ $index ]['a'] = self::apply_operator_tokens( $item['a'], $operator_name );
		}

		return $items;
	}

	/**
	 * @param string $operator_name Term name.
	 * @return array<int,array{q:string,a:string}>
	 */
	private static function get_default_faq_items( $operator_name ) {
		$items = array(
			array(
				'q' => 'Nhà xe {Tên nhà xe} đang chạy những tuyến nào?',
				'a' => 'Trang này tổng hợp các tuyến mà {Tên nhà xe} đang khai thác, được nhóm theo từng tuyến để bạn xem và so sánh card bên dưới.',
			),
			array(
				'q' => 'Đặt vé {Tên nhà xe} như thế nào?',
				'a' => 'Chọn tuyến phù hợp, bấm «Tư vấn ngay» trên card hoặc để lại số điện thoại qua CTA — nhân viên hỗ trợ xác nhận chuyến với {Tên nhà xe}.',
			),
			array(
				'q' => 'Bấm vào card thì có đặt vé được không?',
				'a' => 'Card giúp xem giá tham khảo và gửi yêu cầu tư vấn. Bấm tư vấn trên card để để lại số điện thoại, đội ngũ sẽ gọi lại hỗ trợ đặt với {Tên nhà xe}.',
			),
			array(
				'q' => 'Giá vé {Tên nhà xe} được hiển thị như thế nào?',
				'a' => 'Giá «từ» trên từng card là tham khảo theo loại xe và tuyến. Giá chính xác có thể thay đổi theo khung giờ — tư vấn sẽ báo khi bạn liên hệ.',
			),
			array(
				'q' => 'Có hỗ trợ tư vấn điểm đón và điểm trả không?',
				'a' => 'Có. Sau khi để lại thông tin, nhân viên hỗ trợ chọn điểm đón/trả phù hợp với tuyến bạn chọn của {Tên nhà xe}.',
			),
			array(
				'q' => 'Có thể nhắn Zalo để được tư vấn không?',
				'a' => 'Có. Trên card có nút Zalo (nếu nhà xe cung cấp link). Bạn cũng có thể dùng nút Chat Zalo ở cuối trang.',
			),
			array(
				'q' => 'Thông tin tuyến của {Tên nhà xe} có được cập nhật không?',
				'a' => 'Danh sách card hiển thị các dịch vụ đang kích hoạt trên hệ thống. Khi có thay đổi, thông tin trên card được cập nhật tương ứng.',
			),
			array(
				'q' => 'Tôi nên chọn tuyến nào của {Tên nhà xe}?',
				'a' => 'Xem từng nhóm tuyến bên dưới, so sánh giá và loại xe trên card — hoặc bấm tư vấn để nhân viên gợi ý tuyến phù hợp nhu cầu của bạn.',
			),
		);

		return self::apply_operator_tokens_to_faq_items( $items, $operator_name );
	}

	/**
	 * @param WP_Term                              $term      Term.
	 * @param array<int,array{q:string,a:string}> $faq_items FAQ rows.
	 */
	private static function render_faq( $term, array $faq_items ) {
		unset( $term );
		?>
		<section class="mttf-landing-section mttf-landing-faq" aria-labelledby="mttf-landing-nx-faq-title">
			<div class="mttf-landing-section__head">
				<h2 id="mttf-landing-nx-faq-title" class="mttf-landing-section__title">
					<?php esc_html_e( 'Câu hỏi thường gặp', 'minh-thang-transport-flow' ); ?>
				</h2>
			</div>
			<div class="mttf-landing-faq__list">
				<?php foreach ( $faq_items as $item ) : ?>
					<details class="mttf-landing-faq__item">
						<summary><?php echo esc_html( $item['q'] ); ?></summary>
						<div class="mttf-landing-faq__body">
							<p><?php echo esc_html( $item['a'] ); ?></p>
						</div>
					</details>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * @param WP_Term $term     Term.
	 * @param string  $zalo_url Zalo URL.
	 */
	private static function render_final_cta( $term, $zalo_url ) {
		$name = self::get_operator_name( $term );
		?>
		<section class="mttf-landing-section mttf-landing-cta-final" aria-labelledby="mttf-landing-nx-cta-title">
			<div class="mttf-landing-cta-final__inner">
				<h2 id="mttf-landing-nx-cta-title" class="mttf-landing-cta-final__title">
					<?php echo esc_html( sprintf( 'Bạn cần tư vấn nhà xe %s?', $name ) ); ?>
				</h2>
				<p class="mttf-landing-cta-final__desc">
					<?php esc_html_e( 'Xem các tuyến đang có bên trên hoặc để lại số điện thoại để được hỗ trợ chọn chuyến, khung giờ và điểm đón/trả phù hợp.', 'minh-thang-transport-flow' ); ?>
				</p>
				<div class="mttf-landing-cta-final__actions">
					<button type="button" class="mttf-landing-btn mttf-landing-btn--primary mttf-landing-trigger-lead">
						<?php esc_html_e( 'Tư vấn ngay', 'minh-thang-transport-flow' ); ?>
					</button>
					<?php if ( '' !== $zalo_url ) : ?>
						<a class="mttf-landing-btn mttf-landing-btn--zalo" href="<?php echo esc_url( $zalo_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Chat Zalo', 'minh-thang-transport-flow' ); ?>
						</a>
					<?php else : ?>
						<a class="mttf-landing-btn mttf-landing-btn--zalo" href="#mttf-landing-services">
							<?php esc_html_e( 'Xem các tuyến', 'minh-thang-transport-flow' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * @param WP_Post[] $routes Routes.
	 * @return string
	 */
	private static function get_first_zalo_url( array $routes ) {
		foreach ( $routes as $route ) {
			if ( ! $route instanceof WP_Post ) {
				continue;
			}
			$url = esc_url( (string) get_post_meta( $route->ID, '_mttf_zalo_link', true ) );
			if ( '' !== $url && '#' !== $url ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * @param string $icon Icon key.
	 * @return string
	 */
	private static function get_benefit_icon_svg( $icon ) {
		$icons = array(
			'routes'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h10M4 18h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
			'compare' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M7 4v16M17 4v16M4 9h6M14 15h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
			'support' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 21s6-4.35 6-10a6 6 0 10-12 0c0 5.65 6 10 6 10z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="11" r="2.2" stroke="currentColor" stroke-width="1.8"/></svg>',
			'card'    => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="5" y="4" width="14" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M9 9h6M9 13h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
		);

		return $icons[ $icon ] ?? $icons['routes'];
	}

	/**
	 * @param string $slug Persona slug.
	 * @return string
	 */
	private static function get_persona_icon_svg( $slug ) {
		$icons = array(
			'travel'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M6 18l2-9 4-2 2 4 4-1-2 8H6z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
			'business' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 9h16v10H4V9z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8 9V6h8v3" stroke="currentColor" stroke-width="1.7"/></svg>',
			'family'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="8" r="2.5" stroke="currentColor" stroke-width="1.7"/><circle cx="16" cy="9" r="2" stroke="currentColor" stroke-width="1.7"/><path d="M5 19c.6-2.4 2.4-4 4-4s3.4 1.6 4 4M13 19c.5-1.8 1.8-3 3.5-3S19.5 17.2 20 19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
			'fast'     => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M13 3L5 14h6l-1 7 9-12h-6l0-6z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
		);

		return $icons[ $slug ] ?? $icons['fast'];
	}

	/**
	 * @param string $icon Step icon key.
	 * @return string
	 */
	private static function get_step_icon_svg( $icon ) {
		$icons = array(
			'route'   => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 21s6-4.35 6-10a6 6 0 10-12 0c0 5.65 6 10 6 10z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="11" r="2.2" stroke="currentColor" stroke-width="1.8"/></svg>',
			'card'    => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="5" y="4" width="14" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M9 9h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
			'confirm' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M9 12l2.2 2.2L16 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/></svg>',
		);

		return $icons[ $icon ] ?? $icons['route'];
	}
}
