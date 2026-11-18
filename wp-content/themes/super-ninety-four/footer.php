<?php
/**
 * Theme footer chrome + document close.
 * When $GLOBALS['snf_fse_embed'] is set, outputs nothing (FSE php-view embed).
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $GLOBALS['snf_fse_embed'] ) ) {
	return;
}

get_template_part( 'template-parts/site', 'footer' );
wp_footer();
?>
</body>
</html>
