<?php
/**
 * B2B marketplace extensions: operator orgs + RFQ intake (BuddyPress-free).
 *
 * Hook contract:
 * - User meta: snf_org_* for vendor/operator profiles.
 * - CPT: snf_rfq (admin inbox for Expo RFQs).
 * - REST: POST snf/v1/marketplace/rfq (public lead + member RFQ).
 * - Emit: snf_marketplace_rfq_submitted
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'SNF_B2B_MARKETPLACE_LOADED' ) ) {
	return;
}
define( 'SNF_B2B_MARKETPLACE_LOADED', true );

/**
 * Register RFQ CPT for staff review.
 */
function snf_b2b_register_rfq_cpt() {
	register_post_type(
		'snf_rfq',
		array(
			'labels'              => array(
				'name'          => __( 'Marketplace RFQs', 'super-ninety-four' ),
				'singular_name' => __( 'RFQ', 'super-ninety-four' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=product',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title', 'editor' ),
			'exclude_from_search' => true,
		)
	);
}
add_action( 'init', 'snf_b2b_register_rfq_cpt', 8 );

/**
 * Org profile fields on a user.
 *
 * @param int $user_id User ID.
 * @return array<string, string>
 */
function snf_b2b_get_org_profile( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return array(
			'name'     => '',
			'website'  => '',
			'category' => '',
			'city'     => '',
			'blurb'    => '',
		);
	}

	return array(
		'name'     => (string) get_user_meta( $user_id, 'snf_org_name', true ),
		'website'  => (string) get_user_meta( $user_id, 'snf_org_website', true ),
		'category' => (string) get_user_meta( $user_id, 'snf_org_category', true ),
		'city'     => (string) get_user_meta( $user_id, 'snf_org_city', true ),
		'blurb'    => (string) get_user_meta( $user_id, 'snf_org_blurb', true ),
	);
}

/**
 * Save org profile from account form / REST.
 *
 * @param int   $user_id User ID.
 * @param array $data    Field map.
 * @return array<string, string>
 */
function snf_b2b_save_org_profile( $user_id, $data ) {
	$user_id = absint( $user_id );
	$map     = array(
		'name'     => 'snf_org_name',
		'website'  => 'snf_org_website',
		'category' => 'snf_org_category',
		'city'     => 'snf_org_city',
		'blurb'    => 'snf_org_blurb',
	);

	foreach ( $map as $key => $meta ) {
		if ( ! array_key_exists( $key, $data ) ) {
			continue;
		}
		$value = $data[ $key ];
		if ( 'website' === $key ) {
			$value = esc_url_raw( (string) $value );
		} elseif ( 'blurb' === $key ) {
			$value = sanitize_textarea_field( (string) $value );
		} else {
			$value = sanitize_text_field( (string) $value );
		}
		update_user_meta( $user_id, $meta, $value );
	}

	return snf_b2b_get_org_profile( $user_id );
}

/**
 * Create an RFQ post from marketplace intake.
 *
 * @param array $payload Request fields.
 * @return int|WP_Error Post ID.
 */
