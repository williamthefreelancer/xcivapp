<?php
/**
 * Video Newsletter signups.
 *
 * Stores subscribers in WordPress (snf_subscriber CPT, visible under SNF Club)
 * and emails the desk. No third-party service or API keys required.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * YouTube channel behind the video newsletter.
 *
 * @return string
 */
function snf_newsletter_youtube_url() {
	/**
	 * Filter the YouTube channel URL shown on the Newsletter page.
	 *
	 * @param string $url Channel URL.
	 */
	return (string) apply_filters( 'snf_newsletter_youtube_url', 'https://www.youtube.com/@williamslist' );
}

/**
 * Register the private subscriber inbox.
 */
function snf_newsletter_register_subscriber_cpt() {
	register_post_type(
		'snf_subscriber',
		array(
			'labels'              => array(
				'name'          => __( 'Newsletter Subscribers', 'super-ninety-four' ),
				'singular_name' => __( 'Subscriber', 'super-ninety-four' ),
				'not_found'     => __( 'No subscribers yet.', 'super-ninety-four' ),
			),
			'public'              => false,
			'show_ui'             => true,
			// The branded Newsletter Subscribers dashboard replaces the stock CPT list.
			'show_in_menu'        => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
		)
	);
}
add_action( 'init', 'snf_newsletter_register_subscriber_cpt', 8 );

/**
 * Send the stock subscriber list URL to the branded dashboard.
 *
 * The classic list stays available at edit.php?post_type=snf_subscriber&snf_classic=1
 * (and from the dashboard's Quick actions panel).
 */
