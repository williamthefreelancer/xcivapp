<?php
/**
 * Renders Figma-ready BuddyPress profile design panels.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$panels = snf_get_profile_design_panels();
if ( ! $panels ) {
	return;
}
?>
<div class="snf-dashboard__panels snf-profile__panels" data-figma-slot="profile-panels">
	<?php foreach ( $panels as $panel ) : ?>
		<?php
		get_template_part(
			'template-parts/dashboard/panel',
			null,
			array(
				'panel' => $panel,
			)
		);
		?>
	<?php endforeach; ?>
</div>
