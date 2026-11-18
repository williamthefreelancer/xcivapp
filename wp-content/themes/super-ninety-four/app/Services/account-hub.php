<?php
/**
 * Account / hospitality hub data + BuddyPress-ready helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether BuddyPress is available.
 */
function snf_buddypress_active() {
	return function_exists( 'buddypress' ) || class_exists( 'BuddyPress', false );
}

/**
 * Current member display context for hub pages.
 *
 * @return array<string, mixed>
 */
function snf_get_hub_member_context() {
	$user_id = get_current_user_id();
	$name    = __( 'Guest Member', 'super-ninety-four' );
	$email   = '';
	$avatar  = get_template_directory_uri() . '/assets/icons/user-profile.svg';
	$handle  = 'guest';

	if ( $user_id ) {
		$user   = get_userdata( $user_id );
		$name   = $user ? ( $user->display_name ? $user->display_name : $user->user_login ) : $name;
		$email  = $user ? $user->user_email : '';
		$handle = $user ? $user->user_login : $handle;
		$avatar = get_avatar_url( $user_id, array( 'size' => 192 ) );
		if ( ! $avatar ) {
			$avatar = get_template_directory_uri() . '/assets/icons/user-profile.svg';
		}
	}

	$profile_url = home_url( '/' );
	if ( snf_buddypress_active() && $user_id && function_exists( 'bp_members_get_user_url' ) ) {
		$profile_url = bp_members_get_user_url( $user_id );
	} elseif ( snf_buddypress_active() && $user_id && function_exists( 'bp_core_get_user_domain' ) ) {
		$profile_url = bp_core_get_user_domain( $user_id );
	}

	$membership_label = __( 'Guest preview', 'super-ninety-four' );
	$status_label     = $user_id ? __( 'Signed in', 'super-ninety-four' ) : __( 'Preview', 'super-ninety-four' );
	if ( $user_id && function_exists( 'snf_get_user_membership' ) ) {
		$mem = snf_get_user_membership( $user_id );
		if ( ! empty( $mem['plan_row']['name'] ) ) {
			$membership_label = $mem['plan_row']['name'];
		} elseif ( function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas() ) {
			$membership_label = __( 'Club Member', 'super-ninety-four' );
		}
		if ( ! empty( $mem['status'] ) ) {
			$status_label = ucwords( str_replace( '-', ' ', (string) $mem['status'] ) );
		} elseif ( $mem['is_active'] ) {
			$status_label = __( 'Active', 'super-ninety-four' );
		}
	}

	return array(
		'user_id'     => $user_id,
		'name'        => $name,
		'email'       => $email,
		'handle'      => $handle,
		'avatar'      => $avatar,
		'club'        => __( 'The City Clubs of SUPER NINETY FOUR', 'super-ninety-four' ),
		'membership'  => $membership_label,
		'status'      => $status_label,
		'profile_url' => $profile_url,
		'bp_active'   => snf_buddypress_active(),
	);
}

/**
 * Membership plan catalog for billing / Apply UI.
 *
 * @return array<string, mixed>
 */
function snf_get_membership_plans() {
	if ( function_exists( 'snf_membership_plans_compat_shape' ) ) {
		return snf_membership_plans_compat_shape();
	}

	$renewal_note = __( 'You will get a reminder before renewal.', 'super-ninety-four' );
	return array(
		'initiation_fee' => array(
			'amount' => '$75,000.00',
			'label'  => __( 'Initiation fee', 'super-ninety-four' ),
			'meta'   => __( 'Required for all membership plans', 'super-ninety-four' ),
		),
		'term'           => __( '12 months', 'super-ninety-four' ),
		'renewal_note'   => $renewal_note,
		'plans'          => array(),
	);
}

/**
 * Billing page data — live membership meta when available.
 *
 * @return array<string, mixed>
 */
function snf_get_billing_hub_data() {
	if ( function_exists( 'snf_get_live_billing_hub_data' ) ) {
		return snf_get_live_billing_hub_data();
	}

	$catalog = snf_get_membership_plans();
	$current = isset( $catalog['plans'][0] ) ? $catalog['plans'][0] : array(
		'name'    => __( 'Membership plan', 'super-ninety-four' ),
		'price'   => '',
		'cadence' => '',
		'term'    => '',
		'note'    => '',
		'slug'    => '',
	);

	return array(
		'initiation' => $catalog['initiation_fee'],
		'plans'      => $catalog['plans'],
		'plan'       => array(
			'name'   => $current['name'],
			'price'  => $current['price'] . $current['cadence'],
			'renews' => trim( $current['term'] . ' · ' . $current['note'], ' ·' ),
			'status' => __( 'Catalog', 'super-ninety-four' ),
			'slug'   => $current['slug'],
		),
		'metrics'    => array(),
		'methods'    => array(),
		'invoices'   => array(),
	);
}

