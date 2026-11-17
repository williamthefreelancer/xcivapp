<?php
/**
 * Template Name: Reciprocal Club
 * Description: Figma inquiries-single-page reciprocal club landing.
 */

$slug = get_post_field( 'post_name', get_queried_object_id() );
$club = snf_get_reciprocal_club( $slug );

get_header( 'empty' );

if ( ! $club ) {
	?>
	<main class="snf-page">
		<section class="snf-page__frame snf-container">
			<div class="snf-canvas snf-canvas--message">
				<h1 class="snf-archive__title"><?php the_title(); ?></h1>
				<p class="snf-archive__meta"><?php esc_html_e( 'Reciprocal club details are not available yet.', 'super-ninety-four' ); ?></p>
			</div>
		</section>
	</main>
	<?php
	get_footer( 'empty' );
	return;
}

get_template_part( 'template-parts/reciprocal', 'club', array( 'club' => $club ) );
get_footer( 'empty' );
