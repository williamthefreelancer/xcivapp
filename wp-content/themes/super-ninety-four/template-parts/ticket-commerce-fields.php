<?php
/**
 * Shared TicketSpice-style tier / addon / upsell / interactivity fields.
 *
 * Used by event admissions + workshop Woo order forms.
 *
 * @var array{
 *   commerce?: array<string, mixed>,
 *   context?: string,
 *   show_heading?: bool
 * } $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$commerce = ( isset( $args['commerce'] ) && is_array( $args['commerce'] ) )
	? $args['commerce']
	: array();

if ( empty( $commerce['enabled'] ) ) {
	return;
}

$context      = isset( $args['context'] ) ? sanitize_key( (string) $args['context'] ) : 'event';
$show_heading = ! isset( $args['show_heading'] ) || ! empty( $args['show_heading'] );
$tiers        = isset( $commerce['tiers'] ) && is_array( $commerce['tiers'] ) ? $commerce['tiers'] : array();
$addons       = isset( $commerce['addons'] ) && is_array( $commerce['addons'] ) ? $commerce['addons'] : array();
$upsells      = isset( $commerce['upsells'] ) && is_array( $commerce['upsells'] ) ? $commerce['upsells'] : array();
$interact     = isset( $commerce['interactivities'] ) && is_array( $commerce['interactivities'] ) ? $commerce['interactivities'] : array();
$experiences  = isset( $commerce['experiences'] ) && is_array( $commerce['experiences'] ) ? $commerce['experiences'] : array();

$has_options = $tiers || $addons || $upsells || $interact;
if ( ! $has_options ) {
	return;
}

$field_class = ( 'workshop' === $context ) ? 'snf-workshop__field' : 'snf-event__field';
?>
<div
	class="snf-ticket-commerce"
	data-snf-ticket-commerce
	data-ticket-context="<?php echo esc_attr( $context ); ?>"
>
	<?php if ( $show_heading ) : ?>
		<div class="snf-ticket-commerce__intro">
			<p class="snf-ticket-commerce__title"><?php esc_html_e( 'Ticket options', 'super-ninety-four' ); ?></p>
			<?php if ( $experiences ) : ?>
				<p class="snf-ticket-commerce__experiences">
					<?php
					$names = array_map(
						static function ( $term ) {
							return isset( $term['name'] ) ? (string) $term['name'] : '';
						},
						$experiences
					);
					$names = array_filter( $names );
					echo esc_html( implode( ' · ', $names ) );
					?>
				</p>
			<?php endif; ?>
			<p class="snf-ticket-commerce__lede">
				<?php esc_html_e( 'Choose a tier, then add upsells and immerse interactivities before checkout.', 'super-ninety-four' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $tiers ) : ?>
		<fieldset class="snf-ticket-commerce__group" data-ticket-group="tiers">
			<legend class="<?php echo esc_attr( $field_class ); ?> snf-ticket-commerce__legend">
				<?php esc_html_e( 'Ticket tier', 'super-ninety-four' ); ?>
			</legend>
			<?php foreach ( $tiers as $index => $tier ) : ?>
				<?php
				$tier_id    = isset( $tier['id'] ) ? (string) $tier['id'] : '';
				$tier_label = isset( $tier['label'] ) ? (string) $tier['label'] : $tier_id;
				$tier_price = isset( $tier['price_html'] ) ? (string) $tier['price_html'] : '';
				$tier_desc  = isset( $tier['description'] ) ? (string) $tier['description'] : '';
				$tier_amt   = isset( $tier['price'] ) ? (float) $tier['price'] : 0;
				if ( ! $tier_id ) {
					continue;
				}
				?>
				<label class="snf-ticket-commerce__option">
					<input
						type="radio"
						name="snf_ticket_tier"
						value="<?php echo esc_attr( $tier_id ); ?>"
						data-ticket-price="<?php echo esc_attr( (string) $tier_amt ); ?>"
						data-ticket-tier="<?php echo esc_attr( $tier_id ); ?>"
						<?php checked( 0 === (int) $index ); ?>
						required
					>
					<span class="snf-ticket-commerce__option-body">
						<span class="snf-ticket-commerce__option-name"><?php echo esc_html( $tier_label ); ?></span>
						<?php if ( $tier_price ) : ?>
							<span class="snf-ticket-commerce__option-price"><?php echo esc_html( $tier_price ); ?></span>
						<?php endif; ?>
						<?php if ( $tier_desc ) : ?>
							<span class="snf-ticket-commerce__option-desc"><?php echo esc_html( $tier_desc ); ?></span>
						<?php endif; ?>
					</span>
				</label>
			<?php endforeach; ?>
		</fieldset>
	<?php endif; ?>

	<?php
	$groups = array(
		'addons'          => array(
			'title'  => __( 'Add-ons', 'super-ninety-four' ),
			'rows'   => $addons,
			'prefix' => 'addon',
		),
		'upsells'         => array(
			'title'  => __( 'Upsells', 'super-ninety-four' ),
			'rows'   => $upsells,
			'prefix' => 'upsell',
		),
		'interactivities' => array(
			'title'  => __( 'Immerse interactivities', 'super-ninety-four' ),
			'rows'   => $interact,
			'prefix' => 'interactivity',
		),
	);
	?>

	<?php foreach ( $groups as $group_key => $group ) : ?>
		<?php if ( empty( $group['rows'] ) ) : ?>
			<?php continue; ?>
		<?php endif; ?>
		<fieldset class="snf-ticket-commerce__group" data-ticket-group="<?php echo esc_attr( $group_key ); ?>">
			<legend class="<?php echo esc_attr( $field_class ); ?> snf-ticket-commerce__legend">
				<?php echo esc_html( $group['title'] ); ?>
			</legend>
			<?php foreach ( $group['rows'] as $row ) : ?>
				<?php
				$row_id    = isset( $row['id'] ) ? (string) $row['id'] : '';
				$row_label = isset( $row['label'] ) ? (string) $row['label'] : $row_id;
				$row_price = isset( $row['price_html'] ) ? (string) $row['price_html'] : '';
				$row_desc  = isset( $row['description'] ) ? (string) $row['description'] : '';
				$row_amt   = isset( $row['price'] ) ? (float) $row['price'] : 0;
				$max_qty   = isset( $row['max_qty'] ) ? max( 1, (int) $row['max_qty'] ) : 1;
				$required  = ! empty( $row['required'] );
				$requires  = isset( $row['requires_tier'] ) && is_array( $row['requires_tier'] )
					? implode( ',', $row['requires_tier'] )
					: '';
				if ( ! $row_id ) {
					continue;
				}
				$input_name = 'snf_ticket_' . $group['prefix'] . '[' . $row_id . ']';
				?>
				<label
					class="snf-ticket-commerce__option snf-ticket-commerce__option--qty"
					data-requires-tier="<?php echo esc_attr( $requires ); ?>"
				>
					<span class="snf-ticket-commerce__option-body">
						<span class="snf-ticket-commerce__option-name">
							<?php echo esc_html( $row_label ); ?>
							<?php if ( $required ) : ?>
								<span class="snf-ticket-commerce__required"><?php esc_html_e( 'Required', 'super-ninety-four' ); ?></span>
							<?php endif; ?>
						</span>
						<?php if ( $row_price ) : ?>
							<span class="snf-ticket-commerce__option-price"><?php echo esc_html( $row_price ); ?></span>
						<?php endif; ?>
						<?php if ( $row_desc ) : ?>
							<span class="snf-ticket-commerce__option-desc"><?php echo esc_html( $row_desc ); ?></span>
						<?php endif; ?>
					</span>
					<span class="snf-ticket-commerce__qty">
						<span class="screen-reader-text">
							<?php
							printf(
								/* translators: %s: option label */
								esc_html__( 'Quantity for %s', 'super-ninety-four' ),
								esc_html( $row_label )
							);
							?>
						</span>
						<input
							type="number"
							class="snf-ticket-commerce__qty-input"
							name="<?php echo esc_attr( $input_name ); ?>"
							value="<?php echo esc_attr( $required ? '1' : '0' ); ?>"
							min="<?php echo esc_attr( $required ? '1' : '0' ); ?>"
							max="<?php echo esc_attr( (string) $max_qty ); ?>"
							step="1"
							inputmode="numeric"
							data-ticket-price="<?php echo esc_attr( (string) $row_amt ); ?>"
							data-ticket-option="<?php echo esc_attr( $row_id ); ?>"
							<?php echo $required ? 'required' : ''; ?>
						>
					</span>
				</label>
			<?php endforeach; ?>
		</fieldset>
	<?php endforeach; ?>

	<p class="snf-ticket-commerce__total" data-snf-ticket-total hidden>
		<span><?php esc_html_e( 'Estimated ticket total', 'super-ninety-four' ); ?></span>
		<strong data-snf-ticket-total-value></strong>
	</p>
</div>
