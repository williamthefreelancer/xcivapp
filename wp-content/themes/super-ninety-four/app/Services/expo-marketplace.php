<?php
/**
 * Expo Marketplace catalog rails (single /expo/ page; audience from membership).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current visitor should see the members Expo experience.
 *
 * @return bool
 */
function snf_is_expo_members_audience() {
	return function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas();
}

/**
 * Whether the current request is the Expo marketplace page.
 *
 * @return bool
 */
function snf_is_expo_marketplace_page() {
	return is_page( 'expo' )
		|| snf_is_page_template( 'page-templates/expo-marketplace.php' );
}

/**
 * @deprecated 2.13.1 Use snf_is_expo_members_audience().
 *
 * @return bool
 */
function snf_is_expo_members_page() {
	return snf_is_expo_marketplace_page() && snf_is_expo_members_audience();
}

/**
 * Scenic city-to-city drive definitions (Mapbox-ready).
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_expo_scenic_drives() {
	$img = get_template_directory_uri() . '/assets/images/events/';

	$drives = array(
		array(
			'id'       => 'charlotte-salem',
			'title'    => __( 'Charlotte to Salem Scenic Drive', 'super-ninety-four' ),
			'meta'     => __( 'City to city · lead rider on the road', 'super-ninety-four' ),
			'image'    => $img . 'eight-hundred-journeys-event.jpg',
			'url'      => '#snf-expo-lead-rider--charlotte-salem',
			'route_id' => 'charlotte-salem',
			'kind'     => 'drive',
		),
		array(
			'id'       => 'back-creek-circuit',
			'title'    => __( 'Back Creek Circuit Leg', 'super-ninety-four' ),
			'meta'     => __( 'Scenic corridor · gathering arrival', 'super-ninety-four' ),
			'image'    => $img . 'township-adventures-event.jpg',
			'url'      => '#snf-expo-lead-rider--back-creek-circuit',
			'route_id' => 'back-creek-circuit',
			'kind'     => 'drive',
		),
		array(
			'id'       => 'regions-ne-run',
			'title'    => __( 'Regions NE Coastal Run', 'super-ninety-four' ),
			'meta'     => __( 'City to city · outside Boston approach', 'super-ninety-four' ),
			'image'    => $img . 'badlands-bra-event.jpg',
			'url'      => '#snf-expo-lead-rider--regions-ne-run',
			'route_id' => 'regions-ne-run',
			'kind'     => 'drive',
		),
	);

	/**
	 * Filter Expo scenic drive cards.
	 *
	 * @param array $drives Drive card definitions.
	 */
	return apply_filters( 'snf_expo_scenic_drives', $drives );
}

/**
 * Live / hybrid broadcast cards for Expo (reuses media-library query).
 *
 * @param int $limit Max cards.
 * @return array<int, array<string, string>>
 */
function snf_get_expo_live_broadcast_cards( $limit = 6 ) {
	if ( ! function_exists( 'snf_query_upcoming_broadcasts' ) ) {
		return array();
	}

	$cards = array();
	foreach ( snf_query_upcoming_broadcasts( (int) $limit ) as $item ) {
		$broadcast = isset( $item['broadcast'] ) && is_array( $item['broadcast'] ) ? $item['broadcast'] : array();
		$status    = isset( $broadcast['status_label'] ) ? (string) $broadcast['status_label'] : '';
		$mode      = isset( $broadcast['mode_label'] ) ? (string) $broadcast['mode_label'] : '';
		$when      = isset( $item['when'] ) ? (string) $item['when'] : '';
		$meta_bits = array_filter( array( $mode, $status, $when ) );

		$image = isset( $item['image'] ) ? (string) $item['image'] : '';
		if ( ! $image ) {
			$image = get_template_directory_uri() . '/assets/images/events/eight-hundred-journeys-event.jpg';
		}

		$cards[] = array(
			'title' => isset( $item['title'] ) ? (string) $item['title'] : '',
			'meta'  => $meta_bits ? implode( ' · ', $meta_bits ) : __( 'Live / hybrid', 'super-ninety-four' ),
			'image' => $image,
			'url'   => isset( $item['url'] ) ? (string) $item['url'] : '',
			'kind'  => 'live',
		);
	}

	/**
	 * Filter Expo live/hybrid broadcast cards.
	 *
	 * @param array $cards Broadcast listing cards.
	 */
	return apply_filters( 'snf_expo_live_broadcast_cards', $cards );
}

