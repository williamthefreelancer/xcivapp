<?php
/**
 * Service quote landing (Figma quote-single-page 2166:165).
 *
 * @package Super_Ninety_Four
 * @var array<string, mixed> $args {
 *     @type array $quote Quote content from snf_get_service_quote().
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quote = ( isset( $args['quote'] ) && is_array( $args['quote'] ) ) ? $args['quote'] : array();
if ( ! $quote ) {
	return;
}

$title            = isset( $quote['title'] ) ? (string) $quote['title'] : '';
$lede             = isset( $quote['lede'] ) ? (string) $quote['lede'] : '';
$image            = isset( $quote['image'] ) ? (string) $quote['image'] : '';
$can_heading      = isset( $quote['can_heading'] ) ? (string) $quote['can_heading'] : '';
$can_items        = ( isset( $quote['can_items'] ) && is_array( $quote['can_items'] ) ) ? $quote['can_items'] : array();
$cannot_heading   = isset( $quote['cannot_heading'] ) ? (string) $quote['cannot_heading'] : '';
$cannot_items     = ( isset( $quote['cannot_items'] ) && is_array( $quote['cannot_items'] ) ) ? $quote['cannot_items'] : array();
$pricing_heading  = isset( $quote['pricing_heading'] ) ? (string) $quote['pricing_heading'] : '';
$plan_title       = isset( $quote['plan_title'] ) ? (string) $quote['plan_title'] : '';
$price            = isset( $quote['price'] ) ? (string) $quote['price'] : '';
$card_description = isset( $quote['card_description'] ) ? (string) $quote['card_description'] : '';
$cta_label        = isset( $quote['cta_label'] ) ? (string) $quote['cta_label'] : __( 'Get Quote Now', 'super-ninety-four' );

/*
 * Mark odd-length lists so the final item can stay in the normal grid flow
 * instead of relying on a hardcoded :nth-child(5) selector.
 */
$can_list_class    = 0 !== count( $can_items ) % 2 ? ' snf-quote__list--odd' : '';
$cannot_list_class = 0 !== count( $cannot_items ) % 2 ? ' snf-quote__list--odd' : '';

$quote_slug       = get_post_field( 'post_name', get_the_ID() );
$cta_url          = isset( $quote['cta_url'] ) ? (string) $quote['cta_url'] : '';
if ( ! $cta_url && function_exists( 'snf_get_service_quote_cta_url' ) ) {
	$cta_url = snf_get_service_quote_cta_url( (string) $quote_slug );
}
if ( ! $cta_url && function_exists( 'wc_get_cart_url' ) && get_the_ID() ) {
	$cta_url = add_query_arg( 'add-to-cart', (int) get_the_ID(), wc_get_cart_url() );
}
if ( ! $cta_url ) {
	$cta_url = get_permalink();
}
if ( function_exists( 'wc_get_product' ) && get_the_ID() ) {
	$product = wc_get_product( get_the_ID() );
	if ( $product && $product->get_price() !== '' ) {
		$price_html = wp_strip_all_tags( $product->get_price_html() );
		if ( $price_html ) {
			$price = $price_html;
			if ( false === stripos( $price, '/mo' ) ) {
				$price .= '/mo';
			}
		}
	}
	if ( $product && $product->get_image_id() ) {
		$thumb = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
		if ( $thumb ) {
			$image = $thumb;
		}
	}
}
?>
<main class="snf-quote" data-figma-ready="true" data-figma-node="2166:165">
	<?php
	if ( function_exists( 'wc_print_notices' ) ) {
		ob_start();
		wc_print_notices();
		$notices = trim( (string) ob_get_clean() );
		if ( '' !== $notices ) {
			echo '<div class="snf-quote__notices snf-container">' . $notices . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	?>

	<section class="snf-quote__hero snf-container" data-figma-slot="quote-hero">
		<div class="snf-quote__hero-panel">
			<?php if ( $image ) : ?>
				<figure class="snf-quote__hero-media">
					<img
						src="<?php echo esc_url( $image ); ?>"
						alt="<?php echo esc_attr( $title ); ?>"
						width="912"
						height="451"
						decoding="async"
					>
				</figure>
			<?php endif; ?>
			<div class="snf-quote__hero-copy">
				<h1 class="snf-quote__title"><?php echo esc_html( $title ); ?></h1>
				<?php if ( $lede ) : ?>
					<p class="snf-quote__lede"><?php echo esc_html( $lede ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="snf-quote__section snf-container" data-figma-slot="quote-can" aria-labelledby="snf-quote-can-heading">
		<h2 id="snf-quote-can-heading" class="snf-quote__heading">
			<?php echo esc_html( $can_heading ); ?>
		</h2>
		<?php if ( $can_items ) : ?>
			<ul class="snf-quote__list<?php echo esc_attr( $can_list_class ); ?>">
				<?php foreach ( $can_items as $item ) : ?>
					<li class="snf-quote__item"><?php echo esc_html( (string) $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="snf-quote__section snf-container" data-figma-slot="quote-cannot" aria-labelledby="snf-quote-cannot-heading">
		<h2 id="snf-quote-cannot-heading" class="snf-quote__heading">
			<?php echo esc_html( $cannot_heading ); ?>
		</h2>
		<?php if ( $cannot_items ) : ?>
			<ul class="snf-quote__list<?php echo esc_attr( $cannot_list_class ); ?>">
				<?php foreach ( $cannot_items as $item ) : ?>
					<li class="snf-quote__item"><?php echo esc_html( (string) $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="snf-quote__pricing snf-container" data-figma-slot="quote-pricing" aria-labelledby="snf-quote-pricing-heading">
		<h2 id="snf-quote-pricing-heading" class="snf-quote__heading">
			<?php echo esc_html( $pricing_heading ); ?>
		</h2>
		<article class="snf-quote__card">
			<h3 class="snf-quote__plan"><?php echo esc_html( $plan_title ); ?></h3>
			<p class="snf-quote__price"><?php echo esc_html( $price ); ?></p>
			<?php if ( $card_description ) : ?>
				<p class="snf-quote__card-desc"><?php echo esc_html( $card_description ); ?></p>
			<?php endif; ?>
			<a class="snf-quote__button" href="<?php echo esc_url( $cta_url ); ?>">
				<?php echo esc_html( $cta_label ); ?>
			</a>
		</article>
	</section>
</main>
