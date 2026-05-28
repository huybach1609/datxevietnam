<?php
/**
 * GeneratePress child theme functions and definitions.
 *
 * Add your custom PHP in this file.
 * Only edit this file if you have direct access to it on your server (to fix errors if they happen).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'dxvn_setup_custom_header' );
function dxvn_setup_custom_header() {
	// Replace default GeneratePress header output with child theme custom header.
	remove_action( 'generate_header', 'generate_construct_header' );
	add_action( 'generate_header', 'dxvn_render_custom_header' );

	// Replace default GeneratePress footer output with child theme custom footer.
	remove_action( 'generate_footer', 'generate_construct_footer_widgets', 5 );
	remove_action( 'generate_footer', 'generate_construct_footer', 10 );
	add_action( 'generate_footer', 'dxvn_render_custom_footer', 10 );
}

add_action( 'wp_enqueue_scripts', 'dxvn_enqueue_header_assets' );
function dxvn_enqueue_header_assets() {
	$theme_uri = get_stylesheet_directory_uri();
	$theme_dir = get_stylesheet_directory();

	$css_path = $theme_dir . '/assets/css/header.css';
	$js_path  = $theme_dir . '/assets/js/header.js';
	$footer_css_path = $theme_dir . '/assets/css/footer.css';

	wp_enqueue_style(
		'dxvn-header',
		$theme_uri . '/assets/css/header.css',
		array(),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : null
	);

	wp_enqueue_script(
		'dxvn-header',
		$theme_uri . '/assets/js/header.js',
		array(),
		file_exists( $js_path ) ? (string) filemtime( $js_path ) : null,
		true
	);

	wp_enqueue_style(
		'dxvn-footer',
		$theme_uri . '/assets/css/footer.css',
		array(),
		file_exists( $footer_css_path ) ? (string) filemtime( $footer_css_path ) : null
	);

	$hop_tac_css_path = $theme_dir . '/assets/css/hop-tac.css';
	if ( is_page_template( 'page-hop-tac.php' ) && file_exists( $hop_tac_css_path ) ) {
		wp_enqueue_style(
			'dxvn-hop-tac',
			$theme_uri . '/assets/css/hop-tac.css',
			array(),
			(string) filemtime( $hop_tac_css_path )
		);
	}
}

function dxvn_render_custom_header() {
	get_template_part( 'template-parts/header/site', 'header-custom' );
}

function dxvn_render_custom_footer() {
	get_template_part( 'template-parts/footer/site', 'footer-custom' );
}

add_action( 'admin_menu', 'dxvn_register_header_settings_page' );
function dxvn_register_header_settings_page() {
	add_theme_page(
		'Cài đặt Header DXVN',
		'Header DXVN',
		'manage_options',
		'dxvn-header-settings',
		'dxvn_render_header_settings_page'
	);
}

add_action( 'admin_init', 'dxvn_register_header_settings' );
add_action( 'admin_enqueue_scripts', 'dxvn_enqueue_header_settings_assets' );
function dxvn_register_header_settings() {
	register_setting(
		'dxvn_header_settings_group',
		'dxvn_header_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'dxvn_sanitize_header_settings',
			'default'           => array(),
		)
	);

	add_settings_section(
		'dxvn_header_main_section',
		'Cấu hình Header',
		'__return_false',
		'dxvn-header-settings'
	);

	add_settings_field(
		'hotline_number',
		'Số hotline hiển thị',
		'dxvn_render_header_text_input',
		'dxvn-header-settings',
		'dxvn_header_main_section',
		array(
			'key'         => 'hotline_number',
			'placeholder' => '0900 000 000',
		)
	);

	add_settings_field(
		'booking_button_text',
		'Tên hiển thị nút đặt vé',
		'dxvn_render_header_text_input',
		'dxvn-header-settings',
		'dxvn_header_main_section',
		array(
			'key'         => 'booking_button_text',
			'placeholder' => 'Đặt vé ngay',
		)
	);

	add_settings_field(
		'booking_button_url',
		'Link nút đặt vé',
		'dxvn_render_header_url_input',
		'dxvn-header-settings',
		'dxvn_header_main_section',
		array(
			'key'         => 'booking_button_url',
			'placeholder' => home_url( '/#mttf-search-input' ),
		)
	);

	add_settings_section(
		'dxvn_homepage_main_section',
		'Nội dung trang chủ',
		'__return_false',
		'dxvn-header-settings-home'
	);

	$homepage_fields = array(
		'home_hub_aria_label'      => array( 'label' => 'Mô tả khu vực shortcode (ARIA)', 'type' => 'text' ),
		'home_benefits_eyebrow'    => array( 'label' => 'Lợi ích - Nhãn nhỏ', 'type' => 'text' ),
		'home_benefits_title'      => array( 'label' => 'Lợi ích - Tiêu đề', 'type' => 'text' ),
		'home_benefits_desc'       => array( 'label' => 'Lợi ích - Mô tả', 'type' => 'textarea' ),
		'home_process_eyebrow'     => array( 'label' => 'Quy trình - Nhãn nhỏ', 'type' => 'text' ),
		'home_process_title'       => array( 'label' => 'Quy trình - Tiêu đề', 'type' => 'text' ),
		'home_whyus_eyebrow'       => array( 'label' => 'Cam kết - Nhãn nhỏ', 'type' => 'text' ),
		'home_whyus_title'         => array( 'label' => 'Cam kết - Tiêu đề', 'type' => 'text' ),
		'home_whyus_desc'          => array( 'label' => 'Cam kết - Mô tả', 'type' => 'textarea' ),
		'home_testimonials_eyebrow'=> array( 'label' => 'Đánh giá - Nhãn nhỏ', 'type' => 'text' ),
		'home_testimonials_title'  => array( 'label' => 'Đánh giá - Tiêu đề', 'type' => 'text' ),
		'home_faq_eyebrow'         => array( 'label' => 'FAQ - Nhãn nhỏ', 'type' => 'text' ),
		'home_faq_title'           => array( 'label' => 'FAQ - Tiêu đề', 'type' => 'text' ),
		'home_final_cta_eyebrow'   => array( 'label' => 'CTA cuối - Nhãn nhỏ', 'type' => 'text' ),
		'home_final_cta_title'     => array( 'label' => 'CTA cuối - Tiêu đề', 'type' => 'text' ),
		'home_final_cta_desc'      => array( 'label' => 'CTA cuối - Mô tả', 'type' => 'textarea' ),
		'home_final_cta_primary_text' => array( 'label' => 'CTA cuối - Nút gọi', 'type' => 'text' ),
		'home_final_cta_primary_phone'=> array( 'label' => 'CTA cuối - Số điện thoại', 'type' => 'text' ),
		'home_final_cta_secondary_text'=> array( 'label' => 'CTA cuối - Nút phụ', 'type' => 'text' ),
		'home_final_cta_secondary_url' => array( 'label' => 'CTA cuối - URL nút phụ', 'type' => 'url' ),
	);

	for ( $i = 1; $i <= 6; $i++ ) {
		$homepage_fields[ 'home_benefit_' . $i . '_title' ] = array( 'label' => 'Lợi ích ' . $i . ' - Tiêu đề', 'type' => 'text' );
		$homepage_fields[ 'home_benefit_' . $i . '_text' ]  = array( 'label' => 'Lợi ích ' . $i . ' - Nội dung', 'type' => 'textarea' );
	}

	for ( $i = 1; $i <= 3; $i++ ) {
		$homepage_fields[ 'home_process_' . $i . '_title' ] = array( 'label' => 'Bước ' . $i . ' - Tiêu đề', 'type' => 'text' );
		$homepage_fields[ 'home_process_' . $i . '_text' ]  = array( 'label' => 'Bước ' . $i . ' - Nội dung', 'type' => 'textarea' );
		$homepage_fields[ 'home_commit_' . $i . '_title' ]  = array( 'label' => 'Cam kết ' . $i . ' - Tiêu đề', 'type' => 'text' );
		$homepage_fields[ 'home_commit_' . $i . '_text' ]   = array( 'label' => 'Cam kết ' . $i . ' - Nội dung', 'type' => 'textarea' );
		$homepage_fields[ 'home_testimonial_' . $i . '_text' ]   = array( 'label' => 'Đánh giá ' . $i . ' - Nội dung', 'type' => 'textarea' );
		$homepage_fields[ 'home_testimonial_' . $i . '_author' ] = array( 'label' => 'Đánh giá ' . $i . ' - Tác giả', 'type' => 'text' );
		$homepage_fields[ 'home_testimonial_' . $i . '_status' ] = array( 'label' => 'Đánh giá ' . $i . ' - Trạng thái', 'type' => 'text' );
	}

	for ( $i = 1; $i <= 5; $i++ ) {
		$homepage_fields[ 'home_faq_' . $i . '_q' ] = array( 'label' => 'FAQ ' . $i . ' - Câu hỏi', 'type' => 'text' );
		$homepage_fields[ 'home_faq_' . $i . '_a' ] = array( 'label' => 'FAQ ' . $i . ' - Trả lời', 'type' => 'textarea' );
	}

	foreach ( $homepage_fields as $key => $field ) {
		add_settings_field(
			$key,
			$field['label'],
			'textarea' === $field['type'] ? 'dxvn_render_header_textarea_input' : ( 'url' === $field['type'] ? 'dxvn_render_header_url_input' : 'dxvn_render_header_text_input' ),
			'dxvn-header-settings-home',
			'dxvn_homepage_main_section',
			array(
				'key' => $key,
				'placeholder' => 'home_final_cta_secondary_url' === $key ? home_url( '/#mttf-search-input' ) : '',
			)
		);
	}

	add_settings_section(
		'dxvn_aboutpage_main_section',
		'Nội dung trang giới thiệu',
		'__return_false',
		'dxvn-header-settings-about'
	);

	$about_fields = array(
		'about_hero_eyebrow'    => array( 'label' => 'Hero - Nhãn nhỏ', 'type' => 'text' ),
		'about_hero_title'      => array( 'label' => 'Hero - Tiêu đề H1', 'type' => 'text' ),
		'about_hero_desc'       => array( 'label' => 'Hero - Mô tả', 'type' => 'textarea' ),
		'about_hero_image_url'  => array( 'label' => 'Hero - Ảnh nền', 'type' => 'media' ),
		'about_vision_eyebrow'  => array( 'label' => 'Tầm nhìn/Sứ mệnh - Nhãn nhỏ', 'type' => 'text' ),
		'about_vision_title'    => array( 'label' => 'Tầm nhìn/Sứ mệnh - Tiêu đề', 'type' => 'text' ),
		'about_vision_col_title'=> array( 'label' => 'Cột 1 - Tiêu đề', 'type' => 'text' ),
		'about_vision_col_text' => array( 'label' => 'Cột 1 - Nội dung', 'type' => 'textarea' ),
		'about_mission_col_title' => array( 'label' => 'Cột 2 - Tiêu đề', 'type' => 'text' ),
		'about_mission_col_text_1' => array( 'label' => 'Cột 2 - Nội dung dòng 1', 'type' => 'textarea' ),
		'about_mission_col_text_2' => array( 'label' => 'Cột 2 - Nội dung dòng 2', 'type' => 'textarea' ),
		'about_scale_eyebrow'   => array( 'label' => 'Quy mô - Nhãn nhỏ', 'type' => 'text' ),
		'about_scale_title'     => array( 'label' => 'Quy mô - Tiêu đề', 'type' => 'text' ),
		'about_scale_note'      => array( 'label' => 'Quy mô - Ghi chú', 'type' => 'text' ),
		'about_core_eyebrow'    => array( 'label' => 'Giá trị cốt lõi - Nhãn nhỏ', 'type' => 'text' ),
		'about_core_title'      => array( 'label' => 'Giá trị cốt lõi - Tiêu đề', 'type' => 'text' ),
		'about_verify_eyebrow'  => array( 'label' => 'Xác thực - Nhãn nhỏ', 'type' => 'text' ),
		'about_verify_title'    => array( 'label' => 'Xác thực - Tiêu đề', 'type' => 'text' ),
		'about_gallery_eyebrow' => array( 'label' => 'Gallery - Nhãn nhỏ', 'type' => 'text' ),
		'about_gallery_title'   => array( 'label' => 'Gallery - Tiêu đề', 'type' => 'text' ),
		'about_gallery_desc'    => array( 'label' => 'Gallery - Mô tả', 'type' => 'textarea' ),
		'about_cta_eyebrow'     => array( 'label' => 'CTA - Nhãn nhỏ', 'type' => 'text' ),
		'about_cta_title'       => array( 'label' => 'CTA - Tiêu đề', 'type' => 'text' ),
		'about_cta_desc'        => array( 'label' => 'CTA - Mô tả', 'type' => 'textarea' ),
		'about_cta_primary_text'=> array( 'label' => 'CTA - Nút chính', 'type' => 'text' ),
		'about_cta_primary_url' => array( 'label' => 'CTA - Link nút chính', 'type' => 'url' ),
		'about_cta_secondary_text'=> array( 'label' => 'CTA - Nút phụ', 'type' => 'text' ),
		'about_cta_secondary_url' => array( 'label' => 'CTA - Link nút phụ', 'type' => 'url' ),
	);

	for ( $i = 1; $i <= 4; $i++ ) {
		$about_fields[ 'about_scale_' . $i . '_number' ] = array( 'label' => 'Quy mô ' . $i . ' - Số', 'type' => 'number' );
		$about_fields[ 'about_scale_' . $i . '_suffix' ] = array( 'label' => 'Quy mô ' . $i . ' - Hậu tố (vd: +, /63)', 'type' => 'text' );
		$about_fields[ 'about_scale_' . $i . '_desc' ]   = array( 'label' => 'Quy mô ' . $i . ' - Nội dung', 'type' => 'textarea' );
	}

	for ( $i = 1; $i <= 4; $i++ ) {
		$about_fields[ 'about_core_' . $i . '_title' ] = array( 'label' => 'Giá trị cốt lõi ' . $i . ' - Tiêu đề', 'type' => 'text' );
		$about_fields[ 'about_core_' . $i . '_text' ]  = array( 'label' => 'Giá trị cốt lõi ' . $i . ' - Nội dung', 'type' => 'textarea' );
		$about_fields[ 'about_verify_' . $i . '_title' ] = array( 'label' => 'Bước xác thực ' . $i . ' - Tiêu đề', 'type' => 'text' );
		$about_fields[ 'about_verify_' . $i . '_text' ]  = array( 'label' => 'Bước xác thực ' . $i . ' - Nội dung', 'type' => 'textarea' );
	}

	for ( $i = 1; $i <= 3; $i++ ) {
		$about_fields[ 'about_gallery_' . $i . '_image_url' ] = array( 'label' => 'Gallery ' . $i . ' - Ảnh', 'type' => 'media' );
		$about_fields[ 'about_gallery_' . $i . '_alt' ]       = array( 'label' => 'Gallery ' . $i . ' - Alt ảnh', 'type' => 'text' );
		$about_fields[ 'about_gallery_' . $i . '_caption' ]   = array( 'label' => 'Gallery ' . $i . ' - Chú thích', 'type' => 'text' );
	}

	foreach ( $about_fields as $key => $field ) {
		$callback = 'dxvn_render_header_text_input';
		if ( 'textarea' === $field['type'] ) {
			$callback = 'dxvn_render_header_textarea_input';
		} elseif ( 'url' === $field['type'] ) {
			$callback = 'dxvn_render_header_url_input';
		} elseif ( 'media' === $field['type'] ) {
			$callback = 'dxvn_render_header_media_input';
		} elseif ( 'number' === $field['type'] ) {
			$callback = 'dxvn_render_header_number_input';
		}

		add_settings_field(
			$key,
			$field['label'],
			$callback,
			'dxvn-header-settings-about',
			'dxvn_aboutpage_main_section',
			array(
				'key' => $key,
				'placeholder' => ( false !== strpos( $key, '_url' ) ) ? home_url( '/' ) : '',
			)
		);
	}

	add_settings_section(
		'dxvn_contactpage_main_section',
		'Nội dung trang liên hệ',
		'__return_false',
		'dxvn-header-settings-contact'
	);

	$contact_fields = array(
		'contact_hero_eyebrow' => array( 'label' => 'Hero - Nhãn nhỏ', 'type' => 'text' ),
		'contact_hero_title' => array( 'label' => 'Hero - Tiêu đề H1', 'type' => 'text' ),
		'contact_hero_desc' => array( 'label' => 'Hero - Mô tả', 'type' => 'textarea' ),
		'contact_hero_proof' => array( 'label' => 'Hero - Dòng chứng thực', 'type' => 'text' ),
		'contact_hero_image_url' => array( 'label' => 'Hero - Ảnh', 'type' => 'media' ),
		'contact_office_eyebrow' => array( 'label' => 'Văn phòng - Nhãn nhỏ', 'type' => 'text' ),
		'contact_office_title' => array( 'label' => 'Văn phòng - Tiêu đề', 'type' => 'text' ),
		'contact_channel_eyebrow' => array( 'label' => 'Đa kênh - Nhãn nhỏ', 'type' => 'text' ),
		'contact_channel_title' => array( 'label' => 'Đa kênh - Tiêu đề', 'type' => 'text' ),
		'contact_map_embed_url' => array( 'label' => 'Google Maps embed URL mặc định', 'type' => 'url' ),
		'contact_map_label' => array( 'label' => 'Nhãn khối bản đồ', 'type' => 'text' ),
		'contact_form_title' => array( 'label' => 'Form - Tiêu đề', 'type' => 'text' ),
		'contact_form_name_label' => array( 'label' => 'Form - Nhãn Họ tên', 'type' => 'text' ),
		'contact_form_phone_label' => array( 'label' => 'Form - Nhãn SĐT', 'type' => 'text' ),
		'contact_form_need_label' => array( 'label' => 'Form - Nhãn Nhu cầu', 'type' => 'text' ),
		'contact_form_need_option_1' => array( 'label' => 'Form - Nhu cầu 1', 'type' => 'text' ),
		'contact_form_need_option_2' => array( 'label' => 'Form - Nhu cầu 2', 'type' => 'text' ),
		'contact_form_need_option_3' => array( 'label' => 'Form - Nhu cầu 3', 'type' => 'text' ),
		'contact_form_note_label' => array( 'label' => 'Form - Nhãn Ghi chú', 'type' => 'text' ),
		'contact_form_submit_text' => array( 'label' => 'Form - Nút gửi', 'type' => 'text' ),
		'contact_alt_channels_title' => array( 'label' => 'Kênh khác - Tiêu đề', 'type' => 'text' ),
		'contact_zalo_url' => array( 'label' => 'Kênh khác - Link Zalo', 'type' => 'url' ),
		'contact_whatsapp_url' => array( 'label' => 'Kênh khác - Link WhatsApp', 'type' => 'url' ),
		'contact_messenger_url' => array( 'label' => 'Kênh khác - Link Messenger', 'type' => 'url' ),
		'contact_viber_url' => array( 'label' => 'Kênh khác - Link Viber', 'type' => 'url' ),
		'contact_special_title' => array( 'label' => 'Hỗ trợ chuyên biệt - Tiêu đề', 'type' => 'text' ),
		'contact_special_international_title' => array( 'label' => 'Quốc tế - Tiêu đề', 'type' => 'text' ),
		'contact_special_international_desc' => array( 'label' => 'Quốc tế - Nội dung', 'type' => 'textarea' ),
		'contact_special_partner_title' => array( 'label' => 'Đối tác - Tiêu đề', 'type' => 'text' ),
		'contact_special_partner_desc' => array( 'label' => 'Đối tác - Nội dung', 'type' => 'textarea' ),
		'contact_special_partner_cta_text' => array( 'label' => 'Đối tác - Nút CTA', 'type' => 'text' ),
		'contact_special_partner_cta_url' => array( 'label' => 'Đối tác - Link CTA', 'type' => 'url' ),
	);

	for ( $i = 1; $i <= 6; $i++ ) {
		$contact_fields[ 'contact_office_' . $i . '_name' ] = array( 'label' => 'Văn phòng ' . $i . ' - Tên', 'type' => 'text' );
		$contact_fields[ 'contact_office_' . $i . '_address' ] = array( 'label' => 'Văn phòng ' . $i . ' - Địa chỉ', 'type' => 'text' );
		$contact_fields[ 'contact_office_' . $i . '_image_url' ] = array( 'label' => 'Văn phòng ' . $i . ' - Ảnh', 'type' => 'media' );
		$contact_fields[ 'contact_office_' . $i . '_phone' ] = array( 'label' => 'Văn phòng ' . $i . ' - SĐT', 'type' => 'text' );
		$contact_fields[ 'contact_office_' . $i . '_zalo_url' ] = array( 'label' => 'Văn phòng ' . $i . ' - Link Zalo', 'type' => 'url' );
		$contact_fields[ 'contact_office_' . $i . '_map_url' ] = array( 'label' => 'Văn phòng ' . $i . ' - Link Google Maps', 'type' => 'url' );
		$contact_fields[ 'contact_office_' . $i . '_map_embed_url' ] = array( 'label' => 'Văn phòng ' . $i . ' - Maps embed URL', 'type' => 'url' );
	}

	foreach ( $contact_fields as $key => $field ) {
		$callback = 'dxvn_render_header_text_input';
		if ( 'textarea' === $field['type'] ) {
			$callback = 'dxvn_render_header_textarea_input';
		} elseif ( 'url' === $field['type'] ) {
			$callback = 'dxvn_render_header_url_input';
		} elseif ( 'media' === $field['type'] ) {
			$callback = 'dxvn_render_header_media_input';
		}

		add_settings_field(
			$key,
			$field['label'],
			$callback,
			'dxvn-header-settings-contact',
			'dxvn_contactpage_main_section',
			array(
				'key' => $key,
				'placeholder' => ( false !== strpos( $key, '_url' ) ) ? home_url( '/' ) : '',
			)
		);
	}
}

function dxvn_sanitize_header_settings( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$defaults = dxvn_get_header_default_settings();
	$current  = dxvn_get_header_settings();
	$sanitized = array();
	$textarea_keys = array(
		'home_benefits_desc',
		'home_whyus_desc',
		'home_final_cta_desc',
		'about_hero_desc',
		'about_vision_col_text',
		'about_mission_col_text_1',
		'about_mission_col_text_2',
		'about_gallery_desc',
		'about_cta_desc',
		'contact_hero_desc',
		'contact_special_international_desc',
		'contact_special_partner_desc',
	);

	for ( $i = 1; $i <= 6; $i++ ) {
		$textarea_keys[] = 'home_benefit_' . $i . '_text';
	}
	for ( $i = 1; $i <= 3; $i++ ) {
		$textarea_keys[] = 'home_process_' . $i . '_text';
		$textarea_keys[] = 'home_commit_' . $i . '_text';
		$textarea_keys[] = 'home_testimonial_' . $i . '_text';
	}
	for ( $i = 1; $i <= 5; $i++ ) {
		$textarea_keys[] = 'home_faq_' . $i . '_a';
	}
	for ( $i = 1; $i <= 4; $i++ ) {
		$textarea_keys[] = 'about_scale_' . $i . '_desc';
		$textarea_keys[] = 'about_core_' . $i . '_text';
		$textarea_keys[] = 'about_verify_' . $i . '_text';
	}

	foreach ( $defaults as $key => $default_value ) {
		if ( ! array_key_exists( $key, $input ) ) {
			$sanitized[ $key ] = $current[ $key ] ?? $default_value;
			continue;
		}

		if ( preg_match( '/_url$/', (string) $key ) ) {
			$raw_url = trim( (string) $input[ $key ] );
			if ( '' === $raw_url || '#' === $raw_url ) {
				$sanitized[ $key ] = '#';
			} else {
				$sanitized[ $key ] = esc_url_raw( $raw_url );
			}
			continue;
		}

		if ( preg_match( '/_number$/', (string) $key ) ) {
			$sanitized[ $key ] = absint( $input[ $key ] );
			continue;
		}

		if ( in_array( $key, $textarea_keys, true ) ) {
			$sanitized[ $key ] = sanitize_textarea_field( $input[ $key ] );
			continue;
		}

		$sanitized[ $key ] = sanitize_text_field( $input[ $key ] );
	}

	return $sanitized;
}

function dxvn_get_header_setting( $key, $default = '' ) {
	$options = dxvn_get_header_settings();
	$value   = $options[ $key ] ?? '';

	return '' !== $value ? $value : $default;
}

function dxvn_get_header_settings() {
	$options = get_option( 'dxvn_header_settings', array() );
	$defaults = dxvn_get_header_default_settings();

	if ( ! is_array( $options ) ) {
		return $defaults;
	}

	return wp_parse_args( $options, $defaults );
}

function dxvn_get_header_default_settings() {
	$defaults = array(
		'hotline_number'      => '0900 000 000',
		'booking_button_text' => 'Đặt vé ngay',
		'booking_button_url'  => home_url( '/#mttf-search-input' ),
		'home_hub_aria_label' => 'Danh sách tuyến nổi bật',
		'home_benefits_eyebrow' => 'Lợi ích nổi bật',
		'home_benefits_title' => '6 lợi ích giúp khách hàng an tâm hơn khi tìm và đặt xe',
		'home_benefits_desc'  => 'Không chỉ tìm tuyến nhanh, nền tảng còn tập trung vào độ chính xác, minh bạch giá và kết nối trực tiếp với các nhà xe uy tín.',
		'home_process_eyebrow' => 'Quy trình đặt xe',
		'home_process_title'  => 'Tìm tuyến, để lại số, chuyên viên gọi lại',
		'home_whyus_eyebrow'  => 'Cam kết dịch vụ',
		'home_whyus_title'    => '3 cam kết của ĐẶT XE VIỆT NAM',
		'home_whyus_desc'     => 'Tập trung đúng 3 điều quan trọng nhất khi chọn tuyến: an toàn, minh bạch giá và được tư vấn đúng lộ trình.',
		'home_testimonials_eyebrow' => 'Đánh giá khách hàng',
		'home_testimonials_title' => 'Khách hàng nói gì sau khi được tư vấn và xác nhận chuyến',
		'home_faq_eyebrow'    => 'FAQ',
		'home_faq_title'      => '5 câu hỏi thường gặp',
		'home_final_cta_eyebrow' => 'Hỗ trợ nhanh',
		'home_final_cta_title' => 'Chưa thấy tuyến phù hợp? Gọi ngay hoặc để lại số để được tư vấn',
		'home_final_cta_desc' => 'Nếu bạn chưa chắc nên chọn tuyến nào, hãy ưu tiên gọi trực tiếp. Trường hợp không tiện nghe máy, chỉ cần để lại số để chuyên viên gọi lại.',
		'home_final_cta_primary_text' => 'Ưu tiên gọi 1900 8164',
		'home_final_cta_primary_phone' => '19008164',
		'home_final_cta_secondary_text' => 'Tìm tuyến & để lại số',
		'home_final_cta_secondary_url' => home_url( '/#mttf-search-input' ),
		'about_hero_eyebrow' => 'Datxevietnam',
		'about_hero_title' => 'Datxevietnam - Hệ thống kết nối vận tải cao cấp quy mô toàn quốc.',
		'about_hero_desc' => 'Chúng tôi đang thay đổi cách khách hàng tiếp cận xe Limousine và Cabin VIP bằng một quy trình minh bạch, trực tiếp và có kiểm chứng. Khách hàng không còn phải lo về thông tin mơ hồ, giá không rõ ràng hoặc những đầu mối trung gian không đáng tin.',
		'about_hero_image_url' => get_stylesheet_directory_uri() . '/assets/images/about-hero-limousine.jpg',
		'about_vision_eyebrow' => 'Triết lý vận hành',
		'about_vision_title' => 'Tầm nhìn và sứ mệnh',
		'about_vision_col_title' => 'Tầm nhìn',
		'about_vision_col_text' => 'Trở thành nền tảng top of mind - điểm đến đầu tiên khi khách hàng cần di chuyển hạng thương gia tại Việt Nam.',
		'about_mission_col_title' => 'Sứ mệnh',
		'about_mission_col_text_1' => 'Với khách hàng: xóa bỏ rủi ro lừa đảo, mang lại giá gốc và sự an tâm tuyệt đối.',
		'about_mission_col_text_2' => 'Với đối tác: số hóa quy trình tiếp cận khách hàng, khẳng định uy tín cho các nhà xe làm dịch vụ tử tế.',
		'about_scale_eyebrow' => 'The power of network',
		'about_scale_title' => 'Quy mô toàn quốc',
		'about_scale_note' => 'Số liệu cập nhật đến tháng 04/2026.',
		'about_core_eyebrow' => '4 trụ cột thật',
		'about_core_title' => 'Giá trị cốt lõi',
		'about_verify_eyebrow' => 'Trụ cột lòng tin',
		'about_verify_title' => 'Quy trình xác thực đối tác',
		'about_gallery_eyebrow' => 'Authentic gallery',
		'about_gallery_title' => 'Marketing thực tế',
		'about_gallery_desc' => 'Chúng tôi không chỉ sở hữu website, chúng tôi sở hữu sự am hiểu thực tế trên mỗi cung đường.',
		'about_cta_eyebrow' => 'Đồng hành cùng Datxevietnam',
		'about_cta_title' => 'Sẵn sàng di chuyển an tâm hơn hoặc mở rộng mạng lưới đối tác cùng chúng tôi',
		'about_cta_desc' => 'Datxevietnam chân thành chào đón khách hàng cần hành trình cao cấp và các nhà xe mong muốn phát triển bền vững.',
		'about_cta_primary_text' => 'Tìm chuyến xe ngay',
		'about_cta_primary_url' => home_url( '/?focus_search=1#mttf-search-input' ),
		'about_cta_secondary_text' => 'Trở thành đối tác',
		'about_cta_secondary_url' => home_url( '/lien-he' ),
		'contact_hero_eyebrow' => 'Liên hệ Datxevietnam',
		'contact_hero_title' => 'Liên hệ với Chuyên viên Datxevietnam',
		'contact_hero_desc' => 'Đội ngũ chuyên viên hỗ trợ 24/7 cho mọi lộ trình toàn quốc. Tư vấn trực tiếp từ nhà xe, không qua trung gian.',
		'contact_hero_proof' => 'Phản hồi nhanh theo tuyến toàn quốc - ưu tiên nhu cầu gấp trong ngày.',
		'contact_hero_image_url' => get_stylesheet_directory_uri() . '/assets/images/contact-hero-team.jpg',
		'contact_office_eyebrow' => 'Hệ thống điều hành',
		'contact_office_title' => 'Văn phòng điều hành Datxevietnam tại Hà Nội',
		'contact_channel_eyebrow' => 'Tương tác đa kênh',
		'contact_channel_title' => 'Bản đồ điều hướng và form hỗ trợ nhanh',
		'contact_map_embed_url' => 'https://www.google.com/maps?q=Hanoi&output=embed',
		'contact_map_label' => 'Bản đồ văn phòng',
		'contact_form_title' => 'Gửi nhu cầu liên hệ',
		'contact_form_name_label' => 'Họ và tên',
		'contact_form_phone_label' => 'Số điện thoại',
		'contact_form_need_label' => 'Nhu cầu',
		'contact_form_need_option_1' => 'Tư vấn đặt xe',
		'contact_form_need_option_2' => 'Hợp tác nhà xe',
		'contact_form_need_option_3' => 'Phản ánh chất lượng',
		'contact_form_note_label' => 'Ghi chú lộ trình',
		'contact_form_submit_text' => 'Gửi yêu cầu tư vấn',
		'contact_alt_channels_title' => 'Kênh hỗ trợ khác',
		'contact_zalo_url' => '#',
		'contact_whatsapp_url' => '#',
		'contact_messenger_url' => '#',
		'contact_viber_url' => '#',
		'contact_special_title' => 'Hỗ trợ chuyên biệt',
		'contact_special_international_title' => 'International Travelers & Expats',
		'contact_special_international_desc' => 'Specialized English support for all VIP routes.',
		'contact_special_partner_title' => 'Tuyển đối tác vận hành',
		'contact_special_partner_desc' => 'Mời các nhà xe Limousine/Cabin VIP gia nhập hệ thống để mở rộng lượng khách chất lượng.',
		'contact_special_partner_cta_text' => 'Đăng ký hợp tác nhà xe',
		'contact_special_partner_cta_url' => home_url( '/lien-he' ),
	);

	$benefits = array(
		1 => array( 'Kết nối trực tiếp chuyên viên nhà xe', 'Khách hàng được kết nối thẳng tới đội ngũ điều hành của nhà xe, không phải đi qua tổng đài tự động hay các lớp trung gian rườm rà.' ),
		2 => array( 'Tư vấn lộ trình chuẩn xác', 'Thông tin về giờ đón trả, điểm dừng, loại xe, thời gian di chuyển và chất lượng dịch vụ được tư vấn rõ ràng, sát thực tế.' ),
		3 => array( 'Giá gốc niêm yết, không phí trung gian', 'Hệ thống ưu tiên hiển thị mức giá minh bạch từ đối tác vận tải, hạn chế tối đa phí ẩn hay tình trạng bị nâng giá qua cò vé.' ),
		4 => array( 'Giảm rủi ro lừa đảo cọc vé', 'Các nhà xe trên hệ thống được chọn lọc và xác minh, giúp khách hàng yên tâm hơn trước các trường hợp mạo danh để thu cọc.' ),
		5 => array( 'Danh mục đối tác uy tín, đa dạng', 'Tập trung các dòng xe Limousine và Cabin VIP 24 phòng, giúp khách hàng có lựa chọn phù hợp trên một nền tảng duy nhất.' ),
		6 => array( 'Tìm kiếm thông minh, trải nghiệm cực nhanh', 'Công nghệ tìm kiếm AJAX giúp khách thấy ngay tuyến cần tìm khi vừa gõ, mang lại trải nghiệm mượt mà trên cả điện thoại và máy tính.' ),
	);
	foreach ( $benefits as $index => $content ) {
		$defaults[ 'home_benefit_' . $index . '_title' ] = $content[0];
		$defaults[ 'home_benefit_' . $index . '_text' ]  = $content[1];
	}

	$process = array(
		1 => array( 'Tìm tuyến phù hợp', 'Nhập tỉnh hoặc thành phố, dùng bộ lọc miền và loại xe để chọn đúng chuyến bạn cần.' ),
		2 => array( 'Bấm tư vấn ngay để được kết nối trực tiếp', 'Ưu tiên để lại số điện thoại để hệ thống kết nối trực tiếp bạn với chuyên viên tư vấn của nhà xe phù hợp.' ),
		3 => array( 'Nhận cuộc gọi xác nhận', 'Chuyên viên sẽ liên hệ lại để tư vấn, báo chỗ, chốt thông tin tuyến và hỗ trợ giữ chỗ nếu phù hợp.' ),
	);
	foreach ( $process as $index => $content ) {
		$defaults[ 'home_process_' . $index . '_title' ] = $content[0];
		$defaults[ 'home_process_' . $index . '_text' ]  = $content[1];
	}

	$commits = array(
		1 => array( '100% đối tác xác thực', 'Đặt vé an toàn hơn, giảm mạnh nỗi lo gặp đơn vị mạo danh hoặc bị lừa đảo cọc vé.' ),
		2 => array( 'Giá gốc từ nhà xe', 'Thông tin giá được hiển thị minh bạch, không phí ẩn, không phải đi qua nhiều lớp trung gian.' ),
		3 => array( 'Tư vấn chuẩn lộ trình', 'Khách được kết nối trực tiếp với chuyên viên nhà xe để hỏi nhanh giờ đón, loại xe và lịch trình thực tế.' ),
	);
	foreach ( $commits as $index => $content ) {
		$defaults[ 'home_commit_' . $index . '_title' ] = $content[0];
		$defaults[ 'home_commit_' . $index . '_text' ]  = $content[1];
	}

	$testimonials = array(
		1 => array( '“Mình cần lên Sa Pa cuối tuần, vừa chọn tuyến Hà Nội - Sa Pa là được gọi lại khá nhanh. Nhà xe tư vấn rõ giờ đón và loại xe nên chốt rất yên tâm.”', 'Anh Huy, Hà Nội', 'Đã gửi liên hệ tuyến Hà Nội - Sa Pa' ),
		2 => array( '“Tuyến Hà Nội - Hạ Long hiển thị rất rõ, mình gửi liên hệ xong là có bên nhà xe xác nhận ngay. Giá và điểm đón cũng được báo cụ thể nên đỡ mất thời gian hỏi lại.”', 'Chị Lan, Hà Nội', 'Đã gửi liên hệ tuyến Hà Nội - Hạ Long' ),
		3 => array( '“Mình đi Vũng Tàu nên để lại liên hệ tuyến Sài Gòn - Vũng Tàu, chỉ một lúc sau là có người gọi tư vấn. Cách này nhanh hơn hẳn so với tự tìm từng nhà xe.”', 'Anh Nam, TP.HCM', 'Đã gửi liên hệ tuyến Sài Gòn - Vũng Tàu' ),
	);
	foreach ( $testimonials as $index => $content ) {
		$defaults[ 'home_testimonial_' . $index . '_text' ]   = $content[0];
		$defaults[ 'home_testimonial_' . $index . '_author' ] = $content[1];
		$defaults[ 'home_testimonial_' . $index . '_status' ] = $content[2];
	}

	$faqs = array(
		1 => array( 'Tôi nên gọi trực tiếp hay để lại số trong popup?', 'Nếu bạn cần tư vấn ngay thì nên gọi trực tiếp. Nếu đang bận hoặc muốn được gọi lại, bạn có thể bấm nút tư vấn và để lại số điện thoại.' ),
		2 => array( 'Sau khi để lại số, bao lâu sẽ có người gọi lại?', 'Thông thường chuyên viên sẽ gọi lại khá nhanh trong giờ làm việc để xác nhận nhu cầu và tư vấn tuyến phù hợp.' ),
		3 => array( 'Tôi có thể nhắn Zalo thay vì gọi điện không?', 'Có. Nếu bạn không tiện nghe máy, có thể bấm nút Zalo trên card tuyến để nhắn tin nhanh với bên hỗ trợ.' ),
		4 => array( 'Tôi có cần thanh toán ngay trên website không?', 'Không. Flow hiện tại ưu tiên tư vấn, xác nhận tuyến và hỗ trợ giữ chỗ trước. Các bước tiếp theo sẽ được chuyên viên hướng dẫn rõ.' ),
		5 => array( 'Làm sao để được giữ chỗ nhanh hơn vào giờ cao điểm?', 'Bạn nên gọi sớm hoặc để lại số ngay sau khi chọn tuyến để đội ngũ hỗ trợ kiểm tra chỗ và phản hồi nhanh hơn.' ),
	);
	foreach ( $faqs as $index => $content ) {
		$defaults[ 'home_faq_' . $index . '_q' ] = $content[0];
		$defaults[ 'home_faq_' . $index . '_a' ] = $content[1];
	}

	$about_scales = array(
		1 => array( 63, '/63', 'Tỉnh thành có sự hiện diện của mạng lưới đối tác.' ),
		2 => array( 500, '+', 'Nhà xe Limousine và Cabin VIP đã qua xác thực.' ),
		3 => array( 1000, '+', 'Lộ trình di chuyển được cập nhật mỗi ngày.' ),
		4 => array( 5, '+', 'Văn phòng điều hành trực tiếp tại các quận trọng điểm Hà Nội.' ),
	);
	foreach ( $about_scales as $index => $content ) {
		$defaults[ 'about_scale_' . $index . '_number' ] = $content[0];
		$defaults[ 'about_scale_' . $index . '_suffix' ] = $content[1];
		$defaults[ 'about_scale_' . $index . '_desc' ]   = $content[2];
	}

	$about_core_values = array(
		1 => array( 'Xác thực', 'Chỉ làm việc với đối tác có pháp lý và xe thực tế chuẩn VIP.' ),
		2 => array( 'Trực tiếp', 'Kết nối thẳng khách hàng với chuyên viên nhà xe - không trung gian.' ),
		3 => array( 'Minh bạch', 'Giá gốc niêm yết, không phí ẩn, không "cò" vé.' ),
		4 => array( 'Trách nhiệm', 'Ưu tiên bảo vệ quyền lợi khách hàng và tiền cọc trong mọi trường hợp.' ),
	);
	foreach ( $about_core_values as $index => $content ) {
		$defaults[ 'about_core_' . $index . '_title' ] = $content[0];
		$defaults[ 'about_core_' . $index . '_text' ]  = $content[1];
	}

	$about_verify_steps = array(
		1 => array( 'Thẩm định pháp lý', 'Kiểm tra giấy phép kinh doanh vận tải và hồ sơ pháp nhân.' ),
		2 => array( 'Kiểm tra thực tế', 'Chụp ảnh, quay phim khoang xe và tiện nghi thực tế.' ),
		3 => array( 'Cam kết chất lượng', 'Nhà xe ký cam kết về thái độ tài xế, lộ trình và tiêu chuẩn phục vụ.' ),
		4 => array( 'Cấp chứng nhận', 'Đưa nhà xe lên hệ thống với nhãn đã xác minh.' ),
	);
	foreach ( $about_verify_steps as $index => $content ) {
		$defaults[ 'about_verify_' . $index . '_title' ] = $content[0];
		$defaults[ 'about_verify_' . $index . '_text' ]  = $content[1];
	}

	$about_gallery_items = array(
		1 => array(
			get_stylesheet_directory_uri() . '/assets/images/about-gallery-01.jpg',
			'Founder làm việc với đối tác nhà xe',
			'Founder làm việc trực tiếp với chủ xe tại điểm vận hành.',
		),
		2 => array(
			get_stylesheet_directory_uri() . '/assets/images/about-gallery-02.jpg',
			'Đội ngũ hỗ trợ check-in cho khách',
			'Đội ngũ đang check-in và hỗ trợ khách tại điểm đón.',
		),
		3 => array(
			get_stylesheet_directory_uri() . '/assets/images/about-gallery-03.jpg',
			'Xe Limousine vận hành trên cao tốc',
			'Xe Limousine vận hành trên tuyến trục trong giờ cao điểm.',
		),
	);
	foreach ( $about_gallery_items as $index => $content ) {
		$defaults[ 'about_gallery_' . $index . '_image_url' ] = $content[0];
		$defaults[ 'about_gallery_' . $index . '_alt' ]       = $content[1];
		$defaults[ 'about_gallery_' . $index . '_caption' ]   = $content[2];
	}

	$contact_offices = array(
		1 => array( 'Văn phòng Long Biên', '80 Hồng Tiến, Long Biên, Hà Nội' ),
		2 => array( 'Văn phòng Cầu Giấy', '23 Tú Mỡ, Cầu Giấy, Hà Nội' ),
		3 => array( 'Văn phòng Hoàn Kiếm', '214 Trần Quang Khải, Hoàn Kiếm, Hà Nội' ),
		4 => array( 'Văn phòng Hai Bà Trưng', '56 Phố Vọng, Hai Bà Trưng, Hà Nội' ),
		5 => array( 'Văn phòng Thanh Xuân', '12 Nguyễn Trãi, Thanh Xuân, Hà Nội' ),
		6 => array( 'Văn phòng Hà Đông', '95 Trần Phú, Hà Đông, Hà Nội' ),
	);
	foreach ( $contact_offices as $index => $office ) {
		$defaults[ 'contact_office_' . $index . '_name' ]          = $office[0];
		$defaults[ 'contact_office_' . $index . '_address' ]       = $office[1];
		$defaults[ 'contact_office_' . $index . '_image_url' ]     = get_stylesheet_directory_uri() . '/assets/images/contact-office-' . $index . '.jpg';
		$defaults[ 'contact_office_' . $index . '_phone' ]         = '1900 8164';
		$defaults[ 'contact_office_' . $index . '_zalo_url' ]      = '#';
		$defaults[ 'contact_office_' . $index . '_map_url' ]       = 'https://maps.google.com';
		$defaults[ 'contact_office_' . $index . '_map_embed_url' ] = 'https://www.google.com/maps?q=' . rawurlencode( $office[1] ) . '&output=embed';
	}

	return $defaults;
}

function dxvn_render_header_text_input( $args ) {
	$key         = $args['key'];
	$placeholder = $args['placeholder'] ?? '';
	$default_map = dxvn_get_header_default_settings();
	$default = $default_map[ $key ] ?? '';
	$value   = dxvn_get_header_setting( $key, $default );
	?>
	<input
		type="text"
		class="regular-text"
		name="<?php echo esc_attr( 'dxvn_header_settings[' . $key . ']' ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		placeholder="<?php echo esc_attr( $placeholder ); ?>"
	/>
	<?php
}

function dxvn_render_header_textarea_input( $args ) {
	$key       = $args['key'];
	$defaults  = dxvn_get_header_default_settings();
	$default   = $defaults[ $key ] ?? '';
	$value     = dxvn_get_header_setting( $key, $default );
	?>
	<textarea
		class="large-text"
		rows="3"
		name="<?php echo esc_attr( 'dxvn_header_settings[' . $key . ']' ); ?>"
	><?php echo esc_textarea( $value ); ?></textarea>
	<?php
}

function dxvn_render_header_url_input( $args ) {
	$key         = $args['key'];
	$placeholder = $args['placeholder'] ?? '';
	$value       = dxvn_get_header_setting( $key, home_url( '/#mttf-search-input' ) );
	?>
	<input
		type="text"
		class="regular-text"
		name="<?php echo esc_attr( 'dxvn_header_settings[' . $key . ']' ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		placeholder="<?php echo esc_attr( $placeholder ); ?>"
		autocomplete="off"
	/>
	<?php
}

function dxvn_render_header_number_input( $args ) {
	$key   = $args['key'];
	$value = (int) dxvn_get_header_setting( $key, 0 );
	?>
	<input
		type="number"
		class="small-text"
		name="<?php echo esc_attr( 'dxvn_header_settings[' . $key . ']' ); ?>"
		value="<?php echo esc_attr( (string) $value ); ?>"
		min="0"
		step="1"
	/>
	<?php
}

function dxvn_render_header_media_input( $args ) {
	$key   = $args['key'];
	$value = (string) dxvn_get_header_setting( $key, '' );
	?>
	<div class="dxvn-header-media-wrap" data-target="<?php echo esc_attr( $key ); ?>">
		<input
			type="text"
			class="regular-text dxvn-header-media-url"
			name="<?php echo esc_attr( 'dxvn_header_settings[' . $key . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			readonly
		/>
		<p>
			<button type="button" class="button dxvn-header-media-select">Chọn ảnh</button>
			<button type="button" class="button dxvn-header-media-clear">Xóa ảnh</button>
		</p>
		<?php if ( '' !== $value ) : ?>
			<img src="<?php echo esc_url( $value ); ?>" alt="" style="max-width:180px;height:auto;border:1px solid #dcdcde;border-radius:6px;" />
		<?php endif; ?>
	</div>
	<?php
}

function dxvn_enqueue_header_settings_assets( $hook ) {
	if ( 'appearance_page_dxvn-header-settings' !== $hook ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'jquery' );
	$inline_script = <<<'JS'
(function($){
	$(function(){
		$(document).on('click', '.dxvn-header-media-select', function(e){
			e.preventDefault();
			var wrap = $(this).closest('.dxvn-header-media-wrap');
			var input = wrap.find('.dxvn-header-media-url');
			var frame = wp.media({
				title: 'Chọn ảnh',
				button: { text: 'Dùng ảnh này' },
				multiple: false
			});

			frame.on('select', function(){
				var a = frame.state().get('selection').first().toJSON();
				input.val(a.url || '');
			});

			frame.open();
		});

		$(document).on('click', '.dxvn-header-media-clear', function(e){
			e.preventDefault();
			var wrap = $(this).closest('.dxvn-header-media-wrap');
			wrap.find('.dxvn-header-media-url').val('');
			wrap.find('img').remove();
		});
	});
})(jQuery);
JS;
	wp_add_inline_script(
		'jquery-core',
		$inline_script
	);
}

function dxvn_render_header_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'header';
	$tabs = array(
		'header' => 'Header',
		'home'   => 'Trang chủ',
		'about'  => 'Giới thiệu',
		'contact'=> 'Liên hệ',
	);
	$current_page = 'dxvn-header-settings';
	if ( 'home' === $current_tab ) {
		$current_page = 'dxvn-header-settings-home';
	} elseif ( 'about' === $current_tab ) {
		$current_page = 'dxvn-header-settings-about';
	} elseif ( 'contact' === $current_tab ) {
		$current_page = 'dxvn-header-settings-contact';
	}
	?>
	<div class="wrap">
		<h1>Cài đặt Header DXVN</h1>
		<?php if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>Đã lưu cài đặt thành công.</p>
			</div>
		<?php endif; ?>
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
				<?php
				$tab_url = add_query_arg(
					array(
						'page' => 'dxvn-header-settings',
						'tab'  => $tab_key,
					),
					admin_url( 'themes.php' )
				);
				?>
				<a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( $tab_label ); ?>
				</a>
			<?php endforeach; ?>
		</h2>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'dxvn_header_settings_group' );
			do_settings_sections( $current_page );
			submit_button( 'Lưu cài đặt' );
			?>
		</form>
	</div>
	<?php
}

add_action( 'admin_menu', 'dxvn_register_footer_settings_page' );
function dxvn_register_footer_settings_page() {
	add_theme_page(
		'Cài đặt Footer DXVN',
		'Footer DXVN',
		'manage_options',
		'dxvn-footer-settings',
		'dxvn_render_footer_settings_page'
	);
}

add_action( 'admin_init', 'dxvn_register_footer_settings' );
function dxvn_register_footer_settings() {
	register_setting(
		'dxvn_footer_settings_group',
		'dxvn_footer_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'dxvn_sanitize_footer_settings',
			'default'           => dxvn_get_footer_default_settings(),
		)
	);
}

function dxvn_get_footer_default_settings() {
	return array(
		'partners_title'    => 'Đối tác của chúng tôi',
		'partners'          => array(),
		'brand_description' => 'Nền tảng đặt xe limousine và du lịch hàng đầu Việt Nam. Cam kết: thông tin minh bạch, hỗ trợ nhanh, xác nhận qua email/SMS.',
		'brand_contact_items' => "Hotline|0900 000 000\nDi động|094 247 1111\nEmail|" . get_option( 'admin_email', 'support@datxevietnam.vn' ) . "\nĐịa chỉ|23 P. Tô Mịch, Phường Yên Hòa, Hà Nội",
		'office_title'      => 'Danh sách văn phòng',
		'office_map_label'  => 'Xem bản đồ',
		'office_items'      => "80 Hồng Tiến, Phường Bồ Đề, Hà Nội|#\n214 Đ. Trần Quang Khải, phường Hoàn Kiếm, Hà Nội|#\n56 P. Vọng, Phường Bạch Mai, Hà Nội|#\n23 P. Tú Mỡ, Phường Yên Hòa, Hà Nội|#\n51 Minh Khai, Phường Bạch Mai, Hà Nội|#",
		'services_title'    => 'Dịch vụ phổ biến',
		'services_items'    => "Xe Limousine Hà Nội - Thanh Hóa|" . home_url( '/xe-limousine-ha-noi-thanh-hoa' ) . "\nXe Limousine Hà Nội - Hạ Long|" . home_url( '/xe-limousine-ha-noi-ha-long' ) . "\nXe Limousine Hà Nội - Phú Thọ|" . home_url( '/xe-limousine-ha-noi-phu-tho' ) . "\nXe Limousine Hà Nội - Ninh Bình|" . home_url( '/xe-limousine-ha-noi-ninh-binh' ),
		'about_title'       => 'Về Đặt Xe Việt Nam',
		'about_items'       => "Giới thiệu|" . home_url( '/gioi-thieu' ) . "\nTin tức & sự kiện|" . home_url( '/tin-tuc' ),
		'support_title'     => 'Hỗ trợ',
		'support_items'     => "Chính sách bảo mật|" . home_url( '/chinh-sach-bao-mat' ) . "\nĐiều khoản dịch vụ|" . home_url( '/dieu-khoan-dich-vu' ) . "\nChính sách đổi trả & hoàn tiền|" . home_url( '/chinh-sach-doi-tra' ) . "\nChính sách thanh toán|" . home_url( '/chinh-sach-thanh-toan' ),
		'company_name'      => 'CÔNG TY CỔ PHẦN AN NAM DISCOVERY',
		'company_meta'      => 'MST: 0111205475  -  Số: 01-3006/2025/CDLQGVN-GP LHQT',
		'copyright_text'    => '© ' . gmdate( 'Y' ) . ' Datxevietnam.vn. All rights reserved.',
	);
}

function dxvn_sanitize_footer_settings( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$defaults = dxvn_get_footer_default_settings();
	$text_keys = array(
		'partners_title',
		'brand_description',
		'office_title',
		'office_map_label',
		'services_title',
		'about_title',
		'support_title',
		'company_name',
		'company_meta',
		'copyright_text',
	);
	$list_keys = array(
		'brand_contact_items',
		'office_items',
		'services_items',
		'about_items',
		'support_items',
	);
	$sanitized = array();

	foreach ( $text_keys as $key ) {
		$sanitized[ $key ] = sanitize_text_field( $input[ $key ] ?? $defaults[ $key ] );
	}

	foreach ( $list_keys as $key ) {
		$sanitized[ $key ] = sanitize_textarea_field( $input[ $key ] ?? $defaults[ $key ] );
	}

	$sanitized['partners'] = dxvn_sanitize_footer_partners( $input['partners'] ?? array() );

	if ( '' === $sanitized['copyright_text'] ) {
		$sanitized['copyright_text'] = $defaults['copyright_text'];
	}

	return $sanitized;
}

function dxvn_sanitize_footer_partners( $partners ) {
	$partners = is_array( $partners ) ? $partners : array();
	$cleaned  = array();

	foreach ( $partners as $partner ) {
		if ( ! is_array( $partner ) ) {
			continue;
		}

		$image_id  = absint( $partner['image_id'] ?? 0 );
		$image_url = esc_url_raw( trim( (string) ( $partner['image_url'] ?? '' ) ) );
		$raw_link  = trim( (string) ( $partner['link'] ?? '' ) );
		$link      = '#' === $raw_link ? '#' : esc_url_raw( $raw_link );
		$name      = sanitize_text_field( $partner['name'] ?? '' );

		if ( 0 === $image_id && '' === $image_url ) {
			continue;
		}

		if ( '' === $image_url && $image_id > 0 ) {
			$attachment_url = wp_get_attachment_url( $image_id );
			if ( is_string( $attachment_url ) && '' !== $attachment_url ) {
				$image_url = esc_url_raw( $attachment_url );
			}
		}

		$cleaned[] = array(
			'image_id'  => $image_id,
			'image_url' => $image_url,
			'link'      => $link,
			'name'      => $name,
		);
	}

	return $cleaned;
}

/**
 * Resolve partner logo URL from attachment ID or stored direct URL.
 *
 * @param array<string,mixed> $partner Partner row.
 * @param string              $size    Image size.
 * @return string
 */
