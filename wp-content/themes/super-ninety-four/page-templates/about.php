<?php
/**
 * FSE php-view: loaded by Site Editor templates via snf/php-view (not a classic page template).
 * Description: About — Figma XCIV Social 2176:39 (+ sibling layers 2176:48–54).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$about   = function_exists( 'snf_get_about_content' ) ? snf_get_about_content() : array();
$who     = isset( $about['who'] ) && is_array( $about['who'] ) ? $about['who'] : array();
$founder = isset( $about['founder'] ) && is_array( $about['founder'] ) ? $about['founder'] : array();
$assets  = get_template_directory_uri() . '/assets/images/about';

get_header();
?>
<main
	class="snf-page snf-about"
	data-figma-ready="true"
	data-figma-node="2176:39"
	data-page-slug="about"
	aria-label="<?php esc_attr_e( 'About Super Ninety Four', 'super-ninety-four' ); ?>"
>
	<div class="snf-about__inner snf-container" data-figma-slot="about">
		<section class="snf-about__who" data-figma-node="2176:48" aria-labelledby="snf-about-who-heading">
			<h1 id="snf-about-who-heading" class="snf-about__title">
				<?php echo esc_html( isset( $who['title'] ) ? $who['title'] : __( 'About Who We Are', 'super-ninety-four' ) ); ?>
			</h1>
			<p class="snf-about__body" data-figma-node="2176:49">
				<?php echo esc_html( isset( $who['body'] ) ? $who['body'] : '' ); ?>
			</p>
		</section>

		<section class="snf-about__founder" data-figma-node="2176:51" aria-labelledby="snf-about-founder-heading">
			<h2 id="snf-about-founder-heading" class="snf-about__title snf-about__title--section">
				<?php echo esc_html( isset( $founder['title'] ) ? $founder['title'] : __( 'Meet the Founder', 'super-ninety-four' ) ); ?>
			</h2>

			<figure class="snf-about__portrait" data-figma-node="2176:50">
				<img
					class="snf-about__portrait-img"
					src="<?php echo esc_url( $assets . '/founder-avatar.png' ); ?>"
					alt="<?php echo esc_attr( isset( $founder['name'] ) ? $founder['name'] : __( 'William Antwine Johnson', 'super-ninety-four' ) ); ?>"
					width="352"
					height="310"
					loading="lazy"
					decoding="async"
				>
			</figure>

			<p class="snf-about__name" data-figma-node="2176:54">
				<?php echo esc_html( isset( $founder['name'] ) ? $founder['name'] : __( 'William Antwine Johnson', 'super-ninety-four' ) ); ?>
			</p>
			<p class="snf-about__body" data-figma-node="2176:53">
				<?php echo esc_html( isset( $founder['body'] ) ? $founder['body'] : ( isset( $who['body'] ) ? $who['body'] : '' ) ); ?>
			</p>
		</section>
	</div>
</main>
<?php
get_footer();
