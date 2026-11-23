<?php
/**
 * Live / hybrid venue broadcast panel for event landings.
 *
 * @package Super_Ninety_Four
 * @var array<string, mixed> $args {
 *     @type int   $event_id Event/product ID.
 *     @type array $broadcast Payload from snf_get_event_broadcast_data().
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event_id   = isset( $args['event_id'] ) ? (int) $args['event_id'] : 0;
$broadcast  = ( isset( $args['broadcast'] ) && is_array( $args['broadcast'] ) ) ? $args['broadcast'] : array();
if ( ! $broadcast && $event_id && function_exists( 'snf_get_event_broadcast_data' ) ) {
	$broadcast = snf_get_event_broadcast_data( $event_id );
}
if ( ! $broadcast || empty( $broadcast['mode'] ) ) {
	return;
}

$mode = (string) $broadcast['mode'];
if ( 'in_person' === $mode && ( empty( $broadcast['status'] ) || 'none' === $broadcast['status'] ) ) {
	return;
}
?>
<section
	class="snf-live"
	data-snf-live-room="<?php echo esc_attr( (string) ( $broadcast['room_id'] ?? '' ) ); ?>"
	data-snf-live-provider="<?php echo esc_attr( (string) ( $broadcast['provider'] ?? '' ) ); ?>"
	data-snf-live-event="<?php echo esc_attr( (string) $event_id ); ?>"
	aria-label="<?php esc_attr_e( 'Live and hybrid broadcast', 'super-ninety-four' ); ?>"
>
	<header class="snf-live__header">
		<p class="snf-live__eyebrow">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: attendance mode, 2: live status */
					__( '%1$s · %2$s', 'super-ninety-four' ),
					(string) ( $broadcast['mode_label'] ?? $mode ),
					(string) ( $broadcast['status_label'] ?? '' )
				)
			);
			?>
		</p>
		<h2 class="snf-live__title">
			<?php
			echo ! empty( $broadcast['is_live'] )
				? esc_html__( 'Join the live session', 'super-ninety-four' )
				: esc_html__( 'Broadcast & venue details', 'super-ninety-four' );
			?>
		</h2>
	</header>

	<?php if ( ! empty( $broadcast['venue_name'] ) || ! empty( $broadcast['venue_address'] ) ) : ?>
		<div class="snf-live__venue">
			<?php if ( ! empty( $broadcast['venue_name'] ) ) : ?>
				<p class="snf-live__venue-name"><?php echo esc_html( (string) $broadcast['venue_name'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $broadcast['venue_address'] ) ) : ?>
				<p class="snf-live__venue-address"><?php echo esc_html( (string) $broadcast['venue_address'] ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $broadcast['stream_embed'] ) && in_array( (string) $broadcast['provider'], array( 'youtube_live', 'external' ), true ) ) : ?>
		<?php
		get_template_part(
			'template-parts/media/youtube',
			'embed',
			array(
				'src'   => (string) $broadcast['stream_embed'],
				'title' => __( 'Live stream', 'super-ninety-four' ),
				'class' => 'snf-live__video',
			)
		);
		?>
	<?php elseif ( ! empty( $broadcast['stream_url'] ) && 'external' === (string) $broadcast['provider'] ) : ?>
		<p class="snf-live__actions">
			<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( (string) $broadcast['stream_url'] ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Open webinar room', 'super-ninety-four' ); ?>
			</a>
		</p>
	<?php endif; ?>

	<?php if ( ! empty( $broadcast['audio_url'] ) ) : ?>
		<div class="snf-live__audio">
			<p class="snf-live__audio-label"><?php esc_html_e( 'Listen live (audio)', 'super-ninety-four' ); ?></p>
			<audio class="snf-live__audio-el" controls preload="none" src="<?php echo esc_url( (string) $broadcast['audio_url'] ); ?>"></audio>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $broadcast['webrtc_enabled'] ) ) : ?>
		<div class="snf-live__webrtc">
			<p class="snf-live__webrtc-copy">
				<?php esc_html_e( 'Browser audio room — host broadcasts; members listen in (WebRTC).', 'super-ninety-four' ); ?>
			</p>
			<div class="snf-live__webrtc-actions">
				<?php if ( ! empty( $broadcast['can_host'] ) ) : ?>
					<button type="button" class="snf-hub__action snf-hub__action--primary" data-snf-live-host>
						<?php esc_html_e( 'Host audio room', 'super-ninety-four' ); ?>
					</button>
				<?php endif; ?>
				<button type="button" class="snf-hub__action" data-snf-live-join>
					<?php esc_html_e( 'Join live audio', 'super-ninety-four' ); ?>
				</button>
				<button type="button" class="snf-hub__action" data-snf-live-leave hidden>
					<?php esc_html_e( 'Leave room', 'super-ninety-four' ); ?>
				</button>
			</div>
			<p class="snf-live__webrtc-status" data-snf-live-status aria-live="polite"></p>
			<audio class="snf-live__webrtc-remote" data-snf-live-remote autoplay playsinline></audio>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $broadcast['hybrid_notes'] ) ) : ?>
		<p class="snf-live__notes"><?php echo esc_html( (string) $broadcast['hybrid_notes'] ); ?></p>
	<?php endif; ?>
</section>
