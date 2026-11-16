<?php
/**
 * Front page template.
 */
get_header();
?>
<main class="snf-home">
	<section class="snf-hero" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
		<div class="snf-container snf-hero__container">
			<div class="snf-hero__brand">
				<img
					class="snf-hero__logo"
					src="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/logo-white.svg' ); ?>"
					alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
					width="209"
					height="20"
					decoding="async"
				>
			</div>
			<?php get_template_part( 'template-parts/search', 'events' ); ?>
		</div>
	</section>
</main>
<?php
get_footer();