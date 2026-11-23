<?php
/**
 * BuddyPress — Members Home (Figma-ready shell content).
 *
 * @package SuperNinetyFour
 * @version 3.0.0
 */

$snf_bp_nouveau = function_exists( 'bp_nouveau_member_hook' );
?>

<?php if ( $snf_bp_nouveau ) : ?>
	<?php bp_nouveau_member_hook( 'before', 'home_content' ); ?>
<?php endif; ?>

<div
	id="item-header"
	role="complementary"
	data-bp-item-id="<?php echo esc_attr( (string) bp_displayed_user_id() ); ?>"
	data-bp-item-component="members"
	class="users-header single-headers snf-profile-header"
	data-figma-slot="profile-header"
>
	<?php
	if ( $snf_bp_nouveau && function_exists( 'bp_nouveau_member_header_template_part' ) ) {
		bp_nouveau_member_header_template_part();
	} else {
		bp_get_template_part( 'members/single/member-header' );
	}
	?>
</div><!-- #item-header -->

<div class="bp-wrap snf-profile-wrap" data-figma-slot="profile-wrap">
	<?php
	$snf_show_nav = true;
	if ( $snf_bp_nouveau && function_exists( 'bp_nouveau_is_object_nav_in_sidebar' ) ) {
		$snf_show_nav = ! bp_nouveau_is_object_nav_in_sidebar();
	}
	?>
	<?php if ( $snf_show_nav ) : ?>
		<nav class="snf-profile-bp-nav" data-figma-slot="profile-bp-nav" aria-label="<?php esc_attr_e( 'Profile sections', 'super-ninety-four' ); ?>">
			<?php bp_get_template_part( 'members/single/parts/item-nav' ); ?>
		</nav>
	<?php endif; ?>

	<div id="item-body" class="item-body snf-profile-body" data-figma-slot="profile-body">
		<?php
		if ( $snf_bp_nouveau && function_exists( 'bp_nouveau_member_template_part' ) ) {
			bp_nouveau_member_template_part();
		} else {
			bp_get_template_part( 'members/single/plugins' );
		}
		?>
	</div><!-- #item-body -->
</div><!-- // .bp-wrap -->

<?php if ( $snf_bp_nouveau ) : ?>
	<?php bp_nouveau_member_hook( 'after', 'home_content' ); ?>
<?php endif; ?>
