<?php
/**
 * Event single display helpers (Figma Event #1 / node 174:2).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slugs listed under Select Events (members).
 *
 * @return string[]
 */
function snf_get_member_select_event_slugs() {
	return array(
		'the-badlands-bra-private-gathering',
		'the-eight-hundred-plus-journeys-event',
		'the-township-adventures-event',
		// Legacy CPT slugs kept for older deep links.
		'the-badlands-bra-party-bus-gathering',
		'the-eight-hundred-plus-journeys',
	);
}

/**
 * Whether an event/product belongs to Select Events (members).
 *
 * @param int|WP_Post|null $post Event or product post.
 * @return bool
 */
function snf_is_member_select_event( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! in_array( $post->post_type, array( 'event', 'product' ), true ) ) {
		return false;
	}

	$slug = function_exists( 'snf_resolve_listing_product_slug' )
		? snf_resolve_listing_product_slug( $post->post_name )
		: $post->post_name;

	return in_array( $slug, snf_get_member_select_event_slugs(), true )
		|| in_array( $post->post_name, snf_get_member_select_event_slugs(), true );
}

/**
 * Admissions form audience for an event page.
 *
 * @param int|WP_Post|null $post Event post.
 * @return string 'members' or 'non-members'
 */
function snf_get_event_admission_audience( $post = null ) {
	return snf_is_member_select_event( $post ) ? 'members' : 'non-members';
}

/**
 * Default image filename for known event slugs.
 *
 * @param string $slug Event post slug.
 * @return string
 */
function snf_get_event_default_image_file( $slug ) {
	$map = array(
		'the-badlands-bra-private-gathering'     => 'badlands-bra-event.jpg',
		'the-badlands-bra-party-bus-gathering'   => 'badlands-bra-event.jpg',
		'the-eight-hundred-plus-journeys-event'  => 'eight-hundred-journeys-event.jpg',
		'the-eight-hundred-plus-journeys'        => 'eight-hundred-journeys-event.jpg',
		'the-township-adventures-event'          => 'township-adventures-event.jpg',
		'the-lifestyle-concierge-club-workshop'  => 'lifestyle-concierge-club-workshop.jpg',
	);
	return isset( $map[ $slug ] ) ? $map[ $slug ] : '';
}

/**
 * Default price labels for known events.
 *
 * @param string $slug Event post slug.
 * @return string
 */
function snf_get_event_default_price( $slug ) {
	$map = array(
		'the-eight-hundred-plus-journeys-event' => '$497.00',
		'the-eight-hundred-plus-journeys'       => '$497.00',
		'the-badlands-bra-private-gathering'    => '$397.00',
		'the-badlands-bra-party-bus-gathering'  => '$497.00',
		'the-township-adventures-event'         => '$497.00',
		'the-lifestyle-concierge-club-workshop' => '$97.00',
	);
	return isset( $map[ $slug ] ) ? $map[ $slug ] : '$497.00';
}

/**
 * Build countdown parts from a start datetime string.
 *
 * @param string $starts_at MySQL datetime or empty.
 * @return array{days:string,hours:string,mins:string,secs:string,target:string}
 */
function snf_event_countdown_parts( $starts_at ) {
	$empty = array(
		'days'   => '00',
		'hours'  => '00',
		'mins'   => '00',
		'secs'   => '00',
		'target' => '',
	);

	if ( ! $starts_at ) {
		return $empty;
	}

	$tz = wp_timezone();
	$dt = date_create( $starts_at, $tz );
	if ( ! $dt ) {
		return $empty;
	}

	$now  = new DateTimeImmutable( 'now', $tz );
	$diff = $now->diff( $dt );
	if ( $dt < $now ) {
		return array_merge( $empty, array( 'target' => $dt->format( DATE_ATOM ) ) );
	}

	$days  = (int) $diff->days;
	$hours = (int) $diff->h;
	$mins  = (int) $diff->i;
	$secs  = (int) $diff->s;

	return array(
		'days'   => str_pad( (string) min( 99, $days ), 2, '0', STR_PAD_LEFT ),
		'hours'  => str_pad( (string) $hours, 2, '0', STR_PAD_LEFT ),
		'mins'   => str_pad( (string) $mins, 2, '0', STR_PAD_LEFT ),
		'secs'   => str_pad( (string) $secs, 2, '0', STR_PAD_LEFT ),
		'target' => $dt->format( DATE_ATOM ),
	);
}

/**
 * Whether a product/event should render the Figma event landing (no shop header).
 *
 * @param int|WP_Post|null $post Event or product post.
 * @return bool
 */
