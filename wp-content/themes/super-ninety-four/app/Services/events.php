<?php
/**
 * Event single display helpers (Figma 174:2 / 2167:214 / 2167:243).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slugs listed under Select Events (members).
 *
 * @return string[]
 */
function snf_get_member_select_event_slugs() {
	return array(
		'the-badlands-bra-private-gathering',
		'the-eight-hundred-plus-journeys-event',
		'the-township-adventures-event',
		'the-city-club-private-party',
		// Legacy CPT slugs kept for older deep links.
		'the-badlands-bra-party-bus-gathering',
		'the-eight-hundred-plus-journeys',
	);
}

/**
 * Upcoming gatherings shown on the home one-pager (Woo event products).
 *
 * @return array<string, array{title:string,price:string,location:string,when:string,excerpt:string}>
 */
function snf_get_upcoming_gathering_event_catalog() {
	return array(
		'the-eight-hundred-plus-journeys-event' => array(
			'title'    => __( 'The Eight Hundred Plus Interstate Event', 'super-ninety-four' ),
			'price'    => '497',
			'location' => __( 'Charlotte, NC to Salem, MA', 'super-ninety-four' ),
			'when'     => __( 'Scenic city-to-city immersion', 'super-ninety-four' ),
			'excerpt'  => __( 'Scenic city-to-city immersion along the SUPER NINETY FOUR journeys circuit.', 'super-ninety-four' ),
		),
		'the-township-adventures-event'         => array(
			'title'    => __( 'The Township Adventures Event', 'super-ninety-four' ),
			'price'    => '97',
			'location' => __( 'Township cultural circuit', 'super-ninety-four' ),
			'when'     => __( 'Cultural township immersion', 'super-ninety-four' ),
			'excerpt'  => __( 'Cultural township immersion on the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
		),
		'the-city-club-private-party'           => array(
			'title'    => __( 'The City Club Private Party', 'super-ninety-four' ),
			'price'    => '397',
			'location' => __( 'City Clubs private circuit', 'super-ninety-four' ),
			'when'     => __( 'Private City Clubs gathering', 'super-ninety-four' ),
			'excerpt'  => __( 'Private City Clubs gathering — coach, credentials, and a closed room.', 'super-ninety-four' ),
		),
	);
}

/**
 * Ensure one upcoming-gathering Woo event product exists.
 *
 * @param string $slug Product slug.
 * @return int Product ID or 0.
 */
function snf_ensure_gathering_event_product( $slug ) {
	$slug    = sanitize_title( (string) $slug );
	$catalog = snf_get_upcoming_gathering_event_catalog();
	if ( '' === $slug || ! isset( $catalog[ $slug ] ) ) {
		return 0;
	}

	$entry = $catalog[ $slug ];
	$post  = get_page_by_path( $slug, OBJECT, 'product' );

	if ( $post instanceof WP_Post ) {
		$product_id = (int) $post->ID;
		$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( $product ) {
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_regular_price( $entry['price'] );
			$product->set_price( $entry['price'] );
			$product->set_virtual( true );
			$product->set_sold_individually( true );
			if ( '' === trim( (string) $product->get_short_description() ) ) {
				$product->set_short_description( $entry['excerpt'] );
			}
			$product->save();
		} elseif ( 'publish' !== $post->post_status ) {
			wp_update_post(
				array(
					'ID'          => $product_id,
					'post_status' => 'publish',
				)
			);
		}
	} else {
		$product = new WC_Product_Simple();
		$product->set_name( $entry['title'] );
		$product->set_slug( $slug );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_regular_price( $entry['price'] );
		$product->set_price( $entry['price'] );
		$product->set_virtual( true );
		$product->set_sold_individually( true );
		$product->set_short_description( $entry['excerpt'] );
		$product->set_description(
			sprintf(
				/* translators: %s: event title */
				__( '%s is a SUPER NINETY FOUR immerse gathering. Open the event landing to check availability and reserve.', 'super-ninety-four' ),
				$entry['title']
			)
		);
		$product_id = (int) $product->save();
		if ( ! $product_id ) {
			return 0;
		}
	}

	if ( taxonomy_exists( 'product_cat' ) ) {
		if ( function_exists( 'snf_ensure_serp_product_categories' ) ) {
			snf_ensure_serp_product_categories();
		}
		if ( term_exists( 'events', 'product_cat' ) ) {
			wp_set_object_terms( $product_id, 'events', 'product_cat', true );
		}
	}

	if ( '' === (string) get_post_meta( $product_id, '_snf_location', true ) ) {
		update_post_meta( $product_id, '_snf_location', $entry['location'] );
	}
	if ( '' === (string) get_post_meta( $product_id, '_snf_when', true ) ) {
		update_post_meta( $product_id, '_snf_when', $entry['when'] );
	}
	if ( '' === (string) get_post_meta( $product_id, 'snf_event_category', true ) ) {
		update_post_meta( $product_id, 'snf_event_category', __( 'Gathering', 'super-ninety-four' ) );
	}
	if ( '' === (string) get_post_meta( $product_id, 'snf_event_price', true ) ) {
		update_post_meta( $product_id, 'snf_event_price', '$' . number_format( (float) $entry['price'], 2 ) );
	}

	if ( taxonomy_exists( 'snf_event_experience' ) && ! has_term( 'immerse', 'snf_event_experience', $product_id ) ) {
		wp_set_object_terms( $product_id, 'immerse', 'snf_event_experience', true );
	}

	if ( function_exists( 'snf_ticket_commerce_maybe_seed_post' ) ) {
		snf_ticket_commerce_maybe_seed_post( $product_id );
	}

	return $product_id;
}

/**
 * Ensure upcoming gathering Woo event products exist.
 *
 * @param bool $force Re-run even when the version option matches.
 */
function snf_ensure_gathering_event_products( $force = false ) {
	if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Product_Simple' ) ) {
		return;
	}

	$version = '1.0.0-gathering-events';
	if ( ! $force && get_option( 'snf_gathering_event_products_version' ) === $version ) {
		// Still create any missing products without rewriting existing ones.
		foreach ( array_keys( snf_get_upcoming_gathering_event_catalog() ) as $slug ) {
			if ( ! get_page_by_path( $slug, OBJECT, 'product' ) ) {
				snf_ensure_gathering_event_product( $slug );
			}
		}
		return;
	}

	foreach ( array_keys( snf_get_upcoming_gathering_event_catalog() ) as $slug ) {
		snf_ensure_gathering_event_product( $slug );
	}

	update_option( 'snf_gathering_event_products_version', $version, false );
}
add_action( 'after_switch_theme', 'snf_ensure_gathering_event_products' );
add_action( 'admin_init', 'snf_ensure_gathering_event_products', 33 );
add_action( 'init', 'snf_ensure_gathering_event_products', 33 );

