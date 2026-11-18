<?php
/**
 * Apply known Stripe Product/Price ids onto Woo membership products.
 *
 * Usage: wp eval-file wp-content/themes/super-ninety-four/bin/snf-apply-stripe-catalog-ids.php
 *
 * @package Super_Ninety_Four
 */

if ( ! function_exists( 'snf_ensure_membership_products' ) || ! function_exists( 'snf_set_membership_product_stripe_ids' ) ) {
	echo "helpers:missing\n";
	exit( 1 );
}

snf_ensure_membership_products( true );
if ( function_exists( 'snf_ensure_application_product' ) ) {
	delete_option( 'snf_membership_products_seeded_v2' );
	snf_ensure_application_product();
}

$catalog = array(
	'membership-application'  => array(
		'test' => array( 'product' => 'prod_VeO1XflgyRf7Ze', 'price' => 'price_1Ud5FnE7g7h7od8PIX35VRs9' ),
		'live' => array( 'product' => 'prod_VeO2eLYIyx8VJV', 'price' => 'price_1Ud5G1EPYpF6rD2f7NPCEqMh' ),
	),
	'membership-initiation'   => array(
		'test' => array( 'product' => 'prod_VeO1hoRc0h4GF5', 'price' => 'price_1Ud5FnE7g7h7od8PCZlz8BhA' ),
		'live' => array( 'product' => 'prod_VeO2Yn2DuEYtI9', 'price' => 'price_1Ud5G1EPYpF6rD2folk3T8BL' ),
	),
	'membership-back-creek'   => array(
		'test' => array( 'product' => 'prod_VeO1mv0ih2Pk7Z', 'price' => 'price_1Ud5FnE7g7h7od8P1BIWB0Sr' ),
		'live' => array( 'product' => 'prod_VeO2ILo8WnS8Vt', 'price' => 'price_1Ud5G1EPYpF6rD2fjJL5RAL4' ),
	),
	'membership-badlands'     => array(
		'test' => array( 'product' => 'prod_VeO1kSWCpPckEc', 'price' => 'price_1Ud5FnE7g7h7od8PFcuECL0q' ),
		'live' => array( 'product' => 'prod_VeO239V0E9sA4x', 'price' => 'price_1Ud5G1EPYpF6rD2futbtVIyw' ),
	),
	'membership-regions-ne'   => array(
		'test' => array( 'product' => 'prod_VeO18kNJQM8Weq', 'price' => 'price_1Ud5FoE7g7h7od8Pwwn1v9mn' ),
		'live' => array( 'product' => 'prod_VeO2cWOVZDaOuH', 'price' => 'price_1Ud5G3EPYpF6rD2fnejd1V8L' ),
	),
	'membership-city-to-city' => array(
		'test' => array( 'product' => 'prod_VeO12YNrNSDhGF', 'price' => 'price_1Ud5FqE7g7h7od8PfKnRsxO8' ),
		'live' => array( 'product' => 'prod_VeO2S13L9WN7im', 'price' => 'price_1Ud5G4EPYpF6rD2fSr1axHtP' ),
	),
);

$applied = 0;
$missing = 0;
foreach ( $catalog as $sku => $modes ) {
	$woo_id = snf_get_membership_product_id_by_sku( $sku );
	if ( $woo_id <= 0 ) {
		echo "sku:{$sku}:missing_woo\n";
		++$missing;
		continue;
	}
	foreach ( array( 'test', 'live' ) as $mode ) {
		snf_set_membership_product_stripe_ids(
			$woo_id,
			$sku,
			$mode,
			$modes[ $mode ]['product'],
			$modes[ $mode ]['price']
		);
	}
	echo "sku:{$sku}:woo={$woo_id}:ok\n";
	++$applied;
}

echo 'result:' . ( $missing ? "partial({$applied},missing={$missing})" : "ok({$applied})" ) . "\n";
exit( $missing ? 1 : 0 );
