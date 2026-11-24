<?php
/**
 * Sync lead-rider scenic routes via Mapbox Directions.
 *
 * Usage: wp eval-file wp-content/themes/super-ninety-four/bin/snf-sync-mapbox-routes.php
 * Or:    php -r "define('WP_USE_THEMES', false); require 'wp-load.php'; require get_template_directory() . '/bin/snf-sync-mapbox-routes.php';"
 */

if ( ! defined( 'ABSPATH' ) ) {
	$root = dirname( __DIR__, 4 );
	if ( is_readable( $root . '/wp-load.php' ) ) {
		define( 'WP_USE_THEMES', false );
		require $root . '/wp-load.php';
	} else {
		fwrite( STDERR, "Could not load WordPress.\n" );
		exit( 1 );
	}
}

if ( ! function_exists( 'snf_sync_lead_rider_route_geometries' ) ) {
	fwrite( STDERR, "snf_sync_lead_rider_route_geometries() missing — is the Super Ninety Four theme active?\n" );
	exit( 1 );
}

if ( function_exists( 'snf_mapbox_mirror_token_option' ) ) {
	snf_mapbox_mirror_token_option();
}

$snf_mapbox_sync_result = snf_sync_lead_rider_route_geometries();

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::log(
		sprintf(
			'Mapbox routes: %d synced, %d failed.',
			(int) $snf_mapbox_sync_result['synced'],
			(int) $snf_mapbox_sync_result['failed']
		)
	);
	if ( ! empty( $snf_mapbox_sync_result['errors'] ) ) {
		foreach ( $snf_mapbox_sync_result['errors'] as $route_id => $message ) {
			WP_CLI::warning( $route_id . ': ' . $message );
		}
	}
	if ( empty( $snf_mapbox_sync_result['ok'] ) ) {
		WP_CLI::halt( 1 );
	}
} else {
	printf(
		"mapbox_routes synced=%d failed=%d\n",
		(int) $snf_mapbox_sync_result['synced'],
		(int) $snf_mapbox_sync_result['failed']
	);
	if ( ! empty( $snf_mapbox_sync_result['errors'] ) ) {
		foreach ( $snf_mapbox_sync_result['errors'] as $route_id => $message ) {
			echo $route_id . ': ' . $message . "\n";
		}
	}
}
