<?php
/**
 * Public check-availability booking form (Figma 2194:39).
 *
 * @var array{
 *   commerce?: array<string, mixed>,
 *   product_id?: int,
 *   starts_at?: string,
 *   form_action?: string
 * } $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$commerce    = ( isset( $args['commerce'] ) && is_array( $args['commerce'] ) ) ? $args['commerce'] : array();
$product_id  = isset( $args['product_id'] ) ? absint( $args['product_id'] ) : 0;
$starts_at   = isset( $args['starts_at'] ) ? (string) $args['starts_at'] : '';
$form_action = isset( $args['form_action'] ) ? (string) $args['form_action'] : '';

if ( empty( $commerce['enabled'] ) || ! $product_id ) {
	return;
}

$tiers = isset( $commerce['tiers'] ) && is_array( $commerce['tiers'] ) ? array_slice( $commerce['tiers'], 0, 3 ) : array();
if ( ! $tiers ) {
	return;
}

$tz = wp_timezone();
$dt = $starts_at ? date_create( $starts_at, $tz ) : false;
if ( ! $dt ) {
	$dt = new DateTimeImmutable( 'now', $tz );
}

$year        = (int) $dt->format( 'Y' );
$month       = (int) $dt->format( 'n' );
$event_day   = (int) $dt->format( 'j' );
$date_label  = wp_date( 'F j, Y', $dt->getTimestamp(), $tz );
$time_label  = wp_date( 'g:i A', $dt->getTimestamp(), $tz );
$when_label  = $date_label . ' · ' . $time_label;
$month_label = wp_date( 'F Y', $dt->getTimestamp(), $tz );

$days_in_month = (int) $dt->format( 't' );
$first_dow     = (int) ( new DateTimeImmutable( sprintf( '%04d-%02d-01', $year, $month ), $tz ) )->format( 'w' ); // 0=Sun.
$cells         = array_fill( 0, $first_dow, 0 );
for ( $day = 1; $day <= $days_in_month; $day++ ) {
	$cells[] = $day;
}
while ( count( $cells ) % 7 !== 0 ) {
	$cells[] = 0;
}

$weekday_labels = array(
	__( 'SUN', 'super-ninety-four' ),
	__( 'MON', 'super-ninety-four' ),
	__( 'TUE', 'super-ninety-four' ),
	__( 'WED', 'super-ninety-four' ),
	__( 'THU', 'super-ninety-four' ),
	__( 'FRI', 'super-ninety-four' ),
	__( 'SAT', 'super-ninety-four' ),
);

$default_price = isset( $tiers[0]['price'] ) ? (float) $tiers[0]['price'] : 0;
$price_display = function_exists( 'snf_ticket_commerce_format_price' )
	? snf_ticket_commerce_format_price( $default_price )
	: '$' . number_format_i18n( $default_price, 0 );

if ( ! $form_action ) {
	$form_action = get_permalink( $product_id );
}
?>
<form
	class="snf-booking-form cart"
	data-snf-booking-form
	data-snf-ticket-commerce
	data-ticket-context="public-event"
	action="<?php echo esc_url( $form_action ); ?>"
	method="post"
	novalidate
>
	<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( (string) $product_id ); ?>">
	<input type="hidden" name="quantity" value="1">

	<header class="snf-booking-form__header">
		<h2 class="snf-booking-form__title"><?php esc_html_e( 'Check availability', 'super-ninety-four' ); ?></h2>
		<p class="snf-booking-form__lede"><?php esc_html_e( 'Select your experience', 'super-ninety-four' ); ?></p>
		<p class="snf-booking-form__guest-note"><?php esc_html_e( 'Guest checkout · membership not required', 'super-ninety-four' ); ?></p>
	</header>

	<section class="snf-booking-form__when" aria-label="<?php echo esc_attr( $month_label ); ?>">
		<p class="snf-booking-form__section-label"><?php esc_html_e( 'When', 'super-ninety-four' ); ?></p>
		<p class="snf-booking-form__when-date"><?php echo esc_html( $when_label ); ?></p>

		<div class="snf-booking-form__calendar" aria-hidden="true">
			<div class="snf-booking-form__weekdays">
				<?php foreach ( $weekday_labels as $label ) : ?>
					<span class="snf-booking-form__weekday"><?php echo esc_html( $label ); ?></span>
				<?php endforeach; ?>
			</div>

			<div class="snf-booking-form__days" role="list">
				<?php foreach ( $cells as $day ) : ?>
					<?php if ( ! $day ) : ?>
						<span class="snf-booking-form__day snf-booking-form__day--empty" role="listitem"></span>
					<?php else : ?>
						<span
							class="snf-booking-form__day<?php echo $day === $event_day ? ' is-event' : ''; ?>"
							role="listitem"
							<?php echo $day === $event_day ? ' aria-current="date"' : ''; ?>
						>
							<?php echo esc_html( (string) $day ); ?>
						</span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<fieldset class="snf-booking-form__options">
		<legend class="snf-booking-form__section-label"><?php esc_html_e( 'Experience', 'super-ninety-four' ); ?></legend>
		<?php foreach ( $tiers as $index => $tier ) : ?>
			<?php
			$tier_id    = isset( $tier['id'] ) ? (string) $tier['id'] : '';
			$tier_label = isset( $tier['label'] ) ? (string) $tier['label'] : $tier_id;
			$tier_desc  = isset( $tier['description'] ) ? (string) $tier['description'] : '';
			$tier_amt   = isset( $tier['price'] ) ? (float) $tier['price'] : 0;
			$tier_price = isset( $tier['price_html'] ) && $tier['price_html']
				? (string) $tier['price_html']
				: (
					function_exists( 'snf_ticket_commerce_format_price' )
						? snf_ticket_commerce_format_price( $tier_amt )
						: '$' . number_format_i18n( $tier_amt, 0 )
				);
			if ( ! $tier_id ) {
				continue;
			}
			?>
			<label class="snf-booking-form__option<?php echo 0 === (int) $index ? ' is-selected' : ''; ?>">
				<input
					type="radio"
					name="snf_ticket_tier"
					value="<?php echo esc_attr( $tier_id ); ?>"
					data-ticket-price="<?php echo esc_attr( (string) $tier_amt ); ?>"
					data-ticket-tier="<?php echo esc_attr( $tier_id ); ?>"
					<?php checked( 0 === (int) $index ); ?>
					required
				>
				<span class="snf-booking-form__option-body">
					<span class="snf-booking-form__option-label"><?php echo esc_html( $tier_label ); ?></span>
					<?php if ( $tier_desc ) : ?>
						<span class="snf-booking-form__option-desc"><?php echo esc_html( $tier_desc ); ?></span>
					<?php endif; ?>
				</span>
				<span class="snf-booking-form__option-price"><?php echo esc_html( $tier_price ); ?></span>
			</label>
		<?php endforeach; ?>
	</fieldset>

	<footer class="snf-booking-form__footer">
		<p class="snf-booking-form__total" data-snf-ticket-total>
			<span class="snf-booking-form__total-label"><?php esc_html_e( 'Total', 'super-ninety-four' ); ?></span>
			<strong class="snf-booking-form__total-value" data-snf-ticket-total-value><?php echo esc_html( $price_display ); ?></strong>
		</p>
		<button
			type="submit"
			class="snf-booking-form__cta single_add_to_cart_button"
			name="add-to-cart"
			value="<?php echo esc_attr( (string) $product_id ); ?>"
		>
			<?php esc_html_e( 'Check Availability', 'super-ninety-four' ); ?>
		</button>
	</footer>
</form>
