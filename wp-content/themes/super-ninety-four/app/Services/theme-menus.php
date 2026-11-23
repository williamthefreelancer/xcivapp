<?php
/**
 * Ensure Primary / Account / Footer menus link to club hubs (BuddyPress-free).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'SNF_THEME_MENUS_LOADED' ) ) {
	return;
}
define( 'SNF_THEME_MENUS_LOADED', true );

/**
 * Canonical menu item definitions by location.
 *
 * @return array<string, array<int, array{title:string,url:string,children?:array}>>
 */
function snf_get_theme_menu_blueprints() {
	$primary = array(
		array(
			'title' => __( 'Expo', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'expo' ),
		),
		array(
			'title' => __( 'Events', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'select-events-by-non-members' ),
		),
		array(
			'title' => __( 'Inquiries', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'inquiries' ),
		),
		array(
			'title' => __( 'About', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'about' ),
		),
		array(
			'title' => __( 'Videos & Audio', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'videos-audio' ),
		),
		array(
			'title' => __( 'Contact', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'contact' ),
		),
		array(
			'title' => __( 'Newsletter', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'newsletter' ),
		),
	);

	$account = array(
		array(
			'title' => __( 'Dashboard', 'super-ninety-four' ),
			'url'   => function_exists( 'snf_get_dashboard_url' ) ? snf_get_dashboard_url() : home_url( '/my-account/' ),
		),
		array(
			'title' => __( 'Expo', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'expo' ),
		),
		array(
			'title' => __( 'Select Events', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'select-events-by-members' ),
		),
		array(
			'title' => __( 'Community', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'community' ),
		),
		array(
			'title' => __( 'Messages', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'messages' ),
		),
		array(
			'title' => __( 'Membership', 'super-ninety-four' ),
			'url'   => function_exists( 'snf_get_woo_account_url' ) ? snf_get_woo_account_url( 'membership' ) : home_url( '/my-account/membership/' ),
		),
		array(
			'title' => __( 'Tickets', 'super-ninety-four' ),
			'url'   => function_exists( 'snf_get_woo_account_url' ) ? snf_get_woo_account_url( 'tickets' ) : home_url( '/my-account/tickets/' ),
		),
		array(
			'title' => __( 'Orders', 'super-ninety-four' ),
			'url'   => function_exists( 'snf_get_woo_account_url' ) ? snf_get_woo_account_url( 'orders' ) : home_url( '/my-account/orders/' ),
		),
		array(
			'title' => __( 'Profile', 'super-ninety-four' ),
			'url'   => function_exists( 'snf_get_woo_account_url' ) ? snf_get_woo_account_url( 'edit-account' ) : home_url( '/my-account/edit-account/' ),
		),
	);

	$footer = array();
	if ( function_exists( 'snf_get_footer_legal_links' ) ) {
		foreach ( snf_get_footer_legal_links() as $link ) {
			$footer[] = array(
				'title' => $link['label'],
				'url'   => $link['url'],
			);
		}
	}

	return array(
		'primary' => $primary,
		'account' => $account,
		'footer'  => $footer,
	);
}

/**
 * Create or refresh a nav menu from a blueprint (idempotent by menu name).
 *
 * @param string $menu_name Menu name.
 * @param array  $items     Blueprint items.
 * @return int Menu term ID.
 */
function snf_theme_menus_sync_menu( $menu_name, $items ) {
	$menu = wp_get_nav_menu_object( $menu_name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $menu_id ) ) {
			return 0;
		}
	} else {
		$menu_id = (int) $menu->term_id;
		$existing = wp_get_nav_menu_items( $menu_id );
		if ( is_array( $existing ) ) {
			foreach ( $existing as $item ) {
				wp_delete_post( (int) $item->ID, true );
			}
		}
	}

	$position = 1;
	foreach ( (array) $items as $row ) {
		$title = isset( $row['title'] ) ? (string) $row['title'] : '';
		$url   = isset( $row['url'] ) ? (string) $row['url'] : '';
		if ( '' === $title || '' === $url ) {
			continue;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => $title,
				'menu-item-url'    => $url,
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
				'menu-item-position' => $position,
			)
		);
		++$position;
	}

	return (int) $menu_id;
}

/**
 * Assign theme menu locations when empty or force-refresh option set.
 */
function snf_ensure_theme_menus() {
	if ( wp_installing() ) {
		return;
	}

	$force   = (bool) get_option( 'snf_theme_menus_force_resync' );
	$version = '2.33.0';
	if ( ! $force && get_option( 'snf_theme_menus_version' ) === $version ) {
		return;
	}

	$blueprints = snf_get_theme_menu_blueprints();
	$names      = array(
		'primary' => 'SNF Primary',
		'account' => 'SNF Account',
		'footer'  => 'SNF Footer',
	);

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( ! is_array( $locations ) ) {
		$locations = array();
	}

	foreach ( $names as $location => $menu_name ) {
		$items   = isset( $blueprints[ $location ] ) ? $blueprints[ $location ] : array();
		$menu_id = snf_theme_menus_sync_menu( $menu_name, $items );
		if ( $menu_id > 0 ) {
			$locations[ $location ] = $menu_id;
		}
	}

	set_theme_mod( 'nav_menu_locations', $locations );
	update_option( 'snf_theme_menus_version', $version );
	delete_option( 'snf_theme_menus_force_resync' );
}
add_action( 'after_switch_theme', 'snf_ensure_theme_menus' );
add_action( 'init', 'snf_ensure_theme_menus', 30 );
