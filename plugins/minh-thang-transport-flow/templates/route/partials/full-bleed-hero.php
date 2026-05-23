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
				<p class="mttf-directory-hero__description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
			<div class="mttf-directory-hero__actions">
				<?php if ( '' !== $phone_href && '' !== $phone ) : ?>
					<a class="mttf-directory-hero__cta mttf-directory-hero__cta--call mttf-js-track" href="<?php echo esc_url( $phone_href ); ?>" data-track-event="call_click" data-track-label="hero_call">Gọi <?php echo esc_html( $phone ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $zalo_url ) : ?>
					<a class="mttf-directory-hero__cta mttf-directory-hero__cta--zalo mttf-js-track" href="<?php echo esc_url( $zalo_url ); ?>" target="_blank" rel="noopener" data-track-event="zalo_click" data-track-label="hero_zalo">Chat Zalo</a>
				<?php endif; ?>
				<a class="mttf-directory-hero__secondary-link mttf-js-track" href="<?php echo esc_url( $base_url ); ?>" data-track-event="view_route_click" data-track-label="hero_all_routes">Tất cả tuyến</a>
				<?php if ( '' !== $back_url && '' !== $back_label ) : ?>
					<a class="mttf-directory-hero__secondary-link mttf-js-track" href="<?php echo esc_url( $back_url ); ?>" data-track-event="view_route_click" data-track-label="hero_back_link"><?php echo esc_html( $back_label ); ?></a>
				<?php endif; ?>
			</div>
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
</section>
