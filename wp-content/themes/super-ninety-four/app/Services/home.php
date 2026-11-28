<?php
/**
 * Front page section content — marketing one-pager.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve a one-pager href (path, hash, or absolute URL).
 *
 * @param string $url Raw URL or path.
 * @return string
 */
function snf_home_resolve_href( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '#';
	}
	if ( '#' === $url[0] ) {
		return $url;
	}
	if ( preg_match( '#^https?://#i', $url ) ) {
		return $url;
	}
	if ( 0 === strpos( $url, '/' ) ) {
		$path = $url;
		$hash = '';
		if ( false !== strpos( $path, '#' ) ) {
			list( $path, $hash ) = explode( '#', $path, 2 );
			$hash = '#' . $hash;
		}
		return home_url( $path ) . $hash;
	}
	return $url;
}

/**
 * Hero copy for the marketing one-pager.
 *
 * @return array<string, mixed>
 */
function snf_get_home_hero() {
	$brand = __( 'SUPER NINETY FOUR', 'super-ninety-four' );
	$hero  = array(
		'brand'     => $brand,
		'showBrand' => false,
		'title'      => __( 'If you already run rooms, routes, or estates', 'super-ninety-four' ),
		'titleLead'  => __( 'If you already run', 'super-ninety-four' ),
		'titleFocus' => __( 'rooms, routes, or estates', 'super-ninety-four' ),
		'lede'       => __( 'You’re already running the desk — apply for consideration, or see how we help your rooms, routes, and estates first.', 'super-ninety-four' ),
		'cta'       => array(
			'label' => __( 'Apply', 'super-ninety-four' ),
			'url'   => '/apply',
		),
		'secondary' => array(
			'label' => __( 'See how we help', 'super-ninety-four' ),
			'url'   => '#help',
		),
	);

	/**
	 * Filter one-pager hero payload.
	 *
	 * @param array<string, mixed> $hero Hero content.
	 */
	return apply_filters( 'snf_home_hero', $hero );
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
	$img_base     = get_template_directory_uri() . '/assets/images/services/';
	$workshop_img = function_exists( 'snf_get_event_default_image_url' )
		? snf_get_event_default_image_url( 'the-lifestyle-concierge-club-workshop' )
		: get_template_directory_uri() . '/assets/images/events/lcc-workshop.jpg';
	$workshop_url = function_exists( 'snf_get_workshop_landing_url' )
		? snf_get_workshop_landing_url()
		: '/product/the-lifestyle-concierge-club-workshop';

	$cards = array(
		array(
			'title' => __( 'Litter Pickup Service', 'super-ninety-four' ),
			'meta'  => __( 'Parking lots · Commercial grounds', 'super-ninety-four' ),
			'body'  => __( 'Lots look like you mean business — overnight, not eventually.', 'super-ninety-four' ),
			'url'   => 'https://www.litterpickupserviceatsuperninetyfour.com/',
			'image' => $img_base . 'litter-pickup-service-image-2.jpg',
			'cta'   => __( 'Open the desk', 'super-ninety-four' ),
		),
		array(
			'title' => __( 'Line Sitting & Courier', 'super-ninety-four' ),
			'meta'  => __( 'Busy lines · Shopping runs · Handoffs', 'super-ninety-four' ),
			'body'  => __( 'Hold the line. Move the goods. Stay off the guestlist.', 'super-ninety-four' ),
			'url'   => 'https://www.xcivcourier.com/',
			'image' => $img_base . 'lifestyle-service-image-1.jpg',
			'cta'   => __( 'Open the desk', 'super-ninety-four' ),
		),
		array(
			'title' => __( 'The Lifestyle Concierge Club Workshop', 'super-ninety-four' ),
			'meta'  => __( 'Open admission · Circuit operators', 'super-ninety-four' ),
			'body'  => __( 'Open admission workshop desk for circuit operators and advisors. For new guests and members.', 'super-ninety-four' ),
			'url'   => $workshop_url,
			'image' => $workshop_img,
			'cta'   => __( 'Reserve seat', 'super-ninety-four' ),
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
 * Upcoming gathering event cards for the My Account dashboard.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_upcoming_gathering_cards() {
	$workshop_img = function_exists( 'snf_get_event_default_image_url' )
		? snf_get_event_default_image_url( 'the-lifestyle-concierge-club-workshop' )
		: get_template_directory_uri() . '/assets/images/events/lcc-workshop.jpg';
	$workshop_url = function_exists( 'snf_get_workshop_landing_url' )
		? snf_get_workshop_landing_url()
		: '/product/the-lifestyle-concierge-club-workshop';

	$cards = array(
		array(
			'slug'  => 'the-lifestyle-concierge-club-workshop',
			'title' => __( 'The Lifestyle Concierge Club Workshop', 'super-ninety-four' ),
			'body'  => __( 'Open admission workshop desk for circuit operators and advisors. For new guests and members.', 'super-ninety-four' ),
			'price' => __( 'Open admission', 'super-ninety-four' ),
			'image' => $workshop_img,
			'url'   => $workshop_url,
			'cta'   => __( 'Reserve seat', 'super-ninety-four' ),
		),
	);

	/**
	 * Filter upcoming gathering cards (My Account dashboard).
	 *
	 * @param array<int, array<string, mixed>> $cards Gathering cards.
	 */
	$cards = apply_filters( 'snf_upcoming_gathering_cards', $cards );

	/**
	 * Legacy filter alias (previously home one-pager).
	 *
	 * @param array<int, array<string, mixed>> $cards Gathering cards.
	 */
	return apply_filters( 'snf_home_gathering_cards', $cards );
}

/**
 * @deprecated Use snf_get_upcoming_gathering_cards().
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_home_gathering_cards() {
	return snf_get_upcoming_gathering_cards();
}

/**
 * Front page content payload.
 *
 * @return array<string, mixed>
 */
function snf_get_home_content() {
	$content = array(
		/* One-pager: How can SUPER NINETY FOUR help you */
		'help'     => array(
			'title' => __( 'How can SUPER NINETY FOUR help you', 'super-ninety-four' ),
			'lede'  => __( 'You don’t need membership to open the desk. You need the right stop.', 'super-ninety-four' ),
			'cards' => snf_get_home_help_cards(),
		),
		/* Who is / is not for */
		'audience' => array(
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
		/* Home one-pager no longer lists gatherings; workshop lives under help. */
		'gatherings' => array(
			'title' => '',
			'lede'  => '',
			'cards' => array(),
		),
		/* YouTube newsletter + live video / audio teaser */
		'newsletter' => array(
			'title'      => __( 'The Zircoria Cryon Newsletter', 'super-ninety-four' ),
			'lede'       => __( 'Films, live rooms, and circuit drops — subscribe and SureContact delivers the details.', 'super-ninety-four' ),
			'youtubeUrl' => function_exists( 'snf_newsletter_youtube_url' ) ? snf_newsletter_youtube_url() : 'https://www.youtube.com/@williamslist',
			'benefits'   => array(
				__( 'Upcoming event updates and private invitations', 'super-ninety-four' ),
				__( 'Every new YouTube video, as soon as it lands', 'super-ninety-four' ),
				__( 'Live video and audio details delivered through SureContact', 'super-ninety-four' ),
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
