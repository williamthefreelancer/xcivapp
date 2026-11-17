<?php
/**
 * Single product — workshop landing, Figma event landing, or default Woo layout.
 *
 * @package Super_Ninety_Four
 */

defined( 'ABSPATH' ) || exit;

$is_workshop = false;
$is_event    = false;
if ( have_posts() ) {
	the_post();
	$is_workshop = function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( get_the_ID() );
	$is_event    = ! $is_workshop && function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( get_the_ID() );
	rewind_posts();
}

$blank_chrome = $is_workshop || $is_event;
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
