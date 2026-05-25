<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$operator_id     = (int) $operator->ID;
$defaults        = class_exists( 'MTTF_Operator' ) ? MTTF_Operator::get_operator_defaults( $operator_id ) : array();
$operator_region = isset( $defaults['region'] ) ? (string) $defaults['region'] : '';
$operator_gallery = class_exists( 'MTTF_Operator' ) ? MTTF_Operator::get_gallery_image_urls( $operator_id, 'large' ) : array();
$operator_featured = get_the_post_thumbnail_url( $operator_id, 'large' );
$operator_images = array_values( array_unique( array_filter( array_merge( $operator_gallery, array( $operator_featured ? (string) $operator_featured : '' ) ) ) ) );
$operator_image  = ! empty( $operator_images ) ? (string) $operator_images[0] : '';
$shared_contacts = $get_shared_contact_details();
$hero_lead_routes = array();

foreach ( $routes as $hero_route ) {
	$hero_route_id = (int) $hero_route->ID;
	$hero_lead_routes[] = array(
		'route_id'    => $hero_route_id,
		'route_title' => (string) get_the_title( $hero_route ),
		'route_slug'  => (string) get_post_meta( $hero_route_id, '_mttf_route_slug', true ),
		'region'      => (string) get_post_meta( $hero_route_id, '_mttf_hub_region', true ),
	);
}
?>
<div class="mttf mttf-directory mttf-route-page mttf-route-page--operator-detail" data-route-priority="" data-page-type="operator-detail" data-operator-id="<?php echo esc_attr( (string) $operator_id ); ?>" data-operator-name="<?php echo esc_attr( get_the_title( $operator ) ); ?>" data-operator-slug="<?php echo esc_attr( (string) get_post_field( 'post_name', $operator_id ) ); ?>">
	<?php echo $render_directory_hero( array(
		'eyebrow'       => 'Nhà xe đang xem',
		'title'         => get_the_title( $operator ),
		'description'   => ! empty( $defaults['summary'] ) ? (string) $defaults['summary'] : 'Trang tổng hợp các tuyến đang khai thác của nhà xe này, kèm kênh tư vấn chung để giữ chỗ nhanh.',
		'base_url'      => $base_url,
		'back_url'      => $base_url,
		'back_label'    => 'Tất cả tuyến',
		'image_url'     => $operator_image ? $operator_image : '',
		'image_urls'    => $operator_images,
		'phone'         => $shared_contacts['phone'],
		'phone_href'    => $shared_contacts['phone_href'],
		'zalo_url'      => $shared_contacts['zalo_url'],
		'email'         => $shared_contacts['email'],
		'summary_items' => array(
			array( 'label' => 'Khu vực', 'value' => $get_region_title_compact( $operator_region ) ),
			array( 'label' => 'Số tuyến', 'value' => (string) count( $routes ) ),
		),
		'lead_form'     => array(
			'title'         => 'Giữ chỗ nhanh theo tuyến',
			'subtitle'      => 'Chọn tuyến bạn muốn đi, để lại số điện thoại và chuyên viên sẽ gọi lại trong vài phút.',
			'select_label'  => 'Tuyến cần tư vấn',
			'page_type'     => 'operator-detail',
			'operator_id'   => $operator_id,
			'operator_name' => (string) get_the_title( $operator ),
			'operator_slug' => (string) get_post_field( 'post_name', $operator_id ),
			'routes'        => $hero_lead_routes,
		),
	) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<form method="get" class="mttf-search mttf-search--directory" action="" autocomplete="off">
		<input type="hidden" name="operator" value="<?php echo esc_attr( $operator->post_name ); ?>" />
		<div class="mttf-search__input-wrap">
			<span class="mttf-search__icon" aria-hidden="true"><?php echo file_get_contents( MTTF_PATH . 'assets/icons/search.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<input id="mttf-search-input" type="text" name="mttf_q" value="<?php echo esc_attr( $search_keyword ); ?>" placeholder="Tìm tuyến của nhà xe này" />
		</div>
		<div class="mttf-suggest" hidden>
			<ul class="mttf-suggest__list" role="listbox" aria-label="Gợi ý tuyến"></ul>
		</div>
	</form>

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
