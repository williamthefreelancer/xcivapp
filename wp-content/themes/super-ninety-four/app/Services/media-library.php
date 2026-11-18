<?php
/**
 * Videos & Audio library: YouTube CPT, audio CPT, taxonomies, live/hybrid event
 * broadcast meta, settings, and REST — SiriusXM / webinar-venue style catalog.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SNF_MEDIA_SETTINGS_OPTION = 'snf_media_settings';

/**
 * Default media / broadcast settings.
 *
 * @return array<string, mixed>
 */
function snf_media_default_settings() {
	return array(
		'youtube_privacy_enhanced' => 1,
		'default_video_access'     => 'public',
		'default_audio_access'     => 'public',
		'live_provider'            => 'youtube_live',
		'ice_servers'              => "stun:stun.l.google.com:19302\nstun:stun1.l.google.com:19302",
		'audio_room_ttl'           => 120,
		'hub_intro'                => '',
		'enable_live_audio_rooms'  => 1,
	);
}

/**
 * Merged media settings.
 *
 * @return array<string, mixed>
 */
function snf_get_media_settings() {
	$stored = get_option( SNF_MEDIA_SETTINGS_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	return array_merge( snf_media_default_settings(), $stored );
}

/**
 * Register video + audio CPTs.
 */
function snf_register_media_cpts() {
	register_post_type(
		'snf_video',
		array(
			'labels'              => array(
				'name'               => __( 'Videos', 'super-ninety-four' ),
				'singular_name'      => __( 'Video', 'super-ninety-four' ),
				'add_new_item'       => __( 'Add New Video', 'super-ninety-four' ),
				'edit_item'          => __( 'Edit Video', 'super-ninety-four' ),
				'new_item'           => __( 'New Video', 'super-ninety-four' ),
				'view_item'          => __( 'View Video', 'super-ninety-four' ),
				'search_items'       => __( 'Search Videos', 'super-ninety-four' ),
				'not_found'          => __( 'No videos found.', 'super-ninety-four' ),
				'not_found_in_trash' => __( 'No videos found in Trash.', 'super-ninety-four' ),
				'all_items'          => __( 'All Videos', 'super-ninety-four' ),
				'menu_name'          => __( 'Videos', 'super-ninety-four' ),
			),
			'public'              => true,
			'has_archive'         => 'library/videos',
			'rewrite'             => array( 'slug' => 'library/video' ),
			'menu_icon'           => 'dashicons-video-alt3',
			'show_in_menu'        => 'snf-club',
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'exclude_from_search' => false,
		)
	);

	register_post_type(
		'snf_audio',
		array(
			'labels'              => array(
				'name'               => __( 'Audio', 'super-ninety-four' ),
				'singular_name'      => __( 'Audio', 'super-ninety-four' ),
				'add_new_item'       => __( 'Add New Audio', 'super-ninety-four' ),
				'edit_item'          => __( 'Edit Audio', 'super-ninety-four' ),
				'new_item'           => __( 'New Audio', 'super-ninety-four' ),
				'view_item'          => __( 'View Audio', 'super-ninety-four' ),
				'search_items'       => __( 'Search Audio', 'super-ninety-four' ),
				'not_found'          => __( 'No audio found.', 'super-ninety-four' ),
				'not_found_in_trash' => __( 'No audio found in Trash.', 'super-ninety-four' ),
				'all_items'          => __( 'All Audio', 'super-ninety-four' ),
				'menu_name'          => __( 'Audio', 'super-ninety-four' ),
			),
			'public'              => true,
			'has_archive'         => 'library/audio',
			'rewrite'             => array( 'slug' => 'library/audio' ),
			'menu_icon'           => 'dashicons-playlist-audio',
			'show_in_menu'        => 'snf-club',
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'exclude_from_search' => false,
		)
	);
}
add_action( 'init', 'snf_register_media_cpts', 5 );

/**
 * Register media taxonomies.
 */
function snf_register_media_taxonomies() {
	register_taxonomy(
		'snf_media_topic',
		array( 'snf_video', 'snf_audio' ),
		array(
			'labels'            => array(
				'name'          => __( 'Topics', 'super-ninety-four' ),
				'singular_name' => __( 'Topic', 'super-ninety-four' ),
				'search_items'  => __( 'Search Topics', 'super-ninety-four' ),
				'all_items'     => __( 'All Topics', 'super-ninety-four' ),
				'edit_item'     => __( 'Edit Topic', 'super-ninety-four' ),
				'update_item'   => __( 'Update Topic', 'super-ninety-four' ),
				'add_new_item'  => __( 'Add New Topic', 'super-ninety-four' ),
				'new_item_name' => __( 'New Topic Name', 'super-ninety-four' ),
				'menu_name'     => __( 'Topics', 'super-ninety-four' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'library/topic' ),
		)
	);

	register_taxonomy(
		'snf_media_series',
		array( 'snf_video' ),
		array(
			'labels'            => array(
				'name'          => __( 'Video Series', 'super-ninety-four' ),
				'singular_name' => __( 'Series', 'super-ninety-four' ),
				'search_items'  => __( 'Search Series', 'super-ninety-four' ),
				'all_items'     => __( 'All Series', 'super-ninety-four' ),
				'edit_item'     => __( 'Edit Series', 'super-ninety-four' ),
				'update_item'   => __( 'Update Series', 'super-ninety-four' ),
				'add_new_item'  => __( 'Add New Series', 'super-ninety-four' ),
				'new_item_name' => __( 'New Series Name', 'super-ninety-four' ),
				'menu_name'     => __( 'Series', 'super-ninety-four' ),
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'library/series' ),
		)
	);

	register_taxonomy(
		'snf_audio_channel',
		array( 'snf_audio' ),
		array(
			'labels'            => array(
				'name'          => __( 'Channels', 'super-ninety-four' ),
				'singular_name' => __( 'Channel', 'super-ninety-four' ),
				'search_items'  => __( 'Search Channels', 'super-ninety-four' ),
				'all_items'     => __( 'All Channels', 'super-ninety-four' ),
				'edit_item'     => __( 'Edit Channel', 'super-ninety-four' ),
				'update_item'   => __( 'Update Channel', 'super-ninety-four' ),
				'add_new_item'  => __( 'Add New Channel', 'super-ninety-four' ),
				'new_item_name' => __( 'New Channel Name', 'super-ninety-four' ),
				'menu_name'     => __( 'Channels', 'super-ninety-four' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'library/channel' ),
		)
	);

	register_taxonomy(
		'snf_broadcast_format',
		array( 'snf_video', 'snf_audio', 'product', 'event' ),
		array(
			'labels'            => array(
				'name'          => __( 'Broadcast Formats', 'super-ninety-four' ),
				'singular_name' => __( 'Broadcast Format', 'super-ninety-four' ),
				'search_items'  => __( 'Search Formats', 'super-ninety-four' ),
				'all_items'     => __( 'All Formats', 'super-ninety-four' ),
				'edit_item'     => __( 'Edit Format', 'super-ninety-four' ),
				'update_item'   => __( 'Update Format', 'super-ninety-four' ),
				'add_new_item'  => __( 'Add New Format', 'super-ninety-four' ),
				'new_item_name' => __( 'New Format Name', 'super-ninety-four' ),
				'menu_name'     => __( 'Formats', 'super-ninety-four' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'library/format' ),
		)
	);
}
add_action( 'init', 'snf_register_media_taxonomies', 6 );

/**
 * Seed default broadcast-format terms once.
 */
function snf_ensure_media_taxonomy_terms() {
	if ( ! taxonomy_exists( 'snf_broadcast_format' ) ) {
		return;
	}

	$defaults = array(
		'on-demand'   => __( 'On Demand', 'super-ninety-four' ),
		'live'        => __( 'Live Stream', 'super-ninety-four' ),
		'audio-only'  => __( 'Audio Broadcast', 'super-ninety-four' ),
		'hybrid'      => __( 'Hybrid Venue', 'super-ninety-four' ),
		'in-person'   => __( 'In Person', 'super-ninety-four' ),
	);

	foreach ( $defaults as $slug => $label ) {
		if ( ! term_exists( $slug, 'snf_broadcast_format' ) ) {
			wp_insert_term( $label, 'snf_broadcast_format', array( 'slug' => $slug ) );
		}
	}
}
add_action( 'init', 'snf_ensure_media_taxonomy_terms', 30 );

/**
 * Flush rewrites when media CPTs first land.
 */
function snf_media_maybe_flush_rewrites() {
	if ( get_option( 'snf_media_rewrite_flushed_v1' ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'snf_media_rewrite_flushed_v1', 1, false );
}
add_action( 'init', 'snf_media_maybe_flush_rewrites', 99 );
add_action( 'after_switch_theme', 'snf_media_maybe_flush_rewrites' );

/**
 * Access level choices.
 *
 * @return array<string, string>
 */
function snf_media_access_levels() {
	return array(
		'public'   => __( 'Public', 'super-ninety-four' ),
		'member'   => __( 'Members only', 'super-ninety-four' ),
		'ticketed' => __( 'Ticket / event holders', 'super-ninety-four' ),
	);
}

/**
 * Event attendance / delivery modes (venue + broadcast).
 *
 * @return array<string, string>
 */
function snf_event_attendance_modes() {
	return array(
		'in_person' => __( 'In person', 'super-ninety-four' ),
		'virtual'   => __( 'Virtual / web', 'super-ninety-four' ),
		'hybrid'    => __( 'Hybrid (venue + web/audio)', 'super-ninety-four' ),
		'audio'     => __( 'Audio broadcast only', 'super-ninety-four' ),
	);
}

/**
 * Live stream status choices.
 *
 * @return array<string, string>
 */
function snf_live_status_choices() {
	return array(
		'none'      => __( 'No live session', 'super-ninety-four' ),
		'scheduled' => __( 'Scheduled', 'super-ninety-four' ),
		'live'      => __( 'Live now', 'super-ninety-four' ),
		'ended'     => __( 'Ended', 'super-ninety-four' ),
		'vod'       => __( 'Replay / VOD', 'super-ninety-four' ),
	);
}

/**
 * Live provider choices.
 *
 * @return array<string, string>
 */
function snf_live_provider_choices() {
	return array(
		'youtube_live' => __( 'YouTube Live', 'super-ninety-four' ),
		'external'     => __( 'External webinar URL (BigMarker / Zoom / etc.)', 'super-ninety-four' ),
		'audio_hls'    => __( 'Audio HLS / MP3 stream', 'super-ninety-four' ),
		'webrtc'       => __( 'WebRTC audio room (browser)', 'super-ninety-four' ),
	);
}

/**
 * Extract a YouTube video ID from a URL or bare ID.
 *
 * @param string $raw URL or ID.
 * @return string
 */
function snf_parse_youtube_id( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}

	if ( preg_match( '/^[a-zA-Z0-9_-]{11}$/', $raw ) ) {
		return $raw;
	}

	if ( preg_match( '/(?:youtube\.com\/(?:watch\?v=|embed\/|live\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $raw, $m ) ) {
		return $m[1];
	}

	return '';
}

/**
 * Build a privacy-aware YouTube embed URL.
 *
 * @param string $id_or_url YouTube ID or URL.
 * @return string
 */
function snf_get_youtube_embed_url( $id_or_url ) {
	$id = snf_parse_youtube_id( $id_or_url );
	if ( ! $id ) {
		$url = esc_url_raw( (string) $id_or_url );
		return $url ? $url : '';
	}

	$settings = snf_get_media_settings();
	$host     = ! empty( $settings['youtube_privacy_enhanced'] )
		? 'https://www.youtube-nocookie.com'
		: 'https://www.youtube.com';

	return $host . '/embed/' . rawurlencode( $id );
}

/**
 * Normalize an event promo / library video value to an embeddable URL.
 *
 * @param string $raw Stored meta (URL, ID, or embed).
 * @return string
 */
function snf_normalize_video_embed_src( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}

	$yt = snf_get_youtube_embed_url( $raw );
	return $yt ? $yt : esc_url_raw( $raw );
}

/**
 * Whether the current user may view a media item.
 *
 * @param int    $post_id Post ID.
 * @param string $access  Access level.
 * @return bool
 */
function snf_user_can_view_media( $post_id, $access = '' ) {
	$post_id = (int) $post_id;
	$access  = $access ? sanitize_key( $access ) : (string) get_post_meta( $post_id, '_snf_media_access', true );
	if ( ! $access ) {
		$access = 'public';
	}

	if ( 'public' === $access ) {
		return true;
	}

	if ( 'member' === $access ) {
		return function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas();
	}

	if ( 'ticketed' === $access ) {
		if ( function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas() ) {
			return true;
		}
		$product_id = (int) get_post_meta( $post_id, '_snf_related_product_id', true );
		if ( $product_id < 1 || ! is_user_logged_in() || ! function_exists( 'wc_customer_bought_product' ) ) {
			return false;
		}
		$user = wp_get_current_user();
		return wc_customer_bought_product( $user->user_email, $user->ID, $product_id );
	}

	return current_user_can( 'edit_post', $post_id );
}

/**
 * Video meta field definitions.
 *
 * @return array<string, array<string, mixed>>
 */
function snf_video_meta_fields() {
	return array(
		'_snf_youtube_url'        => array(
			'label' => __( 'YouTube URL or ID', 'super-ninety-four' ),
			'type'  => 'text',
		),
		'_snf_media_access'       => array(
			'label'   => __( 'Access', 'super-ninety-four' ),
			'type'    => 'select',
			'choices' => snf_media_access_levels(),
		),
		'_snf_related_product_id' => array(
			'label' => __( 'Related event product ID', 'super-ninety-four' ),
			'type'  => 'number',
		),
		'_snf_media_duration'     => array(
			'label' => __( 'Duration (e.g. 12:34)', 'super-ninety-four' ),
			'type'  => 'text',
		),
	);
}

/**
 * Audio meta field definitions.
 *
 * @return array<string, array<string, mixed>>
 */
function snf_audio_meta_fields() {
	return array(
		'_snf_audio_url'          => array(
			'label' => __( 'Audio file / stream URL (MP3, AAC, HLS .m3u8)', 'super-ninety-four' ),
			'type'  => 'url',
		),
		'_snf_audio_embed_url'    => array(
			'label' => __( 'Optional embed URL (Spotify / Apple / SoundCloud)', 'super-ninety-four' ),
			'type'  => 'url',
		),
		'_snf_media_access'       => array(
			'label'   => __( 'Access', 'super-ninety-four' ),
			'type'    => 'select',
			'choices' => snf_media_access_levels(),
		),
		'_snf_related_product_id' => array(
			'label' => __( 'Related event product ID', 'super-ninety-four' ),
			'type'  => 'number',
		),
		'_snf_media_duration'     => array(
			'label' => __( 'Duration (e.g. 45:00)', 'super-ninety-four' ),
			'type'  => 'text',
		),
		'_snf_is_live_channel'    => array(
			'label' => __( 'Treat as live / always-on channel', 'super-ninety-four' ),
			'type'  => 'checkbox',
		),
	);
}

/**
 * Live / hybrid fields stored on event products (and legacy event CPT).
 *
 * @return array<string, array<string, mixed>>
 */
function snf_event_broadcast_meta_fields() {
	return array(
		'snf_event_video'         => array(
			'label' => __( 'Promo / library YouTube URL', 'super-ninety-four' ),
			'type'  => 'text',
		),
		'_snf_attendance_mode'    => array(
			'label'   => __( 'Attendance mode', 'super-ninety-four' ),
			'type'    => 'select',
			'choices' => snf_event_attendance_modes(),
		),
		'_snf_live_status'        => array(
			'label'   => __( 'Live status', 'super-ninety-four' ),
			'type'    => 'select',
			'choices' => snf_live_status_choices(),
		),
		'_snf_live_provider'      => array(
			'label'   => __( 'Live provider', 'super-ninety-four' ),
			'type'    => 'select',
			'choices' => snf_live_provider_choices(),
		),
		'_snf_live_stream_url'    => array(
			'label' => __( 'Live video / webinar join URL', 'super-ninety-four' ),
			'type'  => 'url',
		),
		'_snf_live_audio_url'     => array(
			'label' => __( 'Live audio stream URL (HLS / MP3)', 'super-ninety-four' ),
			'type'  => 'url',
		),
		'_snf_live_starts_at'     => array(
			'label'       => __( 'Live window starts (Y-m-d H:i:s)', 'super-ninety-four' ),
			'type'        => 'text',
			'placeholder' => '2027-01-05 11:00:00',
		),
		'_snf_live_ends_at'       => array(
			'label'       => __( 'Live window ends (Y-m-d H:i:s)', 'super-ninety-four' ),
			'type'        => 'text',
			'placeholder' => '2027-01-05 13:00:00',
		),
		'_snf_venue_name'         => array(
			'label' => __( 'Venue name (in-person / hybrid)', 'super-ninety-four' ),
			'type'  => 'text',
		),
		'_snf_venue_address'      => array(
			'label' => __( 'Venue address', 'super-ninety-four' ),
			'type'  => 'text',
		),
		'_snf_hybrid_notes'       => array(
			'label' => __( 'Hybrid notes (doors, listen-along, chat)', 'super-ninety-four' ),
			'type'  => 'textarea',
		),
	);
}

/**
 * Register meta boxes for video + audio.
 */
function snf_media_add_meta_boxes() {
	add_meta_box(
		'snf_video_details',
		__( 'YouTube details', 'super-ninety-four' ),
		'snf_render_video_meta_box',
		'snf_video',
		'normal',
		'high'
	);

	add_meta_box(
		'snf_audio_details',
		__( 'Audio / broadcast details', 'super-ninety-four' ),
		'snf_render_audio_meta_box',
		'snf_audio',
		'normal',
		'high'
	);

	add_meta_box(
		'snf_event_broadcast',
		__( 'Live / hybrid broadcast', 'super-ninety-four' ),
		'snf_render_event_broadcast_meta_box',
		'event',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'snf_media_add_meta_boxes' );

/**
 * Render a generic meta field table.
 *
 * @param int                              $post_id Post ID.
 * @param array<string, array<string, mixed>> $fields Field map.
 */
function snf_render_meta_fields_table( $post_id, array $fields ) {
	echo '<table class="form-table"><tbody>';
	foreach ( $fields as $key => $field ) {
		$value   = get_post_meta( $post_id, $key, true );
		$label   = isset( $field['label'] ) ? (string) $field['label'] : $key;
		$type    = isset( $field['type'] ) ? (string) $field['type'] : 'text';
		$choices = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : array();
		$ph      = isset( $field['placeholder'] ) ? (string) $field['placeholder'] : '';

		echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';

		if ( 'select' === $type ) {
			echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '">';
			foreach ( $choices as $opt_value => $opt_label ) {
				printf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( (string) $opt_value ),
					selected( (string) $value, (string) $opt_value, false ),
					esc_html( (string) $opt_label )
				);
			}
			echo '</select>';
		} elseif ( 'checkbox' === $type ) {
			printf(
				'<label><input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s> %3$s</label>',
				esc_attr( $key ),
				checked( (string) $value, '1', false ),
				esc_html__( 'Enabled', 'super-ninety-four' )
			);
		} elseif ( 'textarea' === $type ) {
			printf(
				'<textarea class="large-text" rows="3" name="%1$s" id="%1$s">%2$s</textarea>',
				esc_attr( $key ),
				esc_textarea( (string) $value )
			);
		} elseif ( 'number' === $type ) {
			printf(
				'<input type="number" class="small-text" name="%1$s" id="%1$s" value="%2$s" min="0">',
				esc_attr( $key ),
				esc_attr( (string) $value )
			);
		} else {
			$input_type = ( 'url' === $type ) ? 'url' : 'text';
			printf(
				'<input type="%1$s" class="large-text" name="%2$s" id="%2$s" value="%3$s" placeholder="%4$s">',
				esc_attr( $input_type ),
				esc_attr( $key ),
				esc_attr( (string) $value ),
				esc_attr( $ph )
			);
		}

		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

/**
 * Video meta box.
 *
 * @param WP_Post $post Post.
 */
function snf_render_video_meta_box( $post ) {
	wp_nonce_field( 'snf_save_video_meta', 'snf_video_meta_nonce' );
	snf_render_meta_fields_table( (int) $post->ID, snf_video_meta_fields() );
	$id = snf_parse_youtube_id( (string) get_post_meta( $post->ID, '_snf_youtube_url', true ) );
	if ( $id ) {
		echo '<p><strong>' . esc_html__( 'Resolved ID:', 'super-ninety-four' ) . '</strong> <code>' . esc_html( $id ) . '</code></p>';
	}
}

/**
 * Audio meta box.
 *
 * @param WP_Post $post Post.
 */
function snf_render_audio_meta_box( $post ) {
	wp_nonce_field( 'snf_save_audio_meta', 'snf_audio_meta_nonce' );
	snf_render_meta_fields_table( (int) $post->ID, snf_audio_meta_fields() );
}

/**
 * Legacy event CPT broadcast meta box.
 *
 * @param WP_Post $post Post.
 */
function snf_render_event_broadcast_meta_box( $post ) {
	wp_nonce_field( 'snf_save_event_broadcast_meta', 'snf_event_broadcast_nonce' );
	snf_render_meta_fields_table( (int) $post->ID, snf_event_broadcast_meta_fields() );
}

/**
 * Persist a meta field map from $_POST.
 *
 * @param int                              $post_id Post ID.
 * @param array<string, array<string, mixed>> $fields Fields.
 */
function snf_save_meta_fields_from_post( $post_id, array $fields ) {
	foreach ( $fields as $key => $field ) {
		$type = isset( $field['type'] ) ? (string) $field['type'] : 'text';

		if ( 'checkbox' === $type ) {
			update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? '1' : '0' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			continue;
		}

		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			continue;
		}

		$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( 'textarea' === $type ) {
			update_post_meta( $post_id, $key, sanitize_textarea_field( $raw ) );
		} elseif ( 'url' === $type ) {
			update_post_meta( $post_id, $key, esc_url_raw( (string) $raw ) );
		} elseif ( 'number' === $type ) {
			update_post_meta( $post_id, $key, absint( $raw ) );
		} elseif ( 'select' === $type ) {
			$choices = isset( $field['choices'] ) ? array_keys( (array) $field['choices'] ) : array();
			$val     = sanitize_key( (string) $raw );
			if ( in_array( $val, $choices, true ) ) {
				update_post_meta( $post_id, $key, $val );
			}
		} else {
			update_post_meta( $post_id, $key, sanitize_text_field( (string) $raw ) );
		}
	}
}

/**
 * Save video meta.
 *
 * @param int $post_id Post ID.
 */
function snf_save_video_meta( $post_id ) {
	if ( ! isset( $_POST['snf_video_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snf_video_meta_nonce'] ) ), 'snf_save_video_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	snf_save_meta_fields_from_post( $post_id, snf_video_meta_fields() );

	$url = (string) get_post_meta( $post_id, '_snf_youtube_url', true );
	$id  = snf_parse_youtube_id( $url );
	if ( $id ) {
		update_post_meta( $post_id, '_snf_youtube_id', $id );
	}
}
add_action( 'save_post_snf_video', 'snf_save_video_meta' );

/**
 * Save audio meta.
 *
 * @param int $post_id Post ID.
 */
function snf_save_audio_meta( $post_id ) {
	if ( ! isset( $_POST['snf_audio_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snf_audio_meta_nonce'] ) ), 'snf_save_audio_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	snf_save_meta_fields_from_post( $post_id, snf_audio_meta_fields() );
}
add_action( 'save_post_snf_audio', 'snf_save_audio_meta' );

/**
 * Save legacy event broadcast meta.
 *
 * @param int $post_id Post ID.
 */
function snf_save_event_broadcast_meta( $post_id ) {
	if ( ! isset( $_POST['snf_event_broadcast_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snf_event_broadcast_nonce'] ) ), 'snf_save_event_broadcast_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	snf_save_meta_fields_from_post( $post_id, snf_event_broadcast_meta_fields() );
}
add_action( 'save_post_event', 'snf_save_event_broadcast_meta' );

/**
 * WooCommerce product fields for promo video + live/hybrid.
 */
function snf_woocommerce_product_broadcast_fields() {
	echo '<div class="options_group">';
	echo '<p class="form-field"><strong>' . esc_html__( 'Videos, audio & live broadcast', 'super-ninety-four' ) . '</strong></p>';

	foreach ( snf_event_broadcast_meta_fields() as $key => $field ) {
		$type    = isset( $field['type'] ) ? (string) $field['type'] : 'text';
		$label   = isset( $field['label'] ) ? (string) $field['label'] : $key;
		$choices = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : array();

		if ( 'select' === $type ) {
			woocommerce_wp_select(
				array(
					'id'      => $key,
					'label'   => $label,
					'options' => $choices,
				)
			);
		} elseif ( 'textarea' === $type ) {
			woocommerce_wp_textarea_input(
				array(
					'id'    => $key,
					'label' => $label,
				)
			);
		} else {
			woocommerce_wp_text_input(
				array(
					'id'          => $key,
					'label'       => $label,
					'desc_tip'    => true,
					'description' => $label,
					'placeholder' => isset( $field['placeholder'] ) ? (string) $field['placeholder'] : '',
				)
			);
		}
	}

	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'snf_woocommerce_product_broadcast_fields', 25 );

/**
 * Persist Woo broadcast meta.
 *
 * @param int $product_id Product ID.
 */
function snf_woocommerce_save_product_broadcast_fields( $product_id ) {
	snf_save_meta_fields_from_post( (int) $product_id, snf_event_broadcast_meta_fields() );
}
add_action( 'woocommerce_process_product_meta', 'snf_woocommerce_save_product_broadcast_fields', 25 );

/**
 * Read live/hybrid payload for an event or product.
 *
 * @param int|WP_Post|null $post Post.
 * @return array<string, mixed>
 */
function snf_get_event_broadcast_data( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! in_array( $post->post_type, array( 'event', 'product' ), true ) ) {
		return array();
	}

	$post_id  = (int) $post->ID;
	$mode     = (string) get_post_meta( $post_id, '_snf_attendance_mode', true );
	$status   = (string) get_post_meta( $post_id, '_snf_live_status', true );
	$provider = (string) get_post_meta( $post_id, '_snf_live_provider', true );
	$settings = snf_get_media_settings();

	if ( ! $mode ) {
		$mode = 'in_person';
	}
	if ( ! $status ) {
		$status = 'none';
	}
	if ( ! $provider ) {
		$provider = (string) $settings['live_provider'];
	}

	$promo = (string) get_post_meta( $post_id, 'snf_event_video', true );
	$stream = (string) get_post_meta( $post_id, '_snf_live_stream_url', true );
	$audio  = (string) get_post_meta( $post_id, '_snf_live_audio_url', true );

	$embed = '';
	if ( $stream && 'youtube_live' === $provider ) {
		$embed = snf_get_youtube_embed_url( $stream );
	} elseif ( $promo ) {
		$embed = snf_normalize_video_embed_src( $promo );
	}

	$now       = time();
	$starts_at = (string) get_post_meta( $post_id, '_snf_live_starts_at', true );
	$ends_at   = (string) get_post_meta( $post_id, '_snf_live_ends_at', true );
	$starts_ts = $starts_at ? (int) strtotime( $starts_at ) : 0;
	$ends_ts   = $ends_at ? (int) strtotime( $ends_at ) : 0;

	$in_window = true;
	if ( $starts_ts && $now < $starts_ts ) {
		$in_window = false;
	}
	if ( $ends_ts && $now > $ends_ts ) {
		$in_window = false;
	}

	$is_live = ( 'live' === $status ) || ( 'scheduled' === $status && $in_window && ( $stream || $audio || 'webrtc' === $provider ) );

	return array(
		'mode'           => $mode,
		'mode_label'     => snf_event_attendance_modes()[ $mode ] ?? $mode,
		'status'         => $status,
		'status_label'   => snf_live_status_choices()[ $status ] ?? $status,
		'provider'       => $provider,
		'provider_label' => snf_live_provider_choices()[ $provider ] ?? $provider,
		'promo_video'    => snf_normalize_video_embed_src( $promo ),
		'stream_url'     => $stream,
		'stream_embed'   => $embed,
		'audio_url'      => $audio,
		'starts_at'      => $starts_at,
		'ends_at'        => $ends_at,
		'venue_name'     => (string) get_post_meta( $post_id, '_snf_venue_name', true ),
		'venue_address'  => (string) get_post_meta( $post_id, '_snf_venue_address', true ),
		'hybrid_notes'   => (string) get_post_meta( $post_id, '_snf_hybrid_notes', true ),
		'is_live'        => $is_live,
		'in_window'      => $in_window,
		'room_id'        => 'event-' . $post_id,
		'can_host'       => current_user_can( 'manage_options' ) || current_user_can( 'edit_post', $post_id ),
		'webrtc_enabled' => ! empty( $settings['enable_live_audio_rooms'] ) && 'webrtc' === $provider,
	);
}

/**
 * Map a video post to a card/API payload.
 *
 * @param int|WP_Post $post Post.
 * @return array<string, mixed>|null
 */
function snf_get_video_item_data( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'snf_video' !== $post->post_type ) {
		return null;
	}

	$access = (string) get_post_meta( $post->ID, '_snf_media_access', true );
	if ( ! $access ) {
		$access = (string) snf_get_media_settings()['default_video_access'];
	}

	$yt_raw = (string) get_post_meta( $post->ID, '_snf_youtube_url', true );
	$yt_id  = (string) get_post_meta( $post->ID, '_snf_youtube_id', true );
	if ( ! $yt_id ) {
		$yt_id = snf_parse_youtube_id( $yt_raw );
	}

	$thumb = get_the_post_thumbnail_url( $post, 'medium_large' );
	if ( ! $thumb && $yt_id ) {
		$thumb = 'https://i.ytimg.com/vi/' . rawurlencode( $yt_id ) . '/hqdefault.jpg';
	}

	$topics = wp_get_post_terms( $post->ID, 'snf_media_topic', array( 'fields' => 'names' ) );
	$series = wp_get_post_terms( $post->ID, 'snf_media_series', array( 'fields' => 'names' ) );

	return array(
		'id'          => (int) $post->ID,
		'title'       => get_the_title( $post ),
		'excerpt'     => get_the_excerpt( $post ),
		'url'         => get_permalink( $post ),
		'thumbnail'   => $thumb ? $thumb : '',
		'youtube_id'  => $yt_id,
		'embed_url'   => $yt_id ? snf_get_youtube_embed_url( $yt_id ) : snf_normalize_video_embed_src( $yt_raw ),
		'access'      => $access,
		'can_view'    => snf_user_can_view_media( $post->ID, $access ),
		'duration'    => (string) get_post_meta( $post->ID, '_snf_media_duration', true ),
		'topics'      => is_wp_error( $topics ) ? array() : array_values( $topics ),
		'series'      => is_wp_error( $series ) ? array() : array_values( $series ),
		'product_id'  => (int) get_post_meta( $post->ID, '_snf_related_product_id', true ),
	);
}

/**
 * Map an audio post to a card/API payload.
 *
 * @param int|WP_Post $post Post.
 * @return array<string, mixed>|null
 */
function snf_get_audio_item_data( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'snf_audio' !== $post->post_type ) {
		return null;
	}

	$access = (string) get_post_meta( $post->ID, '_snf_media_access', true );
	if ( ! $access ) {
		$access = (string) snf_get_media_settings()['default_audio_access'];
	}

	$channels = wp_get_post_terms( $post->ID, 'snf_audio_channel', array( 'fields' => 'names' ) );
	$topics   = wp_get_post_terms( $post->ID, 'snf_media_topic', array( 'fields' => 'names' ) );
	$thumb    = get_the_post_thumbnail_url( $post, 'medium_large' );

	return array(
		'id'           => (int) $post->ID,
		'title'        => get_the_title( $post ),
		'excerpt'      => get_the_excerpt( $post ),
		'url'          => get_permalink( $post ),
		'thumbnail'    => $thumb ? $thumb : '',
		'audio_url'    => (string) get_post_meta( $post->ID, '_snf_audio_url', true ),
		'embed_url'    => (string) get_post_meta( $post->ID, '_snf_audio_embed_url', true ),
		'access'       => $access,
		'can_view'     => snf_user_can_view_media( $post->ID, $access ),
		'duration'     => (string) get_post_meta( $post->ID, '_snf_media_duration', true ),
		'is_live'      => '1' === (string) get_post_meta( $post->ID, '_snf_is_live_channel', true ),
		'channels'     => is_wp_error( $channels ) ? array() : array_values( $channels ),
		'topics'       => is_wp_error( $topics ) ? array() : array_values( $topics ),
		'product_id'   => (int) get_post_meta( $post->ID, '_snf_related_product_id', true ),
	);
}

/**
 * Query published videos for the hub/API.
 *
 * @param array<string, mixed> $args Query args.
 * @return array<int, array<string, mixed>>
 */
function snf_query_video_library( $args = array() ) {
	$defaults = array(
		'posts_per_page' => 24,
		'topic'          => '',
		'series'         => '',
		's'              => '',
	);
	$args     = wp_parse_args( $args, $defaults );

	$query_args = array(
		'post_type'      => 'snf_video',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['posts_per_page'],
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	);

	if ( ! empty( $args['s'] ) ) {
		$query_args['s'] = sanitize_text_field( (string) $args['s'] );
	}

	$tax_query = array();
	if ( ! empty( $args['topic'] ) ) {
		$tax_query[] = array(
			'taxonomy' => 'snf_media_topic',
			'field'    => 'slug',
			'terms'    => sanitize_title( (string) $args['topic'] ),
		);
	}
	if ( ! empty( $args['series'] ) ) {
		$tax_query[] = array(
			'taxonomy' => 'snf_media_series',
			'field'    => 'slug',
			'terms'    => sanitize_title( (string) $args['series'] ),
		);
	}
	if ( $tax_query ) {
		$query_args['tax_query'] = $tax_query;
	}

	$query = new WP_Query( $query_args );
	$items = array();
	foreach ( $query->posts as $post ) {
		$item = snf_get_video_item_data( $post );
		if ( $item ) {
			$items[] = $item;
		}
	}
	return $items;
}

/**
 * Query published audio for the hub/API.
 *
 * @param array<string, mixed> $args Query args.
 * @return array<int, array<string, mixed>>
 */
function snf_query_audio_library( $args = array() ) {
	$defaults = array(
		'posts_per_page' => 24,
		'channel'        => '',
		'topic'          => '',
		's'              => '',
	);
	$args     = wp_parse_args( $args, $defaults );

	$query_args = array(
		'post_type'      => 'snf_audio',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['posts_per_page'],
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	);

	if ( ! empty( $args['s'] ) ) {
		$query_args['s'] = sanitize_text_field( (string) $args['s'] );
	}

	$tax_query = array();
	if ( ! empty( $args['channel'] ) ) {
		$tax_query[] = array(
			'taxonomy' => 'snf_audio_channel',
			'field'    => 'slug',
			'terms'    => sanitize_title( (string) $args['channel'] ),
		);
	}
	if ( ! empty( $args['topic'] ) ) {
		$tax_query[] = array(
			'taxonomy' => 'snf_media_topic',
			'field'    => 'slug',
			'terms'    => sanitize_title( (string) $args['topic'] ),
		);
	}
	if ( $tax_query ) {
		$query_args['tax_query'] = $tax_query;
	}

	$query = new WP_Query( $query_args );
	$items = array();
	foreach ( $query->posts as $post ) {
		$item = snf_get_audio_item_data( $post );
		if ( $item ) {
			$items[] = $item;
		}
	}
	return $items;
}

/**
 * Upcoming / live hybrid event products for the hub.
 *
 * @param int $limit Max items.
 * @return array<int, array<string, mixed>>
 */
function snf_query_upcoming_broadcasts( $limit = 8 ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array();
	}

	$query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 40,
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_snf_attendance_mode',
					'value'   => array( 'virtual', 'hybrid', 'audio' ),
					'compare' => 'IN',
				),
				array(
					'key'     => '_snf_live_status',
					'value'   => array( 'scheduled', 'live', 'vod' ),
					'compare' => 'IN',
				),
			),
		)
	);

	$items = array();
	foreach ( $query->posts as $post ) {
		$broadcast = snf_get_event_broadcast_data( $post );
		if ( ! $broadcast || ( 'none' === $broadcast['status'] && 'in_person' === $broadcast['mode'] ) ) {
			continue;
		}
		$items[] = array(
			'id'         => (int) $post->ID,
			'title'      => get_the_title( $post ),
			'url'        => get_permalink( $post ),
			'image'      => get_the_post_thumbnail_url( $post, 'medium_large' ),
			'when'       => (string) get_post_meta( $post->ID, '_snf_when', true ),
			'location'   => (string) get_post_meta( $post->ID, '_snf_location', true ),
			'broadcast'  => $broadcast,
		);
		if ( count( $items ) >= (int) $limit ) {
			break;
		}
	}

	return $items;
}

