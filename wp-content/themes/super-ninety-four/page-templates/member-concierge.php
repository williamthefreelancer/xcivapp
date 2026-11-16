<?php
/**
 * Template Name: Member Concierge Hub
 * Description: Hospitality request desk boilerplates for club members.
 */

get_header();

$data = snf_get_concierge_hub_data();
?>
<main class="snf-page snf-hub snf-hub--concierge" data-figma-ready="true">
	<section class="snf-hub__shell snf-container" aria-label="<?php esc_attr_e( 'Member concierge', 'super-ninety-four' ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'Lifestyle Concierge', 'super-ninety-four' ),
				'title'   => __( 'Member Concierge', 'super-ninety-four' ),
				'lede'    => __( 'Request dining, guest access, reciprocal introductions, and arrival details through one hospitality desk.', 'super-ninety-four' ),
				'actions' => array(
					array(
						'label' => __( 'Message the desk', 'super-ninety-four' ),
						'url'   => snf_get_page_url( 'messages' ),
					),
					array(
						'label' => __( 'Browse events', 'super-ninety-four' ),
						'url'   => snf_get_page_url( 'select-events-by-members' ),
					),
				),
			)
		);
		?>

		<div class="snf-hub__layout">
			<aside class="snf-hub__aside">
				<?php get_template_part( 'template-parts/hub/account-nav', null, array( 'current' => 'member-concierge' ) ); ?>
			</aside>

			<div class="snf-hub__main">
				<section class="snf-hub__panel" data-figma-slot="concierge-services">
					<div class="snf-hub__panel-head">
						<h2 class="snf-hub__panel-title"><?php esc_html_e( 'How can we assist?', 'super-ninety-four' ); ?></h2>
						<span class="snf-hub__panel-note"><?php esc_html_e( 'Request types', 'super-ninety-four' ); ?></span>
					</div>
					<ul class="snf-hub__service-grid">
						<?php foreach ( $data['services'] as $service ) : ?>
							<li>
								<button
									class="snf-hub__service"
									type="button"
									data-bp-module="<?php echo esc_attr( $service['bp'] ); ?>"
									data-figma-slot="concierge-service"
								>
									<span class="snf-hub__service-title"><?php echo esc_html( $service['title'] ); ?></span>
									<span class="snf-hub__service-meta"><?php echo esc_html( $service['meta'] ); ?></span>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>

				<section class="snf-hub__panel" data-figma-slot="concierge-compose" data-bp-module="messages">
					<div class="snf-hub__panel-head">
						<h2 class="snf-hub__panel-title"><?php esc_html_e( 'New concierge request', 'super-ninety-four' ); ?></h2>
					</div>
					<form class="snf-hub__form" action="#" method="post" onsubmit="return false;">
						<label class="snf-hub__control">
							<span><?php esc_html_e( 'Request type', 'super-ninety-four' ); ?></span>
							<select name="snf_concierge_type">
								<option><?php esc_html_e( 'Table & private dining', 'super-ninety-four' ); ?></option>
								<option><?php esc_html_e( 'Guest passes', 'super-ninety-four' ); ?></option>
								<option><?php esc_html_e( 'Event desk', 'super-ninety-four' ); ?></option>
								<option><?php esc_html_e( 'Reciprocal letters', 'super-ninety-four' ); ?></option>
								<option><?php esc_html_e( 'Transport & arrival', 'super-ninety-four' ); ?></option>
							</select>
						</label>
						<label class="snf-hub__control">
							<span><?php esc_html_e( 'Preferred date', 'super-ninety-four' ); ?></span>
							<input type="date" name="snf_concierge_date" value="2026-11-21">
						</label>
						<label class="snf-hub__control snf-hub__control--full">
							<span><?php esc_html_e( 'Details for the desk', 'super-ninety-four' ); ?></span>
							<textarea name="snf_concierge_notes" rows="4" placeholder="<?php esc_attr_e( 'Party size, dietary notes, arrival window…', 'super-ninety-four' ); ?>"></textarea>
						</label>
						<button class="snf-hub__action snf-hub__action--primary" type="submit">
							<?php esc_html_e( 'Submit request', 'super-ninety-four' ); ?>
						</button>
					</form>
				</section>

				<section class="snf-hub__panel" data-figma-slot="concierge-requests">
					<div class="snf-hub__panel-head">
						<h2 class="snf-hub__panel-title"><?php esc_html_e( 'Open requests', 'super-ninety-four' ); ?></h2>
					</div>
					<ul class="snf-hub__rows">
						<?php foreach ( $data['requests'] as $request ) : ?>
							<li class="snf-hub__row">
								<div>
									<p class="snf-hub__row-title"><?php echo esc_html( $request['title'] ); ?></p>
									<p class="snf-hub__row-meta"><?php echo esc_html( $request['meta'] ); ?></p>
								</div>
								<span class="snf-hub__pill"><?php echo esc_html( $request['status'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
