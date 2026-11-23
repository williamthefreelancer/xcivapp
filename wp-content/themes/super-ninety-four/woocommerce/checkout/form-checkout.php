<?php
/**
 * SNF branded multi-step checkout (overrides WooCommerce form-checkout).
 *
 * @package Super_Ninety_Four
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

$context = class_exists( 'SNF_Payments_Checkout_Fields' )
	? SNF_Payments_Checkout_Fields::cart_context()
	: 'generic';

$get = static function ( $key ) use ( $checkout ) {
	$value = $checkout->get_value( $key );
	if ( '' === $value || null === $value ) {
		$value = apply_filters( 'woocommerce_checkout_get_value', null, $key );
	}
	return is_string( $value ) ? $value : '';
};

$plan_slug = $get( 'snf_apply_plan' );
$plan_label = $plan_slug;
if ( $plan_slug && function_exists( 'snf_get_membership_plan_by_slug' ) ) {
	$plan = snf_get_membership_plan_by_slug( $plan_slug );
	if ( is_array( $plan ) && ! empty( $plan['name'] ) ) {
		$plan_label = (string) $plan['name'];
	}
}

$is_membership = ( 'membership' === $context );
$is_workshop   = ( 'workshop' === $context );
$is_event      = ( 'event' === $context );

$steps = array();
if ( $is_membership || 'generic' === $context ) {
	$steps = array(
		'1'   => __( 'Basics', 'super-ninety-four' ),
		'2'   => __( 'Profile', 'super-ninety-four' ),
		'3'   => __( 'Intent', 'super-ninety-four' ),
		'pay' => __( 'Pay', 'super-ninety-four' ),
	);
} elseif ( $is_workshop ) {
	$steps = array(
		'1'   => __( 'Contact', 'super-ninety-four' ),
		'2'   => __( 'Order', 'super-ninety-four' ),
		'pay' => __( 'Pay', 'super-ninety-four' ),
	);
} else {
	$steps = array(
		'1'   => __( 'Contact', 'super-ninety-four' ),
		'2'   => __( 'Details', 'super-ninety-four' ),
		'pay' => __( 'Pay', 'super-ninety-four' ),
	);
}
?>

<div class="snf-checkout" data-snf-checkout-context="<?php echo esc_attr( $context ); ?>">
	<header class="snf-checkout__intro">
		<h1 class="snf-checkout__title"><?php esc_html_e( 'Checkout', 'super-ninety-four' ); ?></h1>
		<p class="snf-checkout__lede">
			<?php
			if ( $is_membership ) {
				esc_html_e( 'Confirm your admissions details, then pay securely on this page.', 'super-ninety-four' );
			} elseif ( $is_workshop ) {
				esc_html_e( 'Confirm contact details and pay for your workshop reservation.', 'super-ninety-four' );
			} else {
				esc_html_e( 'Confirm your details and complete payment securely.', 'super-ninety-four' );
			}
			?>
		</p>
	</header>

	<form
		name="checkout"
		method="post"
		class="checkout woocommerce-checkout snf-checkout__form"
		action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
		enctype="multipart/form-data"
		aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>"
		data-snf-checkout-steps
		novalidate
	>
		<nav class="snf-checkout__steps" aria-label="<?php esc_attr_e( 'Checkout steps', 'super-ninety-four' ); ?>">
			<ol class="snf-checkout__stepper" role="tablist">
				<?php
				$i = 0;
				foreach ( $steps as $step_key => $step_label ) :
					$i++;
					?>
					<li class="snf-checkout__step<?php echo 1 === $i ? ' is-active' : ''; ?>" data-step-indicator="<?php echo esc_attr( (string) $step_key ); ?>">
						<button type="button" role="tab" class="snf-checkout__step-btn<?php echo 1 === $i ? ' is-active' : ''; ?>" id="snf-checkout-tab-<?php echo esc_attr( (string) $step_key ); ?>" aria-controls="snf-checkout-panel-<?php echo esc_attr( (string) $step_key ); ?>" aria-selected="<?php echo 1 === $i ? 'true' : 'false'; ?>" data-step="<?php echo esc_attr( (string) $step_key ); ?>" <?php echo 1 === $i ? 'aria-current="step"' : ''; ?>>
							<span class="snf-checkout__step-num" aria-hidden="true"><?php echo esc_html( (string) $i ); ?></span>
							<span class="snf-checkout__step-label"><?php echo esc_html( $step_label ); ?></span>
						</button>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<?php /* Keep Woo billing fields in the DOM and accessibility tree for checkout. */ ?>
		<div class="snf-checkout__woo-billing">
			<?php do_action( 'woocommerce_checkout_billing' ); ?>
			<?php do_action( 'woocommerce_checkout_shipping' ); ?>
		</div>

		<?php if ( $is_membership || 'generic' === $context ) : ?>
			<div class="snf-checkout__panel is-active" data-step-panel="1" role="tabpanel" id="snf-checkout-panel-1" aria-labelledby="snf-checkout-tab-1">
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Full name', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_name" autocomplete="name" value="<?php echo esc_attr( $get( 'snf_apply_name' ) ); ?>" required>
				</label>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Email', 'super-ninety-four' ); ?></span>
					<input type="email" name="snf_apply_email" autocomplete="email" value="<?php echo esc_attr( $get( 'snf_apply_email' ) ); ?>" required>
				</label>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Phone', 'super-ninety-four' ); ?></span>
					<input type="tel" name="snf_apply_phone" autocomplete="tel" value="<?php echo esc_attr( $get( 'snf_apply_phone' ) ); ?>" required>
				</label>
				<div class="snf-checkout__actions">
					<button type="button" class="snf-checkout__btn snf-checkout__btn--primary" data-snf-next-step>
						<?php esc_html_e( 'Continue to profile', 'super-ninety-four' ); ?>
					</button>
				</div>
			</div>

			<div class="snf-checkout__panel" data-step-panel="2" role="tabpanel" id="snf-checkout-panel-2" aria-labelledby="snf-checkout-tab-2" hidden>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Organization / firm', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_org" autocomplete="organization" value="<?php echo esc_attr( $get( 'snf_apply_org' ) ); ?>">
				</label>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'City / State', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_city" autocomplete="address-level2" value="<?php echo esc_attr( $get( 'snf_apply_city' ) ); ?>" <?php echo $is_membership ? 'required' : ''; ?>>
				</label>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'How did you hear about us?', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_referral" value="<?php echo esc_attr( $get( 'snf_apply_referral' ) ); ?>">
				</label>
				<div class="snf-checkout__actions">
					<button type="button" class="snf-checkout__btn" data-snf-prev-step><?php esc_html_e( 'Back', 'super-ninety-four' ); ?></button>
					<button type="button" class="snf-checkout__btn snf-checkout__btn--primary" data-snf-next-step>
						<?php esc_html_e( 'Continue to intent', 'super-ninety-four' ); ?>
					</button>
				</div>
			</div>

			<div class="snf-checkout__panel" data-step-panel="3" role="tabpanel" id="snf-checkout-panel-3" aria-labelledby="snf-checkout-tab-3" hidden>
				<?php if ( $plan_slug ) : ?>
					<p class="snf-checkout__summary-title">
						<?php
						printf(
							/* translators: %s: plan name */
							esc_html__( 'Preferred plan: %s', 'super-ninety-four' ),
							esc_html( $plan_label )
						);
						?>
					</p>
					<input type="hidden" name="snf_apply_plan" value="<?php echo esc_attr( $plan_slug ); ?>">
				<?php else : ?>
					<label class="snf-checkout__field">
						<span><?php esc_html_e( 'Preferred membership plan', 'super-ninety-four' ); ?></span>
						<input type="text" name="snf_apply_plan" value="<?php echo esc_attr( $get( 'snf_apply_plan' ) ); ?>">
					</label>
				<?php endif; ?>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Cardholder name', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_card_name" autocomplete="cc-name" value="<?php echo esc_attr( $get( 'snf_apply_card_name' ) ); ?>" required>
				</label>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Billing ZIP / postal', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_billing_zip" autocomplete="postal-code" value="<?php echo esc_attr( $get( 'snf_apply_billing_zip' ) ); ?>" required>
				</label>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Billing notes', 'super-ninety-four' ); ?></span>
					<textarea name="snf_apply_billing_notes" rows="3"><?php echo esc_textarea( $get( 'snf_apply_billing_notes' ) ); ?></textarea>
				</label>
				<div class="snf-checkout__actions">
					<button type="button" class="snf-checkout__btn" data-snf-prev-step><?php esc_html_e( 'Back', 'super-ninety-four' ); ?></button>
					<button type="button" class="snf-checkout__btn snf-checkout__btn--primary" data-snf-next-step>
						<?php esc_html_e( 'Continue to payment', 'super-ninety-four' ); ?>
					</button>
				</div>
			</div>
		<?php else : ?>
			<div class="snf-checkout__panel is-active" data-step-panel="1" role="tabpanel" id="snf-checkout-panel-1" aria-labelledby="snf-checkout-tab-1">
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Full name', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_name" autocomplete="name" value="<?php echo esc_attr( $get( 'snf_apply_name' ) ); ?>" required>
				</label>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Email', 'super-ninety-four' ); ?></span>
					<input type="email" name="snf_apply_email" autocomplete="email" value="<?php echo esc_attr( $get( 'snf_apply_email' ) ); ?>" required>
				</label>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Phone', 'super-ninety-four' ); ?></span>
					<input type="tel" name="snf_apply_phone" autocomplete="tel" value="<?php echo esc_attr( $get( 'snf_apply_phone' ) ); ?>" required>
				</label>
				<div class="snf-checkout__actions">
					<button type="button" class="snf-checkout__btn snf-checkout__btn--primary" data-snf-next-step>
						<?php echo $is_workshop ? esc_html__( 'Continue to order', 'super-ninety-four' ) : esc_html__( 'Continue', 'super-ninety-four' ); ?>
					</button>
				</div>
			</div>

			<div class="snf-checkout__panel" data-step-panel="2" role="tabpanel" id="snf-checkout-panel-2" aria-labelledby="snf-checkout-tab-2" hidden>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Cardholder name', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_card_name" autocomplete="cc-name" value="<?php echo esc_attr( $get( 'snf_apply_card_name' ) ); ?>" required>
				</label>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Billing ZIP / postal', 'super-ninety-four' ); ?></span>
					<input type="text" name="snf_apply_billing_zip" autocomplete="postal-code" value="<?php echo esc_attr( $get( 'snf_apply_billing_zip' ) ); ?>" required>
				</label>
				<label class="snf-checkout__field">
					<span><?php esc_html_e( 'Billing notes', 'super-ninety-four' ); ?></span>
					<textarea name="snf_apply_billing_notes" rows="3"><?php echo esc_textarea( $get( 'snf_apply_billing_notes' ) ); ?></textarea>
				</label>
				<div class="snf-checkout__actions">
					<button type="button" class="snf-checkout__btn" data-snf-prev-step><?php esc_html_e( 'Back', 'super-ninety-four' ); ?></button>
					<button type="button" class="snf-checkout__btn snf-checkout__btn--primary" data-snf-next-step>
						<?php esc_html_e( 'Continue to payment', 'super-ninety-four' ); ?>
					</button>
				</div>
			</div>
		<?php endif; ?>

		<div class="snf-checkout__panel" data-step-panel="pay" role="tabpanel" id="snf-checkout-panel-pay" aria-labelledby="snf-checkout-tab-pay" hidden>
			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
			<h2 class="snf-checkout__summary-title" id="order_review_heading"><?php esc_html_e( 'Your order', 'super-ninety-four' ); ?></h2>
			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
			<div id="order_review" class="woocommerce-checkout-review-order snf-checkout__summary">
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>
			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
			<div class="snf-checkout__actions">
				<button type="button" class="snf-checkout__btn" data-snf-prev-step><?php esc_html_e( 'Back', 'super-ninety-four' ); ?></button>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
	</form>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