/**
 * Gathering cards (not networking) for members Expo.
 *
 * @return array<int, array<string, string>>
 */
function snf_get_expo_gathering_cards() {
	$cards = array_values(
		array_filter(
			array(
				snf_get_dashboard_event_card_by_slug( 'the-badlands-bra-private-gathering', 'badlands-bra-event.jpg' ),
				snf_get_dashboard_event_card_by_slug( 'the-eight-hundred-plus-journeys-event', 'eight-hundred-journeys-event.jpg' ),
				snf_get_dashboard_event_card_by_slug( 'the-township-adventures-event', 'township-adventures-event.jpg' ),
			)
		)
	);

	foreach ( $cards as &$card ) {
		if ( ! empty( $card['meta'] ) ) {
			$card['meta'] = __( 'Gathering · not networking', 'super-ninety-four' ) . ' · ' . $card['meta'];
		} else {
			$card['meta'] = __( 'Gathering · not networking', 'super-ninety-four' );
		}
	}
	unset( $card );

	/**
	 * Filter Expo gathering cards.
	 *
	 * @param array $cards Gathering listing cards.
	 */
	return apply_filters( 'snf_expo_gathering_cards', $cards );
}

/**
 * Non-member teaser cards that deep-link to Apply.
 *
 * @return array<int, array<string, string>>
 */
function snf_get_expo_non_member_teasers() {
	$apply = snf_get_page_url( 'apply' );
	$img   = get_template_directory_uri() . '/assets/images/events/';

	$teasers = array(
		array(
			'title' => __( 'Scenic City to City Drives', 'super-ninety-four' ),
			'meta'  => __( 'Membership unlocks lead rider & walkie', 'super-ninety-four' ),
			'image' => $img . 'eight-hundred-journeys-event.jpg',
			'url'   => add_query_arg( 'q', rawurlencode( 'City to City Drives' ), $apply ),
		),
		array(
			'title' => __( 'Private Gatherings', 'super-ninety-four' ),
			'meta'  => __( 'Gatherings — not networking', 'super-ninety-four' ),
			'image' => $img . 'badlands-bra-event.jpg',
			'url'   => add_query_arg( 'q', rawurlencode( 'Private Gatherings' ), $apply ),
		),
	);

	$workshop = snf_get_dashboard_event_card_by_slug( 'the-lifestyle-concierge-club-workshop', 'lcc-workshop.jpg' );
	if ( $workshop ) {
		array_unshift( $teasers, $workshop );
	}

	/**
	 * Filter non-member Expo teaser cards.
	 *
	 * @param array $teasers Teaser cards.
	 */
	return apply_filters( 'snf_expo_non_member_teasers', $teasers );
}

/**
 * Catalog rails for an Expo marketplace audience.
 *
 * @param string $audience 'members' or 'non-members'.
 * @return array<int, array<string, mixed>>
 */