/**
 * Whether an event/product belongs to Select Events (members).
 *
 * @param int|WP_Post|null $post Event or product post.
 * @return bool
 */
function snf_is_member_select_event( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! in_array( $post->post_type, array( 'event', 'product' ), true ) ) {
		return false;
	}

	$slug = function_exists( 'snf_resolve_listing_product_slug' )
		? snf_resolve_listing_product_slug( $post->post_name )
		: $post->post_name;

	return in_array( $slug, snf_get_member_select_event_slugs(), true )
		|| in_array( $post->post_name, snf_get_member_select_event_slugs(), true );
}

/**
 * Public Select Events catalog (guests only — not workshops/services).
 *
 * @return array<string, array{title:string,location:string,when:string,starts_at:string}>
 */
function snf_get_public_select_event_catalog() {
	return array(
		'harris-houston-to-wyoma-square' => array(
			'title'     => __( 'Harris Houston to Wyoma Square', 'super-ninety-four' ),
			'location'  => __( 'Harris Houston → Wyoma Square', 'super-ninety-four' ),
			'when'      => __( 'Seasonal public immerse route', 'super-ninety-four' ),
			'starts_at' => '2028-11-30 18:00:00',
		),
		'harrisburg-to-lynn'             => array(
			'title'     => __( 'Harrisburg to Lynn', 'super-ninety-four' ),
			'location'  => __( 'Harrisburg → Lynn', 'super-ninety-four' ),
			'when'      => __( 'Seasonal public immerse route', 'super-ninety-four' ),
			'starts_at' => '2028-11-30 18:00:00',
		),
		'lynnfield-to-lynn'              => array(
			'title'     => __( 'Lynnfield to Lynn', 'super-ninety-four' ),
			'location'  => __( 'Lynnfield → Lynn', 'super-ninety-four' ),
			'when'      => __( 'Seasonal public immerse route', 'super-ninety-four' ),
			'starts_at' => '2028-11-30 18:00:00',
		),
		'lynnfield-to-salem'             => array(
			'title'     => __( 'Lynnfield to Salem', 'super-ninety-four' ),
			'location'  => __( 'Lynnfield → Salem', 'super-ninety-four' ),
			'when'      => __( 'Seasonal public immerse route', 'super-ninety-four' ),
			'starts_at' => '2028-11-30 18:00:00',
		),
		'the-northshore-experience'      => array(
			'title'     => __( 'The Northshore Experience', 'super-ninety-four' ),
			'location'  => __( 'North Shore, MA', 'super-ninety-four' ),
			'when'      => __( 'Public immerse experience', 'super-ninety-four' ),
			'starts_at' => '2028-11-30 18:00:00',
		),
		'the-dirty-pub-hour'             => array(
			'title'     => __( 'The Dirty Pub Hour', 'super-ninety-four' ),
			'location'  => __( 'North Shore pubs', 'super-ninety-four' ),
			'when'      => __( 'Public evening experience', 'super-ninety-four' ),
			'starts_at' => '2028-11-30 18:00:00',
		),
	);
}

