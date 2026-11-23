<?php
/**
 * Payment methods — SNF account chrome.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Super_Ninety_Four
 * @version 11.3.0
 */

defined( 'ABSPATH' ) || exit;

$saved_methods = wc_get_customer_saved_methods_list( get_current_user_id() );
$has_methods   = (bool) $saved_methods;
$types         = wc_get_account_payment_methods_types(); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

do_action( 'woocommerce_before_account_payment_methods', $has_methods );
?>

<div class="snf-account-endpoint snf-account-endpoint--payment-methods" data-figma-slot="account-payment-methods">
	<header class="snf-account-endpoint__intro">
		<h2 class="snf-account-endpoint__title"><?php esc_html_e( 'Payment methods', 'super-ninety-four' ); ?></h2>
		<p class="snf-account-endpoint__note">
			<?php esc_html_e( 'Saved cards appear here when a gateway supports tokenization. One-time Stripe Payment Element checkouts may not store methods.', 'super-ninety-four' ); ?>
		</p>
	</header>

	<section class="snf-account-endpoint__legacy">
		<?php if ( $has_methods ) : ?>

			<table class="woocommerce-MyAccount-paymentMethods shop_table shop_table_responsive account-payment-methods-table">
				<thead>
					<tr>
						<?php foreach ( wc_get_account_payment_methods_columns() as $column_id => $column_name ) : ?>
							<th class="woocommerce-PaymentMethod woocommerce-PaymentMethod--<?php echo esc_attr( $column_id ); ?> payment-method-<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<?php foreach ( $saved_methods as $type => $methods ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<?php foreach ( $methods as $method ) : ?>
						<tr class="payment-method<?php echo ! empty( $method['is_default'] ) ? ' default-payment-method' : ''; ?>">
							<?php foreach ( wc_get_account_payment_methods_columns() as $column_id => $column_name ) : ?>
								<td class="woocommerce-PaymentMethod woocommerce-PaymentMethod--<?php echo esc_attr( $column_id ); ?> payment-method-<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
									<?php
									if ( has_action( 'woocommerce_account_payment_methods_column_' . $column_id ) ) {
										do_action( 'woocommerce_account_payment_methods_column_' . $column_id, $method );
									} elseif ( 'method' === $column_id ) {
										$method_label = wc_get_credit_card_type_label( $method['method']['brand'] ?? '' );
										$display_name = isset( $method['display_name'] ) && is_string( $method['display_name'] ) ? trim( $method['display_name'] ) : '';
										if ( '' === $method_label && '' !== $display_name && strtolower( $display_name ) !== $type ) {
											echo esc_html( $display_name );
										} elseif ( ! empty( $method['method']['last4'] ) ) {
											/* translators: 1: credit card type 2: last 4 digits */
											printf( esc_html__( '%1$s ending in %2$s', 'woocommerce' ), esc_html( $method_label ), esc_html( $method['method']['last4'] ) );
										} else {
											echo esc_html( $method_label );
										}
									} elseif ( 'expires' === $column_id ) {
										echo esc_html( $method['expires'] );
									} elseif ( 'actions' === $column_id ) {
										foreach ( $method['actions'] as $key => $action ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
											echo '<a href="' . esc_url( $action['url'] ) . '" class="button ' . sanitize_html_class( $key ) . '">' . esc_html( $action['name'] ) . '</a>&nbsp;';
										}
									}
									?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</table>

		<?php else : ?>

			<?php wc_print_notice( esc_html__( 'No saved methods found.', 'woocommerce' ), 'notice' ); ?>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_account_payment_methods', $has_methods ); ?>

		<?php if ( WC()->payment_gateways->get_available_payment_gateways() ) : ?>
			<p class="snf-account-endpoint__actions">
				<a class="button" href="<?php echo esc_url( wc_get_endpoint_url( 'add-payment-method' ) ); ?>"><?php esc_html_e( 'Add payment method', 'woocommerce' ); ?></a>
			</p>
		<?php endif; ?>
	</section>
</div>
