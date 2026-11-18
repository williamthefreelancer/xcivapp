<?php
/**
 * Block editor / Site Editor configuration for the block theme.
 *
 * Registers pattern categories, opt-in block styles, and keeps classic-theme
 * assets / page-template registration out of the editor.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pattern categories available in the inserter.
 *
 * @return array<string, array{label:string, description:string}>
 */
function snf_editor_pattern_categories() {
	return array(
		'snf'            => array(
			'label'       => __( 'Super Ninety Four', 'super-ninety-four' ),
			'description' => __( 'Brand layouts for marketing and hub canvases.', 'super-ninety-four' ),
		),
		'snf-funnel'     => array(
			'label'       => __( 'SNF Funnels', 'super-ninety-four' ),
			'description' => __( 'Post-purchase upsell and downsell offer layouts.', 'super-ninety-four' ),
		),
		'snf-marketing'  => array(
			'label'       => __( 'SNF Marketing', 'super-ninety-four' ),
			'description' => __( 'Contact, newsletter, and public content sections.', 'super-ninety-four' ),
		),
		'snf-listings'   => array(
			'label'       => __( 'SNF Listings', 'super-ninety-four' ),
			'description' => __( 'Event, reciprocal, Expo, and service listing grids.', 'super-ninety-four' ),
		),
		'snf-woocommerce'=> array(
			'label'       => __( 'SNF WooCommerce', 'super-ninety-four' ),
			'description' => __( 'Store shell helpers for cart, checkout, and account pages.', 'super-ninety-four' ),
		),
	);
}

/**
 * Register pattern categories early so /patterns headers resolve.
 */
function snf_editor_register_pattern_categories() {
	foreach ( snf_editor_pattern_categories() as $slug => $args ) {
		register_block_pattern_category( $slug, $args );
	}
}
add_action( 'init', 'snf_editor_register_pattern_categories', 5 );

/**
 * Block styles registered in PHP (complements styles/*.json variations).
 *
 * @return array<int, array{block:string, name:string, label:string, inline_style?:string}>
 */
function snf_editor_block_styles() {
	return array(
		array(
			'block' => 'core/separator',
			'name'  => 'wide-brand',
			'label' => __( 'Wide brand', 'super-ninety-four' ),
			'inline_style' => '.wp-block-separator.is-style-wide-brand{width:100%;max-width:100%;border:none;height:0.0625rem;background:linear-gradient(90deg,transparent,rgba(129,91,90,.85),transparent)}',
		),
		array(
			'block' => 'core/buttons',
			'name'  => 'stack',
			'label' => __( 'Stack', 'super-ninety-four' ),
			'inline_style' => '.wp-block-buttons.is-style-stack{flex-direction:column;align-items:stretch}.wp-block-buttons.is-style-stack .wp-block-button{width:100%}',
		),
		array(
			'block' => 'core/list',
			'name'  => 'checklist',
			'label' => __( 'Checklist', 'super-ninety-four' ),
			'inline_style' => '.wp-block-list.is-style-checklist{list-style:none;margin-left:0}.wp-block-list.is-style-checklist li{position:relative;margin:0 0 .65rem 1.5rem}.wp-block-list.is-style-checklist li::before{content:"";position:absolute;left:0;top:.45em;width:.55rem;height:.55rem;border:0.0625rem solid rgba(243,235,232,.55);border-radius:0.0625rem}',
		),
		array(
			'block' => 'core/columns',
			'name'  => 'equal-cards',
			'label' => __( 'Equal cards', 'super-ninety-four' ),
			'inline_style' => '.wp-block-columns.is-style-equal-cards{align-items:stretch}.wp-block-columns.is-style-equal-cards > .wp-block-column{display:flex;flex-direction:column}',
		),
	);
}

/**
 * Register PHP block styles for the Site Editor / block inserter.
 */
function snf_editor_register_block_styles() {
	if ( ! function_exists( 'register_block_style' ) ) {
		return;
	}

	foreach ( snf_editor_block_styles() as $style ) {
		$args = array(
			'name'  => $style['name'],
			'label' => $style['label'],
		);
		if ( ! empty( $style['inline_style'] ) ) {
			$args['inline_style'] = $style['inline_style'];
		}
		register_block_style( $style['block'], $args );
	}
}
add_action( 'init', 'snf_editor_register_block_styles', 20 );

/**
 * Block theme supports — no classic widgets / menus chrome extras.
 */
function snf_editor_block_theme_supports() {
	if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
		return;
	}

	// Block themes get title-tag / html5 from core; keep media supports only.
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );

	// Never load classic-theme stylesheet on a block theme.
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );
}
add_action( 'after_setup_theme', 'snf_editor_block_theme_supports', 20 );

/**
 * Keep block library + global styles on every front-end view for this block theme.
 *
 * @param bool $keep Whether performance.php should keep block styles.
 * @return bool
 */
function snf_editor_keep_block_styles( $keep ) {
	if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
		return true;
	}
	return (bool) $keep;
}
add_filter( 'snf_keep_block_styles', 'snf_editor_keep_block_styles' );

/**
 * Editor-only body class for theme.json tooling.
 *
 * @param string $classes Space-separated classes.
 * @return string
 */
function snf_editor_admin_body_class( $classes ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && ( 'site-editor' === $screen->base || 'post' === $screen->base ) ) {
		$classes .= ' snf-block-theme-editor';
	}
	return $classes;
}
add_filter( 'admin_body_class', 'snf_editor_admin_body_class' );
