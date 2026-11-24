<?php
/**
 * Template Name: Gathering thank you
 * Description: Post-purchase thank-you + in-person booking desk for open-admission gatherings.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_slug = get_post_field( 'post_name', get_queried_object_id() );
$slug_map  = array(
	'thank-you-city-club-private-party'           => 'the-city-club-private-party',
	'thank-you-lifestyle-concierge-club-workshop' => 'the-lifestyle-concierge-club-workshop',
);
$product_slug = isset( $slug_map[ $page_slug ] ) ? $slug_map[ $page_slug ] : '';
$order        = function_exists( 'snf_gathering_thankyou_current_order' ) ? snf_gathering_thankyou_current_order() : null;
$payload      = ( $product_slug && function_exists( 'snf_get_gathering_thankyou_payload' ) )
	? snf_get_gathering_thankyou_payload( $product_slug, $order )
	: null;

get_header( 'empty' );
?>
<main class="snf-page snf-gathering-thanks" data-figma-ready="true">
	<?php
	get_template_part(
		'template-parts/gathering',
		'thankyou',
		array(
			'payload' => $payload,
		)
	);
	?>
</main>
<?php
get_footer( 'empty' );