/**
 * Parse ICE server lines from settings.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_media_get_ice_servers() {
	$settings = snf_get_media_settings();
	$lines    = preg_split( '/\r\n|\r|\n/', (string) $settings['ice_servers'] );
	$servers  = array();

	if ( ! is_array( $lines ) ) {
		return array( array( 'urls' => 'stun:stun.l.google.com:19302' ) );
	}

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line || 0 === strpos( $line, '#' ) ) {
			continue;
		}
		$servers[] = array( 'urls' => $line );
	}

	return $servers ? $servers : array( array( 'urls' => 'stun:stun.l.google.com:19302' ) );
}

/**
 * Register media REST routes.
 */
function snf_register_media_rest_routes() {
	register_rest_route(
		'snf/v1',
		'/media/videos',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_media_videos',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'snf/v1',
		'/media/audio',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_media_audio',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'snf/v1',
		'/media/live/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_media_live',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'snf/v1',
		'/media/room/(?P<room>[a-zA-Z0-9_-]+)/signal',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'snf_rest_media_room_poll',
				'permission_callback' => 'snf_rest_media_room_permission',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'snf_rest_media_room_signal',
				'permission_callback' => 'snf_rest_media_room_permission',
			),
		)
	);
}
add_action( 'rest_api_init', 'snf_register_media_rest_routes' );

