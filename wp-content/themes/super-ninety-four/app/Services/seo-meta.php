<?php
/**
 * Lightweight meta description / Open Graph output.
 *
 * The stack has no SEO plugin; WP core emits the title and canonical URL but
 * no description or social preview metadata. Keep this intentionally small
 * and bail out if a dedicated SEO plugin is ever installed.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Should the theme emit its own meta tags?
 *
 * @return bool
 */
function snf_seo_meta_should_run() {
	if ( is_admin() ) {
		return false;
	}
	if ( class_exists( 'WPSEO_Frontend' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
		return false;
	}
	return true;
}

/**
 * Build a description for the current view.
 *
 * @return string
 */
function snf_seo_meta_description() {
	$description = '';

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$description = has_excerpt( $post )
				? get_the_excerpt( $post )
				: wp_trim_words( wp_strip_all_tags( $post->post_content ), 45, '' );
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term && ! empty( $term->description ) ) {
			$description = $term->description;
		}
	}

	$description = is_string( $description ) ? trim( wp_strip_all_tags( $description ) ) : '';
	if ( '' === $description ) {
		$description = (string) get_bloginfo( 'description' );
	}

	return wp_trim_words( $description, 45, '' );
}

/**
 * Print description + Open Graph / Twitter tags on wp_head.
 */
function snf_seo_meta_tags() {
	if ( ! snf_seo_meta_should_run() ) {
		return;
	}

	$title       = wp_get_document_title();
	$description = snf_seo_meta_description();
	$site_name   = (string) get_bloginfo( 'name' );

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? strtok( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '?' ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$url         = home_url( $request_uri ? $request_uri : '/' );

	$image = '';
	if ( is_singular() && has_post_thumbnail() ) {
		$image = (string) get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
	}
	if ( ! $image ) {
		$image = (string) get_site_icon_url( 512 );
	}

	$type = is_singular() ? 'article' : 'website';

	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
	if ( $image ) {
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'snf_seo_meta_tags', 5 );
