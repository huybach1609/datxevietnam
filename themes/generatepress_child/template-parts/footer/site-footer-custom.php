<?php
/**
 * Custom site footer template.
 *
 * @package GeneratePress_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$footer_settings = function_exists( 'dxvn_get_footer_settings' ) ? dxvn_get_footer_settings() : array();
$partners_title  = $footer_settings['partners_title'] ?? 'Đối tác của chúng tôi';
$partners        = isset( $footer_settings['partners'] ) && is_array( $footer_settings['partners'] ) ? $footer_settings['partners'] : array();
$brand_desc      = $footer_settings['brand_description'] ?? '';
$brand_contacts  = function_exists( 'dxvn_parse_footer_contact_items' ) ? dxvn_parse_footer_contact_items( $footer_settings['brand_contact_items'] ?? '' ) : array();
$office_title    = $footer_settings['office_title'] ?? 'Danh sách văn phòng';
$office_map_text = $footer_settings['office_map_label'] ?? 'Xem bản đồ';
$office_items    = function_exists( 'dxvn_parse_footer_links' ) ? dxvn_parse_footer_links( $footer_settings['office_items'] ?? '' ) : array();
$services_title  = $footer_settings['services_title'] ?? 'Dịch vụ phổ biến';
$services_items  = function_exists( 'dxvn_get_footer_popular_routes' ) ? dxvn_get_footer_popular_routes() : array();
$about_title     = $footer_settings['about_title'] ?? 'Về Đặt Xe Việt Nam';
$about_items     = function_exists( 'dxvn_parse_footer_links' ) ? dxvn_parse_footer_links( $footer_settings['about_items'] ?? '' ) : array();
$support_title   = $footer_settings['support_title'] ?? 'Hỗ trợ';
$support_items   = function_exists( 'dxvn_parse_footer_links' ) ? dxvn_parse_footer_links( $footer_settings['support_items'] ?? '' ) : array();
$company_name    = $footer_settings['company_name'] ?? '';
$company_meta    = $footer_settings['company_meta'] ?? '';
$copyright       = $footer_settings['copyright_text'] ?? '';
$services_limit  = 8;
$services_total  = is_array( $services_items ) ? count( $services_items ) : 0;
$services_expandable = $services_total > $services_limit;
?>
<footer class="dxvn-footer" aria-label="Site footer">
	<?php if ( ! empty( $partners ) ) : ?>
		<div class="dxvn-footer__partners">
			<div class="dxvn-footer__container">
				<h3 class="dxvn-footer__partners-title"><?php echo esc_html( $partners_title ); ?></h3>
				<div class="dxvn-footer__partners-slider">
					<div class="dxvn-footer__partners-track">
						<?php
						$render_partners = array_merge( $partners, $partners );
						foreach ( $render_partners as $partner ) :
							$image_id  = absint( $partner['image_id'] ?? 0 );
							$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
							if ( ! $image_url ) {
								continue;
							}

							$name = $partner['name'] ?? '';
							$link = $partner['link'] ?? '';
							?>
							<div class="dxvn-footer__partner-item">
								<?php if ( '' !== $link ) : ?>
									<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer">
										<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
									</a>
								<?php else : ?>
									<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="dxvn-footer__container">
		<div class="dxvn-footer__grid">
			<section class="dxvn-footer__col dxvn-footer__col--brand">
				<div class="dxvn-footer__logo">
					<?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) : ?>
						<?php the_custom_logo(); ?>
					<?php else : ?>
						<span class="dxvn-footer__brand-name"><?php bloginfo( 'name' ); ?></span>
					<?php endif; ?>
				</div>
				<p class="dxvn-footer__desc">
					<?php echo esc_html( $brand_desc ); ?>
				</p>
				<ul class="dxvn-footer__contact">
					<?php foreach ( $brand_contacts as $contact ) : ?>
						<?php
						$contact_label = (string) ( $contact['label'] ?? '' );
						$contact_value = (string) ( $contact['value'] ?? '' );
						$contact_url   = (string) ( $contact['url'] ?? '' );

						if ( '' === $contact_url ) {
							$label_normalized = function_exists( 'remove_accents' ) ? strtolower( remove_accents( $contact_label ) ) : strtolower( $contact_label );
							$value_trimmed    = trim( $contact_value );
							$phone_digits     = preg_replace( '/[^0-9+]/', '', $value_trimmed );

							if ( false !== strpos( $label_normalized, 'hotline' ) || false !== strpos( $label_normalized, 'so dien thoai' ) || false !== strpos( $label_normalized, 'dien thoai' ) ) {
								if ( ! empty( $phone_digits ) ) {
									$contact_url = 'tel:' . $phone_digits;
								}
							} elseif ( is_email( $value_trimmed ) ) {
								$contact_url = 'mailto:' . $value_trimmed;
							}
						}
						?>
						<li>
							<strong><?php echo esc_html( $contact_label ); ?>:</strong>
							<?php if ( '' !== $contact_url ) : ?>
								<a href="<?php echo esc_url( $contact_url ); ?>"><?php echo esc_html( $contact_value ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $contact_value ); ?>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section class="dxvn-footer__col">
				<h3 class="dxvn-footer__title"><?php echo esc_html( $office_title ); ?></h3>
				<ul class="dxvn-footer__menu">
					<?php foreach ( $office_items as $office ) : ?>
						<li class="dxvn-footer__office">
							<span class="dxvn-footer__office-address"><?php echo esc_html( $office['text'] ); ?></span>
							<a class="dxvn-footer__office-map" href="<?php echo esc_url( '' !== $office['url'] ? $office['url'] : '#' ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $office_map_text ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section class="dxvn-footer__col">
				<h3 class="dxvn-footer__title"><?php echo esc_html( $services_title ); ?></h3>
				<div class="dxvn-footer__services<?php echo $services_expandable ? ' is-collapsible' : ''; ?>"<?php echo $services_expandable ? ' data-collapsed="true"' : ''; ?>>
					<ul class="dxvn-footer__menu">
						<?php foreach ( $services_items as $index => $item ) : ?>
							<li class="<?php echo $services_expandable && $index >= $services_limit ? 'dxvn-footer__service-item is-hidden' : 'dxvn-footer__service-item'; ?>">
								<a href="<?php echo esc_url( '' !== $item['url'] ? $item['url'] : '#' ); ?>"><?php echo esc_html( $item['text'] ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php if ( $services_expandable ) : ?>
						<button type="button" class="dxvn-footer__toggle" data-expand-label="Xem tất cả" data-collapse-label="Thu gọn" aria-expanded="false">
							Xem tất cả
						</button>
					<?php endif; ?>
				</div>
			</section>

			<section class="dxvn-footer__col">
				<h3 class="dxvn-footer__title"><?php echo esc_html( $about_title ); ?></h3>
				<ul class="dxvn-footer__menu">
					<?php foreach ( $about_items as $item ) : ?>
						<li><a href="<?php echo esc_url( '' !== $item['url'] ? $item['url'] : '#' ); ?>"><?php echo esc_html( $item['text'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<h3 class="dxvn-footer__title dxvn-footer__title--sub"><?php echo esc_html( $support_title ); ?></h3>
				<ul class="dxvn-footer__menu">
					<?php foreach ( $support_items as $item ) : ?>
						<li><a href="<?php echo esc_url( '' !== $item['url'] ? $item['url'] : '#' ); ?>"><?php echo esc_html( $item['text'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</section>
		</div>
	</div>

	<div class="dxvn-footer__bottom">
		<div class="dxvn-footer__container dxvn-footer__bottom-inner">
			<div class="dxvn-footer__company">
				<span class="dxvn-footer__company-name"><?php echo esc_html( $company_name ); ?></span>
				<span class="dxvn-footer__company-meta"><?php echo esc_html( $company_meta ); ?></span>
			</div>
			<span><?php echo esc_html( $copyright ); ?></span>
		</div>
	</div>
</footer>
<?php if ( $services_expandable ) : ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var footerServiceBlocks = document.querySelectorAll('.dxvn-footer__services.is-collapsible');
  footerServiceBlocks.forEach(function (block) {
    var button = block.querySelector('.dxvn-footer__toggle');
    if (!button) return;

    button.addEventListener('click', function () {
      var isCollapsed = block.getAttribute('data-collapsed') !== 'false';
      block.setAttribute('data-collapsed', isCollapsed ? 'false' : 'true');
      button.setAttribute('aria-expanded', isCollapsed ? 'true' : 'false');
      button.textContent = isCollapsed ? button.getAttribute('data-collapse-label') : button.getAttribute('data-expand-label');
    });
  });
});
</script>
<?php endif; ?>
