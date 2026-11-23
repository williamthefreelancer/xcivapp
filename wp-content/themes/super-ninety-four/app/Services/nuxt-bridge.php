<?php
/**
 * Nuxt hybrid bridge — CORS, auth tokens, My Account / Club redirects.
 *
 * @package SuperNinetyFour
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public Nuxt app base URL (no trailing slash).
 *
 * @return string
 */
function snf_nuxt_app_url() {
	if ( defined( 'SNF_NUXT_APP_URL' ) && SNF_NUXT_APP_URL ) {
		return untrailingslashit( (string) SNF_NUXT_APP_URL );
	}

	$opt = get_option( 'snf_nuxt_app_url', '' );
	if ( is_string( $opt ) && $opt ) {
		return untrailingslashit( $opt );
	}

	// Local default when bridge is enabled without a define.
	if ( snf_nuxt_bridge_enabled() ) {
		return 'http://localhost:3000';
	}

	return '';
}

/**
 * Whether the Nuxt bridge redirects / CORS are active.
 *
 * @return bool
 */
function snf_nuxt_bridge_enabled() {
	if ( defined( 'SNF_NUXT_BRIDGE' ) ) {
		return (bool) SNF_NUXT_BRIDGE;
	}

	return (bool) get_option( 'snf_nuxt_bridge_enabled', false );
}

/**
 * Allowed Nuxt origins for CORS (credentials).
 *
 * @return string[]
 */
function snf_nuxt_allowed_origins() {
	$origins = array();
	$app     = snf_nuxt_app_url();
	if ( $app ) {
		$parts = wp_parse_url( $app );
		if ( ! empty( $parts['scheme'] ) && ! empty( $parts['host'] ) ) {
			$port = isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '';
			$origins[] = $parts['scheme'] . '://' . $parts['host'] . $port;
		}
	}

	$origins[] = 'http://localhost:3000';
	$origins[] = 'http://127.0.0.1:3000';

	/**
	 * Filter allowed Nuxt CORS origins.
	 *
	 * @param string[] $origins Origins.
	 */
	return array_values( array_unique( apply_filters( 'snf_nuxt_allowed_origins', $origins ) ) );
}

/**
 * Signing key for bridge tokens.
 *
 * @return string
 */
function snf_nuxt_token_secret() {
	if ( defined( 'AUTH_KEY' ) && AUTH_KEY ) {
		return (string) AUTH_KEY;
	}
	return wp_salt( 'auth' );
}

/**
 * Issue a short-lived bearer token for the Nuxt app.
 *
 * @param int $user_id User ID.
 * @param int $ttl     Seconds.
 * @return string
 */
function snf_nuxt_issue_token( $user_id, $ttl = 43200 ) {
	$user_id = absint( $user_id );
	if ( $user_id <= 0 ) {
		return '';
	}

	$exp  = time() + max( 300, (int) $ttl );
	$body = $user_id . '|' . $exp;
	$sig  = hash_hmac( 'sha256', $body, snf_nuxt_token_secret() );
	return rtrim( strtr( base64_encode( $body . '|' . $sig ), '+/', '-_' ), '=' );
}

/**
 * Validate a Nuxt bearer token.
 *
 * @param string $token Token.
 * @return int User ID or 0.
 */
function snf_nuxt_validate_token( $token ) {
	$token = is_string( $token ) ? trim( $token ) : '';
	if ( '' === $token ) {
		return 0;
	}

	$b64 = strtr( $token, '-_', '+/' );
	$pad = strlen( $b64 ) % 4;
	if ( $pad ) {
		$b64 .= str_repeat( '=', 4 - $pad );
	}
	$raw = base64_decode( $b64, true );
	if ( ! is_string( $raw ) || '' === $raw ) {
		return 0;
	}

	$parts = explode( '|', $raw );
	if ( count( $parts ) !== 3 ) {
		return 0;
	}

	list( $user_id, $exp, $sig ) = $parts;
	$user_id = absint( $user_id );
	$exp     = absint( $exp );
	if ( $user_id <= 0 || $exp < time() ) {
		return 0;
	}

	$expected = hash_hmac( 'sha256', $user_id . '|' . $exp, snf_nuxt_token_secret() );
	if ( ! hash_equals( $expected, $sig ) ) {
		return 0;
	}

	return $user_id;
}

/**
 * Authenticate REST requests via Bearer bridge token.
 *
 * @param int|false $user Existing user.
 * @return int|false
 */
