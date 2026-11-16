<?php
/**
 * Account hub side / rail navigation.
 *
 * @var string $args['current'] Current page slug.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current = isset( $args['current'] ) ? (string) $args['current'] : '';
$items   = snf_get_account_hub_nav();
?>
<nav class="snf-hub__nav" aria-label="<?php esc_attr_e( 'Account sections', 'super-ninety-four' ); ?>" data-figma-slot="account-nav">
	<ul class="snf-hub__nav-list">
		<?php foreach ( $items as $item ) : ?>
			<li class="snf-hub__nav-item">
				<a
					class="snf-hub__nav-link<?php echo ( $current === $item['slug'] ) ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( $item['url'] ); ?>"
					<?php echo ( $current === $item['slug'] ) ? ' aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $item['label'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
