<?php
/**
 * Template Name: Under Consideration
 * Description: Post-application holding page for snf_applicant Soft-guard redirects.
 */

get_header();

$user          = is_user_logged_in() ? wp_get_current_user() : null;
$display_name  = $user && $user->display_name ? $user->display_name : '';
$messages_url  = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'messages' ) : home_url( '/' );
$billing_url   = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'billing-details' ) : home_url( '/' );
$home_url      = home_url( '/' );
$support_email = 'support@suprxcivplatformholdings.com';
?>
<main class="snf-page snf-hub snf-hub--consideration" data-figma-ready="true">
	<section class="snf-hub__shell snf-hub__shell--narrow snf-container" aria-label="<?php esc_attr_e( 'Application under consideration', 'super-ninety-four' ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'Admissions', 'super-ninety-four' ),
				'title'   => __( 'Under Consideration', 'super-ninety-four' ),
				'lede'    => $display_name
					? sprintf(
						/* translators: %s: applicant display name */
						__( 'Thank you, %s. Your application is with the desk for private review.', 'super-ninety-four' ),
						$display_name
					)
					: __( 'Your application is with the desk for private review.', 'super-ninety-four' ),
			)
		);
		?>

		<div class="snf-hub__auth" data-figma-slot="under-consideration-status">
			<p class="snf-hub__lede">
				<?php esc_html_e( 'We review alignment before granting member access. You will hear from SUPER NINETY FOUR when a decision is ready. Full member areas stay closed until then.', 'super-ninety-four' ); ?>
			</p>

			<ul class="snf-hub__rows" style="margin: 1.5rem 0;">
				<li class="snf-hub__row">
					<span><?php esc_html_e( 'Status', 'super-ninety-four' ); ?></span>
					<strong><?php esc_html_e( 'In review', 'super-ninety-four' ); ?></strong>
				</li>
				<li class="snf-hub__row">
					<span><?php esc_html_e( 'Next step', 'super-ninety-four' ); ?></span>
					<strong><?php esc_html_e( 'Wait for admissions outreach', 'super-ninety-four' ); ?></strong>
				</li>
			</ul>

			<div class="snf-hub__actions">
				<?php if ( is_user_logged_in() ) : ?>
					<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( $messages_url ); ?>">
						<?php esc_html_e( 'Open messages', 'super-ninety-four' ); ?>
					</a>
					<a class="snf-hub__action" href="<?php echo esc_url( $billing_url ); ?>">
						<?php esc_html_e( 'Billing details', 'super-ninety-four' ); ?>
					</a>
				<?php else : ?>
					<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'member-login' ) : wp_login_url() ); ?>">
						<?php esc_html_e( 'Member login', 'super-ninety-four' ); ?>
					</a>
				<?php endif; ?>
				<a class="snf-hub__action" href="<?php echo esc_url( $home_url ); ?>">
					<?php esc_html_e( 'Return home', 'super-ninety-four' ); ?>
				</a>
			</div>

			<p class="snf-hub__auth-foot">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: support email mailto link */
						__( 'Questions about your consideration window? Contact %s.', 'super-ninety-four' ),
						'<a href="mailto:' . esc_attr( $support_email ) . '">' . esc_html( $support_email ) . '</a>'
					),
					array(
						'a' => array(
							'href' => true,
						),
					)
				);
				?>
			</p>
		</div>
	</section>
</main>
<?php
get_footer();
