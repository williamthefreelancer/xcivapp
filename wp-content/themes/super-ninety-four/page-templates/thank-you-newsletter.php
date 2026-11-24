<?php
/**
 * Template Name: Newsletter thank you
 * Description: Post-subscribe thank-you for The Zircoria Cryon Newsletter.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'subscribed'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$is_dup = ( 'duplicate' === $status );
$youtube = function_exists( 'snf_newsletter_youtube_url' ) ? snf_newsletter_youtube_url() : 'https://www.youtube.com/@williamslist';
$home    = home_url( '/' );

get_header();
?>
<main class="snf-page snf-newsletter-thanks-page" data-page-slug="thank-you-newsletter">
	<section class="snf-hero snf-hero--compact" aria-label="<?php esc_attr_e( 'Newsletter thank you', 'super-ninety-four' ); ?>">
		<div class="snf-hero__veil" aria-hidden="true"></div>
		<div class="snf-frame snf-hero__inner">
			<p class="snf-hero__brand snf-display"><?php esc_html_e( 'SUPER NINETY FOUR', 'super-ninety-four' ); ?></p>
			<h1 class="snf-hero__title"><?php esc_html_e( 'You’re on the list', 'super-ninety-four' ); ?></h1>
			<p class="snf-hero__lede">
				<?php
				echo esc_html(
					$is_dup
						? __( 'That email was already subscribed to The Zircoria Cryon Newsletter.', 'super-ninety-four' )
						: __( 'Welcome to The Zircoria Cryon Newsletter.', 'super-ninety-four' )
				);
				?>
			</p>
		</div>
	</section>

	<section class="snf-section snf-frame snf-newsletter-thanks" aria-labelledby="snf-newsletter-thanks-heading">
		<div class="snf-newsletter-thanks__panel">
			<h2 id="snf-newsletter-thanks-heading" class="snf-home-section__title">
				<?php esc_html_e( 'What happens next', 'super-ninety-four' ); ?>
			</h2>
			<p class="snf-home-section__lede snf-newsletter-thanks__lede">
				<?php esc_html_e( 'Live video and audio details are not posted publicly. You’ll get them through SureContact — watch your inbox for the next dispatch.', 'super-ninety-four' ); ?>
			</p>
			<ul class="snf-newsletter-thanks__list">
				<li><?php esc_html_e( 'New YouTube films land in your inbox when they publish', 'super-ninety-four' ); ?></li>
				<li><?php esc_html_e( 'Live room links and listen-along audio arrive via SureContact', 'super-ninety-four' ); ?></li>
				<li><?php esc_html_e( 'Private invitations and circuit drops stay off the public page', 'super-ninety-four' ); ?></li>
			</ul>
			<div class="snf-newsletter-thanks__actions">
				<a class="snf-home-btn snf-home-btn--primary" href="<?php echo esc_url( $home ); ?>">
					<?php esc_html_e( 'Back to home', 'super-ninety-four' ); ?>
				</a>
				<?php if ( $youtube ) : ?>
					<a class="snf-home-btn snf-home-btn--outline" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Watch on YouTube', 'super-ninety-four' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
