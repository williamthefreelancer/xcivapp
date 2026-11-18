<?php
/**
 * FSE php-view: loaded by Site Editor templates via snf/php-view (not a classic page template).
 * Description: Private membership circles + desk messaging entry (no BuddyPress).
 */

get_header();

$boot    = function_exists( 'snf_community_get_bootstrap' ) ? snf_community_get_bootstrap() : array();
$circles = isset( $boot['circles'] ) ? $boot['circles'] : array();
$i18n    = isset( $boot['i18n'] ) ? $boot['i18n'] : array();
?>
<main class="snf-page snf-hub snf-hub--community" data-figma-ready="true">
	<section class="snf-hub__shell snf-container" aria-label="<?php esc_attr_e( 'Community', 'super-ninety-four' ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'Private membership club', 'super-ninety-four' ),
				'title'   => __( 'Community', 'super-ninety-four' ),
				'lede'    => __( 'Member circles for lounge notes, events coordination, and Expo operators — without BuddyPress.', 'super-ninety-four' ),
			)
		);
		?>

		<div class="snf-hub__layout">
			<aside class="snf-hub__aside">
				<?php get_template_part( 'template-parts/hub/account-nav', null, array( 'current' => 'community' ) ); ?>
			</aside>

			<div class="snf-hub__main">
				<div
					id="snf-community"
					class="snf-community"
					data-figma-slot="community-circles"
				>
					<div class="snf-community__actions">
						<?php if ( ! empty( $boot['messagesUrl'] ) ) : ?>
							<a class="snf-community__cta" href="<?php echo esc_url( $boot['messagesUrl'] ); ?>">
								<?php esc_html_e( 'Open messages', 'super-ninety-four' ); ?>
							</a>
						<?php endif; ?>
						<?php if ( ! empty( $boot['eventsUrl'] ) ) : ?>
							<a class="snf-community__cta snf-community__cta--ghost" href="<?php echo esc_url( $boot['eventsUrl'] ); ?>">
								<?php esc_html_e( 'Select events', 'super-ninety-four' ); ?>
							</a>
						<?php endif; ?>
						<?php if ( ! empty( $boot['expoUrl'] ) ) : ?>
							<a class="snf-community__cta snf-community__cta--ghost" href="<?php echo esc_url( $boot['expoUrl'] ); ?>">
								<?php esc_html_e( 'Expo marketplace', 'super-ninety-four' ); ?>
							</a>
						<?php endif; ?>
					</div>

					<ul class="snf-community__grid" id="snf-community-circles" role="list">
						<?php if ( empty( $circles ) ) : ?>
							<li class="snf-community__empty">
								<p><?php echo esc_html( isset( $i18n['empty'] ) ? $i18n['empty'] : __( 'No circles yet.', 'super-ninety-four' ) ); ?></p>
							</li>
						<?php else : ?>
							<?php foreach ( $circles as $circle ) : ?>
								<li class="snf-community__card" data-circle-id="<?php echo esc_attr( (string) $circle['id'] ); ?>">
									<?php if ( ! empty( $circle['image'] ) ) : ?>
										<img class="snf-community__image" src="<?php echo esc_url( $circle['image'] ); ?>" alt="" width="480" height="270" loading="lazy" decoding="async">
									<?php endif; ?>
									<div class="snf-community__body">
										<h2 class="snf-community__title"><?php echo esc_html( $circle['title'] ); ?></h2>
										<p class="snf-community__excerpt"><?php echo esc_html( $circle['excerpt'] ); ?></p>
										<p class="snf-community__meta">
											<span data-member-count><?php echo esc_html( (string) (int) $circle['memberCount'] ); ?></span>
											<?php echo esc_html( isset( $i18n['members'] ) ? $i18n['members'] : __( 'members', 'super-ninety-four' ) ); ?>
										</p>
										<button
											type="button"
											class="snf-community__join<?php echo ! empty( $circle['joined'] ) ? ' is-joined' : ''; ?>"
											data-circle-join
											data-joined="<?php echo ! empty( $circle['joined'] ) ? '1' : '0'; ?>"
										>
											<?php
											echo esc_html(
												! empty( $circle['joined'] )
													? ( isset( $i18n['leave'] ) ? $i18n['leave'] : __( 'Leave', 'super-ninety-four' ) )
													: ( isset( $i18n['join'] ) ? $i18n['join'] : __( 'Join', 'super-ninety-four' ) )
											);
											?>
										</button>
									</div>
								</li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
