<?php
/**
 * TicketSpice-style ticket tiers, addons, upsells, and immerse interactivities.
 *
 * Hook contract:
 * - Listen: woocommerce_add_to_cart_validation, woocommerce_add_cart_item_data,
 *   woocommerce_before_calculate_totals, woocommerce_checkout_create_order_line_item,
 *   woocommerce_payment_complete / order_status_*.
 * - Emit: snf_ticket_order_processed.
 *
 * Extends existing `event` CPT + Woo `product` posts — does not register a
 * duplicate event post type. Taxonomies classify experience / addon kinds only.
 *
 * @package SuperNinetyFour
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SNF_TICKET_ADDON_SETTINGS_OPTION = 'snf_ticket_addon_settings';

/**
 * Default Ticket Addons & Upgrades / commerce desk settings.
 *
 * @return array<string, mixed>
 */
function snf_ticket_addon_default_settings() {
	return array(
		'require_kind_on_addons'          => 1,
		'require_kind_on_upsells'         => 1,
		'require_kind_on_interactivities' => 1,
		'default_addon_max_qty'           => 1,
		'auto_assign_terms'               => 1,
		'desk_intro'                      => '',
	);
}

/**
 * Merged ticket addon settings.
 *
 * @return array<string, mixed>
 */
function snf_get_ticket_addon_settings() {
	$stored = get_option( SNF_TICKET_ADDON_SETTINGS_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	return array_merge( snf_ticket_addon_default_settings(), $stored );
}

/**
 * Post types that accept ticket commerce config.
 *
 * @return string[]
 */
function snf_ticket_commerce_post_types() {
	return array( 'product', 'event' );
}

/**
 * Meta key for structured ticket commerce config.
 *
 * @return string
 */
function snf_ticket_commerce_meta_key() {
	return '_snf_ticket_commerce';
}

/**
 * Register experience + addon-kind taxonomies on event/product (no CPT duplication).
 */
function snf_register_ticket_commerce_taxonomies() {
	$types = snf_ticket_commerce_post_types();

	register_taxonomy(
		'snf_event_experience',
		$types,
		array(
			'labels'            => array(
				'name'          => __( 'Event Experiences', 'super-ninety-four' ),
				'singular_name' => __( 'Event Experience', 'super-ninety-four' ),
				'search_items'  => __( 'Search Experiences', 'super-ninety-four' ),
				'all_items'     => __( 'All Experiences', 'super-ninety-four' ),
				'edit_item'     => __( 'Edit Experience', 'super-ninety-four' ),
				'update_item'   => __( 'Update Experience', 'super-ninety-four' ),
				'add_new_item'  => __( 'Add New Experience', 'super-ninety-four' ),
				'new_item_name' => __( 'New Experience Name', 'super-ninety-four' ),
				'menu_name'     => __( 'Experiences', 'super-ninety-four' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'show_in_menu'      => false,
			'rewrite'           => array( 'slug' => 'experience' ),
		)
	);

	register_taxonomy(
		'snf_ticket_addon_kind',
		$types,
		array(
			'labels'            => array(
				'name'          => __( 'Ticket Addons & Upgrades', 'super-ninety-four' ),
				'singular_name' => __( 'Ticket Addon / Upgrade', 'super-ninety-four' ),
				'search_items'  => __( 'Search Addons & Upgrades', 'super-ninety-four' ),
				'all_items'     => __( 'All Addons & Upgrades', 'super-ninety-four' ),
				'edit_item'     => __( 'Edit Addon / Upgrade', 'super-ninety-four' ),
				'update_item'   => __( 'Update Addon / Upgrade', 'super-ninety-four' ),
				'add_new_item'  => __( 'Add New Addon / Upgrade', 'super-ninety-four' ),
				'new_item_name' => __( 'New Addon / Upgrade Name', 'super-ninety-four' ),
				'menu_name'     => __( 'Addons & Upgrades', 'super-ninety-four' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'show_in_menu'      => false,
			'rewrite'           => array( 'slug' => 'ticket-addon' ),
		)
	);
}
add_action( 'init', 'snf_register_ticket_commerce_taxonomies', 6 );

/**
 * Default experience terms (immerse window included).
 *
 * @return array<string, string>
 */
function snf_ticket_commerce_default_experiences() {
	return array(
		'immerse'   => __( 'Immerse', 'super-ninety-four' ),
		'gathering' => __( 'Gathering', 'super-ninety-four' ),
		'journey'   => __( 'Journey', 'super-ninety-four' ),
		'workshop'  => __( 'Workshop', 'super-ninety-four' ),
		'day-to-day'=> __( 'Day to Day', 'super-ninety-four' ),
	);
}

/**
 * Default addon kind terms.
 *
 * @return array<string, string>
 */
function snf_ticket_commerce_default_addon_kinds() {
	return array(
		'hospitality'   => __( 'Hospitality', 'super-ninety-four' ),
		'transport'     => __( 'Transport', 'super-ninety-four' ),
		'media'         => __( 'Media access', 'super-ninety-four' ),
		'interactivity' => __( 'Interactivity', 'super-ninety-four' ),
		'guest'         => __( 'Guest / party', 'super-ninety-four' ),
		'merch'         => __( 'Merch', 'super-ninety-four' ),
	);
}

/**
 * Insert missing default experience + addon-kind terms.
 *
 * @param bool $force When true, ignore the one-shot seeded option.
 * @return array{experiences:int,addons:int}
 */
function snf_ticket_commerce_seed_default_terms( $force = false ) {
	$created = array(
		'experiences' => 0,
		'addons'      => 0,
	);

	if ( ! $force && get_option( 'snf_ticket_commerce_terms_seeded' ) ) {
		return $created;
	}

	if ( ! taxonomy_exists( 'snf_event_experience' ) || ! taxonomy_exists( 'snf_ticket_addon_kind' ) ) {
		return $created;
	}

	foreach ( snf_ticket_commerce_default_experiences() as $slug => $label ) {
		if ( ! term_exists( $slug, 'snf_event_experience' ) ) {
			$result = wp_insert_term( $label, 'snf_event_experience', array( 'slug' => $slug ) );
			if ( ! is_wp_error( $result ) ) {
				++$created['experiences'];
			}
		}
	}

	foreach ( snf_ticket_commerce_default_addon_kinds() as $slug => $label ) {
		if ( ! term_exists( $slug, 'snf_ticket_addon_kind' ) ) {
			$result = wp_insert_term( $label, 'snf_ticket_addon_kind', array( 'slug' => $slug ) );
			if ( ! is_wp_error( $result ) ) {
				++$created['addons'];
			}
		}
	}

	update_option( 'snf_ticket_commerce_terms_seeded', 1, false );

	return $created;
}

/**
 * Ensure default taxonomy terms exist once.
 */
function snf_ensure_ticket_commerce_terms() {
	snf_ticket_commerce_seed_default_terms( false );
}
add_action( 'init', 'snf_ensure_ticket_commerce_terms', 20 );

/**
 * Empty ticket commerce schema.
 *
 * @return array<string, mixed>
 */
function snf_ticket_commerce_empty_config() {
	return array(
		'enabled'         => false,
		'tiers'           => array(),
		'addons'          => array(),
		'upsells'         => array(),
		'interactivities' => array(),
	);
}

/**
 * Sanitize one commerce line item (tier / addon / upsell / interactivity).
 *
 * @param array<string, mixed> $row   Raw row.
 * @param string               $group Config group key.
 * @return array<string, mixed>|null
 */
function snf_ticket_commerce_sanitize_row( $row, $group = '' ) {
	if ( ! is_array( $row ) ) {
		return null;
	}

	$id = isset( $row['id'] ) ? sanitize_title( (string) $row['id'] ) : '';
	if ( '' === $id && ! empty( $row['label'] ) ) {
		$id = sanitize_title( (string) $row['label'] );
	}
	if ( '' === $id ) {
		return null;
	}

	$price = isset( $row['price'] ) ? (float) $row['price'] : 0.0;
	if ( $price < 0 ) {
		$price = 0.0;
	}

	$settings  = snf_get_ticket_addon_settings();
	$default_q = max( 1, (int) $settings['default_addon_max_qty'] );
	$max_qty   = isset( $row['max_qty'] ) ? absint( $row['max_qty'] ) : $default_q;
	if ( $max_qty < 1 ) {
		$max_qty = $default_q;
	}

	$requires = array();
	if ( ! empty( $row['requires_tier'] ) && is_array( $row['requires_tier'] ) ) {
		foreach ( $row['requires_tier'] as $tier_id ) {
			$tier_id = sanitize_title( (string) $tier_id );
			if ( $tier_id ) {
				$requires[] = $tier_id;
			}
		}
	} elseif ( ! empty( $row['requires_tier'] ) && is_string( $row['requires_tier'] ) ) {
		foreach ( preg_split( '/[\s,]+/', $row['requires_tier'] ) as $tier_id ) {
			$tier_id = sanitize_title( $tier_id );
			if ( $tier_id ) {
				$requires[] = $tier_id;
			}
		}
	}

	$product_id = isset( $row['product_id'] ) ? absint( $row['product_id'] ) : 0;
	$kind       = sanitize_title( (string) ( $row['kind'] ?? '' ) );

	$require_map = array(
		'addons'          => ! empty( $settings['require_kind_on_addons'] ),
		'upsells'         => ! empty( $settings['require_kind_on_upsells'] ),
		'interactivities' => ! empty( $settings['require_kind_on_interactivities'] ),
	);
	if ( $group && ! empty( $require_map[ $group ] ) && '' === $kind ) {
		return null;
	}

	return array(
		'id'            => $id,
		'label'         => sanitize_text_field( (string) ( $row['label'] ?? $id ) ),
		'description'   => sanitize_textarea_field( (string) ( $row['description'] ?? '' ) ),
		'price'         => $price,
		'kind'          => $kind,
		'max_qty'       => $max_qty,
		'capacity'      => isset( $row['capacity'] ) ? absint( $row['capacity'] ) : 0,
		'upsell'        => ! empty( $row['upsell'] ),
		'required'      => ! empty( $row['required'] ),
		'requires_tier' => array_values( array_unique( $requires ) ),
		'product_id'    => $product_id,
		'provider'      => sanitize_key( (string) ( $row['provider'] ?? '' ) ),
	);
}

/**
 * Sanitize full ticket commerce config.
 *
 * @param mixed $raw Raw config.
 * @return array<string, mixed>
 */
function snf_ticket_commerce_sanitize_config( $raw ) {
	$config = snf_ticket_commerce_empty_config();

	if ( is_string( $raw ) ) {
		$decoded = json_decode( $raw, true );
		$raw     = is_array( $decoded ) ? $decoded : array();
	}

	if ( ! is_array( $raw ) ) {
		return $config;
	}

	$config['enabled'] = ! empty( $raw['enabled'] );

	foreach ( array( 'tiers', 'addons', 'upsells', 'interactivities' ) as $group ) {
		if ( empty( $raw[ $group ] ) || ! is_array( $raw[ $group ] ) ) {
			continue;
		}
		foreach ( $raw[ $group ] as $row ) {
			$clean = snf_ticket_commerce_sanitize_row( $row, $group );
			if ( $clean ) {
				$config[ $group ][] = $clean;
			}
		}
	}

	return $config;
}

/**
 * Collect addon-kind slugs referenced by a commerce config.
 *
 * @param array<string, mixed> $config Config.
 * @return string[]
 */
function snf_ticket_commerce_config_kind_slugs( $config ) {
	$slugs = array();
	if ( ! is_array( $config ) ) {
		return $slugs;
	}
	foreach ( array( 'addons', 'upsells', 'interactivities' ) as $group ) {
		if ( empty( $config[ $group ] ) || ! is_array( $config[ $group ] ) ) {
			continue;
		}
		foreach ( $config[ $group ] as $row ) {
			$kind = isset( $row['kind'] ) ? sanitize_title( (string) $row['kind'] ) : '';
			if ( $kind ) {
				$slugs[] = $kind;
			}
		}
	}
	return array_values( array_unique( $slugs ) );
}

/**
 * Assign snf_ticket_addon_kind terms from commerce config kinds.
 *
 * @param int                  $post_id Post ID.
 * @param array<string, mixed> $config  Config.
 */
function snf_ticket_commerce_sync_addon_kind_terms( $post_id, $config ) {
	$settings = snf_get_ticket_addon_settings();
	if ( empty( $settings['auto_assign_terms'] ) || ! taxonomy_exists( 'snf_ticket_addon_kind' ) ) {
		return;
	}

	$post_id = absint( $post_id );
	if ( ! $post_id ) {
		return;
	}

	$slugs = snf_ticket_commerce_config_kind_slugs( $config );
	$ids   = array();
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'snf_ticket_addon_kind' );
		if ( $term && ! is_wp_error( $term ) ) {
			$ids[] = (int) $term->term_id;
			continue;
		}
		$defaults = snf_ticket_commerce_default_addon_kinds();
		if ( isset( $defaults[ $slug ] ) ) {
			$created = wp_insert_term( $defaults[ $slug ], 'snf_ticket_addon_kind', array( 'slug' => $slug ) );
			if ( ! is_wp_error( $created ) && ! empty( $created['term_id'] ) ) {
				$ids[] = (int) $created['term_id'];
			}
		}
	}

	wp_set_object_terms( $post_id, $ids, 'snf_ticket_addon_kind', false );
}

/**
 * Default ticket commerce presets by product/event slug.
 *
 * @param string $slug Post slug.
 * @return array<string, mixed>|null
 */
function snf_ticket_commerce_default_for_slug( $slug ) {
	$slug = sanitize_title( (string) $slug );

	$presets = array(
		'the-township-adventures-event'         => array(
			'enabled'         => true,
			'tiers'           => array(
				array(
					'id'          => 'principal',
					'label'       => __( 'Principal immersion seat', 'super-ninety-four' ),
					'description' => __( 'Full Township Adventures route access.', 'super-ninety-four' ),
					'price'       => 2500,
					'capacity'    => 40,
				),
				array(
					'id'          => 'guest',
					'label'       => __( 'Accompanied guest', 'super-ninety-four' ),
					'description' => __( 'Guest seat tied to a principal.', 'super-ninety-four' ),
					'price'       => 1200,
					'capacity'    => 20,
				),
			),
			'addons'          => array(
				array(
					'id'      => 'hospitality-desk',
					'label'   => __( 'Private hospitality desk', 'super-ninety-four' ),
					'price'   => 450,
					'kind'    => 'hospitality',
					'upsell'  => true,
					'max_qty' => 1,
				),
				array(
					'id'      => 'ground-transfer',
					'label'   => __( 'Ground transfer window', 'super-ninety-four' ),
					'price'   => 275,
					'kind'    => 'transport',
					'upsell'  => true,
					'max_qty' => 4,
				),
			),
			'upsells'         => array(
				array(
					'id'      => 'after-hours-salon',
					'label'   => __( 'After-hours salon upsell', 'super-ninety-four' ),
					'price'   => 350,
					'kind'    => 'hospitality',
					'upsell'  => true,
					'max_qty' => 1,
				),
			),
			'interactivities' => array(
				array(
					'id'            => 'live-audio-room',
					'label'         => __( 'Live audio interactivity room', 'super-ninety-four' ),
					'description'   => __( 'Ticket-gated WebRTC audio desk during the immerse window.', 'super-ninety-four' ),
					'price'         => 95,
					'kind'          => 'interactivity',
					'provider'      => 'webrtc',
					'requires_tier' => array( 'principal', 'guest' ),
					'max_qty'       => 1,
				),
			),
		),
		'the-eight-hundred-plus-journeys-event' => array(
			'enabled'         => true,
			'tiers'           => array(
				array(
					'id'          => 'journey',
					'label'       => __( '800+ journey seat', 'super-ninety-four' ),
					'description' => __( 'Multi-leg immerse journey admission.', 'super-ninety-four' ),
					'price'       => 4800,
					'capacity'    => 24,
				),
				array(
					'id'          => 'observer',
					'label'       => __( 'Observer / soft RSVP', 'super-ninety-four' ),
					'price'       => 900,
					'capacity'    => 12,
				),
			),
			'addons'          => array(
				array(
					'id'     => 'security-detail',
					'label'  => __( 'Private security detail window', 'super-ninety-four' ),
					'price'  => 1200,
					'kind'   => 'hospitality',
					'upsell' => true,
				),
			),
			'upsells'         => array(
				array(
					'id'     => 'media-replay',
					'label'  => __( 'Private media replay vault', 'super-ninety-four' ),
					'price'  => 180,
					'kind'   => 'media',
					'upsell' => true,
				),
			),
			'interactivities' => array(
				array(
					'id'          => 'hybrid-desk',
					'label'       => __( 'Hybrid desk interactivity', 'super-ninety-four' ),
					'description' => __( 'Live + venue hybrid check-ins during journey legs.', 'super-ninety-four' ),
					'price'       => 150,
					'kind'        => 'interactivity',
					'provider'    => 'hybrid',
				),
			),
		),
		'the-badlands-bra-private-gathering'    => array(
			'enabled'         => true,
			'tiers'           => array(
				array(
					'id'          => 'gathering',
					'label'       => __( 'Private gathering seat', 'super-ninety-four' ),
					'price'       => 1800,
					'capacity'    => 60,
				),
				array(
					'id'          => 'table-host',
					'label'       => __( 'Table host', 'super-ninety-four' ),
					'description' => __( 'Host / sponsor a table with party seats.', 'super-ninety-four' ),
					'price'       => 4200,
					'capacity'    => 8,
				),
			),
			'addons'          => array(
				array(
					'id'      => 'guest-pass',
					'label'   => __( 'Additional guest pass', 'super-ninety-four' ),
					'price'   => 650,
					'kind'    => 'guest',
					'upsell'  => true,
					'max_qty' => 3,
				),
			),
			'upsells'         => array(
				array(
					'id'     => 'champagne-service',
					'label'  => __( 'Champagne service upsell', 'super-ninety-four' ),
					'price'  => 220,
					'kind'   => 'hospitality',
					'upsell' => true,
				),
			),
			'interactivities' => array(
				array(
					'id'       => 'bus-relay',
					'label'    => __( 'Party-bus live relay', 'super-ninety-four' ),
					'price'    => 75,
					'kind'     => 'interactivity',
					'provider' => 'webrtc',
				),
			),
		),
		'the-lifestyle-concierge-club-workshop' => array(
			'enabled'         => true,
			'tiers'           => array(
				array(
					'id'          => 'workshop',
					'label'       => __( 'Workshop seat', 'super-ninety-four' ),
					'description' => __( 'Lifestyle Concierge Club workshop admission.', 'super-ninety-four' ),
					'price'       => 0,
					'capacity'    => 100,
				),
			),
			'addons'          => array(
				array(
					'id'      => 'firm-brief',
					'label'   => __( 'Private firm brief add-on', 'super-ninety-four' ),
					'price'   => 497,
					'kind'    => 'hospitality',
					'upsell'  => true,
					'max_qty' => 1,
				),
			),
			'upsells'         => array(
				array(
					'id'     => 'recording-pack',
					'label'  => __( 'Session recording pack', 'super-ninety-four' ),
					'price'  => 97,
					'kind'   => 'media',
					'upsell' => true,
				),
			),
			'interactivities' => array(
				array(
					'id'       => 'qna-room',
					'label'    => __( 'Live Q&A interactivity room', 'super-ninety-four' ),
					'price'    => 49,
					'kind'     => 'interactivity',
					'provider' => 'webrtc',
				),
			),
		),
	);

	return isset( $presets[ $slug ] ) ? snf_ticket_commerce_sanitize_config( $presets[ $slug ] ) : null;
}

/**
 * Experience taxonomy slug for a listing slug.
 *
 * @param string $slug Post slug.
 * @return string
 */
function snf_ticket_commerce_experience_for_slug( $slug ) {
	$slug = sanitize_title( (string) $slug );
	$map  = array(
		'the-township-adventures-event'         => 'immerse',
		'the-eight-hundred-plus-journeys-event' => 'immerse',
		'the-eight-hundred-plus-journeys'       => 'immerse',
		'the-badlands-bra-private-gathering'    => 'gathering',
		'the-badlands-bra-party-bus-gathering'  => 'gathering',
		'the-lifestyle-concierge-club-workshop' => 'workshop',
	);

	return $map[ $slug ] ?? 'day-to-day';
}

/**
 * Resolve product ID for an event CPT twin (same slug) when needed.
 *
 * @param int $post_id Event or product ID.
 * @return int Woo product ID or 0.
 */
function snf_ticket_commerce_resolve_product_id( $post_id ) {
	$post_id = absint( $post_id );
	$post    = get_post( $post_id );
	if ( ! $post ) {
		return 0;
	}

	if ( 'product' === $post->post_type ) {
		return $post_id;
	}

	if ( 'event' !== $post->post_type ) {
		return 0;
	}

	$slugs = array( $post->post_name );
	if ( function_exists( 'snf_resolve_listing_product_slug' ) ) {
		$resolved = snf_resolve_listing_product_slug( $post->post_name );
		if ( $resolved && $resolved !== $post->post_name ) {
			$slugs[] = $resolved;
		}
	}

	foreach ( $slugs as $slug ) {
		$product = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $product ) {
			return (int) $product->ID;
		}
	}

	return 0;
}

/**
 * Read ticket commerce config for a post (with slug presets as fallback).
 *
 * @param int|WP_Post|null $post Post.
 * @return array<string, mixed>
 */
function snf_get_ticket_commerce_config( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! in_array( $post->post_type, snf_ticket_commerce_post_types(), true ) ) {
		return snf_ticket_commerce_empty_config();
	}

	$raw = get_post_meta( (int) $post->ID, snf_ticket_commerce_meta_key(), true );
	if ( $raw ) {
		$config = snf_ticket_commerce_sanitize_config( $raw );
		if ( $config['enabled'] || $config['tiers'] || $config['addons'] || $config['upsells'] || $config['interactivities'] ) {
			return $config;
		}
	}

	$slug = function_exists( 'snf_resolve_listing_product_slug' )
		? snf_resolve_listing_product_slug( $post->post_name )
		: $post->post_name;

	$preset = snf_ticket_commerce_default_for_slug( $slug );
	return $preset ? $preset : snf_ticket_commerce_empty_config();
}

/**
 * Whether ticket commerce UI should render for a post.
 *
 * @param int|WP_Post|null $post Post.
 * @return bool
 */
function snf_ticket_commerce_is_enabled( $post = null ) {
	$config = snf_get_ticket_commerce_config( $post );
	return ! empty( $config['enabled'] ) && ( ! empty( $config['tiers'] ) || ! empty( $config['addons'] ) || ! empty( $config['upsells'] ) || ! empty( $config['interactivities'] ) );
}

/**
 * Seed meta + experience term for known event products without overwriting edits.
 *
 * @param int $post_id Post ID.
 */
function snf_ticket_commerce_maybe_seed_post( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || ! in_array( $post->post_type, snf_ticket_commerce_post_types(), true ) ) {
		return;
	}

	$slug = function_exists( 'snf_resolve_listing_product_slug' )
		? snf_resolve_listing_product_slug( $post->post_name )
		: $post->post_name;

	$preset = snf_ticket_commerce_default_for_slug( $slug );
	if ( $preset && '' === (string) get_post_meta( $post_id, snf_ticket_commerce_meta_key(), true ) ) {
		update_post_meta( $post_id, snf_ticket_commerce_meta_key(), wp_json_encode( $preset ) );
		snf_ticket_commerce_sync_addon_kind_terms( $post_id, $preset );
	}

	if ( taxonomy_exists( 'snf_event_experience' ) ) {
		$exp = snf_ticket_commerce_experience_for_slug( $slug );
		if ( $exp && ! has_term( $exp, 'snf_event_experience', $post_id ) ) {
			wp_set_object_terms( $post_id, $exp, 'snf_event_experience', true );
		}
	}
}

