<?php
/**
 * Single Figma-ready panel card.
 *
 * @var array $args['card'] Card fields.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card = isset( $args['card'] ) && is_array( $args['card'] ) ? snf_normalize_panel_card( $args['card'] ) : snf_normalize_panel_card( array() );
$tone = sanitize_html_class( $card['tone'] ? $card['tone'] : 'neutral' );
$class = 'snf-panel-card snf-panel-card--' . $tone;
?>
<?php if ( $card['url'] ) : ?>
	<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $card['url'] ); ?>" data-figma-slot="panel-card">
<?php else : ?>
	<div class="<?php echo esc_attr( $class ); ?>" data-figma-slot="panel-card">
<?php endif; ?>

	<?php if ( $card['image'] ) : ?>
		<img class="snf-panel-card__media" src="<?php echo esc_url( $card['image'] ); ?>" alt="" loading="lazy" decoding="async">
	<?php endif; ?>

	<div class="snf-panel-card__body">
		<div class="snf-panel-card__top">
			<?php if ( $card['eyebrow'] ) : ?>
				<span class="snf-panel-card__eyebrow"><?php echo esc_html( $card['eyebrow'] ); ?></span>
			<?php endif; ?>
			<?php if ( $card['status'] ) : ?>
				<span class="snf-panel-card__status"><?php echo esc_html( $card['status'] ); ?></span>
			<?php endif; ?>
		</div>

		<h3 class="snf-panel-card__title"><?php echo esc_html( $card['title'] ); ?></h3>

		<?php if ( $card['meta'] ) : ?>
			<p class="snf-panel-card__meta"><?php echo esc_html( $card['meta'] ); ?></p>
		<?php endif; ?>

		<?php if ( $card['action'] ) : ?>
			<span class="snf-panel-card__action"><?php echo esc_html( $card['action'] ); ?></span>
		<?php endif; ?>
	</div>

<?php if ( $card['url'] ) : ?>
	</a>
<?php else : ?>
	</div>
<?php endif; ?>
