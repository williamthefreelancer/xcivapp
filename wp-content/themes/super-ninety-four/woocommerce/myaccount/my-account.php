<?php
/**
 * My Account page — hybrid shell + Woo content.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package SuperNinetyFour
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	?>
	<section class="snf-container snf-account-login" data-figma-slot="account-login" aria-label="<?php esc_attr_e( 'Member login', 'super-ninety-four' ); ?>">
		<div class="woocommerce-MyAccount-content snf-account-login__content">
			<?php do_action( 'woocommerce_account_content' ); ?>
		</div>
	</section>
	<?php
	return;
}

$current = snf_get_account_nav_current_slug();
if ( ! $current ) {
	$current = 'dashboard';
}

$is_dashboard_home = ! is_wc_endpoint_url();

get_template_part(
	'template-parts/dashboard/shell',
	null,
	array(
		'current'    => $current,
		'main_class' => $is_dashboard_home ? '' : 'snf-dashboard-shell__main--account',
	)
);

if ( $is_dashboard_home ) :
	?>
	<div class="woocommerce-MyAccount-content" data-figma-slot="account-content">
		<?php do_action( 'woocommerce_account_content' ); ?>
	</div>
	<?php
else :
	$lede = __( 'Manage membership, tickets, orders, and account details.', 'super-ninety-four' );
	if ( is_wc_endpoint_url( 'membership' ) ) {
		$lede = __( 'Club status, plan, and renewal details for your seat.', 'super-ninety-four' );
	} elseif ( is_wc_endpoint_url( 'tickets' ) ) {
		$lede = __( 'Event tickets and admissions attached to your account.', 'super-ninety-four' );
	} elseif ( is_wc_endpoint_url( 'orders' ) || is_wc_endpoint_url( 'view-order' ) ) {
		$lede = __( 'Orders for events, tickets, and club purchases.', 'super-ninety-four' );
	} elseif ( is_wc_endpoint_url( 'edit-address' ) || is_wc_endpoint_url( 'payment-methods' ) ) {
		$lede = __( 'Billing addresses and payment methods on file.', 'super-ninety-four' );
	}
	?>
	<div class="snf-account-surface" data-figma-slot="account-surface">
		<header class="snf-account-surface__intro" data-figma-slot="account-intro">
			<h1 class="snf-account-surface__title">
				<?php the_title(); ?>
			</h1>
			<p class="snf-account-surface__lede">
				<?php echo esc_html( $lede ); ?>
			</p>
		</header>

		<div class="woocommerce-MyAccount-content snf-account-surface__content" data-figma-slot="account-content">
			<?php do_action( 'woocommerce_account_content' ); ?>
		</div>
	</div>
	<?php
endif;

get_template_part( 'template-parts/dashboard/shell', 'end' );