/**
 * Seed commerce on SERP/event products after they exist (idempotent per post).
 */
function snf_ticket_commerce_seed_known_products() {
	$slugs = array(
		'the-township-adventures-event',
		'the-eight-hundred-plus-journeys-event',
		'the-badlands-bra-private-gathering',
		'the-lifestyle-concierge-club-workshop',
	);

	foreach ( $slugs as $slug ) {
		$product = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $product ) {
			snf_ticket_commerce_maybe_seed_post( (int) $product->ID );
		}
		$event = get_page_by_path( $slug, OBJECT, 'event' );
		if ( $event ) {
			snf_ticket_commerce_maybe_seed_post( (int) $event->ID );
		}
	}
}
add_action( 'init', 'snf_ticket_commerce_seed_known_products', 35 );

/**
 * Find a config row by id across groups.
 *
 * @param array<string, mixed> $config Config.
 * @param string               $id     Row id.
 * @return array{group:string,row:array<string,mixed>}|null
 */
function snf_ticket_commerce_find_row( $config, $id ) {
	$id = sanitize_title( (string) $id );
	foreach ( array( 'tiers', 'addons', 'upsells', 'interactivities' ) as $group ) {
		if ( empty( $config[ $group ] ) || ! is_array( $config[ $group ] ) ) {
			continue;
		}
		foreach ( $config[ $group ] as $row ) {
			if ( isset( $row['id'] ) && $row['id'] === $id ) {
				return array(
					'group' => $group,
					'row'   => $row,
				);
			}
		}
	}
	return null;
}

