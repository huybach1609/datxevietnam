<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores recent lead events and serves them for on-site FOMO notifications.
 */
class MTTF_Activity_Pings {
	public const OPTION_KEY = 'mttf_activity_pings_v1';

	private const FETCH_RATE_LIMIT     = 48;
	private const FETCH_RATE_WINDOW    = MINUTE_IN_SECONDS;
	private const STORED_PING_CAP      = 50;
	private const DEFAULT_MAX_AGE_HOURS = 72;

	public static function init() {
		add_action( 'wp_ajax_nopriv_mttf_get_activity_pings', array( __CLASS__, 'ajax_get_pings' ) );
		add_action( 'wp_ajax_mttf_get_activity_pings', array( __CLASS__, 'ajax_get_pings' ) );
	}

	public static function is_enabled() {
		return 1 === (int) MTTF_Settings::get( 'enable_activity_pings', 1 );
	}

	/**
	 * Persist a ping after a successful lead capture.
	 *
	 * @param string $normalized_phone Phone after MTTF_Ajax::normalize_phone().
	 * @param string $route_title      Route title.
	 * @return array|null { id, ts, message } or null if disabled.
	 */
	public static function record_ping( $normalized_phone, $route_title ) {
		if ( ! self::is_enabled() ) {
			return null;
		}

		$masked       = self::mask_phone_for_display( (string) $normalized_phone );
		$route_title  = sanitize_text_field( (string) $route_title );
		$route_title  = html_entity_decode( $route_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $route_title ) > 120 ) {
				$route_title = mb_substr( $route_title, 0, 117 ) . '…';
			}
		} elseif ( strlen( $route_title ) > 120 ) {
			$route_title = substr( $route_title, 0, 117 ) . '…';
		}

		if ( '' === $route_title ) {
			$route_title = 'tuyến được chọn';
		}

		$id = substr( sha1( uniqid( '', true ) . wp_rand() ), 0, 18 );

		$ping = array(
			'id'      => $id,
			'ts'      => time(),
			'message' => sprintf(
				'Vừa xong — SĐT %s vừa gửi liên hệ tuyến %s',
				$masked,
				$route_title
			),
		);

		$list = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $list ) ) {
			$list = array();
		}

		array_unshift( $list, $ping );
		$list = array_slice( $list, 0, self::STORED_PING_CAP );
		update_option( self::OPTION_KEY, $list, false );

		return $ping;
	}

	/**
	 * @param int $limit Max items.
	 * @return array<int, array{id:string,ts:int,message:string}>
	 */
	public static function get_pings_for_response( $limit = 20 ) {
		$list = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $list ) ) {
			return array();
		}

		$max_age_hours = absint( MTTF_Settings::get( 'activity_ping_max_hours', self::DEFAULT_MAX_AGE_HOURS ) );
		if ( $max_age_hours < 6 ) {
			$max_age_hours = 6;
		}
		if ( $max_age_hours > 168 ) {
			$max_age_hours = 168;
		}

		$cutoff = time() - ( $max_age_hours * HOUR_IN_SECONDS );
		$out    = array();
		$limit  = max( 1, min( 40, absint( $limit ) ) );

		foreach ( $list as $ping ) {
			if ( ! is_array( $ping ) || empty( $ping['id'] ) || empty( $ping['message'] ) ) {
				continue;
			}
			$ts = isset( $ping['ts'] ) ? (int) $ping['ts'] : 0;
			if ( $ts < $cutoff ) {
				continue;
			}
			$out[] = array(
				'id'      => (string) $ping['id'],
				'ts'      => $ts,
				'message' => wp_strip_all_tags( (string) $ping['message'] ),
			);
			if ( count( $out ) >= $limit ) {
				break;
			}
		}

		return $out;
	}

	public static function ajax_get_pings() {
		check_ajax_referer( 'mttf_activity_pings', 'nonce' );

		if ( ! self::is_enabled() ) {
			wp_send_json_success( array( 'pings' => array() ) );
		}

		if ( self::is_fetch_rate_limited() ) {
			wp_send_json_error( array( 'message' => 'Too many requests' ), 429 );
		}

		self::mark_fetch_request();

		wp_send_json_success(
			array(
				'pings' => self::get_pings_for_response( 20 ),
			)
		);
	}

	public static function mask_phone_for_display( $phone ) {
		$digits = preg_replace( '/\D+/', '', (string) $phone );
		$len    = strlen( $digits );

		if ( $len < 7 ) {
			return 'Khách ****';
		}

		$take = $len <= 11 ? 3 : 4;
		$prefix = substr( $digits, 0, $take );
		$suffix = substr( $digits, -3 );
		return $prefix . ' **** ' . $suffix;
	}

	private static function get_fetch_rate_key() {
		return 'mttf_activity_fetch_' . md5( self::get_client_ip() );
	}

	private static function is_fetch_rate_limited() {
		$count = (int) get_transient( self::get_fetch_rate_key() );
		return $count > self::FETCH_RATE_LIMIT;
	}

	private static function mark_fetch_request() {
		$key   = self::get_fetch_rate_key();
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, self::FETCH_RATE_WINDOW );
	}

	private static function get_client_ip() {
		$candidates = array(
			isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) : '',
			isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : '',
			isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		);

		foreach ( $candidates as $raw ) {
			if ( '' === $raw ) {
				continue;
			}
			$parts = array_map( 'trim', explode( ',', $raw ) );
			foreach ( $parts as $ip ) {
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}
