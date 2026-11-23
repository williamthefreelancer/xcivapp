<?php
/**
 * Renders all Figma-ready dashboard design panels.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$panels = snf_get_dashboard_design_panels();
if ( ! $panels ) {
	return;
}
?>
<div class="snf-dashboard__panels" data-figma-slot="dashboard-panels">
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
