<?php
/**
 * Native WordPress search — hand off to the events & services search engine.
 */
$query = get_search_query();
$events_url = get_post_type_archive_link( 'event' );
if ( ! $events_url ) {
	$events_url = home_url( '/events/' );
}

wp_safe_redirect(
	add_query_arg(
		array(
			'q' => $query,
		),
		$events_url
	)
);
exit;