/**
 * REST: video library.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function snf_rest_media_videos( WP_REST_Request $request ) {
	$items = snf_query_video_library(
		array(
			'posts_per_page' => min( 50, max( 1, (int) $request->get_param( 'per_page' ) ?: 24 ) ),
			'topic'          => (string) $request->get_param( 'topic' ),
			'series'         => (string) $request->get_param( 'series' ),
			's'              => (string) $request->get_param( 'q' ),
		)
	);

	$public = array_values(
		array_filter(
			$items,
			static function ( $item ) {
				return ! empty( $item['can_view'] );
			}
		)
	);

	return rest_ensure_response( array( 'items' => $public ) );
}

/**
 * REST: audio library.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function snf_rest_media_audio( WP_REST_Request $request ) {
	$items = snf_query_audio_library(
		array(
			'posts_per_page' => min( 50, max( 1, (int) $request->get_param( 'per_page' ) ?: 24 ) ),
			'channel'        => (string) $request->get_param( 'channel' ),
			'topic'          => (string) $request->get_param( 'topic' ),
			's'              => (string) $request->get_param( 'q' ),
		)
	);

	$public = array_values(
		array_filter(
			$items,
			static function ( $item ) {
				return ! empty( $item['can_view'] );
			}
		)
	);

	return rest_ensure_response( array( 'items' => $public ) );
}

/**
 * REST: live event payload.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_media_live( WP_REST_Request $request ) {
	$id   = (int) $request['id'];
	$post = get_post( $id );
	if ( ! $post || ! in_array( $post->post_type, array( 'product', 'event' ), true ) ) {
		return new WP_Error( 'snf_live_missing', __( 'Event not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}

	return rest_ensure_response(
		array(
			'id'        => $id,
			'title'     => get_the_title( $post ),
			'url'       => get_permalink( $post ),
			'broadcast' => snf_get_event_broadcast_data( $post ),
			'ice'       => snf_media_get_ice_servers(),
		)
	);
}

/**
 * Permission for WebRTC room signaling (logged-in members/staff).
 *
 * @return bool
 */
