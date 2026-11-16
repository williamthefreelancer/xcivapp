<?php
/**
 * Hub page hero / intro.
 *
 * @var string $args['eyebrow']
 * @var string $args['title']
 * @var string $args['lede']
 * @var array  $args['actions'] Optional CTA links.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eyebrow = isset( $args['eyebrow'] ) ? (string) $args['eyebrow'] : '';
$title   = isset( $args['title'] ) ? (string) $args['title'] : get_the_title();
$lede    = isset( $args['lede'] ) ? (string) $args['lede'] : '';
$actions = isset( $args['actions'] ) && is_array( $args['actions'] ) ? $args['actions'] : array();
?>
<header class="snf-hub__hero" data-figma-slot="hub-hero">
	<?php if ( $eyebrow ) : ?>
		<p class="snf-hub__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
	<?php endif; ?>
	<h1 class="snf-hub__title"><?php echo esc_html( $title ); ?></h1>
	<?php if ( $lede ) : ?>
		<p class="snf-hub__lede"><?php echo esc_html( $lede ); ?></p>
	<?php endif; ?>
	<?php if ( $actions ) : ?>
		<div class="snf-hub__actions">
			<?php foreach ( $actions as $index => $action ) : ?>
				<a
					class="snf-hub__action<?php echo 0 === $index ? ' snf-hub__action--primary' : ''; ?>"
					href="<?php echo esc_url( $action['url'] ); ?>"
				>
					<?php echo esc_html( $action['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</header>
