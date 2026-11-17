<?php
/**
 * Sync Code Snippets + Content Control with Super Ninety Four theme surfaces.
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

	$snippets[] = array(
		'name'     => 'SNF — Assign Club Member after Woo purchase',
		'desc'     => 'When a logged-in customer completes a WooCommerce order, upgrade them to the Club Member role (skips staff/reciprocal/admins). Powers Membership endpoint + Content Control access.',
		'scope'    => 'global',
		'active'   => true,
		'priority' => 20,
		'tags'     => array( 'snf', 'woocommerce', 'membership' ),
		'code'     => <<<'PHP'
if ( ! function_exists( 'snf_cs_assign_member_on_completed_order' ) ) {
	function snf_cs_assign_member_on_completed_order( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) || ! function_exists( 'snf_assign_club_role' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$user_id = (int) $order->get_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$skip = array( 'administrator', 'snf_staff', 'snf_reciprocal', 'snf_member' );
		if ( array_intersect( $skip, (array) $user->roles ) ) {
			return;
		}

		snf_assign_club_role( $user_id, 'snf_member' );
	}
}

add_action( 'woocommerce_order_status_completed', 'snf_cs_assign_member_on_completed_order', 20 );
add_action( 'woocommerce_order_status_processing', 'snf_cs_assign_member_on_completed_order', 20 );
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

	$version = '2.9.2-snippets-c';
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
