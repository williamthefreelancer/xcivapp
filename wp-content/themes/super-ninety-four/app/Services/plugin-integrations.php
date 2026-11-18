<?php
/**
 * Marketing plugin bridges for Super Ninety Four.
 *
 * SureForms / SureContact / OttoKit (suretriggers): keep Contact + Newsletter
 * public and host marketing forms via blank-canvas the_content(). Do not
 * soft-guard those pages. Transactional Apply / workshop checkout stay theme
 * PHP — do not replace them with SureForms.
 *
 * Club roles and member-area access live in access-control.php (theme-native).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blank-canvas marketing pages hosted for SureForms / Gutenberg blocks.
 *
 * @return string[]
 */
function snf_get_sureforms_host_page_slugs() {
	// Videos & Audio is theme-owned (CPT library hub); optional SureForms still render via the_content().
	return array( 'contact', 'newsletter', 'videos-audio' );
}

/**
 * Whether the current request is a SureForms / blank-canvas marketing page.
 *
 * @return bool
 */
function snf_is_sureforms_host_page() {
	foreach ( snf_get_sureforms_host_page_slugs() as $slug ) {
		if ( is_page( $slug ) ) {
			return true;
		}
	}
	return function_exists( 'snf_is_page_template' ) && snf_is_page_template( 'page-templates/blank-canvas.php' );
}

/**
 * Keep SureForms host pages out of protected member sets.
 *
 * @param string[] $slugs Protected page slugs.
 * @return string[]
 */
function snf_filter_protected_slugs_exclude_sureforms_hosts( $slugs ) {
	$hosts = snf_get_sureforms_host_page_slugs();
	return array_values( array_diff( (array) $slugs, $hosts ) );
}
add_filter( 'snf_protected_page_slugs', 'snf_filter_protected_slugs_exclude_sureforms_hosts' );

/**
 * Never soft-guard Contact / Newsletter / Videos & Audio (SureForms canvases).
 */
function snf_guard_sureforms_host_pages_remain_public() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( ! snf_is_sureforms_host_page() ) {
		return;
	}
	// Intentionally empty: these pages must render for guests so SureForms works.
}
add_action( 'template_redirect', 'snf_guard_sureforms_host_pages_remain_public', 1 );

/**
 * OttoKit / SureTriggers bridge: document theme actions plugins can listen for.
 *
 * Actions fired elsewhere in the theme:
 * - snf_apply_stripe_checkout_started  (user_id, plan_slug, checkout_url)
 * - snf_workshop_reservation_created   (reservation_id, event_id, email, payload)
 * - snf_service_quote_added_to_cart    (product_id, product_slug, quantity, user_id)
 * - snf_stripe_webhook_handled         (event, result) — already in inc/stripe.php
 *
 * Apply / workshop / quote / reciprocal / member-login stay theme PHP — do not
 * replace them with SureForms. SureForms belongs on blank-canvas marketing pages.
 *
 * @return array<string, string>
 */
function snf_get_ottokit_action_catalog() {
	return array(
		'snf_apply_stripe_checkout_started' => 'Fired when Apply hands off to Stripe Checkout for the application SKU.',
		'snf_workshop_reservation_created'  => 'Fired after a Lifestyle Concierge workshop seat reservation is stored.',
		'snf_service_quote_added_to_cart'   => 'Fired when a service-quote product is added to the WooCommerce cart.',
		'snf_stripe_webhook_handled'        => 'Fired after a verified Stripe webhook is processed.',
	);
}

/**
 * Relay Apply → Stripe handoff to OttoKit-friendly action.
 *
 * @param int    $user_id      User ID.
 * @param string $plan_slug    Preferred plan.
 * @param string $checkout_url Stripe Checkout URL.
 */
function snf_fire_apply_stripe_checkout_started( $user_id, $plan_slug, $checkout_url ) {
	/**
	 * OttoKit: Apply started Stripe Checkout for the application fee.
	 *
	 * @param int    $user_id      User ID.
	 * @param string $plan_slug    Preferred membership plan slug.
	 * @param string $checkout_url Stripe Checkout Session URL.
	 */
	do_action( 'snf_apply_stripe_checkout_started', (int) $user_id, sanitize_title( (string) $plan_slug ), (string) $checkout_url );
}

/**
 * Relay workshop reservation to OttoKit-friendly action.
 *
 * @param int                  $reservation_id Reservation post ID.
 * @param int                  $event_id       Event/product ID.
 * @param string               $email          Applicant email.
 * @param array<string, mixed> $payload        Extra fields.
 */
function snf_fire_workshop_reservation_created( $reservation_id, $event_id, $email, array $payload = array() ) {
	/**
	 * OttoKit: Workshop seat reservation created.
	 *
	 * @param int                  $reservation_id Reservation post ID.
	 * @param int                  $event_id       Related event/product ID.
	 * @param string               $email          Email.
	 * @param array<string, mixed> $payload        Name/phone/firm/etc.
	 */
	do_action( 'snf_workshop_reservation_created', (int) $reservation_id, (int) $event_id, (string) $email, $payload );
}

/**
 * Relay service-quote add-to-cart to OttoKit-friendly action.
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

	/**
	 * OttoKit: Service quote product added to cart.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $slug       Product slug.
	 * @param int    $quantity   Quantity.
	 * @param int    $user_id    Current user ID (0 if guest).
	 */
	do_action( 'snf_service_quote_added_to_cart', (int) $product_id, (string) $slug, (int) $quantity, get_current_user_id() );
}
add_action( 'woocommerce_add_to_cart', 'snf_fire_service_quote_added_to_cart', 30, 3 );
