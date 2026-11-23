<?php
/**
 * Dashboard design panel (billing / schedules / orders).
 *
 * @var array $args['panel'] Panel definition from snf_get_dashboard_*_panel().
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$panel = isset( $args['panel'] ) && is_array( $args['panel'] ) ? $args['panel'] : array();
if ( ! $panel ) {
	return;
}

$id       = isset( $panel['id'] ) ? sanitize_title( $panel['id'] ) : 'panel';
$title    = isset( $panel['title'] ) ? (string) $panel['title'] : '';
$lede     = isset( $panel['lede'] ) ? (string) $panel['lede'] : '';
$view_all = isset( $panel['view_all'] ) ? (string) $panel['view_all'] : '';
$cards    = isset( $panel['cards'] ) && is_array( $panel['cards'] ) ? $panel['cards'] : array();
$heading  = 'snf-panel-' . $id . '-title';
?>
<section
	class="snf-panel snf-panel--<?php echo esc_attr( $id ); ?>"
	data-panel="<?php echo esc_attr( $id ); ?>"
	data-figma-slot="dashboard-panel-<?php echo esc_attr( $id ); ?>"
	aria-labelledby="<?php echo esc_attr( $heading ); ?>"
>
	<header class="snf-panel__head" data-figma-slot="panel-head">
		<div class="snf-panel__copy">
			<h2 class="snf-panel__title" id="<?php echo esc_attr( $heading ); ?>"><?php echo esc_html( $title ); ?></h2>
			<?php if ( $lede ) : ?>
				<p class="snf-panel__lede"><?php echo esc_html( $lede ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $view_all ) : ?>
			<a class="snf-panel__all" href="<?php echo esc_url( $view_all ); ?>">
				<?php esc_html_e( 'View all', 'super-ninety-four' ); ?>
			</a>
		<?php endif; ?>
	</header>

	<ul class="snf-panel__grid" data-figma-slot="panel-grid">
		<?php foreach ( $cards as $card ) : ?>
			<li class="snf-panel__item">
				<?php
				get_template_part(
					'template-parts/dashboard/panel',
					'card',
					array(
						'card' => $card,
					)
				);
				?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
