<?php
/**
 * Videos & Audio CPT helpers.
 *
 * @package SuperNinetyFour
 */

namespace SNF\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MediaItem {
	public const VIDEO_TYPE = 'snf_video';
	public const AUDIO_TYPE = 'snf_audio';

	/**
	 * @param int $post_id Post ID.
	 */
	public static function is_video( int $post_id ): bool {
		return get_post_type( $post_id ) === self::VIDEO_TYPE;
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public static function is_audio( int $post_id ): bool {
		return get_post_type( $post_id ) === self::AUDIO_TYPE;
	}
}
