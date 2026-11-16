<?php
/**
 * Template Name: Reciprocal Hub
 * Description: Index of reciprocal club landings.
 */

get_header();

$data  = snf_get_reciprocal_hub_data();
$clubs = isset( $data['clubs'] ) ? $data['clubs'] : array();
$title = get_the_title() ? get_the_title() : $data['title'];
?>
<main class="snf-page snf-reciprocal snf-reciprocal--hub" data-figma-ready="true">
	<section class="snf-reciprocal__hub-hero snf-container">
		<p class="snf-reciprocal__eyebrow"><?php esc_html_e( 'Private circuit', 'super-ninety-four' ); ?></p>
		<h1 class="snf-reciprocal__hub-title"><?php echo esc_html( $title ); ?></h1>
		<p class="snf-reciprocal__hub-lede"><?php echo esc_html( $data['lede'] ); ?></p>
	</section>

	<section class="snf-reciprocal__hub-grid snf-container" aria-label="<?php esc_attr_e( 'Reciprocal clubs', 'super-ninety-four' ); ?>">
		<?php foreach ( $clubs as $club ) : ?>
			<article class="snf-reciprocal__hub-card">
				<a class="snf-reciprocal__hub-link" href="<?php echo esc_url( snf_get_page_url( $club['slug'] ) ); ?>">
					<figure class="snf-reciprocal__hub-media">
						<img
							src="<?php echo esc_url( $club['card_image'] ); ?>"
							alt="<?php echo esc_attr( $club['title'] ); ?>"
							width="800"
							height="500"
							decoding="async"
							loading="lazy"
						>
					</figure>
					<div class="snf-reciprocal__hub-copy">
						<h2 class="snf-reciprocal__hub-name"><?php echo esc_html( $club['title'] ); ?></h2>
						<p class="snf-reciprocal__hub-meta"><?php echo esc_html( wp_html_excerpt( $club['lede'], 120, '…' ) ); ?></p>
						<span class="snf-reciprocal__hub-cta"><?php esc_html_e( 'View club', 'super-ninety-four' ); ?></span>
					</div>
				</a>
			</article>
		<?php endforeach; ?>
	</section>
</main>
<?php
get_footer();
