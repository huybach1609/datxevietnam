<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Ads landing layout for /tuyen/{slug}/ (presentation only).
 */
class MTTF_Landing_Tuyen {

	/**
	 * @param WP_Term   $term   Queried tuyen term.
	 * @param WP_Post[] $routes Active route cards.
	 */
	public static function render_page( $term, array $routes ) {
		$stats     = self::collect_route_stats( $routes );
		$faq_items = self::get_faq_items( $term );
		$zalo_url  = self::get_first_zalo_url( $routes );
		$first_id  = ! empty( $routes[0] ) ? (int) $routes[0]->ID : 0;

		?>
		<div
			class="mttf mttf-landing mttf-landing--tuyen"
			data-landing-type="tuyen"
			<?php if ( $first_id > 0 ) : ?>
				data-landing-first-route-id="<?php echo esc_attr( (string) $first_id ); ?>"
			<?php endif; ?>
		>
			<?php self::render_hero( $term, $stats, count( $routes ) ); ?>
			<?php self::render_services( $term, $routes ); ?>
			<?php self::render_benefits( $term ); ?>
			<?php self::render_vehicle_compare( $term ); ?>
			<?php self::render_steps(); ?>
			<?php self::render_tips(); ?>
			<?php self::render_faq( $term, $faq_items ); ?>
			<?php self::render_final_cta( $term, $zalo_url ); ?>

			<?php echo MTTF_Shortcode::render_lead_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
	}

	/**
	 * @param WP_Post[] $routes Routes.
	 * @return array<string,mixed>
	 */
	public static function collect_route_stats( array $routes ) {
		$min_price   = 0;
		$car_types   = array();
		$trip_times  = array();

		foreach ( $routes as $route ) {
			if ( ! $route instanceof WP_Post ) {
				continue;
			}

			$price = (int) get_post_meta( $route->ID, '_mttf_price_from', true );
			if ( $price > 0 && ( 0 === $min_price || $price < $min_price ) ) {
				$min_price = $price;
			}

			$car = trim( (string) get_post_meta( $route->ID, '_mttf_car_type', true ) );
			if ( '' !== $car ) {
				$car_types[ sanitize_title( $car ) ] = $car;
			}

			$trip = trim( (string) get_post_meta( $route->ID, '_mttf_trip_frequency', true ) );
			if ( '' !== $trip ) {
				$trip_times[ sanitize_title( $trip ) ] = $trip;
			}
		}

		$car_label = ! empty( $car_types )
			? implode( ', ', array_values( $car_types ) )
			: 'Cabin VIP, Limousine, Giường nằm';

		$trip_label = ! empty( $trip_times )
			? implode( ', ', array_values( $trip_times ) )
			: 'Tùy chuyến';

		return array(
			'min_price'          => $min_price,
			'car_types'          => $car_label,
			'car_types_short'    => self::shorten_car_types_label( $car_types ),
			'car_types_marketing'=> self::build_car_types_marketing_line( $car_types ),
			'trip_times'         => $trip_label,
			'support'            => 'Tư vấn điểm đón/trả',
		);
	}

	/**
	 * @param array<string,string> $car_types Map slug => label.
	 * @return string
	 */
	private static function shorten_car_types_label( array $car_types ) {
		if ( empty( $car_types ) ) {
			return 'Cabin VIP, Limousine';
		}

		$labels = array_values( $car_types );
		if ( count( $labels ) <= 2 ) {
			return implode( ', ', $labels );
		}

		return implode( ', ', array_slice( $labels, 0, 2 ) ) . ' và lựa chọn khác';
	}

	/**
	 * Hãng xe line for hero (count = số card hiển thị).
	 *
	 * @param int    $count Card count.
	 * @param string $style bullet|quickbar
	 * @return string
	 */
	private static function format_route_choice_count_label( $count, $style = 'bullet' ) {
		unset( $style );
		$count = max( 0, (int) $count );
		if ( $count <= 0 ) {
			return 'Đang cập nhật';
		}

		return sprintf( '%d hãng xe đang phục vụ tuyến này', $count );
	}

