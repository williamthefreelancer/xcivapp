<?php
/**
 * Videos & Audio hub — YouTube library, audio channels, upcoming live/hybrid.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings  = function_exists( 'snf_get_media_settings' ) ? snf_get_media_settings() : array();
$intro     = isset( $settings['hub_intro'] ) ? trim( (string) $settings['hub_intro'] ) : '';
$videos    = function_exists( 'snf_query_video_library' ) ? snf_query_video_library( array( 'posts_per_page' => 12 ) ) : array();
$audio     = function_exists( 'snf_query_audio_library' ) ? snf_query_audio_library( array( 'posts_per_page' => 12 ) ) : array();
$live      = function_exists( 'snf_query_upcoming_broadcasts' ) ? snf_query_upcoming_broadcasts( 6 ) : array();
$video_url = get_post_type_archive_link( 'snf_video' );
$audio_url = get_post_type_archive_link( 'snf_audio' );
?>
<section class="snf-media-hub" data-snf-media-hub aria-label="<?php esc_attr_e( 'Videos and audio library', 'super-ninety-four' ); ?>">
	<header class="snf-media-hub__hero">
		<p class="snf-media-hub__brand"><?php esc_html_e( 'SUPER NINETY FOUR', 'super-ninety-four' ); ?></p>
		<h1 class="snf-media-hub__title"><?php esc_html_e( 'Videos & Audio', 'super-ninety-four' ); ?></h1>
		<?php if ( $intro ) : ?>
			<p class="snf-media-hub__intro"><?php echo esc_html( $intro ); ?></p>
		<?php else : ?>
			<p class="snf-media-hub__intro">
				<?php esc_html_e( 'YouTube library, listen-along audio channels, and hybrid venue broadcasts for upcoming events.', 'super-ninety-four' ); ?>
			</p>
		<?php endif; ?>

		<nav class="snf-media-hub__tabs" aria-label="<?php esc_attr_e( 'Library sections', 'super-ninety-four' ); ?>">
			<a class="snf-media-hub__tab is-active" href="#snf-media-videos"><?php esc_html_e( 'Videos', 'super-ninety-four' ); ?></a>
			<a class="snf-media-hub__tab" href="#snf-media-audio"><?php esc_html_e( 'Audio', 'super-ninety-four' ); ?></a>
			<a class="snf-media-hub__tab" href="#snf-media-live"><?php esc_html_e( 'Live & Hybrid', 'super-ninety-four' ); ?></a>
		</nav>
	</header>

	<section id="snf-media-videos" class="snf-media-hub__section">
		<div class="snf-media-hub__section-head">
			<h2 class="snf-media-hub__section-title"><?php esc_html_e( 'YouTube library', 'super-ninety-four' ); ?></h2>
			<?php if ( $video_url ) : ?>
				<a class="snf-media-hub__more" href="<?php echo esc_url( $video_url ); ?>"><?php esc_html_e( 'All videos', 'super-ninety-four' ); ?></a>
			<?php endif; ?>
		</div>

		<?php if ( $videos ) : ?>
			<ul class="snf-media-grid snf-media-grid--video">
				<?php foreach ( $videos as $item ) : ?>
					<li class="snf-media-card">
						<a class="snf-media-card__link" href="<?php echo esc_url( $item['url'] ); ?>">
							<span class="snf-media-card__thumb">
								<?php if ( ! empty( $item['thumbnail'] ) ) : ?>
									<img src="<?php echo esc_url( $item['thumbnail'] ); ?>" alt="" width="640" height="360" loading="lazy" decoding="async">
								<?php endif; ?>
								<?php if ( ! empty( $item['duration'] ) ) : ?>
									<span class="snf-media-card__duration"><?php echo esc_html( (string) $item['duration'] ); ?></span>
								<?php endif; ?>
							</span>
							<span class="snf-media-card__body">
								<span class="snf-media-card__title"><?php echo esc_html( (string) $item['title'] ); ?></span>
								<?php if ( ! empty( $item['series'] ) ) : ?>
									<span class="snf-media-card__meta"><?php echo esc_html( implode( ' · ', $item['series'] ) ); ?></span>
								<?php elseif ( ! empty( $item['topics'] ) ) : ?>
									<span class="snf-media-card__meta"><?php echo esc_html( implode( ' · ', $item['topics'] ) ); ?></span>
								<?php endif; ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="snf-media-hub__empty">
				<?php esc_html_e( 'No videos yet. Add items under WP Admin → Videos & Audio.', 'super-ninety-four' ); ?>
			</p>
		<?php endif; ?>
	</section>

	<section id="snf-media-audio" class="snf-media-hub__section">
		<div class="snf-media-hub__section-head">
			<h2 class="snf-media-hub__section-title"><?php esc_html_e( 'Audio channels', 'super-ninety-four' ); ?></h2>
			<?php if ( $audio_url ) : ?>
				<a class="snf-media-hub__more" href="<?php echo esc_url( $audio_url ); ?>"><?php esc_html_e( 'All audio', 'super-ninety-four' ); ?></a>
			<?php endif; ?>
		</div>

		<?php if ( $audio ) : ?>
			<ul class="snf-media-grid snf-media-grid--audio">
				<?php foreach ( $audio as $item ) : ?>
					<li class="snf-media-card snf-media-card--audio">
						<a class="snf-media-card__link" href="<?php echo esc_url( $item['url'] ); ?>">
							<span class="snf-media-card__thumb snf-media-card__thumb--square">
								<?php if ( ! empty( $item['thumbnail'] ) ) : ?>
									<img src="<?php echo esc_url( $item['thumbnail'] ); ?>" alt="" width="320" height="320" loading="lazy" decoding="async">
								<?php endif; ?>
								<?php if ( ! empty( $item['is_live'] ) ) : ?>
									<span class="snf-media-card__live"><?php esc_html_e( 'Live', 'super-ninety-four' ); ?></span>
								<?php endif; ?>
							</span>
							<span class="snf-media-card__body">
								<span class="snf-media-card__title"><?php echo esc_html( (string) $item['title'] ); ?></span>
								<?php if ( ! empty( $item['channels'] ) ) : ?>
									<span class="snf-media-card__meta"><?php echo esc_html( implode( ' · ', $item['channels'] ) ); ?></span>
								<?php endif; ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="snf-media-hub__empty">
				<?php esc_html_e( 'No audio channels yet. Add episodes under Videos & Audio → Audio.', 'super-ninety-four' ); ?>
			</p>
		<?php endif; ?>
	</section>

	<section id="snf-media-live" class="snf-media-hub__section">
		<div class="snf-media-hub__section-head">
			<h2 class="snf-media-hub__section-title"><?php esc_html_e( 'Live & hybrid events', 'super-ninety-four' ); ?></h2>
		</div>

		<?php if ( $live ) : ?>
			<ul class="snf-media-grid snf-media-grid--live">
				<?php foreach ( $live as $row ) : ?>
					<?php
					$broadcast = isset( $row['broadcast'] ) && is_array( $row['broadcast'] ) ? $row['broadcast'] : array();
					?>
					<li class="snf-media-card snf-media-card--live">
						<a class="snf-media-card__link" href="<?php echo esc_url( (string) $row['url'] ); ?>">
							<span class="snf-media-card__thumb">
								<?php if ( ! empty( $row['image'] ) ) : ?>
									<img src="<?php echo esc_url( (string) $row['image'] ); ?>" alt="" width="640" height="360" loading="lazy" decoding="async">
								<?php endif; ?>
								<?php if ( ! empty( $broadcast['is_live'] ) ) : ?>
									<span class="snf-media-card__live"><?php esc_html_e( 'On air', 'super-ninety-four' ); ?></span>
								<?php endif; ?>
							</span>
							<span class="snf-media-card__body">
								<span class="snf-media-card__title"><?php echo esc_html( (string) $row['title'] ); ?></span>
								<span class="snf-media-card__meta">
									<?php
									echo esc_html(
										trim(
											implode(
												' · ',
												array_filter(
													array(
														(string) ( $broadcast['mode_label'] ?? '' ),
														(string) ( $row['when'] ?? '' ),
														(string) ( $row['location'] ?? '' ),
													)
												)
											)
										)
									);
									?>
								</span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="snf-media-hub__empty">
				<?php esc_html_e( 'Mark an event product as Virtual, Hybrid, or Audio broadcast to list it here.', 'super-ninety-four' ); ?>
			</p>
		<?php endif; ?>
	</section>
</section>
