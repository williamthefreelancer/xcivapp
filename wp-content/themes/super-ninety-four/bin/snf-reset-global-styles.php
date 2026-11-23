<?php
/**
 * Reset Site Editor Global Styles to theme.json defaults.
 *
 * Deletes customized `wp_global_styles` posts for the active theme so
 * Appearance → Editor → Styles uses theme.json again.
 *
 * Usage: wp eval-file wp-content/themes/super-ninety-four/bin/snf-reset-global-styles.php
 *
 * @package Super_Ninety_Four
 */

$stylesheet = get_stylesheet();
$template   = get_template();
$deleted    = 0;

$posts = get_posts(
	array(
		'post_type'              => 'wp_global_styles',
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	)
);

foreach ( $posts as $post ) {
	$name = (string) $post->post_name;
	$match = (
		false !== strpos( $name, $stylesheet )
		|| false !== strpos( $name, $template )
		|| false !== strpos( $name, 'super-ninety-four' )
	);

	if ( ! $match ) {
		continue;
	}

	$ok = wp_delete_post( (int) $post->ID, true );
	if ( $ok ) {
		++$deleted;
		echo 'deleted:' . $post->ID . ':' . $name . PHP_EOL;
	}
}

if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

echo 'stylesheet:' . $stylesheet . PHP_EOL;
echo 'deleted_count:' . $deleted . PHP_EOL;
echo 'global_styles:reset' . PHP_EOL;