function snf_nuxt_rest_authenticate( $user ) {
	if ( $user ) {
		return $user;
	}

	$header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? (string) $_SERVER['HTTP_AUTHORIZATION'] : '';
	if ( ! $header && isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
		$header = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ;
	}
	if ( ! $header && function_exists( 'getallheaders' ) ) {
		$headers = getallheaders();
		if ( is_array( $headers ) ) {
			foreach ( $headers as $key => $value ) {
				if ( 'authorization' === strtolower( (string) $key ) ) {
					$header = (string) $value;
					break;
				}
			}
		}
	}

	if ( ! preg_match( '/Bearer\s+(\S+)/i', $header, $m ) ) {
		return $user;
	}

	$token_user = snf_nuxt_validate_token( $m[1] );
	return $token_user ? $token_user : $user;
}
add_filter( 'determine_current_user', 'snf_nuxt_rest_authenticate', 20 );

/**
 * Send CORS headers for Nuxt origins on REST + preflight.
 */
function snf_nuxt_send_cors_headers() {
	if ( ! snf_nuxt_bridge_enabled() && empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		return;
	}

	$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? (string) $_SERVER['HTTP_ORIGIN'] : '';
	if ( ! $origin || ! in_array( $origin, snf_nuxt_allowed_origins(), true ) ) {
		return;
	}

	header( 'Access-Control-Allow-Origin: ' . $origin );
	header( 'Access-Control-Allow-Credentials: true' );
	header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce, X-Requested-With' );
	header( 'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS' );
	header( 'Vary: Origin' );

	if ( 'OPTIONS' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		status_header( 204 );
		exit;
	}
}
add_action( 'rest_api_init', 'snf_nuxt_send_cors_headers', 1 );
add_action( 'init', 'snf_nuxt_send_cors_headers', 1 );

/**
 * Map a Woo My Account endpoint to a Nuxt /app path.
 *
 * @param string $endpoint Endpoint.
 * @return string
 */
function snf_nuxt_account_path_for_endpoint( $endpoint = '' ) {
	$endpoint = sanitize_title( (string) $endpoint );
	$map      = array(
		''             => '/app',
		'dashboard'    => '/app',
		'orders'       => '/app/orders',
		'view-order'   => '/app/orders',
		'tickets'      => '/app/tickets',
		'membership'   => '/app/membership',
		'edit-address' => '/app/billing',
		'edit-account' => '/app/account',
		'payment-methods' => '/app/billing',
	);

	/**
	 * Filter Nuxt path for a Woo account endpoint.
	 *
	 * @param string $path     Path.
	 * @param string $endpoint Endpoint.
	 */
	return apply_filters(
		'snf_nuxt_account_path_for_endpoint',
		isset( $map[ $endpoint ] ) ? $map[ $endpoint ] : '/app',
		$endpoint
	);
}

/**
 * Build Nuxt URL with optional auth token query.
 *
 * @param string $path    Path starting with /.
 * @param int    $user_id Optional user for token.
 * @return string
 */
function snf_nuxt_url( $path = '/app', $user_id = 0 ) {
	$base = snf_nuxt_app_url();
	if ( ! $base ) {
		return '';
	}

	$path = '/' . ltrim( (string) $path, '/' );
	$url  = $base . $path;

	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $user_id > 0 ) {
		$token = snf_nuxt_issue_token( $user_id );
		if ( $token ) {
			$url = add_query_arg( 'snf_auth', rawurlencode( $token ), $url );
		}
	}

	return $url;
}

/**
 * Redirect Woo My Account (and custom endpoints) to Nuxt when bridge is on.
 */
