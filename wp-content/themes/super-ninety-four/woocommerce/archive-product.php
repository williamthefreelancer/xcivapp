<?php
/**
 * WooCommerce product archive — Google-style SERP for Events & Services.
 *
 * @package SuperNinetyFour
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$args          = snf_get_serp_request_args();
$query         = $args['q'];
$result_count  = (int) $GLOBALS['wp_query']->found_posts;
$display_query = $query ? $query : __( 'events & services', 'super-ninety-four' );
$shop_url      = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$audience      = function_exists( 'snf_get_marketplace_audience' ) ? snf_get_marketplace_audience() : 'non-members';
$facets        = function_exists( 'snf_get_marketplace_taxonomy_facets' )
	? snf_get_marketplace_taxonomy_facets( $audience )
	: ( function_exists( 'snf_get_serp_product_categories' ) ? snf_get_serp_product_categories() : array() );
$active_cat    = isset( $_GET['product_cat'] ) ? sanitize_title( wp_unslash( $_GET['product_cat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<main class="snf-page snf-archive" data-marketplace-audience="<?php echo esc_attr( $audience ); ?>">
	<section class="snf-page__frame snf-container" aria-label="<?php esc_attr_e( 'Search events and services', 'super-ninety-four' ); ?>">
		<div class="snf-archive__search">
			<?php get_template_part( 'template-parts/search', 'events' ); ?>
		</div>
		<div
			id="snf-marketplace-chips"
			class="snf-marketplace__chips snf-marketplace__chips--shop"
			role="group"
			aria-label="<?php esc_attr_e( 'Filter by category', 'super-ninety-four' ); ?>"
		>
			<button
				type="button"
				class="snf-marketplace__chip<?php echo '' === $active_cat ? ' is-active' : ''; ?>"
				data-product-cat=""
				aria-pressed="<?php echo '' === $active_cat ? 'true' : 'false'; ?>"
			>
				<?php esc_html_e( 'All', 'super-ninety-four' ); ?>
			</button>
			<?php foreach ( $facets as $facet ) : ?>
				<?php
				$slug  = is_array( $facet ) ? (string) $facet['slug'] : (string) $facet;
				$label = is_array( $facet ) ? (string) $facet['label'] : (string) $facet;
				$on    = $active_cat === $slug;
				?>
				<button
					type="button"
					class="snf-marketplace__chip<?php echo $on ? ' is-active' : ''; ?>"
					data-product-cat="<?php echo esc_attr( $slug ); ?>"
					aria-pressed="<?php echo $on ? 'true' : 'false'; ?>"
				>
					<?php echo esc_html( $label ); ?>
				</button>
			<?php endforeach; ?>
		</div>

		<div class="snf-serp">
			<p class="snf-serp__stats">
				<?php
				printf(
					esc_html( _n( 'About %1$s result for %2$s', 'About %1$s results for %2$s', $result_count, 'super-ninety-four' ) ),
					number_format_i18n( $result_count ),
					'“' . esc_html( $display_query ) . '”'
				);
				?>
			</p>

			<?php if ( woocommerce_product_loop() ) : ?>
				<ol class="snf-serp__list">
					<?php
					while ( have_posts() ) :
						the_post();
						$product = wc_get_product( get_the_ID() );
						if ( ! $product ) {
							continue;
						}
						$row = snf_get_product_serp_row( $product );
						?>
						<li class="snf-serp__item">
							<article class="snf-result">
								<a class="snf-result__hit" href="<?php echo esc_url( $row['url'] ); ?>">
									<div class="snf-result__source">
										<span class="snf-result__favicon" aria-hidden="true">
											<?php if ( ! empty( $row['favicon'] ) ) : ?>
												<img src="<?php echo esc_url( $row['favicon'] ); ?>" alt="" width="28" height="28">
											<?php endif; ?>
										</span>
										<span class="snf-result__source-text">
											<span class="snf-result__site"><?php bloginfo( 'name' ); ?></span>
											<span class="snf-result__crumb"><?php echo esc_html( $row['crumb'] ); ?></span>
										</span>
									</div>
									<h2 class="snf-result__title"><?php echo esc_html( $row['title'] ); ?></h2>
								</a>
								<?php if ( $row['snippet'] ) : ?>
									<p class="snf-result__snippet"><?php echo esc_html( $row['snippet'] ); ?></p>
								<?php endif; ?>
								<?php if ( $row['meta'] ) : ?>
									<p class="snf-result__meta"><?php echo esc_html( $row['meta'] ); ?></p>
								<?php endif; ?>
								<p class="snf-result__action">
									<a href="<?php echo esc_url( $row['url'] ); ?>">
										<?php echo esc_html( $row['action'] ); ?>
									</a>
								</p>
							</article>
						</li>
					<?php endwhile; ?>
				</ol>
			<?php else : ?>
				<div class="snf-serp__empty">
					<p><?php esc_html_e( 'No listings matched that search.', 'super-ninety-four' ); ?></p>
					<p><a class="snf-archive__admission-link" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'View all listings', 'super-ninety-four' ); ?></a></p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer( 'shop' );
