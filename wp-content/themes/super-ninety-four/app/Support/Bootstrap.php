<?php
/**
 * Theme bootstrap — loads procedural services and Support classes.
 *
 * @package SuperNinetyFour
 */

namespace SNF\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bootstrap {
	/**
	 * Service modules formerly under inc/ (procedural, behavior-preserving).
	 *
	 * @var string[]
	 */
	private static $services = array(
		'account-hub.php',
		'blocks.php',
		'memberships.php',
		'dashboard-shell.php',
		'dashboard-panels.php',
		'buddypress-profile.php',
		'access-control.php',
		'plugin-integrations.php',
		'messages.php',
		'reciprocal.php',
		'membership-subscriptions.php',
		'supabase-payments.php',
		'stripe.php',
		'home.php',
		'events.php',
		'media-library.php',
		'expo-marketplace.php',
		'lead-rider.php',
		'woocommerce-serp.php',
		'woocommerce-endpoints.php',
		'service-quotes.php',
		'schema.php',
	);

	/**
	 * Boot theme services and assets.
	 */
	public static function init(): void {
		$services_dir = get_template_directory() . '/app/Services/';
		foreach ( self::$services as $file ) {
			$path = $services_dir . $file;
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}

		Assets::register();
	}
}
