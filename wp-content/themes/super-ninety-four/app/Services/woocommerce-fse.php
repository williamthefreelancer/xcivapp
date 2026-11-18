<?php
/**
 * WooCommerce + block theme: store pages, classic shells, FSE template hierarchy.
 *
 * Keeps SNF PHP product/cart/checkout/account markup inside Site Editor chrome
 * without WooCommerce’s default block cart/checkout taking over.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical WooCommerce store page slugs for this project.
 *
 * @return array<string, string> option_key_suffix => page slug
 */
function snf_wc_store_page_slugs() {
	return array(
		'shop'      => 'shop',
		'cart'      => 'cart',
		'checkout'  => 'checkout',
		'myaccount' => 'my-account',
	);
}

/**
 * Resolve a published page ID by slug.
 *
 * @param string $slug Page slug.
 * @return int
 */
function snf_wc_find_page_id_by_slug( $slug ) {
	$page = get_page_by_path( sanitize_title( (string) $slug ) );
	return ( $page instanceof WP_Post && 'publish' === $page->post_status ) ? (int) $page->ID : 0;
}

/**
 * Link WooCommerce page options to existing pages and force classic shortcodes.
 *
 * Cart/checkout Blocks conflict with snf-payments Elements + theme form-checkout.
 */
function snf_wc_ensure_store_pages() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$version = '2.31.0-wc-fse-store';
	$force   = ( get_option( 'snf_wc_store_pages_version' ) !== $version );

	foreach ( snf_wc_store_page_slugs() as $key => $slug ) {
		$option  = 'woocommerce_' . $key . '_page_id';
		$current = (int) get_option( $option );
		$page_id = $current > 0 ? $current : snf_wc_find_page_id_by_slug( $slug );

		if ( $page_id <= 0 ) {
			continue;
		}

		if ( $current !== $page_id ) {
			update_option( $option, $page_id );
		}

		if ( 'shop' === $key ) {
			// Shop is a product archive driven by templates/archive-product.html.
			$shop_tpl = (string) get_post_meta( $page_id, '_wp_page_template', true );
			if ( '' !== $shop_tpl && 'default' !== $shop_tpl ) {
				delete_post_meta( $page_id, '_wp_page_template' );
			}
			continue;
		}

		if ( ! $force ) {
			continue;
		}

		if ( 'myaccount' === $key ) {
			update_post_meta( $page_id, '_wp_page_template', 'woocommerce-account' );
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => "<!-- wp:shortcode -->\n[woocommerce_my_account]\n<!-- /wp:shortcode -->",
				)
			);
			continue;
		}

		if ( 'cart' === $key ) {
			update_post_meta( $page_id, '_wp_page_template', 'woocommerce-cart' );
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => "<!-- SNF: classic cart for themed shop_table + snf-payments -->\n[woocommerce_cart]",
				)
			);
			continue;
		}

		if ( 'checkout' === $key ) {
			update_post_meta( $page_id, '_wp_page_template', 'woocommerce-checkout' );
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => "<!-- SNF Payments: classic checkout required for Stripe Elements gateway -->\n[woocommerce_checkout]",
				)
			);
		}
	}

	if ( $force ) {
		// Prefer classic shortcode checkout over Cart/Checkout Blocks.
		update_option( 'woocommerce_cart_checkout_blocks_enabled', 'no' );
		update_option( 'snf_wc_store_pages_version', $version, false );
	}
}
add_action( 'init', 'snf_wc_ensure_store_pages', 6 );

/**
 * Drop broken plugin entries that are active in options but missing on disk.
 */
function snf_wc_prune_missing_active_plugins() {
	$active = (array) get_option( 'active_plugins', array() );
	if ( ! $active ) {
		return;
	}

	$keep = array();
	$changed = false;
	foreach ( $active as $plugin ) {
		$path = WP_PLUGIN_DIR . '/' . $plugin;
		if ( is_readable( $path ) ) {
			$keep[] = $plugin;
			continue;
		}
		$changed = true;
	}

	if ( $changed ) {
		update_option( 'active_plugins', array_values( $keep ) );
	}
}
add_action( 'plugins_loaded', 'snf_wc_prune_missing_active_plugins', 1 );

/**
 * Keep Cart/Checkout Blocks off so theme + snf-payments classic shells win.
 *
 * @param mixed $value Feature flag value.
 * @return string
 */
function snf_wc_disable_cart_checkout_blocks_feature( $value ) {
	return 'no';
}
add_filter( 'option_woocommerce_feature_cart_checkout_blocks_enabled', 'snf_wc_disable_cart_checkout_blocks_feature' );
add_filter( 'default_option_woocommerce_feature_cart_checkout_blocks_enabled', 'snf_wc_disable_cart_checkout_blocks_feature' );
add_filter( 'option_woocommerce_cart_checkout_blocks_enabled', 'snf_wc_disable_cart_checkout_blocks_feature' );

/**
 * Disable Woo block-template hook injection.
 *
 * Compatibility layer marks first/last blocks and re-fires classic product hooks
 * around them, which empties SNF quote/workshop landings and fights legacy PHP.
 *
 * @return bool
 */
function snf_wc_disable_block_template_compatibility_layer() {
	return true;
}
add_filter( 'woocommerce_disable_compatibility_layer', 'snf_wc_disable_block_template_compatibility_layer' );

/**
 * Body classes for themed commerce surfaces.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function snf_wc_body_classes( $classes ) {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return $classes;
	}

	if ( is_cart() ) {
		$classes[] = 'snf-is-cart';
	}
	if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
		$classes[] = 'snf-is-checkout';
	}
	if ( is_account_page() ) {
		$classes[] = 'snf-is-account';
	}
	if ( is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() ) {
		$classes[] = 'snf-is-shop-archive';
	}
	if ( is_product() ) {
		$classes[] = 'snf-is-product';
		$product_id = (int) get_queried_object_id();
		$blank      = (
			( function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( $product_id ) )
			|| ( function_exists( 'snf_is_reciprocal_club_landing' ) && snf_is_reciprocal_club_landing( $product_id ) )
			|| ( function_exists( 'snf_is_service_quote_product' ) && snf_is_service_quote_product( $product_id ) )
			|| ( function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( $product_id ) )
		);
		if ( $blank ) {
			$classes[] = 'snf-no-header';
			$classes[] = 'snf-product-blank-chrome';
		}
	}

	return $classes;
}
add_filter( 'body_class', 'snf_wc_body_classes' );
