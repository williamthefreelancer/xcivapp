<?php
/**
 * Reciprocal club inquiry landing (Figma inquiries-single-page 2162:66).
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

$img_base     = get_template_directory_uri() . '/assets/images/reciprocal/inquiries/';
$hero         = ! empty( $club['hero'] ) ? (string) $club['hero'] : $img_base . 'hero-background.jpg';
$mid_image    = ! empty( $club['mid_image'] ) ? (string) $club['mid_image'] : $img_base . 'mid-highway.jpg';
$gallery      = ( isset( $club['gallery'] ) && is_array( $club['gallery'] ) ) ? $club['gallery'] : array(
	$img_base . 'gallery-1.jpg',
	$img_base . 'gallery-2.jpg',
	$img_base . 'gallery-3.jpg',
);
$interests    = snf_reciprocal_inquiry_interests();
$why_items    = ( isset( $club['why_items'] ) && is_array( $club['why_items'] ) ) ? $club['why_items'] : snf_reciprocal_why_consider_items();
$why_lede     = ! empty( $club['why_lede'] ) ? (string) $club['why_lede'] : snf_reciprocal_why_consider_lede();
$apply_url    = ! empty( $club['cta_url'] ) ? (string) $club['cta_url'] : snf_get_page_url( 'apply' );
$about_title  = ! empty( $club['about_title'] ) ? (string) $club['about_title'] : sprintf(
	/* translators: %s: club title */
	__( 'ABOUT %s', 'super-ninety-four' ),
	strtoupper( (string) $club['title'] )
);
$about_body   = ! empty( $club['about_body'] ) ? (string) $club['about_body'] : (string) $club['lede'];
$limited_sub  = ! empty( $club['limited_sub'] ) ? (string) $club['limited_sub'] : __( 'Invite windows close when the desk fills.', 'super-ninety-four' );
$hero_heading = ! empty( $club['hero_heading'] ) ? (string) $club['hero_heading'] : snf_get_reciprocal_hero_heading();
$hero_lede    = ! empty( $club['hero_lede'] ) ? (string) $club['hero_lede'] : (string) $club['lede'];
$sureforms    = function_exists( 'snf_render_sureforms_embed' ) ? snf_render_sureforms_embed( 'reciprocal' ) : '';
?>
<main
	class="snf-page snf-reciprocal snf-reciprocal--inquiry"
	data-figma-ready="true"
	data-figma-node="2162:66"
	data-club="<?php echo esc_attr( (string) $club['slug'] ); ?>"
