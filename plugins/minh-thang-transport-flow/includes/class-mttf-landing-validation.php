<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Require both landing taxonomies before tuyen_xe can stay published.
 */
class MTTF_Landing_Validation {
	const NOTICE_KEY = 'mttf_landing_publish_notice';

	public static function init() {
		add_action( 'wp_after_insert_post', array( __CLASS__, 'validate_after_insert' ), 20, 4 );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ) );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'render_publish_hint' ) );
	}

	/**
	 * Runs after taxonomies are saved on insert/update.
	 *
	 * @param int           $post_id     Post ID.
	 * @param WP_Post       $post        Post object.
	 * @param bool          $update      Whether this is an existing post being updated.
	 * @param WP_Post|null  $post_before Null on create.
	 */
	public static function validate_after_insert( $post_id, $post, $update, $post_before ) {
		unset( $update, $post_before );

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! $post instanceof WP_Post || 'tuyen_xe' !== $post->post_type ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		if ( self::post_has_required_terms( $post_id ) ) {
			return;
		}

		remove_action( 'wp_after_insert_post', array( __CLASS__, 'validate_after_insert' ), 20 );

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);

		add_action( 'wp_after_insert_post', array( __CLASS__, 'validate_after_insert' ), 20, 4 );

		$user_id = get_current_user_id();
		if ( $user_id ) {
			set_transient(
				self::NOTICE_KEY . '_' . $user_id,
				array(
					'post_id' => $post_id,
					'message' => 'Không thể xuất bản: mỗi dịch vụ phải chọn cả <strong>Tuyến (landing)</strong> và <strong>Nhà xe (landing)</strong>.',
				),
				45
			);
		}
	}

	/**
	 * Hint on tuyen_xe edit screen.
	 */
	public static function render_publish_hint() {
		$screen = get_current_screen();
		if ( ! $screen || 'tuyen_xe' !== $screen->post_type ) {
			return;
		}

		echo '<div class="misc-pub-section mttf-landing-tax-hint">';
		echo '<strong>Tuyến &amp; Nhà xe (landing):</strong> bắt buộc để xuất bản và hiển thị trên <code>/tuyen/</code>, <code>/nha-xe/</code>.';
		echo '</div>';
	}

	/**
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function post_has_required_terms( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id < 1 ) {
			return false;
		}

		$tuyen = wp_get_post_terms( $post_id, MTTF_Landing_Taxonomies::TAX_TUYEN, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $tuyen ) || empty( $tuyen ) ) {
			return false;
		}

		$nha_xe = wp_get_post_terms( $post_id, MTTF_Landing_Taxonomies::TAX_NHA_XE, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $nha_xe ) || empty( $nha_xe ) ) {
			return false;
		}

		return true;
	}

	public static function render_admin_notice() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$data = get_transient( self::NOTICE_KEY . '_' . $user_id );
		if ( ! is_array( $data ) || empty( $data['message'] ) ) {
			return;
		}

		delete_transient( self::NOTICE_KEY . '_' . $user_id );

		echo '<div class="notice notice-error is-dismissible"><p>' . wp_kses_post( (string) $data['message'] ) . '</p></div>';
	}
}
