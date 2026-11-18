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
		$type = get_post_type( $post_id );
		if ( self::POST_TYPE === $type ) {
			return true;
		}

		if ( 'product' !== $type ) {
			return false;
		}

		if ( function_exists( 'snf_is_member_select_event' ) && snf_is_member_select_event( $post_id ) ) {
			return true;
		}

		if ( function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( $post_id ) ) {
			return true;
		}

		if ( taxonomy_exists( 'product_cat' ) && has_term( 'events', 'product_cat', $post_id ) ) {
			return true;
		}

		if ( ! taxonomy_exists( 'snf_event_experience' ) ) {
			return false;
		}

		$terms = get_the_terms( $post_id, 'snf_event_experience' );
		return is_array( $terms ) && ! empty( $terms );
	}
}
