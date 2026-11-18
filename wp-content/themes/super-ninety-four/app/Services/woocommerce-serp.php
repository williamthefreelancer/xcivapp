<?php
/**
 * WooCommerce SERP: product categories, meta, query filters, row mapper.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enable WooCommerce theme support and strip default shop chrome.
 */
function snf_woocommerce_setup() {
	add_theme_support( 'woocommerce' );

	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

	// Use the hybrid sidebar instead of default Woo My Account nav.
	remove_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation' );
}
add_action( 'after_setup_theme', 'snf_woocommerce_setup' );

/**
 * Keep My Account on the Figma-ready account shell template.
 */
function snf_woocommerce_ensure_account_template() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return;
	}

	$page_id = (int) wc_get_page_id( 'myaccount' );
	if ( $page_id <= 0 ) {
		return;
	}

	$desired = 'woocommerce-account';
	if ( get_page_template_slug( $page_id ) !== $desired ) {
		update_post_meta( $page_id, '_wp_page_template', $desired );
	}
}
add_action( 'init', 'snf_woocommerce_ensure_account_template', 25 );

/**
 * Keep the shop publicly reachable so the SERP archive can render.
 */
function snf_woocommerce_ensure_store_live() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	if ( 'yes' === get_option( 'woocommerce_coming_soon' ) ) {
		update_option( 'woocommerce_coming_soon', 'no' );
	}
	if ( 'yes' === get_option( 'woocommerce_store_pages_only' ) ) {
		update_option( 'woocommerce_store_pages_only', 'no' );
	}
}
add_action( 'init', 'snf_woocommerce_ensure_store_live', 5 );

/**
 * Listing product category definitions.
 *
 * @return array<string, string> slug => label
 */
function snf_get_serp_product_categories() {
	return array(
		'events'   => __( 'Events', 'super-ninety-four' ),
		'services' => __( 'Services', 'super-ninety-four' ),
	);
}

/**
 * Ensure Events and Services product categories exist.
 */
function snf_ensure_serp_product_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	foreach ( snf_get_serp_product_categories() as $slug => $label ) {
		if ( term_exists( $slug, 'product_cat' ) ) {
			continue;
		}
		wp_insert_term(
			$label,
			'product_cat',
			array(
				'slug' => $slug,
			)
		);
	}
}
add_action( 'init', 'snf_ensure_serp_product_categories', 30 );

/**
 * Read SERP query params from the request.
 *
 * @return array{q:string,product_cat:string}
 */
function snf_get_serp_request_args() {
	return array(
		'q'           => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
		'product_cat' => isset( $_GET['product_cat'] ) ? sanitize_title( wp_unslash( $_GET['product_cat'] ) ) : '',
	);
}

/**
 * Filter the main Woo product query for SERP params.
 *
 * @param WP_Query $q Woo product WP_Query.
 */
function snf_woocommerce_product_query( $q ) {
	if ( is_admin() || ! $q instanceof WP_Query ) {
		return;
	}

	$args = snf_get_serp_request_args();

	if ( $args['q'] ) {
		$q->set( 's', $args['q'] );
	}

	$q->set( 'orderby', 'title' );
	$q->set( 'order', 'ASC' );
	$q->set( 'posts_per_page', 20 );

	$allowed = array_keys( snf_get_serp_product_categories() );
	$terms   = $allowed;
	if ( $args['product_cat'] && in_array( $args['product_cat'], $allowed, true ) ) {
		$terms = array( $args['product_cat'] );
	}

	$tax_query = $q->get( 'tax_query' );
	if ( ! is_array( $tax_query ) ) {
		$tax_query = array();
	}
	$tax_query[] = array(
		'taxonomy' => 'product_cat',
		'field'    => 'slug',
		'terms'    => $terms,
		'operator' => 'IN',
	);
	$q->set( 'tax_query', $tax_query );
}
add_action( 'woocommerce_product_query', 'snf_woocommerce_product_query' );

/**
 * Theme-relative icon path for a listing slug (SERP favicon).
 *
 * Brand marks first; MIT Phosphor UI icons for services / clubs.
 * Never returns a product photo thumbnail.
 *
 * @param string $slug Product or event slug.
 * @return string Relative path under assets/icons/, or empty.
 */
