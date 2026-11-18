<?php
/**
 * Club roles + Content Control sync for Super Ninety Four.
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
 * Roles allowed through Content Control on member/account pages.
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
		'members',
		'activity',
		'groups',
		'select-events-by-members',
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
	 * Filter protected page slugs synced into Content Control.
	 *
	 * @param string[] $slugs Slugs.
	 */
	return array_values( array_unique( apply_filters( 'snf_protected_page_slugs', $slugs ) ) );
}

/**
 * Page templates that Content Control should treat as member-only.
 *
 * @return string[]
 */
function snf_get_protected_page_templates() {
	$templates = array(
		'page-templates/messages.php',
		'page-templates/billing-details.php',
		'page-templates/woocommerce-account.php',
	);

	/**
	 * Filter protected page templates for Content Control.
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
	return array(
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
}

/**
 * Register club roles.
 */
function snf_register_club_roles() {
	foreach ( snf_get_club_roles() as $role_key => $role ) {
		if ( get_role( $role_key ) ) {
			continue;
		}
		add_role( $role_key, $role['name'], $role['capabilities'] );
	}
}
add_action( 'init', 'snf_register_club_roles', 5 );

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
 */
function snf_user_can_access_member_areas() {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	foreach ( snf_get_member_access_roles() as $role ) {
		if ( current_user_can( $role ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Upsert a Content Control restriction post.
 *
 * @param string               $slug     Restriction post_name.
 * @param string               $title    Title.
 * @param array<string, mixed> $settings Restriction settings.
 * @param int                  $order    Menu order.
 * @return int
 */
function snf_upsert_content_control_restriction( $slug, $title, $settings, $order = 1 ) {
	$existing = get_posts(
		array(
			'post_type'      => 'cc_restriction',
			'name'           => $slug,
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	$message = isset( $settings['customMessage'] ) ? (string) $settings['customMessage'] : '';
	$postarr = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_status'  => 'publish',
		'post_type'    => 'cc_restriction',
		'post_content' => $message,
		'menu_order'   => (int) $order,
	);

	if ( ! empty( $existing ) ) {
		$postarr['ID']  = (int) $existing[0];
		$restriction_id = wp_update_post( $postarr, true );
	} else {
		$restriction_id = wp_insert_post( $postarr, true );
	}

	if ( is_wp_error( $restriction_id ) || ! $restriction_id ) {
		return 0;
	}

	update_post_meta( (int) $restriction_id, 'restriction_settings', $settings );
	update_post_meta( (int) $restriction_id, 'data_version', 3 );

	return (int) $restriction_id;
}

/**
 * Sync Content Control restrictions for theme member surfaces + public funnel.
 */
function snf_sync_content_control_access() {
	if ( ! post_type_exists( 'cc_restriction' ) ) {
		return;
	}

	$protected_ids = snf_resolve_page_ids( snf_get_protected_page_slugs(), true );
	$public_ids    = snf_resolve_page_ids( snf_get_public_page_slugs(), false );
	// Never lock the public funnel.
	$protected_ids = array_values( array_diff( $protected_ids, $public_ids ) );

	$login_page = get_page_by_path( 'member-login' );
	$login_url  = $login_page ? get_permalink( $login_page ) : wp_login_url();

	$settings = get_option( 'content_control_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	$settings['defaultDenialMessage'] = 'This area is for Super Ninety Four members. Please sign in to continue.';
	$settings['excludeAdmins']        = true;
	update_option( 'content_control_settings', $settings );

	$condition_items = array();
	if ( $protected_ids ) {
		$condition_items[] = array(
			'id'         => 'snf_member_pages',
			'type'       => 'rule',
			'name'       => 'content_is_selected_page',
			'notOperand' => false,
			'options'    => array(
				'selected' => $protected_ids,
			),
		);
	}

	$templates = snf_get_protected_page_templates();
	if ( $templates ) {
		$condition_items[] = array(
			'id'         => 'snf_member_templates',
			'type'       => 'rule',
			'name'       => 'content_is_page_with_template',
			'notOperand' => false,
			'options'    => array(
				'selected' => $templates,
			),
		);
	}

	if ( ! $condition_items ) {
		return;
	}

	$member_restriction = array(
		'userStatus'              => 'logged_in',
		'roleMatch'               => 'match',
		'userRoles'               => snf_get_member_access_roles(),
		'protectionMethod'        => 'redirect',
		'redirectType'            => 'custom',
		'redirectUrl'             => $login_url,
		'replacementType'         => 'message',
		'replacementPage'         => 0,
		'archiveHandling'         => 'redirect',
		'archiveReplacementPage'  => 0,
		'archiveRedirectType'     => 'custom',
		'archiveRedirectUrl'      => $login_url,
		'additionalQueryHandling' => 'filter_post_content',
		'showExcerpts'            => false,
		'overrideMessage'         => true,
		'customMessage'           => 'This area is for Super Ninety Four members. Please sign in with a Club Member, Reciprocal, or Staff account.',
		'showInSearch'            => false,
		'conditions'              => array(
			'logicalOperator' => 'or',
			'items'           => $condition_items,
		),
	);

	snf_upsert_content_control_restriction(
		'member-only-club-areas',
		'Member-only club areas',
		$member_restriction,
		1
	);
}
add_action( 'init', 'snf_sync_content_control_access', 40 );

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
