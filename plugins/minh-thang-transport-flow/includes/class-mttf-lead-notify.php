<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lead delivery: email, Telegram, logging.
 */
class MTTF_Lead_Notify {

	/**
	 * @param array<string,mixed> $lead Lead payload.
	 * @return array{email:bool,telegram:bool,email_skipped:bool,telegram_skipped:bool,email_error:string,telegram_error:string}
	 */
	public static function deliver( array $lead ) {
		$context = self::build_context( $lead );

		$email_result    = self::send_email( $context );
		$telegram_result = self::send_telegram( $context );

		self::log(
			sprintf(
				'Lead deliver phone=%s route_id=%d email=%s telegram=%s',
				self::mask_phone_for_log( (string) $context['phone'] ),
				(int) $context['route_id'],
				$email_result['success'] ? 'ok' : ( $email_result['skipped'] ? 'skip' : 'fail' ),
				$telegram_result['success'] ? 'ok' : ( $telegram_result['skipped'] ? 'skip' : 'fail' )
			)
		);

		if ( ! $email_result['success'] && ! $email_result['skipped'] && '' !== $email_result['error'] ) {
			self::log( 'Email error: ' . $email_result['error'], 'error' );
		}
		if ( ! $telegram_result['success'] && ! $telegram_result['skipped'] && '' !== $telegram_result['error'] ) {
			self::log( 'Telegram error: ' . $telegram_result['error'], 'error' );
		}

		return array(
			'email'           => $email_result['success'],
			'telegram'        => $telegram_result['success'],
			'email_skipped'   => $email_result['skipped'],
			'telegram_skipped'=> $telegram_result['skipped'],
			'email_error'     => $email_result['error'],
			'telegram_error'  => $telegram_result['error'],
		);
	}

	/**
	 * @param array<string,mixed> $lead Raw lead.
	 * @return array<string,mixed>
	 */
	public static function build_context( array $lead ) {
		$route_id    = isset( $lead['route_id'] ) ? absint( $lead['route_id'] ) : 0;
		$route_title = isset( $lead['route_title'] ) ? sanitize_text_field( (string) $lead['route_title'] ) : '';
		$route_slug  = isset( $lead['route_slug'] ) ? sanitize_text_field( (string) $lead['route_slug'] ) : '';
		$region      = isset( $lead['region'] ) ? sanitize_text_field( (string) $lead['region'] ) : '';
		$phone       = isset( $lead['phone'] ) ? sanitize_text_field( (string) $lead['phone'] ) : '';
		$source_page = isset( $lead['source_page'] ) ? esc_url_raw( (string) $lead['source_page'] ) : '';
		$contact_apps = isset( $lead['contact_apps'] ) && is_array( $lead['contact_apps'] ) ? $lead['contact_apps'] : array();
		$utm_data    = isset( $lead['utm'] ) && is_array( $lead['utm'] ) ? $lead['utm'] : array();
		$user_agent  = isset( $lead['user_agent'] ) ? sanitize_text_field( (string) $lead['user_agent'] ) : '';
		$client_ip   = isset( $lead['client_ip'] ) ? sanitize_text_field( (string) $lead['client_ip'] ) : '';

		$tuyen_name  = '';
		$nha_xe_name = '';

		if ( $route_id > 0 && 'tuyen_xe' === get_post_type( $route_id ) ) {
			$tuyen_terms = wp_get_post_terms( $route_id, MTTF_Landing_Taxonomies::TAX_TUYEN );
			if ( ! is_wp_error( $tuyen_terms ) && ! empty( $tuyen_terms[0] ) ) {
				$tuyen_name = (string) $tuyen_terms[0]->name;
			}
			$nha_terms = wp_get_post_terms( $route_id, MTTF_Landing_Taxonomies::TAX_NHA_XE );
			if ( ! is_wp_error( $nha_terms ) && ! empty( $nha_terms[0] ) ) {
				$nha_xe_name = (string) $nha_terms[0]->name;
			}
		}

		if ( '' === $tuyen_name && '' !== $source_page ) {
			$tuyen_name = self::detect_term_name_from_url( $source_page, MTTF_Landing_Taxonomies::TAX_TUYEN );
		}
		if ( '' === $nha_xe_name && '' !== $source_page ) {
			$nha_xe_name = self::detect_term_name_from_url( $source_page, MTTF_Landing_Taxonomies::TAX_NHA_XE );
		}

		$landing_label = '';
		if ( '' !== $tuyen_name ) {
			$landing_label = $tuyen_name;
		} elseif ( '' !== $nha_xe_name ) {
			$landing_label = $nha_xe_name;
		} elseif ( '' !== $route_title ) {
			$landing_label = $route_title;
		}

		return array(
			'route_id'       => $route_id,
			'route_title'    => $route_title,
			'route_slug'     => $route_slug,
			'region'         => $region,
			'phone'          => $phone,
			'source_page'    => $source_page,
			'contact_apps'   => $contact_apps,
			'utm'            => $utm_data,
			'user_agent'     => $user_agent,
			'client_ip'      => $client_ip,
			'tuyen_name'     => $tuyen_name,
			'nha_xe_name'    => $nha_xe_name,
			'landing_label'  => $landing_label,
			'sent_at'        => wp_date( 'Y-m-d H:i:s' ),
			'sent_at_human'  => wp_date( 'd/m/Y H:i' ),
		);
	}

