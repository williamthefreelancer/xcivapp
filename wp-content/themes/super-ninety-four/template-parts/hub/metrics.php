<?php
/**
 * Hub metric rail.
 *
 * @var array $args['metrics'] Metric items.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$metrics = isset( $args['metrics'] ) && is_array( $args['metrics'] ) ? $args['metrics'] : array();
if ( ! $metrics ) {
	return;
}
?>
<ul class="snf-hub__metrics" data-figma-slot="hub-metrics">
	<?php foreach ( $metrics as $metric ) : ?>
		<li class="snf-hub__metric">
			<span class="snf-hub__metric-label"><?php echo esc_html( $metric['label'] ); ?></span>
			<span class="snf-hub__metric-value"><?php echo esc_html( $metric['value'] ); ?></span>
			<?php if ( ! empty( $metric['hint'] ) ) : ?>
				<span class="snf-hub__metric-hint"><?php echo esc_html( $metric['hint'] ); ?></span>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
