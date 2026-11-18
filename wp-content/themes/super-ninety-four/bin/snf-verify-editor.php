<?php
/**
 * Verify block-theme editor config (theme.json, patterns, styles).
 *
 * Usage: php wp-content/themes/super-ninety-four/bin/snf-verify-editor.php
 */

$root = dirname( __DIR__, 4 );
require $root . '/wp-load.php';

$theme_dir = get_template_directory();

$json_files = array_merge(
	array( $theme_dir . '/theme.json' ),
	glob( $theme_dir . '/styles/*.json' ) ?: array()
);

foreach ( $json_files as $file ) {
	json_decode( (string) file_get_contents( $file ) );
	$ok = JSON_ERROR_NONE === json_last_error();
	echo basename( dirname( $file ) ) . '/' . basename( $file ) . ': ' . ( $ok ? 'OK' : json_last_error_msg() ) . PHP_EOL;
}

echo 'block_theme=' . ( wp_is_block_theme() ? 'yes' : 'no' ) . PHP_EOL;
echo 'editor_service=' . ( function_exists( 'snf_editor_pattern_categories' ) ? 'ok' : 'missing' ) . PHP_EOL;

$cats = WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered();
foreach ( $cats as $cat ) {
	if ( 0 === strpos( $cat['name'], 'snf' ) ) {
		echo 'cat:' . $cat['name'] . PHP_EOL;
	}
}

$patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
foreach ( $patterns as $pattern ) {
	if ( 0 === strpos( $pattern['name'], 'super-ninety-four/' ) ) {
		echo 'pattern:' . $pattern['name'] . PHP_EOL;
	}
}

$styles = WP_Block_Styles_Registry::get_instance()->get_all_registered();
foreach ( $styles as $block => $list ) {
	foreach ( $list as $style ) {
		$name = isset( $style['name'] ) ? (string) $style['name'] : '';
		if ( '' === $name ) {
			continue;
		}
		$interesting = array( 'wide-brand', 'checklist', 'stack', 'equal-cards', 'surface', 'lede', 'ghost', 'solid', 'outline', 'muted', 'eyebrow', 'framed', 'brand' );
		foreach ( $interesting as $needle ) {
			if ( $name === $needle || false !== strpos( $name, $needle ) ) {
				echo 'style:' . $block . '/' . $name . PHP_EOL;
				break;
			}
		}
	}
}

$templates = wp_get_theme()->get_page_templates();
echo 'classic_page_templates=' . count( $templates ) . PHP_EOL;
foreach ( $templates as $file => $label ) {
	echo 'tmpl:' . $file . ' => ' . $label . PHP_EOL;
}

$settings = wp_get_global_settings();
echo 'appearanceTools=' . ( ! empty( $settings['appearanceTools'] ) ? '1' : '0' ) . PHP_EOL;
echo 'spacing_margin=' . ( ! empty( $settings['spacing']['margin'] ) ? '1' : '0' ) . PHP_EOL;
echo 'typography_lineHeight=' . ( ! empty( $settings['typography']['lineHeight'] ) ? '1' : '0' ) . PHP_EOL;
echo 'font_sizes=' . ( isset( $settings['typography']['fontSizes'] ) ? count( (array) $settings['typography']['fontSizes'] ) : 0 ) . PHP_EOL;
echo 'spacing_sizes=' . ( isset( $settings['spacing']['spacingSizes'] ) ? count( (array) $settings['spacing']['spacingSizes'] ) : 0 ) . PHP_EOL;
echo 'done' . PHP_EOL;
