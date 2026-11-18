<?php
/**
 * Audio library archive.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$items = function_exists( 'snf_query_audio_library' ) ? snf_query_audio_library( array( 'posts_per_page' => 48 ) ) : array();
?>
<main class="snf-page snf-page--media-archive" aria-label="<?php esc_attr_e( 'Audio library', 'super-ninety-four' ); ?>">
	<div class="snf-container snf-media-hub">
		<header class="snf-media-hub__hero">
			<p class="snf-media-hub__brand"><?php esc_html_e( 'SUPER NINETY FOUR', 'super-ninety-four' ); ?></p>
			<h1 class="snf-media-hub__title"><?php esc_html_e( 'Audio library', 'super-ninety-four' ); ?></h1>
			<p class="snf-media-hub__intro">
				<a href="<?php echo esc_url( function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'videos-audio' ) : home_url( '/videos-audio/' ) ); ?>">
					<?php esc_html_e( '← Videos & Audio hub', 'super-ninety-four' ); ?>
				</a>
			</p>
		</header>

		<?php if ( $items ) : ?>
			<ul class="snf-media-grid snf-media-grid--audio">
				<?php foreach ( $items as $item ) : ?>
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
			<p class="snf-media-hub__empty"><?php esc_html_e( 'No audio published yet.', 'super-ninety-four' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
