<?php
/**
 * BP Nouveau default member front — Figma design panels.
 *
 * @package SuperNinetyFour
 * @version 3.1.0
 */
?>

<div class="member-front-page snf-profile-front" data-figma-slot="profile-front">
	<?php get_template_part( 'template-parts/dashboard/profile-panels' ); ?>

	<?php if ( ! is_customize_preview() && bp_current_user_can( 'bp_moderate' ) && ! is_active_sidebar( 'sidebar-buddypress-members' ) ) : ?>
		<div class="bp-feedback custom-homepage-info info snf-profile-front__admin" data-figma-slot="profile-front-admin">
			<strong><?php esc_html_e( 'Manage the members default front page', 'buddypress' ); ?></strong>
			<button type="button" class="bp-tooltip" data-bp-tooltip="<?php echo esc_attr_x( 'Close', 'button', 'buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Close this notice', 'buddypress' ); ?>" data-bp-close="remove"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span></button><br/>
			<?php if ( function_exists( 'bp_nouveau_members_get_customizer_option_link' ) && function_exists( 'bp_nouveau_members_get_customizer_widgets_link' ) ) : ?>
				<?php
				printf(
					/* translators: 1: link to the customizer option. 2: link to the customizer widgets section. */
					esc_html__( 'You can set the preferences of the %1$s or add %2$s to it.', 'buddypress' ),
					bp_nouveau_members_get_customizer_option_link(), // phpcs:ignore WordPress.Security.EscapeOutput
					bp_nouveau_members_get_customizer_widgets_link() // phpcs:ignore WordPress.Security.EscapeOutput
				);
				?>
			<?php else : ?>
				<?php esc_html_e( 'Enable the BuddyPress Nouveau template pack to manage this front page in the Customizer.', 'super-ninety-four' ); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( is_active_sidebar( 'sidebar-buddypress-members' ) ) : ?>
		<div id="member-front-widgets" class="bp-sidebar bp-widget-area" role="complementary" data-figma-slot="profile-front-widgets">
			<?php dynamic_sidebar( 'sidebar-buddypress-members' ); ?>
		</div>
	<?php endif; ?>
</div>
