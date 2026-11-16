<?php
/**
 * Template Name: Under Consideration
 * Description: Public confirmation after an admissions application is submitted.
 */

get_header();
?>
<main class="snf-page snf-hub snf-hub--consideration" data-figma-ready="true">
	<section class="snf-hub__shell snf-hub__shell--narrow snf-container" aria-label="<?php esc_attr_e( 'Application under consideration', 'super-ninety-four' ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'Admissions', 'super-ninety-four' ),
				'title'   => __( 'Under consideration', 'super-ninety-four' ),
				'lede'    => __( 'Your request reached the desk. We review each application manually before membership access is released.', 'super-ninety-four' ),
			)
		);
		?>

		<section class="snf-hub__panel" data-figma-slot="consideration-body">
			<p class="snf-hub__lede">
				<?php esc_html_e( 'You will hear from us by email once a decision is ready. Until then you can browse public event listings or return to the login page if you already hold a seat.', 'super-ninety-four' ); ?>
			</p>
			<div class="snf-hub__actions">
				<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( snf_get_page_url( 'select-events-by-non-members' ) ); ?>">
					<?php esc_html_e( 'Select events (non-members)', 'super-ninety-four' ); ?>
				</a>
				<a class="snf-hub__action" href="<?php echo esc_url( snf_get_page_url( 'member-login' ) ); ?>">
					<?php esc_html_e( 'Member login', 'super-ninety-four' ); ?>
				</a>
				<a class="snf-hub__action" href="<?php echo esc_url( snf_get_page_url( 'about' ) ); ?>">
					<?php esc_html_e( 'About the club', 'super-ninety-four' ); ?>
				</a>
			</div>
		</section>
	</section>
</main>
<?php
get_footer();
