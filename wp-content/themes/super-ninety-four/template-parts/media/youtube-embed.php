<?php
/**
 * Shared YouTube / iframe embed.
 *
 * @package Super_Ninety_Four
 * @var array<string, mixed> $args {
 *     @type string $src     Embed URL or YouTube ID/URL.
 *     @type string $title   Accessible title.
 *     @type string $poster  Optional poster image when src empty.
 *     @type string $class   Optional extra class on wrapper.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$src    = isset( $args['src'] ) ? (string) $args['src'] : '';
$title  = isset( $args['title'] ) ? (string) $args['title'] : __( 'Video', 'super-ninety-four' );
$poster = isset( $args['poster'] ) ? (string) $args['poster'] : '';
$class  = isset( $args['class'] ) ? (string) $args['class'] : '';

$embed = function_exists( 'snf_normalize_video_embed_src' )
	? snf_normalize_video_embed_src( $src )
	: esc_url_raw( $src );

$wrapper_class = trim( 'snf-media-embed ' . $class );
?>
<div class="<?php echo esc_attr( $wrapper_class ); ?>">
	<?php if ( $embed ) : ?>
		<iframe
			src="<?php echo esc_url( $embed ); ?>"
			title="<?php echo esc_attr( $title ); ?>"
			loading="lazy"
			allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
			allowfullscreen
		></iframe>
	<?php elseif ( $poster ) : ?>
		<figure class="snf-media-embed__poster">
			<img
				src="<?php echo esc_url( $poster ); ?>"
				alt="<?php echo esc_attr( $title ); ?>"
				width="1200"
				height="675"
				decoding="async"
			>
		</figure>
	<?php else : ?>
		<span class="snf-media-embed__empty" aria-hidden="true"></span>
	<?php endif; ?>
</div>
