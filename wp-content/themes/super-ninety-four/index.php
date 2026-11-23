<?php
/**
 * Fallback template.
 */
get_header();
?>
<main class="snf-home">
	<section class="snf-hero" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
		<div class="snf-container snf-hero__container">
			<?php get_template_part( 'template-parts/search', 'events' ); ?>
		</div>
	</section>
</main>
<?php
get_footer();