<?php
/**
 * Singular fallback for posts/pages without a more specific template.
 */
get_header();
?>
<main class="snf-page">
	<section class="snf-page__frame snf-container" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'snf-canvas' ); ?>>
				<h1 class="snf-archive__title"><?php the_title(); ?></h1>
				<div class="snf-entry">
					<?php the_content(); ?>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</section>
</main>
<?php
get_footer();
