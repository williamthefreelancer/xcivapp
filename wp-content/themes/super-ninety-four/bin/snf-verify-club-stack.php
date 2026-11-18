<?php
/**
 * Verify BuddyPress-free club stack (roles, pages, services, REST helpers).
 *
 * Usage (WP-CLI): wp eval-file wp-content/themes/super-ninety-four/bin/snf-verify-club-stack.php
 *
 * @package Super_Ninety_Four
 */

$checks = array(
	'role_member'       => (bool) get_role( 'snf_member' ),
	'role_reciprocal'   => (bool) get_role( 'snf_reciprocal' ),
	'role_staff'        => (bool) get_role( 'snf_staff' ),
	'role_applicant'    => (bool) get_role( 'snf_applicant' ),
	'access_guard'      => function_exists( 'snf_guard_member_areas' ),
	'member_access_fn'  => function_exists( 'snf_user_can_access_member_areas' ),
	'memberships'       => function_exists( 'snf_activate_membership' ),
	'messages'          => function_exists( 'snf_messages_rest_can_access' ),
	'messages_peer'     => function_exists( 'snf_messages_get_or_create_peer_thread' ),
	'community'         => function_exists( 'snf_community_list_circles' ),
	'b2b_rfq'           => function_exists( 'snf_b2b_create_rfq' ),
	'expo'              => function_exists( 'snf_is_expo_marketplace_page' ),
	'ticket_commerce'   => function_exists( 'snf_get_ticket_commerce_config' ),
	'theme_menus'       => function_exists( 'snf_ensure_theme_menus' ),
	'page_messages'     => (bool) get_page_by_path( 'messages' ),
	'page_community'    => (bool) get_page_by_path( 'community' ),
	'page_expo'         => (bool) get_page_by_path( 'expo' ),
	'page_events_m'     => (bool) get_page_by_path( 'select-events-by-members' ),
	'page_events_nm'    => (bool) get_page_by_path( 'select-events-by-non-members' ),
	'cpt_circle'        => post_type_exists( 'snf_circle' ),
	'cpt_rfq'           => post_type_exists( 'snf_rfq' ),
	'buddypress_active' => function_exists( 'buddypress' ) || class_exists( 'BuddyPress', false ),
);

$fail = 0;
foreach ( $checks as $key => $ok ) {
	if ( 'buddypress_active' === $key ) {
		$label = $ok ? 'active (deactivate for production)' : 'inactive (ok)';
		echo $key . ':' . $label . PHP_EOL;
		continue;
	}
	if ( ! $ok ) {
		++$fail;
	}
	echo $key . ':' . ( $ok ? 'ok' : 'FAIL' ) . PHP_EOL;
}

echo 'result:' . ( $fail ? 'fail(' . $fail . ')' : 'pass' ) . PHP_EOL;
exit( $fail ? 1 : 0 );