	/**
	 * @param array<string,string> $car_types Map slug => label.
	 * @return string
	 */
	private static function build_car_types_marketing_line( array $car_types ) {
		$haystack = strtolower( implode( ' ', array_values( $car_types ) ) );
		$parts    = array();

		if ( false !== strpos( $haystack, 'cabin' ) || false !== strpos( $haystack, 'vip' ) ) {
			$parts[] = 'Cabin VIP';
		}
		if ( false !== strpos( $haystack, 'limousine' ) || false !== strpos( $haystack, 'limo' ) ) {
			$parts[] = 'Limousine';
		}
		if ( empty( $parts ) && ! empty( $car_types ) ) {
			$parts = array_slice( array_values( $car_types ), 0, 2 );
		}
		if ( empty( $parts ) ) {
			$parts = array( 'Cabin VIP', 'Limousine' );
		}

		$parts[] = 'Xe chất lượng';

		return implode( ' • ', $parts );
	}

	/**
	 * @param WP_Term $term Term.
	 * @return array<int,array{q:string,a:string}>
	 */
	public static function get_faq_items( $term ) {
		$route_name = self::get_tuyen_display_name( $term );
		$raw        = get_term_meta( (int) $term->term_id, '_mttf_tuyen_faq', true );

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
					return self::apply_route_tokens_to_faq_items( $items, $route_name );
				}
			}
		}

		return self::get_default_faq_items( $route_name );
	}

	/**
	 * @param WP_Term $term Tuyen term.
	 * @return string
	 */
	private static function get_tuyen_display_name( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return '';
		}

		return trim( (string) $term->name );
	}

	/**
	 * Replace route placeholders in FAQ copy (custom meta or defaults).
	 *
	 * @param string $text       FAQ text.
	 * @param string $route_name Current tuyen name.
	 * @return string
	 */
	private static function apply_route_tokens( $text, $route_name ) {
		$route_name = (string) $route_name;
		$search     = array( '{Tên tuyến}', '{tên tuyến}', '{route_name}', '{name}' );

		return str_replace( $search, array_fill( 0, count( $search ), $route_name ), (string) $text );
	}

	/**
	 * @param array<int,array{q:string,a:string}> $items      FAQ rows.
	 * @param string                              $route_name Tuyen name.
	 * @return array<int,array{q:string,a:string}>
	 */
	private static function apply_route_tokens_to_faq_items( array $items, $route_name ) {
		foreach ( $items as $index => $item ) {
			$items[ $index ]['q'] = self::apply_route_tokens( $item['q'], $route_name );
			$items[ $index ]['a'] = self::apply_route_tokens( $item['a'], $route_name );
		}

		return $items;
	}

	/**
	 * @param string $route_name Term name (mttf_tuyen).
	 * @return array<int,array{q:string,a:string}>
	 */
	private static function get_default_faq_items( $route_name ) {
		$name = (string) $route_name;

		$items = array(
			array(
				'q' => 'Xe {Tên tuyến} giá từ bao nhiêu?',
				'a' => 'Giá xe {Tên tuyến} phụ thuộc vào từng loại xe và nhà xe. Bạn có thể xem giá tham khảo trên các card hoặc để lại số điện thoại để được tư vấn.',
			),
			array(
				'q' => 'Tuyến {Tên tuyến} có những loại xe nào?',
				'a' => 'Tuyến {Tên tuyến} thường có Cabin VIP, Limousine hoặc giường nằm tùy nhà xe. Danh sách loại xe đang mở bán nằm ở phần card bên dưới.',
			),
			array(
				'q' => 'Có cần đặt vé trước không?',
				'a' => 'Nên đặt trước, nhất là cuối tuần và dịp lễ, để giữ chỗ và chọn điểm đón phù hợp trên tuyến {Tên tuyến}.',
			),
			array(
				'q' => 'Có hỗ trợ tư vấn điểm đón và điểm trả không?',
				'a' => 'Có. Sau khi để lại số điện thoại, nhân viên sẽ gọi tư vấn điểm đón/trả và khung giờ phù hợp cho tuyến {Tên tuyến}.',
			),
			array(
				'q' => 'Thời gian di chuyển tuyến {Tên tuyến} khoảng bao lâu?',
				'a' => 'Thời gian di chuyển tuyến {Tên tuyến} tùy loại xe, điểm đón và tình trạng giao thông. Xem thông tin tham khảo trên từng card hoặc hỏi trực tiếp khi tư vấn.',
			),
			array(
				'q' => 'Có xe cabin VIP không?',
				'a' => 'Nhiều lựa chọn trên tuyến {Tên tuyến} có Cabin VIP. Kiểm tra nhãn loại xe trên card hoặc nhờ tư vấn gợi ý.',
			),
			array(
				'q' => 'Có xe limousine không?',
				'a' => 'Có trên một số dịch vụ của tuyến {Tên tuyến}. So sánh trong danh sách card bên dưới hoặc liên hệ tư vấn.',
			),
			array(
				'q' => 'Bấm vào card thì đặt xe như thế nào?',
				'a' => 'Bấm «Tư vấn ngay» trên card tuyến {Tên tuyến}, nhập số điện thoại — nhân viên gọi lại trong vài phút để xác nhận chuyến. Hoặc bấm Zalo nếu bạn muốn chat trực tiếp nhà xe.',
			),
		);

		return self::apply_route_tokens_to_faq_items( $items, $name );
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
	 * @param WP_Term              $term       Term.
	 * @param array<string,mixed>  $stats      Stats.
	 * @param int                  $count Số card hiển thị.
	 */
	private static function render_hero( $term, array $stats, $count ) {
		$name          = (string) $term->name;
		$count         = max( 0, (int) $count );
		$h1            = class_exists( 'MTTF_Landing_SEO', false ) ? MTTF_Landing_SEO::get_h1() : sprintf( 'Xe %s', $name );
		$desc          = class_exists( 'MTTF_Landing_SEO', false ) ? MTTF_Landing_SEO::get_hero_description() : '';
		$price_text    = ! empty( $stats['min_price'] )
			? number_format_i18n( (int) $stats['min_price'] ) . ' VND'
			: '';
		$price_short   = ! empty( $stats['min_price'] )
			? 'Từ ' . number_format_i18n( (int) $stats['min_price'] ) . ' VND'
			: 'Liên hệ tư vấn';
		$car_short     = (string) $stats['car_types_short'];
		$car_marketing = (string) $stats['car_types_marketing'];

		if ( '' === $desc ) {
			$desc = sprintf(
				'Tổng hợp các lựa chọn xe %1$s theo giá, loại xe và tiện ích. So sánh nhanh các lựa chọn đang khai thác tuyến này và để lại số điện thoại để được tư vấn chuyến phù hợp.',
				$name
			);
		}
		?>
		<header class="mttf-landing-hero mttf-landing-hero--tuyen mttf-landing-hero--premium">
			<div class="mttf-landing-hero__shell">
				<div class="mttf-landing-hero__inner">
					<div class="mttf-landing-hero__main">
						<p class="mttf-landing-hero__eyebrow"><?php esc_html_e( 'Tuyến xe chọn lọc', 'minh-thang-transport-flow' ); ?></p>
						<h1 class="mttf-landing-hero__title"><?php echo esc_html( $h1 ); ?></h1>
						<p class="mttf-landing-hero__desc"><?php echo esc_html( wp_strip_all_tags( $desc ) ); ?></p>
						<ul class="mttf-landing-hero__highlights" role="list">
							<?php if ( '' !== $price_text ) : ?>
								<li><?php echo esc_html( sprintf( 'Giá từ %s', $price_text ) ); ?></li>
							<?php endif; ?>
							<li><?php echo esc_html( sprintf( 'Nhiều lựa chọn %s', $car_short ) ); ?></li>
							<li><?php esc_html_e( 'Hỗ trợ tư vấn điểm đón/trả', 'minh-thang-transport-flow' ); ?></li>
							<?php if ( $count > 0 ) : ?>
								<li><?php echo esc_html( self::format_route_choice_count_label( $count, 'bullet' ) ); ?></li>
							<?php endif; ?>
						</ul>
						<div class="mttf-landing-hero__actions">
							<button type="button" class="mttf-landing-btn mttf-landing-btn--primary mttf-landing-trigger-lead" <?php echo $count > 0 ? '' : 'disabled'; ?>>
								<?php esc_html_e( 'Tư vấn tuyến này', 'minh-thang-transport-flow' ); ?>
							</button>
							<a class="mttf-landing-btn mttf-landing-btn--ghost" href="#mttf-landing-services">
								<?php esc_html_e( 'Xem danh sách xe', 'minh-thang-transport-flow' ); ?>
							</a>
						</div>
					</div>
					<?php if ( ! wp_is_mobile() ) : ?>
					<aside class="mttf-landing-hero__aside" aria-label="<?php esc_attr_e( 'Tóm tắt tuyến', 'minh-thang-transport-flow' ); ?>">
						<div class="mttf-landing-hero__summary">
							<div class="mttf-landing-hero__summary-icon" aria-hidden="true">
								<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
									<rect width="48" height="48" rx="14" fill="url(#mttfHeroGrad)"/>
									<path d="M14 30h20v2H14v-2zm2-10h16l-2 8H18l-2-8zm1-4h14l1 3H16l1-3z" fill="#fff" opacity=".95"/>
									<defs>
										<linearGradient id="mttfHeroGrad" x1="8" y1="6" x2="42" y2="44" gradientUnits="userSpaceOnUse">
											<stop stop-color="#2b9fe8"/>
											<stop offset="1" stop-color="#003f6c"/>
										</linearGradient>
									</defs>
								</svg>
							</div>
							<p class="mttf-landing-hero__summary-lead">
								<strong><?php echo esc_html( (string) $count ); ?></strong>
								<?php echo esc_html( ' hãng xe đang phục vụ tuyến này' ); ?>
							</p>
							<?php if ( '' !== $price_text ) : ?>
								<p class="mttf-landing-hero__summary-price"><?php echo esc_html( 'Giá từ ' . $price_text ); ?></p>
							<?php endif; ?>
							<p class="mttf-landing-hero__summary-types"><?php echo esc_html( $car_marketing ); ?></p>
							<p class="mttf-landing-hero__summary-note"><?php esc_html_e( 'Tư vấn chọn xe phù hợp', 'minh-thang-transport-flow' ); ?></p>
						</div>
					</aside>
					<?php endif; ?>
				</div>
				<?php
				if ( ! wp_is_mobile() ) {
					self::render_quick_bar( $stats, $count, $price_short, $car_short );
				}
				?>
			</div>
		</header>
		<?php
	}

	/**
	 * @param array<string,mixed> $stats       Stats.
	 * @param int                 $count       Service count.
	 * @param string              $price_short Price label for quick bar.
	 * @param string              $car_short   Short car types label.
	 */
	private static function render_quick_bar( array $stats, $count, $price_short, $car_short ) {
		unset( $stats );
		$count_label = self::format_route_choice_count_label( $count, 'quickbar' );
		?>
		<div class="mttf-landing-quickbar" aria-label="<?php esc_attr_e( 'Thông tin nhanh', 'minh-thang-transport-flow' ); ?>">
			<div class="mttf-landing-quickbar__grid">
				<div class="mttf-landing-quickbar__item">
					<span class="mttf-landing-quickbar__label"><?php esc_html_e( 'Giá tham khảo', 'minh-thang-transport-flow' ); ?></span>
					<span class="mttf-landing-quickbar__value"><?php echo esc_html( $price_short ); ?></span>
				</div>
				<div class="mttf-landing-quickbar__item">
					<span class="mttf-landing-quickbar__label"><?php esc_html_e( 'Loại xe', 'minh-thang-transport-flow' ); ?></span>
					<span class="mttf-landing-quickbar__value"><?php echo esc_html( $car_short ); ?></span>
				</div>
				<div class="mttf-landing-quickbar__item">
					<span class="mttf-landing-quickbar__label"><?php esc_html_e( 'Hãng xe', 'minh-thang-transport-flow' ); ?></span>
					<span class="mttf-landing-quickbar__value"><?php echo esc_html( $count_label ); ?></span>
				</div>
				<div class="mttf-landing-quickbar__item">
					<span class="mttf-landing-quickbar__label"><?php esc_html_e( 'Hỗ trợ', 'minh-thang-transport-flow' ); ?></span>
					<span class="mttf-landing-quickbar__value"><?php esc_html_e( 'Tư vấn điểm đón/trả', 'minh-thang-transport-flow' ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param WP_Term   $term   Term.
	 * @param WP_Post[] $routes Routes.
	 */
	private static function render_services( $term, array $routes ) {
		$section_title = class_exists( 'MTTF_Landing_SEO', false )
			? MTTF_Landing_SEO::get_services_section_title()
			: sprintf( 'Các lựa chọn xe tuyến %s', (string) $term->name );
		?>
		<section class="mttf-landing-section mttf-landing-services" id="mttf-landing-services" aria-labelledby="mttf-landing-services-title">
			<div class="mttf-landing-section__head">
				<h2 id="mttf-landing-services-title" class="mttf-landing-section__title">
					<?php echo esc_html( $section_title ); ?>
				</h2>
				<p class="mttf-landing-section__desc">
					<?php esc_html_e( 'So sánh giá, loại xe, tiện ích và bấm Tư vấn ngay trên card để được gọi lại trong vài phút.', 'minh-thang-transport-flow' ); ?>
				</p>
			</div>
			<?php if ( empty( $routes ) ) : ?>
				<div class="mttf-landing-empty">
					<p><?php esc_html_e( 'Chưa có dịch vụ nào được gán cho tuyến này.', 'minh-thang-transport-flow' ); ?></p>
				</div>
			<?php else : ?>
				<div class="mttf-hub mttf-landing-hub">
					<div class="mttf-hub__track mttf-hub__track--landing mttf-landing-grid">
						<?php foreach ( $routes as $card_index => $route ) : ?>
							<?php
							echo MTTF_Shortcode::render_route_card(
								$route,
								array(
									'eager_image' => 0 === $card_index,
								)
							); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * @param WP_Term $term Term.
	 */
	private static function render_benefits( $term ) {
		unset( $term );
		$items = array(
			array(
				'icon'  => 'choices',
				'title' => 'Nhiều lựa chọn trên cùng tuyến',
				'desc'  => 'Tổng hợp nhiều phương án xe trên cùng một tuyến để khách dễ tham khảo.',
			),
			array(
				'icon'  => 'compare',
				'title' => 'Dễ so sánh trước khi đặt',
				'desc'  => 'So sánh nhanh giá, loại xe và tiện ích giữa các lựa chọn đang có.',
			),
			array(
				'icon'  => 'support',
				'title' => 'Tư vấn điểm đón, điểm trả',
				'desc'  => 'Có nhân viên hỗ trợ chọn phương án phù hợp với nhu cầu thực tế.',
			),
			array(
				'icon'  => 'time',
				'title' => 'Tiết kiệm thời gian tìm kiếm',
				'desc'  => 'Không cần tự tìm từng nhà xe riêng lẻ, mọi lựa chọn đã được tổng hợp sẵn.',
			),
		);
		?>
		<section class="mttf-landing-section mttf-landing-benefits" aria-labelledby="mttf-landing-benefits-title">
			<div class="mttf-landing-section__head">
				<h2 id="mttf-landing-benefits-title" class="mttf-landing-section__title">
					<?php esc_html_e( 'Vì sao nên đặt tuyến này tại Đặt Xe Việt Nam?', 'minh-thang-transport-flow' ); ?>
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
	private static function render_vehicle_compare( $term ) {
		$name  = (string) $term->name;
		$cards = array(
			array(
				'slug'  => 'cabin',
				'badge' => 'Riêng tư',
				'title' => 'Cabin VIP',
				'desc'  => 'Không gian riêng tư, phù hợp với khách muốn nghỉ ngơi thoải mái hơn trên đường dài.',
				'fits'  => array( 'Đi đường dài', 'Muốn riêng tư', 'Ưu tiên nghỉ ngơi' ),
			),
			array(
				'slug'  => 'limousine',
				'badge' => 'Linh hoạt',
				'title' => 'Limousine',
				'desc'  => 'Phù hợp với khách thích xe nhỏ gọn, ghế ngồi êm và di chuyển linh hoạt.',
				'fits'  => array( 'Đi công tác', 'Đi nhóm nhỏ', 'Ưu tiên linh hoạt' ),
			),
			array(
				'slug'  => 'bed',
				'badge' => 'Tiết kiệm',
				'title' => 'Giường nằm',
				'desc'  => 'Lựa chọn phù hợp nếu muốn tối ưu chi phí và có nhiều khung giờ hơn.',
				'fits'  => array( 'Tối ưu chi phí', 'Nhiều khung giờ', 'Nhu cầu phổ thông' ),
			),
		);
		?>
		<section class="mttf-landing-section mttf-landing-compare" aria-labelledby="mttf-landing-compare-title">
			<div class="mttf-landing-section__head">
				<h2 id="mttf-landing-compare-title" class="mttf-landing-section__title">
					<?php echo esc_html( sprintf( 'Nên chọn loại xe nào cho tuyến %s?', $name ) ); ?>
				</h2>
			</div>
			<div class="mttf-landing-compare__grid">
				<?php foreach ( $cards as $card ) : ?>
					<article class="mttf-landing-compare__card mttf-landing-compare__card--<?php echo esc_attr( $card['slug'] ); ?>">
						<span class="mttf-landing-compare__badge"><?php echo esc_html( $card['badge'] ); ?></span>
						<div class="mttf-landing-compare__head">
							<div class="mttf-landing-compare__icon" aria-hidden="true">
								<?php echo self::get_compare_icon_svg( $card['slug'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<h3 class="mttf-landing-compare__title"><?php echo esc_html( $card['title'] ); ?></h3>
						</div>
						<p class="mttf-landing-compare__desc"><?php echo esc_html( $card['desc'] ); ?></p>
						<p class="mttf-landing-compare__fits-label"><?php esc_html_e( 'Phù hợp với', 'minh-thang-transport-flow' ); ?></p>
						<ul class="mttf-landing-compare__fits" role="list">
							<?php foreach ( $card['fits'] as $fit ) : ?>
								<li><?php echo esc_html( $fit ); ?></li>
							<?php endforeach; ?>
						</ul>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * @param string $icon Icon key.
	 * @return string SVG markup.
	 */
	private static function get_benefit_icon_svg( $icon ) {
		$icons = array(
			'choices'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 7h6v6H4V7zm10 0h6v6h-6V7zM4 17h6v6H4v-6zm10 0h6v6h-6v-6z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
			'compare'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 4v16M17 4v16M4 9h6M14 15h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
			'support'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 21s6-4.35 6-10a6 6 0 10-12 0c0 5.65 6 10 6 10z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="11" r="2.2" stroke="currentColor" stroke-width="1.8"/></svg>',
			'time'     => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v4.5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		);

		return $icons[ $icon ] ?? $icons['choices'];
	}

	/**
	 * @param string $slug Vehicle slug.
	 * @return string SVG markup.
	 */
	private static function get_compare_icon_svg( $slug ) {
		$icons = array(
			'cabin'      => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 18V8l7-4 7 4v10" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 18v-5h6v5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
			'limousine'  => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 14h16l-1.5-5H6.5L4 14z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="7.5" cy="16.5" r="1.5" fill="currentColor"/><circle cx="16.5" cy="16.5" r="1.5" fill="currentColor"/><path d="M3 14h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
			'bed'        => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 16V9h11v7M4 16h16v2H4v-2z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7 9V6h5v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
		);

		return $icons[ $slug ] ?? $icons['cabin'];
	}

	private static function render_steps() {
		$steps = array(
			array(
				'icon'  => 'choose',
				'title' => 'Chọn xe phù hợp',
				'desc'  => 'Xem danh sách các lựa chọn trên tuyến và chọn loại xe phù hợp với nhu cầu.',
			),
			array(
				'icon'  => 'contact',
				'title' => 'Để lại thông tin liên hệ',
				'desc'  => 'Điền số điện thoại hoặc nhắn Zalo để đội ngũ tư vấn hỗ trợ nhanh hơn.',
			),
			array(
				'icon'  => 'confirm',
				'title' => 'Nhận tư vấn và xác nhận chuyến',
				'desc'  => 'Nhân viên hỗ trợ chọn khung giờ, điểm đón/trả và xác nhận phương án phù hợp.',
			),
		);
		?>
		<section class="mttf-landing-section mttf-landing-steps" aria-labelledby="mttf-landing-steps-title">
			<div class="mttf-landing-section__head">
				<h2 id="mttf-landing-steps-title" class="mttf-landing-section__title">
					<?php esc_html_e( 'Quy trình đặt xe 3 bước', 'minh-thang-transport-flow' ); ?>
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

	private static function render_tips() {
		$tips = array(
			array(
				'slug'  => 'family',
				'badge' => 'Gia đình',
				'title' => 'Đi cùng gia đình',
				'desc'  => 'Ưu tiên xe rộng, chỗ ngồi thoải mái và điểm đón thuận tiện để di chuyển nhẹ nhàng hơn.',
				'hint'  => 'Cabin VIP hoặc limousine rộng rãi.',
			),
			array(
				'slug'  => 'business',
				'badge' => 'Công tác',
				'title' => 'Đi công tác',
				'desc'  => 'Nên chọn khung giờ sát lịch trình và loại xe yên tĩnh để nghỉ ngơi trước khi làm việc.',
				'hint'  => 'Limousine hoặc cabin VIP.',
			),
			array(
				'slug'  => 'travel',
				'badge' => 'Du lịch',
				'title' => 'Đi du lịch',
				'desc'  => 'Nên ưu tiên xe có tiện ích ổn, giờ chạy phù hợp với lịch nhận phòng và hành trình tham quan.',
				'hint'  => 'So sánh thêm giờ chạy và tiện ích.',
			),
			array(
				'slug'  => 'solo',
				'badge' => 'Một mình',
				'title' => 'Đi một mình',
				'desc'  => 'Có thể chọn phương án linh hoạt theo ngân sách, sau đó nhờ tư vấn để chọn chỗ phù hợp.',
				'hint'  => 'Limousine hoặc phương án tiết kiệm.',
			),
		);
		?>
		<section class="mttf-landing-section mttf-landing-tips" aria-labelledby="mttf-landing-tips-title">
			<div class="mttf-landing-section__head">
				<h2 id="mttf-landing-tips-title" class="mttf-landing-section__title">
					<?php esc_html_e( 'Kinh nghiệm chọn xe theo nhu cầu', 'minh-thang-transport-flow' ); ?>
				</h2>
			</div>
			<div class="mttf-landing-tips__grid">
				<?php foreach ( $tips as $tip ) : ?>
					<article class="mttf-landing-tips__card mttf-landing-tips__card--<?php echo esc_attr( $tip['slug'] ); ?>">
						<span class="mttf-landing-tips__badge"><?php echo esc_html( $tip['badge'] ); ?></span>
						<div class="mttf-landing-tips__icon" aria-hidden="true">
							<?php echo self::get_tip_icon_svg( $tip['slug'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<h3 class="mttf-landing-tips__title"><?php echo esc_html( $tip['title'] ); ?></h3>
						<p class="mttf-landing-tips__desc"><?php echo esc_html( $tip['desc'] ); ?></p>
						<p class="mttf-landing-tips__hint">
							<span class="mttf-landing-tips__hint-label"><?php esc_html_e( 'Gợi ý', 'minh-thang-transport-flow' ); ?></span>
							<?php echo esc_html( $tip['hint'] ); ?>
						</p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * @param string $icon Step icon key.
	 * @return string SVG markup.
	 */
	private static function get_step_icon_svg( $icon ) {
		$icons = array(
			'choose'   => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
			'contact'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.5 4h11l-1 14H7.5l-1-14z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 18.5a2 2 0 104 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
			'confirm'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12l2.2 2.2L16 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/></svg>',
		);

		return $icons[ $icon ] ?? $icons['choose'];
	}

	/**
	 * @param string $slug Persona slug.
	 * @return string SVG markup.
	 */
	private static function get_tip_icon_svg( $slug ) {
		$icons = array(
			'family'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="9" cy="8" r="2.5" stroke="currentColor" stroke-width="1.7"/><circle cx="16" cy="9" r="2" stroke="currentColor" stroke-width="1.7"/><path d="M5 19c.6-2.4 2.4-4 4-4s3.4 1.6 4 4M13 19c.5-1.8 1.8-3 3.5-3S19.5 17.2 20 19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
			'business' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 9h16v10H4V9z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8 9V6h8v3" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M10 13h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
			'travel'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 18l2-9 4-2 2 4 4-1-2 8H6z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="12" cy="7" r="2" stroke="currentColor" stroke-width="1.7"/></svg>',
			'solo'     => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M6 19c.8-2.8 3-4.5 6-4.5s5.2 1.7 6 4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
		);

		return $icons[ $slug ] ?? $icons['solo'];
	}

	/**
	 * @param WP_Term                              $term Term.
	 * @param array<int,array{q:string,a:string}> $faq_items FAQ rows.
	 */
	private static function render_faq( $term, array $faq_items ) {
		unset( $term );
		?>
		<section class="mttf-landing-section mttf-landing-faq" aria-labelledby="mttf-landing-faq-title">
			<div class="mttf-landing-section__head">
				<h2 id="mttf-landing-faq-title" class="mttf-landing-section__title">
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
	 * @param string  $zalo_url Zalo link.
	 */
	private static function render_final_cta( $term, $zalo_url ) {
		$name = (string) $term->name;
		?>
		<section class="mttf-landing-section mttf-landing-cta-final" aria-labelledby="mttf-landing-cta-final-title">
			<div class="mttf-landing-cta-final__inner">
				<h2 id="mttf-landing-cta-final-title" class="mttf-landing-cta-final__title">
					<?php echo esc_html( sprintf( 'Bạn cần tư vấn xe %s?', $name ) ); ?>
				</h2>
				<p class="mttf-landing-cta-final__desc">
					<?php esc_html_e( 'Để lại số điện thoại, đội ngũ tư vấn sẽ hỗ trợ chọn loại xe, khung giờ và điểm đón/trả phù hợp với nhu cầu của bạn.', 'minh-thang-transport-flow' ); ?>
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
							<?php esc_html_e( 'Xem card Zalo', 'minh-thang-transport-flow' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
