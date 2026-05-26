<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shared_contacts = $get_shared_contact_details();
$route_slug      = (string) get_post_meta( $route_post->ID, '_mttf_route_slug', true );
$route_title     = (string) get_the_title( $route_post );
$operator_count  = count( $operators );
$route_images    = array();
$min_price       = 0;
$car_type_counts = array();
$trip_frequency_counts = array();
$lead_form_routes = array();
$lead_form_seen_operators = array();

foreach ( $routes as $route_article ) {
	$route_article_id = (int) $route_article->ID;
	$article_images = $get_route_hero_images( (int) $route_article->ID, 'large' );
	if ( empty( $route_images ) && ! empty( $article_images ) ) {
		$route_images = $article_images;
	}

	$article_price = (int) get_post_meta( $route_article_id, '_mttf_price_from', true );
	if ( $article_price > 0 && ( 0 === $min_price || $article_price < $min_price ) ) {
		$min_price = $article_price;
	}

	$article_car_type = trim( (string) get_post_meta( $route_article_id, '_mttf_car_type', true ) );
	if ( '' !== $article_car_type ) {
		$car_type_counts[ $article_car_type ] = ( $car_type_counts[ $article_car_type ] ?? 0 ) + 1;
	}

	$article_trip_frequency = trim( (string) get_post_meta( $route_article_id, '_mttf_trip_frequency', true ) );
	if ( '' !== $article_trip_frequency ) {
		$trip_frequency_counts[ $article_trip_frequency ] = ( $trip_frequency_counts[ $article_trip_frequency ] ?? 0 ) + 1;
	}

	$article_operator_id = (int) get_post_meta( $route_article_id, '_mttf_selected_operator_id', true );
	if ( $article_operator_id <= 0 && class_exists( 'MTTF_Route_Operators' ) ) {
		$operator_rows = MTTF_Route_Operators::get_route_operator_rows( $route_article_id, true );
		if ( 1 === count( $operator_rows ) && ! empty( $operator_rows[0]['operator_id'] ) ) {
			$article_operator_id = (int) $operator_rows[0]['operator_id'];
		}
	}

	if ( $article_operator_id > 0 && ! isset( $lead_form_seen_operators[ $article_operator_id ] ) ) {
		$lead_form_seen_operators[ $article_operator_id ] = true;
		$lead_form_routes[] = array(
			'route_id'      => $route_article_id,
			'route_title'   => $route_title,
			'route_slug'    => (string) get_post_meta( $route_article_id, '_mttf_route_slug', true ),
			'region'        => (string) get_post_meta( $route_article_id, '_mttf_hub_region', true ),
			'operator_id'   => $article_operator_id,
			'operator_name' => (string) get_the_title( $article_operator_id ),
			'operator_slug' => (string) get_post_field( 'post_name', $article_operator_id ),
			'label'         => (string) get_the_title( $article_operator_id ),
		);
	}
}

$summary_items = array();

if ( $min_price > 0 ) {
	$summary_items[] = array(
		'label' => 'Giá từ',
		'value' => number_format_i18n( $min_price ) . ' VND',
	);
}

if ( $operator_count > 0 ) {
	$summary_items[] = array(
		'label' => '',
		'value' => sprintf( '%d nhà xe', $operator_count ),
	);
}

arsort( $car_type_counts );
if ( ! empty( $car_type_counts ) ) {
	$summary_items[] = array(
		'label' => '',
		'value' => (string) array_key_first( $car_type_counts ),
	);
}

arsort( $trip_frequency_counts );
if ( ! empty( $trip_frequency_counts ) ) {
	$summary_items[] = array(
		'label' => 'Tần suất:',
		'value' => (string) array_key_first( $trip_frequency_counts ),
	);
}

$hero_description = sprintf(
	'Tổng hợp thông tin, giá vé và loại xe từ các hãng uy tín tuyến %s.',
	$route_title
);
?>
<div class="mttf mttf-directory mttf-route-page mttf-route-page--route-archive" data-route-priority="<?php echo esc_attr( $route_slug ); ?>" data-page-type="route-archive">
	<?php echo $render_directory_hero( array(
		'eyebrow'       => 'Tuyến xe',
		'title'         => $route_title,
		'description'   => $hero_description,
		'base_url'      => $base_url,
		'back_url'      => '',
		'back_label'    => '',
		'image_url'     => ! empty( $route_images ) ? (string) $route_images[0] : '',
		'image_urls'    => $route_images,
		'phone'         => $shared_contacts['phone'],
		'phone_href'    => $shared_contacts['phone_href'],
		'phone_label'   => 'Tổng đài: ' . $shared_contacts['phone'],
		'cta_link_label'=> 'Chọn nhà xe',
		'cta_link_url'  => '#mttf-route-archive-list',
		'zalo_url'      => '',
		'email'         => $shared_contacts['email'],
		'summary_items' => $summary_items,
		'lead_form'     => array(
			'title'         => 'Chọn nhà xe phù hợp, đội ngũ hỗ trợ ngay',
			'subtitle'      => 'Chọn nhà xe bạn quan tâm trên tuyến này và để lại số điện thoại để được gọi lại.',
			'select_label'  => 'Nhà xe',
			'page_type'     => 'route-archive',
			'route_title'   => $route_title,
			'route_slug'    => $route_slug,
			'show_route_context' => false,
			'routes'        => $lead_form_routes,
		),
	) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

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
		echo '<section class="mttf-hub mttf-route-directory-group" id="mttf-route-archive-list">';
		echo '<h2 class="mttf-hub__title">Các nhà xe đang kinh doanh tuyến này</h2>';
		echo $render_article_route_sections( $routes, '', $base_url, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</section>';
	}
	?>

	<?php echo $render_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
