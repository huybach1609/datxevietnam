<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MTTF_Operator {
	const META_REGION           = '_mttf_operator_region';
	const META_GALLERY_IDS     = '_mttf_operator_gallery_ids';
	const META_LEAD_EMAIL      = '_mttf_operator_lead_email';
	const META_TELEGRAM_CHAT_ID = '_mttf_operator_telegram_chat_id';
	const META_PRIORITY        = '_mttf_operator_priority';
	const META_IS_ACTIVE       = '_mttf_operator_is_active';
	const META_SUMMARY         = '_mttf_operator_summary';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
		add_action( 'save_post_mttf_operator', array( __CLASS__, 'save_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_filter( 'manage_mttf_operator_posts_columns', array( __CLASS__, 'set_admin_columns' ) );
		add_action( 'manage_mttf_operator_posts_custom_column', array( __CLASS__, 'render_admin_column' ), 10, 2 );
	}

	public static function enqueue_admin_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'mttf_operator' !== $screen->post_type ) {
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

	public static function register_post_type() {
		$labels = array(
			'name'               => 'Nhà xe',
			'singular_name'      => 'Nhà xe',
			'menu_name'          => 'Nhà xe',
			'name_admin_bar'     => 'Nhà xe',
			'add_new'            => 'Thêm mới',
			'add_new_item'       => 'Thêm nhà xe',
			'new_item'           => 'Nhà xe mới',
			'edit_item'          => 'Sửa nhà xe',
			'view_item'          => 'Xem nhà xe',
			'all_items'          => 'Tất cả nhà xe',
			'search_items'       => 'Tìm nhà xe',
			'not_found'          => 'Không tìm thấy nhà xe',
			'not_found_in_trash' => 'Không có nhà xe trong thùng rác',
		);

		register_post_type(
			'mttf_operator',
			array(
				'labels'              => $labels,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=tuyen_xe',
				'show_in_nav_menus'   => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'supports'            => array( 'title', 'thumbnail' ),
				'menu_icon'           => 'dashicons-groups',
			)
		);
	}

	public static function register_metabox() {
		add_meta_box(
			'mttf_operator_info',
			'Thông tin nhà xe',
			array( __CLASS__, 'render_metabox' ),
			'mttf_operator',
			'normal',
			'high'
		);
	}

	public static function render_metabox( $post ) {
		wp_nonce_field( 'mttf_save_operator_info', 'mttf_operator_nonce' );

		$values = self::get_values( $post->ID );
		?>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th><label for="mttf_operator_summary">Mô tả ngắn</label></th>
				<td>
					<textarea name="mttf_operator_summary" id="mttf_operator_summary" rows="3" class="large-text"><?php echo esc_textarea( $values['summary'] ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th><label for="mttf_operator_region">Khu vực hoạt động</label></th>
				<td>
					<select name="mttf_operator_region" id="mttf_operator_region">
						<option value="" <?php selected( $values['region'], '' ); ?>>Tất cả</option>
						<option value="bac" <?php selected( $values['region'], 'bac' ); ?>>Bắc</option>
						<option value="trung" <?php selected( $values['region'], 'trung' ); ?>>Trung</option>
						<option value="nam" <?php selected( $values['region'], 'nam' ); ?>>Nam</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="mttf_operator_gallery_ids">Gallery ảnh</label></th>
				<td>
					<input type="hidden" name="mttf_operator_gallery_ids" id="mttf_operator_gallery_ids" value="<?php echo esc_attr( $values['gallery_ids'] ); ?>" />
					<button type="button" class="button" id="mttf_operator_select_gallery">Chọn ảnh gallery</button>
					<button type="button" class="button-link-delete" id="mttf_operator_clear_gallery">Xóa tất cả</button>
					<p class="description">Logo dùng Ảnh đại diện. Gallery dùng cho hero hoặc ảnh bổ sung của nhà xe.</p>
					<div id="mttf_operator_gallery_preview" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
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
				<th><label for="mttf_operator_lead_email">Email lead mặc định</label></th>
				<td>
					<input name="mttf_operator_lead_email" id="mttf_operator_lead_email" type="text" class="regular-text" value="<?php echo esc_attr( $values['lead_email'] ); ?>" placeholder="sale@example.com, team@example.com" />
				</td>
			</tr>
			<tr>
				<th><label for="mttf_operator_telegram_chat_id">Telegram Chat ID mặc định</label></th>
				<td><input name="mttf_operator_telegram_chat_id" id="mttf_operator_telegram_chat_id" type="text" class="regular-text" value="<?php echo esc_attr( $values['telegram_chat_id'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mttf_operator_priority">Ưu tiên</label></th>
				<td><input name="mttf_operator_priority" id="mttf_operator_priority" type="number" min="0" step="1" value="<?php echo esc_attr( $values['priority'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mttf_operator_is_active">Đang kích hoạt</label></th>
				<td><input type="checkbox" id="mttf_operator_is_active" name="mttf_operator_is_active" value="1" <?php checked( $values['is_active'], 1 ); ?> /></td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	public static function save_metabox( $post_id ) {
		if ( ! isset( $_POST['mttf_operator_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mttf_operator_nonce'] ) ), 'mttf_save_operator_info' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, self::META_SUMMARY, sanitize_textarea_field( wp_unslash( $_POST['mttf_operator_summary'] ?? '' ) ) );
		update_post_meta( $post_id, self::META_REGION, self::sanitize_region( wp_unslash( $_POST['mttf_operator_region'] ?? '' ) ) );
		update_post_meta( $post_id, self::META_GALLERY_IDS, self::sanitize_gallery_ids( wp_unslash( $_POST['mttf_operator_gallery_ids'] ?? '' ) ) );
		update_post_meta( $post_id, self::META_LEAD_EMAIL, self::sanitize_lead_emails_field( wp_unslash( $_POST['mttf_operator_lead_email'] ?? '' ) ) );
		update_post_meta( $post_id, self::META_TELEGRAM_CHAT_ID, sanitize_text_field( wp_unslash( $_POST['mttf_operator_telegram_chat_id'] ?? '' ) ) );
		update_post_meta( $post_id, self::META_PRIORITY, absint( $_POST['mttf_operator_priority'] ?? 0 ) );
		update_post_meta( $post_id, self::META_IS_ACTIVE, isset( $_POST['mttf_operator_is_active'] ) ? 1 : 0 );
	}

	public static function set_admin_columns( $columns ) {
		$title = isset( $columns['title'] ) ? $columns['title'] : 'Tiêu đề';
		$date  = isset( $columns['date'] ) ? $columns['date'] : 'Ngày';

		return array(
			'cb'                  => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'mttf_operator_logo'   => 'Logo',
			'title'               => $title,
			'mttf_operator_region' => 'Khu vực',
			'mttf_operator_routes' => 'Số tuyến',
			'mttf_operator_priority' => 'Ưu tiên',
			'mttf_operator_active' => 'Kích hoạt',
			'date'                => $date,
		);
	}

	public static function render_admin_column( $column, $post_id ) {
		if ( 'mttf_operator_region' === $column ) {
			$region = (string) get_post_meta( $post_id, self::META_REGION, true );
			echo esc_html( self::get_region_label( $region ) );
			return;
		}

		if ( 'mttf_operator_logo' === $column ) {
			$thumb = get_the_post_thumbnail( $post_id, array( 48, 48 ) );
			echo '' !== $thumb ? $thumb : '-';
			return;
		}

		if ( 'mttf_operator_routes' === $column ) {
			echo esc_html( (string) count( MTTF_Route_Operators::get_operator_routes( $post_id, false ) ) );
			return;
		}

		if ( 'mttf_operator_priority' === $column ) {
			echo esc_html( (string) (int) get_post_meta( $post_id, self::META_PRIORITY, true ) );
			return;
		}

		if ( 'mttf_operator_active' === $column ) {
			echo (int) get_post_meta( $post_id, self::META_IS_ACTIVE, true ) === 1 ? 'Có' : 'Không';
		}
	}

	public static function get_values( $post_id ) {
		return array(
			'summary'          => (string) get_post_meta( $post_id, self::META_SUMMARY, true ),
			'region'           => (string) get_post_meta( $post_id, self::META_REGION, true ),
			'gallery_ids'      => (string) get_post_meta( $post_id, self::META_GALLERY_IDS, true ),
			'lead_email'       => (string) get_post_meta( $post_id, self::META_LEAD_EMAIL, true ),
			'telegram_chat_id' => (string) get_post_meta( $post_id, self::META_TELEGRAM_CHAT_ID, true ),
			'priority'         => (int) get_post_meta( $post_id, self::META_PRIORITY, true ),
			'is_active'        => (int) get_post_meta( $post_id, self::META_IS_ACTIVE, true ),
		);
	}

	public static function get_operator_choices( $active_only = false ) {
		$posts = get_posts(
			array(
				'post_type'      => 'mttf_operator',
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$choices = array();
		foreach ( $posts as $post ) {
			$values = self::get_values( $post->ID );
			if ( $active_only && 1 !== (int) $values['is_active'] ) {
				continue;
			}

			$label = get_the_title( $post );
			if ( 1 !== (int) $values['is_active'] ) {
				$label .= ' (Tắt)';
			}

			$choices[] = array(
				'id'       => (int) $post->ID,
				'label'    => $label,
				'priority' => (int) $values['priority'],
				'active'   => (int) $values['is_active'],
			);
		}

		usort(
			$choices,
			static function ( $a, $b ) {
				if ( $a['priority'] === $b['priority'] ) {
					return strcasecmp( $a['label'], $b['label'] );
				}

				return $a['priority'] <=> $b['priority'];
			}
		);

		return $choices;
	}

	public static function get_operator_defaults( $operator_id ) {
		$operator_id = absint( $operator_id );
		if ( $operator_id <= 0 || 'mttf_operator' !== get_post_type( $operator_id ) ) {
			return array();
		}

		$values = self::get_values( $operator_id );

		return array(
			'operator_id'       => $operator_id,
			'operator_name'     => get_the_title( $operator_id ),
			'gallery_ids'       => $values['gallery_ids'],
			'lead_email'        => $values['lead_email'],
			'telegram_chat_id'  => $values['telegram_chat_id'],
			'priority'          => $values['priority'],
			'is_active'         => $values['is_active'],
			'region'            => $values['region'],
			'summary'           => $values['summary'],
		);
	}

	public static function get_gallery_image_urls( $operator_id, $size = 'large' ) {
		$values = self::get_values( $operator_id );
		$urls   = array();

		foreach ( self::parse_gallery_ids( $values['gallery_ids'] ) as $image_id ) {
			$url = wp_get_attachment_image_url( $image_id, $size );
			if ( $url ) {
				$urls[] = (string) $url;
			}
		}

		return array_values( array_unique( $urls ) );
	}

	public static function get_region_label( $region ) {
		if ( 'bac' === $region ) {
			return 'Bắc';
		}

		if ( 'trung' === $region ) {
			return 'Trung';
		}

		if ( 'nam' === $region ) {
			return 'Nam';
		}

		return 'Tất cả';
	}

	private static function sanitize_region( $value ) {
		$region = sanitize_text_field( (string) $value );
		if ( in_array( $region, array( 'bac', 'trung', 'nam' ), true ) ) {
			return $region;
		}

		return '';
	}

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

	private static function parse_gallery_ids( $raw_ids ) {
		if ( ! is_string( $raw_ids ) || '' === trim( $raw_ids ) ) {
			return array();
		}

		$ids = array_map( 'absint', array_filter( array_map( 'trim', explode( ',', $raw_ids ) ) ) );
		return array_values( array_filter( array_unique( $ids ) ) );
	}

	private static function sanitize_gallery_ids( $raw_ids ) {
		$ids = self::parse_gallery_ids( (string) $raw_ids );
		return ! empty( $ids ) ? implode( ',', $ids ) : '';
	}
}
