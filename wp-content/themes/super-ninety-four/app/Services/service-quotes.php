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
		'professional-line-sitting-courier-service',
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
		'grounds-litter-pickup'                     => 'litter-pickup-service',
		'line-sitting-service'                      => 'professional-line-sitting-courier-service',
		'courier-service'                           => 'professional-line-sitting-courier-service',
		'professional-line-sitting-service'         => 'professional-line-sitting-courier-service',
		'commercial-janitor-cleaning'               => 'janitorial-cleaning-service',
		'litter-pickup-service'                     => 'litter-pickup-service',
		'janitorial-cleaning-service'               => 'janitorial-cleaning-service',
		'professional-line-sitting-courier-service' => 'professional-line-sitting-courier-service',
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
			'image'            => $img_base . 'litter-pickup-service-image-2.jpg',
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
		'professional-line-sitting-courier-service' => array(
			'title'            => __( 'Professional Line Sitting & Courier Service', 'super-ninety-four' ),
			'lede'             => __( 'Trained sitters and discreet couriers hold your place and move consumer goods so principals reclaim the hours without losing the drop, release, or handoff window.', 'super-ninety-four' ),
			'image'            => $img_base . 'lifestyle-service-image-1.jpg',
			'can_heading'      => __( 'What Can SUPER NINETY FOUR Do For You?', 'super-ninety-four' ),
			'can_items'        => array(
				__( 'On-site presence through the full queue window', 'super-ninety-four' ),
				__( 'Live status pings before your handover', 'super-ninety-four' ),
				__( 'Rules-aware holding for retail and release events', 'super-ninety-four' ),
				__( 'Pickup and delivery across mapped club corridors', 'super-ninety-four' ),
				__( 'Receipt photo log when goods change hands', 'super-ninety-four' ),
			),
			'cannot_heading'   => __( 'What SUPER NINETY FOUR Can Not Do For You?', 'super-ninety-four' ),
			'cannot_items'     => array(
				__( 'Guarantee stock if the retailer sells out before you arrive', 'super-ninety-four' ),
				__( 'Break posted venue or retailer queue rules', 'super-ninety-four' ),
				__( 'Transport illegal, hazardous, or restricted goods', 'super-ninety-four' ),
				__( 'Overnight holding without a booked request', 'super-ninety-four' ),
				__( 'Proxy identity fraud or ticket scalping', 'super-ninety-four' ),
			),
			'pricing_heading'  => __( 'Service Pricing Is', 'super-ninety-four' ),
			'plan_title'       => __( 'On-Demand Line & Courier Window', 'super-ninety-four' ),
			'price'            => '$400.00/mo',
			'card_description' => __( 'Line sitting and courier runs for consumer-goods drops: we hold your place, ping status, and hand off with a photographed receipt — one request, one outcome.', 'super-ninety-four' ),
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
 * Ensure the line-sitting/courier quote product exists (undoes prior retirement cleanup).
 */
function snf_ensure_line_sitting_courier_quote_product() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( get_option( 'snf_deleted_line_sitting_courier_product_v1' ) ) {
		delete_option( 'snf_deleted_line_sitting_courier_product_v1' );
	}

	$slug = 'professional-line-sitting-courier-service';
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $post instanceof WP_Post ) {
		if ( 'publish' !== $post->post_status ) {
			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_status' => 'publish',
				)
			);
		}
		return;
	}

	$quote = snf_get_service_quote( $slug );
	if ( ! $quote ) {
		return;
	}

	$product_id = wp_insert_post(
		array(
			'post_title'   => $quote['title'],
			'post_name'    => $slug,
			'post_content' => $quote['card_description'],
			'post_excerpt' => $quote['lede'],
			'post_status'  => 'publish',
			'post_type'    => 'product',
		),
		true
	);

	if ( is_wp_error( $product_id ) || ! $product_id ) {
		return;
	}

	wp_set_object_terms( $product_id, 'simple', 'product_type' );
	update_post_meta( $product_id, '_virtual', 'yes' );
	update_post_meta( $product_id, '_sold_individually', 'yes' );
	update_post_meta( $product_id, '_manage_stock', 'no' );
	update_post_meta( $product_id, '_price', '400' );
	update_post_meta( $product_id, '_regular_price', '400' );
	update_post_meta( $product_id, '_catalog_visibility', 'visible' );

	if ( function_exists( 'snf_attach_theme_service_image' ) ) {
		snf_attach_theme_service_image( $product_id, 'lifestyle-service-image-1.jpg' );
	}
}
add_action( 'after_switch_theme', 'snf_ensure_line_sitting_courier_quote_product' );
add_action( 'admin_init', 'snf_ensure_line_sitting_courier_quote_product', 33 );
add_action( 'init', 'snf_ensure_line_sitting_courier_quote_product', 60 );
