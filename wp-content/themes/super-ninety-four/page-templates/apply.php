<?php
/**
 * Template Name: Apply Hub
 * Description: Non-member event admissions — contact details, then billing details.
 */

get_header();

$event_id    = snf_get_requested_event_id();
$event_post  = $event_id ? get_post( $event_id ) : null;
$event_title = ( $event_post && 'event' === $event_post->post_type ) ? get_the_title( $event_post ) : '';
$query_label = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$focus_title = $event_title ? $event_title : $query_label;
$apply_name  = isset( $_GET['name'] ) ? sanitize_text_field( wp_unslash( $_GET['name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$apply_email = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$plans      = snf_get_membership_plans();
$plan_list  = isset( $plans['plans'] ) && is_array( $plans['plans'] ) ? $plans['plans'] : array();
$initiation = isset( $plans['initiation_fee'] ) && is_array( $plans['initiation_fee'] ) ? $plans['initiation_fee'] : array();

$lede = __( 'Open admission for guests exploring SUPER NINETY FOUR — contact details first, then billing.', 'super-ninety-four' );
if ( $focus_title ) {
	$lede = sprintf(
		/* translators: %s: event title */
		__( 'You are applying in connection with “%s”. Complete contact details, then billing details for admissions.', 'super-ninety-four' ),
		$focus_title
	);
}
?>
<main class="snf-page snf-hub snf-hub--apply" data-figma-ready="true">
	<section class="snf-hub__shell snf-hub__shell--narrow snf-container" aria-label="<?php esc_attr_e( 'Event admissions application', 'super-ninety-four' ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'Admissions', 'super-ninety-four' ),
				'title'   => __( 'Apply', 'super-ninety-four' ),
				'lede'    => $lede,
			)
		);
		?>

		<?php if ( $focus_title ) : ?>
			<section class="snf-hub__panel" data-figma-slot="apply-event-context">
				<div class="snf-hub__panel-head">
					<h2 class="snf-hub__panel-title"><?php esc_html_e( 'Selected event', 'super-ninety-four' ); ?></h2>
					<?php if ( $event_id ) : ?>
						<a class="snf-hub__ghost-link" href="<?php echo esc_url( get_permalink( $event_id ) ); ?>">
							<?php esc_html_e( 'View event', 'super-ninety-four' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<p class="snf-hub__plan-name"><?php echo esc_html( $focus_title ); ?></p>
			</section>
		<?php endif; ?>

		<section class="snf-hub__panel snf-hub__auth" data-figma-slot="apply-form">
			<div class="snf-hub__panel-head">
				<h2 class="snf-hub__panel-title"><?php esc_html_e( 'Event admissions', 'super-ninety-four' ); ?></h2>
				<span class="snf-hub__panel-note" data-snf-apply-step-note><?php esc_html_e( 'Step 1 of 2 — Contact details', 'super-ninety-four' ); ?></span>
			</div>

			<form class="snf-hub__form snf-apply-steps" data-snf-apply-steps action="#" method="post" novalidate>
				<?php if ( $event_id ) : ?>
					<input type="hidden" name="snf_apply_event" value="<?php echo esc_attr( (string) $event_id ); ?>">
				<?php endif; ?>

				<div class="snf-apply-steps__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Application steps', 'super-ninety-four' ); ?>">
					<button type="button" class="snf-apply-steps__tab is-active" role="tab" aria-selected="true" data-step="1">
						<?php esc_html_e( 'Contact details', 'super-ninety-four' ); ?>
					</button>
					<button type="button" class="snf-apply-steps__tab" role="tab" aria-selected="false" data-step="2">
						<?php esc_html_e( 'Billing details', 'super-ninety-four' ); ?>
					</button>
				</div>

				<div class="snf-apply-steps__panel is-active" data-step-panel="1" role="tabpanel">
					<label class="snf-hub__control">
						<span><?php esc_html_e( 'Full name', 'super-ninety-four' ); ?></span>
						<input type="text" name="snf_apply_name" autocomplete="name" value="<?php echo esc_attr( $apply_name ); ?>" required>
					</label>
					<label class="snf-hub__control">
						<span><?php esc_html_e( 'Email', 'super-ninety-four' ); ?></span>
						<input type="email" name="snf_apply_email" autocomplete="email" value="<?php echo esc_attr( $apply_email ); ?>" required>
					</label>
					<label class="snf-hub__control">
						<span><?php esc_html_e( 'Phone', 'super-ninety-four' ); ?></span>
						<input type="tel" name="snf_apply_phone" autocomplete="tel" required>
					</label>
					<label class="snf-hub__control">
						<span><?php esc_html_e( 'Organization / firm', 'super-ninety-four' ); ?></span>
						<input type="text" name="snf_apply_org" autocomplete="organization">
					</label>
					<label class="snf-hub__control snf-hub__control--full">
						<span><?php esc_html_e( 'City / State', 'super-ninety-four' ); ?></span>
						<input type="text" name="snf_apply_city" autocomplete="address-level2" required>
					</label>
					<button class="snf-hub__action snf-hub__action--primary" type="button" data-snf-next-step>
						<?php esc_html_e( 'Continue to billing details', 'super-ninety-four' ); ?>
					</button>
				</div>

				<div class="snf-apply-steps__panel" data-step-panel="2" role="tabpanel" hidden>
					<?php if ( ! empty( $initiation['amount'] ) ) : ?>
						<p class="snf-apply-steps__meta">
							<?php
							printf(
								/* translators: %s: initiation fee amount */
								esc_html__( 'Initiation fee for all plans: %s', 'super-ninety-four' ),
								esc_html( $initiation['amount'] )
							);
							?>
						</p>
					<?php endif; ?>

					<fieldset class="snf-apply-steps__plans">
						<legend><?php esc_html_e( 'Membership plan', 'super-ninety-four' ); ?></legend>
						<?php foreach ( $plan_list as $index => $plan ) : ?>
							<label class="snf-apply-steps__plan">
								<input
									type="radio"
									name="snf_apply_plan"
									value="<?php echo esc_attr( $plan['slug'] ); ?>"
									<?php checked( 0 === (int) $index ); ?>
									required
								>
								<span>
									<strong><?php echo esc_html( $plan['name'] ); ?></strong>
									<em><?php echo esc_html( $plan['price'] . $plan['cadence'] . ' ' . $plan['term'] ); ?></em>
									<small><?php echo esc_html( $plan['note'] ); ?></small>
								</span>
							</label>
						<?php endforeach; ?>
					</fieldset>

					<label class="snf-hub__control">
						<span><?php esc_html_e( 'Cardholder name', 'super-ninety-four' ); ?></span>
						<input type="text" name="snf_apply_card_name" autocomplete="cc-name" required>
					</label>
					<label class="snf-hub__control">
						<span><?php esc_html_e( 'Billing ZIP / postal', 'super-ninety-four' ); ?></span>
						<input type="text" name="snf_apply_billing_zip" autocomplete="postal-code" required>
					</label>
					<label class="snf-hub__control snf-hub__control--full">
						<span><?php esc_html_e( 'Billing notes', 'super-ninety-four' ); ?></span>
						<textarea name="snf_apply_billing_notes" rows="3" placeholder="<?php esc_attr_e( 'Optional invoice firm name or remittance notes…', 'super-ninety-four' ); ?>"></textarea>
					</label>

					<div class="snf-apply-steps__actions">
						<button class="snf-hub__action" type="button" data-snf-prev-step>
							<?php esc_html_e( 'Back to contact details', 'super-ninety-four' ); ?>
						</button>
						<button class="snf-hub__action snf-hub__action--primary" type="submit">
							<?php esc_html_e( 'Submit admissions request', 'super-ninety-four' ); ?>
						</button>
					</div>
				</div>

				<p class="snf-apply-steps__status" data-snf-apply-status hidden role="status"></p>
			</form>

			<p class="snf-hub__auth-foot">
				<?php esc_html_e( 'Already admitted?', 'super-ninety-four' ); ?>
				<a href="<?php echo esc_url( snf_get_page_url( 'member-login' ) ); ?>">
					<?php esc_html_e( 'Member login', 'super-ninety-four' ); ?>
				</a>
			</p>
		</section>
	</section>
</main>
<?php
get_footer();
