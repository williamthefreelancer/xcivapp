<?php
/**
 * Theme Gutenberg blocks for blank-canvas marketing pages.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block folder names under /blocks.
 *
 * @return string[]
 */
function snf_get_theme_block_slugs() {
	return array(
		'page-hero',
		'cta-button',
		'listing-grid',
		'form-slot',
		'site-header',
		'site-footer',
		'php-view',
	);
}

/**
 * Register Super Ninety Four block category.
 *
 * @param array[] $categories Existing categories.
 * @return array[]
 */
function snf_block_categories( $categories ) {
	$categories[] = array(
		'slug'  => 'snf',
		'title' => __( 'Super Ninety Four', 'super-ninety-four' ),
		'icon'  => 'star-filled',
	);
	return $categories;
}
add_filter( 'block_categories_all', 'snf_block_categories' );

/**
 * Register dynamic theme blocks.
 */
function snf_register_theme_blocks() {
	$base_dir = get_template_directory() . '/blocks';
	$base_uri = get_template_directory_uri() . '/blocks';
	$deps     = array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' );

	foreach ( snf_get_theme_block_slugs() as $slug ) {
		$dir    = $base_dir . '/' . $slug;
		$script = 'snf-block-' . $slug;
		$cb     = 'snf_render_block_' . str_replace( '-', '_', $slug );

		if ( ! is_dir( $dir ) || ! function_exists( $cb ) ) {
			continue;
		}

		wp_register_script(
			$script,
			$base_uri . '/' . $slug . '/editor.js',
			$deps,
			SNF_VERSION,
			true
		);

		register_block_type(
			$dir,
			array(
				'editor_script'   => $script,
				'render_callback' => $cb,
			)
		);
	}
}
add_action( 'init', 'snf_register_theme_blocks' );

/**
 * Resolve CTA destination URL from block attributes.
 *
 * @param array<string, mixed> $attrs Block attributes.
 * @return string
 */
function snf_block_resolve_cta_url( array $attrs ) {
	$target = isset( $attrs['target'] ) ? sanitize_title( (string) $attrs['target'] ) : 'apply';
	$custom = isset( $attrs['customUrl'] ) ? esc_url_raw( (string) $attrs['customUrl'] ) : '';

	if ( 'custom' === $target && $custom ) {
		return $custom;
	}

	switch ( $target ) {
		case 'workshop':
			return function_exists( 'snf_get_workshop_landing_url' ) ? snf_get_workshop_landing_url() : home_url( '/' );
		case 'litter-pickup-service':
		case 'professional-line-sitting-courier-service':
		case 'janitorial-cleaning-service':
			return function_exists( 'snf_get_service_quote_product_url' )
				? snf_get_service_quote_product_url( $target )
				: home_url( '/' );
		case 'expo':
			return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
		case 'apply':
		default:
			return function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'apply' ) : home_url( '/apply/' );
	}
}

/**
 * Listing cards for a block source key.
 *
 * @param string $source Source slug.
 * @return array<int, array<string, string>>
 */
function snf_block_listing_cards_for_source( $source ) {
	$source = sanitize_title( (string) $source );
	switch ( $source ) {
		case 'member-events':
			return function_exists( 'snf_get_dashboard_member_event_cards' ) ? snf_get_dashboard_member_event_cards() : array();
		case 'non-member-events':
			return function_exists( 'snf_get_dashboard_non_member_event_cards' ) ? snf_get_dashboard_non_member_event_cards() : array();
		case 'reciprocal':
			return function_exists( 'snf_get_dashboard_reciprocal_cards' ) ? snf_get_dashboard_reciprocal_cards() : array();
		case 'service-inquiries':
			return function_exists( 'snf_get_dashboard_service_inquiry_cards' ) ? snf_get_dashboard_service_inquiry_cards() : array();
		case 'expo-scenic-drives':
			return function_exists( 'snf_get_expo_scenic_drives' ) ? snf_get_expo_scenic_drives() : array();
		case 'expo-gatherings':
			return function_exists( 'snf_get_expo_gathering_cards' ) ? snf_get_expo_gathering_cards() : array();
		case 'expo-live':
			return function_exists( 'snf_get_expo_live_broadcast_cards' ) ? snf_get_expo_live_broadcast_cards() : array();
		default:
			return array();
	}
}

/**
 * Render snf/page-hero.
 *
 * @param array<string, mixed> $attrs Attributes.
 * @return string
 */
