<?php
/**
 * Default page template.
 */
get_header();
?>
<main class="snf-page">
	<section class="snf-page__frame snf-container" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
		<div class="snf-canvas" data-figma-ready="true">
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			?>
		</div>
	</section>
</main>
<?php
get_footer();