>
	<section class="snf-reciprocal__hero" aria-label="<?php echo esc_attr( $club['title'] ); ?>" data-figma-slot="image-background">
		<div class="snf-reciprocal__hero-media">
			<?php
			snf_the_optimized_image(
				$hero,
				array(
					'class'          => 'snf-reciprocal__hero-img',
					'alt'            => (string) $club['hero_alt'],
					'width'          => 1400,
					'height'         => 567,
					'loading'        => '',
					'fetchpriority'  => 'high',
				)
			);
			?>
			<div class="snf-reciprocal__hero-grid">
				<div class="snf-reciprocal__hero-copy">
					<h1 class="snf-reciprocal__hero-heading"><?php echo esc_html( $hero_heading ); ?></h1>
					<p class="snf-reciprocal__hero-lede"><?php echo esc_html( $hero_lede ); ?></p>
				</div>

				<?php if ( $sureforms ) : ?>
					<div class="snf-reciprocal__form snf-reciprocal__form--sureforms" data-figma-slot="capture-form">
						<?php echo $sureforms; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SureForms markup. ?>
					</div>
				<?php else : ?>
				<form
					class="snf-reciprocal__form"
					action="<?php echo esc_url( $apply_url ); ?>"
					method="get"
					data-figma-slot="capture-form"
				>
					<input type="hidden" name="q" value="<?php echo esc_attr( (string) $club['title'] ); ?>">
					<input type="hidden" name="club" value="<?php echo esc_attr( (string) $club['slug'] ); ?>">

					<div class="snf-reciprocal__form-row">
						<label class="snf-reciprocal__field">
							<span><?php esc_html_e( 'Your name', 'super-ninety-four' ); ?></span>
							<input type="text" name="name" autocomplete="name" required>
						</label>
						<label class="snf-reciprocal__field">
							<span><?php esc_html_e( 'Your phone', 'super-ninety-four' ); ?></span>
							<input type="tel" name="phone" autocomplete="tel" required>
						</label>
					</div>

					<fieldset class="snf-reciprocal__interests">
						<legend><?php esc_html_e( 'What are you most interested in?', 'super-ninety-four' ); ?></legend>
						<?php foreach ( $interests as $interest_key => $interest_label ) : ?>
							<label class="snf-reciprocal__interest">
								<span><?php echo esc_html( $interest_label ); ?></span>
								<input type="checkbox" name="interest[]" value="<?php echo esc_attr( $interest_key ); ?>">
							</label>
						<?php endforeach; ?>
					</fieldset>

					<button class="snf-reciprocal__submit" type="submit">
						<?php esc_html_e( 'Yes, Send Me Those Inquiries', 'super-ninety-four' ); ?>
					</button>
				</form>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="snf-reciprocal__limited" data-figma-slot="limited-banner" aria-label="<?php esc_attr_e( 'Limited time', 'super-ninety-four' ); ?>">
		<p class="snf-reciprocal__limited-title"><?php esc_html_e( 'LIMITED TIME', 'super-ninety-four' ); ?></p>
		<p class="snf-reciprocal__limited-sub"><?php echo esc_html( $limited_sub ); ?></p>
	</section>

	<section class="snf-reciprocal__mid" data-figma-slot="image-section" aria-hidden="true">
		<?php
		snf_the_optimized_image(
			$mid_image,
			array(
				'alt'     => '',
				'width'   => 1400,
				'height'  => 567,
				'loading' => 'lazy',
			)
		);
		?>
	</section>

	<section class="snf-reciprocal__about" data-figma-slot="about" aria-labelledby="snf-reciprocal-about-heading">
		<div class="snf-reciprocal__about-inner">
			<h2 id="snf-reciprocal-about-heading" class="snf-reciprocal__about-title">
				<?php echo esc_html( $about_title ); ?>
			</h2>
			<p class="snf-reciprocal__about-body"><?php echo esc_html( $about_body ); ?></p>
		</div>
	</section>

	<section class="snf-reciprocal__gallery" data-figma-slot="image-section-3" aria-label="<?php echo esc_attr( $club['title'] ); ?>">
		<?php foreach ( $gallery as $index => $gallery_src ) : ?>
			<figure class="snf-reciprocal__gallery-item">
				<?php
				snf_the_optimized_image(
					$gallery_src,
					array(
						'alt'     => sprintf(
							/* translators: 1: club title, 2: image number */
							__( '%1$s gallery image %2$d', 'super-ninety-four' ),
							$club['title'],
							$index + 1
						),
						'width'   => 467,
						'height'  => 567,
						'loading' => 'lazy',
					)
				);
				?>
			</figure>
		<?php endforeach; ?>
	</section>

	<section class="snf-reciprocal__why" data-figma-slot="why-consider-section" aria-labelledby="snf-reciprocal-why-heading">
		<div class="snf-reciprocal__why-intro">
			<h2 id="snf-reciprocal-why-heading" class="snf-reciprocal__why-title">
				<?php esc_html_e( 'A FEW REASON WHY YOU SHOULD CONSIDER INQUIRY AT SUPER NINETY FOUR', 'super-ninety-four' ); ?>
			</h2>
			<p class="snf-reciprocal__why-lede"><?php echo esc_html( $why_lede ); ?></p>
		</div>
		<ul class="snf-reciprocal__why-list">
			<?php foreach ( $why_items as $item ) : ?>
				<li class="snf-reciprocal__why-item">
					<h3 class="snf-reciprocal__why-item-title"><?php echo esc_html( $item['title'] ); ?></h3>
					<p class="snf-reciprocal__why-item-body"><?php echo esc_html( $item['body'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
</main>
