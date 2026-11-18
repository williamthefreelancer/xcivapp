<?php
/**
 * X Ads conversion tracking: Universal Website Tag (pp813) + Conversion API.
 *
 * Base tag loads once via wp_head (theme-wide). Server conversions POST to
 * https://ads-api.x.com/12/measurement/conversions/pp813 with X-Pixel-Token.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Option key for X Ads pixel / CAPI event settings. */
const SNF_X_ADS_PIXEL_OPTION = 'snf_x_ads_pixel_settings';

/** Pixel ID (website tag). */
const SNF_X_ADS_PIXEL_ID_DEFAULT = 'pp813';

/**
 * Default settings.
 *
 * Base web pixel is always pp813. Conversion API event_id values that return 200
 * for this account are the website_tag_id strings (e.g. pp814 Purchase Tracker),
 * not tw-pp813-xl… UWT event strings.
 *
 * @return array<string, string>
 */
function snf_x_ads_pixel_default_settings() {
	return array(
		'enabled'            => 'yes',
		'pixel_id'           => SNF_X_ADS_PIXEL_ID_DEFAULT,
		'purchase_event'     => 'pp814', // Purchase Tracker
		'signup_event'       => 'pp815', // Lead Generation Tracker
		'add_to_cart_event'  => 'pp817', // Add To Cart Tracker
		'checkout_event'     => 'rjdo4', // Checkout Initiated (X Ads CUSTOM tag)
		'complete_reg_event' => 'pp815',
	);
}

/**
 * Merged settings.
 *
 * @return array<string, string>
 */
function snf_get_x_ads_pixel_settings() {
	$stored = get_option( SNF_X_ADS_PIXEL_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	$settings = array_merge( snf_x_ads_pixel_default_settings(), array_map( 'strval', $stored ) );

	if ( defined( 'SNF_X_ADS_PIXEL_ENABLED' ) ) {
		$settings['enabled'] = SNF_X_ADS_PIXEL_ENABLED ? 'yes' : 'no';
	}

	/**
	 * Filter X Ads pixel settings.
	 *
	 * @param array<string, string> $settings Settings.
	 */
	return apply_filters( 'snf_x_ads_pixel_settings', $settings );
}

/**
 * Whether the base web pixel should load.
 *
 * @return bool
 */
function snf_x_ads_pixel_is_enabled() {
	$s = snf_get_x_ads_pixel_settings();
	return ( ! empty( $s['enabled'] ) && 'yes' === $s['enabled'] );
}

/**
 * Sanitize event / pixel id (tw-pp813-xxxxx or pp813).
 *
 * @param string $value Raw.
 * @return string
 */
function snf_x_ads_sanitize_id( $value ) {
	return (string) preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $value );
}

/**
 * Read Conversion API access token (never hard-code).
 *
 * Prefers getenv( X_PIXEL_TOKEN ), then wp-config constant X_PIXEL_TOKEN.
 *
 * @return string
 */
function snf_x_ads_get_pixel_token() {
	$token = '';
	if ( function_exists( 'getenv' ) ) {
		$env = getenv( 'X_PIXEL_TOKEN' );
		if ( is_string( $env ) && '' !== $env ) {
			$token = $env;
		}
	}
	if ( ! $token && defined( 'X_PIXEL_TOKEN' ) && X_PIXEL_TOKEN ) {
		$token = (string) X_PIXEL_TOKEN;
	}

	/**
	 * Filter X Pixel Conversion API token.
	 *
	 * @param string $token Token.
	 */
	return (string) apply_filters( 'snf_x_ads_pixel_token', $token );
}

/**
 * Persist twclid from landing URL for later CAPI / browser events.
 */
