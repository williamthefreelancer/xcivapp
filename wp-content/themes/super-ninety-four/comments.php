<?php
/**
 * Comments template.
 */
if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="snf-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="snf-comments__title">
			<?php
			$comment_count = (int) get_comments_number();
			printf(
				esc_html( _n( '%s comment', '%s comments', $comment_count, 'super-ninety-four' ) ),
				esc_html( number_format_i18n( $comment_count ) )
			);
			?>
		</h2>

		<ol class="snf-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
				)
			);
			?>
		</ol>

		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="snf-comments__closed"><?php esc_html_e( 'Comments are closed.', 'super-ninety-four' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply' => __( 'Leave a comment', 'super-ninety-four' ),
		)
	);
	?>
</section>
