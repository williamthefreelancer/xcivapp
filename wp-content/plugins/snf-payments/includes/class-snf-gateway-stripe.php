<?php
/**
 * WooCommerce payment gateway: SUPER NINETY FOUR Stripe Elements.
 *
 * @package SNF_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SNF Stripe gateway.
 */
class SNF_Gateway_Stripe extends WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = SNF_PAYMENTS_GATEWAY_ID;
		$this->method_title       = __( 'SUPER NINETY FOUR', 'snf-payments' );
		$this->method_description = __( 'Accept card payments on-site with Stripe Elements. Keys prefer theme / wp-config SNF_STRIPE_* when present. Webhook: /wp-json/snf-payments/v1/stripe/webhook', 'snf-payments' );
		$this->has_fields         = true;
		$this->supports           = array( 'products', 'refunds' );

		$this->init_form_fields();
		$this->init_settings();

		$this->enabled     = $this->get_option( 'enabled', 'no' );
		$this->title       = $this->get_option( 'title', __( 'SUPER NINETY FOUR', 'snf-payments' ) );
		$this->description = $this->get_option( 'description', __( 'Pay securely with card.', 'snf-payments' ) );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
	}

	/**
	 * Admin settings fields.
	 */
	public function init_form_fields() {
		$theme_hint = function_exists( 'snf_stripe_get_secret_key' ) && snf_stripe_get_secret_key()
			? __( 'Theme / wp-config keys detected — gateway fields are fallbacks only.', 'snf-payments' )
			: __( 'Enter Stripe keys here, or define SNF_STRIPE_* in wp-config / WooCommerce → SNF Stripe.', 'snf-payments' );

		$this->form_fields = array(
			'enabled'               => array(
				'title'   => __( 'Enable/Disable', 'snf-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable SUPER NINETY FOUR payments', 'snf-payments' ),
				'default' => 'yes',
			),
			'title'                 => array(
				'title'       => __( 'Title', 'snf-payments' ),
				'type'        => 'text',
				'description' => __( 'Shown to customers at checkout.', 'snf-payments' ),
				'default'     => __( 'SUPER NINETY FOUR', 'snf-payments' ),
				'desc_tip'    => true,
			),
			'description'           => array(
				'title'       => __( 'Description', 'snf-payments' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description on checkout.', 'snf-payments' ),
				'default'     => __( 'Pay securely with card.', 'snf-payments' ),
				'desc_tip'    => true,
			),
			'mode'                  => array(
				'title'       => __( 'Mode', 'snf-payments' ),
				'type'        => 'select',
				'description' => $theme_hint,
				'default'     => 'test',
				'options'     => array(
					'test' => __( 'Test', 'snf-payments' ),
					'live' => __( 'Live', 'snf-payments' ),
				),
			),
			'test_publishable_key'  => array(
				'title'       => __( 'Test publishable key', 'snf-payments' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => 'pk_test_…',
			),
			'test_secret_key'       => array(
				'title'       => __( 'Test secret key', 'snf-payments' ),
				'type'        => 'password',
				'default'     => '',
				'placeholder' => 'sk_test_…',
			),
			'test_webhook_secret'   => array(
				'title'       => __( 'Test webhook secret', 'snf-payments' ),
				'type'        => 'password',
				'default'     => '',
				'placeholder' => 'whsec_…',
			),
			'publishable_key'       => array(
				'title'       => __( 'Live publishable key', 'snf-payments' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => 'pk_live_…',
			),
			'secret_key'            => array(
				'title'       => __( 'Live secret key', 'snf-payments' ),
				'type'        => 'password',
				'default'     => '',
				'placeholder' => 'sk_live_…',
			),
			'webhook_secret'        => array(
				'title'       => __( 'Live webhook secret', 'snf-payments' ),
				'type'        => 'password',
				'default'     => '',
				'placeholder' => 'whsec_…',
			),
			'webhook_url'           => array(
				'title'       => __( 'Webhook URL', 'snf-payments' ),
				'type'        => 'title',
				'description' => sprintf(
					/* translators: %s: webhook URL */
					__( 'Point Stripe webhooks to: %s — events: payment_intent.succeeded, payment_intent.payment_failed, charge.refunded', 'snf-payments' ),
					'<code>' . esc_html( rest_url( 'snf-payments/v1/stripe/webhook' ) ) . '</code>'
				),
			),
		);
	}

	/**
	 * Whether the gateway can be used at checkout.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}
		if ( ! SNF_Payments_Stripe_API::is_ready() ) {
			return false;
		}
		return parent::is_available();
	}

	/**
	 * Payment fields (Stripe Payment Element mount).
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
		}
		echo '<div id="snf-payment-element" class="snf-payment-element" data-snf-payment-element>';
		echo '<p class="snf-payment-element__placeholder">' . esc_html__( 'Card details will load here…', 'snf-payments' ) . '</p>';
		echo '</div>';
		echo '<div id="snf-payment-errors" class="snf-payment-errors" role="alert" hidden></div>';
		echo '<input type="hidden" name="snf_payment_intent_id" id="snf_payment_intent_id" value="">';
	}

	/**
	 * Verify a client-confirmed PaymentIntent, then mark the order paid.
	 *
	 * Card confirmation happens in JS before checkout submit (avoids Woo's
	 * checkout_place_order_success + return-false "Invalid response" path).
	 *
	 * @param int $order_id Order ID.
	 * @return array<string, mixed>
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wc_add_notice( __( 'Unable to process this order.', 'snf-payments' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$pi_id = isset( $_POST['snf_payment_intent_id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? sanitize_text_field( wp_unslash( $_POST['snf_payment_intent_id'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			: '';
		if ( ! $pi_id && function_exists( 'WC' ) && WC()->session ) {
			$pi_id = (string) WC()->session->get( 'snf_payments_pi_id' );
		}
		if ( ! $pi_id ) {
			wc_add_notice( __( 'Please complete your card details before placing the order.', 'snf-payments' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$intent = SNF_Payments_Stripe_API::request( 'GET', 'payment_intents/' . rawurlencode( $pi_id ) );
		if ( is_wp_error( $intent ) ) {
			wc_add_notice( $intent->get_error_message(), 'error' );
			return array( 'result' => 'failure' );
		}

		$status = isset( $intent['status'] ) ? (string) $intent['status'] : '';
		if ( ! in_array( $status, array( 'succeeded', 'processing' ), true ) ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: Stripe PaymentIntent status */
					__( 'Card payment is not complete yet (status: %s). Please try again.', 'snf-payments' ),
					$status ? $status : 'unknown'
				),
				'error'
			);
			return array( 'result' => 'failure' );
		}

		$expected = SNF_Payments_Stripe_API::amount_to_cents( (float) $order->get_total(), $order->get_currency() );
		$charged  = isset( $intent['amount'] ) ? (int) $intent['amount'] : 0;
		if ( $charged > 0 && $expected > 0 && $charged !== $expected ) {
			wc_add_notice( __( 'Payment amount no longer matches this order. Refresh checkout and try again.', 'snf-payments' ), 'error' );
			return array( 'result' => 'failure' );
		}

		// Attach order metadata for webhooks (safe on succeeded intents).
		SNF_Payments_Stripe_API::request(
			'POST',
			'payment_intents/' . rawurlencode( $pi_id ),
			array(
				'metadata' => array(
					'order_id'    => (string) $order->get_id(),
					'order_key'   => (string) $order->get_order_key(),
					'source'      => 'snf_payments_gateway',
					'customer_id' => (string) $order->get_customer_id(),
				),
			)
		);

		$order->update_meta_data( '_snf_payment_intent', $pi_id );
		if ( ! empty( $intent['client_secret'] ) ) {
			$order->update_meta_data( '_snf_payment_intent_secret', (string) $intent['client_secret'] );
		}
		$order->save();

		SNF_Payments_Payment_Intent::complete_order_from_intent( $order, $intent );

		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( 'snf_payments_pi_id', null );
			WC()->session->set( 'snf_payments_pi_secret', null );
		}

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_order_received_url(),
		);
	}

	/**
	 * Process a refund via Stripe.
	 *
	 * @param int        $order_id Order ID.
	 * @param float|null $amount   Amount.
	 * @param string     $reason   Reason.
	 * @return bool|\WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'snf_payments_refund', __( 'Order not found.', 'snf-payments' ) );
		}

		$pi = (string) $order->get_meta( '_snf_payment_intent' );
		if ( ! $pi ) {
			$pi = (string) $order->get_transaction_id();
		}
		if ( ! $pi ) {
			return new WP_Error( 'snf_payments_refund', __( 'No Stripe PaymentIntent on this order.', 'snf-payments' ) );
		}

		$body = array(
			'payment_intent' => $pi,
		);
		if ( null !== $amount ) {
			$body['amount'] = SNF_Payments_Stripe_API::amount_to_cents( (float) $amount, $order->get_currency() );
		}
		if ( $reason ) {
			$body['reason'] = 'requested_by_customer';
			$body['metadata'] = array(
				'order_id' => (string) $order_id,
				'note'     => sanitize_text_field( $reason ),
			);
		}

		$result = SNF_Payments_Stripe_API::request( 'POST', 'refunds', $body );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$order->add_order_note(
			sprintf(
				/* translators: 1: amount, 2: refund id */
				__( 'SUPER NINETY FOUR: Refunded %1$s (Stripe refund %2$s).', 'snf-payments' ),
				wc_price( (float) $amount, array( 'currency' => $order->get_currency() ) ),
				isset( $result['id'] ) ? (string) $result['id'] : ''
			)
		);

		return true;
	}

	/**
	 * Thank you note.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->is_paid() ) {
			return;
		}
		echo '<p class="snf-payments-thankyou">' . esc_html__( 'Payment received. Admissions and membership activations run from this order.', 'snf-payments' ) . '</p>';
	}
}
