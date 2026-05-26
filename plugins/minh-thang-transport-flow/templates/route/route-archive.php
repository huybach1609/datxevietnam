<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shared_contacts = $get_shared_contact_details();
$route_slug      = (string) get_post_meta( $route_post->ID, '_mttf_route_slug', true );
$route_title     = (string) get_the_title( $route_post );
$route_count     = count( $routes );
$operator_count  = count( $operators );
$route_images    = array();
$min_price       = 0;

foreach ( $routes as $route_article ) {
	$article_images = $get_route_hero_images( (int) $route_article->ID, 'large' );
	if ( empty( $route_images ) && ! empty( $article_images ) ) {
		$route_images = $article_images;
	}

	$article_price = (int) get_post_meta( (int) $route_article->ID, '_mttf_price_from', true );
	if ( $article_price > 0 && ( 0 === $min_price || $article_price < $min_price ) ) {
		$min_price = $article_price;
	}
}

$summary_items = array(
	array( 'label' => 'Bài xe', 'value' => (string) $route_count ),
	array( 'label' => 'Nhà xe', 'value' => (string) $operator_count ),
);

if ( $min_price > 0 ) {
	$summary_items[] = array(
		'label' => 'Giá từ',
		'value' => number_format_i18n( $min_price ) . ' VND',
	);
}
?>
<div class="mttf mttf-directory mttf-route-page mttf-route-page--route-archive" data-route-priority="<?php echo esc_attr( $route_slug ); ?>" data-page-type="route-archive">
	<?php echo $render_directory_hero( array(
		'eyebrow'       => 'Tuyến xe',
		'title'         => $route_title,
		'description'   => sprintf( 'Xem các bài xe đang mở bán cho tuyến %s, so sánh nhà xe và chọn phương án phù hợp.', $route_title ),
		'base_url'      => $base_url,
		'back_url'      => $base_url,
		'back_label'    => 'Tất cả tuyến',
		'image_url'     => ! empty( $route_images ) ? (string) $route_images[0] : '',
		'image_urls'    => $route_images,
		'phone'         => $shared_contacts['phone'],
		'phone_href'    => $shared_contacts['phone_href'],
		'zalo_url'      => $shared_contacts['zalo_url'],
		'email'         => $shared_contacts['email'],
		'summary_items' => $summary_items,
	) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<div class="mttf-filters-panel">
		<div class="mttf-filters-group mttf-quick-filters" role="group" aria-label="Lọc nhanh theo miền">
			<button type="button" class="mttf-chip is-active" data-region-filter="bac">Miền Bắc</button>
			<button type="button" class="mttf-chip" data-region-filter="nam">Miền Nam</button>
			<button type="button" class="mttf-chip" data-region-filter="trung">Miền Trung</button>
		</div>
		<?php if ( ! empty( $car_types ) ) : ?>
			<div class="mttf-filters-group mttf-car-filters" role="group" aria-label="Lọc theo loại xe">
				<?php foreach ( $car_types as $car_type ) : ?>
					<button type="button" class="mttf-chip" data-car-filter="<?php echo esc_attr( $normalize_car_type_key( $car_type ) ); ?>">
						<?php echo esc_html( $car_type ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $operators ) ) : ?>
		<?php echo $render_operator_brand_section( $operators, $base_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>

	<?php
	if ( empty( $routes ) ) {
		echo $render_directory_not_found_body(
			'Chưa có bài xe nào đang mở bán cho tuyến này.',
			array(
				'title'      => 'Tuyến này chưa có bài xe hiển thị',
				'phone'      => $shared_contacts['phone'],
				'phone_href' => $shared_contacts['phone_href'],
				'back_url'   => $base_url,
				'back_label' => 'Quay lại tất cả tuyến',
			)
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo $render_article_route_sections( $routes, '', $base_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>

	<?php echo $render_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
