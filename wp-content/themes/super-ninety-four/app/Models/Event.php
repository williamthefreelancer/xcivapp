<?php
/**
 * Event listing model helpers.
 *
 * @package SuperNinetyFour
 */

namespace SNF\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Event {
	public const POST_TYPE = 'event';

	/**
	 * Whether a post ID is an event CPT or linked Woo product landing.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function is_event( int $post_id ): bool {
		return get_post_type( $post_id ) === self::POST_TYPE;
	}
}
