<?php
/**
 * Live member messaging (Instagram-style inbox).
 * Theme-native custom tables — BuddyPress is not required.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Messages schema version for dbDelta upgrades.
 */
define( 'SNF_MESSAGES_DB_VERSION', '1.0.0' );

/**
 * System sender id for desk / admissions bots (not a WP user).
 */
define( 'SNF_MESSAGES_SYSTEM_SENDER', 0 );

/**
 * Create / upgrade messaging tables.
 */
function snf_messages_install_tables() {
	global $wpdb;

	$installed = get_option( 'snf_messages_db_version' );
	if ( SNF_MESSAGES_DB_VERSION === $installed ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset = $wpdb->get_charset_collate();
	$threads = $wpdb->prefix . 'snf_message_threads';
	$parts   = $wpdb->prefix . 'snf_message_participants';
	$msgs    = $wpdb->prefix . 'snf_messages';

	$sql = "CREATE TABLE {$threads} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		slug varchar(64) NOT NULL DEFAULT '',
		title varchar(191) NOT NULL DEFAULT '',
		subtitle varchar(191) NOT NULL DEFAULT '',
		avatar_kind varchar(32) NOT NULL DEFAULT 'desk',
		is_system tinyint(1) NOT NULL DEFAULT 0,
		updated_at datetime NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY slug (slug),
		KEY updated_at (updated_at)
	) {$charset};

	CREATE TABLE {$parts} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		thread_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		last_read_id bigint(20) unsigned NOT NULL DEFAULT 0,
		joined_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY thread_user (thread_id, user_id),
		KEY user_id (user_id)
	) {$charset};

	CREATE TABLE {$msgs} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		thread_id bigint(20) unsigned NOT NULL,
		sender_id bigint(20) unsigned NOT NULL DEFAULT 0,
		body text NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY thread_created (thread_id, created_at),
		KEY thread_id (thread_id)
	) {$charset};";

	dbDelta( $sql );
	update_option( 'snf_messages_db_version', SNF_MESSAGES_DB_VERSION );
}
add_action( 'after_switch_theme', 'snf_messages_install_tables' );
add_action( 'init', 'snf_messages_install_tables', 5 );

/**
 * Desk directory used to seed per-member threads.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_messages_get_desk_catalog() {
	return array(
		array(
			'slug'        => 'concierge-desk',
			'title'       => __( 'Concierge desk', 'super-ninety-four' ),
			'subtitle'    => __( 'Dining · guest access · arrivals', 'super-ninety-four' ),
			'avatar_kind' => 'desk',
			'welcome'     => __( 'Welcome in. Tell us what you need tonight — corner tables, guest passes, or arrival notes.', 'super-ninety-four' ),
		),
		array(
			'slug'        => 'city-clubs-admissions',
			'title'       => __( 'City Clubs admissions', 'super-ninety-four' ),
			'subtitle'    => __( 'Membership · guest credentials', 'super-ninety-four' ),
			'avatar_kind' => 'admissions',
			'welcome'     => __( 'Admissions here. Share guest names or reciprocal questions and we will confirm credentials.', 'super-ninety-four' ),
		),
		array(
			'slug'        => 'reciprocal-programs',
			'title'       => __( 'Reciprocal programs', 'super-ninety-four' ),
			'subtitle'    => __( 'Letters · introductions', 'super-ninety-four' ),
			'avatar_kind' => 'reciprocal',
			'welcome'     => __( 'Need a letter of introduction or a visit window at a reciprocal club? Drop the details here.', 'super-ninety-four' ),
		),
	);
}

/**
 * Ensure the current member has seeded desk threads.
 *
 * @param int $user_id User id.
 */
