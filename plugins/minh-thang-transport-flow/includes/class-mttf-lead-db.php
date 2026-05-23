<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MTTF_Lead_DB {
	public const SCHEMA_VERSION       = '1';
	public const OPTION_SCHEMA_VERSION = 'mttf_leads_schema_version';

	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_install_table' ), 5 );
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'mttf_leads';
	}

	public static function activate() {
		self::install_table();
		update_option( self::OPTION_SCHEMA_VERSION, self::SCHEMA_VERSION );
	}

	public static function maybe_install_table() {
		$stored = get_option( self::OPTION_SCHEMA_VERSION );
		if ( self::SCHEMA_VERSION !== $stored || ! self::table_exists() ) {
			self::install_table();
			update_option( self::OPTION_SCHEMA_VERSION, self::SCHEMA_VERSION );
		}
	}

	public static function table_exists() {
		global $wpdb;
		$tbl = self::table_name();
		return (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl ) ) === $tbl;
	}

	public static function count_all() {
		global $wpdb;
		if ( ! self::table_exists() ) {
			return 0;
		}

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- static table identifier from \$wpdb->prefix.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	public static function install_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$table           = self::table_name();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL,
			route_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			route_slug varchar(191) NOT NULL DEFAULT '',
			route_title text NULL,
			hub_region varchar(32) NOT NULL DEFAULT '',
			phone varchar(32) NOT NULL DEFAULT '',
			contact_apps varchar(191) NOT NULL DEFAULT '',
			source_url text NULL,
			utm_json text NULL,
			PRIMARY KEY  (id),
			KEY route_id (route_id),
			KEY created_at (created_at),
			KEY phone (phone)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Persist lead after capture succeeds.
	 *
	 * @param array<string,mixed> $data route_id, route_title, route_slug, hub_region, phone, contact_apps_csv, source_url, utm_array.
	 */
	public static function record_lead( array $data ) {
		global $wpdb;

		if ( ! self::table_exists() ) {
			self::install_table();
		}

		$table = self::table_name();

		$utm = isset( $data['utm'] ) && is_array( $data['utm'] ) ? $data['utm'] : array();

		$inserted = $wpdb->insert(
			$table,
			array(
				'created_at'    => isset( $data['created_at'] ) ? sanitize_text_field( (string) $data['created_at'] ) : current_time( 'mysql' ),
				'route_id'      => isset( $data['route_id'] ) ? absint( $data['route_id'] ) : 0,
				'route_slug'    => isset( $data['route_slug'] ) ? sanitize_text_field( (string) $data['route_slug'] ) : '',
				'route_title'   => isset( $data['route_title'] ) ? sanitize_text_field( (string) $data['route_title'] ) : '',
				'hub_region'    => isset( $data['hub_region'] ) ? sanitize_text_field( (string) $data['hub_region'] ) : '',
				'phone'         => isset( $data['phone'] ) ? self::sanitize_stored_phone( (string) $data['phone'] ) : '',
				'contact_apps'  => isset( $data['contact_apps'] ) ? sanitize_text_field( (string) $data['contact_apps'] ) : '',
				'source_url'    => isset( $data['source_url'] ) ? esc_url_raw( (string) $data['source_url'] ) : '',
				'utm_json'      => wp_json_encode( array_map( 'sanitize_text_field', $utm ), JSON_UNESCAPED_UNICODE ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return false !== $inserted;
	}

	private static function sanitize_stored_phone( $phone ) {
		$p = sanitize_text_field( $phone );
		if ( strlen( $p ) > 32 ) {
			$p = substr( $p, 0, 32 );
		}
		return $p;
	}

	/**
	 * @param array{route_id?:int|string,phone?:string,paged?:int,per_page?:int} $args Filters.
	 * @return array{items:array<int,object>,total:int}
	 */
	public static function query_leads( $args ) {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return array(
				'items' => array(),
				'total' => 0,
			);
		}

		$table = self::table_name();

		$where  = array( '1=1' );
		$params = array();

		$route_id = isset( $args['route_id'] ) ? absint( $args['route_id'] ) : 0;
		if ( $route_id > 0 ) {
			$where[]  = 'route_id = %d';
			$params[] = $route_id;
		}

		$phone_q = isset( $args['phone'] ) ? trim( (string) $args['phone'] ) : '';
		if ( '' !== $phone_q ) {
			$patterns      = array();
			$digits_only   = preg_replace( '/[^0-9+]/', '', $phone_q );
			if ( class_exists( 'MTTF_Ajax' ) ) {
				$n = MTTF_Ajax::normalize_phone_for_lookup( $phone_q );
				if ( strlen( $n ) >= 4 ) {
					$patterns[] = '%' . $wpdb->esc_like( $n ) . '%';
				}
			}
			if ( strlen( $digits_only ) >= 3 ) {
				$patterns[] = '%' . $wpdb->esc_like( $digits_only ) . '%';
			}
			$patterns = array_values( array_unique( $patterns ) );
			if ( ! empty( $patterns ) ) {
				$or_parts = array();
				foreach ( $patterns as $_p ) {
					$or_parts[] = 'phone LIKE %s';
					$params[]   = $_p;
				}
				$where[] = '(' . implode( ' OR ', $or_parts ) . ')';
			}
		}

		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dynamic placeholders count matches $params from filters only.
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );
		} else {
			$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no placeholders.
		}

		$per_page = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : 25;
		if ( $per_page < 10 ) {
			$per_page = 10;
		}
		if ( $per_page > 500 ) {
			$per_page = 500;
		}

		$paged = isset( $args['paged'] ) ? absint( $args['paged'] ) : 1;
		if ( $paged < 1 ) {
			$paged = 1;
		}
		$offset = ( $paged - 1 ) * $per_page;

		if ( ! empty( $args['export_cap'] ) ) {
			$cap = absint( $args['export_cap'] );
			if ( $cap < 1 ) {
				$cap = 5000;
			}
			if ( $cap > 20000 ) {
				$cap = 20000;
			}
			$params_export = $params;
			$params_export[] = $cap;
			$select_sql       = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d";
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $select_sql, $params_export ), OBJECT );

			return array(
				'items' => is_array( $rows ) ? $rows : array(),
				'total' => $total,
			);
		}

		$params_with_limit   = $params;
		$params_with_limit[] = $per_page;
		$params_with_limit[] = $offset;

		$select_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders match $params_with_limit order.
		$rows = $wpdb->get_results( $wpdb->prepare( $select_sql, $params_with_limit ), OBJECT );

		return array(
			'items' => is_array( $rows ) ? $rows : array(),
			'total' => $total,
		);
	}
}
