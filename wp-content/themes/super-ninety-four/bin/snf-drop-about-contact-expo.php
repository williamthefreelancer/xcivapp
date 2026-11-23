<?php
/**
 * One-shot: drop About / Contact / Expo pages and refresh menus.
 *
 * Usage: wp eval-file wp-content/themes/super-ninety-four/bin/snf-drop-about-contact-expo.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run via WP-CLI eval-file.\n" );
	exit( 1 );
}

delete_option( 'snf_obsolete_pages_cleaned' );

if ( function_exists( 'snf_delete_obsolete_site_pages' ) ) {
	snf_delete_obsolete_site_pages();
}

if ( function_exists( 'snf_ensure_site_pages' ) ) {
	snf_ensure_site_pages();
}

delete_option( 'snf_theme_menus_version' );
if ( function_exists( 'snf_ensure_theme_menus' ) ) {
	snf_ensure_theme_menus();
}

$check = array(
	'about',
	'contact',
	'expo',
	'inquiries',
	'select-events-by-members',
	'select-events-by-non-members',
	'find-other-reciprocal-programs',
);

foreach ( $check as $slug ) {
	$page = get_page_by_path( $slug );
	echo $slug . ': ' . ( $page ? ( 'KEEP id=' . (int) $page->ID ) : 'GONE' ) . PHP_EOL;
}
