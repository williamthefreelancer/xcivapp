<?php
/**
 * On-demand / live audio player shell.
 *
 * @package Super_Ninety_Four
 * @var array<string, mixed> $args {
 *     @type array  $item Audio item payload from snf_get_audio_item_data().
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$item = ( isset( $args['item'] ) && is_array( $args['item'] ) ) ? $args['item'] : array();
if ( ! $item ) {
	return;
}

$can_view = ! empty( $item['can_view'] );
$title    = isset( $item['title'] ) ? (string) $item['title'] : '';
$audio    = isset( $item['audio_url'] ) ? (string) $item['audio_url'] : '';
$embed    = isset( $item['embed_url'] ) ? (string) $item['embed_url'] : '';
$is_live  = ! empty( $item['is_live'] );
?>
<div class="snf-audio-player" data-audio-id="<?php echo esc_attr( (string) ( $item['id'] ?? '' ) ); ?>" data-live="<?php echo $is_live ? '1' : '0'; ?>">
	<div class="snf-audio-player__meta">
		<?php if ( ! empty( $item['thumbnail'] ) ) : ?>
			<img class="snf-audio-player__art" src="<?php echo esc_url( $item['thumbnail'] ); ?>" alt="" width="96" height="96" decoding="async">
		<?php endif; ?>
		<div class="snf-audio-player__copy">
			<?php if ( $is_live ) : ?>
				<p class="snf-audio-player__badge"><?php esc_html_e( 'Live channel', 'super-ninety-four' ); ?></p>
			<?php endif; ?>
			<h2 class="snf-audio-player__title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( ! empty( $item['channels'] ) && is_array( $item['channels'] ) ) : ?>
				<p class="snf-audio-player__channel"><?php echo esc_html( implode( ' · ', $item['channels'] ) ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $item['duration'] ) ) : ?>
				<p class="snf-audio-player__duration"><?php echo esc_html( (string) $item['duration'] ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! $can_view ) : ?>
		<p class="snf-audio-player__locked">
			<?php esc_html_e( 'This audio is for members or ticket holders.', 'super-ninety-four' ); ?>
			<?php if ( function_exists( 'snf_get_page_url' ) ) : ?>
				<a href="<?php echo esc_url( snf_get_page_url( 'member-login' ) ); ?>"><?php esc_html_e( 'Sign in', 'super-ninety-four' ); ?></a>
			<?php endif; ?>
		</p>
	<?php elseif ( $audio ) : ?>
		<audio class="snf-audio-player__el" controls preload="metadata" src="<?php echo esc_url( $audio ); ?>">
			<?php esc_html_e( 'Your browser does not support the audio element.', 'super-ninety-four' ); ?>
		</audio>
	<?php elseif ( $embed ) : ?>
		<iframe
			class="snf-audio-player__iframe"
			src="<?php echo esc_url( $embed ); ?>"
			title="<?php echo esc_attr( $title ); ?>"
			loading="lazy"
			allow="autoplay; clipboard-write; encrypted-media"
		></iframe>
	<?php else : ?>
		<p class="snf-audio-player__empty"><?php esc_html_e( 'No stream URL configured yet.', 'super-ninety-four' ); ?></p>
	<?php endif; ?>
</div>
