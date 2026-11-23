<?php
/**
 * Single audio / channel episode.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$item = function_exists( 'snf_get_audio_item_data' ) ? snf_get_audio_item_data( get_the_ID() ) : null;
?>
<main class="snf-page snf-page--media-single snf-page--audio" aria-label="<?php the_title_attribute(); ?>">
	<div class="snf-container snf-media-single">
		<p class="snf-media-single__crumb">
			<a href="<?php echo esc_url( function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'videos-audio' ) : home_url( '/videos-audio/' ) ); ?>">
				<?php esc_html_e( 'Videos & Audio', 'super-ninety-four' ); ?>
			</a>
		</p>

		<?php if ( $item ) : ?>
			<?php
			get_template_part(
				'template-parts/media/audio',
				'player',
				array(
					'item' => $item,
				)
			);
			?>

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
