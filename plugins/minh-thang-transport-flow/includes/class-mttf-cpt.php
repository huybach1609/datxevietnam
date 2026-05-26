<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MTTF_CPT {
	const POST_TYPE         = 'bai_xe';
	const LEGACY_POST_TYPE  = 'tuyen_xe';
	const ROUTE_POST_TYPE   = 'tuyen_xe';
	const MIGRATION_VERSION = '20260525_bai_xe';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_migrate_post_types' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'set_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_admin_column' ), 10, 2 );
		add_filter( 'manage_' . self::ROUTE_POST_TYPE . '_posts_columns', array( __CLASS__, 'set_route_columns' ) );
		add_action( 'manage_' . self::ROUTE_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_route_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'add_duplicate_action' ), 10, 2 );
		add_action( 'admin_action_mttf_duplicate_route', array( __CLASS__, 'handle_duplicate_action' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ) );
	}

	public static function maybe_migrate_post_types() {
		if ( self::MIGRATION_VERSION === get_option( 'mttf_post_type_migration_version' ) ) {
			return;
		}

		global $wpdb;

		$legacy_ids = get_posts(
			array(
				'post_type'      => self::LEGACY_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $legacy_ids ) ) {
			$wpdb->update(
				$wpdb->posts,
				array( 'post_type' => self::POST_TYPE ),
				array( 'post_type' => self::LEGACY_POST_TYPE )
			);

			foreach ( $legacy_ids as $legacy_id ) {
				clean_post_cache( (int) $legacy_id );
			}
		}

		self::seed_route_posts_from_articles();
		update_option( 'mttf_post_type_migration_version', self::MIGRATION_VERSION, false );
	}

	public static function register_post_types() {
		$labels = array(
			'name'               => 'Bài xe',
			'singular_name'      => 'Bài xe',
			'menu_name'          => 'Minh Thang Flow',
			'name_admin_bar'     => 'Bài xe',
			'add_new'            => 'Thêm mới',
			'add_new_item'       => 'Thêm bài xe',
			'new_item'           => 'Bài xe mới',
			'edit_item'          => 'Sửa bài xe',
			'view_item'          => 'Xem bài xe',
			'all_items'          => 'Tất cả bài xe',
			'search_items'       => 'Tìm bài xe',
			'not_found'          => 'Không tìm thấy bài xe',
			'not_found_in_trash' => 'Không có bài xe trong thùng rác',
		);

		register_post_type(
			self::POST_TYPE,
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

		register_post_type(
			self::ROUTE_POST_TYPE,
			array(
				'labels' => array(
					'name'               => 'Tuyến xe',
					'singular_name'      => 'Tuyến xe',
					'menu_name'          => 'Tuyến xe',
					'name_admin_bar'     => 'Tuyến xe',
					'add_new'            => 'Thêm mới',
					'add_new_item'       => 'Thêm tuyến xe',
					'new_item'           => 'Tuyến xe mới',
					'edit_item'          => 'Sửa tuyến xe',
					'view_item'          => 'Xem tuyến xe',
					'all_items'          => 'Tất cả tuyến xe',
					'search_items'       => 'Tìm tuyến xe',
					'not_found'          => 'Không tìm thấy tuyến xe',
					'not_found_in_trash' => 'Không có tuyến xe trong thùng rác',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=' . self::POST_TYPE,
				'show_in_nav_menus'   => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'supports'            => array( 'title' ),
				'menu_icon'           => 'dashicons-location',
			)
		);
	}

	public static function set_admin_columns( $columns ) {
		$title = isset( $columns['title'] ) ? $columns['title'] : 'Tiêu đề';
		$date  = isset( $columns['date'] ) ? $columns['date'] : 'Ngày';

		return array(
			'cb'                 => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'              => $title,
			'mttf_truyen_xe'     => 'Tuyến xe',
			'mttf_route_post'    => 'Chọn tuyến',
			'mttf_region'        => 'Khu vực',
			'mttf_price'         => 'Giá từ',
			'mttf_hotline'       => 'Hotline',
			'mttf_priority'      => 'Ưu tiên',
			'mttf_active'        => 'Kích hoạt',
			'date'               => $date,
		);
	}

	public static function set_route_columns( $columns ) {
		$title = isset( $columns['title'] ) ? $columns['title'] : 'Tiêu đề';
		$date  = isset( $columns['date'] ) ? $columns['date'] : 'Ngày';

		return array(
			'cb'              => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'           => $title,
			'mttf_route_slug' => 'Route Slug',
			'date'            => $date,
		);
	}

	public static function render_route_column( $column, $post_id ) {
		if ( 'mttf_route_slug' !== $column ) {
			return;
		}

		echo esc_html( self::get_route_slug_from_route_post( $post_id ) );
	}

	public static function render_admin_column( $column, $post_id ) {
		if ( 'mttf_truyen_xe' === $column ) {
			$value = (string) get_post_meta( $post_id, '_mttf_truyen_xe', true );
			echo '' !== $value ? esc_html( $value ) : '-';
			return;
		}

		if ( 'mttf_route_post' === $column ) {
			$route_id = (int) get_post_meta( $post_id, '_mttf_selected_route_id', true );
			echo $route_id > 0 ? esc_html( get_the_title( $route_id ) ) : '-';
			return;
		}
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
		if ( self::POST_TYPE !== $post->post_type || ! current_user_can( 'edit_posts' ) ) {
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
		if ( ! $source_post || self::POST_TYPE !== $source_post->post_type ) {
			wp_die( 'Không tìm thấy tuyến cần nhân bản.' );
		}

		$new_post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
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
					'post_type' => self::POST_TYPE,
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

		if ( self::POST_TYPE !== sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) {
			return;
		}

		if ( '1' !== sanitize_text_field( wp_unslash( $_GET['duplicated'] ) ) ) {
			return;
		}

		echo '<div class="notice notice-success is-dismissible"><p>Nhân bản tuyến thành công. Bản sao đang ở trạng thái nháp.</p></div>';
	}

	public static function get_article_post_type() {
		return self::POST_TYPE;
	}

	public static function get_route_post_type() {
		return self::ROUTE_POST_TYPE;
	}

	public static function get_route_slug_from_route_post( $route_id ) {
		$route_id = absint( $route_id );
		if ( $route_id <= 0 ) {
			return '';
		}

		$meta_slug = (string) get_post_meta( $route_id, '_mttf_route_slug', true );
		if ( '' !== $meta_slug ) {
			return sanitize_title( $meta_slug );
		}

		return sanitize_title( (string) get_post_field( 'post_name', $route_id ) );
	}

	private static function seed_route_posts_from_articles() {
		$articles = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		if ( empty( $articles ) ) {
			return;
		}

		$route_index = array();
		$route_posts = get_posts(
			array(
				'post_type'      => self::ROUTE_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		foreach ( $route_posts as $route_post ) {
			$route_slug = self::get_route_slug_from_route_post( $route_post->ID );
			if ( '' !== $route_slug ) {
				$route_index[ $route_slug ] = (int) $route_post->ID;
			}
		}

		foreach ( $articles as $article ) {
			$article_id = (int) $article->ID;
			$route_slug = sanitize_title( (string) get_post_meta( $article_id, '_mttf_route_slug', true ) );
			if ( '' === $route_slug ) {
				$route_slug = sanitize_title( (string) $article->post_name );
				update_post_meta( $article_id, '_mttf_route_slug', $route_slug );
			}

			if ( '' === $route_slug ) {
				continue;
			}

			if ( ! isset( $route_index[ $route_slug ] ) ) {
				$route_title = (string) get_post_meta( $article_id, '_mttf_truyen_xe', true );
				if ( '' === $route_title ) {
					$route_title = (string) get_the_title( $article_id );
				}

				$route_post_id = wp_insert_post(
					array(
						'post_type'   => self::ROUTE_POST_TYPE,
						'post_status' => 'publish',
						'post_title'  => $route_title,
						'post_name'   => $route_slug,
					)
				);

				if ( ! is_wp_error( $route_post_id ) && $route_post_id > 0 ) {
					update_post_meta( $route_post_id, '_mttf_route_slug', $route_slug );
					$route_index[ $route_slug ] = (int) $route_post_id;
				}
			}

			if ( isset( $route_index[ $route_slug ] ) && (int) get_post_meta( $article_id, '_mttf_selected_route_id', true ) <= 0 ) {
				update_post_meta( $article_id, '_mttf_selected_route_id', (int) $route_index[ $route_slug ] );
			}

			if ( '' === (string) get_post_meta( $article_id, '_mttf_truyen_xe', true ) && isset( $route_index[ $route_slug ] ) ) {
				update_post_meta( $article_id, '_mttf_truyen_xe', (string) get_the_title( (int) $route_index[ $route_slug ] ) );
			}

			if ( (int) get_post_meta( $article_id, '_mttf_selected_operator_id', true ) <= 0 && class_exists( 'MTTF_Route_Operators' ) ) {
				$operator_rows = MTTF_Route_Operators::get_route_operator_rows( $article_id, false );
				if ( 1 === count( $operator_rows ) && ! empty( $operator_rows[0]['operator_id'] ) ) {
					update_post_meta( $article_id, '_mttf_selected_operator_id', (int) $operator_rows[0]['operator_id'] );
				}
			}
		}
	}
}
