<?php
/**
 * Headless content, forms, auth, reciprocal, and account mutation REST.
 *
 * Namespace: snf/v1
 *
 * @package SuperNinetyFour
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map a site page slug to a Nuxt path (leading slash).
 *
 * @param string $slug WP page / surface slug.
 * @return string
 */
function snf_nuxt_path_for_slug( $slug ) {
	$slug = sanitize_title( (string) $slug );
	$map  = array(
		'home'                               => '/',
		'about'                              => '/about',
		'contact'                            => '/contact',
		'newsletter'                         => '/newsletter',
		'apply'                              => '/apply',
		'under-consideration'                => '/under-consideration',
		'inquiries'                          => '/inquiries',
		'videos-audio'                       => '/media',
		'find-other-reciprocal-programs'     => '/reciprocal',
		'expo'                               => '/expo',
		'community'                          => '/community',
		'messages'                           => '/messages',
		'member-login'                       => '/login',
		'billing-details'                    => '/app/billing',
		'privacy-policy'                     => '/legal/privacy-policy',
		'terms-of-service'                   => '/legal/terms-of-service',
		'cookie-policy'                      => '/legal/cookie-policy',
		'refund_returns'                     => '/legal/refund-returns',
		'select-events-by-members'           => '/events',
		'select-events-by-non-members'       => '/events',
		'cart'                               => '/cart',
		'checkout'                           => '/checkout',
		'shop'                               => '/shop',
	);

	if ( isset( $map[ $slug ] ) ) {
		return $map[ $slug ];
	}

	if ( function_exists( 'snf_get_reciprocal_clubs' ) ) {
		$clubs = snf_get_reciprocal_clubs();
		if ( isset( $clubs[ $slug ] ) ) {
			return '/reciprocal/' . $slug;
		}
	}

	return '/' . $slug;
}

/**
 * Absolute Nuxt URL for a slug (no auth token).
 *
 * @param string $slug Slug.
 * @return string
 */
function snf_nuxt_public_url_for_slug( $slug ) {
	$path = snf_nuxt_path_for_slug( $slug );
	$base = function_exists( 'snf_nuxt_app_url' ) ? snf_nuxt_app_url() : '';
	if ( $base && function_exists( 'snf_nuxt_bridge_enabled' ) && snf_nuxt_bridge_enabled() ) {
		return untrailingslashit( $base ) . ( '/' === $path ? '/' : $path );
	}
	return function_exists( 'snf_get_page_url' ) ? snf_get_page_url( $slug ) : home_url( '/' . $slug . '/' );
}

/**
 * Rewrite absolute/relative WP URLs inside content payloads to Nuxt paths when bridge is on.
 *
 * @param mixed $data Data.
 * @return mixed
 */
function snf_rest_rewrite_urls_for_nuxt( $data ) {
	if ( ! function_exists( 'snf_nuxt_bridge_enabled' ) || ! snf_nuxt_bridge_enabled() ) {
		return $data;
	}

	$base = function_exists( 'snf_nuxt_app_url' ) ? snf_nuxt_app_url() : '';
	if ( ! $base ) {
		return $data;
	}

	if ( is_string( $data ) ) {
		if ( ! preg_match( '#^https?://#i', $data ) && 0 !== strpos( $data, '/' ) ) {
			return $data;
		}
		$path = (string) wp_parse_url( $data, PHP_URL_PATH );
		$path = $path ? untrailingslashit( $path ) : '';
		// Strip WP install subdirectory (e.g. /super-ninety-four).
		$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = $home_path ? untrailingslashit( $home_path ) : '';
		if ( $home_path && 0 === strpos( $path, $home_path ) ) {
			$path = substr( $path, strlen( $home_path ) ) ?: '';
		}
		$slug = sanitize_title( basename( $path ) );
		$pages = function_exists( 'snf_get_site_pages' ) ? snf_get_site_pages() : array();
		if ( $slug && ( isset( $pages[ $slug ] ) || ( function_exists( 'snf_get_reciprocal_clubs' ) && isset( snf_get_reciprocal_clubs()[ $slug ] ) ) ) ) {
			$nuxt_path = snf_nuxt_path_for_slug( $slug );
			return untrailingslashit( $base ) . ( '/' === $nuxt_path ? '/' : $nuxt_path );
		}
		return $data;
	}

	if ( is_array( $data ) ) {
		$out = array();
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, array( 'url', 'cta_url', 'href', 'permalink', 'editUrl', 'dashboardHref', 'listHref', 'loginUrl', 'logoutUrl', 'cartUrl', 'checkoutUrl' ), true ) ) {
				$out[ $key ] = snf_rest_rewrite_urls_for_nuxt( $value );
			} elseif ( is_array( $value ) ) {
				$out[ $key ] = snf_rest_rewrite_urls_for_nuxt( $value );
			} else {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}

	return $data;
}

