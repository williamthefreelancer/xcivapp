<?php
/**
 * Single product — workshop landing or default Woo layout.
 *
 * @package Super_Ninety_Four
 */

defined( 'ABSPATH' ) || exit;

$is_workshop = false;
if ( have_posts() ) {
	the_post();
	$is_workshop = function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( get_the_ID() );
	rewind_posts();
}

get_header( $is_workshop ? 'empty' : 'shop' );

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

	wc_get_template_part( 'content', 'single-product' );
endwhile;

get_footer( $is_workshop ? 'empty' : 'shop' );
