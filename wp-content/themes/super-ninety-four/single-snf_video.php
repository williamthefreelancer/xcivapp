<?php
/**
 * Single YouTube library video.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$item = function_exists( 'snf_get_video_item_data' ) ? snf_get_video_item_data( get_the_ID() ) : null;
?>
<main class="snf-page snf-page--media-single snf-page--video" aria-label="<?php the_title_attribute(); ?>">
	<div class="snf-container snf-media-single">
		<p class="snf-media-single__crumb">
			<a href="<?php echo esc_url( function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'videos-audio' ) : home_url( '/videos-audio/' ) ); ?>">
				<?php esc_html_e( 'Videos & Audio', 'super-ninety-four' ); ?>
			</a>
		</p>

		<?php if ( $item ) : ?>
			<h1 class="snf-media-single__title"><?php echo esc_html( $item['title'] ); ?></h1>
			<?php if ( ! empty( $item['series'] ) || ! empty( $item['topics'] ) ) : ?>
				<p class="snf-media-single__meta">
					<?php
					echo esc_html(
						implode(
							' · ',
							array_merge(
								(array) ( $item['series'] ?? array() ),
								(array) ( $item['topics'] ?? array() )
							)
						)
					);
					?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $item['can_view'] ) ) : ?>
				<?php
				get_template_part(
					'template-parts/media/youtube',
					'embed',
					array(
						'src'    => (string) ( $item['embed_url'] ?: $item['youtube_id'] ),
						'title'  => (string) $item['title'],
						'poster' => (string) ( $item['thumbnail'] ?? '' ),
						'class'  => 'snf-media-single__player',
					)
				);
				?>
			<?php else : ?>
				<p class="snf-media-single__locked">
					<?php esc_html_e( 'This video is for members or ticket holders.', 'super-ninety-four' ); ?>
					<a href="<?php echo esc_url( function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'member-login' ) : wp_login_url() ); ?>">
						<?php esc_html_e( 'Sign in', 'super-ninety-four' ); ?>
					</a>
				</p>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<div class="snf-media-single__content">
						<?php the_content(); ?>
					</div>
				<?php endwhile; ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