function snf_rest_media_room_permission() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	if ( function_exists( 'snf_user_can_access_member_areas' ) ) {
		return snf_user_can_access_member_areas();
	}
	return true;
}

/**
 * Transient key for a media room.
 *
 * @param string $room Room id.
 * @return string
 */
function snf_media_room_transient_key( $room ) {
	return 'snf_media_room_' . sanitize_key( $room );
}

/**
 * Poll WebRTC signals for a room.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function snf_rest_media_room_poll( WP_REST_Request $request ) {
	$room = sanitize_key( (string) $request['room'] );
	$key  = snf_media_room_transient_key( $room );
	$data = get_transient( $key );
	if ( ! is_array( $data ) ) {
		$data = array(
			'host'     => 0,
			'peers'    => array(),
			'signals'  => array(),
			'updated'  => time(),
		);
	}

	$user_id = get_current_user_id();
	$mine    = array();
	$kept    = array();
	foreach ( (array) $data['signals'] as $signal ) {
		if ( ! is_array( $signal ) ) {
			continue;
		}
		$to = isset( $signal['to'] ) ? (int) $signal['to'] : 0;
		if ( $to === $user_id ) {
			$mine[] = $signal;
			continue;
		}
		$kept[] = $signal;
	}
	$data['signals'] = $kept;
	$ttl             = max( 30, (int) snf_get_media_settings()['audio_room_ttl'] );
	set_transient( $key, $data, $ttl );

	return rest_ensure_response(
		array(
			'room'     => $room,
			'host'     => (int) ( $data['host'] ?? 0 ),
			'peers'    => array_values( (array) ( $data['peers'] ?? array() ) ),
			'signals'  => $mine,
			'ice'      => snf_media_get_ice_servers(),
		)
	);
}

/**
 * Post a WebRTC signal / presence update.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_media_room_signal( WP_REST_Request $request ) {
	$room = sanitize_key( (string) $request['room'] );
	$key  = snf_media_room_transient_key( $room );
	$data = get_transient( $key );
	if ( ! is_array( $data ) ) {
		$data = array(
			'host'    => 0,
			'peers'   => array(),
			'signals' => array(),
			'updated' => time(),
		);
	}

	$user_id = get_current_user_id();
	$action  = sanitize_key( (string) $request->get_param( 'action' ) );
	$ttl     = max( 30, (int) snf_get_media_settings()['audio_room_ttl'] );

	if ( 'claim_host' === $action ) {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_products' ) ) {
			return new WP_Error( 'snf_room_forbidden', __( 'Only staff can host a live audio room.', 'super-ninety-four' ), array( 'status' => 403 ) );
		}
		$data['host'] = $user_id;
	} elseif ( 'leave' === $action ) {
		if ( (int) ( $data['host'] ?? 0 ) === $user_id ) {
			$data['host'] = 0;
		}
		$data['peers'] = array_values(
			array_filter(
				(array) ( $data['peers'] ?? array() ),
				static function ( $peer ) use ( $user_id ) {
					return (int) $peer !== $user_id;
				}
			)
		);
	} elseif ( 'join' === $action ) {
		$peers   = (array) ( $data['peers'] ?? array() );
		$peers[] = $user_id;
		$data['peers'] = array_values( array_unique( array_map( 'intval', $peers ) ) );
	} else {
		$payload = $request->get_param( 'payload' );
		$to      = (int) $request->get_param( 'to' );
		$type    = sanitize_key( (string) $request->get_param( 'type' ) );
		if ( ! $to || ! $type ) {
			return new WP_Error( 'snf_room_bad', __( 'Missing signal target or type.', 'super-ninety-four' ), array( 'status' => 400 ) );
		}
		$signals   = (array) ( $data['signals'] ?? array() );
		$signals[] = array(
			'from'    => $user_id,
			'to'      => $to,
			'type'    => $type,
			'payload' => $payload,
			'at'      => time(),
		);
		$data['signals'] = array_slice( $signals, -40 );
	}

	$data['updated'] = time();
	set_transient( $key, $data, $ttl );

	return rest_ensure_response(
		array(
			'ok'   => true,
			'room' => $room,
			'host' => (int) ( $data['host'] ?? 0 ),
		)
	);
}

/**
 * Admin menu: media settings under Videos & Audio.
 */
