<?php
/**
 * Stripe integration: settings, Checkout Sessions, and custom webhook endpoint.
 *
 * Hook contract:
 * - Listen: REST snf/v1/stripe/* (admin + webhook).
 * - Emit: snf_stripe_webhook_handled; membership/reciprocal via WooCommerce order hooks only.
 * - Bridge: checkout.session.completed creates/completes a WC order (never activates with order_id 0).
 *
 * Webhook URL: /wp-json/snf/v1/stripe/webhook
 * Uses stripe/stripe-php (Composer) when vendor/autoload.php is present;
 * falls back to wp_remote_* if the SDK is missing.
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
 * Whether the Composer Stripe PHP SDK is loaded.
 *
 * @return bool
 */
function snf_stripe_sdk_available() {
	return class_exists( '\Stripe\StripeClient' );
}

/**
 * Configured StripeClient for the active mode.
 *
 * @return \Stripe\StripeClient|WP_Error
 */
function snf_stripe_client() {
	$secret = snf_stripe_get_secret_key();
	if ( ! $secret ) {
		return new WP_Error( 'snf_stripe_no_key', __( 'Stripe secret key is not configured.', 'super-ninety-four' ) );
	}
	if ( ! snf_stripe_sdk_available() ) {
		return new WP_Error( 'snf_stripe_no_sdk', __( 'stripe/stripe-php is not installed. Run composer install in the theme directory.', 'super-ninety-four' ) );
	}

	return new \Stripe\StripeClient(
		array(
			'api_key'        => $secret,
			'stripe_version' => '2024-06-20',
		)
	);
}

/**
 * Expand flat form-encoded keys (line_items[0][price]) into nested arrays for the SDK.
 *
 * @param array<string, mixed> $body Request body.
 * @return array<string, mixed>
 */
function snf_stripe_expand_form_body( array $body ) {
	$out = array();
	foreach ( $body as $key => $value ) {
		$key = (string) $key;
		if ( false === strpos( $key, '[' ) ) {
			$out[ $key ] = $value;
			continue;
		}

		$parts = preg_split( '/[\[\]]+/', $key, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) || ! $parts ) {
			$out[ $key ] = $value;
			continue;
		}

		$ref = &$out;
		$last = array_pop( $parts );
		foreach ( $parts as $part ) {
			if ( ! isset( $ref[ $part ] ) || ! is_array( $ref[ $part ] ) ) {
				$ref[ $part ] = array();
			}
			$ref = &$ref[ $part ];
		}
		$ref[ $last ] = $value;
		unset( $ref );
	}

	return $out;
}

/**
 * Stripe API request helper (SDK when available).
 *
 * @param string               $method GET|POST|DELETE.
 * @param string               $path   Path after /v1/ (or full /v1/...).
 * @param array<string, mixed> $body   Nested or flat form-style body.
 * @return array|WP_Error Decoded JSON or error.
 */
