<?php
/**
 * Template Name: Settings Hub
 * Description: Account, privacy, and club preference boilerplates.
 */

get_header();

$data = snf_get_settings_hub_data();
?>
<main class="snf-page snf-hub snf-hub--settings" data-figma-ready="true">
	<section class="snf-hub__shell snf-container" aria-label="<?php esc_attr_e( 'Account settings', 'super-ninety-four' ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'Account controls', 'super-ninety-four' ),
				'title'   => __( 'Settings', 'super-ninety-four' ),
				'lede'    => __( 'Notifications, privacy, and club defaults — mapped for BuddyPress settings screens and Figma components.', 'super-ninety-four' ),
			)
		);
		?>

		<div class="snf-hub__layout">
			<aside class="snf-hub__aside">
				<?php get_template_part( 'template-parts/hub/account-nav', null, array( 'current' => 'settings' ) ); ?>
			</aside>

			<div class="snf-hub__main">
				<?php foreach ( $data['groups'] as $group ) : ?>
					<section
						class="snf-hub__panel"
						data-figma-slot="settings-<?php echo esc_attr( $group['id'] ); ?>"
						data-bp-module="<?php echo esc_attr( $group['bp'] ); ?>"
					>
						<div class="snf-hub__panel-head">
							<h2 class="snf-hub__panel-title"><?php echo esc_html( $group['title'] ); ?></h2>
						</div>
						<ul class="snf-hub__switch-list">
							<?php foreach ( $group['options'] as $option ) : ?>
								<li>
									<label class="snf-hub__switch<?php echo ! empty( $option['checked'] ) ? ' is-on' : ''; ?>">
										<span><?php echo esc_html( $option['label'] ); ?></span>
										<input type="checkbox" <?php checked( ! empty( $option['checked'] ) ); ?>>
										<span class="snf-hub__switch-ui" aria-hidden="true"></span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endforeach; ?>

				<section class="snf-hub__panel" data-figma-slot="settings-danger">
					<div class="snf-hub__panel-head">
						<h2 class="snf-hub__panel-title"><?php esc_html_e( 'Session', 'super-ninety-four' ); ?></h2>
					</div>
					<div class="snf-hub__actions">
						<a class="snf-hub__action" href="<?php echo esc_url( snf_get_page_url( 'member-login' ) ); ?>">
							<?php esc_html_e( 'Switch member login', 'super-ninety-four' ); ?>
						</a>
						<a class="snf-hub__action snf-hub__action--primary" href="<?php echo esc_url( snf_get_page_url( 'my-profile' ) ); ?>">
							<?php esc_html_e( 'Back to profile', 'super-ninety-four' ); ?>
						</a>
					</div>
				</section>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
