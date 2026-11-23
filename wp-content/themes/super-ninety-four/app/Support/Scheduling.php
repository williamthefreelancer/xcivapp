<?php
/**
 * Shared Action Scheduler / WP-Cron helpers for theme jobs.
 *
 * Prefer Action Scheduler when WooCommerce (or AS) is present; fall back to wp_cron.
 *
 * @package SuperNinetyFour
 */

namespace SNF\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Scheduling {
	/**
	 * Schedule a one-off action.
	 *
	 * @param string               $hook      Action hook name.
	 * @param int                  $timestamp Unix timestamp.
	 * @param array<int|string,mixed> $args  Hook args.
	 * @param string               $group     Action Scheduler group.
	 * @return bool True if scheduled or already pending.
	 */
	public static function once( string $hook, int $timestamp, array $args = array(), string $group = 'snf' ): bool {
		$hook = sanitize_key( $hook );
		if ( '' === $hook ) {
			return false;
		}

		if ( $timestamp <= time() ) {
			$timestamp = time() + 60;
		}

		if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_single_action' ) ) {
			if ( ! as_has_scheduled_action( $hook, $args, $group ) ) {
				as_schedule_single_action( $timestamp, $hook, $args, $group );
			}
			return true;
		}

		if ( ! wp_next_scheduled( $hook, $args ) ) {
			wp_schedule_single_event( $timestamp, $hook, $args );
		}

		return true;
	}

	/**
	 * Schedule a recurring action (daily-style interval in seconds).
	 *
	 * @param string               $hook             Action hook name.
	 * @param int                  $interval_seconds Recurrence in seconds.
	 * @param array<int|string,mixed> $args          Hook args.
	 * @param string               $group            Action Scheduler group.
	 * @param int                  $first_run        First run timestamp (0 = now + 1 hour).
	 * @return bool True if scheduled or already pending.
	 */
	public static function recurring( string $hook, int $interval_seconds, array $args = array(), string $group = 'snf', int $first_run = 0 ): bool {
		$hook = sanitize_key( $hook );
		if ( '' === $hook ) {
			return false;
		}

		$interval_seconds = max( MINUTE_IN_SECONDS, (int) $interval_seconds );
		$first_run        = $first_run > 0 ? $first_run : ( time() + HOUR_IN_SECONDS );

		if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( ! as_has_scheduled_action( $hook, $args, $group ) ) {
				as_schedule_recurring_action( $first_run, $interval_seconds, $hook, $args, $group );
			}
		}

		// WP-Cron safety net even when Action Scheduler is present.
		$wp_recurrence = self::interval_to_wp_recurrence( $interval_seconds );
		if ( ! wp_next_scheduled( $hook, $args ) ) {
			wp_schedule_event( $first_run, $wp_recurrence, $hook, $args );
		}

		return true;
	}

	/**
	 * Map seconds to a registered WP-Cron recurrence slug.
	 *
	 * @param int $interval_seconds Interval.
	 * @return string
	 */
	private static function interval_to_wp_recurrence( int $interval_seconds ): string {
		if ( $interval_seconds >= DAY_IN_SECONDS ) {
			return 'daily';
		}
		if ( $interval_seconds >= HOUR_IN_SECONDS ) {
			return 'hourly';
		}
		return 'hourly';
	}
}