function snf_get_expo_catalog_rails( $audience = 'members' ) {
	if ( 'non-members' === $audience ) {
		$rails = array(
			array(
				'id'         => 'open-admission',
				'title'      => __( 'Open admission', 'super-ninety-four' ),
				'bp'         => 'events',
				'empty_text' => __( 'No open admission listings yet.', 'super-ninety-four' ),
				'cards'      => snf_get_dashboard_non_member_event_cards(),
			),
			array(
				'id'         => 'drive-gathering-teasers',
				'title'      => __( 'Drives & gatherings (preview)', 'super-ninety-four' ),
				'bp'         => 'events',
				'empty_text' => __( 'Preview listings will appear here.', 'super-ninety-four' ),
				'cards'      => snf_get_expo_non_member_teasers(),
			),
			array(
				'id'         => 'apply-path',
				'title'      => __( 'Join the circuit', 'super-ninety-four' ),
				'bp'         => 'activity',
				'empty_text' => '',
				'cards'      => array(
					array(
						'title' => __( 'Apply for membership', 'super-ninety-four' ),
						'meta'  => __( 'Unlock live lead rider, walkie, and select events', 'super-ninety-four' ),
						'image' => get_template_directory_uri() . '/assets/images/events/lifestyle-concierge-club-workshop.jpg',
						'url'   => snf_get_page_url( 'apply' ),
					),
				),
			),
		);
	} else {
		$rails = array(
			array(
				'id'         => 'select-events',
				'title'      => __( 'Select events', 'super-ninety-four' ),
				'bp'         => 'events',
				'empty_text' => __( 'No select events are listed yet.', 'super-ninety-four' ),
				'cards'      => snf_get_dashboard_member_event_cards(),
			),
			array(
				'id'         => 'scenic-drives',
				'title'      => __( 'Scenic city to city drives', 'super-ninety-four' ),
				'bp'         => 'events',
				'empty_text' => __( 'No scenic drives are listed yet.', 'super-ninety-four' ),
				'cards'      => snf_get_expo_scenic_drives(),
			),
			array(
				'id'         => 'gatherings',
				'title'      => __( 'Gatherings — not networking', 'super-ninety-four' ),
				'bp'         => 'events',
				'empty_text' => __( 'No gatherings are listed yet.', 'super-ninety-four' ),
				'cards'      => snf_get_expo_gathering_cards(),
			),
			array(
				'id'         => 'live-hybrid',
				'title'      => __( 'Live & hybrid broadcasts', 'super-ninety-four' ),
				'bp'         => 'activity',
				'empty_text' => __( 'No live or hybrid events are scheduled yet.', 'super-ninety-four' ),
				'cards'      => snf_get_expo_live_broadcast_cards(),
			),
		);
	}

	/**
	 * Filter Expo catalog rails.
	 *
	 * @param array  $rails    Rail definitions.
	 * @param string $audience Audience key.
	 */
	return apply_filters( 'snf_expo_catalog_rails', $rails, $audience );
}

/**
 * Marketplace audience key for the current user.
 *
 * @return string 'members'|'non-members'
 */
function snf_get_marketplace_audience() {
	return snf_is_expo_members_audience() ? 'members' : 'non-members';
}

/**
 * Product category facets available to the current audience.
 *
 * @param string $audience Audience key.
 * @return array<int, array{slug:string,label:string}>
 */
function snf_get_marketplace_taxonomy_facets( $audience = '' ) {
	if ( ! $audience ) {
		$audience = snf_get_marketplace_audience();
	}

	$cats = function_exists( 'snf_get_serp_product_categories' )
		? snf_get_serp_product_categories()
		: array(
			'events'   => __( 'Events', 'super-ninety-four' ),
			'services' => __( 'Services', 'super-ninety-four' ),
		);

	// Guests stay on open-admission events; services remain Apply-gated teasers.
	if ( 'members' !== $audience ) {
		unset( $cats['services'] );
	}

	$facets = array();
	foreach ( $cats as $slug => $label ) {
		$facets[] = array(
			'slug'  => (string) $slug,
			'label' => (string) $label,
		);
	}

	/**
	 * Filter marketplace taxonomy facets.
	 *
	 * @param array  $facets   Facet list.
	 * @param string $audience Audience key.
	 */
	return apply_filters( 'snf_marketplace_taxonomy_facets', $facets, $audience );
}

/**
 * Normalize product_cat query arg into sanitized slugs.
 *
 * @param string|array<int, string> $raw Raw product_cat value.
 * @param string                    $audience Audience key.
 * @return string[]
 */
