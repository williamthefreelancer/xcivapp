<?php
/**
 * My Account — Tickets endpoint (Figma panels from Woo orders).
 *
 * @package SuperNinetyFour
 */

defined( 'ABSPATH' ) || exit;

$panel = snf_get_tickets_endpoint_panel();
?>

<div class="snf-account-endpoint snf-account-endpoint--tickets" data-figma-slot="account-tickets">
	<div class="snf-dashboard__panels" data-figma-slot="account-tickets-panels">
		<?php
		get_template_part(
			'template-parts/dashboard/panel',
			null,
			array(
				'panel' => $panel,
			)
		);
		?>
	</div>

	<section class="snf-account-endpoint__legacy" data-figma-slot="account-tickets-meta" aria-label="<?php esc_attr_e( 'Ticket help', 'super-ninety-four' ); ?>">
		<p class="snf-account-endpoint__note">
			<?php esc_html_e( 'Tickets are sourced from your WooCommerce orders. Open an order for receipts, refunds, and admission details.', 'super-ninety-four' ); ?>
		</p>
		<p class="snf-account-endpoint__note">
			<a href="<?php echo esc_url( snf_get_woo_account_url( 'orders' ) ); ?>"><?php esc_html_e( 'Full order history', 'super-ninety-four' ); ?></a>
			·
			<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Browse events', 'super-ninety-four' ); ?></a>
		</p>
	</section>
</div>