/**
 * Slugs listed under Select Events (public / non-members).
 *
 * @return string[]
 */
function snf_get_public_select_event_slugs() {
	return array_keys( snf_get_public_select_event_catalog() );
}

/**
 * Whether an event/product belongs to the public Select Events catalog.
 *
 * @param int|WP_Post|null $post Event or product post.
 * @return bool
 */
function snf_is_public_select_event( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! in_array( $post->post_type, array( 'event', 'product' ), true ) ) {
		return false;
	}

	$slug = function_exists( 'snf_resolve_listing_product_slug' )
		? snf_resolve_listing_product_slug( $post->post_name )
		: $post->post_name;

	return in_array( $slug, snf_get_public_select_event_slugs(), true )
		|| in_array( $post->post_name, snf_get_public_select_event_slugs(), true );
}

/**
 * Lowest paid ticket tier for a public Select Events product.
 *
 * @param string $slug Product slug.
 * @return float
 */
function snf_get_public_event_starting_price( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( function_exists( 'snf_ticket_commerce_default_for_slug' ) ) {
		$preset = snf_ticket_commerce_default_for_slug( $slug );
		if ( is_array( $preset ) && ! empty( $preset['tiers'] ) && is_array( $preset['tiers'] ) ) {
			$prices = array();
			foreach ( $preset['tiers'] as $tier ) {
				$amount = isset( $tier['price'] ) ? (float) $tier['price'] : 0.0;
				if ( $amount > 0 ) {
					$prices[] = $amount;
				}
			}
			if ( $prices ) {
				return min( $prices );
			}
		}
	}

	return 97.0;
}

/**
 * Keep the public event product base price aligned with the lowest paid ticket tier.
 *
 * @param int    $product_id Product ID.
 * @param string $slug       Product slug.
 */
function snf_sync_public_event_product_price( $product_id, $slug ) {
	$product_id = absint( $product_id );
	$amount     = snf_get_public_event_starting_price( $slug );
	if ( ! $product_id || $amount <= 0 ) {
		return;
	}

	$formatted = function_exists( 'wc_format_decimal' ) ? wc_format_decimal( $amount ) : (string) $amount;
	$current   = get_post_meta( $product_id, '_regular_price', true );
	// Theme-managed public catalog: rewrite empty, $0, or stale base prices.
	if ( '' !== (string) $current && abs( (float) $current - (float) $amount ) < 0.001 ) {
		$sale = get_post_meta( $product_id, '_price', true );
		if ( '' !== (string) $sale && abs( (float) $sale - (float) $amount ) < 0.001 ) {
			return;
		}
	}

	if ( function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$product->set_regular_price( $formatted );
			$product->set_price( $formatted );
			$product->save();
			return;
		}
	}

	update_post_meta( $product_id, '_regular_price', $formatted );
	update_post_meta( $product_id, '_price', $formatted );
}

/**
 * Ensure one public Select Events Woo product exists.
 *
 * @param string $slug Product slug.
 * @return int Product ID or 0.
 */