function snf_messages_ensure_member_threads( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return;
	}

	global $wpdb;
	$threads_table = $wpdb->prefix . 'snf_message_threads';
	$parts_table   = $wpdb->prefix . 'snf_message_participants';
	$msgs_table    = $wpdb->prefix . 'snf_messages';
	$now           = current_time( 'mysql' );

	foreach ( snf_messages_get_desk_catalog() as $desk ) {
		$thread_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT t.id FROM {$threads_table} t
				INNER JOIN {$parts_table} p ON p.thread_id = t.id
				WHERE t.slug = %s AND p.user_id = %d
				LIMIT 1",
				$desk['slug'],
				$user_id
			)
		);

		if ( $thread_id ) {
			continue;
		}

		$wpdb->insert(
			$threads_table,
			array(
				'slug'        => $desk['slug'],
				'title'       => $desk['title'],
				'subtitle'    => $desk['subtitle'],
				'avatar_kind' => $desk['avatar_kind'],
				'is_system'   => 1,
				'updated_at'  => $now,
				'created_at'  => $now,
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		$thread_id = (int) $wpdb->insert_id;
		if ( ! $thread_id ) {
			continue;
		}

		$wpdb->insert(
			$parts_table,
			array(
				'thread_id'    => $thread_id,
				'user_id'      => $user_id,
				'last_read_id' => 0,
				'joined_at'    => $now,
			),
			array( '%d', '%d', '%d', '%s' )
		);

		$wpdb->insert(
			$msgs_table,
			array(
				'thread_id'  => $thread_id,
				'sender_id'  => SNF_MESSAGES_SYSTEM_SENDER,
				'body'       => $desk['welcome'],
				'created_at' => $now,
			),
			array( '%d', '%d', '%s', '%s' )
		);
	}
}

/**
 * Whether the user participates in a thread.
 *
 * @param int $thread_id Thread id.
 * @param int $user_id   User id.
 * @return bool
 */
function snf_messages_user_in_thread( $thread_id, $user_id ) {
	global $wpdb;
	$parts_table = $wpdb->prefix . 'snf_message_participants';
	$found       = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$parts_table} WHERE thread_id = %d AND user_id = %d LIMIT 1",
			$thread_id,
			$user_id
		)
	);
	return $found > 0;
}

/**
 * Format a relative timestamp for the inbox list.
 *
 * @param string $mysql_datetime Datetime string.
 * @return string
 */
function snf_messages_format_time( $mysql_datetime ) {
	$dt = date_create( $mysql_datetime, wp_timezone() );
	if ( ! $dt ) {
		return '';
	}
	$ts  = $dt->getTimestamp();
	$now = current_time( 'timestamp' );
	$diff = $now - $ts;

	if ( $diff < MINUTE_IN_SECONDS ) {
		return __( 'Just now', 'super-ninety-four' );
	}
	if ( $diff < HOUR_IN_SECONDS ) {
		$mins = max( 1, (int) floor( $diff / MINUTE_IN_SECONDS ) );
		return sprintf(
			/* translators: %d: minutes */
			_n( '%d min', '%d mins', $mins, 'super-ninety-four' ),
			$mins
		);
	}
	if ( gmdate( 'Y-m-d', $ts ) === gmdate( 'Y-m-d', $now ) ) {
		return wp_date( 'g:i A', $ts );
	}
	if ( $diff < WEEK_IN_SECONDS ) {
		return wp_date( 'D', $ts );
	}
	return wp_date( 'M j', $ts );
}

/**
 * Avatar initials / glyph for a desk kind.
 *
 * @param string $kind Avatar kind.
 * @return string
 */
function snf_messages_avatar_glyph( $kind ) {
	$map = array(
		'desk'       => 'CD',
		'admissions' => 'CA',
		'reciprocal' => 'RP',
		'member'     => 'M',
	);
	return isset( $map[ $kind ] ) ? $map[ $kind ] : 'SN';
}

/**
 * Serialize a message row for the API / template.
 *
 * @param object $row     Message row.
 * @param int    $user_id Current user id.
 * @return array<string, mixed>
 */
