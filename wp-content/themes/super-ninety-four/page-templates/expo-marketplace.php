<?php
/**
 * FSE php-view: loaded by Site Editor templates via snf/php-view (not a classic page template).
 * Description: Private members Expo directory — vendors & professionals + register event cards.
 */

$is_members = snf_is_expo_members_audience();
$audience   = $is_members ? 'members' : 'non-members';
$rails      = snf_get_expo_catalog_rails( $audience );
$facets     = function_exists( 'snf_get_marketplace_taxonomy_facets' )
	? snf_get_marketplace_taxonomy_facets( $audience, 'expo' )
	: array();
$expo_boot  = function_exists( 'snf_get_expo_js_bootstrap' ) ? snf_get_expo_js_bootstrap( $audience ) : array();
$routes     = isset( $expo_boot['routes'] ) ? $expo_boot['routes'] : array();
$lead_name  = isset( $expo_boot['leadRiderName'] ) ? $expo_boot['leadRiderName'] : '';
$can_live   = ! empty( $expo_boot['canLive'] );
$can_broadcast = ! empty( $expo_boot['canBroadcast'] );
$apply_url  = isset( $expo_boot['applyUrl'] ) ? $expo_boot['applyUrl'] : snf_get_page_url( 'apply' );
$boot_i18n  = isset( $expo_boot['i18n'] ) ? $expo_boot['i18n'] : array();
$title      = __( 'Expo', 'super-ninety-four' );
$eyebrow    = __( 'Private members directory', 'super-ninety-four' );
$lede       = __( 'Vendors and professionals that represent Super Ninety Four — register for the Expo marketplace floor, then browse the private directory.', 'super-ninety-four' );

