<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MTTF_Contact_Counter {
	const CRON_HOOK = 'mttf_hourly_contact_counter';

	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_hourly_counter' ) );
		add_action( 'init', array( __CLASS__, 'ensure_cron_scheduled' ) );
	}

	public static function activate() {
		self::ensure_cron_scheduled();
	}

	public static function deactivate() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	public static function ensure_cron_scheduled() {
		if ( wp_next_scheduled( self::CRON_HOOK ) ) {
			return;
		}

		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK );
	}

	public static function run_hourly_counter() {
		$route_ids = get_posts(
			array(
				'post_type'      => 'tuyen_xe',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		if ( empty( $route_ids ) ) {
			return;
		}

		$current_date = wp_date( 'Y-m-d' );
		$current_hour = (int) wp_date( 'G' );
		$hours_left   = max( 1, 24 - $current_hour );

		foreach ( $route_ids as $route_id ) {
			self::maybe_reset_daily_stats( $route_id, $current_date );

			$daily_target   = (int) get_post_meta( $route_id, '_mttf_contact_daily_target', true );
			$daily_progress = (int) get_post_meta( $route_id, '_mttf_contact_daily_progress', true );
			$remaining      = max( 0, $daily_target - $daily_progress );

			if ( $remaining <= 0 ) {
				continue;
			}

			$increment = self::generate_hourly_increment( $remaining, $hours_left );
			if ( $increment <= 0 ) {
				continue;
			}

			$current_contact_count = self::parse_contact_count(
				(string) get_post_meta( $route_id, '_mttf_contact_count', true )
			);

			update_post_meta( $route_id, '_mttf_contact_count', (string) ( $current_contact_count + $increment ) );
			update_post_meta( $route_id, '_mttf_contact_daily_progress', $daily_progress + $increment );
		}
	}

	private static function maybe_reset_daily_stats( $route_id, $current_date ) {
		$stored_date = (string) get_post_meta( $route_id, '_mttf_contact_daily_date', true );
		if ( $stored_date === $current_date ) {
			return;
		}

		update_post_meta( $route_id, '_mttf_contact_daily_date', $current_date );
		update_post_meta( $route_id, '_mttf_contact_daily_target', wp_rand( 30, 100 ) );
		update_post_meta( $route_id, '_mttf_contact_daily_progress', 0 );
	}

	private static function generate_hourly_increment( $remaining, $hours_left ) {
		if ( $hours_left <= 1 ) {
			return $remaining;
		}

		$max_this_hour = max( 1, (int) ceil( $remaining / $hours_left * 2 ) );
		$max_this_hour = min( $max_this_hour, $remaining );
		$min_this_hour = 1;

		if ( $max_this_hour < $min_this_hour ) {
			return $remaining;
		}

		return wp_rand( $min_this_hour, $max_this_hour );
	}

	private static function parse_contact_count( $raw_value ) {
		$number = preg_replace( '/[^0-9]/', '', $raw_value );
		return $number ? (int) $number : 0;
	}
}