function snf_is_figma_event_landing( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! in_array( $post->post_type, array( 'event', 'product' ), true ) ) {
		return false;
	}

	if ( function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( $post ) ) {
		return false;
	}

	return snf_is_member_select_event( $post );
}

/**
 * Normalized payload for the Figma event single template.
 *
 * @param int|WP_Post|null $post Event or product post.
 * @return array<string, mixed>|null
 */
function snf_get_event_single_data( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! in_array( $post->post_type, array( 'event', 'product' ), true ) ) {
		return null;
	}

	$post_id = (int) $post->ID;
	$slug    = function_exists( 'snf_resolve_listing_product_slug' )
		? snf_resolve_listing_product_slug( $post->post_name )
		: $post->post_name;
	$img_dir = get_template_directory_uri() . '/assets/images/events/';
	$is_product = ( 'product' === $post->post_type );

	$location  = (string) get_post_meta( $post_id, $is_product ? '_snf_location' : 'snf_event_location', true );
	$timing    = (string) get_post_meta( $post_id, $is_product ? '_snf_when' : 'snf_event_when', true );
	$price     = (string) get_post_meta( $post_id, 'snf_event_price', true );
	$organizer = (string) get_post_meta( $post_id, 'snf_event_organizer', true );
	$category  = (string) get_post_meta( $post_id, 'snf_event_category', true );
	$starts_at = (string) get_post_meta( $post_id, $is_product ? '_snf_starts_at' : 'snf_event_starts_at', true );
	$video     = (string) get_post_meta( $post_id, 'snf_event_video', true );

	if ( ! $starts_at && $is_product ) {
		$starts_at = (string) get_post_meta( $post_id, 'snf_event_starts_at', true );
	}
	if ( ! $location && $is_product ) {
		$location = (string) get_post_meta( $post_id, 'snf_event_location', true );
	}
	if ( ! $timing && $is_product ) {
		$timing = (string) get_post_meta( $post_id, 'snf_event_when', true );
	}

	if ( ! $price && $is_product && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $post_id );
		if ( $product ) {
			$price = html_entity_decode( wp_strip_all_tags( $product->get_price_html() ), ENT_QUOTES, 'UTF-8' );
		}
	}
	if ( ! $price ) {
		$price = snf_get_event_default_price( $slug );
	}
	if ( ! $organizer ) {
		$organizer = __( 'THE SUPER NINETY FOUR EVENTS COMPANY', 'super-ninety-four' );
	}
	if ( ! $category ) {
		$category = __( 'Day to Day', 'super-ninety-four' );
	}
	if ( ! $location ) {
		$defaults = function_exists( 'snf_get_default_event_product_serp_meta' )
			? snf_get_default_event_product_serp_meta()
			: array();
		$location = isset( $defaults[ $slug ]['location'] )
			? (string) $defaults[ $slug ]['location']
			: __( 'Charlotte, NC to Salem, MA', 'super-ninety-four' );
	}

	$image = get_the_post_thumbnail_url( $post_id, 'full' );
	if ( ! $image ) {
		$file = snf_get_event_default_image_file( $slug );
		if ( $file ) {
			$image = $img_dir . $file;
		}
	}

	$content = $post->post_content;
	if ( $is_product && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $post_id );
		if ( $product ) {
			$content = $product->get_description();
			if ( ! $content ) {
				$content = $product->get_short_description();
			}
		}
	}

	$description = has_excerpt( $post_id )
		? get_the_excerpt( $post )
		: wp_trim_words( wp_strip_all_tags( $content ), 90 );

	if ( ! $description ) {
		$description = __( 'Private invite-only immersion on the SUPER NINETY FOUR circuit. Apply to attend, then complete desk confirmation for credentials and arrival notes.', 'super-ninety-four' );
	}

	$is_workshop = snf_is_lifestyle_concierge_workshop( $post_id );
	$audience    = snf_get_event_admission_audience( $post );
	$member      = snf_get_hub_member_context();
	$plans       = snf_get_membership_plans();

	return array(
		'id'           => $post_id,
		'slug'         => $slug,
		'title'        => get_the_title( $post ),
		'category'     => $category,
		'location'     => $location,
		'timing'       => $timing,
		'organizer'    => $organizer,
		'price'        => $price,
		'description'  => $description,
		'content'      => apply_filters( 'the_content', $content ),
		'image'        => $image,
		'video'        => $video,
		'countdown'    => snf_event_countdown_parts( $starts_at ),
		'action_url'   => snf_get_event_action_url( $post_id ),
		'action_label' => snf_get_event_action_label( $post_id ),
		'is_workshop'  => $is_workshop,
		'audience'     => $audience,
		'member'       => $member,
		'plans'        => $plans,
		'apply_url'    => snf_get_page_url( 'apply' ),
	);
}
