<?php
/**
 * FSE php-view: loaded by Site Editor templates via snf/php-view (not a classic page template).
 * Description: Privacy, terms, cookies, and other policy pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="snf-page snf-hub snf-hub--public snf-hub--legal" data-figma-ready="true">
	<section class="snf-hub__shell snf-hub__shell--narrow snf-container" aria-label="<?php the_title_attribute(); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'Legal', 'super-ninety-four' ),
				'title'   => get_the_title(),
				'lede'    => '',
				'actions' => array(),
			)
		);
		?>

		<section class="snf-hub__panel snf-legal" data-figma-slot="legal-body">
			<?php if ( have_posts() ) : ?>
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<div class="snf-hub__lede snf-hub__rich snf-legal__content">
						<?php the_content(); ?>
					</div>
				<?php endwhile; ?>
			<?php endif; ?>
		</section>
	</section>
</main>
<?php
get_footer();