function dxvn_resolve_footer_partner_image_url( $partner, $size = 'medium' ) {
	if ( ! is_array( $partner ) ) {
		return '';
	}

	$image_id = absint( $partner['image_id'] ?? 0 );
	if ( $image_id > 0 ) {
		$sizes = array_unique( array( $size, 'medium', 'full', 'thumbnail' ) );
		foreach ( $sizes as $try_size ) {
			$url = wp_get_attachment_image_url( $image_id, $try_size );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}
	}

	$direct = trim( (string) ( $partner['image_url'] ?? '' ) );
	if ( '' !== $direct ) {
		if ( wp_http_validate_url( $direct ) ) {
			return esc_url_raw( $direct );
		}
		if ( 0 === strpos( $direct, '/' ) ) {
			return esc_url_raw( home_url( $direct ) );
		}
	}

	return '';
}

/**
 * Partners that have a displayable logo URL.
 *
 * @param array<int,array<string,mixed>> $partners Raw partners from settings.
 * @return array<int,array<string,mixed>>
 */
function dxvn_get_footer_partners_for_display( array $partners ) {
	$ready = array();

	foreach ( $partners as $partner ) {
		if ( ! is_array( $partner ) ) {
			continue;
		}

		$image_url = dxvn_resolve_footer_partner_image_url( $partner );
		if ( '' === $image_url ) {
			continue;
		}

		$partner['_display_image_url'] = $image_url;
		$ready[]                     = $partner;
	}

	return $ready;
}

