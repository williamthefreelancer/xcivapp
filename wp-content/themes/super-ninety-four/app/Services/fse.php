<?php
/**
 * Full Site Editing helpers: legacy PHP view embed + page template migration.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map of FSE custom template / php-view slugs → relative theme PHP paths.
 *
 * @return array<string, string>
 */
function snf_fse_view_map() {
	return array(
		'front-page'            => 'front-page.php',
		'home'                  => 'home.php',
		'index'                 => 'index.php',
		'page'                  => 'page.php',
		'single'                => 'single.php',
		'singular'              => 'singular.php',
		'archive'               => 'archive.php',
		'search'                => 'search.php',
		'404'                   => '404.php',
		'archive-event'         => 'archive-event.php',
		'single-event'          => 'single-event.php',
		'archive-snf_audio'     => 'archive-snf_audio.php',
		'single-snf_audio'      => 'single-snf_audio.php',
		'archive-snf_video'     => 'archive-snf_video.php',
		'single-snf_video'      => 'single-snf_video.php',
		'buddypress'            => 'buddypress.php',
		'apply-hub'             => 'page-templates/apply.php',
		'about'                 => 'page-templates/about.php',
		'billing-details'       => 'page-templates/billing-details.php',
		'blank-canvas'          => 'page-templates/blank-canvas.php',
		'expo-marketplace'      => 'page-templates/expo-marketplace.php',
		'home-page'             => 'page-templates/home.php',
		'legal'                 => 'page-templates/legal.php',
		'member-login'          => 'page-templates/member-login.php',
		'messages'              => 'page-templates/messages.php',
		'public-content'        => 'page-templates/public-content.php',
		'reciprocal-club'       => 'page-templates/reciprocal-club.php',
		'reciprocal-hub'        => 'page-templates/reciprocal-hub.php',
		'select-events'         => 'page-templates/select-events.php',
		'under-consideration'   => 'page-templates/under-consideration.php',
		'videos-audio'          => 'page-templates/videos-audio.php',
		'woocommerce-account'   => 'page-templates/woocommerce-account.php',
	);
}

/**
 * Classic page-template paths → FSE custom template slugs.
 *
 * @return array<string, string>
 */
function snf_fse_classic_page_template_map() {
	return array(
		'page-templates/apply.php'                 => 'apply-hub',
		'page-templates/about.php'                 => 'about',
		'page-templates/billing-details.php'       => 'billing-details',
		'page-templates/blank-canvas.php'          => 'blank-canvas',
		'page-templates/expo-marketplace.php'      => 'expo-marketplace',
		'page-templates/home.php'                  => 'home-page',
		'page-templates/legal.php'                 => 'legal',
		'page-templates/member-login.php'          => 'member-login',
		'page-templates/messages.php'              => 'messages',
		'page-templates/public-content.php'        => 'public-content',
		'page-templates/reciprocal-club.php'       => 'reciprocal-club',
		'page-templates/reciprocal-hub.php'        => 'reciprocal-hub',
		'page-templates/select-events.php'         => 'select-events',
		'page-templates/under-consideration.php'   => 'under-consideration',
		'page-templates/videos-audio.php'          => 'videos-audio',
		'page-templates/woocommerce-account.php'   => 'woocommerce-account',
	);
}

/**
 * Resolve a php-view slug to an absolute file path, or empty string.
 *
 * @param string $slug View slug.
 * @return string
 */
function snf_fse_resolve_view_path( $slug ) {
	$slug = sanitize_title( (string) $slug );
	$map  = snf_fse_view_map();
	if ( ! isset( $map[ $slug ] ) ) {
		return '';
	}
	$path = get_template_directory() . '/' . $map[ $slug ];
	return is_readable( $path ) ? $path : '';
}

/**
 * Render a legacy PHP template without document chrome (for FSE block embed).
 *
 * @param string $slug View slug from snf_fse_view_map().
 * @return string
 */
function snf_fse_render_php_view( $slug ) {
	$path = snf_fse_resolve_view_path( $slug );
	if ( ! $path ) {
		return '';
	}

	$GLOBALS['snf_fse_embed'] = true;
	ob_start();
	include $path;
	$html = (string) ob_get_clean();
	unset( $GLOBALS['snf_fse_embed'] );

	return $html;
}

/**
 * Whether the active theme is a block theme (has templates/index.html).
 *
 * @return bool
 */
function snf_fse_is_block_theme() {
	return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
}

/**
 * is_page_template() that accepts classic PHP paths and FSE custom template slugs.
 *
 * @param string|string[] $template Template path(s) or FSE slug(s).
 * @return bool
 */
function snf_is_page_template( $template ) {
	$templates = is_array( $template ) ? $template : array( $template );
	$map       = snf_fse_classic_page_template_map();
	$flip      = array_flip( $map );
	$candidates = array();

	foreach ( $templates as $item ) {
		$item = (string) $item;
		if ( '' === $item ) {
			continue;
		}
		$candidates[] = $item;
		if ( isset( $map[ $item ] ) ) {
			$candidates[] = $map[ $item ];
		}
		if ( isset( $flip[ $item ] ) ) {
			$candidates[] = $flip[ $item ];
		}
	}

	foreach ( array_unique( $candidates ) as $candidate ) {
		if ( is_page_template( $candidate ) ) {
			return true;
		}
	}

	return false;
}

/**
 * One-time remap of classic page template meta to FSE custom template slugs.
 */
function snf_fse_maybe_migrate_page_templates() {
	global $wpdb;

	$map           = snf_fse_classic_page_template_map();
	$classic_paths = array_keys( $map );
	$placeholders  = implode( ',', array_fill( 0, count( $classic_paths ), '%s' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built from fixed map size.
	$sql = $wpdb->prepare(
		"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_page_template' AND meta_value IN ($placeholders)",
		$classic_paths
	);

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$rows = $wpdb->get_results( $sql );
	if ( ! $rows ) {
		update_option( 'snf_fse_page_templates_migrated', SNF_VERSION, false );
		return;
	}

	foreach ( $rows as $row ) {
		$classic = (string) $row->meta_value;
		if ( ! isset( $map[ $classic ] ) ) {
			continue;
		}
		update_post_meta( (int) $row->post_id, '_wp_page_template', $map[ $classic ] );
	}

	update_option( 'snf_fse_page_templates_migrated', SNF_VERSION, false );
}
add_action( 'init', 'snf_fse_maybe_migrate_page_templates', 20 );

/**
 * Keep classic PHP page templates out of the editor dropdown once FSE custom templates exist.
 *
 * @param array<string, string> $templates Templates.
 * @return array<string, string>
 */
function snf_fse_filter_theme_page_templates( $templates ) {
	if ( ! snf_fse_is_block_theme() ) {
		return $templates;
	}
	foreach ( array_keys( snf_fse_classic_page_template_map() ) as $classic ) {
		unset( $templates[ $classic ] );
	}
	return $templates;
}
add_filter( 'theme_page_templates', 'snf_fse_filter_theme_page_templates', 20 );
