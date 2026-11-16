<?php
/**
 * Search events & services engine.
 * Submits to the events archive (Google-style results).
 */
$events_url = get_post_type_archive_link( 'event' );
if ( ! $events_url ) {
	$events_url = home_url( '/events/' );
}
$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$where = isset( $_GET['where'] ) ? sanitize_text_field( wp_unslash( $_GET['where'] ) ) : '';
$when  = isset( $_GET['when'] ) ? sanitize_text_field( wp_unslash( $_GET['when'] ) ) : '';
?>
<form class="snf-search" role="search" method="get" action="<?php echo esc_url( $events_url ); ?>" aria-label="<?php esc_attr_e( 'Search events and services', 'super-ninety-four' ); ?>">
	<label class="snf-search__field snf-search__field--query">
		<span class="screen-reader-text"><?php esc_html_e( 'Search events and services', 'super-ninety-four' ); ?></span>
		<input class="snf-search__input" type="search" name="q" value="<?php echo esc_attr( $query ); ?>" placeholder="<?php esc_attr_e( 'Search events & services', 'super-ninety-four' ); ?>" autocomplete="off">
	</label>
	<label class="snf-search__field snf-search__field--where">
		<span class="screen-reader-text"><?php esc_html_e( 'Location', 'super-ninety-four' ); ?></span>
		<input class="snf-search__input" type="text" name="where" value="<?php echo esc_attr( $where ); ?>" placeholder="<?php esc_attr_e( 'Where', 'super-ninety-four' ); ?>" autocomplete="off">
	</label>
	<label class="snf-search__field snf-search__field--when">
		<span class="screen-reader-text"><?php esc_html_e( 'When', 'super-ninety-four' ); ?></span>
		<input class="snf-search__input" type="text" name="when" value="<?php echo esc_attr( $when ); ?>" placeholder="<?php esc_attr_e( 'When', 'super-ninety-four' ); ?>" autocomplete="off">
	</label>
	<button class="snf-search__submit" type="submit">
		<span class="screen-reader-text"><?php esc_html_e( 'Search', 'super-ninety-four' ); ?></span>
		<svg class="snf-search__icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.75"/>
			<path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
		</svg>
	</button>
</form>
