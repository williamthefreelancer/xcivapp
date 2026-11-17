<?php
/**
 * BuddyPress profile: Figma-ready panels + member page helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displayed member context for profile surfaces.
 *
 * @return array<string, mixed>
 */
function snf_get_displayed_member_context() {
	$user_id = function_exists( 'bp_displayed_user_id' ) ? (int) bp_displayed_user_id() : 0;
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	$user   = $user_id ? get_userdata( $user_id ) : null;
	$name   = $user ? ( $user->display_name ? $user->display_name : $user->user_login ) : __( 'Member', 'super-ninety-four' );
	$handle = $user ? $user->user_login : '';
	$avatar = $user_id ? get_avatar_url( $user_id, array( 'size' => 256 ) ) : '';
	if ( ! $avatar ) {
		$avatar = get_template_directory_uri() . '/assets/icons/user-profile.svg';
	}

	$mention = '';
	if ( $user_id && function_exists( 'bp_activity_get_user_mentionname' ) ) {
		$mention = (string) bp_activity_get_user_mentionname( $user_id );
	} elseif ( $handle ) {
		$mention = $handle;
	}

	$profile_url = '';
	if ( $user_id && function_exists( 'bp_members_get_user_url' ) ) {
		$profile_url = bp_members_get_user_url( $user_id );
	} elseif ( $user_id && function_exists( 'bp_core_get_user_domain' ) ) {
		$profile_url = bp_core_get_user_domain( $user_id );
	}

	$edit_url = $profile_url;
	if ( $user_id && function_exists( 'bp_displayed_user_domain' ) && function_exists( 'bp_get_profile_slug' ) ) {
		$edit_url = trailingslashit( bp_displayed_user_domain() ) . trailingslashit( bp_get_profile_slug() ) . 'edit/';
	}

	$cover = '';
	if ( $user_id && function_exists( 'bp_attachments_get_user_has_cover_image' ) && bp_attachments_get_user_has_cover_image( $user_id )
		&& function_exists( 'bp_attachments_get_attachment' ) ) {
		$cover = (string) bp_attachments_get_attachment(
			'url',
			array(
				'object_dir' => 'members',
				'item_id'    => $user_id,
			)
		);
	}

	$bio = $user_id ? (string) get_the_author_meta( 'description', $user_id ) : '';

	return array(
		'user_id'     => $user_id,
		'name'        => $name,
		'handle'      => $handle,
		'mention'     => $mention,
		'avatar'      => $avatar,
		'cover'       => $cover,
		'bio'         => $bio,
		'profile_url' => $profile_url,
		'edit_url'    => $edit_url,
		'is_mine'     => function_exists( 'bp_is_my_profile' ) ? (bool) bp_is_my_profile() : ( get_current_user_id() === $user_id ),
		'membership'  => __( 'Reciprocal Principal', 'super-ninety-four' ),
		'club'        => __( 'The City Clubs of SUPER NINETY FOUR', 'super-ninety-four' ),
	);
}

/**
 * XProfile field cards for the About panel.
 *
 * @param int $user_id User ID.
 * @return array<int, array<string, string>>
 */
function snf_get_profile_xprofile_cards( $user_id ) {
	$cards = array();

	if ( ! $user_id || ! function_exists( 'bp_has_profile' ) || ! bp_is_active( 'xprofile' ) ) {
		return $cards;
	}

	if ( bp_has_profile( array( 'user_id' => $user_id ) ) ) {
		while ( bp_profile_groups() ) {
			bp_the_profile_group();
			if ( ! bp_profile_group_has_fields() ) {
				continue;
			}
			while ( bp_profile_fields() ) {
				bp_the_profile_field();
				if ( ! bp_field_has_data() ) {
					continue;
				}
				$cards[] = array(
					'eyebrow' => bp_get_the_profile_group_name(),
					'title'   => bp_get_the_profile_field_name(),
					'meta'    => wp_strip_all_tags( bp_get_the_profile_field_value() ),
					'status'  => __( 'Profile', 'super-ninety-four' ),
					'tone'    => 'neutral',
					'url'     => '',
					'action'  => '',
				);
				if ( count( $cards ) >= 6 ) {
					break 2;
				}
			}
		}
	}

	return $cards;
}

