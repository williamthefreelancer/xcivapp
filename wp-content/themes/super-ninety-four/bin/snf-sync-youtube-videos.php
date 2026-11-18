<?php
/**
 * Sync recent YouTube uploads into the snf_video library.
 *
 * Run manually:  wp eval-file wp-content/themes/super-ninety-four/bin/snf-sync-youtube-videos.php
 * Also called on deploy and by the snf_youtube_video_sync cron hook.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'snf_sync_youtube_videos' ) ) {
	/**
	 * Pull the channel RSS feed and create any missing video posts.
	 *
	 * @return array{added:int,skipped:int,error:string}
	 */
	function snf_sync_youtube_videos() {
		$result = array(
			'added'   => 0,
			'skipped' => 0,
			'error'   => '',
		);

		/**
		 * Filter the YouTube channel ID used for the RSS sync.
		 *
		 * @param string $channel_id Channel ID (UC...).
		 */
		$channel_id = (string) apply_filters( 'snf_youtube_channel_id', 'UCbhI6iw4w753-hcEKdF3Ugg' );
		if ( ! preg_match( '/^UC[A-Za-z0-9_-]{20,}$/', $channel_id ) ) {
			$result['error'] = 'Invalid YouTube channel ID.';
			return $result;
		}

		$url      = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . rawurlencode( $channel_id );
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => array( 'Accept' => 'application/atom+xml, application/xml, text/xml' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			$result['error'] = $response->get_error_message();
			return $result;
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			$result['error'] = 'YouTube feed returned HTTP ' . wp_remote_retrieve_response_code( $response );
			return $result;
		}

		$xml = simplexml_load_string( wp_remote_retrieve_body( $response ), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR );
		if ( false === $xml ) {
			$result['error'] = 'Could not parse the YouTube feed.';
			return $result;
		}

		$entries = isset( $xml->entry ) ? $xml->entry : array();
		foreach ( $entries as $entry ) {
			$yt      = $entry->children( 'yt', true );
			$media   = $entry->children( 'media', true );
			$video_id = isset( $yt->videoId ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $yt->videoId ) : '';
			$title    = isset( $entry->title ) ? sanitize_text_field( (string) $entry->title ) : '';
			if ( ! $video_id || ! $title ) {
				continue;
			}

			$existing = get_posts(
				array(
					'post_type'      => 'snf_video',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_snf_youtube_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $video_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
			if ( $existing ) {
				++$result['skipped'];
				continue;
			}

			$description = isset( $media->group->description ) ? (string) $media->group->description : '';
			$published   = isset( $entry->published ) ? (string) $entry->published : '';
			$post_date   = $published ? gmdate( 'Y-m-d H:i:s', (int) strtotime( $published ) ) : current_time( 'mysql' );

			$post_id = wp_insert_post(
				array(
					'post_type'    => 'snf_video',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_excerpt' => wp_trim_words( wp_strip_all_tags( $description ), 40, '' ),
					'post_content' => '',
					'post_date'    => $post_date,
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				++$result['skipped'];
				continue;
			}

			update_post_meta( $post_id, '_snf_youtube_id', $video_id );
			update_post_meta( $post_id, '_snf_youtube_url', 'https://www.youtube.com/watch?v=' . $video_id );
			update_post_meta( $post_id, '_snf_media_access', 'public' );
			update_post_meta( $post_id, '_snf_sync_source', 'youtube-rss' );
			++$result['added'];
		}

		return $result;
	}
}

$snf_sync_result = snf_sync_youtube_videos();
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::log(
		sprintf(
			'YouTube sync: %d added, %d skipped%s',
			$snf_sync_result['added'],
			$snf_sync_result['skipped'],
			$snf_sync_result['error'] ? ' — ' . $snf_sync_result['error'] : ''
		)
	);
}

update_option( 'snf_youtube_sync_last', array(
	'added'   => $snf_sync_result['added'],
	'skipped' => $snf_sync_result['skipped'],
	'error'   => $snf_sync_result['error'],
	'time'    => current_time( 'mysql' ),
), false );