function snf_ensure_public_event_product( $slug ) {
	$slug     = sanitize_title( (string) $slug );
	$catalog  = snf_get_public_select_event_catalog();
	if ( '' === $slug || ! isset( $catalog[ $slug ] ) ) {
		return 0;
	}

	$entry = $catalog[ $slug ];
	$post  = get_page_by_path( $slug, OBJECT, 'product' );

	if ( $post instanceof WP_Post ) {
		$product_id = (int) $post->ID;
		if ( 'publish' !== $post->post_status ) {
			wp_update_post(
				array(
					'ID'          => $product_id,
					'post_status' => 'publish',
				)
			);
		}
	} else {
		$product_id = wp_insert_post(
			array(
				'post_title'   => $entry['title'],
				'post_name'    => $slug,
				'post_content' => sprintf(
					/* translators: %s: experience title */
					__( '%s is a public SUPER NINETY FOUR immerse experience. Select your option and check availability to reserve.', 'super-ninety-four' ),
					$entry['title']
				),
				'post_excerpt' => $entry['when'],
				'post_status'  => 'publish',
				'post_type'    => 'product',
			),
			true
		);

		if ( is_wp_error( $product_id ) || ! $product_id ) {
			return 0;
		}

		$product_id = (int) $product_id;
		wp_set_object_terms( $product_id, 'simple', 'product_type' );
		update_post_meta( $product_id, '_virtual', 'yes' );
		update_post_meta( $product_id, '_sold_individually', 'yes' );
		update_post_meta( $product_id, '_manage_stock', 'no' );
		update_post_meta( $product_id, '_catalog_visibility', 'visible' );
	}

	snf_sync_public_event_product_price( $product_id, $slug );

	if ( taxonomy_exists( 'product_cat' ) ) {
		if ( function_exists( 'snf_ensure_serp_product_categories' ) ) {
			snf_ensure_serp_product_categories();
		}
		if ( term_exists( 'events', 'product_cat' ) ) {
			wp_set_object_terms( $product_id, 'events', 'product_cat', true );
		}
	}

	if ( '' === (string) get_post_meta( $product_id, '_snf_location', true ) ) {
		update_post_meta( $product_id, '_snf_location', $entry['location'] );
	}
	if ( '' === (string) get_post_meta( $product_id, '_snf_when', true ) ) {
		update_post_meta( $product_id, '_snf_when', $entry['when'] );
	}
	if ( '' === (string) get_post_meta( $product_id, '_snf_starts_at', true ) ) {
		update_post_meta( $product_id, '_snf_starts_at', $entry['starts_at'] );
	}
	if ( '' === (string) get_post_meta( $product_id, 'snf_event_category', true ) ) {
		update_post_meta( $product_id, 'snf_event_category', __( 'Immerse', 'super-ninety-four' ) );
	}

	if ( taxonomy_exists( 'snf_event_experience' ) && ! has_term( 'immerse', 'snf_event_experience', $product_id ) ) {
		wp_set_object_terms( $product_id, 'immerse', 'snf_event_experience', true );
	}

	if ( function_exists( 'snf_ticket_commerce_maybe_seed_post' ) ) {
		snf_ticket_commerce_maybe_seed_post( $product_id );
	}

	return $product_id;
}

/**
 * Ensure all public Select Events Woo products exist.
 */
function snf_ensure_public_event_products() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	foreach ( snf_get_public_select_event_slugs() as $slug ) {
		snf_ensure_public_event_product( $slug );
	}
}
add_action( 'after_switch_theme', 'snf_ensure_public_event_products' );
add_action( 'admin_init', 'snf_ensure_public_event_products', 34 );
// Before ticket-commerce seed (priority 35) so Woo products exist on first request.
add_action( 'init', 'snf_ensure_public_event_products', 34 );

/**
 * Admissions form audience for an event page.
 *
 * @param int|WP_Post|null $post Event post.
 * @return string 'members', 'public', or 'non-members'
 */
function snf_get_event_admission_audience( $post = null ) {
	if ( snf_is_member_select_event( $post ) ) {
		return 'members';
	}
	if ( snf_is_public_select_event( $post ) ) {
		return 'public';
	}
	return 'non-members';
}

/**
 * Header brand logo filename under assets/icons/ for a select-event slug.
 *
 * @param string $slug Event/product slug.
 * @return array{file:string,width:int,height:int,variant:string}
 */
