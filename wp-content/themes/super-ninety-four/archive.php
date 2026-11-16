<?php
/**
 * Generic archive template (categories, tags, dates, authors).
 */
get_header();
?>
<main class="snf-page snf-archive">
	<section class="snf-page__frame snf-container" aria-label="<?php echo esc_attr( get_the_archive_title() ); ?>">
		<header class="snf-archive__intro">
			<h1 class="snf-archive__title"><?php the_archive_title(); ?></h1>
			<?php
			$description = get_the_archive_description();
			if ( $description ) :
				?>
				<div class="snf-archive__description"><?php echo wp_kses_post( $description ); ?></div>
			<?php endif; ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<ol class="snf-serp__list">
				<?php
				while ( have_posts() ) :
					the_post();
					$snippet = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content() ), 28 );
					?>
					<li class="snf-serp__item">
						<article <?php post_class( 'snf-result' ); ?>>
							<a class="snf-result__hit" href="<?php the_permalink(); ?>">
								<h2 class="snf-result__title"><?php the_title(); ?></h2>
							</a>
							<p class="snf-result__snippet"><?php echo esc_html( $snippet ); ?></p>
							<p class="snf-result__meta"><?php echo esc_html( get_the_date() ); ?></p>
						</article>
					</li>
					<?php
				endwhile;
				?>
			</ol>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<div class="snf-serp__empty">
				<p><?php esc_html_e( 'Nothing found in this archive.', 'super-ninety-four' ); ?></p>
				<p><a class="snf-archive__admission-link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back home', 'super-ninety-four' ); ?></a></p>
			</div>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
