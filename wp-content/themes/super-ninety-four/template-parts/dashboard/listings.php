<?php
/**
 * Dashboard intro + listing sliders.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$listings = snf_get_dashboard_event_listings();
?>
<div class="snf-dashboard__intro" data-figma-slot="dashboard-intro">
	<h1 class="snf-dashboard__intro-title">
		<?php esc_html_e( 'To get started, how can SUPER NINETY FOUR help you?', 'super-ninety-four' ); ?>
	</h1>
</div>

<div class="snf-dashboard__listings" data-figma-slot="event-listings">
	<?php foreach ( $listings as $section ) : ?>
		<section
			class="snf-dashboard__row snf-dashboard__row--slider"
			data-listing="<?php echo esc_attr( $section['id'] ); ?>"
			data-bp-module="<?php echo esc_attr( $section['bp'] ); ?>"
			data-figma-slot="listing-slider"
			aria-labelledby="snf-listing-<?php echo esc_attr( $section['id'] ); ?>"
		>
			<div class="snf-dashboard__row-head">
				<h2 class="snf-dashboard__row-title" id="snf-listing-<?php echo esc_attr( $section['id'] ); ?>">
					<a href="<?php echo esc_url( $section['url'] ); ?>">
						<?php echo esc_html( $section['title'] ); ?>
					</a>
				</h2>
				<div class="snf-dashboard__row-actions">
					<button
						type="button"
						class="snf-dashboard__slider-btn snf-dashboard__slider-btn--prev"
						data-slider-prev
						aria-label="<?php esc_attr_e( 'Previous listings', 'super-ninety-four' ); ?>"
					>
						<span aria-hidden="true">‹</span>
					</button>
					<button
						type="button"
						class="snf-dashboard__slider-btn snf-dashboard__slider-btn--next"
						data-slider-next
						aria-label="<?php esc_attr_e( 'Next listings', 'super-ninety-four' ); ?>"
					>
						<span aria-hidden="true">›</span>
					</button>
					<a class="snf-dashboard__row-all" href="<?php echo esc_url( $section['url'] ); ?>">
						<?php esc_html_e( 'View all', 'super-ninety-four' ); ?>
					</a>
				</div>
			</div>

			<?php
			get_template_part(
				'template-parts/dashboard/event-cards',
				null,
				array(
					'cards'  => $section['cards'],
					'slider' => true,
				)
			);
			?>
		</section>
	<?php endforeach; ?>
</div>
