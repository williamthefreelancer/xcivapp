<?php
/**
 * Event single — Figma select-event landings (174:2 / 2167:214 / 2167:243).
 *
 * @var array<string, mixed> $args {
 *     @type array $event Event payload from snf_get_event_single_data().
 * }
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event = ( isset( $args['event'] ) && is_array( $args['event'] ) ) ? $args['event'] : array();
if ( ! $event ) {
	return;
}

$countdown  = isset( $event['countdown'] ) && is_array( $event['countdown'] ) ? $event['countdown'] : array();
$member     = isset( $event['member'] ) && is_array( $event['member'] ) ? $event['member'] : array();
$plans      = isset( $event['plans'] ) && is_array( $event['plans'] ) ? $event['plans'] : array();
$audience   = isset( $event['audience'] ) ? (string) $event['audience'] : 'members';
if ( ! in_array( $audience, array( 'members', 'non-members', 'public' ), true ) ) {
	$audience = 'members';
}
$is_member  = 'members' === $audience;
$figma_node = ! empty( $event['figma_node'] ) ? (string) $event['figma_node'] : '174:2';
$avatar     = get_template_directory_uri() . '/assets/icons/user-profile.svg';
if ( ! empty( $member['avatar'] ) ) {
	$avatar = $member['avatar'];
}

$member_name  = isset( $member['name'] ) ? (string) $member['name'] : '';
$member_email = isset( $member['email'] ) ? (string) $member['email'] : '';
$member_club  = isset( $member['club'] ) ? (string) $member['club'] : '';
$plan_list    = isset( $plans['plans'] ) && is_array( $plans['plans'] ) ? $plans['plans'] : array();
$initiation   = isset( $plans['initiation_fee'] ) && is_array( $plans['initiation_fee'] ) ? $plans['initiation_fee'] : array();
$logo         = isset( $event['logo'] ) && is_array( $event['logo'] ) ? $event['logo'] : array();
$logo_url     = ! empty( $logo['url'] )
	? (string) $logo['url']
	: get_template_directory_uri() . '/assets/icons/female-body-icon-white.svg';
$logo_width   = ! empty( $logo['width'] ) ? (int) $logo['width'] : 28;
$logo_height  = ! empty( $logo['height'] ) ? (int) $logo['height'] : 42;
$logo_variant = ( ! empty( $logo['variant'] ) && 'wordmark' === $logo['variant'] ) ? 'wordmark' : 'icon';
$commerce     = isset( $event['ticket'] ) && is_array( $event['ticket'] ) ? $event['ticket'] : array();
$can_purchase = ! empty( $event['can_purchase'] );
$is_public    = ! empty( $event['is_public'] );
$product_id   = ! empty( $event['product_id'] ) ? (int) $event['product_id'] : 0;
$starts_at    = isset( $event['starts_at'] ) ? (string) $event['starts_at'] : '';
$allow_ticket = $can_purchase && $product_id && ( $is_member || $is_public );
$use_booking  = $is_public && $allow_ticket && ! empty( $commerce['enabled'] );
$form_action  = '#';
$form_class   = 'snf-event__steps';
if ( $allow_ticket ) {
	$form_action = ! empty( $event['permalink'] )
		? (string) $event['permalink']
		: get_permalink( $product_id );
	$form_class .= ' cart';
}
?>
<main
	class="snf-page snf-event<?php echo $use_booking ? ' snf-event--public-booking' : ''; ?>"
	data-figma-ready="true"
	data-figma-node="<?php echo esc_attr( $figma_node ); ?>"
	data-admission-audience="<?php echo esc_attr( $audience ); ?>"
	data-ticket-purchase="<?php echo $allow_ticket ? '1' : '0'; ?>"
>
	<section class="snf-event__shell snf-container" aria-label="<?php echo esc_attr( $event['title'] ); ?>">
		<?php
		get_template_part(
			'template-parts/event',
			'breadcrumbs',
			array(
				'post'  => ! empty( $event['id'] ) ? (int) $event['id'] : get_the_ID(),
				'class' => 'snf-crumbs--event',
			)
		);
		?>
		<article class="snf-event__frame">
			<header class="snf-event__topbar">
				<a class="snf-event__brand snf-event__brand--<?php echo esc_attr( $logo_variant ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<img
						class="snf-event__logo snf-event__logo--<?php echo esc_attr( $logo_variant ); ?>"
						src="<?php echo esc_url( $logo_url ); ?>"
						alt="<?php echo esc_attr( $event['title'] ); ?>"
						width="<?php echo esc_attr( (string) $logo_width ); ?>"
						height="<?php echo esc_attr( (string) $logo_height ); ?>"
						decoding="async"
					>
				</a>
				<a class="snf-event__avatar" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Home', 'super-ninety-four' ); ?>">
					<img src="<?php echo esc_url( $avatar ); ?>" alt="" width="40" height="40" decoding="async">
				</a>
			</header>

			<figure class="snf-event__media">
				<?php
				get_template_part(
					'template-parts/media/youtube',
					'embed',
					array(
						'src'    => ! empty( $event['video'] ) ? (string) $event['video'] : '',
						'title'  => (string) $event['title'],
						'poster' => ! empty( $event['image'] ) ? (string) $event['image'] : '',
						'class'  => 'snf-event__media-embed',
					)
				);
				?>
			</figure>

			<?php
			if ( ! empty( $event['broadcast'] ) && is_array( $event['broadcast'] ) ) {
				get_template_part(
					'template-parts/media/live',
					'broadcast',
					array(
						'event_id'  => (int) ( $event['id'] ?? 0 ),
						'broadcast' => $event['broadcast'],
					)
				);
			}
			?>

			<div class="snf-event__body">
				<div class="snf-event__details">
					<p class="snf-event__countdown" data-snf-countdown="<?php echo esc_attr( $countdown['target'] ?? '' ); ?>">
						<span class="snf-event__countdown-label"><?php esc_html_e( 'Date/Start at:', 'super-ninety-four' ); ?></span>
						<span class="snf-event__countdown-digits" aria-live="polite">
							<span data-unit="days"><?php echo esc_html( $countdown['days'] ?? '00' ); ?></span>
							<span class="snf-event__countdown-sep" aria-hidden="true">|</span>
							<span data-unit="hours"><?php echo esc_html( $countdown['hours'] ?? '00' ); ?></span>
							<span class="snf-event__countdown-sep" aria-hidden="true">|</span>
							<span data-unit="mins"><?php echo esc_html( $countdown['mins'] ?? '00' ); ?></span>
							<span class="snf-event__countdown-sep" aria-hidden="true">|</span>
							<span data-unit="secs"><?php echo esc_html( $countdown['secs'] ?? '00' ); ?></span>
						</span>
					</p>

					<p class="snf-event__category"><?php echo esc_html( $event['category'] ); ?></p>
					<h1 class="snf-event__title"><?php echo esc_html( $event['title'] ); ?></h1>
					<p class="snf-event__location">
						<?php
						printf(
							/* translators: %s: city/state or route */
							esc_html__( 'City/State: %s', 'super-ninety-four' ),
							esc_html( $event['location'] )
						);
						?>
					</p>
					<p class="snf-event__organizer">
						<?php
						printf(
							/* translators: %s: organizer name */
							esc_html__( 'ORGANIZER: %s', 'super-ninety-four' ),
							esc_html( $event['organizer'] )
						);
						?>
					</p>
					<p class="snf-event__price"><?php echo esc_html( $event['price'] ); ?></p>

					<?php if ( ! empty( $event['content'] ) && trim( wp_strip_all_tags( $event['content'] ) ) ) : ?>
						<div class="snf-event__description">
							<?php echo $event['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php else : ?>
						<div class="snf-event__description">
							<p><?php echo esc_html( $event['description'] ); ?></p>
						</div>
					<?php endif; ?>
				</div>

				<aside
					class="snf-event__apply<?php echo $use_booking ? ' snf-event__apply--booking' : ''; ?>"
					aria-label="<?php echo $use_booking ? esc_attr__( 'Check availability', 'super-ninety-four' ) : esc_attr__( 'Event admissions', 'super-ninety-four' ); ?>"
				>
					<?php if ( $use_booking ) : ?>
						<?php
						get_template_part(
							'template-parts/check-availability',
							'booking-form',
							array(
								'commerce'    => $commerce,
								'product_id'  => $product_id,
								'starts_at'   => $starts_at,
								'form_action' => $form_action,
							)
						);
						?>
					<?php elseif ( $is_public ) : ?>
						<h2 class="snf-event__apply-title"><?php esc_html_e( 'Check availability', 'super-ninety-four' ); ?></h2>
						<p class="snf-event__step-copy">
							<?php esc_html_e( 'Online booking for this public event is temporarily unavailable. Please try again shortly, or contact the desk if you need help reserving a seat.', 'super-ninety-four' ); ?>
						</p>
						<?php if ( ! empty( $event['permalink'] ) ) : ?>
							<p class="snf-event__cart-note">
								<a class="snf-event__cta" href="<?php echo esc_url( (string) $event['permalink'] ); ?>">
									<?php esc_html_e( 'Refresh event page', 'super-ninety-four' ); ?>
								</a>
							</p>
						<?php endif; ?>
					<?php else : ?>
					<h2 class="snf-event__apply-title">
						<?php
						echo $can_purchase && $is_member
							? esc_html__( 'Reserve tickets for this event', 'super-ninety-four' )
							: esc_html__( 'Apply to attend this upcoming event', 'super-ninety-four' );
						?>
					</h2>

					<form
						class="<?php echo esc_attr( $form_class ); ?>"
						data-snf-event-steps
						data-admission-audience="<?php echo esc_attr( $audience ); ?>"
						data-ticket-purchase="<?php echo $allow_ticket && $is_member ? '1' : '0'; ?>"
						action="<?php echo esc_url( $form_action ); ?>"
						method="post"
						enctype="multipart/form-data"
						novalidate
					>
						<input type="hidden" name="snf_event_id" value="<?php echo esc_attr( (string) ( $event['id'] ?? '' ) ); ?>">
						<input type="hidden" name="snf_event_audience" value="<?php echo esc_attr( $audience ); ?>">
						<?php if ( $allow_ticket && $is_member ) : ?>
							<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( (string) $product_id ); ?>">
							<input type="hidden" name="quantity" value="1">
						<?php endif; ?>

						<?php if ( $is_member ) : ?>
							<div class="snf-event__step-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Member admissions steps', 'super-ninety-four' ); ?>">
								<button type="button" class="snf-event__step-tab is-active" role="tab" id="snf-event-tab-1" aria-controls="snf-event-panel-1" aria-selected="true" data-step="1">
									<?php esc_html_e( 'Profile', 'super-ninety-four' ); ?>
								</button>
								<button type="button" class="snf-event__step-tab" role="tab" id="snf-event-tab-2" aria-controls="snf-event-panel-2" aria-selected="false" data-step="2">
									<?php esc_html_e( 'Contact', 'super-ninety-four' ); ?>
								</button>
								<button type="button" class="snf-event__step-tab" role="tab" id="snf-event-tab-3" aria-controls="snf-event-panel-3" aria-selected="false" data-step="3">
									<?php esc_html_e( 'Intent', 'super-ninety-four' ); ?>
								</button>
								<?php if ( $can_purchase && ! empty( $commerce['enabled'] ) ) : ?>
									<button type="button" class="snf-event__step-tab" role="tab" id="snf-event-tab-4" aria-controls="snf-event-panel-4" aria-selected="false" data-step="4">
										<?php esc_html_e( 'Tickets', 'super-ninety-four' ); ?>
									</button>
								<?php endif; ?>
							</div>

							<div class="snf-event__step-panel is-active" data-step-panel="1" role="tabpanel" id="snf-event-panel-1" aria-labelledby="snf-event-tab-1">
								<p class="snf-event__step-copy"><?php esc_html_e( 'Step 1 — Profile. Confirm how the desk should recognize you for this member gathering.', 'super-ninety-four' ); ?></p>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Preferred name', 'super-ninety-four' ); ?></span>
									<input type="text" name="snf_event_preferred_name" id="snf-event-preferred-name" autocomplete="nickname" value="<?php echo esc_attr( $member_name ); ?>" required>
								</label>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Home club', 'super-ninety-four' ); ?></span>
									<input type="text" name="snf_event_home_club" id="snf-event-home-club" value="<?php echo esc_attr( $member_club ); ?>" required>
								</label>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Membership seat', 'super-ninety-four' ); ?></span>
									<select name="snf_event_seat" id="snf-event-seat" required>
										<option value="principal" selected><?php esc_html_e( 'Principal', 'super-ninety-four' ); ?></option>
										<option value="reciprocal"><?php esc_html_e( 'Reciprocal member', 'super-ninety-four' ); ?></option>
										<option value="guest"><?php esc_html_e( 'Member guest', 'super-ninety-four' ); ?></option>
									</select>
								</label>
								<button type="button" class="snf-event__step-next" data-snf-next-step>
									<?php esc_html_e( 'Continue to contact details', 'super-ninety-four' ); ?>
								</button>
							</div>

							<div class="snf-event__step-panel" data-step-panel="2" role="tabpanel" id="snf-event-panel-2" aria-labelledby="snf-event-tab-2" hidden>
								<p class="snf-event__step-copy"><?php esc_html_e( 'Step 2 — Contact details. Where should credentials and arrival notes be sent?', 'super-ninety-four' ); ?></p>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Email', 'super-ninety-four' ); ?></span>
									<input type="email" name="snf_event_email" id="snf-event-email" autocomplete="email" value="<?php echo esc_attr( $member_email ); ?>" required>
								</label>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Phone', 'super-ninety-four' ); ?></span>
									<input type="tel" name="snf_event_phone" id="snf-event-phone" autocomplete="tel" required>
								</label>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'City / State', 'super-ninety-four' ); ?></span>
									<input type="text" name="snf_event_city" id="snf-event-city" autocomplete="address-level2" required>
								</label>
								<div class="snf-event__step-actions">
									<button type="button" class="snf-event__step-back" data-snf-prev-step>
										<?php esc_html_e( 'Back', 'super-ninety-four' ); ?>
									</button>
									<button type="button" class="snf-event__step-next" data-snf-next-step>
										<?php esc_html_e( 'Continue to your intent', 'super-ninety-four' ); ?>
									</button>
								</div>
							</div>

							<div class="snf-event__step-panel" data-step-panel="3" role="tabpanel" id="snf-event-panel-3" aria-labelledby="snf-event-tab-3" hidden>
								<p class="snf-event__step-copy"><?php esc_html_e( 'Step 3 — Your intent. Tell admissions how you plan to participate.', 'super-ninety-four' ); ?></p>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Attendance intent', 'super-ninety-four' ); ?></span>
									<select name="snf_event_intent_type" id="snf-event-intent-type" required>
										<option value="attend" selected><?php esc_html_e( 'Attend as principal', 'super-ninety-four' ); ?></option>
										<option value="guest"><?php esc_html_e( 'Bring a guest', 'super-ninety-four' ); ?></option>
										<option value="host"><?php esc_html_e( 'Host / sponsor a table', 'super-ninety-four' ); ?></option>
										<option value="observe"><?php esc_html_e( 'Observe / soft RSVP', 'super-ninety-four' ); ?></option>
									</select>
								</label>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Party size', 'super-ninety-four' ); ?></span>
									<input type="number" name="snf_event_party_size" id="snf-event-party-size" min="1" max="12" value="1" required>
								</label>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Notes for the desk', 'super-ninety-four' ); ?></span>
									<textarea name="snf_event_intent" id="snf-event-intent" rows="4" placeholder="<?php esc_attr_e( 'Arrival window, guest names, hospitality preferences…', 'super-ninety-four' ); ?>" required></textarea>
								</label>
								<div class="snf-event__step-actions">
									<button type="button" class="snf-event__step-back" data-snf-prev-step>
										<?php esc_html_e( 'Back', 'super-ninety-four' ); ?>
									</button>
									<?php if ( $can_purchase && ! empty( $commerce['enabled'] ) ) : ?>
										<button type="button" class="snf-event__step-next" data-snf-next-step>
											<?php esc_html_e( 'Continue to tickets', 'super-ninety-four' ); ?>
										</button>
									<?php else : ?>
										<button type="submit" class="snf-event__cta" id="snf-event-cta">
											<?php esc_html_e( 'Submit admissions request', 'super-ninety-four' ); ?>
										</button>
									<?php endif; ?>
								</div>
							</div>

							<?php if ( $can_purchase && ! empty( $commerce['enabled'] ) ) : ?>
								<div class="snf-event__step-panel" data-step-panel="4" role="tabpanel" id="snf-event-panel-4" aria-labelledby="snf-event-tab-4" hidden>
									<p class="snf-event__step-copy"><?php esc_html_e( 'Step 4 — Tickets. Select your tier, add-ons, upsells, and immerse interactivities.', 'super-ninety-four' ); ?></p>
									<?php
									get_template_part(
										'template-parts/ticket',
										'commerce-fields',
										array(
											'commerce' => $commerce,
											'context'  => 'event',
										)
									);
									?>
									<div class="snf-event__step-actions">
										<button type="button" class="snf-event__step-back" data-snf-prev-step>
											<?php esc_html_e( 'Back', 'super-ninety-four' ); ?>
										</button>
										<button type="submit" class="snf-event__cta single_add_to_cart_button" id="snf-event-cta" name="add-to-cart" value="<?php echo esc_attr( (string) $product_id ); ?>">
											<?php esc_html_e( 'Add tickets to cart', 'super-ninety-four' ); ?>
										</button>
									</div>
									<?php if ( ! empty( $event['cart_url'] ) ) : ?>
										<p class="snf-event__cart-note">
											<a href="<?php echo esc_url( (string) $event['cart_url'] ); ?>"><?php esc_html_e( 'View cart', 'super-ninety-four' ); ?></a>
										</p>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						<?php else : ?>
							<div class="snf-event__step-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Non-member admissions steps', 'super-ninety-four' ); ?>">
								<button type="button" class="snf-event__step-tab is-active" role="tab" id="snf-event-guest-tab-1" aria-controls="snf-event-guest-panel-1" aria-selected="true" data-step="1">
									<?php esc_html_e( 'Contact', 'super-ninety-four' ); ?>
								</button>
								<button type="button" class="snf-event__step-tab" role="tab" id="snf-event-guest-tab-2" aria-controls="snf-event-guest-panel-2" aria-selected="false" data-step="2">
									<?php esc_html_e( 'Billing', 'super-ninety-four' ); ?>
								</button>
							</div>

							<div class="snf-event__step-panel is-active" data-step-panel="1" role="tabpanel" id="snf-event-guest-panel-1" aria-labelledby="snf-event-guest-tab-1">
								<p class="snf-event__step-copy"><?php esc_html_e( 'Step 1 — Contact details. Introduce yourself to the admissions desk.', 'super-ninety-four' ); ?></p>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Full name', 'super-ninety-four' ); ?></span>
									<input type="text" name="snf_event_name" id="snf-event-name" autocomplete="name" required>
								</label>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Email', 'super-ninety-four' ); ?></span>
									<input type="email" name="snf_event_email" id="snf-event-email" autocomplete="email" required>
								</label>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Phone', 'super-ninety-four' ); ?></span>
									<input type="tel" name="snf_event_phone" id="snf-event-phone" autocomplete="tel" required>
								</label>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Organization / firm', 'super-ninety-four' ); ?></span>
									<input type="text" name="snf_event_org" id="snf-event-org" autocomplete="organization">
								</label>
								<button type="button" class="snf-event__step-next" data-snf-next-step>
									<?php esc_html_e( 'Continue to billing details', 'super-ninety-four' ); ?>
								</button>
							</div>

							<div class="snf-event__step-panel" data-step-panel="2" role="tabpanel" id="snf-event-guest-panel-2" aria-labelledby="snf-event-guest-tab-2" hidden>
								<p class="snf-event__step-copy"><?php esc_html_e( 'Step 2 — Billing details. Choose a membership plan to complete open admission.', 'super-ninety-four' ); ?></p>
								<?php if ( ! empty( $initiation['amount'] ) ) : ?>
									<p class="snf-event__step-meta">
										<?php
										printf(
											/* translators: 1: initiation fee amount */
											esc_html__( 'Initiation fee for all plans: %s', 'super-ninety-four' ),
											esc_html( $initiation['amount'] )
										);
										?>
									</p>
								<?php endif; ?>
								<?php if ( ! empty( $commerce['enabled'] ) ) : ?>
									<div class="snf-event__ticket-preview">
										<?php
										get_template_part(
											'template-parts/ticket',
											'commerce-fields',
											array(
												'commerce'      => $commerce,
												'context'       => 'event',
												'show_heading'  => true,
											)
										);
										?>
										<p class="snf-event__step-meta">
											<?php esc_html_e( 'Ticket options unlock after membership is approved. Your selections below are saved with the application.', 'super-ninety-four' ); ?>
										</p>
									</div>
								<?php endif; ?>
								<fieldset class="snf-event__plans">
									<legend class="snf-event__field-label"><?php esc_html_e( 'Membership plan', 'super-ninety-four' ); ?></legend>
									<?php foreach ( $plan_list as $index => $plan ) : ?>
										<label class="snf-event__plan">
											<input
												type="radio"
												name="snf_event_plan"
												value="<?php echo esc_attr( $plan['slug'] ); ?>"
												<?php checked( 0 === (int) $index ); ?>
												required
											>
											<?php if ( ! empty( $plan['image'] ) ) : ?>
												<span class="snf-event__plan-media" aria-hidden="true">
													<img
														src="<?php echo esc_url( (string) $plan['image'] ); ?>"
														alt=""
														width="96"
														height="72"
														loading="lazy"
														decoding="async"
													>
												</span>
											<?php endif; ?>
											<span class="snf-event__plan-body">
												<span class="snf-event__plan-name"><?php echo esc_html( $plan['name'] ); ?></span>
												<span class="snf-event__plan-price">
													<?php echo esc_html( $plan['price'] . $plan['cadence'] ); ?>
													<span><?php echo esc_html( $plan['term'] ); ?></span>
												</span>
												<span class="snf-event__plan-note"><?php echo esc_html( $plan['note'] ); ?></span>
											</span>
										</label>
									<?php endforeach; ?>
								</fieldset>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Cardholder name', 'super-ninety-four' ); ?></span>
									<input type="text" name="snf_event_card_name" id="snf-event-card-name" autocomplete="cc-name" required>
								</label>
								<label class="snf-event__field">
									<span><?php esc_html_e( 'Billing ZIP / postal', 'super-ninety-four' ); ?></span>
									<input type="text" name="snf_event_billing_zip" id="snf-event-billing-zip" autocomplete="postal-code" required>
								</label>
								<div class="snf-event__step-actions">
									<button type="button" class="snf-event__step-back" data-snf-prev-step>
										<?php esc_html_e( 'Back', 'super-ninety-four' ); ?>
									</button>
									<?php if ( ! empty( $event['apply_url'] ) ) : ?>
										<a
											class="snf-event__cta"
											id="snf-event-cta"
											href="<?php echo esc_url( add_query_arg( 'snf_event', (string) ( $event['id'] ?? '' ), (string) $event['apply_url'] ) ); ?>"
											data-snf-apply-redirect
										>
											<?php esc_html_e( 'Continue to membership apply', 'super-ninety-four' ); ?>
										</a>
									<?php else : ?>
										<button type="submit" class="snf-event__cta" id="snf-event-cta">
											<?php esc_html_e( 'Submit admissions request', 'super-ninety-four' ); ?>
										</button>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>

						<p class="snf-event__form-status" data-snf-form-status hidden role="status"></p>
					</form>
					<?php endif; ?>
				</aside>
			</div>
		</article>
	</section>
</main>
