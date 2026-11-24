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
 * Mirror SNF_MAPBOX_TOKEN into the snf_mapbox_token option and refresh health.
 */
function snf_mapbox_mirror_token_option() {
	if ( ! defined( 'SNF_MAPBOX_TOKEN' ) || ! SNF_MAPBOX_TOKEN ) {
		return;
	}

	$const = (string) SNF_MAPBOX_TOKEN;
	$saved = (string) get_option( 'snf_mapbox_token', '' );
	if ( $saved === $const ) {
		return;
	}

	update_option( 'snf_mapbox_token', $const, false );

	if ( function_exists( 'snf_stripe_refresh_api_health' ) ) {
		snf_stripe_refresh_api_health( false );
	} else {
		$health           = get_option( 'snf_api_health_cache', array() );
		$health           = is_array( $health ) ? $health : array();
		$health['mapbox'] = 'ok';
		update_option( 'snf_api_health_cache', $health, false );
	}
}
add_action( 'admin_init', 'snf_mapbox_mirror_token_option', 5 );
add_action( 'rest_api_init', 'snf_mapbox_mirror_token_option', 1 );

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
 * Seeded scenic route waypoints keyed by route id (pre-Directions).
 *
 * @return array<string, array<string, mixed>>
 */
function snf_get_lead_rider_route_seeds() {
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
	 * Filter lead rider scenic route seeds.
	 *
	 * @param array $routes Route definitions.
	 */
	return apply_filters( 'snf_lead_rider_routes', $routes );
}

/**
 * Cached Mapbox Directions polylines keyed by route id.
 *
 * Stored as JSON string for reliable large payloads.
 *
 * @return array<string, array{coordinates: array<int, array{0: float, 1: float}>, syncedAt?: string}>
 */
function snf_get_lead_rider_route_geometries() {
	$raw = get_option( 'snf_lead_rider_route_geometries', '' );
	if ( is_array( $raw ) ) {
		return $raw;
	}
	if ( is_string( $raw ) && '' !== $raw ) {
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : array();
	}
	return array();
}

/**
 * Persist Mapbox Directions polylines.
 *
 * @param array<string, array{coordinates: array<int, array{0: float, 1: float}>, syncedAt?: string}> $geometries Geometries.
 * @return bool
 */
function snf_save_lead_rider_route_geometries( array $geometries ) {
	$json = wp_json_encode( $geometries );
	if ( ! is_string( $json ) || '' === $json ) {
		return false;
	}
	return (bool) update_option( 'snf_lead_rider_route_geometries', $json, false );
}

/**
 * Fetch a driving polyline from Mapbox Directions for waypoint seeds.
 *
 * @param array<int, array{0: float, 1: float}> $waypoints Lon/lat pairs.
 * @param string                                 $token     Mapbox token.
 * @return array<int, array{0: float, 1: float}>|WP_Error
 */
