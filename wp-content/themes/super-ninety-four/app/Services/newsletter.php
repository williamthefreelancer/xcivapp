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
			'show_in_menu'        => 'snf-club',
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
 * Admin list columns for the subscriber inbox.
 *
 * @param array<string,string> $columns Columns.
 * @return array<string,string>
 */
function snf_newsletter_subscriber_columns( $columns ) {
	$columns['title']       = __( 'Name / email', 'super-ninety-four' );
	$columns['email']       = __( 'Email', 'super-ninety-four' );
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
	$url = function_exists( 'snf_get_page_url' )
		? snf_get_page_url( 'newsletter' )
		: home_url( '/newsletter/' );
	wp_safe_redirect( add_query_arg( 'snf_newsletter', $status, $url ) );
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
	$name  = isset( $_POST['snf_newsletter_name'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_newsletter_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( ! is_email( $email ) ) {
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
			'post_title'   => $name ? $name : $email,
			'post_content' => '',
		),
		true
	);

	if ( is_wp_error( $subscriber_id ) ) {
		snf_newsletter_redirect( 'error' );
	}

	update_post_meta( $subscriber_id, '_snf_newsletter_email', $email );
	update_post_meta( $subscriber_id, '_snf_newsletter_name', $name );
	update_post_meta( $subscriber_id, '_snf_newsletter_source', 'newsletter-page' );
	update_post_meta( $subscriber_id, '_snf_newsletter_user_id', get_current_user_id() );
	update_post_meta( $subscriber_id, '_snf_newsletter_subscribed_at', current_time( 'mysql' ) );

	$to = apply_filters( 'snf_newsletter_notify_email', get_option( 'admin_email' ) );
	if ( is_string( $to ) && is_email( $to ) ) {
		$message = sprintf(
			/* translators: 1: name, 2: email, 3: date */
			__( "New SUPER NINETY FOUR video newsletter subscriber\n\nName: %1\$s\nEmail: %2\$s\nDate: %3\$s\n\nView subscribers: %4\$s", 'super-ninety-four' ),
			$name ? $name : __( '(not provided)', 'super-ninety-four' ),
			$email,
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
