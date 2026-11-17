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

	return array(
		'user_id'     => $user_id,
		'name'        => $name,
		'email'       => $email,
		'handle'      => $handle,
		'avatar'      => $avatar,
		'club'        => __( 'The City Clubs of SUPER NINETY FOUR', 'super-ninety-four' ),
		'membership'  => __( 'Reciprocal Principal', 'super-ninety-four' ),
		'status'      => $user_id ? __( 'Active', 'super-ninety-four' ) : __( 'Preview', 'super-ninety-four' ),
		'profile_url' => $profile_url,
		'bp_active'   => snf_buddypress_active(),
	);
}

/**
 * Membership plan catalog for billing.
 *
 * @return array<string, mixed>
 */
function snf_get_membership_plans() {
	$renewal_note = __( 'You will get a reminder before renewal.', 'super-ninety-four' );

	return array(
		'initiation_fee' => array(
			'amount' => '$75,000.00',
			'label'  => __( 'Initiation fee', 'super-ninety-four' ),
			'meta'   => __( 'Required for all membership plans', 'super-ninety-four' ),
		),
		'term'           => __( '12 months', 'super-ninety-four' ),
		'renewal_note'   => $renewal_note,
		'plans'          => array(
			array(
				'slug'   => 'back-creek',
				'name'   => __( 'Back Creek Plan', 'super-ninety-four' ),
				'price'  => '$1,000.00',
				'cadence'=> __( '/mo', 'super-ninety-four' ),
				'term'   => __( 'for 12 months', 'super-ninety-four' ),
				'note'   => $renewal_note,
			),
			array(
				'slug'   => 'badlands',
				'name'   => __( 'Badlands Plan', 'super-ninety-four' ),
				'price'  => '$2,129.00',
				'cadence'=> __( '/mo', 'super-ninety-four' ),
				'term'   => __( 'for 12 months', 'super-ninety-four' ),
				'note'   => $renewal_note,
			),
			array(
				'slug'   => 'regions-ne',
				'name'   => __( 'Regions NE Plan', 'super-ninety-four' ),
				'price'  => '$2,899.00',
				'cadence'=> __( '/mo', 'super-ninety-four' ),
				'term'   => __( 'for 12 months', 'super-ninety-four' ),
				'note'   => $renewal_note,
			),
			array(
				'slug'   => 'city-to-city',
				'name'   => __( 'City to City', 'super-ninety-four' ),
				'price'  => '$3,649.00',
				'cadence'=> __( '/mo', 'super-ninety-four' ),
				'term'   => __( 'for 12 months', 'super-ninety-four' ),
				'note'   => $renewal_note,
			),
		),
	);
}

/**
 * Billing page boilerplate.
 *
 * @return array<string, mixed>
 */
function snf_get_billing_hub_data() {
	$catalog = snf_get_membership_plans();
	$current = $catalog['plans'][0];

	return array(
		'initiation' => $catalog['initiation_fee'],
		'plans'      => $catalog['plans'],
		'plan'       => array(
			'name'   => $current['name'],
			'price'  => $current['price'] . $current['cadence'],
			'renews' => sprintf(
				/* translators: 1: term length, 2: renewal reminder */
				__( '%1$s · %2$s', 'super-ninety-four' ),
				$current['term'],
				$current['note']
			),
			'status' => __( 'Current', 'super-ninety-four' ),
			'slug'   => $current['slug'],
		),
		'metrics'    => array(
			array(
				'label' => __( 'Initiation fee', 'super-ninety-four' ),
				'value' => $catalog['initiation_fee']['amount'],
				'hint'  => __( 'All plans', 'super-ninety-four' ),
			),
			array(
				'label' => __( 'Current dues', 'super-ninety-four' ),
				'value' => $current['price'],
				'hint'  => __( 'Monthly · 12-month term', 'super-ninety-four' ),
			),
			array(
				'label' => __( 'Renewal notice', 'super-ninety-four' ),
				'value' => __( 'Before term', 'super-ninety-four' ),
				'hint'  => __( 'Reminder sent prior to renewal', 'super-ninety-four' ),
			),
		),
		'methods'    => array(
			array(
				'label'  => __( 'Visa ···· 4242', 'super-ninety-four' ),
				'meta'   => __( 'Primary · Expires 08/28', 'super-ninety-four' ),
				'action' => __( 'Replace', 'super-ninety-four' ),
			),
			array(
				'label'  => __( 'House account', 'super-ninety-four' ),
				'meta'   => __( 'Club ledger for dining & guest fees', 'super-ninety-four' ),
				'action' => __( 'Manage', 'super-ninety-four' ),
			),
		),
		'invoices'   => array(
			array(
				'id'     => 'INV-2101',
				'label'  => __( 'Initiation fee', 'super-ninety-four' ),
				'date'   => 'Nov 01, 2026',
				'amount' => '$75,000.00',
				'status' => __( 'Paid', 'super-ninety-four' ),
			),
			array(
				'id'     => 'INV-2102',
				'label'  => __( 'Back Creek Plan — monthly dues', 'super-ninety-four' ),
				'date'   => 'Nov 01, 2026',
				'amount' => '$1,000.00',
				'status' => __( 'Paid', 'super-ninety-four' ),
			),
			array(
				'id'     => 'INV-2041',
				'label'  => __( 'Lifestyle Concierge Workshop', 'super-ninety-four' ),
				'date'   => 'Oct 12, 2026',
				'amount' => '$97.00',
				'status' => __( 'Paid', 'super-ninety-four' ),
			),
		),
	);
}