function snf_mapbox_fetch_driving_geometry( array $waypoints, $token ) {
	if ( count( $waypoints ) < 2 ) {
		return new WP_Error( 'snf_mapbox_waypoints', 'Need at least two waypoints.' );
	}

	// Directions allows up to 25 coordinates; keep seeds as-is.
	$parts = array();
	foreach ( $waypoints as $pair ) {
		if ( ! is_array( $pair ) || count( $pair ) < 2 ) {
			continue;
		}
		$parts[] = rawurlencode( (string) (float) $pair[0] ) . ',' . rawurlencode( (string) (float) $pair[1] );
	}
	if ( count( $parts ) < 2 ) {
		return new WP_Error( 'snf_mapbox_waypoints', 'Need at least two valid waypoints.' );
	}

	$url = sprintf(
		'https://api.mapbox.com/directions/v5/mapbox/driving/%s?geometries=geojson&overview=simplified&access_token=%s',
		implode( ';', $parts ),
		rawurlencode( (string) $token )
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 20,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( 200 !== $code || ! is_array( $body ) ) {
		$message = is_array( $body ) && ! empty( $body['message'] ) ? (string) $body['message'] : 'Mapbox Directions request failed.';
		return new WP_Error( 'snf_mapbox_directions', $message, array( 'status' => $code ) );
	}

	$coords = $body['routes'][0]['geometry']['coordinates'] ?? null;
	if ( ! is_array( $coords ) || count( $coords ) < 2 ) {
		return new WP_Error( 'snf_mapbox_directions', 'No route geometry returned.' );
	}

	$out = array();
	foreach ( $coords as $pair ) {
		if ( ! is_array( $pair ) || count( $pair ) < 2 ) {
			continue;
		}
		$out[] = array( (float) $pair[0], (float) $pair[1] );
	}

	return count( $out ) >= 2 ? $out : new WP_Error( 'snf_mapbox_directions', 'Empty geometry.' );
}

/**
 * Sync all lead-rider route polylines via Mapbox Directions.
 *
 * @return array{ok: bool, synced: int, failed: int, errors: array<string, string>, geometries: array}
 */
function snf_sync_lead_rider_route_geometries() {
	$token  = snf_get_mapbox_token();
	$result = array(
		'ok'         => false,
		'synced'     => 0,
		'failed'     => 0,
		'errors'     => array(),
		'geometries' => array(),
	);

	if ( '' === $token ) {
		$result['errors']['_token'] = 'Mapbox token missing.';
		return $result;
	}

	$seeds   = snf_get_lead_rider_route_seeds();
	$current = snf_get_lead_rider_route_geometries();
	$now     = gmdate( 'c' );

	foreach ( $seeds as $id => $route ) {
		$waypoints = isset( $route['coordinates'] ) && is_array( $route['coordinates'] ) ? $route['coordinates'] : array();
		$geometry  = snf_mapbox_fetch_driving_geometry( $waypoints, $token );
		if ( is_wp_error( $geometry ) ) {
			$result['failed']++;
			$result['errors'][ (string) $id ] = $geometry->get_error_message();
			if ( isset( $current[ $id ] ) && is_array( $current[ $id ] ) ) {
				$result['geometries'][ $id ] = $current[ $id ];
			}
			continue;
		}

		$result['synced']++;
		$result['geometries'][ $id ] = array(
			'coordinates' => $geometry,
			'syncedAt'    => $now,
		);
	}

	snf_save_lead_rider_route_geometries( $result['geometries'] );
	update_option(
		'snf_lead_rider_route_sync_meta',
		array(
			'syncedAt' => $now,
			'synced'   => $result['synced'],
			'failed'   => $result['failed'],
			'errors'   => $result['errors'],
		),
		false
	);

	$result['ok'] = $result['synced'] > 0 && 0 === $result['failed'];
	return $result;
}

/**
 * Scenic routes with Directions polylines when cached, else seeds.
 *
 * @return array<string, array<string, mixed>>
 */
function snf_get_lead_rider_routes() {
	$routes     = snf_get_lead_rider_route_seeds();
	$geometries = snf_get_lead_rider_route_geometries();

	foreach ( $routes as $id => &$route ) {
		if ( empty( $geometries[ $id ]['coordinates'] ) || ! is_array( $geometries[ $id ]['coordinates'] ) ) {
			continue;
		}
		$route['coordinates'] = $geometries[ $id ]['coordinates'];
		$route['geometrySource'] = 'mapbox-directions';
	}
	unset( $route );

	return $routes;
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
			'id'             => $route['id'],
			'title'          => $route['title'],
			'center'         => $route['center'],
			'zoom'           => $route['zoom'],
			'coordinates'    => $route['coordinates'],
			'geometrySource' => isset( $route['geometrySource'] ) ? $route['geometrySource'] : 'seed',
		);
	}

	if ( ! $audience ) {
		$audience = ( function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas() )
			? 'members'
			: 'non-members';
	}

	$is_members = ( 'members' === $audience );
	$ice        = function_exists( 'snf_media_get_ice_servers' )
		? snf_media_get_ice_servers()
		: array( array( 'urls' => 'stun:stun.l.google.com:19302' ) );

	$boot = array(
		'audience'       => $audience,
		'mapboxToken'    => snf_get_mapbox_token(),
		'leadRiderName'  => 'William A. Johnson',
		'canBroadcast'   => $is_members && snf_user_can_broadcast_lead_rider(),
		'canLive'        => $is_members && snf_user_can_use_lead_rider(),
		'applyUrl'       => snf_get_page_url( 'apply' ),
		'routes'         => $route_payload,
		'defaultRouteId' => 'charlotte-salem',
		'ice'            => $ice,
		'rest'           => array(
			'bootstrap' => esc_url_raw( rest_url( 'snf/v1/expo/bootstrap' ) ),
			'position'  => esc_url_raw( rest_url( 'snf/v1/lead-rider/position' ) ),
			'signal'    => esc_url_raw( rest_url( 'snf/v1/lead-rider/signal' ) ),
			'peers'     => esc_url_raw( rest_url( 'snf/v1/lead-rider/peers' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
		),
		'pollMs'         => 4000,
		'i18n'           => array(
			'standby'       => __( 'Standby', 'super-ninety-four' ),
			'onRoad'        => __( 'On the road', 'super-ninety-four' ),
			'holdToTalk'    => __( 'Hold to talk', 'super-ninety-four' ),
			'release'       => __( 'Release', 'super-ninety-four' ),
			'noToken'       => __( 'Add a Mapbox token (SNF_MAPBOX_TOKEN or snf_mapbox_token) to activate the lead rider map.', 'super-ninety-four' ),
			'mapError'      => __( 'The map could not be loaded. Check that the Mapbox token is valid and has map styles enabled.', 'super-ninety-four' ),
			'broadcast'     => __( 'Broadcast my position as lead rider', 'super-ninety-four' ),
			'membersOnly'   => __( 'Membership required for live lead rider & walkie.', 'super-ninety-four' ),
			'micDenied'     => __( 'Microphone access is required for walkie-talkie.', 'super-ninety-four' ),
			'geoDenied'     => __( 'Location access is required to broadcast as lead rider.', 'super-ninety-four' ),
			'walkieHint'    => __( 'Walkie works on iPhone, iPad, and Android over HTTPS.', 'super-ninety-four' ),
		),
	);

	/**
	 * Filter Expo / lead-rider JS bootstrap (B2B RFQ fields attach here).
	 *
	 * @param array  $boot     Bootstrap payload.
	 * @param string $audience Audience key.
	 */
	return apply_filters( 'snf_expo_js_bootstrap', $boot, $audience );
}

/**
 * REST: public Expo / lead-rider bootstrap (pk token intentional).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function snf_expo_rest_get_bootstrap( WP_REST_Request $request ) {
	$audience = sanitize_key( (string) $request->get_param( 'audience' ) );
	if ( ! in_array( $audience, array( 'members', 'non-members' ), true ) ) {
		$audience = ( function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas() )
			? 'members'
			: 'non-members';
	}

	return rest_ensure_response( snf_get_expo_js_bootstrap( $audience ) );
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
		'/expo/bootstrap',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'snf_expo_rest_get_bootstrap',
			'permission_callback' => '__return_true',
			'args'                => array(
				'audience' => array(
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_key',
				),
			),
		)
	);

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
