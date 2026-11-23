<?php
/**
 * FSE php-view: loaded by Site Editor templates via snf/php-view (not a classic page template).
 * Description: Branded classic cart surface (shortcode), matching SNF account/checkout chrome.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="snf-page snf-cart" data-figma-ready="true" data-figma-node="2175:2">
	<section class="snf-page__frame snf-container snf-cart__frame" aria-label="<?php esc_attr_e( 'Cart', 'super-ninety-four' ); ?>">
		<header class="snf-cart__intro" data-figma-slot="cart-intro">
			<h1 class="snf-cart__title"><?php esc_html_e( 'Cart', 'super-ninety-four' ); ?></h1>
			<p class="snf-cart__lede"><?php esc_html_e( 'Review your events, workshops, and club purchases before checkout.', 'super-ninety-four' ); ?></p>
		</header>
		<div class="snf-cart__surface" data-figma-slot="cart-surface">
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			?>
		</div>
	</section>
</main>
<?php
get_footer();
