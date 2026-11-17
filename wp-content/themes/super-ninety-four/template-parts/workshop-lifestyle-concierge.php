<?php
/**
 * Lifestyle Concierge Club Workshop landing (Figma 2046:2).
 *
 * @package Super_Ninety_Four
 * @var array{event?:WP_Post} $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event = ( isset( $args['event'] ) && $args['event'] instanceof WP_Post )
	? $args['event']
	: ( ( isset( $event ) && $event instanceof WP_Post ) ? $event : get_post() );

$event_id   = $event ? (int) $event->ID : 0;
$product    = ( $event_id && function_exists( 'wc_get_product' ) ) ? wc_get_product( $event_id ) : null;
$price_html = $product ? $product->get_price_html() : '';
$price_raw  = $product ? (string) $product->get_price() : '97';
$price_label = $price_html
	? wp_strip_all_tags( $price_html )
	: ( $event_id && get_post_meta( $event_id, 'snf_event_price', true )
		? (string) get_post_meta( $event_id, 'snf_event_price', true )
		: '$97' );

$video_url  = $event_id ? (string) get_post_meta( $event_id, 'snf_event_video', true ) : '';
$hero_image = get_template_directory_uri() . '/assets/images/events/lifestyle-concierge-club-workshop.jpg';
if ( $product && $product->get_image_id() ) {
	$thumb = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
	if ( $thumb ) {
		$hero_image = $thumb;
	}
}

$starts_at = $event_id ? (string) get_post_meta( $event_id, '_snf_starts_at', true ) : '';
if ( ! $starts_at && $event_id ) {
	$starts_at = (string) get_post_meta( $event_id, 'snf_event_starts_at', true );
}
if ( ! $starts_at ) {
	// Figma: Tuesday, January 5th @ 11:00 AM EST (2027-01-05 is a Tuesday).
	$starts_at = '2027-01-05 11:00:00';
}

$countdown     = function_exists( 'snf_event_countdown_parts' ) ? snf_event_countdown_parts( $starts_at ) : array();
$date_display  = __( 'Tuesday, January 5th @ 11:00 AM EST', 'super-ninety-four' );
$tz            = wp_timezone();
$starts_dt     = date_create( $starts_at, $tz );
if ( $starts_dt ) {
	$date_display = wp_date( 'l, F jS @ g:i A T', $starts_dt->getTimestamp(), $tz );
}

$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
?>
<main class="snf-workshop" data-figma-ready="true" data-figma-node="2046:2">
	<?php
	if ( function_exists( 'wc_print_notices' ) ) {
		echo '<div class="snf-workshop__notices snf-container">';
		wc_print_notices();
		echo '</div>';
	}
	?>
	<div class="snf-workshop__countdown" data-snf-countdown="<?php echo esc_attr( $countdown['target'] ?? '' ); ?>" data-figma-slot="workshop-countdown">
		<p class="snf-workshop__countdown-text">
			<span class="snf-workshop__countdown-date">
				<?php
				printf(
					/* translators: %s: workshop date/time */
					esc_html__( 'Workshop Date: %s', 'super-ninety-four' ),
					esc_html( $date_display )
				);
				?>
			</span>
			<span class="snf-workshop__countdown-sep" aria-hidden="true">|</span>
			<span class="snf-workshop__countdown-live">
				<?php esc_html_e( 'STARTS IN:', 'super-ninety-four' ); ?>
				<span class="snf-workshop__countdown-digits" aria-live="polite">
					<span data-unit="days"><?php echo esc_html( $countdown['days'] ?? '00' ); ?></span>d
					<span class="snf-workshop__countdown-colon" aria-hidden="true">:</span>
					<span data-unit="hours"><?php echo esc_html( $countdown['hours'] ?? '00' ); ?></span>h
					<span class="snf-workshop__countdown-colon" aria-hidden="true">:</span>
					<span data-unit="mins"><?php echo esc_html( $countdown['mins'] ?? '00' ); ?></span>m
					<span class="snf-workshop__countdown-colon" aria-hidden="true">:</span>
					<span data-unit="secs"><?php echo esc_html( $countdown['secs'] ?? '00' ); ?></span>s
				</span>
			</span>
		</p>
	</div>

	<section class="snf-workshop__hero snf-container" data-figma-slot="workshop-hero">
		<p class="snf-workshop__eyebrow">
			<?php esc_html_e( 'FOR CLUB OR HOSPITALITY MANAGEMENT FIRMS, PARTY OR EVENT ORGANIZERS & MANAGEMENT CONSULTING FIRMS.', 'super-ninety-four' ); ?>
		</p>
		<h1 class="snf-workshop__title">
			<?php esc_html_e( 'What It Is, How It Benefits Sovereign Firms, and How to Claim 100% Ownership of Your Data & Results', 'super-ninety-four' ); ?>
		</h1>
		<p class="snf-workshop__watch-label"><?php esc_html_e( 'WATCH VIDEO BELOW', 'super-ninety-four' ); ?></p>

		<div class="snf-workshop__video" aria-label="<?php esc_attr_e( 'Workshop video', 'super-ninety-four' ); ?>">
			<?php if ( $video_url ) : ?>
				<iframe
					src="<?php echo esc_url( $video_url ); ?>"
					title="<?php esc_attr_e( 'The Lifestyle Concierge Club Workshop', 'super-ninety-four' ); ?>"
					loading="lazy"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowfullscreen
				></iframe>
			<?php else : ?>
				<figure class="snf-workshop__video-poster">
					<img
						src="<?php echo esc_url( $hero_image ); ?>"
						alt="<?php esc_attr_e( 'The Lifestyle Concierge Club Workshop', 'super-ninety-four' ); ?>"
						width="1200"
						height="675"
						decoding="async"
					>
				</figure>
			<?php endif; ?>
		</div>
	</section>

	<section class="snf-workshop__body snf-container" data-figma-slot="workshop-body">
		<div class="snf-workshop__grid snf-workshop__grid--split">
			<article class="snf-workshop__block">
				<h2 class="snf-workshop__heading"><?php esc_html_e( 'The Problem', 'super-ninety-four' ); ?></h2>
				<p>
					<?php esc_html_e( 'If you run a hospitality group, produce select events, or advise high-net-worth clients, you are likely flying blind on data attribution while giving up leverage to outside platforms.', 'super-ninety-four' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'You cannot scale at the highest standard when third parties control your audience trail, censor your high-energy concepts, or surround you with middlemen instead of true principals.', 'super-ninety-four' ); ?>
				</p>
			</article>
			<article class="snf-workshop__block">
				<h2 class="snf-workshop__heading"><?php esc_html_e( 'The Solution', 'super-ninety-four' ); ?></h2>
				<p>
					<?php esc_html_e( 'Plug directly into an existing, turnkey touring membership circuit', 'super-ninety-four' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Instead of building infrastructure from scratch or chasing cold ticket sales, your firm leverages our pre-vetted eight-figure member base, private security, compliance shield, and rotating road production.', 'super-ninety-four' ); ?>
				</p>
			</article>
		</div>

		<div class="snf-workshop__grid snf-workshop__grid--split">
			<article class="snf-workshop__block">
				<h2 class="snf-workshop__heading"><?php esc_html_e( 'The Outcome', 'super-ninety-four' ); ?></h2>
				<ul class="snf-workshop__list snf-workshop__list--learn">
					<li>
						<strong><?php esc_html_e( 'Full Sovereign Control:', 'super-ninety-four' ); ?></strong>
						<span><?php esc_html_e( 'Operate under your own holding structure and run independent event charters without outside interference.', 'super-ninety-four' ); ?></span>
					</li>
					<li>
						<strong><?php esc_html_e( '100% Data & Attribution Ownership:', 'super-ninety-four' ); ?></strong>
						<span><?php esc_html_e( 'Own every piece of client data, transaction trail, and performance metric so you always know exactly where your results come from.', 'super-ninety-four' ); ?></span>
					</li>
					<li>
						<strong><?php esc_html_e( 'Unfiltered Peer Calibration:', 'super-ninety-four' ); ?></strong>
						<span><?php esc_html_e( 'Contribute to and collaborate inside a raw, high-energy environment built exclusively for hospitality groups, event organizers, and consultancies that live this lifestyle at the highest standard.', 'super-ninety-four' ); ?></span>
					</li>
				</ul>
			</article>
			<article class="snf-workshop__block">
				<h2 class="snf-workshop__heading"><?php esc_html_e( "Who's Operation", 'super-ninety-four' ); ?></h2>
				<p>
					<?php esc_html_e( 'William A. Johnson: The owner of THE SUPER NINETY FOUR EVENTS COMPANY & SUPR XCIV PLATFORM HOLDINGS', 'super-ninety-four' ); ?>
				</p>
			</article>
		</div>

		<article class="snf-workshop__block snf-workshop__block--learn">
			<h2 class="snf-workshop__heading"><?php esc_html_e( 'What will you learn', 'super-ninety-four' ); ?></h2>
			<ul class="snf-workshop__list snf-workshop__list--learn">
				<li>
					<strong><?php esc_html_e( 'The Club Blueprint:', 'super-ninety-four' ); ?></strong>
					<span><?php esc_html_e( 'Exactly how a Lifestyle Concierge Club functions as an operational home base for your existing holding structure.', 'super-ninety-four' ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'The Data Reclamation Protocol:', 'super-ninety-four' ); ?></strong>
					<span><?php esc_html_e( 'Why knowing where your data lives is non-negotiable—and the exact steps to claim 100% ownership of your client trail and conversion metrics.', 'super-ninety-four' ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'The Charter & Idea Exchange:', 'super-ninety-four' ); ?></strong>
					<span><?php esc_html_e( 'How to use our newly built environment to pressure-test radical ideas, collaborate with aligned principals, and deploy select road events on your own terms.', 'super-ninety-four' ); ?></span>
				</li>
			</ul>
		</article>
	</section>

	<section class="snf-workshop__cta snf-container" id="snf-workshop-reserve" data-figma-slot="workshop-purchase">
		<h2 class="snf-workshop__title snf-workshop__title--cta">
			<?php esc_html_e( 'What It Is, How It Benefits Sovereign Firms, and How to Claim 100% Ownership of Your Data & Results', 'super-ninety-four' ); ?>
		</h2>

		<nav class="snf-workshop__steps" aria-label="<?php esc_attr_e( 'Workshop purchase steps', 'super-ninety-four' ); ?>">
			<ol class="snf-workshop__stepper">
				<li class="snf-workshop__step is-active" data-step-indicator="1">
					<button type="button" class="snf-workshop__step-btn" data-step="1" aria-current="step">
						<span class="snf-workshop__step-num" aria-hidden="true">1</span>
						<span class="snf-workshop__step-label"><?php esc_html_e( 'Contact details', 'super-ninety-four' ); ?></span>
					</button>
				</li>
				<li class="snf-workshop__step" data-step-indicator="2">
					<button type="button" class="snf-workshop__step-btn" data-step="2">
						<span class="snf-workshop__step-num" aria-hidden="true">2</span>
						<span class="snf-workshop__step-label"><?php esc_html_e( 'Ticket selection', 'super-ninety-four' ); ?></span>
					</button>
				</li>
			</ol>
		</nav>

		<?php if ( $product ) : ?>
			<form
				class="snf-workshop__form cart"
				method="post"
				action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
				enctype="multipart/form-data"
				data-snf-workshop-steps
			>
				<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( (string) $product->get_id() ); ?>">
				<input type="hidden" name="snf_cart_name" value="">
				<input type="hidden" name="snf_cart_email" value="">
				<input type="hidden" name="snf_cart_phone" value="">
				<input type="hidden" name="snf_cart_firm" value="">

				<div class="snf-workshop__panel is-active" data-step-panel="1">
					<label class="snf-workshop__field">
						<span><?php esc_html_e( 'Full name *', 'super-ninety-four' ); ?></span>
						<input type="text" name="snf_workshop_name" required autocomplete="name">
					</label>
					<label class="snf-workshop__field">
						<span><?php esc_html_e( 'Email *', 'super-ninety-four' ); ?></span>
						<input type="email" name="snf_workshop_email" required autocomplete="email">
					</label>
					<label class="snf-workshop__field">
						<span><?php esc_html_e( 'Phone *', 'super-ninety-four' ); ?></span>
						<input type="tel" name="snf_workshop_phone" required autocomplete="tel">
					</label>
					<label class="snf-workshop__field">
						<span><?php esc_html_e( 'Firm / organization', 'super-ninety-four' ); ?></span>
						<input type="text" name="snf_workshop_firm" autocomplete="organization">
					</label>
					<button class="snf-workshop__button" type="button" data-snf-next-step>
						<?php esc_html_e( 'Continue to ticket selection', 'super-ninety-four' ); ?>
					</button>
				</div>

				<div class="snf-workshop__panel" data-step-panel="2" hidden>
					<div class="snf-workshop__ticket">
						<p class="snf-workshop__ticket-name"><?php echo esc_html( $product->get_name() ); ?></p>
						<p class="snf-workshop__ticket-price">
							<?php
							echo $price_html
								? wp_kses_post( $price_html )
								: esc_html( $price_label );
							?>
						</p>
						<label class="snf-workshop__field snf-workshop__field--qty">
							<span><?php esc_html_e( 'Quantity', 'super-ninety-four' ); ?></span>
							<input
								type="number"
								name="quantity"
								value="1"
								min="1"
								step="1"
								inputmode="numeric"
								aria-label="<?php esc_attr_e( 'Product quantity', 'super-ninety-four' ); ?>"
							>
						</label>
					</div>
					<div class="snf-workshop__panel-actions">
						<button class="snf-workshop__button snf-workshop__button--ghost" type="button" data-snf-prev-step>
							<?php esc_html_e( 'Back', 'super-ninety-four' ); ?>
						</button>
						<button class="snf-workshop__button snf-workshop__button--submit single_add_to_cart_button" type="submit" name="add-to-cart" value="<?php echo esc_attr( (string) $product->get_id() ); ?>">
							<?php
							printf(
								/* translators: %s: workshop price */
								esc_html__( 'Add to cart — %s', 'super-ninety-four' ),
								esc_html( $price_label )
							);
							?>
						</button>
					</div>
					<p class="snf-workshop__cart-note">
						<a href="<?php echo esc_url( $cart_url ); ?>"><?php esc_html_e( 'View cart', 'super-ninety-four' ); ?></a>
					</p>
				</div>
			</form>
		<?php else : ?>
			<p class="snf-workshop__cta-copy">
				<?php esc_html_e( 'Workshop checkout is temporarily unavailable. Please try again shortly.', 'super-ninety-four' ); ?>
			</p>
		<?php endif; ?>
	</section>
</main>
