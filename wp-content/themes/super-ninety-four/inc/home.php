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
	);

	/**
	 * Filter front page content sections.
	 *
	 * @param array<string, mixed> $content Home content.
	 */
	return apply_filters( 'snf_home_content', $content );
}
