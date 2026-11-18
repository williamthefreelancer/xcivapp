<?php
/**
 * FSE php-view: loaded by Site Editor templates via snf/php-view (not a classic page template).
 * Description: Blank About page reserved for Figma design conversion.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main
	class="snf-page snf-page--blank snf-page--about"
	data-figma-ready="true"
	data-page-slug="about"
	aria-label="<?php esc_attr_e( 'About Super Ninety Four', 'super-ninety-four' ); ?>"
>
	<div class="snf-page__canvas snf-container" data-figma-slot="about">
		<span class="screen-reader-text"><?php esc_html_e( 'About Super Ninety Four', 'super-ninety-four' ); ?></span>
	</div>
</main>
<?php
get_footer();
