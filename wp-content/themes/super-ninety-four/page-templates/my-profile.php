<?php
/**
 * Template Name: My Profile Hub
 * Description: BuddyPress-ready member profile boilerplates for club hospitality.
 */

get_header();

$data   = snf_get_profile_hub_data();
$member = $data['member'];
?>
<main class="snf-page snf-hub snf-hub--profile" data-figma-ready="true" data-bp-ready="<?php echo $member['bp_active'] ? 'true' : 'false'; ?>">
	<section class="snf-hub__shell snf-container" aria-label="<?php esc_attr_e( 'Member profile', 'super-ninety-four' ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'Member desk', 'super-ninety-four' ),
				'title'   => __( 'My Profile', 'super-ninety-four' ),
				'lede'    => __( 'A private member record for hospitality preferences, club standing, and BuddyPress identity — ready for your Figma layer.', 'super-ninety-four' ),
				'actions' => array(
					array(
						'label' => __( 'Open messages', 'super-ninety-four' ),
						'url'   => snf_get_page_url( 'messages' ),
					),
					array(
						'label' => __( 'Edit settings', 'super-ninety-four' ),
						'url'   => snf_get_page_url( 'settings' ),
					),
				),
			)
		);
		?>

		<div class="snf-hub__layout">
			<aside class="snf-hub__aside">
				<?php get_template_part( 'template-parts/hub/account-nav', null, array( 'current' => 'my-profile' ) ); ?>
			</aside>

			<div class="snf-hub__main">
				<section class="snf-hub__identity" data-figma-slot="profile-identity" data-bp-module="members">
					<div class="snf-hub__identity-media">
						<img
							src="<?php echo esc_url( $member['avatar'] ); ?>"
							alt=""
							width="96"
							height="96"
							decoding="async"
						>
					</div>
					<div class="snf-hub__identity-copy">
						<p class="snf-hub__identity-name"><?php echo esc_html( $member['name'] ); ?></p>
						<p class="snf-hub__identity-meta">
							@<?php echo esc_html( $member['handle'] ); ?>
							<span aria-hidden="true">·</span>
							<?php echo esc_html( $member['membership'] ); ?>
						</p>
						<p class="snf-hub__identity-club"><?php echo esc_html( $member['club'] ); ?></p>
						<span class="snf-hub__pill"><?php echo esc_html( $member['status'] ); ?></span>
					</div>
					<?php if ( ! empty( $member['profile_url'] ) ) : ?>
						<a class="snf-hub__ghost-link" href="<?php echo esc_url( $member['profile_url'] ); ?>">
							<?php esc_html_e( 'BuddyPress profile', 'super-ninety-four' ); ?>
						</a>
					<?php endif; ?>
				</section>

				<?php get_template_part( 'template-parts/hub/metrics', null, array( 'metrics' => $data['metrics'] ) ); ?>

				<section class="snf-hub__panel" data-figma-slot="profile-fields" data-bp-module="xprofile">
					<div class="snf-hub__panel-head">
						<h2 class="snf-hub__panel-title"><?php esc_html_e( 'Hospitality profile', 'super-ninety-four' ); ?></h2>
						<span class="snf-hub__panel-note"><?php esc_html_e( 'xProfile field map', 'super-ninety-four' ); ?></span>
					</div>
					<dl class="snf-hub__fields">
						<?php foreach ( $data['fields'] as $field ) : ?>
							<div class="snf-hub__field" data-bp-field="<?php echo esc_attr( $field['bp'] ); ?>">
								<dt><?php echo esc_html( $field['label'] ); ?></dt>
								<dd><?php echo esc_html( $field['value'] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
					<?php
					/**
					 * Fires inside the profile fields panel for BuddyPress / custom field UIs.
					 */
					do_action( 'snf_profile_hub_fields' );
					?>
				</section>

				<div class="snf-hub__split">
					<?php foreach ( $data['modules'] as $module ) : ?>
						<section
							class="snf-hub__panel"
							data-figma-slot="profile-<?php echo esc_attr( $module['id'] ); ?>"
							data-bp-module="<?php echo esc_attr( $module['bp'] ); ?>"
						>
							<div class="snf-hub__panel-head">
								<h2 class="snf-hub__panel-title"><?php echo esc_html( $module['title'] ); ?></h2>
							</div>
							<ul class="snf-hub__plain-list">
								<?php foreach ( $module['items'] as $item ) : ?>
									<li><?php echo esc_html( $item ); ?></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
