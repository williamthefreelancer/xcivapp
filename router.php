<?php
/**
 * Router for PHP's built-in server so WordPress permalinks work locally.
 * Usage: php -S localhost:8080 router.php
 */
$root = __DIR__;
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = $root . $path;

if ( $path !== '/' && is_file( $file ) ) {
	return false;
}

require $root . '/index.php';
