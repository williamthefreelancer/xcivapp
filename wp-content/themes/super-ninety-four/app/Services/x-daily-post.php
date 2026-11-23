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
 * Append an entry to the recent X post run log (newest first, capped at 20).
 *
 * @param array<string,mixed> $entry Log entry: time, ok, text, error, code.
 */
function snf_x_daily_log_push( $entry ) {
	$log = get_option( 'snf_x_daily_log', array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	array_unshift( $log, $entry );
	update_option( 'snf_x_daily_log', array_slice( $log, 0, 20 ), false );
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
		$error = 'X_API_BEARER_TOKEN / SNF_X_BEARER_TOKEN is not configured.';
		update_option(
			'snf_x_daily_status',
			array(
				'error' => $error,
				'time'  => current_time( 'mysql' ),
			),
			false
		);
		snf_x_daily_log_push( array( 'time' => current_time( 'mysql' ), 'ok' => false, 'text' => '', 'error' => $error ) );
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
		$error = $response->get_error_message();
		update_option( 'snf_x_daily_status', array( 'error' => $error, 'time' => current_time( 'mysql' ) ), false );
		snf_x_daily_log_push( array( 'time' => current_time( 'mysql' ), 'ok' => false, 'text' => $text, 'error' => $error ) );
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		$body  = wp_remote_retrieve_body( $response );
		$error = 'HTTP ' . $code . ': ' . substr( $body, 0, 300 );
		update_option(
			'snf_x_daily_status',
			array(
				'error' => $error,
				'time'  => current_time( 'mysql' ),
			),
			false
		);
		snf_x_daily_log_push( array( 'time' => current_time( 'mysql' ), 'ok' => false, 'text' => $text, 'error' => $error, 'code' => $code ) );
		return new WP_Error( 'snf_x_daily_api', __( 'X API rejected the post.', 'super-ninety-four' ), array( 'status' => $code ) );
	}

	update_option(
		'snf_x_daily_status',
		array(
			'hash'  => $hash,
			'text'  => $text,
			'time'  => current_time( 'mysql' ),
			'error' => '',
		),
		false
	);
	snf_x_daily_log_push( array( 'time' => current_time( 'mysql' ), 'ok' => true, 'text' => $text, 'error' => '', 'code' => $code ) );
	return true;
}
add_action( 'snf_x_daily_post', 'snf_x_daily_publish' );

/**
 * Render the X Daily Post dashboard.
 *
 * Registered as an SNF Club desk in admin-dashboard.php so it shares the branded
 * hero, nav, stat cards, and panels used by the other Club dashboards.
 */