/**
 * Register content / form / auth / reciprocal routes.
 */
function snf_rest_content_register_routes() {
	$ns = 'snf/v1';

	register_rest_route(
		$ns,
		'/content/home',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_content_home',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/content/about',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_content_about',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/content/contact',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_content_contact',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/content/newsletter',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_content_newsletter',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/content/apply',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_content_apply',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/content/legal/(?P<slug>[a-z0-9\-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_content_legal',
			'permission_callback' => '__return_true',
			'args'                => array(
				'slug' => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_title',
				),
			),
		)
	);

	register_rest_route(
		$ns,
		'/content/page/(?P<slug>[a-z0-9\-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_content_page',
			'permission_callback' => '__return_true',
			'args'                => array(
				'slug' => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_title',
				),
			),
		)
	);

	register_rest_route(
		$ns,
		'/content/menus',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_content_menus',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/forms/contact',
		array(
			'methods'             => 'POST',
			'callback'            => 'snf_rest_form_contact',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/forms/newsletter',
		array(
			'methods'             => 'POST',
			'callback'            => 'snf_rest_form_newsletter',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/forms/apply',
		array(
			'methods'             => 'POST',
			'callback'            => 'snf_rest_form_apply',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/auth/login',
		array(
			'methods'             => 'POST',
			'callback'            => 'snf_rest_auth_login',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/auth/logout',
		array(
			'methods'             => 'POST',
			'callback'            => 'snf_rest_auth_logout',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/account/profile',
		array(
			'methods'             => 'POST',
			'callback'            => 'snf_rest_account_profile_update',
			'permission_callback' => 'snf_rest_require_login',
		)
	);

	register_rest_route(
		$ns,
		'/account/addresses/update',
		array(
			'methods'             => 'POST',
			'callback'            => 'snf_rest_account_addresses_update',
			'permission_callback' => 'snf_rest_require_login',
		)
	);

	register_rest_route(
		$ns,
		'/reciprocal',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_reciprocal_hub',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/reciprocal/(?P<slug>[a-z0-9\-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_reciprocal_club',
			'permission_callback' => '__return_true',
			'args'                => array(
				'slug' => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_title',
				),
			),
		)
	);

	register_rest_route(
		$ns,
		'/funnel/(?P<step>[a-z]+)/action',
		array(
			'methods'             => 'POST',
			'callback'            => 'snf_rest_funnel_action',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'snf_rest_content_register_routes' );

/**
 * GET /content/home
 *
 * @return WP_REST_Response
 */
function snf_rest_content_home() {
	$home = function_exists( 'snf_get_home_content' ) ? snf_get_home_content() : array();
	$brand = array(
		'name'     => get_bloginfo( 'name' ),
		'tagline'  => get_bloginfo( 'description' ),
		'hero'     => array(
			'title' => __( 'SUPER NINETY FOUR', 'super-ninety-four' ),
			'lede'  => __( 'Not a marketplace. A private hospitality circuit — if you already clear the bar.', 'super-ninety-four' ),
			'cta'   => array(
				'label' => __( 'Apply', 'super-ninety-four' ),
				'path'  => '/apply',
			),
			'secondary' => array(
				'label' => __( 'See how we help', 'super-ninety-four' ),
				'path'  => '/#help',
			),
		),
	);

	return rest_ensure_response(
		snf_rest_rewrite_urls_for_nuxt(
			array(
				'brand'   => $brand,
				'content' => $home,
			)
		)
	);
}

/**
 * GET /content/about
 *
 * @return WP_REST_Response
 */
function snf_rest_content_about() {
	$about = function_exists( 'snf_get_about_content' ) ? snf_get_about_content() : array();
	return rest_ensure_response( snf_rest_rewrite_urls_for_nuxt( array( 'content' => $about ) ) );
}

/**
 * GET /content/contact
 *
 * @return WP_REST_Response
 */
function snf_rest_content_contact() {
	$connect = function_exists( 'snf_get_connect_content' ) ? snf_get_connect_content() : array();
	return rest_ensure_response( snf_rest_rewrite_urls_for_nuxt( array( 'content' => $connect ) ) );
}

/**
 * GET /content/newsletter
 *
 * @return WP_REST_Response
 */
function snf_rest_content_newsletter() {
	return rest_ensure_response(
		array(
			'title'      => __( 'Video newsletter', 'super-ninety-four' ),
			'lede'       => __( 'Get circuit updates, workshop invites, and select-event drops.', 'super-ninety-four' ),
			'youtubeUrl' => function_exists( 'snf_newsletter_youtube_url' ) ? snf_newsletter_youtube_url() : '',
		)
	);
}

/**
 * GET /content/apply
 *
 * @return WP_REST_Response
 */
function snf_rest_content_apply() {
	return rest_ensure_response(
		snf_rest_rewrite_urls_for_nuxt(
			array(
				'title' => __( 'Apply for consideration', 'super-ninety-four' ),
				'lede'  => __( 'Private consideration for operators and principals who already clear the hospitality bar.', 'super-ninety-four' ),
				'next'  => array(
					'label' => __( 'Under consideration', 'super-ninety-four' ),
					'path'  => '/under-consideration',
				),
			)
		)
	);
}

/**
 * Serialize a WP page for headless consumption.
 *
 * @param WP_Post $post Page.
 * @return array<string, mixed>
 */
function snf_rest_serialize_page( WP_Post $post ) {
	return array(
		'id'      => $post->ID,
		'slug'    => $post->post_name,
		'title'   => get_the_title( $post ),
		'content' => apply_filters( 'the_content', $post->post_content ),
		'excerpt' => wp_kses_post( get_the_excerpt( $post ) ),
		'path'    => snf_nuxt_path_for_slug( $post->post_name ),
	);
}

/**
 * GET /content/legal/{slug}
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_content_legal( WP_REST_Request $request ) {
	$slug = sanitize_title( (string) $request['slug'] );
	if ( 'refund-returns' === $slug ) {
		$slug = 'refund_returns';
	}
	$page = get_page_by_path( $slug );
	if ( ! $page && 'refund_returns' === $slug && function_exists( 'wc_get_page_id' ) ) {
		$id = (int) wc_get_page_id( 'refund_returns' );
		if ( $id > 0 ) {
			$page = get_post( $id );
		}
	}
	if ( ! $page || 'publish' !== $page->post_status ) {
		return new WP_Error( 'snf_not_found', __( 'Legal page not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}
	return rest_ensure_response( snf_rest_serialize_page( $page ) );
}

/**
 * GET /content/page/{slug}
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_content_page( WP_REST_Request $request ) {
	$slug = sanitize_title( (string) $request['slug'] );
	$page = get_page_by_path( $slug );
	if ( ! $page || 'publish' !== $page->post_status ) {
		return new WP_Error( 'snf_not_found', __( 'Page not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}
	return rest_ensure_response( snf_rest_serialize_page( $page ) );
}

/**
 * Menu item for Nuxt chrome.
 *
 * @param string $title Title.
 * @param string $path  Nuxt path.
 * @return array{title:string,path:string}
 */
function snf_rest_menu_item( $title, $path ) {
	return array(
		'title' => $title,
		'path'  => $path,
	);
}

/**
 * GET /content/menus
 *
 * @return WP_REST_Response
 */
function snf_rest_content_menus() {
	$primary = array(
		snf_rest_menu_item( __( 'Help', 'super-ninety-four' ), '/#help' ),
		snf_rest_menu_item( __( 'Gatherings', 'super-ninety-four' ), '/#gatherings' ),
		snf_rest_menu_item( __( 'Fit', 'super-ninety-four' ), '/#fit' ),
		snf_rest_menu_item( __( 'Pricing', 'super-ninety-four' ), '/#pricing' ),
		snf_rest_menu_item( __( 'Shop', 'super-ninety-four' ), '/shop' ),
	);

	$footer = array(
		snf_rest_menu_item( __( 'Privacy Policy', 'super-ninety-four' ), '/legal/privacy-policy' ),
		snf_rest_menu_item( __( 'Terms of Service', 'super-ninety-four' ), '/legal/terms-of-service' ),
		snf_rest_menu_item( __( 'Cookie Policy', 'super-ninety-four' ), '/legal/cookie-policy' ),
		snf_rest_menu_item( __( 'Refund & Returns', 'super-ninety-four' ), '/legal/refund-returns' ),
		snf_rest_menu_item( __( 'Shop', 'super-ninety-four' ), '/shop' ),
		snf_rest_menu_item( __( 'Apply', 'super-ninety-four' ), '/apply' ),
	);

	$account = array(
		snf_rest_menu_item( __( 'Dashboard', 'super-ninety-four' ), '/app' ),
		snf_rest_menu_item( __( 'Community', 'super-ninety-four' ), '/community' ),
		snf_rest_menu_item( __( 'Messages', 'super-ninety-four' ), '/messages' ),
		snf_rest_menu_item( __( 'Membership', 'super-ninety-four' ), '/app/membership' ),
		snf_rest_menu_item( __( 'Tickets', 'super-ninety-four' ), '/app/tickets' ),
		snf_rest_menu_item( __( 'Orders', 'super-ninety-four' ), '/app/orders' ),
		snf_rest_menu_item( __( 'Profile', 'super-ninety-four' ), '/app/account' ),
	);

	return rest_ensure_response(
		array(
			'primary' => $primary,
			'footer'  => $footer,
			'account' => $account,
			'brand'   => array(
				'name' => get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'SUPER NINETY FOUR',
				'path' => '/',
			),
		)
	);
}

/**
 * POST /forms/contact
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_form_contact( WP_REST_Request $request ) {
	$params  = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$name    = isset( $params['name'] ) ? sanitize_text_field( (string) $params['name'] ) : '';
	$email   = isset( $params['email'] ) ? sanitize_email( (string) $params['email'] ) : '';
	$phone   = isset( $params['phone'] ) ? sanitize_text_field( (string) $params['phone'] ) : '';
	$org     = isset( $params['org'] ) ? sanitize_text_field( (string) $params['org'] ) : '';
	$day     = isset( $params['day'] ) ? sanitize_key( (string) $params['day'] ) : '';
	$time    = isset( $params['time'] ) ? sanitize_text_field( (string) $params['time'] ) : '';
	$intent  = isset( $params['intent'] ) ? sanitize_key( (string) $params['intent'] ) : '';
	$message = isset( $params['message'] ) ? sanitize_textarea_field( (string) $params['message'] ) : '';

	if ( '' === $name || ! is_email( $email ) || '' === $phone || '' === $day || '' === $time || '' === $intent || '' === $message ) {
		return new WP_Error( 'snf_invalid', __( 'Please complete all required fields.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$payload = array(
		'name'    => $name,
		'email'   => $email,
		'phone'   => $phone,
		'org'     => $org,
		'day'     => $day,
		'time'    => $time,
		'intent'  => $intent,
		'message' => $message,
		'user_id' => get_current_user_id(),
		'created' => gmdate( 'c' ),
	);

	do_action( 'snf_connect_request_submitted', $payload );

	$to = (string) apply_filters( 'snf_connect_notify_email', (string) get_option( 'admin_email' ) );
	$subject = sprintf(
		/* translators: %s: requester name */
		__( '[SUPER NINETY FOUR] Connect request from %s', 'super-ninety-four' ),
		$name
	);
	$body = implode(
		"\n",
		array(
			'Name: ' . $name,
			'Email: ' . $email,
			'Phone: ' . $phone,
			'Organization: ' . $org,
			'Day: ' . $day,
			'Time: ' . $time,
			'Intent: ' . $intent,
			'',
			'Message:',
			$message,
		)
	);
	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );
	$sent    = $to ? wp_mail( $to, $subject, $body, $headers ) : false;

	if ( ! $sent ) {
		return new WP_Error( 'snf_mail_failed', __( 'Could not send your request. Try again.', 'super-ninety-four' ), array( 'status' => 500 ) );
	}

	return rest_ensure_response( array( 'ok' => true, 'status' => 'sent' ) );
}

/**
 * POST /forms/newsletter
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_form_newsletter( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	// Honeypot.
	if ( ! empty( $params['website'] ) ) {
		return rest_ensure_response( array( 'ok' => true, 'status' => 'subscribed' ) );
	}

	$email = isset( $params['email'] ) ? sanitize_email( (string) $params['email'] ) : '';
	$name  = isset( $params['name'] ) ? sanitize_text_field( (string) $params['name'] ) : '';

	if ( ! is_email( $email ) ) {
		return new WP_Error( 'snf_invalid', __( 'Enter a valid email.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$duplicate = get_posts(
		array(
			'post_type'      => 'snf_subscriber',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_snf_newsletter_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( $duplicate ) {
		return rest_ensure_response( array( 'ok' => true, 'status' => 'duplicate' ) );
	}

	$subscriber_id = wp_insert_post(
		array(
			'post_type'    => 'snf_subscriber',
			'post_status'  => 'publish',
			'post_title'   => $name ? $name : $email,
			'post_content' => '',
		),
		true
	);

	if ( is_wp_error( $subscriber_id ) ) {
		return new WP_Error( 'snf_save_failed', __( 'Could not save subscription.', 'super-ninety-four' ), array( 'status' => 500 ) );
	}

	update_post_meta( $subscriber_id, '_snf_newsletter_email', $email );
	update_post_meta( $subscriber_id, '_snf_newsletter_name', $name );
	update_post_meta( $subscriber_id, '_snf_newsletter_source', 'nuxt-newsletter' );
	update_post_meta( $subscriber_id, '_snf_newsletter_user_id', get_current_user_id() );
	update_post_meta( $subscriber_id, '_snf_newsletter_subscribed_at', current_time( 'mysql' ) );

	return rest_ensure_response( array( 'ok' => true, 'status' => 'subscribed' ) );
}

/**
 * POST /forms/apply
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_form_apply( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$name    = isset( $params['name'] ) ? sanitize_text_field( (string) $params['name'] ) : '';
	$email   = isset( $params['email'] ) ? sanitize_email( (string) $params['email'] ) : '';
	$org     = isset( $params['org'] ) ? sanitize_text_field( (string) $params['org'] ) : '';
	$intent  = isset( $params['intent'] ) ? sanitize_text_field( (string) $params['intent'] ) : '';
	$message = isset( $params['message'] ) ? sanitize_textarea_field( (string) $params['message'] ) : '';

	if ( '' === $name || ! is_email( $email ) || '' === $message ) {
		return new WP_Error( 'snf_invalid', __( 'Name, email, and message are required.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$payload = array(
		'name'    => $name,
		'email'   => $email,
		'org'     => $org,
		'intent'  => $intent,
		'message' => $message,
		'user_id' => get_current_user_id(),
		'created' => gmdate( 'c' ),
	);
	do_action( 'snf_apply_submitted', $payload );

	$to = (string) get_option( 'admin_email' );
	$subject = sprintf(
		/* translators: %s: applicant name */
		__( '[SUPER NINETY FOUR] Apply request from %s', 'super-ninety-four' ),
		$name
	);
	$body = implode(
		"\n",
		array(
			'Name: ' . $name,
			'Email: ' . $email,
			'Organization: ' . $org,
			'Intent: ' . $intent,
			'',
			'Message:',
			$message,
		)
	);
	$sent = $to ? wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $name . ' <' . $email . '>' ) ) : false;

	if ( ! $sent ) {
		return new WP_Error( 'snf_mail_failed', __( 'Could not send application.', 'super-ninety-four' ), array( 'status' => 500 ) );
	}

	return rest_ensure_response(
		array(
			'ok'     => true,
			'status' => 'sent',
			'next'   => '/under-consideration',
		)
	);
}

