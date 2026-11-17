<?php
/**
 * BuddyPress — Users Header (Figma-ready identity strip).
 *
 * @package SuperNinetyFour
 * @version 7.0.0
 */

$member = snf_get_displayed_member_context();
?>

<div class="snf-profile-identity" data-figma-slot="profile-identity">
	<div id="item-header-avatar" class="snf-profile-identity__avatar">
		<a href="<?php bp_displayed_user_link(); ?>">
			<img
				src="<?php echo esc_url( $member['avatar'] ); ?>"
				alt=""
				width="120"
				height="120"
				decoding="async"
			>
		</a>
	</div><!-- #item-header-avatar -->

	<div id="item-header-content" class="snf-profile-identity__content">
		<p class="snf-profile-identity__eyebrow"><?php echo esc_html( $member['membership'] ); ?></p>
		<h2 class="snf-profile-identity__name"><?php echo esc_html( $member['name'] ); ?></h2>

		<?php if ( $member['mention'] ) : ?>
			<p class="user-nicename snf-profile-identity__handle">@<?php echo esc_html( $member['mention'] ); ?></p>
		<?php endif; ?>

		<?php bp_nouveau_member_hook( 'before', 'header_meta' ); ?>

		<?php if ( bp_nouveau_member_has_meta() ) : ?>
			<div class="item-meta snf-profile-identity__meta">
				<?php bp_nouveau_member_meta(); ?>
			</div><!-- #item-meta -->
		<?php endif; ?>

		<?php
		bp_member_type_list(
			bp_displayed_user_id(),
			array(
				'label'        => array(
					'plural'   => __( 'Member Types', 'buddypress' ),
					'singular' => __( 'Member Type', 'buddypress' ),
				),
				'list_element' => 'span',
			)
		);
		?>

		<div class="snf-profile-identity__actions" data-figma-slot="profile-identity-actions">
			<?php bp_nouveau_member_header_buttons( array( 'container_classes' => array( 'member-header-actions' ) ) ); ?>
		</div>
	</div><!-- #item-header-content -->
</div>
