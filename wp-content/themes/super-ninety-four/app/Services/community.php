<?php
/**
 * Private community (BuddyPress-free): circles + member directory stubs.
 *
 * Hook contract:
 * - CPT: snf_circle (member-only archive via theme page /community/).
 * - Table: {prefix}snf_circle_members
 * - REST: snf/v1/community/*
 * - Emit: snf_circle_joined, snf_circle_left
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'SNF_COMMUNITY_LOADED' ) ) {
	return;
}
define( 'SNF_COMMUNITY_LOADED', true );

define( 'SNF_COMMUNITY_DB_VERSION', '1.0.0' );

/**
 * Install circle membership table.
 */
function snf_community_install_tables() {
	global $wpdb;

	$installed = get_option( 'snf_community_db_version' );
	if ( SNF_COMMUNITY_DB_VERSION === $installed ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset = $wpdb->get_charset_collate();
	$table   = $wpdb->prefix . 'snf_circle_members';

	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		circle_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		member_role varchar(32) NOT NULL DEFAULT 'member',
		joined_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY circle_user (circle_id, user_id),
		KEY user_id (user_id)
	) {$charset};";

	dbDelta( $sql );
	update_option( 'snf_community_db_version', SNF_COMMUNITY_DB_VERSION );
}
add_action( 'after_switch_theme', 'snf_community_install_tables' );
add_action( 'init', 'snf_community_install_tables', 5 );

/**
 * Register private circles CPT (not a public WP archive — gated page lists them).
 */