/**
 * Identity panel for member profiles.
 *
 * @return array<string, mixed>
 */
function snf_get_profile_identity_panel() {
	$member = snf_get_displayed_member_context();

	$cards = array(
		array(
			'eyebrow' => __( 'Member', 'super-ninety-four' ),
			'title'   => $member['name'],
			'meta'    => $member['mention'] ? '@' . $member['mention'] : $member['handle'],
			'status'  => $member['is_mine'] ? __( 'You', 'super-ninety-four' ) : __( 'Member', 'super-ninety-four' ),
			'tone'    => 'success',
			'url'     => $member['profile_url'],
			'action'  => __( 'View profile', 'super-ninety-four' ),
			'image'   => $member['avatar'],
		),
		array(
			'eyebrow' => __( 'Club', 'super-ninety-four' ),
			'title'   => $member['membership'],
			'meta'    => $member['club'],
			'status'  => __( 'Active', 'super-ninety-four' ),
			'tone'    => 'info',
			'url'     => snf_get_woo_account_url( 'edit-address' ),
			'action'  => __( 'Membership', 'super-ninety-four' ),
		),
	);

	if ( $member['is_mine'] ) {
		$cards[] = array(
			'eyebrow' => __( 'Edit', 'super-ninety-four' ),
			'title'   => __( 'Update profile', 'super-ninety-four' ),
			'meta'    => __( 'Avatar, cover, and profile fields.', 'super-ninety-four' ),
			'status'  => __( 'Owner', 'super-ninety-four' ),
			'tone'    => 'neutral',
			'url'     => $member['edit_url'],
			'action'  => __( 'Edit profile', 'super-ninety-four' ),
		);
	} else {
		$cards[] = array(
			'eyebrow' => __( 'Connect', 'super-ninety-four' ),
			'title'   => __( 'Send a message', 'super-ninety-four' ),
			'meta'    => __( 'Open the Messages hub to start a thread.', 'super-ninety-four' ),
			'status'  => __( 'Inbox', 'super-ninety-four' ),
			'tone'    => 'info',
			'url'     => snf_get_bp_messages_url(),
			'action'  => __( 'Messages', 'super-ninety-four' ),
		);
	}

	return apply_filters(
		'snf_profile_identity_panel',
		array(
			'id'       => 'identity',
			'title'    => __( 'Identity', 'super-ninety-four' ),
			'lede'     => __( 'Name, membership, and profile actions.', 'super-ninety-four' ),
			'view_all' => $member['edit_url'],
			'cards'    => array_map( 'snf_normalize_panel_card', $cards ),
		)
	);
}

/**
 * About panel — bio + XProfile field cards.
 *
 * @return array<string, mixed>
 */
function snf_get_profile_about_panel() {
	$member = snf_get_displayed_member_context();
	$cards  = array();

	if ( $member['bio'] ) {
		$cards[] = array(
			'eyebrow' => __( 'Bio', 'super-ninety-four' ),
			'title'   => __( 'About', 'super-ninety-four' ),
			'meta'    => wp_trim_words( $member['bio'], 36, '…' ),
			'status'  => __( 'Public', 'super-ninety-four' ),
			'tone'    => 'neutral',
			'url'     => $member['is_mine'] ? $member['edit_url'] : '',
			'action'  => $member['is_mine'] ? __( 'Edit bio', 'super-ninety-four' ) : '',
		);
	}

	$xcards = snf_get_profile_xprofile_cards( $member['user_id'] );
	if ( $xcards ) {
		$cards = array_merge( $cards, $xcards );
	}

	if ( ! $cards ) {
		$cards[] = array(
			'eyebrow' => __( 'About', 'super-ninety-four' ),
			'title'   => __( 'No profile details yet', 'super-ninety-four' ),
			'meta'    => __( 'Add XProfile fields or a bio to populate this panel.', 'super-ninety-four' ),
			'status'  => __( 'Empty', 'super-ninety-four' ),
			'tone'    => 'neutral',
			'url'     => $member['is_mine'] ? $member['edit_url'] : $member['profile_url'],
			'action'  => $member['is_mine'] ? __( 'Complete profile', 'super-ninety-four' ) : __( 'View profile', 'super-ninety-four' ),
		);
	}

	$view_all = $member['profile_url'];
	if ( function_exists( 'bp_displayed_user_domain' ) && function_exists( 'bp_get_profile_slug' ) ) {
		$view_all = trailingslashit( bp_displayed_user_domain() ) . trailingslashit( bp_get_profile_slug() );
	}

	return apply_filters(
		'snf_profile_about_panel',
		array(
			'id'       => 'about',
			'title'    => __( 'About', 'super-ninety-four' ),
			'lede'     => __( 'Bio and extended profile fields.', 'super-ninety-four' ),
			'view_all' => $view_all,
			'cards'    => array_map( 'snf_normalize_panel_card', $cards ),
		)
	);
}

