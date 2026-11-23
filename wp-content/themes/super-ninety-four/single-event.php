<?php
/**
 * Single event — Figma Event #1 landing (no site header).
 *
 * @package Super_Ninety_Four
 */

$event = function_exists( 'snf_get_event_single_data' ) ? snf_get_event_single_data( get_queried_object() ) : null;

get_header( $event ? 'empty' : '' );

if ( $event ) {
	get_template_part(
		'template-parts/event',
		'single',
		array(
			'event' => $event,
		)
	);
} else {
	while ( have_posts() ) :
		the_post();
		?>
		<main class="snf-page snf-event-single" data-figma-ready="true">
			<section class="snf-page__frame snf-container" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
				<div class="snf-canvas snf-event-single__canvas">
					<?php the_content(); ?>
				</div>
			</section>
		</main>
		<?php
	endwhile;
}

get_footer( $event ? 'empty' : '' );