/**
 * POST /auth/login
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_auth_login( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$login    = isset( $params['username'] ) ? sanitize_text_field( (string) $params['username'] ) : '';
	$password = isset( $params['password'] ) ? (string) $params['password'] : '';

	if ( '' === $login || '' === $password ) {
		return new WP_Error( 'snf_invalid', __( 'Username and password required.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$user = wp_authenticate( $login, $password );
	if ( is_wp_error( $user ) ) {
		return new WP_Error( 'snf_auth_failed', __( 'Invalid credentials.', 'super-ninety-four' ), array( 'status' => 401 ) );
	}

	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, ! empty( $params['remember'] ) );

	$token = function_exists( 'snf_nuxt_issue_token' ) ? snf_nuxt_issue_token( (int) $user->ID ) : '';

	return rest_ensure_response(
		array(
			'ok'    => true,
			'token' => $token,
			'user'  => array(
				'id'            => (int) $user->ID,
				'name'          => $user->display_name ? $user->display_name : $user->user_login,
				'email'         => $user->user_email,
				'canManageClub' => function_exists( 'snf_user_can_manage_club' ) ? snf_user_can_manage_club() : user_can( $user, 'edit_posts' ),
			),
		)
	);
}

/**
 * POST /auth/logout
 *
 * @return WP_REST_Response
 */