function snf_get_listing_favicon_relative_path( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( 'the-eight-hundred-plus-journeys' === $slug ) {
		$slug = 'the-eight-hundred-plus-journeys-event';
	}
	if ( 'the-badlands-bra-party-bus-gathering' === $slug ) {
		$slug = 'the-badlands-bra-private-gathering';
	}

	$map = array(
		/* Brand marks when they read at favicon size; Phosphor MIT otherwise. */
		'the-badlands-bra-private-gathering'        => 'female-body-icon.svg',
		'the-eight-hundred-plus-journeys-event'     => 'ui/car.svg',
		'the-township-adventures-event'             => 'ui/buildings.svg',
		'the-lifestyle-concierge-club-workshop'     => 'ui/chalkboard-teacher.svg',
		'litter-pickup-service'                     => 'ui/broom.svg',
		'professional-line-sitting-courier-service' => 'ui/person-simple-walk.svg',
		'janitorial-cleaning-service'               => 'ui/sparkle.svg',
		'the-clubs-at-back-creek'                   => 'ui/buildings.svg',
		'the-badlands-bra-club'                     => 'female-body-icon.svg',
		'the-clubs-at-wyoma-square'                 => 'ui/map-pin.svg',
		'the-city-clubs-at-super-ninety-four'       => 'ui/buildings.svg',
	);

	/**
	 * Filter listing favicon relative paths.
	 *
	 * @param array<string, string> $map Slug => path under assets/icons/.
	 */
	$map = apply_filters( 'snf_listing_favicon_map', $map );

	if ( empty( $map[ $slug ] ) ) {
		return '';
	}

	$rel  = ltrim( (string) $map[ $slug ], '/' );
	$file = get_template_directory() . '/assets/icons/' . $rel;
	return is_readable( $file ) ? $rel : '';
}

/**
 * Absolute URL for a listing SERP favicon.
 *
 * @param string $slug Product or event slug.
 * @return string
 */
function snf_get_listing_favicon_url( $slug ) {
	$rel = snf_get_listing_favicon_relative_path( $slug );
	if ( ! $rel ) {
		// Site mark — never a letter placeholder when a brand asset exists.
		$fallback = get_template_directory() . '/assets/icons/logo-white.svg';
		if ( is_readable( $fallback ) ) {
			return get_template_directory_uri() . '/assets/icons/logo-white.svg';
		}
		return '';
	}

	return get_template_directory_uri() . '/assets/icons/' . $rel;
}

/**
 * Map a product to a SERP result row.
 *
 * @param WC_Product|int|null $product Product object or ID.
 * @return array<string, string>
 */
function snf_get_product_serp_row( $product = null ) {
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( $product ? $product : get_the_ID() );
	}

	if ( ! $product ) {
		return array(
			'title'    => '',
			'url'      => '',
			'snippet'  => '',
			'meta'     => '',
			'crumb'    => '',
			'action'   => __( 'View listing', 'super-ninety-four' ),
			'favicon'  => '',
			'fallback' => '',
		);
	}

	$product_id = $product->get_id();
	$title      = $product->get_name();
	$snippet    = $product->get_short_description();
	if ( ! $snippet ) {
		$snippet = wp_trim_words( wp_strip_all_tags( $product->get_description() ), 28 );
	} else {
		$snippet = wp_strip_all_tags( $snippet );
	}

	$location = (string) get_post_meta( $product_id, '_snf_location', true );
	$timing   = (string) get_post_meta( $product_id, '_snf_when', true );
	$action   = (string) get_post_meta( $product_id, '_snf_action_label', true );
	if ( '' === $action ) {
		$action = __( 'View listing', 'super-ninety-four' );
	}

	$favicon = snf_get_listing_favicon_url( $product->get_slug() );

	$result_url = get_permalink( $product_id );
	if ( function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( $product_id ) ) {
		$result_url = snf_get_workshop_landing_url();
	}

	return array(
		'title'    => $title,
		'url'      => $result_url,
		'snippet'  => $snippet,
		'meta'     => trim( implode( ' · ', array_filter( array( $location, $timing ) ) ) ),
		'crumb'    => 'superninetyfour.com › shop › ' . $product->get_slug(),
		'action'   => $action,
		'favicon'  => $favicon,
		'fallback' => '',
	);
}

