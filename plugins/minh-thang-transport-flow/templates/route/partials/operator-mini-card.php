<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<a class="mttf-brand-card" href="<?php echo esc_url( $url ); ?>">
	<div class="mttf-brand-card__logo-wrap">
		<?php if ( '' !== $logo ) : ?>
			<img class="mttf-brand-card__logo" src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
		<?php else : ?>
			<span class="mttf-brand-card__logo-fallback" aria-hidden="true"><?php echo esc_html( $initials ); ?></span>
		<?php endif; ?>
	</div>
	<div class="mttf-brand-card__body">
		<h3 class="mttf-brand-card__title"><?php echo esc_html( $name ); ?></h3>
		<p class="mttf-brand-card__meta"><?php echo esc_html( $route_count . ' tuyến' ); ?></p>
	</div>
</a>