function snf_get_event_brand_logo( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( 'the-eight-hundred-plus-journeys' === $slug ) {
		$slug = 'the-eight-hundred-plus-journeys-event';
	}
	if ( 'the-badlands-bra-party-bus-gathering' === $slug ) {
		$slug = 'the-badlands-bra-private-gathering';
	}

	$map = array(
		'the-township-adventures-event'         => array(
			'file'    => 'events/the-cultural-guide-of-township-adventures-white.svg',
			'width'   => 220,
			'height'  => 89,
			'variant' => 'wordmark',
		),
		'the-eight-hundred-plus-journeys-event' => array(
			'file'    => 'events/the-800-plus-journeys-logo-white.svg',
			'width'   => 220,
			'height'  => 50,
			'variant' => 'wordmark',
		),
		'the-badlands-bra-private-gathering'    => array(
			'file'    => 'female-body-icon-white.svg',
			'width'   => 28,
			'height'  => 42,
			'variant' => 'icon',
		),
	);

	if ( isset( $map[ $slug ] ) ) {
		return $map[ $slug ];
	}

	return array(
		'file'    => 'female-body-icon-white.svg',
		'width'   => 28,
		'height'  => 42,
		'variant' => 'icon',
	);
}

/**
 * Default image filename for known event slugs.
 *
 * @param string $slug Event post slug.
 * @return string
 */
function snf_get_event_default_image_file( $slug ) {
	$map = array(
		'the-badlands-bra-private-gathering'     => 'badlands-bra-event.jpg',
		'the-badlands-bra-party-bus-gathering'   => 'badlands-bra-event.jpg',
		'the-eight-hundred-plus-journeys-event'  => 'eight-hundred-journeys-event.jpg',
		'the-eight-hundred-plus-journeys'        => 'eight-hundred-journeys-event.jpg',
		'the-township-adventures-event'          => 'township-adventures-event.jpg',
		'the-lifestyle-concierge-club-workshop'  => 'lifestyle-concierge-club-workshop.jpg',
		// Public Select Events — shared immerse hero until dedicated art ships.
		'harris-houston-to-wyoma-square'         => 'township-adventures-event.jpg',
		'harrisburg-to-lynn'                     => 'township-adventures-event.jpg',
		'lynnfield-to-lynn'                      => 'township-adventures-event.jpg',
		'lynnfield-to-salem'                     => 'township-adventures-event.jpg',
		'the-northshore-experience'              => 'eight-hundred-journeys-event.jpg',
		'the-dirty-pub-hour'                     => 'eight-hundred-journeys-event.jpg',
	);
	return isset( $map[ $slug ] ) ? $map[ $slug ] : '';
}

/**
 * Default price labels for known events.
 *
 * @param string $slug Event post slug.
 * @return string
 */
function snf_get_event_default_price( $slug ) {
	$map = array(
		'the-eight-hundred-plus-journeys-event' => '$497.00',
		'the-eight-hundred-plus-journeys'       => '$497.00',
		'the-badlands-bra-private-gathering'    => '$397.00',
		'the-badlands-bra-party-bus-gathering'  => '$497.00',
		'the-township-adventures-event'         => '$97.00',
		'the-city-club-private-party'           => '$397.00',
		'the-lifestyle-concierge-club-workshop' => '$97.00',
		'harris-houston-to-wyoma-square'        => '$97.00',
		'harrisburg-to-lynn'                    => '$97.00',
		'lynnfield-to-lynn'                     => '$97.00',
		'lynnfield-to-salem'                    => '$97.00',
		'the-northshore-experience'             => '$97.00',
		'the-dirty-pub-hour'                    => '$97.00',
	);
	return isset( $map[ $slug ] ) ? $map[ $slug ] : '$497.00';
}

/**
 * Figma frame node for a select-event landing slug.
 *
 * @param string $slug Event/product slug.
 * @return string
 */
function snf_get_event_figma_node( $slug ) {
	$slug = sanitize_title( (string) $slug );
	$map  = array(
		'the-badlands-bra-private-gathering'    => '174:2',
		'the-badlands-bra-party-bus-gathering'  => '174:2',
		'the-eight-hundred-plus-journeys-event' => '2167:214',
		'the-eight-hundred-plus-journeys'       => '2167:214',
		'the-township-adventures-event'         => '2167:243',
	);
	if ( in_array( $slug, snf_get_public_select_event_slugs(), true ) ) {
		return '2194:39';
	}
	return isset( $map[ $slug ] ) ? $map[ $slug ] : '174:2';
}

/**
 * Default short + long copy for known event landings (Figma sync).
 *
 * @param string $slug Event/product slug.
 * @return array{short:string,long:string}
 */
