<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="mttf-card mttf-operator-route-card" data-route-id="<?php echo esc_attr( (string) $post_id ); ?>" data-route-title="<?php echo esc_attr( $title ); ?>" data-route-slug="<?php echo esc_attr( $route_slug ); ?>" data-route-region="<?php echo esc_attr( $region ); ?>" data-route-car-type="<?php echo esc_attr( sanitize_title( $car_type ) ); ?>" data-route-image="<?php echo esc_url( $image_url ); ?>" data-operator-id="<?php echo esc_attr( (string) $operator_id ); ?>" data-operator-name="<?php echo esc_attr( $operator_name ); ?>" data-operator-slug="<?php echo esc_attr( $operator_slug ); ?>">
	<div class="mttf-card__media">
		<div class="mttf-card__badges">
			<span class="mttf-badge mttf-badge--dat_xe_viet_nam_chon_loc">Đang khai thác</span>
		</div>
		<img class="mttf-card__image is-active" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
	</div>
	<div class="mttf-operator-route-card__body">
		<div class="mttf-operator-route-card__brand-row">
			<!-- <span class="mttf-operator-route-card__brand-label">Nhà xe đang xem</span> -->
		</div>
		<h3 class="mttf-card__title"><?php echo esc_html( $title ); ?></h3>
		<?php if ( '' !== $rating_score && '' !== $review_count ) : ?>
			<div class="mttf-card__rating-row">
				<span class="mttf-card__rating"><?php echo esc_html( $rating_score ); ?> <span class="mttf-card__star" aria-hidden="true">★</span> (<?php echo esc_html( $review_count ); ?> đánh giá)</span>
			</div>
		<?php endif; ?>
		<div class="mttf-card__meta mttf-card__meta--primary">
			<span class="mttf-card__price">Từ <?php echo esc_html( number_format_i18n( $price_from ) ); ?> VND</span>
			<!-- <?php if ( $operator_count > 0 ) : ?>
				<span class="mttf-card__contact-count"><?php echo esc_html( (string) $operator_count ); ?> nhà xe</span>
			<?php endif; ?> -->
		</div>
		<div class="mttf-card__meta">
			<?php if ( '' !== $car_type ) : ?>
				<span class="mttf-card__car-type"><?php echo esc_html( $car_type ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $trip_frequency ) : ?>
				<span><?php echo esc_html( $trip_frequency ); ?></span>
			<?php endif; ?>
			<!-- <?php if ( '' !== $region_label ) : ?>
				<span><?php echo esc_html( $region_label ); ?></span>
			<?php endif; ?> -->
			</div>
			<div class="mttf-card__actions mttf-operator-route-card__actions">
				<button type="button" class="mttf-btn mttf-btn--call mttf-open-modal mttf-js-track" data-track-event="book_click" data-track-label="operator_card_consult">Tư vấn</button>
				<?php if ( ! empty( $zalo_url ) ) : ?>
					<a class="mttf-btn mttf-btn--zalo mttf-js-track" href="<?php echo esc_url( $zalo_url ); ?>" target="_blank" rel="noopener" data-track-event="zalo_click" data-track-label="operator_card_zalo">
						<img class="mttf-btn__icon" src="<?php echo esc_url( MTTF_URL . 'assets/icons/icons8-zalo.svg' ); ?>" alt="" aria-hidden="true" loading="lazy" />
						<span>Chat Zalo</span>
					</a>
				<?php else : ?>
					<a class="mttf-btn mttf-js-track" href="<?php echo esc_url( $detail_url ); ?>" data-track-event="view_route_click" data-track-label="operator_card_view_route">Xem tuyến này</a>
				<?php endif; ?>
			</div>
		</div>
	</article>
