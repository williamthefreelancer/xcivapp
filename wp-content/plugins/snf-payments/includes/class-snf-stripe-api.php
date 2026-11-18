<?php
/**
 * Stripe API helpers for the SNF Payments gateway.
 *
 * Prefers theme helpers (snf_stripe_*) when available so wp-config / SNF Stripe
 * settings stay the source of truth.
 *
 * @package SNF_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stripe credential + request layer.
 */
class SNF_Payments_Stripe_API {

	/**
	 * Gateway settings option key (WooCommerce stores as woocommerce_{id}_settings).
	 *
	 * @return array<string, string>
	 */
	public static function gateway_settings() {
		$stored = get_option( 'woocommerce_' . SNF_PAYMENTS_GATEWAY_ID . '_settings', array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Active mode: test|live.
	 *
	 * @return string
	 */
	public static function mode() {
		if ( function_exists( 'snf_get_stripe_settings' ) ) {
			$s = snf_get_stripe_settings();
			if ( ! empty( $s['mode'] ) ) {
				return ( 'live' === $s['mode'] ) ? 'live' : 'test';
			}
		}
		$g = self::gateway_settings();
		return ( isset( $g['mode'] ) && 'live' === $g['mode'] ) ? 'live' : 'test';
	}

	/**
	 * Secret key for active mode.
	 *
	 * @return string
	 */
	public static function secret_key() {
		if ( function_exists( 'snf_stripe_get_secret_key' ) ) {
			$key = (string) snf_stripe_get_secret_key();
			if ( $key ) {
				return $key;
			}
		}
		$g    = self::gateway_settings();
		$mode = self::mode();
		if ( 'live' === $mode ) {
			return isset( $g['secret_key'] ) ? (string) $g['secret_key'] : '';
		}
		if ( ! empty( $g['test_secret_key'] ) ) {
			return (string) $g['test_secret_key'];
		}
		return isset( $g['secret_key'] ) ? (string) $g['secret_key'] : '';
	}

	/**
	 * Publishable key for active mode.
	 *
	 * @return string
	 */
	public static function publishable_key() {
		if ( function_exists( 'snf_stripe_get_publishable_key' ) ) {
			$key = (string) snf_stripe_get_publishable_key();
			if ( $key ) {
				return $key;
			}
		}
		$g    = self::gateway_settings();
		$mode = self::mode();
		if ( 'live' === $mode ) {
			return isset( $g['publishable_key'] ) ? (string) $g['publishable_key'] : '';
		}
		if ( ! empty( $g['test_publishable_key'] ) ) {
			return (string) $g['test_publishable_key'];
		}
		return isset( $g['publishable_key'] ) ? (string) $g['publishable_key'] : '';
	}

	/**
	 * Webhook signing secret for active mode.
	 *
	 * @return string
	 */
	public static function webhook_secret() {
		if ( function_exists( 'snf_stripe_get_webhook_secret' ) ) {
			$key = (string) snf_stripe_get_webhook_secret();
			if ( $key ) {
				return $key;
			}
		}
		$g    = self::gateway_settings();
		$mode = self::mode();
		if ( 'live' === $mode ) {
			return isset( $g['webhook_secret'] ) ? (string) $g['webhook_secret'] : '';
		}
		if ( ! empty( $g['test_webhook_secret'] ) ) {
			return (string) $g['test_webhook_secret'];
		}
		return isset( $g['webhook_secret'] ) ? (string) $g['webhook_secret'] : '';
	}

	/**
	 * Whether Stripe is configured enough to charge.
	 *
	 * @return bool
	 */
	public static function is_ready() {
		return (bool) self::secret_key() && (bool) self::publishable_key();
	}

	/**
	 * Perform a Stripe API request.
	 *
	 * @param string               $method GET|POST|DELETE.
	 * @param string               $path   Path after /v1/.
	 * @param array<string, mixed> $body   Body params.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function request( $method, $path, $body = array() ) {
		if ( function_exists( 'snf_stripe_request' ) ) {
			return snf_stripe_request( $method, $path, $body );
		}

		$secret = self::secret_key();
		if ( ! $secret ) {
			return new WP_Error( 'snf_payments_no_key', __( 'Stripe secret key is not configured.', 'snf-payments' ) );
		}

		$path = '/' . ltrim( (string) $path, '/' );
		if ( 0 !== strpos( $path, '/v1/' ) ) {
			$path = '/v1/' . ltrim( $path, '/' );
		}

		$method = strtoupper( (string) $method );
		$url    = 'https://api.stripe.com' . $path;
		$args   = array(
			'method'  => $method,
			'timeout' => 45,
			'headers' => array(
				'Authorization'  => 'Bearer ' . $secret,
				'Stripe-Version' => '2024-06-20',
			),
		);

		if ( 'GET' !== $method && $body ) {
			$args['body'] = self::flatten( $body );
		} elseif ( 'GET' === $method && $body ) {
			$url = add_query_arg( self::flatten( $body ), $url );
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'snf_payments_bad_json', __( 'Invalid Stripe API response.', 'snf-payments' ) );
		}
		if ( $code >= 400 ) {
			$message = isset( $data['error']['message'] ) ? (string) $data['error']['message'] : __( 'Stripe API error.', 'snf-payments' );
			return new WP_Error( 'snf_payments_api', $message, array( 'status' => $code, 'body' => $data ) );
		}

		return $data;
	}

	/**
	 * Flatten nested arrays for Stripe form encoding.
	 *
	 * @param array<string, mixed> $data Nested data.
	 * @param string               $prefix Prefix.
	 * @return array<string, mixed>
	 */
	protected static function flatten( array $data, $prefix = '' ) {
		$out = array();
		foreach ( $data as $key => $value ) {
			$full = $prefix ? $prefix . '[' . $key . ']' : (string) $key;
			if ( is_array( $value ) ) {
				$out = array_merge( $out, self::flatten( $value, $full ) );
			} else {
				$out[ $full ] = $value;
			}
		}
		return $out;
	}

	/**
	 * Convert WC order total to Stripe minor units.
	 *
	 * @param float  $amount   Decimal amount.
	 * @param string $currency Currency code.
	 * @return int
	 */
	public static function amount_to_cents( $amount, $currency = 'usd' ) {
		$currency = strtolower( (string) $currency );
		$zero_dec = array( 'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf' );
		if ( in_array( $currency, $zero_dec, true ) ) {
			return (int) round( (float) $amount );
		}
		return (int) round( (float) $amount * 100 );
	}
}
