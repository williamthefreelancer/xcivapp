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
	$card_img   = get_template_directory_uri() . '/assets/images/reciprocal/';
	$uploads    = wp_get_upload_dir();
	$upload_uri = trailingslashit( $uploads['baseurl'] ) . '2026/11/';
	$cta        = snf_get_page_url( 'apply' );
	$heading    = snf_get_reciprocal_hero_heading();
	$media      = snf_reciprocal_default_inquiry_images();
	$why        = snf_reciprocal_why_consider_items();
	$why_lede   = snf_reciprocal_why_consider_lede();

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
			'hero'         => $upload_uri . 'badlands-bra-image-background.jpg',
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
			'hero'         => $upload_uri . 'wyoma-square-in-lynn-ma.png',
			'hero_alt'     => __( 'Wyoma Square in Lynn, MA', 'super-ninety-four' ),
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
			'hero'         => $upload_uri . 'private-club-image-background.jpg',
			'hero_alt'     => __( 'The City Clubs of SUPER NINETY FOUR', 'super-ninety-four' ),
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
 * Phosphor (MIT) / theme UI icon filename for a semantic key.
 *
 * @param string $name Icon key.
 * @return string Filename under assets/icons/ui/.
 */
function snf_ui_icon_filename( $name ) {
	$map = array(
		'pin'       => 'map-pin.svg',
		'crown'     => 'crown.svg',
		'shield'    => 'shield-check.svg',
		'clipboard' => 'clipboard-text.svg',
		'ticket'    => 'ticket.svg',
		'car'       => 'car.svg',
		'home'      => 'house.svg',
		'globe'     => 'globe.svg',
		'walk'      => 'person-simple-walk.svg',
		'broom'     => 'broom.svg',
		'bulb'      => 'sparkle.svg',
		'buildings' => 'buildings.svg',
		'users'     => 'users-three.svg',
		'workshop'  => 'chalkboard-teacher.svg',
	);

	return isset( $map[ $name ] ) ? $map[ $name ] : $map['pin'];
}

/**
 * Inline SVG icon shared by reciprocal / home cards (Phosphor MIT assets).
 *
 * @param string $name  Icon key.
 * @param string $class CSS class on the root <svg>.
 * @return string
 */
function snf_reciprocal_icon_svg( $name, $class = 'snf-reciprocal__icon' ) {
	$file = get_template_directory() . '/assets/icons/ui/' . snf_ui_icon_filename( $name );
	if ( ! is_readable( $file ) ) {
		$file = get_template_directory() . '/assets/icons/ui/map-pin.svg';
	}

	$svg = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$svg = preg_replace( '/<\?xml[^>]*\?>/', '', $svg );
	$class = sanitize_html_class( $class );

	if ( preg_match( '/<svg\b([^>]*)>/i', $svg, $m ) ) {
		$attrs = $m[1];
		if ( false === stripos( $attrs, 'class=' ) ) {
			$attrs .= ' class="' . esc_attr( $class ) . '"';
		} else {
			$attrs = preg_replace( '/class="([^"]*)"/i', 'class="$1 ' . esc_attr( $class ) . '"', $attrs, 1 );
		}
		if ( false === stripos( $attrs, 'width=' ) ) {
			$attrs .= ' width="28" height="28"';
		}
		if ( false === stripos( $attrs, 'aria-hidden' ) ) {
			$attrs .= ' aria-hidden="true" focusable="false"';
		}
		$svg = preg_replace( '/<svg\b[^>]*>/i', '<svg' . $attrs . '>', $svg, 1 );
	}

	return $svg;
}