function snf_x_daily_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Redirect-after-post keeps results inside the shared shell notices.
	if ( isset( $_POST['snf_x_daily_post_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		check_admin_referer( 'snf_x_daily_post_now', 'snf_x_daily_nonce' );
		$result = snf_x_daily_publish( true );
		if ( is_wp_error( $result ) ) {
			set_transient( 'snf_x_daily_post_error_' . get_current_user_id(), $result->get_error_message(), 5 * MINUTE_IN_SECONDS );
			wp_safe_redirect( admin_url( 'admin.php?page=snf-club-x-daily&snf_x_error=1' ) );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=snf-club-x-daily&snf_x_posted=1' ) );
		}
		exit;
	}

	snf_admin_render_shell_start(
		'snf-club-x-daily',
		__( 'X Daily Post', 'super-ninety-four' ),
		__( 'The daily X (Twitter) post that shares the newest library video — token health, composed copy, and publish history.', 'super-ninety-four' )
	);

	$token  = snf_x_daily_token();
	$status = get_option( 'snf_x_daily_status', array() );
	$status = is_array( $status ) ? $status : array();
	$log    = get_option( 'snf_x_daily_log', array() );
	$log    = is_array( $log ) ? $log : array();
	$next   = wp_next_scheduled( 'snf_x_daily_post' );
	$error  = isset( $status['error'] ) ? (string) $status['error'] : '';
	$last   = isset( $status['time'] ) ? (string) $status['time'] : '';
	$text   = snf_x_daily_compose_text();

	$newest = get_posts(
		array(
			'post_type'      => 'snf_video',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	$video_count = function_exists( 'snf_admin_count_posts' ) ? snf_admin_count_posts( 'snf_video', 'publish' ) : count( $newest );
	$length      = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo $token ? esc_html__( 'SET', 'super-ninety-four' ) : esc_html__( 'MISSING', 'super-ninety-four' ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'API token', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( $last ?: __( 'Never', 'super-ninety-four' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Last post', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( $next ? gmdate( 'Y-m-d H:i', $next ) . ' UTC' : __( 'Not scheduled', 'super-ninety-four' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Next scheduled run', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo $error ? esc_html__( 'FAILED', 'super-ninety-four' ) : ( $last ? esc_html__( 'OK', 'super-ninety-four' ) : esc_html__( '—', 'super-ninety-four' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Last result', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) $video_count ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Videos in library', 'super-ninety-four' ); ?></span>
		</div>
	</div>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Publish control', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Automation runs daily; publish manually to override it.', 'super-ninety-four' ); ?></p>
			<ul class="snf-admin__list">
				<li>
					<span><?php esc_html_e( 'Bearer token', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo $token ? 'snf-admin__pill--ok' : 'snf-admin__pill--bad'; ?>"><?php echo $token ? esc_html__( 'Configured', 'super-ninety-four' ) : esc_html__( 'Missing', 'super-ninety-four' ); ?></span>
				</li>
				<li>
					<span><?php esc_html_e( 'Endpoint', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill snf-admin__code">POST /2/tweets</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Schedule', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill"><?php echo $next ? esc_html__( 'Daily · active', 'super-ninety-four' ) : esc_html__( 'Not scheduled', 'super-ninety-four' ); ?></span>
				</li>
				<li>
					<span><?php esc_html_e( 'Last error', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo $error ? 'snf-admin__pill--bad' : 'snf-admin__pill--ok'; ?>"><?php echo $error ? esc_html__( 'See history', 'super-ninety-four' ) : esc_html__( 'None', 'super-ninety-four' ); ?></span>
				</li>
			</ul>
			<form class="snf-admin__actions" method="post">
				<?php wp_nonce_field( 'snf_x_daily_post_now', 'snf_x_daily_nonce' ); ?>
				<button type="submit" class="snf-admin__btn" name="snf_x_daily_post_now" value="1" <?php disabled( ! $token ); ?>><?php esc_html_e( 'Post now', 'super-ninety-four' ); ?></button>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_video' ) ); ?>"><?php esc_html_e( 'Video library', 'super-ninety-four' ); ?></a>
			</form>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Next post preview', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Composed from the newest published video. Customise with the snf_x_daily_post_text filter.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__notice">
				<p><?php echo esc_html( $text ); ?></p>
			</div>
			<ul class="snf-admin__list">
				<li>
					<span><?php esc_html_e( 'Length', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo $length > 280 ? 'snf-admin__pill--bad' : 'snf-admin__pill--ok'; ?>"><?php echo esc_html( sprintf( '%d / 280', $length ) ); ?></span>
				</li>
				<li>
					<span><?php esc_html_e( 'Source video', 'super-ninety-four' ); ?></span>
					<?php if ( $newest ) : ?>
						<a href="<?php echo esc_url( get_edit_post_link( $newest[0]->ID ) ); ?>"><?php echo esc_html( get_the_title( $newest[0] ) ); ?></a>
					<?php else : ?>
						<span class="snf-admin__pill snf-admin__pill--warn"><?php esc_html_e( 'Newsletter link fallback', 'super-ninety-four' ); ?></span>
					<?php endif; ?>
				</li>
				<li>
					<span><?php esc_html_e( 'Last text posted', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__muted"><?php echo esc_html( isset( $status['text'] ) ? (string) $status['text'] : '—' ); ?></span>
				</li>
			</ul>
		</section>

		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Recent runs', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Last 20 automated or manual publish attempts, newest first.', 'super-ninety-four' ); ?></p>
			<table class="snf-admin__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Result', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Text', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Detail', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $log ) : ?>
						<tr><td colspan="4" class="snf-admin__muted"><?php esc_html_e( 'No runs recorded yet. The next scheduled post (or Post now) will appear here.', 'super-ninety-four' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $log as $entry ) : ?>
						<?php
						$entry     = is_array( $entry ) ? $entry : array();
						$run_ok    = ! empty( $entry['ok'] );
						$run_time  = isset( $entry['time'] ) ? (string) $entry['time'] : '—';
						$run_text  = isset( $entry['text'] ) ? (string) $entry['text'] : '';
						$run_error = isset( $entry['error'] ) ? (string) $entry['error'] : '';
						$run_code  = isset( $entry['code'] ) ? (string) $entry['code'] : '';
						?>
						<tr>
							<td><?php echo esc_html( $run_time ); ?></td>
							<td><span class="snf-admin__pill <?php echo $run_ok ? 'snf-admin__pill--ok' : 'snf-admin__pill--bad'; ?>"><?php echo $run_ok ? esc_html__( 'Posted', 'super-ninety-four' ) : esc_html__( 'Failed', 'super-ninety-four' ); ?></span></td>
							<td><?php echo $run_text ? esc_html( $run_text ) : '—'; ?></td>
							<td><?php echo $run_error ? esc_html( $run_code ? 'HTTP ' . $run_code . ': ' . $run_error : $run_error ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Setup reference', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'The integration is code-driven; keep credentials out of git.', 'super-ninety-four' ); ?></p>
			<ul class="snf-admin__list">
				<li>
					<span><?php esc_html_e( 'Bearer token', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill snf-admin__code">X_API_BEARER_TOKEN / SNF_X_BEARER_TOKEN</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Cron hook', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill snf-admin__code">snf_x_daily_post (daily)</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Copy filter', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill snf-admin__code">snf_x_daily_post_text</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Duplicate protection', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill snf-admin__pill--ok"><?php esc_html_e( 'Skips identical copy unless Post now is used', 'super-ninety-four' ); ?></span>
				</li>
			</ul>
		</section>
	</div>
	<?php
	snf_admin_render_shell_end();
}
