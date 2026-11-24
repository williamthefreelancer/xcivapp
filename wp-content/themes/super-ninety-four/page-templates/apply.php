<?php
/**
 * FSE php-view: loaded by Site Editor templates via snf/php-view (not a classic page template).
 * Description: Non-member admissions — Basics, Profile, then Intent (billing).
 */

$event_id = snf_get_requested_event_id();

// Public ticket products check out on their Woo landing, not membership Apply.
if ( $event_id && function_exists( 'snf_is_public_select_event' ) && snf_is_public_select_event( $event_id ) ) {
	$public_url = function_exists( 'snf_get_event_action_url' )
		? snf_get_event_action_url( $event_id )
		: get_permalink( $event_id );
	if ( $public_url ) {
		wp_safe_redirect( $public_url );
		exit;
	}
}

get_header();

$event_post  = $event_id ? get_post( $event_id ) : null;
$event_title = ( $event_post && 'event' === $event_post->post_type ) ? get_the_title( $event_post ) : '';
$query_label = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$focus_title = $event_title ? $event_title : $query_label;
$apply_name  = isset( $_GET['name'] ) ? sanitize_text_field( wp_unslash( $_GET['name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$apply_email = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$apply_phone = isset( $_GET['phone'] ) ? sanitize_text_field( wp_unslash( $_GET['phone'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$plans = snf_get_membership_plans();
if ( $event_id && function_exists( 'snf_filter_membership_plans_for_event' ) ) {
	$event_post_for_plans = get_post( $event_id );
	$event_slug_for_plans = $event_post_for_plans ? (string) $event_post_for_plans->post_name : '';
	if ( $event_slug_for_plans ) {
		$plans = snf_filter_membership_plans_for_event( $event_slug_for_plans, $plans );
	}
}
$plan_list  = isset( $plans['plans'] ) && is_array( $plans['plans'] ) ? $plans['plans'] : array();
$initiation = isset( $plans['initiation_fee'] ) && is_array( $plans['initiation_fee'] ) ? $plans['initiation_fee'] : array();

$application_label  = __( '4-Week Experience Application', 'super-ninety-four' );
$application_amount = '$97.00';
if ( function_exists( 'snf_supabase_get_stripe_catalog' ) ) {
	$sync_catalog = snf_supabase_get_stripe_catalog();
	if ( is_array( $sync_catalog ) ) {
		foreach ( $sync_catalog as $row ) {
			$name = isset( $row['product_name'] ) ? (string) $row['product_name'] : '';
			if ( $name && false !== stripos( $name, '4-week' ) && isset( $row['unit_amount'] ) ) {
				$application_label  = $name;
				$application_amount = sprintf( '$%0.2f', ( (int) $row['unit_amount'] ) / 100 );
				break;
			}
		}
	}
}
?>
<main class="snf-page snf-hub snf-hub--apply" data-figma-ready="true" data-figma-node="2139:2">
	<section class="snf-apply" aria-label="<?php esc_attr_e( 'Event admissions application', 'super-ninety-four' ); ?>">
		<header class="snf-apply__intro" data-figma-slot="apply-intro">
			<h1 class="snf-apply__title" data-snf-apply-title><?php esc_html_e( 'Basics', 'super-ninety-four' ); ?></h1>
			<p class="snf-apply__lede" data-snf-apply-lede><?php esc_html_e( 'A few details to get started', 'super-ninety-four' ); ?></p>
		</header>

		<?php if ( $focus_title ) : ?>
			<p class="snf-apply__context" data-figma-slot="apply-event-context">
				<?php
				printf(
					/* translators: %s: event or inquiry title */
					esc_html__( 'Applying for: %s', 'super-ninety-four' ),
					esc_html( $focus_title )
				);
				?>
				<?php if ( $event_id ) : ?>
					<a class="snf-apply__context-link" href="<?php echo esc_url( get_permalink( $event_id ) ); ?>">
						<?php esc_html_e( 'View event', 'super-ninety-four' ); ?>
					</a>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<nav class="snf-apply__steps" aria-label="<?php esc_attr_e( 'Application steps', 'super-ninety-four' ); ?>" data-figma-slot="apply-pagination">
			<ol class="snf-apply__stepper">
				<li class="snf-apply__step is-active" data-step-indicator="1">
					<button type="button" class="snf-apply__step-btn" data-step="1" aria-current="step">
						<span class="snf-apply__step-num" aria-hidden="true">1</span>
						<span class="snf-apply__step-label"><?php esc_html_e( 'Basics', 'super-ninety-four' ); ?></span>
					</button>
				</li>
				<li class="snf-apply__step" data-step-indicator="2">
					<button type="button" class="snf-apply__step-btn" data-step="2">
						<span class="snf-apply__step-num" aria-hidden="true">2</span>
						<span class="snf-apply__step-label"><?php esc_html_e( 'Profile', 'super-ninety-four' ); ?></span>
					</button>
				</li>
				<li class="snf-apply__step" data-step-indicator="3">
					<button type="button" class="snf-apply__step-btn" data-step="3">
						<span class="snf-apply__step-num" aria-hidden="true">3</span>
						<span class="snf-apply__step-label"><?php esc_html_e( 'Intent', 'super-ninety-four' ); ?></span>
					</button>
				</li>
			</ol>
		</nav>

		<form class="snf-apply__form snf-hub__form snf-apply-steps" data-snf-apply-steps action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate data-figma-slot="apply-form">
			<input type="hidden" name="action" value="snf_apply_membership">
			<?php wp_nonce_field( 'snf_apply_membership', 'snf_apply_nonce' ); ?>
			<?php if ( $event_id ) : ?>
				<input type="hidden" name="snf_apply_event" value="<?php echo esc_attr( (string) $event_id ); ?>">
			<?php endif; ?>

			<div class="snf-apply-steps__panel is-active" data-step-panel="1" role="tabpanel">
				<label class="snf-hub__control">
					<span><?php esc_html_e( 'Full name', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_name" autocomplete="name" value="<?php echo esc_attr( $apply_name ); ?>" required>
				</label>
				<label class="snf-hub__control">
					<span><?php esc_html_e( 'Email', 'super-ninety-four' ); ?></span>
					<input type="email" name="snf_apply_email" autocomplete="email" value="<?php echo esc_attr( $apply_email ); ?>" required>
				</label>
				<label class="snf-hub__control snf-hub__control--full">
					<span><?php esc_html_e( 'Phone', 'super-ninety-four' ); ?></span>
					<input type="tel" name="snf_apply_phone" autocomplete="tel" value="<?php echo esc_attr( $apply_phone ); ?>" required>
				</label>
				<button class="snf-hub__action snf-hub__action--primary" type="button" data-snf-next-step>
					<?php esc_html_e( 'Continue to profile', 'super-ninety-four' ); ?>
				</button>
			</div>

			<div class="snf-apply-steps__panel" data-step-panel="2" role="tabpanel" hidden>
				<label class="snf-hub__control">
					<span><?php esc_html_e( 'Organization / firm', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_org" autocomplete="organization">
				</label>
				<label class="snf-hub__control snf-hub__control--full">
					<span><?php esc_html_e( 'City / State', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_city" autocomplete="address-level2" required>
				</label>
				<label class="snf-hub__control snf-hub__control--full">
					<span><?php esc_html_e( 'How did you hear about us?', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_referral" autocomplete="off">
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
				<p class="snf-apply-steps__meta">
					<?php
					printf(
						/* translators: 1: application product name, 2: amount */
						esc_html__( 'Application fee: %1$s — %2$s', 'super-ninety-four' ),
						esc_html( $application_label ),
						esc_html( $application_amount )
					);
					?>
				</p>
				<p class="snf-apply-steps__meta">
					<?php esc_html_e( 'You’ll continue to secure checkout on this site. Preferred membership plan is saved for admissions.', 'super-ninety-four' ); ?>
				</p>
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
					<legend><?php esc_html_e( 'Preferred membership plan', 'super-ninety-four' ); ?></legend>
					<?php foreach ( $plan_list as $index => $plan ) : ?>
						<label class="snf-apply-steps__plan">
							<input
								type="radio"
								name="snf_apply_plan"
								value="<?php echo esc_attr( $plan['slug'] ); ?>"
								<?php checked( 0 === (int) $index ); ?>
								required
							>
							<?php if ( ! empty( $plan['image'] ) ) : ?>
								<span class="snf-apply-steps__plan-media" aria-hidden="true">
									<img
										src="<?php echo esc_url( (string) $plan['image'] ); ?>"
										alt=""
										width="96"
										height="72"
										loading="lazy"
										decoding="async"
									>
								</span>
							<?php endif; ?>
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
						<?php esc_html_e( 'Back', 'super-ninety-four' ); ?>
					</button>
					<button class="snf-hub__action snf-hub__action--primary" type="submit">
						<?php
						printf(
							/* translators: %s: application fee amount */
							esc_html__( 'Continue to secure checkout (%s)', 'super-ninety-four' ),
							esc_html( $application_amount )
						);
						?>
					</button>
				</div>
			</div>

			<p class="snf-apply-steps__status" data-snf-apply-status hidden role="status"></p>
		</form>

		<p class="snf-apply__auth-foot">
			<?php esc_html_e( 'Already admitted?', 'super-ninety-four' ); ?>
			<a href="<?php echo esc_url( snf_get_page_url( 'member-login' ) ); ?>">
				<?php esc_html_e( 'Member login', 'super-ninety-four' ); ?>
			</a>
		</p>

		<p class="snf-apply__footnote" data-figma-slot="apply-footnote">
			<?php
			echo wp_kses(
				__( 'Applicants requiring the greatest level of excellence may forgo our application and be interviewed directly by SUPER NINETY FOUR. To pay by bank transfer or ACH Payment, please contact: <a href="mailto:support@suprxcivplatformholdings.com">support@suprxcivplatformholdings.com</a>', 'super-ninety-four' ),
				array(
					'a' => array(
						'href' => true,
					),
				)
			);
			?>
		</p>
	</section>
</main>
<?php
get_footer();
