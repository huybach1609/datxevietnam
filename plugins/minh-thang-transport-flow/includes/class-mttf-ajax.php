<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MTTF_Ajax {
	private const DEFAULT_LOCK_SECONDS     = 45;
	private const IP_BURST_LIMIT           = 8;
	private const IP_BURST_WINDOW_SECONDS  = MINUTE_IN_SECONDS;

	public static function init() {
		add_action( 'wp_ajax_nopriv_mttf_capture_lead', array( __CLASS__, 'capture_lead' ) );
		add_action( 'wp_ajax_mttf_capture_lead', array( __CLASS__, 'capture_lead' ) );
		add_action( 'wp_ajax_nopriv_mttf_live_search_routes', array( __CLASS__, 'live_search_routes' ) );
		add_action( 'wp_ajax_mttf_live_search_routes', array( __CLASS__, 'live_search_routes' ) );
		add_action( 'wp_ajax_nopriv_mttf_track_route_search', array( __CLASS__, 'track_route_search' ) );
		add_action( 'wp_ajax_mttf_track_route_search', array( __CLASS__, 'track_route_search' ) );
	}

	public static function capture_lead() {
		check_ajax_referer( 'mttf_capture_lead', 'nonce' );
		$spam_protection_enabled = self::is_spam_protection_enabled();

		$honeypot = sanitize_text_field( wp_unslash( $_POST['website'] ?? '' ) );
		if ( '' !== $honeypot ) {
			wp_send_json_error( array( 'message' => 'Invalid request.' ), 400 );
		}

		if ( $spam_protection_enabled && self::is_suspicious_user_agent() ) {
			wp_send_json_error( array( 'message' => 'Yêu cầu không hợp lệ.' ), 400 );
		}

		if ( $spam_protection_enabled && self::is_ip_rate_limited() ) {
			wp_send_json_error(
				array(
					'message'     => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau ít phút.',
					'retry_after' => self::get_ip_retry_after_seconds(),
				),
				429
			);
		}

		$phone = self::normalize_phone( (string) wp_unslash( $_POST['phone'] ?? '' ) );
		if ( ! self::is_valid_phone( $phone ) ) {
			wp_send_json_error( array( 'message' => 'Số điện thoại không hợp lệ.' ), 400 );
		}

		$route_id    = absint( $_POST['route_id'] ?? 0 );
		$route_title = sanitize_text_field( wp_unslash( $_POST['route_title'] ?? '' ) );
		$route_slug  = sanitize_text_field( wp_unslash( $_POST['route_slug'] ?? '' ) );
		$region      = sanitize_text_field( wp_unslash( $_POST['route_region'] ?? '' ) );
		$source_page = esc_url_raw( wp_unslash( $_POST['source_page'] ?? '' ) );
		$utm_data    = self::sanitize_utm_data( $_POST );
		$contact_apps = self::sanitize_contact_apps( $_POST['contact_apps'] ?? array() );

		if ( $spam_protection_enabled && self::is_rate_limited( $phone ) ) {
			wp_send_json_error(
				array(
					'message'     => 'Bạn vừa gửi gần đây. Thử lại sau ít phút.',
					'retry_after' => self::get_phone_retry_after_seconds( $phone ),
				),
				429
			);
		}

		if ( $spam_protection_enabled ) {
			self::mark_rate_limit( $phone );
			self::mark_ip_rate_limit();
		}

		$lead_payload = array(
			'route_id'     => $route_id,
			'route_title'  => $route_title,
			'route_slug'   => $route_slug,
			'region'       => $region,
			'phone'        => $phone,
			'source_page'  => $source_page,
			'contact_apps' => $contact_apps,
			'utm'          => $utm_data,
			'user_agent'   => self::get_user_agent(),
			'client_ip'    => self::get_client_ip(),
		);

		$db_saved = false;
		if ( class_exists( 'MTTF_Lead_DB' ) ) {
			$db_saved = (bool) MTTF_Lead_DB::record_lead(
				array(
					'route_id'     => $route_id,
					'route_title'  => $route_title,
					'route_slug'   => $route_slug,
					'hub_region'   => $region,
					'phone'        => $phone,
					'contact_apps' => implode( ', ', $contact_apps ),
					'source_url'   => $source_page,
					'utm'          => $utm_data,
				)
			);
		}

		$delivery = array(
			'email'    => false,
			'telegram' => false,
		);
		if ( class_exists( 'MTTF_Lead_Notify' ) ) {
			$delivery = MTTF_Lead_Notify::deliver( $lead_payload );
		}

		$email_ok    = ! empty( $delivery['email'] );
		$telegram_ok = ! empty( $delivery['telegram'] );
		$email_wanted    = empty( $delivery['email_skipped'] );
		$telegram_wanted = empty( $delivery['telegram_skipped'] );

		$notify_ok = $email_ok || $telegram_ok;
		$any_channel_configured = $email_wanted || $telegram_wanted;

		if ( ! $notify_ok && ! $db_saved ) {
			if ( $any_channel_configured ) {
				wp_send_json_error(
					array(
						'message' => 'Gửi thông tin chưa thành công, vui lòng thử lại hoặc liên hệ Zalo.',
					),
					500
				);
			}

			wp_send_json_error(
				array(
					'message' => 'Hệ thống chưa được cấu hình nhận lead. Vui lòng liên hệ Zalo hoặc hotline.',
				),
				500
			);
		}

		if ( ! $notify_ok && $db_saved && class_exists( 'MTTF_Lead_Notify', false ) ) {
			MTTF_Lead_Notify::log( 'Lead saved to DB but all notify channels failed.', 'error' );
		}

		do_action(
			'mttf_lead_captured',
			array(
				'phone'        => $phone,
				'route_id'     => $route_id,
				'route_slug'   => $route_slug,
				'region'       => $region,
				'utm'          => $utm_data,
				'contact_apps' => $contact_apps,
				'delivery'     => $delivery,
				'db_saved'     => $db_saved,
			)
		);

		$activity_ping = null;
		if ( class_exists( 'MTTF_Activity_Pings' ) && MTTF_Activity_Pings::is_enabled() ) {
			$activity_ping = MTTF_Activity_Pings::record_ping( $phone, $route_title );
		}

		wp_send_json_success(
			array(
				'message'          => 'Đã nhận thông tin. Sale sẽ liên hệ ngay.',
				'cooldown_seconds' => $spam_protection_enabled ? self::get_lock_seconds() : 0,
				'activity_ping'    => $activity_ping,
			)
		);
	}

	private static function is_spam_protection_enabled() {
		return 1 === (int) MTTF_Settings::get( 'enable_spam_protection', 1 );
	}

	public static function live_search_routes() {
		check_ajax_referer( 'mttf_live_search', 'nonce' );

		$keyword = sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) );
		if ( mb_strlen( $keyword ) < 2 ) {
			wp_send_json_success(
				array(
					'route_ids' => array(),
				)
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'tuyen_xe',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				's'              => $keyword,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => '_mttf_is_active',
						'value' => 1,
					),
				),
			)
		);

		wp_send_json_success(
			array(
				'route_ids' => array_map( 'intval', $query->posts ),
			)
		);
	}

	public static function track_route_search() {
		check_ajax_referer( 'mttf_track_route_search', 'nonce' );

		$route_id = absint( $_POST['route_id'] ?? 0 );
		if ( $route_id <= 0 || 'tuyen_xe' !== get_post_type( $route_id ) ) {
			wp_send_json_error(
				array(
					'message' => 'Tuyến không hợp lệ.',
				),
				400
			);
		}

		$current_count = (int) get_post_meta( $route_id, '_mttf_search_count', true );
		$new_count     = $current_count + 1;

		update_post_meta( $route_id, '_mttf_search_count', $new_count );
		update_post_meta( $route_id, '_mttf_last_searched_at', time() );

		wp_send_json_success(
			array(
				'route_id'      => $route_id,
				'search_count'  => $new_count,
			)
		);
	}

	private static function sanitize_utm_data( $raw ) {
		$keys = array(
			'utm_source',
			'utm_medium',
			'utm_campaign',
			'utm_content',
			'utm_term',
			'gclid',
			'fbclid',
			'ttclid',
			'first_touch',
			'last_touch',
		);
		$data = array();

		foreach ( $keys as $key ) {
			if ( isset( $raw[ $key ] ) ) {
				$data[ $key ] = sanitize_text_field( wp_unslash( $raw[ $key ] ) );
			}
		}

		return $data;
	}

	private static function sanitize_contact_apps( $raw_apps ) {
		$allowed = array( 'WhatsApp', 'Viber', 'WeChat', 'KakaoTalk' );
		$apps    = is_array( $raw_apps ) ? $raw_apps : array();
		$clean   = array();

		foreach ( $apps as $app ) {
			$value = sanitize_text_field( wp_unslash( (string) $app ) );
			if ( in_array( $value, $allowed, true ) && ! in_array( $value, $clean, true ) ) {
				$clean[] = $value;
			}
		}

		return $clean;
	}

	private static function is_rate_limited( $phone ) {
		$key = self::get_phone_rate_limit_key( $phone );
		return false !== get_transient( $key );
	}

	private static function mark_rate_limit( $phone ) {
		$key = self::get_phone_rate_limit_key( $phone );
		set_transient( $key, 1, self::get_lock_seconds() );
	}

	/**
	 * Public wrapper for lookups (e.g. lead log filter).
	 */
	public static function normalize_phone_for_lookup( $raw_phone ) {
		return self::normalize_phone( (string) $raw_phone );
	}

	public static function normalize_phone( $raw_phone ) {
		$phone = preg_replace( '/[^0-9+]/', '', sanitize_text_field( $raw_phone ) );

		// Convert 00-countrycode format to +countrycode for WhatsApp-style international input.
		if ( 0 === strpos( $phone, '00' ) ) {
			$phone = '+' . substr( $phone, 2 );
		}

		if ( 0 === strpos( $phone, '+84' ) ) {
			$phone = '0' . substr( $phone, 3 );
		}

		if ( 0 === strpos( $phone, '84' ) && strlen( $phone ) >= 10 ) {
			$phone = '0' . substr( $phone, 2 );
		}

		return $phone;
	}

	private static function is_valid_phone( $phone ) {
		// Accept Vietnam mobile prefixes (03/05/07/08/09) with total length 10.
		if ( 1 === preg_match( '/^0(?:3|5|7|8|9)[0-9]{8}$/', $phone ) ) {
			return true;
		}

		// Accept international E.164 numbers (common for WhatsApp), e.g. +12025550123.
		if ( 1 === preg_match( '/^\+[1-9][0-9]{7,14}$/', $phone ) ) {
			return true;
		}

		return false;
	}

	private static function is_ip_rate_limited() {
		$key   = self::get_ip_burst_key();
		$count = (int) get_transient( $key );
		return $count >= self::IP_BURST_LIMIT;
	}

	private static function mark_ip_rate_limit() {
		$key   = self::get_ip_burst_key();
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, self::IP_BURST_WINDOW_SECONDS );
	}

	private static function get_phone_retry_after_seconds( $phone ) {
		return self::get_transient_ttl( self::get_phone_rate_limit_key( $phone ) );
	}

	private static function get_ip_retry_after_seconds() {
		return self::get_transient_ttl( self::get_ip_burst_key() );
	}

	private static function get_phone_rate_limit_key( $phone ) {
		return 'mttf_lead_' . md5( $phone . '|' . self::get_client_ip() );
	}

	private static function get_ip_burst_key() {
		return 'mttf_ip_burst_' . md5( self::get_client_ip() );
	}

	private static function get_transient_ttl( $key ) {
		$timeout = (int) get_option( '_transient_timeout_' . $key );
		if ( $timeout <= 0 ) {
			return self::get_lock_seconds();
		}

		$ttl = $timeout - time();
		return $ttl > 0 ? $ttl : self::get_lock_seconds();
	}

	private static function get_lock_seconds() {
		$seconds = (int) MTTF_Settings::get( 'lead_lock_seconds', self::DEFAULT_LOCK_SECONDS );
		if ( $seconds < 10 ) {
			$seconds = 10;
		}
		if ( $seconds > 600 ) {
			$seconds = 600;
		}

		return $seconds;
	}

	private static function is_suspicious_user_agent() {
		$ua = self::get_user_agent();
		if ( '' === $ua ) {
			return true;
		}

		$ua = strtolower( $ua );
		$blocked_fragments = array(
			'curl',
			'wget',
			'python-requests',
			'httpclient',
			'bot',
			'spider',
			'scrapy',
			'java/',
		);

		foreach ( $blocked_fragments as $fragment ) {
			if ( false !== strpos( $ua, $fragment ) ) {
				return true;
			}
		}

		return false;
	}

	private static function get_user_agent() {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
	}

	private static function get_client_ip() {
		$candidates = array(
			$_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
			$_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
			$_SERVER['REMOTE_ADDR'] ?? '',
		);

		foreach ( $candidates as $candidate ) {
			$raw = sanitize_text_field( wp_unslash( $candidate ) );
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
