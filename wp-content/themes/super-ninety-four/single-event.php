<?php
/**
 * Single event — blank canvas for per-event content.
 */
get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main class="snf-page snf-event-single" data-figma-ready="true">
		<section class="snf-page__frame snf-container" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
			<div class="snf-canvas snf-event-single__canvas">
				<?php
				// Dynamic per-event content from the editor (and future Figma builds).
				the_content();
				?>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();