function snf_x_ads_capture_twclid() {
	if ( empty( $_GET['twclid'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$twclid = snf_x_ads_sanitize_id( wp_unslash( (string) $_GET['twclid'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $twclid ) {
		return;
	}

	$expire = time() + ( 30 * DAY_IN_SECONDS );
	$path   = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
	$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
	setcookie( 'twclid', $twclid, $expire, $path, $domain, is_ssl(), true );

	if ( function_exists( 'WC' ) ) {
		if ( function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
		if ( WC()->session ) {
			WC()->session->set( 'snf_twclid', $twclid );
		}
	}
}
add_action( 'init', 'snf_x_ads_capture_twclid', 5 );

/**
 * Resolved twclid from cookie or Woo session.
 *
 * @return string
 */
function snf_x_ads_get_twclid() {
	if ( ! empty( $_COOKIE['twclid'] ) ) {
		return snf_x_ads_sanitize_id( wp_unslash( (string) $_COOKIE['twclid'] ) );
	}
	if ( function_exists( 'WC' ) && WC()->session ) {
		return snf_x_ads_sanitize_id( (string) WC()->session->get( 'snf_twclid' ) );
	}
	return '';
}

/**
 * SHA-256 hex of a trimmed, lowercased email.
 *
 * @param string $email Email.
 * @return string Empty if invalid.
 */
function snf_x_ads_hash_email( $email ) {
	$email = strtolower( trim( (string) $email ) );
	if ( ! $email || ! is_email( $email ) ) {
		return '';
	}
	return hash( 'sha256', $email );
}

/**
 * SHA-256 hex of an E.164 phone number.
 *
 * @param string $phone Raw phone.
 * @return string Empty if unusable.
 */
function snf_x_ads_hash_phone( $phone ) {
	$digits = preg_replace( '/\D+/', '', (string) $phone );
	if ( ! $digits ) {
		return '';
	}
	// Assume US if 10 digits; otherwise require leading country code already present.
	if ( 10 === strlen( $digits ) ) {
		$e164 = '+1' . $digits;
	} elseif ( 11 === strlen( $digits ) && '1' === $digits[0] ) {
		$e164 = '+' . $digits;
	} elseif ( strlen( $digits ) >= 8 ) {
		$e164 = '+' . ltrim( $digits, '+' );
	} else {
		return '';
	}
	return hash( 'sha256', $e164 );
}

/**
 * Client IP for CAPI (best-effort).
 *
 * @return string
 */
function snf_x_ads_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	/**
	 * Filter client IP used for X CAPI.
	 *
	 * @param string $ip IP.
	 */
	return (string) apply_filters( 'snf_x_ads_client_ip', $ip );
}

/**
 * User-Agent for CAPI.
 *
 * @return string
 */
function snf_x_ads_user_agent() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( (string) $_SERVER['HTTP_USER_AGENT'], 0, 512 ) : '';
}

/**
 * Build identifiers array with at least one usable key (or IP+UA pair).
 *
 * @param array{email?:string,phone?:string,twclid?:string} $hints Optional hints.
 * @return array<int, array<string, string>>
 */
function snf_x_ads_build_identifiers( array $hints = array() ) {
	$row = array();

	$twclid = isset( $hints['twclid'] ) ? snf_x_ads_sanitize_id( $hints['twclid'] ) : snf_x_ads_get_twclid();
	if ( $twclid ) {
		$row['twclid'] = $twclid;
	}

	if ( ! empty( $hints['email'] ) ) {
		$hashed = snf_x_ads_hash_email( $hints['email'] );
		if ( $hashed ) {
			$row['hashed_email'] = $hashed;
		}
	}

	if ( ! empty( $hints['phone'] ) ) {
		$hashed = snf_x_ads_hash_phone( $hints['phone'] );
		if ( $hashed ) {
			$row['hashed_phone_number'] = $hashed;
		}
	}

	$ip = snf_x_ads_client_ip();
	$ua = snf_x_ads_user_agent();
	if ( $ip && $ua ) {
		$row['ip_address'] = $ip;
		$row['user_agent'] = $ua;
	}

	$has_single = ! empty( $row['twclid'] ) || ! empty( $row['hashed_email'] ) || ! empty( $row['hashed_phone_number'] );
	$has_pair   = ! empty( $row['ip_address'] ) && ! empty( $row['user_agent'] );
	if ( ! $has_single && ! $has_pair ) {
		return array();
	}

	return array( $row );
}

/**
 * POST conversions to X Conversion API (server-only).
 *
 * @param string               $event_id      tw-pp813-xxxxx.
 * @param string               $conversion_id Shared with web pixel for dedupe.
 * @param array<string, mixed> $args          email, phone, twclid, event_source_url, value, etc.
 * @return true|WP_Error
 */
function snf_x_ads_send_conversion( $event_id, $conversion_id, array $args = array() ) {
	$event_id      = snf_x_ads_sanitize_id( $event_id );
	$conversion_id = sanitize_text_field( (string) $conversion_id );
	if ( ! $event_id ) {
		return new WP_Error( 'snf_x_ads_no_event', __( 'X Ads event ID is not configured.', 'super-ninety-four' ) );
	}

	$token = snf_x_ads_get_pixel_token();
	if ( ! $token ) {
		return new WP_Error( 'snf_x_ads_no_token', __( 'X_PIXEL_TOKEN is not set.', 'super-ninety-four' ) );
	}

	$identifiers = snf_x_ads_build_identifiers(
		array(
			'email'  => isset( $args['email'] ) ? (string) $args['email'] : '',
			'phone'  => isset( $args['phone'] ) ? (string) $args['phone'] : '',
			'twclid' => isset( $args['twclid'] ) ? (string) $args['twclid'] : '',
		)
	);
	if ( ! $identifiers ) {
		return new WP_Error( 'snf_x_ads_no_id', __( 'X CAPI requires twclid, hashed email/phone, or IP + user agent.', 'super-ninety-four' ) );
	}

	$conversion = array(
		'conversion_time' => gmdate( 'Y-m-d\TH:i:s.000\Z' ),
		'event_id'        => $event_id,
		'identifiers'     => $identifiers,
	);
	if ( $conversion_id ) {
		$conversion['conversion_id'] = $conversion_id;
	}
	if ( ! empty( $args['event_source_url'] ) ) {
		$conversion['event_source_url'] = esc_url_raw( (string) $args['event_source_url'] );
	} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$conversion['event_source_url'] = esc_url_raw( (string) wp_unslash( $_SERVER['HTTP_REFERER'] ) );
	}

	$pixel = SNF_X_ADS_PIXEL_ID_DEFAULT;
	$url   = 'https://ads-api.x.com/12/measurement/conversions/' . rawurlencode( $pixel );

	$response = wp_remote_post(
		$url,
		array(
			'timeout' => 15,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'X-Pixel-Token' => $token,
			),
			'body'    => wp_json_encode( array( 'conversions' => array( $conversion ) ) ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = (string) wp_remote_retrieve_body( $response );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error(
			'snf_x_ads_capi_http',
			sprintf(
				/* translators: 1: HTTP status, 2: body excerpt */
				__( 'X CAPI HTTP %1$d: %2$s', 'super-ninety-four' ),
				$code,
				substr( $body, 0, 200 )
			),
			array( 'status' => $code, 'body' => $body )
		);
	}

	/**
	 * Fires after a successful X Conversion API request.
	 *
	 * @param array  $conversion Payload row.
	 * @param int    $code       HTTP status.
	 * @param string $body       Response body.
	 */
	do_action( 'snf_x_ads_conversion_sent', $conversion, $code, $body );

	return true;
}

/**
 * Exact X base code once per page, before </head> (wp_head).
 */
function snf_x_ads_pixel_print_base() {
	if ( is_admin() || ! snf_x_ads_pixel_is_enabled() ) {
		return;
	}
	?>
<!-- X conversion tracking base code -->
<script>
!function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments);
},s.version='1.1',s.queue=[],u=t.createElement(n),u.async=!0,u.src='https://static.ads-twitter.com/uwt.js',
a=t.getElementsByTagName(n)[0],a.parentNode.insertBefore(u,a))}(window,document,'script');
twq('config','pp813');
</script>
<!-- End X conversion tracking base code -->
	<?php
}
add_action( 'wp_head', 'snf_x_ads_pixel_print_base', 50 );

