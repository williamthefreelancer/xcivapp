<?php
/**
 * Front page section content — marketing one-pager.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Home page SVG icon (reuses reciprocal icon paths).
 *
 * @param string $name Icon key.
 * @return string
 */
function snf_home_icon_svg( $name ) {
	$svg = snf_reciprocal_icon_svg( $name );
	return str_replace( 'snf-reciprocal__icon', 'snf-home-icon', $svg );
}

/**
 * Service help cards for the one-pager (image overlay + CTA).
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_home_help_cards() {
	$img_base = get_template_directory_uri() . '/assets/images/services/';
	$cards    = array(
		array(
			'title' => __( 'Litter Pickup Service', 'super-ninety-four' ),
			'meta'  => __( 'Parking lots · Commercial grounds', 'super-ninety-four' ),
			'body'  => __( 'Lots look like you mean business — overnight, not eventually.', 'super-ninety-four' ),
			'url'   => '/product/litter-pickup-service',
			'image' => $img_base . 'litter-pickup-service-image-2.jpg',
			'cta'   => __( 'Open the desk', 'super-ninety-four' ),
		),
		array(
			'title' => __( 'Line Sitting & Courier', 'super-ninety-four' ),
			'meta'  => __( 'Busy lines · Shopping runs · Handoffs', 'super-ninety-four' ),
			'body'  => __( 'Hold the line. Move the goods. Stay off the guestlist.', 'super-ninety-four' ),
			'url'   => '/product/professional-line-sitting-courier-service',
			'image' => $img_base . 'lifestyle-service-image-1.jpg',
			'cta'   => __( 'Open the desk', 'super-ninety-four' ),
		),
		array(
			'title' => __( 'Janitorial Cleaning', 'super-ninety-four' ),
			'meta'  => __( 'Suites · Bars · Overnight reset', 'super-ninety-four' ),
			'body'  => __( 'Suites and bars reset before the next room walks in.', 'super-ninety-four' ),
			'url'   => '/product/janitorial-cleaning-service',
			'image' => $img_base . 'commercial-janitor-cleaning.jpg',
			'cta'   => __( 'Open the desk', 'super-ninety-four' ),
		),
	);

	/**
	 * Filter one-pager help cards.
	 *
	 * @param array<int, array<string, mixed>> $cards Help cards.
	 */
	return apply_filters( 'snf_home_help_cards', $cards );
}

/**
 * Reciprocal program cards for the one-pager (excludes BADLANDS BRA).
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_home_program_cards() {
	$clubs = function_exists( 'snf_get_public_reciprocal_clubs' )
		? snf_get_public_reciprocal_clubs()
		: array();

	$short = array(
		'the-clubs-at-back-creek'             => __( 'Charlotte ↔ outside Boston. Credentials that travel the backway.', 'super-ninety-four' ),
		'the-clubs-at-wyoma-square'           => __( 'Plaza hospitality without the plaza crowd.', 'super-ninety-four' ),
		'the-city-clubs-at-super-ninety-four' => __( 'City to city. Coach, not chaos.', 'super-ninety-four' ),
	);

	$cards = array();
	foreach ( $clubs as $slug => $club ) {
		$cards[] = array(
			'slug'  => $slug,
			'title' => isset( $club['title'] ) ? $club['title'] : '',
			'body'  => isset( $short[ $slug ] ) ? $short[ $slug ] : ( isset( $club['hero_lede'] ) ? $club['hero_lede'] : '' ),
			'scope' => isset( $club['scope'] ) ? $club['scope'] : '',
			'image' => isset( $club['card_image'] ) ? $club['card_image'] : '',
			'url'   => '/reciprocal/' . $slug,
			'cta'   => __( 'Request consideration', 'super-ninety-four' ),
		);
	}

	/**
	 * Filter one-pager reciprocal program cards.
	 *
	 * @param array<int, array<string, mixed>> $cards Program cards.
	 */
	return apply_filters( 'snf_home_program_cards', $cards );
}

/**
 * Upcoming gathering event cards for the one-pager.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_home_gathering_cards() {
	$img_base = get_template_directory_uri() . '/assets/images/events/';
	$city_img = get_template_directory_uri() . '/assets/images/reciprocal/the-city-clubs-at-super-ninety-four.jpg';

	$cards = array(
		array(
			'slug'  => 'the-eight-hundred-plus-journeys-event',
			'title' => __( 'The Eight Hundred Plus Interstate Event', 'super-ninety-four' ),
			'body'  => __( 'Scenic city-to-city immersion along the SUPER NINETY FOUR journeys circuit.', 'super-ninety-four' ),
			'price' => '$497.00',
			'image' => $img_base . 'eight-hundred-journeys-event.jpg',
			'url'   => '/events/the-eight-hundred-plus-journeys-event',
			'cta'   => __( 'View event', 'super-ninety-four' ),
		),
		array(
			'slug'  => 'the-township-adventures-event',
			'title' => __( 'The Township Adventures Event', 'super-ninety-four' ),
			'body'  => __( 'Cultural township immersion on the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
			'price' => '$97.00',
			'image' => $img_base . 'township-adventures-event.jpg',
			'url'   => '/events/the-township-adventures-event',
			'cta'   => __( 'View event', 'super-ninety-four' ),
		),
		array(
			'slug'  => 'the-city-club-private-party',
			'title' => __( 'The City Club Private Party', 'super-ninety-four' ),
			'body'  => __( 'Private City Clubs gathering — coach, credentials, and a closed room.', 'super-ninety-four' ),
			'price' => '$397.00',
			'image' => $city_img,
			'url'   => '/events/the-city-club-private-party',
			'cta'   => __( 'View event', 'super-ninety-four' ),
		),
	);

	/**
	 * Filter one-pager upcoming gathering cards.
	 *
	 * @param array<int, array<string, mixed>> $cards Gathering cards.
	 */
	return apply_filters( 'snf_home_gathering_cards', $cards );
}

