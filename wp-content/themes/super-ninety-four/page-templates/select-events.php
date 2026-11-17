<?php
/**
 * Template Name: Select Events Listings
 * Description: Member / non-member event listing pages using dashboard cards.
 */

$event_id = snf_get_requested_event_id();
if ( $event_id && snf_is_lifestyle_concierge_workshop( $event_id ) ) {
	wp_safe_redirect( snf_get_workshop_landing_url() );
	exit;
}

$is_members     = is_page( 'select-events-by-members' );
$is_non_members = is_page( 'select-events-by-non-members' );

// Logged-out visitors deep-linking a non-member event go to Apply
// (Lifestyle Concierge Club Workshop already redirected above).
if ( $is_non_members && ! is_user_logged_in() && $event_id ) {
	wp_safe_redirect(
		add_query_arg(
			array(
				'q'         => get_the_title( $event_id ),
				'snf_event' => $event_id,
			),
			snf_get_page_url( 'apply' )
		)
	);
	exit;
}

if ( $is_members ) {
	$listing_id = 'select-events-members';
	$bp_module  = 'events';
	$title      = __( 'Select events (members)', 'super-ninety-four' );
	$lede       = __( 'Private member gatherings and club circuit events reserved for principals and reciprocal members.', 'super-ninety-four' );
	$cards      = snf_get_dashboard_member_event_cards();
	$empty      = __( 'No member events are listed yet.', 'super-ninety-four' );
} else {
	$listing_id = 'events-non-members';
	$bp_module  = 'events';
	$title      = __( 'Select events (non-members)', 'super-ninety-four' );
	$lede       = __( 'Open workshops and admission events for guests exploring SUPER NINETY FOUR hospitality.', 'super-ninety-four' );
	$cards      = snf_get_dashboard_non_member_event_cards();
	$empty      = __( 'No non-member events are listed yet.', 'super-ninety-four' );
}

$card_count = count( $cards );
$row_class  = 'snf-dashboard__row';
if ( $card_count >= 4 ) {
	$row_class .= ' snf-dashboard__row--four';
}

get_header();
?>
<main class="snf-page snf-dashboard snf-dashboard--select-events" data-figma-ready="true">
	<section class="snf-dashboard__shell snf-container" aria-label="<?php echo esc_attr( $title ); ?>">

		<div class="snf-dashboard__intro" data-figma-slot="select-events-intro">
			<p class="snf-hub__eyebrow">
				<?php
				echo esc_html(
					$is_members
						? __( 'Members only', 'super-ninety-four' )
						: __( 'Open admission', 'super-ninety-four' )
				);
				?>
			</p>
			<h1 class="snf-dashboard__intro-title"><?php echo esc_html( $title ); ?></h1>
			<p class="snf-hub__lede"><?php echo esc_html( $lede ); ?></p>
			<div class="snf-dashboard__search" data-figma-slot="select-events-search">
				<?php get_template_part( 'template-parts/search', 'events' ); ?>
			</div>
		</div>

		<section
			class="<?php echo esc_attr( $row_class ); ?>"
			data-listing="<?php echo esc_attr( $listing_id ); ?>"
			data-bp-module="<?php echo esc_attr( $bp_module ); ?>"
			data-figma-slot="select-events-listings"
			aria-labelledby="snf-select-events-heading"
		>
			<div class="snf-dashboard__row-head">
				<h2 class="snf-dashboard__row-title" id="snf-select-events-heading">
					<?php echo esc_html( $title ); ?>
				</h2>
				<a class="snf-dashboard__row-all" href="<?php echo esc_url( snf_get_dashboard_url() ); ?>">
					<?php esc_html_e( 'Back to dashboard', 'super-ninety-four' ); ?>
				</a>
			</div>

			<?php
			get_template_part(
				'template-parts/dashboard/event-cards',
				null,
				array(
					'cards'      => $cards,
					'empty_text' => $empty,
				)
			);
			?>
		</section>

	</section>
</main>
<?php
get_footer();