function snf_messages_serialize_message( $row, $user_id ) {
	$sender_id = (int) $row->sender_id;
	$mine      = $sender_id === (int) $user_id && $sender_id > 0;

	if ( $mine ) {
		$sender_name = __( 'You', 'super-ninety-four' );
	} elseif ( $sender_id > 0 ) {
		$user        = get_userdata( $sender_id );
		$sender_name = $user ? $user->display_name : __( 'Member', 'super-ninety-four' );
	} else {
		$sender_name = __( 'Desk', 'super-ninety-four' );
	}

	return array(
		'id'          => (int) $row->id,
		'thread_id'   => (int) $row->thread_id,
		'sender_id'   => $sender_id,
		'sender_name' => $sender_name,
		'body'        => $row->body,
		'mine'        => $mine,
		'created_at'  => $row->created_at,
		'time_label'  => snf_messages_format_time( $row->created_at ),
	);
}

/**
 * Threads for a member with last message preview.
 *
 * @param int $user_id User id.
 * @return array<int, array<string, mixed>>
 */
function snf_messages_get_threads_for_user( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return array();
	}

	snf_messages_ensure_member_threads( $user_id );

	global $wpdb;
	$threads_table = $wpdb->prefix . 'snf_message_threads';
	$parts_table   = $wpdb->prefix . 'snf_message_participants';
	$msgs_table    = $wpdb->prefix . 'snf_messages';

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT t.*, p.last_read_id,
				(SELECT m.body FROM {$msgs_table} m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) AS last_body,
				(SELECT m.created_at FROM {$msgs_table} m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) AS last_at,
				(SELECT m.id FROM {$msgs_table} m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) AS last_id,
				(SELECT COUNT(*) FROM {$msgs_table} m WHERE m.thread_id = t.id AND m.id > p.last_read_id AND m.sender_id <> %d) AS unread_count
			FROM {$threads_table} t
			INNER JOIN {$parts_table} p ON p.thread_id = t.id
			WHERE p.user_id = %d
			ORDER BY COALESCE(
				(SELECT m.created_at FROM {$msgs_table} m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1),
				t.updated_at
			) DESC",
			$user_id,
			$user_id
		)
	);

	$out = array();
	foreach ( (array) $rows as $row ) {
		$preview = $row->last_body ? wp_html_excerpt( $row->last_body, 72, '…' ) : $row->subtitle;
		$out[]   = array(
			'id'           => (int) $row->id,
			'slug'         => $row->slug,
			'title'        => $row->title,
			'subtitle'     => $row->subtitle,
			'avatar_kind'  => $row->avatar_kind,
			'avatar_glyph' => snf_messages_avatar_glyph( $row->avatar_kind ),
			'preview'      => $preview,
			'time_label'   => $row->last_at ? snf_messages_format_time( $row->last_at ) : '',
			'unread'       => (int) $row->unread_count > 0,
			'unread_count' => (int) $row->unread_count,
			'last_id'      => (int) $row->last_id,
		);
	}

	return $out;
}

/**
 * Messages in a thread after an optional cursor.
 *
 * @param int $thread_id Thread id.
 * @param int $user_id   User id.
 * @param int $after_id  Return messages with id greater than this.
 * @param int $limit     Max rows.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function snf_messages_get_thread_messages( $thread_id, $user_id, $after_id = 0, $limit = 80 ) {
	$thread_id = absint( $thread_id );
	$user_id   = absint( $user_id );
	$after_id  = absint( $after_id );
	$limit     = min( 120, max( 1, absint( $limit ) ) );

	if ( ! $thread_id || ! $user_id || ! snf_messages_user_in_thread( $thread_id, $user_id ) ) {
		return new WP_Error( 'snf_forbidden', __( 'Thread not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}

	global $wpdb;
	$msgs_table = $wpdb->prefix . 'snf_messages';

	if ( $after_id > 0 ) {
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$msgs_table} WHERE thread_id = %d AND id > %d ORDER BY id ASC LIMIT %d",
				$thread_id,
				$after_id,
				$limit
			)
		);
	} else {
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM (
					SELECT * FROM {$msgs_table} WHERE thread_id = %d ORDER BY id DESC LIMIT %d
				) recent ORDER BY id ASC",
				$thread_id,
				$limit
			)
		);
	}

	$messages = array();
	foreach ( (array) $rows as $row ) {
		$messages[] = snf_messages_serialize_message( $row, $user_id );
	}

	return $messages;
}

/**
 * Mark a thread as read up to the latest message.
 *
 * @param int $thread_id Thread id.
 * @param int $user_id   User id.
 * @param int $last_id   Optional last read message id.
 */
