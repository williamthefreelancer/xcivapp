<?php
/**
 * Supabase Stripe Sync Engine bridge (Data Management → stripe_sdp_data).
 *
 * Uses project SUPER NINETY FOUR PAYMENTS RPCs:
 * - public.get_stripe_catalog()
 * - public.get_stripe_checkout_by_reference(ref)   [service_role]
 * - public.get_stripe_subscriptions_by_email(email) [service_role]
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Option key for Supabase payments bridge settings. */
const SNF_SUPABASE_PAYMENTS_OPTION = 'snf_supabase_payments_settings';

/**
 * Default Supabase payments settings.
 *
 * @return array<string, string>
 */
function snf_supabase_payments_default_settings() {
	return array(
		'enabled'          => 'yes',
		// SUPER NINETY FOUR PAYMENTS (Stripe Sync Engine / Data Management).
		'url'              => 'https://evzjkydniknyfjftfutv.supabase.co',
		// Legacy anon JWT — publishable; override via SNF_SUPABASE_ANON_KEY or admin settings.
		'anon_key'         => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImV2empreWRuaWtueWZqZnRmdXR2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3OTQ5MzI5MzYsImV4cCI6MjExMDUwODkzNn0._Oz-LDSf462ooz1DODSUOElAtVQXSXDqo8UOGlXGVrw',
		'service_role_key' => '',
		'price_map'        => '{"application":"price_1TuKVxEPYpF6rD2fQR8MWyPo","add-on":"price_1TuKf1EPYpF6rD2fktLdTyjV"}',
	);
}

/**
 * Merged settings (wp-config constants win).
 *
 * @return array<string, string>
 */
