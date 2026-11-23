<?php
/**
 * My Account dashboard — listing sliders.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package SuperNinetyFour
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * My Account dashboard.
 *
 * @since 2.6.0
 */
do_action( 'woocommerce_account_dashboard' );

get_template_part( 'template-parts/dashboard/listings' );
