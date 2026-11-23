<?php
/**
 * Marketing page helpers and theme action relays for Super Ninety Four.
 *
 * Contact (Let's Connect) / Newsletter / Videos stay public. Transactional Apply /
 * workshop / quote / reciprocal flows stay theme PHP + WooCommerce.
 *
 * Club roles and member-area access live in access-control.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blank-canvas / public marketing page slugs.
 *
 * @return string[]
 */
function snf_get_marketing_host_page_slugs() {
	$slugs = array( 'contact', 'newsletter', 'videos-audio' );

	/**
	 * Extra public blank-canvas / funnel page slugs.
	 *
	 * @param string[] $slugs Slugs.
	 */
	return array_values( array_unique( apply_filters( 'snf_marketing_host_page_slugs', $slugs ) ) );
}

/**
 * Whether the current request is a public marketing / blank-canvas page.
 *
 * @return bool
 */
function snf_is_marketing_host_page() {
	foreach ( snf_get_marketing_host_page_slugs() as $slug ) {
		if ( is_page( $slug ) ) {
			return true;
		}
	}
	return function_exists( 'snf_is_page_template' ) && snf_is_page_template( 'page-templates/blank-canvas.php' );
}

/**
 * Keep marketing hosts out of protected member sets.
 *
 * @param string[] $slugs Protected page slugs.
 * @return string[]
 */
function snf_filter_protected_slugs_exclude_marketing_hosts( $slugs ) {
	$hosts = snf_get_marketing_host_page_slugs();
	return array_values( array_diff( (array) $slugs, $hosts ) );
}
add_filter( 'snf_protected_page_slugs', 'snf_filter_protected_slugs_exclude_marketing_hosts' );

/**
 * Relay Apply → Stripe handoff.
 *
 * @param int    $user_id      User ID.
 * @param string $plan_slug    Preferred plan.
 * @param string $checkout_url Stripe Checkout URL.
 */
function snf_fire_apply_stripe_checkout_started( $user_id, $plan_slug, $checkout_url ) {
	do_action( 'snf_apply_stripe_checkout_started', (int) $user_id, sanitize_title( (string) $plan_slug ), (string) $checkout_url );
}

/**
 * Relay workshop reservation.
 *
 * @param int                  $reservation_id Reservation post ID.
 * @param int                  $event_id       Event/product ID.
 * @param string               $email          Applicant email.
 * @param array<string, mixed> $payload        Extra fields.
 */
function snf_fire_workshop_reservation_created( $reservation_id, $event_id, $email, array $payload = array() ) {
	do_action( 'snf_workshop_reservation_created', (int) $reservation_id, (int) $event_id, (string) $email, $payload );
}

/**
 * Relay service-quote add-to-cart.
 *
 * @param string $cart_item_key Cart key.
 * @param int    $product_id    Product ID.
 * @param int    $quantity      Qty.
 */
function snf_fire_service_quote_added_to_cart( $cart_item_key, $product_id, $quantity ) {
	if ( ! function_exists( 'snf_is_service_quote_product' ) || ! snf_is_service_quote_product( $product_id ) ) {
		return;
	}

	$slug = get_post_field( 'post_name', $product_id );
	do_action( 'snf_service_quote_added_to_cart', (int) $product_id, (string) $slug, (int) $quantity, get_current_user_id() );
}
add_action( 'woocommerce_add_to_cart', 'snf_fire_service_quote_added_to_cart', 30, 3 );
