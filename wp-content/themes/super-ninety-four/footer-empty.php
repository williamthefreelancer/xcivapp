<?php
/**
 * Minimal document close (no site footer chrome).
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $GLOBALS['snf_fse_embed'] ) ) {
	return;
}

wp_footer();
?>
</body>
</html>