/**
 * Add Location / When / CTA fields on the product General tab.
 */
function snf_woocommerce_product_serp_fields() {
	echo '<div class="options_group">';

	woocommerce_wp_text_input(
		array(
			'id'          => '_snf_location',
			'label'       => __( 'Location (Where)', 'super-ninety-four' ),
			'desc_tip'    => true,
			'description' => __( 'Shown in SERP meta and used for Where search.', 'super-ninety-four' ),
		)
	);

	woocommerce_wp_text_input(
		array(
			'id'          => '_snf_when',
			'label'       => __( 'When', 'super-ninety-four' ),
			'desc_tip'    => true,
			'description' => __( 'Shown in SERP meta and used for When search.', 'super-ninety-four' ),
		)
	);

	woocommerce_wp_text_input(
		array(
			'id'          => '_snf_action_label',
			'label'       => __( 'SERP action label', 'super-ninety-four' ),
			'placeholder' => __( 'View listing', 'super-ninety-four' ),
			'desc_tip'    => true,
			'description' => __( 'CTA text under each search result.', 'super-ninety-four' ),
		)
	);

	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'snf_woocommerce_product_serp_fields' );

/**
 * Persist SERP product meta.
 *
 * @param int $product_id Product ID.
 */
function snf_woocommerce_save_product_serp_fields( $product_id ) {
	$fields = array( '_snf_location', '_snf_when', '_snf_action_label' );

	foreach ( $fields as $field ) {
		if ( ! isset( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			continue;
		}
		$value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $product_id, $field, $value );
	}
}
add_action( 'woocommerce_process_product_meta', 'snf_woocommerce_save_product_serp_fields' );

/**
 * Default SERP meta for known event products.
 *
 * @return array<string, array{location:string,when:string,action:string}>
 */
function snf_get_default_event_product_serp_meta() {
	return array(
		'the-eight-hundred-plus-journeys-event' => array(
			'location' => __( 'Charlotte, NC to Salem, MA', 'super-ninety-four' ),
			'when'     => __( 'Scenic city-to-city immersion', 'super-ninety-four' ),
			'action'   => __( 'View event', 'super-ninety-four' ),
		),
		'the-badlands-bra-private-gathering'    => array(
			'location' => __( 'Private circuit gathering', 'super-ninety-four' ),
			'when'     => __( 'Members select event', 'super-ninety-four' ),
			'action'   => __( 'View gathering', 'super-ninety-four' ),
		),
		'the-township-adventures-event'         => array(
			'location' => __( 'Township cultural circuit', 'super-ninety-four' ),
			'when'     => __( 'Cultural township immersion', 'super-ninety-four' ),
			'action'   => __( 'View event', 'super-ninety-four' ),
		),
		'the-lifestyle-concierge-club-workshop' => array(
			'location' => __( 'Open admission workshop', 'super-ninety-four' ),
			'when'     => __( 'Guest workshop', 'super-ninety-four' ),
			'action'   => __( 'Reserve seat', 'super-ninety-four' ),
		),
	);
}

/**
 * Default product → brand assignments for reciprocal programs.
 *
 * @return array<string, string> product slug => brand slug
 */
function snf_get_default_product_brand_map() {
	return array(
		'the-eight-hundred-plus-journeys-event' => 'the-eight-hundred-plus-journeys',
		'the-badlands-bra-private-gathering'    => 'the-badlands-bra-club',
		'the-township-adventures-event'         => 'the-cultural-guides-of-township-adventures',
	);
}

/**
 * Fallback image path under theme assets for a brand slug.
 *
 * @param string $slug Brand slug.
 * @return string Absolute URL or empty.
 */
