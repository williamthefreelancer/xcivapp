<?php
/**
 * Template Name: Member Login Hub
 * Description: Private club member authentication boilerplate.
 */

get_header();

$redirect = snf_get_page_url( 'dashboard' );
?>
<main class="snf-page snf-hub snf-hub--auth" data-figma-ready="true">
	<section class="snf-hub__shell snf-hub__shell--narrow snf-container" aria-label="<?php esc_attr_e( 'Member login', 'super-ninety-four' ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'Private entrance', 'super-ninety-four' ),
				'title'   => __( 'Member Login', 'super-ninety-four' ),
				'lede'    => __( 'Sign in to your SUPER NINETY FOUR member desk for events, concierge, and reciprocal programs.', 'super-ninety-four' ),
			)
		);
		?>

		<div class="snf-hub__auth" data-figma-slot="member-login-form" data-bp-module="members">
			<?php if ( is_user_logged_in() ) : ?>
				<p class="snf-hub__lede">
					<?php esc_html_e( 'You are already signed in. Continue to Messages or Billing.', 'super-ninety-four' ); ?>
				</p>
				<div class="snf-hub__actions">
					<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( snf_get_page_url( 'messages' ) ); ?>">
						<?php esc_html_e( 'Open messages', 'super-ninety-four' ); ?>
					</a>
					<a class="snf-hub__action" href="<?php echo esc_url( snf_get_page_url( 'billing-details' ) ); ?>">
						<?php esc_html_e( 'Billing details', 'super-ninety-four' ); ?>
					</a>
				</div>
			<?php else : ?>
				<form class="snf-hub__form snf-hub__form--auth" action="<?php echo esc_url( wp_login_url( $redirect ) ); ?>" method="post">
					<label class="snf-hub__control snf-hub__control--full">
						<span><?php esc_html_e( 'Email or username', 'super-ninety-four' ); ?></span>
						<input type="text" name="log" autocomplete="username" required>
					</label>
					<label class="snf-hub__control snf-hub__control--full">
						<span><?php esc_html_e( 'Password', 'super-ninety-four' ); ?></span>
						<input type="password" name="pwd" autocomplete="current-password" required>
					</label>
					<label class="snf-hub__check">
						<input type="checkbox" name="rememberme" value="forever">
						<span><?php esc_html_e( 'Keep me signed in on this device', 'super-ninety-four' ); ?></span>
					</label>
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect ); ?>">
					<button class="snf-hub__action snf-hub__action--primary" type="submit">
						<?php esc_html_e( 'Enter the club', 'super-ninety-four' ); ?>
					</button>
				</form>
				<p class="snf-hub__auth-foot">
					<?php esc_html_e( 'Not a member yet?', 'super-ninety-four' ); ?>
					<a href="<?php echo esc_url( snf_get_page_url( 'apply' ) ); ?>">
						<?php esc_html_e( 'Apply for consideration', 'super-ninety-four' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