/**
 * Queue a browser twq('event') with conversion_id for dedupe with CAPI.
 *
 * @param string               $event_id Event id.
 * @param string               $conversion_id Shared id.
 * @param array<string, mixed> $params Extra twq params.
 */
function snf_x_ads_pixel_track_event( $event_id, $conversion_id = '', array $params = array() ) {
	if ( ! snf_x_ads_pixel_is_enabled() ) {
		return;
	}
	$event_id = snf_x_ads_sanitize_id( $event_id );
	if ( ! $event_id ) {
		return;
	}

	if ( $conversion_id ) {
		$params['conversion_id'] = (string) $conversion_id;
	}

	static $printed = array();
	$key            = $event_id . ':' . md5( wp_json_encode( $params ) );
	if ( isset( $printed[ $key ] ) ) {
		return;
	}
	$printed[ $key ] = true;

	$payload = $params ? ( ', ' . wp_json_encode( $params ) ) : '';
	$js      = sprintf(
		"if(typeof twq==='function'){twq('event','%s'%s);}",
		esc_js( $event_id ),
		$payload
	);

	$print = static function () use ( $js ) {
		echo '<script>' . $js . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	};

	if ( did_action( 'wp_footer' ) || doing_action( 'wp_footer' ) ) {
		$print();
		return;
	}
	add_action( 'wp_footer', $print, 90 );
}

