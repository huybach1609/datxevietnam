<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MTTF_CPT {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_filter( 'manage_tuyen_xe_posts_columns', array( __CLASS__, 'set_admin_columns' ) );
		add_action( 'manage_tuyen_xe_posts_custom_column', array( __CLASS__, 'render_admin_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'add_duplicate_action' ), 10, 2 );
		add_action( 'admin_action_mttf_duplicate_route', array( __CLASS__, 'handle_duplicate_action' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ) );
	}

	public static function register_post_type() {
		$labels = array(
			'name'               => 'Tuyến xe',
			'singular_name'      => 'Tuyến xe',
			'menu_name'          => 'Minh Thang Flow',
			'name_admin_bar'     => 'Tuyến xe',
			'add_new'            => 'Thêm mới',
			'add_new_item'       => 'Thêm tuyến xe',
			'new_item'           => 'Tuyến xe mới',
			'edit_item'          => 'Sửa tuyến xe',
			'view_item'          => 'Xem tuyến xe',
			'all_items'          => 'Tất cả tuyến',
			'search_items'       => 'Tìm tuyến',
			'not_found'          => 'Không tìm thấy tuyến',
			'not_found_in_trash' => 'Không có tuyến trong thùng rác',
		);

		register_post_type(
			'tuyen_xe',
			array(
				'labels'              => $labels,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'supports'            => array( 'title', 'thumbnail' ),
				'menu_icon'           => 'dashicons-location-alt',
			)
		);
	}

	public static function set_admin_columns( $columns ) {
		$columns['mttf_region']   = 'Khu vực';
		$columns['mttf_price']    = 'Giá từ';
		$columns['mttf_hotline']  = 'Hotline';
		$columns['mttf_priority'] = 'Ưu tiên';
		$columns['mttf_active']   = 'Kích hoạt';

		return $columns;
	}

	public static function render_admin_column( $column, $post_id ) {
		if ( 'mttf_region' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_mttf_hub_region', true ) );
			return;
		}

		if ( 'mttf_price' === $column ) {
			$price = (int) get_post_meta( $post_id, '_mttf_price_from', true );
			echo $price > 0 ? esc_html( number_format_i18n( $price ) . ' VND' ) : '-';
			return;
		}

		if ( 'mttf_hotline' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_mttf_hotline_number', true ) );
			return;
		}

		if ( 'mttf_priority' === $column ) {
			echo esc_html( (string) (int) get_post_meta( $post_id, '_mttf_priority', true ) );
			return;
		}

		if ( 'mttf_active' === $column ) {
			echo get_post_meta( $post_id, '_mttf_is_active', true ) ? 'Có' : 'Không';
		}
	}

	public static function add_duplicate_action( $actions, $post ) {
		if ( 'tuyen_xe' !== $post->post_type || ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin.php?action=mttf_duplicate_route&post=' . $post->ID ),
			'mttf_duplicate_route_' . $post->ID
		);

		$actions['mttf_duplicate'] = '<a href="' . esc_url( $url ) . '">Nhân bản</a>';

		return $actions;
	}

	public static function handle_duplicate_action() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( 'Bạn không có quyền thực hiện thao tác này.' );
		}

		check_admin_referer( 'mttf_duplicate_route_' . $post_id );

		$source_post = get_post( $post_id );
		if ( ! $source_post || 'tuyen_xe' !== $source_post->post_type ) {
			wp_die( 'Không tìm thấy tuyến cần nhân bản.' );
		}

		$new_post_id = wp_insert_post(
			array(
				'post_type'    => 'tuyen_xe',
				'post_status'  => 'draft',
				'post_title'   => $source_post->post_title . ' (Bản sao)',
				'post_content' => $source_post->post_content,
			)
		);

		if ( is_wp_error( $new_post_id ) || ! $new_post_id ) {
			wp_die( 'Không thể nhân bản tuyến.' );
		}

		$meta = get_post_meta( $post_id );
		foreach ( $meta as $meta_key => $values ) {
			if ( '_edit_lock' === $meta_key || '_edit_last' === $meta_key ) {
				continue;
			}

			delete_post_meta( $new_post_id, $meta_key );
			foreach ( $values as $value ) {
				add_post_meta( $new_post_id, $meta_key, maybe_unserialize( $value ) );
			}
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $new_post_id, $thumbnail_id );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => 'tuyen_xe',
					'duplicated' => 1,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	public static function render_admin_notice() {
		if ( ! isset( $_GET['post_type'], $_GET['duplicated'] ) ) {
			return;
		}

		if ( 'tuyen_xe' !== sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) {
			return;
		}

		if ( '1' !== sanitize_text_field( wp_unslash( $_GET['duplicated'] ) ) ) {
			return;
		}

		echo '<div class="notice notice-success is-dismissible"><p>Nhân bản tuyến thành công. Bản sao đang ở trạng thái nháp.</p></div>';
	}
}
