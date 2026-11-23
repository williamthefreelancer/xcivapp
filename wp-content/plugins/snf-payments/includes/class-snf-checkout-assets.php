<?php
/**
 * Enqueue checkout Elements JS/CSS.
 *
 * @package SNF_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend assets for branded checkout + Stripe Elements.
 */
class SNF_Payments_Checkout_Assets {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 30 );
	}

	/**
	 * Enqueue on checkout / order-pay.
	 */
	public static function enqueue() {
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_wc_endpoint_url( 'order-pay' ) ) ) {
			return;
		}

		$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->get_available_payment_gateways() : array();
		if ( empty( $gateways[ SNF_PAYMENTS_GATEWAY_ID ] ) ) {
			return;
		}

		wp_enqueue_style(
			'snf-payments-checkout',
			SNF_PAYMENTS_URL . 'assets/css/checkout.css',
			array(),
			SNF_PAYMENTS_VERSION
		);

		wp_enqueue_script(
			'stripe-js',
			'https://js.stripe.com/v3/',
			array(),
			null,
			true
		);

		wp_enqueue_script(
			'snf-payments-checkout',
			SNF_PAYMENTS_URL . 'assets/js/checkout-elements.js',
			array( 'jquery', 'stripe-js', 'wc-checkout' ),
			SNF_PAYMENTS_VERSION,
			true
		);

		wp_localize_script(
			'snf-payments-checkout',
			'snfPayments',
			array(
				'publishableKey' => SNF_Payments_Stripe_API::publishable_key(),
				'ajaxUrl'        => WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'createIntent'   => WC_AJAX::get_endpoint( 'snf_create_payment_intent' ),
				'prepareIntent'  => WC_AJAX::get_endpoint( 'snf_prepare_payment_intent' ),
				'confirmPayment' => WC_AJAX::get_endpoint( 'snf_confirm_payment' ),
				'security'       => wp_create_nonce( 'snf-payments-checkout' ),
				'gatewayId'      => SNF_PAYMENTS_GATEWAY_ID,
				'context'        => SNF_Payments_Checkout_Fields::cart_context(),
				'i18n'           => array(
					'payError'   => __( 'Payment could not be completed. Please try again.', 'snf-payments' ),
					'processing' => __( 'Processing payment…', 'snf-payments' ),
					'cardLoad'   => __( 'Loading secure card fields…', 'snf-payments' ),
				),
			)
		);
	}
}