get_header();
?>
<main class="snf-page snf-expo snf-expo--directory" data-figma-ready="true" data-expo-audience="<?php echo esc_attr( $audience ); ?>" data-expo-surface="directory">
	<section class="snf-expo__shell snf-container" aria-label="<?php echo esc_attr( $title ); ?>">

		<header class="snf-expo__hero" data-figma-slot="expo-intro">
			<p class="snf-hub__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<h1 class="snf-expo__title"><?php echo esc_html( $title ); ?></h1>
			<p class="snf-hub__lede"><?php echo esc_html( $lede ); ?></p>

			<div class="snf-dashboard__search snf-expo__search" data-figma-slot="expo-search">
				<?php get_template_part( 'template-parts/search', 'events' ); ?>
			</div>

			<div
				id="snf-marketplace-chips"
				class="snf-marketplace__chips"
				role="group"
				aria-label="<?php esc_attr_e( 'Filter directory', 'super-ninety-four' ); ?>"
				data-figma-slot="expo-taxonomy-chips"
			>
				<button
					type="button"
					class="snf-marketplace__chip is-active"
					data-product-cat=""
					aria-pressed="true"
				>
					<?php esc_html_e( 'All', 'super-ninety-four' ); ?>
				</button>
				<?php foreach ( $facets as $facet ) : ?>
					<button
						type="button"
						class="snf-marketplace__chip"
						data-product-cat="<?php echo esc_attr( $facet['slug'] ); ?>"
						aria-pressed="false"
					>
						<?php echo esc_html( $facet['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</header>

		<div
			id="snf-marketplace-results"
			class="snf-marketplace__results"
			data-figma-slot="expo-async-results"
			hidden
		></div>

		<div class="snf-expo__listings" data-figma-slot="expo-listings">
			<?php foreach ( $rails as $rail ) : ?>
				<?php
				$track      = isset( $rail['track'] ) ? (string) $rail['track'] : (string) $rail['id'];
				$variant    = ( 'register' === $track ) ? 'register' : 'directory';
				$row_class  = 'snf-expo__row snf-expo-dir__rail';
				if ( 'register' === $variant ) {
					$row_class .= ' snf-expo-dir__rail--register';
				}
				?>
				<section
					class="<?php echo esc_attr( $row_class ); ?>"
					data-listing="<?php echo esc_attr( $rail['id'] ); ?>"
					data-track="<?php echo esc_attr( $track ); ?>"
					aria-labelledby="snf-expo-rail-<?php echo esc_attr( $rail['id'] ); ?>"
				>
					<div class="snf-expo__row-head">
						<h2 class="snf-expo__row-title" id="snf-expo-rail-<?php echo esc_attr( $rail['id'] ); ?>">
							<?php echo esc_html( $rail['title'] ); ?>
						</h2>
						<?php if ( 'register' !== $track ) : ?>
							<a class="snf-expo__row-all" href="<?php echo esc_url( snf_get_dashboard_url() ); ?>">
								<?php esc_html_e( 'Back to dashboard', 'super-ninety-four' ); ?>
							</a>
						<?php endif; ?>
					</div>

					<?php
					get_template_part(
						'template-parts/expo/directory-cards',
						null,
						array(
							'cards'      => $rail['cards'],
							'empty_text' => $rail['empty_text'],
							'variant'    => $variant,
						)
					);
					?>
				</section>
			<?php endforeach; ?>
		</div>

		<section
			id="snf-expo-lead-rider"
			class="snf-expo__lead"
			data-figma-slot="expo-lead-rider"
			aria-labelledby="snf-expo-lead-title"
		>
			<div class="snf-expo__lead-copy">
				<p class="snf-hub__eyebrow"><?php esc_html_e( 'Live from the road', 'super-ninety-four' ); ?></p>
				<h2 class="snf-expo__lead-title" id="snf-expo-lead-title"><?php esc_html_e( 'Ride along with the lead rider', 'super-ninety-four' ); ?></h2>
				<p class="snf-expo__lead-lede"><?php esc_html_e( 'Follow the scenic route in real time and listen in with the walkie-talkie while the club is on the road.', 'super-ninety-four' ); ?></p>

				<?php if ( $routes ) : ?>
					<div class="snf-expo__route-chips" role="group" aria-label="<?php esc_attr_e( 'Choose a route', 'super-ninety-four' ); ?>">
						<?php foreach ( $routes as $route ) : ?>
							<button
								type="button"
								id="snf-expo-lead-rider--<?php echo esc_attr( $route['id'] ); ?>"
								class="snf-expo__chip"
								data-route-id="<?php echo esc_attr( $route['id'] ); ?>"
								aria-pressed="false"
							><?php echo esc_html( $route['title'] ); ?></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="snf-expo__map-stage">
				<div
					id="snf-expo-map"
					class="snf-expo__map"
					role="img"
					aria-label="<?php esc_attr_e( 'Lead rider route map', 'super-ninety-four' ); ?>"
				></div>
				<div id="snf-expo-map-fallback" class="snf-expo__map-fallback" hidden>
					<p class="snf-expo__fallback-text"></p>
				</div>

				<div class="snf-expo__rider-panel">
					<div class="snf-expo__rider">
						<p class="snf-expo__rider-name"><?php echo esc_html( $lead_name ); ?></p>
						<p
							id="snf-expo-status"
							class="snf-expo__rider-status"
							data-status="standby"
						><?php echo esc_html( isset( $boot_i18n['standby'] ) ? $boot_i18n['standby'] : __( 'Standby', 'super-ninety-four' ) ); ?></p>
					</div>

					<?php if ( $can_live ) : ?>
						<div class="snf-expo__walkie">
							<button type="button" id="snf-expo-ptt" class="snf-expo__ptt" aria-pressed="false">
								<span class="snf-expo__ptt-label"><?php echo esc_html( isset( $boot_i18n['holdToTalk'] ) ? $boot_i18n['holdToTalk'] : __( 'Hold to talk', 'super-ninety-four' ) ); ?></span>
							</button>
							<p class="snf-expo__walkie-hint"><?php echo esc_html( isset( $boot_i18n['walkieHint'] ) ? $boot_i18n['walkieHint'] : __( 'Walkie works over HTTPS on iPhone, iPad, and Android.', 'super-ninety-four' ) ); ?></p>
							<?php if ( $can_broadcast ) : ?>
								<button type="button" id="snf-expo-broadcast" class="snf-expo__broadcast"><?php esc_html_e( 'Broadcast my position as lead rider', 'super-ninety-four' ); ?></button>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<div class="snf-expo__gate">
							<p class="snf-expo__gate-copy"><?php echo esc_html( isset( $boot_i18n['membersOnly'] ) ? $boot_i18n['membersOnly'] : __( 'Membership required for live lead rider & walkie.', 'super-ninety-four' ) ); ?></p>
							<a class="snf-expo__gate-cta" href="<?php echo esc_url( $apply_url ); ?>"><?php esc_html_e( 'Apply for membership', 'super-ninety-four' ); ?></a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<section
			class="snf-expo__rfq-host"
			id="snf-expo-rfq-host"
			aria-label="<?php esc_attr_e( 'Request a quote', 'super-ninety-four' ); ?>"
			data-figma-slot="expo-rfq"
		></section>

	</section>
</main>
<?php
get_footer();
