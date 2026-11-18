<?php
/**
 * Front-end Core Web Vitals helpers: trim third-party assets, resource hints,
 * deferred theme scripts, and image helpers for WebP + priority.
 *
 * @package SuperNinetyFour
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether WooCommerce storefront assets are needed on this request.
 *
 * @return bool
 */
function snf_needs_woocommerce_assets() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return false;
	}

	if ( is_admin() ) {
		return true;
	}

	if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
		return true;
	}

	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		return true;
	}

	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
		return true;
	}

	// Theme surfaces that still rely on Woo templates / fragments.
	if ( function_exists( 'snf_is_expo_marketplace_page' ) && snf_is_expo_marketplace_page() ) {
		return true;
	}

	if ( is_page_template( 'page-templates/select-events.php' )
		|| is_page( 'select-events-by-members' )
		|| is_page( 'select-events-by-non-members' )
	) {
		return true;
	}

	/**
	 * Filter whether WooCommerce CSS/JS should load.
	 *
	 * @param bool $needed Default decision.
	 */
	return (bool) apply_filters( 'snf_needs_woocommerce_assets', false );
}

/**
 * Whether BuddyPress Nouveau assets are needed.
 *
 * @return bool
 */
function snf_needs_buddypress_assets() {
	if ( ! function_exists( 'buddypress' ) && ! class_exists( 'BuddyPress', false ) ) {
		return false;
	}

	if ( function_exists( 'is_buddypress' ) && is_buddypress() ) {
		return true;
	}

	if ( function_exists( 'snf_is_bp_member_profile' ) && snf_is_bp_member_profile() ) {
		return true;
	}

	return (bool) apply_filters( 'snf_needs_buddypress_assets', false );
}

/**
 * Whether the theme marketplace island (and jQuery) are needed.
 *
 * @return bool
 */
function snf_needs_marketplace_assets() {
	$needed = ( function_exists( 'snf_is_expo_marketplace_page' ) && snf_is_expo_marketplace_page() )
		|| ( function_exists( 'is_shop' ) && is_shop() )
		|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
		|| is_post_type_archive( 'event' )
		|| is_page_template( 'page-templates/select-events.php' )
		|| is_page( 'select-events-by-members' )
		|| is_page( 'select-events-by-non-members' );

	return (bool) apply_filters( 'snf_needs_marketplace_assets', $needed );
}

/**
 * Strip emoji detection scripts and related styles from public views.
 */
function snf_disable_emoji_assets() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'snf_disable_emoji_assets' );

/**
 * Prefetch / preconnect only what we still load remotely.
 *
 * @param array<int, object|string> $urls          Existing hints.
 * @param string                    $relation_type Relation type.
 * @return array<int, object|string>
 */
function snf_resource_hints( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$urls = array_filter(
			$urls,
			static function ( $url ) {
				$host = is_string( $url ) ? $url : ( is_object( $url ) && isset( $url->href ) ? (string) $url->href : '' );
				return false === strpos( $host, 'fonts.googleapis.com' )
					&& false === strpos( $host, 'fonts.gstatic.com' );
			}
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'snf_resource_hints', 20, 2 );

/**
 * Preload critical theme CSS and primary latin font files.
 */
function snf_preload_critical_assets() {
	if ( is_admin() ) {
		return;
	}

	$theme_uri = get_template_directory_uri();
	$theme_dir = get_template_directory();

	$css_path = $theme_dir . '/dist/main.css';
	if ( file_exists( $css_path ) ) {
		printf(
			'<link rel="preload" href="%s" as="style" />' . "\n",
			esc_url( $theme_uri . '/dist/main.css?ver=' . rawurlencode( (string) filemtime( $css_path ) ) )
		);
	}

	$font_css = $theme_dir . '/assets/fonts/snf-fonts.css';
	if ( file_exists( $font_css ) ) {
		printf(
			'<link rel="preload" href="%s" as="style" />' . "\n",
			esc_url( $theme_uri . '/assets/fonts/snf-fonts.css?ver=' . rawurlencode( (string) filemtime( $font_css ) ) )
		);
	}

	// Preload the primary latin faces used above the fold.
	$preload_fonts = array(
		'OpenSans-Regular.woff2',
		'LeagueGothic-Regular.woff2',
	);
	foreach ( $preload_fonts as $font_file ) {
		$path = $theme_dir . '/assets/fonts/' . $font_file;
		if ( ! file_exists( $path ) ) {
			continue;
		}
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin />' . "\n",
			esc_url( $theme_uri . '/assets/fonts/' . rawurlencode( $font_file ) )
		);
	}
}
add_action( 'wp_head', 'snf_preload_critical_assets', 1 );

/**
 * Dequeue unused core / plugin front-end assets.
 */
