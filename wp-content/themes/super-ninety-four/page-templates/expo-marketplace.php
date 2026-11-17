<?php
/**
 * Template Name: Expo Marketplace
 * Description: Select events, scenic drives, and gatherings with Mapbox lead rider + walkie.
 */

$is_members = is_page( 'expo-marketplace-by-members' );
$audience   = $is_members ? 'members' : 'non-members';
$rails      = snf_get_expo_catalog_rails( $audience );
$routes     = snf_get_lead_rider_routes();

if ( $is_members ) {
	$title = __( 'Expo Marketplace (members)', 'super-ninety-four' );
	$lede  = __( 'Curated select events, scenic city-to-city drives, and gatherings — not networking. Ride with lead rider William A. Johnson on the road.', 'super-ninety-four' );
	$eyebrow = __( 'Members only', 'super-ninety-four' );
} else {
	$title = __( 'Expo Marketplace (non-members)', 'super-ninety-four' );
	$lede  = __( 'Preview open admission, scenic drives, and private gatherings. Membership unlocks live lead rider tracking and walkie-talkie on the road.', 'super-ninety-four' );
	$eyebrow = __( 'Open preview', 'super-ninety-four' );
}

get_header();
?>
<main class="snf-page snf-dashboard snf-expo" data-figma-ready="true" data-expo-audience="<?php echo esc_attr( $audience ); ?>">
	<section class="snf-dashboard__shell snf-container" aria-label="<?php echo esc_attr( $title ); ?>">

		<div class="snf-dashboard__intro snf-expo__intro" data-figma-slot="expo-intro">
			<p class="snf-hub__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<h1 class="snf-dashboard__intro-title"><?php echo esc_html( $title ); ?></h1>
			<p class="snf-hub__lede"><?php echo esc_html( $lede ); ?></p>
			<div class="snf-dashboard__search" data-figma-slot="expo-search">
				<?php get_template_part( 'template-parts/search', 'events' ); ?>
			</div>
		</div>

		<div class="snf-expo__listings" data-figma-slot="expo-listings">
			<?php foreach ( $rails as $rail ) : ?>
				<?php
				$card_count = count( $rail['cards'] );
				$row_class  = 'snf-dashboard__row snf-expo__row';
				if ( $card_count >= 4 ) {
					$row_class .= ' snf-dashboard__row--four';
				}
				?>
				<section
					class="<?php echo esc_attr( $row_class ); ?>"
					data-listing="<?php echo esc_attr( $rail['id'] ); ?>"
					data-bp-module="<?php echo esc_attr( $rail['bp'] ); ?>"
					aria-labelledby="snf-expo-rail-<?php echo esc_attr( $rail['id'] ); ?>"
				>
					<div class="snf-dashboard__row-head">
						<h2 class="snf-dashboard__row-title" id="snf-expo-rail-<?php echo esc_attr( $rail['id'] ); ?>">
							<?php echo esc_html( $rail['title'] ); ?>
						</h2>
						<a class="snf-dashboard__row-all" href="<?php echo esc_url( snf_get_dashboard_url() ); ?>">
							<?php esc_html_e( 'Back to dashboard', 'super-ninety-four' ); ?>
						</a>
					</div>

					<?php
					get_template_part(
						'template-parts/dashboard/event-cards',
						null,
						array(
							'cards'      => $rail['cards'],
							'empty_text' => $rail['empty_text'],
						)
					);
					?>
				</section>
			<?php endforeach; ?>
		</div>

		<section
			id="snf-expo-lead-rider"
			class="snf-expo__lead"
			aria-labelledby="snf-expo-lead-heading"
			data-figma-slot="expo-lead-rider"
		>
			<div class="snf-expo__lead-copy">
				<p class="snf-hub__eyebrow"><?php esc_html_e( 'Lead rider', 'super-ninety-four' ); ?></p>
				<h2 class="snf-expo__lead-title" id="snf-expo-lead-heading">
					<?php esc_html_e( 'William A. Johnson on the road', 'super-ninety-four' ); ?>
				</h2>
				<p class="snf-hub__lede snf-expo__lead-lede">
					<?php
					echo esc_html(
						$is_members
							? __( 'Follow the scenic route, track your lead rider, and hold to talk on walkie from iPhone, iPad, or Android.', 'super-ninety-four' )
							: __( 'Preview scenic city-to-city routes. Live tracking and walkie-talkie unlock with membership.', 'super-ninety-four' )
					);
					?>
				</p>

				<div class="snf-expo__route-chips" role="list" aria-label="<?php esc_attr_e( 'Scenic routes', 'super-ninety-four' ); ?>">
					<?php foreach ( $routes as $route ) : ?>
						<button
							type="button"
							class="snf-expo__chip"
							role="listitem"
							data-route-id="<?php echo esc_attr( $route['id'] ); ?>"
						>
							<?php echo esc_html( $route['title'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="snf-expo__map-stage">
				<div
					id="snf-expo-map"
					class="snf-expo__map"
					role="region"
					aria-label="<?php esc_attr_e( 'Lead rider map', 'super-ninety-four' ); ?>"
				></div>
				<div class="snf-expo__map-fallback" id="snf-expo-map-fallback" hidden>
					<p class="snf-expo__fallback-text"></p>
				</div>

				<div class="snf-expo__rider-panel">
					<div class="snf-expo__rider-meta">
						<p class="snf-expo__rider-name">William A. Johnson</p>
						<p class="snf-expo__rider-status" id="snf-expo-status" data-status="standby">
							<?php esc_html_e( 'Standby', 'super-ninety-four' ); ?>
						</p>
					</div>

					<?php if ( $is_members ) : ?>
						<div class="snf-expo__walkie">
							<button
								type="button"
								class="snf-expo__ptt"
								id="snf-expo-ptt"
								aria-pressed="false"
							>
								<span class="snf-expo__ptt-label"><?php esc_html_e( 'Hold to talk', 'super-ninety-four' ); ?></span>
							</button>
							<p class="snf-expo__walkie-hint">
								<?php esc_html_e( 'Walkie works on iPhone, iPad, and Android over HTTPS.', 'super-ninety-four' ); ?>
							</p>
							<?php if ( snf_user_can_broadcast_lead_rider() ) : ?>
								<button type="button" class="snf-expo__broadcast" id="snf-expo-broadcast">
									<?php esc_html_e( 'Broadcast my position as lead rider', 'super-ninety-four' ); ?>
								</button>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<div class="snf-expo__gate">
							<p class="snf-expo__gate-copy">
								<?php esc_html_e( 'Membership required for live lead rider & walkie.', 'super-ninety-four' ); ?>
							</p>
							<a class="snf-expo__gate-cta" href="<?php echo esc_url( snf_get_page_url( 'apply' ) ); ?>">
								<?php esc_html_e( 'Apply', 'super-ninety-four' ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>

	</section>
</main>
<?php
get_footer();