/**
 * Stable conversion id for a Woo order (web + CAPI).
 *
 * @param WC_Order $order Order.
 * @return string
 */
function snf_x_ads_order_conversion_id( $order ) {
	return 'order-' . (string) $order->get_id();
}

/**
 * Server CAPI purchase when payment completes.
 *
 * @param int $order_id Order ID.
 */
function snf_x_ads_capi_on_payment_complete( $order_id ) {
	$order_id = (int) $order_id;
	if ( $order_id <= 0 || ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	if ( 'yes' === $order->get_meta( '_snf_x_ads_capi_purchase' ) ) {
		return;
	}

	$s     = snf_get_x_ads_pixel_settings();
	$event = isset( $s['purchase_event'] ) ? snf_x_ads_sanitize_id( $s['purchase_event'] ) : '';
	if ( ! $event ) {
		return;
	}

	$conversion_id = snf_x_ads_order_conversion_id( $order );
	$result        = snf_x_ads_send_conversion(
		$event,
		$conversion_id,
		array(
			'email'            => $order->get_billing_email(),
			'phone'            => $order->get_billing_phone(),
			'event_source_url' => $order->get_checkout_order_received_url(),
		)
	);

	if ( ! is_wp_error( $result ) ) {
		$order->update_meta_data( '_snf_x_ads_capi_purchase', 'yes' );
		$order->update_meta_data( '_snf_x_ads_conversion_id', $conversion_id );
		$order->save();
	} else {
		$order->add_order_note( 'X CAPI: ' . $result->get_error_message() );
		$order->save();
	}
}
add_action( 'woocommerce_payment_complete', 'snf_x_ads_capi_on_payment_complete', 35 );
add_action( 'woocommerce_order_status_processing', 'snf_x_ads_capi_on_payment_complete', 35 );
add_action( 'woocommerce_order_status_completed', 'snf_x_ads_capi_on_payment_complete', 35 );

/**
 * Browser Purchase event on thank-you (same conversion_id as CAPI).
 *
 * @param int $order_id Order ID.
 */
function snf_x_ads_pixel_on_thankyou( $order_id ) {
	$order_id = (int) $order_id;
	if ( $order_id <= 0 || ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	if ( 'yes' === $order->get_meta( '_snf_x_ads_pixel_purchase' ) ) {
		return;
	}

	$s     = snf_get_x_ads_pixel_settings();
	$event = isset( $s['purchase_event'] ) ? snf_x_ads_sanitize_id( $s['purchase_event'] ) : '';
	if ( ! $event ) {
		return;
	}

	$conversion_id = (string) $order->get_meta( '_snf_x_ads_conversion_id' );
	if ( ! $conversion_id ) {
		$conversion_id = snf_x_ads_order_conversion_id( $order );
	}

	snf_x_ads_pixel_track_event(
		$event,
		$conversion_id,
		array(
			'value'     => (float) $order->get_total(),
			'currency'  => $order->get_currency(),
			'num_items' => (int) $order->get_item_count(),
		)
	);

	$order->update_meta_data( '_snf_x_ads_pixel_purchase', 'yes' );
	$order->save();
}
add_action( 'woocommerce_thankyou', 'snf_x_ads_pixel_on_thankyou', 40 );

/**
 * Queue add-to-cart for browser (optional event id).
 *
 * @param string $cart_item_key Key.
 * @param int    $product_id    Product.
 * @param int    $quantity      Qty.
 */
function snf_x_ads_pixel_on_add_to_cart( $cart_item_key, $product_id, $quantity ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	$s     = snf_get_x_ads_pixel_settings();
	$event = isset( $s['add_to_cart_event'] ) ? snf_x_ads_sanitize_id( $s['add_to_cart_event'] ) : '';
	if ( ! $event ) {
		return;
	}
	WC()->session->set(
		'snf_x_ads_pending_atc',
		array(
			'event' => $event,
			'qty'   => (int) $quantity,
			'id'    => 'atc-' . (int) $product_id . '-' . time(),
		)
	);
}
add_action( 'woocommerce_add_to_cart', 'snf_x_ads_pixel_on_add_to_cart', 40, 3 );

/**
 * Stable checkout conversion id for the Woo session (browser + CAPI).
 *
 * @return string
 */
function snf_x_ads_checkout_conversion_id() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return 'checkout-' . wp_generate_uuid4();
	}
	$existing = (string) WC()->session->get( 'snf_x_ads_checkout_conversion_id' );
	if ( $existing ) {
		return $existing;
	}
	$id = 'checkout-' . wp_generate_uuid4();
	WC()->session->set( 'snf_x_ads_checkout_conversion_id', $id );
	return $id;
}