function dxvn_get_footer_settings() {
	$defaults = dxvn_get_footer_default_settings();
	$options  = get_option( 'dxvn_footer_settings', array() );

	if ( ! is_array( $options ) ) {
		return $defaults;
	}

	return wp_parse_args( $options, $defaults );
}

function dxvn_get_footer_setting( $key, $default = '' ) {
	$options = dxvn_get_footer_settings();
	$value   = $options[ $key ] ?? $default;

	return '' !== $value ? $value : $default;
}

function dxvn_parse_footer_links( $raw_lines ) {
	$items = array();
	$lines = preg_split( '/\r\n|\r|\n/', (string) $raw_lines );

	if ( ! is_array( $lines ) ) {
		return $items;
	}

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}

		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$text  = $parts[0] ?? '';
		$url   = $parts[1] ?? '';

		if ( '' === $text ) {
			continue;
		}

		$items[] = array(
			'text' => $text,
			'url'  => $url,
		);
	}

	return $items;
}

function dxvn_parse_footer_contact_items( $raw_lines ) {
	$items = array();
	$lines = preg_split( '/\r\n|\r|\n/', (string) $raw_lines );

	if ( ! is_array( $lines ) ) {
		return $items;
	}

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}

		$parts = array_map( 'trim', explode( '|', $line ) );
		$label = $parts[0] ?? '';
		$value = $parts[1] ?? '';
		$url   = $parts[2] ?? '';

		if ( '' === $label || '' === $value ) {
			continue;
		}

		$items[] = array(
			'label' => $label,
			'value' => $value,
			'url'   => $url,
		);
	}

	return $items;
}

