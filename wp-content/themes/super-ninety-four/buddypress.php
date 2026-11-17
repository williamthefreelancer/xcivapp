<?php
/**
 * BuddyPress root template — hybrid shell for member profiles.
 *
 * @package SuperNinetyFour
 */

get_header();

$is_member = snf_is_bp_member_profile();
$member    = $is_member ? snf_get_displayed_member_context() : array();
?>
<main
	class="snf-page snf-dashboard snf-dashboard--profile<?php echo $is_member ? '' : ' snf-dashboard--bp-directory'; ?>"
	data-figma-ready="true"
	data-figma-slot="buddypress-root"
>
	<?php if ( $is_member ) : ?>
		<?php
		get_template_part(
			'template-parts/dashboard/shell',
			null,
			array(
				'current'    => 'profile',
				'main_class' => 'snf-dashboard-shell__main--profile',
			)
		);
		?>

		<div class="snf-account-surface snf-profile-surface" data-figma-slot="profile-surface">
			<header class="snf-account-surface__intro" data-figma-slot="profile-intro">
				<h1 class="snf-account-surface__title">
					<?php
					echo esc_html(
						! empty( $member['is_mine'] )
							? __( 'My Profile', 'super-ninety-four' )
							: ( isset( $member['name'] ) ? $member['name'] : __( 'Profile', 'super-ninety-four' ) )
					);
					?>
				</h1>
				<p class="snf-account-surface__lede">
					<?php esc_html_e( 'Identity, about, and network — ready for your Figma layouts.', 'super-ninety-four' ); ?>
				</p>
			</header>

			<div class="snf-profile-surface__content" data-figma-slot="profile-content">
				<?php
				while ( have_posts() ) :
					the_post();
					the_content();
				endwhile;
				?>
			</div>
		</div>

		<?php get_template_part( 'template-parts/dashboard/shell', 'end' ); ?>

	<?php else : ?>

		<section class="snf-container snf-bp-directory" data-figma-slot="bp-directory" aria-label="<?php esc_attr_e( 'Community', 'super-ninety-four' ); ?>">
			<div class="snf-canvas" data-figma-ready="true">
				<?php
				while ( have_posts() ) :
					the_post();
					the_content();
				endwhile;
				?>
			</div>
		</section>

	<?php endif; ?>
</main>
<?php
get_footer();