	/**
	 * @param string $url      Page URL.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	private static function detect_term_name_from_url( $url, $taxonomy ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! is_string( $path ) || '' === $path ) {
			return '';
		}

		$slug = '';
		if ( MTTF_Landing_Taxonomies::TAX_TUYEN === $taxonomy && preg_match( '#/tuyen/([^/]+)/?#i', $path, $m ) ) {
			$slug = $m[1];
		} elseif ( MTTF_Landing_Taxonomies::TAX_NHA_XE === $taxonomy && preg_match( '#/nha-xe/([^/]+)/?#i', $path, $m ) ) {
			$slug = $m[1];
		}

		if ( '' === $slug ) {
			return '';
		}

		$term = get_term_by( 'slug', sanitize_title( $slug ), $taxonomy );
		if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
			return (string) $term->name;
		}

		return '';
	}

	/**
	 * @param array<string,mixed> $context Context.
	 * @return array{success:bool,skipped:bool,error:string}
	 */
	private static function send_email( array $context ) {
		$recipients = self::resolve_email_recipients( (int) $context['route_id'] );
		if ( empty( $recipients ) ) {
			return array(
				'success' => false,
				'skipped' => true,
				'error'   => '',
			);
		}

		$to      = implode( ',', $recipients );
		$subject = self::build_email_subject( $context );
		$message = self::build_message_body( $context );
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		$sent = wp_mail( $to, $subject, $message, $headers );

		return array(
			'success' => (bool) $sent,
			'skipped' => false,
			'error'   => $sent ? '' : 'wp_mail_failed',
		);
	}

	/**
	 * @param array<string,mixed> $context Context.
	 * @return array{success:bool,skipped:bool,error:string}
	 */
	private static function send_telegram( array $context ) {
		$default_token = MTTF_Settings::get( 'telegram_bot_token', '' );
		$token         = trim( (string) apply_filters( 'mttf_telegram_bot_token', $default_token ) );
		if ( '' === $token ) {
			return array(
				'success' => false,
				'skipped' => true,
				'error'   => '',
			);
		}

		$chat_id = self::resolve_telegram_chat_id( (int) $context['route_id'], (string) $context['region'] );
		if ( '' === $chat_id ) {
			return array(
				'success' => false,
				'skipped' => true,
				'error'   => '',
			);
		}

		$text = self::build_message_body( $context );

		$response = wp_remote_post(
			'https://api.telegram.org/bot' . $token . '/sendMessage',
			array(
				'timeout' => 15,
				'body'    => array(
					'chat_id' => $chat_id,
					'text'    => $text,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'skipped' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || ! is_array( $body ) || empty( $body['ok'] ) ) {
			$desc = '';
			if ( is_array( $body ) && isset( $body['description'] ) ) {
				$desc = (string) $body['description'];
			}
			return array(
				'success' => false,
				'skipped' => false,
				'error'   => '' !== $desc ? $desc : 'telegram_http_' . $code,
			);
		}

		return array(
			'success' => true,
			'skipped' => false,
			'error'   => '',
		);
	}

	/**
	 * @param int    $route_id Route post ID.
	 * @param string $region   Hub region.
	 * @return string
	 */
	private static function resolve_telegram_chat_id( $route_id, $region ) {
		$chat_id = '';
		if ( $route_id > 0 ) {
			$chat_id = trim( (string) get_post_meta( $route_id, '_mttf_telegram_chat_id', true ) );
		}

		if ( '' === $chat_id && '' !== $region ) {
			$region_chat_id = MTTF_Settings::get( 'telegram_chat_id_' . $region, '' );
			$chat_id        = trim( (string) apply_filters( 'mttf_telegram_chat_id_' . $region, $region_chat_id ) );
		}

		if ( '' === $chat_id ) {
			$default_chat_id = MTTF_Settings::get( 'telegram_default_chat_id', '' );
			$chat_id         = trim( (string) apply_filters( 'mttf_telegram_default_chat_id', $default_chat_id ) );
		}

		return $chat_id;
	}

	/**
	 * @param int $route_id Route ID.
	 * @return string[]
	 */
	public static function resolve_email_recipients( $route_id ) {
		$route_id = absint( $route_id );
		if ( $route_id > 0 && 'tuyen_xe' === get_post_type( $route_id ) ) {
			$route_raw = trim( (string) get_post_meta( $route_id, '_mttf_lead_email', true ) );
			if ( '' !== $route_raw ) {
				$parsed = self::parse_email_list( $route_raw );
				if ( ! empty( $parsed ) ) {
					$parsed = (array) apply_filters( 'mttf_lead_email_recipients', $parsed, $route_id );
					$legacy_to = apply_filters( 'mttf_lead_email_to', implode( ',', $parsed ), $route_id );
					if ( is_string( $legacy_to ) && '' !== trim( $legacy_to ) ) {
						$parsed = self::parse_email_list( $legacy_to );
					}
					return $parsed;
				}
			}
		}

		$default_to = MTTF_Settings::get( 'lead_email', '' );
		$default_to = is_string( $default_to ) ? trim( $default_to ) : '';
		if ( '' === $default_to ) {
			$default_to = (string) get_option( 'admin_email' );
		}

		$parsed = self::parse_email_list( $default_to );

		$parsed = (array) apply_filters( 'mttf_lead_email_recipients', $parsed, $route_id );

		$legacy_to = apply_filters( 'mttf_lead_email_to', implode( ',', $parsed ), $route_id );
		if ( is_string( $legacy_to ) && '' !== trim( $legacy_to ) ) {
			$parsed = self::parse_email_list( $legacy_to );
		}

		return $parsed;
	}

	/**
	 * @param string $raw Comma/semicolon separated emails.
	 * @return string[]
	 */
	private static function parse_email_list( $raw ) {
		$raw    = str_replace( array( ';', "\n", "\r" ), ',', (string) $raw );
		$parts  = array_map( 'trim', explode( ',', $raw ) );
		$emails = array();

		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}
			$email = sanitize_email( $part );
			if ( $email && is_email( $email ) ) {
				$emails[] = $email;
			}
		}

		return array_values( array_unique( $emails ) );
	}