/**
 * Format money for UI.
 *
 * @param float $amount Amount.
 * @return string
 */
function snf_ticket_commerce_format_price( $amount ) {
	$amount = (float) $amount;
	if ( function_exists( 'wc_price' ) ) {
		return wp_strip_all_tags( wc_price( $amount ) );
	}
	return '$' . number_format_i18n( $amount, 2 );
}

/**
 * Admin metabox / Woo panel: editable commerce JSON rows.
 *
 * @param int $post_id Post ID.
 */
function snf_ticket_commerce_render_admin_fields( $post_id ) {
	$config = snf_get_ticket_commerce_config( $post_id );
	$json   = wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	?>
	<p>
		<label>
			<input type="checkbox" name="snf_ticket_commerce_enabled" value="1" <?php checked( ! empty( $config['enabled'] ) ); ?>>
			<?php esc_html_e( 'Enable TicketSpice-style tiers, addons, upsells & interactivities on order forms', 'super-ninety-four' ); ?>
		</label>
	</p>
	<p class="description">
		<?php esc_html_e( 'Edit the JSON below. Groups: tiers, addons, upsells, interactivities. Each row supports id, label, description, price, kind, max_qty, capacity, upsell, required, requires_tier, product_id, provider.', 'super-ninety-four' ); ?>
	</p>
	<textarea
		name="snf_ticket_commerce_json"
		id="snf_ticket_commerce_json"
		rows="18"
		style="width:100%;font-family:Consolas,Monaco,monospace;font-size:12px;"
	><?php echo esc_textarea( (string) $json ); ?></textarea>
	<?php
}

