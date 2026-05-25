<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shared_contacts = $get_shared_contact_details();
$directory_hero_image = (string) MTTF_Settings::get( 'hero_background_url', 'https://images.pexels.com/photos/120049/pexels-photo-120049.jpeg' );
?>
<div class="mttf mttf-directory mttf-route-page mttf-route-page--directory" data-route-priority="" data-page-type="directory">
	<?php echo $render_directory_hero( array(
		'eyebrow'       => 'Khám phá tuyến xe',
		'title'         => 'Đặt xe toàn quốc, chọn tuyến đi nhanh chóng',
		'description'   => 'Tìm nhanh tuyến hot, xem nhà xe đang mở bán và kết nối tư vấn trong vài giây.',
		'base_url'      => $base_url,
		'image_url'     => $directory_hero_image,
		'image_urls'    => array( $directory_hero_image ),
		'phone'         => $shared_contacts['phone'],
		'phone_href'    => $shared_contacts['phone_href'],
		'zalo_url'      => $shared_contacts['zalo_url'],
		'email'         => $shared_contacts['email'],
		'summary_items' => array(
			array( 'label' => 'Tuyến đang mở', 'value' => (string) count( $routes ) ),
			array( 'label' => 'Nhà xe', 'value' => (string) count( $operators ) ),
		),
	) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<!-- <form method="get" class="mttf-search mttf-search--directory" action="" autocomplete="off">
		<div class="mttf-search__input-wrap">
			<span class="mttf-search__icon" aria-hidden="true"><?php echo file_get_contents( MTTF_PATH . 'assets/icons/search.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<input id="mttf-search-input" type="text" name="mttf_q" value="<?php echo esc_attr( $search_keyword ); ?>" placeholder="Tìm tuyến hoặc điểm đến" />
		</div>
		<div class="mttf-suggest" hidden>
			<ul class="mttf-suggest__list" role="listbox" aria-label="Gợi ý tuyến"></ul>
		</div>
	</form> -->

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
		echo $render_fallback_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo $render_directory_route_sections( $routes, '', $base_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</div>
