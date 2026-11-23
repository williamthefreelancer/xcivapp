<?php
/**
 * Dashboard shell: centered main content.
 *
 * @var string $args['main_class'] Extra classes on main column.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$main_class = isset( $args['main_class'] ) ? (string) $args['main_class'] : '';
?>
<section class="snf-dashboard-shell snf-container" data-figma-slot="dashboard-shell" aria-label="<?php esc_attr_e( 'Member dashboard', 'super-ninety-four' ); ?>">
	<div class="snf-dashboard-shell__main <?php echo esc_attr( $main_class ); ?>" data-figma-slot="dashboard-main">
