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
			'hero_lede'    => __( 'Charlotte ↔ outside Boston. Credentials that travel the backway.', 'super-ninety-four' ),
			'cta_label'    => __( 'Request consideration', 'super-ninety-four' ),
			'cta_url'      => $cta,
			'lede'         => __( 'Charlotte ↔ outside Boston. Credentials that travel the backway.', 'super-ninety-four' ),
			'scope'        => __( 'North Carolina only', 'super-ninety-four' ),
			'about_title'  => __( 'ABOUT THE CLUBS AT BACK CREEK', 'super-ninety-four' ),
			'about_body'   => __( 'North Carolina only. Back Creek is the Harrisburg / Harris Houston / Back Creek desk — road immersions, guest credentials, and a private desk on the Charlotte backway that points toward outside Boston.', 'super-ninety-four' ),
			'limited_sub'  => __( 'Inquiry seats for the next Back Creek immerse window are limited.', 'super-ninety-four' ),
			'why_lede'     => $why_lede,
			'why_items'    => $why,
			'public'       => true,
		),
		/* BADLANDS BRA kept in catalog for Woo/legacy URLs but excluded from public hub/one-pager. */
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
			'public'       => false,
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
			'hero_lede'    => __( 'Plaza hospitality without the plaza crowd.', 'super-ninety-four' ),
			'cta_label'    => __( 'Request consideration', 'super-ninety-four' ),
			'cta_url'      => $cta,
			'lede'         => __( 'Plaza hospitality without the plaza crowd.', 'super-ninety-four' ),
			'scope'        => __( 'Massachusetts North Shore and New Hampshire only', 'super-ninety-four' ),
			'about_title'  => __( 'ABOUT THE CLUBS AT WYOMA SQUARE', 'super-ninety-four' ),
			'about_body'   => __( 'Massachusetts specifically — North Shore area and New Hampshire only. Plaza hospitality without the plaza crowd: member evenings and reciprocal hospitality on the square, linked into the broader circuit with discretion on and off the road.', 'super-ninety-four' ),
			'limited_sub'  => __( 'Inquiry seats for the next Wyoma Square window are limited.', 'super-ninety-four' ),
			'why_lede'     => $why_lede,
			'why_items'    => $why,
			'public'       => true,
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
			'hero_lede'    => __( 'City to city. Coach, not chaos.', 'super-ninety-four' ),
			'cta_label'    => __( 'Request consideration', 'super-ninety-four' ),
			'cta_url'      => $cta,
			'lede'         => __( 'City to city. Coach, not chaos.', 'super-ninety-four' ),
			'scope'        => __( 'City-to-city interstate: Harrisburg / Harris Houston / Back Creek → North Shore', 'super-ninety-four' ),
			'about_title'  => __( 'ABOUT THE CITY CLUBS AT SUPER NINETY FOUR', 'super-ninety-four' ),
			'about_body'   => __( 'City to city interstate — coach, not chaos. The corridor starts in the neighborhoods of Harrisburg, NC and Harris Houston / Back Creek and runs to the North Shore via I-485 N, I-77 N, I-81, I-87, I-90, then RTE 1, RTE 129A, RTE 107, and RTE 117. Credentials, discretion, and a desk that follows the route.', 'super-ninety-four' ),
			'limited_sub'  => __( 'Inquiry seats for the next City Clubs window are limited.', 'super-ninety-four' ),
			'why_lede'     => $why_lede,
			'why_items'    => $why,
			'public'       => true,
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
 * Reciprocal clubs exposed on hub / marketing one-pager (excludes BADLANDS BRA).
 *
 * @return array<string, array<string, mixed>>
 */
function snf_get_public_reciprocal_clubs() {
	$clubs = snf_get_reciprocal_clubs();
	$out   = array();
	foreach ( $clubs as $slug => $club ) {
		if ( isset( $club['public'] ) && false === $club['public'] ) {
			continue;
		}
		$out[ $slug ] = $club;
	}

	/**
	 * Filter public reciprocal clubs.
	 *
	 * @param array<string, array<string, mixed>> $out Public clubs by slug.
	 */
	return apply_filters( 'snf_public_reciprocal_clubs', $out );
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
	$clubs = snf_get_public_reciprocal_clubs();

	// Merge WooCommerce product brands so new reciprocal programs appear on the hub.
	if ( function_exists( 'snf_get_product_brand_cards' ) ) {
		foreach ( snf_get_product_brand_cards( 24 ) as $card ) {
			$slug = '';
			if ( ! empty( $card['url'] ) ) {
				$path = (string) wp_parse_url( $card['url'], PHP_URL_PATH );
				$slug = sanitize_title( basename( untrailingslashit( $path ) ) );
			}
			if ( ! $slug || isset( $clubs[ $slug ] ) || 'the-badlands-bra-club' === $slug ) {
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
				'cta_label'    => __( 'Request consideration', 'super-ninety-four' ),
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
				'public'       => true,
			);
		}
	}

	return array(
		'title' => __( 'Reciprocal Programs', 'super-ninety-four' ),
		'lede'  => __( 'Three doors. Same circuit. No public guestlist — Back Creek, Wyoma Square, City Clubs.', 'super-ninety-four' ),
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
