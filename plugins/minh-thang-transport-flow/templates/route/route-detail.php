<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$route_id      = (int) $route->ID;
$route_slug    = (string) get_post_meta( $route_id, '_mttf_route_slug', true );
$route_region  = (string) get_post_meta( $route_id, '_mttf_hub_region', true );
$route_images  = $get_route_hero_images( $route_id, 'large' );
$route_image   = ! empty( $route_images ) ? (string) $route_images[0] : '';
$route_price   = (int) get_post_meta( $route_id, '_mttf_price_from', true );
$route_trip_frequency = (string) get_post_meta( $route_id, '_mttf_trip_frequency', true );
$shared_contacts = $get_shared_contact_details();
$related_routes = $get_related_routes( $route_id, 3 );
$hero_lead_operators = array();

foreach ( $operator_rows as $operator_row ) {
	$hero_operator_id = (int) ( $operator_row['operator_id'] ?? 0 );
	if ( $hero_operator_id <= 0 ) {
		continue;
	}

	$hero_lead_operators[] = array(
		'route_id'      => $route_id,
		'route_title'   => (string) get_the_title( $route ),
		'route_slug'    => $route_slug,
		'region'        => $route_region,
		'operator_id'   => $hero_operator_id,
		'operator_name' => (string) ( $operator_row['operator_name'] ?? '' ),
		'operator_slug' => (string) get_post_field( 'post_name', $hero_operator_id ),
		'label'         => (string) ( $operator_row['operator_name'] ?? '' ),
	);
}
?>
<div class="mttf mttf-directory mttf-route-page mttf-route-page--route-detail" data-page-type="route-detail">
	<?php echo $render_directory_hero( array(
		'eyebrow'       => 'Tuyến đang xem',
		'title'         => get_the_title( $route ),
		'description'   => 'Xem tất cả nhà xe đang khai thác tuyến này, so sánh nhanh và kết nối tư vấn giữ chỗ trong vài phút.',
		'base_url'      => $base_url,
		'back_url'      => $base_url,
		'back_label'    => 'Tất cả tuyến',
		'image_url'     => $route_image ? $route_image : '',
		'image_urls'    => $route_images,
		'phone'         => $shared_contacts['phone'],
		'phone_href'    => $shared_contacts['phone_href'],
		'zalo_url'      => $shared_contacts['zalo_url'],
		'email'         => $shared_contacts['email'],
		'summary_items' => array(
			array( 'label' => 'Giá từ', 'value' => $route_price > 0 ? number_format_i18n( $route_price ) . ' VND' : 'Đang cập nhật' ),
			array( 'label' => 'Nhà xe', 'value' => (string) count( $operator_rows ) ),
			array( 'label' => 'Khu vực', 'value' => $get_region_title_compact( $route_region ) ),
			array( 'label' => 'Tần suất', 'value' => '' !== $route_trip_frequency ? $route_trip_frequency : 'Liên hệ tư vấn' ),
		),
		'lead_form'     => array(
			'title'         => 'Tư vấn nhanh theo nhà xe',
			'subtitle'      => 'Tuyến này có nhiều nhà xe khai thác. Chọn đúng nhà xe bạn muốn đi để đội ngũ gọi lại chính xác hơn.',
			'select_label'  => 'Nhà xe cần tư vấn',
			'page_type'     => 'route-detail',
			'route_id'      => $route_id,
			'route_title'   => (string) get_the_title( $route ),
			'route_slug'    => $route_slug,
			'route_region'  => $route_region,
			'routes'        => $hero_lead_operators,
		),
	) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<?php if ( empty( $operator_rows ) ) : ?>
		<?php echo $render_directory_not_found_body(
			'Chưa có nhà xe nào được gán cho tuyến này.',
			array(
				'title'      => 'Tuyến này chưa có nhà xe hiển thị',
				'phone'      => $shared_contacts['phone'],
				'phone_href' => $shared_contacts['phone_href'],
				'back_url'   => $base_url,
				'back_label' => 'Xem tất cả tuyến',
			)
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php else : ?>
		<?php echo $render_route_operator_grid( $operator_rows, $route, $base_url, $shared_contacts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>

	<?php echo $render_related_routes( $related_routes, $base_url, 'Tuyến liên quan cùng khu vực' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<?php echo $render_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
