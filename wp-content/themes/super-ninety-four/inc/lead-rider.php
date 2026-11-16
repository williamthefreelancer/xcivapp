<?php
/**
 * Mapbox lead rider position + WebRTC walkie signaling.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve Mapbox public access token.
 *
 * @return string
 */
function snf_get_mapbox_token() {
	if ( defined( 'SNF_MAPBOX_TOKEN' ) && SNF_MAPBOX_TOKEN ) {
		return (string) SNF_MAPBOX_TOKEN;
	}

	$token = (string) get_option( 'snf_mapbox_token', '' );

	/**
	 * Filter Mapbox token used by Expo lead rider.
	 *
	 * @param string $token Public access token.
	 */
	return apply_filters( 'snf_mapbox_token', $token );
}

/**
 * Whether the current user may broadcast as lead rider.
 *
 * @return bool
 */
function snf_user_can_broadcast_lead_rider() {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	return current_user_can( 'manage_options' )
		|| current_user_can( 'snf_staff' );
}

/**
 * Whether the current user may use live lead rider + walkie.
 *
 * @return bool
 */
function snf_user_can_use_lead_rider() {
	return snf_user_can_access_member_areas();
}

/**
 * Seeded scenic route GeoJSON FeatureCollections keyed by route id.
 *
 * @return array<string, array<string, mixed>>
 */
function snf_get_lead_rider_routes() {
	$routes = array(
		'charlotte-salem'    => array(
			'id'          => 'charlotte-salem',
			'title'       => __( 'Charlotte to Salem', 'super-ninety-four' ),
			'center'      => array( -77.5, 38.2 ),
			'zoom'        => 5.4,
			'coordinates' => array(
				array( -80.8431, 35.2271 ),
				array( -79.9908, 36.0726 ),
				array( -78.6382, 35.7796 ),
				array( -77.4360, 37.5407 ),
				array( -77.0369, 38.9072 ),
				array( -75.1652, 39.9526 ),
				array( -71.0589, 42.3601 ),
				array( -70.8967, 42.5195 ),
			),
		),
		'back-creek-circuit' => array(
			'id'          => 'back-creek-circuit',
			'title'       => __( 'Back Creek Circuit', 'super-ninety-four' ),
			'center'      => array( -80.75, 35.35 ),
			'zoom'        => 9.2,
			'coordinates' => array(
				array( -80.8431, 35.2271 ),
				array( -80.7200, 35.3100 ),
				array( -80.6500, 35.4200 ),
				array( -80.7800, 35.4800 ),
				array( -80.9000, 35.3600 ),
				array( -80.8431, 35.2271 ),
			),
		),
		'regions-ne-run'     => array(
			'id'          => 'regions-ne-run',
			'title'       => __( 'Regions NE Coastal Run', 'super-ninety-four' ),
			'center'      => array( -71.2, 42.2 ),
			'zoom'        => 8.2,
			'coordinates' => array(
				array( -71.4128, 41.8240 ),
				array( -71.0589, 42.3601 ),
				array( -70.8967, 42.5195 ),
				array( -70.6610, 42.6584 ),
				array( -70.9495, 42.5584 ),
			),
		),
	);

	/**
	 * Filter lead rider scenic routes.
	 *
	 * @param array $routes Route definitions.
	 */
	return apply_filters( 'snf_lead_rider_routes', $routes );
}

/**
 * Build bootstrap payload for Expo JS.
 *
 * @param string $audience 'members' or 'non-members'.
 * @return array<string, mixed>
 */