function snf_get_event_default_description( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( 'the-eight-hundred-plus-journeys' === $slug ) {
		$slug = 'the-eight-hundred-plus-journeys-event';
	}
	if ( 'the-badlands-bra-party-bus-gathering' === $slug ) {
		$slug = 'the-badlands-bra-private-gathering';
	}

	$map = array(
		'the-eight-hundred-plus-journeys-event' => array(
			'short' => __( 'Scenic city-to-city immersion along the SUPER NINETY FOUR journeys circuit.', 'super-ninety-four' ),
			'long'  => __( 'Private invite-only route from Charlotte, NC to Salem, MA. Apply to attend, then complete desk confirmation for credentials and arrival notes.', 'super-ninety-four' ),
		),
		'the-township-adventures-event'         => array(
			'short' => __( 'Cultural township immersion on the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
			'long'  => __( 'Private invite-only cultural circuit gathering. Apply to attend, then complete desk confirmation for credentials and arrival notes.', 'super-ninety-four' ),
		),
		'the-city-club-private-party'           => array(
			'short' => __( 'Private City Clubs gathering — coach, credentials, and a closed room.', 'super-ninety-four' ),
			'long'  => __( 'Invite-only City Clubs private party on the SUPER NINETY FOUR circuit. Apply to attend, then complete desk confirmation for credentials and arrival notes.', 'super-ninety-four' ),
		),
		'the-badlands-bra-private-gathering'    => array(
			'short' => __( 'Private invite-only immersion on the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
			'long'  => __( 'Apply to attend, then complete desk confirmation for credentials and arrival notes.', 'super-ninety-four' ),
		),
		'harris-houston-to-wyoma-square'        => array(
			'short' => __( 'Public immerse route from Harris Houston to Wyoma Square.', 'super-ninety-four' ),
			'long'  => __( 'Check availability, pick your ticket tier, and reserve your seat online. Confirmation and arrival notes follow after checkout.', 'super-ninety-four' ),
		),
		'harrisburg-to-lynn'                    => array(
			'short' => __( 'Public immerse route from Harrisburg to Lynn.', 'super-ninety-four' ),
			'long'  => __( 'Check availability, pick your ticket tier, and reserve your seat online. Confirmation and arrival notes follow after checkout.', 'super-ninety-four' ),
		),
		'lynnfield-to-lynn'                     => array(
			'short' => __( 'Public immerse route from Lynnfield to Lynn.', 'super-ninety-four' ),
			'long'  => __( 'Check availability, pick your ticket tier, and reserve your seat online. Confirmation and arrival notes follow after checkout.', 'super-ninety-four' ),
		),
		'lynnfield-to-salem'                    => array(
			'short' => __( 'Public immerse route from Lynnfield to Salem.', 'super-ninety-four' ),
			'long'  => __( 'Check availability, pick your ticket tier, and reserve your seat online. Confirmation and arrival notes follow after checkout.', 'super-ninety-four' ),
		),
		'the-northshore-experience'             => array(
			'short' => __( 'A public immerse experience across the North Shore.', 'super-ninety-four' ),
			'long'  => __( 'Check availability, pick your ticket tier, and reserve your seat online. Confirmation and arrival notes follow after checkout.', 'super-ninety-four' ),
		),
		'the-dirty-pub-hour'                    => array(
			'short' => __( 'A public evening pub experience on the North Shore.', 'super-ninety-four' ),
			'long'  => __( 'Check availability, pick your ticket tier, and reserve your seat online. Confirmation and arrival notes follow after checkout.', 'super-ninety-four' ),
		),
	);

	if ( isset( $map[ $slug ] ) ) {
		return $map[ $slug ];
	}

	if ( in_array( $slug, snf_get_public_select_event_slugs(), true ) ) {
		return array(
			'short' => __( 'Public immerse experience on the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
			'long'  => __( 'Check availability, pick your ticket tier, and reserve your seat online. Confirmation and arrival notes follow after checkout.', 'super-ninety-four' ),
		);
	}

	return array(
		'short' => __( 'Private invite-only immersion on the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
		'long'  => __( 'Apply to attend, then complete desk confirmation for credentials and arrival notes.', 'super-ninety-four' ),
	);
}

/**
 * Build countdown parts from a start datetime string.
 *
 * @param string $starts_at MySQL datetime or empty.
 * @return array{days:string,hours:string,mins:string,secs:string,target:string}
 */
function snf_event_countdown_parts( $starts_at ) {
	$empty = array(
		'days'   => '00',
		'hours'  => '00',
		'mins'   => '00',
		'secs'   => '00',
		'target' => '',
	);

	if ( ! $starts_at ) {
		return $empty;
	}

	$tz = wp_timezone();
	$dt = date_create( $starts_at, $tz );
	if ( ! $dt ) {
		return $empty;
	}

	$now  = new DateTimeImmutable( 'now', $tz );
	$diff = $now->diff( $dt );
	if ( $dt < $now ) {
		return array_merge( $empty, array( 'target' => $dt->format( DATE_ATOM ) ) );
	}

	$days  = (int) $diff->days;
	$hours = (int) $diff->h;
	$mins  = (int) $diff->i;
	$secs  = (int) $diff->s;

	return array(
		'days'   => str_pad( (string) min( 99, $days ), 2, '0', STR_PAD_LEFT ),
		'hours'  => str_pad( (string) $hours, 2, '0', STR_PAD_LEFT ),
		'mins'   => str_pad( (string) $mins, 2, '0', STR_PAD_LEFT ),
		'secs'   => str_pad( (string) $secs, 2, '0', STR_PAD_LEFT ),
		'target' => $dt->format( DATE_ATOM ),
	);
}

/**
 * Whether a product/event should render the Figma event landing (no shop header).
 *
 * @param int|WP_Post|null $post Event or product post.
 * @return bool
 */
function snf_is_figma_event_landing( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! in_array( $post->post_type, array( 'event', 'product' ), true ) ) {
		return false;
	}

	if ( function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( $post ) ) {
		return false;
	}

	return snf_is_member_select_event( $post ) || snf_is_public_select_event( $post );
}

/**
 * Normalized payload for the Figma event single template.
 *
 * @param int|WP_Post|null $post Event or product post.
 * @return array<string, mixed>|null
 */
function snf_get_event_single_data( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! in_array( $post->post_type, array( 'event', 'product' ), true ) ) {
		return null;
	}

	$post_id = (int) $post->ID;
	$slug    = function_exists( 'snf_resolve_listing_product_slug' )
		? snf_resolve_listing_product_slug( $post->post_name )
		: $post->post_name;
	$img_dir = get_template_directory_uri() . '/assets/images/events/';
	$is_product = ( 'product' === $post->post_type );

	$location  = (string) get_post_meta( $post_id, $is_product ? '_snf_location' : 'snf_event_location', true );
	$timing    = (string) get_post_meta( $post_id, $is_product ? '_snf_when' : 'snf_event_when', true );
	$price     = (string) get_post_meta( $post_id, 'snf_event_price', true );
	$organizer = (string) get_post_meta( $post_id, 'snf_event_organizer', true );
	$category  = (string) get_post_meta( $post_id, 'snf_event_category', true );
	$starts_at = (string) get_post_meta( $post_id, $is_product ? '_snf_starts_at' : 'snf_event_starts_at', true );
	$video     = (string) get_post_meta( $post_id, 'snf_event_video', true );
	if ( $video && function_exists( 'snf_normalize_video_embed_src' ) ) {
		$video = snf_normalize_video_embed_src( $video );
	}
	$broadcast = function_exists( 'snf_get_event_broadcast_data' )
		? snf_get_event_broadcast_data( $post )
		: array();

	if ( ! $starts_at && $is_product ) {
		$starts_at = (string) get_post_meta( $post_id, 'snf_event_starts_at', true );
	}
	if ( ! $location && $is_product ) {
		$location = (string) get_post_meta( $post_id, 'snf_event_location', true );
	}
	if ( ! $timing && $is_product ) {
		$timing = (string) get_post_meta( $post_id, 'snf_event_when', true );
	}

	if ( ! $price && $is_product && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $post_id );
		if ( $product ) {
			$price = html_entity_decode( wp_strip_all_tags( $product->get_price_html() ), ENT_QUOTES, 'UTF-8' );
		}
	}
	if ( ! $price ) {
		$price = snf_get_event_default_price( $slug );
	}
	if ( ! $organizer ) {
		$organizer = __( 'THE SUPER NINETY FOUR EVENTS COMPANY', 'super-ninety-four' );
	}
	if ( ! $category ) {
		$category = __( 'Day to Day', 'super-ninety-four' );
	}
	if ( ! $location ) {
		$defaults = function_exists( 'snf_get_default_event_product_serp_meta' )
			? snf_get_default_event_product_serp_meta()
			: array();
		$location = isset( $defaults[ $slug ]['location'] )
			? (string) $defaults[ $slug ]['location']
			: '';
	}

	$image = get_the_post_thumbnail_url( $post_id, 'full' );
	if ( ! $image ) {
		$file = snf_get_event_default_image_file( $slug );
		if ( $file ) {
			$image = $img_dir . $file;
		}
	}

	$defaults_copy = snf_get_event_default_description( $slug );

	$content = $post->post_content;
	if ( $is_product && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $post_id );
		if ( $product ) {
			$content = $product->get_description();
			if ( ! $content ) {
				$content = $product->get_short_description();
			}
		}
	}

	$description = has_excerpt( $post_id )
		? get_the_excerpt( $post )
		: wp_trim_words( wp_strip_all_tags( (string) $content ), 90 );

	// Shared placeholder used across older product bodies — swap for event-specific copy.
	$generic_blurb = 'Private invite-only immersion on the SUPER NINETY FOUR circuit';
	$content_plain = trim( wp_strip_all_tags( (string) $content ) );
	$is_generic    = ( $description && false !== stripos( wp_strip_all_tags( (string) $description ), $generic_blurb ) )
		|| ( $content_plain && false !== stripos( $content_plain, $generic_blurb ) );

	if ( ! $description || $is_generic ) {
		$description = trim( $defaults_copy['short'] . ' ' . $defaults_copy['long'] );
	}

	// Prefer event-specific defaults over empty or generic product blurbs.
	if ( ! $content_plain || $is_generic ) {
		$content = '';
	}

	$is_workshop = function_exists( 'snf_is_lifestyle_concierge_workshop' )
		? snf_is_lifestyle_concierge_workshop( $post_id )
		: false;
	$audience    = snf_get_event_admission_audience( $post );
	$member      = function_exists( 'snf_get_hub_member_context' ) ? snf_get_hub_member_context() : array();
	$plans       = function_exists( 'snf_get_membership_plans' ) ? snf_get_membership_plans() : array();
	$rendered    = $content ? apply_filters( 'the_content', $content ) : '';
	$logo        = snf_get_event_brand_logo( $slug );
	$logo_url    = get_template_directory_uri() . '/assets/icons/' . ltrim( (string) $logo['file'], '/' );
	$commerce    = function_exists( 'snf_get_ticket_commerce_payload' )
		? snf_get_ticket_commerce_payload( $post )
		: array();
	$product_id   = ! empty( $commerce['product_id'] )
		? (int) $commerce['product_id']
		: ( $is_product ? $post_id : 0 );
	$wc_product   = ( $product_id && function_exists( 'wc_get_product' ) )
		? wc_get_product( $product_id )
		: null;
	$can_purchase = $product_id
		&& ! empty( $commerce['enabled'] )
		&& $wc_product
		&& $wc_product->is_purchasable();

	return array(
		'id'            => $post_id,
		'slug'          => $slug,
		'title'         => get_the_title( $post ),
		'category'      => $category,
		'location'      => $location,
		'timing'        => $timing,
		'organizer'     => $organizer,
		'price'         => $price,
		'description'   => $description,
		'content'       => $rendered,
		'image'         => $image,
		'video'         => $video,
		'broadcast'     => $broadcast,
		'logo'          => array(
			'url'     => $logo_url,
			'width'   => (int) $logo['width'],
			'height'  => (int) $logo['height'],
			'variant' => (string) $logo['variant'],
		),
		'countdown'     => snf_event_countdown_parts( $starts_at ),
		'action_url'    => snf_get_event_action_url( $post_id ),
		'action_label'  => snf_get_event_action_label( $post_id ),
		'is_workshop'   => $is_workshop,
		'audience'      => $audience,
		'member'        => $member,
		'plans'         => $plans,
		'apply_url'     => function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'apply' ) : home_url( '/apply/' ),
		'figma_node'    => snf_get_event_figma_node( $slug ),
		'ticket'        => $commerce,
		'product_id'    => $product_id,
		'can_purchase'  => (bool) $can_purchase,
		'is_public'     => snf_is_public_select_event( $post ),
		'starts_at'     => $starts_at,
		'cart_url'      => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
		'permalink'     => get_permalink( $product_id ? $product_id : $post_id ),
	);
}
