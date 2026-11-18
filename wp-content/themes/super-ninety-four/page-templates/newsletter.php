<?php
/**
 * FSE php-view: Video Newsletter landing — signup + latest videos.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status = isset( $_GET['snf_newsletter'] ) ? sanitize_key( wp_unslash( $_GET['snf_newsletter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$videos = function_exists( 'snf_query_video_library' )
	? snf_query_video_library( array( 'posts_per_page' => 6 ) )
	: array();
$videos = array_values(
	array_filter(
		$videos,
		static function ( $item ) {
			return ! empty( $item['can_view'] );
		}
	)
);

$all_videos_url = function_exists( 'snf_get_page_url' )
	? snf_get_page_url( 'videos-audio' )
	: home_url( '/videos-audio/' );

$youtube_url = function_exists( 'snf_newsletter_youtube_url' )
	? snf_newsletter_youtube_url()
	: 'https://www.youtube.com/@williamslist';

get_header();
?>
<main class="snf-page snf-newsletter" data-page-slug="newsletter">
	<section class="snf-newsletter__hero snf-container" aria-labelledby="snf-newsletter-title">
		<div class="snf-newsletter__intro">
			<p class="snf-newsletter__eyebrow"><?php esc_html_e( 'The video newsletter', 'super-ninety-four' ); ?></p>
			<h1 id="snf-newsletter-title" class="snf-newsletter__title">
				<?php esc_html_e( 'The SUPER NINETY FOUR Dispatch', 'super-ninety-four' ); ?>
			</h1>
			<p class="snf-newsletter__lede">
				<?php esc_html_e( 'New films, member dispatches, and Expo highlights — the best of the channel, delivered to your inbox.', 'super-ninety-four' ); ?>
			</p>

			<ul class="snf-newsletter__benefits">
				<li><?php esc_html_e( 'Fresh videos before they hit the main library', 'super-ninety-four' ); ?></li>
				<li><?php esc_html_e( 'Event and reciprocal-club updates', 'super-ninety-four' ); ?></li>
				<li><?php esc_html_e( 'No noise — just the highlights', 'super-ninety-four' ); ?></li>
			</ul>

			<?php if ( $youtube_url ) : ?>
				<a class="snf-newsletter__youtube" href="<?php echo esc_url( $youtube_url ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="snf-newsletter__youtube-icon" aria-hidden="true">▶</span>
					<?php esc_html_e( 'Watch on YouTube', 'super-ninety-four' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="snf-newsletter__signup">
			<h2 class="snf-newsletter__signup-title"><?php esc_html_e( 'Subscribe', 'super-ninety-four' ); ?></h2>
			<p class="snf-newsletter__signup-lede"><?php esc_html_e( 'Join the list and never miss an upload.', 'super-ninety-four' ); ?></p>

			<?php if ( 'subscribed' === $status ) : ?>
				<p class="snf-newsletter__flash snf-newsletter__flash--ok" role="status">
					<?php esc_html_e( 'You’re on the list. Welcome to the dispatch.', 'super-ninety-four' ); ?>
				</p>
			<?php elseif ( 'duplicate' === $status ) : ?>
				<p class="snf-newsletter__flash snf-newsletter__flash--ok" role="status">
					<?php esc_html_e( 'That email is already subscribed.', 'super-ninety-four' ); ?>
				</p>
			<?php elseif ( 'error' === $status ) : ?>
				<p class="snf-newsletter__flash snf-newsletter__flash--err" role="alert">
					<?php esc_html_e( 'Please check your email address and try again.', 'super-ninety-four' ); ?>
				</p>
			<?php endif; ?>

			<form class="snf-newsletter__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_newsletter_subscribe">
				<?php wp_nonce_field( 'snf_newsletter_subscribe', 'snf_newsletter_nonce' ); ?>

				<p class="snf-newsletter__honeypot" aria-hidden="true">
					<label><?php esc_html_e( 'Website', 'super-ninety-four' ); ?>
						<input type="text" name="snf_newsletter_website" tabindex="-1" autocomplete="off">
					</label>
				</p>

				<label class="snf-newsletter__field">
					<span><?php esc_html_e( 'First name (optional)', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_newsletter_name" autocomplete="given-name">
				</label>
				<label class="snf-newsletter__field">
					<span><?php esc_html_e( 'Email address', 'super-ninety-four' ); ?></span>
					<input type="email" name="snf_newsletter_email" autocomplete="email" required>
				</label>
				<button class="snf-hub__action snf-hub__action--primary snf-newsletter__submit" type="submit">
					<?php esc_html_e( 'Subscribe to the dispatch', 'super-ninety-four' ); ?>
				</button>
				<p class="snf-newsletter__privacy">
					<?php esc_html_e( 'We only use your address for the newsletter. Unsubscribe any time.', 'super-ninety-four' ); ?>
				</p>
			</form>
		</div>
	</section>

	<section class="snf-newsletter__videos snf-container" aria-labelledby="snf-newsletter-videos-title">
		<div class="snf-newsletter__section-head">
			<div>
				<p class="snf-newsletter__eyebrow"><?php esc_html_e( 'From the channel', 'super-ninety-four' ); ?></p>
				<h2 id="snf-newsletter-videos-title" class="snf-newsletter__section-title"><?php esc_html_e( 'Latest videos', 'super-ninety-four' ); ?></h2>
			</div>
			<?php if ( $videos ) : ?>
				<a class="snf-hub__action" href="<?php echo esc_url( $all_videos_url ); ?>">
					<?php esc_html_e( 'Browse all videos', 'super-ninety-four' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $videos ) : ?>
			<ul class="snf-media-grid">
				<?php foreach ( $videos as $item ) : ?>
					<li class="snf-media-card">
						<a class="snf-media-card__link" href="<?php echo esc_url( $item['url'] ); ?>">
							<span class="snf-media-card__thumb">
								<?php if ( ! empty( $item['thumbnail'] ) ) : ?>
									<img src="<?php echo esc_url( $item['thumbnail'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" width="640" height="360" loading="lazy" decoding="async">
								<?php endif; ?>
								<?php if ( ! empty( $item['duration'] ) ) : ?>
									<span class="snf-media-card__duration"><?php echo esc_html( $item['duration'] ); ?></span>
								<?php endif; ?>
							</span>
							<span class="snf-media-card__body">
								<span class="snf-media-card__title"><?php echo esc_html( $item['title'] ); ?></span>
								<?php if ( ! empty( $item['series'][0] ) ) : ?>
									<span class="snf-media-card__meta"><?php echo esc_html( $item['series'][0] ); ?></span>
								<?php elseif ( ! empty( $item['topics'][0] ) ) : ?>
									<span class="snf-media-card__meta"><?php echo esc_html( $item['topics'][0] ); ?></span>
								<?php endif; ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<div class="snf-newsletter__empty">
				<p><?php esc_html_e( 'New videos are on the way. Until then, catch the full library on YouTube.', 'super-ninety-four' ); ?></p>
				<?php if ( $youtube_url ) : ?>
					<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( $youtube_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Watch on YouTube', 'super-ninety-four' ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