/**
 * Liên hệ cột 1 footer (đảm bảo có Di động ngay sau Hotline).
 *
 * @return array<int,array{label:string,value:string,url:string}>
 */
function dxvn_get_footer_brand_contacts() {
	$settings = dxvn_get_footer_settings();
	$items    = dxvn_parse_footer_contact_items( $settings['brand_contact_items'] ?? '' );

	$has_mobile = false;
	foreach ( $items as $item ) {
		$label_norm = function_exists( 'remove_accents' ) ? strtolower( remove_accents( (string) ( $item['label'] ?? '' ) ) ) : strtolower( (string) ( $item['label'] ?? '' ) );
		if ( false !== strpos( $label_norm, 'di dong' ) || false !== strpos( $label_norm, 'di động' ) ) {
			$has_mobile = true;
			break;
		}
	}

	if ( $has_mobile ) {
		return $items;
	}

	$mobile_item = array(
		'label' => 'Di động',
		'value' => '094 247 1111',
		'url'   => '',
	);

	$new_items = array();
	$inserted  = false;
	foreach ( $items as $item ) {
		$new_items[] = $item;
		$label_norm  = function_exists( 'remove_accents' ) ? strtolower( remove_accents( (string) ( $item['label'] ?? '' ) ) ) : strtolower( (string) ( $item['label'] ?? '' ) );
		if ( ! $inserted && false !== strpos( $label_norm, 'hotline' ) ) {
			$new_items[] = $mobile_item;
			$inserted    = true;
		}
	}

	if ( ! $inserted ) {
		array_unshift( $new_items, $mobile_item );
	}

	return $new_items;
}

