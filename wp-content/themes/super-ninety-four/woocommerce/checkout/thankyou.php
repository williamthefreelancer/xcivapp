<?php
/**
 * Order received / thank you — SNF branded confirmation.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Super_Ninety_Four
 * @version 11.2.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;

$account_url     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
$tickets_url     = function_exists( 'snf_get_woo_account_url' ) ? snf_get_woo_account_url( 'tickets' ) : $account_url;
$membership_url  = function_exists( 'snf_get_woo_account_url' ) ? snf_get_woo_account_url( 'membership' ) : $account_url;
$orders_url      = function_exists( 'snf_get_woo_account_url' ) ? snf_get_woo_account_url( 'orders' ) : $account_url;
?>

<div class="woocommerce-order snf-thankyou" data-figma-ready="true" data-figma-node="2175:38" data-figma-slot="checkout-thankyou">

	<?php if ( $order ) : ?>

		<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<?php
			$default_order_failed_text = esc_html__( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' );
			$order_failed_text         = apply_filters( 'woocommerce_thankyou_order_failed_text', $default_order_failed_text, $order );
			$order_failed_text         = is_string( $order_failed_text ) ? $order_failed_text : $default_order_failed_text;
			?>

			<header class="snf-thankyou__intro snf-thankyou__intro--failed">
				<h1 class="snf-thankyou__title"><?php esc_html_e( 'Payment unsuccessful', 'super-ninety-four' ); ?></h1>
				<p class="snf-thankyou__lede"><?php echo wp_kses_post( $order_failed_text ); ?></p>
			</header>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions snf-thankyou__actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Try payment again', 'super-ninety-four' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( $account_url ); ?>" class="button"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</p>

		<?php else : ?>

			<?php
			$gathering_slug = function_exists( 'snf_order_open_admission_gathering_slug' )
				? snf_order_open_admission_gathering_slug( $order )
				: '';
			$gathering_url  = ( $gathering_slug && function_exists( 'snf_get_gathering_thankyou_url' ) )
				? snf_get_gathering_thankyou_url( $gathering_slug, $order )
				: '';
			if ( $gathering_url ) {
				wp_safe_redirect( $gathering_url, 302 );
				exit;
			}
			?>

			<header class="snf-thankyou__intro">
				<h1 class="snf-thankyou__title"><?php esc_html_e( 'You\'re confirmed', 'super-ninety-four' ); ?></h1>
				<p class="snf-thankyou__lede">
					<?php esc_html_e( 'Thanks for your purchase. Keep this confirmation for your records, then manage tickets and membership from your account.', 'super-ninety-four' ); ?>
				</p>
			</header>

			<?php wc_get_template( 'checkout/order-received.php', array( 'order' => $order ) ); ?>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details snf-thankyou__overview">

				<li class="woocommerce-order-overview__order order">
					<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<li class="woocommerce-order-overview__date date">
					<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
					<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
					<li class="woocommerce-order-overview__email email">
						<?php esc_html_e( 'Email:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>
				<?php endif; ?>

				<li class="woocommerce-order-overview__total total">
					<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>

			</ul>

			<nav class="snf-thankyou__next" aria-label="<?php esc_attr_e( 'Next steps', 'super-ninety-four' ); ?>">
				<a class="button snf-thankyou__btn snf-thankyou__btn--primary" href="<?php echo esc_url( $tickets_url ); ?>"><?php esc_html_e( 'View tickets', 'super-ninety-four' ); ?></a>
				<a class="button snf-thankyou__btn" href="<?php echo esc_url( $membership_url ); ?>"><?php esc_html_e( 'Membership', 'super-ninety-four' ); ?></a>
				<a class="button snf-thankyou__btn" href="<?php echo esc_url( $orders_url ); ?>"><?php esc_html_e( 'Order history', 'super-ninety-four' ); ?></a>
				<?php
				if ( function_exists( 'snf_funnel_resolve_for_order' ) ) :
					$funnel_trigger = snf_funnel_resolve_for_order( $order );
					if ( $funnel_trigger ) :
						$funnel_offer = snf_funnel_offer_url( (int) $funnel_trigger['config']['upsell_page_id'], $order, 'upsell' );
						if ( $funnel_offer ) :
							?>
							<a class="button snf-thankyou__btn" href="<?php echo esc_url( $funnel_offer ); ?>"><?php esc_html_e( 'View special offer', 'super-ninety-four' ); ?></a>
							<?php
						endif;
					endif;
				endif;
				?>
			</nav>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php else : ?>

		<header class="snf-thankyou__intro">
			<h1 class="snf-thankyou__title"><?php esc_html_e( 'Order received', 'super-ninety-four' ); ?></h1>
		</header>
		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

	<?php endif; ?>

</div>
