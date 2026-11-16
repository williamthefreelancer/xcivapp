<?php
/**
 * Template Name: Messages Hub
 * Description: Live Instagram-style member inbox + conversation pane.
 */

get_header();

$boot    = snf_messages_get_bootstrap();
$threads = isset( $boot['threads'] ) ? $boot['threads'] : array();
$logged  = ! empty( $boot['loggedIn'] );
$i18n    = $boot['i18n'];
?>
<main class="snf-page snf-hub snf-hub--messages" data-figma-ready="true">
	<section class="snf-hub__shell snf-hub__shell--messages snf-container" aria-label="<?php esc_attr_e( 'Messages', 'super-ninety-four' ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'Private correspondence', 'super-ninety-four' ),
				'title'   => __( 'Messages', 'super-ninety-four' ),
				'lede'    => __( 'Live desk threads — send notes to concierge, admissions, and reciprocal programs in one Instagram-style inbox.', 'super-ninety-four' ),
			)
		);
		?>

		<div class="snf-hub__layout snf-hub__layout--messages">
			<aside class="snf-hub__aside">
				<?php get_template_part( 'template-parts/hub/account-nav', null, array( 'current' => 'messages' ) ); ?>
			</aside>

			<div class="snf-hub__main snf-hub__main--messages">
				<div
					class="snf-msg"
					id="snf-msg"
					data-bp-module="messages"
					data-figma-slot="messages-live"
					<?php echo $logged ? '' : ' data-guest="true"'; ?>
				>
					<aside class="snf-msg__rail" aria-label="<?php echo esc_attr( $i18n['inbox'] ); ?>">
						<div class="snf-msg__rail-head">
							<h2 class="snf-msg__rail-title"><?php echo esc_html( $i18n['inbox'] ); ?></h2>
							<span class="snf-msg__live-dot" aria-hidden="true"></span>
							<span class="snf-msg__live-label"><?php esc_html_e( 'Live', 'super-ninety-four' ); ?></span>
						</div>

						<label class="snf-msg__search">
							<span class="screen-reader-text"><?php echo esc_html( $i18n['search'] ); ?></span>
							<input
								type="search"
								id="snf-msg-search"
								placeholder="<?php echo esc_attr( $i18n['search'] ); ?>"
								autocomplete="off"
							>
						</label>

						<ul class="snf-msg__threads" id="snf-msg-threads" role="listbox" aria-label="<?php echo esc_attr( $i18n['inbox'] ); ?>">
							<?php if ( $logged && $threads ) : ?>
								<?php foreach ( $threads as $index => $thread ) : ?>
									<li>
										<button
											type="button"
											class="snf-msg__thread<?php echo ! empty( $thread['unread'] ) ? ' is-unread' : ''; ?><?php echo 0 === $index ? ' is-active' : ''; ?>"
											role="option"
											aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
											data-thread-id="<?php echo esc_attr( (string) $thread['id'] ); ?>"
											data-title="<?php echo esc_attr( $thread['title'] ); ?>"
											data-subtitle="<?php echo esc_attr( $thread['subtitle'] ); ?>"
											data-glyph="<?php echo esc_attr( $thread['avatar_glyph'] ); ?>"
										>
											<span class="snf-msg__avatar" data-kind="<?php echo esc_attr( $thread['avatar_kind'] ); ?>" aria-hidden="true">
												<?php echo esc_html( $thread['avatar_glyph'] ); ?>
											</span>
											<span class="snf-msg__thread-body">
												<span class="snf-msg__thread-top">
													<span class="snf-msg__thread-name"><?php echo esc_html( $thread['title'] ); ?></span>
													<span class="snf-msg__thread-time"><?php echo esc_html( $thread['time_label'] ); ?></span>
												</span>
												<span class="snf-msg__thread-preview"><?php echo esc_html( $thread['preview'] ); ?></span>
											</span>
											<?php if ( ! empty( $thread['unread'] ) ) : ?>
												<span class="snf-msg__unread" aria-label="<?php esc_attr_e( 'Unread', 'super-ninety-four' ); ?>"></span>
											<?php endif; ?>
										</button>
									</li>
								<?php endforeach; ?>
							<?php elseif ( ! $logged ) : ?>
								<li class="snf-msg__empty-rail">
									<p><?php echo esc_html( $i18n['signIn'] ); ?></p>
									<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( $boot['loginUrl'] ); ?>">
										<?php esc_html_e( 'Member login', 'super-ninety-four' ); ?>
									</a>
								</li>
							<?php else : ?>
								<li class="snf-msg__empty-rail">
									<p><?php esc_html_e( 'No conversations yet.', 'super-ninety-four' ); ?></p>
								</li>
							<?php endif; ?>
						</ul>
					</aside>

					<section class="snf-msg__stage" aria-live="polite">
						<header class="snf-msg__stage-head" id="snf-msg-head">
							<?php if ( $logged && ! empty( $threads[0] ) ) : ?>
								<span class="snf-msg__avatar snf-msg__avatar--lg" data-kind="<?php echo esc_attr( $threads[0]['avatar_kind'] ); ?>" aria-hidden="true">
									<?php echo esc_html( $threads[0]['avatar_glyph'] ); ?>
								</span>
								<div class="snf-msg__stage-meta">
									<h3 class="snf-msg__stage-title" id="snf-msg-title"><?php echo esc_html( $threads[0]['title'] ); ?></h3>
									<p class="snf-msg__stage-status" id="snf-msg-status"><?php echo esc_html( $i18n['online'] ); ?></p>
								</div>
							<?php else : ?>
								<div class="snf-msg__stage-meta">
									<h3 class="snf-msg__stage-title" id="snf-msg-title"><?php echo esc_html( $i18n['selectThread'] ); ?></h3>
									<p class="snf-msg__stage-status" id="snf-msg-status">
										<?php echo $logged ? '' : esc_html( $i18n['signIn'] ); ?>
									</p>
								</div>
							<?php endif; ?>
						</header>

						<div class="snf-msg__stream" id="snf-msg-stream" role="log" aria-relevant="additions">
							<?php if ( ! $logged ) : ?>
								<div class="snf-msg__placeholder">
									<p><?php echo esc_html( $i18n['signIn'] ); ?></p>
									<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( $boot['loginUrl'] ); ?>">
										<?php esc_html_e( 'Open member login', 'super-ninety-four' ); ?>
									</a>
								</div>
							<?php else : ?>
								<div class="snf-msg__placeholder" id="snf-msg-placeholder">
									<p><?php echo esc_html( $i18n['emptyThread'] ); ?></p>
								</div>
							<?php endif; ?>
						</div>

						<form class="snf-msg__composer" id="snf-msg-composer" autocomplete="off" <?php echo $logged ? '' : ' hidden'; ?>>
							<label class="screen-reader-text" for="snf-msg-input"><?php echo esc_html( $i18n['placeholder'] ); ?></label>
							<textarea
								id="snf-msg-input"
								name="body"
								rows="1"
								placeholder="<?php echo esc_attr( $i18n['placeholder'] ); ?>"
								maxlength="2000"
								<?php disabled( ! $logged ); ?>
							></textarea>
							<button class="snf-msg__send" type="submit" id="snf-msg-send" <?php disabled( ! $logged ); ?>>
								<span class="snf-msg__send-label"><?php echo esc_html( $i18n['send'] ); ?></span>
								<svg class="snf-msg__send-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
									<path fill="currentColor" d="M3.4 20.6 21 12 3.4 3.4 3 10.5 15 12 3 13.5z"/>
								</svg>
							</button>
						</form>
					</section>
				</div>
				<?php do_action( 'snf_messages_hub_threads' ); ?>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
