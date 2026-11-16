<?php
/**
 * 404 template — same brand background.
 */
get_header();
?>
<main class="snf-page">
	<section class="snf-page__frame snf-container">
		<div class="snf-canvas snf-canvas--message">
			<h1 class="snf-archive__title"><?php esc_html_e( 'Page not found', 'super-ninety-four' ); ?></h1>
			<p><a class="snf-archive__admission-link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back home', 'super-ninety-four' ); ?></a></p>
		</div>
	</section>
</main>
<?php
get_footer();