<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$classes = 'mttf-route-header';
if ( '' !== $modifier_class ) {
	$classes .= ' ' . sanitize_html_class( $modifier_class );
}
?>
<section class="<?php echo esc_attr( $classes ); ?>">
	<div class="mttf-route-header__inner">
		<div class="mttf-route-header__content">
			<?php if ( '' !== $eyebrow ) : ?>
				<p class="mttf-route-header__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<h1 class="mttf-route-header__title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( '' !== $description ) : ?>
				<p class="mttf-route-header__description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
		<div class="mttf-route-header__actions">
			<?php if ( '' !== $phone_href && '' !== $phone ) : ?>
				<a class="mttf-route-header__action mttf-route-header__action--call" href="<?php echo esc_url( $phone_href ); ?>">
					<span class="mttf-route-header__action-label">Gọi tư vấn</span>
					<strong class="mttf-route-header__action-value"><?php echo esc_html( $phone ); ?></strong>
				</a>
			<?php endif; ?>
			<?php if ( '' !== $zalo_url ) : ?>
				<a class="mttf-route-header__action mttf-route-header__action--zalo" href="<?php echo esc_url( $zalo_url ); ?>" target="_blank" rel="noopener">
					<span class="mttf-route-header__action-label"><?php echo esc_html( $zalo_label ); ?></span>
					<strong class="mttf-route-header__action-value">Chat nhanh</strong>
				</a>
			<?php endif; ?>
			<?php if ( '' !== $email ) : ?>
				<a class="mttf-route-header__action mttf-route-header__action--email" href="<?php echo esc_url( 'mailto:' . $email ); ?>">
					<span class="mttf-route-header__action-label">Email</span>
					<strong class="mttf-route-header__action-value"><?php echo esc_html( $email ); ?></strong>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>
