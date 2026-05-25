<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$operator_id     = (int) $operator->ID;
$defaults        = class_exists( 'MTTF_Operator' ) ? MTTF_Operator::get_operator_defaults( $operator_id ) : array();
$operator_region = isset( $defaults['region'] ) ? (string) $defaults['region'] : '';
$operator_gallery = class_exists( 'MTTF_Operator' ) ? MTTF_Operator::get_gallery_image_urls( $operator_id, 'large' ) : array();
$operator_featured = get_the_post_thumbnail_url( $operator_id, 'large' );
$operator_logo = get_the_post_thumbnail_url( $operator_id, 'medium' );
$operator_images = array_values( array_unique( array_filter( array_merge( $operator_gallery, array( $operator_featured ? (string) $operator_featured : '' ) ) ) ) );
$operator_image  = ! empty( $operator_images ) ? (string) $operator_images[0] : '';
$shared_contacts = $get_shared_contact_details();
$hero_lead_routes = array();
$operator_route_count = count( $routes );
$operator_min_price = 0;
$operator_highlight_counts = array();
$operator_feature_labels = array(
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
$operator_car_type_counts = array();

foreach ( $routes as $hero_route ) {
	$hero_route_id = (int) $hero_route->ID;
	$route_car_type = (string) get_post_meta( $hero_route_id, '_mttf_car_type', true );
	$route_price = (int) get_post_meta( $hero_route_id, '_mttf_price_from', true );
	$route_feature_keys = (array) get_post_meta( $hero_route_id, '_mttf_route_features', true );
	$hero_lead_routes[] = array(
		'route_id'    => $hero_route_id,
		'route_title' => (string) get_the_title( $hero_route ),
		'route_slug'  => (string) get_post_meta( $hero_route_id, '_mttf_route_slug', true ),
		'region'      => (string) get_post_meta( $hero_route_id, '_mttf_hub_region', true ),
	);

	if ( '' !== $route_car_type ) {
		$operator_car_type_counts[ $route_car_type ] = ($operator_car_type_counts[ $route_car_type ] ?? 0 ) + 1;
	}

	if ( $route_price > 0 && ( 0 === $operator_min_price || $route_price < $operator_min_price ) ) {
		$operator_min_price = $route_price;
	}

	foreach ( $route_feature_keys as $feature_key ) {
		if ( isset( $operator_feature_labels[ $feature_key ] ) ) {
			$operator_highlight_counts[ $feature_key ] = ($operator_highlight_counts[ $feature_key ] ?? 0 ) + 1;
		}
	}
}

arsort( $operator_highlight_counts );
arsort( $operator_car_type_counts );

$operator_highlights = isset( $defaults['highlights'] ) && is_array( $defaults['highlights'] ) ? array_values( array_filter( $defaults['highlights'] ) ) : array();

if ( empty( $operator_highlights ) ) {
	foreach ( array_keys( $operator_highlight_counts ) as $feature_key ) {
		$operator_highlights[] = $operator_feature_labels[ $feature_key ];
		if ( count( $operator_highlights ) >= 4 ) {
			break;
		}
	}
}

$operator_primary_car_type = ! empty( $operator_car_type_counts ) ? (string) array_key_first( $operator_car_type_counts ) : '';
$operator_eyebrow = '' !== $operator_primary_car_type ? $operator_primary_car_type : 'Nhà xe đang khai thác';
$operator_description = ! empty( $defaults['summary'] )
	? (string) $defaults['summary']
	: sprintf(
		'Nhà xe đang khai thác %1$d tuyến%2$s, hỗ trợ chọn tuyến phù hợp và giữ chỗ nhanh qua điện thoại hoặc Zalo.',
		$operator_route_count,
		'' !== $operator_region ? ' tại ' . $get_region_title_compact( $operator_region ) : ''
	);

$operator_summary_items = array();

if ( $operator_min_price > 0 ) {
	$operator_summary_items[] = array(
		'label' => 'Giá từ',
		'value' => number_format_i18n( $operator_min_price ) . ' VND',
	);
}

foreach ( array_slice( $operator_highlights, 0, 4 ) as $highlight ) {
	$operator_summary_items[] = array(
		'label' => '',
		'value' => (string) $highlight,
	);
}

if ( empty( $operator_summary_items ) && '' !== $operator_primary_car_type ) {
	$operator_summary_items[] = array(
		'label' => '',
		'value' => $operator_primary_car_type,
	);
}

if ( empty( $operator_summary_items ) && '' !== $operator_region ) {
	$operator_summary_items[] = array(
		'label' => '',
		'value' => 'Khai thác ' . $get_region_title_compact( $operator_region ),
	);
}

if ( empty( $operator_summary_items ) ) {
	$operator_summary_items[] = array(
		'label' => '',
		'value' => (string) $operator_route_count . ' tuyến đang mở bán',
	);
}
?>
<div class="mttf mttf-directory mttf-route-page mttf-route-page--operator-detail" data-route-priority="" data-page-type="operator-detail" data-operator-id="<?php echo esc_attr( (string) $operator_id ); ?>" data-operator-name="<?php echo esc_attr( get_the_title( $operator ) ); ?>" data-operator-slug="<?php echo esc_attr( (string) get_post_field( 'post_name', $operator_id ) ); ?>">
	<?php echo $render_directory_hero( array(
		'eyebrow'       => $operator_eyebrow,
		'title'         => get_the_title( $operator ),
		'description'   => $operator_description,
		'base_url'      => $base_url,
		'back_url'      => $base_url,
		'back_label'    => 'Tất cả tuyến',
		'logo_url'      => $operator_logo ? (string) $operator_logo : '',
		'logo_alt'      => get_the_title( $operator ),
		'image_url'     => $operator_image ? $operator_image : '',
		'image_urls'    => $operator_images,
		'phone'         => $shared_contacts['phone'],
		'phone_href'    => $shared_contacts['phone_href'],
		'zalo_url'      => $shared_contacts['zalo_url'],
		'email'         => $shared_contacts['email'],
		'summary_items' => $operator_summary_items,
		'lead_form'     => array(
			'title'         => 'Chọn lựa phù hợp, đội ngũ hỗ trợ ngay',
			'subtitle'      => 'Chọn phương án bạn đang quan tâm, để lại số điện thoại và đội ngũ sẽ liên hệ lại trong ít phút.',
			'select_label'  => 'Tuyến cần tư vấn',
			'page_type'     => 'operator-detail',
			'operator_id'   => $operator_id,
			'operator_name' => (string) get_the_title( $operator ),
			'operator_slug' => (string) get_post_field( 'post_name', $operator_id ),
			'routes'        => $hero_lead_routes,
		),
	) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<!-- <form method="get" class="mttf-search mttf-search--directory" action="" autocomplete="off">
		<input type="hidden" name="operator" value="<?php echo esc_attr( $operator->post_name ); ?>" />
		<div class="mttf-search__input-wrap">
			<span class="mttf-search__icon" aria-hidden="true"><?php echo file_get_contents( MTTF_PATH . 'assets/icons/search.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<input id="mttf-search-input" type="text" name="mttf_q" value="<?php echo esc_attr( $search_keyword ); ?>" placeholder="Tìm tuyến của nhà xe này" />
		</div>
		<div class="mttf-suggest" hidden>
			<ul class="mttf-suggest__list" role="listbox" aria-label="Gợi ý tuyến"></ul>
		</div>
	</form> -->

	<?php
	if ( empty( $routes ) ) {
		echo $render_directory_not_found_body(
			'Nhà xe này hiện chưa có tuyến nào đang hoạt động.',
			array(
				'title'      => 'Nhà xe chưa có tuyến mở bán',
				'phone'      => $shared_contacts['phone'],
				'phone_href' => $shared_contacts['phone_href'],
				'back_url'   => $base_url,
				'back_label' => 'Quay lại tất cả tuyến',
			)
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo $render_operator_route_sections( $routes, '', $base_url, $operator ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>

	<?php echo $render_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
