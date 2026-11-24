<?php
/**
 * Visible breadcrumb nav for no-header event / Select Events surfaces.
 *
 * @package Super_Ninety_Four
 * @var array{
 *   post?: int|WP_Post|null,
 *   class?: string
 * } $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'snf_get_event_nav_breadcrumbs' ) ) {
	return;
}

$post  = isset( $args['post'] ) ? $args['post'] : null;
$extra = isset( $args['class'] ) ? sanitize_html_class( (string) $args['class'] ) : '';
$items = snf_get_event_nav_breadcrumbs( $post );

if ( ! $items ) {
	return;
}

$nav_class = 'snf-crumbs';
if ( $extra ) {
	$nav_class .= ' ' . $extra;
}
?>
<nav class="<?php echo esc_attr( $nav_class ); ?>" aria-label="<?php esc_attr_e( 'Breadcrumb', 'super-ninety-four' ); ?>">
	<ol class="snf-crumbs__list">
		<?php foreach ( $items as $item ) : ?>
			<?php
			$label   = isset( $item['label'] ) ? (string) $item['label'] : '';
			$url     = isset( $item['url'] ) ? (string) $item['url'] : '';
			$current = ! empty( $item['current'] ) || '' === $url;
			if ( '' === $label ) {
				continue;
			}
			?>
			<li class="snf-crumbs__item<?php echo $current ? ' snf-crumbs__item--current' : ''; ?>">
				<?php if ( $current ) : ?>
					<span class="snf-crumbs__current" aria-current="page"><?php echo esc_html( $label ); ?></span>
				<?php else : ?>
					<a class="snf-crumbs__link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
