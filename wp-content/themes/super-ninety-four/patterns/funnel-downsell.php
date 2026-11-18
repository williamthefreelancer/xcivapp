<?php
/**
 * Title: Funnel Downsell
 * slug: super-ninety-four/funnel-downsell
 * categories: snf, snf-funnel
 * keywords: downsell, oto, funnel, woocommerce
 * viewportWidth: 720
 * Description: Decline-path downsell for Funnel Offer pages. Keep [snf_funnel_actions] so accept/decline stay wired.
 */
?>
<!-- wp:group {"className":"snf-funnel-pattern snf-funnel-pattern--downsell","layout":{"type":"constrained","contentSize":"40rem"}} -->
<div class="wp-block-group snf-funnel-pattern snf-funnel-pattern--downsell">
	<!-- wp:paragraph {"className":"snf-funnel-pattern__eyebrow"} -->
	<p class="snf-funnel-pattern__eyebrow"><?php echo esc_html__( 'Last chance', 'super-ninety-four' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"snf-funnel-pattern__title"} -->
	<h1 class="wp-block-heading snf-funnel-pattern__title"><?php echo esc_html__( 'A lighter option, still exclusive', 'super-ninety-four' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"snf-funnel-pattern__lede"} -->
	<p class="snf-funnel-pattern__lede"><?php echo esc_html__( 'Shown when the buyer declines the upsell. Edit this copy, then point the trigger product’s downsell page + product IDs at this page and offer.', 'super-ninety-four' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:shortcode -->
	[snf_funnel_actions accept_label="Yes — add this instead" decline_label="No thanks, finish"]
	<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
