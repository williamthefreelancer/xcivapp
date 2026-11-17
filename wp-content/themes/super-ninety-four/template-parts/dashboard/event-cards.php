<?php
/**
 * Event listing card grid / slider track.
 *
 * @var array  $args['cards']      Card definitions.
 * @var string $args['empty_text'] Optional empty-state copy.
 * @var bool   $args['slider']     Render as horizontal slider track.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cards      = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
$empty_text = isset( $args['empty_text'] ) ? (string) $args['empty_text'] : __( 'No listings available yet.', 'super-ninety-four' );
$is_slider  = ! empty( $args['slider'] );
$card_count = count( $cards );
$list_class = $is_slider ? 'snf-dashboard__cards snf-dashboard__cards--slider' : 'snf-dashboard__cards';
if ( ! $is_slider && $card_count >= 4 ) {
	$list_class .= ' snf-dashboard__cards--four';
}
?>
<?php if ( $cards ) : ?>
	<div class="<?php echo $is_slider ? 'snf-dashboard__slider' : 'snf-dashboard__cards-wrap'; ?>" <?php echo $is_slider ? 'data-figma-slot="listing-slider-track"' : ''; ?>>
		<ul class="<?php echo esc_attr( $list_class ); ?>" <?php echo $is_slider ? 'data-slider-track' : ''; ?>>
			<?php foreach ( $cards as $card ) : ?>
				<li class="snf-dashboard__card-item">
					<a class="snf-dashboard__card" href="<?php echo esc_url( $card['url'] ); ?>">
						<?php if ( ! empty( $card['image'] ) ) : ?>
							<img
								class="snf-dashboard__card-media"
								src="<?php echo esc_url( $card['image'] ); ?>"
								alt=""
								loading="lazy"
								decoding="async"
							>
						<?php else : ?>
							<span class="snf-dashboard__card-media snf-dashboard__card-media--empty" aria-hidden="true"></span>
						<?php endif; ?>
						<span class="snf-dashboard__card-body">
							<span class="snf-dashboard__card-title"><?php echo esc_html( $card['title'] ); ?></span>
							<?php if ( ! empty( $card['meta'] ) ) : ?>
								<span class="snf-dashboard__card-meta"><?php echo esc_html( $card['meta'] ); ?></span>
							<?php endif; ?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php else : ?>
	<p class="snf-dashboard__empty"><?php echo esc_html( $empty_text ); ?></p>
<?php endif; ?>
