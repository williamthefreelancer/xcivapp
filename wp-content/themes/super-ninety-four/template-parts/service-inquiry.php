<?php
/**
 * Service inquiry landing (Figma 2064:*).
 *
 * @var array<string, mixed> $args {
 *     @type array $service Service content from snf_get_service_inquiry().
 * }
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$service = ( isset( $args['service'] ) && is_array( $args['service'] ) ) ? $args['service'] : array();
if ( ! $service ) {
	return;
}

$title            = isset( $service['title'] ) ? (string) $service['title'] : '';
$lede             = isset( $service['lede'] ) ? (string) $service['lede'] : '';
$image            = isset( $service['image'] ) ? (string) $service['image'] : '';
$benefits_heading = isset( $service['benefits_heading'] ) ? (string) $service['benefits_heading'] : '';
$benefits         = ( isset( $service['benefits'] ) && is_array( $service['benefits'] ) ) ? $service['benefits'] : array();
$price            = isset( $service['price'] ) ? (string) $service['price'] : '$000.00/mo';
$card_description = isset( $service['card_description'] ) ? (string) $service['card_description'] : '';
$workshop_url     = isset( $service['workshop_url'] ) ? (string) $service['workshop_url'] : home_url( '/' );
$inquiry_url      = isset( $service['inquiry_url'] ) ? (string) $service['inquiry_url'] : snf_get_page_url( 'apply' );
?>
<main class="snf-page snf-service" data-figma-ready="true">
	<section class="snf-service__hero snf-container" data-figma-slot="service-hero">
		<div class="snf-service__hero-panel">
			<?php if ( $image ) : ?>
				<figure class="snf-service__hero-media">
					<img
						src="<?php echo esc_url( $image ); ?>"
						alt="<?php echo esc_attr( $title ); ?>"
						width="1200"
						height="675"
						decoding="async"
					>
				</figure>
			<?php endif; ?>
			<div class="snf-service__hero-copy">
				<h1 class="snf-service__heading"><?php echo esc_html( $title ); ?></h1>
				<?php if ( $lede ) : ?>
					<p class="snf-service__lede"><?php echo esc_html( $lede ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="snf-service__benefits snf-container" data-figma-slot="service-benefits" aria-labelledby="snf-service-benefits-heading">
		<h2 id="snf-service-benefits-heading" class="snf-service__heading snf-service__heading--section">
			<?php echo esc_html( $benefits_heading ); ?>
		</h2>
		<?php if ( $benefits ) : ?>
			<ul class="snf-service__benefit-list">
				<?php foreach ( $benefits as $benefit ) : ?>
					<li class="snf-service__benefit"><?php echo esc_html( (string) $benefit ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="snf-service__pricing snf-container" data-figma-slot="service-pricing" aria-labelledby="snf-service-pricing-heading">
		<h2 id="snf-service-pricing-heading" class="snf-service__heading snf-service__heading--section">
			<?php echo esc_html( $title ); ?>
		</h2>
		<article class="snf-service__card">
			<h3 class="snf-service__card-title"><?php echo esc_html( $title ); ?></h3>
			<p class="snf-service__price"><?php echo esc_html( $price ); ?></p>
			<?php if ( $card_description ) : ?>
				<p class="snf-service__card-desc"><?php echo esc_html( $card_description ); ?></p>
			<?php endif; ?>
			<a class="snf-service__button" href="<?php echo esc_url( $workshop_url ); ?>">
				<?php esc_html_e( 'Attend Online Workshop', 'super-ninety-four' ); ?>
			</a>
			<a class="snf-service__inquiry" href="<?php echo esc_url( $inquiry_url ); ?>">
				<?php esc_html_e( 'Apply by Inquiry', 'super-ninety-four' ); ?>
			</a>
		</article>
	</section>
</main>