function snf_get_expo_js_bootstrap( $audience = 'members' ) {
	$routes = snf_get_lead_rider_routes();
	$route_payload = array();
	foreach ( $routes as $route ) {
		$route_payload[] = array(
			'id'          => $route['id'],
			'title'       => $route['title'],
			'center'      => $route['center'],
			'zoom'        => $route['zoom'],
			'coordinates' => $route['coordinates'],
		);
	}

	$is_members = ( 'members' === $audience );

	return array(
		'audience'       => $audience,
		'mapboxToken'    => snf_get_mapbox_token(),
		'leadRiderName'  => 'William A. Johnson',
		'canBroadcast'   => $is_members && snf_user_can_broadcast_lead_rider(),
		'canLive'        => $is_members && snf_user_can_use_lead_rider(),
		'applyUrl'       => snf_get_page_url( 'apply' ),
		'routes'         => $route_payload,
		'defaultRouteId' => 'charlotte-salem',
		'rest'           => array(
			'position' => esc_url_raw( rest_url( 'snf/v1/lead-rider/position' ) ),
			'signal'   => esc_url_raw( rest_url( 'snf/v1/lead-rider/signal' ) ),
			'peers'    => esc_url_raw( rest_url( 'snf/v1/lead-rider/peers' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
		),
		'pollMs'         => 4000,
		'i18n'           => array(
			'standby'       => __( 'Standby', 'super-ninety-four' ),
			'onRoad'        => __( 'On the road', 'super-ninety-four' ),
			'holdToTalk'    => __( 'Hold to talk', 'super-ninety-four' ),
			'release'       => __( 'Release', 'super-ninety-four' ),
			'noToken'       => __( 'Add a Mapbox token (SNF_MAPBOX_TOKEN or snf_mapbox_token) to activate the lead rider map.', 'super-ninety-four' ),
			'membersOnly'   => __( 'Membership required for live lead rider & walkie.', 'super-ninety-four' ),
			'micDenied'     => __( 'Microphone access is required for walkie-talkie.', 'super-ninety-four' ),
			'geoDenied'     => __( 'Location access is required to broadcast as lead rider.', 'super-ninety-four' ),
			'walkieHint'    => __( 'Walkie works on iPhone, iPad, and Android over HTTPS.', 'super-ninety-four' ),
		),
	);
}

/**
 * REST: members can read position / signal; staff can write position.
 *
 * @return bool|WP_Error
 */
function snf_lead_rider_rest_can_read() {
	if ( ! snf_user_can_use_lead_rider() ) {
		return new WP_Error(
			'snf_forbidden',
			__( 'Lead rider tools are for members.', 'super-ninety-four' ),
			array( 'status' => 403 )
		);
	}
	return true;
}

/**
 * REST: only lead rider broadcasters may publish position.
 *
 * @return bool|WP_Error
 */
function snf_lead_rider_rest_can_broadcast() {
	if ( ! snf_user_can_broadcast_lead_rider() ) {
		return new WP_Error(
			'snf_forbidden',
			__( 'Only the lead rider desk may broadcast position.', 'super-ninety-four' ),
			array( 'status' => 403 )
		);
	}
	return true;
}

/**
 * Register lead rider REST routes.
 */
function snf_lead_rider_register_rest_routes() {
	register_rest_route(
		'snf/v1',
		'/lead-rider/position',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'snf_lead_rider_rest_get_position',
				'permission_callback' => 'snf_lead_rider_rest_can_read',
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'snf_lead_rider_rest_post_position',
				'permission_callback' => 'snf_lead_rider_rest_can_broadcast',
				'args'                => array(
					'lat'     => array(
						'type'              => 'number',
						'required'          => true,
						'sanitize_callback' => 'floatval',
					),
					'lng'     => array(
						'type'              => 'number',
						'required'          => true,
						'sanitize_callback' => 'floatval',
					),
					'heading' => array(
						'type'              => 'number',
						'required'          => false,
						'default'           => null,
						'sanitize_callback' => 'floatval',
					),
					'status'  => array(
						'type'              => 'string',
						'required'          => false,
						'default'           => 'on_road',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			),
		)
	);

	register_rest_route(
		'snf/v1',
		'/lead-rider/peers',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'snf_lead_rider_rest_get_peers',
				'permission_callback' => 'snf_lead_rider_rest_can_read',
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'snf_lead_rider_rest_post_peer',
				'permission_callback' => 'snf_lead_rider_rest_can_read',
				'args'                => array(
					'peer_id' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'name'    => array(
						'type'              => 'string',
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		)
	);

	register_rest_route(
		'snf/v1',
		'/lead-rider/signal',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'snf_lead_rider_rest_get_signals',
				'permission_callback' => 'snf_lead_rider_rest_can_read',
				'args'                => array(
					'peer_id' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'after'   => array(
						'type'              => 'integer',
						'required'          => false,
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'snf_lead_rider_rest_post_signal',
				'permission_callback' => 'snf_lead_rider_rest_can_read',
				'args'                => array(
					'from'    => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'to'      => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'type'    => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'payload' => array(
						'required' => true,
					),
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'snf_lead_rider_register_rest_routes' );

/**
 * GET position.
 *
 * @return WP_REST_Response
 */
function snf_lead_rider_rest_get_position() {
	$position = get_transient( 'snf_lead_rider_position' );
	if ( ! is_array( $position ) ) {
		$position = array(
			'lat'       => null,
			'lng'       => null,
			'heading'   => null,
			'status'    => 'standby',
			'name'      => 'William A. Johnson',
			'updatedAt' => null,
		);
	}

	return rest_ensure_response( $position );
}

/**
 * POST position (lead rider broadcast).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_lead_rider_rest_post_position( WP_REST_Request $request ) {
	$lat = (float) $request->get_param( 'lat' );
	$lng = (float) $request->get_param( 'lng' );

	if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
		return new WP_Error( 'snf_invalid', __( 'Invalid coordinates.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$status = (string) $request->get_param( 'status' );
	if ( ! in_array( $status, array( 'on_road', 'standby' ), true ) ) {
		$status = 'on_road';
	}

	$heading = $request->get_param( 'heading' );
	$payload = array(
		'lat'       => $lat,
		'lng'       => $lng,
		'heading'   => null === $heading ? null : (float) $heading,
		'status'    => $status,
		'name'      => 'William A. Johnson',
		'updatedAt' => gmdate( 'c' ),
		'userId'    => get_current_user_id(),
	);

	set_transient( 'snf_lead_rider_position', $payload, 15 * MINUTE_IN_SECONDS );

	return rest_ensure_response( $payload );
}

/**
 * Read peer registry.
 *
 * @return array<string, array<string, mixed>>
 */
function snf_lead_rider_get_peers() {
	$peers = get_transient( 'snf_lead_rider_peers' );
	return is_array( $peers ) ? $peers : array();
}

/**
 * Persist peer registry.
 *
 * @param array $peers Peers keyed by peer_id.
 */
function snf_lead_rider_save_peers( array $peers ) {
	$now = time();
	foreach ( $peers as $id => $peer ) {
		if ( empty( $peer['seen'] ) || ( $now - (int) $peer['seen'] ) > 90 ) {
			unset( $peers[ $id ] );
		}
	}
	set_transient( 'snf_lead_rider_peers', $peers, 5 * MINUTE_IN_SECONDS );
}

/**
 * GET peers.
 *
 * @return WP_REST_Response
 */
function snf_lead_rider_rest_get_peers() {
	$peers = snf_lead_rider_get_peers();
	snf_lead_rider_save_peers( $peers );
	return rest_ensure_response( array_values( snf_lead_rider_get_peers() ) );
}

/**
 * POST peer presence heartbeat.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function snf_lead_rider_rest_post_peer( WP_REST_Request $request ) {
	$peer_id = (string) $request->get_param( 'peer_id' );
	$name    = (string) $request->get_param( 'name' );
	if ( ! $name ) {
		$user = wp_get_current_user();
		$name = $user && $user->display_name ? $user->display_name : __( 'Rider', 'super-ninety-four' );
	}

	$peers = snf_lead_rider_get_peers();
	$peers[ $peer_id ] = array(
		'peer_id' => $peer_id,
		'name'    => $name,
		'userId'  => get_current_user_id(),
		'seen'    => time(),
	);
	snf_lead_rider_save_peers( $peers );

	return rest_ensure_response( array_values( snf_lead_rider_get_peers() ) );
}

/**
 * Signal queue helpers.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_lead_rider_get_signals() {
	$signals = get_transient( 'snf_lead_rider_signals' );
	return is_array( $signals ) ? $signals : array();
}

/**
 * @param array $signals Signal queue.
 */
function snf_lead_rider_save_signals( array $signals ) {
	// Keep a short rolling window.
	if ( count( $signals ) > 200 ) {
		$signals = array_slice( $signals, -200 );
	}
	set_transient( 'snf_lead_rider_signals', $signals, 10 * MINUTE_IN_SECONDS );
}

/**
 * GET signals for a peer.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function snf_lead_rider_rest_get_signals( WP_REST_Request $request ) {
	$peer_id = (string) $request->get_param( 'peer_id' );
	$after   = (int) $request->get_param( 'after' );
	$out     = array();

	foreach ( snf_lead_rider_get_signals() as $signal ) {
		if ( (string) $signal['to'] !== $peer_id ) {
			continue;
		}
		if ( (int) $signal['id'] <= $after ) {
			continue;
		}
		$out[] = $signal;
	}

	return rest_ensure_response(
		array(
			'signals' => $out,
		)
	);
}

/**
 * POST WebRTC signal.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_lead_rider_rest_post_signal( WP_REST_Request $request ) {
	$type = (string) $request->get_param( 'type' );
	if ( ! in_array( $type, array( 'offer', 'answer', 'ice', 'bye' ), true ) ) {
		return new WP_Error( 'snf_invalid', __( 'Invalid signal type.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$payload = $request->get_param( 'payload' );
	$signals = snf_lead_rider_get_signals();
	$next_id = 1;
	if ( $signals ) {
		$last    = end( $signals );
		$next_id = isset( $last['id'] ) ? ( (int) $last['id'] + 1 ) : 1;
	}

	$entry = array(
		'id'        => $next_id,
		'from'      => (string) $request->get_param( 'from' ),
		'to'        => (string) $request->get_param( 'to' ),
		'type'      => $type,
		'payload'   => $payload,
		'createdAt' => time(),
	);

	$signals[] = $entry;
	snf_lead_rider_save_signals( $signals );

	return rest_ensure_response( $entry );
}
