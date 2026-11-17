<?php
/**
 * My Account — Membership endpoint (plugin-aware + Figma panels).
 *
 * @package SuperNinetyFour
 */

defined( 'ABSPATH' ) || exit;

$data  = snf_get_membership_endpoint_data();
$panel = snf_get_membership_endpoint_panel();
?>

<div class="snf-account-endpoint snf-account-endpoint--membership" data-figma-slot="account-membership">
	<div class="snf-dashboard__panels" data-figma-slot="account-membership-panels">
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

	<section class="snf-account-endpoint__legacy" data-figma-slot="account-membership-meta" aria-label="<?php esc_attr_e( 'Membership source', 'super-ninety-four' ); ?>">
		<p class="snf-account-endpoint__note">
			<?php
			printf(
				/* translators: %s: engine label */
				esc_html__( 'Data source: %s. Club dues and reciprocal programs use theme roles/meta, WooCommerce order hooks, and Action Scheduler (or WP-Cron). Content Control still gates member areas.', 'super-ninety-four' ),
				esc_html( $data['engine_label'] )
			);
			?>
		</p>
		<p class="snf-account-endpoint__note">
			<a href="<?php echo esc_url( snf_get_woo_account_url( 'edit-address' ) ); ?>"><?php esc_html_e( 'Billing & addresses', 'super-ninety-four' ); ?></a>
			·
			<a href="<?php echo esc_url( snf_get_woo_account_url( 'orders' ) ); ?>"><?php esc_html_e( 'Orders', 'super-ninety-four' ); ?></a>
			·
			<a href="<?php echo esc_url( snf_get_page_url( 'apply' ) ); ?>"><?php esc_html_e( 'Apply / change plan', 'super-ninety-four' ); ?></a>
		</p>
	</section>
</div>
