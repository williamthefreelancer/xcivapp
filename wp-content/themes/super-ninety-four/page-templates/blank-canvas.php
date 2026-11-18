<?php
/**
 * FSE php-view: loaded by Site Editor templates via snf/php-view (not a classic page template).
 * Description: Public pages with header/footer; renders Gutenberg/plugin blocks when content exists.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug  = get_post_field( 'post_name', get_queried_object_id() );
$title = get_the_title();

get_header();
?>
<main
	class="snf-page snf-page--blank"
	data-figma-ready="true"
	data-page-slug="<?php echo esc_attr( $slug ); ?>"
	aria-label="<?php echo esc_attr( $title ); ?>"
>
	<div class="snf-page__canvas snf-container" data-figma-slot="<?php echo esc_attr( $slug ); ?>">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				$content = trim( (string) get_the_content() );
				if ( '' !== $content ) :
					?>
					<div class="snf-page__plugin-content snf-page__plugin-content--<?php echo esc_attr( $slug ); ?>">
						<?php the_content(); ?>
					</div>
				<?php else : ?>
					<span class="screen-reader-text"><?php echo esc_html( $title ); ?></span>
				<?php endif; ?>
			<?php endwhile; ?>
		<?php else : ?>
			<span class="screen-reader-text"><?php echo esc_html( $title ); ?></span>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
