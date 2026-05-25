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
$route_car_type = (string) get_post_meta( $route_id, '_mttf_car_type', true );
$route_trip_frequency = (string) get_post_meta( $route_id, '_mttf_trip_frequency', true );
$route_feature_keys = array_slice( (array) get_post_meta( $route_id, '_mttf_route_features', true ), 0, 4 );
$shared_contacts = $get_shared_contact_details();
$related_routes = $get_related_routes( $route_id, 3 );
$hero_lead_operators = array();
$route_feature_labels = array(
	'don_tra_tan_noi'             => 'Đón trả tận nơi',
	'don_tra_linh_hoat'           => 'Đón trả linh hoạt',
	'mien_phi_nuoc_loc_khan_lanh' => 'Miễn phí nước lọc & khăn lạnh',
	'ghe_massage_boc_da_cao_cap'  => 'Ghế massage bọc da cao cấp',
	'cabin_rieng_tu'              => 'Cabin riêng tư',
	'wifi_cong_sac_usb'           => 'Wifi tốc độ cao, cổng sạc USB & Type C',
	'chan_goi_sach_se'            => 'Chăn gối sạch sẽ',
	'chay_cao_toc_100'            => 'Chạy cao tốc 100%',
	'khong_bat_khach_doc_duong'   => 'Không bắt khách dọc đường',
	'xe_doi_moi_2025_2026'        => 'Xe đời mới 2025 - 2026',
	'dung_gio_dung_chuyen'        => 'Đúng giờ - đúng chuyến',
	'bao_hiem_hanh_khach'         => 'Bảo hiểm hành khách',
);
$route_feature_values = array();
$route_price_text = $route_price > 0 ? number_format_i18n( $route_price ) . ' VND' : 'Đang cập nhật';
$route_operator_count = count( $operator_rows );

foreach ( $route_feature_keys as $feature_key ) {
	if ( isset( $route_feature_labels[ $feature_key ] ) ) {
		$route_feature_values[] = $route_feature_labels[ $feature_key ];
	}
}

$route_title = (string) get_the_title( $route );
$hero_eyebrow = '' !== $route_car_type ? $route_car_type : 'Tuyến xe đang mở bán';
$hero_title = 0 === stripos( $route_title, 'Đặt Vé ' ) ? $route_title : 'Đặt Vé ' . $route_title;
$hero_description_parts = array();

if ( $route_price > 0 ) {
	$hero_description_parts[] = 'Giá từ ' . number_format_i18n( $route_price ) . ' VND';
}

if ( ! empty( $route_feature_values ) ) {
	$hero_description_parts[] = implode( ', ', array_slice( $route_feature_values, 0, 2 ) );
}

// if ( $route_operator_count > 0 ) {
// 	$hero_description_parts[] = $route_operator_count . ' nhà xe khai thác';
// }

$hero_description = ! empty( $hero_description_parts )
	? implode( ', ', $hero_description_parts ) . '.'
	: 'Nhiều nhà xe khai thác, hỗ trợ giữ chỗ nhanh qua điện thoại hoặc Zalo.';

$hero_summary_items = array(
	array( 'label' => 'Giá từ', 'value' => $route_price_text ),
);

foreach ( $route_feature_values as $feature_value ) {
	$hero_summary_items[] = array(
		'label' => '',
		'value' => $feature_value,
	);
}

if ( count( $route_feature_values ) < 2 && '' !== $route_car_type ) {
	$hero_summary_items[] = array(
		'label' => '',
		'value' => $route_car_type,
	);
}

if ( count( $route_feature_values ) < 3 && '' !== $route_trip_frequency ) {
	$hero_summary_items[] = array(
		'label' => '',
		'value' => $route_trip_frequency,
	);
}

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
		'eyebrow'       => $hero_eyebrow,
		'title'         => $hero_title,
		'description'   => $hero_description,
		'base_url'      => $base_url,
		'back_url'      => $base_url,
		'back_label'    => 'Tất cả tuyến',
		'image_url'     => $route_image ? $route_image : '',
		'image_urls'    => $route_images,
		'phone'         => $shared_contacts['phone'],
		'phone_href'    => $shared_contacts['phone_href'],
		'zalo_url'      => $shared_contacts['zalo_url'],
		'email'         => $shared_contacts['email'],
		'summary_items' => $hero_summary_items,
		'lead_form'     => array(
			'title'         => 'Chọn lựa phù hợp, đội ngũ hỗ trợ ngay',
			'subtitle'      => 'Chọn phương án bạn đang quan tâm, để lại số điện thoại và đội ngũ sẽ liên hệ lại trong ít phút.',
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
