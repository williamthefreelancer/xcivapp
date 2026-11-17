<?php
/**
 * BuddyPress — Members Home (Figma-ready shell content).
 *
 * @package SuperNinetyFour
 * @version 3.0.0
 */
?>

<?php bp_nouveau_member_hook( 'before', 'home_content' ); ?>

<div
	id="item-header"
	role="complementary"
	data-bp-item-id="<?php echo esc_attr( (string) bp_displayed_user_id() ); ?>"
	data-bp-item-component="members"
	class="users-header single-headers snf-profile-header"
	data-figma-slot="profile-header"
>
	<?php bp_nouveau_member_header_template_part(); ?>
</div><!-- #item-header -->

<div class="bp-wrap snf-profile-wrap" data-figma-slot="profile-wrap">
	<?php if ( ! bp_nouveau_is_object_nav_in_sidebar() ) : ?>
		<nav class="snf-profile-bp-nav" data-figma-slot="profile-bp-nav" aria-label="<?php esc_attr_e( 'Profile sections', 'super-ninety-four' ); ?>">
			<?php bp_get_template_part( 'members/single/parts/item-nav' ); ?>
		</nav>
	<?php endif; ?>

	<div id="item-body" class="item-body snf-profile-body" data-figma-slot="profile-body">
		<?php bp_nouveau_member_template_part(); ?>
	</div><!-- #item-body -->
</div><!-- // .bp-wrap -->

<?php bp_nouveau_member_hook( 'after', 'home_content' ); ?>