function snf_community_register_cpt() {
	register_post_type(
		'snf_circle',
		array(
			'labels'              => array(
				'name'          => __( 'Circles', 'super-ninety-four' ),
				'singular_name' => __( 'Circle', 'super-ninety-four' ),
				'add_new_item'  => __( 'Add Circle', 'super-ninety-four' ),
				'edit_item'     => __( 'Edit Circle', 'super-ninety-four' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-groups',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
			'exclude_from_search' => true,
		)
	);
}
add_action( 'init', 'snf_community_register_cpt', 8 );

/**
 * Seed default club circles once.
 */
function snf_community_ensure_default_circles() {
	if ( get_option( 'snf_community_circles_seeded' ) ) {
		return;
	}

	$defaults = array(
		array(
			'slug'  => 'members-lounge',
			'title' => __( 'Members lounge', 'super-ninety-four' ),
			'lede'  => __( 'Quiet notes between club principals — introductions, travel windows, and house etiquette.', 'super-ninety-four' ),
		),
		array(
			'slug'  => 'events-circle',
			'title' => __( 'Events circle', 'super-ninety-four' ),
			'lede'  => __( 'Private gathering coordination for Select Events and hybrid broadcasts.', 'super-ninety-four' ),
		),
		array(
			'slug'  => 'expo-operators',
			'title' => __( 'Expo operators', 'super-ninety-four' ),
			'lede'  => __( 'B2B and reciprocal operators coordinating Expo listings and RFQs.', 'super-ninety-four' ),
		),
	);

	foreach ( $defaults as $row ) {
		$existing = get_page_by_path( $row['slug'], OBJECT, 'snf_circle' );
		if ( $existing ) {
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'snf_circle',
				'post_status'  => 'publish',
				'post_title'   => $row['title'],
				'post_name'    => $row['slug'],
				'post_content' => $row['lede'],
				'post_excerpt' => $row['lede'],
			),
			true
		);

		if ( ! is_wp_error( $post_id ) && $post_id ) {
			update_post_meta( (int) $post_id, '_snf_circle_visibility', 'members' );
		}
	}

	update_option( 'snf_community_circles_seeded', '1' );
}
add_action( 'init', 'snf_community_ensure_default_circles', 25 );

/**
 * Whether the current request is the Community hub.
 *
 * @return bool
 */
function snf_is_community_page() {
	return is_page( 'community' )
		|| ( function_exists( 'snf_is_page_template' ) && snf_is_page_template( 'page-templates/community.php' ) );
}

/**
 * Circle membership table name.
 *
 * @return string
 */
function snf_community_members_table() {
	global $wpdb;
	return $wpdb->prefix . 'snf_circle_members';
}

/**
 * Whether a user belongs to a circle.
 *
 * @param int $circle_id Circle post ID.
 * @param int $user_id   User ID.
 * @return bool
 */
function snf_community_user_in_circle( $circle_id, $user_id ) {
	global $wpdb;
	$circle_id = absint( $circle_id );
	$user_id   = absint( $user_id );
	if ( ! $circle_id || ! $user_id ) {
		return false;
	}

	$found = (int) $wpdb->get_var(
		$wpdb->prepare(
			'SELECT id FROM ' . snf_community_members_table() . ' WHERE circle_id = %d AND user_id = %d LIMIT 1',
			$circle_id,
			$user_id
		)
	);

	return $found > 0;
}

/**
 * Join a circle.
 *
 * @param int $circle_id Circle post ID.
 * @param int $user_id   User ID.
 * @return true|WP_Error
 */
function snf_community_join_circle( $circle_id, $user_id ) {
	$circle_id = absint( $circle_id );
	$user_id   = absint( $user_id );
	$circle    = get_post( $circle_id );

	if ( ! $circle || 'snf_circle' !== $circle->post_type || 'publish' !== $circle->post_status ) {
		return new WP_Error( 'snf_circle_missing', __( 'Circle not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}

	if ( ! function_exists( 'snf_user_can_access_member_areas' ) || ! snf_user_can_access_member_areas() ) {
		return new WP_Error( 'snf_forbidden', __( 'Members only.', 'super-ninety-four' ), array( 'status' => 403 ) );
	}

	$self_join = true;
	if ( function_exists( 'snf_get_circle_settings' ) ) {
		$circle_settings = snf_get_circle_settings();
		$self_join       = ! empty( $circle_settings['allow_self_join'] );
	}
	$self_join = (bool) apply_filters( 'snf_circle_allow_self_join', $self_join, $circle_id, $user_id );
	if ( ! $self_join ) {
		return new WP_Error( 'snf_self_join_disabled', __( 'Circle self-join is disabled.', 'super-ninety-four' ), array( 'status' => 403 ) );
	}

	if ( snf_community_user_in_circle( $circle_id, $user_id ) ) {
		return true;
	}

	global $wpdb;
	$inserted = $wpdb->insert(
		snf_community_members_table(),
		array(
			'circle_id'   => $circle_id,
			'user_id'     => $user_id,
			'member_role' => 'member',
			'joined_at'   => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s' )
	);

	if ( ! $inserted ) {
		return new WP_Error( 'snf_db', __( 'Could not join circle.', 'super-ninety-four' ), array( 'status' => 500 ) );
	}

	do_action( 'snf_circle_joined', $circle_id, $user_id );
	return true;
}

/**
 * Leave a circle.
 *
 * @param int $circle_id Circle post ID.
 * @param int $user_id   User ID.
 * @return true|WP_Error
 */
function snf_community_leave_circle( $circle_id, $user_id ) {
	global $wpdb;
	$circle_id = absint( $circle_id );
	$user_id   = absint( $user_id );

	$wpdb->delete(
		snf_community_members_table(),
		array(
			'circle_id' => $circle_id,
			'user_id'   => $user_id,
		),
		array( '%d', '%d' )
	);

	do_action( 'snf_circle_left', $circle_id, $user_id );
	return true;
}

/**
 * Serialize a circle for REST / template.
 *
 * @param WP_Post $post    Circle post.
 * @param int     $user_id Current user.
 * @return array<string, mixed>
 */
function snf_community_serialize_circle( $post, $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	$thumb   = get_the_post_thumbnail_url( $post, 'medium' );
	if ( ! $thumb ) {
		$thumb = get_template_directory_uri() . '/assets/images/events/township-adventures-event.jpg';
	}

	global $wpdb;
	$count = (int) $wpdb->get_var(
		$wpdb->prepare(
			'SELECT COUNT(*) FROM ' . snf_community_members_table() . ' WHERE circle_id = %d',
			(int) $post->ID
		)
	);

	return array(
		'id'          => (int) $post->ID,
		'slug'        => $post->post_name,
		'title'       => get_the_title( $post ),
		'excerpt'     => $post->post_excerpt ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 28 ),
		'image'       => $thumb,
		'memberCount' => $count,
		'joined'      => snf_community_user_in_circle( (int) $post->ID, $user_id ),
		'messagesUrl' => function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'messages' ) : home_url( '/messages/' ),
	);
}

/**
 * List published circles.
 *
 * @param int $user_id User ID for joined state.
 * @return array<int, array<string, mixed>>
 */
function snf_community_list_circles( $user_id = 0 ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'snf_circle',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	$out = array();
	foreach ( $query->posts as $post ) {
		$out[] = snf_community_serialize_circle( $post, $user_id );
	}
	return $out;
}

/**
 * REST permission: club member areas only.
 *
 * @return bool|WP_Error
 */
function snf_community_rest_can_access() {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'snf_auth', __( 'Sign in required.', 'super-ninety-four' ), array( 'status' => 401 ) );
	}
	if ( ! function_exists( 'snf_user_can_access_member_areas' ) || ! snf_user_can_access_member_areas() ) {
		return new WP_Error( 'snf_forbidden', __( 'Members only.', 'super-ninety-four' ), array( 'status' => 403 ) );
	}
	return true;
}

/**
 * Register community REST routes.
 */
function snf_community_register_rest_routes() {
	register_rest_route(
		'snf/v1',
		'/community/circles',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => function () {
				return rest_ensure_response(
					array(
						'circles' => snf_community_list_circles( get_current_user_id() ),
					)
				);
			},
			'permission_callback' => 'snf_community_rest_can_access',
		)
	);

	register_rest_route(
		'snf/v1',
		'/community/circles/(?P<id>\d+)/join',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => function ( WP_REST_Request $request ) {
				$result = snf_community_join_circle( (int) $request['id'], get_current_user_id() );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				$post = get_post( (int) $request['id'] );
				return rest_ensure_response(
					array(
						'ok'     => true,
						'circle' => $post ? snf_community_serialize_circle( $post, get_current_user_id() ) : null,
					)
				);
			},
			'permission_callback' => 'snf_community_rest_can_access',
			'args'                => array(
				'id' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'snf/v1',
		'/community/circles/(?P<id>\d+)/leave',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => function ( WP_REST_Request $request ) {
				snf_community_leave_circle( (int) $request['id'], get_current_user_id() );
				$post = get_post( (int) $request['id'] );
				return rest_ensure_response(
					array(
						'ok'     => true,
						'circle' => $post ? snf_community_serialize_circle( $post, get_current_user_id() ) : null,
					)
				);
			},
			'permission_callback' => 'snf_community_rest_can_access',
			'args'                => array(
				'id' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'snf_community_register_rest_routes' );

/**
 * Bootstrap payload for the Community Vue island.
 *
 * @return array<string, mixed>
 */
function snf_community_get_bootstrap() {
	$user_id = get_current_user_id();
	return array(
		'restUrl'     => esc_url_raw( untrailingslashit( rest_url( 'snf/v1/community' ) ) ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'loggedIn'    => (bool) $user_id,
		'messagesUrl' => function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'messages' ) : home_url( '/messages/' ),
		'expoUrl'     => function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'expo' ) : home_url( '/expo/' ),
		'eventsUrl'   => function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'select-events-by-members' ) : home_url( '/select-events-by-members/' ),
		'circles'     => $user_id && function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas()
			? snf_community_list_circles( $user_id )
			: array(),
		'i18n'        => array(
			'join'   => __( 'Join', 'super-ninety-four' ),
			'leave'  => __( 'Leave', 'super-ninety-four' ),
			'joined' => __( 'Joined', 'super-ninety-four' ),
			'empty'  => __( 'No circles yet.', 'super-ninety-four' ),
			'error'  => __( 'Could not update membership.', 'super-ninety-four' ),
			'members'=> __( 'members', 'super-ninety-four' ),
		),
	);
}