/**
 * Activity / social stubs panel.
 *
 * @return array<string, mixed>
 */
function snf_get_profile_activity_panel() {
	$member = snf_get_displayed_member_context();
	$cards  = array();

	$activity_url = $member['profile_url'];
	if ( function_exists( 'bp_displayed_user_domain' ) && function_exists( 'bp_get_activity_slug' ) && bp_is_active( 'activity' ) ) {
		$activity_url = trailingslashit( bp_displayed_user_domain() ) . trailingslashit( bp_get_activity_slug() );
	}

	$friends_url = $member['profile_url'];
	if ( function_exists( 'bp_displayed_user_domain' ) && function_exists( 'bp_get_friends_slug' ) && bp_is_active( 'friends' ) ) {
		$friends_url = trailingslashit( bp_displayed_user_domain() ) . trailingslashit( bp_get_friends_slug() );
	}

	$cards[] = array(
		'eyebrow' => __( 'Activity', 'super-ninety-four' ),
		'title'   => __( 'Recent updates', 'super-ninety-four' ),
		'meta'    => __( 'Ready for your Figma activity stream layout.', 'super-ninety-four' ),
		'status'  => bp_is_active( 'activity' ) ? __( 'Live', 'super-ninety-four' ) : __( 'Off', 'super-ninety-four' ),
		'tone'    => bp_is_active( 'activity' ) ? 'info' : 'neutral',
		'url'     => $activity_url,
		'action'  => __( 'Open activity', 'super-ninety-four' ),
	);

	$cards[] = array(
		'eyebrow' => __( 'Network', 'super-ninety-four' ),
		'title'   => __( 'Connections', 'super-ninety-four' ),
		'meta'    => __( 'Friends and reciprocal contacts — Figma slot ready.', 'super-ninety-four' ),
		'status'  => bp_is_active( 'friends' ) ? __( 'Friends', 'super-ninety-four' ) : __( 'Soon', 'super-ninety-four' ),
		'tone'    => 'neutral',
		'url'     => $friends_url,
		'action'  => __( 'View network', 'super-ninety-four' ),
	);

	$cards[] = array(
		'eyebrow' => __( 'Inbox', 'super-ninety-four' ),
		'title'   => __( 'Messages', 'super-ninety-four' ),
		'meta'    => __( 'Private threads stay on the theme Messages hub.', 'super-ninety-four' ),
		'status'  => __( 'Hub', 'super-ninety-four' ),
		'tone'    => 'success',
		'url'     => snf_get_bp_messages_url(),
		'action'  => __( 'Open messages', 'super-ninety-four' ),
	);

	return apply_filters(
		'snf_profile_activity_panel',
		array(
			'id'       => 'activity',
			'title'    => __( 'Activity & network', 'super-ninety-four' ),
			'lede'     => __( 'Streams, connections, and inbox entry points.', 'super-ninety-four' ),
			'view_all' => $activity_url,
			'cards'    => array_map( 'snf_normalize_panel_card', $cards ),
		)
	);
}

/**
 * All profile design panels.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_profile_design_panels() {
	$panels = array(
		snf_get_profile_identity_panel(),
		snf_get_profile_about_panel(),
		snf_get_profile_activity_panel(),
	);

	return apply_filters( 'snf_profile_design_panels', $panels );
}

/**
 * Whether the current request is a BuddyPress member single screen.
 *
 * @return bool
 */
function snf_is_bp_member_profile() {
	return function_exists( 'bp_is_user' ) && bp_is_user();
}
