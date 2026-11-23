<?php
/**
 * Front page template.
 * Figma: XCIV Social home — 2100:86, 2100:132, 2100:133, 2118:179.
 */
get_header();

$home        = snf_get_home_content();
$icons       = isset( $home['icons'] ) ? $home['icons'] : array();
$spotlight   = isset( $home['spotlight'] ) ? $home['spotlight'] : array();
$standard    = isset( $home['standard'] ) ? $home['standard'] : array();
$audience    = isset( $home['audience'] ) ? $home['audience'] : array();
$for         = isset( $audience['for'] ) ? $audience['for'] : array();
$not_for     = isset( $audience['not_for'] ) ? $audience['not_for'] : array();
$paths       = isset( $home['paths'] ) ? $home['paths'] : array();
$membership  = isset( $home['membership'] ) ? $home['membership'] : array();
$application = isset( $home['application'] ) ? $home['application'] : array();
$journey     = isset( $home['journey'] ) ? $home['journey'] : array();
$rules_faq   = isset( $home['rules_faq'] ) ? $home['rules_faq'] : array();
?>
<main class="snf-home" data-figma-ready="true">
	<h1 class="screen-reader-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>

	<?php /* 2100:86 — Hero: brand, search, icon row */ ?>
	<section class="snf-hero" data-figma-node="2100:86" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
		<div class="snf-container snf-hero__container">
			<div class="snf-hero__brand">
				<img
					class="snf-hero__logo"
					src="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/logo-white.svg' ); ?>"
					alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
					width="209"
					height="20"
					decoding="async"
					fetchpriority="high"
				>
			</div>
			<?php get_template_part( 'template-parts/search', 'events' ); ?>

			<?php if ( $icons ) : ?>
				<ul class="snf-home-icons" aria-label="<?php esc_attr_e( 'Highlights', 'super-ninety-four' ); ?>">
					<?php foreach ( $icons as $item ) : ?>
						<li class="snf-home-icons__item">
							<?php if ( ! empty( $item['url'] ) ) : ?>
								<a class="snf-home-icons__link" href="<?php echo esc_url( $item['url'] ); ?>">
							<?php endif; ?>
							<span class="snf-home-icons__media" aria-hidden="true">
								<?php echo snf_home_icon_svg( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<span class="snf-home-icons__copy">
								<span class="snf-home-icons__title"><?php echo esc_html( $item['title'] ); ?></span>
								<span class="snf-home-icons__body"><?php echo esc_html( $item['body'] ); ?></span>
							</span>
							<?php if ( ! empty( $item['url'] ) ) : ?>
								</a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $spotlight['title'] ) ) : ?>
				<?php /* 2100:132 — Why Choose SUPER NINETY FOUR */ ?>
				<section class="snf-home-spotlight" data-figma-node="2100:132" aria-labelledby="snf-home-why-heading">
					<div class="snf-home-spotlight__inner">
						<h2 id="snf-home-why-heading" class="snf-home-spotlight__title"><?php echo esc_html( $spotlight['title'] ); ?></h2>
						<p class="snf-home-spotlight__body"><?php echo esc_html( $spotlight['body'] ); ?></p>
					</div>
				</section>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( ! empty( $standard['title'] ) ) : ?>
	<?php /* 2100:133 — SUPER NINETY FOUR's Standard */ ?>
	<section class="snf-home-section snf-home-standard" data-figma-node="2100:133" aria-labelledby="snf-home-standard-heading">
		<div class="snf-container">
			<header class="snf-home-standard__header">
				<h2 id="snf-home-standard-heading" class="snf-home-section__title"><?php echo esc_html( $standard['title'] ); ?></h2>
				<p class="snf-home-section__lede"><?php echo esc_html( $standard['body'] ); ?></p>
			</header>
			<div class="snf-home-standard__split">
				<?php if ( ! empty( $standard['media']['src'] ) ) : ?>
				<figure class="snf-home-standard__media">
					<?php
					snf_the_optimized_image(
						$standard['media']['src'],
						array(
							'alt'     => $standard['media']['alt'],
							'width'   => 640,
							'height'  => 480,
							'loading' => 'lazy',
							'class'   => 'snf-home-standard__img',
						)
					);
					?>
				</figure>
				<?php endif; ?>
				<ul class="snf-home-standard__features">
					<?php foreach ( ( isset( $standard['features'] ) ? $standard['features'] : array() ) as $feature ) : ?>
						<li class="snf-home-standard__feature">
							<?php echo snf_home_icon_svg( $feature['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<h3 class="snf-home-feature__title"><?php echo esc_html( $feature['title'] ); ?></h3>
							<p class="snf-home-feature__body"><?php echo esc_html( $feature['body'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php /* 2118:179 — Who is this for / not for */ ?>
	<section class="snf-home-section snf-home-audience" data-figma-node="2118:179" aria-label="<?php esc_attr_e( 'Audience', 'super-ninety-four' ); ?>">
		<div class="snf-container">
			<?php if ( ! empty( $for['cards'] ) ) : ?>
				<div class="snf-home-audience__block">
					<h2 class="snf-home-section__title snf-home-audience__title"><?php echo esc_html( $for['title'] ); ?></h2>
					<ul class="snf-home-cards snf-home-cards--two snf-home-audience__grid">
						<?php foreach ( $for['cards'] as $card ) : ?>
							<li class="snf-home-card snf-home-card--audience">
								<h3 class="snf-home-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
								<p class="snf-home-card__body"><?php echo esc_html( $card['body'] ); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $not_for['cards'] ) ) : ?>
				<div class="snf-home-audience__block snf-home-audience__block--not">
					<h2 class="snf-home-section__title snf-home-audience__title"><?php echo esc_html( $not_for['title'] ); ?></h2>
					<ul class="snf-home-cards snf-home-cards--two snf-home-audience__grid">
						<?php foreach ( $not_for['cards'] as $card ) : ?>
							<li class="snf-home-card snf-home-card--audience">
								<h3 class="snf-home-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
								<p class="snf-home-card__body"><?php echo esc_html( $card['body'] ); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $paths ) : ?>
		<section class="snf-home-section snf-home-paths" aria-labelledby="snf-home-paths-heading">
			<div class="snf-container">
				<header class="snf-home-section__header">
					<h2 id="snf-home-paths-heading" class="snf-home-section__title"><?php echo esc_html( $paths['title'] ); ?></h2>
					<?php if ( ! empty( $paths['lede'] ) ) : ?>
						<p class="snf-home-section__lede"><?php echo esc_html( $paths['lede'] ); ?></p>
					<?php endif; ?>
				</header>

				<?php if ( ! empty( $paths['cta'] ) ) : ?>
					<div class="snf-home-cta">
						<div class="snf-home-cta__copy">
							<h3 class="snf-home-cta__title"><?php echo esc_html( $paths['cta']['title'] ); ?></h3>
							<p class="snf-home-cta__body"><?php echo esc_html( $paths['cta']['body'] ); ?></p>
						</div>
						<a class="snf-hub__action snf-hub__action--primary snf-home-cta__action" href="<?php echo esc_url( $paths['cta']['url'] ); ?>">
							<?php echo esc_html( $paths['cta']['label'] ); ?>
						</a>
					</div>
				<?php endif; ?>

				<ul class="snf-home-cards snf-home-cards--two snf-home-paths__grid">
					<?php if ( ! empty( $paths['guest'] ) ) : ?>
						<li class="snf-home-card snf-home-card--path">
							<a class="snf-home-card__link" href="<?php echo esc_url( $paths['guest']['url'] ); ?>">
								<h3 class="snf-home-card__title"><?php echo esc_html( $paths['guest']['title'] ); ?></h3>
								<p class="snf-home-card__body"><?php echo esc_html( $paths['guest']['body'] ); ?></p>
								<span class="snf-home-card__action"><?php echo esc_html( $paths['guest']['label'] ); ?></span>
							</a>
						</li>
					<?php endif; ?>
					<?php if ( ! empty( $paths['inquiry'] ) ) : ?>
						<li class="snf-home-card snf-home-card--path">
							<a class="snf-home-card__link" href="<?php echo esc_url( $paths['inquiry']['url'] ); ?>">
								<h3 class="snf-home-card__title"><?php echo esc_html( $paths['inquiry']['title'] ); ?></h3>
								<p class="snf-home-card__body"><?php echo esc_html( $paths['inquiry']['body'] ); ?></p>
								<span class="snf-home-card__action"><?php echo esc_html( $paths['inquiry']['label'] ); ?></span>
							</a>
						</li>
					<?php endif; ?>
				</ul>

				<?php if ( ! empty( $paths['inquiry']['cards'] ) ) : ?>
					<ul class="snf-home-cards snf-home-cards--two snf-home-paths__inquiries">
						<?php foreach ( $paths['inquiry']['cards'] as $card ) : ?>
							<li class="snf-home-card snf-home-card--inquiry">
								<a class="snf-home-card__link" href="<?php echo esc_url( $card['url'] ); ?>">
									<h3 class="snf-home-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
									<p class="snf-home-card__body"><?php echo esc_html( $card['body'] ); ?></p>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $membership ) : ?>
		<section class="snf-home-section snf-home-membership" aria-labelledby="snf-home-membership-heading">
			<div class="snf-container">
				<header class="snf-home-section__header">
					<h2 id="snf-home-membership-heading" class="snf-home-section__title"><?php echo esc_html( $membership['title'] ); ?></h2>
					<?php if ( ! empty( $membership['lede'] ) ) : ?>
						<p class="snf-home-section__lede"><?php echo esc_html( $membership['lede'] ); ?></p>
					<?php endif; ?>
				</header>

				<?php if ( ! empty( $membership['initiation'] ) ) : ?>
					<p class="snf-home-membership__initiation">
						<span class="snf-home-membership__initiation-label"><?php echo esc_html( $membership['initiation']['label'] ); ?></span>
						<span class="snf-home-membership__initiation-amount"><?php echo esc_html( $membership['initiation']['amount'] ); ?></span>
						<span class="snf-home-membership__initiation-meta"><?php echo esc_html( $membership['initiation']['meta'] ); ?></span>
					</p>
				<?php endif; ?>

				<?php if ( ! empty( $membership['plans'] ) ) : ?>
					<ul class="snf-home-plans">
						<?php foreach ( $membership['plans'] as $plan ) : ?>
							<li class="snf-home-plan">
								<h3 class="snf-home-plan__name"><?php echo esc_html( $plan['name'] ); ?></h3>
								<p class="snf-home-plan__price">
									<span class="snf-home-plan__amount"><?php echo esc_html( $plan['price'] ); ?></span>
									<span class="snf-home-plan__cadence"><?php echo esc_html( $plan['cadence'] ); ?></span>
									<span class="snf-home-plan__term"><?php echo esc_html( $plan['term'] ); ?></span>
								</p>
								<?php if ( ! empty( $plan['benefits'] ) ) : ?>
									<ul class="snf-home-plan__benefits">
										<?php foreach ( $plan['benefits'] as $benefit ) : ?>
											<li><?php echo esc_html( $benefit ); ?></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( ! empty( $membership['cta'] ) ) : ?>
					<div class="snf-home-section__actions">
						<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( $membership['cta']['url'] ); ?>">
							<?php echo esc_html( $membership['cta']['label'] ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $application ) : ?>
		<section class="snf-home-section snf-home-application" aria-labelledby="snf-home-application-heading">
			<div class="snf-container">
				<header class="snf-home-section__header">
					<h2 id="snf-home-application-heading" class="snf-home-section__title"><?php echo esc_html( $application['title'] ); ?></h2>
					<?php if ( ! empty( $application['lede'] ) ) : ?>
						<p class="snf-home-section__lede"><?php echo esc_html( $application['lede'] ); ?></p>
					<?php endif; ?>
				</header>

				<?php if ( ! empty( $application['steps'] ) ) : ?>
					<ol class="snf-home-steps">
						<?php foreach ( $application['steps'] as $step ) : ?>
							<li class="snf-home-step">
								<span class="snf-home-step__num" aria-hidden="true"><?php echo esc_html( $step['num'] ); ?></span>
								<div class="snf-home-step__copy">
									<h3 class="snf-home-step__title"><?php echo esc_html( $step['title'] ); ?></h3>
									<p class="snf-home-step__body"><?php echo esc_html( $step['body'] ); ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>

				<?php if ( ! empty( $application['cta'] ) ) : ?>
					<div class="snf-home-section__actions">
						<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( $application['cta']['url'] ); ?>">
							<?php echo esc_html( $application['cta']['label'] ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $journey ) : ?>
		<section class="snf-home-section snf-home-journey" aria-labelledby="snf-home-journey-heading">
			<div class="snf-container">
				<header class="snf-home-section__header">
					<h2 id="snf-home-journey-heading" class="snf-home-section__title"><?php echo esc_html( $journey['title'] ); ?></h2>
					<?php if ( ! empty( $journey['lede'] ) ) : ?>
						<p class="snf-home-section__lede"><?php echo esc_html( $journey['lede'] ); ?></p>
					<?php endif; ?>
				</header>

				<div class="snf-home-journey__grid">
					<?php foreach ( array( 'before', 'after' ) as $side ) : ?>
						<?php if ( empty( $journey[ $side ] ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<?php $column = $journey[ $side ]; ?>
						<div class="snf-home-journey__column snf-home-journey__column--<?php echo esc_attr( $side ); ?>">
							<h3 class="snf-home-journey__title"><?php echo esc_html( $column['title'] ); ?></h3>
							<?php if ( ! empty( $column['items'] ) ) : ?>
								<ul class="snf-home-journey__list">
									<?php foreach ( $column['items'] as $item ) : ?>
										<li><?php echo esc_html( $item ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $rules_faq ) : ?>
		<section id="faq" class="snf-home-section snf-home-faq" aria-labelledby="snf-home-faq-heading">
			<div class="snf-container">
				<header class="snf-home-section__header">
					<h2 id="snf-home-faq-heading" class="snf-home-section__title"><?php echo esc_html( $rules_faq['title'] ); ?></h2>
					<?php if ( ! empty( $rules_faq['lede'] ) ) : ?>
						<p class="snf-home-section__lede"><?php echo esc_html( $rules_faq['lede'] ); ?></p>
					<?php endif; ?>
				</header>

				<?php if ( ! empty( $rules_faq['rules'] ) ) : ?>
					<ul class="snf-home-rules">
						<?php foreach ( $rules_faq['rules'] as $rule ) : ?>
							<li class="snf-home-rules__item"><?php echo esc_html( $rule ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( ! empty( $rules_faq['faq'] ) ) : ?>
					<div class="snf-home-accordion">
						<?php foreach ( $rules_faq['faq'] as $index => $item ) : ?>
							<details class="snf-home-accordion__item" <?php echo 0 === (int) $index ? 'open' : ''; ?>>
								<summary class="snf-home-accordion__summary">
									<span class="snf-home-accordion__question"><?php echo esc_html( $item['q'] ); ?></span>
									<span class="snf-home-accordion__icon" aria-hidden="true"></span>
								</summary>
								<div class="snf-home-accordion__panel">
									<p><?php echo esc_html( $item['a'] ); ?></p>
								</div>
							</details>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="snf-home-section__actions snf-home-faq__actions">
					<a class="snf-hub__action snf-hub__action--primary" href="#snf-search-events">
						<?php esc_html_e( 'Back to search events', 'super-ninety-four' ); ?>
					</a>
					<a class="snf-hub__action" href="<?php echo esc_url( snf_get_page_url( 'apply' ) ); ?>">
						<?php esc_html_e( 'Apply for membership', 'super-ninety-four' ); ?>
					</a>
				</div>
			</div>
		</section>
	<?php endif; ?>

</main>
<?php
get_footer();