/**
 * Taxonomy slug tuyến landing (MTTF).
 *
 * @return string
 */
function dxvn_get_tuyen_taxonomy_slug() {
	return class_exists( 'MTTF_Landing_Taxonomies' ) ? MTTF_Landing_Taxonomies::TAX_TUYEN : 'mttf_tuyen';
}

/**
 * @param WP_Term $term Tuyến term.
 * @return array{text:string,url:string}|null
 */
function dxvn_footer_tuyen_term_to_item( WP_Term $term ) {
	$link = get_term_link( $term );
	if ( is_wp_error( $link ) ) {
		return null;
	}

	return array(
		'text' => 'Đặt vé xe ' . $term->name,
		'url'  => $link,
	);
}

/**
 * @param WP_Term $term Tuyến term.
 * @return int
 */
function dxvn_count_active_routes_for_tuyen_term( WP_Term $term ) {
	if ( class_exists( 'MTTF_Landing_Query' ) ) {
		return count( MTTF_Landing_Query::get_routes_for_tuyen( $term ) );
	}

	$routes = get_posts(
		array(
			'post_type'      => 'tuyen_xe',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => dxvn_get_tuyen_taxonomy_slug(),
					'field'    => 'term_id',
					'terms'    => array( (int) $term->term_id ),
				),
			),
			'meta_query'     => array(
				array(
					'key'   => '_mttf_is_active',
					'value' => '1',
				),
			),
		)
	);

	return is_array( $routes ) ? count( $routes ) : 0;
}

