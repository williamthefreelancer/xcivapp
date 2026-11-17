<?php
/**
 * Account hub / dashboard side navigation.
 *
 * @var string $args['current'] Current page slug.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current = isset( $args['current'] ) ? (string) $args['current'] : snf_get_account_nav_current_slug();
$items   = snf_get_account_hub_nav();
?>
<nav class="snf-hub__nav" aria-label="<?php esc_attr_e( 'Account sections', 'super-ninety-four' ); ?>" data-figma-slot="account-nav">
	<ul class="snf-hub__nav-list">
		<?php foreach ( $items as $item ) : ?>
			<li class="snf-hub__nav-item" data-nav-group="<?php echo esc_attr( isset( $item['group'] ) ? $item['group'] : '' ); ?>">
				<a
					class="snf-hub__nav-link<?php echo ( $current === $item['slug'] ) ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( $item['url'] ); ?>"
					data-nav-slug="<?php echo esc_attr( $item['slug'] ); ?>"
					<?php echo ( $current === $item['slug'] ) ? ' aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $item['label'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
