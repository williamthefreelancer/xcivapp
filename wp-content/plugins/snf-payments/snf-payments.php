<?php
/**
 * Plugin Name: SUPER NINETY FOUR Payments
 * Plugin URI:  https://superninetyfour.com
 * Description: WooCommerce payment gateway with Stripe Elements and SNF branded checkout fields. Enable under WooCommerce → Settings → Payments.
 * Version:     1.0.1
 * Author:      SUPER NINETY FOUR
 * Author URI:  https://superninetyfour.com
 * Text Domain: snf-payments
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.6
 *
 * @package SNF_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SNF_PAYMENTS_VERSION', '1.0.1' );
define( 'SNF_PAYMENTS_FILE', __FILE__ );
define( 'SNF_PAYMENTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'SNF_PAYMENTS_URL', plugin_dir_url( __FILE__ ) );
define( 'SNF_PAYMENTS_GATEWAY_ID', 'snf_stripe' );

/**
 * Declare HPOS / cart-checkout blocks compatibility.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SNF_PAYMENTS_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', SNF_PAYMENTS_FILE, false );
		}
	}
);

/**
 * Bootstrap after plugins load.
 */
function snf_payments_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			static function () {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'SUPER NINETY FOUR Payments requires WooCommerce.', 'snf-payments' );
				echo '</p></div>';
			}
		);
		return;
	}

	require_once SNF_PAYMENTS_PATH . 'includes/class-snf-stripe-api.php';
	require_once SNF_PAYMENTS_PATH . 'includes/class-snf-payment-intent.php';
	require_once SNF_PAYMENTS_PATH . 'includes/class-snf-checkout-fields.php';
	require_once SNF_PAYMENTS_PATH . 'includes/class-snf-webhook.php';
	require_once SNF_PAYMENTS_PATH . 'includes/class-snf-gateway-stripe.php';
	require_once SNF_PAYMENTS_PATH . 'includes/class-snf-checkout-assets.php';

	SNF_Payments_Checkout_Fields::init();
	SNF_Payments_Webhook::init();
	SNF_Payments_Checkout_Assets::init();
	SNF_Payments_Payment_Intent::init();
}
add_action( 'plugins_loaded', 'snf_payments_init', 20 );

/**
 * Register the gateway with WooCommerce.
 *
 * @param array<int, string> $gateways Gateways.
 * @return array<int, string>
 */
function snf_payments_register_gateway( $gateways ) {
	$gateways[] = 'SNF_Gateway_Stripe';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'snf_payments_register_gateway' );

/**
 * Activation: enable gateway when Stripe keys exist; avoid rewrite flush (can hang on local MySQL).
 */
function snf_payments_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( SNF_PAYMENTS_FILE ) );
		wp_die(
			esc_html__( 'SUPER NINETY FOUR Payments requires WooCommerce to be active.', 'snf-payments' ),
			esc_html__( 'Plugin activation error', 'snf-payments' ),
			array( 'back_link' => true )
		);
	}

	$settings = get_option( 'woocommerce_' . SNF_PAYMENTS_GATEWAY_ID . '_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	$settings['enabled']     = 'yes';
	$settings['title']       = ! empty( $settings['title'] ) ? $settings['title'] : __( 'SUPER NINETY FOUR', 'snf-payments' );
	$settings['description'] = ! empty( $settings['description'] ) ? $settings['description'] : __( 'Pay securely with card.', 'snf-payments' );
	if ( empty( $settings['mode'] ) ) {
		$settings['mode'] = 'test';
	}
	update_option( 'woocommerce_' . SNF_PAYMENTS_GATEWAY_ID . '_settings', $settings );
}
register_activation_hook( __FILE__, 'snf_payments_activate' );

/**
 * Deactivation cleanup.
 */
function snf_payments_deactivate() {
	flush_rewrite_rules( false );
}
register_deactivation_hook( __FILE__, 'snf_payments_deactivate' );

/**
 * Theme helper: stash Apply form fields into the Woo session for checkout prefill.
 *
 * @param array<string, mixed> $fields Field map.
 */
function snf_payments_stash_apply_fields( array $fields ) {
	if ( class_exists( 'SNF_Payments_Checkout_Fields' ) ) {
		SNF_Payments_Checkout_Fields::stash_apply_fields( $fields );
	}
}