function snf_get_product_brand_fallback_image( $slug ) {
	$slug = sanitize_title( (string) $slug );
	$map  = array(
		'the-clubs-at-wyoma-square'                   => 'reciprocal/the-clubs-at-wyoma-square.jpg',
		'the-city-clubs-at-super-ninety-four'          => 'reciprocal/the-city-clubs-at-super-ninety-four.jpg',
		'the-clubs-at-back-creek'                     => 'reciprocal/the-clubs-at-back-creek.jpg',
		'the-badlands-bra-club'                       => 'reciprocal/the-badlands-bra-club.jpg',
		'the-eight-hundred-plus-journeys'             => 'events/eight-hundred-journeys-event.jpg',
		'the-cultural-guides-of-township-adventures'  => 'events/township-adventures-event.jpg',
	);

	if ( empty( $map[ $slug ] ) ) {
		return '';
	}

	$relative = $map[ $slug ];
	$path     = get_template_directory() . '/assets/images/' . $relative;
	if ( ! file_exists( $path ) ) {
		return '';
	}

	return get_template_directory_uri() . '/assets/images/' . $relative;
}

/**
 * Resolve a brand landing / archive URL.
 *
 * @param WP_Term $term Brand term.
 * @return string
 */
function snf_get_product_brand_url( $term ) {
	if ( ! $term instanceof WP_Term ) {
		return snf_get_page_url( 'find-other-reciprocal-programs' );
	}

	if ( function_exists( 'snf_get_reciprocal_club_url' ) ) {
		$club_url = snf_get_reciprocal_club_url( $term->slug );
		$hub_url  = snf_get_page_url( 'find-other-reciprocal-programs' );
		if ( $club_url && untrailingslashit( $club_url ) !== untrailingslashit( $hub_url ) ) {
			return $club_url;
		}
	}

	// Prefer a published reciprocal landing page when one exists.
	$page = get_page_by_path( $term->slug );
	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		return get_permalink( $page );
	}

	// Otherwise use the WooCommerce brand archive.
	$link = get_term_link( $term );
	if ( ! is_wp_error( $link ) ) {
		return $link;
	}

	return snf_get_page_url( 'find-other-reciprocal-programs' );
}

/**
 * Dashboard / SERP cards from WooCommerce product brands (reciprocal programs).
 *
 * @param int $limit Max cards.
 * @return array<int, array<string, string>>
 */
function snf_get_product_brand_cards( $limit = 12 ) {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_brand',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'number'     => (int) $limit,
		)
	);

	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}

	$cards = array();
	foreach ( $terms as $term ) {
		$image = '';
		$thumb = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		if ( $thumb ) {
			$url = wp_get_attachment_image_url( $thumb, 'medium_large' );
			if ( $url ) {
				$image = $url;
			}
		}
		if ( ! $image ) {
			$image = snf_get_product_brand_fallback_image( $term->slug );
		}

		$cards[] = array(
			'title' => $term->name,
			'meta'  => __( 'Reciprocal club program', 'super-ninety-four' ),
			'url'   => snf_get_product_brand_url( $term ),
			'image' => $image ? $image : '',
		);
	}

	/**
	 * Filter reciprocal brand cards built from product_brand terms.
	 *
	 * @param array $cards Brand cards.
	 */
	return apply_filters( 'snf_product_brand_cards', $cards );
}

/**
 * Assign default brands to known event products when missing.
 */
function snf_ensure_event_product_brands() {
	if ( ! class_exists( 'WooCommerce' ) || ! taxonomy_exists( 'product_brand' ) ) {
		return;
	}

	$map = snf_get_default_product_brand_map();
	if ( ! $map ) {
		return;
	}

	$query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'post_name__in'  => array_keys( $map ),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( $query->posts as $product_id ) {
		$product_id = (int) $product_id;
		$post       = get_post( $product_id );
		if ( ! $post || empty( $map[ $post->post_name ] ) ) {
			continue;
		}

		$brand_slug = $map[ $post->post_name ];
		if ( ! term_exists( $brand_slug, 'product_brand' ) ) {
			continue;
		}
		if ( has_term( $brand_slug, 'product_brand', $product_id ) ) {
			continue;
		}

		wp_set_object_terms( $product_id, $brand_slug, 'product_brand', true );
	}
}
add_action( 'init', 'snf_ensure_event_product_brands', 45 );

