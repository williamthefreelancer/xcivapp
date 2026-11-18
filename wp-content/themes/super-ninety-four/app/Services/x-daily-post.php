<?php
/**
 * Daily X (Twitter) post: shares the newest video from the library.
 *
 * Requires a write-capable X API v2 bearer token, supplied outside git via
 * X_API_BEARER_TOKEN (env) or SNF_X_BEARER_TOKEN (wp-config constant).
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve the X API bearer token.
 *
 * @return string
 */
function snf_x_daily_token() {
	$token = getenv( 'X_API_BEARER_TOKEN' );
	if ( ! $token && defined( 'SNF_X_BEARER_TOKEN' ) && SNF_X_BEARER_TOKEN ) {
		$token = SNF_X_BEARER_TOKEN;
	}
	if ( ! $token && defined( 'X_API_BEARER_TOKEN' ) && X_API_BEARER_TOKEN ) {
		$token = X_API_BEARER_TOKEN;
	}
	return is_string( $token ) ? trim( $token ) : '';
}

/**
 * Schedule the daily post if it is not already scheduled.
 */
function snf_x_daily_schedule() {
	if ( ! wp_next_scheduled( 'snf_x_daily_post' ) ) {
		wp_schedule_event( time() + 12 * HOUR_IN_SECONDS, 'daily', 'snf_x_daily_post' );
	}
}
add_action( 'init', 'snf_x_daily_schedule' );

/**
 * Build the post text from the newest library video.
 *
 * @return string
 */
function snf_x_daily_compose_text() {
	$posts = get_posts(
		array(
			'post_type'      => 'snf_video',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( ! $posts ) {
		$url = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'newsletter' ) : home_url( '/newsletter/' );
		$text = sprintf( __( 'New videos and event updates are on the way — join the SUPER NINETY FOUR dispatch: %s', 'super-ninety-four' ), $url );
		$post = null;
	} else {
		$post  = $posts[0];
		$title = get_the_title( $post );
		$url   = get_permalink( $post );
		$text  = sprintf( __( 'New on the channel: %s — %s', 'super-ninety-four' ), $title, $url );
	}

	if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > 280 ) {
		$text = mb_substr( $text, 0, 250 ) . '… ' . $url;
	}

	/**
	 * Filter the daily X post text.
	 *
	 * @param string       $text Post text.
	 * @param WP_Post|null $post Source video, if any.
	 */
	return (string) apply_filters( 'snf_x_daily_post_text', $text, $post );
}

/**
 * Publish the daily X post.
 *
 * @param bool $force Post even if the text is unchanged.
 * @return bool|WP_Error
 */
function snf_x_daily_publish( $force = false ) {
	$token = snf_x_daily_token();
	if ( ! $token ) {
		update_option(
			'snf_x_daily_status',
			array(
				'error' => 'X_API_BEARER_TOKEN / SNF_X_BEARER_TOKEN is not configured.',
				'time'  => current_time( 'mysql' ),
			),
			false
		);
		return new WP_Error( 'snf_x_daily_no_token', __( 'X API bearer token is not configured.', 'super-ninety-four' ) );
	}

	$text = snf_x_daily_compose_text();
	$hash = hash( 'sha256', $text );
	$status = get_option( 'snf_x_daily_status', array() );
	if ( ! $force && is_array( $status ) && ! empty( $status['hash'] ) && $status['hash'] === $hash ) {
		return false; // Already posted this exact update.
	}

	$response = wp_remote_post(
		'https://api.x.com/2/tweets',
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'    => wp_json_encode( array( 'text' => $text ) ),
		)
	);

	if ( is_wp_error( $response ) ) {
		update_option( 'snf_x_daily_status', array( 'error' => $response->get_error_message(), 'time' => current_time( 'mysql' ) ), false );
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		$body = wp_remote_retrieve_body( $response );
		update_option(
			'snf_x_daily_status',
			array(
				'error' => 'HTTP ' . $code . ': ' . substr( $body, 0, 300 ),
				'time'  => current_time( 'mysql' ),
			),
			false
		);
		return new WP_Error( 'snf_x_daily_api', __( 'X API rejected the post.', 'super-ninety-four' ), array( 'status' => $code ) );
	}

	update_option(
		'snf_x_daily_status',
		array(
			'hash' => $hash,
			'text' => $text,
			'time' => current_time( 'mysql' ),
			'error' => '',
		),
		false
	);
	return true;
}
add_action( 'snf_x_daily_post', 'snf_x_daily_publish' );

/**
 * Register the X status page under SNF Club.
 */
function snf_x_daily_admin_menu() {
	add_submenu_page(
		'snf-club',
		__( 'X Daily Post', 'super-ninety-four' ),
		__( 'X Daily Post', 'super-ninety-four' ),
		'manage_options',
		'snf-club-x-daily',
		'snf_x_daily_admin_page'
	);
}
add_action( 'admin_menu', 'snf_x_daily_admin_menu' );

/**
 * Render the X status page.
 */
function snf_x_daily_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = '';
	if ( isset( $_POST['snf_x_daily_post_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		check_admin_referer( 'snf_x_daily_post_now', 'snf_x_daily_nonce' );
		$result = snf_x_daily_publish( true );
		$notice = is_wp_error( $result )
			? sprintf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $result->get_error_message() ) )
			: '<div class="notice notice-success"><p>' . esc_html__( 'Posted to X.', 'super-ninety-four' ) . '</p></div>';
	}

	$status = get_option( 'snf_x_daily_status', array() );
	$status = is_array( $status ) ? $status : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'X Daily Post', 'super-ninety-four' ); ?></h1>
		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p><?php esc_html_e( 'Shares the newest video from the library on X once a day.', 'super-ninety-four' ); ?></p>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'API token', 'super-ninety-four' ); ?></th>
					<td><?php echo snf_x_daily_token() ? esc_html__( 'Configured', 'super-ninety-four' ) : esc_html__( 'Missing — set X_API_BEARER_TOKEN or SNF_X_BEARER_TOKEN', 'super-ninety-four' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last post', 'super-ninety-four' ); ?></th>
					<td><?php echo esc_html( isset( $status['time'] ) ? $status['time'] : __( 'Never', 'super-ninety-four' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last text', 'super-ninety-four' ); ?></th>
					<td><?php echo esc_html( isset( $status['text'] ) ? $status['text'] : '—' ); ?></td>
				</tr>
				<?php if ( ! empty( $status['error'] ) ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Last error', 'super-ninety-four' ); ?></th>
						<td><?php echo esc_html( $status['error'] ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<form method="post">
			<?php wp_nonce_field( 'snf_x_daily_post_now', 'snf_x_daily_nonce' ); ?>
			<?php submit_button( __( 'Post now', 'super-ninety-four' ), 'primary', 'snf_x_daily_post_now' ); ?>
		</form>
		<p class="description">
			<?php esc_html_e( 'Next scheduled run: ', 'super-ninety-four' ); ?>
			<?php
			$next = wp_next_scheduled( 'snf_x_daily_post' );
			echo esc_html( $next ? gmdate( 'Y-m-d H:i:s', $next ) . ' UTC' : __( 'not scheduled', 'super-ninety-four' ) );
			?>
		</p>
	</div>
	<?php
}
