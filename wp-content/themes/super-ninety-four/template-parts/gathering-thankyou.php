<?php
/**
 * Gathering thank-you + blunt booking desk.
 *
 * @var array $args {
 *   @type array|null $payload Thank-you payload.
 * }
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$payload = ( isset( $args['payload'] ) && is_array( $args['payload'] ) ) ? $args['payload'] : null;
if ( ! $payload ) {
	echo '<section class="snf-gathering-thanks__shell snf-container"><p class="snf-gathering-thanks__lede">' . esc_html__( 'Thank you. Your order was received.', 'super-ninety-four' ) . '</p></section>';
	return;
}

$order     = isset( $payload['order'] ) && is_array( $payload['order'] ) ? $payload['order'] : null;
$questions = isset( $payload['questions'] ) && is_array( $payload['questions'] ) ? $payload['questions'] : array();
$windows   = isset( $payload['windows'] ) && is_array( $payload['windows'] ) ? $payload['windows'] : array();
$redirect  = ( is_singular() && get_permalink() ) ? get_permalink() : home_url( '/' );
if ( $order ) {
	$redirect = add_query_arg(
		array(
			'order' => (string) $order['id'],
			'key'   => (string) $order['key'],
		),
		$redirect
	);
}
?>
<section class="snf-gathering-thanks__shell snf-container" aria-label="<?php echo esc_attr( (string) $payload['title'] ); ?>">
	<header class="snf-gathering-thanks__intro">
		<h1 class="snf-gathering-thanks__title"><?php echo esc_html( (string) $payload['title'] ); ?></h1>
		<p class="snf-gathering-thanks__lede"><?php echo esc_html( (string) $payload['lede'] ); ?></p>
		<?php if ( $order ) : ?>
			<p class="snf-gathering-thanks__meta">
				<?php
				printf(
					/* translators: 1: order number, 2: total */
					esc_html__( 'Order #%1$s · %2$s', 'super-ninety-four' ),
					esc_html( (string) $order['number'] ),
					wp_kses_post( (string) $order['total'] )
				);
				?>
			</p>
		<?php endif; ?>
		<p class="snf-gathering-thanks__location"><?php echo esc_html( (string) $payload['location'] ); ?></p>
	</header>

	<?php if ( ! empty( $payload['submitted'] ) ) : ?>
		<p class="snf-gathering-thanks__status snf-gathering-thanks__status--ok" role="status">
			<?php esc_html_e( 'Received. I’ll review your answers and confirm the in-person window.', 'super-ninety-four' ); ?>
		</p>
	<?php elseif ( ! empty( $payload['error'] ) ) : ?>
		<p class="snf-gathering-thanks__status snf-gathering-thanks__status--err" role="alert">
			<?php esc_html_e( 'Something was missing. Answer all three questions bluntly and pick a window.', 'super-ninety-four' ); ?>
		</p>
	<?php endif; ?>

	<form
		class="snf-gathering-thanks__form"
		method="post"
		action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		novalidate
	>
		<input type="hidden" name="action" value="snf_gathering_book">
		<input type="hidden" name="snf_gathering_slug" value="<?php echo esc_attr( (string) $payload['slug'] ); ?>">
		<input type="hidden" name="snf_redirect" value="<?php echo esc_url( $redirect ); ?>">
		<?php if ( $order ) : ?>
			<input type="hidden" name="snf_order_id" value="<?php echo esc_attr( (string) $order['id'] ); ?>">
			<input type="hidden" name="snf_order_key" value="<?php echo esc_attr( (string) $order['key'] ); ?>">
		<?php endif; ?>
		<?php wp_nonce_field( 'snf_gathering_book', 'snf_gathering_book_nonce' ); ?>

		<p class="snf-gathering-thanks__form-lede">
			<?php esc_html_e( 'Book the in-person conversation. Answer these three questions bluntly so I know the real problem — based on what you’ve lived, I’ll come with solutions from what I know.', 'super-ninety-four' ); ?>
		</p>

		<label class="snf-gathering-thanks__field">
			<span><?php esc_html_e( 'Full name', 'super-ninety-four' ); ?></span>
			<input type="text" name="snf_book_name" required autocomplete="name" value="<?php echo esc_attr( $order ? (string) $order['name'] : '' ); ?>">
		</label>
		<label class="snf-gathering-thanks__field">
			<span><?php esc_html_e( 'Email', 'super-ninety-four' ); ?></span>
			<input type="email" name="snf_book_email" required autocomplete="email" value="<?php echo esc_attr( $order ? (string) $order['email'] : '' ); ?>">
		</label>
		<label class="snf-gathering-thanks__field">
			<span><?php esc_html_e( 'Phone', 'super-ninety-four' ); ?></span>
			<input type="tel" name="snf_book_phone" required autocomplete="tel" value="<?php echo esc_attr( $order ? (string) $order['phone'] : '' ); ?>">
		</label>
		<label class="snf-gathering-thanks__field">
			<span><?php esc_html_e( 'Preferred in-person window', 'super-ninety-four' ); ?></span>
			<select name="snf_book_window" required>
				<option value=""><?php esc_html_e( 'Select a window', 'super-ninety-four' ); ?></option>
				<?php foreach ( $windows as $window ) : ?>
					<option value="<?php echo esc_attr( (string) $window['value'] ); ?>"><?php echo esc_html( (string) $window['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<?php foreach ( $questions as $question ) : ?>
			<label class="snf-gathering-thanks__field snf-gathering-thanks__field--wide">
				<span><?php echo esc_html( (string) $question['label'] ); ?></span>
				<textarea name="<?php echo esc_attr( (string) $question['key'] ); ?>" rows="4" required placeholder="<?php echo esc_attr( (string) $question['hint'] ); ?>"></textarea>
			</label>
		<?php endforeach; ?>

		<button type="submit" class="snf-gathering-thanks__submit">
			<?php esc_html_e( 'Submit booking request', 'super-ninety-four' ); ?>
		</button>
	</form>
</section>