function snf_messages_mark_read( $thread_id, $user_id, $last_id = 0 ) {
	global $wpdb;
	$parts_table = $wpdb->prefix . 'snf_message_participants';
	$msgs_table  = $wpdb->prefix . 'snf_messages';

	if ( ! $last_id ) {
		$last_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$msgs_table} WHERE thread_id = %d ORDER BY id DESC LIMIT 1",
				$thread_id
			)
		);
	}

	$wpdb->update(
		$parts_table,
		array( 'last_read_id' => absint( $last_id ) ),
		array(
			'thread_id' => absint( $thread_id ),
			'user_id'   => absint( $user_id ),
		),
		array( '%d' ),
		array( '%d', '%d' )
	);
}

/**
 * Lightweight desk auto-reply for system threads.
 *
 * @param string $body Incoming body.
 * @param string $slug Thread slug.
 * @return string
 */
function snf_messages_desk_reply( $body, $slug ) {
	$lower = strtolower( $body );

	if ( false !== strpos( $lower, 'guest' ) || false !== strpos( $lower, 'pass' ) ) {
		return __( 'Noted — we will prep guest credentials and confirm once security clears the names.', 'super-ninety-four' );
	}
	if ( false !== strpos( $lower, 'table' ) || false !== strpos( $lower, 'dinner' ) || false !== strpos( $lower, 'reserv' ) ) {
		return __( 'We are holding a dining request. Share preferred time and party size if you have not already.', 'super-ninety-four' );
	}
	if ( false !== strpos( $lower, 'reciprocal' ) || false !== strpos( $lower, 'letter' ) || 'reciprocal-programs' === $slug ) {
		return __( 'We will draft the introduction note. Confirm the destination club and visit dates when ready.', 'super-ninety-four' );
	}
	if ( 'city-clubs-admissions' === $slug ) {
		return __( 'Received by admissions. Expect a credentials update shortly.', 'super-ninety-four' );
	}

	return __( 'Received. A desk host is reviewing and will follow up in this thread.', 'super-ninety-four' );
}

/**
 * Send a message into a thread.
 *
 * @param int    $thread_id Thread id.
 * @param int    $user_id   Sender user id.
 * @param string $body      Message body.
 * @return array<string, mixed>|WP_Error
 */
