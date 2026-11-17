<?php
/**
 * Reciprocal club inquiry landings (Figma inquiries-single-page 2162:66).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interest options for the capture form (Figma 2163:121).
 *
 * @return array<string, string>
 */
function snf_reciprocal_inquiry_interests() {
	return array(
		'interstate-travel'   => __( 'Interstate Travel', 'super-ninety-four' ),
		'theme-parties'       => __( 'Theme Parties', 'super-ninety-four' ),
		'tribe-contribution'  => __( 'Tribe Contribution', 'super-ninety-four' ),
		'reciprocal-affinity' => __( 'Reciprocal Affinity', 'super-ninety-four' ),
	);
}

/**
 * Shared “why consider” lede under the section heading.
 *
 * @return string
 */
function snf_reciprocal_why_consider_lede() {
	return __( 'Principals who already run rooms, routes, or estates use inquiry to enter the circuit without public marketplaces.', 'super-ninety-four' );
}

/**
 * Shared “why consider” list (Figma 2163:164).
 *
 * @return array<int, array{title:string,body:string}>
 */
function snf_reciprocal_why_consider_items() {
	$body = __( 'Bring your existing holding structure into a vetted reciprocal desk that keeps discretion and road production in-house.', 'super-ninety-four' );

	return array(
		array(
			'title' => __( 'Event & Service Marketplace Owners', 'super-ninety-four' ),
			'body'  => $body,
		),
		array(
			'title' => __( 'Club & Event Management Companies', 'super-ninety-four' ),
			'body'  => $body,
		),
		array(
			'title' => __( 'Hospitality Management Companies', 'super-ninety-four' ),
			'body'  => $body,
		),
		array(
			'title' => __( 'Property Managers', 'super-ninety-four' ),
			'body'  => $body,
		),
	);
}

/**
 * Whether a post is a reciprocal club Woo product (or legacy page) landing.
 *
 * @param int|WP_Post|null $post Product or page.
 * @return bool
 */
function snf_is_reciprocal_club_landing( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! in_array( $post->post_type, array( 'product', 'page' ), true ) ) {
		return false;
	}

	$clubs = snf_get_reciprocal_clubs();
	return isset( $clubs[ $post->post_name ] );
}

/**
 * Public URL for a reciprocal club (Woo product preferred, then legacy page, then hub).
 *
 * @param string $slug Club / product slug.
 * @return string
 */
function snf_get_reciprocal_club_url( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( ! $slug ) {
		return snf_get_page_url( 'find-other-reciprocal-programs' );
	}

	$product = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $product instanceof WP_Post && 'publish' === $product->post_status ) {
		return get_permalink( $product );
	}

	$page = get_page_by_path( $slug );
	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		return get_permalink( $page );
	}

	return snf_get_page_url( 'find-other-reciprocal-programs' );
}

/**
 * Shared reciprocal hero heading copy (Figma 2163:95).
 *
 * @return string
 */
function snf_get_reciprocal_hero_heading() {
	return __( "You're invited to a multi-week immerse lifestyle experience", 'super-ninety-four' );
}

/**
 * Default inquiry imagery shared by club landings.
 *
 * @return array{hero:string,mid_image:string,gallery:array<int,string>}
 */
function snf_reciprocal_default_inquiry_images() {
	$img = get_template_directory_uri() . '/assets/images/reciprocal/inquiries/';

	return array(
		'hero'      => $img . 'hero-background.jpg',
		'mid_image' => $img . 'mid-highway.jpg',
		'gallery'   => array(
			$img . 'gallery-1.jpg',
			$img . 'gallery-2.jpg',
			$img . 'gallery-3.jpg',
		),
	);
}

/**
 * All reciprocal club landings keyed by page slug.
 *
 * @return array<string, array<string, mixed>>
 */
