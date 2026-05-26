<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$operator_id   = isset( $operator_id ) ? (int) $operator_id : 0;
$operator_name = isset( $operator_name ) ? (string) $operator_name : '';
$operator_slug = isset( $operator_slug ) ? (string) $operator_slug : '';
$contact_count = isset( $contact_count ) ? (string) $contact_count : '';
$call_icon_url = isset( $call_icon_url ) ? (string) $call_icon_url : '';
$features      = isset( $features ) && is_array( $features ) ? $features : array();
$hot_badges    = isset( $hot_badges ) && is_array( $hot_badges ) ? $hot_badges : array();
$operator_url  = isset( $operator_url ) ? (string) $operator_url : '';
$primary_url   = isset( $primary_url ) ? (string) $primary_url : '';
$use_modal_cta = ! empty( $use_modal_cta );
?>
<article class="mttf-card mttf-route-discovery-card" data-detail-url="<?php echo esc_url( $detail_url ); ?>" data-route-id="<?php echo esc_attr( (string) $post_id ); ?>" data-route-title="<?php echo esc_attr( $title ); ?>" data-route-slug="<?php echo esc_attr( $route_slug ); ?>" data-route-region="<?php echo esc_attr( $region ); ?>" data-route-car-type="<?php echo esc_attr( sanitize_title( $car_type ) ); ?>" data-route-image="<?php echo esc_url( $image_url ); ?>" data-operator-id="<?php echo esc_attr( (string) $operator_id ); ?>" data-operator-name="<?php echo esc_attr( $operator_name ); ?>" data-operator-slug="<?php echo esc_attr( $operator_slug ); ?>">
	<a class="mttf-route-discovery-card__media-link" href="<?php echo esc_url( $detail_url ); ?>">
		<div class="mttf-card__media">
			<?php if ( ! empty( $hot_badges ) ) : ?>
				<div class="mttf-card__badges">
					<?php foreach ( $hot_badges as $badge ) : ?>
						<span class="mttf-badge mttf-badge--<?php echo esc_attr( (string) ( $badge['key'] ?? '' ) ); ?>"><?php echo esc_html( (string) ( $badge['label'] ?? '' ) ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<img class="mttf-card__image is-active" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
		</div>
	</a>
	<?php if ( '' !== $operator_name ) : ?>
		<p class="mttf-route-discovery-card__detail-row">
			<?php if ( '' !== $operator_url ) : ?>
				<a class="mttf-route-discovery-card__count mttf-js-track" href="<?php echo esc_url( $operator_url ); ?>" data-track-event="view_route_click" data-track-label="route_archive_card_view_operator"><?php echo esc_html( $operator_name ); ?></a>
			<?php else : ?>
				<span class="mttf-route-discovery-card__count"><?php echo esc_html( $operator_name ); ?></span>
			<?php endif; ?>
		</p>
	<?php endif; ?>
	<h4 class="mttf-card__title"><?php echo esc_html( $title ); ?></h4>
	<?php if ( '' !== $rating_score && '' !== $review_count ) : ?>
		<div class="mttf-card__rating-row">
			<span class="mttf-card__rating"><?php echo esc_html( $rating_score ); ?> <span class="mttf-card__star" aria-hidden="true">★</span> (<?php echo esc_html( $review_count ); ?> đánh giá)</span>
		</div>
	<?php endif; ?>
	<div class="mttf-card__meta mttf-card__meta--primary">
		<span class="mttf-card__price">Từ <?php echo esc_html( number_format_i18n( $price_from ) ); ?> VND</span>
		<?php if ( '' !== $contact_count ) : ?>
			<span class="mttf-card__contact-count"><?php echo esc_html( $contact_count ); ?> lượt liên hệ</span>
		<?php endif; ?>
	</div>
	<div class="mttf-card__meta">
		<?php if ( '' !== $car_type ) : ?>
			<span class="mttf-card__car-type"><?php echo esc_html( $car_type ); ?></span>
		<?php endif; ?>
		<?php if ( '' !== $trip_frequency ) : ?>
			<span><?php echo esc_html( $trip_frequency ); ?></span>
		<?php endif; ?>
	</div>
	<?php if ( ! empty( $features ) ) : ?>
		<ul class="mttf-card__features">
			<?php foreach ( $features as $feature ) : ?>
				<li><?php echo esc_html( $feature ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<div class="mttf-card__actions mttf-route-discovery-card__actions">
		<?php if ( $use_modal_cta ) : ?>
			<button type="button" class="mttf-btn mttf-btn--call mttf-open-modal mttf-js-track" data-track-event="book_click" data-track-label="route_archive_card_book">
				<?php if ( '' !== $call_icon_url ) : ?>
					<img class="mttf-btn__icon" src="<?php echo esc_url( $call_icon_url ); ?>" alt="" aria-hidden="true" />
				<?php endif; ?>
				<span><?php echo esc_html( isset( $secondary_cta ) ? (string) $secondary_cta : 'Đặt xe' ); ?></span>
			</button>
		<?php else : ?>
			<a class="mttf-btn mttf-js-track" href="<?php echo esc_url( $detail_url ); ?>" data-track-event="view_route_click" data-track-label="directory_card_view_route"><?php echo esc_html( isset( $secondary_cta ) ? (string) $secondary_cta : 'Xem tuyến' ); ?></a>
		<?php endif; ?>
		<a class="mttf-btn mttf-btn--zalo mttf-js-track" href="<?php echo esc_url( '' !== $primary_url ? $primary_url : $detail_url ); ?>"<?php echo '' !== $primary_url ? ' target="_blank" rel="noopener"' : ''; ?> data-track-event="<?php echo '' !== $primary_url ? 'zalo_click' : 'view_route_click'; ?>" data-track-label="<?php echo '' !== $primary_url ? 'directory_card_zalo' : 'route_archive_card_view_detail'; ?>"><?php echo esc_html( isset( $primary_cta ) ? (string) $primary_cta : 'Xem chi tiết' ); ?></a>
	</div>
</article>
