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
			class="snf-expo__rfq-host"
			id="snf-expo-rfq-host"
			aria-label="<?php esc_attr_e( 'Request a quote', 'super-ninety-four' ); ?>"
			data-figma-slot="expo-rfq"
		></section>

	</section>
</main>
<?php
get_footer();
