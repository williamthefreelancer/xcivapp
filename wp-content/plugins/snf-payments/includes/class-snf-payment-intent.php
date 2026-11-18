<?php
/**
 * PaymentIntent create / update for checkout orders.
 *
 * @package SNF_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PaymentIntent service + wc-ajax endpoints.
 */
class SNF_Payments_Payment_Intent {

	/**
	 * Hook AJAX endpoints.
	 */
	public static function init() {
		add_action( 'wc_ajax_snf_create_payment_intent', array( __CLASS__, 'ajax_create' ) );
		add_action( 'wc_ajax_nopriv_snf_create_payment_intent', array( __CLASS__, 'ajax_create' ) );
		add_action( 'wc_ajax_snf_prepare_payment_intent', array( __CLASS__, 'ajax_prepare_from_cart' ) );
		add_action( 'wc_ajax_nopriv_snf_prepare_payment_intent', array( __CLASS__, 'ajax_prepare_from_cart' ) );
		add_action( 'wc_ajax_snf_confirm_payment', array( __CLASS__, 'ajax_confirm' ) );
		add_action( 'wc_ajax_nopriv_snf_confirm_payment', array( __CLASS__, 'ajax_confirm' ) );
	}

	/**
	 * Create or refresh a PaymentIntent for an order.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function create_for_order( WC_Order $order ) {
		if ( ! SNF_Payments_Stripe_API::is_ready() ) {
			return new WP_Error( 'snf_payments_not_ready', __( 'Stripe is not configured.', 'snf-payments' ) );
		}

		$currency = strtolower( $order->get_currency() ? $order->get_currency() : get_woocommerce_currency() );
		$amount   = SNF_Payments_Stripe_API::amount_to_cents( (float) $order->get_total(), $currency );
		if ( $amount < 1 ) {
			return new WP_Error( 'snf_payments_zero', __( 'Order total must be greater than zero.', 'snf-payments' ) );
		}

		$existing = (string) $order->get_meta( '_snf_payment_intent' );
		$meta     = array(
			'order_id'    => (string) $order->get_id(),
			'order_key'   => (string) $order->get_order_key(),
			'source'      => 'snf_payments_gateway',
			'customer_id' => (string) $order->get_customer_id(),
		);

		$body = array(
			'amount'               => $amount,
			'currency'             => $currency,
			'metadata'             => $meta,
			'description'          => sprintf(
				/* translators: %s: order number */
				__( 'SUPER NINETY FOUR order %s', 'snf-payments' ),
				$order->get_order_number()
			),
			'automatic_payment_methods' => array(
				'enabled' => 'true',
			),
		);

		$email = $order->get_billing_email();
		if ( $email ) {
			$body['receipt_email'] = $email;
		}

		if ( $existing ) {
			$updated = SNF_Payments_Stripe_API::request(
				'POST',
				'payment_intents/' . rawurlencode( $existing ),
				array(
					'amount'   => $amount,
					'currency' => $currency,
					'metadata' => $meta,
				)
			);
			if ( ! is_wp_error( $updated ) && ! empty( $updated['client_secret'] ) ) {
				$order->update_meta_data( '_snf_payment_intent', (string) $updated['id'] );
				$order->update_meta_data( '_snf_payment_intent_secret', (string) $updated['client_secret'] );
				$order->save();
				return $updated;
			}
			// Fall through to create a new PI if update failed (e.g. already succeeded).
		}

		$created = SNF_Payments_Stripe_API::request( 'POST', 'payment_intents', $body );
		if ( is_wp_error( $created ) ) {
			return $created;
		}
		if ( empty( $created['id'] ) || empty( $created['client_secret'] ) ) {
			return new WP_Error( 'snf_payments_no_pi', __( 'Stripe did not return a PaymentIntent.', 'snf-payments' ) );
		}

		$order->update_meta_data( '_snf_payment_intent', (string) $created['id'] );
		$order->update_meta_data( '_snf_payment_intent_secret', (string) $created['client_secret'] );
		$order->save();

		return $created;
	}

	/**
	 * Mark order paid from a succeeded PaymentIntent.
	 *
	 * @param WC_Order             $order Order.
	 * @param array<string, mixed> $intent PaymentIntent payload.
	 * @return bool
	 */
	public static function complete_order_from_intent( WC_Order $order, array $intent ) {
		if ( $order->is_paid() ) {
			return true;
		}

		$status = isset( $intent['status'] ) ? (string) $intent['status'] : '';
		if ( 'succeeded' !== $status && 'processing' !== $status ) {
			return false;
		}

		$txn = isset( $intent['id'] ) ? sanitize_text_field( (string) $intent['id'] ) : '';
		$order->payment_complete( $txn );
		$order->add_order_note(
			sprintf(
				/* translators: %s: PaymentIntent id */
				__( 'SUPER NINETY FOUR: Stripe PaymentIntent %s succeeded.', 'snf-payments' ),
				$txn
			)
		);
		return true;
	}

	/**
	 * Resolve order from request (order_id + order_key).
	 *
	 * @return WC_Order|\WP_Error
	 */
	protected static function order_from_request() {
		$order_id  = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$order_key = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $order_id ) {
			return new WP_Error( 'snf_payments_no_order', __( 'Missing order.', 'snf-payments' ), array( 'status' => 400 ) );
		}

		// The order key is the per-order bearer secret. It is mandatory: without it
		// any known order ID could be used to create or complete a payment intent.
		if ( ! $order_key ) {
			return new WP_Error( 'snf_payments_bad_order', __( 'Invalid order.', 'snf-payments' ), array( 'status' => 403 ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! hash_equals( (string) $order->get_order_key(), $order_key ) ) {
			return new WP_Error( 'snf_payments_bad_order', __( 'Invalid order.', 'snf-payments' ), array( 'status' => 403 ) );
		}

		// Registered customers must also own the order; guest orders rely on the key.
		$customer_id = (int) $order->get_customer_id();
		if ( $customer_id && (int) get_current_user_id() !== $customer_id && ! current_user_can( 'edit_shop_orders', $order_id ) ) {
			return new WP_Error( 'snf_payments_bad_order', __( 'Invalid order.', 'snf-payments' ), array( 'status' => 403 ) );
		}

		return $order;
	}

	/**
	 * Create / refresh a PaymentIntent from the current cart (pre-order Elements mount).
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function create_from_cart() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return new WP_Error( 'snf_payments_no_cart', __( 'Cart is unavailable.', 'snf-payments' ) );
		}
		if ( ! SNF_Payments_Stripe_API::is_ready() ) {
			return new WP_Error( 'snf_payments_not_ready', __( 'Stripe is not configured.', 'snf-payments' ) );
		}

		WC()->cart->calculate_totals();
		$currency = strtolower( get_woocommerce_currency() );
		$raw      = isset( WC()->cart->total ) ? (float) WC()->cart->total : (float) WC()->cart->get_total( 'edit' );
		$amount   = SNF_Payments_Stripe_API::amount_to_cents( $raw, $currency );
		if ( $amount < 1 ) {
			return new WP_Error( 'snf_payments_zero', __( 'Cart total must be greater than zero.', 'snf-payments' ) );
		}

		$existing = '';
		if ( WC()->session ) {
			$existing = (string) WC()->session->get( 'snf_payments_pi_id' );
		}

		$body = array(
			'amount'                    => $amount,
			'currency'                  => $currency,
			'metadata'                  => array(
				'source'    => 'snf_payments_cart',
				'cart_hash' => (string) WC()->cart->get_cart_hash(),
			),
			'automatic_payment_methods' => array(
				'enabled' => 'true',
			),
		);

		if ( $existing ) {
			$updated = SNF_Payments_Stripe_API::request(
				'POST',
				'payment_intents/' . rawurlencode( $existing ),
				array(
					'amount'   => $amount,
					'currency' => $currency,
					'metadata' => $body['metadata'],
				)
			);
			if ( ! is_wp_error( $updated ) && ! empty( $updated['client_secret'] ) ) {
				if ( WC()->session ) {
					WC()->session->set( 'snf_payments_pi_id', (string) $updated['id'] );
					WC()->session->set( 'snf_payments_pi_secret', (string) $updated['client_secret'] );
				}
				return $updated;
			}
		}

		$created = SNF_Payments_Stripe_API::request( 'POST', 'payment_intents', $body );
		if ( is_wp_error( $created ) ) {
			return $created;
		}
		if ( empty( $created['id'] ) || empty( $created['client_secret'] ) ) {
			return new WP_Error( 'snf_payments_no_pi', __( 'Stripe did not return a PaymentIntent.', 'snf-payments' ) );
		}

		if ( WC()->session ) {
			WC()->session->set( 'snf_payments_pi_id', (string) $created['id'] );
			WC()->session->set( 'snf_payments_pi_secret', (string) $created['client_secret'] );
		}

		return $created;
	}

	/**
	 * Attach session PaymentIntent to an order (or create a new one).
	 *
	 * @param WC_Order $order Order.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function attach_or_create_for_order( WC_Order $order ) {
		$session_pi = ( WC()->session ) ? (string) WC()->session->get( 'snf_payments_pi_id' ) : '';
		if ( $session_pi ) {
			$order->update_meta_data( '_snf_payment_intent', $session_pi );
			$order->save();
		}
		return self::create_for_order( $order );
	}

	/**
	 * AJAX: prepare PaymentIntent from cart for Elements mount.
	 */
	public static function ajax_prepare_from_cart() {
		check_ajax_referer( 'snf-payments-checkout', 'security' );

		$intent = self::create_from_cart();
		if ( is_wp_error( $intent ) ) {
			wp_send_json_error( array( 'message' => $intent->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'payment_intent_id' => (string) $intent['id'],
				'client_secret'     => (string) $intent['client_secret'],
				'publishable_key'   => SNF_Payments_Stripe_API::publishable_key(),
			)
		);
	}

	/**
	 * AJAX: create/update PaymentIntent after order is created.
	 */
	public static function ajax_create() {
		check_ajax_referer( 'snf-payments-checkout', 'security' );

		$order = self::order_from_request();
		if ( is_wp_error( $order ) ) {
			wp_send_json_error( array( 'message' => $order->get_error_message() ), 400 );
		}

		$intent = self::attach_or_create_for_order( $order );
		if ( is_wp_error( $intent ) ) {
			wp_send_json_error( array( 'message' => $intent->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'payment_intent_id' => (string) $intent['id'],
				'client_secret'     => (string) $intent['client_secret'],
				'publishable_key'   => SNF_Payments_Stripe_API::publishable_key(),
				'return_url'        => $order->get_checkout_order_received_url(),
			)
		);
	}

	/**
	 * AJAX: verify intent status and complete order (fallback if webhook lags).
	 */
	public static function ajax_confirm() {
		check_ajax_referer( 'snf-payments-checkout', 'security' );

		$order = self::order_from_request();
		if ( is_wp_error( $order ) ) {
			wp_send_json_error( array( 'message' => $order->get_error_message() ), 400 );
		}

		$pi_id = isset( $_POST['payment_intent_id'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_intent_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $pi_id ) {
			$pi_id = (string) $order->get_meta( '_snf_payment_intent' );
		}
		if ( ! $pi_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing PaymentIntent.', 'snf-payments' ) ), 400 );
		}

		$intent = SNF_Payments_Stripe_API::request( 'GET', 'payment_intents/' . rawurlencode( $pi_id ) );
		if ( is_wp_error( $intent ) ) {
			wp_send_json_error( array( 'message' => $intent->get_error_message() ), 400 );
		}

		// Bind the intent to this order. Without this check a valid, paid intent
		// from one order could be replayed to mark a different (unpaid) order paid.
		$intent_meta  = isset( $intent['metadata'] ) && is_array( $intent['metadata'] ) ? $intent['metadata'] : array();
		$intent_order = isset( $intent_meta['order_id'] ) ? absint( $intent_meta['order_id'] ) : 0;
		$intent_key   = isset( $intent_meta['order_key'] ) ? (string) $intent_meta['order_key'] : '';
		$stored_pi    = (string) $order->get_meta( '_snf_payment_intent' );

		$belongs = ( $intent_order && $intent_order === $order->get_id() )
			|| ( ! $intent_order && $stored_pi && hash_equals( $stored_pi, $pi_id ) );
		if ( ! $belongs ) {
			wp_send_json_error( array( 'message' => __( 'Payment intent does not belong to this order.', 'snf-payments' ) ), 403 );
		}
		if ( $intent_key && ! hash_equals( (string) $order->get_order_key(), $intent_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Payment intent does not belong to this order.', 'snf-payments' ) ), 403 );
		}

		$ok = self::complete_order_from_intent( $order, $intent );
		if ( ! $ok ) {
			$status = isset( $intent['status'] ) ? (string) $intent['status'] : 'unknown';
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: Stripe status */
						__( 'Payment not complete yet (status: %s).', 'snf-payments' ),
						$status
					),
					'status'  => $status,
				),
				400
			);
		}

		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}

		wp_send_json_success(
			array(
				'redirect' => $order->get_checkout_order_received_url(),
			)
		);
	}
}