/**
 * Front page content payload.
 *
 * @return array<string, mixed>
 */
function snf_get_home_content() {
	$content = array(
		/* One-pager: How can SUPER NINETY FOUR help you */
		'help'       => array(
			'title' => __( 'How can SUPER NINETY FOUR help you', 'super-ninety-four' ),
			'lede'  => __( 'You don’t need membership to open the desk. You need the right stop.', 'super-ninety-four' ),
			'cards' => snf_get_home_help_cards(),
		),
		/* One-pager: Upcoming gatherings (3 events) */
		'gatherings' => array(
			'title' => __( 'Upcoming gatherings', 'super-ninety-four' ),
			'lede'  => __( 'Three seats on the circuit. Prices as posted.', 'super-ninety-four' ),
			'cards' => snf_get_home_gathering_cards(),
		),
		/* Who is / is not for */
		'audience'   => array(
			'for'     => array(
				'title' => __( 'Who this is for', 'super-ninety-four' ),
				'cards' => array(
					array(
						'title' => __( 'Operators', 'super-ninety-four' ),
						'body'  => __( 'Already run rooms, routes, or estates.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Firms', 'super-ninety-four' ),
						'body'  => __( 'Want reciprocal reach — not another listing board.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Principals', 'super-ninety-four' ),
						'body'  => __( 'Pay for discretion, not applause.', 'super-ninety-four' ),
					),
				),
			),
			'not_for' => array(
				'title' => __( 'Who this is not for', 'super-ninety-four' ),
				'cards' => array(
					array(
						'title' => __( 'Browsers', 'super-ninety-four' ),
						'body'  => __( 'This desk doesn’t educate tourists.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Almost affluent', 'super-ninety-four' ),
						'body'  => __( 'Probable isn’t enough.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Loud rooms', 'super-ninety-four' ),
						'body'  => __( 'If your guestlist leaks, you don’t belong here.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Badge hunters', 'super-ninety-four' ),
						'body'  => __( 'Status without spend stays outside.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Spectacle seekers', 'super-ninety-four' ),
						'body'  => __( 'We sell alignment, not a scene.', 'super-ninety-four' ),
					),
				),
			),
		),
		'membership' => array(
			'title'      => __( 'Membership', 'super-ninety-four' ),
			'lede'       => __( 'Initiation is the filter. Dues keep the desk awake.', 'super-ninety-four' ),
			'initiation' => array(
				'label'  => __( 'Initiation fee', 'super-ninety-four' ),
				'amount' => '$75,000.00',
				'meta'   => __( 'Required. No exceptions.', 'super-ninety-four' ),
			),
			'plans'      => array(
				array(
					'name'     => __( 'Back Creek Plan', 'super-ninety-four' ),
					'price'    => '$1,000.00',
					'cadence'  => __( '/mo', 'super-ninety-four' ),
					'term'     => __( 'for 12 months', 'super-ninety-four' ),
					'benefits' => array(
						__( 'North Carolina only — guest credentials on the Back Creek backway.', 'super-ninety-four' ),
					),
				),
				array(
					'name'     => __( 'Regions NE Plan', 'super-ninety-four' ),
					'price'    => '$2,899.00',
					'cadence'  => __( '/mo', 'super-ninety-four' ),
					'term'     => __( 'for 12 months', 'super-ninety-four' ),
					'benefits' => array(
						__( 'MA North Shore and New Hampshire only — Wyoma Square hospitality.', 'super-ninety-four' ),
					),
				),
				array(
					'name'     => __( 'City to City', 'super-ninety-four' ),
					'price'    => '$3,649.00',
					'cadence'  => __( '/mo', 'super-ninety-four' ),
					'term'     => __( 'for 12 months', 'super-ninety-four' ),
					'benefits' => array(
						__( 'Harrisburg / Back Creek → North Shore interstate — coach, credentials, desk.', 'super-ninety-four' ),
					),
				),
			),
			'cta'        => array(
				'label' => __( 'Apply for consideration', 'super-ninety-four' ),
				'url'   => '/apply',
			),
		),
	);

	/**
	 * Filter front page content sections.
	 *
	 * @param array<string, mixed> $content Home content.
	 */
	return apply_filters( 'snf_home_content', $content );
}