function snf_get_reciprocal_clubs() {
	$card_img = get_template_directory_uri() . '/assets/images/reciprocal/';
	$cta      = snf_get_page_url( 'apply' );
	$heading  = snf_get_reciprocal_hero_heading();
	$media    = snf_reciprocal_default_inquiry_images();
	$why      = snf_reciprocal_why_consider_items();
	$why_lede = snf_reciprocal_why_consider_lede();

	$clubs = array(
		'the-clubs-at-back-creek'             => array(
			'slug'         => 'the-clubs-at-back-creek',
			'title'        => __( 'The Clubs at Back Creek', 'super-ninety-four' ),
			'mark'         => 'SN',
			'mark_label'   => __( 'SUPER NINETY FOUR', 'super-ninety-four' ),
			'hero'         => $media['hero'],
			'hero_alt'     => __( 'On the road toward a Back Creek members experience', 'super-ninety-four' ),
			'mid_image'    => $media['mid_image'],
			'gallery'      => $media['gallery'],
			'card_image'   => $card_img . 'the-clubs-at-back-creek.jpg',
			'hero_heading' => $heading,
			'hero_lede'    => __( 'Private regional backway travel and a discreet atmosphere — invite-only events on wheels between Charlotte and outside of Boston.', 'super-ninety-four' ),
			'cta_label'    => __( 'Yes, Send Me Those Inquiries', 'super-ninety-four' ),
			'cta_url'      => $cta,
			'lede'         => __( 'The Clubs at Back Creek is a private community for regional backway travel and a discreet atmosphere — invite-only events on wheels between Charlotte and outside of Boston.', 'super-ninety-four' ),
			'about_title'  => __( 'ABOUT THE CLUBS AT BACK CREEK', 'super-ninety-four' ),
			'about_body'   => __( 'Back Creek hosts reciprocal principals for road immersions, guest credentials, and a desk that stays with you between Charlotte and outside of Boston.', 'super-ninety-four' ),
			'limited_sub'  => __( 'Inquiry seats for the next Back Creek immerse window are limited.', 'super-ninety-four' ),
			'why_lede'     => $why_lede,
			'why_items'    => $why,
		),
		'the-badlands-bra-club'               => array(
			'slug'         => 'the-badlands-bra-club',
			'title'        => __( 'BADLANDS BRA', 'super-ninety-four' ),
			'mark'         => 'BB',
			'mark_label'   => __( 'BADLANDS BRA', 'super-ninety-four' ),
			'hero'         => $media['hero'],
			'hero_alt'     => __( 'Badlands Bra members-only experience', 'super-ninety-four' ),
			'mid_image'    => $media['mid_image'],
			'gallery'      => $media['gallery'],
			'card_image'   => $card_img . 'the-badlands-bra-club.jpg',
			'hero_heading' => $heading,
			'hero_lede'    => __( 'A private members-only events club for provocative social gatherings with road travel and adventurous vibes.', 'super-ninety-four' ),
			'cta_label'    => __( 'Yes, Send Me Those Inquiries', 'super-ninety-four' ),
			'cta_url'      => $cta,
			'lede'         => __( 'BADLANDS BRA is a private members-only events club for provocative social gatherings. With road travel and adventurous vibes, it creates an atmosphere for those seeking both adventure and discretion.', 'super-ninety-four' ),
			'about_title'  => __( 'ABOUT BADLANDS BRA', 'super-ninety-four' ),
			'about_body'   => __( 'BADLANDS BRA gathers reciprocal members for high-energy private parties, interstate travel, and discreet hospitality across the circuit.', 'super-ninety-four' ),
			'limited_sub'  => __( 'Inquiry seats for the next Badlands window are limited.', 'super-ninety-four' ),
			'why_lede'     => $why_lede,
			'why_items'    => $why,
		),
		'the-clubs-at-wyoma-square'           => array(
			'slug'         => 'the-clubs-at-wyoma-square',
			'title'        => __( 'The Clubs at Wyoma Square', 'super-ninety-four' ),
			'mark'         => 'WS',
			'mark_label'   => __( 'WYOMA SQUARE', 'super-ninety-four' ),
			'hero'         => $media['hero'],
			'hero_alt'     => __( 'The Clubs at Wyoma Square', 'super-ninety-four' ),
			'mid_image'    => $media['mid_image'],
			'gallery'      => $media['gallery'],
			'card_image'   => $card_img . 'the-clubs-at-wyoma-square.jpg',
			'hero_heading' => $heading,
			'hero_lede'    => __( 'A reciprocal square for private gatherings, plaza hospitality, and discreet member evenings on the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
			'cta_label'    => __( 'Yes, Send Me Those Inquiries', 'super-ninety-four' ),
			'cta_url'      => $cta,
			'lede'         => __( 'The Clubs at Wyoma Square is a reciprocal square for private gatherings, plaza hospitality, and discreet member evenings linked into the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
			'about_title'  => __( 'ABOUT THE CLUBS AT WYOMA SQUARE', 'super-ninety-four' ),
			'about_body'   => __( 'Wyoma Square links plaza hospitality and member evenings into the broader reciprocal network for principals who need discretion on and off the road.', 'super-ninety-four' ),
			'limited_sub'  => __( 'Inquiry seats for the next Wyoma Square window are limited.', 'super-ninety-four' ),
			'why_lede'     => $why_lede,
			'why_items'    => $why,
		),
		'the-city-clubs-at-super-ninety-four' => array(
			'slug'         => 'the-city-clubs-at-super-ninety-four',
			'title'        => __( 'The City Clubs of SUPER NINETY FOUR', 'super-ninety-four' ),
			'mark'         => 'CC',
			'mark_label'   => __( 'THE CITY CLUBS', 'super-ninety-four' ),
			'hero'         => $media['hero'],
			'hero_alt'     => __( 'The City Clubs of SUPER NINETY FOUR coach', 'super-ninety-four' ),
			'mid_image'    => $media['mid_image'],
			'gallery'      => $media['gallery'],
			'card_image'   => $card_img . 'the-city-clubs-at-super-ninety-four.jpg',
			'hero_heading' => $heading,
			'hero_lede'    => __( 'City-to-city immersions, coach travel, and reciprocal hospitality for principals across the private events network.', 'super-ninety-four' ),
			'cta_label'    => __( 'Yes, Send Me Those Inquiries', 'super-ninety-four' ),
			'cta_url'      => $cta,
			'lede'         => __( 'The City Clubs of SUPER NINETY FOUR connect city-to-city immersions, coach travel, and reciprocal hospitality for principals across the private events network.', 'super-ninety-four' ),
			'about_title'  => __( 'ABOUT THE CITY CLUBS AT SUPER NINETY FOUR', 'super-ninety-four' ),
			'about_body'   => __( 'The City Clubs connect coach travel and city immersions so reciprocal members can move with credentials, discretion, and a desk that follows the route.', 'super-ninety-four' ),
			'limited_sub'  => __( 'Inquiry seats for the next City Clubs window are limited.', 'super-ninety-four' ),
			'why_lede'     => $why_lede,
			'why_items'    => $why,
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
	$clubs = snf_get_reciprocal_clubs();

	// Merge WooCommerce product brands so new reciprocal programs appear on the hub.
	if ( function_exists( 'snf_get_product_brand_cards' ) ) {
		foreach ( snf_get_product_brand_cards( 24 ) as $card ) {
			$slug = '';
			if ( ! empty( $card['url'] ) ) {
				$path = (string) wp_parse_url( $card['url'], PHP_URL_PATH );
				$slug = sanitize_title( basename( untrailingslashit( $path ) ) );
			}
			if ( ! $slug || isset( $clubs[ $slug ] ) ) {
				continue;
			}
			$media = snf_reciprocal_default_inquiry_images();
			$clubs[ $slug ] = array(
				'slug'         => $slug,
				'title'        => $card['title'],
				'mark'         => strtoupper( mb_substr( $card['title'], 0, 2 ) ),
				'mark_label'   => $card['title'],
				'hero'         => $media['hero'],
				'hero_alt'     => $card['title'],
				'mid_image'    => $media['mid_image'],
				'gallery'      => $media['gallery'],
				'card_image'   => $card['image'],
				'hero_heading' => snf_get_reciprocal_hero_heading(),
				'hero_lede'    => __( 'A reciprocal program on the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
				'cta_label'    => __( 'Yes, Send Me Those Inquiries', 'super-ninety-four' ),
				'cta_url'      => snf_get_page_url( 'apply' ),
				'lede'         => __( 'A reciprocal program on the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
				'about_title'  => sprintf(
					/* translators: %s: club title */
					__( 'ABOUT %s', 'super-ninety-four' ),
					strtoupper( (string) $card['title'] )
				),
				'about_body'   => __( 'A reciprocal program on the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
				'limited_sub'  => __( 'Inquiry seats for the next immerse window are limited.', 'super-ninety-four' ),
				'why_lede'     => snf_reciprocal_why_consider_lede(),
				'why_items'    => snf_reciprocal_why_consider_items(),
			);
		}
	}

	return array(
		'title' => __( 'Reciprocal Programs', 'super-ninety-four' ),
		'lede'  => __( 'Explore partner clubs across the SUPER NINETY FOUR circuit — Back Creek, Badlands, Wyoma Square, The City Clubs, and more.', 'super-ninety-four' ),
		'clubs' => array_values( $clubs ),
	);
}

/**
 * Simple SVG icon shared by reciprocal / home cards.
 *
 * @param string $name Icon key.
 * @return string
 */
function snf_reciprocal_icon_svg( $name ) {
	$icons = array(
		'pin'       => '<path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/>',
		'crown'     => '<path d="M3 18h18v2H3zm1.5-3 2.7-7.2L12 11l4.8-3.2L19.5 15z"/>',
		'shield'    => '<path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5z"/>',
		'clipboard' => '<path d="M9 2h6a2 2 0 0 1 2 2h1a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1a2 2 0 0 1 2-2zm0 2v2h6V4zm2 8h6v2h-6zm0 4h6v2h-6zM7 12h2v2H7zm0 4h2v2H7z"/>',
		'bulb'      => '<path d="M9 21h6v1H9zm3-19a7 7 0 0 0-4 12.7V18h8v-3.3A7 7 0 0 0 12 2z"/>',
		'walk'      => '<path d="M13.5 5.5a2 2 0 1 0-2 0V8l-3 2 1 7h2l1-4 2 2v5h2v-6l-2.5-2.5L15 8z"/>',
		'cart'      => '<path d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM3 4h2l3.6 9.6L7.2 16H19v-2H8.4l.8-1.5H19l2-7H6.2L5.4 2H3z"/>',
		'lock'      => '<path d="M17 8h-1V6a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2zm-7-2a2 2 0 1 1 4 0v2h-4z"/>',
		'ticket'    => '<path d="M22 10V6H2v4a2 2 0 0 1 0 4v4h20v-4a2 2 0 0 1 0-4zM4 8h16v1.5a3.5 3.5 0 0 0 0 5V18H4v-3.5a3.5 3.5 0 0 0 0-5z"/>',
		'car'       => '<path d="M5 11 6.5 6.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11h1a1 1 0 0 1 1 1v5h-2a2 2 0 1 1-4 0H9a2 2 0 1 1-4 0H3v-5a1 1 0 0 1 1-1zm2.2-3.5L6.3 11h11.4l-.9-3.5z"/>',
		'card'      => '<path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm2 0v3h16V6zm0 5v7h16v-7z"/>',
		'home'      => '<path d="M12 3 3 10v11h6v-6h6v6h6V10z"/>',
		'bed'       => '<path d="M3 7h2v6h14V9a2 2 0 0 0-2-2H9V7H3zm0 10h18v2H3z"/>',
		'wrench'    => '<path d="M22.7 19.4 15 11.7A6 6 0 0 0 7.2 3.1L11 7 7 11 3.1 7.2A6 6 0 0 0 11.7 15l7.7 7.7a1 1 0 0 0 1.4 0l1.9-1.9a1 1 0 0 0 0-1.4z"/>',
		'globe'     => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 2c1.7 0 3.3 2.7 3.8 6H8.2C8.7 6.7 10.3 4 12 4zm-4.9 8H16.9c-.2 1.4-.7 2.8-1.4 4H8.5c-.7-1.2-1.2-2.6-1.4-4zM12 20c-1.7 0-3.3-2.7-3.8-6h7.6C15.3 17.3 13.7 20 12 20z"/>',
	);

	$path = isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['pin'];
	return '<svg class="snf-reciprocal__icon" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false">' . $path . '</svg>';
}
