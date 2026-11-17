<?php
/**
 * Theme footer with legal / policy links.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$snf_legal_links = function_exists( 'snf_get_footer_legal_links' ) ? snf_get_footer_legal_links() : array();
?>
<footer class="snf-footer" role="contentinfo">
	<div class="snf-container snf-footer__inner">
		<div class="snf-footer__brand">
			<a class="snf-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img
					src="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/logo-white.svg' ); ?>"
					alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
					width="180"
					height="17"
					decoding="async"
				>
			</a>
			<p class="snf-footer__tagline">
				<?php
				esc_html_e(
					'The Super Ninety Four App is a club platform of SUPR XCIV PLATFORM HOLDINGS LLC & the SUPER NINETY FOUR EVENTS COMPANY. A registered limited liability holding company.',
					'super-ninety-four'
				);
				?>
			</p>
		</div>

		<?php if ( $snf_legal_links ) : ?>
			<nav class="snf-footer__nav" aria-label="<?php esc_attr_e( 'Legal and policies', 'super-ninety-four' ); ?>">
				<p class="snf-footer__nav-label"><?php esc_html_e( 'Policies', 'super-ninety-four' ); ?></p>
				<ul class="snf-footer__list">
					<?php foreach ( $snf_legal_links as $link ) : ?>
						<li class="snf-footer__item">
							<a class="snf-footer__link" href="<?php echo esc_url( $link['url'] ); ?>">
								<?php echo esc_html( $link['label'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