function snf_newsletter_subscribers_dashboard_redirect() {
	global $pagenow;
	if ( 'edit.php' !== $pagenow || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'snf_subscriber' !== $type || isset( $_GET['snf_classic'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	// Let filtered / paginated list views load normally.
	$extra = array_diff( array_keys( (array) $_GET ), array( 'post_type' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $extra ) {
		return;
	}

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-subscribers' ) );
	exit;
}
add_action( 'admin_init', 'snf_newsletter_subscribers_dashboard_redirect' );

/**
 * Stream the newsletter subscribers as a CSV download.
 */
function snf_newsletter_export_subscribers() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You are not allowed to export newsletter subscribers.', 'super-ninety-four' ) );
	}
	check_admin_referer( 'snf_subscribers_export', 'snf_subscribers_export_nonce' );

	$subscribers = get_posts(
		array(
			'post_type'      => 'snf_subscriber',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$filename = 'snf-newsletter-subscribers-' . gmdate( 'Y-m-d' ) . '.csv';
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	$handle = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	fputcsv( $handle, array( 'Email', 'Phone', 'Source', 'User ID', 'Subscribed', 'Post ID' ) );
	foreach ( $subscribers as $subscriber ) {
		fputcsv(
			$handle,
			array(
				(string) get_post_meta( $subscriber->ID, '_snf_newsletter_email', true ),
				(string) get_post_meta( $subscriber->ID, '_snf_newsletter_phone', true ),
				(string) get_post_meta( $subscriber->ID, '_snf_newsletter_source', true ),
				(string) get_post_meta( $subscriber->ID, '_snf_newsletter_user_id', true ),
				(string) get_post_meta( $subscriber->ID, '_snf_newsletter_subscribed_at', true ),
				(int) $subscriber->ID,
			)
		);
	}
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_snf_subscribers_export', 'snf_newsletter_export_subscribers' );

/**
 * Admin list columns for the subscriber inbox.
 *
 * @param array<string,string> $columns Columns.
 * @return array<string,string>
 */
function snf_newsletter_subscriber_columns( $columns ) {
	$columns['title']       = __( 'Email', 'super-ninety-four' );
	$columns['email']       = __( 'Email', 'super-ninety-four' );
	$columns['phone']       = __( 'Phone', 'super-ninety-four' );
	$columns['subscribed']  = __( 'Subscribed', 'super-ninety-four' );
	$columns['source']      = __( 'Source', 'super-ninety-four' );
	unset( $columns['date'] );
	return $columns;
}
add_filter( 'manage_snf_subscriber_posts_columns', 'snf_newsletter_subscriber_columns' );

/**
 * Render subscriber list column content.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function snf_newsletter_subscriber_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'email':
			echo esc_html( (string) get_post_meta( $post_id, '_snf_newsletter_email', true ) );
			break;
		case 'phone':
			echo esc_html( (string) get_post_meta( $post_id, '_snf_newsletter_phone', true ) );
			break;
		case 'subscribed':
			echo esc_html( (string) get_post_meta( $post_id, '_snf_newsletter_subscribed_at', true ) );
			break;
		case 'source':
			echo esc_html( (string) get_post_meta( $post_id, '_snf_newsletter_source', true ) );
			break;
	}
}
add_action( 'manage_snf_subscriber_posts_custom_column', 'snf_newsletter_subscriber_column_content', 10, 2 );

/**
 * Redirect back to the Newsletter page with a status.
 *
 * @param string $status Status key.
 */
function snf_newsletter_redirect( $status ) {
	$status = sanitize_key( (string) $status );

	// Errors return to the home signup section; success lands on thank-you.
	if ( 'error' === $status ) {
		$url = add_query_arg( 'snf_newsletter', 'error', home_url( '/' ) ) . '#newsletter';
		wp_safe_redirect( $url );
		exit;
	}

	$query = array( 'status' => in_array( $status, array( 'duplicate', 'subscribed' ), true ) ? $status : 'subscribed' );

	if ( function_exists( 'snf_nuxt_app_url' ) && function_exists( 'snf_nuxt_bridge_enabled' ) && snf_nuxt_bridge_enabled() ) {
		$base = snf_nuxt_app_url();
		if ( $base ) {
			wp_safe_redirect( add_query_arg( $query, trailingslashit( $base ) . 'thank-you/newsletter' ) );
			exit;
		}
	}

	$wp_thanks = function_exists( 'snf_get_page_url' )
		? snf_get_page_url( 'thank-you-newsletter' )
		: home_url( '/thank-you-newsletter/' );
	wp_safe_redirect( add_query_arg( $query, $wp_thanks ) );
	exit;
}

/**
 * Handle a Newsletter subscribe submission.
 */
function snf_newsletter_handle_subscribe() {
	check_admin_referer( 'snf_newsletter_subscribe', 'snf_newsletter_nonce' );

	// Honeypot: silently accept bots so they do not keep retrying.
	if ( ! empty( $_POST['snf_newsletter_website'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		snf_newsletter_redirect( 'subscribed' );
	}

	$email = isset( $_POST['snf_newsletter_email'] ) ? sanitize_email( wp_unslash( $_POST['snf_newsletter_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$phone = isset( $_POST['snf_newsletter_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_newsletter_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( ! is_email( $email ) || '' === $phone ) {
		snf_newsletter_redirect( 'error' );
	}

	$duplicate = get_posts(
		array(
			'post_type'      => 'snf_subscriber',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_snf_newsletter_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( $duplicate ) {
		snf_newsletter_redirect( 'duplicate' );
	}

	$subscriber_id = wp_insert_post(
		array(
			'post_type'    => 'snf_subscriber',
			'post_status'  => 'publish',
			'post_title'   => $email,
			'post_content' => '',
		),
		true
	);

	if ( is_wp_error( $subscriber_id ) ) {
		snf_newsletter_redirect( 'error' );
	}

	update_post_meta( $subscriber_id, '_snf_newsletter_email', $email );
	update_post_meta( $subscriber_id, '_snf_newsletter_phone', $phone );
	update_post_meta( $subscriber_id, '_snf_newsletter_source', 'newsletter-page' );
	update_post_meta( $subscriber_id, '_snf_newsletter_user_id', get_current_user_id() );
	update_post_meta( $subscriber_id, '_snf_newsletter_subscribed_at', current_time( 'mysql' ) );

	/**
	 * Hook for SureContact / CRM sync after a successful subscribe.
	 *
	 * @param int    $subscriber_id Subscriber post ID.
	 * @param string $email         Email.
	 * @param string $phone         Phone.
	 */
	do_action( 'snf_newsletter_subscribed', (int) $subscriber_id, $email, $phone );

	$to = apply_filters( 'snf_newsletter_notify_email', get_option( 'admin_email' ) );
	if ( is_string( $to ) && is_email( $to ) ) {
		$message = sprintf(
			/* translators: 1: email, 2: phone, 3: date, 4: admin URL */
			__( "New SUPER NINETY FOUR video newsletter subscriber\n\nEmail: %1\$s\nPhone: %2\$s\nDate: %3\$s\n\nView subscribers: %4\$s", 'super-ninety-four' ),
			$email,
			$phone,
			current_time( 'mysql' ),
			admin_url( 'edit.php?post_type=snf_subscriber' )
		);
		wp_mail(
			$to,
			sprintf( /* translators: %s: subscriber email */ __( 'New video newsletter subscriber: %s', 'super-ninety-four' ), $email ),
			$message,
			array( 'Content-Type: text/plain; charset=UTF-8' )
		);
	}

	snf_newsletter_redirect( 'subscribed' );
}
add_action( 'admin_post_snf_newsletter_subscribe', 'snf_newsletter_handle_subscribe' );
add_action( 'admin_post_nopriv_snf_newsletter_subscribe', 'snf_newsletter_handle_subscribe' );

/**
 * Keep the video library fresh from the YouTube channel feed.
 */
function snf_youtube_schedule_sync() {
	if ( ! wp_next_scheduled( 'snf_youtube_video_sync' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'twicedaily', 'snf_youtube_video_sync' );
	}
}
add_action( 'init', 'snf_youtube_schedule_sync' );

/**
 * Cron callback for the YouTube RSS sync.
 */
function snf_youtube_run_sync() {
	$path = get_template_directory() . '/bin/snf-sync-youtube-videos.php';
	if ( is_readable( $path ) ) {
		require_once $path;
	}
}
add_action( 'snf_youtube_video_sync', 'snf_youtube_run_sync' );