function snf_messages_send( $thread_id, $user_id, $body ) {
	$thread_id = absint( $thread_id );
	$user_id   = absint( $user_id );
	$body      = trim( wp_strip_all_tags( (string) $body ) );

	if ( ! $user_id ) {
		return new WP_Error( 'snf_auth', __( 'Sign in to send messages.', 'super-ninety-four' ), array( 'status' => 401 ) );
	}
	if ( ! $thread_id || ! snf_messages_user_in_thread( $thread_id, $user_id ) ) {
		return new WP_Error( 'snf_forbidden', __( 'Thread not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}
	if ( '' === $body ) {
		return new WP_Error( 'snf_empty', __( 'Write a message first.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}
	if ( strlen( $body ) > 2000 ) {
		return new WP_Error( 'snf_too_long', __( 'Messages are limited to 2,000 characters.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	global $wpdb;
	$threads_table = $wpdb->prefix . 'snf_message_threads';
	$msgs_table    = $wpdb->prefix . 'snf_messages';
	$now           = current_time( 'mysql' );

	$thread = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$threads_table} WHERE id = %d LIMIT 1", $thread_id )
	);
	if ( ! $thread ) {
		return new WP_Error( 'snf_forbidden', __( 'Thread not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}

	$inserted = $wpdb->insert(
		$msgs_table,
		array(
			'thread_id'  => $thread_id,
			'sender_id'  => $user_id,
			'body'       => $body,
			'created_at' => $now,
		),
		array( '%d', '%d', '%s', '%s' )
	);

	if ( ! $inserted ) {
		return new WP_Error( 'snf_db', __( 'Could not send message.', 'super-ninety-four' ), array( 'status' => 500 ) );
	}

	$user_message_id = (int) $wpdb->insert_id;
	$wpdb->update(
		$threads_table,
		array( 'updated_at' => $now ),
		array( 'id' => $thread_id ),
		array( '%s' ),
		array( '%d' )
	);

	snf_messages_mark_read( $thread_id, $user_id, $user_message_id );

	$payload = array(
		'message' => snf_messages_serialize_message(
			(object) array(
				'id'         => $user_message_id,
				'thread_id'  => $thread_id,
				'sender_id'  => $user_id,
				'body'       => $body,
				'created_at' => $now,
			),
			$user_id
		),
		'replies' => array(),
	);

	if ( (int) $thread->is_system === 1 ) {
		$reply_body = snf_messages_desk_reply( $body, $thread->slug );
		$reply_at   = current_time( 'mysql' );

		$wpdb->insert(
			$msgs_table,
			array(
				'thread_id'  => $thread_id,
				'sender_id'  => SNF_MESSAGES_SYSTEM_SENDER,
				'body'       => $reply_body,
				'created_at' => $reply_at,
			),
			array( '%d', '%d', '%s', '%s' )
		);

		$reply_id = (int) $wpdb->insert_id;
		if ( $reply_id ) {
			$wpdb->update(
				$threads_table,
				array( 'updated_at' => $reply_at ),
				array( 'id' => $thread_id ),
				array( '%s' ),
				array( '%d' )
			);
			$payload['replies'][] = snf_messages_serialize_message(
				(object) array(
					'id'         => $reply_id,
					'thread_id'  => $thread_id,
					'sender_id'  => SNF_MESSAGES_SYSTEM_SENDER,
					'body'       => $reply_body,
					'created_at' => $reply_at,
				),
				$user_id
			);
		}
	}

	/**
	 * Fires after a member message is stored.
	 *
	 * @param int   $thread_id Thread id.
	 * @param int   $user_id   Sender.
	 * @param array $payload   API payload.
	 */
	do_action( 'snf_messages_sent', $thread_id, $user_id, $payload );

	return $payload;
}

/**
 * REST permission: club member-area roles only (not applicants).
 *
 * @return bool|WP_Error
 */
function snf_messages_rest_can_access() {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'snf_auth', __( 'Sign in to use messages.', 'super-ninety-four' ), array( 'status' => 401 ) );
	}
	if ( ! function_exists( 'snf_user_can_access_member_areas' ) || ! snf_user_can_access_member_areas() ) {
		return new WP_Error( 'snf_forbidden', __( 'Messages are for club members.', 'super-ninety-four' ), array( 'status' => 403 ) );
	}
	return true;
}

/**
 * Get or create a peer DM thread between two members.
 *
 * @param int $user_a User ID.
 * @param int $user_b User ID.
 * @return int|WP_Error Thread ID.
 */
function snf_messages_get_or_create_peer_thread( $user_a, $user_b ) {
	$user_a = absint( $user_a );
	$user_b = absint( $user_b );

	if ( ! $user_a || ! $user_b || $user_a === $user_b ) {
		return new WP_Error( 'snf_peer_invalid', __( 'Choose another member.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$a = get_userdata( $user_a );
	$b = get_userdata( $user_b );
	if ( ! $a || ! $b ) {
		return new WP_Error( 'snf_peer_missing', __( 'Member not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}

	$low  = min( $user_a, $user_b );
	$high = max( $user_a, $user_b );
	$slug = 'peer-' . $low . '-' . $high;

	global $wpdb;
	$threads_table = $wpdb->prefix . 'snf_message_threads';
	$parts_table   = $wpdb->prefix . 'snf_message_participants';
	$now           = current_time( 'mysql' );

	$thread_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$threads_table} WHERE slug = %s AND is_system = 0 LIMIT 1",
			$slug
		)
	);

	if ( $thread_id ) {
		foreach ( array( $user_a, $user_b ) as $uid ) {
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$parts_table} WHERE thread_id = %d AND user_id = %d LIMIT 1",
					$thread_id,
					$uid
				)
			);
			if ( ! $exists ) {
				$wpdb->insert(
					$parts_table,
					array(
						'thread_id'    => $thread_id,
						'user_id'      => $uid,
						'last_read_id' => 0,
						'joined_at'    => $now,
					),
					array( '%d', '%d', '%d', '%s' )
				);
			}
		}
		return $thread_id;
	}

	$other_name = ( $user_a === get_current_user_id() ) ? $b->display_name : $a->display_name;
	$title      = $other_name ? $other_name : __( 'Direct message', 'super-ninety-four' );

	$wpdb->insert(
		$threads_table,
		array(
			'slug'        => $slug,
			'title'       => $title,
			'subtitle'    => __( 'Private member message', 'super-ninety-four' ),
			'avatar_kind' => 'peer',
			'is_system'   => 0,
			'updated_at'  => $now,
			'created_at'  => $now,
		),
		array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
	);

	$thread_id = (int) $wpdb->insert_id;
	if ( ! $thread_id ) {
		return new WP_Error( 'snf_db', __( 'Could not open conversation.', 'super-ninety-four' ), array( 'status' => 500 ) );
	}

	foreach ( array( $user_a, $user_b ) as $uid ) {
		$wpdb->insert(
			$parts_table,
			array(
				'thread_id'    => $thread_id,
				'user_id'      => $uid,
				'last_read_id' => 0,
				'joined_at'    => $now,
			),
			array( '%d', '%d', '%d', '%s' )
		);
	}

	return $thread_id;
}

/**
 * REST: open or resume a peer DM.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_messages_rest_open_peer( WP_REST_Request $request ) {
	$peer_id = absint( $request->get_param( 'userId' ) );
	$user_id = get_current_user_id();
	$body    = trim( (string) $request->get_param( 'body' ) );

	$thread_id = snf_messages_get_or_create_peer_thread( $user_id, $peer_id );
	if ( is_wp_error( $thread_id ) ) {
		return $thread_id;
	}

	$payload = array(
		'threadId' => $thread_id,
		'threads'  => snf_messages_get_threads_for_user( $user_id ),
	);

	if ( '' !== $body ) {
		$sent = snf_messages_send( $thread_id, $user_id, $body );
		if ( is_wp_error( $sent ) ) {
			return $sent;
		}
		$payload['send'] = $sent;
	}

	return rest_ensure_response( $payload );
}

/**
 * Register REST routes for live messaging.
 */
function snf_messages_register_rest_routes() {
	register_rest_route(
		'snf/v1',
		'/messages/threads',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'snf_messages_rest_list_threads',
			'permission_callback' => 'snf_messages_rest_can_access',
		)
	);

	register_rest_route(
		'snf/v1',
		'/messages/threads/(?P<id>\d+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'snf_messages_rest_get_thread',
			'permission_callback' => 'snf_messages_rest_can_access',
			'args'                => array(
				'id'    => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'after' => array(
					'type'              => 'integer',
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'snf/v1',
		'/messages/peer',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'snf_messages_rest_open_peer',
			'permission_callback' => 'snf_messages_rest_can_access',
			'args'                => array(
				'userId' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'body'   => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
			),
		)
	);

	register_rest_route(
		'snf/v1',
		'/messages/threads/(?P<id>\d+)/send',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'snf_messages_rest_send',
			'permission_callback' => 'snf_messages_rest_can_access',
			'args'                => array(
				'id'   => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'body' => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_textarea_field',
				),
			),
		)
	);

	register_rest_route(
		'snf/v1',
		'/messages/threads/(?P<id>\d+)/read',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'snf_messages_rest_mark_read',
			'permission_callback' => 'snf_messages_rest_can_access',
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
add_action( 'rest_api_init', 'snf_messages_register_rest_routes' );

/**
 * GET /messages/threads
 *
 * @return WP_REST_Response
 */
function snf_messages_rest_list_threads() {
	return rest_ensure_response(
		array(
			'threads' => snf_messages_get_threads_for_user( get_current_user_id() ),
		)
	);
}

/**
 * GET /messages/threads/{id}
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_messages_rest_get_thread( WP_REST_Request $request ) {
	$user_id   = get_current_user_id();
	$thread_id = (int) $request['id'];
	$after     = (int) $request->get_param( 'after' );

	$messages = snf_messages_get_thread_messages( $thread_id, $user_id, $after );
	if ( is_wp_error( $messages ) ) {
		return $messages;
	}

	if ( 0 === $after && ! empty( $messages ) ) {
		$last = end( $messages );
		snf_messages_mark_read( $thread_id, $user_id, (int) $last['id'] );
		reset( $messages );
	}

	$threads = snf_messages_get_threads_for_user( $user_id );
	$thread  = null;
	foreach ( $threads as $item ) {
		if ( (int) $item['id'] === $thread_id ) {
			$thread = $item;
			break;
		}
	}

	return rest_ensure_response(
		array(
			'thread'   => $thread,
			'messages' => $messages,
		)
	);
}

/**
 * POST /messages/threads/{id}/send
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_messages_rest_send( WP_REST_Request $request ) {
	$result = snf_messages_send(
		(int) $request['id'],
		get_current_user_id(),
		(string) $request->get_param( 'body' )
	);

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return rest_ensure_response( $result );
}

/**
 * POST /messages/threads/{id}/read
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_messages_rest_mark_read( WP_REST_Request $request ) {
	$thread_id = (int) $request['id'];
	$user_id   = get_current_user_id();

	if ( ! snf_messages_user_in_thread( $thread_id, $user_id ) ) {
		return new WP_Error( 'snf_forbidden', __( 'Thread not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}

	snf_messages_mark_read( $thread_id, $user_id );
	return rest_ensure_response( array( 'ok' => true ) );
}

/**
 * Bootstrap data for the messages page script.
 *
 * @return array<string, mixed>
 */
function snf_messages_get_bootstrap() {
	$user_id = get_current_user_id();
	$member  = snf_get_hub_member_context();

	$threads = array();
	if ( $user_id ) {
		$threads = snf_messages_get_threads_for_user( $user_id );
	}

	$can_member = $user_id && function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas();

	return array(
		'restUrl'     => esc_url_raw( untrailingslashit( rest_url( 'snf/v1/messages' ) ) ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'loggedIn'    => (bool) $user_id,
		'canMember'   => (bool) $can_member,
		'loginUrl'    => snf_get_page_url( 'member-login' ),
		'communityUrl'=> snf_get_page_url( 'community' ),
		'userId'      => $user_id,
		'userName'    => $member['name'],
		'userAvatar'  => $member['avatar'],
		'pollMs'      => 2500,
		'threads'     => $can_member ? $threads : array(),
		'i18n'        => array(
			'placeholder'   => __( 'Message…', 'super-ninety-four' ),
			'send'          => __( 'Send', 'super-ninety-four' ),
			'emptyThread'   => __( 'No messages yet. Say hello.', 'super-ninety-four' ),
			'selectThread'  => __( 'Select a conversation', 'super-ninety-four' ),
			'signIn'        => __( 'Sign in to message the desk', 'super-ninety-four' ),
			'sending'       => __( 'Sending…', 'super-ninety-four' ),
			'error'         => __( 'Could not send. Try again.', 'super-ninety-four' ),
			'online'        => __( 'Active now', 'super-ninety-four' ),
			'search'        => __( 'Search messages', 'super-ninety-four' ),
			'inbox'         => __( 'Inbox', 'super-ninety-four' ),
			'newMessage'    => __( 'New message', 'super-ninety-four' ),
			'peerHint'      => __( 'Peer DMs use member user IDs via REST /messages/peer.', 'super-ninety-four' ),
		),
	);
}
