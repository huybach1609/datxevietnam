<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="mttf-card mttf-route-discovery-card" data-detail-url="<?php echo esc_url( $detail_url ); ?>" data-route-id="<?php echo esc_attr( (string) $post_id ); ?>" data-route-title="<?php echo esc_attr( $title ); ?>" data-route-slug="<?php echo esc_attr( $route_slug ); ?>" data-route-region="<?php echo esc_attr( $region ); ?>" data-route-car-type="<?php echo esc_attr( sanitize_title( $car_type ) ); ?>" data-route-image="<?php echo esc_url( $image_url ); ?>">
	<a class="mttf-route-discovery-card__media-link" href="<?php echo esc_url( $detail_url ); ?>">
		<div class="mttf-card__media">
			<img class="mttf-card__image is-active" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
		</div>
	</a>
	<div class="mttf-route-discovery-card__body">
		<div class="mttf-route-discovery-card__top">
			<span class="mttf-route-discovery-card__region"><?php echo esc_html( $region_label ); ?></span>
			<?php if ( $operator_count > 0 ) : ?>
				<span class="mttf-route-discovery-card__count"><?php echo esc_html( (string) $operator_count ); ?> nhà xe</span>
			<?php endif; ?>
		</div>
		<h4 class="mttf-card__title"><?php echo esc_html( $title ); ?></h4>
		<div class="mttf-card__meta mttf-card__meta--primary mttf-route-discovery-card__meta mttf-route-discovery-card__meta--primary">
			<span class="mttf-card__price">Từ <?php echo esc_html( number_format_i18n( $price_from ) ); ?> VND</span>
			<?php if ( $operator_count > 0 ) : ?>
				<span class="mttf-card__contact-count"><?php echo esc_html( (string) $operator_count ); ?> nhà xe</span>
			<?php endif; ?>
		</div>
		<div class="mttf-card__meta mttf-route-discovery-card__meta">
			<?php if ( '' !== $car_type ) : ?>
				<span class="mttf-card__car-type"><?php echo esc_html( $car_type ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $trip_frequency ) : ?>
				<span><?php echo esc_html( $trip_frequency ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( '' !== $rating_score && '' !== $review_count ) : ?>
			<div class="mttf-card__rating-row">
				<span class="mttf-card__rating"><?php echo esc_html( $rating_score ); ?> <span class="mttf-card__star" aria-hidden="true">★</span> (<?php echo esc_html( $review_count ); ?> đánh giá)</span>
			</div>
		<?php endif; ?>
		<div class="mttf-card__actions mttf-route-discovery-card__actions">
			<a class="mttf-route-discovery-card__link mttf-js-track" href="<?php echo esc_url( $detail_url ); ?>" data-track-event="view_route_click" data-track-label="directory_card_view_operator_list">Xem nhà xe tuyến này</a>
			<a class="mttf-btn mttf-btn--call mttf-route-discovery-card__button mttf-js-track" href="<?php echo esc_url( $detail_url ); ?>" data-track-event="view_route_click" data-track-label="directory_card_view_route">Xem tuyến này</a>
		</div>
	</div>
</article>