/**
 * Server CAPI + browser handoff when the customer reaches WooCommerce checkout.
 */
function snf_x_ads_track_checkout_initiated() {
	if ( is_admin() || ! snf_x_ads_pixel_is_enabled() ) {
		return;
	}
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	$s     = snf_get_x_ads_pixel_settings();
	$event = isset( $s['checkout_event'] ) ? snf_x_ads_sanitize_id( $s['checkout_event'] ) : '';
	if ( ! $event ) {
		return;
	}

	if ( ! WC()->session ) {
		return;
	}

	$conversion_id = snf_x_ads_checkout_conversion_id();

	// Server CAPI once per session.
	if ( 'yes' !== WC()->session->get( 'snf_x_ads_capi_checkout' ) ) {
		$email = '';
		$phone = '';
		if ( is_user_logged_in() ) {
			$user  = wp_get_current_user();
			$email = (string) $user->user_email;
		}
		$customer = WC()->customer;
		if ( $customer ) {
			if ( ! $email ) {
				$email = (string) $customer->get_billing_email();
			}
			$phone = (string) $customer->get_billing_phone();
		}

		$result = snf_x_ads_send_conversion(
			$event,
			$conversion_id,
			array(
				'email'            => $email,
				'phone'            => $phone,
				'event_source_url' => wc_get_checkout_url(),
			)
		);
		if ( ! is_wp_error( $result ) ) {
			WC()->session->set( 'snf_x_ads_capi_checkout', 'yes' );
		}
	}

	// Browser event once per session (same conversion_id).
	if ( 'yes' !== WC()->session->get( 'snf_x_ads_pixel_checkout' ) ) {
		$value = (float) WC()->cart->get_total( 'edit' );
		snf_x_ads_pixel_track_event(
			$event,
			$conversion_id,
			array(
				'value'     => $value,
				'currency'  => get_woocommerce_currency(),
				'num_items' => (int) WC()->cart->get_cart_contents_count(),
			)
		);
		WC()->session->set( 'snf_x_ads_pixel_checkout', 'yes' );
	}
}
add_action( 'template_redirect', 'snf_x_ads_track_checkout_initiated', 25 );

