<?php
/**
 * Template Name: Funnel Offer
 * Description: Minimal chrome for post-purchase upsell / downsell pages. Build content with SNF Funnel patterns and [snf_funnel_actions].
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug  = get_post_field( 'post_name', get_queried_object_id() );
$title = get_the_title();
$step  = isset( $_GET['snf_step'] ) ? sanitize_key( wp_unslash( $_GET['snf_step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

get_header( 'empty' );
?>
<main
	class="snf-page snf-page--funnel snf-funnel-offer"
	data-figma-ready="true"
	data-page-slug="<?php echo esc_attr( $slug ); ?>"
	data-funnel-step="<?php echo esc_attr( $step ); ?>"
	aria-label="<?php echo esc_attr( $title ); ?>"
>
	<div class="snf-funnel-offer__canvas snf-container" data-figma-slot="<?php echo esc_attr( $slug ); ?>">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				$content = trim( (string) get_the_content() );
				if ( '' !== $content ) :
					?>
					<div class="snf-funnel-offer__content snf-page__plugin-content">
						<?php the_content(); ?>
					</div>
				<?php else : ?>
					<header class="snf-funnel-offer__fallback">
						<p class="snf-funnel-offer__eyebrow"><?php esc_html_e( 'One-time offer', 'super-ninety-four' ); ?></p>
						<h1 class="snf-funnel-offer__title"><?php echo esc_html( $title ); ?></h1>
						<p class="snf-funnel-offer__lede">
							<?php esc_html_e( 'Add your offer copy in the editor, or insert the Funnel Upsell / Downsell pattern.', 'super-ninety-four' ); ?>
						</p>
						<?php echo do_shortcode( '[snf_funnel_actions]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</header>
				<?php endif; ?>
			<?php endwhile; ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer( 'empty' );
