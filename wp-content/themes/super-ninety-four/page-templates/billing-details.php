<?php
/**
 * Template Name: Billing Details Hub
 * Description: Membership dues, house account, and invoice boilerplates.
 */

get_header();

$data        = snf_get_billing_hub_data();
$current_slug = isset( $data['plan']['slug'] ) ? $data['plan']['slug'] : '';
?>
<main class="snf-page snf-hub snf-hub--billing" data-figma-ready="true">
	<section class="snf-hub__shell snf-container" aria-label="<?php esc_attr_e( 'Billing details', 'super-ninety-four' ); ?>">
		<?php
		get_template_part(
			'template-parts/hub/page-hero',
			null,
			array(
				'eyebrow' => __( 'Accounts receivable', 'super-ninety-four' ),
				'title'   => __( 'Billing Details', 'super-ninety-four' ),
				'lede'    => __( 'Initiation, monthly dues, and renewal reminders for Back Creek, Badlands, Regions NE, and City to City membership.', 'super-ninety-four' ),
				'actions' => array(
					array(
						'label' => __( 'Download statements', 'super-ninety-four' ),
						'url'   => '#snf-invoices',
					),
				),
			)
		);
		?>

		<div class="snf-hub__layout">
			<aside class="snf-hub__aside">
				<?php get_template_part( 'template-parts/hub/account-nav', null, array( 'current' => 'billing-details' ) ); ?>
			</aside>

			<div class="snf-hub__main">
				<?php get_template_part( 'template-parts/hub/metrics', null, array( 'metrics' => $data['metrics'] ) ); ?>

				<section class="snf-hub__panel snf-hub__panel--initiation" data-figma-slot="billing-initiation">
					<div class="snf-hub__panel-head">
						<h2 class="snf-hub__panel-title"><?php echo esc_html( $data['initiation']['label'] ); ?></h2>
						<span class="snf-hub__panel-note"><?php esc_html_e( 'All plans', 'super-ninety-four' ); ?></span>
					</div>
					<div class="snf-hub__plan">
						<p class="snf-hub__plan-price"><?php echo esc_html( $data['initiation']['amount'] ); ?></p>
						<p class="snf-hub__plan-meta"><?php echo esc_html( $data['initiation']['meta'] ); ?></p>
					</div>
				</section>

				<section class="snf-hub__panel snf-hub__panel--plans" data-figma-slot="billing-plans">
					<div class="snf-hub__panel-head">
						<h2 class="snf-hub__panel-title"><?php esc_html_e( 'Membership plans', 'super-ninety-four' ); ?></h2>
						<span class="snf-hub__panel-note"><?php esc_html_e( '12-month terms', 'super-ninety-four' ); ?></span>
					</div>
					<ul class="snf-hub__plan-grid">
						<?php foreach ( $data['plans'] as $plan ) : ?>
							<?php $is_current = ( $current_slug === $plan['slug'] ); ?>
							<li class="snf-hub__plan-card<?php echo $is_current ? ' is-current' : ''; ?>">
								<div class="snf-hub__plan-card-head">
									<p class="snf-hub__plan-name"><?php echo esc_html( $plan['name'] ); ?></p>
									<?php if ( $is_current ) : ?>
										<span class="snf-hub__pill snf-hub__pill--ok"><?php esc_html_e( 'Current', 'super-ninety-four' ); ?></span>
									<?php endif; ?>
								</div>
								<p class="snf-hub__plan-price">
									<?php echo esc_html( $plan['price'] ); ?><span class="snf-hub__plan-cadence"><?php echo esc_html( $plan['cadence'] ); ?></span>
								</p>
								<p class="snf-hub__plan-meta"><?php echo esc_html( $plan['term'] ); ?></p>
								<p class="snf-hub__plan-note"><?php echo esc_html( $plan['note'] ); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>

				<section class="snf-hub__panel" data-figma-slot="billing-methods">
					<div class="snf-hub__panel-head">
						<h2 class="snf-hub__panel-title"><?php esc_html_e( 'Payment methods', 'super-ninety-four' ); ?></h2>
					</div>
					<ul class="snf-hub__rows">
						<?php foreach ( $data['methods'] as $method ) : ?>
							<li class="snf-hub__row">
								<div>
									<p class="snf-hub__row-title"><?php echo esc_html( $method['label'] ); ?></p>
									<p class="snf-hub__row-meta"><?php echo esc_html( $method['meta'] ); ?></p>
								</div>
								<button class="snf-hub__row-action" type="button"><?php echo esc_html( $method['action'] ); ?></button>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>

				<section id="snf-invoices" class="snf-hub__panel" data-figma-slot="billing-invoices">
					<div class="snf-hub__panel-head">
						<h2 class="snf-hub__panel-title"><?php esc_html_e( 'Invoice history', 'super-ninety-four' ); ?></h2>
						<span class="snf-hub__panel-note"><?php esc_html_e( 'Club ledger', 'super-ninety-four' ); ?></span>
					</div>
					<?php if ( empty( $data['invoices'] ) ) : ?>
						<p class="snf-hub__lede">
							<?php esc_html_e( 'No paid invoices yet. Completed WooCommerce orders will appear here.', 'super-ninety-four' ); ?>
						</p>
						<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
							<div class="snf-hub__actions">
								<a class="snf-hub__action" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">
									<?php esc_html_e( 'View orders', 'super-ninety-four' ); ?>
								</a>
							</div>
						<?php endif; ?>
					<?php else : ?>
						<ul class="snf-hub__rows snf-hub__rows--dense">
							<?php foreach ( $data['invoices'] as $invoice ) : ?>
								<li class="snf-hub__row">
									<div>
										<p class="snf-hub__row-title"><?php echo esc_html( $invoice['label'] ); ?></p>
										<p class="snf-hub__row-meta">
											<?php echo esc_html( $invoice['id'] ); ?>
											<span aria-hidden="true">·</span>
											<?php echo esc_html( $invoice['date'] ); ?>
										</p>
									</div>
									<div class="snf-hub__row-end">
										<span class="snf-hub__amount"><?php echo esc_html( $invoice['amount'] ); ?></span>
										<span class="snf-hub__pill snf-hub__pill--ok"><?php echo esc_html( $invoice['status'] ); ?></span>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
