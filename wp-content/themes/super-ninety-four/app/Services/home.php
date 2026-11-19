<?php
/**
 * Front page section content (Figma XCIV Social home).
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
 * Front page content payload.
 *
 * @return array<string, mixed>
 */
function snf_get_home_content() {
	$img_recip = get_template_directory_uri() . '/assets/images/reciprocal/';

	$content = array(
		'icons'     => array(
			array(
				'icon'  => 'ticket',
				'title' => __( 'Search Events', 'super-ninety-four' ),
				'body'  => __( 'Browse the events archive.', 'super-ninety-four' ),
				'url'   => ( get_post_type_archive_link( 'event' ) ? get_post_type_archive_link( 'event' ) : home_url( '/events/' ) ),
			),
			array(
				'icon'  => 'home',
				'title' => __( 'Reciprocal Programs', 'super-ninety-four' ),
				'body'  => __( 'Find other reciprocal programs.', 'super-ninety-four' ),
				'url'   => snf_get_page_url( 'find-other-reciprocal-programs' ),
			),
			array(
				'icon'  => 'clipboard',
				'title' => __( 'Apply', 'super-ninety-four' ),
				'body'  => __( 'Start private consideration.', 'super-ninety-four' ),
				'url'   => snf_get_page_url( 'apply' ),
			),
		),
		/* 2100:132 — Why Choose */
		'spotlight' => array(
			'title' => __( 'Why Choose SUPER NINETY FOUR', 'super-ninety-four' ),
			'body'  => __( 'A private hospitality circuit for select events, reciprocal clubs, and Lifestyle Concierge — built for discretion, alignment, and operators who already run at a high standard.', 'super-ninety-four' ),
		),
		/* 2100:133 — Standard */
		'standard'  => array(
			'title'    => __( "SUPER NINETY FOUR's Standard", 'super-ninety-four' ),
			'body'     => __( 'Admission and partnership follow a strict hospitality bar. Guests may browse; operators and members who meet the standard join a private desk for events, clubs, and concierge.', 'super-ninety-four' ),
			'media'    => array(
				'src' => $img_recip . 'the-city-clubs-at-super-ninety-four.jpg',
				'alt' => __( 'The City Clubs of SUPER NINETY FOUR', 'super-ninety-four' ),
			),
			'features' => array(
				array(
					'icon'  => 'shield',
					'title' => __( 'Excellence of Alignment', 'super-ninety-four' ),
					'body'  => __( 'Partners and members are screened for fit with the circuit — reputation, discretion, and how they host.', 'super-ninety-four' ),
				),
				array(
					'icon'  => 'crown',
					'title' => __( 'Highly Probable but Affluent', 'super-ninety-four' ),
					'body'  => __( 'Built for principals and operators who can sustain private membership, road immersions, and elevated hospitality.', 'super-ninety-four' ),
				),
				array(
					'icon'  => 'globe',
					'title' => __( 'Community of highly intent', 'super-ninety-four' ),
					'body'  => __( 'A network for people who already know what they came for — not browsers looking for a public ticket marketplace.', 'super-ninety-four' ),
				),
			),
		),
		/* 2118:179 — Who is / is not for */
		'audience'  => array(
			'for'     => array(
				'title' => __( 'Who is this for', 'super-ninety-four' ),
				'cards' => array(
					array(
						'title' => __( 'Event & Service Marketplace Owners', 'super-ninety-four' ),
						'body'  => __( 'Expo, vendor, and B2B marketplace operators who need a private circuit — not another public listing board.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Club & Event Management Companies', 'super-ninety-four' ),
						'body'  => __( 'Teams running invite-only clubs and immersions who want reciprocal reach and a discreet desk.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Hospitality Management Companies', 'super-ninety-four' ),
						'body'  => __( 'Operators who already hold a high hospitality bar and want aligned partners on the road.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Property Managers', 'super-ninety-four' ),
						'body'  => __( 'Property management companies coordinating stays, service stops, and private venues on the circuit.', 'super-ninety-four' ),
					),
				),
			),
			'not_for' => array(
				'title' => __( 'Who is this not for', 'super-ninety-four' ),
				'cards' => array(
					array(
						'title' => __( "Doesn't meet standards", 'super-ninety-four' ),
						'body'  => __( 'Operators or guests who cannot clear the hospitality, discretion, and alignment bar.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Not Highly Probable', 'super-ninety-four' ),
						'body'  => __( 'Prospects without the means or readiness for private membership and road immersions.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Uninterested or Not Motivated', 'super-ninety-four' ),
						'body'  => __( 'Anyone browsing casually without intent to host, partner, or join the private desk.', 'super-ninety-four' ),
					),
					array(
						'title' => __( 'Violate Any Highest of Standard', 'super-ninety-four' ),
						'body'  => __( 'Conduct that breaks discretion, consent, or the circuit’s hospitality standards.', 'super-ninety-four' ),
					),
				),
			),
		),
		/* Paths after audience fit */
		'paths'     => array(
			'title' => __( 'Choose how you enter', 'super-ninety-four' ),
			'lede'  => __( 'Membership is optional. Guests can attend select events; property managers and clients can open the inquiry desk for the service they need.', 'super-ninety-four' ),
			'cta'   => array(
				'title' => __( 'What It Is, How It Benefits Sovereign Firms, and How to Claim 100% Ownership of Your Data & Results', 'super-ninety-four' ),
				'body'  => __( 'Join the live workshop on Tuesday, January 5th at 11:00 AM EST. See how a Lifestyle Concierge Club benefits sovereign firms and how to claim 100% ownership of your data and results.', 'super-ninety-four' ),
				'label' => __( 'Attend the workshop', 'super-ninety-four' ),
				'url'   => 'https://www.superninetyfour.com/product/the-lifestyle-concierge-club-workshop/',
			),
			'guest'   => array(
				'title' => __( 'Attend without membership', 'super-ninety-four' ),
				'body'  => __( 'You do not have to become a member. Enjoy attending select events that offer open admission — tickets and fees still apply.', 'super-ninety-four' ),
				'label' => __( 'Search select events', 'super-ninety-four' ),
				'url'   => '#snf-search-events',
			),
			'inquiry' => array(
				'title' => __( 'Property managers & clients', 'super-ninety-four' ),
				'body'  => __( 'Skip membership when you need a service desk. Open the inquiry that matches what you are looking for — stays, cleaning, courier, or reciprocal programs.', 'super-ninety-four' ),
				'label' => __( 'Go to inquiries', 'super-ninety-four' ),
				'url'   => snf_get_page_url( 'inquiries' ),
				'cards' => array(
					array(
						'title' => __( 'Service inquiries', 'super-ninety-four' ),
						'body'  => __( 'Litter pickup, line sitting & courier, and janitorial cleaning for properties on the circuit.', 'super-ninety-four' ),
						'url'   => snf_get_page_url( 'inquiries' ),
					),
					array(
						'title' => __( 'Reciprocal club inquiry', 'super-ninety-four' ),
						'body'  => __( 'Back Creek, Badlands, Wyoma Square, and City Clubs — request a consideration window.', 'super-ninety-four' ),
						'url'   => snf_get_page_url( 'find-other-reciprocal-programs' ),
					),
				),
			),
		),
		'membership' => array(
			'title'      => __( 'What you get with membership', 'super-ninety-four' ),
			'lede'       => __( 'Each plan unlocks a private desk, reciprocal reach, and member-only select events. Initiation is required for every membership plan.', 'super-ninety-four' ),
			'initiation' => array(
				'label'  => __( 'Initiation fee', 'super-ninety-four' ),
				'amount' => '$75,000.00',
				'meta'   => __( 'Required for all membership plans', 'super-ninety-four' ),
			),
			'plans'      => array(
				array(
					'name'     => __( 'Back Creek Plan', 'super-ninety-four' ),
					'price'    => '$1,000.00',
					'cadence'  => __( '/mo', 'super-ninety-four' ),
					'term'     => __( 'for 12 months', 'super-ninety-four' ),
					'benefits' => array(
						__( 'Regional backway immersions between Charlotte and outside of Boston', 'super-ninety-four' ),
						__( 'Guest credentials at The Clubs at Back Creek', 'super-ninety-four' ),
						__( 'Member select events and Lifestyle Concierge desk access', 'super-ninety-four' ),
					),
				),
				array(
					'name'     => __( 'Badlands Plan', 'super-ninety-four' ),
					'price'    => '$2,129.00',
					'cadence'  => __( '/mo', 'super-ninety-four' ),
					'term'     => __( 'for 12 months', 'super-ninety-four' ),
					'benefits' => array(
						__( 'Private gatherings with road travel and adventurous vibes', 'super-ninety-four' ),
						__( 'BADLANDS BRA reciprocal seat and guest windows', 'super-ninety-four' ),
						__( 'Live lead rider & walkie on eligible member drives', 'super-ninety-four' ),
					),
				),
				array(
					'name'     => __( 'Regions NE Plan', 'super-ninety-four' ),
					'price'    => '$2,899.00',
					'cadence'  => __( '/mo', 'super-ninety-four' ),
					'term'     => __( 'for 12 months', 'super-ninety-four' ),
					'benefits' => array(
						__( 'Northeast reciprocal square hospitality and member evenings', 'super-ninety-four' ),
						__( 'Priority consideration for Wyoma Square immerse windows', 'super-ninety-four' ),
						__( 'Expanded select-event calendar across the region', 'super-ninety-four' ),
					),
				),
				array(
					'name'     => __( 'City to City', 'super-ninety-four' ),
					'price'    => '$3,649.00',
					'cadence'  => __( '/mo', 'super-ninety-four' ),
					'term'     => __( 'for 12 months', 'super-ninety-four' ),
					'benefits' => array(
						__( 'City-to-city immersions, coach travel, and reciprocal hospitality', 'super-ninety-four' ),
						__( 'The City Clubs of SUPER NINETY FOUR circuit access', 'super-ninety-four' ),
						__( 'Full member desk — events, clubs, and Lifestyle Concierge', 'super-ninety-four' ),
					),
				),
			),
			'cta'        => array(
				'label' => __( 'Start application', 'super-ninety-four' ),
				'url'   => snf_get_page_url( 'apply' ),
			),
		),
		'application' => array(
			'title' => __( 'Application process', 'super-ninety-four' ),
			'lede'  => __( 'Private consideration — not a public checkout. High-alignment principals may be interviewed directly.', 'super-ninety-four' ),
			'steps' => array(
				array(
					'num'   => '1',
					'title' => __( 'Basics', 'super-ninety-four' ),
					'body'  => __( 'Share your name, email, and phone so the desk can reach you.', 'super-ninety-four' ),
				),
				array(
					'num'   => '2',
					'title' => __( 'Profile', 'super-ninety-four' ),
					'body'  => __( 'Tell us how you host, operate, or travel — reputation and fit matter.', 'super-ninety-four' ),
				),
				array(
					'num'   => '3',
					'title' => __( 'Intent', 'super-ninety-four' ),
					'body'  => __( 'Choose a preferred membership plan and pay the application fee to enter consideration.', 'super-ninety-four' ),
				),
				array(
					'num'   => '4',
					'title' => __( 'Decision', 'super-ninety-four' ),
					'body'  => __( 'Admissions reviews alignment. Approved applicants complete initiation and plan billing.', 'super-ninety-four' ),
				),
			),
			'cta'   => array(
				'label' => __( 'Apply now', 'super-ninety-four' ),
				'url'   => snf_get_page_url( 'apply' ),
			),
		),
		'journey'   => array(
			'title'  => __( 'Before & after joining', 'super-ninety-four' ),
			'lede'   => __( 'What changes when you move from guest or prospect to a seated member.', 'super-ninety-four' ),
			'before' => array(
				'title' => __( 'Before joining', 'super-ninety-four' ),
				'items' => array(
					__( 'Browse and search open admission and select events', 'super-ninety-four' ),
					__( 'Attend eligible events with paid admission — no membership required', 'super-ninety-four' ),
					__( 'Submit service or reciprocal club inquiries as a property manager or client', 'super-ninety-four' ),
					__( 'Start a membership application when you are ready for the private desk', 'super-ninety-four' ),
				),
			),
			'after'  => array(
				'title' => __( 'After joining', 'super-ninety-four' ),
				'items' => array(
					__( 'Member select events, reciprocal clubs, and Lifestyle Concierge', 'super-ninety-four' ),
					__( 'Live lead rider tracking and walkie on eligible road immersions', 'super-ninety-four' ),
					__( 'Account hub for membership, tickets, billing, and messages', 'super-ninety-four' ),
					__( 'Credentials that travel with you across the circuit', 'super-ninety-four' ),
				),
			),
		),
		'rules_faq' => array(
			'title' => __( 'Program rules & FAQ', 'super-ninety-four' ),
			'lede'  => __( 'Discretion, consent, and hospitality standards govern every seat and every guest window.', 'super-ninety-four' ),
			'rules' => array(
				__( 'Admission and partnership follow a strict hospitality bar — alignment over volume.', 'super-ninety-four' ),
				__( 'Discretion and consent are non-negotiable at every event and reciprocal visit.', 'super-ninety-four' ),
				__( 'Membership plans require initiation; dues renew on a 12-month term with a reminder before renewal.', 'super-ninety-four' ),
				__( 'Guests may attend select open-admission events without joining; payment is still required.', 'super-ninety-four' ),
				__( 'SUPER NINETY FOUR may accept, defer, or decline any application without public explanation.', 'super-ninety-four' ),
			),
			'faq'   => array(
				array(
					'q' => __( 'Do I have to become a member to attend events?', 'super-ninety-four' ),
					'a' => __( 'No. You can enjoy attending select events with open admission without joining. You still pay for those tickets or fees. Membership unlocks the private desk, reciprocal clubs, and member-only select events.', 'super-ninety-four' ),
				),
				array(
					'q' => __( 'What does membership include?', 'super-ninety-four' ),
					'a' => __( 'Each plan includes a 12-month seat, initiation, access to member select events, reciprocal club credentials for that plan’s circuit, and Lifestyle Concierge desk access. Higher plans expand road immersions and city-to-city reach.', 'super-ninety-four' ),
				),
				array(
					'q' => __( 'How does the application work?', 'super-ninety-four' ),
					'a' => __( 'Complete Basics, Profile, and Intent on the Apply page, pay the application fee, and wait for admissions. Principals who already meet the highest standard may be interviewed directly and may forgo the standard application path.', 'super-ninety-four' ),
				),
				array(
					'q' => __( 'I am a property manager or client — where do I go?', 'super-ninety-four' ),
					'a' => __( 'Open Inquiries and choose the service or reciprocal program that matches what you need. You do not need membership to submit an inquiry; the desk routes you to the right consideration window.', 'super-ninety-four' ),
				),
				array(
					'q' => __( 'What are the program rules?', 'super-ninety-four' ),
					'a' => __( 'Meet the hospitality standard, protect discretion and consent, pay initiation and dues when seated, and honor guest credentials. Conduct that breaks the standard ends access.', 'super-ninety-four' ),
				),
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
