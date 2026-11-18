<?php
/**
 * View Order — SNF account chrome + receipt layout.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Super_Ninety_Four
 * @version 10.6.0
 */

defined( 'ABSPATH' ) || exit;

$notes       = $order->get_customer_order_notes();
$tickets_url = function_exists( 'snf_get_woo_account_url' ) ? snf_get_woo_account_url( 'tickets' ) : wc_get_account_endpoint_url( 'orders' );
$orders_url  = function_exists( 'snf_get_woo_account_url' ) ? snf_get_woo_account_url( 'orders' ) : wc_get_account_endpoint_url( 'orders' );
?>

<div class="snf-account-endpoint snf-account-endpoint--view-order" data-figma-slot="account-view-order">
	<header class="snf-account-endpoint__intro">
		<h2 class="snf-account-endpoint__title">
			<?php
			printf(
				/* translators: %s: order number */
				esc_html__( 'Order #%s', 'super-ninety-four' ),
				esc_html( $order->get_order_number() )
			);
			?>
		</h2>
		<p class="snf-account-endpoint__note">
			<?php
			echo wp_kses_post(
				apply_filters(
					'woocommerce_order_details_status',
					sprintf(
						/* translators: 1: order number 2: order date 3: order status */
						esc_html__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'woocommerce' ),
						'<mark class="order-number">' . $order->get_order_number() . '</mark>',
						'<mark class="order-date">' . wc_format_datetime( $order->get_date_created() ) . '</mark>',
						'<mark class="order-status">' . wc_get_order_status_name( $order->get_status() ) . '</mark>'
					),
					$order
				)
			);
			?>
		</p>
		<p class="snf-account-endpoint__note">
			<a href="<?php echo esc_url( $tickets_url ); ?>"><?php esc_html_e( 'Tickets', 'super-ninety-four' ); ?></a>
			·
			<a href="<?php echo esc_url( $orders_url ); ?>"><?php esc_html_e( 'All orders', 'super-ninety-four' ); ?></a>
		</p>
	</header>

	<?php if ( $notes ) : ?>
		<section class="snf-account-endpoint__legacy" aria-label="<?php esc_attr_e( 'Order updates', 'woocommerce' ); ?>">
			<h3><?php esc_html_e( 'Order updates', 'woocommerce' ); ?></h3>
			<ol class="woocommerce-OrderUpdates commentlist notes">
				<?php foreach ( $notes as $note ) : ?>
				<li class="woocommerce-OrderUpdate comment note">
					<div class="woocommerce-OrderUpdate-inner comment_container">
						<div class="woocommerce-OrderUpdate-text comment-text">
							<p class="woocommerce-OrderUpdate-meta meta"><?php echo date_i18n( esc_html__( 'l jS \o\f F Y, h:ia', 'woocommerce' ), strtotime( $note->comment_date ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
							<div class="woocommerce-OrderUpdate-description description">
								<?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?>
							</div>
						</div>
					</div>
				</li>
				<?php endforeach; ?>
			</ol>
		</section>
	<?php endif; ?>

	<section class="snf-account-endpoint__legacy snf-view-order__details" data-figma-slot="account-view-order-details">
		<?php do_action( 'woocommerce_view_order', $order_id ); ?>
	</section>
</div>
