<?php
/**
 * Title: Funnel Upsell
 * slug: super-ninety-four/funnel-upsell
 * categories: snf-funnel
 * keywords: upsell, oto, funnel, woocommerce
 * viewportWidth: 720
 * Description: Post-purchase upsell layout for Funnel Offer / Blank Canvas pages. Keep [snf_funnel_actions] for accept/decline.
 */
?>
<!-- wp:group {"className":"snf-funnel-pattern snf-funnel-pattern--upsell","layout":{"type":"constrained","contentSize":"40rem"}} -->
<div class="wp-block-group snf-funnel-pattern snf-funnel-pattern--upsell">
	<!-- wp:paragraph {"className":"snf-funnel-pattern__eyebrow"} -->
	<p class="snf-funnel-pattern__eyebrow"><?php echo esc_html__( 'Wait — one upgrade before you go', 'super-ninety-four' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"snf-funnel-pattern__title"} -->
	<h1 class="wp-block-heading snf-funnel-pattern__title"><?php echo esc_html__( 'Add the exclusive upgrade', 'super-ninety-four' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"snf-funnel-pattern__lede"} -->
	<p class="snf-funnel-pattern__lede"><?php echo esc_html__( 'Replace this copy with your offer. Buyers land here right after checkout when the trigger product has an SNF Funnel configured.', 'super-ninety-four' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:list {"className":"snf-funnel-pattern__bullets"} -->
	<ul class="wp-block-list snf-funnel-pattern__bullets">
		<!-- wp:list-item -->
		<li><?php echo esc_html__( 'Benefit one — what they unlock immediately', 'super-ninety-four' ); ?></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><?php echo esc_html__( 'Benefit two — why this is only available now', 'super-ninety-four' ); ?></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><?php echo esc_html__( 'Benefit three — risk reversal or guarantee', 'super-ninety-four' ); ?></li>
		<!-- /wp:list-item -->
	</ul>
	<!-- /wp:list -->

	<!-- wp:shortcode -->
	[snf_funnel_actions accept_label="Yes — upgrade my order" decline_label="No thanks, continue"]
	<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
