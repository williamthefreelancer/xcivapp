<?php
/**
 * Template Name: Blank Canvas
 * Description: Empty page ready for Figma design implementation.
 */
get_header();
?>
<main class="snf-page">
	<section class="snf-page__frame snf-container" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
		<div class="snf-canvas" data-figma-ready="true">
			<!-- Figma design placeholder -->
		</div>
	</section>
</main>
<?php
get_footer();