function snf_media_admin_menu() {
	// Legacy slug kept for bookmarks; UI lives in SNF Club → Videos & Audio.
	add_submenu_page(
		'snf-club',
		__( 'Media Settings', 'super-ninety-four' ),
		__( 'Media settings (legacy)', 'super-ninety-four' ),
		'manage_options',
		'snf-media-settings',
		'snf_media_render_settings_page'
	);
}
add_action( 'admin_menu', 'snf_media_admin_menu', 20 );

/**
 * Save media settings.
 */
function snf_media_handle_settings_save() {
	if ( ! isset( $_POST['snf_media_settings_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_media_settings', 'snf_media_settings_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$current = snf_get_media_settings();
	$access  = snf_media_access_levels();
	$providers = snf_live_provider_choices();

	$current['youtube_privacy_enhanced'] = isset( $_POST['youtube_privacy_enhanced'] ) ? 1 : 0;
	$current['enable_live_audio_rooms']  = isset( $_POST['enable_live_audio_rooms'] ) ? 1 : 0;
	$current['hub_intro']                = isset( $_POST['hub_intro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hub_intro'] ) ) : '';
	$current['ice_servers']              = isset( $_POST['ice_servers'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ice_servers'] ) ) : $current['ice_servers'];
	$current['audio_room_ttl']           = isset( $_POST['audio_room_ttl'] ) ? max( 30, absint( $_POST['audio_room_ttl'] ) ) : 120;

	$video_access = isset( $_POST['default_video_access'] ) ? sanitize_key( wp_unslash( $_POST['default_video_access'] ) ) : 'public';
	$audio_access = isset( $_POST['default_audio_access'] ) ? sanitize_key( wp_unslash( $_POST['default_audio_access'] ) ) : 'public';
	$provider     = isset( $_POST['live_provider'] ) ? sanitize_key( wp_unslash( $_POST['live_provider'] ) ) : 'youtube_live';

	$current['default_video_access'] = isset( $access[ $video_access ] ) ? $video_access : 'public';
	$current['default_audio_access'] = isset( $access[ $audio_access ] ) ? $audio_access : 'public';
	$current['live_provider']        = isset( $providers[ $provider ] ) ? $provider : 'youtube_live';

	update_option( SNF_MEDIA_SETTINGS_OPTION, $current, false );

	$redirect_page = 'snf-club-media';
	if ( isset( $_POST['snf_media_redirect'] ) ) {
		$candidate = sanitize_key( wp_unslash( $_POST['snf_media_redirect'] ) );
		if ( in_array( $candidate, array( 'snf-club-media', 'snf-media-settings' ), true ) ) {
			$redirect_page = $candidate;
		}
	}

	if ( 'snf-media-settings' === $redirect_page ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'snf-media-settings',
					'updated' => '1',
				),
				admin_url( 'edit.php?post_type=snf_video' )
			)
		);
	} else {
		wp_safe_redirect( admin_url( 'admin.php?page=snf-club-media&updated=1' ) );
	}
	exit;
}
add_action( 'admin_post_snf_media_save_settings', 'snf_media_handle_settings_save' );

/**
 * Render media settings page (legacy menu → Club media dashboard).
 */
function snf_media_render_settings_page() {
	if ( function_exists( 'snf_admin_render_media' ) ) {
		snf_admin_render_media();
		return;
	}
	echo '<div class="wrap"><p>' . esc_html__( 'Media dashboard unavailable.', 'super-ninety-four' ) . '</p></div>';
}

/**
 * Bootstrap object for media-library.js.
 *
 * @return array<string, mixed>
 */
function snf_get_media_js_bootstrap() {
	$settings = snf_get_media_settings();

	return array(
		'restUrl'   => esc_url_raw( rest_url( 'snf/v1/media' ) ),
		'nonce'     => wp_create_nonce( 'wp_rest' ),
		'ice'       => snf_media_get_ice_servers(),
		'webrtc'    => ! empty( $settings['enable_live_audio_rooms'] ),
		'loginUrl'  => function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'member-login' ) : wp_login_url(),
		'i18n'      => array(
			'locked'   => __( 'Members or ticket holders only.', 'super-ninety-four' ),
			'listening'=> __( 'Listening…', 'super-ninety-four' ),
			'hosting'  => __( 'You are hosting this audio room.', 'super-ninety-four' ),
			'joinLive' => __( 'Join live audio', 'super-ninety-four' ),
			'leave'    => __( 'Leave room', 'super-ninety-four' ),
		),
	);
}

/**
 * Whether the current request is a media library surface.
 *
 * @return bool
 */
function snf_is_media_library_request() {
	return is_page( 'videos-audio' )
		|| snf_is_page_template( 'page-templates/videos-audio.php' )
		|| is_singular( array( 'snf_video', 'snf_audio' ) )
		|| is_post_type_archive( array( 'snf_video', 'snf_audio' ) )
		|| is_tax( array( 'snf_media_topic', 'snf_media_series', 'snf_audio_channel', 'snf_broadcast_format' ) );
}
