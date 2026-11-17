<?php
/**
 * Stripe integration: settings, Checkout Sessions, and custom webhook endpoint.
 *
 * Webhook URL: /wp-json/snf/v1/stripe/webhook
 * No paid Stripe plugins required — uses Stripe HTTP API + signature verify.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Option key for Stripe settings array. */
const SNF_STRIPE_OPTION = 'snf_stripe_settings';

/**
 * Default Stripe settings shape.
 *
 * @return array<string, string>
 */
function snf_stripe_default_settings() {
	return array(
		'enabled'           => 'no',
		'mode'              => 'test',
		'publishable_key'   => '',
		'secret_key'        => '',
		'webhook_secret'    => '',
		'test_publishable'  => '',
		'test_secret'       => '',
		'test_webhook'      => '',
		'prefer_supabase'   => 'yes',
	);
}

/**
 * Merged Stripe settings (constants override options when defined).
 *
 * @return array<string, string>
 */
function snf_get_stripe_settings() {
	$stored = get_option( SNF_STRIPE_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	$settings = array_merge( snf_stripe_default_settings(), $stored );

	if ( defined( 'SNF_STRIPE_MODE' ) && SNF_STRIPE_MODE ) {
		$settings['mode'] = ( 'live' === SNF_STRIPE_MODE ) ? 'live' : 'test';
	}
	if ( defined( 'SNF_STRIPE_PUBLISHABLE_KEY' ) && SNF_STRIPE_PUBLISHABLE_KEY ) {
		$settings['publishable_key'] = (string) SNF_STRIPE_PUBLISHABLE_KEY;
	}
	if ( defined( 'SNF_STRIPE_SECRET_KEY' ) && SNF_STRIPE_SECRET_KEY ) {
		$settings['secret_key'] = (string) SNF_STRIPE_SECRET_KEY;
	}
	if ( defined( 'SNF_STRIPE_WEBHOOK_SECRET' ) && SNF_STRIPE_WEBHOOK_SECRET ) {
		$settings['webhook_secret'] = (string) SNF_STRIPE_WEBHOOK_SECRET;
	}
	if ( defined( 'SNF_STRIPE_TEST_PUBLISHABLE_KEY' ) && SNF_STRIPE_TEST_PUBLISHABLE_KEY ) {
		$settings['test_publishable'] = (string) SNF_STRIPE_TEST_PUBLISHABLE_KEY;
	}
	if ( defined( 'SNF_STRIPE_TEST_SECRET_KEY' ) && SNF_STRIPE_TEST_SECRET_KEY ) {
		$settings['test_secret'] = (string) SNF_STRIPE_TEST_SECRET_KEY;
	}
	if ( defined( 'SNF_STRIPE_TEST_WEBHOOK_SECRET' ) && SNF_STRIPE_TEST_WEBHOOK_SECRET ) {
		$settings['test_webhook'] = (string) SNF_STRIPE_TEST_WEBHOOK_SECRET;
	}

	return $settings;
}

/**
 * Whether Stripe is enabled and has a secret key for the active mode.
 *
 * @return bool
 */
function snf_stripe_is_ready() {
	$s = snf_get_stripe_settings();
	if ( 'yes' !== $s['enabled'] ) {
		return false;
	}
	return (bool) snf_stripe_get_secret_key();
}

/**
 * Active-mode secret key.
 *
 * @return string
 */
function snf_stripe_get_secret_key() {
	$s = snf_get_stripe_settings();
	if ( 'live' === $s['mode'] ) {
		return (string) $s['secret_key'];
	}
	return (string) ( $s['test_secret'] ? $s['test_secret'] : $s['secret_key'] );
}

/**
 * Active-mode publishable key.
 *
 * @return string
 */
function snf_stripe_get_publishable_key() {
	$s = snf_get_stripe_settings();
	if ( 'live' === $s['mode'] ) {
		return (string) $s['publishable_key'];
	}
	return (string) ( $s['test_publishable'] ? $s['test_publishable'] : $s['publishable_key'] );
}

/**
 * Active-mode webhook signing secret.
 *
 * @return string
 */
function snf_stripe_get_webhook_secret() {
	$s = snf_get_stripe_settings();
	if ( 'live' === $s['mode'] ) {
		return (string) $s['webhook_secret'];
	}
	return (string) ( $s['test_webhook'] ? $s['test_webhook'] : $s['webhook_secret'] );
}

/**
 * Public webhook endpoint URL.
 *
 * @return string
 */
function snf_stripe_webhook_url() {
	return rest_url( 'snf/v1/stripe/webhook' );
}

/**
 * Stripe API request helper.
 *
 * @param string               $method GET|POST|DELETE.
 * @param string               $path   Path after /v1/.
 * @param array<string, mixed> $body   Form body for POST.
 * @return array|WP_Error Decoded JSON or error.
 */
function snf_stripe_request( $method, $path, $body = array() ) {
	$secret = snf_stripe_get_secret_key();
	if ( ! $secret ) {
		return new WP_Error( 'snf_stripe_no_key', __( 'Stripe secret key is not configured.', 'super-ninety-four' ) );
	}

	$url  = 'https://api.stripe.com/v1/' . ltrim( $path, '/' );
	$args = array(
		'method'  => strtoupper( (string) $method ),
		'timeout' => 45,
		'headers' => array(
			'Authorization' => 'Bearer ' . $secret,
			'Stripe-Version'=> '2024-06-20',
		),
	);

	if ( 'GET' !== $args['method'] && $body ) {
		$args['body'] = $body;
	} elseif ( 'GET' === $args['method'] && $body ) {
		$url = add_query_arg( $body, $url );
	}

	$response = wp_remote_request( $url, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'snf_stripe_bad_json', __( 'Invalid Stripe API response.', 'super-ninety-four' ) );
	}
	if ( $code >= 400 ) {
		$message = isset( $data['error']['message'] ) ? (string) $data['error']['message'] : __( 'Stripe API error.', 'super-ninety-four' );
		return new WP_Error( 'snf_stripe_api', $message, array( 'status' => $code, 'body' => $data ) );
	}

	return $data;
}