function snf_b2b_create_rfq( $payload ) {
	$name    = isset( $payload['name'] ) ? sanitize_text_field( (string) $payload['name'] ) : '';
	$email   = isset( $payload['email'] ) ? sanitize_email( (string) $payload['email'] ) : '';
	$company = isset( $payload['company'] ) ? sanitize_text_field( (string) $payload['company'] ) : '';
	$product = isset( $payload['productId'] ) ? absint( $payload['productId'] ) : 0;
	$message = isset( $payload['message'] ) ? sanitize_textarea_field( (string) $payload['message'] ) : '';
	$audience = isset( $payload['audience'] ) ? sanitize_key( (string) $payload['audience'] ) : 'public';

	if ( '' === $name || '' === $email || '' === $message ) {
		return new WP_Error( 'snf_rfq_invalid', __( 'Name, email, and message are required.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'snf_rfq_email', __( 'Enter a valid email.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}
	if ( strlen( $message ) > 4000 ) {
		return new WP_Error( 'snf_rfq_long', __( 'Message is too long.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$product_title = '';
	if ( $product ) {
		$p = get_post( $product );
		if ( $p && 'product' === $p->post_type ) {
			$product_title = get_the_title( $p );
		}
	}

	$title = sprintf(
		/* translators: 1: company or name, 2: product title */
		__( 'RFQ · %1$s%2$s', 'super-ninety-four' ),
		$company ? $company : $name,
		$product_title ? ' · ' . $product_title : ''
	);

	$body = "Name: {$name}\nEmail: {$email}\nCompany: {$company}\nAudience: {$audience}\nProduct ID: {$product}\n\n{$message}";

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'snf_rfq',
			'post_status'  => 'private',
			'post_title'   => $title,
			'post_content' => $body,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	update_post_meta( $post_id, '_snf_rfq_name', $name );
	update_post_meta( $post_id, '_snf_rfq_email', $email );
	update_post_meta( $post_id, '_snf_rfq_company', $company );
	update_post_meta( $post_id, '_snf_rfq_product_id', $product );
	update_post_meta( $post_id, '_snf_rfq_audience', $audience );
	update_post_meta( $post_id, '_snf_rfq_user_id', get_current_user_id() );

	/**
	 * Fires after an Expo / marketplace RFQ is stored.
	 *
	 * @param int   $post_id RFQ post ID.
	 * @param array $payload Sanitized payload.
	 */
	do_action(
		'snf_marketplace_rfq_submitted',
		$post_id,
		array(
			'name'      => $name,
			'email'     => $email,
			'company'   => $company,
			'productId' => $product,
			'message'   => $message,
			'audience'  => $audience,
			'userId'    => get_current_user_id(),
		)
	);

	return (int) $post_id;
}

/**
 * REST: submit RFQ (public allowed; rate-light via nonce when logged in).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_b2b_rest_submit_rfq( WP_REST_Request $request ) {
	$result = snf_b2b_create_rfq(
		array(
			'name'      => $request->get_param( 'name' ),
			'email'     => $request->get_param( 'email' ),
			'company'   => $request->get_param( 'company' ),
			'productId' => $request->get_param( 'productId' ),
			'message'   => $request->get_param( 'message' ),
			'audience'  => $request->get_param( 'audience' ),
		)
	);

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return rest_ensure_response(
		array(
			'ok'    => true,
			'rfqId' => $result,
			'message' => __( 'Request received. Concierge will follow up.', 'super-ninety-four' ),
		)
	);
}

/**
 * REST: get/save org profile (members only).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_b2b_rest_org_profile( WP_REST_Request $request ) {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'snf_auth', __( 'Sign in required.', 'super-ninety-four' ), array( 'status' => 401 ) );
	}
	if ( ! function_exists( 'snf_user_can_access_member_areas' ) || ! snf_user_can_access_member_areas() ) {
		return new WP_Error( 'snf_forbidden', __( 'Members only.', 'super-ninety-four' ), array( 'status' => 403 ) );
	}

	$user_id = get_current_user_id();
	if ( 'POST' === $request->get_method() || 'PUT' === $request->get_method() ) {
		$profile = snf_b2b_save_org_profile(
			$user_id,
			array(
				'name'     => $request->get_param( 'name' ),
				'website'  => $request->get_param( 'website' ),
				'category' => $request->get_param( 'category' ),
				'city'     => $request->get_param( 'city' ),
				'blurb'    => $request->get_param( 'blurb' ),
			)
		);
	} else {
		$profile = snf_b2b_get_org_profile( $user_id );
	}

	return rest_ensure_response( array( 'org' => $profile ) );
}

/**
 * Register B2B REST routes.
 */
function snf_b2b_register_rest_routes() {
	register_rest_route(
		'snf/v1',
		'/marketplace/rfq',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'snf_b2b_rest_submit_rfq',
			'permission_callback' => '__return_true',
			'args'                => array(
				'name'      => array(
					'type'     => 'string',
					'required' => true,
				),
				'email'     => array(
					'type'     => 'string',
					'required' => true,
				),
				'company'   => array(
					'type'    => 'string',
					'default' => '',
				),
				'productId' => array(
					'type'    => 'integer',
					'default' => 0,
				),
				'message'   => array(
					'type'     => 'string',
					'required' => true,
				),
				'audience'  => array(
					'type'    => 'string',
					'default' => 'public',
				),
			),
		)
	);

	register_rest_route(
		'snf/v1',
		'/marketplace/org',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'snf_b2b_rest_org_profile',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'snf_b2b_rest_org_profile',
				'permission_callback' => '__return_true',
			),
		)
	);
}
add_action( 'rest_api_init', 'snf_b2b_register_rest_routes' );

/**
 * JS bootstrap extras for Expo RFQ form.
 *
 * @param array $boot Existing expo/marketplace boot.
 * @return array
 */
function snf_b2b_filter_expo_bootstrap( $boot ) {
	if ( ! is_array( $boot ) ) {
		$boot = array();
	}

	$boot['rfqUrl']   = esc_url_raw( rest_url( 'snf/v1/marketplace/rfq' ) );
	$boot['orgUrl']   = esc_url_raw( rest_url( 'snf/v1/marketplace/org' ) );
	$boot['rfqNonce'] = wp_create_nonce( 'wp_rest' );
	$boot['org']      = ( is_user_logged_in() && function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas() )
		? snf_b2b_get_org_profile( get_current_user_id() )
		: null;
	$boot['rfqI18n']  = array(
		'title'   => __( 'Request a quote', 'super-ninety-four' ),
		'submit'  => __( 'Send RFQ', 'super-ninety-four' ),
		'success' => __( 'Request received. Concierge will follow up.', 'super-ninety-four' ),
		'error'   => __( 'Could not send RFQ. Try again.', 'super-ninety-four' ),
	);

	return $boot;
}
add_filter( 'snf_expo_js_bootstrap', 'snf_b2b_filter_expo_bootstrap' );
add_filter( 'snf_marketplace_js_bootstrap', 'snf_b2b_filter_expo_bootstrap' );
