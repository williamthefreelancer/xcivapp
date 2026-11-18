<?php
/**
 * Media library page orchestration (thin controller over Services).
 *
 * @package SuperNinetyFour
 */

namespace SNF\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MediaLibraryController {
	/**
	 * Whether the current request is a media library surface.
	 */
	public static function is_library_request(): bool {
		return function_exists( 'snf_is_media_library_request' ) && snf_is_media_library_request();
	}
}
