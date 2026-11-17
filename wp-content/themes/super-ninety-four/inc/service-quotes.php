<?php
/**
 * Service quote landings (Figma quote-single-page 2166:165).
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce product slugs that use the quote-single landing.
 *
 * @return string[]
 */
function snf_get_service_quote_product_slugs() {
	return array(
		'litter-pickup-service',
		'janitorial-cleaning-service',
	);
}

/**
 * Legacy page slug → product slug redirects.
 *
 * @return array<string, string>
 */
function snf_get_service_quote_legacy_redirects() {
	return array(
		'grounds-litter-pickup'       => 'litter-pickup-service',
		'commercial-janitor-cleaning' => 'janitorial-cleaning-service',
		'litter-pickup-service'       => 'litter-pickup-service',
		'janitorial-cleaning-service' => 'janitorial-cleaning-service',
	);
}

/**
 * Whether a post is a service quote product landing.
 *
 * @param int|WP_Post|null $post Post ID or object.
 * @return bool
 */
function snf_is_service_quote_product( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || 'product' !== $post->post_type ) {
		return false;
	}

	return in_array( $post->post_name, snf_get_service_quote_product_slugs(), true );
}

/**
 * Permalink for a service quote product by slug.
 *
 * @param string $slug Product slug.
 * @return string
 */
function snf_get_service_quote_product_url( $slug ) {
	$slug    = sanitize_title( $slug );
	$product = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $product instanceof WP_Post && 'publish' === $product->post_status ) {
		return get_permalink( $product );
	}

	return home_url( '/product/' . $slug . '/' );
}

/**
 * Quote landing content keyed by product slug.
 *
 * @param string $slug Product slug.
 * @return array<string, mixed>|null
 */
function snf_get_service_quote( $slug = '' ) {
	$slug     = $slug ? sanitize_title( $slug ) : '';
	$img_base = get_template_directory_uri() . '/assets/images/services/';
	$apply    = snf_get_page_url( 'apply' );

	$quotes = array(
		'litter-pickup-service' => array(
			'title'            => __( 'Litter Pickup Service', 'super-ninety-four' ),
			'lede'             => __( 'Cleaning up litter around parking lots so properties are clean and professionals or client that come to those offices with a smile.', 'super-ninety-four' ),
			'image'            => $img_base . 'litter-pickup-service-image-2.png',
			'can_heading'      => __( 'What Can SUPER NINETY FOUR Do For You?', 'super-ninety-four' ),
			'can_items'        => array(
				__( 'Mapped walk routes for parks, lots, and tenant frontage', 'super-ninety-four' ),
				__( 'Bagged debris removed before peak guest hours', 'super-ninety-four' ),
				__( 'Bar-tenant curb lines kept camera-ready', 'super-ninety-four' ),
				__( 'Photo log of cleared zones after each visit', 'super-ninety-four' ),
				__( 'One invoice lane for multi-site property managers', 'super-ninety-four' ),
			),
			'cannot_heading'   => __( 'What SUPER NINETY FOUR Can Not Do For You?', 'super-ninety-four' ),
			'cannot_items'     => array(
				__( 'Interior suite or restroom janitorial cleaning', 'super-ninety-four' ),
				__( 'Landscaping, mowing, or hardscape repair', 'super-ninety-four' ),
				__( 'Hazardous or regulated waste removal', 'super-ninety-four' ),
				__( 'Same-hour emergency sweeps outside booked windows', 'super-ninety-four' ),
				__( 'Unofficial cash work without a documented route', 'super-ninety-four' ),
			),
			'pricing_heading'  => __( 'Service Pricing Is', 'super-ninety-four' ),
			'plan_title'       => __( '4X/Weekly Overnight (Sun-Wed)', 'super-ninety-four' ),
			'price'            => '$400.00/mo',
			'card_description' => __( 'Exterior litter pickup on a fixed overnight route for business-park grounds and bar-tenant frontage — bagged, logged, and cleared to a photographed standard.', 'super-ninety-four' ),
			'cta_label'        => __( 'Get Quote Now', 'super-ninety-four' ),
			'cta_url'          => $apply,
		),
		'janitorial-cleaning-service' => array(
			'title'            => __( 'Janitorial Cleaning Service', 'super-ninety-four' ),
			'lede'             => __( 'Interior janitorial coverage for business-park suites and bar tenants — restrooms, floors, and back-of-house reset on a calendar you can audit.', 'super-ninety-four' ),
			'image'            => $img_base . 'commercial-janitor-cleaning.jpg',
			'can_heading'      => __( 'What Can SUPER NINETY FOUR Do For You?', 'super-ninety-four' ),
			'can_items'        => array(
				__( 'Restroom and floor reset to a written checklist', 'super-ninety-four' ),
				__( 'Bar back-of-house wipe-down after service hours', 'super-ninety-four' ),
				__( 'Suite common areas kept tenant-ready', 'super-ninety-four' ),
				__( 'Supply restock notes for managers', 'super-ninety-four' ),
				__( 'Recurring calendar with visit confirmation', 'super-ninety-four' ),
			),
			'cannot_heading'   => __( 'What SUPER NINETY FOUR Can Not Do For You?', 'super-ninety-four' ),
			'cannot_items'     => array(
				__( 'Exterior litter routes or parking-lot grounds work', 'super-ninety-four' ),
				__( 'Construction debris or post-renovation deep cleans', 'super-ninety-four' ),
				__( 'Biohazard or trauma remediation', 'super-ninety-four' ),
				__( 'Unscheduled overnight cleans without a booked visit', 'super-ninety-four' ),
				__( 'Cash-only work outside the recurring contract', 'super-ninety-four' ),
			),
			'pricing_heading'  => __( 'Service Pricing Is', 'super-ninety-four' ),
			'plan_title'       => __( '4X/Weekly Overnight (Sun-Wed)', 'super-ninety-four' ),
			'price'            => '$400.00/mo',
			'card_description' => __( 'Commercial janitorial cleaning for suites and bar tenants: checklist-driven interior reset on a recurring overnight cadence you can verify.', 'super-ninety-four' ),
			'cta_label'        => __( 'Get Quote Now', 'super-ninety-four' ),
			'cta_url'          => $apply,
		),
	);

	/**
	 * Filter service quote landing content.
	 *
	 * @param array $quotes Quote definitions keyed by product slug.
	 */
	$quotes = apply_filters( 'snf_service_quote_content', $quotes );

	if ( $slug && isset( $quotes[ $slug ] ) ) {
		return $quotes[ $slug ];
	}

	return null;
}

