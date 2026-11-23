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
 * Register URL for an Expo track (members land on Select Events with context).
 *
 * @param string $track vendor|professional.
 * @param string $label Query label for Apply / Select Events context.
 * @return string
 */
function snf_get_expo_register_track_url( $track, $label ) {
	$track = sanitize_key( $track );
	$label = (string) $label;
	$base  = snf_get_page_url( 'select-events-by-members' );
	if ( ! $base ) {
		$base = snf_get_page_url( 'apply' );
	}

	return add_query_arg(
		array(
			'q'     => $label,
			'track' => $track,
		),
		$base
	);
}

/**
 * Expo marketplace register-event cards (Vendor + Professional tracks).
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_expo_register_event_cards() {
	$img = get_template_directory_uri() . '/assets/images/';

	$cards = array(
		array(
			'id'       => 'register-vendor',
			'title'    => __( 'Register as Vendor', 'super-ninety-four' ),
			'meta'     => __( 'Expo marketplace · booth & listing track', 'super-ninety-four' ),
			'snippet'  => __( 'Claim a vendor booth listing for operators that sell into the Super Ninety Four circuit.', 'super-ninety-four' ),
			'image'    => $img . 'events/township-adventures-event.jpg',
			'url'      => snf_get_expo_register_track_url( 'vendor', __( 'Vendor Expo', 'super-ninety-four' ) ),
			'action'   => __( 'Register', 'super-ninety-four' ),
			'kind'     => 'register',
			'track'    => 'vendors',
			'cta'      => __( 'Register', 'super-ninety-four' ),
			'crumb'    => 'expo › register › vendor',
		),
		array(
			'id'       => 'register-professional',
			'title'    => __( 'Register as Professional', 'super-ninety-four' ),
			'meta'     => __( 'Expo marketplace · platform professional track', 'super-ninety-four' ),
			'snippet'  => __( 'Join the professionals that represent Super Ninety Four services on the private Expo floor.', 'super-ninety-four' ),
			'image'    => $img . 'services/lifestyle-service-image-1.jpg',
			'url'      => snf_get_expo_register_track_url( 'professional', __( 'Professional Expo', 'super-ninety-four' ) ),
			'action'   => __( 'Register', 'super-ninety-four' ),
			'kind'     => 'register',
			'track'    => 'professionals',
			'cta'      => __( 'Register', 'super-ninety-four' ),
			'crumb'    => 'expo › register › professional',
		),
	);

	/**
	 * Filter Expo register-event cards.
	 *
	 * @param array $cards Register cards.
	 */
	return apply_filters( 'snf_expo_register_event_cards', $cards );
}

/**
 * Normalize an org / platform directory entry for Expo cards.
 *
 * @param array<string, mixed> $row Raw entry.
 * @return array<string, mixed>
 */
function snf_normalize_expo_directory_entry( $row ) {
	$title = isset( $row['title'] ) ? (string) $row['title'] : '';
	$initial = isset( $row['initial'] ) ? (string) $row['initial'] : '';
	if ( ! $initial && $title ) {
		$parts = preg_split( '/\s+/', $title );
		$initial = strtoupper( substr( (string) ( $parts[0] ?? '' ), 0, 1 ) . substr( (string) ( $parts[1] ?? '' ), 0, 1 ) );
	}

	$category = isset( $row['category'] ) ? (string) $row['category'] : '';
	$city     = isset( $row['city'] ) ? (string) $row['city'] : '';
	$meta_bits = array_filter( array( $category, $city ) );
	$meta      = isset( $row['meta'] ) ? (string) $row['meta'] : ( $meta_bits ? implode( ' · ', $meta_bits ) : '' );
	$blurb     = isset( $row['blurb'] ) ? (string) $row['blurb'] : ( isset( $row['snippet'] ) ? (string) $row['snippet'] : '' );
	$track     = isset( $row['track'] ) ? sanitize_key( (string) $row['track'] ) : 'vendors';
	$kind      = isset( $row['kind'] ) ? (string) $row['kind'] : ( 'professionals' === $track ? 'professional' : 'vendor' );

	return array(
		'id'       => isset( $row['id'] ) ? (string) $row['id'] : sanitize_title( $title ),
		'title'    => $title,
		'meta'     => $meta,
		'snippet'  => $blurb,
		'blurb'    => $blurb,
		'image'    => isset( $row['image'] ) ? (string) $row['image'] : '',
		'url'      => isset( $row['url'] ) ? (string) $row['url'] : '',
		'action'   => isset( $row['action'] ) ? (string) $row['action'] : __( 'View listing', 'super-ninety-four' ),
		'cta'      => isset( $row['cta'] ) ? (string) $row['cta'] : ( isset( $row['action'] ) ? (string) $row['action'] : __( 'View listing', 'super-ninety-four' ) ),
		'kind'     => $kind,
		'track'    => $track,
		'category' => $category,
		'city'     => $city,
		'initial'  => $initial,
		'crumb'    => isset( $row['crumb'] ) ? (string) $row['crumb'] : ( 'expo › ' . $track ),
	);
}

