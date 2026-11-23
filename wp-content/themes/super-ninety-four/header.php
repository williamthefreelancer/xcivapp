<?php
/**
 * Theme header document shell + chrome.
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
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php get_template_part( 'template-parts/site', 'header' ); ?>
