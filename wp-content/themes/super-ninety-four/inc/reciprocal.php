<?php
/**
 * Reciprocal club landing content (Back Creek / Badlands style).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared application steps from the reciprocal club screenshots.
 *
 * @return array<int, array{title:string,body:string,icon:string}>
 */
function snf_reciprocal_application_steps() {
	return array(
		array(
			'icon'  => 'clipboard',
			'title' => __( 'Start Application', 'super-ninety-four' ),
			'body'  => __( 'Fill out your name, paste a Loom video link, and share your phone number.', 'super-ninety-four' ),
		),
		array(
			'icon'  => 'bulb',
			'title' => __( 'Intelligence Gathering Q&A', 'super-ninety-four' ),
			'body'  => __( 'Answer mailed questions so the desk can understand your situation and fit.', 'super-ninety-four' ),
		),
		array(
			'icon'  => 'walk',
			'title' => __( 'The Waiting List', 'super-ninety-four' ),
			'body'  => __( 'Join the list for club updates while your application is reviewed.', 'super-ninety-four' ),
		),
		array(
			'icon'  => 'cart',
			'title' => __( 'Discreet Details', 'super-ninety-four' ),
			'body'  => __( 'Receive event listings and membership checkout details once approved.', 'super-ninety-four' ),
		),
		array(
			'icon'  => 'lock',
			'title' => __( 'Manual Approval', 'super-ninety-four' ),
			'body'  => __( 'Accounts are manually approved before dashboard access is created.', 'super-ninety-four' ),
		),
	);
}

/**
 * Shared FAQ drawn from the Back Creek reciprocal landing.
 *
 * @return array<int, array{q:string,a:string}>
 */
function snf_reciprocal_faq() {
	return array(
		array(
			'q' => __( 'What services are included in your private invite-only events community?', 'super-ninety-four' ),
			'a' => __( 'Members receive invite-only event access, lead-rider concierge support, digital membership credentials, reciprocal privileges across the circuit, and discreet stay/service-stop coordination.', 'super-ninety-four' ),
		),
		array(
			'q' => __( 'How can I become a member?', 'super-ninety-four' ),
			'a' => __( 'Start an application, complete the intelligence Q&A, join the waiting list, and wait for manual approval before checkout details are released.', 'super-ninety-four' ),
		),
		array(
			'q' => __( 'How much does it cost to join?', 'super-ninety-four' ),
			'a' => __( 'All plans include a $75,000.00 initiation fee. Monthly dues depend on your selected plan (Back Creek, Badlands, Regions NE, or City to City) for a 12-month term.', 'super-ninety-four' ),
		),
		array(
			'q' => __( 'What happens if I have problems during my private membership service?', 'super-ninety-four' ),
			'a' => __( 'Contact your Lead Rider and Member Concierge through Messages or the Concierge desk. Support continues by Google Meet and walkie-talkie while you are on the road.', 'super-ninety-four' ),
		),
		array(
			'q' => __( 'What sets SUPER NINETY FOUR apart?', 'super-ninety-four' ),
			'a' => __( 'A private circuit of reciprocal clubs with discreet hospitality standards, immersive road events, and member-only access instead of public ticket marketplaces.', 'super-ninety-four' ),
		),
		array(
			'q' => __( 'Which regions does your private invite-only events community cover?', 'super-ninety-four' ),
			'a' => __( 'Selected locations and routes between Charlotte and outside of Boston, plus reciprocal privileges across partner clubs in the network.', 'super-ninety-four' ),
		),
		array(
			'q' => __( 'How do I contact my Lead Rider & Member Concierge?', 'super-ninety-four' ),
			'a' => __( 'Use the Messages inbox inside the SUPER NINETY FOUR app after your account is approved.', 'super-ninety-four' ),
		),
		array(
			'q' => __( 'Does my membership have a minimum duration?', 'super-ninety-four' ),
			'a' => __( 'Yes. Plans run for 12 months, and you will get a reminder before renewal.', 'super-ninety-four' ),
		),
	);
}

/**
 * Feature icons shared by reciprocal landings.
 *
 * @return array<int, array{title:string,body:string,icon:string}>
 */
