<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register settings for the plugin.
 */
function wfcb_register_settings() {
	register_setting(
		'wfcb_settings_group',
		'wfcb_settings',
		'wfcb_sanitize_settings'
	);
}
add_action( 'admin_init', 'wfcb_register_settings' );

/**
 * Sanitize settings.
 *
 * @param array $input Raw input.
 *
 * @return array
 */
function wfcb_sanitize_settings( $input ) {
	$output = array();

	$output['enabled'] = ! empty( $input['enabled'] ) ? 1 : 0;

	$allowed_positions = array( 'left', 'right', 'bottom' );
	$position          = isset( $input['position'] ) ? $input['position'] : 'right';

	$output['position'] = in_array( $position, $allowed_positions, true ) ? $position : 'right';

	$excluded_slugs = isset( $input['excluded_slugs'] ) ? $input['excluded_slugs'] : '';
	$output['excluded_slugs'] = wfcb_normalize_excluded_slugs( $excluded_slugs );

	$output['items'] = array();

	if ( ! empty( $input['items'] ) && is_array( $input['items'] ) ) {
		foreach ( $input['items'] as $item ) {
			$label            = isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : '';
			$icon_url         = isset( $item['icon_url'] ) ? esc_url_raw( $item['icon_url'] ) : '';
			$link_url         = isset( $item['link_url'] ) ? esc_url_raw( $item['link_url'] ) : '';
			$app_key          = isset( $item['app_key'] ) ? sanitize_key( $item['app_key'] ) : '';
			$handle           = isset( $item['handle'] ) ? sanitize_text_field( $item['handle'] ) : '';
			$qr_image_url     = isset( $item['qr_image_url'] ) ? esc_url_raw( $item['qr_image_url'] ) : '';
			$scan_hint        = isset( $item['scan_hint'] ) ? sanitize_text_field( $item['scan_hint'] ) : '';
			$desktop_behavior = isset( $item['desktop_behavior'] ) ? sanitize_key( $item['desktop_behavior'] ) : 'link';
			$accent_color     = isset( $item['accent_color'] ) ? sanitize_hex_color( $item['accent_color'] ) : '';

			$allowed_desktop_behaviors = array( 'link', 'qr_page' );
			if ( ! in_array( $desktop_behavior, $allowed_desktop_behaviors, true ) ) {
				$desktop_behavior = 'link';
			}

			if ( '' === $label && '' === $icon_url && '' === $link_url ) {
				continue;
			}

			$output['items'][] = array(
				'label'            => $label,
				'icon_url'         => $icon_url,
				'link_url'         => $link_url,
				'app_key'          => $app_key,
				'handle'           => $handle,
				'qr_image_url'     => $qr_image_url,
				'scan_hint'        => $scan_hint,
				'desktop_behavior' => $desktop_behavior,
				'accent_color'     => $accent_color ? $accent_color : '',
			);
		}
	}

	return $output;
}

/**
 * Normalize a list of slugs from a textarea or array input.
 *
 * @param string|array $value Raw slug list.
 *
 * @return array
 */
function wfcb_normalize_excluded_slugs( $value ) {
	if ( is_array( $value ) ) {
		$raw_values = $value;
	} else {
		$raw_values = preg_split( '/[\s,]+/', (string) $value );
	}

	$slugs = array();

	foreach ( $raw_values as $raw_value ) {
		$slug = sanitize_title( wp_unslash( $raw_value ) );

		if ( '' !== $slug ) {
			$slugs[] = $slug;
		}
	}

	return array_values( array_unique( $slugs ) );
}

/**
 * Add settings page.
 */
function wfcb_add_settings_page() {
	add_options_page(
		__( 'Floating Contact Bar', 'wfcb' ),
		__( 'Floating Contact Bar', 'wfcb' ),
		'manage_options',
		'wfcb-settings',
		'wfcb_render_settings_page'
	);
}
add_action( 'admin_menu', 'wfcb_add_settings_page' );

/**
 * Enqueue admin assets.
 *
 * @param string $hook Current admin page hook.
 */
