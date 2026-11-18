<?php
/**
 * Expo members directory / register card grid.
 *
 * @var array  $args['cards']      Card definitions.
 * @var string $args['empty_text'] Optional empty-state copy.
 * @var string $args['variant']    register|directory.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cards      = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
$empty_text = isset( $args['empty_text'] ) ? (string) $args['empty_text'] : __( 'No listings available yet.', 'super-ninety-four' );
$variant    = isset( $args['variant'] ) ? sanitize_key( (string) $args['variant'] ) : 'directory';
$list_class = 'snf-expo-dir__grid';
if ( 'register' === $variant ) {
	$list_class .= ' snf-expo-dir__grid--register';
}
?>
<?php if ( $cards ) : ?>
	<ul class="<?php echo esc_attr( $list_class ); ?>" role="list">
		<?php foreach ( $cards as $card ) : ?>
			<?php
			$title    = isset( $card['title'] ) ? (string) $card['title'] : '';
			$meta     = isset( $card['meta'] ) ? (string) $card['meta'] : '';
			$blurb    = isset( $card['blurb'] ) ? (string) $card['blurb'] : ( isset( $card['snippet'] ) ? (string) $card['snippet'] : '' );
			$url      = isset( $card['url'] ) ? (string) $card['url'] : '#';
			$image    = isset( $card['image'] ) ? (string) $card['image'] : '';
			$initial  = isset( $card['initial'] ) ? (string) $card['initial'] : '';
			$cta      = isset( $card['cta'] ) ? (string) $card['cta'] : ( isset( $card['action'] ) ? (string) $card['action'] : __( 'View listing', 'super-ninety-four' ) );
			$kind     = isset( $card['kind'] ) ? (string) $card['kind'] : $variant;
			$item_cls = 'snf-expo-dir__item';
			if ( 'register' === $variant || 'register' === $kind ) {
				$item_cls .= ' snf-expo-dir__item--register';
			}
			?>
			<li class="<?php echo esc_attr( $item_cls ); ?>" data-kind="<?php echo esc_attr( $kind ); ?>">
				<a class="snf-expo-dir__card" href="<?php echo esc_url( $url ); ?>">
					<?php if ( $image ) : ?>
						<img
							class="snf-expo-dir__media"
							src="<?php echo esc_url( $image ); ?>"
							alt=""
							loading="lazy"
							decoding="async"
							width="640"
							height="400"
						>
					<?php else : ?>
						<span class="snf-expo-dir__avatar" aria-hidden="true"><?php echo esc_html( $initial ? $initial : 'SN' ); ?></span>
					<?php endif; ?>
					<span class="snf-expo-dir__body">
						<span class="snf-expo-dir__title"><?php echo esc_html( $title ); ?></span>
						<?php if ( $meta ) : ?>
							<span class="snf-expo-dir__meta"><?php echo esc_html( $meta ); ?></span>
						<?php endif; ?>
						<?php if ( $blurb ) : ?>
							<span class="snf-expo-dir__blurb"><?php echo esc_html( $blurb ); ?></span>
						<?php endif; ?>
						<span class="snf-expo-dir__cta"><?php echo esc_html( $cta ); ?></span>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
<?php else : ?>
	<p class="snf-expo-dir__empty"><?php echo esc_html( $empty_text ); ?></p>
<?php endif; ?>