/**
 * Whether an org category maps to the vendors track.
 *
 * @param string $category Category slug or label.
 * @return bool
 */
function snf_expo_category_is_vendor( $category ) {
	$category = strtolower( trim( (string) $category ) );
	if ( '' === $category ) {
		return true;
	}
	foreach ( array( 'vendor', 'operator', 'booth', 'merchant', 'supplier' ) as $needle ) {
		if ( false !== strpos( $category, $needle ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Whether an org category maps to the professionals track.
 *
 * @param string $category Category slug or label.
 * @return bool
 */
function snf_expo_category_is_professional( $category ) {
	$category = strtolower( trim( (string) $category ) );
	foreach ( array( 'professional', 'service', 'concierge', 'operator-pro' ) as $needle ) {
		if ( false !== strpos( $category, $needle ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Live member org profiles for Expo directory rails.
 *
 * @param string $track vendors|professionals.
 * @return array<int, array<string, mixed>>
 */
function snf_query_expo_org_directory( $track = 'vendors' ) {
	if ( ! function_exists( 'snf_b2b_get_org_profile' ) ) {
		return array();
	}

	$track   = 'professionals' === $track ? 'professionals' : 'vendors';
	$roles   = function_exists( 'snf_get_member_access_roles' ) ? snf_get_member_access_roles() : array( 'snf_member', 'snf_reciprocal', 'snf_staff', 'administrator' );
	$users   = get_users(
		array(
			'role__in'   => $roles,
			'number'     => 48,
			'orderby'    => 'display_name',
			'order'      => 'ASC',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'snf_org_name',
					'value'   => '',
					'compare' => '!=',
				),
			),
			'fields'     => array( 'ID', 'display_name', 'user_url' ),
		)
	);
	$messages = snf_get_page_url( 'messages' );
	$entries  = array();

	foreach ( $users as $user ) {
		$profile  = snf_b2b_get_org_profile( (int) $user->ID );
		$name     = trim( (string) ( $profile['name'] ?? '' ) );
		if ( '' === $name ) {
			continue;
		}
		$category = (string) ( $profile['category'] ?? '' );
		$is_pro   = snf_expo_category_is_professional( $category );
		$is_vendor = snf_expo_category_is_vendor( $category );

		if ( 'professionals' === $track && ! $is_pro ) {
			continue;
		}
		if ( 'vendors' === $track && ( $is_pro || ! $is_vendor ) ) {
			continue;
		}

		$url = $messages ? add_query_arg( 'with', (int) $user->ID, $messages ) : (string) ( $profile['website'] ?? '' );
		if ( ! $url && ! empty( $profile['website'] ) ) {
			$url = (string) $profile['website'];
		}

		$entries[] = snf_normalize_expo_directory_entry(
			array(
				'id'       => 'org-' . (int) $user->ID,
				'title'    => $name,
				'category' => $category ? $category : ( 'professionals' === $track ? __( 'Professional', 'super-ninety-four' ) : __( 'Vendor', 'super-ninety-four' ) ),
				'city'     => (string) ( $profile['city'] ?? '' ),
				'blurb'    => (string) ( $profile['blurb'] ?? '' ),
				'url'      => $url ? $url : '#',
				'action'   => $messages ? __( 'Message', 'super-ninety-four' ) : __( 'View listing', 'super-ninety-four' ),
				'track'    => $track,
				'kind'     => 'professionals' === $track ? 'professional' : 'vendor',
			)
		);
	}

	return $entries;
}

/**
 * Curated platform vendor placeholders (keeps the rail populated).
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_expo_curated_vendor_directory() {
	$img = get_template_directory_uri() . '/assets/images/';
	$register = snf_get_expo_register_track_url( 'vendor', __( 'Vendor Expo', 'super-ninety-four' ) );

	$entries = array(
		array(
			'id'       => 'platform-vendor-circuit',
			'title'    => __( 'Circuit Operators Desk', 'super-ninety-four' ),
			'category' => __( 'Vendor', 'super-ninety-four' ),
			'city'     => __( 'Charlotte · corridor', 'super-ninety-four' ),
			'blurb'    => __( 'Official Super Ninety Four vendor desk for booth allotments, RFQs, and reciprocal operator listings.', 'super-ninety-four' ),
			'image'    => $img . 'events/township-adventures-event.jpg',
			'url'      => $register,
			'action'   => __( 'Register', 'super-ninety-four' ),
			'track'    => 'vendors',
		),
		array(
			'id'       => 'platform-vendor-marketplace',
			'title'    => __( 'Expo Marketplace Supply', 'super-ninety-four' ),
			'category' => __( 'Operator', 'super-ninety-four' ),
			'city'     => __( 'Platform · curated', 'super-ninety-four' ),
			'blurb'    => __( 'Curated suppliers that stock the private Expo floor for member gatherings and scenic arrivals.', 'super-ninety-four' ),
			'image'    => $img . 'events/eight-hundred-journeys-event.jpg',
			'url'      => $register,
			'action'   => __( 'Register', 'super-ninety-four' ),
			'track'    => 'vendors',
		),
	);

	return array_map( 'snf_normalize_expo_directory_entry', $entries );
}

/**
 * Platform professionals that represent Super Ninety Four services.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_expo_platform_professional_directory() {
	$entries = array();
	$slugs   = array(
		'litter-pickup-service',
		'professional-line-sitting-courier-service',
		'janitorial-cleaning-service',
	);

	foreach ( $slugs as $slug ) {
		$quote = function_exists( 'snf_get_service_quote' ) ? snf_get_service_quote( $slug ) : null;
		if ( ! is_array( $quote ) || empty( $quote['title'] ) ) {
			continue;
		}
		$entries[] = snf_normalize_expo_directory_entry(
			array(
				'id'       => 'pro-' . $slug,
				'title'    => (string) $quote['title'],
				'category' => __( 'Platform professional', 'super-ninety-four' ),
				'city'     => __( 'Super Ninety Four', 'super-ninety-four' ),
				'blurb'    => isset( $quote['card_description'] ) ? (string) $quote['card_description'] : (string) ( $quote['lede'] ?? '' ),
				'image'    => isset( $quote['image'] ) ? (string) $quote['image'] : '',
				'url'      => isset( $quote['cta_url'] ) ? (string) $quote['cta_url'] : snf_get_expo_register_track_url( 'professional', (string) $quote['title'] ),
				'action'   => __( 'View listing', 'super-ninety-four' ),
				'track'    => 'professionals',
				'kind'     => 'professional',
			)
		);
	}

	return $entries;
}

/**
 * Vendor directory cards for the private Expo members page.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_expo_vendor_directory() {
	$live     = snf_query_expo_org_directory( 'vendors' );
	$curated  = snf_get_expo_curated_vendor_directory();
	$combined = array_merge( $live, $curated );
	$seen     = array();
	$cards    = array();

	foreach ( $combined as $card ) {
		$key = strtolower( (string) ( $card['title'] ?? '' ) );
		if ( '' === $key || isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$cards[]      = $card;
	}

	/**
	 * Filter Expo vendor directory cards.
	 *
	 * @param array $cards Vendor cards.
	 */
	return apply_filters( 'snf_expo_vendor_directory', $cards );
}

/**
 * Professional directory cards for the private Expo members page.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_expo_professional_directory() {
	$live     = snf_query_expo_org_directory( 'professionals' );
	$platform = snf_get_expo_platform_professional_directory();
	$combined = array_merge( $platform, $live );
	$seen     = array();
	$cards    = array();

	foreach ( $combined as $card ) {
		$key = strtolower( (string) ( $card['title'] ?? '' ) );
		if ( '' === $key || isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$cards[]      = $card;
	}

	/**
	 * Filter Expo professional directory cards.
	 *
	 * @param array $cards Professional cards.
	 */
	return apply_filters( 'snf_expo_professional_directory', $cards );
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
						'meta'  => __( 'Unlock the private Expo vendor & professional directory', 'super-ninety-four' ),
						'image' => get_template_directory_uri() . '/assets/images/events/lcc-workshop.jpg',
						'url'   => snf_get_page_url( 'apply' ),
					),
				),
			),
		);
	} else {
		$rails = array(
			array(
				'id'         => 'register',
				'title'      => __( 'Register for Expo', 'super-ninety-four' ),
				'bp'         => 'events',
				'track'      => 'register',
				'empty_text' => __( 'Registration tracks will appear here.', 'super-ninety-four' ),
				'cards'      => snf_get_expo_register_event_cards(),
			),
			array(
				'id'         => 'vendors',
				'title'      => __( 'Vendors', 'super-ninety-four' ),
				'bp'         => 'members',
				'track'      => 'vendors',
				'empty_text' => __( 'No vendor listings yet. Register to claim a booth.', 'super-ninety-four' ),
				'cards'      => snf_get_expo_vendor_directory(),
			),
			array(
				'id'         => 'professionals',
				'title'      => __( 'Professionals', 'super-ninety-four' ),
				'bp'         => 'members',
				'track'      => 'professionals',
				'empty_text' => __( 'No professional listings yet.', 'super-ninety-four' ),
				'cards'      => snf_get_expo_professional_directory(),
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
function snf_get_marketplace_taxonomy_facets( $audience = '', $surface = '' ) {
	if ( ! $audience ) {
		$audience = snf_get_marketplace_audience();
	}
	if ( ! $surface && function_exists( 'snf_is_expo_marketplace_page' ) && snf_is_expo_marketplace_page() ) {
		$surface = 'expo';
	}

	// Expo members directory uses Vendor / Professional tracks.
	if ( 'members' === $audience && 'expo' === $surface ) {
		$facets = array(
			array(
				'slug'  => 'vendors',
				'label' => __( 'Vendors', 'super-ninety-four' ),
			),
			array(
				'slug'  => 'professionals',
				'label' => __( 'Professionals', 'super-ninety-four' ),
			),
		);

		/**
		 * Filter marketplace taxonomy facets.
		 *
		 * @param array  $facets   Facet list.
		 * @param string $audience Audience key.
		 */
		return apply_filters( 'snf_marketplace_taxonomy_facets', $facets, $audience );
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
 * Directory rows for marketplace search / chip filters on Expo.
 *
 * @param string   $q    Search query.
 * @param string[] $cats Active facet slugs (vendors|professionals).
 * @return array<int, array<string, string>>
 */
function snf_get_marketplace_directory_rows( $q = '', $cats = array() ) {
	$q_lower = strtolower( trim( (string) $q ) );
	$want_vendors = empty( $cats ) || in_array( 'vendors', $cats, true );
	$want_pros    = empty( $cats ) || in_array( 'professionals', $cats, true );
	$cards        = array();

	if ( $want_vendors ) {
		$cards = array_merge( $cards, snf_get_expo_vendor_directory() );
	}
	if ( $want_pros ) {
		$cards = array_merge( $cards, snf_get_expo_professional_directory() );
	}
	// Register cards surface when searching without a track chip, or when query mentions register.
	if ( empty( $cats ) || $q_lower ) {
		foreach ( snf_get_expo_register_event_cards() as $card ) {
			$track = isset( $card['track'] ) ? (string) $card['track'] : '';
			if ( $cats && $track && ! in_array( $track, $cats, true ) ) {
				continue;
			}
			$cards[] = $card;
		}
	}

	$rows = array();
	foreach ( $cards as $card ) {
		$title   = isset( $card['title'] ) ? (string) $card['title'] : '';
		$meta    = isset( $card['meta'] ) ? (string) $card['meta'] : '';
		$snippet = isset( $card['snippet'] ) ? (string) $card['snippet'] : ( isset( $card['blurb'] ) ? (string) $card['blurb'] : '' );
		if ( $q_lower ) {
			$hay = strtolower( $title . ' ' . $meta . ' ' . $snippet );
			if ( false === strpos( $hay, $q_lower ) ) {
				continue;
			}
		}
		$rows[] = snf_normalize_marketplace_card_row(
			array_merge(
				$card,
				array(
					'snippet' => $snippet,
					'action'  => isset( $card['action'] ) ? (string) $card['action'] : __( 'View listing', 'super-ninety-four' ),
				)
			)
		);
	}

	return $rows;
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

	// Expo directory facets are not Woo product_cat terms.
	$cats = array_values(
		array_filter(
			(array) $cats,
			static function ( $slug ) {
				return ! in_array( (string) $slug, array( 'vendors', 'professionals' ), true );
			}
		)
	);

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
	$surface  = isset( $args['surface'] ) ? sanitize_key( (string) $args['surface'] ) : '';
	if ( ! $surface && function_exists( 'snf_is_expo_marketplace_page' ) && snf_is_expo_marketplace_page() ) {
		$surface = 'expo';
	}
	$q        = isset( $args['q'] ) ? sanitize_text_field( (string) $args['q'] ) : '';
	$facets   = snf_get_marketplace_taxonomy_facets( $audience, $surface );
	$allowed  = wp_list_pluck( $facets, 'slug' );
	$raw_cats = isset( $args['product_cat'] ) ? $args['product_cat'] : '';
	$parts    = is_array( $raw_cats ) ? $raw_cats : preg_split( '/[\s,]+/', (string) $raw_cats, -1, PREG_SPLIT_NO_EMPTY );
	$cats     = array();
	foreach ( (array) $parts as $part ) {
		$slug = sanitize_title( (string) $part );
		if ( $slug && in_array( $slug, $allowed, true ) ) {
			$cats[] = $slug;
		}
	}
	$cats     = array_values( array_unique( $cats ) );
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

	$expo_directory = ( 'expo' === $surface && 'members' === $audience );
	// Directory chip filters are client-side; only a text query flips into SERP mode on Expo.
	$searching      = $expo_directory ? ( '' !== $q ) : ( ( '' !== $q ) || ! empty( $cats ) );
	$results        = array();

	if ( $searching ) {
		if ( $expo_directory ) {
			$results = snf_get_marketplace_directory_rows( $q, $cats );
		} else {
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
		'audience'    => $audience,
		'surface'     => $surface ? $surface : 'shop',
		'q'           => $q,
		'product_cat' => $cats,
		'facets'      => $facets,
		'searching'   => $searching,
		'results'     => array_values( $results ),
		'rails'       => $rails,
		'shopUrl'     => $shop_url ? $shop_url : home_url( '/shop/' ),
		'expoUrl'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
		'applyUrl'    => snf_get_page_url( 'apply' ),
		'count'       => count( $results ),
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
	$surface = ( function_exists( 'snf_is_expo_marketplace_page' ) && snf_is_expo_marketplace_page() ) ? 'expo' : 'shop';
	$payload = snf_get_marketplace_payload(
		array(
			'q'           => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'product_cat' => isset( $_GET['product_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['product_cat'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'surface'     => $surface,
		)
	);

	$boot = array(
		'restUrl'   => esc_url_raw( rest_url( 'snf/v1/marketplace' ) ),
		'nonce'     => wp_create_nonce( 'wp_rest' ),
		'shopUrl'   => $payload['shopUrl'],
		'expoUrl'   => $payload['expoUrl'],
		'audience'  => $payload['audience'],
		'surface'   => $payload['surface'],
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

	/**
	 * Filter marketplace JS bootstrap (B2B RFQ fields attach here).
	 *
	 * @param array $boot Bootstrap payload.
	 */
	return apply_filters( 'snf_marketplace_js_bootstrap', $boot );
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
			'surface'     => (string) $request->get_param( 'surface' ),
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
					'surface'     => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'snf_marketplace_register_rest_routes' );

