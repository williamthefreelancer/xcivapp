<?php
/**
 * My Addresses — Figma-ready billing panel + Woo address blocks.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package SuperNinetyFour
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing'  => __( 'Billing address', 'woocommerce' ),
			'shipping' => __( 'Shipping address', 'woocommerce' ),
		),
		$customer_id
	);
} else {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing' => __( 'Billing address', 'woocommerce' ),
		),
		$customer_id
	);
}

$oldcol = 1;
$col    = 1;
?>

<div class="snf-account-endpoint snf-account-endpoint--billing" data-figma-slot="account-billing">
	<div class="snf-dashboard__panels" data-figma-slot="account-billing-panels">
		<?php
		foreach ( snf_get_account_endpoint_panels( 'billing' ) as $panel ) {
			get_template_part(
				'template-parts/dashboard/panel',
				null,
				array(
					'panel' => $panel,
				)
			);
		}
		?>
	</div>

	<section class="snf-account-endpoint__legacy" data-figma-slot="account-addresses" aria-label="<?php esc_attr_e( 'Saved addresses', 'super-ninety-four' ); ?>">
		<p class="snf-account-endpoint__note">
			<?php echo apply_filters( 'woocommerce_my_account_my_address_description', esc_html__( 'The following addresses will be used on the checkout page by default.', 'woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</p>

		<?php if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) : ?>
			<div class="u-columns woocommerce-Addresses col2-set addresses">
		<?php endif; ?>

		<?php foreach ( $get_addresses as $name => $address_title ) : ?>
			<?php
			$address = wc_get_account_formatted_address( $name );
			$col     = $col * -1;
			$oldcol  = $oldcol * -1;
			?>

			<div class="u-column<?php echo $col < 0 ? 1 : 2; ?> col-<?php echo $oldcol < 0 ? 1 : 2; ?> woocommerce-Address" data-figma-slot="account-address-<?php echo esc_attr( $name ); ?>">
				<header class="woocommerce-Address-title title">
					<h2><?php echo esc_html( $address_title ); ?></h2>
					<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', $name ) ); ?>" class="edit">
						<?php
						printf(
							/* translators: %s: Address title */
							$address ? esc_html__( 'Edit %s', 'woocommerce' ) : esc_html__( 'Add %s', 'woocommerce' ),
							esc_html( $address_title )
						);
						?>
					</a>
				</header>
				<address>
					<?php
					echo $address ? wp_kses_post( $address ) : esc_html_e( 'You have not set up this type of address yet.', 'woocommerce' );

					/**
					 * Used to output content after core address fields.
					 *
					 * @param string $name Address type.
					 * @since 8.7.0
					 */
					do_action( 'woocommerce_my_account_after_my_address', $name );
					?>
				</address>
			</div>

		<?php endforeach; ?>

		<?php if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) : ?>
			</div>
		<?php endif; ?>
	</section>
</div>
