<?php
/**
 * Template Name: Blank Canvas
 * Description: Empty public pages reserved for Figma design conversion.
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
		<span class="screen-reader-text"><?php echo esc_html( $title ); ?></span>
	</div>
</main>
<?php
get_footer();
