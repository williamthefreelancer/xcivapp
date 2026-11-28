<?php
/**
 * Title: Service inquiry listings
 * slug: super-ninety-four/listings-service-inquiries
 * categories: snf, snf-listings
 * keywords: services, inquiries, listings, grid
 * viewportWidth: 1200
 * Description: Listing grid fed by theme service-inquiry cards.
 */
?>
<!-- wp:group {"className":"snf-pattern snf-pattern--listings","layout":{"type":"constrained"}} -->
<div class="wp-block-group snf-pattern snf-pattern--listings">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading"><?php echo esc_html__( 'Service inquiries', 'super-ninety-four' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"is-style-lede"} -->
	<p class="is-style-lede"><?php echo esc_html__( 'Request a quote for litter pickup or line sitting & courier.', 'super-ninety-four' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:snf/listing-grid {"source":"service-inquiries","align":"wide"} /-->
</div>
<!-- /wp:group -->
