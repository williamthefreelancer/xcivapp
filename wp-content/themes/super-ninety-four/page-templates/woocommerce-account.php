<?php
/**
 * Template Name: WooCommerce Account Shell
 * Description: Full-bleed account layout for My Account (sidebar + Figma panels).
 */

get_header();
?>
<main class="snf-page snf-dashboard snf-dashboard--account" data-figma-ready="true">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>
<?php
get_footer();
