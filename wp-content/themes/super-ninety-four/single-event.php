<?php
/**
 * Single event — Figma Event #1 layout for all event posts.
 */
get_header();

while ( have_posts() ) :
	the_post();

	// Workshop keeps its dedicated landing with reserve form.
	if ( snf_is_lifestyle_concierge_workshop( get_the_ID() ) ) {
		$event = get_post();
		get_template_part( 'template-parts/workshop', 'lifestyle-concierge', array( 'event' => $event ) );
		continue;
	}

	$data = snf_get_event_single_data( get_post() );
	if ( ! $data ) {
		?>
		<main class="snf-page">
			<section class="snf-page__frame snf-container">
				<div class="snf-canvas snf-canvas--message">
					<h1 class="snf-archive__title"><?php the_title(); ?></h1>
					<p class="snf-archive__meta"><?php esc_html_e( 'Event details are not available yet.', 'super-ninety-four' ); ?></p>
				</div>
			</section>
		</main>
		<?php
		continue;
	}

	get_template_part( 'template-parts/event', 'single', array( 'event' => $data ) );
endwhile;

get_footer();
