<?php
/**
 * Template Name: Service Inquiry
 * Description: Figma Service Inquiries #1 landing for individual service pages.
 */

$slug    = get_post_field( 'post_name', get_queried_object_id() );
$service = snf_get_service_inquiry( $slug );

get_header();

if ( ! $service ) {
	?>
	<main class="snf-page">
		<section class="snf-page__frame snf-container">
			<div class="snf-canvas snf-canvas--message">
				<h1 class="snf-archive__title"><?php the_title(); ?></h1>
				<p class="snf-archive__meta"><?php esc_html_e( 'Service details are not available yet.', 'super-ninety-four' ); ?></p>
			</div>
		</section>
	</main>
	<?php
	get_footer();
	return;
}

get_template_part( 'template-parts/service', 'inquiry', array( 'service' => $service ) );
get_footer();
