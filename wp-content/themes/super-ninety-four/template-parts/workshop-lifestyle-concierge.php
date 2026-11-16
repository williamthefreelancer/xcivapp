<?php
/**
 * Lifestyle Concierge Club Workshop landing (Figma 2046:*).
 *
 * @var WP_Post|null $event Optional event post.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event       = ( isset( $args['event'] ) && $args['event'] instanceof WP_Post )
	? $args['event']
	: ( ( isset( $event ) && $event instanceof WP_Post ) ? $event : get_post() );
$event_id    = $event ? (int) $event->ID : 0;
$price       = $event_id ? get_post_meta( $event_id, 'snf_event_price', true ) : '$97';
$price       = $price ? $price : '$97';
$reserve_id  = 'snf-workshop-reserve';
$video_url   = $event_id ? get_post_meta( $event_id, 'snf_event_video', true ) : '';
$hero_image  = get_template_directory_uri() . '/assets/images/events/lifestyle-concierge-club-workshop.jpg';
$form_notice = isset( $_GET['reserved'] ) ? sanitize_text_field( wp_unslash( $_GET['reserved'] ) ) : '';
?>
<main class="snf-workshop">
	<section class="snf-workshop__hero snf-container">
		<p class="snf-workshop__eyebrow">
			<?php esc_html_e( 'FOR CLUB OR HOSPITALITY MANAGEMENT FIRMS, PARTY OR EVENT ORGANIZERS & MANAGEMENT CONSULTING FIRMS.', 'super-ninety-four' ); ?>
		</p>
		<h1 class="snf-workshop__title">
			<?php esc_html_e( 'What It Is, How It Benefits Sovereign Firms, and How to Claim 100% Ownership of Your Data & Results.', 'super-ninety-four' ); ?>
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
					<figcaption><?php esc_html_e( 'Video coming soon — reserve your seat below.', 'super-ninety-four' ); ?></figcaption>
				</figure>
			<?php endif; ?>
		</div>
	</section>

	<section class="snf-workshop__body snf-container">
		<div class="snf-workshop__grid">
			<article class="snf-workshop__card">
				<h2 class="snf-workshop__heading"><?php esc_html_e( 'The Problem', 'super-ninety-four' ); ?></h2>
				<p>
					<?php esc_html_e( 'Relying on third-party platforms means you do not own the audience relationship. High-value client data, attribution, and follow-through sit with middlemen — while your firm still carries the risk and reputation.', 'super-ninety-four' ); ?>
				</p>
			</article>
			<article class="snf-workshop__card">
				<h2 class="snf-workshop__heading"><?php esc_html_e( 'The Solution', 'super-ninety-four' ); ?></h2>
				<p>
					<?php esc_html_e( 'Plug into an existing member circuit instead of building infrastructure from scratch or chasing cold ticket sales. Operate with aligned principals who already understand private hospitality standards.', 'super-ninety-four' ); ?>
				</p>
			</article>
		</div>

		<p class="snf-workshop__price-line">
			<?php
			printf(
				/* translators: %s: workshop price */
				esc_html__( 'Attend Now Below For %s', 'super-ninety-four' ),
				esc_html( $price )
			);
			?>
		</p>

		<article class="snf-workshop__operator">
			<h2 class="snf-workshop__heading"><?php esc_html_e( "Who's Operating", 'super-ninety-four' ); ?></h2>
			<p>
				<?php esc_html_e( 'William A. Johnson, The owner of THE SUPER NINETY FOUR EVENTS COMPANY & XCIV PLATFORM HOLDINGS.', 'super-ninety-four' ); ?>
			</p>
		</article>

		<article class="snf-workshop__outcome">
			<h2 class="snf-workshop__heading"><?php esc_html_e( 'The Outcome', 'super-ninety-four' ); ?></h2>
			<ul class="snf-workshop__list">
				<li><?php esc_html_e( 'Full Sovereign Control', 'super-ninety-four' ); ?></li>
				<li><?php esc_html_e( '100% Data & Attribution Ownership', 'super-ninety-four' ); ?></li>
				<li><?php esc_html_e( 'Unfiltered Peer Calibration', 'super-ninety-four' ); ?></li>
			</ul>
		</article>

		<article class="snf-workshop__learn">
			<h2 class="snf-workshop__heading"><?php esc_html_e( 'What you will learn', 'super-ninety-four' ); ?></h2>
			<ul class="snf-workshop__list snf-workshop__list--learn">
				<li>
					<strong><?php esc_html_e( 'The Club Blueprint', 'super-ninety-four' ); ?></strong>
					<span><?php esc_html_e( 'Scalable concierge club functions you can install into a sovereign firm.', 'super-ninety-four' ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'The Data Reclamation Protocol', 'super-ninety-four' ); ?></strong>
					<span><?php esc_html_e( 'Strategies to reclaim ownership of audience data and results.', 'super-ninety-four' ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'The Charter & Idea Exchange', 'super-ninety-four' ); ?></strong>
					<span><?php esc_html_e( 'A collaborative environment for aligned principals to calibrate in public.', 'super-ninety-four' ); ?></span>
				</li>
			</ul>
		</article>
	</section>

	<section class="snf-workshop__cta snf-container" id="<?php echo esc_attr( $reserve_id ); ?>">
		<p class="snf-workshop__cta-copy">
			<?php
			printf(
				/* translators: %s: workshop price */
				esc_html__( 'If this is the way you want to operate, reserve your seat at the 2-hour workshop for %s.', 'super-ninety-four' ),
				esc_html( $price )
			);
			?>
		</p>

		<?php if ( '1' === $form_notice ) : ?>
			<p class="snf-workshop__notice snf-workshop__notice--success" role="status">
				<?php esc_html_e( 'Reservation received. We will confirm your seat by email.', 'super-ninety-four' ); ?>
			</p>
		<?php elseif ( '0' === $form_notice ) : ?>
			<p class="snf-workshop__notice snf-workshop__notice--error" role="alert">
				<?php esc_html_e( 'Something went wrong. Please check the form and try again.', 'super-ninety-four' ); ?>
			</p>
		<?php endif; ?>

		<form
			id="snf-workshop-form"
			class="snf-workshop__form"
			method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		>
			<input type="hidden" name="action" value="snf_workshop_reserve">
			<input type="hidden" name="event_id" value="<?php echo esc_attr( (string) $event_id ); ?>">
			<?php wp_nonce_field( 'snf_workshop_reserve', 'snf_workshop_nonce' ); ?>

			<label class="snf-workshop__field">
				<span><?php esc_html_e( 'Your full name *', 'super-ninety-four' ); ?></span>
				<input type="text" name="full_name" required autocomplete="name">
			</label>
			<label class="snf-workshop__field">
				<span><?php esc_html_e( 'Your email *', 'super-ninety-four' ); ?></span>
				<input type="email" name="email" required autocomplete="email">
			</label>
			<label class="snf-workshop__field">
				<span><?php esc_html_e( 'Your phone *', 'super-ninety-four' ); ?></span>
				<input type="tel" name="phone" required autocomplete="tel">
			</label>
			<label class="snf-workshop__field">
				<span><?php esc_html_e( 'Firm / organization', 'super-ninety-four' ); ?></span>
				<input type="text" name="firm" autocomplete="organization">
			</label>

			<button class="snf-workshop__button snf-workshop__button--submit" type="submit">
				<?php
				printf(
					/* translators: %s: workshop price */
					esc_html__( 'Reserve your seat – %s', 'super-ninety-four' ),
					esc_html( $price )
				);
				?>
			</button>
		</form>
	</section>
</main>
