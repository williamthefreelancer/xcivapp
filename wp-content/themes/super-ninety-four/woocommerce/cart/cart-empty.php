<?php
/**
 * Empty cart — SNF branded empty state.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Super_Ninety_Four
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

$shop_url   = apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) );
$events_url = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'select-events-by-non-members' ) : home_url( '/select-events-by-non-members/' );
?>

<div class="snf-cart-empty" data-figma-slot="cart-empty">
	<header class="snf-cart-empty__intro">
		<h2 class="snf-cart-empty__title"><?php esc_html_e( 'Your cart is empty', 'super-ninety-four' ); ?></h2>
		<p class="snf-cart-empty__lede">
			<?php esc_html_e( 'Browse events, workshops, and club offerings, then return here to check out.', 'super-ninety-four' ); ?>
		</p>
	</header>

	<?php
	/**
	 * @hooked wc_empty_cart_message - 10
	 */
	do_action( 'woocommerce_cart_is_empty' );
	?>

	<div class="snf-cart-empty__actions">
		<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
			<a class="button snf-cart-empty__btn snf-cart-empty__btn--primary<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" href="<?php echo esc_url( $shop_url ); ?>">
				<?php echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Browse listings', 'super-ninety-four' ) ) ); ?>
			</a>
		<?php endif; ?>
		<?php if ( $events_url ) : ?>
			<a class="button snf-cart-empty__btn<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" href="<?php echo esc_url( $events_url ); ?>">
				<?php esc_html_e( 'Browse events', 'super-ninety-four' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