function snf_stripe_request( $method, $path, $body = array() ) {
	$secret = snf_stripe_get_secret_key();
	if ( ! $secret ) {
		return new WP_Error( 'snf_stripe_no_key', __( 'Stripe secret key is not configured.', 'super-ninety-four' ) );
	}

	$path   = '/' . ltrim( (string) $path, '/' );
	if ( 0 !== strpos( $path, '/v1/' ) ) {
		$path = '/v1/' . ltrim( $path, '/' );
	}
	$method = strtoupper( (string) $method );
	$params = snf_stripe_expand_form_body( is_array( $body ) ? $body : array() );

	if ( snf_stripe_sdk_available() ) {
		$client = snf_stripe_client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		try {
			$response = $client->request( strtolower( $method ), $path, $params, array() );
			if ( is_object( $response ) && method_exists( $response, 'toArray' ) ) {
				return $response->toArray();
			}
			if ( is_array( $response ) ) {
				return $response;
			}
			return (array) $response;
		} catch ( \Stripe\Exception\ApiErrorException $e ) {
			$http = method_exists( $e, 'getHttpStatus' ) ? (int) $e->getHttpStatus() : 400;
			$body_data = method_exists( $e, 'getJsonBody' ) ? $e->getJsonBody() : array();
			return new WP_Error(
				'snf_stripe_api',
				$e->getMessage(),
				array(
					'status' => $http ? $http : 400,
					'body'   => is_array( $body_data ) ? $body_data : array(),
				)
			);
		} catch ( \Throwable $e ) {
			return new WP_Error( 'snf_stripe_sdk', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	// Fallback without Composer vendor (should be rare after deploy).
	$url  = 'https://api.stripe.com' . $path;
	$args = array(
		'method'  => $method,
		'timeout' => 45,
		'headers' => array(
			'Authorization'  => 'Bearer ' . $secret,
			'Stripe-Version' => '2024-06-20',
		),
	);

	if ( 'GET' !== $method && $params ) {
		$args['body'] = $params;
	} elseif ( 'GET' === $method && $params ) {
		$url = add_query_arg( $params, $url );
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
 * Verify Stripe-Signature header against raw payload (SDK when available).
 *
 * @param string $payload    Raw request body.
 * @param string $sig_header Stripe-Signature header.
 * @param string $secret     whsec_… secret.
 * @param int    $tolerance  Seconds.
 * @return true|WP_Error
 */
function snf_stripe_verify_webhook_signature( $payload, $sig_header, $secret, $tolerance = 300 ) {
	if ( ! $secret ) {
		return new WP_Error( 'snf_stripe_no_whsec', __( 'Webhook signing secret is not configured.', 'super-ninety-four' ), array( 'status' => 500 ) );
	}
	if ( ! $sig_header ) {
		return new WP_Error( 'snf_stripe_no_sig', __( 'Missing Stripe-Signature header.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	if ( class_exists( '\Stripe\Webhook' ) ) {
		try {
			\Stripe\Webhook::constructEvent( $payload, $sig_header, $secret, $tolerance );
			return true;
		} catch ( \UnexpectedValueException $e ) {
			return new WP_Error( 'snf_stripe_bad_event', $e->getMessage(), array( 'status' => 400 ) );
		} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
			return new WP_Error( 'snf_stripe_sig_mismatch', $e->getMessage(), array( 'status' => 400 ) );
		}
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

	$mapbox = function_exists( 'snf_get_mapbox_token' ) ? (string) snf_get_mapbox_token() : '';

	return rest_ensure_response(
		array(
			'enabled'          => ( 'yes' === $s['enabled'] ),
			'mode'             => $s['mode'],
			'ready'            => snf_stripe_is_ready(),
			'sdk'              => snf_stripe_sdk_available(),
			'has_secret'       => (bool) snf_stripe_get_secret_key(),
			'has_publishable'  => (bool) snf_stripe_get_publishable_key(),
			'has_webhook'      => (bool) snf_stripe_get_webhook_secret(),
			'webhook_url'      => snf_stripe_webhook_url(),
			'mapbox'           => array(
				'configured' => ( '' !== $mapbox ),
			),
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
 * Resolve WooCommerce product IDs for Stripe session metadata.
 *
 * @param array<string, mixed> $meta Session metadata.
 * @return int[] Product IDs.
 */
function snf_stripe_bridge_product_ids_from_meta( array $meta ) {
	$ids     = array();
	$sku     = isset( $meta['sku'] ) ? sanitize_title( (string) $meta['sku'] ) : '';
	$plan    = isset( $meta['plan'] ) ? sanitize_title( (string) $meta['plan'] ) : '';
	$program = isset( $meta['program'] ) ? sanitize_title( (string) $meta['program'] ) : '';
	$init    = ! empty( $meta['initiation'] ) && in_array( (string) $meta['initiation'], array( '1', 'yes', 'true' ), true );

	if ( 'application' === $sku && function_exists( 'snf_get_membership_product_id' ) ) {
		$app_id = snf_get_membership_product_id( 'application' );
		if ( $app_id ) {
			$ids[] = $app_id;
		}
	}

	if ( $init && function_exists( 'snf_get_membership_product_id' ) ) {
		$init_id = snf_get_membership_product_id( 'initiation' );
		if ( $init_id ) {
			$ids[] = $init_id;
		}
	}

	if ( $plan && 'application' !== $sku && function_exists( 'snf_get_membership_product_id' ) ) {
		$plan_id = snf_get_membership_product_id( $plan );
		if ( $plan_id ) {
			$ids[] = $plan_id;
		}
	}

	if ( $program ) {
		$product = get_page_by_path( $program, OBJECT, 'product' );
		if ( $product ) {
			$ids[] = (int) $product->ID;
		}
	}

	/**
	 * Filter Woo product IDs bridged from a Stripe Checkout session.
	 *
	 * @param int[]                $ids  Product IDs.
	 * @param array<string, mixed> $meta Session metadata.
	 */
	$ids = apply_filters( 'snf_stripe_bridge_product_ids', $ids, $meta );

	return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
}

/**
 * Create a paid WooCommerce order from Stripe session metadata, then rely on WC hooks.
 *
 * @param int                  $user_id User ID.
 * @param array<string, mixed> $meta    Session metadata.
 * @param array<string, mixed> $session Stripe session.
 * @return int|\WP_Error Order ID or error.
 */
function snf_stripe_bridge_create_wc_order( $user_id, array $meta, array $session ) {
	if ( ! function_exists( 'wc_create_order' ) ) {
		return new WP_Error( 'snf_stripe_no_woo', __( 'WooCommerce is required to bridge Stripe payments.', 'super-ninety-four' ) );
	}

	$user_id     = (int) $user_id;
	$product_ids = snf_stripe_bridge_product_ids_from_meta( $meta );
	if ( ! $product_ids ) {
		return new WP_Error( 'snf_stripe_no_products', __( 'No WooCommerce products matched this Stripe session.', 'super-ninety-four' ) );
	}

	$order = wc_create_order(
		array(
			'customer_id' => $user_id,
			'created_via' => 'snf_stripe_bridge',
		)
	);
	if ( is_wp_error( $order ) || ! $order ) {
		return is_wp_error( $order ) ? $order : new WP_Error( 'snf_stripe_order_failed', __( 'Could not create WooCommerce order.', 'super-ninety-four' ) );
	}

	$plan = isset( $meta['plan'] ) ? sanitize_title( (string) $meta['plan'] ) : '';

	foreach ( $product_ids as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			continue;
		}
		$item_id = $order->add_product( $product, 1 );
		if ( $item_id && $plan && function_exists( 'snf_get_plan_slug_from_product' ) ) {
			$item = $order->get_item( $item_id );
			if ( $item && snf_get_plan_slug_from_product( $product ) === $plan ) {
				$item->add_meta_data( '_snf_membership_plan', $plan, true );
				$item->save();
			}
		}
	}

	if ( $plan ) {
		$order->update_meta_data( '_snf_membership_plan', $plan );
	}
	$order->update_meta_data( '_snf_stripe_checkout_session', sanitize_text_field( (string) ( $session['id'] ?? '' ) ) );
	$order->update_meta_data( '_snf_stripe_bridged', 'yes' );

	$user = get_userdata( $user_id );
	if ( $user ) {
		$order->set_billing_email( $user->user_email );
		$order->set_billing_first_name( $user->first_name ? $user->first_name : $user->display_name );
	} elseif ( ! empty( $session['customer_email'] ) ) {
		$order->set_billing_email( sanitize_email( (string) $session['customer_email'] ) );
	}

	$order->set_payment_method( 'snf_stripe' );
	$order->set_payment_method_title( __( 'Stripe (theme bridge)', 'super-ninety-four' ) );
	$order->calculate_totals( false );
	$order->save();

	$txn = sanitize_text_field( (string) ( $session['payment_intent'] ?? $session['id'] ?? '' ) );
	$order->payment_complete( $txn );
	$order->add_order_note( __( 'SUPER NINETY FOUR: Stripe Checkout bridged to WooCommerce order; activations run via WC hooks.', 'super-ninety-four' ) );

	/**
	 * Fires after a Stripe Checkout session is bridged into a WooCommerce order.
	 *
	 * @param int                  $order_id Order ID.
	 * @param int                  $user_id  User ID.
	 * @param array<string, mixed> $session  Stripe session.
	 * @param array<string, mixed> $meta     Session metadata.
	 */
	do_action( 'snf_stripe_order_bridged', $order->get_id(), $user_id, $session, $meta );

	return (int) $order->get_id();
}

/**
 * Bridge Checkout Session to a WooCommerce order (activations via WC hooks only).
 *
 * @param array<string, mixed> $session Session object.
 * @return array<string, mixed>
 */
function snf_stripe_handle_checkout_session_completed( array $session ) {
	$meta    = isset( $session['metadata'] ) && is_array( $session['metadata'] ) ? $session['metadata'] : array();
	$user_id = isset( $meta['user_id'] ) ? (int) $meta['user_id'] : 0;
	$plan    = isset( $meta['plan'] ) ? sanitize_title( (string) $meta['plan'] ) : '';
	$program = isset( $meta['program'] ) ? sanitize_title( (string) $meta['program'] ) : '';
	$sku     = isset( $meta['sku'] ) ? sanitize_title( (string) $meta['sku'] ) : '';

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
		'user_id'  => $user_id,
		'plan'     => $plan,
		'program'  => $program,
		'sku'      => $sku,
		'actions'  => array(),
		'order_id' => 0,
	);

	if ( $user_id <= 0 ) {
		$out['actions'][] = 'no_user';
		return $out;
	}

	if ( $plan ) {
		update_user_meta( $user_id, 'snf_pending_membership_plan', $plan );
	}
	update_user_meta( $user_id, 'snf_stripe_checkout_session', sanitize_text_field( (string) ( $session['id'] ?? '' ) ) );

	// Prefer completing an existing Woo order referenced in metadata.
	if ( ! empty( $meta['order_id'] ) && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( (int) $meta['order_id'] );
		if ( $order && ! $order->is_paid() ) {
			$order->payment_complete( sanitize_text_field( (string) ( $session['payment_intent'] ?? $session['id'] ?? '' ) ) );
			$order->add_order_note( __( 'SUPER NINETY FOUR: Stripe Checkout session completed via webhook.', 'super-ninety-four' ) );
			$out['actions'][] = 'order_paid';
			$out['order_id']  = (int) $order->get_id();
			return $out;
		}
		if ( $order && $order->is_paid() ) {
			$out['actions'][] = 'order_already_paid';
			$out['order_id']  = (int) $order->get_id();
			return $out;
		}
	}

	// Create a Woo order so membership/reciprocal listeners run on WC hooks.
	$bridged = snf_stripe_bridge_create_wc_order( $user_id, $meta, $session );
	if ( is_wp_error( $bridged ) ) {
		$out['actions'][] = 'bridge_failed';
		$out['error']     = $bridged->get_error_message();
		return $out;
	}

	$out['actions'][] = 'order_bridged';
	$out['order_id']  = (int) $bridged;
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
		'mode'                => 'payment',
		'success_url'         => $success,
		'cancel_url'          => $cancel,
		'client_reference_id' => (string) $user_id,
		'customer_email'      => $user ? (string) $user->user_email : '',
		'line_items'          => array(
			array(
				'price'    => $price_id,
				'quantity' => 1,
			),
		),
		'metadata'            => array(
			'user_id' => (string) $user_id,
			'plan'    => sanitize_title( (string) $plan_slug ),
			'sku'     => 'application',
			'source'  => 'snf_apply',
		),
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
		'line_items'          => $line_items,
		'metadata'            => array(
			'user_id'     => (string) $user_id,
			'plan'        => $plan,
			'program'     => $program,
			'sku'         => $sku,
			'initiation'  => $initiation ? '1' : '0',
			'source'      => 'snf_theme',
		),
	);

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

	if ( isset( $_POST['snf_mapbox_token'] ) && ! ( defined( 'SNF_MAPBOX_TOKEN' ) && SNF_MAPBOX_TOKEN ) ) {
		$mapbox = sanitize_text_field( wp_unslash( (string) $_POST['snf_mapbox_token'] ) );
		// Password-style UX: blank keeps the previously saved token.
		if ( '' !== $mapbox ) {
			update_option( 'snf_mapbox_token', $mapbox, false );
		}
	}

	$probe_message = snf_stripe_refresh_api_health();

	wp_safe_redirect( admin_url( 'admin.php?page=snf-stripe&updated=1' . $probe_message ) );
	exit;
}
add_action( 'admin_post_snf_stripe_save_settings', 'snf_stripe_handle_settings_save' );

/**
 * Probe Stripe + Supabase catalog + Mapbox and refresh the health cache.
 *
 * @param bool $force_catalog_refresh Bypass catalog transient.
 * @return string Query-string fragment (leading &) for admin redirects.
 */
function snf_stripe_refresh_api_health( $force_catalog_refresh = true ) {
	$probe_message = '';
	$health        = array(
		'checked'  => time(),
		'sdk'      => snf_stripe_sdk_available(),
		'stripe'   => 'skip',
		'supabase' => 'skip',
		'mapbox'   => 'skip',
		'messages' => array(),
	);

	$settings = snf_get_stripe_settings();
	if ( 'yes' === $settings['enabled'] && snf_stripe_get_secret_key() ) {
		$probe = snf_stripe_request( 'GET', 'account' );
		if ( is_wp_error( $probe ) ) {
			$probe_message        = '&stripe_probe=fail';
			$health['stripe']     = 'fail';
			$health['messages'][] = $probe->get_error_message();
		} else {
			$probe_message    = '&stripe_probe=ok';
			$health['stripe'] = 'ok';
			update_option(
				'snf_stripe_account_cache',
				array(
					'id'       => isset( $probe['id'] ) ? (string) $probe['id'] : '',
					'email'    => isset( $probe['email'] ) ? (string) $probe['email'] : '',
					'livemode' => ! empty( $probe['charges_enabled'] ),
					'checked'  => time(),
				),
				false
			);
		}
	}

	if ( function_exists( 'snf_supabase_payments_is_ready' ) && snf_supabase_payments_is_ready() && function_exists( 'snf_supabase_get_stripe_catalog' ) ) {
		if ( $force_catalog_refresh ) {
			delete_transient( 'snf_supabase_stripe_catalog_v1' );
		}
		$catalog = snf_supabase_get_stripe_catalog( (bool) $force_catalog_refresh );
		if ( is_wp_error( $catalog ) ) {
			$health['supabase']   = 'fail';
			$health['messages'][] = $catalog->get_error_message();
			$probe_message       .= '&supabase_probe=fail';
		} else {
			$health['supabase']      = 'ok';
			$health['catalog_count'] = is_array( $catalog ) ? count( $catalog ) : 0;
			$probe_message          .= '&supabase_probe=ok';
		}
	}

	$mapbox_token = function_exists( 'snf_get_mapbox_token' ) ? (string) snf_get_mapbox_token() : (string) get_option( 'snf_mapbox_token', '' );
	if ( defined( 'SNF_MAPBOX_TOKEN' ) && SNF_MAPBOX_TOKEN ) {
		$mapbox_token = (string) SNF_MAPBOX_TOKEN;
	}
	if ( '' !== $mapbox_token ) {
		$health['mapbox'] = 'ok';
		$probe_message   .= '&mapbox_probe=ok';
	} else {
		$health['mapbox'] = 'missing';
		$probe_message   .= '&mapbox_probe=missing';
	}

	update_option( 'snf_api_health_cache', $health, false );

	return $probe_message;
}

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
	$health  = get_option( 'snf_api_health_cache', array() );
	if ( ! is_array( $health ) ) {
		$health = array();
	}
	$webhook = snf_stripe_webhook_url();
	$catalog = function_exists( 'snf_supabase_get_stripe_catalog' ) ? snf_supabase_get_stripe_catalog( true ) : array();

	$enabled       = ( 'yes' === $s['enabled'] );
	$mode_label    = ( 'live' === $s['mode'] ) ? __( 'Live', 'super-ninety-four' ) : __( 'Test', 'super-ninety-four' );
	$sdk_ok        = snf_stripe_sdk_available();
	$ready         = snf_stripe_is_ready();
	$webhook_set   = (bool) snf_stripe_get_webhook_secret();
	$catalog_count = isset( $health['catalog_count'] ) ? (int) $health['catalog_count'] : 0;
	if ( ! $catalog_count && is_array( $catalog ) && ! is_wp_error( $catalog ) ) {
		$catalog_count = count( $catalog );
	}
	$mapbox_saved      = (string) get_option( 'snf_mapbox_token', '' );
	$mapbox_from_const = defined( 'SNF_MAPBOX_TOKEN' ) && SNF_MAPBOX_TOKEN;
	$mapbox_ok         = $mapbox_from_const || '' !== $mapbox_saved;
	if ( isset( $health['mapbox'] ) ) {
		$mapbox_ok = ( 'ok' === $health['mapbox'] );
	}

	$nav = function_exists( 'snf_admin_payments_nav_pages' ) ? snf_admin_payments_nav_pages() : array();
	if ( function_exists( 'snf_admin_render_shell_start' ) ) {
		snf_admin_render_shell_start(
			'snf-stripe',
			__( 'Payments & integrations', 'super-ninety-four' ),
			__( 'Stripe Checkout, webhooks, Supabase price sync, and Mapbox for Expo lead-rider maps.', 'super-ninety-four' ),
			$nav
		);
	} else {
		echo '<div class="wrap"><h1>' . esc_html__( 'SUPER NINETY FOUR — Stripe', 'super-ninety-four' ) . '</h1>';
	}
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo $enabled ? esc_html__( 'On', 'super-ninety-four' ) : esc_html__( 'Off', 'super-ninety-four' ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Enabled', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( $mode_label ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Mode', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo $sdk_ok ? esc_html__( 'Yes', 'super-ninety-four' ) : esc_html__( 'No', 'super-ninety-four' ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'SDK loaded', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo $ready ? esc_html__( 'Ready', 'super-ninety-four' ) : esc_html__( 'No', 'super-ninety-four' ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Stripe ready', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo $webhook_set ? esc_html__( 'Set', 'super-ninety-four' ) : esc_html__( 'Missing', 'super-ninety-four' ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Webhook secret', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) $catalog_count ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Catalog prices', 'super-ninety-four' ); ?></span>
		</div>
	</div>

	<?php if ( function_exists( 'snf_commerce_requirements_checklist' ) ) : ?>
	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Commerce requirements', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Checklist for SUPER NINETY FOUR Payments + Club membership catalog.', 'super-ninety-four' ); ?></p>
			<ul class="snf-admin__list">
				<?php foreach ( snf_commerce_requirements_checklist() as $req ) : ?>
					<li>
						<span><?php echo esc_html( $req['label'] ); ?></span>
						<span class="snf-admin__pill <?php echo ! empty( $req['ok'] ) ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
							<?php echo esc_html( $req['detail'] ); ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="snf-admin__actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="snf_ops_ensure_membership_products">
					<input type="hidden" name="snf_ops_redirect" value="snf-stripe">
					<?php wp_nonce_field( 'snf_ops_ensure_membership', 'snf_ops_ensure_membership_nonce' ); ?>
					<button type="submit" class="snf-admin__btn snf-admin__btn--ghost"><?php esc_html_e( 'Ensure membership products', 'super-ninety-four' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="snf_ops_sync_stripe_membership_catalog">
					<input type="hidden" name="snf_ops_redirect" value="snf-stripe">
					<?php wp_nonce_field( 'snf_ops_sync_stripe_membership', 'snf_ops_sync_stripe_membership_nonce' ); ?>
					<button type="submit" class="snf-admin__btn"><?php esc_html_e( 'Sync Stripe membership catalog', 'super-ninety-four' ); ?></button>
				</form>
			</div>
		</section>
	</div>
	<?php endif; ?>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'API health', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Probes refresh when you save settings below, or via Re-probe APIs in Critical ops.', 'super-ninety-four' ); ?></p>
			<ul class="snf-admin__list">
				<li>
					<span><?php esc_html_e( 'Payments webhook (primary)', 'super-ninety-four' ); ?></span>
					<code class="snf-admin__code"><?php echo esc_html( function_exists( 'snf_payments_webhook_url' ) ? snf_payments_webhook_url() : rest_url( 'snf-payments/v1/stripe/webhook' ) ); ?></code>
				</li>
				<li>
					<span><?php esc_html_e( 'Theme webhook (Checkout Sessions)', 'super-ninety-four' ); ?></span>
					<code class="snf-admin__code"><?php echo esc_html( $webhook ); ?></code>
				</li>
				<?php if ( ! empty( $account['id'] ) ) : ?>
				<li>
					<span><?php esc_html_e( 'Last connected account', 'super-ninety-four' ); ?></span>
					<span>
						<code><?php echo esc_html( (string) $account['id'] ); ?></code>
						<?php if ( ! empty( $account['email'] ) ) : ?>
							<span class="snf-admin__muted"> — <?php echo esc_html( (string) $account['email'] ); ?></span>
						<?php endif; ?>
					</span>
				</li>
				<?php endif; ?>
				<li>
					<span><?php esc_html_e( 'Mapbox token', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo $mapbox_ok ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
						<?php echo $mapbox_ok ? esc_html__( 'Present', 'super-ninety-four' ) : esc_html__( 'Missing', 'super-ninety-four' ); ?>
					</span>
				</li>
			</ul>
			<p class="snf-admin__muted"><?php esc_html_e( 'Point Stripe webhooks at both URLs. Payments plugin: payment_intent.succeeded, payment_intent.payment_failed, charge.refunded. Theme bridge: checkout.session.completed, checkout.session.async_payment_succeeded, payment_intent.succeeded, charge.refunded.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club' ) ); ?>"><?php esc_html_e( 'Club overview', 'super-ninety-four' ); ?></a>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-settings' ) ); ?>"><?php esc_html_e( 'Critical settings', 'super-ninety-four' ); ?></a>
				<?php endif; ?>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-events' ) ); ?>"><?php esc_html_e( 'Events desk', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-addons' ) ); ?>"><?php esc_html_e( 'Addons desk', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>"><?php esc_html_e( 'Woo payments', 'super-ninety-four' ); ?></a>
			</div>
		</section>

		<?php
		if ( function_exists( 'snf_admin_render_critical_ops_panels' ) ) {
			snf_admin_render_critical_ops_panels( 'stripe' );
		}
		?>

		<form class="snf-admin__form snf-admin__panel snf-admin__panel--wide" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="snf_stripe_save_settings">
			<?php wp_nonce_field( 'snf_stripe_settings', 'snf_stripe_settings_nonce' ); ?>

			<h2><?php esc_html_e( 'Stripe API keys', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Accept Stripe Checkout + webhooks for club dues / reciprocal. Secret fields keep the previous value when left blank.', 'super-ninety-four' ); ?></p>
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

			<h2><?php esc_html_e( 'Supabase Stripe Sync', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Reads products/prices from your SUPER NINETY FOUR PAYMENTS Supabase project (stripe_sdp_data). Checkout still needs a Stripe secret key; the sync supplies the catalog + status mirror.', 'super-ninety-four' ); ?></p>
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
				<div class="snf-admin__notice snf-admin__notice--warn"><p><?php echo esc_html( $catalog->get_error_message() ); ?></p></div>
			<?php elseif ( is_array( $catalog ) && $catalog ) : ?>
				<h3 class="snf-admin__subhead"><?php esc_html_e( 'Synced catalog', 'super-ninety-four' ); ?></h3>
				<table class="snf-admin__table">
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

			<h2><?php esc_html_e( 'Mapbox token', 'super-ninety-four' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Mapbox token', 'super-ninety-four' ); ?></th>
					<td>
						<input
							type="password"
							class="large-text"
							name="snf_mapbox_token"
							value=""
							placeholder="<?php echo $mapbox_from_const ? esc_attr__( 'Set via SNF_MAPBOX_TOKEN in wp-config.php', 'super-ninety-four' ) : ( $mapbox_saved ? esc_attr__( '•••• saved — enter a new token to replace', 'super-ninety-four' ) : '' ); ?>"
							autocomplete="new-password"
							<?php disabled( (bool) $mapbox_from_const ); ?>
						>
					</td>
				</tr>
			</table>

			<div class="snf-admin__actions">
				<button type="submit" class="snf-admin__btn"><?php esc_html_e( 'Save Stripe settings', 'super-ninety-four' ); ?></button>
			</div>
		</form>
	</div>
	<?php
	if ( function_exists( 'snf_admin_render_shell_end' ) ) {
		snf_admin_render_shell_end();
	} else {
		echo '</div>';
	}
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

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$payments_active = function_exists( 'is_plugin_active' ) && is_plugin_active( 'snf-payments/snf-payments.php' );
	if ( ! $payments_active && class_exists( 'WooCommerce' ) ) {
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'SUPER NINETY FOUR Payments is inactive. Club Apply / membership checkout expects that gateway. Activate it under Plugins, then enable it in WooCommerce → Settings → Payments.', 'super-ninety-four' );
		echo '</p></div>';
	}
}
add_action( 'admin_notices', 'snf_stripe_admin_notices' );

/**
 * Convert a major-unit amount to Stripe's smallest currency unit.
 *
 * @param float  $amount   Major units.
 * @param string $currency ISO currency.
 * @return int
 */
function snf_stripe_amount_to_cents( $amount, $currency = 'usd' ) {
	$currency = strtolower( (string) $currency );
	$zero     = array( 'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf' );
	if ( in_array( $currency, $zero, true ) ) {
		return (int) round( (float) $amount );
	}
	return (int) round( (float) $amount * 100 );
}

/**
 * Find an existing Stripe Product by snf_sku metadata (current API keys / mode).
 *
 * @param string $sku Catalog SKU.
 * @return array<string, mixed>|null
 */
function snf_stripe_find_product_by_sku( $sku ) {
	$sku = sanitize_title( (string) $sku );
	if ( ! $sku ) {
		return null;
	}

	$query = "metadata['snf_sku']:'" . str_replace( "'", "\\'", $sku ) . "'";
	$found = snf_stripe_request(
		'GET',
		'products/search',
		array(
			'query' => $query,
			'limit' => 1,
		)
	);
	if ( is_wp_error( $found ) || empty( $found['data'][0] ) || ! is_array( $found['data'][0] ) ) {
		return null;
	}
	return $found['data'][0];
}

/**
 * Ensure one membership catalog row exists as Stripe Product + one-time Price, linked to Woo.
 *
 * @param array{sku:string,title:string,amount_raw:float,kind:string,plan:string} $row  Catalog row.
 * @param string                                                                   $mode test|live (for meta storage only; API uses active keys).
 * @return array<string, mixed>|WP_Error
 */
function snf_stripe_ensure_membership_catalog_row( array $row, $mode = 'test' ) {
	$mode = ( 'live' === $mode ) ? 'live' : 'test';
	$sku  = isset( $row['sku'] ) ? sanitize_title( (string) $row['sku'] ) : '';
	if ( ! $sku ) {
		return new WP_Error( 'snf_stripe_row', __( 'Catalog row missing SKU.', 'super-ninety-four' ) );
	}

	if ( function_exists( 'snf_ensure_membership_products' ) ) {
		snf_ensure_membership_products( true );
	}

	$woo_id = function_exists( 'snf_get_membership_product_id_by_sku' )
		? snf_get_membership_product_id_by_sku( $sku )
		: 0;
	if ( $woo_id <= 0 ) {
		return new WP_Error(
			'snf_stripe_no_woo',
			sprintf(
				/* translators: %s: product slug */
				__( 'Woo product missing for SKU %s — run Ensure membership products first.', 'super-ninety-four' ),
				$sku
			)
		);
	}

	$product = snf_stripe_find_product_by_sku( $sku );
	if ( ! $product ) {
		$created = snf_stripe_request(
			'POST',
			'products',
			array(
				'name'     => isset( $row['title'] ) ? (string) $row['title'] : $sku,
				'metadata' => array(
					'snf_sku'  => $sku,
					'snf_kind' => isset( $row['kind'] ) ? (string) $row['kind'] : '',
					'snf_plan' => isset( $row['plan'] ) ? (string) $row['plan'] : '',
				),
			)
		);
		if ( is_wp_error( $created ) ) {
			return $created;
		}
		$product = $created;
	}

	$product_id = isset( $product['id'] ) ? (string) $product['id'] : '';
	if ( ! $product_id ) {
		return new WP_Error( 'snf_stripe_no_prod', __( 'Stripe product id missing.', 'super-ninety-four' ) );
	}

	$existing_ids = function_exists( 'snf_get_membership_product_stripe_ids' )
		? snf_get_membership_product_stripe_ids( $woo_id )
		: array( 'test' => array( 'price' => '' ), 'live' => array( 'price' => '' ) );
	$price_id     = isset( $existing_ids[ $mode ]['price'] ) ? (string) $existing_ids[ $mode ]['price'] : '';

	if ( $price_id ) {
		$price_get = snf_stripe_request( 'GET', 'prices/' . rawurlencode( $price_id ) );
		if ( is_wp_error( $price_get ) ) {
			$price_id = '';
		}
	}

	if ( ! $price_id ) {
		$amount = snf_stripe_amount_to_cents( isset( $row['amount_raw'] ) ? (float) $row['amount_raw'] : 0, 'usd' );
		$price  = snf_stripe_request(
			'POST',
			'prices',
			array(
				'product'    => $product_id,
				'currency'   => 'usd',
				'unit_amount'=> $amount,
				'metadata'   => array(
					'snf_sku'  => $sku,
					'snf_kind' => isset( $row['kind'] ) ? (string) $row['kind'] : '',
					'snf_plan' => isset( $row['plan'] ) ? (string) $row['plan'] : '',
				),
			)
		);
		if ( is_wp_error( $price ) ) {
			return $price;
		}
		$price_id = isset( $price['id'] ) ? (string) $price['id'] : '';
	}

	if ( ! $price_id ) {
		return new WP_Error( 'snf_stripe_no_price', __( 'Stripe price id missing.', 'super-ninety-four' ) );
	}

	if ( function_exists( 'snf_set_membership_product_stripe_ids' ) ) {
		snf_set_membership_product_stripe_ids( $woo_id, $sku, $mode, $product_id, $price_id );
	}

	return array(
		'sku'            => $sku,
		'woo_product_id' => $woo_id,
		'product_id'     => $product_id,
		'price_id'       => $price_id,
		'mode'           => $mode,
	);
}

/**
 * Sync all membership catalog rows to Stripe Products/Prices for the active key mode.
 *
 * @param string|null $mode Force test|live for meta keys; null = from settings.
 * @return array{ok:bool,mode:string,rows:array<int,array<string,mixed>>,errors:array<int,string>}
 */
function snf_stripe_sync_membership_catalog( $mode = null ) {
	$settings = snf_get_stripe_settings();
	if ( null === $mode ) {
		$mode = ( isset( $settings['mode'] ) && 'live' === $settings['mode'] ) ? 'live' : 'test';
	} else {
		$mode = ( 'live' === $mode ) ? 'live' : 'test';
	}

	$out = array(
		'ok'     => true,
		'mode'   => $mode,
		'rows'   => array(),
		'errors' => array(),
	);

	if ( ! snf_stripe_get_secret_key() ) {
		$out['ok']       = false;
		$out['errors'][] = __( 'Stripe secret key is not configured for the active mode.', 'super-ninety-four' );
		return $out;
	}

	$rows = function_exists( 'snf_get_membership_catalog_rows' ) ? snf_get_membership_catalog_rows() : array();
	foreach ( $rows as $row ) {
		$result = snf_stripe_ensure_membership_catalog_row( $row, $mode );
		if ( is_wp_error( $result ) ) {
			$out['ok']       = false;
			$out['errors'][] = $result->get_error_message();
			continue;
		}
		$out['rows'][] = $result;
	}

	return $out;
}

/**
 * Commerce requirements checklist for Club Critical / SNF Stripe.
 *
 * @return array<int, array{id:string,label:string,ok:bool,detail:string}>
 */
function snf_commerce_requirements_checklist() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$woo_ok      = class_exists( 'WooCommerce' );
	$plugin_ok   = function_exists( 'is_plugin_active' ) && is_plugin_active( 'snf-payments/snf-payments.php' );
	$gateway_ok  = false;
	$gateway_rdy = false;
	if ( $plugin_ok && class_exists( 'SNF_Payments_Stripe_API' ) ) {
		$gateway_rdy = SNF_Payments_Stripe_API::is_ready();
		$gs          = get_option( 'woocommerce_snf_stripe_settings', array() );
		$gateway_ok  = is_array( $gs ) && isset( $gs['enabled'] ) && 'yes' === $gs['enabled'];
	}

	$products = function_exists( 'snf_membership_products_status' )
		? snf_membership_products_status()
		: array( 'ok' => false, 'missing' => array( 'unknown' ), 'products' => array() );

	$mode       = ( isset( snf_get_stripe_settings()['mode'] ) && 'live' === snf_get_stripe_settings()['mode'] ) ? 'live' : 'test';
	$linked     = 0;
	$link_total = 0;
	if ( ! empty( $products['products'] ) && is_array( $products['products'] ) ) {
		foreach ( $products['products'] as $woo_id ) {
			$woo_id = (int) $woo_id;
			if ( $woo_id <= 0 ) {
				continue;
			}
			++$link_total;
			$ids = function_exists( 'snf_get_membership_product_stripe_ids' )
				? snf_get_membership_product_stripe_ids( $woo_id )
				: array();
			if ( ! empty( $ids[ $mode ]['price'] ) ) {
				++$linked;
			}
		}
	}
	$prices_ok = $link_total > 0 && $linked === $link_total;

	$wh_secret  = (bool) snf_stripe_get_webhook_secret();
	$blocks_raw = get_option( 'woocommerce_cart_checkout_blocks_enabled', 'no' );
	$blocks_off = ( 'yes' !== $blocks_raw && '1' !== (string) $blocks_raw );

	$items = array(
		array(
			'id'     => 'woocommerce',
			'label'  => __( 'WooCommerce active', 'super-ninety-four' ),
			'ok'     => $woo_ok,
			'detail' => $woo_ok ? __( 'OK', 'super-ninety-four' ) : __( 'Required', 'super-ninety-four' ),
		),
		array(
			'id'     => 'snf_payments',
			'label'  => __( 'SUPER NINETY FOUR Payments plugin', 'super-ninety-four' ),
			'ok'     => $plugin_ok,
			'detail' => $plugin_ok ? __( 'Active', 'super-ninety-four' ) : __( 'Inactive', 'super-ninety-four' ),
		),
		array(
			'id'     => 'gateway',
			'label'  => __( 'SNF gateway enabled + ready', 'super-ninety-four' ),
			'ok'     => $gateway_ok && $gateway_rdy,
			'detail' => ( $gateway_ok && $gateway_rdy )
				? __( 'Ready', 'super-ninety-four' )
				: __( 'Enable under WooCommerce → Payments', 'super-ninety-four' ),
		),
		array(
			'id'     => 'membership_products',
			'label'  => __( 'Membership Woo products (6 SKUs)', 'super-ninety-four' ),
			'ok'     => ! empty( $products['ok'] ),
			'detail' => ! empty( $products['ok'] )
				? __( 'All present', 'super-ninety-four' )
				: sprintf(
					/* translators: %s: missing slugs */
					__( 'Missing: %s', 'super-ninety-four' ),
					implode( ', ', (array) $products['missing'] )
				),
		),
		array(
			'id'     => 'stripe_prices',
			'label'  => sprintf(
				/* translators: %s: test|live */
				__( 'Stripe Price IDs linked (%s)', 'super-ninety-four' ),
				$mode
			),
			'ok'     => $prices_ok,
			'detail' => sprintf(
				/* translators: 1: linked count, 2: total */
				__( '%1$d / %2$d', 'super-ninety-four' ),
				$linked,
				$link_total
			),
		),
		array(
			'id'     => 'webhook_secret',
			'label'  => __( 'Webhook signing secret', 'super-ninety-four' ),
			'ok'     => $wh_secret,
			'detail' => $wh_secret ? __( 'Set', 'super-ninety-four' ) : __( 'Missing', 'super-ninety-four' ),
		),
		array(
			'id'     => 'classic_checkout',
			'label'  => __( 'Classic checkout (Blocks off)', 'super-ninety-four' ),
			'ok'     => $blocks_off,
			'detail' => $blocks_off ? __( 'OK', 'super-ninety-four' ) : __( 'Disable Cart/Checkout Blocks', 'super-ninety-four' ),
		),
	);

	/**
	 * Filter commerce requirements checklist rows.
	 *
	 * @param array<int, array{id:string,label:string,ok:bool,detail:string}> $items Items.
	 */
	return apply_filters( 'snf_commerce_requirements_checklist', $items );
}

/**
 * Payments webhook URL (plugin primary).
 *
 * @return string
 */
function snf_payments_webhook_url() {
	return rest_url( 'snf-payments/v1/stripe/webhook' );
}