function snf_parse_marketplace_product_cats( $raw, $audience = '' ) {
	if ( ! $audience ) {
		$audience = snf_get_marketplace_audience();
	}

	$allowed = wp_list_pluck( snf_get_marketplace_taxonomy_facets( $audience ), 'slug' );
	$parts   = is_array( $raw ) ? $raw : preg_split( '/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY );
	$slugs   = array();

	foreach ( (array) $parts as $part ) {
		$slug = sanitize_title( (string) $part );
		if ( $slug && in_array( $slug, $allowed, true ) ) {
			$slugs[] = $slug;
		}
	}

	return array_values( array_unique( $slugs ) );
}

/**
 * Map a dashboard/expo card into a shared marketplace result row.
 *
 * @param array<string, mixed> $card Card definition.
 * @return array<string, string>
 */
function snf_normalize_marketplace_card_row( $card ) {
	$image = isset( $card['image'] ) ? (string) $card['image'] : '';
	return array(
		'title'   => isset( $card['title'] ) ? (string) $card['title'] : '',
		'url'     => isset( $card['url'] ) ? (string) $card['url'] : '',
		'snippet' => isset( $card['snippet'] ) ? (string) $card['snippet'] : '',
		'meta'    => isset( $card['meta'] ) ? (string) $card['meta'] : '',
		'crumb'   => isset( $card['crumb'] ) ? (string) $card['crumb'] : 'expo',
		'action'  => isset( $card['action'] ) ? (string) $card['action'] : __( 'View listing', 'super-ninety-four' ),
		'favicon' => isset( $card['favicon'] ) ? (string) $card['favicon'] : '',
		'image'   => $image,
		'kind'    => isset( $card['kind'] ) ? (string) $card['kind'] : 'listing',
	);
}

/**
 * Query Woo products for marketplace search / taxonomy filter.
 *
 * @param string   $q        Search query.
 * @param string[] $cats     product_cat slugs.
 * @param string   $audience Audience key.
 * @return array<int, array<string, string>>
 */
function snf_query_marketplace_product_rows( $q = '', $cats = array(), $audience = '' ) {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'snf_get_product_serp_row' ) ) {
		return array();
	}

	if ( ! $audience ) {
		$audience = snf_get_marketplace_audience();
	}

	if ( empty( $cats ) ) {
		$cats = wp_list_pluck( snf_get_marketplace_taxonomy_facets( $audience ), 'slug' );
	}

	if ( empty( $cats ) ) {
		return array();
	}

	$args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'posts_per_page'         => 20,
		'orderby'                => 'title',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
		'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $cats,
				'operator' => 'IN',
			),
		),
	);

	if ( $q ) {
		$args['s'] = $q;
	}

	// Guests: only the Lifestyle Concierge workshop product from events.
	if ( 'members' !== $audience && function_exists( 'snf_get_workshop_product_slug' ) ) {
		$workshop = get_page_by_path( snf_get_workshop_product_slug(), OBJECT, 'product' );
		if ( $workshop instanceof WP_Post ) {
			$args['post__in'] = array( (int) $workshop->ID );
			$args['orderby']  = 'post__in';
		} else {
			return array();
		}
	}

	$query = new WP_Query( $args );
	$rows  = array();

	foreach ( $query->posts as $post ) {
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			continue;
		}
		$row         = snf_get_product_serp_row( $product );
		$row['kind'] = 'product';
		$row['image'] = '';
		$rows[]      = $row;
	}
	wp_reset_postdata();

	return $rows;
}

/**
 * Scenic drive rows that match a marketplace search (members only).
 *
 * @param string   $q    Search query.
 * @param string[] $cats Active product_cat slugs.
 * @return array<int, array<string, string>>
 */
function snf_get_marketplace_drive_rows( $q = '', $cats = array() ) {
	if ( ! empty( $cats ) && ! in_array( 'events', $cats, true ) ) {
		return array();
	}

	$q_lower = strtolower( trim( (string) $q ) );
	$rows    = array();

	foreach ( snf_get_expo_scenic_drives() as $drive ) {
		$title = isset( $drive['title'] ) ? (string) $drive['title'] : '';
		$meta  = isset( $drive['meta'] ) ? (string) $drive['meta'] : '';
		if ( $q_lower ) {
			$hay = strtolower( $title . ' ' . $meta );
			if ( false === strpos( $hay, $q_lower ) ) {
				continue;
			}
		}
		$rows[] = snf_normalize_marketplace_card_row(
			array_merge(
				$drive,
				array(
					'kind'   => 'drive',
					'action' => __( 'Open route', 'super-ninety-four' ),
					'crumb'  => 'expo › scenic drives',
				)
			)
		);
	}

	return $rows;
}

/**
 * Build marketplace REST / bootstrap payload.
 *
 * @param array<string, mixed> $args {
 *     @type string $q           Search query.
 *     @type string $product_cat Comma-separated product_cat slugs.
 * }
 * @return array<string, mixed>
 */
