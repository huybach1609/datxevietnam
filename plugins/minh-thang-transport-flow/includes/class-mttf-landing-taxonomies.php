<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Landing hubs: Tuyến (/tuyen/) and Nhà xe (/nha-xe/) for grouping existing tuyen_xe cards.
 */
class MTTF_Landing_Taxonomies {
	const TAX_TUYEN   = 'mttf_tuyen';
	const TAX_NHA_XE  = 'mttf_nha_xe';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 20 );
	}

	public static function register() {
		register_taxonomy(
			self::TAX_TUYEN,
			array( 'tuyen_xe' ),
			array(
				'labels'            => array(
					'name'          => 'Tuyến (landing)',
					'singular_name' => 'Tuyến',
					'menu_name'     => 'Tuyến landing',
					'all_items'     => 'Tất cả tuyến',
					'edit_item'     => 'Sửa tuyến',
					'view_item'     => 'Xem trang tuyến',
					'update_item'   => 'Cập nhật tuyến',
					'add_new_item'  => 'Thêm tuyến mới',
					'new_item_name' => 'Tên tuyến mới',
					'search_items'  => 'Tìm tuyến',
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_tagcloud'     => false,
				'publicly_queryable'=> true,
				'rewrite'           => array(
					'slug'         => 'tuyen',
					'with_front'   => false,
					'hierarchical' => false,
				),
				'show_in_rest'      => true,
			)
		);

		register_taxonomy(
			self::TAX_NHA_XE,
			array( 'tuyen_xe' ),
			array(
				'labels'            => array(
					'name'          => 'Nhà xe (landing)',
					'singular_name' => 'Nhà xe',
					'menu_name'     => 'Nhà xe landing',
					'all_items'     => 'Tất cả nhà xe',
					'edit_item'     => 'Sửa nhà xe',
					'view_item'     => 'Xem trang nhà xe',
					'update_item'   => 'Cập nhật nhà xe',
					'add_new_item'  => 'Thêm nhà xe mới',
					'new_item_name' => 'Tên nhà xe mới',
					'search_items'  => 'Tìm nhà xe',
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_tagcloud'     => false,
				'publicly_queryable'=> true,
				'rewrite'           => array(
					'slug'         => 'nha-xe',
					'with_front'   => false,
					'hierarchical' => false,
				),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Call on plugin activation to register rules before flush.
	 */
	public static function activate() {
		self::register();
		flush_rewrite_rules();
	}
}