	/**
	 * @param array<string,mixed> $context Context.
	 * @return string
	 */
	private static function build_email_subject( array $context ) {
		$label = (string) $context['landing_label'];
		if ( '' === $label ) {
			$label = (string) $context['route_title'];
		}
		if ( '' === $label ) {
			$label = __( 'Khách mới', 'minh-thang-transport-flow' );
		}

		return sprintf(
			/* translators: %s: route or operator name */
			__( 'Lead mới từ Đặt Xe Việt Nam - %s', 'minh-thang-transport-flow' ),
			$label
		);
	}

	/**
	 * @param array<string,mixed> $context Context.
	 * @return string
	 */
	private static function build_message_body( array $context ) {
		$apps = ! empty( $context['contact_apps'] )
			? implode( ', ', $context['contact_apps'] )
			: __( 'Không chọn', 'minh-thang-transport-flow' );

		$lines = array(
			__( 'Lead mới từ Đặt Xe Việt Nam', 'minh-thang-transport-flow' ),
			'',
			'SĐT: ' . (string) $context['phone'],
			'Dịch vụ: ' . (string) $context['route_title'],
			'Tuyến: ' . ( '' !== (string) $context['tuyen_name'] ? (string) $context['tuyen_name'] : '—' ),
			'Nhà xe: ' . ( '' !== (string) $context['nha_xe_name'] ? (string) $context['nha_xe_name'] : '—' ),
			'Miền (hub): ' . ( '' !== (string) $context['region'] ? (string) $context['region'] : '—' ),
			'Ứng dụng liên hệ: ' . $apps,
			'Trang gửi: ' . ( '' !== (string) $context['source_page'] ? (string) $context['source_page'] : '—' ),
			'Thời gian: ' . (string) $context['sent_at_human'],
		);

		if ( '' !== (string) $context['client_ip'] && '0.0.0.0' !== (string) $context['client_ip'] ) {
			$lines[] = 'IP: ' . (string) $context['client_ip'];
		}

		if ( '' !== (string) $context['user_agent'] ) {
			$lines[] = 'Thiết bị: ' . (string) $context['user_agent'];
		}

		if ( ! empty( $context['utm'] ) && is_array( $context['utm'] ) ) {
			$lines[] = 'UTM/Ads: ' . wp_json_encode( $context['utm'], JSON_UNESCAPED_UNICODE );
		}

		$lines[] = '';
		$lines[] = __( 'Vui lòng gọi lại khách trong vài phút.', 'minh-thang-transport-flow' );

		return implode( "\n", $lines );
	}

	/**
	 * @param string $phone Phone.
	 * @return string
	 */
	private static function mask_phone_for_log( $phone ) {
		$digits = preg_replace( '/\D+/', '', (string) $phone );
		if ( strlen( $digits ) <= 4 ) {
			return '****';
		}

		return str_repeat( '*', max( 0, strlen( $digits ) - 3 ) ) . substr( $digits, -3 );
	}

	/**
	 * @param string $message Message.
	 * @param string $level   Level.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$line = '[MTTF Lead][' . $level . '] ' . $message;
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $line );
	}
}
