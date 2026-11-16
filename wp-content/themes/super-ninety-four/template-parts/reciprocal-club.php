<?php
/**
 * Reciprocal club landing (Back Creek / Badlands screenshot layout).
 *
 * @var array<string, mixed> $args {
 *     @type array $club Club content from snf_get_reciprocal_club().
 * }
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$club = ( isset( $args['club'] ) && is_array( $args['club'] ) ) ? $args['club'] : array();
if ( ! $club ) {
	return;
}

$steps    = snf_reciprocal_application_steps();
$faq      = snf_reciprocal_faq();
$features = isset( $club['features'] ) && is_array( $club['features'] ) ? $club['features'] : array();
$outcomes = isset( $club['outcomes'] ) && is_array( $club['outcomes'] ) ? $club['outcomes'] : array();
?>
<main class="snf-page snf-reciprocal" data-figma-ready="true" data-club="<?php echo esc_attr( (string) $club['slug'] ); ?>">
	<section class="snf-reciprocal__hero" aria-label="<?php echo esc_attr( $club['title'] ); ?>">
		<div
			class="snf-reciprocal__hero-media"
			style="--snf-reciprocal-hero: url('<?php echo esc_url( $club['hero'] ); ?>');"
		>
			<img
				class="snf-reciprocal__hero-img"
				src="<?php echo esc_url( $club['hero'] ); ?>"
				alt="<?php echo esc_attr( $club['hero_alt'] ); ?>"
				width="1600"
				height="760"
				decoding="async"
			>
			<div class="snf-reciprocal__hero-copy">
				<h1 class="snf-reciprocal__hero-heading"><?php echo esc_html( $club['hero_heading'] ); ?></h1>
				<a class="snf-reciprocal__cta" href="<?php echo esc_url( $club['cta_url'] ); ?>">
					<?php echo esc_html( $club['cta_label'] ); ?>
				</a>
			</div>
		</div>
	</section>

	<section class="snf-reciprocal__intro" data-figma-slot="reciprocal-intro">
		<div class="snf-reciprocal__inner">
			<div class="snf-reciprocal__mark" aria-hidden="true">
				<span><?php echo esc_html( $club['mark'] ); ?></span>
			</div>
			<p class="snf-reciprocal__mark-label"><?php echo esc_html( $club['mark_label'] ); ?></p>
			<h2 class="snf-reciprocal__title"><?php echo esc_html( $club['title'] ); ?></h2>
			<p class="snf-reciprocal__lede"><?php echo esc_html( $club['lede'] ); ?></p>

			<?php if ( $features ) : ?>
				<ul class="snf-reciprocal__cards snf-reciprocal__cards--3">
					<?php foreach ( $features as $feature ) : ?>
						<li class="snf-reciprocal__card">
							<?php echo snf_reciprocal_icon_svg( $feature['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<h3 class="snf-reciprocal__card-title"><?php echo esc_html( $feature['title'] ); ?></h3>
							<p class="snf-reciprocal__card-body"><?php echo esc_html( $feature['body'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>

	<section class="snf-reciprocal__process" data-figma-slot="reciprocal-process" aria-labelledby="snf-reciprocal-process-heading">
		<div class="snf-reciprocal__inner">
			<h2 id="snf-reciprocal-process-heading" class="snf-reciprocal__section-title">
				<?php esc_html_e( 'The Application Process', 'super-ninety-four' ); ?>
			</h2>
			<ul class="snf-reciprocal__cards snf-reciprocal__cards--5">
				<?php foreach ( $steps as $step ) : ?>
					<li class="snf-reciprocal__card">
						<?php echo snf_reciprocal_icon_svg( $step['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<h3 class="snf-reciprocal__card-title"><?php echo esc_html( $step['title'] ); ?></h3>
						<p class="snf-reciprocal__card-body"><?php echo esc_html( $step['body'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<section
		class="snf-reciprocal__outcomes"
		data-figma-slot="reciprocal-outcomes"
		aria-labelledby="snf-reciprocal-outcomes-heading"
		style="--snf-reciprocal-outcomes: url('<?php echo esc_url( $club['benefits_bg'] ); ?>');"
	>
		<div class="snf-reciprocal__inner">
			<h2 id="snf-reciprocal-outcomes-heading" class="snf-reciprocal__section-title">
				<?php esc_html_e( 'Outcomes & Benefits', 'super-ninety-four' ); ?>
			</h2>
			<?php if ( $outcomes ) : ?>
				<ul class="snf-reciprocal__cards snf-reciprocal__cards--3">
					<?php foreach ( $outcomes as $outcome ) : ?>
						<li class="snf-reciprocal__card">
							<?php echo snf_reciprocal_icon_svg( $outcome['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<h3 class="snf-reciprocal__card-title"><?php echo esc_html( $outcome['title'] ); ?></h3>
							<p class="snf-reciprocal__card-body"><?php echo esc_html( $outcome['body'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>

	<section class="snf-reciprocal__faq" data-figma-slot="reciprocal-faq" aria-labelledby="snf-reciprocal-faq-heading">
		<div class="snf-reciprocal__inner snf-reciprocal__inner--narrow">
			<h2 id="snf-reciprocal-faq-heading" class="snf-reciprocal__faq-title"><?php esc_html_e( 'FAQ', 'super-ninety-four' ); ?></h2>
			<div class="snf-reciprocal__faq-list">
				<?php foreach ( $faq as $index => $item ) : ?>
					<details class="snf-reciprocal__faq-item" <?php echo 0 === $index ? 'open' : ''; ?>>
						<summary><?php echo esc_html( $item['q'] ); ?></summary>
						<p><?php echo esc_html( $item['a'] ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<footer class="snf-reciprocal__foot">
		<p>
			<?php
			printf(
				/* translators: %s: club title */
				esc_html__( '© %1$s %2$s — THE SUPER NINETY FOUR EVENTS COMPANY LLC', 'super-ninety-four' ),
				esc_html( gmdate( 'Y' ) ),
				esc_html( $club['title'] )
			);
			?>
		</p>
	</footer>
</main>
