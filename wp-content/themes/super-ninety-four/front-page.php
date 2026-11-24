<?php
/**
 * Front page — marketing one-pager (matches Nuxt home).
 */
get_header();

$home       = function_exists( 'snf_get_home_content' ) ? snf_get_home_content() : array();
$hero       = function_exists( 'snf_get_home_hero' ) ? snf_get_home_hero() : array();
$help       = isset( $home['help'] ) ? $home['help'] : array();
$audience   = isset( $home['audience'] ) ? $home['audience'] : array();
$for        = isset( $audience['for'] ) ? $audience['for'] : array();
$not_for    = isset( $audience['not_for'] ) ? $audience['not_for'] : array();
$membership = isset( $home['membership'] ) ? $home['membership'] : array();
$gatherings = isset( $home['gatherings'] ) ? $home['gatherings'] : array();
$newsletter = isset( $home['newsletter'] ) ? $home['newsletter'] : array();

$newsletter_status = isset( $_GET['snf_newsletter'] ) ? sanitize_key( wp_unslash( $_GET['snf_newsletter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$newsletter_youtube = isset( $newsletter['youtubeUrl'] )
	? (string) $newsletter['youtubeUrl']
	: ( function_exists( 'snf_newsletter_youtube_url' ) ? snf_newsletter_youtube_url() : 'https://www.youtube.com/@williamslist' );

$brand_name  = isset( $hero['brand'] ) ? (string) $hero['brand'] : __( 'SUPER NINETY FOUR', 'super-ninety-four' );
$hero_title  = isset( $hero['title'] ) ? (string) $hero['title'] : $brand_name;
$hero_lead   = isset( $hero['titleLead'] ) ? (string) $hero['titleLead'] : '';
$hero_focus  = isset( $hero['titleFocus'] ) ? (string) $hero['titleFocus'] : '';
$hero_lede   = isset( $hero['lede'] ) ? (string) $hero['lede'] : '';
$show_brand  = ! empty( $hero['showBrand'] );
/* Brand is the hero signal; hide a duplicate title when it matches the brand. */
$title_is_brand = ( '' !== $brand_name && 0 === strcasecmp( trim( $brand_name ), trim( $hero_title ) ) );
$title_is_primary = ( ! $title_is_brand || ! $show_brand );

$apply_url = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'apply' ) : home_url( '/apply/' );
$cta_label = isset( $hero['cta']['label'] ) ? (string) $hero['cta']['label'] : __( 'Apply', 'super-ninety-four' );
$cta_url   = isset( $hero['cta']['url'] ) ? snf_home_resolve_href( $hero['cta']['url'] ) : $apply_url;
$sec_label = isset( $hero['secondary']['label'] ) ? (string) $hero['secondary']['label'] : __( 'See how we help', 'super-ninety-four' );
$sec_url   = isset( $hero['secondary']['url'] ) ? snf_home_resolve_href( $hero['secondary']['url'] ) : '#help';

$mem_cta_label = isset( $membership['cta']['label'] ) ? (string) $membership['cta']['label'] : __( 'Apply for consideration', 'super-ninety-four' );
$mem_cta_url   = isset( $membership['cta']['url'] ) ? snf_home_resolve_href( $membership['cta']['url'] ) : $apply_url;
?>
<main class="snf-home" data-figma-ready="true">
	<div id="top" class="snf-home-top-anchor" aria-hidden="true"></div>

	<section class="snf-hero snf-hero--home" aria-label="<?php echo esc_attr( $brand_name ); ?>">
		<div class="snf-hero__veil" aria-hidden="true"></div>
		<div class="snf-frame snf-hero__inner">
			<?php if ( $show_brand && $brand_name ) : ?>
				<p class="snf-hero__brand snf-display"><?php echo esc_html( $brand_name ); ?></p>
			<?php endif; ?>

			<?php if ( $title_is_primary ) : ?>
				<?php if ( $hero_lead && $hero_focus ) : ?>
					<h1 class="snf-hero__title snf-hero__title--statement">
						<span class="snf-hero__title-lead"><?php echo esc_html( $hero_lead ); ?></span>
						<span class="snf-hero__title-focus"><?php echo esc_html( $hero_focus ); ?></span>
					</h1>
				<?php else : ?>
					<h1 class="snf-hero__title snf-hero__title--statement"><?php echo esc_html( $hero_title ); ?></h1>
				<?php endif; ?>
			<?php else : ?>
				<h1 class="screen-reader-text"><?php echo esc_html( $hero_title ); ?></h1>
			<?php endif; ?>

			<?php if ( $hero_lede ) : ?>
				<p class="snf-hero__lede"><?php echo esc_html( $hero_lede ); ?></p>
			<?php endif; ?>

			<div class="snf-hero__actions">
				<a class="snf-home-btn snf-home-btn--primary" href="<?php echo esc_url( $cta_url ); ?>">
					<?php echo esc_html( $cta_label ); ?>
				</a>
				<a class="snf-home-btn snf-home-btn--outline" href="<?php echo esc_url( $sec_url ); ?>">
					<?php echo esc_html( $sec_label ); ?>
				</a>
			</div>
		</div>
	</section>

	<?php if ( ! empty( $help['cards'] ) ) : ?>
		<section id="help" class="snf-section snf-frame snf-home-reveal" aria-labelledby="snf-home-help-heading">
			<header class="snf-home-section__header snf-home-section__header--center">
				<h2 id="snf-home-help-heading" class="snf-home-section__title"><?php echo esc_html( $help['title'] ); ?></h2>
				<?php if ( ! empty( $help['lede'] ) ) : ?>
					<p class="snf-home-section__lede"><?php echo esc_html( $help['lede'] ); ?></p>
				<?php endif; ?>
			</header>
			<div class="snf-home-overlay-grid">
				<?php foreach ( $help['cards'] as $card ) : ?>
					<a class="snf-home-overlay-card" href="<?php echo esc_url( snf_home_resolve_href( isset( $card['url'] ) ? $card['url'] : '' ) ); ?>">
						<?php if ( ! empty( $card['image'] ) ) : ?>
							<img
								class="snf-home-overlay-card__img"
								src="<?php echo esc_url( $card['image'] ); ?>"
								alt="<?php echo esc_attr( $card['title'] ); ?>"
								loading="lazy"
								decoding="async"
							>
						<?php endif; ?>
						<span class="snf-home-overlay-card__veil" aria-hidden="true"></span>
						<span class="snf-home-overlay-card__copy">
							<span class="snf-home-overlay-card__title"><?php echo esc_html( $card['title'] ); ?></span>
							<?php if ( ! empty( $card['body'] ) ) : ?>
								<span class="snf-home-overlay-card__body"><?php echo esc_html( $card['body'] ); ?></span>
							<?php elseif ( ! empty( $card['meta'] ) ) : ?>
								<span class="snf-home-overlay-card__body"><?php echo esc_html( $card['meta'] ); ?></span>
							<?php endif; ?>
							<span class="snf-home-overlay-card__cta">
								<?php echo esc_html( ! empty( $card['cta'] ) ? $card['cta'] : __( 'Open the desk', 'super-ninety-four' ) ); ?>
							</span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $gatherings['cards'] ) ) : ?>
		<section id="gatherings" class="snf-section snf-frame snf-home-reveal" aria-labelledby="snf-home-gatherings-heading">
			<header class="snf-home-section__header snf-home-section__header--center">
				<h2 id="snf-home-gatherings-heading" class="snf-home-section__title"><?php echo esc_html( $gatherings['title'] ); ?></h2>
				<?php if ( ! empty( $gatherings['lede'] ) ) : ?>
					<p class="snf-home-section__lede"><?php echo esc_html( $gatherings['lede'] ); ?></p>
				<?php endif; ?>
			</header>
			<div class="snf-home-gatherings snf-home-gatherings--two">
				<?php foreach ( $gatherings['cards'] as $card ) : ?>
					<article class="snf-home-gathering">
						<?php if ( ! empty( $card['image'] ) ) : ?>
							<div class="snf-home-gathering__media">
								<img
									class="snf-home-gathering__img"
									src="<?php echo esc_url( $card['image'] ); ?>"
									alt="<?php echo esc_attr( $card['title'] ); ?>"
									loading="lazy"
									decoding="async"
								>
							</div>
						<?php endif; ?>
						<div class="snf-home-gathering__copy">
							<h3 class="snf-home-gathering__title"><?php echo esc_html( $card['title'] ); ?></h3>
							<?php if ( ! empty( $card['price'] ) ) : ?>
								<p class="snf-home-gathering__price"><?php echo esc_html( $card['price'] ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $card['body'] ) ) : ?>
								<p class="snf-home-gathering__body"><?php echo esc_html( $card['body'] ); ?></p>
							<?php endif; ?>
							<a class="snf-home-btn snf-home-btn--primary snf-home-gathering__cta" href="<?php echo esc_url( snf_home_resolve_href( isset( $card['url'] ) ? $card['url'] : '' ) ); ?>">
								<?php echo esc_html( ! empty( $card['cta'] ) ? $card['cta'] : __( 'View event', 'super-ninety-four' ) ); ?>
							</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<section id="fit" class="snf-section snf-frame snf-home-reveal" aria-label="<?php esc_attr_e( 'Audience', 'super-ninety-four' ); ?>">
		<div class="snf-home-audience">
			<?php if ( ! empty( $for['cards'] ) ) : ?>
				<div>
					<h2 class="snf-home-section__title snf-home-audience__heading"><?php echo esc_html( $for['title'] ); ?></h2>
					<div class="snf-home-audience__cards">
						<?php foreach ( $for['cards'] as $card ) : ?>
							<article class="snf-home-audience__card">
								<h3 class="snf-home-audience__card-title"><?php echo esc_html( $card['title'] ); ?></h3>
								<p class="snf-home-audience__card-body"><?php echo esc_html( $card['body'] ); ?></p>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $not_for['cards'] ) ) : ?>
				<div>
					<h2 class="snf-home-section__title snf-home-audience__heading"><?php echo esc_html( $not_for['title'] ); ?></h2>
					<div class="snf-home-accordion">
						<?php foreach ( $not_for['cards'] as $card ) : ?>
							<details class="snf-home-accordion__item">
								<summary class="snf-home-accordion__summary"><?php echo esc_html( $card['title'] ); ?></summary>
								<p class="snf-home-accordion__body"><?php echo esc_html( $card['body'] ); ?></p>
							</details>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $membership ) : ?>
		<section id="pricing" class="snf-section snf-frame snf-home-reveal" aria-labelledby="snf-home-membership-heading">
			<header class="snf-home-section__header snf-home-section__header--center">
				<h2 id="snf-home-membership-heading" class="snf-home-section__title"><?php echo esc_html( $membership['title'] ); ?></h2>
				<?php if ( ! empty( $membership['lede'] ) ) : ?>
					<p class="snf-home-section__lede"><?php echo esc_html( $membership['lede'] ); ?></p>
				<?php endif; ?>
			</header>

			<?php if ( ! empty( $membership['initiation'] ) ) : ?>
				<p class="snf-home-membership__initiation">
					<span class="snf-home-membership__initiation-label"><?php echo esc_html( $membership['initiation']['label'] ); ?></span>
					—
					<strong class="snf-home-membership__initiation-amount"><?php echo esc_html( $membership['initiation']['amount'] ); ?></strong>
					<?php if ( ! empty( $membership['initiation']['meta'] ) ) : ?>
						<span class="snf-home-membership__initiation-meta">(<?php echo esc_html( $membership['initiation']['meta'] ); ?>)</span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $membership['plans'] ) ) : ?>
				<div class="snf-home-plans">
					<?php foreach ( $membership['plans'] as $plan ) : ?>
						<article class="snf-home-plan">
							<h3 class="snf-home-plan__name"><?php echo esc_html( $plan['name'] ); ?></h3>
							<p class="snf-home-plan__price">
								<span class="snf-home-plan__amount"><?php echo esc_html( $plan['price'] ); ?></span>
								<span class="snf-home-plan__meta"><?php echo esc_html( trim( ( isset( $plan['cadence'] ) ? $plan['cadence'] : '' ) . ' ' . ( isset( $plan['term'] ) ? $plan['term'] : '' ) ) ); ?></span>
							</p>
							<?php if ( ! empty( $plan['benefits'] ) ) : ?>
								<ul class="snf-home-plan__list">
									<?php foreach ( $plan['benefits'] as $benefit ) : ?>
										<li><?php echo esc_html( $benefit ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="snf-home-section__actions snf-home-section__actions--center">
				<a class="snf-home-btn snf-home-btn--primary" href="<?php echo esc_url( $mem_cta_url ); ?>">
					<?php echo esc_html( $mem_cta_label ); ?>
				</a>
			</div>
		</section>
	<?php endif; ?>

	<section id="newsletter" class="snf-section snf-frame snf-home-reveal" aria-labelledby="snf-home-newsletter-heading">
		<div class="snf-home-newsletter">
			<header class="snf-home-newsletter__header">
				<h2 id="snf-home-newsletter-heading" class="snf-home-section__title">
					<?php echo esc_html( isset( $newsletter['title'] ) ? $newsletter['title'] : __( 'The Zircoria Cryon Newsletter', 'super-ninety-four' ) ); ?>
				</h2>
				<?php if ( ! empty( $newsletter['lede'] ) ) : ?>
					<p class="snf-home-section__lede"><?php echo esc_html( $newsletter['lede'] ); ?></p>
				<?php endif; ?>
			</header>

			<?php if ( ! empty( $newsletter['benefits'] ) && is_array( $newsletter['benefits'] ) ) : ?>
				<ul class="snf-home-newsletter__benefits">
					<?php foreach ( $newsletter['benefits'] as $benefit ) : ?>
						<li><?php echo esc_html( $benefit ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( 'error' === $newsletter_status ) : ?>
				<p class="snf-home-newsletter__flash snf-home-newsletter__flash--err" role="alert">
					<?php esc_html_e( 'Please check your email address and try again.', 'super-ninety-four' ); ?>
				</p>
			<?php endif; ?>

			<form class="snf-home-newsletter__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_newsletter_subscribe">
				<?php wp_nonce_field( 'snf_newsletter_subscribe', 'snf_newsletter_nonce' ); ?>
				<div class="snf-home-newsletter__honeypot" aria-hidden="true">
					<label>
						<span><?php esc_html_e( 'Website', 'super-ninety-four' ); ?></span>
						<input type="text" name="snf_newsletter_website" tabindex="-1" autocomplete="off">
					</label>
				</div>
				<div class="snf-home-newsletter__fields">
					<label class="snf-home-newsletter__field">
						<span><?php esc_html_e( 'Email', 'super-ninety-four' ); ?></span>
						<input type="email" name="snf_newsletter_email" autocomplete="email" required placeholder="<?php esc_attr_e( 'you@example.com', 'super-ninety-four' ); ?>">
					</label>
					<label class="snf-home-newsletter__field">
						<span><?php esc_html_e( 'Phone number', 'super-ninety-four' ); ?></span>
						<input type="tel" name="snf_newsletter_phone" autocomplete="tel" required placeholder="<?php esc_attr_e( '(555) 000-0000', 'super-ninety-four' ); ?>">
					</label>
				</div>
				<div class="snf-home-newsletter__actions">
					<button class="snf-home-btn snf-home-btn--primary" type="submit">
						<?php esc_html_e( 'Subscribe', 'super-ninety-four' ); ?>
					</button>
					<?php if ( $newsletter_youtube ) : ?>
						<a class="snf-home-btn snf-home-btn--outline" href="<?php echo esc_url( $newsletter_youtube ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Watch on YouTube', 'super-ninety-four' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<p class="snf-home-newsletter__privacy">
					<?php esc_html_e( 'Live video and audio links stay private — SureContact sends them after you join. Unsubscribe any time.', 'super-ninety-four' ); ?>
				</p>
			</form>
		</div>
	</section>

	<section id="apply-again" class="snf-section snf-frame snf-home-reveal" aria-labelledby="snf-home-closing-heading">
		<div class="snf-home-closing">
			<h2 id="snf-home-closing-heading" class="snf-home-section__title"><?php esc_html_e( 'Ready when you clear the bar', 'super-ninety-four' ); ?></h2>
			<p class="snf-home-section__lede snf-home-closing__lede">
				<?php esc_html_e( 'You’re in the right room — or you’re not. Apply, or go back up and confirm the fit.', 'super-ninety-four' ); ?>
			</p>
			<div class="snf-home-closing__actions">
				<a class="snf-home-btn snf-home-btn--primary" href="<?php echo esc_url( $apply_url ); ?>">
					<?php esc_html_e( 'Apply for consideration', 'super-ninety-four' ); ?>
				</a>
				<a class="snf-home-btn snf-home-btn--outline" href="#top">
					<?php esc_html_e( 'Back to top', 'super-ninety-four' ); ?>
				</a>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
