<?php
/**
 * Club roles + theme-native member access for Super Ninety Four.
 *
 * Replaces Content Control + Code Snippets soft-guards. Roles, redirects,
 * admin-bar policy, and Apply applicant tagging all live in the theme.
 *
 * Hook contract:
 * - Listen: init (roles), template_redirect (guards), after_setup_theme (admin bar).
 * - Emit/filter: snf_protected_page_slugs, snf_protected_page_templates, snf_user_can_access_member_areas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'SNF_ACCESS_CONTROL_LOADED' ) ) {
	return;
}
define( 'SNF_ACCESS_CONTROL_LOADED', true );

/**
 * Club role definitions.
 *
 * @return array<string, array{name:string,capabilities:array<string,bool>}>
 */
function snf_get_club_roles() {
	$read = array(
		'read' => true,
	);

	return array(
		'snf_member'     => array(
			'name'         => __( 'Club Member', 'super-ninety-four' ),
			'capabilities' => $read,
		),
		'snf_reciprocal' => array(
			'name'         => __( 'Reciprocal Member', 'super-ninety-four' ),
			'capabilities' => $read,
		),
		'snf_staff'      => array(
			'name'         => __( 'Club Staff', 'super-ninety-four' ),
			'capabilities' => array(
				'read'         => true,
				'edit_posts'   => true,
				'upload_files' => true,
			),
		),
		'snf_applicant'  => array(
			'name'         => __( 'Under Consideration', 'super-ninety-four' ),
			'capabilities' => $read,
		),
	);
}

/**
 * Roles allowed on member/account pages.
 *
 * @return string[]
 */
function snf_get_member_access_roles() {
	return array( 'snf_member', 'snf_reciprocal', 'snf_staff', 'administrator' );
}

/**
 * Member / account page slugs that must stay protected.
 *
 * @return string[]
 */
function snf_get_protected_page_slugs() {
	$slugs = array(
		'billing-details',
		'messages',
		'community',
		'select-events-by-members',
		// Legacy BuddyPress directory slugs (if plugin pages still exist).
		'members',
		'activity',
		'groups',
	);

	if ( function_exists( 'wc_get_page_id' ) ) {
		$myaccount_id = (int) wc_get_page_id( 'myaccount' );
		if ( $myaccount_id > 0 ) {
			$page = get_post( $myaccount_id );
			if ( $page && ! empty( $page->post_name ) ) {
				$slugs[] = $page->post_name;
			}
		}
	}

	/**
	 * Filter protected page slugs for theme access control.
	 *
	 * @param string[] $slugs Slugs.
	 */
	return array_values( array_unique( apply_filters( 'snf_protected_page_slugs', $slugs ) ) );
}

/**
 * Page templates that should be treated as member-only.
 *
 * @return string[]
 */
function snf_get_protected_page_templates() {
	$templates = array(
		'messages',
		'community',
		'billing-details',
		'woocommerce-account',
		'page-templates/messages.php',
		'page-templates/community.php',
		'page-templates/billing-details.php',
		'page-templates/woocommerce-account.php',
	);

	/**
	 * Filter protected page templates for theme access control.
	 *
	 * @param string[] $templates Template paths relative to theme.
	 */
	return array_values( array_unique( apply_filters( 'snf_protected_page_templates', $templates ) ) );
}

/**
 * Public funnel page slugs (must remain open).
 *
 * @return string[]
 */
function snf_get_public_page_slugs() {
	$slugs = array(
		'home',
		'about',
		'inquiries',
		'videos-audio',
		'contact',
		'newsletter',
		'apply',
		'under-consideration',
		'member-login',
		'find-other-reciprocal-programs',
		'select-events-by-non-members',
		'expo',
		'privacy-policy',
		'terms-of-service',
		'cookie-policy',
		'refund_returns',
	);

	/**
	 * Extra public funnel / marketing slugs (upsell & downsell pages).
	 *
	 * @param string[] $slugs Slugs.
	 */
	return array_values( array_unique( apply_filters( 'snf_public_page_slugs', $slugs ) ) );
}

/**
 * Register / refresh club roles.
 */
function snf_register_club_roles() {
	foreach ( snf_get_club_roles() as $role_key => $role ) {
		$existing = get_role( $role_key );
		if ( ! $existing ) {
			add_role( $role_key, $role['name'], $role['capabilities'] );
			continue;
		}

		foreach ( $role['capabilities'] as $cap => $grant ) {
			if ( $grant ) {
				$existing->add_cap( $cap );
			} else {
				$existing->remove_cap( $cap );
			}
		}
	}
}
add_action( 'init', 'snf_register_club_roles', 5 );
add_action( 'after_switch_theme', 'snf_register_club_roles' );

