<?php
/**
 * Front page template.
 * Figma: XCIV Social home — 2100:86, 2100:132, 2100:133, 2118:179.
 */
get_header();

$home      = snf_get_home_content();
$icons     = isset( $home['icons'] ) ? $home['icons'] : array();
$spotlight = isset( $home['spotlight'] ) ? $home['spotlight'] : array();
$standard  = isset( $home['standard'] ) ? $home['standard'] : array();
$audience  = isset( $home['audience'] ) ? $home['audience'] : array();
$for       = isset( $audience['for'] ) ? $audience['for'] : array();
$not_for   = isset( $audience['not_for'] ) ? $audience['not_for'] : array();
?>
<main class="snf-home" data-figma-ready="true">

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

			<?php /* 2100:132 — Why Choose SUPER NINETY FOUR */ ?>
			<section class="snf-home-spotlight" data-figma-node="2100:132" aria-labelledby="snf-home-why-heading">
				<div class="snf-home-spotlight__inner">
					<h2 id="snf-home-why-heading" class="snf-home-spotlight__title"><?php echo esc_html( $spotlight['title'] ); ?></h2>
					<p class="snf-home-spotlight__body"><?php echo esc_html( $spotlight['body'] ); ?></p>
				</div>
			</section>
		</div>
	</section>

	<?php /* 2100:133 — SUPER NINETY FOUR's Standard */ ?>
	<section class="snf-home-section snf-home-standard" data-figma-node="2100:133" aria-labelledby="snf-home-standard-heading">
		<div class="snf-container">
			<header class="snf-home-standard__header">
				<h2 id="snf-home-standard-heading" class="snf-home-section__title"><?php echo esc_html( $standard['title'] ); ?></h2>
				<p class="snf-home-section__lede"><?php echo esc_html( $standard['body'] ); ?></p>
			</header>
			<div class="snf-home-standard__split">
				<figure class="snf-home-standard__media">
					<img
						src="<?php echo esc_url( $standard['media']['src'] ); ?>"
						alt="<?php echo esc_attr( $standard['media']['alt'] ); ?>"
						width="640"
						height="480"
						loading="lazy"
						decoding="async"
					>
				</figure>
				<ul class="snf-home-standard__features">
					<?php foreach ( $standard['features'] as $feature ) : ?>
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

</main>
<?php
get_footer();
