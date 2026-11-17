<?php
/**
 * Search events & services engine.
 * Submits to the WooCommerce shop (Google-style SERP).
 */
$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
if ( ! $shop_url ) {
	$shop_url = home_url( '/shop/' );
}
$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
?>
<form class="snf-search" role="search" method="get" action="<?php echo esc_url( $shop_url ); ?>" aria-label="<?php esc_attr_e( 'Search events and services', 'super-ninety-four' ); ?>">
	<label class="snf-search__field snf-search__field--query">
		<span class="screen-reader-text"><?php esc_html_e( 'Search events and services', 'super-ninety-four' ); ?></span>
		<input class="snf-search__input" type="search" name="q" value="<?php echo esc_attr( $query ); ?>" placeholder="<?php esc_attr_e( 'Search events & services', 'super-ninety-four' ); ?>" autocomplete="off">
	</label>
	<button class="snf-search__submit" type="submit">
		<span class="screen-reader-text"><?php esc_html_e( 'Search', 'super-ninety-four' ); ?></span>
		<svg class="snf-search__icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.75"/>
			<path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
		</svg>
	</button>
</form>
