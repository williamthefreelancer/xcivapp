<?php
/**
 * One-off stack verification for WP-CLI: wp eval-file bin/snf-verify-stack.php
 *
 * @package Super_Ninety_Four
 */

echo 'sdk:' . ( class_exists( '\Stripe\StripeClient' ) ? 'yes' : 'no' ) . PHP_EOL;
echo 'theme_sdk:' . ( function_exists( 'snf_stripe_sdk_available' ) && snf_stripe_sdk_available() ? 'yes' : 'no' ) . PHP_EOL;
echo 'expand:' . ( function_exists( 'snf_stripe_expand_form_body' ) ? 'yes' : 'no' ) . PHP_EOL;

$types = WP_Block_Type_Registry::get_instance()->get_all_registered();
foreach ( array( 'snf/page-hero', 'snf/cta-button', 'snf/listing-grid', 'snf/form-slot' ) as $name ) {
	echo $name . ':' . ( isset( $types[ $name ] ) ? 'ok' : 'missing' ) . PHP_EOL;
}

echo 'ottokit_actions:' . ( function_exists( 'snf_get_ottokit_action_catalog' ) ? (string) count( snf_get_ottokit_action_catalog() ) : '0' ) . PHP_EOL;
echo 'sureforms_hosts:' . ( function_exists( 'snf_get_sureforms_host_page_slugs' ) ? implode( ',', snf_get_sureforms_host_page_slugs() ) : 'none' ) . PHP_EOL;
echo 'funnel_intents:' . ( function_exists( 'snf_get_sureforms_funnel_intents' ) ? (string) count( snf_get_sureforms_funnel_intents() ) : '0' ) . PHP_EOL;
echo 'funnel_class:' . ( class_exists( 'SNF_SureForms_Funnel' ) ? 'ok' : 'missing' ) . PHP_EOL;
echo 'media_cpts:' . ( post_type_exists( 'snf_video' ) && post_type_exists( 'snf_audio' ) ? 'ok' : 'missing' ) . PHP_EOL;
echo 'media_tax:' . ( taxonomy_exists( 'snf_media_topic' ) && taxonomy_exists( 'snf_audio_channel' ) && taxonomy_exists( 'snf_broadcast_format' ) ? 'ok' : 'missing' ) . PHP_EOL;
echo 'media_settings:' . ( function_exists( 'snf_get_media_settings' ) ? 'ok' : 'missing' ) . PHP_EOL;

$hero = function_exists( 'snf_render_block_page_hero' )
	? snf_render_block_page_hero( array( 'title' => 'Contact', 'lede' => 'Reach the club.' ) )
	: '';
echo 'hero_html:' . ( false !== strpos( $hero, 'snf-block-page-hero' ) ? 'ok' : 'fail' ) . PHP_EOL;

$cta = function_exists( 'snf_render_block_cta_button' )
	? snf_render_block_cta_button( array( 'label' => 'Apply', 'target' => 'apply' ) )
	: '';
echo 'cta_html:' . ( false !== strpos( $cta, 'snf-block-cta__button' ) ? 'ok' : 'fail' ) . PHP_EOL;

$grid = function_exists( 'snf_render_block_listing_grid' )
	? snf_render_block_listing_grid( array( 'source' => 'service-inquiries' ) )
	: '';
echo 'grid_html:' . ( false !== strpos( $grid, 'snf-block-listing-grid' ) ? 'ok' : 'fail' ) . PHP_EOL;

$slot = function_exists( 'snf_render_block_form_slot' )
	? snf_render_block_form_slot( array( 'heading' => 'Write us' ), '' )
	: '';
echo 'slot_html:' . ( false !== strpos( $slot, 'snf-block-form-slot' ) ? 'ok' : 'fail' ) . PHP_EOL;

$status = function_exists( 'snf_stripe_rest_status' ) ? snf_stripe_rest_status() : null;
if ( $status instanceof WP_REST_Response ) {
	$data = $status->get_data();
	echo 'status_sdk:' . ( ! empty( $data['sdk'] ) ? 'yes' : 'no' ) . PHP_EOL;
	echo 'status_mapbox_key:' . ( isset( $data['mapbox'] ) ? 'yes' : 'no' ) . PHP_EOL;
}

echo 'plugins:';
foreach ( array( 'sureforms/sureforms.php', 'surecontact/surecontact.php', 'suretriggers/suretriggers.php' ) as $plugin ) {
	echo ( function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin ) ? basename( dirname( $plugin ) ) . '=active ' : basename( dirname( $plugin ) ) . '=off ' );
}
echo PHP_EOL;