/**
 * Dịch vụ phổ biến — cột 3 footer (động từ mttf_tuyen).
 *
 * @return array<int,array{text:string,url:string}>
 */
function dxvn_get_footer_popular_routes() {
	$limit    = 8;
	$taxonomy = dxvn_get_tuyen_taxonomy_slug();
	$items    = array();
	$used_ids = array();

	$push_term = static function ( WP_Term $term ) use ( &$items, &$used_ids, $limit ) {
		if ( count( $items ) >= $limit || isset( $used_ids[ (int) $term->term_id ] ) ) {
			return;
		}

		$item = dxvn_footer_tuyen_term_to_item( $term );
		if ( null === $item ) {
			return;
		}

		$items[] = $item;
		$used_ids[ (int) $term->term_id ] = true;
	};

	// 1) Tuyến phổ biến do admin chọn (Hero trang chủ — MTTF).
	if ( class_exists( 'MTTF_Settings' ) && method_exists( 'MTTF_Settings', 'get_hero_popular_tuyen_terms' ) ) {
		foreach ( MTTF_Settings::get_hero_popular_tuyen_terms() as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			$push_term( $term );
			if ( count( $items ) >= $limit ) {
				return $items;
			}
		}
	}

	// 2) Các tuyến có nhiều card tuyen_xe đang kích hoạt nhất.
	$all_terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $all_terms ) ) {
		$all_terms = array();
	}

	$by_active_count = array();
	foreach ( $all_terms as $term ) {
		if ( ! $term instanceof WP_Term || isset( $used_ids[ (int) $term->term_id ] ) ) {
			continue;
		}

		$active_count = dxvn_count_active_routes_for_tuyen_term( $term );
		if ( $active_count > 0 ) {
			$by_active_count[] = array(
				'term'  => $term,
				'count' => $active_count,
			);
		}
	}

	usort(
		$by_active_count,
		static function ( $a, $b ) {
			if ( $a['count'] !== $b['count'] ) {
				return $b['count'] <=> $a['count'];
			}

			return strcasecmp( $a['term']->name, $b['term']->name );
		}
	);

	foreach ( $by_active_count as $row ) {
		$push_term( $row['term'] );
		if ( count( $items ) >= $limit ) {
			return $items;
		}
	}

	// 3) Fallback: term tuyến mới nhất (có link landing hợp lệ).
	usort(
		$all_terms,
		static function ( $a, $b ) {
			return (int) $b->term_id <=> (int) $a->term_id;
		}
	);

	foreach ( $all_terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$push_term( $term );
		if ( count( $items ) >= $limit ) {
			break;
		}
	}

	return $items;
}