function snf_nuxt_redirect_account_pages() {
	if ( ! snf_nuxt_bridge_enabled() || is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	// Allow classic Woo account when ?classic=1.
	if ( isset( $_GET['classic'] ) && '1' === (string) $_GET['classic'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}

	$endpoint = '';
	if ( function_exists( 'WC' ) && WC()->query ) {
		$endpoint = WC()->query->get_current_endpoint();
	}
	if ( ! $endpoint && function_exists( 'snf_get_current_account_endpoint' ) ) {
		$endpoint = snf_get_current_account_endpoint();
	}

	// Keep checkout-adjacent payment flows on Woo when classic is forced;
	// headless checkout lives on Nuxt — still skip order-pay for gateway return URLs.
	if ( in_array( $endpoint, array( 'order-pay', 'lost-password' ), true ) ) {
		return;
	}

	$target = snf_nuxt_url( snf_nuxt_account_path_for_endpoint( $endpoint ) );
	if ( ! $target ) {
		return;
	}

	wp_safe_redirect( $target, 302 );
	exit;
}
add_action( 'template_redirect', 'snf_nuxt_redirect_account_pages', 20 );

/**
 * Redirect public WP front routes to Nuxt when the headless bridge is on.
 * Keeps wp-admin, REST, cron, webhooks, and ?classic=1 on WordPress.
 */
function snf_nuxt_redirect_public_front() {
	if ( ! snf_nuxt_bridge_enabled() || is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	// Allow classic Woo/PHP when ?classic=1.
	if ( isset( $_GET['classic'] ) && '1' === (string) $_GET['classic'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	if ( preg_match( '#/(wp-json|wp-admin|wp-login\.php|xmlrpc\.php)#', $request_uri ) ) {
		return;
	}
	// Stripe / payment webhooks stay on WP.
	if ( false !== strpos( $request_uri, 'snf' ) && false !== strpos( $request_uri, 'webhook' ) ) {
		return;
	}

	$path = '';
	if ( is_front_page() || is_home() ) {
		$path = '/';
	} elseif ( function_exists( 'is_cart' ) && is_cart() ) {
		$path = '/cart';
	} elseif ( function_exists( 'is_checkout' ) && is_checkout() && ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-pay' ) ) ) {
		$path = '/checkout';
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$path = '/shop';
	} elseif ( is_singular( 'event' ) || ( function_exists( 'is_product' ) && is_product() && has_term( 'events', 'product_cat' ) ) ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		$path = $slug ? '/events/' . $slug : '/events';
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		$path = $slug ? '/product/' . $slug : '/shop';
	} elseif ( is_post_type_archive( 'event' ) ) {
		$path = '/events';
	} elseif ( is_singular( array( 'snf_video', 'snf_audio' ) ) ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		$type = get_post_type();
		$path = '/media/' . ( 'snf_audio' === $type ? 'audio' : 'video' ) . '/' . $slug;
	} elseif ( is_page() ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		if ( $slug && function_exists( 'snf_nuxt_path_for_slug' ) ) {
			$path = snf_nuxt_path_for_slug( $slug );
		}
	}

	if ( ! $path ) {
		return;
	}

	// Avoid redirect loop when Nuxt and WP share origin via reverse proxy —
	// only redirect when the Nuxt app URL host differs from the current host,
	// or when SNF_NUXT_FORCE_PUBLIC_REDIRECT is true.
	$app = snf_nuxt_app_url();
	if ( ! $app ) {
		return;
	}
	$app_host  = (string) wp_parse_url( $app, PHP_URL_HOST );
	$site_host = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	$force     = defined( 'SNF_NUXT_FORCE_PUBLIC_REDIRECT' ) && SNF_NUXT_FORCE_PUBLIC_REDIRECT;
	if ( ! $force && $app_host && $site_host && strtolower( $app_host ) === strtolower( $site_host ) ) {
		// Same host: reverse proxy owns routing; do not 302.
		return;
	}

	$target = untrailingslashit( $app ) . ( '/' === $path ? '/' : $path );
	if ( is_user_logged_in() ) {
		$token = snf_nuxt_issue_token( get_current_user_id() );
		if ( $token ) {
			$target = add_query_arg( 'snf_auth', rawurlencode( $token ), $target );
		}
	}

	wp_safe_redirect( $target, 302 );
	exit;
}
add_action( 'template_redirect', 'snf_nuxt_redirect_public_front', 15 );

/**
 * Add “Open Club UI” link under SNF Club.
 */
function snf_nuxt_admin_menu_link() {
	if ( ! snf_nuxt_bridge_enabled() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$url = snf_nuxt_url( '/club' );
	if ( ! $url ) {
		return;
	}

	add_submenu_page(
		'snf-club',
		__( 'Open Club UI (Nuxt)', 'super-ninety-four' ),
		__( 'Open Club UI', 'super-ninety-four' ),
		'manage_options',
		'snf-nuxt-club',
		static function () use ( $url ) {
			wp_safe_redirect( $url );
			exit;
		}
	);
}
add_action( 'admin_menu', 'snf_nuxt_admin_menu_link', 80 );

/**
 * Admin notice when bridge is enabled.
 */
function snf_nuxt_admin_notice() {
	if ( ! snf_nuxt_bridge_enabled() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! function_exists( 'snf_admin_is_club_screen' ) || ! snf_admin_is_club_screen() ) {
		return;
	}

	$url = snf_nuxt_app_url();
	echo '<div class="notice notice-info"><p>';
	echo esc_html__( 'Nuxt headless bridge is enabled. Public front and My Account resolve to the Nuxt app.', 'super-ninety-four' );
	if ( $url ) {
		echo ' <a href="' . esc_url( snf_nuxt_url( '/club' ) ) . '">' . esc_html__( 'Open Club UI', 'super-ninety-four' ) . '</a>';
	}
	echo '</p></div>';
}
add_action( 'admin_notices', 'snf_nuxt_admin_notice' );