function snf_reciprocal_default_features() {
	return array(
		array(
			'icon'  => 'pin',
			'title' => __( 'Selected Locations', 'super-ninety-four' ),
			'body'  => __( 'Undisclosed gathering points and routes between Charlotte and outside of Boston.', 'super-ninety-four' ),
		),
		array(
			'icon'  => 'crown',
			'title' => __( 'Member Concierge Support', 'super-ninety-four' ),
			'body'  => __( 'Strict discretion on and off the road, with a desk that stays with you.', 'super-ninety-four' ),
		),
		array(
			'icon'  => 'shield',
			'title' => __( 'Adventurous Atmosphere', 'super-ninety-four' ),
			'body'  => __( 'A private venue to express what you came for — with hospitality standards intact.', 'super-ninety-four' ),
		),
	);
}

/**
 * Default outcomes grid.
 *
 * @return array<int, array{title:string,body:string,icon:string}>
 */
function snf_reciprocal_default_outcomes() {
	return array(
		array(
			'icon'  => 'ticket',
			'title' => __( 'Member Only Event Experiences', 'super-ninety-four' ),
			'body'  => __( 'Access exclusive immersions city by city across the reciprocal circuit.', 'super-ninety-four' ),
		),
		array(
			'icon'  => 'car',
			'title' => __( 'Lead Rider', 'super-ninety-four' ),
			'body'  => __( 'Ongoing concierge via Google Meet and walkie-talkie while you travel.', 'super-ninety-four' ),
		),
		array(
			'icon'  => 'card',
			'title' => __( 'Digital Membership Card', 'super-ninety-four' ),
			'body'  => __( 'Carry credentials in the SUPER NINETY FOUR app for iPhone or iPad.', 'super-ninety-four' ),
		),
		array(
			'icon'  => 'home',
			'title' => __( 'Reciprocal Privileges', 'super-ninety-four' ),
			'body'  => __( 'Visit partner clubs with letters of introduction and guest credentials.', 'super-ninety-four' ),
		),
		array(
			'icon'  => 'bed',
			'title' => __( 'Stays & Service Stops', 'super-ninety-four' ),
			'body'  => __( 'Request regional B&B spots and service stops on direct routes.', 'super-ninety-four' ),
		),
		array(
			'icon'  => 'wrench',
			'title' => __( 'Discreet & Privacy', 'super-ninety-four' ),
			'body'  => __( 'Operate with the expectation that nobody needs to know your business.', 'super-ninety-four' ),
		),
	);
}

/**
 * All reciprocal club landings keyed by page slug.
 *
 * @return array<string, array<string, mixed>>
 */