/**
 * Resolve page IDs for a list of slugs.
 *
 * @param string[] $slugs            Page slugs.
 * @param bool     $include_bp_pages Whether to merge BuddyPress directory page IDs.
 * @return int[]
 */
function snf_resolve_page_ids( $slugs, $include_bp_pages = false ) {
	$ids = array();
	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$ids[] = (int) $page->ID;
		}
	}

	if ( $include_bp_pages && function_exists( 'bp_get_option' ) ) {
		$bp_pages = bp_get_option( 'bp-pages', array() );
		foreach ( array( 'members', 'activity', 'groups' ) as $comp ) {
			if ( ! empty( $bp_pages[ $comp ] ) ) {
				$ids[] = (int) $bp_pages[ $comp ];
			}
		}
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Whether the current user may access member/account pages.
 *
 * @return bool
 */
function snf_user_can_access_member_areas() {
	$allowed = false;

	if ( is_user_logged_in() ) {
		if ( current_user_can( 'manage_options' ) ) {
			$allowed = true;
		} else {
			foreach ( snf_get_member_access_roles() as $role ) {
				if ( current_user_can( $role ) ) {
					$allowed = true;
					break;
				}
			}
		}
	}

	/**
	 * Filter member-area access for the current user.
	 *
	 * @param bool $allowed Whether access is allowed.
	 * @param int  $user_id Current user ID.
	 */
	return (bool) apply_filters( 'snf_user_can_access_member_areas', $allowed, get_current_user_id() );
}

/**
 * Whether the current front-end request needs member access.
 *
 * @return bool
 */
function snf_request_requires_member_access() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	if ( function_exists( 'snf_is_marketing_host_page' ) && snf_is_marketing_host_page() ) {
		return false;
	}

	foreach ( snf_get_public_page_slugs() as $slug ) {
		if ( is_page( $slug ) ) {
			return false;
		}
	}

	$protected_slugs = snf_get_protected_page_slugs();
	foreach ( $protected_slugs as $slug ) {
		if ( is_page( $slug ) ) {
			return true;
		}
	}

	if ( is_singular( 'page' ) ) {
		$templates = snf_get_protected_page_templates();
		foreach ( $templates as $template ) {
			if ( function_exists( 'snf_is_page_template' ) && snf_is_page_template( $template ) ) {
				return true;
			}
			if ( is_page_template( $template ) ) {
				return true;
			}
		}
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return true;
	}

	if ( function_exists( 'bp_is_members_directory' ) && bp_is_members_directory() ) {
		return true;
	}
	if ( function_exists( 'bp_is_activity_directory' ) && bp_is_activity_directory() ) {
		return true;
	}
	if ( function_exists( 'bp_is_groups_directory' ) && bp_is_groups_directory() ) {
		return true;
	}
	if ( function_exists( 'bp_is_user' ) && bp_is_user() ) {
		return true;
	}
	if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
		return true;
	}

	return false;
}

/**
 * Redirect guests / non-members away from protected club surfaces.
 */
function snf_guard_member_areas() {
	if ( ! snf_request_requires_member_access() ) {
		return;
	}

	if ( snf_user_can_access_member_areas() ) {
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
add_action( 'template_redirect', 'snf_guard_member_areas', 35 );

/**
 * Hide the admin bar on the front end for non-admins.
 */
function snf_hide_admin_bar_for_club_users() {
	if ( is_admin() || current_user_can( 'manage_options' ) ) {
		return;
	}
	show_admin_bar( false );
}
add_action( 'after_setup_theme', 'snf_hide_admin_bar_for_club_users' );

/**
 * Tag logged-in visitors without a club role as Under Consideration on Apply.
 */
function snf_mark_applicant_on_apply() {
	if ( ! is_user_logged_in() || ! is_page( 'apply' ) ) {
		return;
	}

	$user = wp_get_current_user();
	$skip = array( 'administrator', 'snf_staff', 'snf_reciprocal', 'snf_member', 'snf_applicant' );
	if ( array_intersect( $skip, (array) $user->roles ) ) {
		return;
	}

	snf_assign_club_role( (int) $user->ID, 'snf_applicant' );
}
add_action( 'template_redirect', 'snf_mark_applicant_on_apply', 20 );

/**
 * Assign a club role to a user (replaces other snf_* roles, keeps staff caps intact).
 *
 * @param int    $user_id User ID.
 * @param string $role    Role key.
 * @return bool
 */
function snf_assign_club_role( $user_id, $role ) {
	$user_id = (int) $user_id;
	$roles   = snf_get_club_roles();

	if ( $user_id <= 0 || ! isset( $roles[ $role ] ) ) {
		return false;
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}

	foreach ( array_keys( $roles ) as $club_role ) {
		$user->remove_role( $club_role );
	}

	$user->add_role( $role );
	return true;
}