/**
 * Clear checkout session flags after a successful order so the next checkout can fire again.
 *
 * @param int $order_id Order ID.
 */
function snf_x_ads_reset_checkout_session_flags( $order_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	WC()->session->set( 'snf_x_ads_capi_checkout', null );
	WC()->session->set( 'snf_x_ads_pixel_checkout', null );
	WC()->session->set( 'snf_x_ads_checkout_conversion_id', null );
}
add_action( 'woocommerce_thankyou', 'snf_x_ads_reset_checkout_session_flags', 5 );

/**
 * Flush browser add-to-cart events.
 */
function snf_x_ads_pixel_flush_session_events() {
	if ( ! snf_x_ads_pixel_is_enabled() || ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	$pending = WC()->session->get( 'snf_x_ads_pending_atc' );
	if ( is_array( $pending ) && ! empty( $pending['event'] ) ) {
		snf_x_ads_pixel_track_event(
			(string) $pending['event'],
			isset( $pending['id'] ) ? (string) $pending['id'] : '',
			array( 'num_items' => isset( $pending['qty'] ) ? (int) $pending['qty'] : 1 )
		);
		WC()->session->set( 'snf_x_ads_pending_atc', null );
	}
}
add_action( 'wp_footer', 'snf_x_ads_pixel_flush_session_events', 85 );

/**
 * Server CAPI + browser handoff for Apply sign-up.
 *
 * @param string $plan_slug    Plan.
 * @param string $checkout_url Checkout URL.
 * @param int    $user_id      User.
 */
function snf_x_ads_on_apply_checkout( $plan_slug, $checkout_url, $user_id ) {
	$s     = snf_get_x_ads_pixel_settings();
	$event = isset( $s['signup_event'] ) ? snf_x_ads_sanitize_id( $s['signup_event'] ) : '';
	if ( ! $event ) {
		$event = isset( $s['complete_reg_event'] ) ? snf_x_ads_sanitize_id( $s['complete_reg_event'] ) : '';
	}
	if ( ! $event ) {
		return;
	}

	$user_id       = (int) $user_id;
	$conversion_id = 'signup-' . ( $user_id ? $user_id : wp_generate_uuid4() ) . '-' . time();
	$email         = '';
	if ( $user_id ) {
		$user = get_userdata( $user_id );
		if ( $user ) {
			$email = (string) $user->user_email;
		}
	}

	snf_x_ads_send_conversion(
		$event,
		$conversion_id,
		array(
			'email'            => $email,
			'event_source_url' => $checkout_url ? (string) $checkout_url : home_url( '/apply/' ),
		)
	);

	if ( function_exists( 'WC' ) ) {
		if ( function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
		if ( WC()->session ) {
			WC()->session->set(
				'snf_x_ads_pending_signup',
				array(
					'event' => $event,
					'id'    => $conversion_id,
				)
			);
		}
	}
}
add_action( 'snf_apply_checkout_prepared', 'snf_x_ads_on_apply_checkout', 40, 3 );

/**
 * Browser signup event after Apply redirect (shared conversion_id).
 */
function snf_x_ads_pixel_flush_signup() {
	if ( ! snf_x_ads_pixel_is_enabled() || ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	$pending = WC()->session->get( 'snf_x_ads_pending_signup' );
	if ( ! is_array( $pending ) || empty( $pending['event'] ) ) {
		return;
	}
	snf_x_ads_pixel_track_event(
		(string) $pending['event'],
		isset( $pending['id'] ) ? (string) $pending['id'] : ''
	);
	WC()->session->set( 'snf_x_ads_pending_signup', null );
}
add_action( 'wp_footer', 'snf_x_ads_pixel_flush_signup', 84 );