function snf_get_marketplace_payload( $args = array() ) {
	$audience = snf_get_marketplace_audience();
	$q        = isset( $args['q'] ) ? sanitize_text_field( (string) $args['q'] ) : '';
	$cats     = snf_parse_marketplace_product_cats( isset( $args['product_cat'] ) ? $args['product_cat'] : '', $audience );
	$facets   = snf_get_marketplace_taxonomy_facets( $audience );
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

	$searching = ( '' !== $q ) || ! empty( $cats );
	$results   = array();

	if ( $searching ) {
		$results = snf_query_marketplace_product_rows( $q, $cats, $audience );
		if ( 'members' === $audience ) {
			$results = array_merge( $results, snf_get_marketplace_drive_rows( $q, $cats ) );
		} elseif ( '' !== $q || in_array( 'events', $cats, true ) || empty( $cats ) ) {
			// Guest teasers that match the query (Apply deep-links).
			foreach ( snf_get_expo_non_member_teasers() as $teaser ) {
				$title = isset( $teaser['title'] ) ? (string) $teaser['title'] : '';
				$meta  = isset( $teaser['meta'] ) ? (string) $teaser['meta'] : '';
				if ( $q && false === stripos( $title . ' ' . $meta, $q ) ) {
					continue;
				}
				$results[] = snf_normalize_marketplace_card_row(
					array_merge(
						$teaser,
						array(
							'kind'   => 'teaser',
							'action' => __( 'Apply', 'super-ninety-four' ),
							'crumb'  => 'expo › preview',
						)
					)
				);
			}
		}
	}

	$rails = array();
	foreach ( snf_get_expo_catalog_rails( $audience ) as $rail ) {
		$rails[] = array(
			'id'         => isset( $rail['id'] ) ? (string) $rail['id'] : '',
			'title'      => isset( $rail['title'] ) ? (string) $rail['title'] : '',
			'empty_text' => isset( $rail['empty_text'] ) ? (string) $rail['empty_text'] : '',
			'cards'      => array_map(
				'snf_normalize_marketplace_card_row',
				isset( $rail['cards'] ) && is_array( $rail['cards'] ) ? $rail['cards'] : array()
			),
		);
	}

	$payload = array(
		'audience'  => $audience,
		'q'         => $q,
		'product_cat' => $cats,
		'facets'    => $facets,
		'searching' => $searching,
		'results'   => array_values( $results ),
		'rails'     => $rails,
		'shopUrl'   => $shop_url ? $shop_url : home_url( '/shop/' ),
		'expoUrl'   => snf_get_page_url( 'expo' ),
		'applyUrl'  => snf_get_page_url( 'apply' ),
		'count'     => count( $results ),
	);

	/**
	 * Filter marketplace payload.
	 *
	 * @param array  $payload  Response payload.
	 * @param string $audience Audience key.
	 */
	return apply_filters( 'snf_marketplace_payload', $payload, $audience );
}

/**
 * JS bootstrap for async marketplace search.
 *
 * @return array<string, mixed>
 */
function snf_get_marketplace_js_bootstrap() {
	$payload = snf_get_marketplace_payload(
		array(
			'q'           => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'product_cat' => isset( $_GET['product_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['product_cat'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		)
	);

	return array(
		'restUrl'   => esc_url_raw( rest_url( 'snf/v1/marketplace' ) ),
		'nonce'     => wp_create_nonce( 'wp_rest' ),
		'shopUrl'   => $payload['shopUrl'],
		'expoUrl'   => $payload['expoUrl'],
		'audience'  => $payload['audience'],
		'facets'    => $payload['facets'],
		'debounce'  => 280,
		'i18n'      => array(
			'all'          => __( 'All', 'super-ninety-four' ),
			'searching'    => __( 'Searching…', 'super-ninety-four' ),
			'empty'        => __( 'No listings matched that search.', 'super-ninety-four' ),
			'error'        => __( 'Could not load marketplace results. Try the full shop page.', 'super-ninety-four' ),
			'resultsFor'   => __( 'About %1$s results for “%2$s”', 'super-ninety-four' ),
			'browseShop'   => __( 'Browse shop', 'super-ninety-four' ),
			'viewListing'  => __( 'View listing', 'super-ninety-four' ),
		),
	);
}

/**
 * REST: public marketplace catalog (audience-filtered server-side).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function snf_marketplace_rest_get( WP_REST_Request $request ) {
	$payload = snf_get_marketplace_payload(
		array(
			'q'           => (string) $request->get_param( 'q' ),
			'product_cat' => (string) $request->get_param( 'product_cat' ),
		)
	);

	return rest_ensure_response( $payload );
}

/**
 * Register marketplace REST routes.
 */
function snf_marketplace_register_rest_routes() {
	register_rest_route(
		'snf/v1',
		'/marketplace',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'snf_marketplace_rest_get',
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'           => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'product_cat' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'snf_marketplace_register_rest_routes' );

