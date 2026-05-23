<?php
/**
 * Main plugin class.
 *
 * @package DXVN_PWA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DXVN_PWA {
	const OPTION_KEY = 'dxvn_pwa_settings';

	/**
	 * Bootstrap hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve_manifest' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve_service_worker' ) );
		add_action( 'wp_head', array( __CLASS__, 'render_manifest_tags' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public static function activate() {
		self::register_routes();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Register rewrite routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		add_rewrite_tag( '%dxvn_pwa_manifest%', '1' );
		add_rewrite_tag( '%dxvn_pwa_sw%', '1' );
		add_rewrite_rule( '^dxvn-pwa-manifest\.json$', 'index.php?dxvn_pwa_manifest=1', 'top' );
		add_rewrite_rule( '^dxvn-pwa-sw\.js$', 'index.php?dxvn_pwa_sw=1', 'top' );
	}

	/**
	 * Register query vars.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public static function register_query_vars( $vars ) {
		$vars[] = 'dxvn_pwa_manifest';
		$vars[] = 'dxvn_pwa_sw';
		return $vars;
	}

	/**
	 * Render head tags.
	 *
	 * @return void
	 */
	public static function render_manifest_tags() {
		if ( ! self::is_enabled() ) {
			return;
		}

		echo '<link rel="manifest" href="' . esc_url( home_url( '/dxvn-pwa-manifest.json' ) ) . '">' . "\n";
		echo '<meta name="theme-color" content="' . esc_attr( self::get_setting( 'theme_color' ) ) . '">' . "\n";
		echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
		echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr( self::get_setting( 'short_name' ) ) . '">' . "\n";

		$icon_192 = self::get_icon_url( 'icon_192_id' );
		if ( $icon_192 ) {
			echo '<link rel="apple-touch-icon" href="' . esc_url( $icon_192 ) . '">' . "\n";
		}
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		if ( ! self::is_enabled() ) {
			return;
		}

		$js_path  = DXVN_PWA_PATH . 'assets/js/prompt.js';
		$css_path = DXVN_PWA_PATH . 'assets/css/prompt.css';

		wp_enqueue_style(
			'dxvn-pwa-prompt',
			DXVN_PWA_URL . 'assets/css/prompt.css',
			array(),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : DXVN_PWA_VERSION
		);

		wp_enqueue_script(
			'dxvn-pwa-prompt',
			DXVN_PWA_URL . 'assets/js/prompt.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : DXVN_PWA_VERSION,
			true
		);

		wp_localize_script(
			'dxvn-pwa-prompt',
			'dxvnPwaData',
			array(
				'enabled'          => true,
				'promptDelay'      => (int) self::get_setting( 'prompt_delay', 4500 ),
				'promptCooldown'   => (int) self::get_setting( 'prompt_cooldown_days', 7 ),
				'appName'          => self::get_setting( 'name' ),
				'iosEnabled'       => (bool) self::get_setting( 'ios_prompt_enabled', true ),
				'androidEnabled'   => (bool) self::get_setting( 'android_prompt_enabled', true ),
				'serviceWorkerUrl' => home_url( '/dxvn-pwa-sw.js' ),
				'assetBase'        => DXVN_PWA_URL . 'assets/',
				'copy'             => array(
					'title'            => self::get_setting( 'prompt_title' ),
					'description'      => self::get_setting( 'prompt_description' ),
					'installCta'       => self::get_setting( 'install_button_label' ),
					'laterCta'         => self::get_setting( 'later_button_label' ),
					'dismissCta'       => self::get_setting( 'dismiss_button_label' ),
					'iosTitle'         => self::get_setting( 'ios_prompt_title' ),
					'iosStepOne'       => self::get_setting( 'ios_step_one' ),
					'iosStepTwo'       => self::get_setting( 'ios_step_two' ),
					'iosStepThree'     => self::get_setting( 'ios_step_three' ),
				),
			)
		);
	}

	/**
	 * Register settings page.
	 *
	 * @return void
	 */
	public static function register_settings_page() {
		add_options_page(
			'DXVN PWA',
			'DXVN PWA',
			'manage_options',
			'dxvn-pwa',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'dxvn_pwa_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::get_default_settings(),
			)
		);

		add_settings_section(
			'dxvn_pwa_main',
			'Cấu hình PWA',
			'__return_false',
			'dxvn-pwa'
		);

		$fields = array(
			'enabled'                => array( 'label' => 'Bật PWA', 'type' => 'checkbox' ),
			'name'                   => array( 'label' => 'Tên app', 'type' => 'text' ),
			'short_name'             => array( 'label' => 'Tên ngắn', 'type' => 'text' ),
			'description'            => array( 'label' => 'Mô tả ngắn', 'type' => 'textarea' ),
			'start_url'              => array( 'label' => 'Start URL', 'type' => 'text' ),
			'theme_color'            => array( 'label' => 'Theme color', 'type' => 'text' ),
			'background_color'       => array( 'label' => 'Background color', 'type' => 'text' ),
			'prompt_delay'           => array( 'label' => 'Delay hiện prompt (ms)', 'type' => 'number' ),
			'prompt_cooldown_days'   => array( 'label' => 'Số ngày nhắc lại', 'type' => 'number' ),
			'android_prompt_enabled' => array( 'label' => 'Hiện prompt Android', 'type' => 'checkbox' ),
			'ios_prompt_enabled'     => array( 'label' => 'Hiện hướng dẫn iPhone/iPad', 'type' => 'checkbox' ),
			'prompt_title'           => array( 'label' => 'Tiêu đề prompt Android', 'type' => 'text' ),
			'prompt_description'     => array( 'label' => 'Mô tả prompt Android', 'type' => 'textarea' ),
			'install_button_label'   => array( 'label' => 'Nhãn nút cài app', 'type' => 'text' ),
			'later_button_label'     => array( 'label' => 'Nhãn nút để sau', 'type' => 'text' ),
			'dismiss_button_label'   => array( 'label' => 'Nhãn nút không nhắc nữa', 'type' => 'text' ),
			'ios_prompt_title'       => array( 'label' => 'Tiêu đề hướng dẫn iOS', 'type' => 'text' ),
			'ios_step_one'           => array( 'label' => 'Bước 1 trên iOS', 'type' => 'text' ),
			'ios_step_two'           => array( 'label' => 'Bước 2 trên iOS', 'type' => 'text' ),
			'ios_step_three'         => array( 'label' => 'Bước 3 trên iOS', 'type' => 'text' ),
			'icon_192_id'            => array( 'label' => 'Icon 192x192', 'type' => 'media' ),
			'icon_512_id'            => array( 'label' => 'Icon 512x512', 'type' => 'media' ),
		);

		foreach ( $fields as $key => $field ) {
			add_settings_field(
				$key,
				$field['label'],
				array( __CLASS__, 'render_field' ),
				'dxvn-pwa',
				'dxvn_pwa_main',
				array(
					'key'  => $key,
					'type' => $field['type'],
				)
			);
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current screen hook.
	 * @return void
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_dxvn-pwa' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_enqueue_media();
		$inline_script = <<<'JS'
(function($){
	$(function(){
		var frame;

		$(document).on('click', '.dxvn-pwa-media-btn', function(e){
			e.preventDefault();

			var button = $(this);
			var target = $('#' + button.data('target'));
			var preview = $('#' + button.data('preview'));

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: 'Chọn icon PWA',
				button: { text: 'Dùng ảnh này' },
				multiple: false
			});

			frame.on('select', function(){
				var attachment = frame.state().get('selection').first().toJSON();
				target.val(attachment.id);
				preview.attr('src', attachment.url).show();
			});

			frame.open();
		});

		$(document).on('click', '.dxvn-pwa-media-clear', function(e){
			e.preventDefault();

			var button = $(this);
			$('#' + button.data('target')).val('');
			$('#' + button.data('preview')).hide().attr('src', '');
		});
	});
})(jQuery);
JS;

		wp_add_inline_script(
			'jquery-core',
			$inline_script
		);
	}

	/**
	 * Render a settings field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_field( $args ) {
		$key      = $args['key'];
		$type     = $args['type'];
		$settings = self::get_settings();
		$value    = $settings[ $key ] ?? '';
		$name     = self::OPTION_KEY . '[' . $key . ']';

		if ( 'checkbox' === $type ) {
			echo '<label><input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( ! empty( $value ), true, false ) . '> Bật</label>';
			return;
		}

		if ( 'textarea' === $type ) {
			echo '<textarea class="large-text" rows="3" name="' . esc_attr( $name ) . '">' . esc_textarea( (string) $value ) . '</textarea>';
			return;
		}

		if ( 'number' === $type ) {
			echo '<input type="number" class="small-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
			return;
		}

		if ( 'media' === $type ) {
			$preview_url = $value ? wp_get_attachment_image_url( (int) $value, 'thumbnail' ) : '';
			$input_id    = 'dxvn-pwa-' . $key;
			$preview_id  = $input_id . '-preview';
			echo '<input id="' . esc_attr( $input_id ) . '" type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
			echo '<button type="button" class="button dxvn-pwa-media-btn" data-target="' . esc_attr( $input_id ) . '" data-preview="' . esc_attr( $preview_id ) . '">Chọn ảnh</button> ';
			echo '<button type="button" class="button-link-delete dxvn-pwa-media-clear" data-target="' . esc_attr( $input_id ) . '" data-preview="' . esc_attr( $preview_id ) . '">Xóa</button>';
			echo '<div style="margin-top:10px">';
			echo '<img id="' . esc_attr( $preview_id ) . '" src="' . esc_url( (string) $preview_url ) . '" style="max-width:96px;height:auto;' . ( $preview_url ? '' : 'display:none;' ) . '">';
			echo '</div>';
			return;
		}

		echo '<input type="text" class="regular-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
		$tabs = array(
			'settings' => 'Cấu hình',
			'guide'    => 'Hướng dẫn',
		);
		?>
		<div class="wrap">
			<h1>DXVN PWA</h1>
			<p>Cấu hình lớp PWA để website có thể ghim ra màn hình như app và tái sử dụng trên nhiều site WordPress khác nhau.</p>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
					<?php
					$tab_url = add_query_arg(
						array(
							'page' => 'dxvn-pwa',
							'tab'  => $tab_key,
						),
						admin_url( 'options-general.php' )
					);
					?>
					<a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<?php if ( 'guide' === $current_tab ) : ?>
				<div style="max-width:920px;background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:24px;margin-top:18px;">
					<h2 style="margin-top:0;">Cách sử dụng DXVN PWA</h2>
					<p>Plugin này thêm lớp PWA dùng chung cho WordPress: manifest, service worker và prompt cài app ra màn hình chính.</p>

					<h3>1. Thiết lập cơ bản</h3>
					<ol>
						<li>Bật plugin trong trang <code>Plugins</code>.</li>
						<li>Vào <code>Settings &gt; DXVN PWA</code> tab <code>Cấu hình</code>.</li>
						<li>Điền <code>Tên app</code>, <code>Tên ngắn</code>, màu chủ đạo và màu nền.</li>
						<li>Chọn icon <code>192x192</code> và <code>512x512</code> từ Media Library.</li>
						<li>Lưu lại cấu hình.</li>
					</ol>

					<h3>2. Cách prompt hoạt động</h3>
					<ul style="list-style:disc;padding-left:18px;">
						<li>Android/Chrome: plugin dùng sự kiện <code>beforeinstallprompt</code> để hiện nút cài app.</li>
						<li>iPhone/iPad: plugin hiện box hướng dẫn thủ công vì iOS không hỗ trợ prompt cài app như Android.</li>
						<li>Nếu người dùng bấm <code>Để sau</code>, plugin sẽ lưu thời gian và chỉ nhắc lại sau số ngày bạn cấu hình.</li>
					</ul>

					<h3>3. Cách test</h3>
					<ul style="list-style:disc;padding-left:18px;">
						<li>Nên test trên môi trường có HTTPS hoặc môi trường local hỗ trợ service worker.</li>
						<li>Sau khi thay icon hoặc màu, hãy tải lại cứng trình duyệt hoặc xóa cache PWA để thấy bản mới.</li>
						<li>Nếu vừa kích hoạt plugin mà link manifest/service worker chưa nhận, hãy lưu lại <code>Permalinks</code> một lần để refresh rewrite rules.</li>
					</ul>

					<h3>4. Dùng trên site khác</h3>
					<ul style="list-style:disc;padding-left:18px;">
						<li>Plugin không phụ thuộc theme hay plugin <code>minh-thang-transport-flow</code>.</li>
						<li>Mặc định plugin tự lấy tên site trong WordPress để tạo app name và prompt copy.</li>
						<li>Bạn có thể thay toàn bộ nội dung prompt ngay trong tab <code>Cấu hình</code> mà không cần sửa code.</li>
					</ul>

					<h3>5. Lưu ý</h3>
					<ul style="list-style:disc;padding-left:18px;">
						<li>Không phải mọi trình duyệt đều hỗ trợ prompt cài app giống nhau.</li>
						<li>iOS cần thao tác <code>Chia sẻ &gt; Thêm vào Màn hình chính</code>.</li>
						<li>Nếu site dùng plugin cache/CDN, nên xóa cache sau khi đổi cấu hình PWA.</li>
					</ul>

					<p style="margin-bottom:0;"><a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'dxvn-pwa', 'tab' => 'settings' ), admin_url( 'options-general.php' ) ) ); ?>">Quay về cấu hình</a></p>
				</div>
			<?php else : ?>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'dxvn_pwa_group' );
					do_settings_sections( 'dxvn-pwa' );
					submit_button( 'Lưu cấu hình PWA' );
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Maybe serve manifest.
	 *
	 * @return void
	 */
	public static function maybe_serve_manifest() {
		if ( '1' !== get_query_var( 'dxvn_pwa_manifest' ) ) {
			return;
		}

		$manifest = array(
			'name'             => self::get_setting( 'name' ),
			'short_name'       => self::get_setting( 'short_name' ),
			'description'      => self::get_setting( 'description' ),
			'start_url'        => self::get_setting( 'start_url' ),
			'scope'            => '/',
			'display'          => 'standalone',
			'background_color' => self::get_setting( 'background_color' ),
			'theme_color'      => self::get_setting( 'theme_color' ),
			'icons'            => self::get_manifest_icons(),
		);

		nocache_headers();
		header( 'Content-Type: application/manifest+json; charset=' . get_option( 'blog_charset' ) );
		echo wp_json_encode( $manifest );
		exit;
	}

	/**
	 * Maybe serve service worker.
	 *
	 * @return void
	 */
	public static function maybe_serve_service_worker() {
		if ( '1' !== get_query_var( 'dxvn_pwa_sw' ) ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ) );
		echo self::get_service_worker_contents();
		exit;
	}

	/**
	 * Service worker contents.
	 *
	 * @return string
	 */
	protected static function get_service_worker_contents() {
		$cache_name = 'dxvn-pwa-v' . preg_replace( '/[^0-9a-z\.\-]/i', '', DXVN_PWA_VERSION );
		$offline    = array(
			home_url( '/' ),
			home_url( '/dxvn-pwa-manifest.json' ),
		);

		return "(function(){const CACHE_NAME=" . wp_json_encode( $cache_name ) . ";const URLS=" . wp_json_encode( $offline ) . ";self.addEventListener('install',event=>{event.waitUntil(caches.open(CACHE_NAME).then(cache=>cache.addAll(URLS)).then(()=>self.skipWaiting()));});self.addEventListener('activate',event=>{event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key!==CACHE_NAME).map(key=>caches.delete(key)))).then(()=>self.clients.claim()));});self.addEventListener('fetch',event=>{if(event.request.method!=='GET'){return;}event.respondWith(caches.match(event.request).then(response=>response||fetch(event.request).then(networkResponse=>{const copy=networkResponse.clone();caches.open(CACHE_NAME).then(cache=>cache.put(event.request,copy));return networkResponse;}).catch(()=>caches.match(" . wp_json_encode( home_url( '/' ) ) . "))));});})();";
	}

	/**
	 * Build manifest icons.
	 *
	 * @return array
	 */
	protected static function get_manifest_icons() {
		$icons = array();

		foreach ( array( '192', '512' ) as $size ) {
			$url = self::get_icon_url( 'icon_' . $size . '_id', $size );
			if ( ! $url ) {
				continue;
			}

			$icons[] = array(
				'src'   => $url,
				'sizes' => $size . 'x' . $size,
				'type'  => 'image/png',
			);
		}

		return $icons;
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();

		return array(
			'enabled'                => ! empty( $input['enabled'] ),
			'name'                   => sanitize_text_field( $input['name'] ?? '' ),
			'short_name'             => sanitize_text_field( $input['short_name'] ?? '' ),
			'description'            => sanitize_textarea_field( $input['description'] ?? '' ),
			'start_url'              => esc_url_raw( $input['start_url'] ?? '' ),
			'theme_color'            => self::sanitize_color( $input['theme_color'] ?? '' ),
			'background_color'       => self::sanitize_color( $input['background_color'] ?? '' ),
			'prompt_delay'           => max( 0, absint( $input['prompt_delay'] ?? 4500 ) ),
			'prompt_cooldown_days'   => max( 1, absint( $input['prompt_cooldown_days'] ?? 7 ) ),
			'android_prompt_enabled' => ! empty( $input['android_prompt_enabled'] ),
			'ios_prompt_enabled'     => ! empty( $input['ios_prompt_enabled'] ),
			'prompt_title'           => sanitize_text_field( $input['prompt_title'] ?? '' ),
			'prompt_description'     => sanitize_textarea_field( $input['prompt_description'] ?? '' ),
			'install_button_label'   => sanitize_text_field( $input['install_button_label'] ?? '' ),
			'later_button_label'     => sanitize_text_field( $input['later_button_label'] ?? '' ),
			'dismiss_button_label'   => sanitize_text_field( $input['dismiss_button_label'] ?? '' ),
			'ios_prompt_title'       => sanitize_text_field( $input['ios_prompt_title'] ?? '' ),
			'ios_step_one'           => sanitize_text_field( $input['ios_step_one'] ?? '' ),
			'ios_step_two'           => sanitize_text_field( $input['ios_step_two'] ?? '' ),
			'ios_step_three'         => sanitize_text_field( $input['ios_step_three'] ?? '' ),
			'icon_192_id'            => absint( $input['icon_192_id'] ?? 0 ),
			'icon_512_id'            => absint( $input['icon_512_id'] ?? 0 ),
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		$site_name  = wp_strip_all_tags( get_bloginfo( 'name' ) );
		$site_short = self::get_default_short_name( $site_name );

		return array(
			'enabled'                => true,
			'name'                   => $site_name,
			'short_name'             => $site_short,
			'description'            => 'Phiên bản web app của ' . $site_name . '.',
			'start_url'              => home_url( '/' ),
			'theme_color'            => '#005b98',
			'background_color'       => '#ffffff',
			'prompt_delay'           => 4500,
			'prompt_cooldown_days'   => 7,
			'android_prompt_enabled' => true,
			'ios_prompt_enabled'     => true,
			'prompt_title'           => 'Ghim ' . $site_name . ' ra màn hình chính',
			'prompt_description'     => 'Mở website nhanh hơn như app ngay từ màn hình chính.',
			'install_button_label'   => 'Cài app',
			'later_button_label'     => 'Để sau',
			'dismiss_button_label'   => 'Không nhắc nữa',
			'ios_prompt_title'       => 'Thêm ' . $site_name . ' ra màn hình',
			'ios_step_one'           => 'Bấm nút Chia sẻ ở thanh trình duyệt.',
			'ios_step_two'           => 'Chọn "Thêm vào Màn hình chính".',
			'ios_step_three'         => 'Bấm "Thêm" để mở website như app.',
			'icon_192_id'            => 0,
			'icon_512_id'            => 0,
		);
	}

	/**
	 * Build a default short name from the site title.
	 *
	 * @param string $site_name Site title.
	 * @return string
	 */
	protected static function get_default_short_name( $site_name ) {
		$site_name = trim( preg_replace( '/\s+/', ' ', (string) $site_name ) );
		if ( '' === $site_name ) {
			return 'PWA App';
		}

		if ( function_exists( 'mb_strlen' ) && mb_strlen( $site_name ) <= 12 ) {
			return $site_name;
		}

		$words = preg_split( '/\s+/', $site_name );
		if ( is_array( $words ) && count( $words ) > 1 ) {
			$initials = '';
			foreach ( $words as $word ) {
				$initials .= function_exists( 'mb_substr' ) ? mb_substr( $word, 0, 1 ) : substr( $word, 0, 1 );
			}
			if ( '' !== $initials && strlen( $initials ) <= 12 ) {
				return strtoupper( $initials );
			}
		}

		return function_exists( 'mb_substr' ) ? mb_substr( $site_name, 0, 12 ) : substr( $site_name, 0, 12 );
	}

	/**
	 * Get merged settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::get_default_settings() );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_setting( $key, $default = '' ) {
		$settings = self::get_settings();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Is PWA enabled.
	 *
	 * @return bool
	 */
	protected static function is_enabled() {
		return ! is_admin() && ! is_feed() && ! is_robots() && (bool) self::get_setting( 'enabled', true );
	}

	/**
	 * Sanitize a hex color.
	 *
	 * @param string $color Color value.
	 * @return string
	 */
	protected static function sanitize_color( $color ) {
		$color = sanitize_hex_color( $color );
		return $color ? $color : '#005b98';
	}

	/**
	 * Get icon URL by attachment ID setting.
	 *
	 * @param string $key Option key.
	 * @return string
	 */
	protected static function get_icon_url( $key ) {
		$id = (int) self::get_setting( $key, 0 );
		$size = 'full';
		$url  = '';

		if ( func_num_args() > 1 ) {
			$requested_size = func_get_arg( 1 );
			if ( ! empty( $requested_size ) ) {
				$size = $requested_size;
			}
		}

		if ( $id ) {
			$url = wp_get_attachment_image_url( $id, $size );
			if ( $url ) {
				return $url;
			}
		}

		$site_icon_id = (int) get_option( 'site_icon' );
		if ( $site_icon_id ) {
			$url = wp_get_attachment_image_url( $site_icon_id, $size );
			if ( $url ) {
				return $url;
			}
		}

		return $url ? $url : '';
	}
}