function snf_get_reciprocal_clubs() {
	$img = get_template_directory_uri() . '/assets/images/reciprocal/';
	$cta = snf_get_page_url( 'apply' );

	$clubs = array(
		'the-clubs-at-back-creek'            => array(
			'slug'         => 'the-clubs-at-back-creek',
			'title'        => __( 'The Clubs at Back Creek', 'super-ninety-four' ),
			'mark'         => 'SN',
			'mark_label'   => __( 'SUPER NINETY FOUR', 'super-ninety-four' ),
			'hero'         => $img . 'the-clubs-at-back-creek.jpg',
			'hero_alt'     => __( 'On the road toward a Back Creek members experience', 'super-ninety-four' ),
			'benefits_bg'  => $img . 'back-creek-benefits.jpg',
			'card_image'   => $img . 'the-clubs-at-back-creek.jpg',
			'hero_heading' => __( 'To Attend This Members Only Experience, You have to select a immerse event?', 'super-ninety-four' ),
			'cta_label'    => __( 'Schedule Your Experience', 'super-ninety-four' ),
			'cta_url'      => $cta,
			'lede'         => __( 'The Clubs at Back Creek is a private community for regional backway travel and a discreet atmosphere — invite-only events on wheels between Charlotte and outside of Boston.', 'super-ninety-four' ),
			'features'     => snf_reciprocal_default_features(),
			'outcomes'     => snf_reciprocal_default_outcomes(),
		),
		'the-badlands-bra-club'              => array(
			'slug'         => 'the-badlands-bra-club',
			'title'        => __( 'BADLANDS BRA', 'super-ninety-four' ),
			'mark'         => 'BB',
			'mark_label'   => __( 'BADLANDS BRA', 'super-ninety-four' ),
			'hero'         => $img . 'the-badlands-bra-club.jpg',
			'hero_alt'     => __( 'Badlands Bra members-only experience', 'super-ninety-four' ),
			'benefits_bg'  => $img . 'badlands-benefits.jpg',
			'card_image'   => $img . 'the-badlands-bra-club.jpg',
			'hero_heading' => __( 'To Attend This Members Only Experience, You have to select a immerse event?', 'super-ninety-four' ),
			'cta_label'    => __( 'Schedule Your Experience', 'super-ninety-four' ),
			'cta_url'      => $cta,
			'lede'         => __( 'BADLANDS BRA is a private members-only events club for provocative social gatherings. With road travel and adventurous vibes, it creates an atmosphere for those seeking both adventure and discretion.', 'super-ninety-four' ),
			'features'     => snf_reciprocal_default_features(),
			'outcomes'     => array(
				array(
					'icon'  => 'ticket',
					'title' => __( 'Member Only Event Experiences', 'super-ninety-four' ),
					'body'  => __( 'Access exclusive events city by city across the Badlands circuit.', 'super-ninety-four' ),
				),
				array(
					'icon'  => 'car',
					'title' => __( 'Lead Rider', 'super-ninety-four' ),
					'body'  => __( 'Ongoing concierge service via Google Meet and walkie-talkie.', 'super-ninety-four' ),
				),
				array(
					'icon'  => 'card',
					'title' => __( 'Digital Membership Card', 'super-ninety-four' ),
					'body'  => __( 'An app credential for iPhone or iPad while you travel.', 'super-ninety-four' ),
				),
				array(
					'icon'  => 'globe',
					'title' => __( 'Private Exploration', 'super-ninety-four' ),
					'body'  => __( 'A recreational environment for expressing desires with written consent.', 'super-ninety-four' ),
				),
				array(
					'icon'  => 'bed',
					'title' => __( 'Stays & Service Stops', 'super-ninety-four' ),
					'body'  => __( 'Request service stops and regional B&B spots on direct routes.', 'super-ninety-four' ),
				),
				array(
					'icon'  => 'wrench',
					'title' => __( 'Discreet & Privacy', 'super-ninety-four' ),
					'body'  => __( 'Emphasizing that nobody needs to know your business.', 'super-ninety-four' ),
				),
			),
		),
		'the-clubs-at-wyoma-square'          => array(
			'slug'         => 'the-clubs-at-wyoma-square',
			'title'        => __( 'The Clubs at Wyoma Square', 'super-ninety-four' ),
			'mark'         => 'WS',
			'mark_label'   => __( 'WYOMA SQUARE', 'super-ninety-four' ),
			'hero'         => $img . 'the-clubs-at-wyoma-square.jpg',
			'hero_alt'     => __( 'The Clubs at Wyoma Square', 'super-ninety-four' ),
			'benefits_bg'  => $img . 'back-creek-benefits.jpg',
			'card_image'   => $img . 'the-clubs-at-wyoma-square.jpg',
			'hero_heading' => __( 'To Attend This Members Only Experience, You have to select a immerse event?', 'super-ninety-four' ),
			'cta_label'    => __( 'Schedule Your Experience', 'super-ninety-four' ),
			'cta_url'      => $cta,
			'lede'         => __( 'The Clubs at Wyoma Square is a reciprocal square for private gatherings, plaza hospitality, and discreet member evenings linked into the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
			'features'     => snf_reciprocal_default_features(),
			'outcomes'     => snf_reciprocal_default_outcomes(),
		),
		'the-city-clubs-at-super-ninety-four'=> array(
			'slug'         => 'the-city-clubs-at-super-ninety-four',
			'title'        => __( 'The City Clubs of SUPER NINETY FOUR', 'super-ninety-four' ),
			'mark'         => 'CC',
			'mark_label'   => __( 'THE CITY CLUBS', 'super-ninety-four' ),
			'hero'         => $img . 'the-city-clubs-at-super-ninety-four.jpg',
			'hero_alt'     => __( 'The City Clubs of SUPER NINETY FOUR coach', 'super-ninety-four' ),
			'benefits_bg'  => $img . 'back-creek-benefits.jpg',
			'card_image'   => $img . 'the-city-clubs-at-super-ninety-four.jpg',
			'hero_heading' => __( 'To Attend This Members Only Experience, You have to select a immerse event?', 'super-ninety-four' ),
			'cta_label'    => __( 'Schedule Your Experience', 'super-ninety-four' ),
			'cta_url'      => $cta,
			'lede'         => __( 'The City Clubs of SUPER NINETY FOUR connect city-to-city immersions, coach travel, and reciprocal hospitality for principals across the private events network.', 'super-ninety-four' ),
			'features'     => snf_reciprocal_default_features(),
			'outcomes'     => snf_reciprocal_default_outcomes(),
		),
	);

	/**
	 * Filter reciprocal club landing definitions.
	 *
	 * @param array<string, array<string, mixed>> $clubs Clubs by slug.
	 */
	return apply_filters( 'snf_reciprocal_clubs', $clubs );
}

/**
 * Resolve a reciprocal club by slug.
 *
 * @param string $slug Page slug.
 * @return array<string, mixed>|null
 */
function snf_get_reciprocal_club( $slug = '' ) {
	$slug  = $slug ? $slug : get_post_field( 'post_name', get_queried_object_id() );
	$clubs = snf_get_reciprocal_clubs();
	return isset( $clubs[ $slug ] ) ? $clubs[ $slug ] : null;
}

/**
 * Hub listing for reciprocal index pages.
 *
 * @return array<string, mixed>
 */
function snf_get_reciprocal_hub_data() {
	$clubs = array_values( snf_get_reciprocal_clubs() );
	return array(
		'title' => __( 'Reciprocal Programs', 'super-ninety-four' ),
		'lede'  => __( 'Explore partner clubs across the SUPER NINETY FOUR circuit — Back Creek, Badlands, Wyoma Square, and The City Clubs.', 'super-ninety-four' ),
		'clubs' => $clubs,
	);
}

/**
 * Simple SVG icon for reciprocal cards.
 *
 * @param string $name Icon key.
 * @return string
 */
function snf_reciprocal_icon_svg( $name ) {
	$icons = array(
		'pin'      => '<path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/>',
		'crown'    => '<path d="M3 18h18v2H3zm1.5-3 2.7-7.2L12 11l4.8-3.2L19.5 15z"/>',
		'shield'   => '<path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5z"/>',
		'clipboard'=> '<path d="M9 2h6a2 2 0 0 1 2 2h1a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1a2 2 0 0 1 2-2zm0 2v2h6V4zm2 8h6v2h-6zm0 4h6v2h-6zM7 12h2v2H7zm0 4h2v2H7z"/>',
		'bulb'     => '<path d="M9 21h6v1H9zm3-19a7 7 0 0 0-4 12.7V18h8v-3.3A7 7 0 0 0 12 2z"/>',
		'walk'     => '<path d="M13.5 5.5a2 2 0 1 0-2 0V8l-3 2 1 7h2l1-4 2 2v5h2v-6l-2.5-2.5L15 8z"/>',
		'cart'     => '<path d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM3 4h2l3.6 9.6L7.2 16H19v-2H8.4l.8-1.5H19l2-7H6.2L5.4 2H3z"/>',
		'lock'     => '<path d="M17 8h-1V6a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2zm-7-2a2 2 0 1 1 4 0v2h-4z"/>',
		'ticket'   => '<path d="M22 10V6H2v4a2 2 0 0 1 0 4v4h20v-4a2 2 0 0 1 0-4zM4 8h16v1.5a3.5 3.5 0 0 0 0 5V18H4v-3.5a3.5 3.5 0 0 0 0-5z"/>',
		'car'      => '<path d="M5 11 6.5 6.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11h1a1 1 0 0 1 1 1v5h-2a2 2 0 1 1-4 0H9a2 2 0 1 1-4 0H3v-5a1 1 0 0 1 1-1zm2.2-3.5L6.3 11h11.4l-.9-3.5z"/>',
		'card'     => '<path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm2 0v3h16V6zm0 5v7h16v-7z"/>',
		'home'     => '<path d="M12 3 3 10v11h6v-6h6v6h6V10z"/>',
		'bed'      => '<path d="M3 7h2v6h14V9a2 2 0 0 0-2-2H9V7H3zm0 10h18v2H3z"/>',
		'wrench'   => '<path d="M22.7 19.4 15 11.7A6 6 0 0 0 7.2 3.1L11 7 7 11 3.1 7.2A6 6 0 0 0 11.7 15l7.7 7.7a1 1 0 0 0 1.4 0l1.9-1.9a1 1 0 0 0 0-1.4z"/>',
		'globe'    => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 2c1.7 0 3.3 2.7 3.8 6H8.2C8.7 6.7 10.3 4 12 4zm-4.9 8H16.9c-.2 1.4-.7 2.8-1.4 4H8.5c-.7-1.2-1.2-2.6-1.4-4zM12 20c-1.7 0-3.3-2.7-3.8-6h7.6C15.3 17.3 13.7 20 12 20z"/>',
	);

	$path = isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['pin'];
	return '<svg class="snf-reciprocal__icon" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false">' . $path . '</svg>';
}
