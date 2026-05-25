<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="mttf-card mttf-operator-route-card mttf-route-operator-card" data-route-id="<?php echo esc_attr( (string) $route_id ); ?>" data-route-title="<?php echo esc_attr( $route_title ); ?>" data-route-slug="<?php echo esc_attr( $route_slug ); ?>" data-route-region="<?php echo esc_attr( $route_region ); ?>" data-route-image="<?php echo esc_url( $image_url ); ?>" data-operator-id="<?php echo esc_attr( (string) $operator_id ); ?>" data-operator-name="<?php echo esc_attr( $operator_name ); ?>" data-operator-slug="<?php echo esc_attr( $operator_slug ); ?>">
	<div class="mttf-card__media">
		<?php if ( ! empty( $hot_badges ) ) : ?>
			<div class="mttf-card__badges">
				<?php foreach ( $hot_badges as $badge ) : ?>
					<span class="mttf-badge mttf-badge--<?php echo esc_attr( (string) ( $badge['key'] ?? '' ) ); ?>"><?php echo esc_html( (string) ( $badge['label'] ?? '' ) ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<img class="mttf-card__image is-active" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $route_title ); ?>" loading="lazy" />
	</div>
	<div class="mttf-operator-route-card__body">
		<div class="mttf-operator-route-card__brand-row">
			<span class="mttf-operator-route-card__brand-label"><?php echo esc_html( $operator_name ); ?></span>
		</div>
		<h3 class="mttf-card__title"><?php echo esc_html( $route_title ); ?></h3>
		<?php if ( '' !== $rating_score && '' !== $review_count ) : ?>
			<div class="mttf-card__rating-row">
				<span class="mttf-card__rating"><?php echo esc_html( $rating_score ); ?> <span class="mttf-card__star" aria-hidden="true">★</span> (<?php echo esc_html( $review_count ); ?> đánh giá)</span>
			</div>
		<?php endif; ?>
		<div class="mttf-card__meta mttf-card__meta--primary">
			<span class="mttf-card__price">Từ <?php echo esc_html( number_format_i18n( $price_from ) ); ?> VND</span>
			<!-- <?php if ( $route_count > 0 ) : ?>
				<span class="mttf-card__contact-count"><?php echo esc_html( (string) $route_count ); ?> tuyến</span>
			<?php endif; ?> -->
		</div>
		<div class="mttf-card__meta">
			<?php if ( '' !== $car_type ) : ?>
				<span class="mttf-card__car-type"><?php echo esc_html( $car_type ); ?></span>
			<?php endif; ?>
			<!-- <?php if ( '' !== $region_label ) : ?>
				<span><?php echo esc_html( $region_label ); ?></span>
			<?php endif; ?> -->
			<?php if ( '' !== $trip_frequency ) : ?>
				<span><?php echo esc_html( $trip_frequency ); ?></span>
			<?php endif; ?>
		</div>
		<div class="mttf-card__actions mttf-operator-route-card__actions">
			<button type="button" class="mttf-btn mttf-btn--call mttf-open-modal mttf-js-track" data-track-event="book_click" data-track-label="route_card_call_now">Đặt ngay</button>
			<a class="mttf-btn mttf-btn--zalo mttf-js-track" href="<?php echo esc_url( $zalo_url ); ?>" target="_blank" rel="noopener" data-track-event="zalo_click" data-track-label="route_card_zalo">Zalo</a>
		</div>
		<!-- <a class="mttf-route-operator-card__operator" href="<?php echo esc_url( $operator_url ); ?>" aria-label="<?php echo esc_attr( 'Xem các tuyến của ' . $operator_name ); ?>">
			<span class="mttf-route-operator-card__operator-logo-wrap" aria-hidden="true">
				<?php if ( ! empty( $operator_logo ) ) : ?>
					<img class="mttf-route-operator-card__operator-logo" src="<?php echo esc_url( $operator_logo ); ?>" alt="" loading="lazy" />
				<?php else : ?>
					<span class="mttf-route-operator-card__operator-fallback"><?php echo esc_html( $initials ); ?></span>
				<?php endif; ?>
			</span>
			<span class="mttf-route-operator-card__operator-body">
				<span class="mttf-route-operator-card__operator-name"><?php echo esc_html( $operator_name ); ?></span>
			</span>
		</a> -->
	</div>
</article>
