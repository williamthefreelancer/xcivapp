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
if ( have_posts() ) {
	the_post();
	$product_id    = get_the_ID();
	$is_workshop   = function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( $product_id );
	$is_reciprocal = ! $is_workshop && function_exists( 'snf_is_reciprocal_club_landing' ) && snf_is_reciprocal_club_landing( $product_id );
	$is_event      = ! $is_workshop && ! $is_reciprocal && function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( $product_id );
	rewind_posts();
}

$blank_chrome = $is_workshop || $is_event || $is_reciprocal;
get_header( $blank_chrome ? 'empty' : 'shop' );

while ( have_posts() ) :
	the_post();

	if ( $is_workshop ) {
		get_template_part(
			'template-parts/workshop',
			'lifestyle-concierge',
			array(
				'event' => get_post(),
			)
		);
		continue;
	}

	if ( $is_reciprocal ) {
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

	if ( $is_event ) {
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
