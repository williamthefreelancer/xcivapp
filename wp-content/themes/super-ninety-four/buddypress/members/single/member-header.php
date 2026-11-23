<?php
/**
 * BuddyPress — Users Header (Figma-ready identity strip).
 *
 * @package SuperNinetyFour
 * @version 7.0.0
 */

$member         = function_exists( 'snf_get_displayed_member_context' )
	? snf_get_displayed_member_context()
	: array(
		'name'       => function_exists( 'bp_get_displayed_user_fullname' ) ? bp_get_displayed_user_fullname() : '',
		'membership' => '',
		'mention'    => function_exists( 'bp_get_displayed_user_mentionname' ) ? bp_get_displayed_user_mentionname() : '',
		'avatar'     => function_exists( 'bp_core_fetch_avatar' )
			? bp_core_fetch_avatar(
				array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'full',
					'html'    => false,
				)
			)
			: '',
	);
$snf_bp_nouveau = function_exists( 'bp_nouveau_member_hook' );
?>

<div class="snf-profile-identity" data-figma-slot="profile-identity">
	<div id="item-header-avatar" class="snf-profile-identity__avatar">
		<a href="<?php bp_displayed_user_link(); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: member display name */ __( 'View %s profile', 'super-ninety-four' ), $member['name'] ) ); ?>">
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
		<?php if ( ! empty( $member['membership'] ) ) : ?>
			<p class="snf-profile-identity__eyebrow"><?php echo esc_html( $member['membership'] ); ?></p>
		<?php endif; ?>
		<h2 class="snf-profile-identity__name"><?php echo esc_html( $member['name'] ); ?></h2>

		<?php if ( ! empty( $member['mention'] ) ) : ?>
			<p class="user-nicename snf-profile-identity__handle">@<?php echo esc_html( $member['mention'] ); ?></p>
		<?php endif; ?>

		<?php if ( $snf_bp_nouveau ) : ?>
			<?php bp_nouveau_member_hook( 'before', 'header_meta' ); ?>

			<?php if ( function_exists( 'bp_nouveau_member_has_meta' ) && bp_nouveau_member_has_meta() ) : ?>
				<div class="item-meta snf-profile-identity__meta">
					<?php bp_nouveau_member_meta(); ?>
				</div><!-- #item-meta -->
			<?php endif; ?>
		<?php endif; ?>

		<?php
		if ( function_exists( 'bp_member_type_list' ) ) {
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
		}
		?>

		<?php if ( $snf_bp_nouveau && function_exists( 'bp_nouveau_member_header_buttons' ) ) : ?>
			<div class="snf-profile-identity__actions" data-figma-slot="profile-identity-actions">
				<?php bp_nouveau_member_header_buttons( array( 'container_classes' => array( 'member-header-actions' ) ) ); ?>
			</div>
		<?php endif; ?>
	</div><!-- #item-header-content -->
</div>
