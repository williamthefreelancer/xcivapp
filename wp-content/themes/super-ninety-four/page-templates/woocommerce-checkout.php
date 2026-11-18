<?php
/**
 * Template Name: WooCommerce Checkout Shell
 * Description: Branded classic checkout host for [woocommerce_checkout] + snf-payments.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="snf-page snf-checkout-page" data-figma-ready="true">
	<section class="snf-page__frame snf-container snf-checkout-page__frame" aria-label="<?php esc_attr_e( 'Checkout', 'super-ninety-four' ); ?>">
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	</section>
</main>
<?php
get_footer();
