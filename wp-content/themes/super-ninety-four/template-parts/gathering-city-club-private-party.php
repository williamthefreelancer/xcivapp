<?php
/**
 * The City Club Private Party — social gathering product landing.
 * Reuses the Lifestyle Concierge purchase UX (contact → ticket), not workshop copy.
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

$event_id    = $event ? (int) $event->ID : 0;
$product     = ( $event_id && function_exists( 'wc_get_product' ) ) ? wc_get_product( $event_id ) : null;
$price_html  = $product ? $product->get_price_html() : '';
$price_label = $price_html
	? wp_strip_all_tags( $price_html )
	: ( $event_id && get_post_meta( $event_id, 'snf_event_price', true )
		? (string) get_post_meta( $event_id, 'snf_event_price', true )
		: '$497.00' );

$video_url  = $event_id ? (string) get_post_meta( $event_id, 'snf_event_video', true ) : '';
$hero_image = get_template_directory_uri() . '/assets/images/events/city-club-private-party.webp';
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
	$starts_at = '2027-03-14 19:00:00';
}

$countdown    = function_exists( 'snf_event_countdown_parts' ) ? snf_event_countdown_parts( $starts_at ) : array();
$commerce     = ( $event_id && function_exists( 'snf_get_ticket_commerce_payload' ) )
	? snf_get_ticket_commerce_payload( $event_id )
	: array();
$date_display = __( 'Saturday, March 14th @ 7:00 PM EST', 'super-ninety-four' );
$tz           = wp_timezone();
$starts_dt    = date_create( $starts_at, $tz );
if ( $starts_dt ) {
	$date_display = wp_date( 'l, F jS @ g:i A T', $starts_dt->getTimestamp(), $tz );
}

$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );

$who_for = array(
	array(
		'icon'  => 'users',
		'title' => __( 'Operators', 'super-ninety-four' ),
		'body'  => __( 'Already run rooms, routes, or estates — and need a closed social gathering that matches the standard.', 'super-ninety-four' ),
	),
	array(
		'icon'  => 'buildings',
		'title' => __( 'Firms', 'super-ninety-four' ),
		'body'  => __( 'Want reciprocal City Clubs reach — not another open guestlist.', 'super-ninety-four' ),
	),
	array(
		'icon'  => 'crown',
		'title' => __( 'Principals', 'super-ninety-four' ),
		'body'  => __( 'Pay for discretion, coach credentials, and a private party that stays private.', 'super-ninety-four' ),
	),
);
?>
<main class="snf-workshop snf-gathering snf-gathering--city-club" data-figma-ready="true">
	<?php
	if ( function_exists( 'wc_print_notices' ) ) {
		ob_start();
		wc_print_notices();
		$notices = trim( (string) ob_get_clean() );
		if ( '' !== $notices ) {
			echo '<div class="snf-workshop__notices snf-container">' . $notices . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	?>
	<div class="snf-workshop__countdown" data-snf-countdown="<?php echo esc_attr( $countdown['target'] ?? '' ); ?>">
		<p class="snf-workshop__countdown-text">
			<span class="snf-workshop__countdown-date">
				<?php
				printf(
					/* translators: %s: gathering date/time */
					esc_html__( 'Gathering date: %s', 'super-ninety-four' ),
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

	<section class="snf-workshop__hero snf-container">
		<p class="snf-workshop__eyebrow">
			<?php esc_html_e( 'A PRIVATE SOCIAL GATHERING FOR OPERATORS, HOSPITALITY FIRMS & PRINCIPALS WHO CLEAR THE BAR.', 'super-ninety-four' ); ?>
		</p>
		<h1 class="snf-workshop__title">
			<?php esc_html_e( 'The City Club Private Party — Coach, Credentials, and a Closed Room', 'super-ninety-four' ); ?>
		</h1>
		<p class="snf-workshop__watch-label"><?php esc_html_e( 'WATCH VIDEO BELOW', 'super-ninety-four' ); ?></p>

		<div class="snf-workshop__video" aria-label="<?php esc_attr_e( 'City Club Private Party video', 'super-ninety-four' ); ?>">
			<?php
			get_template_part(
				'template-parts/media/youtube',
				'embed',
				array(
					'src'    => $video_url,
					'title'  => __( 'The City Club Private Party', 'super-ninety-four' ),
					'poster' => $hero_image,
					'class'  => 'snf-workshop__video-embed',
				)
			);
			?>
		</div>
		<?php
		if ( $event_id && function_exists( 'snf_get_event_broadcast_data' ) ) {
			get_template_part(
				'template-parts/media/live',
				'broadcast',
				array(
					'event_id'  => $event_id,
					'broadcast' => snf_get_event_broadcast_data( $event_id ),
				)
			);
		}
		?>
	</section>

	<section class="snf-workshop__body snf-container">
		<div class="snf-workshop__grid snf-workshop__grid--split">
			<article class="snf-workshop__block">
				<h2 class="snf-workshop__heading"><?php esc_html_e( 'The Problem', 'super-ninety-four' ); ?></h2>
				<p>
					<?php esc_html_e( 'Open rooms leak. Loud guestlists travel. If your private party can be screenshotted into the wrong feed, you are not running a City Club — you are renting a scene.', 'super-ninety-four' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Principals and operators need a closed social gathering: coach, credentials, and a room that only admits people who already clear the bar.', 'super-ninety-four' ); ?>
				</p>
			</article>
			<article class="snf-workshop__block">
				<h2 class="snf-workshop__heading"><?php esc_html_e( 'The Solution', 'super-ninety-four' ); ?></h2>
				<p>
					<?php esc_html_e( 'The City Club Private Party is a social gathering on the SUPER NINETY FOUR circuit — private hospitality, not a marketplace listing.', 'super-ninety-four' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'You arrive credentialed. The room stays closed. The desk holds the standard so you do not have to police the guestlist yourself.', 'super-ninety-four' ); ?>
				</p>
			</article>
		</div>

		<article class="snf-workshop__block snf-workshop__block--who">
			<h2 class="snf-workshop__heading"><?php esc_html_e( 'Who is this for', 'super-ninety-four' ); ?></h2>
			<ul class="snf-workshop__who-list" role="list">
				<?php foreach ( $who_for as $row ) : ?>
					<li class="snf-workshop__who-item">
						<span class="snf-workshop__who-icon" aria-hidden="true">
							<?php
							echo function_exists( 'snf_reciprocal_icon_svg' )
								? snf_reciprocal_icon_svg( $row['icon'], 'snf-workshop__who-svg' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								: '';
							?>
						</span>
						<span class="snf-workshop__who-copy">
							<strong class="snf-workshop__who-title"><?php echo esc_html( $row['title'] ); ?></strong>
							<span class="snf-workshop__who-body"><?php echo esc_html( $row['body'] ); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</article>

		<div class="snf-workshop__grid snf-workshop__grid--split">
			<article class="snf-workshop__block">
				<h2 class="snf-workshop__heading"><?php esc_html_e( 'What you get', 'super-ninety-four' ); ?></h2>
				<ul class="snf-workshop__list snf-workshop__list--learn">
					<li>
						<strong><?php esc_html_e( 'Closed-room admission:', 'super-ninety-four' ); ?></strong>
						<span><?php esc_html_e( 'A private City Clubs social gathering with credentials that travel with you — not a public ticket dump.', 'super-ninety-four' ); ?></span>
					</li>
					<li>
						<strong><?php esc_html_e( 'Coach & hospitality desk:', 'super-ninety-four' ); ?></strong>
						<span><?php esc_html_e( 'Arrive aligned. The desk holds logistics so the room stays on standard.', 'super-ninety-four' ); ?></span>
					</li>
					<li>
						<strong><?php esc_html_e( 'Circuit reciprocity:', 'super-ninety-four' ); ?></strong>
						<span><?php esc_html_e( 'One seat that signals you already belong on the SUPER NINETY FOUR City Clubs circuit.', 'super-ninety-four' ); ?></span>
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
	</section>

	<section class="snf-workshop__cta snf-container" id="snf-gathering-reserve">
		<h2 class="snf-workshop__title snf-workshop__title--cta">
			<?php
			printf(
				/* translators: %s: ticket price */
				esc_html__( 'Reserve your seat for: %s', 'super-ninety-four' ),
				esc_html( $price_label )
			);
			?>
		</h2>

		<nav class="snf-workshop__steps" aria-label="<?php esc_attr_e( 'Gathering purchase steps', 'super-ninety-four' ); ?>">
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

				<div class="snf-workshop__panel is-active" data-step-panel="1">
					<label class="snf-workshop__field">
						<span class="screen-reader-text"><?php esc_html_e( 'Full name', 'super-ninety-four' ); ?></span>
						<input
							type="text"
							name="snf_workshop_name"
							required
							autocomplete="name"
							placeholder="<?php esc_attr_e( 'Full name', 'super-ninety-four' ); ?>"
							aria-label="<?php esc_attr_e( 'Full name', 'super-ninety-four' ); ?>"
						>
					</label>
					<label class="snf-workshop__field">
						<span class="screen-reader-text"><?php esc_html_e( 'Your email', 'super-ninety-four' ); ?></span>
						<input
							type="email"
							name="snf_workshop_email"
							required
							autocomplete="email"
							placeholder="<?php esc_attr_e( 'Your email', 'super-ninety-four' ); ?>"
							aria-label="<?php esc_attr_e( 'Your email', 'super-ninety-four' ); ?>"
						>
					</label>
					<label class="snf-workshop__field">
						<span class="screen-reader-text"><?php esc_html_e( 'Your phone', 'super-ninety-four' ); ?></span>
						<input
							type="tel"
							name="snf_workshop_phone"
							required
							autocomplete="tel"
							placeholder="<?php esc_attr_e( 'Your phone', 'super-ninety-four' ); ?>"
							aria-label="<?php esc_attr_e( 'Your phone', 'super-ninety-four' ); ?>"
						>
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
								class="qty"
								name="quantity"
								value="1"
								min="1"
								step="1"
								inputmode="numeric"
								aria-label="<?php esc_attr_e( 'Product quantity', 'super-ninety-four' ); ?>"
							>
						</label>
					</div>
					<?php if ( ! empty( $commerce['enabled'] ) ) : ?>
						<?php
						get_template_part(
							'template-parts/ticket',
							'commerce-fields',
							array(
								'commerce' => $commerce,
								'context'  => 'gathering',
							)
						);
						?>
					<?php endif; ?>
					<div class="snf-workshop__panel-actions">
						<button class="snf-workshop__button snf-workshop__button--ghost" type="button" data-snf-prev-step>
							<?php esc_html_e( 'Back', 'super-ninety-four' ); ?>
						</button>
						<button class="snf-workshop__button snf-workshop__button--submit single_add_to_cart_button button alt" type="submit" name="add-to-cart" value="<?php echo esc_attr( (string) $product->get_id() ); ?>">
							<?php
							printf(
								/* translators: %s: ticket price */
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
				<?php esc_html_e( 'Checkout is temporarily unavailable. Please try again shortly.', 'super-ninety-four' ); ?>
			</p>
		<?php endif; ?>
	</section>
</main>
