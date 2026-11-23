<?php
/**
 * Single product — workshop, event, reciprocal club, or default Woo layout.
 *
 * @package Super_Ninety_Four
 */

defined( 'ABSPATH' ) || exit;

$is_workshop   = false;
$is_event      = false;
$is_reciprocal = false;
$is_service    = false;
if ( have_posts() ) {
	the_post();
	$product_id    = get_the_ID();
	$is_workshop   = function_exists( 'snf_is_gathering_landing_product' )
		? snf_is_gathering_landing_product( $product_id )
		: ( function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( $product_id ) );
	$is_reciprocal = ! $is_workshop && function_exists( 'snf_is_reciprocal_club_landing' ) && snf_is_reciprocal_club_landing( $product_id );
	$is_service    = ! $is_workshop && ! $is_reciprocal && function_exists( 'snf_is_service_quote_product' ) && snf_is_service_quote_product( $product_id );
	$is_event      = ! $is_workshop && ! $is_reciprocal && ! $is_service && function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( $product_id );
	rewind_posts();
}

$blank_chrome = $is_workshop || $is_event || $is_reciprocal || $is_service;
get_header( $blank_chrome ? 'empty' : 'shop' );

while ( have_posts() ) :
	the_post();

	if ( $is_workshop ) {
		$landing = function_exists( 'snf_get_stepped_checkout_landing' )
			? snf_get_stepped_checkout_landing( get_the_ID() )
			: array(
				'dir'  => 'workshop',
				'part' => 'lifestyle-concierge',
			);
		get_template_part(
			'template-parts/' . $landing['dir'],
			$landing['part'],
			array(
				'event' => get_post(),
			)
		);
		continue;
	}

	if ( $is_reciprocal && function_exists( 'snf_get_reciprocal_club' ) ) {
		$club = snf_get_reciprocal_club( get_post_field( 'post_name', get_the_ID() ) );
		if ( $club ) {
			get_template_part(
				'template-parts/reciprocal',
				'club',
				array(
					'club' => $club,
				)
			);
			continue;
		}
	}

	if ( $is_service && function_exists( 'snf_get_service_quote' ) ) {
		$quote = snf_get_service_quote( get_post_field( 'post_name', get_the_ID() ) );
		if ( $quote ) {
			get_template_part(
				'template-parts/service',
				'quote',
				array(
					'quote' => $quote,
				)
			);
			continue;
		}
	}

	if ( $is_event && function_exists( 'snf_get_event_single_data' ) ) {
		$event = snf_get_event_single_data( get_post() );
		if ( $event ) {
			get_template_part(
				'template-parts/event',
				'single',
				array(
					'event' => $event,
				)
			);
			continue;
		}
	}

	wc_get_template_part( 'content', 'single-product' );
endwhile;

get_footer( $blank_chrome ? 'empty' : 'shop' );