function snf_rest_auth_logout() {
	wp_logout();
	return rest_ensure_response( array( 'ok' => true ) );
}

/**
 * POST /account/profile
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_account_profile_update( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$user_id = get_current_user_id();
	$update  = array( 'ID' => $user_id );

	if ( isset( $params['display_name'] ) ) {
		$update['display_name'] = sanitize_text_field( (string) $params['display_name'] );
	}
	if ( isset( $params['email'] ) ) {
		$email = sanitize_email( (string) $params['email'] );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'snf_invalid', __( 'Invalid email.', 'super-ninety-four' ), array( 'status' => 400 ) );
		}
		$update['user_email'] = $email;
	}
	if ( isset( $params['first_name'] ) ) {
		update_user_meta( $user_id, 'first_name', sanitize_text_field( (string) $params['first_name'] ) );
	}
	if ( isset( $params['last_name'] ) ) {
		update_user_meta( $user_id, 'last_name', sanitize_text_field( (string) $params['last_name'] ) );
	}

	$result = wp_update_user( $update );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	if ( ! empty( $params['password'] ) ) {
		wp_set_password( (string) $params['password'], $user_id );
	}

	return rest_ensure_response( array( 'ok' => true ) );
}

/**
 * POST /account/addresses
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_account_addresses_update( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$type = isset( $params['type'] ) ? sanitize_key( (string) $params['type'] ) : 'billing';
	if ( ! in_array( $type, array( 'billing', 'shipping' ), true ) ) {
		return new WP_Error( 'snf_invalid', __( 'Invalid address type.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$user_id = get_current_user_id();
	$fields  = array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone', 'email' );
	foreach ( $fields as $field ) {
		if ( ! array_key_exists( $field, $params ) ) {
			continue;
		}
		$key = $type . '_' . $field;
		$val = 'email' === $field
			? sanitize_email( (string) $params[ $field ] )
			: sanitize_text_field( (string) $params[ $field ] );
		update_user_meta( $user_id, $key, $val );
	}

	if ( function_exists( 'WC' ) && WC()->customer ) {
		WC()->customer->set_props(
			array(
				$type . '_first_name' => get_user_meta( $user_id, $type . '_first_name', true ),
				$type . '_last_name'  => get_user_meta( $user_id, $type . '_last_name', true ),
				$type . '_company'    => get_user_meta( $user_id, $type . '_company', true ),
				$type . '_address_1'  => get_user_meta( $user_id, $type . '_address_1', true ),
				$type . '_address_2'  => get_user_meta( $user_id, $type . '_address_2', true ),
				$type . '_city'       => get_user_meta( $user_id, $type . '_city', true ),
				$type . '_state'      => get_user_meta( $user_id, $type . '_state', true ),
				$type . '_postcode'   => get_user_meta( $user_id, $type . '_postcode', true ),
				$type . '_country'    => get_user_meta( $user_id, $type . '_country', true ),
			)
		);
		WC()->customer->save();
	}

	return rest_ensure_response( array( 'ok' => true, 'type' => $type ) );
}

/**
 * GET /reciprocal
 *
 * @return WP_REST_Response
 */
