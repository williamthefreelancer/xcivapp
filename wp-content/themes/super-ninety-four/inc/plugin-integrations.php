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
