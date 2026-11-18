<?php
/**
 * Stripe webhook for SNF Payments gateway.
 *
 * @package SNF_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST webhook handler.
 */
class SNF_Payments_Webhook {

	/**
	 * Register routes.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'snf-payments/v1',
			'/stripe/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle Stripe webhook.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle( WP_REST_Request $request ) {
		$payload = $request->get_body();
		$sig     = $request->get_header( 'stripe-signature' );
		$secret  = SNF_Payments_Stripe_API::webhook_secret();

		// Fail closed: never trust an unsigned or unconfigured webhook. Without this,
		// anyone could POST a forged payment_intent.succeeded and mark orders paid.
		if ( ! $secret ) {
			return new WP_Error( 'snf_payments_no_whsec', __( 'Webhook secret not configured.', 'snf-payments' ), array( 'status' => 500 ) );
		}
		if ( ! $sig ) {
			return new WP_Error( 'snf_payments_sig', __( 'Missing Stripe signature.', 'snf-payments' ), array( 'status' => 400 ) );
		}

		$event = null;

		if ( function_exists( 'snf_stripe_verify_webhook_signature' ) ) {
			$verified = snf_stripe_verify_webhook_signature( $payload, $sig, $secret );
			if ( is_wp_error( $verified ) ) {
				return new WP_Error( 'snf_payments_sig', $verified->get_error_message(), array( 'status' => 400 ) );
			}
			$decoded = json_decode( $payload, true );
			if ( ! is_array( $decoded ) ) {
				return new WP_Error( 'snf_payments_payload', __( 'Invalid webhook payload.', 'snf-payments' ), array( 'status' => 400 ) );
			}
			$event = $decoded;
		} elseif ( class_exists( '\Stripe\Webhook' ) ) {
			try {
				$event = \Stripe\Webhook::constructEvent( $payload, $sig, $secret );
				if ( is_object( $event ) && method_exists( $event, 'toArray' ) ) {
					$event = $event->toArray();
				} else {
					$event = (array) $event;
				}
			} catch ( \Throwable $e ) {
				return new WP_Error( 'snf_payments_sig', $e->getMessage(), array( 'status' => 400 ) );
			}
		} else {
			return new WP_Error( 'snf_payments_no_verifier', __( 'Webhook signature verification unavailable.', 'snf-payments' ), array( 'status' => 500 ) );
		}

		$type     = isset( $event['type'] ) ? (string) $event['type'] : '';
		$event_id = isset( $event['id'] ) ? sanitize_text_field( (string) $event['id'] ) : '';
		$data     = isset( $event['data']['object'] ) && is_array( $event['data']['object'] ) ? $event['data']['object'] : array();

		if ( $event_id && get_transient( 'snf_payments_evt_' . $event_id ) ) {
			return new WP_REST_Response(
				array(
					'received'  => true,
					'duplicate' => true,
					'type'      => $type,
				),
				200
			);
		}

		$result = array( 'handled' => false, 'type' => $type );

		switch ( $type ) {
			case 'payment_intent.succeeded':
			case 'payment_intent.processing':
				$result = self::handle_payment_intent( $data );
				break;
			case 'payment_intent.payment_failed':
				$result = self::handle_payment_failed( $data );
				break;
			case 'charge.refunded':
				$result = self::handle_charge_refunded( $data );
				break;
			default:
				$result['ignored'] = true;
				break;
		}

		if ( $event_id ) {
			set_transient( 'snf_payments_evt_' . $event_id, 1, WEEK_IN_SECONDS );
		}

		/**
		 * Fires after an SNF Payments Stripe webhook is processed.
		 *
		 * @param array              $event  Decoded event.
		 * @param array<string,mixed> $result Handler result.
		 */
		do_action( 'snf_payments_webhook_handled', $event, $result );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Find order by PaymentIntent metadata or meta.
	 *
	 * @param array<string, mixed> $intent Intent.
	 * @return WC_Order|null
	 */
	protected static function order_from_intent( array $intent ) {
		$meta = isset( $intent['metadata'] ) && is_array( $intent['metadata'] ) ? $intent['metadata'] : array();
		if ( ! empty( $meta['order_id'] ) ) {
			$order = wc_get_order( (int) $meta['order_id'] );
			if ( $order ) {
				return $order;
			}
		}

		$pi = isset( $intent['id'] ) ? (string) $intent['id'] : '';
		if ( ! $pi ) {
			return null;
		}

		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => '_snf_payment_intent', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $pi, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return $orders ? $orders[0] : null;
	}

	/**
	 * Complete order on succeeded PI.
	 *
	 * @param array<string, mixed> $intent Intent.
	 * @return array<string, mixed>
	 */
	protected static function handle_payment_intent( array $intent ) {
		$order = self::order_from_intent( $intent );
		if ( ! $order ) {
			return array( 'handled' => false, 'reason' => 'no_order' );
		}
		SNF_Payments_Payment_Intent::complete_order_from_intent( $order, $intent );
		return array(
			'handled'  => true,
			'order_id' => $order->get_id(),
		);
	}

	/**
	 * Note failed payment.
	 *
	 * @param array<string, mixed> $intent Intent.
	 * @return array<string, mixed>
	 */
	protected static function handle_payment_failed( array $intent ) {
		$order = self::order_from_intent( $intent );
		if ( ! $order ) {
			return array( 'handled' => false, 'reason' => 'no_order' );
		}
		$message = isset( $intent['last_payment_error']['message'] )
			? (string) $intent['last_payment_error']['message']
			: __( 'Payment failed.', 'snf-payments' );
		$order->add_order_note( 'SUPER NINETY FOUR: ' . $message );
		return array(
			'handled'  => true,
			'order_id' => $order->get_id(),
		);
	}

	/**
	 * Mark refund on charge.refunded when fully refunded.
	 *
	 * @param array<string, mixed> $charge Charge.
	 * @return array<string, mixed>
	 */
	protected static function handle_charge_refunded( array $charge ) {
		$pi = isset( $charge['payment_intent'] ) ? (string) $charge['payment_intent'] : '';
		if ( ! $pi ) {
			return array( 'handled' => false, 'reason' => 'no_pi' );
		}

		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => '_snf_payment_intent', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $pi, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		if ( ! $orders ) {
			return array( 'handled' => false, 'reason' => 'no_order' );
		}

		$order = $orders[0];
		if ( ! empty( $charge['refunded'] ) && $order->get_status() !== 'refunded' ) {
			$order->update_status( 'refunded', __( 'SUPER NINETY FOUR: Fully refunded via Stripe webhook.', 'snf-payments' ) );
		}

		if ( ! empty( $charge['refunded'] ) ) {
			/**
			 * Fires when an SNF Payments order is fully refunded via Stripe.
			 * Theme listens to end club membership when the order carried dues.
			 *
			 * @param int      $order_id Order ID.
			 * @param WC_Order $order    Order.
			 */
			do_action( 'snf_payments_order_fully_refunded', $order->get_id(), $order );
		}

		return array(
			'handled'  => true,
			'order_id' => $order->get_id(),
		);
	}
}
