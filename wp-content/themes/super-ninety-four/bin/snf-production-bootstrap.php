<?php
/**
 * Production post-deploy bootstrap (roles, tables, pages, menus).
 *
 * Usage: wp eval-file wp-content/themes/super-ninety-four/bin/snf-production-bootstrap.php
 *
 * @package Super_Ninety_Four
 */

if ( function_exists( 'snf_register_club_roles' ) ) {
	snf_register_club_roles();
	echo "roles:ok\n";
}

if ( function_exists( 'snf_messages_install_tables' ) ) {
	snf_messages_install_tables();
	echo "messages_tables:ok\n";
}

if ( function_exists( 'snf_community_install_tables' ) ) {
	snf_community_install_tables();
	echo "community_tables:ok\n";
}

if ( function_exists( 'snf_community_ensure_default_circles' ) ) {
	snf_community_ensure_default_circles();
	echo "circles:ok\n";
}

if ( function_exists( 'snf_ensure_site_pages' ) ) {
	snf_ensure_site_pages();
	echo "pages:ok\n";
}

update_option( 'snf_theme_menus_force_resync', 1, false );
if ( function_exists( 'snf_ensure_theme_menus' ) ) {
	snf_ensure_theme_menus();
	echo "menus:ok\n";
}

// Pull recent YouTube uploads into the video library on every deploy.
$snf_youtube_sync = get_template_directory() . '/bin/snf-sync-youtube-videos.php';
if ( is_readable( $snf_youtube_sync ) ) {
	require_once $snf_youtube_sync;
	echo 'youtube_sync:' . (int) $snf_sync_result['added'] . " added\n";
}

echo "bootstrap:done\n";
