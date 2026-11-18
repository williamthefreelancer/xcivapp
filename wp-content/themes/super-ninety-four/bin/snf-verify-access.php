<?php
/**
 * Verify theme-native roles + access helpers.
 *
 * @package Super_Ninety_Four
 */

if ( function_exists( 'snf_register_club_roles' ) ) {
	snf_register_club_roles();
}

foreach ( array( 'snf_member', 'snf_reciprocal', 'snf_staff', 'snf_applicant' ) as $role ) {
	echo $role . ':' . ( get_role( $role ) ? 'ok' : 'missing' ) . PHP_EOL;
}

echo 'guard:' . ( function_exists( 'snf_guard_member_areas' ) ? 'ok' : 'missing' ) . PHP_EOL;
echo 'requires:' . ( function_exists( 'snf_request_requires_member_access' ) ? 'ok' : 'missing' ) . PHP_EOL;
echo 'snippets:' . ( function_exists( 'snf_upsert_code_snippet' ) ? 'still' : 'gone' ) . PHP_EOL;
echo 'cc_sync:' . ( function_exists( 'snf_sync_content_control_access' ) ? 'still' : 'gone' ) . PHP_EOL;
echo 'cc_posts:' . ( post_type_exists( 'cc_restriction' ) ? 'type_exists' : 'type_gone' ) . PHP_EOL;
echo 'plugins_cc:' . ( file_exists( WP_PLUGIN_DIR . '/content-control/content-control.php' ) ? 'present' : 'removed' ) . PHP_EOL;
echo 'plugins_cs:' . ( file_exists( WP_PLUGIN_DIR . '/code-snippets/code-snippets.php' ) ? 'present' : 'removed' ) . PHP_EOL;
