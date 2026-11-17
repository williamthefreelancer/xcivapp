<?php
/**
 * BuddyPress — Members Profile Loop (Figma-ready field panels + legacy table).
 *
 * @package SuperNinetyFour
 * @version 3.1.0
 */
?>

<h2 class="screen-heading view-profile-screen"><?php esc_html_e( 'View Profile', 'buddypress' ); ?></h2>

<?php get_template_part( 'template-parts/dashboard/profile-panels' ); ?>

<?php bp_nouveau_xprofile_hook( 'before', 'loop_content' ); ?>

<?php if ( bp_has_profile() ) : ?>

	<div class="snf-account-endpoint__legacy snf-profile-fields" data-figma-slot="profile-fields-legacy" aria-label="<?php esc_attr_e( 'Profile fields', 'super-ninety-four' ); ?>">
		<?php
		while ( bp_profile_groups() ) :
			bp_the_profile_group();
			?>

			<?php if ( bp_profile_group_has_fields() ) : ?>

				<?php bp_nouveau_xprofile_hook( 'before', 'field_content' ); ?>

				<div class="bp-widget <?php bp_the_profile_group_slug(); ?>" data-figma-slot="profile-field-group">

					<h3 class="screen-heading profile-group-title">
						<?php bp_the_profile_group_name(); ?>
					</h3>

					<table class="profile-fields bp-tables-user">

						<?php
						while ( bp_profile_fields() ) :
							bp_the_profile_field();
							?>

							<?php if ( bp_field_has_data() ) : ?>

								<tr<?php bp_field_css_class(); ?>>
									<td class="label"><?php bp_the_profile_field_name(); ?></td>
									<td class="data"><?php bp_the_profile_field_value(); ?></td>
								</tr>

							<?php endif; ?>

							<?php bp_nouveau_xprofile_hook( '', 'field_item' ); ?>

						<?php endwhile; ?>

					</table>
				</div>

				<?php bp_nouveau_xprofile_hook( 'after', 'field_content' ); ?>

			<?php endif; ?>

		<?php endwhile; ?>
	</div>

<?php else : ?>

	<div class="snf-account-endpoint__legacy">
		<p><?php esc_html_e( 'No public profile fields are available yet.', 'super-ninety-four' ); ?></p>
	</div>

<?php endif; ?>

<?php bp_nouveau_xprofile_hook( 'after', 'loop_content' ); ?>
