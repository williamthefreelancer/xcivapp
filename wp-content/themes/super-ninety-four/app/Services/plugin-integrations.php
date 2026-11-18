<?php
/**
 * Sync Code Snippets + Content Control with Super Ninety Four theme surfaces.
 *
 * SureForms / SureContact / OttoKit (suretriggers): keep Contact + Newsletter
 * public and host marketing forms via blank-canvas the_content(). Do not
 * soft-guard those pages. Transactional Apply / workshop checkout stay theme
 * PHP; SureForms feeds the sales funnel via app/Services/sureforms-funnel.php
 * (submit hooks, REST webhooks, optional Pro native actions, embeds).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upsert a Code Snippet by stable name.
 *
 * @param array<string, mixed> $args Snippet fields.
 * @return int Snippet ID or 0.
 */
function snf_upsert_code_snippet( $args ) {
	if ( ! function_exists( 'Code_Snippets\\save_snippet' ) || ! class_exists( 'Code_Snippets\\Model\\Snippet' ) ) {
		return 0;
	}

	$name = isset( $args['name'] ) ? (string) $args['name'] : '';
	if ( '' === $name ) {
		return 0;
	}

	$existing_id = 0;
	if ( function_exists( 'Code_Snippets\\get_snippets' ) ) {
		foreach ( Code_Snippets\get_snippets() as $snippet ) {
			if ( isset( $snippet->name ) && $snippet->name === $name ) {
				$existing_id = (int) $snippet->id;
				break;
			}
		}
	}

	$snippet = new Code_Snippets\Model\Snippet(
		array(
			'id'       => $existing_id,
			'name'     => $name,
			'desc'     => isset( $args['desc'] ) ? (string) $args['desc'] : '',
			'code'     => isset( $args['code'] ) ? (string) $args['code'] : '',
			'tags'     => isset( $args['tags'] ) ? (array) $args['tags'] : array( 'snf', 'theme' ),
			'scope'    => isset( $args['scope'] ) ? (string) $args['scope'] : 'global',
			'active'   => ! empty( $args['active'] ),
			'priority' => isset( $args['priority'] ) ? (int) $args['priority'] : 10,
			'network'  => false,
			'revision' => 2,
		)
	);

	// Avoid Code Snippets deactivating on theme-function references during save-time lint.
	$snippet->code_error = null;

	$saved = Code_Snippets\save_snippet( $snippet );
	if ( ! $saved ) {
		return 0;
	}

	$id = (int) $saved->id;
	if ( ! empty( $args['active'] ) ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'snippets',
			array( 'active' => 1 ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	return $id;
}

/**
 * Canonical Code Snippets for this theme.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_theme_code_snippets() {
	$snippets = array();

	// Keep legacy name so Code Snippets deactivates the old blanket order→role snippet.
	$snippets[] = array(
		'name'     => 'SNF — Assign Club Member after Woo purchase',
		'desc'     => 'DEPRECATED. Club dues + reciprocal subscriptions are handled in theme (inc/memberships.php + inc/membership-subscriptions.php). Left inactive so event/workshop orders do not grant membership.',
		'scope'    => 'global',
		'active'   => false,
		'priority' => 20,
		'tags'     => array( 'snf', 'woocommerce', 'membership', 'deprecated' ),
		'code'     => <<<'PHP'
// Intentionally inactive — snf_process_membership_order() and
// snf_process_order_for_reciprocal_subscriptions() in the theme handle
// plan products, initiation fees, reciprocal programs, roles, and schedules.
PHP
	,
	);

	$snippets[] = array(
		'name'     => 'SNF — Soft-guard member hubs (BP + account)',
		'desc'     => 'Backup redirect if Content Control misses BuddyPress member routes or Woo My Account endpoints. Uses theme access helper + member-login page.',
		'scope'    => 'front-end',
		'active'   => true,
		'priority' => 30,
		'tags'     => array( 'snf', 'buddypress', 'woocommerce', 'access' ),
		'code'     => <<<'PHP'
if ( ! function_exists( 'snf_cs_soft_guard_member_hubs' ) ) {
	function snf_cs_soft_guard_member_hubs() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! function_exists( 'snf_user_can_access_member_areas' ) ) {
			return;
		}

		$needs_member = false;

		if ( function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in() ) {
			$needs_member = true;
		}

		if ( function_exists( 'bp_is_user' ) && bp_is_user() && function_exists( 'bp_is_my_profile' ) && bp_is_my_profile() ) {
			$needs_member = true;
		}

		if ( function_exists( 'is_page' ) && ( is_page( 'messages' ) || is_page( 'billing-details' ) ) ) {
			$needs_member = true;
		}

		if ( ! $needs_member || snf_user_can_access_member_areas() ) {
			return;
		}

		$login = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'member-login' ) : wp_login_url();

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( $login );
			exit;
		}

		$target = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'under-consideration' ) : home_url( '/' );
		wp_safe_redirect( $target );
		exit;
	}
}

add_action( 'template_redirect', 'snf_cs_soft_guard_member_hubs', 35 );
PHP
	,
	);

	$snippets[] = array(
		'name'     => 'SNF — Hide admin bar for club members',
		'desc'     => 'Keeps Figma member surfaces clean. Admins still see the admin bar.',
		'scope'    => 'front-end',
		'active'   => true,
		'priority' => 10,
		'tags'     => array( 'snf', 'ui' ),
		'code'     => <<<'PHP'
add_action( 'after_setup_theme', function () {
	if ( is_admin() ) {
		return;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	show_admin_bar( false );
} );
PHP
	,
	);

	$snippets[] = array(
		'name'     => 'SNF — Mark applicant on Apply submit',
		'desc'     => 'If a logged-in user hits the Apply page without a club role, tag them Under Consideration (snf_applicant).',
		'scope'    => 'front-end',
		'active'   => true,
		'priority' => 25,
		'tags'     => array( 'snf', 'apply', 'membership' ),
		'code'     => <<<'PHP'
if ( ! function_exists( 'snf_cs_mark_applicant_on_apply' ) ) {
	function snf_cs_mark_applicant_on_apply() {
		if ( ! is_user_logged_in() || ! is_page( 'apply' ) ) {
			return;
		}
		if ( ! function_exists( 'snf_assign_club_role' ) ) {
			return;
		}

		$user = wp_get_current_user();
		$skip = array( 'administrator', 'snf_staff', 'snf_reciprocal', 'snf_member', 'snf_applicant' );
		if ( array_intersect( $skip, (array) $user->roles ) ) {
			return;
		}

		snf_assign_club_role( (int) $user->ID, 'snf_applicant' );
	}
}

add_action( 'template_redirect', 'snf_cs_mark_applicant_on_apply', 20 );
PHP
	,
	);

	return $snippets;
}

/**
 * Sync theme Code Snippets into the Code Snippets plugin.
 */
function snf_sync_code_snippets() {
	if ( ! function_exists( 'Code_Snippets\\save_snippet' ) ) {
		return;
	}

	$version = '2.11.1-snippets-memberships';
	if ( get_option( 'snf_code_snippets_version' ) === $version ) {
		return;
	}

	foreach ( snf_get_theme_code_snippets() as $snippet ) {
		snf_upsert_code_snippet( $snippet );
	}

	update_option( 'snf_code_snippets_version', $version, false );
}
add_action( 'init', 'snf_sync_code_snippets', 50 );

/**
 * Force snippet resync from WP-CLI / one-off: delete_option('snf_code_snippets_version').
 */
function snf_force_sync_code_snippets() {
	delete_option( 'snf_code_snippets_version' );
	snf_sync_code_snippets();
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
	return snf_is_page_template( 'page-templates/blank-canvas.php' );
}

/**
 * Keep SureForms host pages out of Content Control protected sets.
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
		'snf_apply_stripe_checkout_started'   => 'Fired when Apply hands off to Stripe Checkout for the application SKU.',
		'snf_workshop_reservation_created'    => 'Fired after a Lifestyle Concierge workshop seat reservation is stored.',
		'snf_service_quote_added_to_cart'     => 'Fired when a service-quote product is added to the WooCommerce cart.',
		'snf_stripe_webhook_handled'          => 'Fired after a verified Stripe webhook is processed.',
		'snf_funnel_lead_created'             => 'Fired when SureForms / webhook stores an SNF funnel lead.',
		'snf_funnel_intent_processed'         => 'Fired after a funnel intent (quote, workshop, reciprocal, apply, marketing) finishes.',
		'snf_sureforms_service_quote_lead'    => 'Fired when a SureForms service-quote lead is processed.',
		'snf_sureforms_reciprocal_interest'   => 'Fired when SureForms captures reciprocal club interest.',
		'snf_sureforms_apply_lead'            => 'Fired when SureForms captures a membership apply lead.',
		'snf_sureforms_marketing_lead'        => 'Fired when SureForms Contact / Newsletter (or similar) is processed.',
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