function snf_trim_front_assets() {
	if ( is_admin() ) {
		return;
	}

	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-blocks-style' );
	wp_dequeue_style( 'wc-blocks-vendors-style' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );

	if ( ! snf_needs_woocommerce_assets() ) {
		$wc_styles = array(
			'woocommerce-general',
			'woocommerce-layout',
			'woocommerce-smallscreen',
			'woocommerce-inline',
			'wc-blocks-style',
			'wc-blocks-vendors-style',
			'woocommerce_payments',
			'WCPAY_ASSETS',
		);
		foreach ( $wc_styles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}

		$wc_scripts = array(
			'woocommerce',
			'wc-add-to-cart',
			'wc-cart-fragments',
			'wc-checkout',
			'wc-single-product',
			'wc-jquery-blockui',
			'jquery-blockui',
			'js-cookie',
			'wc-js-cookie',
			'sourcebuster-js',
			'wc-order-attribution',
			'wc-credit-card-form',
			'stripe',
			'woocommerce_payments',
			'WCPAY_ASSETS',
		);
		foreach ( $wc_scripts as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}

	if ( ! snf_needs_buddypress_assets() ) {
		$bp_styles = array(
			'bp-nouveau',
			'bp-member-block',
			'bp-members-block',
			'bp-groups-block',
			'bp-legacy-css',
		);
		foreach ( $bp_styles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
		$bp_scripts = array(
			'bp-nouveau',
			'bp-widget-members',
			'bp-jquery-query',
			'bp-jquery-cookie',
		);
		foreach ( $bp_scripts as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}

	if ( ! snf_needs_marketplace_assets() && ! snf_needs_woocommerce_assets() ) {
		wp_dequeue_script( 'jquery-migrate' );
	}
}
add_action( 'wp_enqueue_scripts', 'snf_trim_front_assets', 100 );

/**
 * Ensure the Vite entry loads as an ES module so dynamic island chunks resolve.
 *
 * @param string $tag    Script HTML.
 * @param string $handle Script handle.
 * @param string $src    Script URL.
 * @return string
 */
function snf_module_script_loader_tag( $tag, $handle, $src ) {
	if ( 'snf-main' !== $handle ) {
		return $tag;
	}

	return sprintf(
		'<script type="module" src="%s" id="snf-main-js"></script>' . "\n",
		esc_url( $src )
	);
}
add_filter( 'script_loader_tag', 'snf_module_script_loader_tag', 10, 3 );

/**
 * Prefer WebP when a sibling file exists beside a theme image URL.
 *
 * @param string $url Absolute or theme-relative image URL.
 * @return string
 */
function snf_prefer_webp_url( $url ) {
	$url = (string) $url;
	if ( '' === $url || false !== strpos( $url, '.webp' ) ) {
		return $url;
	}

	$theme_uri = get_template_directory_uri();
	$theme_dir = get_template_directory();
	if ( 0 !== strpos( $url, $theme_uri . '/' ) ) {
		return $url;
	}

	$relative = substr( $url, strlen( $theme_uri ) + 1 );
	$path     = $theme_dir . '/' . $relative;
	$webp     = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $path );
	if ( $webp && file_exists( $webp ) ) {
		return preg_replace( '/\.(jpe?g|png)$/i', '.webp', $url );
	}

	return $url;
}

/**
 * Print a responsive theme image with optional WebP source and dimensions.
 *
 * @param string               $src     Image URL (jpg/png).
 * @param array<string, mixed> $attrs   img attributes (alt, width, height, class, loading, fetchpriority).
 * @return void
 */
function snf_the_optimized_image( $src, $attrs = array() ) {
	$src  = (string) $src;
	$alt  = isset( $attrs['alt'] ) ? (string) $attrs['alt'] : '';
	$width  = isset( $attrs['width'] ) ? (int) $attrs['width'] : 0;
	$height = isset( $attrs['height'] ) ? (int) $attrs['height'] : 0;
	$class  = isset( $attrs['class'] ) ? (string) $attrs['class'] : '';
	$loading = isset( $attrs['loading'] ) ? (string) $attrs['loading'] : 'lazy';
	$decoding = isset( $attrs['decoding'] ) ? (string) $attrs['decoding'] : 'async';
	$fetchpriority = isset( $attrs['fetchpriority'] ) ? (string) $attrs['fetchpriority'] : '';

	$webp = snf_prefer_webp_url( $src );
	$img_attrs = array(
		'src'      => $src,
		'alt'      => $alt,
		'decoding' => $decoding,
	);
	if ( $width > 0 ) {
		$img_attrs['width'] = (string) $width;
	}
	if ( $height > 0 ) {
		$img_attrs['height'] = (string) $height;
	}
	if ( '' !== $class ) {
		$img_attrs['class'] = $class;
	}
	if ( '' !== $loading && 'eager' !== $loading ) {
		$img_attrs['loading'] = $loading;
	} elseif ( 'eager' === $loading ) {
		$img_attrs['loading'] = 'eager';
	}
	if ( '' !== $fetchpriority ) {
		$img_attrs['fetchpriority'] = $fetchpriority;
	}

	$attr_html = '';
	foreach ( $img_attrs as $key => $value ) {
		$attr_html .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
	}

	if ( $webp && $webp !== $src ) {
		printf(
			'<picture><source srcset="%s" type="image/webp"><img%s></picture>',
			esc_url( $webp ),
			$attr_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped attrs.
		);
		return;
	}

	printf( '<img%s>', $attr_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Add width/height defaults for content images missing dimensions (CLS).
 *
 * @param array<string, string>|mixed $attr       Attributes.
 * @param WP_Post                     $attachment Attachment.
 * @return array<string, string>|mixed
 */
function snf_attachment_image_attrs( $attr, $attachment ) {
	if ( ! is_array( $attr ) ) {
		return $attr;
	}
	if ( empty( $attr['decoding'] ) ) {
		$attr['decoding'] = 'async';
	}
	if ( empty( $attr['loading'] ) ) {
		$attr['loading'] = 'lazy';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'snf_attachment_image_attrs', 10, 2 );
