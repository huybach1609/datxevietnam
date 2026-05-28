<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MTTF_Metabox {
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
		add_action( 'save_post_tuyen_xe', array( __CLASS__, 'save_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	public static function enqueue_admin_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'tuyen_xe' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'mttf-admin-metabox',
			MTTF_URL . 'assets/js/admin-metabox.js',
			array( 'jquery' ),
			MTTF_VERSION,
			true
		);
	}

	public static function register_metabox() {
		add_meta_box(
			'mttf_route_info',
			'Thông tin tuyến xe',
			array( __CLASS__, 'render_metabox' ),
			'tuyen_xe',
			'normal',
			'high'
		);
	}

	public static function render_metabox( $post ) {
		wp_nonce_field( 'mttf_save_route_info', 'mttf_nonce' );

		$values = self::get_values( $post->ID );
		$features = array(
			'don_tra_tan_noi'                 => 'Đón trả tận nơi',
			'don_tra_linh_hoat'               => 'Đón trả linh hoạt',
			'mien_phi_nuoc_loc_khan_lanh'     => 'Miễn phí nước lọc & khăn lạnh',
			'ghe_massage_boc_da_cao_cap'      => 'Ghế Massage bọc da cao cấp',
			'cabin_rieng_tu'                  => 'Cabin riêng tư',
			'wifi_cong_sac_usb'               => 'Wifi tốc độ cao, Cổng sạc USB & Type C',
			'chan_goi_sach_se'                => 'Chăn gối sạch sẽ',
			'chay_cao_toc_100'                => 'Chạy cao tốc 100%',
			'khong_bat_khach_doc_duong'       => 'Không bắt khách dọc đường',
			'xe_doi_moi_2025_2026'            => 'Xe đời mới 2025 - 2026',
			'dung_gio_dung_chuyen'            => 'Đúng giờ - Đúng chuyến',
			'bao_hiem_hanh_khach'             => 'Bảo hiểm hành khách',
		);
		$hot_badges = array(
			'dat_nhieu_hom_nay' => 'Đặt nhiều hôm nay',
			'sap_het_cho'       => 'Sắp hết chỗ',
			'dat_xe_viet_nam_chon_loc' => 'Đặt Xe Việt Nam chọn lọc',
		);
		?>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th><label for="mttf_route_slug">Route Slug</label></th>
				<td>
					<input name="mttf_route_slug" id="mttf_route_slug" type="text" class="regular-text" value="<?php echo esc_attr( $values['route_slug'] ); ?>" />
					<p class="description">
						Link ưu tiên tuyến theo slug: <code><?php echo esc_html( home_url( '/?route=' . sanitize_title( (string) $values['route_slug'] ) ) ); ?></code>
					</p>
					<p class="description">Nếu trang chứa shortcode <code>[mttf_hub]</code> không nằm ở trang chủ, hãy thay URL trang đích tương ứng và giữ tham số <code>?route=route-slug</code>.</p>
				</td>
			</tr>
			<tr>
				<th><label for="mttf_hub_region">Khu vực</label></th>
				<td>
					<select name="mttf_hub_region" id="mttf_hub_region">
						<option value="bac" <?php selected( $values['hub_region'], 'bac' ); ?>>Bắc</option>
						<option value="trung" <?php selected( $values['hub_region'], 'trung' ); ?>>Trung</option>
						<option value="nam" <?php selected( $values['hub_region'], 'nam' ); ?>>Nam</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="mttf_price_from">Giá từ (VND)</label></th>
				<td><input name="mttf_price_from" id="mttf_price_from" type="number" min="0" step="1000" value="<?php echo esc_attr( $values['price_from'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mttf_rating_score">Rating</label></th>
				<td><input name="mttf_rating_score" id="mttf_rating_score" type="number" min="0" max="5" step="0.1" value="<?php echo esc_attr( $values['rating_score'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mttf_review_count">Số lượt đánh giá</label></th>
				<td><input name="mttf_review_count" id="mttf_review_count" type="text" class="regular-text" value="<?php echo esc_attr( $values['review_count'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mttf_contact_count">Số lượt liên hệ</label></th>
				<td><input name="mttf_contact_count" id="mttf_contact_count" type="text" class="regular-text" value="<?php echo esc_attr( $values['contact_count'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mttf_slider_interval">Thời gian chuyển ảnh (giây)</label></th>
				<td>
					<input name="mttf_slider_interval" id="mttf_slider_interval" type="number" min="1" max="30" step="1" value="<?php echo esc_attr( $values['slider_interval'] ); ?>" />
					<p class="description">Ví dụ: 3 giây/ảnh. Nếu để trống sẽ dùng mặc định 3 giây.</p>
				</td>
			</tr>
			<tr>
				<th><label for="mttf_gallery_ids">Ảnh bổ sung cho card</label></th>
				<td>
					<input type="hidden" name="mttf_gallery_ids" id="mttf_gallery_ids" value="<?php echo esc_attr( $values['gallery_ids'] ); ?>" />
					<button type="button" class="button" id="mttf_select_gallery">Chọn thêm ảnh</button>
					<button type="button" class="button-link-delete" id="mttf_clear_gallery">Xóa tất cả</button>
					<div id="mttf_gallery_preview" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
						<?php foreach ( self::parse_gallery_ids( $values['gallery_ids'] ) as $image_id ) : ?>
							<?php $thumb = wp_get_attachment_image_url( $image_id, 'thumbnail' ); ?>
							<?php if ( $thumb ) : ?>
								<img src="<?php echo esc_url( $thumb ); ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #ddd;" />
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="mttf_trip_frequency">Tần suất</label></th>
				<td><input name="mttf_trip_frequency" id="mttf_trip_frequency" type="text" class="regular-text" value="<?php echo esc_attr( $values['trip_frequency'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mttf_car_type">Loại xe</label></th>
				<td><input name="mttf_car_type" id="mttf_car_type" type="text" class="regular-text" value="<?php echo esc_attr( $values['car_type'] ); ?>" /></td>
			</tr>
			<tr>
				<th>Route Features</th>
				<td>
					<?php foreach ( $features as $key => $label ) : ?>
						<label style="display:block;margin-bottom:6px;">
							<input type="checkbox" name="mttf_route_features[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $values['route_features'], true ) ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th>Tuyến hot (badge)</th>
				<td>
					<?php foreach ( $hot_badges as $key => $label ) : ?>
						<label style="display:block;margin-bottom:6px;">
							<input type="checkbox" name="mttf_hot_badges[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $values['hot_badges'], true ) ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><label for="mttf_hotline_number">Hotline</label></th>
				<td><input name="mttf_hotline_number" id="mttf_hotline_number" type="text" class="regular-text" value="<?php echo esc_attr( $values['hotline_number'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mttf_zalo_link">Zalo link</label></th>
				<td><input name="mttf_zalo_link" id="mttf_zalo_link" type="url" class="regular-text" value="<?php echo esc_attr( $values['zalo_link'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mttf_search_keywords">Từ khóa phụ</label></th>
				<td><textarea name="mttf_search_keywords" id="mttf_search_keywords" rows="3" class="large-text"><?php echo esc_textarea( $values['search_keywords'] ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="mttf_priority">Ưu tiên</label></th>
				<td><input name="mttf_priority" id="mttf_priority" type="number" min="0" step="1" value="<?php echo esc_attr( $values['priority'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mttf_lead_email">Email nhận lead (theo tuyến)</label></th>
				<td>
					<input name="mttf_lead_email" id="mttf_lead_email" type="text" class="regular-text" value="<?php echo esc_attr( $values['lead_email'] ); ?>" placeholder="sale@example.com, team@example.com" />
					<p class="description">Lead từ form tuyến này sẽ được gửi đến email trên. Có thể nhập nhiều địa chỉ, phân tách bằng dấu phẩy. Để trống sẽ dùng email trong <strong>Cài đặt MTTF → Email nhận lead</strong>.</p>
				</td>
			</tr>
			<tr>
				<th><label for="mttf_telegram_chat_id">Telegram Chat ID</label></th>
				<td><input name="mttf_telegram_chat_id" id="mttf_telegram_chat_id" type="text" class="regular-text" value="<?php echo esc_attr( $values['telegram_chat_id'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mttf_is_active">Đang kích hoạt</label></th>
				<td><input type="checkbox" id="mttf_is_active" name="mttf_is_active" value="1" <?php checked( $values['is_active'], 1 ); ?> /></td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	public static function save_metabox( $post_id ) {
		if ( ! isset( $_POST['mttf_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mttf_nonce'] ) ), 'mttf_save_route_info' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_mttf_route_slug', sanitize_title( wp_unslash( $_POST['mttf_route_slug'] ?? '' ) ) );
		update_post_meta( $post_id, '_mttf_hub_region', sanitize_text_field( wp_unslash( $_POST['mttf_hub_region'] ?? 'bac' ) ) );
		update_post_meta( $post_id, '_mttf_price_from', absint( $_POST['mttf_price_from'] ?? 0 ) );
		update_post_meta( $post_id, '_mttf_rating_score', (float) ( $_POST['mttf_rating_score'] ?? 0 ) );
		update_post_meta( $post_id, '_mttf_review_count', sanitize_text_field( wp_unslash( $_POST['mttf_review_count'] ?? '' ) ) );
		update_post_meta( $post_id, '_mttf_contact_count', sanitize_text_field( wp_unslash( $_POST['mttf_contact_count'] ?? '' ) ) );
		update_post_meta( $post_id, '_mttf_slider_interval', absint( $_POST['mttf_slider_interval'] ?? 3 ) );
		update_post_meta( $post_id, '_mttf_gallery_ids', self::sanitize_gallery_ids( wp_unslash( $_POST['mttf_gallery_ids'] ?? '' ) ) );
		update_post_meta( $post_id, '_mttf_trip_frequency', sanitize_text_field( wp_unslash( $_POST['mttf_trip_frequency'] ?? '' ) ) );
		update_post_meta( $post_id, '_mttf_car_type', sanitize_text_field( wp_unslash( $_POST['mttf_car_type'] ?? '' ) ) );
		update_post_meta( $post_id, '_mttf_hotline_number', sanitize_text_field( wp_unslash( $_POST['mttf_hotline_number'] ?? '' ) ) );
		update_post_meta( $post_id, '_mttf_zalo_link', esc_url_raw( wp_unslash( $_POST['mttf_zalo_link'] ?? '' ) ) );
		update_post_meta( $post_id, '_mttf_search_keywords', sanitize_textarea_field( wp_unslash( $_POST['mttf_search_keywords'] ?? '' ) ) );
		update_post_meta( $post_id, '_mttf_priority', absint( $_POST['mttf_priority'] ?? 0 ) );
		update_post_meta( $post_id, '_mttf_lead_email', self::sanitize_lead_emails_field( wp_unslash( $_POST['mttf_lead_email'] ?? '' ) ) );
		update_post_meta( $post_id, '_mttf_telegram_chat_id', sanitize_text_field( wp_unslash( $_POST['mttf_telegram_chat_id'] ?? '' ) ) );
		update_post_meta( $post_id, '_mttf_is_active', isset( $_POST['mttf_is_active'] ) ? 1 : 0 );

		$features = $_POST['mttf_route_features'] ?? array();
		$features = is_array( $features ) ? array_map( 'sanitize_text_field', wp_unslash( $features ) ) : array();
		update_post_meta( $post_id, '_mttf_route_features', array_values( array_unique( $features ) ) );

		$hot_badges = $_POST['mttf_hot_badges'] ?? array();
		$hot_badges = is_array( $hot_badges ) ? array_map( 'sanitize_text_field', wp_unslash( $hot_badges ) ) : array();
		update_post_meta( $post_id, '_mttf_hot_badges', array_values( array_unique( $hot_badges ) ) );
	}

	private static function get_values( $post_id ) {
		return array(
			'route_slug'       => get_post_meta( $post_id, '_mttf_route_slug', true ),
			'hub_region'       => get_post_meta( $post_id, '_mttf_hub_region', true ) ?: 'bac',
			'price_from'       => get_post_meta( $post_id, '_mttf_price_from', true ),
			'rating_score'     => get_post_meta( $post_id, '_mttf_rating_score', true ),
			'review_count'     => get_post_meta( $post_id, '_mttf_review_count', true ),
			'contact_count'    => get_post_meta( $post_id, '_mttf_contact_count', true ),
			'slider_interval'  => (int) get_post_meta( $post_id, '_mttf_slider_interval', true ) ?: 3,
			'gallery_ids'      => get_post_meta( $post_id, '_mttf_gallery_ids', true ),
			'trip_frequency'   => get_post_meta( $post_id, '_mttf_trip_frequency', true ),
			'car_type'         => get_post_meta( $post_id, '_mttf_car_type', true ),
			'route_features'   => (array) get_post_meta( $post_id, '_mttf_route_features', true ),
			'hot_badges'       => (array) get_post_meta( $post_id, '_mttf_hot_badges', true ),
			'hotline_number'   => get_post_meta( $post_id, '_mttf_hotline_number', true ),
			'zalo_link'        => get_post_meta( $post_id, '_mttf_zalo_link', true ),
			'search_keywords'  => get_post_meta( $post_id, '_mttf_search_keywords', true ),
			'priority'         => get_post_meta( $post_id, '_mttf_priority', true ),
			'lead_email'       => get_post_meta( $post_id, '_mttf_lead_email', true ),
			'telegram_chat_id' => get_post_meta( $post_id, '_mttf_telegram_chat_id', true ),
			'is_active'        => (int) get_post_meta( $post_id, '_mttf_is_active', true ),
		);
	}

	private static function parse_gallery_ids( $raw_ids ) {
		if ( ! is_string( $raw_ids ) || '' === trim( $raw_ids ) ) {
			return array();
		}

		$parts = array_map( 'trim', explode( ',', $raw_ids ) );
		$parts = array_filter( $parts, 'strlen' );
		return array_map( 'absint', $parts );
	}

	private static function sanitize_gallery_ids( $raw_ids ) {
		$ids = self::parse_gallery_ids( (string) $raw_ids );
		$ids = array_filter( $ids );
		$ids = array_values( array_unique( $ids ) );
		return implode( ',', $ids );
	}

	/**
	 * Comma-separated list of recipient emails for route leads.
	 *
	 * @param string $raw Raw POST value.
	 * @return string Sanitized comma-separated emails or empty string.
	 */
	private static function sanitize_lead_emails_field( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}

		$parts = array_map( 'trim', explode( ',', $raw ) );
		$clean = array();
		foreach ( $parts as $part ) {
			$email = sanitize_email( $part );
			if ( $email && is_email( $email ) ) {
				$clean[] = $email;
			}
		}

		return ! empty( $clean ) ? implode( ',', array_unique( $clean ) ) : '';
	}
}
