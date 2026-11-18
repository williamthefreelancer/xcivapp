<?php
/**
 * FSE php-view: loaded by Site Editor templates via snf/php-view (not a classic page template).
 * Description: Contact / Let's Connect — Figma XCIV Social 2176:55 (+ sibling layers).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$connect = function_exists( 'snf_get_connect_content' ) ? snf_get_connect_content() : array();
$days    = isset( $connect['days'] ) && is_array( $connect['days'] ) ? $connect['days'] : array();
$times   = isset( $connect['times'] ) && is_array( $connect['times'] ) ? $connect['times'] : array();
$intents = isset( $connect['intents'] ) && is_array( $connect['intents'] ) ? $connect['intents'] : array();

$status = isset( $_GET['snf_connect'] ) ? sanitize_key( wp_unslash( $_GET['snf_connect'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

get_header();
?>
<main
	class="snf-page snf-hub snf-hub--connect"
	data-figma-ready="true"
	data-figma-node="2176:55"
	data-page-slug="contact"
	aria-label="<?php esc_attr_e( 'Let\'s Connect', 'super-ninety-four' ); ?>"
>
	<section class="snf-connect" aria-label="<?php esc_attr_e( 'Schedule a call with William', 'super-ninety-four' ); ?>">
		<header class="snf-connect__intro" data-figma-slot="connect-intro">
			<h1 class="snf-connect__title" data-figma-node="2176:66" data-snf-connect-title>
				<?php echo esc_html( isset( $connect['title'] ) ? $connect['title'] : __( 'Schedule Your Time to Talk to William', 'super-ninety-four' ) ); ?>
			</h1>
			<p class="snf-connect__lede" data-figma-node="2176:56" data-snf-connect-lede>
				<?php echo esc_html( isset( $connect['lede'] ) ? $connect['lede'] : '' ); ?>
			</p>
		</header>

		<?php if ( 'sent' === $status ) : ?>
			<p class="snf-connect__flash snf-connect__flash--ok" role="status">
				<?php esc_html_e( 'Request received. William’s desk will confirm your window by email.', 'super-ninety-four' ); ?>
			</p>
		<?php elseif ( 'error' === $status ) : ?>
			<p class="snf-connect__flash snf-connect__flash--err" role="alert">
				<?php esc_html_e( 'We could not send that request. Check the form and try again.', 'super-ninety-four' ); ?>
			</p>
		<?php endif; ?>

		<nav class="snf-connect__steps snf-apply__steps" aria-label="<?php esc_attr_e( 'Connect steps', 'super-ninety-four' ); ?>" data-figma-slot="connect-pagination">
			<ol class="snf-apply__stepper">
				<li class="snf-apply__step is-active" data-step-indicator="1">
					<button type="button" class="snf-apply__step-btn" data-step="1" aria-current="step">
						<span class="snf-apply__step-num" aria-hidden="true">1</span>
						<span class="snf-apply__step-label"><?php esc_html_e( 'Contact Details', 'super-ninety-four' ); ?></span>
					</button>
				</li>
				<li class="snf-apply__step" data-step-indicator="2">
					<button type="button" class="snf-apply__step-btn" data-step="2">
						<span class="snf-apply__step-num" aria-hidden="true">2</span>
						<span class="snf-apply__step-label"><?php esc_html_e( 'Select Day & Times', 'super-ninety-four' ); ?></span>
					</button>
				</li>
				<li class="snf-apply__step" data-step-indicator="3">
					<button type="button" class="snf-apply__step-btn" data-step="3">
						<span class="snf-apply__step-num" aria-hidden="true">3</span>
						<span class="snf-apply__step-label"><?php esc_html_e( 'Intent & Confirm', 'super-ninety-four' ); ?></span>
					</button>
				</li>
			</ol>
		</nav>

		<form
			class="snf-connect__form snf-hub__form snf-apply-steps"
			data-snf-connect-steps
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			method="post"
			novalidate
			data-figma-slot="connect-form"
		>
			<input type="hidden" name="action" value="snf_connect_request">
			<?php wp_nonce_field( 'snf_connect_request', 'snf_connect_nonce' ); ?>

			<p class="snf-connect__status" data-snf-connect-status hidden></p>

			<div class="snf-apply-steps__panel is-active" data-step-panel="1" role="tabpanel">
				<label class="snf-hub__control">
					<span><?php esc_html_e( 'Full name', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_connect_name" autocomplete="name" required>
				</label>
				<label class="snf-hub__control">
					<span><?php esc_html_e( 'Email', 'super-ninety-four' ); ?></span>
					<input type="email" name="snf_connect_email" autocomplete="email" required>
				</label>
				<label class="snf-hub__control">
					<span><?php esc_html_e( 'Phone', 'super-ninety-four' ); ?></span>
					<input type="tel" name="snf_connect_phone" autocomplete="tel" required>
				</label>
				<label class="snf-hub__control">
					<span><?php esc_html_e( 'Organization / firm', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_connect_org" autocomplete="organization">
				</label>
				<button class="snf-hub__action snf-hub__action--primary" type="button" data-snf-next-step>
					<?php esc_html_e( 'Continue to day & times', 'super-ninety-four' ); ?>
				</button>
			</div>

			<div class="snf-apply-steps__panel" data-step-panel="2" role="tabpanel" hidden>
				<fieldset class="snf-connect__fieldset snf-hub__control--full">
					<legend><?php esc_html_e( 'Preferred day (Sun–Wed)', 'super-ninety-four' ); ?></legend>
					<div class="snf-connect__chips" role="radiogroup" aria-label="<?php esc_attr_e( 'Preferred day', 'super-ninety-four' ); ?>">
						<?php foreach ( $days as $index => $day ) : ?>
							<label class="snf-connect__chip">
								<input
									type="radio"
									name="snf_connect_day"
									value="<?php echo esc_attr( $day['value'] ); ?>"
									<?php checked( 0 === (int) $index ); ?>
									required
								>
								<span><?php echo esc_html( $day['label'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<label class="snf-hub__control snf-hub__control--full">
					<span><?php esc_html_e( 'Preferred time window', 'super-ninety-four' ); ?></span>
					<select name="snf_connect_time" required>
						<option value=""><?php esc_html_e( 'Select a time', 'super-ninety-four' ); ?></option>
						<?php foreach ( $times as $time ) : ?>
							<option value="<?php echo esc_attr( $time['value'] ); ?>">
								<?php echo esc_html( $time['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>

				<div class="snf-apply-steps__actions">
					<button class="snf-hub__action" type="button" data-snf-prev-step>
						<?php esc_html_e( 'Back', 'super-ninety-four' ); ?>
					</button>
					<button class="snf-hub__action snf-hub__action--primary" type="button" data-snf-next-step>
						<?php esc_html_e( 'Continue to intent', 'super-ninety-four' ); ?>
					</button>
				</div>
			</div>

			<div class="snf-apply-steps__panel" data-step-panel="3" role="tabpanel" hidden>
				<label class="snf-hub__control snf-hub__control--full">
					<span><?php esc_html_e( 'Intent', 'super-ninety-four' ); ?></span>
					<select name="snf_connect_intent" required>
						<option value=""><?php esc_html_e( 'What is this call about?', 'super-ninety-four' ); ?></option>
						<?php foreach ( $intents as $intent ) : ?>
							<option value="<?php echo esc_attr( $intent['value'] ); ?>">
								<?php echo esc_html( $intent['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="snf-hub__control snf-hub__control--full">
					<span><?php esc_html_e( 'What do you want to cover?', 'super-ninety-four' ); ?></span>
					<textarea name="snf_connect_message" rows="4" required></textarea>
				</label>
				<div class="snf-apply-steps__actions">
					<button class="snf-hub__action" type="button" data-snf-prev-step>
						<?php esc_html_e( 'Back', 'super-ninety-four' ); ?>
					</button>
					<button class="snf-hub__action snf-hub__action--primary" type="submit">
						<?php esc_html_e( 'Confirm & schedule', 'super-ninety-four' ); ?>
					</button>
				</div>
			</div>
		</form>
	</section>
</main>
<?php
get_footer();