function wfcb_admin_enqueue_assets( $hook ) {
	if ( 'settings_page_wfcb-settings' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'wfcb-admin',
		WFCB_PLUGIN_URL . 'assets/admin.css',
		array(),
		WFCB_VERSION
	);

	wp_enqueue_media();

	wp_enqueue_script(
		'wfcb-admin',
		WFCB_PLUGIN_URL . 'assets/admin.js',
		array( 'jquery', 'jquery-ui-sortable' ),
		WFCB_VERSION,
		true
	);

	wp_localize_script(
		'wfcb-admin',
		'wfcbAdmin',
		array(
			'itemTemplate' => wfcb_get_item_template(),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'wfcb_admin_enqueue_assets' );

/**
 * Get a single item row HTML template for JS cloning.
 *
 * @return string
 */
function wfcb_get_item_template() {
	ob_start();
	?>
	<li class="wfcb-item">
		<div class="wfcb-item-inner">
			<div class="wfcb-item-header">
				<span class="wfcb-item-handle">☰</span>
				<div class="wfcb-item-header-actions">
					<button type="button" class="button-link-delete wfcb-remove-item">
						<?php esc_html_e( 'Remove', 'wfcb' ); ?>
					</button>
				</div>
			</div>

			<div class="wfcb-item-grid wfcb-item-grid--main">
				<div class="wfcb-field-group">
					<label>
						<?php esc_html_e( 'Label', 'wfcb' ); ?>
						<input type="text" name="wfcb_settings[items][{{index}}][label]" value="" />
					</label>
				</div>

				<div class="wfcb-field-group">
					<label>
						<?php esc_html_e( 'Link URL', 'wfcb' ); ?>
						<input type="text" name="wfcb_settings[items][{{index}}][link_url]" value="" />
					</label>
				</div>

				<div class="wfcb-field-group">
					<label>
						<?php esc_html_e( 'Icon URL', 'wfcb' ); ?>
						<span class="wfcb-media-field">
							<input type="text" class="wfcb-media-url" name="wfcb_settings[items][{{index}}][icon_url]" value="" />
							<button type="button" class="button wfcb-upload-media"><?php esc_html_e( 'Choose', 'wfcb' ); ?></button>
						</span>
					</label>
				</div>
			</div>

			<details class="wfcb-item-advanced">
				<summary><?php esc_html_e( 'QR Cards (advanced)', 'wfcb' ); ?></summary>

				<div class="wfcb-item-grid wfcb-item-grid--advanced">
					<div class="wfcb-field-group">
						<label>
							<?php esc_html_e( 'App key', 'wfcb' ); ?>
							<input type="text" name="wfcb_settings[items][{{index}}][app_key]" value="" placeholder="line / zalo / viber" />
						</label>
					</div>

					<div class="wfcb-field-group">
						<label>
							<?php esc_html_e( 'Handle / ID', 'wfcb' ); ?>
							<input type="text" name="wfcb_settings[items][{{index}}][handle]" value="" placeholder="@yourusername" />
						</label>
					</div>

					<div class="wfcb-field-group">
						<label>
							<?php esc_html_e( 'Accent color', 'wfcb' ); ?>
							<input type="color" name="wfcb_settings[items][{{index}}][accent_color]" value="#0ea5e9" />
						</label>
					</div>

					<div class="wfcb-field-group">
						<label>
							<?php esc_html_e( 'Desktop behavior', 'wfcb' ); ?>
							<select name="wfcb_settings[items][{{index}}][desktop_behavior]">
								<option value="link"><?php esc_html_e( 'Open link', 'wfcb' ); ?></option>
								<option value="qr_page"><?php esc_html_e( 'Go to /quet-ma-qr (hash by app key)', 'wfcb' ); ?></option>
							</select>
						</label>
					</div>

					<div class="wfcb-field-group">
						<label>
							<?php esc_html_e( 'QR Image URL', 'wfcb' ); ?>
							<span class="wfcb-media-field">
								<input type="text" class="wfcb-media-url" name="wfcb_settings[items][{{index}}][qr_image_url]" value="" />
								<button type="button" class="button wfcb-upload-media"><?php esc_html_e( 'Choose', 'wfcb' ); ?></button>
							</span>
						</label>
					</div>

					<div class="wfcb-field-group">
						<label>
							<?php esc_html_e( 'Scan hint', 'wfcb' ); ?>
							<input type="text" name="wfcb_settings[items][{{index}}][scan_hint]" value="" placeholder="<?php echo esc_attr__( 'Quét mã để kết bạn', 'wfcb' ); ?>" />
						</label>
					</div>
				</div>
			</details>
		</div>
	</li>
	<?php

	$html = ob_get_clean();

	return $html;
}

/**
 * Render the settings page.
 */
function wfcb_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$defaults = array(
		'enabled'        => 0,
		'position'       => 'right',
		'excluded_slugs' => array(
			'dat-ve-truc-tuyen',
			've-xe-khach',
			'dopayment',
			'paymentsuccess',
			'bookingsuccess',
			'booking',
			'ticketinfo',
			'kiem-tra-ve',
			'pm',
		),
		'items'          => array(),
	);

	$options = wp_parse_args( get_option( 'wfcb_settings', array() ), $defaults );
	?>
	<div class="wrap wfcb-settings-wrap">
		<h1><?php esc_html_e( 'Floating Contact Bar', 'wfcb' ); ?></h1>

		<p>
			<strong><?php esc_html_e( 'Hướng dẫn nhanh:', 'wfcb' ); ?></strong>
		</p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Bật "Enable bar" để hiển thị thanh liên hệ nổi trên toàn bộ website.', 'wfcb' ); ?></li>
			<li><?php esc_html_e( 'Chọn "Position" là Left / Right / Bottom để đặt vị trí hiển thị.', 'wfcb' ); ?></li>
			<li><?php esc_html_e( 'Dùng "Excluded slugs" để ẩn thanh liên hệ ở các trang hoặc slug bạn không muốn hiển thị.', 'wfcb' ); ?></li>
			<li><?php esc_html_e( 'Trong "Contact items", thêm từng kênh liên hệ (SĐT, Zalo, Messenger, WhatsApp, Email...) và kéo-thả để sắp xếp.', 'wfcb' ); ?></li>
			<li><?php esc_html_e( 'Trường "Link URL" nên dùng đúng định dạng, ví dụ: tel:0865333266, https://zalo.me/0865333266, https://m.me/tenfanpage, https://wa.me/84865333266, mailto:email@domain.com.', 'wfcb' ); ?></li>
			<li><?php esc_html_e( 'Trường "Icon URL" chọn icon từ Media Library để hiển thị logo tương ứng.', 'wfcb' ); ?></li>
			<li><?php esc_html_e( 'Phần "QR Cards (advanced)" chỉ cần dùng khi muốn tạo trang /quet-ma-qr với mã QR cho từng ứng dụng.', 'wfcb' ); ?></li>
		</ul>

		<p>
			<strong><?php esc_html_e( 'Hướng dẫn setup trang QR code /quet-ma-qr:', 'wfcb' ); ?></strong>
		</p>
		<ol style="margin-left: 20px;">
			<li><?php esc_html_e( 'Vào Trang → Thêm trang mới, đặt tiêu đề "Quét mã QR" (hoặc tên bạn muốn) và slug là "quet-ma-qr". Trong nội dung trang, chèn shortcode [wfcb_qr_cards]. Xuất bản trang.', 'wfcb' ); ?></li>
			<li><?php esc_html_e( 'Trong mỗi Contact item bạn muốn dùng QR, mở "QR Cards (advanced)" và điền App key (ví dụ: zalo, line, viber) giống nhau cho tất cả item thuộc cùng ứng dụng.', 'wfcb' ); ?></li>
			<li><?php esc_html_e( 'Điền QR Image URL là đường dẫn ảnh QR của kênh đó (upload vào Media Library rồi copy link).', 'wfcb' ); ?></li>
			<li><?php esc_html_e( 'Chọn Desktop behavior = "Go to /quet-ma-qr (hash by app key)". Khi người dùng trên desktop bấm icon, plugin sẽ chuyển đến trang /quet-ma-qr và hiển thị đúng QR theo app key.', 'wfcb' ); ?></li>
			<li><?php esc_html_e( 'Tùy chọn: chỉnh Scan hint (ví dụ: "Quét mã để kết bạn") và Accent color để đồng bộ với màu thương hiệu.', 'wfcb' ); ?></li>
		</ol>

		<form method="post" action="options.php">
			<?php settings_fields( 'wfcb_settings_group' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="wfcb_enabled"><?php esc_html_e( 'Enable bar', 'wfcb' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" id="wfcb_enabled" name="wfcb_settings[enabled]" value="1" <?php checked( ! empty( $options['enabled'] ) ); ?> />
							<?php esc_html_e( 'Show floating contact bar on site', 'wfcb' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="wfcb_excluded_slugs"><?php esc_html_e( 'Excluded slugs', 'wfcb' ); ?></label>
					</th>
					<td>
						<textarea
							id="wfcb_excluded_slugs"
							name="wfcb_settings[excluded_slugs]"
							rows="5"
							cols="50"
							class="large-text code"
							placeholder="dat-ve-truc-tuyen
ve-xe-khach
dopayment"
						><?php echo esc_textarea( implode( "\n", isset( $options['excluded_slugs'] ) && is_array( $options['excluded_slugs'] ) ? $options['excluded_slugs'] : array() ) ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Enter one slug per line or separate them with commas. The bar will be hidden when the current request matches any of these slugs.', 'wfcb' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="wfcb_position"><?php esc_html_e( 'Position', 'wfcb' ); ?></label>
					</th>
					<td>
						<select id="wfcb_position" name="wfcb_settings[position]">
							<option value="left" <?php selected( $options['position'], 'left' ); ?>>
								<?php esc_html_e( 'Left', 'wfcb' ); ?>
							</option>
							<option value="right" <?php selected( $options['position'], 'right' ); ?>>
								<?php esc_html_e( 'Right', 'wfcb' ); ?>
							</option>
							<option value="bottom" <?php selected( $options['position'], 'bottom' ); ?>>
								<?php esc_html_e( 'Bottom (mobile style)', 'wfcb' ); ?>
							</option>
						</select>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Contact items', 'wfcb' ); ?></h2>

			<p><?php esc_html_e( 'Add contact channels (Zalo, WhatsApp, Email, v.v.) và kéo-thả để sắp xếp thứ tự.', 'wfcb' ); ?></p>

			<ul id="wfcb-items-list">
				<?php if ( ! empty( $options['items'] ) && is_array( $options['items'] ) ) : ?>
					<?php foreach ( $options['items'] as $index => $item ) : ?>
						<?php
						$desktop_behavior_value = isset( $item['desktop_behavior'] ) ? $item['desktop_behavior'] : 'link';
						if ( ! in_array( $desktop_behavior_value, array( 'link', 'qr_page' ), true ) ) {
							$desktop_behavior_value = 'link';
						}
						?>
						<li class="wfcb-item">
							<div class="wfcb-item-inner">
								<div class="wfcb-item-header">
									<span class="wfcb-item-handle">☰</span>
									<div class="wfcb-item-header-actions">
										<button type="button" class="button-link-delete wfcb-remove-item">
											<?php esc_html_e( 'Remove', 'wfcb' ); ?>
										</button>
									</div>
								</div>

								<div class="wfcb-item-grid wfcb-item-grid--main">
									<div class="wfcb-field-group">
										<label>
											<?php esc_html_e( 'Label', 'wfcb' ); ?>
											<input
												type="text"
												name="<?php echo esc_attr( "wfcb_settings[items][{$index}][label]" ); ?>"
												value="<?php echo esc_attr( isset( $item['label'] ) ? $item['label'] : '' ); ?>"
											/>
										</label>
									</div>

									<div class="wfcb-field-group">
										<label>
											<?php esc_html_e( 'Link URL', 'wfcb' ); ?>
											<input
												type="text"
												name="<?php echo esc_attr( "wfcb_settings[items][{$index}][link_url]" ); ?>"
												value="<?php echo esc_attr( isset( $item['link_url'] ) ? $item['link_url'] : '' ); ?>"
											/>
										</label>
									</div>

									<div class="wfcb-field-group">
										<label>
											<?php esc_html_e( 'Icon URL', 'wfcb' ); ?>
											<span class="wfcb-media-field">
												<input
													type="text"
													class="wfcb-media-url"
													name="<?php echo esc_attr( "wfcb_settings[items][{$index}][icon_url]" ); ?>"
													value="<?php echo esc_attr( isset( $item['icon_url'] ) ? $item['icon_url'] : '' ); ?>"
												/>
												<button type="button" class="button wfcb-upload-media"><?php esc_html_e( 'Choose', 'wfcb' ); ?></button>
											</span>
										</label>
									</div>
								</div>

								<details class="wfcb-item-advanced" <?php echo ( isset( $item['app_key'] ) && $item['app_key'] ) || ( isset( $item['qr_image_url'] ) && $item['qr_image_url'] ) ? 'open' : ''; ?>>
									<summary><?php esc_html_e( 'QR Cards (advanced)', 'wfcb' ); ?></summary>

									<div class="wfcb-item-grid wfcb-item-grid--advanced">
										<div class="wfcb-field-group">
											<label>
												<?php esc_html_e( 'App key', 'wfcb' ); ?>
												<input
													type="text"
													name="<?php echo esc_attr( "wfcb_settings[items][{$index}][app_key]" ); ?>"
													value="<?php echo esc_attr( isset( $item['app_key'] ) ? $item['app_key'] : '' ); ?>"
													placeholder="line / zalo / viber"
												/>
											</label>
										</div>

										<div class="wfcb-field-group">
											<label>
												<?php esc_html_e( 'Handle / ID', 'wfcb' ); ?>
												<input
													type="text"
													name="<?php echo esc_attr( "wfcb_settings[items][{$index}][handle]" ); ?>"
													value="<?php echo esc_attr( isset( $item['handle'] ) ? $item['handle'] : '' ); ?>"
													placeholder="@yourusername"
												/>
											</label>
										</div>

										<div class="wfcb-field-group">
											<label>
												<?php esc_html_e( 'Accent color', 'wfcb' ); ?>
												<input
													type="color"
													name="<?php echo esc_attr( "wfcb_settings[items][{$index}][accent_color]" ); ?>"
													value="<?php echo esc_attr( isset( $item['accent_color'] ) && $item['accent_color'] ? $item['accent_color'] : '#0ea5e9' ); ?>"
												/>
											</label>
										</div>

										<div class="wfcb-field-group">
											<label>
												<?php esc_html_e( 'Desktop behavior', 'wfcb' ); ?>
												<select name="<?php echo esc_attr( "wfcb_settings[items][{$index}][desktop_behavior]" ); ?>">
													<option value="link" <?php selected( $desktop_behavior_value, 'link' ); ?>>
														<?php esc_html_e( 'Open link', 'wfcb' ); ?>
													</option>
													<option value="qr_page" <?php selected( $desktop_behavior_value, 'qr_page' ); ?>>
														<?php esc_html_e( 'Go to /quet-ma-qr (hash by app key)', 'wfcb' ); ?>
													</option>
												</select>
											</label>
										</div>

										<div class="wfcb-field-group">
											<label>
												<?php esc_html_e( 'QR Image URL', 'wfcb' ); ?>
												<span class="wfcb-media-field">
													<input
														type="text"
														class="wfcb-media-url"
														name="<?php echo esc_attr( "wfcb_settings[items][{$index}][qr_image_url]" ); ?>"
														value="<?php echo esc_attr( isset( $item['qr_image_url'] ) ? $item['qr_image_url'] : '' ); ?>"
													/>
													<button type="button" class="button wfcb-upload-media"><?php esc_html_e( 'Choose', 'wfcb' ); ?></button>
												</span>
											</label>
										</div>

										<div class="wfcb-field-group">
											<label>
												<?php esc_html_e( 'Scan hint', 'wfcb' ); ?>
												<input
													type="text"
													name="<?php echo esc_attr( "wfcb_settings[items][{$index}][scan_hint]" ); ?>"
													value="<?php echo esc_attr( isset( $item['scan_hint'] ) ? $item['scan_hint'] : '' ); ?>"
													placeholder="<?php echo esc_attr__( 'Quét mã để kết bạn', 'wfcb' ); ?>"
												/>
											</label>
										</div>
									</div>
								</details>
							</div>
						</li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>

			<p>
				<button type="button" class="button" id="wfcb-add-item">
					<?php esc_html_e( 'Add item', 'wfcb' ); ?>
				</button>
			</p>

			<script type="text/html" id="tmpl-wfcb-item">
				<?php echo wp_kses_post( wfcb_get_item_template() ); ?>
			</script>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
