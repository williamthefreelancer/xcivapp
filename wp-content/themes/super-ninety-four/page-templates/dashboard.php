<?php
/**
 * Template Name: Community Dashboard
 * Description: XCIV Social Event Listings dashboard (Figma node 2055:95).
 */

get_header();

$listings = snf_get_dashboard_event_listings();
?>
<main class="snf-page snf-dashboard" data-figma-ready="true">
	<section class="snf-dashboard__shell snf-container" aria-label="<?php esc_attr_e( 'Event listings dashboard', 'super-ninety-four' ); ?>">

		<div class="snf-dashboard__intro" data-figma-slot="dashboard-intro">
			<h1 class="snf-dashboard__intro-title">
				<?php esc_html_e( 'To get started, how can SUPER NINETY FOUR help you?', 'super-ninety-four' ); ?>
			</h1>
			<div class="snf-dashboard__search" data-figma-slot="dashboard-search">
				<?php get_template_part( 'template-parts/search', 'events' ); ?>
			</div>
		</div>

		<div class="snf-dashboard__listings" data-figma-slot="event-listings">
			<?php foreach ( $listings as $section ) : ?>
				<?php
				$card_count = count( $section['cards'] );
				$row_class  = 'snf-dashboard__row';
				if ( $card_count >= 4 ) {
					$row_class .= ' snf-dashboard__row--four';
				}
				?>
				<section
					class="<?php echo esc_attr( $row_class ); ?>"
					data-listing="<?php echo esc_attr( $section['id'] ); ?>"
					data-bp-module="<?php echo esc_attr( $section['bp'] ); ?>"
					aria-labelledby="snf-listing-<?php echo esc_attr( $section['id'] ); ?>"
				>
					<div class="snf-dashboard__row-head">
						<h2 class="snf-dashboard__row-title" id="snf-listing-<?php echo esc_attr( $section['id'] ); ?>">
							<a href="<?php echo esc_url( $section['url'] ); ?>">
								<?php echo esc_html( $section['title'] ); ?>
							</a>
						</h2>
						<a class="snf-dashboard__row-all" href="<?php echo esc_url( $section['url'] ); ?>">
							<?php esc_html_e( 'View all', 'super-ninety-four' ); ?>
						</a>
					</div>

					<?php
					get_template_part(
						'template-parts/dashboard/event-cards',
						null,
						array(
							'cards' => $section['cards'],
						)
					);
					?>
				</section>
			<?php endforeach; ?>
		</div>

	</section>
</main>
<?php
get_footer();
