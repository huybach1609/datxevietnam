<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$classes = 'mttf-directory-hero mttf-directory-hero--full-bleed';
if ( '' !== $modifier_class ) {
	$classes .= ' ' . sanitize_html_class( $modifier_class );
}

$hero_images = isset( $image_urls ) && is_array( $image_urls ) ? array_values( array_unique( array_filter( $image_urls ) ) ) : array();
if ( empty( $hero_images ) && '' !== $image_url ) {
	$hero_images[] = $image_url;
}

$lead_form = isset( $lead_form ) && is_array( $lead_form ) ? $lead_form : array();
$lead_form_routes = isset( $lead_form['routes'] ) && is_array( $lead_form['routes'] ) ? array_values( array_filter( $lead_form['routes'] ) ) : array();
$default_lead_item = ! empty( $lead_form_routes ) ? $lead_form_routes[0] : array();
$lead_route_id = (string) ( $lead_form['route_id'] ?? $default_lead_item['route_id'] ?? '' );
$lead_route_title = (string) ( $lead_form['route_title'] ?? $default_lead_item['route_title'] ?? $default_lead_item['label'] ?? '' );
$lead_route_slug = (string) ( $lead_form['route_slug'] ?? $default_lead_item['route_slug'] ?? '' );
$lead_route_region = (string) ( $lead_form['route_region'] ?? $default_lead_item['region'] ?? '' );
$lead_operator_id = (string) ( $lead_form['operator_id'] ?? $default_lead_item['operator_id'] ?? '' );
$lead_operator_name = (string) ( $lead_form['operator_name'] ?? $default_lead_item['operator_name'] ?? '' );
$lead_operator_slug = (string) ( $lead_form['operator_slug'] ?? $default_lead_item['operator_slug'] ?? '' );
if ( ! empty( $lead_form_routes ) ) {
	$classes .= ' mttf-directory-hero--with-lead-form';
}
?>
<section class="<?php echo esc_attr( $classes ); ?>">
	<?php if ( ! empty( $hero_images ) ) : ?>
		<div class="mttf-directory-hero__media" data-hero-slide-interval="5">
			<?php foreach ( $hero_images as $index => $hero_image ) : ?>
				<img class="mttf-directory-hero__image<?php echo 0 === $index ? ' is-active' : ''; ?>" src="<?php echo esc_url( (string) $hero_image ); ?>" alt="<?php echo esc_attr( $title ); ?>" <?php echo 0 === $index ? 'fetchpriority="high"' : 'loading="lazy"'; ?> />
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<div class="mttf-directory-hero__overlay"></div>
	<div class="mttf-directory-hero__inner">
		<div class="mttf-directory-hero__body">
			<?php if ( '' !== $eyebrow ) : ?>
				<p class="mttf-directory-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<h1 class="mttf-directory-hero__title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( '' !== $description ) : ?>
				<div class="mttf-directory-hero__description-row" data-mttf-hero-description>
					<p class="mttf-directory-hero__description"><?php echo esc_html( $description ); ?></p>
					<button class="mttf-directory-hero__description-toggle" type="button" aria-expanded="false" data-mttf-hero-description-toggle>Đọc thêm</button>
				</div>
			<?php endif; ?>
			<div class="mttf-directory-hero__actions">
				<?php if ( '' !== $phone_href && '' !== $phone ) : ?>
					<a class="mttf-directory-hero__cta mttf-directory-hero__cta--call mttf-js-track" href="<?php echo esc_url( $phone_href ); ?>" data-track-event="call_click" data-track-label="hero_call">Gọi <?php echo esc_html( $phone ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $zalo_url ) : ?>
					<a class="mttf-directory-hero__cta mttf-directory-hero__cta--zalo mttf-js-track" href="<?php echo esc_url( $zalo_url ); ?>" target="_blank" rel="noopener" data-track-event="zalo_click" data-track-label="hero_zalo">Chat Zalo</a>
				<?php endif; ?>
				<!-- <a class="mttf-directory-hero__secondary-link mttf-js-track" href="<?php echo esc_url( $base_url ); ?>" data-track-event="view_route_click" data-track-label="hero_all_routes">Tất cả tuyến</a>
				<?php if ( '' !== $back_url && '' !== $back_label ) : ?>
					<a class="mttf-directory-hero__secondary-link mttf-js-track" href="<?php echo esc_url( $back_url ); ?>" data-track-event="view_route_click" data-track-label="hero_back_link"><?php echo esc_html( $back_label ); ?></a>
				<?php endif; ?> -->
			</div>
			<?php if ( ! empty( $summary_items ) ) : ?>
				<div class="mttf-directory-hero__summary">
					<?php foreach ( $summary_items as $item ) : ?>
						<div class="mttf-directory-hero__summary-item">
							<span class="mttf-directory-hero__summary-label"><?php echo esc_html( (string) $item['label'] ); ?></span>
							<strong class="mttf-directory-hero__summary-value"><?php echo esc_html( (string) $item['value'] ); ?></strong>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( ! empty( $lead_form_routes ) ) : ?>
			<div class="mttf-directory-hero__lead">
				<div class="mttf-directory-hero__lead-card">
					<h2 class="mttf-directory-hero__lead-title"><?php echo esc_html( $lead_form['title'] ?? 'Tư vấn nhanh theo tuyến' ); ?></h2>
					<p class="mttf-directory-hero__lead-subtitle"><?php echo esc_html( $lead_form['subtitle'] ?? 'Chọn tuyến bạn cần và để lại số điện thoại để được gọi lại.' ); ?></p>
					<form class="mttf-lead-form mttf-lead-form--hero" data-mttf-lead-form data-mttf-form-context="hero">
						<input type="hidden" name="route_id" value="<?php echo esc_attr( $lead_route_id ); ?>" />
						<input type="hidden" name="route_title" value="<?php echo esc_attr( $lead_route_title ); ?>" />
						<input type="hidden" name="route_slug" value="<?php echo esc_attr( $lead_route_slug ); ?>" />
						<input type="hidden" name="route_region" value="<?php echo esc_attr( $lead_route_region ); ?>" />
						<input type="hidden" name="operator_id" value="<?php echo esc_attr( $lead_operator_id ); ?>" />
						<input type="hidden" name="operator_name" value="<?php echo esc_attr( $lead_operator_name ); ?>" />
						<input type="hidden" name="operator_slug" value="<?php echo esc_attr( $lead_operator_slug ); ?>" />
						<input type="hidden" name="page_type" value="<?php echo esc_attr( (string) ( $lead_form['page_type'] ?? '' ) ); ?>" />
						<?php if ( count( $lead_form_routes ) > 1 ) : ?>
							<label class="mttf-directory-hero__lead-field">
								<span class="mttf-directory-hero__lead-label"><?php echo esc_html( $lead_form['select_label'] ?? 'Chọn tuyến cần tư vấn' ); ?></span>
								<select name="selected_route" class="mttf-directory-hero__lead-select" data-mttf-route-select required>
									<?php foreach ( $lead_form_routes as $route_item ) : ?>
										<option
											value="<?php echo esc_attr( (string) ( $route_item['route_id'] ?? '' ) ); ?>"
											data-route-id="<?php echo esc_attr( (string) ( $route_item['route_id'] ?? '' ) ); ?>"
											data-route-title="<?php echo esc_attr( (string) ( $route_item['route_title'] ?? ( $route_item['label'] ?? '' ) ) ); ?>"
											data-route-slug="<?php echo esc_attr( (string) ( $route_item['route_slug'] ?? '' ) ); ?>"
											data-route-region="<?php echo esc_attr( (string) ( $route_item['region'] ?? '' ) ); ?>"
											data-operator-id="<?php echo esc_attr( (string) ( $route_item['operator_id'] ?? '' ) ); ?>"
											data-operator-name="<?php echo esc_attr( (string) ( $route_item['operator_name'] ?? '' ) ); ?>"
											data-operator-slug="<?php echo esc_attr( (string) ( $route_item['operator_slug'] ?? '' ) ); ?>"
										><?php echo esc_html( (string) ( $route_item['label'] ?? $route_item['route_title'] ?? '' ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						<?php endif; ?>
						<label class="mttf-directory-hero__lead-field mttf-directory-hero__lead-field--phone">
							<span class="mttf-directory-hero__lead-label">Số điện thoại</span>
							<div class="mttf-input-wrap">
								<span class="mttf-input-icon" aria-hidden="true"><?php echo file_get_contents( MTTF_PATH . 'assets/icons/phone-incoming.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<input type="tel" name="phone" inputmode="tel" autocomplete="tel" aria-describedby="mttf-hero-phone-hint" placeholder="Nhập số điện thoại để được gọi lại" required />
							</div>
							<span class="screen-reader-text" id="mttf-hero-phone-hint">Nhập số điện thoại để đội ngũ tư vấn liên hệ lại cho bạn.</span>
						</label>
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
						<button type="submit" class="mttf-btn">Gửi yêu cầu tư vấn</button>
						<p class="mttf-lead-form__status" data-mttf-status></p>
					</form>
					<p class="mttf-directory-hero__lead-note">Đặt Xe Việt Nam cam kết bảo mật thông tin khách hàng.</p>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
