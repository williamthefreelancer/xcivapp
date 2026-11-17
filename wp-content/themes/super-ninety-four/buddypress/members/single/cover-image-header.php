<?php
/**
 * BuddyPress — Users Cover Image Header (Figma-ready).
 *
 * @package SuperNinetyFour
 * @version 12.0.0
 */

$member = snf_get_displayed_member_context();
?>

<div id="cover-image-container" class="snf-profile-cover" data-figma-slot="profile-cover">
	<div
		id="header-cover-image"
		class="snf-profile-cover__media"
		<?php if ( ! empty( $member['cover'] ) ) : ?>
			style="background-image: url('<?php echo esc_url( $member['cover'] ); ?>');"
		<?php endif; ?>
	></div>

	<div id="item-header-cover-image" class="snf-profile-cover__inner">
		<?php bp_get_template_part( 'members/single/member-header' ); ?>
	</div><!-- #item-header-cover-image -->
</div><!-- #cover-image-container -->
