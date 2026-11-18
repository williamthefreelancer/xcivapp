<?php
/**
 * Template Name: Videos & Audio
 * Description: YouTube library, audio channels, and live/hybrid event hub.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="snf-page snf-page--media" data-figma-ready="true" data-page-slug="videos-audio" aria-label="<?php esc_attr_e( 'Videos and Audio', 'super-ninety-four' ); ?>">
	<div class="snf-page__canvas snf-container">
		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				$content = trim( (string) get_the_content() );
				if ( '' !== $content ) {
					echo '<div class="snf-page__plugin-content snf-page__plugin-content--videos-audio">';
					the_content();
					echo '</div>';
				}
			}
			rewind_posts();
		}

		get_template_part( 'template-parts/media/library', 'hub' );
		?>
	</div>
</main>
<?php
get_footer();