function dxvn_render_footer_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = dxvn_get_footer_settings();
	?>
	<div class="wrap">
		<h1>Cài đặt Footer DXVN</h1>
		<p>Định dạng cho danh sách liên kết: <code>Tên hiển thị|https://example.com</code> (mỗi dòng 1 mục).</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'dxvn_footer_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="dxvn_footer_partners_title">Tiêu đề đối tác</label></th>
					<td><input id="dxvn_footer_partners_title" type="text" class="regular-text" name="dxvn_footer_settings[partners_title]" value="<?php echo esc_attr( $settings['partners_title'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row">Danh sách đối tác</th>
					<td>
						<div id="dxvn-footer-partners-list">
							<?php
							$partners = isset( $settings['partners'] ) && is_array( $settings['partners'] ) ? $settings['partners'] : array();
							if ( empty( $partners ) ) {
								$partners = array(
									array(
										'image_id' => 0,
										'link'     => '',
										'name'     => '',
									),
								);
							}
							foreach ( $partners as $index => $partner ) :
								$image_id  = absint( $partner['image_id'] ?? 0 );
								$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
								$link      = $partner['link'] ?? '';
								$name      = $partner['name'] ?? '';
								?>
								<div class="dxvn-partner-row">
									<input type="hidden" class="dxvn-partner-image-id" name="<?php echo esc_attr( 'dxvn_footer_settings[partners][' . $index . '][image_id]' ); ?>" value="<?php echo esc_attr( (string) $image_id ); ?>" />
									<input type="hidden" class="dxvn-partner-image-url" name="<?php echo esc_attr( 'dxvn_footer_settings[partners][' . $index . '][image_url]' ); ?>" value="<?php echo esc_attr( $image_id ? (string) wp_get_attachment_url( $image_id ) : '' ); ?>" />
									<div class="dxvn-partner-preview-wrap">
										<img class="dxvn-partner-preview" src="<?php echo esc_url( $image_url ); ?>" alt="" <?php echo $image_url ? '' : 'style="display:none;"'; ?> />
									</div>
									<div class="dxvn-partner-fields">
										<p>
											<button type="button" class="button dxvn-partner-select-image">Chọn ảnh</button>
											<button type="button" class="button-link-delete dxvn-partner-clear-image">Xóa ảnh</button>
										</p>
										<p>
											<label>Tên đối tác (tùy chọn):</label><br />
											<input type="text" class="regular-text" name="<?php echo esc_attr( 'dxvn_footer_settings[partners][' . $index . '][name]' ); ?>" value="<?php echo esc_attr( $name ); ?>" />
										</p>
										<p>
											<label>Link đối tác (tùy chọn):</label><br />
											<input type="text" class="regular-text" name="<?php echo esc_attr( 'dxvn_footer_settings[partners][' . $index . '][link]' ); ?>" value="<?php echo esc_attr( $link ); ?>" placeholder="https://example.com hoặc #" />
										</p>
										<button type="button" class="button-link-delete dxvn-partner-remove-row">Xóa đối tác</button>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<p><button type="button" class="button" id="dxvn-add-partner-row">+ Thêm đối tác</button></p>
						<p class="description">Chọn ảnh từ thư viện Media, có thể gắn link cho từng đối tác. Hỗ trợ link đầy đủ hoặc <code>#</code>. Nếu để trống sẽ chỉ hiển thị logo.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_brand_description">Mô tả thương hiệu</label></th>
					<td><input id="dxvn_footer_brand_description" type="text" class="regular-text" name="dxvn_footer_settings[brand_description]" value="<?php echo esc_attr( $settings['brand_description'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_brand_contact_items">Thông tin liên hệ (dạng li)</label></th>
					<td>
						<textarea id="dxvn_footer_brand_contact_items" class="large-text code" rows="6" name="dxvn_footer_settings[brand_contact_items]"><?php echo esc_textarea( $settings['brand_contact_items'] ); ?></textarea>
						<p class="description">Mỗi dòng: <code>Nhãn|Giá trị</code> hoặc <code>Nhãn|Giá trị|Link</code> (ví dụ tel:, mailto:).</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_office_title">Tiêu đề cột văn phòng</label></th>
					<td><input id="dxvn_footer_office_title" type="text" class="regular-text" name="dxvn_footer_settings[office_title]" value="<?php echo esc_attr( $settings['office_title'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_office_map_label">Nhãn link maps</label></th>
					<td><input id="dxvn_footer_office_map_label" type="text" class="regular-text" name="dxvn_footer_settings[office_map_label]" value="<?php echo esc_attr( $settings['office_map_label'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_office_items">Danh sách văn phòng</label></th>
					<td><textarea id="dxvn_footer_office_items" class="large-text code" rows="7" name="dxvn_footer_settings[office_items]"><?php echo esc_textarea( $settings['office_items'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_services_title">Tiêu đề cột dịch vụ</label></th>
					<td><input id="dxvn_footer_services_title" type="text" class="regular-text" name="dxvn_footer_settings[services_title]" value="<?php echo esc_attr( $settings['services_title'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row">Danh sách dịch vụ</th>
					<td>
						<p class="description" style="max-width:640px;">
							Cột <strong>Dịch vụ phổ biến</strong> lấy tự động từ taxonomy <code>mttf_tuyen</code>
							(dạng «Đặt vé xe {Tên tuyến}» → <code>/tuyen/{slug}/</code>, tối đa 8 tuyến).
							Thứ tự ưu tiên: tuyến chọn ở <strong>Tuyến xe → Cài đặt → Hero trang chủ → Tuyến phổ biến</strong>,
							sau đó các tuyến có nhiều card <code>tuyen_xe</code> đang kích hoạt nhất.
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_about_title">Tiêu đề cột giới thiệu</label></th>
					<td><input id="dxvn_footer_about_title" type="text" class="regular-text" name="dxvn_footer_settings[about_title]" value="<?php echo esc_attr( $settings['about_title'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_about_items">Danh sách giới thiệu</label></th>
					<td><textarea id="dxvn_footer_about_items" class="large-text code" rows="5" name="dxvn_footer_settings[about_items]"><?php echo esc_textarea( $settings['about_items'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_support_title">Tiêu đề hỗ trợ</label></th>
					<td><input id="dxvn_footer_support_title" type="text" class="regular-text" name="dxvn_footer_settings[support_title]" value="<?php echo esc_attr( $settings['support_title'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_support_items">Danh sách hỗ trợ</label></th>
					<td><textarea id="dxvn_footer_support_items" class="large-text code" rows="7" name="dxvn_footer_settings[support_items]"><?php echo esc_textarea( $settings['support_items'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_company_name">Tên công ty</label></th>
					<td><input id="dxvn_footer_company_name" type="text" class="regular-text" name="dxvn_footer_settings[company_name]" value="<?php echo esc_attr( $settings['company_name'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_company_meta">MST và giấy phép</label></th>
					<td><input id="dxvn_footer_company_meta" type="text" class="regular-text" name="dxvn_footer_settings[company_meta]" value="<?php echo esc_attr( $settings['company_meta'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="dxvn_footer_copyright_text">Copyright</label></th>
					<td><input id="dxvn_footer_copyright_text" type="text" class="regular-text" name="dxvn_footer_settings[copyright_text]" value="<?php echo esc_attr( $settings['copyright_text'] ); ?>" /></td>
				</tr>
			</table>
			<?php submit_button( 'Lưu cài đặt Footer' ); ?>
		</form>
	</div>
	<?php
}

add_action( 'admin_enqueue_scripts', 'dxvn_enqueue_footer_code_editor' );
function dxvn_enqueue_footer_code_editor( $hook ) {
	if ( 'appearance_page_dxvn-footer-settings' !== $hook ) {
		return;
	}

	wp_enqueue_media();

	$settings = wp_enqueue_code_editor(
		array(
			'type'       => 'text/plain',
			'codemirror' => array(
				'lineNumbers' => true,
				'lineWrapping' => true,
				'mode'        => 'null',
			),
		)
	);

	if ( false === $settings ) {
		return;
	}

	wp_enqueue_script( 'wp-theme-plugin-editor' );
	wp_enqueue_style( 'wp-codemirror' );

	$textarea_ids = array(
		'dxvn_footer_brand_contact_items',
		'dxvn_footer_office_items',
		'dxvn_footer_about_items',
		'dxvn_footer_support_items',
	);

	$config_json = wp_json_encode( $settings );
	$ids_json    = wp_json_encode( $textarea_ids );
	$inline_js   = "jQuery(function($){var cfg={$config_json};var ids={$ids_json};ids.forEach(function(id){var el=document.getElementById(id);if(el){wp.codeEditor.initialize(el,cfg);}});});";
	wp_add_inline_script( 'wp-theme-plugin-editor', $inline_js );

	$inline_css = '.CodeMirror{height:auto;min-height:180px;border:1px solid #ccd0d4;border-radius:4px}.CodeMirror-scroll{min-height:180px}';
	$inline_css .= '#dxvn-footer-partners-list{display:grid;gap:12px;margin-bottom:8px}.dxvn-partner-row{display:grid;grid-template-columns:120px 1fr;gap:12px;padding:12px;border:1px solid #dcdcde;border-radius:6px;background:#fff}.dxvn-partner-preview-wrap{width:120px;height:80px;border:1px dashed #c3c4c7;border-radius:4px;display:flex;align-items:center;justify-content:center;background:#f6f7f7;overflow:hidden}.dxvn-partner-preview{max-width:100%;max-height:100%;object-fit:contain}.dxvn-partner-fields p{margin:0 0 8px}@media (max-width:782px){.dxvn-partner-row{grid-template-columns:1fr}}';
	wp_add_inline_style( 'wp-codemirror', $inline_css );

	$partners_script = <<<'JS'
jQuery(function($){
	var $list = $("#dxvn-footer-partners-list");
	var rowIndex = $list.find(".dxvn-partner-row").length;

	function createRow(index){
		return $(
			'<div class="dxvn-partner-row">' +
				'<input type="hidden" class="dxvn-partner-image-id" name="dxvn_footer_settings[partners][' + index + '][image_id]" value="" />' +
				'<input type="hidden" class="dxvn-partner-image-url" name="dxvn_footer_settings[partners][' + index + '][image_url]" value="" />' +
				'<div class="dxvn-partner-preview-wrap"><img class="dxvn-partner-preview" src="" alt="" style="display:none;" /></div>' +
				'<div class="dxvn-partner-fields">' +
					'<p><button type="button" class="button dxvn-partner-select-image">Chọn ảnh</button> <button type="button" class="button-link-delete dxvn-partner-clear-image">Xóa ảnh</button></p>' +
					'<p><label>Tên đối tác (tùy chọn):</label><br /><input type="text" class="regular-text" name="dxvn_footer_settings[partners][' + index + '][name]" value="" /></p>' +
					'<p><label>Link đối tác (tùy chọn):</label><br /><input type="text" class="regular-text" name="dxvn_footer_settings[partners][' + index + '][link]" value="" placeholder="https://example.com hoặc #" /></p>' +
					'<button type="button" class="button-link-delete dxvn-partner-remove-row">Xóa đối tác</button>' +
				'</div>' +
			'</div>'
		);
	}

	$("#dxvn-add-partner-row").on("click", function(){
		$list.append(createRow(rowIndex));
		rowIndex += 1;
	});

	$list.on("click", ".dxvn-partner-remove-row", function(){
		$(this).closest(".dxvn-partner-row").remove();
	});

	$list.on("click", ".dxvn-partner-clear-image", function(){
		var $row = $(this).closest(".dxvn-partner-row");
		$row.find(".dxvn-partner-image-id").val("");
		$row.find(".dxvn-partner-image-url").val("");
		$row.find(".dxvn-partner-preview").attr("src","").hide();
	});

	$list.on("click", ".dxvn-partner-select-image", function(){
		var $row = $(this).closest(".dxvn-partner-row");
		var frame = wp.media({
			title: "Chọn logo đối tác",
			button: { text: "Dùng ảnh này" },
			multiple: false
		});

		frame.on("select", function(){
			var attachment = frame.state().get("selection").first().toJSON();
			$row.find(".dxvn-partner-image-id").val(attachment.id || "");
			$row.find(".dxvn-partner-image-url").val(attachment.url || "");
			$row.find(".dxvn-partner-preview").attr("src", attachment.url || "").show();
		});

		frame.open();
	});
});
JS;
	wp_add_inline_script( 'wp-theme-plugin-editor', $partners_script );
}

add_filter( 'generate_show_title', 'dxvn_hide_homepage_title' );
function dxvn_hide_homepage_title( $show ) {
	if ( is_front_page() && is_page() ) {
		return false;
	}

	return $show;
}

/**
 * Telegram bot token từ MTTF_Settings (cùng filter với Minh Thang Transport Flow).
 *
 * @return string
 */
function dxvn_get_mttf_telegram_bot_token() {
	if ( ! class_exists( 'MTTF_Settings' ) ) {
		return '';
	}

	$default = (string) MTTF_Settings::get( 'telegram_bot_token', '' );

	return trim( (string) apply_filters( 'mttf_telegram_bot_token', $default ) );
}

/**
 * Chat ID Telegram: ưu tiên nhóm miền (bac/trung/nam), sau đó chat mặc định trong cài đặt plugin.
 *
 * @param string $partner_region Giá trị form hợp tác: north|central|south|interprovincial|other; chuỗi rỗng chỉ dùng chat mặc định.
 * @return string
 */
function dxvn_resolve_mttf_telegram_chat_id( $partner_region = '' ) {
	if ( ! class_exists( 'MTTF_Settings' ) ) {
		return '';
	}

	$key = sanitize_key( (string) $partner_region );

	$map = array(
		'north'   => 'bac',
		'central' => 'trung',
		'south'   => 'nam',
	);

	$mttf_slug = isset( $map[ $key ] ) ? $map[ $key ] : '';
	$chat_id   = '';

	if ( '' !== $mttf_slug ) {
		$region_chat_id = MTTF_Settings::get( 'telegram_chat_id_' . $mttf_slug, '' );
		$chat_id       = trim( (string) apply_filters( 'mttf_telegram_chat_id_' . $mttf_slug, $region_chat_id ) );
	}

	if ( '' === $chat_id ) {
		$default = MTTF_Settings::get( 'telegram_default_chat_id', '' );
		$chat_id = trim( (string) apply_filters( 'mttf_telegram_default_chat_id', $default ) );
	}

	return $chat_id;
}

add_action( 'wp_ajax_dxvn_contact_submit', 'dxvn_handle_contact_submit' );
add_action( 'wp_ajax_nopriv_dxvn_contact_submit', 'dxvn_handle_contact_submit' );
function dxvn_handle_contact_submit() {
	check_ajax_referer( 'dxvn_contact_submit', 'nonce' );

	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
	$need  = sanitize_text_field( wp_unslash( $_POST['need'] ?? '' ) );
	$note  = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );

	if ( '' === $name || '' === $phone ) {
		wp_send_json_error(
			array(
				'message' => 'Vui lòng nhập đầy đủ Họ tên và Số điện thoại.',
			),
			400
		);
	}

	$need_labels = array(
		'tu-van-dat-xe' => 'Tư vấn đặt xe',
		'partnership'   => 'Hợp tác nhà xe',
		'quality'       => 'Phản ánh chất lượng',
	);
	$need_label = $need_labels[ $need ] ?? $need;
	$current_url = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ?? home_url( '/lien-he' ) ) );

	$to = get_option( 'admin_email' );
	if ( class_exists( 'MTTF_Settings' ) ) {
		$to = MTTF_Settings::get( 'lead_email', $to );
	}

	$subject = '[DXVN Liên hệ] ' . $name . ' - ' . $phone;
	$message = "Bạn nhận được yêu cầu liên hệ mới:\n";
	$message .= 'Họ tên: ' . $name . "\n";
	$message .= 'SĐT: ' . $phone . "\n";
	$message .= 'Nhu cầu: ' . $need_label . "\n";
	$message .= 'Ghi chú: ' . ( '' !== $note ? $note : 'Không có' ) . "\n";
	$message .= 'Trang gửi: ' . $current_url . "\n";
	$message .= 'Thời gian: ' . wp_date( 'Y-m-d H:i:s' ) . "\n";

	wp_mail( $to, $subject, $message );

	$token   = dxvn_get_mttf_telegram_bot_token();
	$chat_id = dxvn_resolve_mttf_telegram_chat_id( '' );

	if ( '' !== $token && '' !== $chat_id ) {
		$text  = "DXVN - Yêu cầu liên hệ mới\n";
		$text .= 'Họ tên: ' . $name . "\n";
		$text .= 'SĐT: ' . $phone . "\n";
		$text .= 'Nhu cầu: ' . $need_label . "\n";
		$text .= 'Ghi chú: ' . ( '' !== $note ? $note : 'Không có' ) . "\n";
		$text .= 'Trang gửi: ' . $current_url;

		wp_remote_post(
			'https://api.telegram.org/bot' . $token . '/sendMessage',
			array(
				'timeout' => 10,
				'body'    => array(
					'chat_id' => $chat_id,
					'text'    => $text,
				),
			)
		);
	}

	wp_send_json_success(
		array(
			'message' => 'Đã gửi yêu cầu thành công. Chuyên viên sẽ liên hệ sớm.',
		)
	);
}

add_action( 'wp_ajax_dxvn_partnership_submit', 'dxvn_handle_partnership_submit' );
add_action( 'wp_ajax_nopriv_dxvn_partnership_submit', 'dxvn_handle_partnership_submit' );
function dxvn_handle_partnership_submit() {
	check_ajax_referer( 'dxvn_partnership_submit', 'nonce' );

	$fleet_name    = sanitize_text_field( wp_unslash( $_POST['fleet_name'] ?? '' ) );
	$contact_name  = sanitize_text_field( wp_unslash( $_POST['contact_name'] ?? '' ) );
	$phone         = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
	$email         = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$route_main    = sanitize_text_field( wp_unslash( $_POST['route_main'] ?? '' ) );
	$region        = sanitize_text_field( wp_unslash( $_POST['region'] ?? '' ) );
	$note          = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
	$confirm_saved = sanitize_text_field( wp_unslash( $_POST['confirm_accurate'] ?? '' ) );

	if ( '' === $fleet_name || '' === $contact_name || '' === $phone || '' === $route_main || '' === $region ) {
		wp_send_json_error(
			array(
				'message' => 'Vui lòng điền đầy đủ các ô bắt buộc (nhà xe, người liên hệ, SĐT, tuyến, khu vực).',
			),
			400
		);
	}

	if ( '1' !== $confirm_saved ) {
		wp_send_json_error(
			array(
				'message' => 'Vui lòng đánh dấu xác nhận thông tin đúng sự thật và đồng ý quy trình xét duyệt.',
			),
			400
		);
	}

	$region_labels = array(
		'north'         => 'Miền Bắc',
		'central'       => 'Miền Trung',
		'south'         => 'Miền Nam',
		'interprovincial' => 'Liên miền / nối miền',
		'other'         => 'Khác',
	);
	$region_label = $region_labels[ $region ] ?? $region;
	$current_url  = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ?? home_url() ) );

	$to = get_option( 'admin_email' );
	if ( class_exists( 'MTTF_Settings' ) ) {
		$to = MTTF_Settings::get( 'lead_email', $to );
	}

	$subject = '[DXVN Hợp tác] ' . $fleet_name . ' — ' . $phone;
	$message = "Yêu cầu hợp tác nhà xe mới:\n";
	$message .= 'Tên nhà xe: ' . $fleet_name . "\n";
	$message .= 'Người liên hệ: ' . $contact_name . "\n";
	$message .= 'SĐT: ' . $phone . "\n";
	$message .= 'Email: ' . ( is_email( $email ) ? $email : '(không có)' ) . "\n";
	$message .= 'Tuyến chính: ' . $route_main . "\n";
	$message .= 'Khu vực: ' . $region_label . "\n";
	$message .= 'Ghi chú: ' . ( '' !== $note ? $note : 'Không có' ) . "\n";
	$message .= 'Trang gửi: ' . $current_url . "\n";
	$message .= 'Thời gian: ' . wp_date( 'Y-m-d H:i:s' ) . "\n";

	wp_mail( $to, $subject, $message );

	$token   = dxvn_get_mttf_telegram_bot_token();
	$chat_id = dxvn_resolve_mttf_telegram_chat_id( $region );

	if ( '' !== $token && '' !== $chat_id ) {
		$text  = "DXVN - Yêu cầu hợp tác nhà xe\n";
		$text .= 'Nhà xe: ' . $fleet_name . "\n";
		$text .= 'LH: ' . $contact_name . ' — ' . $phone . "\n";
		$text .= 'Email: ' . ( is_email( $email ) ? $email : '(không có)' ) . "\n";
		$text .= 'Tuyến: ' . $route_main . "\n";
		$text .= 'KV: ' . $region_label . "\n";
		$text .= 'GC: ' . ( '' !== $note ? $note : '-' ) . "\n";
		$text .= 'URL: ' . $current_url . "\n";
		$text .= 'Lúc: ' . wp_date( 'd/m/Y H:i' );

		wp_remote_post(
			'https://api.telegram.org/bot' . $token . '/sendMessage',
			array(
				'timeout' => 10,
				'body'    => array(
					'chat_id' => $chat_id,
					'text'    => $text,
				),
			)
		);
	}

	wp_send_json_success(
		array(
			'message' => 'Đã gửi thành công. Bộ phận đối tác sẽ liên hệ khi hồ sơ được xem xét.',
		)
	);
}
