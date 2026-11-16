<?php
/**
 * Template Name: About
 * Description: Public about page for Super Ninety Four.
 */

get_header();
?>
<main class="snf-page snf-hub snf-hub--about" data-figma-ready="true">
	<section class="snf-hub__shell snf-hub__shell--narrow snf-container" aria-label="<?php esc_attr_e( 'About Super Ninety Four', 'super-ninety-four' ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'The club', 'super-ninety-four' ),
				'title'   => __( 'About Super Ninety Four', 'super-ninety-four' ),
				'lede'    => __( 'A private hospitality and reciprocal membership community for events, concierge, and city club access.', 'super-ninety-four' ),
			)
		);
		?>

		<section class="snf-hub__panel" data-figma-slot="about-body">
			<p class="snf-hub__lede">
				<?php esc_html_e( 'SUPER NINETY FOUR connects members through select events, Lifestyle Concierge service, and reciprocal programs across partner clubs. Guests may browse public listings and apply for consideration; admitted members use the private desk for dashboard, messaging, and account tools.', 'super-ninety-four' ); ?>
			</p>
			<div class="snf-hub__actions">
				<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( snf_get_page_url( 'apply' ) ); ?>">
					<?php esc_html_e( 'Apply for consideration', 'super-ninety-four' ); ?>
				</a>
				<a class="snf-hub__action" href="<?php echo esc_url( snf_get_page_url( 'select-events-by-non-members' ) ); ?>">
					<?php esc_html_e( 'Browse events', 'super-ninety-four' ); ?>
				</a>
				<a class="snf-hub__action" href="<?php echo esc_url( snf_get_page_url( 'member-login' ) ); ?>">
					<?php esc_html_e( 'Member login', 'super-ninety-four' ); ?>
				</a>
			</div>
		</section>
	</section>
</main>
<?php
get_footer();
