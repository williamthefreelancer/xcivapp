<?php
/**
 * Events archive — Google-style results for Super Ninety Four listings.
 */
get_header();

$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

$args = array(
	'post_type'      => 'event',
	'post_status'    => 'publish',
	'posts_per_page' => 20,
	'orderby'        => 'title',
	'order'          => 'ASC',
);

if ( $query ) {
	$args['s'] = $query;
}

$events_query = new WP_Query( $args );
$service_results = snf_get_service_inquiry_search_results( $query );
$result_count  = (int) $events_query->found_posts + count( $service_results );
$display_query = $query ? $query : __( 'events & services', 'super-ninety-four' );
?>
<main class="snf-page snf-archive">
	<section class="snf-page__frame snf-container" aria-label="<?php esc_attr_e( 'Search events and services', 'super-ninety-four' ); ?>">
		<div class="snf-archive__search">
			<?php get_template_part( 'template-parts/search', 'events' ); ?>
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

			<?php if ( $events_query->have_posts() || $service_results ) : ?>
				<ol class="snf-serp__list">
					<?php
					while ( $events_query->have_posts() ) :
						$events_query->the_post();
						$admission_url = snf_get_event_action_url( get_the_ID() );
						$location = get_post_meta( get_the_ID(), 'snf_event_location', true );
						$timing   = get_post_meta( get_the_ID(), 'snf_event_when', true );
						$icon     = get_post_meta( get_the_ID(), 'snf_event_icon', true );
						$snippet  = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content() ), 28 );
						$crumb    = 'superninetyfour.com › events › ' . get_post_field( 'post_name', get_the_ID() );
						?>
						<li class="snf-serp__item">
							<article class="snf-result">
								<a class="snf-result__hit" href="<?php echo esc_url( $admission_url ); ?>">
									<div class="snf-result__source">
										<span class="snf-result__favicon" aria-hidden="true">
											<?php if ( $icon ) : ?>
												<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/events/' . $icon ); ?>" alt="" width="28" height="28">
											<?php else : ?>
												<span class="snf-result__favicon-fallback"><?php echo esc_html( mb_substr( get_the_title(), 0, 1 ) ); ?></span>
											<?php endif; ?>
										</span>
										<span class="snf-result__source-text">
											<span class="snf-result__site"><?php bloginfo( 'name' ); ?></span>
											<span class="snf-result__crumb"><?php echo esc_html( $crumb ); ?></span>
										</span>
									</div>
									<h2 class="snf-result__title"><?php the_title(); ?></h2>
								</a>
								<p class="snf-result__snippet"><?php echo esc_html( $snippet ); ?></p>
								<?php if ( $location || $timing ) : ?>
									<p class="snf-result__meta">
										<?php echo esc_html( trim( implode( ' · ', array_filter( array( $location, $timing ) ) ) ) ); ?>
									</p>
								<?php endif; ?>
								<p class="snf-result__action">
									<a href="<?php echo esc_url( $admission_url ); ?>">
										<?php echo esc_html( snf_get_event_action_label( get_the_ID() ) ); ?>
									</a>
								</p>
							</article>
						</li>
					<?php endwhile; ?>

					<?php foreach ( $service_results as $service ) : ?>
						<li class="snf-serp__item">
							<article class="snf-result">
								<a class="snf-result__hit" href="<?php echo esc_url( $service['url'] ); ?>">
									<div class="snf-result__source">
										<span class="snf-result__favicon" aria-hidden="true">
											<span class="snf-result__favicon-fallback"><?php echo esc_html( mb_substr( $service['title'], 0, 1 ) ); ?></span>
										</span>
										<span class="snf-result__source-text">
											<span class="snf-result__site"><?php bloginfo( 'name' ); ?></span>
											<span class="snf-result__crumb"><?php echo esc_html( $service['crumb'] ); ?></span>
										</span>
									</div>
									<h2 class="snf-result__title"><?php echo esc_html( $service['title'] ); ?></h2>
								</a>
								<p class="snf-result__snippet"><?php echo esc_html( $service['snippet'] ); ?></p>
								<?php if ( ! empty( $service['meta'] ) ) : ?>
									<p class="snf-result__meta"><?php echo esc_html( $service['meta'] ); ?></p>
								<?php endif; ?>
								<p class="snf-result__action">
									<a href="<?php echo esc_url( $service['url'] ); ?>">
										<?php echo esc_html( $service['action'] ); ?>
									</a>
								</p>
							</article>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php else : ?>
				<div class="snf-serp__empty">
					<p><?php esc_html_e( 'No events matched that search.', 'super-ninety-four' ); ?></p>
					<p><a class="snf-archive__admission-link" href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"><?php esc_html_e( 'View all events', 'super-ninety-four' ); ?></a></p>
				</div>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	</section>
</main>
<?php
get_footer();
