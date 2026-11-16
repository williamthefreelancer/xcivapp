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

	$profile_url = snf_get_page_url( 'my-profile' );
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
 * Side navigation for account hub pages.
 *
 * @return array<int, array{label:string,url:string,slug:string}>
 */
function snf_get_account_hub_nav() {
	return array(
		array(
			'slug'  => 'my-profile',
			'label' => __( 'My Profile', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'my-profile' ),
		),
		array(
			'slug'  => 'billing-details',
			'label' => __( 'Billing', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'billing-details' ),
		),
		array(
			'slug'  => 'member-concierge',
			'label' => __( 'Concierge', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'member-concierge' ),
		),
		array(
			'slug'  => 'messages',
			'label' => __( 'Messages', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'messages' ),
		),
		array(
			'slug'  => 'settings',
			'label' => __( 'Settings', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'settings' ),
		),
		array(
			'slug'  => 'member-login',
			'label' => __( 'Member Login', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'member-login' ),
		),
		array(
			'slug'  => 'apply',
			'label' => __( 'Apply', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'apply' ),
		),
	);
}

/**
 * Profile page boilerplate sections (BuddyPress-ready).
 *
 * @return array<string, mixed>
 */
function snf_get_profile_hub_data() {
	$member = snf_get_hub_member_context();

	return array(
		'member'  => $member,
		'metrics' => array(
			array(
				'label' => __( 'Club seat', 'super-ninety-four' ),
				'value' => __( 'Principal', 'super-ninety-four' ),
				'hint'  => __( 'City Clubs reciprocal', 'super-ninety-four' ),
			),
			array(
				'label' => __( 'Events this season', 'super-ninety-four' ),
				'value' => '06',
				'hint'  => __( 'Members + guests', 'super-ninety-four' ),
			),
			array(
				'label' => __( 'Concierge open', 'super-ninety-four' ),
				'value' => '02',
				'hint'  => __( 'Awaiting confirmation', 'super-ninety-four' ),
			),
		),
		'fields'  => array(
			array(
				'label' => __( 'Preferred name', 'super-ninety-four' ),
				'value' => $member['name'],
				'bp'    => 'xprofile:preferred_name',
			),
			array(
				'label' => __( 'Member email', 'super-ninety-four' ),
				'value' => $member['email'] ? $member['email'] : __( 'Add a contact email', 'super-ninety-four' ),
				'bp'    => 'xprofile:email',
			),
			array(
				'label' => __( 'Home club', 'super-ninety-four' ),
				'value' => $member['club'],
				'bp'    => 'xprofile:home_club',
			),
			array(
				'label' => __( 'Hospitality notes', 'super-ninety-four' ),
				'value' => __( 'Quiet table preference · No shellfish · Late seating welcome', 'super-ninety-four' ),
				'bp'    => 'xprofile:hospitality_notes',
			),
		),
		'modules' => array(
			array(
				'id'    => 'activity',
				'title' => __( 'Recent club activity', 'super-ninety-four' ),
				'bp'    => 'activity',
				'items' => array(
					__( 'RSVP confirmed — Select Events (members)', 'super-ninety-four' ),
					__( 'Guest pass requested for reciprocal evening', 'super-ninety-four' ),
					__( 'Profile visibility set to members only', 'super-ninety-four' ),
				),
			),
			array(
				'id'    => 'groups',
				'title' => __( 'Clubs & circles', 'super-ninety-four' ),
				'bp'    => 'groups',
				'items' => array(
					__( 'The City Clubs of SUPER NINETY FOUR', 'super-ninety-four' ),
					__( 'Lifestyle Concierge Workshop cohort', 'super-ninety-four' ),
					__( 'Reciprocal Programs discovery', 'super-ninety-four' ),
				),
			),
		),
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

/**
 * Member Concierge page boilerplate.
 *
 * @return array<string, mixed>
 */
function snf_get_concierge_hub_data() {
	return array(
		'services' => array(
			array(
				'title' => __( 'Table & private dining', 'super-ninety-four' ),
				'meta'  => __( 'Reserve rooms, tasting menus, host notes', 'super-ninety-four' ),
				'bp'    => 'messages',
			),
			array(
				'title' => __( 'Guest passes', 'super-ninety-four' ),
				'meta'  => __( 'Introduce guests to reciprocal evenings', 'super-ninety-four' ),
				'bp'    => 'friends',
			),
			array(
				'title' => __( 'Event desk', 'super-ninety-four' ),
				'meta'  => __( 'Hold seats, change parties, waitlists', 'super-ninety-four' ),
				'bp'    => 'events',
			),
			array(
				'title' => __( 'Reciprocal letters', 'super-ninety-four' ),
				'meta'  => __( 'Introduction to partner clubs worldwide', 'super-ninety-four' ),
				'bp'    => 'groups',
			),
			array(
				'title' => __( 'Transport & arrival', 'super-ninety-four' ),
				'meta'  => __( 'Car service, porte-cochère timing', 'super-ninety-four' ),
				'bp'    => 'messages',
			),
			array(
				'title' => __( 'Housekeeping liaison', 'super-ninety-four' ),
				'meta'  => __( 'Suite prep and amenity requests', 'super-ninety-four' ),
				'bp'    => 'messages',
			),
		),
		'requests' => array(
			array(
				'title'  => __( 'Corner table for four — Friday 8:00p', 'super-ninety-four' ),
				'meta'   => __( 'Dining · Awaiting host confirmation', 'super-ninety-four' ),
				'status' => __( 'In review', 'super-ninety-four' ),
			),
			array(
				'title'  => __( 'Guest pass — reciprocal evening', 'super-ninety-four' ),
				'meta'   => __( 'City Clubs · Approved for one guest', 'super-ninety-four' ),
				'status' => __( 'Ready', 'super-ninety-four' ),
			),
		),
	);
}

/**
 * Settings page boilerplate.
 *
 * @return array<string, mixed>
 */
function snf_get_settings_hub_data() {
	return array(
		'groups' => array(
			array(
				'id'      => 'notifications',
				'title'   => __( 'Notifications', 'super-ninety-four' ),
				'bp'      => 'settings',
				'options' => array(
					array(
						'label'   => __( 'Event reminders', 'super-ninety-four' ),
						'checked' => true,
					),
					array(
						'label'   => __( 'Concierge updates', 'super-ninety-four' ),
						'checked' => true,
					),
					array(
						'label'   => __( 'Billing receipts', 'super-ninety-four' ),
						'checked' => true,
					),
					array(
						'label'   => __( 'Club announcements', 'super-ninety-four' ),
						'checked' => false,
					),
				),
			),
			array(
				'id'      => 'privacy',
				'title'   => __( 'Privacy & visibility', 'super-ninety-four' ),
				'bp'      => 'settings',
				'options' => array(
					array(
						'label'   => __( 'Show profile to members only', 'super-ninety-four' ),
						'checked' => true,
					),
					array(
						'label'   => __( 'Allow reciprocal introductions', 'super-ninety-four' ),
						'checked' => true,
					),
					array(
						'label'   => __( 'List me in guest directories', 'super-ninety-four' ),
						'checked' => false,
					),
				),
			),
			array(
				'id'      => 'club-defaults',
				'title'   => __( 'Club defaults', 'super-ninety-four' ),
				'bp'      => 'settings',
				'options' => array(
					array(
						'label'   => __( 'Default to City Clubs calendar', 'super-ninety-four' ),
						'checked' => true,
					),
					array(
						'label'   => __( 'Prefer member-only event listings', 'super-ninety-four' ),
						'checked' => true,
					),
				),
			),
		),
	);
}
