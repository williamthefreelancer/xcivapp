<?php
/**
 * Template Name: Public Content
 * Description: Lightweight public landing pages (Service Inquiries).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = get_post_field( 'post_name', get_queried_object_id() );

$copy = array(
	'inquiries' => array(
		'eyebrow' => __( 'Desk', 'super-ninety-four' ),
		'title'   => __( 'Service inquiries', 'super-ninety-four' ),
		'lede'    => __( 'Request Lifestyle Concierge services for properties, suites, and guest movement across the SUPER NINETY FOUR circuit.', 'super-ninety-four' ),
	),
);

$meta = isset( $copy[ $slug ] ) ? $copy[ $slug ] : array(
	'eyebrow' => __( 'SUPER NINETY FOUR', 'super-ninety-four' ),
	'title'   => get_the_title(),
	'lede'    => '',
);

$actions = array();
if ( 'inquiries' === $slug && function_exists( 'snf_get_dashboard_service_inquiry_cards' ) ) {
	foreach ( snf_get_dashboard_service_inquiry_cards() as $card ) {
		$actions[] = array(
			'label' => $card['title'],
			'url'   => $card['url'],
		);
	}
}

get_header();
?>
<main class="snf-page snf-hub snf-hub--public" data-figma-ready="true">
	<section class="snf-hub__shell snf-hub__shell--narrow snf-container" aria-label="<?php echo esc_attr( $meta['title'] ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => $meta['eyebrow'],
				'title'   => $meta['title'],
				'lede'    => $meta['lede'],
				'actions' => $actions,
			)
		);
		?>

		<section class="snf-hub__panel" data-figma-slot="public-body">
			<?php if ( have_posts() ) : ?>
				<?php
				while ( have_posts() ) :
					the_post();
					$content = trim( (string) get_the_content() );
					if ( $content ) {
						echo '<div class="snf-hub__lede snf-hub__rich">';
						the_content();
						echo '</div>';
					}
				endwhile;
				?>
			<?php endif; ?>
		</section>
	</section>
</main>
<?php
get_footer();