/**
 * WooCommerce product data panel for ticket commerce.
 */
function snf_ticket_commerce_product_panel() {
	global $post;
	if ( ! $post ) {
		return;
	}
	echo '<div id="snf_ticket_commerce_data" class="panel woocommerce_options_panel hidden">';
	echo '<div class="options_group" style="padding:12px 12px 0;">';
	snf_ticket_commerce_render_admin_fields( (int) $post->ID );
	echo '</div></div>';
}
add_action( 'woocommerce_product_data_panels', 'snf_ticket_commerce_product_panel' );

/**
 * Add Ticket Commerce tab on product data.
 *
 * @param array<string, array<string, mixed>> $tabs Tabs.
 * @return array<string, array<string, mixed>>
 */
function snf_ticket_commerce_product_data_tab( $tabs ) {
	$tabs['snf_ticket_commerce'] = array(
		'label'    => __( 'Ticket commerce', 'super-ninety-four' ),
		'target'   => 'snf_ticket_commerce_data',
		'class'    => array(),
		'priority' => 65,
	);
	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'snf_ticket_commerce_product_data_tab' );

/**
 * Persist product ticket commerce fields.
 *
 * @param int $product_id Product ID.
 */
function snf_ticket_commerce_save_product_fields( $product_id ) {
	if ( ! isset( $_POST['snf_ticket_commerce_json'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	$raw = wp_unslash( $_POST['snf_ticket_commerce_json'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$config = snf_ticket_commerce_sanitize_config( $raw );
	$config['enabled'] = ! empty( $_POST['snf_ticket_commerce_enabled'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	update_post_meta( $product_id, snf_ticket_commerce_meta_key(), wp_json_encode( $config ) );
	snf_ticket_commerce_sync_addon_kind_terms( $product_id, $config );
	snf_ticket_commerce_maybe_seed_post( $product_id );
}
add_action( 'woocommerce_process_product_meta', 'snf_ticket_commerce_save_product_fields', 25 );

/**
 * Metabox for legacy event CPT.
 */
function snf_ticket_commerce_add_event_metabox() {
	add_meta_box(
		'snf_ticket_commerce',
		__( 'Ticket commerce', 'super-ninety-four' ),
		'snf_ticket_commerce_render_event_metabox',
		'event',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'snf_ticket_commerce_add_event_metabox' );

/**
 * Render event CPT metabox.
 *
 * @param WP_Post $post Post.
 */
function snf_ticket_commerce_render_event_metabox( $post ) {
	wp_nonce_field( 'snf_save_ticket_commerce', 'snf_ticket_commerce_nonce' );
	snf_ticket_commerce_render_admin_fields( (int) $post->ID );
}

/**
 * Save event CPT ticket commerce.
 *
 * @param int $post_id Post ID.
 */
function snf_ticket_commerce_save_event_metabox( $post_id ) {
	if ( ! isset( $_POST['snf_ticket_commerce_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snf_ticket_commerce_nonce'] ) ), 'snf_save_ticket_commerce' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( 'event' !== get_post_type( $post_id ) ) {
		return;
	}

	$raw = isset( $_POST['snf_ticket_commerce_json'] ) ? wp_unslash( $_POST['snf_ticket_commerce_json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$config = snf_ticket_commerce_sanitize_config( $raw );
	$config['enabled'] = ! empty( $_POST['snf_ticket_commerce_enabled'] );
	update_post_meta( $post_id, snf_ticket_commerce_meta_key(), wp_json_encode( $config ) );
	snf_ticket_commerce_sync_addon_kind_terms( $post_id, $config );
}
add_action( 'save_post_event', 'snf_ticket_commerce_save_event_metabox' );

/**
 * Parse selected ticket options from request.
 *
 * @param array<string, mixed> $config Config.
 * @return array{tier:string,items:array<int,array{id:string,qty:int,group:string,label:string,price:float,product_id:int}>}
 */
function snf_ticket_commerce_parse_request_selection( $config ) {
	$tier = isset( $_REQUEST['snf_ticket_tier'] ) ? sanitize_title( wp_unslash( $_REQUEST['snf_ticket_tier'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$items = array();

	$groups = array(
		'addon'          => 'addons',
		'upsell'         => 'upsells',
		'interactivity'  => 'interactivities',
	);

	foreach ( $groups as $prefix => $group ) {
		$qty_map = isset( $_REQUEST[ 'snf_ticket_' . $prefix ] ) ? (array) wp_unslash( $_REQUEST[ 'snf_ticket_' . $prefix ] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( $qty_map as $id => $qty ) {
			$id  = sanitize_title( (string) $id );
			$qty = absint( $qty );
			if ( ! $id || $qty < 1 ) {
				continue;
			}
			$found = snf_ticket_commerce_find_row( $config, $id );
			if ( ! $found || $found['group'] !== $group ) {
				continue;
			}
			$row     = $found['row'];
			$max     = ! empty( $row['max_qty'] ) ? (int) $row['max_qty'] : 1;
			$qty     = min( $qty, $max );
			$requires = isset( $row['requires_tier'] ) ? (array) $row['requires_tier'] : array();
			if ( $requires && $tier && ! in_array( $tier, $requires, true ) ) {
				continue;
			}
			$items[] = array(
				'id'         => $id,
				'qty'        => $qty,
				'group'      => $group,
				'label'      => (string) $row['label'],
				'price'      => (float) $row['price'],
				'product_id' => (int) ( $row['product_id'] ?? 0 ),
			);
		}
	}

	// Checkbox-style selections (qty implied 1).
	foreach ( array( 'snf_ticket_addon_ids', 'snf_ticket_upsell_ids', 'snf_ticket_interactivity_ids' ) as $field ) {
		if ( empty( $_REQUEST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			continue;
		}
		$ids = (array) wp_unslash( $_REQUEST[ $field ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$map = array(
			'snf_ticket_addon_ids'          => 'addons',
			'snf_ticket_upsell_ids'         => 'upsells',
			'snf_ticket_interactivity_ids'  => 'interactivities',
		);
		$group = $map[ $field ];
		foreach ( $ids as $id ) {
			$id = sanitize_title( (string) $id );
			if ( ! $id ) {
				continue;
			}
			$already = false;
			foreach ( $items as $existing ) {
				if ( $existing['id'] === $id ) {
					$already = true;
					break;
				}
			}
			if ( $already ) {
				continue;
			}
			$found = snf_ticket_commerce_find_row( $config, $id );
			if ( ! $found || $found['group'] !== $group ) {
				continue;
			}
			$row = $found['row'];
			$requires = isset( $row['requires_tier'] ) ? (array) $row['requires_tier'] : array();
			if ( $requires && $tier && ! in_array( $tier, $requires, true ) ) {
				continue;
			}
			$items[] = array(
				'id'         => $id,
				'qty'        => 1,
				'group'      => $group,
				'label'      => (string) $row['label'],
				'price'      => (float) $row['price'],
				'product_id' => (int) ( $row['product_id'] ?? 0 ),
			);
		}
	}

	return array(
		'tier'  => $tier,
		'items' => $items,
	);
}

/**
 * Validate required tiers/addons on add-to-cart.
 *
 * @param bool $passed     Passed.
 * @param int  $product_id Product ID.
 * @return bool
 */
function snf_ticket_commerce_validate_add_to_cart( $passed, $product_id ) {
	if ( ! $passed || ! snf_ticket_commerce_is_enabled( $product_id ) ) {
		return $passed;
	}

	$config    = snf_get_ticket_commerce_config( $product_id );
	$selection = snf_ticket_commerce_parse_request_selection( $config );

	if ( ! empty( $config['tiers'] ) && '' === $selection['tier'] ) {
		wc_add_notice( __( 'Please choose a ticket tier before continuing.', 'super-ninety-four' ), 'error' );
		return false;
	}

	if ( $selection['tier'] ) {
		$found = snf_ticket_commerce_find_row( $config, $selection['tier'] );
		if ( ! $found || 'tiers' !== $found['group'] ) {
			wc_add_notice( __( 'The selected ticket tier is invalid.', 'super-ninety-four' ), 'error' );
			return false;
		}
	}

	foreach ( array( 'addons', 'upsells', 'interactivities' ) as $group ) {
		if ( empty( $config[ $group ] ) ) {
			continue;
		}
		foreach ( $config[ $group ] as $row ) {
			if ( empty( $row['required'] ) ) {
				continue;
			}
			$has = false;
			foreach ( $selection['items'] as $item ) {
				if ( $item['id'] === $row['id'] ) {
					$has = true;
					break;
				}
			}
			if ( ! $has ) {
				wc_add_notice(
					sprintf(
						/* translators: %s: required addon label */
						__( 'Please include the required option: %s', 'super-ninety-four' ),
						$row['label']
					),
					'error'
				);
				return false;
			}
		}
	}

	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'snf_ticket_commerce_validate_add_to_cart', 15, 2 );

/**
 * Attach ticket selection to cart line.
 *
 * @param array $cart_item_data Cart data.
 * @param int   $product_id    Product ID.
 * @return array
 */
function snf_ticket_commerce_add_cart_item_data( $cart_item_data, $product_id ) {
	if ( ! snf_ticket_commerce_is_enabled( $product_id ) ) {
		return $cart_item_data;
	}

	$config    = snf_get_ticket_commerce_config( $product_id );
	$selection = snf_ticket_commerce_parse_request_selection( $config );

	$tier_label = '';
	$tier_price = 0.0;
	if ( $selection['tier'] ) {
		$found = snf_ticket_commerce_find_row( $config, $selection['tier'] );
		if ( $found ) {
			$tier_label = (string) $found['row']['label'];
			$tier_price = (float) $found['row']['price'];
		}
	}

	$cart_item_data['snf_ticket_tier']       = $selection['tier'];
	$cart_item_data['snf_ticket_tier_label'] = $tier_label;
	$cart_item_data['snf_ticket_tier_price'] = $tier_price;
	$cart_item_data['snf_ticket_items']      = $selection['items'];

	// Admissions fields from event order form (optional).
	$admission_keys = array(
		'snf_event_preferred_name',
		'snf_event_home_club',
		'snf_event_seat',
		'snf_event_email',
		'snf_event_phone',
		'snf_event_city',
		'snf_event_intent_type',
		'snf_event_party_size',
		'snf_event_intent',
		'snf_event_name',
		'snf_event_org',
	);
	foreach ( $admission_keys as $key ) {
		if ( ! isset( $_REQUEST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			continue;
		}
		$value = wp_unslash( $_REQUEST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( 'snf_event_email' === $key ) {
			$value = sanitize_email( $value );
		} elseif ( 'snf_event_party_size' === $key ) {
			$value = (string) absint( $value );
		} elseif ( 'snf_event_intent' === $key ) {
			$value = sanitize_textarea_field( $value );
		} else {
			$value = sanitize_text_field( $value );
		}
		if ( '' !== $value ) {
			$cart_item_data[ $key ] = $value;
		}
	}

	$cart_item_data['unique_key'] = md5( wp_json_encode( array( $selection, microtime(), wp_rand() ) ) );

	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'snf_ticket_commerce_add_cart_item_data', 20, 2 );

/**
 * Adjust cart item price for selected tier (when tier price > 0).
 *
 * @param WC_Cart $cart Cart.
 */
function snf_ticket_commerce_before_calculate_totals( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	if ( ! $cart || $cart->is_empty() ) {
		return;
	}

	foreach ( $cart->get_cart() as $cart_item ) {
		if ( empty( $cart_item['snf_ticket_tier_price'] ) || empty( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) ) {
			continue;
		}
		$tier_price = (float) $cart_item['snf_ticket_tier_price'];
		if ( $tier_price <= 0 ) {
			continue;
		}
		$cart_item['data']->set_price( $tier_price );
	}
}
add_action( 'woocommerce_before_calculate_totals', 'snf_ticket_commerce_before_calculate_totals', 20 );

/**
 * Add Woo fees for selected addons / upsells / interactivities (non-linked products).
 *
 * @param WC_Cart $cart Cart.
 */
function snf_ticket_commerce_cart_fees( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	if ( ! $cart || $cart->is_empty() ) {
		return;
	}

	$fee_index = 0;
	foreach ( $cart->get_cart() as $cart_item ) {
		if ( empty( $cart_item['snf_ticket_items'] ) || ! is_array( $cart_item['snf_ticket_items'] ) ) {
			continue;
		}
		foreach ( $cart_item['snf_ticket_items'] as $item ) {
			if ( ! empty( $item['product_id'] ) ) {
				continue; // Linked products are added separately.
			}
			$qty   = max( 1, (int) ( $item['qty'] ?? 1 ) );
			$price = (float) ( $item['price'] ?? 0 );
			$total = $price * $qty;
			if ( $total <= 0 ) {
				continue;
			}
			$label = sprintf(
				/* translators: 1: addon label 2: qty */
				__( '%1$s × %2$d', 'super-ninety-four' ),
				(string) ( $item['label'] ?? __( 'Ticket add-on', 'super-ninety-four' ) ),
				$qty
			);
			$cart->add_fee( $label, $total, true );
			$fee_index++;
		}
	}
}
add_action( 'woocommerce_cart_calculate_fees', 'snf_ticket_commerce_cart_fees', 20 );

/**
 * After add-to-cart, also add linked addon products.
 *
 * @param string $cart_item_key Cart key.
 * @param int    $product_id    Product ID.
 * @param int    $quantity      Qty.
 * @param int    $variation_id  Variation.
 * @param array  $variation     Variation data.
 * @param array  $cart_item_data Cart item data.
 */
function snf_ticket_commerce_add_linked_products( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
	if ( empty( $cart_item_data['snf_ticket_items'] ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	foreach ( $cart_item_data['snf_ticket_items'] as $item ) {
		$linked = absint( $item['product_id'] ?? 0 );
		if ( ! $linked || $linked === (int) $product_id ) {
			continue;
		}
		$qty = max( 1, (int) ( $item['qty'] ?? 1 ) );
		WC()->cart->add_to_cart(
			$linked,
			$qty,
			0,
			array(),
			array(
				'snf_ticket_parent'     => (int) $product_id,
				'snf_ticket_option_id'  => (string) ( $item['id'] ?? '' ),
				'snf_ticket_option_lbl' => (string) ( $item['label'] ?? '' ),
				'unique_key'            => md5( $cart_item_key . $linked . wp_rand() ),
			)
		);
	}
}
add_action( 'woocommerce_add_to_cart', 'snf_ticket_commerce_add_linked_products', 30, 6 );

/**
 * Display ticket options in cart/checkout.
 *
 * @param array $item_data Display rows.
 * @param array $cart_item Cart item.
 * @return array
 */
function snf_ticket_commerce_cart_item_data( $item_data, $cart_item ) {
	if ( ! empty( $cart_item['snf_ticket_tier_label'] ) ) {
		$item_data[] = array(
			'key'   => __( 'Ticket tier', 'super-ninety-four' ),
			'value' => $cart_item['snf_ticket_tier_label'],
		);
	}

	if ( ! empty( $cart_item['snf_ticket_items'] ) && is_array( $cart_item['snf_ticket_items'] ) ) {
		$labels = array();
		foreach ( $cart_item['snf_ticket_items'] as $item ) {
			$labels[] = sprintf(
				'%s × %d',
				(string) ( $item['label'] ?? $item['id'] ),
				max( 1, (int) ( $item['qty'] ?? 1 ) )
			);
		}
		if ( $labels ) {
			$item_data[] = array(
				'key'   => __( 'Add-ons / upsells', 'super-ninety-four' ),
				'value' => implode( ', ', $labels ),
			);
		}
	}

	$admission_map = array(
		'snf_event_preferred_name' => __( 'Preferred name', 'super-ninety-four' ),
		'snf_event_home_club'      => __( 'Home club', 'super-ninety-four' ),
		'snf_event_seat'           => __( 'Membership seat', 'super-ninety-four' ),
		'snf_event_email'          => __( 'Email', 'super-ninety-four' ),
		'snf_event_phone'          => __( 'Phone', 'super-ninety-four' ),
		'snf_event_city'           => __( 'City / State', 'super-ninety-four' ),
		'snf_event_intent_type'    => __( 'Attendance intent', 'super-ninety-four' ),
		'snf_event_party_size'     => __( 'Party size', 'super-ninety-four' ),
		'snf_event_intent'         => __( 'Notes for the desk', 'super-ninety-four' ),
		'snf_event_name'           => __( 'Full name', 'super-ninety-four' ),
		'snf_event_org'            => __( 'Organization', 'super-ninety-four' ),
	);
	foreach ( $admission_map as $key => $label ) {
		if ( empty( $cart_item[ $key ] ) ) {
			continue;
		}
		$item_data[] = array(
			'key'   => $label,
			'value' => $cart_item[ $key ],
		);
	}

	if ( ! empty( $cart_item['snf_ticket_option_lbl'] ) ) {
		$item_data[] = array(
			'key'   => __( 'Event add-on', 'super-ninety-four' ),
			'value' => $cart_item['snf_ticket_option_lbl'],
		);
	}

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'snf_ticket_commerce_cart_item_data', 20, 2 );

/**
 * Persist ticket selection onto order line items.
 *
 * @param WC_Order_Item_Product $item          Order item.
 * @param string                $cart_item_key Cart key.
 * @param array                 $values        Cart values.
 */
function snf_ticket_commerce_checkout_line_item( $item, $cart_item_key, $values ) {
	if ( ! empty( $values['snf_ticket_tier_label'] ) ) {
		$item->add_meta_data( __( 'Ticket tier', 'super-ninety-four' ), $values['snf_ticket_tier_label'], true );
		$item->add_meta_data( '_snf_ticket_tier', (string) ( $values['snf_ticket_tier'] ?? '' ), true );
	}

	if ( ! empty( $values['snf_ticket_items'] ) && is_array( $values['snf_ticket_items'] ) ) {
		$labels = array();
		foreach ( $values['snf_ticket_items'] as $row ) {
			$labels[] = sprintf(
				'%s × %d (%s)',
				(string) ( $row['label'] ?? $row['id'] ),
				max( 1, (int) ( $row['qty'] ?? 1 ) ),
				(string) ( $row['group'] ?? 'addon' )
			);
		}
		$item->add_meta_data( __( 'Add-ons / upsells / interactivities', 'super-ninety-four' ), implode( '; ', $labels ), true );
		$item->add_meta_data( '_snf_ticket_items', wp_json_encode( $values['snf_ticket_items'] ), true );
	}

	$admission_map = array(
		'snf_event_preferred_name' => __( 'Preferred name', 'super-ninety-four' ),
		'snf_event_home_club'      => __( 'Home club', 'super-ninety-four' ),
		'snf_event_seat'           => __( 'Membership seat', 'super-ninety-four' ),
		'snf_event_email'          => __( 'Email', 'super-ninety-four' ),
		'snf_event_phone'          => __( 'Phone', 'super-ninety-four' ),
		'snf_event_city'           => __( 'City / State', 'super-ninety-four' ),
		'snf_event_intent_type'    => __( 'Attendance intent', 'super-ninety-four' ),
		'snf_event_party_size'     => __( 'Party size', 'super-ninety-four' ),
		'snf_event_intent'         => __( 'Notes for the desk', 'super-ninety-four' ),
		'snf_event_name'           => __( 'Full name', 'super-ninety-four' ),
		'snf_event_org'            => __( 'Organization', 'super-ninety-four' ),
	);
	foreach ( $admission_map as $key => $label ) {
		if ( empty( $values[ $key ] ) ) {
			continue;
		}
		$item->add_meta_data( $label, $values[ $key ], true );
		$item->add_meta_data( '_' . $key, $values[ $key ], true );
	}

	if ( ! empty( $values['snf_ticket_option_lbl'] ) ) {
		$item->add_meta_data( __( 'Event add-on', 'super-ninety-four' ), $values['snf_ticket_option_lbl'], true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'snf_ticket_commerce_checkout_line_item', 20, 3 );

/**
 * Redirect event ticket purchases to cart (alongside workshop).
 *
 * @param string $url URL.
 * @return string
 */
function snf_ticket_commerce_add_to_cart_redirect( $url ) {
	$product_id = 0;
	if ( isset( $_REQUEST['add-to-cart'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$product_id = absint( $_REQUEST['add-to-cart'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( $product_id && snf_ticket_commerce_is_enabled( $product_id ) && function_exists( 'wc_get_cart_url' ) ) {
		return wc_get_cart_url();
	}
	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'snf_ticket_commerce_add_to_cart_redirect', 20 );

/**
 * Payload slice for templates / snf_get_event_single_data.
 *
 * @param int|WP_Post|null $post Post.
 * @return array<string, mixed>
 */
function snf_get_ticket_commerce_payload( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return snf_ticket_commerce_empty_config();
	}

	$config     = snf_get_ticket_commerce_config( $post );
	$product_id = snf_ticket_commerce_resolve_product_id( (int) $post->ID );
	$experiences = array();
	if ( taxonomy_exists( 'snf_event_experience' ) ) {
		$terms = get_the_terms( (int) $post->ID, 'snf_event_experience' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$experiences[] = array(
					'slug' => $term->slug,
					'name' => $term->name,
				);
			}
		}
	}

	$decorate = static function ( $rows ) {
		$out = array();
		foreach ( (array) $rows as $row ) {
			$row['price_html'] = snf_ticket_commerce_format_price( (float) ( $row['price'] ?? 0 ) );
			$out[]             = $row;
		}
		return $out;
	};

	return array(
		'enabled'         => ! empty( $config['enabled'] ),
		'product_id'      => $product_id,
		'experiences'     => $experiences,
		'tiers'           => $decorate( $config['tiers'] ?? array() ),
		'addons'          => $decorate( $config['addons'] ?? array() ),
		'upsells'         => $decorate( $config['upsells'] ?? array() ),
		'interactivities' => $decorate( $config['interactivities'] ?? array() ),
	);
}

/**
 * Store apply-flow event attribution on the user / session.
 */
function snf_ticket_commerce_capture_apply_event() {
	$event_id = 0;
	if ( isset( $_POST['snf_apply_event'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$event_id = absint( wp_unslash( $_POST['snf_apply_event'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	} elseif ( isset( $_REQUEST['snf_event'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$event_id = absint( wp_unslash( $_REQUEST['snf_event'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	if ( ! $event_id ) {
		return;
	}

	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), 'snf_apply_event_id', $event_id );
	}

	if ( function_exists( 'WC' ) && WC()->session ) {
		WC()->session->set( 'snf_apply_event_id', $event_id );
	}
}
add_action( 'admin_post_nopriv_snf_apply_membership', 'snf_ticket_commerce_capture_apply_event', 5 );
add_action( 'admin_post_snf_apply_membership', 'snf_ticket_commerce_capture_apply_event', 5 );

/**
 * Attach apply event id onto membership orders.
 *
 * @param int $order_id Order ID.
 */
function snf_ticket_commerce_attach_apply_event_to_order( $order_id ) {
	$event_id = 0;
	if ( is_user_logged_in() ) {
		$event_id = absint( get_user_meta( get_current_user_id(), 'snf_apply_event_id', true ) );
	}
	if ( ! $event_id && function_exists( 'WC' ) && WC()->session ) {
		$event_id = absint( WC()->session->get( 'snf_apply_event_id' ) );
	}
	if ( ! $event_id || ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$order->update_meta_data( '_snf_apply_event_id', $event_id );
	$order->add_order_note(
		sprintf(
			/* translators: %d: event/product ID */
			__( 'Membership application attributed to event/product #%d.', 'super-ninety-four' ),
			$event_id
		)
	);
	$order->save();
}
add_action( 'woocommerce_checkout_order_processed', 'snf_ticket_commerce_attach_apply_event_to_order', 20 );

/**
 * Whether an order contains ticket-commerce line items.
 *
 * @param WC_Order $order Order.
 * @return bool
 */
function snf_order_has_ticket_commerce_items( $order ) {
	if ( ! $order ) {
		return false;
	}

	foreach ( $order->get_items() as $item ) {
		$product_id = (int) $item->get_product_id();
		if ( $product_id && function_exists( 'snf_ticket_commerce_is_enabled' ) && snf_ticket_commerce_is_enabled( $product_id ) ) {
			return true;
		}
		if ( $item->get_meta( '_snf_ticket_tier' ) || $item->get_meta( 'snf_ticket_tier' ) || $item->get_meta( '_snf_event_name' ) ) {
			return true;
		}
	}

	return (bool) apply_filters( 'snf_order_has_ticket_commerce_items', false, $order );
}

/**
 * Emit ticket order processed after Woo payment/status hooks.
 *
 * @param int $order_id Order ID.
 */
function snf_ticket_commerce_process_order( $order_id ) {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$order_id = (int) $order_id;
	$order    = wc_get_order( $order_id );
	if ( ! $order || 'yes' === $order->get_meta( '_snf_ticket_processed' ) ) {
		return;
	}

	if ( ! snf_order_has_ticket_commerce_items( $order ) ) {
		return;
	}

	$order->update_meta_data( '_snf_ticket_processed', 'yes' );
	$order->save();

	/**
	 * Fires when a WooCommerce order containing ticket commerce is paid/processing.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order.
	 */
	do_action( 'snf_ticket_order_processed', $order_id, $order );
}
add_action( 'woocommerce_order_status_completed', 'snf_ticket_commerce_process_order', 30 );
add_action( 'woocommerce_order_status_processing', 'snf_ticket_commerce_process_order', 30 );
add_action( 'woocommerce_payment_complete', 'snf_ticket_commerce_process_order', 30 );