/**
 * Verify Stripe-Signature header against raw payload.
 *
 * @param string $payload   Raw request body.
 * @param string $sig_header Stripe-Signature header.
 * @param string $secret    whsec_… secret.
 * @param int    $tolerance Seconds.
 * @return true|WP_Error
 */
function snf_stripe_verify_webhook_signature( $payload, $sig_header, $secret, $tolerance = 300 ) {
	if ( ! $secret ) {
		return new WP_Error( 'snf_stripe_no_whsec', __( 'Webhook signing secret is not configured.', 'super-ninety-four' ), array( 'status' => 500 ) );
	}
	if ( ! $sig_header ) {
		return new WP_Error( 'snf_stripe_no_sig', __( 'Missing Stripe-Signature header.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$parts = array();
	foreach ( explode( ',', $sig_header ) as $piece ) {
		$kv = explode( '=', trim( $piece ), 2 );
		if ( 2 === count( $kv ) ) {
			$parts[ $kv[0] ][] = $kv[1];
		}
	}

	if ( empty( $parts['t'][0] ) || empty( $parts['v1'] ) ) {
		return new WP_Error( 'snf_stripe_bad_sig', __( 'Malformed Stripe-Signature header.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$timestamp = (int) $parts['t'][0];
	if ( abs( time() - $timestamp ) > $tolerance ) {
		return new WP_Error( 'snf_stripe_stale', __( 'Stripe webhook timestamp outside tolerance.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$signed   = $timestamp . '.' . $payload;
	$expected = hash_hmac( 'sha256', $signed, $secret );
	$matched  = false;
	foreach ( $parts['v1'] as $candidate ) {
		if ( hash_equals( $expected, $candidate ) ) {
			$matched = true;
			break;
		}
	}

	if ( ! $matched ) {
		return new WP_Error( 'snf_stripe_sig_mismatch', __( 'Stripe webhook signature mismatch.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	return true;
}

/**
 * Register REST routes.
 */
function snf_stripe_register_rest_routes() {
	register_rest_route(
		'snf/v1',
		'/stripe/webhook',
		array(
			'methods'             => 'POST',
			'callback'            => 'snf_stripe_rest_webhook',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'snf/v1',
		'/stripe/checkout',
		array(
			'methods'             => 'POST',
			'callback'            => 'snf_stripe_rest_create_checkout',
			'permission_callback' => function () {
				return is_user_logged_in();
			},
			'args'                => array(
				'plan'      => array(
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_title',
				),
				'program'   => array(
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_title',
				),
				'sku'       => array(
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_title',
					'description'       => 'Stripe Sync Engine SKU (e.g. application, add-on).',
				),
				'initiation'=> array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);

	register_rest_route(
		'snf/v1',
		'/stripe/status',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_stripe_rest_status',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		)
	);
}
add_action( 'rest_api_init', 'snf_stripe_register_rest_routes' );

/**
 * Admin status payload (no secrets).
 *
 * @return WP_REST_Response
 */
function snf_stripe_rest_status() {
	$s          = snf_get_stripe_settings();
	$catalog    = function_exists( 'snf_supabase_get_stripe_catalog' ) ? snf_supabase_get_stripe_catalog() : array();
	$catalog_ok = is_array( $catalog ) && ! is_wp_error( $catalog );

	return rest_ensure_response(
		array(
			'enabled'          => ( 'yes' === $s['enabled'] ),
			'mode'             => $s['mode'],
			'ready'            => snf_stripe_is_ready(),
			'has_secret'       => (bool) snf_stripe_get_secret_key(),
			'has_publishable'  => (bool) snf_stripe_get_publishable_key(),
			'has_webhook'      => (bool) snf_stripe_get_webhook_secret(),
			'webhook_url'      => snf_stripe_webhook_url(),
			'supabase'         => array(
				'ready'            => function_exists( 'snf_supabase_payments_is_ready' ) && snf_supabase_payments_is_ready(),
				'has_service_role' => function_exists( 'snf_supabase_payments_has_service_role' ) && snf_supabase_payments_has_service_role(),
				'catalog_count'    => $catalog_ok ? count( $catalog ) : 0,
				'catalog_error'    => is_wp_error( $catalog ) ? $catalog->get_error_message() : '',
				'prefer'           => ! empty( $s['prefer_supabase'] ) && 'yes' === $s['prefer_supabase'],
			),
		)
	);
}

/**
 * Webhook receiver.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_stripe_rest_webhook( WP_REST_Request $request ) {
	$payload    = $request->get_body();
	$sig_header = (string) $request->get_header( 'stripe_signature' );
	if ( ! $sig_header ) {
		$sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? (string) $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
	}

	$verified = snf_stripe_verify_webhook_signature( $payload, $sig_header, snf_stripe_get_webhook_secret() );
	if ( is_wp_error( $verified ) ) {
		return $verified;
	}

	$event = json_decode( $payload, true );
	if ( ! is_array( $event ) || empty( $event['id'] ) || empty( $event['type'] ) ) {
		return new WP_Error( 'snf_stripe_bad_event', __( 'Invalid Stripe event payload.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$event_id = sanitize_text_field( (string) $event['id'] );
	if ( get_transient( 'snf_stripe_evt_' . $event_id ) ) {
		return rest_ensure_response( array( 'received' => true, 'duplicate' => true ) );
	}

	$result = snf_stripe_handle_event( $event );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	set_transient( 'snf_stripe_evt_' . $event_id, 1, WEEK_IN_SECONDS );

	/**
	 * Fires after a verified Stripe webhook is handled.
	 *
	 * @param array $event  Stripe event.
	 * @param mixed $result Handler result.
	 */
	do_action( 'snf_stripe_webhook_handled', $event, $result );

	return rest_ensure_response(
		array(
			'received' => true,
			'type'     => $event['type'],
			'result'   => $result,
		)
	);
}

/**
 * Dispatch Stripe event types.
 *
 * @param array<string, mixed> $event Event.
 * @return array<string, mixed>|WP_Error
 */
function snf_stripe_handle_event( array $event ) {
	$type = (string) $event['type'];
	$obj  = isset( $event['data']['object'] ) && is_array( $event['data']['object'] ) ? $event['data']['object'] : array();

	switch ( $type ) {
		case 'checkout.session.completed':
		case 'checkout.session.async_payment_succeeded':
			return snf_stripe_handle_checkout_session_completed( $obj );

		case 'payment_intent.succeeded':
			return snf_stripe_handle_payment_intent_succeeded( $obj );

		case 'charge.refunded':
			return snf_stripe_handle_charge_refunded( $obj );

		default:
			return array( 'ignored' => true, 'type' => $type );
	}
}

/**
 * Activate membership / reciprocal from Checkout Session metadata.
 *
 * @param array<string, mixed> $session Session object.
 * @return array<string, mixed>
 */
function snf_stripe_handle_checkout_session_completed( array $session ) {
	$meta    = isset( $session['metadata'] ) && is_array( $session['metadata'] ) ? $session['metadata'] : array();
	$user_id = isset( $meta['user_id'] ) ? (int) $meta['user_id'] : 0;
	$plan    = isset( $meta['plan'] ) ? sanitize_title( (string) $meta['plan'] ) : '';
	$program = isset( $meta['program'] ) ? sanitize_title( (string) $meta['program'] ) : '';
	$init    = ! empty( $meta['initiation'] ) && in_array( (string) $meta['initiation'], array( '1', 'yes', 'true' ), true );

	if ( $user_id <= 0 && ! empty( $session['client_reference_id'] ) ) {
		$user_id = (int) $session['client_reference_id'];
	}
	if ( $user_id <= 0 && ! empty( $session['customer_email'] ) ) {
		$user = get_user_by( 'email', sanitize_email( (string) $session['customer_email'] ) );
		if ( $user ) {
			$user_id = (int) $user->ID;
		}
	}

	$out = array(
		'user_id' => $user_id,
		'plan'    => $plan,
		'program' => $program,
		'actions' => array(),
	);

	if ( $user_id <= 0 ) {
		$out['actions'][] = 'no_user';
		return $out;
	}

	$sku = isset( $meta['sku'] ) ? sanitize_title( (string) $meta['sku'] ) : '';
	$out['sku'] = $sku;

	if ( 'application' === $sku ) {
		update_user_meta( $user_id, 'snf_application_paid', 1 );
		update_user_meta( $user_id, 'snf_application_paid_at', time() );
		update_user_meta( $user_id, 'snf_application_checkout_session', sanitize_text_field( (string) ( $session['id'] ?? '' ) ) );
		if ( $plan ) {
			update_user_meta( $user_id, 'snf_pending_membership_plan', $plan );
		}
		if ( function_exists( 'snf_assign_club_role' ) ) {
			$user = get_userdata( $user_id );
			$skip = array( 'administrator', 'snf_staff', 'snf_reciprocal', 'snf_member' );
			if ( $user && ! array_intersect( $skip, (array) $user->roles ) ) {
				snf_assign_club_role( $user_id, 'snf_applicant' );
			}
		}
		$out['actions'][] = 'application_paid';
	}

	if ( $init && defined( 'SNF_META_INITIATION' ) ) {
		update_user_meta( $user_id, SNF_META_INITIATION, 1 );
		$out['actions'][] = 'initiation_paid';
	}

	if ( $plan && function_exists( 'snf_activate_membership' ) && 'application' !== $sku ) {
		snf_activate_membership(
			$user_id,
			$plan,
			array(
				'initiation_paid' => $init,
				'order_id'        => 0,
			)
		);
		update_user_meta( $user_id, 'snf_stripe_checkout_session', sanitize_text_field( (string) ( $session['id'] ?? '' ) ) );
		$out['actions'][] = 'membership_activated';
	}

	if ( $program && function_exists( 'snf_activate_reciprocal_subscription' ) ) {
		$product_id = 0;
		$product    = get_page_by_path( $program, OBJECT, 'product' );
		if ( $product ) {
			$product_id = (int) $product->ID;
		}
		snf_activate_reciprocal_subscription( $user_id, $program, $product_id, 0 );
		$out['actions'][] = 'reciprocal_activated';
	}

	// If this session is tied to a Woo order, mark paid / processing.
	if ( ! empty( $meta['order_id'] ) && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( (int) $meta['order_id'] );
		if ( $order && ! $order->is_paid() ) {
			$order->payment_complete( sanitize_text_field( (string) ( $session['payment_intent'] ?? $session['id'] ?? '' ) ) );
			$order->add_order_note( __( 'SUPER NINETY FOUR: Stripe Checkout session completed via webhook.', 'super-ninety-four' ) );
			$out['actions'][] = 'order_paid';
		}
	}

	return $out;
}

/**
 * Fallback: PaymentIntent metadata activation.
 *
 * @param array<string, mixed> $intent PaymentIntent.
 * @return array<string, mixed>
 */
function snf_stripe_handle_payment_intent_succeeded( array $intent ) {
	$meta = isset( $intent['metadata'] ) && is_array( $intent['metadata'] ) ? $intent['metadata'] : array();
	if ( empty( $meta['user_id'] ) ) {
		return array( 'ignored' => true, 'reason' => 'no_metadata' );
	}

	// Reuse checkout handler shape.
	return snf_stripe_handle_checkout_session_completed(
		array(
			'id'       => $intent['id'] ?? '',
			'metadata' => $meta,
		)
	);
}

/**
 * Optional refund handling — expire club membership when fully refunded.
 *
 * @param array<string, mixed> $charge Charge.
 * @return array<string, mixed>
 */
function snf_stripe_handle_charge_refunded( array $charge ) {
	if ( empty( $charge['refunded'] ) ) {
		return array( 'ignored' => true, 'reason' => 'partial_or_not_refunded' );
	}
	$meta    = isset( $charge['metadata'] ) && is_array( $charge['metadata'] ) ? $charge['metadata'] : array();
	$user_id = isset( $meta['user_id'] ) ? (int) $meta['user_id'] : 0;
	if ( $user_id <= 0 ) {
		return array( 'ignored' => true, 'reason' => 'no_user' );
	}
	if ( function_exists( 'snf_end_membership' ) ) {
		snf_end_membership( $user_id, 'cancelled' );
		return array( 'user_id' => $user_id, 'actions' => array( 'membership_cancelled' ) );
	}
	return array( 'ignored' => true, 'reason' => 'no_end_helper' );
}

/**
 * Create a Checkout Session for the synced "application" SKU ($97 4-week experience).
 *
 * @param int    $user_id   WP user ID.
 * @param string $plan_slug Preferred membership plan slug (metadata only).
 * @return string|WP_Error Checkout URL.
 */
function snf_stripe_create_application_checkout( $user_id, $plan_slug = '' ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return new WP_Error( 'snf_stripe_no_user', __( 'Sign in before paying the application fee.', 'super-ninety-four' ) );
	}
	if ( ! snf_stripe_is_ready() ) {
		return new WP_Error( 'snf_stripe_not_ready', __( 'Stripe is not connected yet.', 'super-ninety-four' ) );
	}
	if ( ! function_exists( 'snf_supabase_resolve_price_id' ) ) {
		return new WP_Error( 'snf_stripe_no_sync', __( 'Supabase Stripe catalog is unavailable.', 'super-ninety-four' ) );
	}

	$price_id = snf_supabase_resolve_price_id( 'application' );
	if ( ! $price_id ) {
		return new WP_Error( 'snf_stripe_no_app_price', __( 'Application price is not synced from Supabase yet.', 'super-ninety-four' ) );
	}

	$user    = get_userdata( $user_id );
	$success = add_query_arg(
		'snf_stripe',
		'success',
		function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'under-consideration' ) : home_url( '/' )
	);
	$cancel = add_query_arg(
		'snf_stripe',
		'cancel',
		function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'apply' ) : home_url( '/' )
	);

	$body = array(
		'mode'                 => 'payment',
		'success_url'          => $success,
		'cancel_url'           => $cancel,
		'client_reference_id'  => (string) $user_id,
		'customer_email'       => $user ? (string) $user->user_email : '',
		'line_items[0][price]' => $price_id,
		'line_items[0][quantity]' => 1,
		'metadata[user_id]'    => (string) $user_id,
		'metadata[plan]'       => sanitize_title( (string) $plan_slug ),
		'metadata[sku]'        => 'application',
		'metadata[source]'     => 'snf_apply',
	);

	$session = snf_stripe_request( 'POST', 'checkout/sessions', $body );
	if ( is_wp_error( $session ) ) {
		return $session;
	}
	if ( empty( $session['url'] ) ) {
		return new WP_Error( 'snf_stripe_no_url', __( 'Stripe did not return a Checkout URL.', 'super-ninety-four' ) );
	}

	update_user_meta( $user_id, 'snf_pending_application_session', sanitize_text_field( (string) $session['id'] ) );
	return (string) $session['url'];
}

/**
 * Create a Stripe Checkout Session for a club plan and/or reciprocal program.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_stripe_rest_create_checkout( WP_REST_Request $request ) {
	if ( ! snf_stripe_is_ready() ) {
		return new WP_Error( 'snf_stripe_not_ready', __( 'Stripe is not connected yet.', 'super-ninety-four' ), array( 'status' => 503 ) );
	}

	$user_id    = get_current_user_id();
	$plan       = (string) $request->get_param( 'plan' );
	$program    = (string) $request->get_param( 'program' );
	$sku        = sanitize_title( (string) $request->get_param( 'sku' ) );
	$initiation = (bool) $request->get_param( 'initiation' );
	$settings   = snf_get_stripe_settings();
	$use_sync   = ! empty( $settings['prefer_supabase'] ) && 'yes' === $settings['prefer_supabase'] && function_exists( 'snf_supabase_resolve_price_id' );

	if ( ! $plan && ! $program && ! $sku ) {
		return new WP_Error( 'snf_stripe_no_target', __( 'Provide a plan, reciprocal program, or Stripe SKU slug.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$line_items = array();
	$currency   = function_exists( 'get_woocommerce_currency' ) ? strtolower( get_woocommerce_currency() ) : 'usd';

	/**
	 * Prefer a synced Stripe Price ID; fall back to ad-hoc price_data.
	 *
	 * @param string               $slug Resolve key.
	 * @param array<string, mixed> $fallback price_data payload when unresolved.
	 */
	$push_line = function ( $slug, $fallback ) use ( &$line_items, $use_sync ) {
		$price_id = $use_sync ? snf_supabase_resolve_price_id( $slug ) : '';
		if ( $price_id ) {
			$line_items[] = array(
				'quantity' => 1,
				'price'    => $price_id,
			);
			return;
		}
		$line_items[] = array(
			'quantity'   => 1,
			'price_data' => $fallback,
		);
	};

	if ( $sku ) {
		$price_id = $use_sync ? snf_supabase_resolve_price_id( $sku ) : '';
		if ( ! $price_id ) {
			return new WP_Error( 'snf_stripe_bad_sku', __( 'Unknown Stripe SKU in Supabase catalog.', 'super-ninety-four' ), array( 'status' => 400 ) );
		}
		$line_items[] = array(
			'quantity' => 1,
			'price'    => $price_id,
		);
	}

	if ( $plan && function_exists( 'snf_get_membership_plan_by_slug' ) ) {
		$plan_row = snf_get_membership_plan_by_slug( $plan );
		if ( ! $plan_row ) {
			return new WP_Error( 'snf_stripe_bad_plan', __( 'Unknown membership plan.', 'super-ninety-four' ), array( 'status' => 400 ) );
		}
		$push_line(
			$plan,
			array(
				'currency'     => $currency,
				'unit_amount'  => (int) round( ( (float) $plan_row['amount_raw'] ) * 100 ),
				'product_data' => array(
					'name' => $plan_row['name'],
				),
			)
		);

		if ( $initiation && function_exists( 'snf_get_membership_plan_definitions' ) ) {
			$already = (bool) get_user_meta( $user_id, defined( 'SNF_META_INITIATION' ) ? SNF_META_INITIATION : 'snf_initiation_paid', true );
			if ( ! $already ) {
				$init = snf_get_membership_plan_definitions()['initiation_fee'];
				$push_line(
					'initiation',
					array(
						'currency'     => $currency,
						'unit_amount'  => (int) round( ( (float) $init['amount_raw'] ) * 100 ),
						'product_data' => array(
							'name' => $init['label'],
						),
					)
				);
			}
		}
	}

	if ( $program && function_exists( 'snf_get_reciprocal_program_catalog' ) ) {
		$catalog = snf_get_reciprocal_program_catalog();
		if ( empty( $catalog[ $program ] ) ) {
			return new WP_Error( 'snf_stripe_bad_program', __( 'Unknown reciprocal program.', 'super-ninety-four' ), array( 'status' => 400 ) );
		}
		$product = get_page_by_path( $program, OBJECT, 'product' );
		$amount  = $product ? (float) get_post_meta( $product->ID, '_price', true ) : 0;
		if ( $amount <= 0 && ! ( $use_sync && snf_supabase_resolve_price_id( $program ) ) ) {
			return new WP_Error( 'snf_stripe_program_price', __( 'Set a price on the reciprocal program product before Checkout.', 'super-ninety-four' ), array( 'status' => 400 ) );
		}
		$push_line(
			$program,
			array(
				'currency'     => $currency,
				'unit_amount'  => (int) round( $amount * 100 ),
				'product_data' => array(
					'name' => $catalog[ $program ]['title'],
				),
			)
		);
	}

	if ( ! $line_items ) {
		return new WP_Error( 'snf_stripe_no_lines', __( 'Nothing to charge.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$success = add_query_arg( 'snf_stripe', 'success', function_exists( 'snf_get_woo_account_url' ) ? snf_get_woo_account_url( 'membership' ) : home_url( '/' ) );
	$cancel  = add_query_arg( 'snf_stripe', 'cancel', function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'apply' ) : home_url( '/' ) );

	$body = array(
		'mode'                => 'payment',
		'success_url'         => $success,
		'cancel_url'          => $cancel,
		'client_reference_id' => (string) $user_id,
		'customer_email'      => wp_get_current_user()->user_email,
		'metadata[user_id]'   => (string) $user_id,
		'metadata[plan]'      => $plan,
		'metadata[program]'   => $program,
		'metadata[sku]'       => $sku,
		'metadata[initiation]'=> $initiation ? '1' : '0',
		'metadata[source]'    => 'snf_theme',
	);

	foreach ( $line_items as $i => $item ) {
		$body[ "line_items[{$i}][quantity]" ] = $item['quantity'];
		if ( ! empty( $item['price'] ) ) {
			$body[ "line_items[{$i}][price]" ] = $item['price'];
			continue;
		}
		$body[ "line_items[{$i}][price_data][currency]" ] = $item['price_data']['currency'];
		$body[ "line_items[{$i}][price_data][unit_amount]" ] = $item['price_data']['unit_amount'];
		$body[ "line_items[{$i}][price_data][product_data][name]" ] = $item['price_data']['product_data']['name'];
	}

	$session = snf_stripe_request( 'POST', 'checkout/sessions', $body );
	if ( is_wp_error( $session ) ) {
		return $session;
	}

	return rest_ensure_response(
		array(
			'id'  => $session['id'] ?? '',
			'url' => $session['url'] ?? '',
		)
	);
}

/**
 * Settings page under WooCommerce.
 */
function snf_stripe_admin_menu() {
	add_submenu_page(
		'woocommerce',
		__( 'SNF Stripe', 'super-ninety-four' ),
		__( 'SNF Stripe', 'super-ninety-four' ),
		'manage_woocommerce',
		'snf-stripe',
		'snf_stripe_render_settings_page'
	);
}
add_action( 'admin_menu', 'snf_stripe_admin_menu', 60 );

/**
 * Persist settings.
 */
function snf_stripe_handle_settings_save() {
	if ( ! isset( $_POST['snf_stripe_settings_nonce'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	check_admin_referer( 'snf_stripe_settings', 'snf_stripe_settings_nonce' );

	$current = snf_get_stripe_settings();
	$posted  = isset( $_POST['snf_stripe'] ) && is_array( $_POST['snf_stripe'] ) ? wp_unslash( $_POST['snf_stripe'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	$next = array(
		'enabled'          => ! empty( $posted['enabled'] ) ? 'yes' : 'no',
		'mode'             => ( isset( $posted['mode'] ) && 'live' === $posted['mode'] ) ? 'live' : 'test',
		'publishable_key'  => isset( $posted['publishable_key'] ) ? sanitize_text_field( $posted['publishable_key'] ) : $current['publishable_key'],
		'secret_key'       => isset( $posted['secret_key'] ) ? sanitize_text_field( $posted['secret_key'] ) : $current['secret_key'],
		'webhook_secret'   => isset( $posted['webhook_secret'] ) ? sanitize_text_field( $posted['webhook_secret'] ) : $current['webhook_secret'],
		'test_publishable' => isset( $posted['test_publishable'] ) ? sanitize_text_field( $posted['test_publishable'] ) : $current['test_publishable'],
		'test_secret'      => isset( $posted['test_secret'] ) ? sanitize_text_field( $posted['test_secret'] ) : $current['test_secret'],
		'test_webhook'     => isset( $posted['test_webhook'] ) ? sanitize_text_field( $posted['test_webhook'] ) : $current['test_webhook'],
		'prefer_supabase'  => ! empty( $posted['prefer_supabase'] ) ? 'yes' : 'no',
	);

	// Keep previous secret if the field was left blank (password-style UX).
	foreach ( array( 'secret_key', 'webhook_secret', 'test_secret', 'test_webhook' ) as $secret_field ) {
		if ( '' === $next[ $secret_field ] && ! empty( $current[ $secret_field ] ) ) {
			$next[ $secret_field ] = $current[ $secret_field ];
		}
	}

	update_option( SNF_STRIPE_OPTION, $next, false );

	// Supabase Stripe Sync Engine bridge.
	if ( isset( $_POST['snf_supabase'] ) && is_array( $_POST['snf_supabase'] ) && function_exists( 'snf_get_supabase_payments_settings' ) ) {
		$sb_current = snf_get_supabase_payments_settings();
		$sb_posted  = wp_unslash( $_POST['snf_supabase'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$sb_next    = array(
			'enabled'          => ! empty( $sb_posted['enabled'] ) ? 'yes' : 'no',
			'url'              => isset( $sb_posted['url'] ) ? esc_url_raw( (string) $sb_posted['url'] ) : $sb_current['url'],
			'anon_key'         => isset( $sb_posted['anon_key'] ) ? sanitize_text_field( (string) $sb_posted['anon_key'] ) : $sb_current['anon_key'],
			'service_role_key' => isset( $sb_posted['service_role_key'] ) ? sanitize_text_field( (string) $sb_posted['service_role_key'] ) : $sb_current['service_role_key'],
			'price_map'        => isset( $sb_posted['price_map'] ) ? sanitize_textarea_field( (string) $sb_posted['price_map'] ) : $sb_current['price_map'],
		);
		if ( '' === $sb_next['service_role_key'] && ! empty( $sb_current['service_role_key'] ) ) {
			$sb_next['service_role_key'] = $sb_current['service_role_key'];
		}
		if ( '' === $sb_next['anon_key'] && ! empty( $sb_current['anon_key'] ) ) {
			$sb_next['anon_key'] = $sb_current['anon_key'];
		}
		update_option( SNF_SUPABASE_PAYMENTS_OPTION, $sb_next, false );
		delete_transient( 'snf_supabase_stripe_catalog_v1' );
	}

	// Optional connectivity probe.
	$probe_message = '';
	if ( 'yes' === $next['enabled'] && snf_stripe_get_secret_key() ) {
		$probe = snf_stripe_request( 'GET', 'account' );
		if ( is_wp_error( $probe ) ) {
			$probe_message = '&stripe_probe=fail';
		} else {
			$probe_message = '&stripe_probe=ok';
			update_option(
				'snf_stripe_account_cache',
				array(
					'id'     => isset( $probe['id'] ) ? (string) $probe['id'] : '',
					'email'  => isset( $probe['email'] ) ? (string) $probe['email'] : '',
					'livemode' => ! empty( $probe['charges_enabled'] ),
					'checked'=> time(),
				),
				false
			);
		}
	}

	wp_safe_redirect( admin_url( 'admin.php?page=snf-stripe&updated=1' . $probe_message ) );
	exit;
}
add_action( 'admin_post_snf_stripe_save_settings', 'snf_stripe_handle_settings_save' );

/**
 * Settings UI.
 */
function snf_stripe_render_settings_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$s       = snf_get_stripe_settings();
	$sb      = function_exists( 'snf_get_supabase_payments_settings' ) ? snf_get_supabase_payments_settings() : array();
	$account = get_option( 'snf_stripe_account_cache', array() );
	$webhook = snf_stripe_webhook_url();
	$catalog = function_exists( 'snf_supabase_get_stripe_catalog' ) ? snf_supabase_get_stripe_catalog( true ) : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SUPER NINETY FOUR — Stripe', 'super-ninety-four' ); ?></h1>

		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Stripe settings saved.', 'super-ninety-four' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['stripe_probe'] ) && 'ok' === $_GET['stripe_probe'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Connected: Stripe API key accepted.', 'super-ninety-four' ); ?></p></div>
		<?php elseif ( isset( $_GET['stripe_probe'] ) && 'fail' === $_GET['stripe_probe'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Stripe API probe failed — check the secret key for the selected mode.', 'super-ninety-four' ); ?></p></div>
		<?php endif; ?>

		<p>
			<?php esc_html_e( 'Custom webhook endpoint (add this in Stripe Dashboard → Developers → Webhooks):', 'super-ninety-four' ); ?>
			<br>
			<code><?php echo esc_html( $webhook ); ?></code>
		</p>
		<p class="description">
			<?php esc_html_e( 'Events to send: checkout.session.completed, checkout.session.async_payment_succeeded, payment_intent.succeeded, charge.refunded.', 'super-ninety-four' ); ?>
		</p>

		<?php if ( ! empty( $account['id'] ) ) : ?>
			<p>
				<strong><?php esc_html_e( 'Last connected account', 'super-ninety-four' ); ?>:</strong>
				<code><?php echo esc_html( (string) $account['id'] ); ?></code>
				<?php if ( ! empty( $account['email'] ) ) : ?>
					— <?php echo esc_html( (string) $account['email'] ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="snf_stripe_save_settings">
			<?php wp_nonce_field( 'snf_stripe_settings', 'snf_stripe_settings_nonce' ); ?>

			<h2><?php esc_html_e( 'Supabase Stripe Sync (Data Management)', 'super-ninety-four' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Reads products/prices from your SUPER NINETY FOUR PAYMENTS Supabase project (stripe_sdp_data). Checkout still needs a Stripe secret key to create sessions; the sync supplies the catalog + status mirror.', 'super-ninety-four' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Use synced prices', 'super-ninety-four' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="snf_stripe[prefer_supabase]" value="1" <?php checked( ! empty( $s['prefer_supabase'] ) ? $s['prefer_supabase'] : 'yes', 'yes' ); ?>>
							<?php esc_html_e( 'Prefer Stripe Price IDs from Supabase Sync Engine when creating Checkout', 'super-ninety-four' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable bridge', 'super-ninety-four' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="snf_supabase[enabled]" value="1" <?php checked( isset( $sb['enabled'] ) ? $sb['enabled'] : 'yes', 'yes' ); ?>>
							<?php esc_html_e( 'Query Supabase for catalog / session lookups', 'super-ninety-four' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Project URL', 'super-ninety-four' ); ?></th>
					<td><input type="url" class="regular-text" name="snf_supabase[url]" value="<?php echo esc_attr( isset( $sb['url'] ) ? $sb['url'] : '' ); ?>" placeholder="https://evzjkydniknyfjftfutv.supabase.co"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Anon / publishable key', 'super-ninety-four' ); ?></th>
					<td><input type="password" class="large-text" name="snf_supabase[anon_key]" value="" placeholder="<?php echo ! empty( $sb['anon_key'] ) ? esc_attr__( '•••• saved — leave blank to keep', 'super-ninety-four' ) : ''; ?>" autocomplete="new-password"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Service role key (optional)', 'super-ninety-four' ); ?></th>
					<td>
						<input type="password" class="large-text" name="snf_supabase[service_role_key]" value="" placeholder="<?php echo ! empty( $sb['service_role_key'] ) ? esc_attr__( '•••• saved — leave blank to keep', 'super-ninety-four' ) : ''; ?>" autocomplete="new-password">
						<p class="description"><?php esc_html_e( 'Needed only for per-user checkout/subscription lookups. Keep server-side (or define SNF_SUPABASE_SERVICE_ROLE_KEY in wp-config.php).', 'super-ninety-four' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Price map JSON', 'super-ninety-four' ); ?></th>
					<td>
						<textarea class="large-text code" rows="4" name="snf_supabase[price_map]" placeholder='{"application":"price_…","add-on":"price_…"}'><?php echo esc_textarea( isset( $sb['price_map'] ) ? $sb['price_map'] : '' ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Optional explicit slug → price_… map. Without it, names like “4-Week Experience” / “Add-On” auto-match.', 'super-ninety-four' ); ?></p>
					</td>
				</tr>
			</table>

			<?php if ( is_wp_error( $catalog ) ) : ?>
				<div class="notice notice-warning inline"><p><?php echo esc_html( $catalog->get_error_message() ); ?></p></div>
			<?php elseif ( is_array( $catalog ) && $catalog ) : ?>
				<h3><?php esc_html_e( 'Synced catalog', 'super-ninety-four' ); ?></h3>
				<table class="widefat striped" style="max-width:960px">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Product', 'super-ninety-four' ); ?></th>
							<th><?php esc_html_e( 'Price ID', 'super-ninety-four' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'super-ninety-four' ); ?></th>
							<th><?php esc_html_e( 'Type', 'super-ninety-four' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $catalog as $row ) : ?>
							<tr>
								<td><?php echo esc_html( (string) ( $row['product_name'] ?? '' ) ); ?></td>
								<td><code><?php echo esc_html( (string) ( $row['price_id'] ?? '' ) ); ?></code></td>
								<td>
									<?php
									$cents = isset( $row['unit_amount'] ) ? (int) $row['unit_amount'] : 0;
									$cur   = isset( $row['currency'] ) ? strtoupper( (string) $row['currency'] ) : 'USD';
									echo esc_html( sprintf( '%s %0.2f', $cur, $cents / 100 ) );
									?>
								</td>
								<td><?php echo esc_html( (string) ( $row['price_type'] ?? '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Stripe API keys', 'super-ninety-four' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Stripe', 'super-ninety-four' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="snf_stripe[enabled]" value="1" <?php checked( $s['enabled'], 'yes' ); ?>>
							<?php esc_html_e( 'Accept Stripe Checkout + webhooks for club dues / reciprocal', 'super-ninety-four' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Mode', 'super-ninety-four' ); ?></th>
					<td>
						<select name="snf_stripe[mode]">
							<option value="test" <?php selected( $s['mode'], 'test' ); ?>><?php esc_html_e( 'Test', 'super-ninety-four' ); ?></option>
							<option value="live" <?php selected( $s['mode'], 'live' ); ?>><?php esc_html_e( 'Live', 'super-ninety-four' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Test publishable key', 'super-ninety-four' ); ?></th>
					<td><input type="text" class="regular-text" name="snf_stripe[test_publishable]" value="<?php echo esc_attr( $s['test_publishable'] ); ?>" autocomplete="off"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Test secret key', 'super-ninety-four' ); ?></th>
					<td><input type="password" class="regular-text" name="snf_stripe[test_secret]" value="" placeholder="<?php echo $s['test_secret'] ? esc_attr__( '•••• saved — leave blank to keep', 'super-ninety-four' ) : ''; ?>" autocomplete="new-password"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Test webhook secret', 'super-ninety-four' ); ?></th>
					<td><input type="password" class="regular-text" name="snf_stripe[test_webhook]" value="" placeholder="<?php echo $s['test_webhook'] ? esc_attr__( '•••• saved — leave blank to keep', 'super-ninety-four' ) : ''; ?>" autocomplete="new-password"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Live publishable key', 'super-ninety-four' ); ?></th>
					<td><input type="text" class="regular-text" name="snf_stripe[publishable_key]" value="<?php echo esc_attr( $s['publishable_key'] ); ?>" autocomplete="off"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Live secret key', 'super-ninety-four' ); ?></th>
					<td><input type="password" class="regular-text" name="snf_stripe[secret_key]" value="" placeholder="<?php echo $s['secret_key'] ? esc_attr__( '•••• saved — leave blank to keep', 'super-ninety-four' ) : ''; ?>" autocomplete="new-password"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Live webhook secret', 'super-ninety-four' ); ?></th>
					<td><input type="password" class="regular-text" name="snf_stripe[webhook_secret]" value="" placeholder="<?php echo $s['webhook_secret'] ? esc_attr__( '•••• saved — leave blank to keep', 'super-ninety-four' ) : ''; ?>" autocomplete="new-password"></td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Stripe settings', 'super-ninety-four' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Admin notice when Stripe is enabled but incomplete.
 */
function snf_stripe_admin_notices() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || false === strpos( (string) $screen->id, 'snf-stripe' ) ) {
		$s = snf_get_stripe_settings();
		if ( 'yes' === $s['enabled'] && ! snf_stripe_is_ready() ) {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html__( 'SNF Stripe is enabled but missing a secret key. Open WooCommerce → SNF Stripe to finish connecting.', 'super-ninety-four' );
			echo '</p></div>';
		}
	}
}
add_action( 'admin_notices', 'snf_stripe_admin_notices' );
