<?php
/**
 * Downloads — SNF account chrome.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Super_Ninety_Four
 * @version 7.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$downloads     = WC()->customer->get_downloadable_products();
$has_downloads = (bool) $downloads;
$shop_url      = apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) );

do_action( 'woocommerce_before_account_downloads', $has_downloads );
?>

<div class="snf-account-endpoint snf-account-endpoint--downloads" data-figma-slot="account-downloads">
	<header class="snf-account-endpoint__intro">
		<h2 class="snf-account-endpoint__title"><?php esc_html_e( 'Downloads', 'super-ninety-four' ); ?></h2>
		<p class="snf-account-endpoint__note">
			<?php esc_html_e( 'Digital files tied to your orders appear here when a product is downloadable.', 'super-ninety-four' ); ?>
		</p>
	</header>

	<section class="snf-account-endpoint__legacy">
		<?php if ( $has_downloads ) : ?>

			<?php do_action( 'woocommerce_before_available_downloads' ); ?>

			<?php do_action( 'woocommerce_available_downloads', $downloads ); ?>

			<?php do_action( 'woocommerce_after_available_downloads' ); ?>

		<?php else : ?>

			<?php
			$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';
			wc_print_notice(
				esc_html__( 'No downloads available yet.', 'woocommerce' ) . ' <a class="button wc-forward' . esc_attr( $wp_button_class ) . '" href="' . esc_url( $shop_url ) . '">' . esc_html__( 'Browse listings', 'super-ninety-four' ) . '</a>',
				'notice'
			);
			?>

		<?php endif; ?>
	</section>
</div>

<?php do_action( 'woocommerce_after_account_downloads', $has_downloads ); ?>
