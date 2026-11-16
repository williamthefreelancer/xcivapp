<?php
/**
 * Expo Marketplace catalog rails (members / non-members).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request is the members Expo page.
 *
 * @return bool
 */
function snf_is_expo_members_page() {
	return is_page( 'expo-marketplace-by-members' )
		|| ( is_page_template( 'page-templates/expo-marketplace.php' ) && is_page( 'expo-marketplace-by-members' ) );
}

/**
 * Whether the current request is either Expo marketplace page.
 *
 * @return bool
 */
function snf_is_expo_marketplace_page() {
	return is_page_template( 'page-templates/expo-marketplace.php' )
		|| is_page( 'expo-marketplace-by-members' )
		|| is_page( 'expo-marketplace-by-non-members' );
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
 * Gathering cards (not networking) for members Expo.
 *
 * @return array<int, array<string, string>>
 */
function snf_get_expo_gathering_cards() {
	$cards = array_values(
		array_filter(
			array(
				snf_get_dashboard_event_card_by_slug( 'the-badlands-bra-party-bus-gathering', 'badlands-bra-event.jpg' ),
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
 * Dashboard listing rows for Expo marketplace.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_expo_dashboard_listings() {
	return array(
		array(
			'id'    => 'expo-non-members',
			'title' => __( 'Expo Marketplace (non-members)', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'expo-marketplace-by-non-members' ),
			'bp'    => 'events',
			'cards' => snf_get_expo_non_member_teasers(),
		),
		array(
			'id'    => 'expo-members',
			'title' => __( 'Expo Marketplace (members)', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'expo-marketplace-by-members' ),
			'bp'    => 'events',
			'cards' => array_slice(
				array_merge(
					snf_get_dashboard_member_event_cards(),
					snf_get_expo_scenic_drives()
				),
				0,
				3
			),
		),
	);
}

/**
 * Insert Expo listings into the community dashboard.
 *
 * @param array $listings Existing listings.
 * @return array
 */
function snf_inject_expo_dashboard_listings( $listings ) {
	if ( ! is_array( $listings ) ) {
		$listings = array();
	}

	$expo = snf_get_expo_dashboard_listings();
	array_splice( $listings, 1, 0, $expo );

	return $listings;
}
add_filter( 'snf_dashboard_event_listings', 'snf_inject_expo_dashboard_listings' );