/**
 * Fill empty Location / When / CTA fields on published event products.
 */
function snf_ensure_event_product_serp_meta() {
	if ( ! class_exists( 'WooCommerce' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	$defaults = snf_get_default_event_product_serp_meta();
	if ( ! $defaults ) {
		return;
	}

	$query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'post_name__in'  => array_keys( $defaults ),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( $query->posts as $product_id ) {
		$product_id = (int) $product_id;
		$post       = get_post( $product_id );
		if ( ! $post || ! isset( $defaults[ $post->post_name ] ) ) {
			continue;
		}

		$meta = $defaults[ $post->post_name ];
		if ( '' === (string) get_post_meta( $product_id, '_snf_location', true ) ) {
			update_post_meta( $product_id, '_snf_location', $meta['location'] );
		}
		if ( '' === (string) get_post_meta( $product_id, '_snf_when', true ) ) {
			update_post_meta( $product_id, '_snf_when', $meta['when'] );
		}
		if ( '' === (string) get_post_meta( $product_id, '_snf_action_label', true ) ) {
			update_post_meta( $product_id, '_snf_action_label', $meta['action'] );
		}

		// Keep products in the Events category the theme shop/dashboard query on.
		if ( ! has_term( 'events', 'product_cat', $product_id ) ) {
			wp_set_object_terms( $product_id, 'events', 'product_cat', true );
		}
	}
}
add_action( 'init', 'snf_ensure_event_product_serp_meta', 40 );

/**
 * Default theme images for known service products (under assets/images/services/).
 *
 * @return array<string, string> product slug => filename
 */
function snf_get_default_service_product_images() {
	return array(
		'janitorial-cleaning-service'               => 'commercial-janitor-cleaning.jpg',
		'litter-pickup-service'                     => 'litter-pickup-service-image-2.jpg',
		'professional-line-sitting-courier-service' => 'lifestyle-service-image-1.jpg',
	);
}

/**
 * Attach a theme image file as a product featured image.
 *
 * @param int    $product_id Product ID.
 * @param string $filename   File under assets/images/services/.
 * @return int Attachment ID or 0.
 */
function snf_attach_theme_service_image( $product_id, $filename ) {
	$product_id = (int) $product_id;
	$filename   = basename( (string) $filename );
	if ( $product_id <= 0 || '' === $filename ) {
		return 0;
	}

	$source = get_template_directory() . '/assets/images/services/' . $filename;
	if ( ! file_exists( $source ) || ! is_readable( $source ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// Prefer wp_upload_bits: media_handle_sideload + wp_tempnam often yields 0-byte
	// temps on Windows (is_uploaded_file / empty-file upload_error).
	$bits = file_get_contents( $source );
	if ( false === $bits || '' === $bits ) {
		return 0;
	}

	$upload = wp_upload_bits( $filename, null, $bits );
	if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
		return 0;
	}

	$filetype      = wp_check_filetype( $filename, null );
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'image/jpeg',
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$upload['file'],
		$product_id,
		true
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return 0;
	}

	$attachment_id = (int) $attachment_id;
	$meta          = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
	if ( $meta ) {
		wp_update_attachment_metadata( $attachment_id, $meta );
	}

	set_post_thumbnail( $product_id, $attachment_id );
	return $attachment_id;
}

/**
 * Ensure known service products have a featured image from theme assets.
 */
function snf_ensure_service_product_images() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$map = snf_get_default_service_product_images();
	if ( ! $map ) {
		return;
	}

	$query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'post_name__in'  => array_keys( $map ),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( $query->posts as $product_id ) {
		$product_id = (int) $product_id;
		if ( has_post_thumbnail( $product_id ) ) {
			continue;
		}

		$post = get_post( $product_id );
		if ( ! $post || empty( $map[ $post->post_name ] ) ) {
			continue;
		}

		snf_attach_theme_service_image( $product_id, $map[ $post->post_name ] );
	}
}
add_action( 'init', 'snf_ensure_service_product_images', 46 );
