<?php
/**
 * SureForms Pro WordPress-plugin integration handler bootstrap.
 *
 * Pro loads this via srfm_pro_wordpress_plugin_integrations. Action callbacks
 * live in app/Services/sureforms-funnel.php (srfm_snf_funnel_* hooks).
 *
 * @package SuperNinetyFour
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure the funnel service is loaded when Pro boots this file early.
$snf_funnel = get_template_directory() . '/app/Services/sureforms-funnel.php';
if ( file_exists( $snf_funnel ) && ! function_exists( 'snf_process_funnel_intent' ) ) {
	require_once $snf_funnel;
}
