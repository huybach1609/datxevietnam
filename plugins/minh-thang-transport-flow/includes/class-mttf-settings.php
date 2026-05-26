<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MTTF_Settings {
	const OPTION_KEY = 'mttf_settings';

	/** Tab “Lead”: Settings API section page slug */
	const SETTINGS_PAGE_LEAD = 'mttf-settings-lead';

	/** Tab đo lường */
	const SETTINGS_PAGE_MEASUREMENT = 'mttf-settings-measurement';

	const SETTINGS_PAGE_HERO = 'mttf-settings-hero';

	const SETTINGS_PAGE_TELEGRAM = 'mttf-settings-telegram';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_search_stats_actions' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'submenu_file', array( __CLASS__, 'ensure_settings_default_tab_in_menu' ), 20, 2 );
	}

	/**
	 * Sidebar “Cài đặt” mở luôn URL có tab=lead (mặc định trùng logic render khi thiếu ?tab).
	 *
	 * @param string $submenu_file Submenu slug / query.
	 * @param string $parent_file  Parent menu file.
	 * @return string
	 */
	public static function ensure_settings_default_tab_in_menu( $submenu_file, $parent_file ) {
		if ( 'edit.php?post_type=' . MTTF_CPT::get_article_post_type() !== $parent_file ) {
			return $submenu_file;
		}
		$f = (string) $submenu_file;
		// Sidebar thường dùng slug `mttf-settings`; một số bản có full query chứa page=mttf-settings.
		$is_mttf = ( 'mttf-settings' === $f ) || ( false !== strpos( $f, 'page=mttf-settings' ) );
		if ( ! $is_mttf ) {
			return $submenu_file;
		}
		if ( false !== strpos( $f, 'tab=' ) ) {
			return $submenu_file;
		}

		return add_query_arg(
			array(
				'page' => 'mttf-settings',
				'tab'  => 'lead',
			),
			'edit.php?post_type=' . MTTF_CPT::get_article_post_type()
		);
	}

	public static function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=' . MTTF_CPT::get_article_post_type(),
			'Cài đặt MTTF',
			'Cài đặt',
			'manage_options',
			'mttf-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'mttf_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			'mttf_contact_section',
			'Cấu hình nhận lead',
			'__return_false',
			self::SETTINGS_PAGE_LEAD
		);

		add_settings_field(
			'lead_email',
			'Email nhận lead',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_LEAD,
			'mttf_contact_section',
			array(
				'key'         => 'lead_email',
				'placeholder' => 'sale@example.com',
			)
		);

		add_settings_field(
			'fallback_hotline',
			'Hotline fallback',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_LEAD,
			'mttf_contact_section',
			array(
				'key'         => 'fallback_hotline',
				'placeholder' => '0900000000',
			)
		);

		add_settings_field(
			'call_icon_url',
			'Icon nút Gọi',
			array( __CLASS__, 'render_media_input' ),
			self::SETTINGS_PAGE_LEAD,
			'mttf_contact_section',
			array(
				'key'   => 'call_icon_url',
				'label' => 'Chọn icon từ Thư viện Media',
			)
		);

		add_settings_field(
			'zalo_icon_url',
			'Icon nút Zalo',
			array( __CLASS__, 'render_media_input' ),
			self::SETTINGS_PAGE_LEAD,
			'mttf_contact_section',
			array(
				'key'   => 'zalo_icon_url',
				'label' => 'Chọn icon từ Thư viện Media',
			)
		);

		add_settings_field(
			'enable_spam_protection',
			'Chống spam lead (khóa gửi lại)',
			array( __CLASS__, 'render_checkbox_with_hidden' ),
			self::SETTINGS_PAGE_LEAD,
			'mttf_contact_section',
			array(
				'key'         => 'enable_spam_protection',
				'label'       => 'Bật chống spam và đếm ngược gửi lại trên popup',
				'default_on' => true,
			)
		);

		add_settings_field(
			'lead_lock_seconds',
			'Thời gian khóa gửi lại (giây)',
			array( __CLASS__, 'render_number_input' ),
			self::SETTINGS_PAGE_LEAD,
			'mttf_contact_section',
			array(
				'key'         => 'lead_lock_seconds',
				'placeholder' => '45',
				'min'         => 10,
				'max'         => 600,
				'step'        => 1,
			)
		);

		add_settings_field(
			'enable_activity_pings',
			'Thông báo hoạt động (FOMO) trên hub',
			array( __CLASS__, 'render_checkbox_with_hidden' ),
			self::SETTINGS_PAGE_LEAD,
			'mttf_contact_section',
			array(
				'key'         => 'enable_activity_pings',
				'label'       => 'Hiện dòng “khách vừa gửi liên hệ…” (SĐT được che) trên trang có shortcode hub',
				'default_on' => true,
			)
		);

		add_settings_field(
			'activity_poll_interval',
			'Chu kỳ cập nhật thông báo (giây)',
			array( __CLASS__, 'render_number_input' ),
			self::SETTINGS_PAGE_LEAD,
			'mttf_contact_section',
			array(
				'key'         => 'activity_poll_interval',
				'placeholder' => '28',
				'min'         => 15,
				'max'         => 120,
				'step'        => 1,
			)
		);

		add_settings_field(
			'activity_ping_max_hours',
			'Tuổi tối đa của ping (giờ)',
			array( __CLASS__, 'render_number_input' ),
			self::SETTINGS_PAGE_LEAD,
			'mttf_contact_section',
			array(
				'key'         => 'activity_ping_max_hours',
				'placeholder' => '72',
				'description' => 'Ping quá cũ sẽ không hiển thị (khuyến nghị 24–72 giờ).',
				'min'         => 6,
				'max'         => 168,
				'step'        => 1,
			)
		);

		add_settings_section(
			'mttf_measurement_section',
			'Đo lường chuyển đổi lead (GTM / GA4)',
			array( __CLASS__, 'measurement_section_intro' ),
			self::SETTINGS_PAGE_MEASUREMENT
		);

		add_settings_field(
			'measurement_data_layer_enabled',
			'Gửi sự kiện vào Google Tag Manager',
			array( __CLASS__, 'render_checkbox_with_hidden' ),
			self::SETTINGS_PAGE_MEASUREMENT,
			'mttf_measurement_section',
			array(
				'key'         => 'measurement_data_layer_enabled',
				'label'       => 'Khi khách gửi lead thành công, đẩy dữ liệu vào <code>window.dataLayer</code> để GTM / GA4 bắt.',
				'default_on' => true,
			)
		);

		add_settings_field(
			'measurement_event_name',
			'Tên sự kiện Custom Event',
			array( __CLASS__, 'render_measurement_event_input' ),
			self::SETTINGS_PAGE_MEASUREMENT,
			'mttf_measurement_section'
		);

		add_settings_field(
			'measurement_duplicate_ga4_generate_lead',
			'Sự kiện khuyến nghị GA4',
			array( __CLASS__, 'render_checkbox_with_hidden' ),
			self::SETTINGS_PAGE_MEASUREMENT,
			'mttf_measurement_section',
			array(
				'key'          => 'measurement_duplicate_ga4_generate_lead',
				'label'        => 'Ngoài sự kiện tùy chỉnh ở trên, đồng thời đẩy thêm sự kiện <code>generate_lead</code> (tên cố định theo GA4).',

				'default_on'   => false,
			)
		);

		add_settings_field(
			'measurement_gtm_container_id',
			'Google Tag Manager — Container ID',
			array( __CLASS__, 'render_measurement_gtm_field' ),
			self::SETTINGS_PAGE_MEASUREMENT,
			'mttf_measurement_section'
		);

		add_settings_field(
			'measurement_ga4_measurement_id',
			'Google Analytics 4 — Measurement ID',
			array( __CLASS__, 'render_measurement_ga4_field' ),
			self::SETTINGS_PAGE_MEASUREMENT,
			'mttf_measurement_section'
		);

		add_settings_field(
			'measurement_ga4_gtag_with_gtm',
			'Gtag trực tiếp song song GTM',
			array( __CLASS__, 'render_checkbox_with_hidden' ),
			self::SETTINGS_PAGE_MEASUREMENT,
			'mttf_measurement_section',
			array(
				'key'         => 'measurement_ga4_gtag_with_gtm',
				'label'       => '<strong>Chỉ bật khi bạn biết mình đang làm gì:</strong> chèn thêm mã <code>gtag.js</code> trực tiếp cho GA4 <em>trong khi</em> đã điền GTM. Nếu GA4 chỉ được cấu hình <strong>bên trong GTM</strong>, hãy <strong>để trống GA4 hoặc tắt</strong> tùy chọn này để tránh đếm trùng (double counting).',

				'default_on' => false,
			)
		);

		add_settings_field(
			'measurement_guide',
			'Hướng dẫn từng bước GTM &amp; GA4',
			array( __CLASS__, 'render_measurement_guide' ),
			self::SETTINGS_PAGE_MEASUREMENT,
			'mttf_measurement_section'
		);

		add_settings_section(
			'mttf_hero_section',
			'Hero trang chủ',
			'__return_false',
			self::SETTINGS_PAGE_HERO
		);

		add_settings_field(
			'hero_title_1',
			'Dòng tiêu đề 1',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_HERO,
			'mttf_hero_section',
			array(
				'key'         => 'hero_title_1',
				'placeholder' => 'Nền tảng Đặt Vé Xe toàn Việt Nam',
			)
		);

		add_settings_field(
			'hero_title_2',
			'Dòng tiêu đề 2',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_HERO,
			'mttf_hero_section',
			array(
				'key'         => 'hero_title_2',
				'placeholder' => 'Nhanh chóng. Minh bạch. Cam kết có chỗ.',
			)
		);

		add_settings_field(
			'hero_title_3',
			'Dòng tiêu đề 3',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_HERO,
			'mttf_hero_section',
			array(
				'key'         => 'hero_title_3',
				'placeholder' => 'Nhập tỉnh hoặc thành phố để lọc nhanh',
			)
		);

		add_settings_field(
			'hero_background_url',
			'Ảnh nền Hero',
			array( __CLASS__, 'render_media_input' ),
			self::SETTINGS_PAGE_HERO,
			'mttf_hero_section',
			array(
				'key'   => 'hero_background_url',
				'label' => 'Chọn ảnh nền từ Thư viện Media',
			)
		);

		add_settings_field(
			'route_section_title_bac',
			'Tiêu đề section miền Bắc',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_HERO,
			'mttf_hero_section',
			array(
				'key'         => 'route_section_title_bac',
				'placeholder' => 'Gợi ý tuyến miền Bắc cho bạn',
			)
		);

		add_settings_field(
			'route_section_title_trung',
			'Tiêu đề section miền Trung',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_HERO,
			'mttf_hero_section',
			array(
				'key'         => 'route_section_title_trung',
				'placeholder' => 'Gợi ý tuyến miền Trung cho bạn',
			)
		);

		add_settings_field(
			'route_section_title_nam',
			'Tiêu đề section miền Nam',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_HERO,
			'mttf_hero_section',
			array(
				'key'         => 'route_section_title_nam',
				'placeholder' => 'Gợi ý tuyến miền Nam cho bạn',
			)
		);

		add_settings_field(
			'route_section_title_default',
			'Tiêu đề section mặc định',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_HERO,
			'mttf_hero_section',
			array(
				'key'         => 'route_section_title_default',
				'placeholder' => 'Gợi ý tuyến phù hợp cho bạn',
			)
		);

		add_settings_section(
			'mttf_telegram_section',
			'Telegram',
			'__return_false',
			self::SETTINGS_PAGE_TELEGRAM
		);

		add_settings_field(
			'telegram_bot_token',
			'Telegram Bot Token',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_TELEGRAM,
			'mttf_telegram_section',
			array(
				'key'         => 'telegram_bot_token',
				'placeholder' => '123456:ABCDEF...',
			)
		);

		add_settings_field(
			'telegram_default_chat_id',
			'Telegram Chat ID mặc định',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_TELEGRAM,
			'mttf_telegram_section',
			array(
				'key'         => 'telegram_default_chat_id',
				'placeholder' => '-1001234567890',
			)
		);

		add_settings_field(
			'telegram_chat_id_bac',
			'Chat ID khu vực Bắc',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_TELEGRAM,
			'mttf_telegram_section',
			array(
				'key'         => 'telegram_chat_id_bac',
				'placeholder' => '-1001234567890',
			)
		);

		add_settings_field(
			'telegram_chat_id_trung',
			'Chat ID khu vực Trung',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_TELEGRAM,
			'mttf_telegram_section',
			array(
				'key'         => 'telegram_chat_id_trung',
				'placeholder' => '-1001234567890',
			)
		);

		add_settings_field(
			'telegram_chat_id_nam',
			'Chat ID khu vực Nam',
			array( __CLASS__, 'render_input' ),
			self::SETTINGS_PAGE_TELEGRAM,
			'mttf_telegram_section',
			array(
				'key'         => 'telegram_chat_id_nam',
				'placeholder' => '-1001234567890',
			)
		);
	}

	/**
	 * Giá trị mặc định khi option chưa có (nhiều tab form chỉ POST một phần key).
	 *
	 * @return array<string, mixed>
	 */
	private static function default_option_values() {
		return array(
			'lead_email'                               => '',
			'fallback_hotline'                         => '',
			'call_icon_url'                            => '',
			'zalo_icon_url'                            => '',
			'hero_title_1'                             => '',
			'hero_title_2'                             => '',
			'hero_title_3'                             => '',
			'hero_background_url'                       => '',
			'route_section_title_bac'                  => '',
			'route_section_title_trung'                => '',
			'route_section_title_nam'                  => '',
			'route_section_title_default'              => '',
			'enable_spam_protection'                   => 1,
			'lead_lock_seconds'                        => 45,
			'enable_activity_pings'                     => 1,
			'activity_poll_interval'                   => 28,
			'activity_ping_max_hours'                   => 72,
			'measurement_data_layer_enabled'            => 1,
			'measurement_event_name'                   => 'mttf_lead_submit',
			'measurement_duplicate_ga4_generate_lead' => 0,
			'measurement_gtm_container_id'             => '',
			'measurement_ga4_measurement_id'           => '',
			'measurement_ga4_gtag_with_gtm'           => 0,
			'telegram_bot_token'                       => '',
			'telegram_default_chat_id'                 => '',
			'telegram_chat_id_bac'                     => '',
			'telegram_chat_id_trung'                   => '',
			'telegram_chat_id_nam'                     => '',
		);
	}

	public static function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		$prev  = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $prev ) ) {
			$prev = array();
		}

		$out = array_merge( self::default_option_values(), $prev );

		if ( array_key_exists( 'lead_email', $input ) ) {
			$out['lead_email'] = sanitize_email( $input['lead_email'] );
		}
		if ( array_key_exists( 'fallback_hotline', $input ) ) {
			$out['fallback_hotline'] = sanitize_text_field( $input['fallback_hotline'] );
		}
		if ( array_key_exists( 'call_icon_url', $input ) ) {
			$out['call_icon_url'] = esc_url_raw( $input['call_icon_url'] );
		}
		if ( array_key_exists( 'zalo_icon_url', $input ) ) {
			$out['zalo_icon_url'] = esc_url_raw( $input['zalo_icon_url'] );
		}
		if ( array_key_exists( 'hero_title_1', $input ) ) {
			$out['hero_title_1'] = sanitize_text_field( $input['hero_title_1'] );
		}
		if ( array_key_exists( 'hero_title_2', $input ) ) {
			$out['hero_title_2'] = sanitize_text_field( $input['hero_title_2'] );
		}
		if ( array_key_exists( 'hero_title_3', $input ) ) {
			$out['hero_title_3'] = sanitize_text_field( $input['hero_title_3'] );
		}
		if ( array_key_exists( 'hero_background_url', $input ) ) {
			$out['hero_background_url'] = esc_url_raw( $input['hero_background_url'] );
		}
		if ( array_key_exists( 'route_section_title_bac', $input ) ) {
			$out['route_section_title_bac'] = sanitize_text_field( $input['route_section_title_bac'] );
		}
		if ( array_key_exists( 'route_section_title_trung', $input ) ) {
			$out['route_section_title_trung'] = sanitize_text_field( $input['route_section_title_trung'] );
		}
		if ( array_key_exists( 'route_section_title_nam', $input ) ) {
			$out['route_section_title_nam'] = sanitize_text_field( $input['route_section_title_nam'] );
		}
		if ( array_key_exists( 'route_section_title_default', $input ) ) {
			$out['route_section_title_default'] = sanitize_text_field( $input['route_section_title_default'] );
		}
		if ( array_key_exists( 'enable_spam_protection', $input ) ) {
			$out['enable_spam_protection'] = empty( $input['enable_spam_protection'] ) ? 0 : 1;
		}
		if ( array_key_exists( 'lead_lock_seconds', $input ) ) {
			$out['lead_lock_seconds'] = self::sanitize_lock_seconds( $input['lead_lock_seconds'] );
		}
		if ( array_key_exists( 'enable_activity_pings', $input ) ) {
			$out['enable_activity_pings'] = empty( $input['enable_activity_pings'] ) ? 0 : 1;
		}
		if ( array_key_exists( 'activity_poll_interval', $input ) ) {
			$out['activity_poll_interval'] = self::sanitize_activity_poll_interval( $input['activity_poll_interval'] );
		}
		if ( array_key_exists( 'activity_ping_max_hours', $input ) ) {
			$out['activity_ping_max_hours'] = self::sanitize_activity_max_hours( $input['activity_ping_max_hours'] );
		}
		if ( array_key_exists( 'measurement_data_layer_enabled', $input ) ) {
			$out['measurement_data_layer_enabled'] = empty( $input['measurement_data_layer_enabled'] ) ? 0 : 1;
		}
		if ( array_key_exists( 'measurement_event_name', $input ) ) {
			$out['measurement_event_name'] = self::sanitize_measurement_event_name( $input['measurement_event_name'] );
		}
		if ( array_key_exists( 'measurement_duplicate_ga4_generate_lead', $input ) ) {
			$out['measurement_duplicate_ga4_generate_lead'] = empty( $input['measurement_duplicate_ga4_generate_lead'] ) ? 0 : 1;
		}
		if ( array_key_exists( 'measurement_gtm_container_id', $input ) ) {
			$out['measurement_gtm_container_id'] = self::sanitize_gtm_container_id( $input['measurement_gtm_container_id'] );
		}
		if ( array_key_exists( 'measurement_ga4_measurement_id', $input ) ) {
			$out['measurement_ga4_measurement_id'] = self::sanitize_ga4_measurement_id( $input['measurement_ga4_measurement_id'] );
		}
		if ( array_key_exists( 'measurement_ga4_gtag_with_gtm', $input ) ) {
			$out['measurement_ga4_gtag_with_gtm'] = empty( $input['measurement_ga4_gtag_with_gtm'] ) ? 0 : 1;
		}
		if ( array_key_exists( 'telegram_bot_token', $input ) ) {
			$out['telegram_bot_token'] = sanitize_text_field( $input['telegram_bot_token'] );
		}
		if ( array_key_exists( 'telegram_default_chat_id', $input ) ) {
			$out['telegram_default_chat_id'] = sanitize_text_field( $input['telegram_default_chat_id'] );
		}
		if ( array_key_exists( 'telegram_chat_id_bac', $input ) ) {
			$out['telegram_chat_id_bac'] = sanitize_text_field( $input['telegram_chat_id_bac'] );
		}
		if ( array_key_exists( 'telegram_chat_id_trung', $input ) ) {
			$out['telegram_chat_id_trung'] = sanitize_text_field( $input['telegram_chat_id_trung'] );
		}
		if ( array_key_exists( 'telegram_chat_id_nam', $input ) ) {
			$out['telegram_chat_id_nam'] = sanitize_text_field( $input['telegram_chat_id_nam'] );
		}

		return $out;
	}

	public static function render_input( $args ) {
		$key         = $args['key'];
		$placeholder = $args['placeholder'] ?? '';
		$value       = self::get( $key );
		?>
		<input
			type="text"
			class="regular-text"
			name="<?php echo esc_attr( self::OPTION_KEY . '[' . $key . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
		/>
		<?php
	}

	public static function render_media_input( $args ) {
		$key   = $args['key'];
		$value = self::get( $key );
		$label = $args['label'] ?? '';
		?>
		<div class="mttf-media-picker" data-target="<?php echo esc_attr( $key ); ?>">
			<input
				type="text"
				class="regular-text mttf-media-url"
				name="<?php echo esc_attr( self::OPTION_KEY . '[' . $key . ']' ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				readonly
			/>
			<p>
				<button type="button" class="button mttf-select-media">Chọn ảnh</button>
				<button type="button" class="button mttf-clear-media">Xóa ảnh</button>
			</p>
			<?php if ( '' !== $label ) : ?>
				<p class="description"><?php echo esc_html( $label ); ?></p>
			<?php endif; ?>
			<div class="mttf-media-preview">
				<?php if ( '' !== $value ) : ?>
					<img src="<?php echo esc_url( $value ); ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #ddd;" />
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public static function render_checkbox( $args ) {
		$key   = $args['key'];
		$label = $args['label'] ?? '';
		$value = (int) self::get( $key, 1 );
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::OPTION_KEY . '[' . $key . ']' ); ?>"
				value="1"
				<?php checked( 1, $value ); ?>
			/>
			<?php echo esc_html( $label ); ?>
		</label>
		<?php
	}

	public static function render_number_input( $args ) {
		$key           = $args['key'];
		$placeholder = $args['placeholder'] ?? '';
		$min         = isset( $args['min'] ) ? (int) $args['min'] : 0;
		$max         = isset( $args['max'] ) ? (int) $args['max'] : 9999;
		$step        = isset( $args['step'] ) ? (int) $args['step'] : 1;
		$description = $args['description'] ?? '';
		$defaults    = array(
			'lead_lock_seconds'        => 45,
			'activity_poll_interval'   => 28,
			'activity_ping_max_hours'  => 72,
		);
		$default_val = isset( $defaults[ $key ] ) ? (int) $defaults[ $key ] : 45;
		$value       = (int) self::get( $key, $default_val );
		?>
		<input
			type="number"
			class="small-text"
			name="<?php echo esc_attr( self::OPTION_KEY . '[' . $key . ']' ); ?>"
			value="<?php echo esc_attr( (string) $value ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			min="<?php echo esc_attr( (string) $min ); ?>"
			max="<?php echo esc_attr( (string) $max ); ?>"
			step="<?php echo esc_attr( (string) $step ); ?>"
		/>
		<?php if ( '' !== $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php elseif ( 'lead_lock_seconds' === $key ) : ?>
			<p class="description">Áp dụng cho thời gian đếm ngược khóa gửi lại lead.</p>
		<?php elseif ( 'activity_poll_interval' === $key ) : ?>
			<p class="description">Trang hub sẽ hỏi server định kỳ để lấy thông báo mới nhất.</p>
		<?php endif; ?>
		<?php
	}

	private static function sanitize_lock_seconds( $value ) {
		$seconds = absint( $value );
		if ( $seconds < 10 ) {
			$seconds = 10;
		}
		if ( $seconds > 600 ) {
			$seconds = 600;
		}

		return $seconds;
	}

	private static function sanitize_activity_poll_interval( $value ) {
		$s = absint( $value );
		if ( $s < 15 ) {
			$s = 15;
		}
		if ( $s > 120 ) {
			$s = 120;
		}
		return $s;
	}

	private static function sanitize_activity_max_hours( $value ) {
		$h = absint( $value );
		if ( $h < 6 ) {
			$h = 6;
		}
		if ( $h > 168 ) {
			$h = 168;
		}
		return $h;
	}

	/**
	 * GTM chỉ nhận tên event an toàn (chữ thường, số, gạch ngang/ngăn — WordPress sanitize_key).
	 *
	 * @param string $name Raw POST.
	 * @return string
	 */
	/**
	 * @param mixed $raw User input.
	 * @return string Empty or GTM-xxxx…
	 */
	public static function sanitize_gtm_container_id( $raw ) {
		$t = sanitize_text_field( is_string( $raw ) ? $raw : '' );
		$t = strtoupper( preg_replace( '/\s+/', '', $t ) );

		if ( ! preg_match( '/^GTM-[A-Z0-9]{4,}$/', $t ) ) {
			return '';
		}

		return substr( $t, 0, 22 );
	}

	/**
	 * @param mixed $raw User input.
	 * @return string Empty hoặc Measurement ID G-xxxx…
	 */
	public static function sanitize_ga4_measurement_id( $raw ) {
		$t = sanitize_text_field( is_string( $raw ) ? $raw : '' );
		$t = strtoupper( preg_replace( '/\s+/', '', $t ) );

		if ( ! preg_match( '/^G-[A-Z0-9]{4,}$/', $t ) ) {
			return '';
		}

		return substr( $t, 0, 24 );
	}

	private static function sanitize_measurement_event_name( $name ) {
		$key = sanitize_key( is_string( $name ) ? $name : '' );
		if ( '' === $key ) {
			$key = 'mttf_lead_submit';
		}
		return substr( $key, 0, 80 );
	}

	public static function measurement_section_intro() {
		echo '<p>Nếu bạn điền <strong>Container ID</strong> hoặc <strong>Measurement ID</strong> ở dưới, plugin sẽ tự chèn snippet chuẩn của Google (GTM vào đầu <code>&lt;head&gt;</code> + <code>noscript</code> trong <code>&lt;body&gt;</code>; GA4 là <code>gtag.js</code>).</p>';
		echo '<p>Sau khi khách nhấn “Gửi” lead thành công (tùy chọn): đẩy dữ liệu vào <code>window.dataLayer</code> để GTM đọc. <strong>Xóa mã trùng</strong> (GTM/G-XXXX) trong theme nếu bạn nhập tại đây để tránh hai lần chèn.</p>';
	}

	/**
	 * Checkbox + hidden 0 để POST luôn có giá trị rõ khi Gỡ chọn.
	 *
	 * @param array{key:string,label:string,default_on?:bool} $args Params.
	 */
	public static function render_checkbox_with_hidden( $args ) {
		$key         = $args['key'];
		$label       = $args['label'] ?? '';
		$default_on  = ! empty( $args['default_on'] );
		$value       = (int) self::get( $key, $default_on ? 1 : 0 );

		printf(
			'<input type="hidden" name="%s" value="0" />',
			esc_attr( self::OPTION_KEY . '[' . $key . ']' )
		);
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::OPTION_KEY . '[' . $key . ']' ); ?>"
				value="1"
				<?php checked( 1, $value ); ?>
			/>
			<span class="description"><?php echo wp_kses_post( $label ); ?></span>
		</label>
		<?php
	}

	public static function render_measurement_gtm_field() {
		$val = self::get( 'measurement_gtm_container_id', '' );
		?>
		<input
			type="text"
			class="regular-text code"
			name="<?php echo esc_attr( self::OPTION_KEY . '[measurement_gtm_container_id]' ); ?>"
			value="<?php echo esc_attr( $val ); ?>"
			placeholder="GTM-xxxxxxx"
			pattern="[Gg][Tt][Mm]-[a-zA-Z0-9]+"
			inputmode="text"
			autocomplete="off"
		/>
		<p class="description">Định dạng Google: <code>GTM-xxxxx</code> (lấy trong GTM → Admin → Container). Để trống = không chèn snippet GTM từ plugin.</p>
		<?php
	}

	public static function render_measurement_ga4_field() {
		$val = self::get( 'measurement_ga4_measurement_id', '' );
		?>
		<input
			type="text"
			class="regular-text code"
			name="<?php echo esc_attr( self::OPTION_KEY . '[measurement_ga4_measurement_id]' ); ?>"
			value="<?php echo esc_attr( $val ); ?>"
			placeholder="G-xxxxxxxxxx"
			pattern="[Gg]-[a-zA-Z0-9]+"
			autocomplete="off"
		/>
		<p class="description">Định dạng GA4: <code>G-xxxxxxxxxx</code> (Admin → Data streams → Web). Khi <strong>chỉ</strong> dùng GA4 qua GTM, thường để trống ô này. Nếu chỉ điền GA4 (không GTM), plugin chèn <code>gtag.js</code> trực tiếp.</p>
		<?php
	}

	public static function render_measurement_event_input() {
		$name = self::sanitize_measurement_event_name(
			self::get( 'measurement_event_name', 'mttf_lead_submit' )
		);
		?>
		<input
			type="text"
			class="regular-text code"
			name="<?php echo esc_attr( self::OPTION_KEY . '[measurement_event_name]' ); ?>"
			value="<?php echo esc_attr( $name ); ?>"
			pattern="[a-zA-Z0-9_\-]+"
			autocomplete="off"
		/>
		<p class="description"><strong>GTM Trigger → Custom Event</strong>: <code>Event name</code> phải <strong>trùng khớp tuyệt đối</strong> với ô này. Mặc định khuyến nghị: <code>mttf_lead_submit</code>.</p>
		<?php
	}

	public static function render_measurement_guide() {
		$event_slug = esc_html(
			self::sanitize_measurement_event_name(
				self::get( 'measurement_event_name', 'mttf_lead_submit' )
			)
		);
		$gtm_url       = esc_url( 'https://tagmanager.google.com/' );
		$ga4_admin_url = esc_url( 'https://analytics.google.com/' );
		$help_convs    = esc_url( 'https://support.google.com/tagmanager/answer/6106951' );
		$help_ga_conv  = esc_url( 'https://support.google.com/analytics/answer/10104470' );
		?>
		<style>
			.mttf-measurement-guide{border:1px solid #dcdcde;border-radius:10px;background:#fdfdfd;padding:16px 20px;margin-top:12px;max-width:960px;line-height:1.55;font-size:14px;}
			.mttf-measurement-guide h3{margin:.6rem 0 1rem;font-size:15px;}
			.mttf-measurement-guide ol{padding-left:1.25rem;margin:0 0 .75rem 1rem;}
			.mttf-measurement-guide li{margin-bottom:.65rem;}
			.mttf-measurement-guide details{margin:.75rem 0;padding:12px;background:#fff;border:1px solid #e8e8e8;border-radius:8px;}
			.mttf-measurement-guide code{background:rgba(100,115,137,.09);padding:1px 5px;border-radius:3px;}
		</style>
		<div class="mttf-measurement-guide">
			<p><strong>Biến có sẵn trên dataLayer</strong> mỗi khi lead thành công (khi bạn bật gửi sự kiện):</p>
			<ul style="margin:0 0 1rem;padding-left:1.25rem;">
				<li><code>route_id</code> — ID tuyến (post)</li>
				<li><code>route_slug</code> — slug tuyến</li>
				<li>Nếu bật thêm GA4 khuyến nghị: sự kiện thứ hai <code>generate_lead</code> (cùng biến trên)</li>
			</ul>

			<h3>Bước A — Chuẩn bị GA4</h3>
			<ol>
				<li>Đăng nhập <a href="<?php echo esc_url( $ga4_admin_url ); ?>" target="_blank" rel="noopener noreferrer">Google Analytics (GA4)</a>, chọn đúng thuộc tính (property) của website.</li>
				<li>Vào <strong>Quản trị (Admin)</strong> → cột <strong>Thuộc tính</strong> (<strong>Property</strong>) của site → <strong>Sự kiện (Events)</strong>.</li>
				<li>Đặt trong GTM một thẻ <strong>Google Analytics: GA4 Event</strong>; khi GA4 nhận sự kiện lần đầu có thể bấm <strong>Tạo sự kiện chính (Mark as conversion)</strong> cho sự kiện đó (hoặc trong <strong>Mục tiêu chuyển đổi / Conversions</strong> chỉ định tên event trùng với GA4).</li>
				<li>Tham khảo chi tiết: <a href="<?php echo esc_url( $help_ga_conv ); ?>" target="_blank" rel="noopener noreferrer">Đo chuyển đổi trong GA4</a>.</li>
			</ol>

			<h3>Bước B — Google Tag Manager (đây là bước bắt buộc nếu bạn đang dùng GTM làm cổng ra GA4)</h3>
			<ol>
				<li>Mở <a href="<?php echo esc_url( $gtm_url ); ?>" target="_blank" rel="noopener noreferrer">tagmanager.google.com</a>, chọn <strong>Container</strong> và lấy <strong>Container ID</strong> — nếu bạn dán ID vào MTTF ở trên thì <strong>không cần</strong> gắn thêm snippet GTM thủ công trong theme (tránh trùng).</li>
				<li><strong>Trigger mới:</strong> <em>Triggers</em> → <strong>New</strong> → <strong>Trigger Configuration</strong> → chọn loại <strong>Custom Event</strong>. Tại <strong>Event name</strong> nhập chính xác: <code><?php echo $event_slug; ?></code>. Đặt tên ví dụ: <strong>Lead — <?php echo $event_slug; ?></strong>.</li>
				<li><strong>Tag GA4 Events:</strong> <em>Tags</em> → <strong>New</strong> → <strong>Google Analytics: GA4 Event</strong>. Chọn đúng <strong>Measurement ID</strong> (định dạng G-…) đã có sẵn trong tag Cấu hình GA4 (Google tag) của bạn. Mục <strong>Event Name</strong> có thể đặt lại là <strong><?php echo $event_slug; ?></strong> (hoặc tên chuẩn nội bộ khác).</li>
				<li>Dưới mục <strong>Fields to Set</strong> (tuỳ chọn): đặt ví dụ tham số tùy chỉnh như <code>route_slug</code> và gán Giá trị từ <strong>Built-In Variable → Data Layer Variable</strong> (tạo biến mới trong GTM có tên <code>route_slug</code>, Data Layer Variable Name = <code>route_slug</code>).</li>
				<li>Trong Tag GA4 Events, ô <strong>Triggering</strong>: chọn trigger vừa tạo ở bước trigger trùng tên event <strong><?php echo $event_slug; ?></strong>.</li>
				<li><strong>Xuất bản:</strong> bấm <strong>Submit / Publish</strong> trên GTM. Sau đó dùng <strong>GTM Preview / Debug View</strong> để kiểm tra khi submit form popup lead trên web.</li>
				<li>Tham khảo Trigger Custom Event: <a href="<?php echo esc_url( $help_convs ); ?>" target="_blank" rel="noopener noreferrer">Hướng dẫn Google</a>.</li>
			</ol>

			<?php if ( (int) self::get( 'measurement_duplicate_ga4_generate_lead', 0 ) ) : ?>
				<p><strong>Bạn đã bật thêm</strong> <code>generate_lead</code>: trong GTM tạo thêm một <strong>Trigger — Custom Event</strong> có <strong>Event name</strong> = <code>generate_lead</code> và gắn cùng (hoặc tag riêng) với một thẻ <strong>GA4 Event</strong> có <strong>Event Name</strong> = <code>generate_lead</code>, rồi đánh dấu conversion trong GA4 như các sự kiện khác.</p>
			<?php else : ?>
				<details>
					<summary><strong>Dùng tên khuyến nghị GA4 <code>generate_lead</code> không?</strong></summary>
					<p>Bật ô “Sự kiện khuyến nghị GA4” phía trên: plugin sẽ đẩy thêm <code>generate_lead</code> giúp bạn không cần đổi tên trong GTM. Nếu không bật, chỉ cần follow trigger với event <code><?php echo $event_slug; ?></code>.</p>
				</details>
			<?php endif; ?>

			<h3>Bước C — Kiểm tra có hoạt động không</h3>
			<ol>
				<li>Đảm bảo trong MTTF ô <strong>Gửi sự kiện vào GTM</strong> đang bật (mục cài đặt trên).</li>
				<li>Mở site ở chế độ ẩn danh có GTM Preview, điền form lead → nhận thông báo thành công → trong Preview phải thấy <strong>Custom Event</strong>: <strong><?php echo $event_slug; ?></strong>.</li>
				<li>GA4 → <strong>Reports</strong> → <strong>Realtime</strong> → phần Events sẽ thấy tên event tương ứng (có độ trễ vài chục giây).</li>
			</ol>

			<p class="description" style="margin-bottom:0;">Lưu ý: không gửi số điện thoại/email lên tag nếu chưa đồng ý/ngăn nhạy cảm theo GDPR/chính sách của bạn. Plugin chỉ đẩy <code>route_id</code> và <code>route_slug</code> trên layer phân tích.</p>
		</div>
		<?php
	}

	public static function enqueue_assets( $hook ) {
		if ( MTTF_CPT::get_article_post_type() . '_page_mttf-settings' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'mttf-admin-settings',
			MTTF_URL . 'assets/js/admin-settings.js',
			array( 'jquery' ),
			MTTF_VERSION,
			true
		);
	}

	/**
	 * URL trang cài đặt có tab (để _wp_http_referer sau khi lưu).
	 *
	 * @param string $tab lead|measurement|hero|telegram
	 */
	public static function admin_tab_url( $tab ) {
		$tab = sanitize_key( $tab );
		return add_query_arg(
			array(
				'post_type' => MTTF_CPT::get_article_post_type(),
				'page'      => 'mttf-settings',
				'tab'       => $tab,
			),
			admin_url( 'edit.php' )
		);
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs = array(
			'lead'        => array(
				'label' => 'Lead & liên lạc',
				'page'  => self::SETTINGS_PAGE_LEAD,
			),
			'measurement' => array(
				'label' => 'Đo lường & GTM / GA4',
				'page'  => self::SETTINGS_PAGE_MEASUREMENT,
			),
			'hero'        => array(
				'label' => 'Hero trang chủ',
				'page'  => self::SETTINGS_PAGE_HERO,
			),
			'telegram'    => array(
				'label' => 'Telegram',
				'page'  => self::SETTINGS_PAGE_TELEGRAM,
			),
		);

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'lead'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- tab display only.

		if ( ! isset( $tabs[ $current_tab ] ) ) {
			$current_tab = 'lead';
		}

		$stats = self::get_route_search_stats();

		?>
		<div class="wrap">
			<h1>Cài đặt Minh Thang Transport Flow</h1>
			<?php settings_errors(); ?>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_id => $t ) : ?>
					<a
						href="<?php echo esc_url( self::admin_tab_url( $tab_id ) ); ?>"
						class="nav-tab<?php echo esc_attr( $current_tab === $tab_id ? ' nav-tab-active' : '' ); ?>"><?php echo esc_html( $t['label'] ); ?></a>
				<?php endforeach; ?>
			</h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" style="margin-top:18px;">
				<?php settings_fields( 'mttf_settings_group' ); ?>
				<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( esc_url( self::admin_tab_url( $current_tab ) ) ); ?>" />
				<?php
				do_settings_sections( $tabs[ $current_tab ]['page'] );
				submit_button( 'Lưu cài đặt' );
				?>
			</form>

			<?php if ( 'lead' === $current_tab ) : ?>
			<hr style="margin:32px 0;">

			<div style="max-width:1100px;">
				<h2>Thống kê tuyến hot theo search</h2>
				<p>Theo dõi tuyến nào đang được người dùng chọn nhiều nhất từ ô tìm kiếm. Badge <code>Tuyến hot</code> hiện tại được tự động gắn khi tuyến đạt từ 3 lượt search trở lên.</p>

				<?php if ( isset( $_GET['mttf_search_stats_reset'] ) && '1' === $_GET['mttf_search_stats_reset'] ) : ?>
					<div class="notice notice-success is-dismissible"><p>Đã reset toàn bộ số liệu hot theo search.</p></div>
				<?php endif; ?>

				<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin:18px 0 22px;">
					<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px;">
						<div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:700;">Tổng tuyến có dữ liệu</div>
						<div style="font-size:28px;font-weight:800;line-height:1.2;margin-top:6px;"><?php echo esc_html( (string) count( $stats ) ); ?></div>
					</div>
					<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px;">
						<div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:700;">Tuyến đứng đầu</div>
						<div style="font-size:20px;font-weight:800;line-height:1.35;margin-top:6px;"><?php echo ! empty( $stats ) ? esc_html( $stats[0]['title'] ) : 'Chưa có dữ liệu'; ?></div>
					</div>
					<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px;">
						<div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:700;">Lượt search cao nhất</div>
						<div style="font-size:28px;font-weight:800;line-height:1.2;margin-top:6px;"><?php echo ! empty( $stats ) ? esc_html( (string) $stats[0]['search_count'] ) : '0'; ?></div>
					</div>
				</div>

				<form method="post" action="<?php echo esc_url( self::admin_tab_url( 'lead' ) ); ?>" onsubmit="return window.confirm('Reset toàn bộ search_count và trạng thái hot của các tuyến?');">
					<?php wp_nonce_field( 'mttf_reset_search_stats', 'mttf_reset_search_stats_nonce' ); ?>
					<input type="hidden" name="mttf_action" value="reset_search_stats">
					<p><button type="submit" class="button button-secondary">Reset số liệu hot</button></p>
				</form>

				<?php if ( empty( $stats ) ) : ?>
					<p>Chưa có dữ liệu search nào được ghi nhận.</p>
				<?php else : ?>
					<table class="widefat striped" style="max-width:1100px;">
						<thead>
							<tr>
								<th style="width:60px;">#</th>
								<th>Tuyến</th>
								<th style="width:120px;">Search count</th>
								<th style="width:180px;">Lần search gần nhất</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $stats as $index => $row ) : ?>
								<tr>
									<td><?php echo esc_html( (string) ( $index + 1 ) ); ?></td>
									<td>
										<strong><?php echo esc_html( $row['title'] ); ?></strong>
										<div><a href="<?php echo esc_url( get_edit_post_link( $row['id'] ) ); ?>">Sửa tuyến</a></div>
									</td>
									<td><?php echo esc_html( (string) $row['search_count'] ); ?></td>
									<td><?php echo esc_html( $row['last_searched_label'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div>
		<?php
	}

	public static function handle_search_stats_actions() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page   = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );
		$action = sanitize_text_field( wp_unslash( $_POST['mttf_action'] ?? '' ) );

		if ( 'mttf-settings' !== $page || 'reset_search_stats' !== $action ) {
			return;
		}

		check_admin_referer( 'mttf_reset_search_stats', 'mttf_reset_search_stats_nonce' );

		$route_ids = get_posts(
			array(
				'post_type'      => MTTF_CPT::get_article_post_type(),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $route_ids as $route_id ) {
			delete_post_meta( $route_id, '_mttf_search_count' );
			delete_post_meta( $route_id, '_mttf_last_searched_at' );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'               => MTTF_CPT::get_article_post_type(),
					'page'                    => 'mttf-settings',
					'tab'                     => 'lead',
					'mttf_search_stats_reset' => '1',
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	private static function get_route_search_stats() {
		$route_ids = get_posts(
			array(
				'post_type'      => MTTF_CPT::get_article_post_type(),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_mttf_search_count',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$rows = array();

		foreach ( $route_ids as $route_id ) {
			$search_count = (int) get_post_meta( $route_id, '_mttf_search_count', true );
			if ( $search_count <= 0 ) {
				continue;
			}

			$last_searched_at = (int) get_post_meta( $route_id, '_mttf_last_searched_at', true );
			$rows[] = array(
				'id'                => (int) $route_id,
				'title'             => get_the_title( $route_id ),
				'search_count'      => $search_count,
				'last_searched_at'  => $last_searched_at,
				'last_searched_label' => $last_searched_at > 0 ? wp_date( 'd/m/Y H:i', $last_searched_at ) : 'Chưa có',
			);
		}

		usort(
			$rows,
			static function( $a, $b ) {
				if ( $a['search_count'] !== $b['search_count'] ) {
					return $b['search_count'] <=> $a['search_count'];
				}

				return $b['last_searched_at'] <=> $a['last_searched_at'];
			}
		);

		return $rows;
	}

	public static function get( $key, $default = '' ) {
		$options = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $options ) ) {
			return $default;
		}

		return $options[ $key ] ?? $default;
	}

	/**
	 * Tên Custom Event đang lưu (để GTM và REST localise).
	 */
	public static function get_measurement_event_slug() {
		return self::sanitize_measurement_event_name( self::get( 'measurement_event_name', '' ) );
	}

	/**
	 * ID GTM đã chuẩn hóa (rỗng nếu không hợp lệ).
	 */
	public static function get_saved_gtm_container_id() {
		return self::sanitize_gtm_container_id( self::get( 'measurement_gtm_container_id', '' ) );
	}

	/**
	 * Measurement ID GA4 đã chuẩn hóa.
	 */
	public static function get_saved_ga4_measurement_id() {
		return self::sanitize_ga4_measurement_id( self::get( 'measurement_ga4_measurement_id', '' ) );
	}

	/**
	 * Cấu hình đẩy dataLayer — dùng bởi wp_localize_script.
	 *
	 * @return array{enabled:int,eventName:string,duplicateGa4:int}
	 */
	public static function get_frontend_measurement_payload() {
		return array(
			'enabled'       => (int) self::get( 'measurement_data_layer_enabled', 1 ),
			'eventName'     => self::get_measurement_event_slug(),
			'duplicateGa4'  => (int) self::get( 'measurement_duplicate_ga4_generate_lead', 0 ),
		);
	}
}