function snf_rest_reciprocal_hub() {
	$data = function_exists( 'snf_get_reciprocal_hub_data' ) ? snf_get_reciprocal_hub_data() : array();
	if ( ! empty( $data['clubs'] ) && is_array( $data['clubs'] ) ) {
		foreach ( $data['clubs'] as &$club ) {
			$slug = isset( $club['slug'] ) ? $club['slug'] : '';
			$club['path'] = $slug ? '/reciprocal/' . $slug : '/reciprocal';
			$club['cta_path'] = '/apply';
		}
		unset( $club );
	}
	return rest_ensure_response( snf_rest_rewrite_urls_for_nuxt( $data ) );
}

/**
 * GET /reciprocal/{slug}
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_reciprocal_club( WP_REST_Request $request ) {
	$slug = sanitize_title( (string) $request['slug'] );
	$club = function_exists( 'snf_get_reciprocal_club' ) ? snf_get_reciprocal_club( $slug ) : null;
	if ( ! $club ) {
		return new WP_Error( 'snf_not_found', __( 'Club not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}
	$club['path'] = '/reciprocal/' . $slug;
	$club['cta_path'] = '/apply';
	$club['interests'] = function_exists( 'snf_reciprocal_inquiry_interests' ) ? snf_reciprocal_inquiry_interests() : array();
	return rest_ensure_response( snf_rest_rewrite_urls_for_nuxt( $club ) );
}

/**
 * POST /funnel/{step} — accept/decline actions as JSON.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_funnel_action( WP_REST_Request $request ) {
	$step   = sanitize_key( (string) $request['step'] );
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}
	$action = isset( $params['action'] ) ? sanitize_key( (string) $params['action'] ) : '';

	if ( ! in_array( $action, array( 'accept', 'decline' ), true ) ) {
		return new WP_Error( 'snf_invalid', __( 'Invalid funnel action.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$payload = function_exists( 'snf_rest_funnel_step' ) ? snf_rest_funnel_step( $request ) : null;
	$data    = $payload instanceof WP_REST_Response ? $payload->get_data() : array();

	if ( 'decline' === $action ) {
		return rest_ensure_response(
			array(
				'ok'   => true,
				'next' => isset( $data['declinePath'] ) ? $data['declinePath'] : ( isset( $data['declineUrl'] ) ? $data['declineUrl'] : '/' ),
			)
		);
	}

	// Accept: prefer add-to-cart / checkout handoff URLs already on the step payload.
	$next = '';
	if ( ! empty( $data['acceptUrl'] ) ) {
		$next = (string) $data['acceptUrl'];
	} elseif ( ! empty( $data['addToCartUrl'] ) ) {
		$next = (string) $data['addToCartUrl'];
	} elseif ( ! empty( $data['checkoutUrl'] ) ) {
		$next = (string) $data['checkoutUrl'];
	} else {
		$next = '/checkout';
	}

	return rest_ensure_response(
		array(
			'ok'   => true,
			'next' => $next,
		)
	);
}