function snf_render_block_page_hero( $attrs ) {
	$title = isset( $attrs['title'] ) ? (string) $attrs['title'] : '';
	$lede  = isset( $attrs['lede'] ) ? (string) $attrs['lede'] : '';

	if ( '' === $title ) {
		$title = get_the_title();
	}

	ob_start();
	?>
	<section class="snf-block snf-block-page-hero">
		<?php if ( $title ) : ?>
			<h1 class="snf-block-page-hero__title"><?php echo esc_html( $title ); ?></h1>
		<?php endif; ?>
		<?php if ( $lede ) : ?>
			<p class="snf-block-page-hero__lede"><?php echo esc_html( $lede ); ?></p>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render snf/cta-button.
 *
 * @param array<string, mixed> $attrs Attributes.
 * @return string
 */
function snf_render_block_cta_button( $attrs ) {
	$label = isset( $attrs['label'] ) ? (string) $attrs['label'] : __( 'Continue', 'super-ninety-four' );
	$url   = snf_block_resolve_cta_url( is_array( $attrs ) ? $attrs : array() );

	ob_start();
	?>
	<p class="snf-block snf-block-cta">
		<a class="snf-block-cta__button" href="<?php echo esc_url( $url ); ?>">
			<?php echo esc_html( $label ); ?>
		</a>
	</p>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render snf/listing-grid.
 *
 * @param array<string, mixed> $attrs Attributes.
 * @return string
 */
function snf_render_block_listing_grid( $attrs ) {
	$source = isset( $attrs['source'] ) ? (string) $attrs['source'] : 'service-inquiries';
	$cards  = snf_block_listing_cards_for_source( $source );

	ob_start();
	?>
	<section class="snf-block snf-block-listing-grid" data-source="<?php echo esc_attr( $source ); ?>">
		<?php if ( ! $cards ) : ?>
			<p class="snf-block-listing-grid__empty"><?php esc_html_e( 'No listings available.', 'super-ninety-four' ); ?></p>
		<?php else : ?>
			<ul class="snf-block-listing-grid__list">
				<?php foreach ( $cards as $card ) : ?>
					<li class="snf-block-listing-grid__item">
						<a class="snf-block-listing-grid__card" href="<?php echo esc_url( isset( $card['url'] ) ? $card['url'] : '#' ); ?>">
							<?php if ( ! empty( $card['image'] ) ) : ?>
								<span class="snf-block-listing-grid__media" style="background-image:url(<?php echo esc_url( $card['image'] ); ?>)"></span>
							<?php endif; ?>
							<span class="snf-block-listing-grid__body">
								<span class="snf-block-listing-grid__title"><?php echo esc_html( isset( $card['title'] ) ? $card['title'] : '' ); ?></span>
								<?php if ( ! empty( $card['meta'] ) ) : ?>
									<span class="snf-block-listing-grid__meta"><?php echo esc_html( $card['meta'] ); ?></span>
								<?php endif; ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render snf/form-slot (wrapper for contact / newsletter form blocks).
 *
 * @param array<string, mixed> $attrs      Attributes.
 * @param string               $content    Inner blocks HTML.
 * @return string
 */
function snf_render_block_form_slot( $attrs, $content = '' ) {
	$heading = isset( $attrs['heading'] ) ? (string) $attrs['heading'] : '';
	$hint    = isset( $attrs['hint'] ) ? (string) $attrs['hint'] : '';
	$inner   = trim( (string) $content );

	ob_start();
	?>
	<section class="snf-block snf-block-form-slot">
		<?php if ( $heading ) : ?>
			<h2 class="snf-block-form-slot__heading"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>
		<?php if ( $inner ) : ?>
			<div class="snf-block-form-slot__inner">
				<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks already sanitized by WP. ?>
			</div>
		<?php else : ?>
			<p class="snf-block-form-slot__hint">
				<?php
				echo esc_html(
					$hint
						? $hint
						: __( 'Add a form or shortcode block here for Contact or Newsletter submissions.', 'super-ninety-four' )
				);
				?>
			</p>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render snf/site-header.
 *
 * @return string
 */
function snf_render_block_site_header() {
	ob_start();
	get_template_part( 'template-parts/site', 'header' );
	return (string) ob_get_clean();
}

/**
 * Render snf/site-footer.
 *
 * @return string
 */
function snf_render_block_site_footer() {
	ob_start();
	get_template_part( 'template-parts/site', 'footer' );
	return (string) ob_get_clean();
}

/**
 * Render snf/php-view — embeds a legacy PHP template without document chrome.
 *
 * @param array<string, mixed> $attrs Attributes.
 * @return string
 */
function snf_render_block_php_view( $attrs ) {
	$slug = isset( $attrs['view'] ) ? sanitize_title( (string) $attrs['view'] ) : '';
	if ( ! $slug || ! function_exists( 'snf_fse_render_php_view' ) ) {
		return '';
	}
	return snf_fse_render_php_view( $slug );
}