/**
 * Delete obsolete service inquiry scaffolding pages (products are kept).
 */
function snf_delete_service_inquiry_scaffolding_pages() {
	if ( get_option( 'snf_service_quote_scaffold_cleaned_v1' ) ) {
		return;
	}

	$slugs = array(
		'grounds-litter-pickup',
		'line-sitting-service',
		'courier-service',
		'commercial-janitor-cleaning',
		'service-inquiries',
		'parking-lot-commercial-grounds-cleaning',
		'professional-line-sitting-service',
		'commercial-janitorial-cleaning-consulting',
	);

	foreach ( $slugs as $slug ) {
		foreach ( array( 'page', 'event', 'post' ) as $post_type ) {
			$legacy = get_page_by_path( $slug, OBJECT, $post_type );
			if ( $legacy instanceof WP_Post && 'product' !== $legacy->post_type ) {
				wp_delete_post( $legacy->ID, true );
			}
		}
	}

	update_option( 'snf_service_quote_scaffold_cleaned_v1', 1, false );
}
add_action( 'after_switch_theme', 'snf_delete_service_inquiry_scaffolding_pages' );
add_action( 'admin_init', 'snf_delete_service_inquiry_scaffolding_pages', 32 );

/**
 * Redirect legacy service page URLs to WooCommerce quote products.
 */
function snf_redirect_legacy_service_quote_pages() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( function_exists( 'is_product' ) && is_product() ) {
		return;
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( ! $request_path ) {
		return;
	}

	foreach ( snf_get_service_quote_legacy_redirects() as $legacy_slug => $product_slug ) {
		if ( preg_match( '#/product/' . preg_quote( $product_slug, '#' ) . '/?$#', $request_path ) ) {
			continue;
		}
		if ( preg_match( '#/' . preg_quote( $legacy_slug, '#' ) . '/?$#', $request_path ) ) {
			wp_safe_redirect( snf_get_service_quote_product_url( $product_slug ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'snf_redirect_legacy_service_quote_pages', 16 );

/**
 * Permanently delete the retired line-sitting/courier Woo product.
 */
function snf_delete_retired_line_sitting_courier_product() {
	if ( get_option( 'snf_deleted_line_sitting_courier_product_v1' ) ) {
		return;
	}

	$slug = 'professional-line-sitting-courier-service';
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $post instanceof WP_Post ) {
		wp_delete_post( $post->ID, true );
	}

	update_option( 'snf_deleted_line_sitting_courier_product_v1', 1, false );
}
add_action( 'after_switch_theme', 'snf_delete_retired_line_sitting_courier_product' );
add_action( 'admin_init', 'snf_delete_retired_line_sitting_courier_product', 33 );
add_action( 'init', 'snf_delete_retired_line_sitting_courier_product', 60 );

/**
 * Send retired line-sitting/courier URLs to the shop.
 */
function snf_redirect_retired_line_sitting_courier_product() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( ! $request_path ) {
		return;
	}

	$patterns = array(
		'#/product/professional-line-sitting-courier-service/?$#',
		'#/line-sitting-service/?$#',
		'#/courier-service/?$#',
		'#/professional-line-sitting-service/?$#',
	);

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $request_path ) ) {
			$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
			wp_safe_redirect( $shop ? $shop : home_url( '/' ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'snf_redirect_retired_line_sitting_courier_product', 15 );