function snf_get_supabase_payments_settings() {
	$stored = get_option( SNF_SUPABASE_PAYMENTS_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	$settings = array_merge( snf_supabase_payments_default_settings(), $stored );

	if ( defined( 'SNF_SUPABASE_URL' ) && SNF_SUPABASE_URL ) {
		$settings['url'] = (string) SNF_SUPABASE_URL;
	}
	if ( defined( 'SNF_SUPABASE_ANON_KEY' ) && SNF_SUPABASE_ANON_KEY ) {
		$settings['anon_key'] = (string) SNF_SUPABASE_ANON_KEY;
	}
	if ( defined( 'SNF_SUPABASE_SERVICE_ROLE_KEY' ) && SNF_SUPABASE_SERVICE_ROLE_KEY ) {
		$settings['service_role_key'] = (string) SNF_SUPABASE_SERVICE_ROLE_KEY;
	}

	return $settings;
}

/**
 * Whether the Supabase payments bridge can call the public catalog RPC.
 *
 * @return bool
 */
function snf_supabase_payments_is_ready() {
	$s = snf_get_supabase_payments_settings();
	return ( 'yes' === $s['enabled'] && $s['url'] && $s['anon_key'] );
}

/**
 * Whether privileged Stripe sync lookups are available.
 *
 * @return bool
 */
function snf_supabase_payments_has_service_role() {
	$s = snf_get_supabase_payments_settings();
	return (bool) ( $s['url'] && $s['service_role_key'] );
}

/**
 * Call a Supabase RPC.
 *
 * @param string               $fn      Function name.
 * @param array<string, mixed> $args    JSON body args.
 * @param bool                 $service Use service_role key when true.
 * @return array<mixed>|WP_Error
 */
function snf_supabase_rpc( $fn, $args = array(), $service = false ) {
	$s = snf_get_supabase_payments_settings();
	$key = $service ? $s['service_role_key'] : $s['anon_key'];
	if ( ! $s['url'] || ! $key ) {
		return new WP_Error( 'snf_supabase_cfg', __( 'Supabase payments bridge is not configured.', 'super-ninety-four' ) );
	}

	$url  = trailingslashit( $s['url'] ) . 'rest/v1/rpc/' . rawurlencode( $fn );
	$res  = wp_remote_post(
		$url,
		array(
			'timeout' => 20,
			'headers' => array(
				'apikey'        => $key,
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'    => wp_json_encode( (object) $args ),
		)
	);

	if ( is_wp_error( $res ) ) {
		return $res;
	}

	$code = (int) wp_remote_retrieve_response_code( $res );
	$body = (string) wp_remote_retrieve_body( $res );
	$data = json_decode( $body, true );

	if ( $code < 200 || $code >= 300 ) {
		$message = is_array( $data ) && isset( $data['message'] ) ? (string) $data['message'] : __( 'Supabase RPC error.', 'super-ninety-four' );
		return new WP_Error( 'snf_supabase_rpc', $message, array( 'status' => $code, 'body' => $data ) );
	}

	return is_array( $data ) ? $data : array();
}

/**
 * Active Stripe catalog from Sync Engine (cached briefly).
 *
 * @param bool $force Bypass transient.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function snf_supabase_get_stripe_catalog( $force = false ) {
	$cache_key = 'snf_supabase_stripe_catalog_v1';
	if ( ! $force ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	if ( ! snf_supabase_payments_is_ready() ) {
		return new WP_Error( 'snf_supabase_not_ready', __( 'Supabase Stripe catalog is not connected.', 'super-ninety-four' ) );
	}

	$rows = snf_supabase_rpc( 'get_stripe_catalog' );
	if ( is_wp_error( $rows ) ) {
		return $rows;
	}

	set_transient( $cache_key, $rows, 5 * MINUTE_IN_SECONDS );
	return $rows;
}

/**
 * Decode admin price map JSON.
 *
 * @return array<string, string>
 */
function snf_supabase_get_price_map() {
	$s    = snf_get_supabase_payments_settings();
	$raw  = trim( (string) $s['price_map'] );
	if ( '' === $raw ) {
		return array();
	}
	$decoded = json_decode( $raw, true );
	if ( ! is_array( $decoded ) ) {
		return array();
	}
	$out = array();
	foreach ( $decoded as $k => $v ) {
		$slug = sanitize_title( (string) $k );
		$pid  = sanitize_text_field( (string) $v );
		if ( $slug && 0 === strpos( $pid, 'price_' ) ) {
			$out[ $slug ] = $pid;
		}
	}
	return $out;
}

/**
 * Resolve a Stripe Price ID for a logical SKU / plan slug.
 *
 * Order: explicit map → lookup_key → metadata.snf_slug → fuzzy name match.
 *
 * @param string $slug Plan / program / application slug.
 * @return string Empty when unresolved.
 */
function snf_supabase_resolve_price_id( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( ! $slug ) {
		return '';
	}

	$map = snf_supabase_get_price_map();
	if ( ! empty( $map[ $slug ] ) ) {
		return $map[ $slug ];
	}

	$catalog = snf_supabase_get_stripe_catalog();
	if ( is_wp_error( $catalog ) || ! $catalog ) {
		return '';
	}

	foreach ( $catalog as $row ) {
		$lookup = isset( $row['lookup_key'] ) ? sanitize_title( (string) $row['lookup_key'] ) : '';
		if ( $lookup && $lookup === $slug && ! empty( $row['price_id'] ) ) {
			return (string) $row['price_id'];
		}
		$meta = isset( $row['price_metadata'] ) && is_array( $row['price_metadata'] ) ? $row['price_metadata'] : array();
		$pmeta = isset( $row['product_metadata'] ) && is_array( $row['product_metadata'] ) ? $row['product_metadata'] : array();
		foreach ( array( $meta, $pmeta ) as $bag ) {
			foreach ( array( 'snf_slug', 'slug', 'plan', 'program' ) as $key ) {
				if ( ! empty( $bag[ $key ] ) && sanitize_title( (string) $bag[ $key ] ) === $slug && ! empty( $row['price_id'] ) ) {
					return (string) $row['price_id'];
				}
			}
		}
	}

	// Convenience aliases for the live catalog already synced.
	$aliases = array(
		'application'      => '4-week',
		'apply'            => '4-week',
		'city-club'        => '4-week',
		'4-week-experience'=> '4-week',
		'add-on'           => 'add-on',
		'addon'            => 'add-on',
	);
	$needle = isset( $aliases[ $slug ] ) ? $aliases[ $slug ] : $slug;

	foreach ( $catalog as $row ) {
		$name = isset( $row['product_name'] ) ? strtolower( (string) $row['product_name'] ) : '';
		if ( ! $name || empty( $row['price_id'] ) ) {
			continue;
		}
		if ( '4-week' === $needle && false !== strpos( $name, '4-week' ) ) {
			return (string) $row['price_id'];
		}
		if ( 'add-on' === $needle && false !== strpos( $name, 'add-on' ) ) {
			return (string) $row['price_id'];
		}
		if ( false !== strpos( sanitize_title( $name ), $slug ) ) {
			return (string) $row['price_id'];
		}
	}

	return '';
}

/**
 * Checkout sessions for a WP user (via Sync Engine).
 *
 * @param int $user_id User ID.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function snf_supabase_get_checkout_sessions_for_user( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 || ! snf_supabase_payments_has_service_role() ) {
		return new WP_Error( 'snf_supabase_no_service', __( 'Supabase service role key required for session lookup.', 'super-ninety-four' ) );
	}

	$user = get_userdata( $user_id );
	$refs = array_filter(
		array(
			(string) $user_id,
			$user ? (string) $user->user_email : '',
			'app_' . $user_id,
		)
	);

	$all = array();
	foreach ( $refs as $ref ) {
		$rows = snf_supabase_rpc( 'get_stripe_checkout_by_reference', array( 'ref' => $ref ), true );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
		foreach ( $rows as $row ) {
			if ( ! empty( $row['id'] ) ) {
				$all[ (string) $row['id'] ] = $row;
			}
		}
	}

	return array_values( $all );
}
