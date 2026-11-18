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
$audience   = ( isset( $event['audience'] ) && 'non-members' === $event['audience'] ) ? 'non-members' : 'members';
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
?>
<main
	class="snf-page snf-event"
	data-figma-ready="true"
	data-figma-node="<?php echo esc_attr( $figma_node ); ?>"
	data-admission-audience="<?php echo esc_attr( $audience ); ?>"
>
	<section class="snf-event__shell snf-container" aria-label="<?php echo esc_attr( $event['title'] ); ?>">
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
				<a class="snf-event__avatar" href="<?php echo esc_url( home_url( '/' ) ); ?>">
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

				<aside class="snf-event__apply" aria-label="<?php esc_attr_e( 'Event admissions', 'super-ninety-four' ); ?>">
					<h2 class="snf-event__apply-title"><?php esc_html_e( 'Apply to attend this upcoming event', 'super-ninety-four' ); ?></h2>

					<form
						class="snf-event__steps"
						data-snf-event-steps
						data-admission-audience="<?php echo esc_attr( $audience ); ?>"
						action="#"
						method="post"
						novalidate
					>
						<input type="hidden" name="snf_event_id" value="<?php echo esc_attr( (string) ( $event['id'] ?? '' ) ); ?>">
						<input type="hidden" name="snf_event_audience" value="<?php echo esc_attr( $audience ); ?>">

						<?php if ( $is_member ) : ?>
							<div class="snf-event__step-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Member admissions steps', 'super-ninety-four' ); ?>">
								<button type="button" class="snf-event__step-tab is-active" role="tab" aria-selected="true" data-step="1">
									<?php esc_html_e( 'Profile', 'super-ninety-four' ); ?>
								</button>
								<button type="button" class="snf-event__step-tab" role="tab" aria-selected="false" data-step="2">
									<?php esc_html_e( 'Contact', 'super-ninety-four' ); ?>
								</button>
								<button type="button" class="snf-event__step-tab" role="tab" aria-selected="false" data-step="3">
									<?php esc_html_e( 'Intent', 'super-ninety-four' ); ?>
								</button>
							</div>

							<div class="snf-event__step-panel is-active" data-step-panel="1" role="tabpanel">
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

							<div class="snf-event__step-panel" data-step-panel="2" role="tabpanel" hidden>
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

							<div class="snf-event__step-panel" data-step-panel="3" role="tabpanel" hidden>
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
									<button type="submit" class="snf-event__cta" id="snf-event-cta">
										<?php esc_html_e( 'Submit admissions request', 'super-ninety-four' ); ?>
									</button>
								</div>
							</div>
						<?php else : ?>
							<div class="snf-event__step-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Non-member admissions steps', 'super-ninety-four' ); ?>">
								<button type="button" class="snf-event__step-tab is-active" role="tab" aria-selected="true" data-step="1">
									<?php esc_html_e( 'Contact', 'super-ninety-four' ); ?>
								</button>
								<button type="button" class="snf-event__step-tab" role="tab" aria-selected="false" data-step="2">
									<?php esc_html_e( 'Billing', 'super-ninety-four' ); ?>
								</button>
							</div>

							<div class="snf-event__step-panel is-active" data-step-panel="1" role="tabpanel">
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

							<div class="snf-event__step-panel" data-step-panel="2" role="tabpanel" hidden>
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
									<button type="submit" class="snf-event__cta" id="snf-event-cta">
										<?php esc_html_e( 'Submit admissions request', 'super-ninety-four' ); ?>
									</button>
								</div>
							</div>
						<?php endif; ?>

						<p class="snf-event__form-status" data-snf-form-status hidden role="status"></p>
					</form>
				</aside>
			</div>
		</article>
	</section>
</main>
