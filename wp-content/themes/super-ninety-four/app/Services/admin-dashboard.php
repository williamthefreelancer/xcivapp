<?php
/**
 * SNF Club wp-admin dashboards for Events, Circles, Videos & Audio, Marketplace RFQs,
 * Ticket Addons & Upgrades, and critical settings.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SNF_EVENT_SETTINGS_OPTION  = 'snf_event_settings';
const SNF_CIRCLE_SETTINGS_OPTION = 'snf_circle_settings';

/**
 * Default event club settings.
 *
 * @return array<string, mixed>
 */
function snf_event_default_settings() {
	return array(
		'default_attendance_mode' => 'hybrid',
		'default_live_provider'   => 'youtube_live',
		'archive_show_past'       => 1,
		'require_ticket_product'  => 0,
	);
}

/**
 * Merged event settings.
 *
 * @return array<string, mixed>
 */
function snf_get_event_settings() {
	$stored = get_option( SNF_EVENT_SETTINGS_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	return array_merge( snf_event_default_settings(), $stored );
}

/**
 * Default circle settings.
 *
 * @return array<string, mixed>
 */
function snf_circle_default_settings() {
	return array(
		'default_visibility' => 'members',
		'allow_self_join'    => 1,
		'hub_intro'          => '',
	);
}

/**
 * Merged circle settings.
 *
 * @return array<string, mixed>
 */
function snf_get_circle_settings() {
	$stored = get_option( SNF_CIRCLE_SETTINGS_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	return array_merge( snf_circle_default_settings(), $stored );
}

/**
 * Circle visibility choices.
 *
 * @return array<string, string>
 */
function snf_circle_visibility_choices() {
	return array(
		'members'    => __( 'Club members', 'super-ninety-four' ),
		'reciprocal' => __( 'Members + reciprocal', 'super-ninety-four' ),
		'staff'      => __( 'Staff only', 'super-ninety-four' ),
	);
}

/**
 * Admin pages registry.
 *
 * @return array<string, array<string, string>>
 */
function snf_admin_dashboard_pages() {
	return array(
		'snf-club'          => array(
			'title' => __( 'Overview', 'super-ninety-four' ),
			'label' => __( 'Overview', 'super-ninety-four' ),
		),
		'snf-club-events'   => array(
			'title' => __( 'Events', 'super-ninety-four' ),
			'label' => __( 'Events', 'super-ninety-four' ),
		),
		'snf-club-circles'  => array(
			'title' => __( 'Circles', 'super-ninety-four' ),
			'label' => __( 'Circles', 'super-ninety-four' ),
		),
		'snf-club-media'    => array(
			'title' => __( 'Videos & Audio', 'super-ninety-four' ),
			'label' => __( 'Videos & Audio', 'super-ninety-four' ),
		),
		'snf-club-subscribers' => array(
			'title' => __( 'Newsletter Subscribers', 'super-ninety-four' ),
			'label' => __( 'Newsletter Subscribers', 'super-ninety-four' ),
		),
		'snf-club-x-daily'  => array(
			'title' => __( 'X Daily Post', 'super-ninety-four' ),
			'label' => __( 'X Daily Post', 'super-ninety-four' ),
		),
		'snf-club-rfqs'     => array(
			'title' => __( 'Marketplace RFQs', 'super-ninety-four' ),
			'label' => __( 'Marketplace RFQs', 'super-ninety-four' ),
		),
		'snf-club-addons'   => array(
			'title' => __( 'Ticket Addons & Upgrades', 'super-ninety-four' ),
			'label' => __( 'Ticket Addons & Upgrades', 'super-ninety-four' ),
		),
		'snf-club-settings' => array(
			'title' => __( 'Critical settings', 'super-ninety-four' ),
			'label' => __( 'Critical settings', 'super-ninety-four' ),
		),
	);
}

/**
 * Whether the current screen is an SNF Club dashboard.
 *
 * @return bool
 */
function snf_admin_is_club_screen() {
	if ( ! is_admin() ) {
		return false;
	}
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( snf_admin_dashboard_pages()[ $page ] );
}

/**
 * Whether the current screen is the core wp-admin Dashboard (index.php).
 *
 * @return bool
 */
function snf_admin_is_core_dashboard() {
	if ( ! is_admin() ) {
		return false;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	return $screen && 'dashboard' === $screen->id;
}

/**
 * Whether the branded Club overview should replace the core Dashboard.
 *
 * @return bool
 */
function snf_admin_should_brand_core_dashboard() {
	return snf_admin_is_core_dashboard() && current_user_can( 'edit_posts' );
}

/**
 * Whether the modern Vue overview should mount (Dashboard Home + SNF Club Overview).
 *
 * @return bool
 */
function snf_admin_is_overview_screen() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return false;
	}
	if ( snf_admin_should_brand_core_dashboard() ) {
		return true;
	}
	global $pagenow;
	if ( is_admin() && isset( $pagenow ) && 'index.php' === $pagenow ) {
		return true;
	}
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return 'snf-club' === $page;
}

/**
 * Whether Critical settings should mount the Vue pulse strip.
 *
 * @return bool
 */
function snf_admin_is_settings_screen() {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return 'snf-club-settings' === $page && current_user_can( 'manage_options' );
}

/**
 * Collect flash notices from query args for Vue / PHP shells.
 *
 * @return array<int, array{type:string,message:string}>
 */
function snf_admin_collect_notices() {
	$notices = array();

	if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'success',
			'message' => __( 'Settings saved.', 'super-ninety-four' ),
		);
	}
	if ( isset( $_GET['flushed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'success',
			'message' => __( 'Permalinks flushed.', 'super-ninety-four' ),
		);
	}
	if ( isset( $_GET['styles_reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'success',
			'message' => __( 'Global Styles reset to theme.json defaults.', 'super-ninety-four' ),
		);
	}
	if ( isset( $_GET['seeded'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'success',
			'message' => __( 'Default ticket addon kinds / experiences checked and missing terms inserted.', 'super-ninety-four' ),
		);
	}
	if ( isset( $_GET['bootstrapped'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'success',
			'message' => __( 'Club bootstrap finished (roles, tables, circles, pages, menus).', 'super-ninety-four' ),
		);
	}
	if ( isset( $_GET['products_ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$kind = sanitize_key( wp_unslash( (string) $_GET['products_ok'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'success',
			'message' => ( 'reciprocal' === $kind )
				? __( 'Reciprocal program products checked; missing products created.', 'super-ninety-four' )
				: __( 'Membership catalog products checked; missing products created.', 'super-ninety-four' ),
		);
	}
	if ( isset( $_GET['stripe_catalog'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$catalog_flag = sanitize_key( wp_unslash( (string) $_GET['stripe_catalog'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'ok' === $catalog_flag ) {
			$notices[] = array(
				'type'    => 'success',
				'message' => __( 'Stripe membership catalog synced for the active mode; Price IDs saved on Woo products.', 'super-ninety-four' ),
			);
		} elseif ( 'fail' === $catalog_flag ) {
			$err_msg = __( 'Stripe membership catalog sync reported errors — check keys and Club Critical notices.', 'super-ninety-four' );
			$stored  = get_transient( 'snf_ops_stripe_catalog_errors_' . get_current_user_id() );
			if ( is_array( $stored ) && $stored ) {
				$err_msg .= ' ' . implode( ' ', array_map( 'strval', $stored ) );
				delete_transient( 'snf_ops_stripe_catalog_errors_' . get_current_user_id() );
			}
			$notices[] = array(
				'type'    => 'error',
				'message' => $err_msg,
			);
		}
	}
	if ( isset( $_GET['stripe_probe'] ) && 'ok' === $_GET['stripe_probe'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'success',
			'message' => __( 'Connected: Stripe API key accepted.', 'super-ninety-four' ),
		);
	} elseif ( isset( $_GET['stripe_probe'] ) && 'fail' === $_GET['stripe_probe'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'error',
			'message' => __( 'Stripe API probe failed — check the secret key for the selected mode.', 'super-ninety-four' ),
		);
	}
	if ( isset( $_GET['supabase_probe'] ) && 'ok' === $_GET['supabase_probe'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'success',
			'message' => __( 'Supabase catalog probe succeeded.', 'super-ninety-four' ),
		);
	} elseif ( isset( $_GET['supabase_probe'] ) && 'fail' === $_GET['supabase_probe'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'warn',
			'message' => __( 'Supabase catalog probe failed — check project URL and anon key.', 'super-ninety-four' ),
		);
	}
	if ( isset( $_GET['mapbox_probe'] ) && 'missing' === $_GET['mapbox_probe'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'warn',
			'message' => __( 'Mapbox token is not set — Expo lead-rider map will stay inactive.', 'super-ninety-four' ),
		);
	} elseif ( isset( $_GET['mapbox_probe'] ) && 'ok' === $_GET['mapbox_probe'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'success',
			'message' => __( 'Mapbox token is present.', 'super-ninety-four' ),
		);
	}
	if ( isset( $_GET['lookup'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$lookup = sanitize_key( wp_unslash( (string) $_GET['lookup'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'ok' === $lookup ) {
			$notices[] = array(
				'type'    => 'success',
				'message' => __( 'Subscription lookup completed — results below.', 'super-ninety-four' ),
			);
		} elseif ( 'empty' === $lookup ) {
			$notices[] = array(
				'type'    => 'warn',
				'message' => __( 'No Stripe subscriptions found for that email.', 'super-ninety-four' ),
			);
		} elseif ( 'fail' === $lookup ) {
			$err = get_transient( 'snf_admin_lookup_error_' . get_current_user_id() );
			$notices[] = array(
				'type'    => 'error',
				'message' => ( is_string( $err ) && $err ) ? $err : __( 'Subscription lookup failed.', 'super-ninety-four' ),
			);
		}
	}

	// X Daily Post uses a redirect-after-post flow so results render in the shared shell.
	if ( isset( $_GET['snf_x_posted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices[] = array(
			'type'    => 'success',
			'message' => __( 'Posted to X. The run was added to the history below.', 'super-ninety-four' ),
		);
	}
	if ( isset( $_GET['snf_x_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = 'snf_x_daily_post_error_' . get_current_user_id();
		$err = get_transient( $key );
		$notices[] = array(
			'type'    => 'error',
			'message' => ( is_string( $err ) && $err ) ? $err : __( 'The X post could not be published.', 'super-ninety-four' ),
		);
		// Leave the transient to expire — notices are collected more than once per request.
	}

	return $notices;
}

/**
 * Whether the built Vue admin bundle is available.
 *
 * @return bool
 */
function snf_admin_vue_bundle_ready() {
	return file_exists( get_template_directory() . '/dist/admin.js' );
}

/**
 * Current Club / Stripe admin page slug (empty when not a Club surface).
 *
 * @return string
 */
function snf_admin_current_page_slug() {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $page && ( isset( snf_admin_dashboard_pages()[ $page ] ) || 'snf-stripe' === $page ) ) {
		return $page;
	}
	if ( snf_admin_is_overview_screen() ) {
		return 'snf-club';
	}
	return '';
}

/**
 * Build Vue nav items from a pages map.
 *
 * @param array<string, array<string, string>> $pages Page registry.
 * @return array<int, array{slug:string,label:string,href:string}>
 */
function snf_admin_vue_nav_from_pages( $pages ) {
	$items = array();
	if ( ! is_array( $pages ) ) {
		return $items;
	}
	foreach ( $pages as $slug => $page ) {
		if ( 'snf-club-settings' === $slug && ! current_user_can( 'manage_options' ) ) {
			continue;
		}
		if ( 'snf-stripe' === $slug && ! current_user_can( 'manage_woocommerce' ) ) {
			continue;
		}
		if ( 'snf-club-x-daily' === $slug && ! current_user_can( 'manage_options' ) ) {
			continue;
		}
		$items[] = array(
			'slug'  => $slug,
			'label' => isset( $page['label'] ) ? (string) $page['label'] : (string) $slug,
			'href'  => ( 'snf-club' === $slug ) ? admin_url( 'index.php' ) : admin_url( 'admin.php?page=' . $slug ),
		);
	}
	return $items;
}

/**
 * Nav items for Vue shells.
 *
 * @param array<string, array<string, string>>|null $pages Optional page map (null = Club nav).
 * @return array<int, array{slug:string,label:string,href:string}>
 */
function snf_admin_vue_nav_items( $pages = null ) {
	return snf_admin_vue_nav_from_pages( null === $pages ? snf_admin_club_nav_pages() : $pages );
}

/**
 * Integration pulse rows for Vue (status: ok|warn|bad|outline).
 *
 * @param string $context overview|critical|stripe.
 * @return array<int, array{id:string,label:string,value:string,status:string}>
 */
function snf_admin_vue_pulse_rows( $context = 'overview' ) {
	$health = get_option( 'snf_api_health_cache', array() );
	if ( ! is_array( $health ) ) {
		$health = array();
	}

	$stripe_ready = function_exists( 'snf_stripe_is_ready' ) && snf_stripe_is_ready();
	$stripe_probe = isset( $health['stripe'] ) ? (string) $health['stripe'] : '';
	$sdk_ok       = function_exists( 'snf_stripe_sdk_available' ) && snf_stripe_sdk_available();
	$mapbox_ok    = function_exists( 'snf_get_mapbox_token' ) && (string) snf_get_mapbox_token();
	if ( ! $mapbox_ok && defined( 'SNF_MAPBOX_TOKEN' ) && SNF_MAPBOX_TOKEN ) {
		$mapbox_ok = true;
	}
	if ( isset( $health['mapbox'] ) ) {
		$mapbox_ok = ( 'ok' === $health['mapbox'] );
	}

	$supabase_probe = isset( $health['supabase'] ) ? (string) $health['supabase'] : '';
	$supabase_ready = function_exists( 'snf_supabase_payments_is_ready' ) && snf_supabase_payments_is_ready();
	$catalog_count  = isset( $health['catalog_count'] ) ? (int) $health['catalog_count'] : 0;

	$media = function_exists( 'snf_get_media_settings' ) ? snf_get_media_settings() : array();
	$live  = ! empty( $media['enable_live_audio_rooms'] );

	$stripe_status = 'warn';
	$stripe_label  = __( 'Needs keys', 'super-ninety-four' );
	if ( 'ok' === $stripe_probe || $stripe_ready ) {
		$stripe_status = 'ok';
		$stripe_label  = __( 'Ready', 'super-ninety-four' );
	} elseif ( 'fail' === $stripe_probe ) {
		$stripe_status = 'bad';
		$stripe_label  = __( 'Probe failed', 'super-ninety-four' );
	} elseif ( 'critical' === $context ) {
		$stripe_label = __( 'Check keys', 'super-ninety-four' );
	}

	$supabase_status = 'warn';
	$supabase_label  = __( 'Not probed', 'super-ninety-four' );
	if ( 'ok' === $supabase_probe ) {
		$supabase_status = 'ok';
		$supabase_label  = $catalog_count
			? sprintf(
				/* translators: %d: catalog price count */
				_n( '%d price', '%d prices', $catalog_count, 'super-ninety-four' ),
				$catalog_count
			)
			: __( 'Ready', 'super-ninety-four' );
	} elseif ( 'fail' === $supabase_probe ) {
		$supabase_status = 'bad';
		$supabase_label  = __( 'Probe failed', 'super-ninety-four' );
	} elseif ( $supabase_ready ) {
		$supabase_status = 'ok';
		$supabase_label  = __( 'Configured', 'super-ninety-four' );
	} elseif ( ! $supabase_ready ) {
		$supabase_label = __( 'Off / incomplete', 'super-ninety-four' );
	}

	$rows = array(
		array(
			'id'     => 'stripe',
			'label'  => __( 'Stripe', 'super-ninety-four' ),
			'value'  => $stripe_label,
			'status' => $stripe_status,
		),
		array(
			'id'     => 'sdk',
			'label'  => __( 'stripe/stripe-php SDK', 'super-ninety-four' ),
			'value'  => $sdk_ok ? __( 'Loaded', 'super-ninety-four' ) : __( 'Missing', 'super-ninety-four' ),
			'status' => $sdk_ok ? 'ok' : 'warn',
		),
		array(
			'id'     => 'supabase',
			'label'  => __( 'Supabase catalog', 'super-ninety-four' ),
			'value'  => $supabase_label,
			'status' => $supabase_status,
		),
		array(
			'id'     => 'mapbox',
			'label'  => __( 'Mapbox', 'super-ninety-four' ),
			'value'  => $mapbox_ok ? __( 'Token set', 'super-ninety-four' ) : __( 'Missing', 'super-ninety-four' ),
			'status' => $mapbox_ok ? 'ok' : 'warn',
		),
	);

	if ( 'overview' === $context ) {
		$rows[] = array(
			'id'     => 'live-audio',
			'label'  => __( 'Live audio rooms', 'super-ninety-four' ),
			'value'  => $live ? __( 'Enabled', 'super-ninety-four' ) : __( 'Off', 'super-ninety-four' ),
			'status' => $live ? 'ok' : 'warn',
		);
	}

	return $rows;
}

/**
 * Bootstrap payload for the Vue admin island.
 *
 * @param string $page overview|settings|chrome.
 * @return array<string, mixed>
 */
function snf_admin_vue_bootstrap( $page = 'overview' ) {
	$slug = snf_admin_current_page_slug();
	if ( ! $slug ) {
		$slug = ( 'settings' === $page ) ? 'snf-club-settings' : 'snf-club';
	}

	$payload = array(
		'page'    => $page,
		'brand'   => __( 'Super Ninety Four', 'super-ninety-four' ),
		'nav'     => snf_admin_vue_nav_items( 'snf-stripe' === $slug ? snf_admin_payments_nav_pages() : null ),
		'active'  => $slug,
		'notices' => snf_admin_collect_notices(),
	);

	if ( 'settings' === $page || 'snf-club-settings' === $slug ) {
		$payload['page']       = 'settings';
		$payload['title']      = __( 'Critical settings', 'super-ninety-four' );
		$payload['lede']       = __( 'Integrations, commerce seeds, and maintenance tools the Club and Stripe stacks rely on.', 'super-ninety-four' );
		$payload['active']     = 'snf-club-settings';
		$payload['pulse']      = snf_admin_vue_pulse_rows( 'critical' );
		$payload['pageHealth'] = snf_admin_page_health_rows();
		$payload['stripeHref'] = current_user_can( 'manage_woocommerce' )
			? admin_url( 'admin.php?page=snf-stripe' )
			: '';
		return $payload;
	}

	if ( 'chrome' === $page ) {
		return $payload;
	}

	$rfq_count = snf_admin_count_posts( 'snf_rfq', 'private' ) + snf_admin_count_posts( 'snf_rfq', 'publish' );

	$payload['title'] = __( 'Club operations', 'super-ninety-four' );
	$payload['lede']  = __( 'Dashboards for Events, Circles, Videos & Audio, Marketplace RFQs, Ticket Addons & Upgrades, plus the settings those surfaces depend on.', 'super-ninety-four' );
	$payload['active'] = 'snf-club';
	$payload['stats']  = array(
		array(
			'value' => (string) snf_admin_count_posts( 'event' ),
			'label' => __( 'Published events', 'super-ninety-four' ),
		),
		array(
			'value' => (string) snf_admin_count_posts( 'snf_circle' ),
			'label' => __( 'Circles', 'super-ninety-four' ),
		),
		array(
			'value' => (string) snf_admin_count_posts( 'snf_video' ),
			'label' => __( 'Videos', 'super-ninety-four' ),
		),
		array(
			'value' => (string) snf_admin_count_posts( 'snf_audio' ),
			'label' => __( 'Audio', 'super-ninety-four' ),
		),
		array(
			'value' => (string) ( snf_admin_count_posts( 'snf_reservation', 'publish' ) + snf_admin_count_posts( 'snf_reservation', 'pending' ) ),
			'label' => __( 'Reservations', 'super-ninety-four' ),
		),
		array(
			'value' => (string) $rfq_count,
			'label' => __( 'RFQs', 'super-ninety-four' ),
		),
	);
	$payload['desksTitle'] = __( 'Content desks', 'super-ninety-four' );
	$payload['desksLede']  = __( 'Jump into the CPT lists or their dedicated dashboards.', 'super-ninety-four' );
	$payload['desks']      = array(
		array(
			'id'             => 'events',
			'label'          => __( 'Events', 'super-ninety-four' ),
			'dashboardHref'  => admin_url( 'admin.php?page=snf-club-events' ),
			'dashboardLabel' => __( 'Events dashboard', 'super-ninety-four' ),
			'listHref'       => admin_url( 'edit.php?post_type=event' ),
			'listLabel'      => __( 'All events', 'super-ninety-four' ),
		),
		array(
			'id'             => 'circles',
			'label'          => __( 'Circles', 'super-ninety-four' ),
			'dashboardHref'  => admin_url( 'admin.php?page=snf-club-circles' ),
			'dashboardLabel' => __( 'Circles dashboard', 'super-ninety-four' ),
			'listHref'       => admin_url( 'edit.php?post_type=snf_circle' ),
			'listLabel'      => __( 'All circles', 'super-ninety-four' ),
		),
		array(
			'id'             => 'media',
			'label'          => __( 'Videos & Audio', 'super-ninety-four' ),
			'dashboardHref'  => admin_url( 'admin.php?page=snf-club-media' ),
			'dashboardLabel' => __( 'Media dashboard', 'super-ninety-four' ),
			'listHref'       => admin_url( 'edit.php?post_type=snf_video' ),
			'listLabel'      => __( 'All videos', 'super-ninety-four' ),
		),
		array(
			'id'             => 'subscribers',
			'label'          => __( 'Newsletter', 'super-ninety-four' ),
			'dashboardHref'  => admin_url( 'admin.php?page=snf-club-subscribers' ),
			'dashboardLabel' => __( 'Subscribers dashboard', 'super-ninety-four' ),
			'listHref'       => admin_url( 'edit.php?post_type=snf_subscriber&snf_classic=1' ),
			'listLabel'      => __( 'Classic list', 'super-ninety-four' ),
		),
		array(
			'id'             => 'x-daily',
			'label'          => __( 'X Daily Post', 'super-ninety-four' ),
			'dashboardHref'  => admin_url( 'admin.php?page=snf-club-x-daily' ),
			'dashboardLabel' => __( 'X Daily Post desk', 'super-ninety-four' ),
			'listHref'       => '',
			'listLabel'      => '',
		),
		array(
			'id'             => 'rfqs',
			'label'          => __( 'Marketplace RFQs', 'super-ninety-four' ),
			'dashboardHref'  => admin_url( 'admin.php?page=snf-club-rfqs' ),
			'dashboardLabel' => __( 'RFQ dashboard', 'super-ninety-four' ),
			'listHref'       => admin_url( 'edit.php?post_type=snf_rfq' ),
			'listLabel'      => __( 'All RFQs', 'super-ninety-four' ),
		),
		array(
			'id'             => 'addons',
			'label'          => __( 'Ticket Addons & Upgrades', 'super-ninety-four' ),
			'dashboardHref'  => admin_url( 'admin.php?page=snf-club-addons' ),
			'dashboardLabel' => __( 'Addons & upgrades dashboard', 'super-ninety-four' ),
			'listHref'       => admin_url( 'edit-tags.php?taxonomy=snf_ticket_addon_kind&post_type=product' ),
			'listLabel'      => __( 'All addon kinds', 'super-ninety-four' ),
		),
	);
	$payload['pulseTitle'] = __( 'Integrations pulse', 'super-ninety-four' );
	$payload['pulseLede']  = __( 'Payments, maps, and media defaults that unlock front-end features.', 'super-ninety-four' );
	$payload['pulse']      = snf_admin_vue_pulse_rows( 'overview' );
	$payload['settingsHref'] = current_user_can( 'manage_options' )
		? admin_url( 'admin.php?page=snf-club-settings' )
		: '';
	$payload['stripeHref'] = current_user_can( 'manage_woocommerce' )
		? admin_url( 'admin.php?page=snf-stripe' )
		: '';

	return $payload;
}

/**
 * Enqueue admin dashboard assets.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function snf_admin_dashboard_enqueue( $hook_suffix ) {
	$hook = (string) $hook_suffix;
	$load = snf_admin_is_club_screen()
		|| 'index.php' === $hook
		|| false !== strpos( $hook, 'snf-club' )
		|| false !== strpos( $hook, 'snf-media-settings' )
		|| false !== strpos( $hook, 'snf-stripe' );

	if ( ! $load ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$types  = array( 'event', 'snf_circle', 'snf_video', 'snf_audio', 'snf_reservation', 'snf_rfq', 'snf_subscriber' );
		$tax    = $screen && ! empty( $screen->taxonomy ) ? (string) $screen->taxonomy : '';
		if ( ! $screen || ( ! in_array( $screen->post_type, $types, true ) && 'snf_ticket_addon_kind' !== $tax ) ) {
			return;
		}
	}

	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();
	$css       = $theme_dir . '/assets/css/admin-dashboard.css';
	wp_enqueue_style(
		'snf-admin-dashboard',
		$theme_uri . '/assets/css/admin-dashboard.css',
		array(),
		file_exists( $css ) ? (string) filemtime( $css ) : ( defined( 'SNF_VERSION' ) ? SNF_VERSION : '1.0' )
	);

	if ( ! snf_admin_vue_bundle_ready() ) {
		return;
	}

	$vue_page = '';
	if ( snf_admin_is_overview_screen() ) {
		$vue_page = 'overview';
	} elseif ( snf_admin_is_settings_screen() ) {
		$vue_page = 'settings';
	} elseif ( snf_admin_is_club_screen() || ( isset( $_GET['page'] ) && 'snf-stripe' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$vue_page = 'chrome';
	}

	if ( ! $vue_page ) {
		return;
	}

	$admin_css = $theme_dir . '/dist/admin.css';
	$admin_js  = $theme_dir . '/dist/admin.js';
	$deps      = array( 'snf-admin-dashboard' );

	if ( file_exists( $admin_css ) ) {
		wp_enqueue_style(
			'snf-admin-vue',
			$theme_uri . '/dist/admin.css',
			$deps,
			(string) filemtime( $admin_css )
		);
		$deps[] = 'snf-admin-vue';
	}

	wp_enqueue_script(
		'snf-admin-vue',
		$theme_uri . '/dist/admin.js',
		array(),
		(string) filemtime( $admin_js ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_localize_script(
		'snf-admin-vue',
		'snfAdminDashboard',
		snf_admin_vue_bootstrap( $vue_page )
	);
}
add_action( 'admin_enqueue_scripts', 'snf_admin_dashboard_enqueue' );

/**
 * Mark the core Dashboard body when the branded Club UI is active.
 *
 * @param string $classes Space-separated admin body classes.
 * @return string
 */
function snf_admin_core_dashboard_body_class( $classes ) {
	if ( snf_admin_should_brand_core_dashboard() ) {
		$classes .= ' snf-admin-home';
	}
	return $classes;
}
add_filter( 'admin_body_class', 'snf_admin_core_dashboard_body_class' );

/**
 * Strip default Dashboard widgets / welcome panel so the branded UI can own the screen.
 */
function snf_admin_replace_core_dashboard() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	remove_action( 'welcome_panel', 'wp_welcome_panel' );

	global $wp_meta_boxes;
	if ( isset( $wp_meta_boxes['dashboard'] ) ) {
		$wp_meta_boxes['dashboard'] = array();
	}
}
add_action( 'wp_dashboard_setup', 'snf_admin_replace_core_dashboard', 999 );

/**
 * Render the branded Club overview on wp-admin/index.php.
 */
function snf_admin_render_core_dashboard() {
	if ( ! snf_admin_should_brand_core_dashboard() ) {
		return;
	}

	snf_admin_render_overview();
}
add_action( 'all_admin_notices', 'snf_admin_render_core_dashboard', 1 );

/**
 * Replace stock Dashboard help with Club overview context.
 */
function snf_admin_core_dashboard_help() {
	if ( ! snf_admin_should_brand_core_dashboard() ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}

	$screen->remove_help_tabs();
	$screen->add_help_tab(
		array(
			'id'      => 'snf-club-overview',
			'title'   => __( 'Club overview', 'super-ninety-four' ),
			'content' => '<p>' . esc_html__( 'This Dashboard is the Super Ninety Four Club operations overview — the same home as SNF Club → Overview.', 'super-ninety-four' ) . '</p>',
		)
	);
	$screen->set_help_sidebar(
		'<p><strong>' . esc_html__( 'Jump to:', 'super-ninety-four' ) . '</strong></p>' .
		'<p><a href="' . esc_url( admin_url( 'admin.php?page=snf-club-events' ) ) . '">' . esc_html__( 'Events', 'super-ninety-four' ) . '</a></p>' .
		'<p><a href="' . esc_url( admin_url( 'admin.php?page=snf-club-settings' ) ) . '">' . esc_html__( 'Critical settings', 'super-ninety-four' ) . '</a></p>'
	);
}
add_action( 'admin_head-index.php', 'snf_admin_core_dashboard_help' );

/**
 * Register top-level SNF Club menu + CPT dashboard shortcuts.
 */
function snf_admin_dashboard_menu() {
	add_menu_page(
		__( 'SNF Club', 'super-ninety-four' ),
		__( 'SNF Club', 'super-ninety-four' ),
		'edit_posts',
		'snf-club',
		'snf_admin_render_overview',
		'dashicons-shield-alt',
		3
	);

	// Parent slug already creates the Overview entry; add the rest as children.
	$pages = snf_admin_dashboard_pages();
	foreach ( $pages as $slug => $page ) {
		if ( 'snf-club' === $slug ) {
			continue;
		}

		$callback = 'snf_admin_render_overview';
		if ( 'snf-club-events' === $slug ) {
			$callback = 'snf_admin_render_events';
		} elseif ( 'snf-club-circles' === $slug ) {
			$callback = 'snf_admin_render_circles';
		} elseif ( 'snf-club-media' === $slug ) {
			$callback = 'snf_admin_render_media';
		} elseif ( 'snf-club-rfqs' === $slug ) {
			$callback = 'snf_admin_render_rfqs';
		} elseif ( 'snf-club-addons' === $slug ) {
			$callback = 'snf_admin_render_addon_kinds';
		} elseif ( 'snf-club-subscribers' === $slug ) {
			$callback = 'snf_admin_render_subscribers';
		} elseif ( 'snf-club-x-daily' === $slug ) {
			$callback = 'snf_x_daily_admin_page';
		} elseif ( 'snf-club-settings' === $slug ) {
			$callback = 'snf_admin_render_critical_settings';
		}

		$cap = in_array( $slug, array( 'snf-club-settings', 'snf-club-x-daily' ), true ) ? 'manage_options' : 'edit_posts';

		add_submenu_page(
			'snf-club',
			$page['title'],
			$page['label'],
			$cap,
			$slug,
			$callback
		);
	}

	// Rename the auto submenu that mirrors the top-level item.
	add_action(
		'admin_menu',
		static function () {
			global $submenu;
			if ( isset( $submenu['snf-club'][0][0] ) ) {
				$submenu['snf-club'][0][0] = __( 'Overview', 'super-ninety-four' );
			}
		},
		999
	);
}
add_action( 'admin_menu', 'snf_admin_dashboard_menu', 9 );

/**
 * Count posts by type/status.
 *
 * @param string $post_type Post type.
 * @param string $status    Status.
 * @return int
 */
function snf_admin_count_posts( $post_type, $status = 'publish' ) {
	$counts = wp_count_posts( $post_type );
	if ( ! $counts || ! isset( $counts->{$status} ) ) {
		return 0;
	}
	return (int) $counts->{$status};
}

/**
 * Taxonomy term count helper.
 *
 * @param string $taxonomy Taxonomy.
 * @return int
 */
function snf_admin_count_terms( $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return 0;
	}
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'fields'     => 'count',
		)
	);
	return is_wp_error( $terms ) ? 0 : (int) $terms;
}

/**
 * Page health rows for critical surfaces.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_admin_page_health_rows() {
	$slugs = array(
		'videos-audio'                 => __( 'Videos & Audio hub', 'super-ninety-four' ),
		'community'                    => __( 'Community', 'super-ninety-four' ),
		'messages'                     => __( 'Messages', 'super-ninety-four' ),
		'expo'                         => __( 'Expo marketplace', 'super-ninety-four' ),
		'select-events-by-members'     => __( 'Select Events (members)', 'super-ninety-four' ),
		'select-events-by-non-members' => __( 'Select Events (public)', 'super-ninety-four' ),
		'apply'                        => __( 'Apply', 'super-ninety-four' ),
		'member-login'                 => __( 'Member login', 'super-ninety-four' ),
	);

	$rows = array();
	foreach ( $slugs as $slug => $label ) {
		$page = get_page_by_path( $slug );
		$url  = $page ? get_permalink( $page ) : '';
		$rows[] = array(
			'label'  => $label,
			'slug'   => $slug,
			'ok'     => (bool) $page,
			'edit'   => $page ? get_edit_post_link( $page->ID, 'raw' ) : '',
			'view'   => $url,
		);
	}
	return $rows;
}

/**
 * Nav pages for Stripe / payments shell (Club desks + Stripe).
 *
 * @return array<string, array<string, string>>
 */
function snf_admin_payments_nav_pages() {
	$pages = array(
		'snf-club'          => array(
			'title' => __( 'Overview', 'super-ninety-four' ),
			'label' => __( 'Overview', 'super-ninety-four' ),
		),
		'snf-club-settings' => array(
			'title' => __( 'Critical settings', 'super-ninety-four' ),
			'label' => __( 'Critical settings', 'super-ninety-four' ),
		),
		'snf-stripe'        => array(
			'title' => __( 'Stripe', 'super-ninety-four' ),
			'label' => __( 'Stripe', 'super-ninety-four' ),
		),
	);

	if ( ! current_user_can( 'manage_options' ) ) {
		unset( $pages['snf-club-settings'] );
	}
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		unset( $pages['snf-stripe'] );
	}

	return $pages;
}

/**
 * Club section nav including Stripe for Woo managers.
 *
 * @return array<string, array<string, string>>
 */
function snf_admin_club_nav_pages() {
	$pages = snf_admin_dashboard_pages();
	if ( current_user_can( 'manage_woocommerce' ) ) {
		$pages['snf-stripe'] = array(
			'title' => __( 'Stripe', 'super-ninety-four' ),
			'label' => __( 'Stripe', 'super-ninety-four' ),
		);
	}
	return $pages;
}

/**
 * Shared dashboard chrome (Vue AdminShell when dist/admin.js exists).
 *
 * @param string                                    $active    Active page slug.
 * @param string                                    $title     Page title.
 * @param string                                    $lede      Supporting sentence.
 * @param array<string, array<string, string>>|null $nav_pages Nav map (null = Club nav; empty array = hero only).
 */
function snf_admin_render_shell_start( $active, $title, $lede, $nav_pages = null ) {
	$pages = null === $nav_pages ? snf_admin_club_nav_pages() : $nav_pages;

	if ( snf_admin_vue_bundle_ready() ) {
		$chrome = array(
			'brand'   => __( 'Super Ninety Four', 'super-ninety-four' ),
			'title'   => $title,
			'lede'    => $lede,
			'active'  => $active,
			'nav'     => snf_admin_vue_nav_from_pages( is_array( $pages ) ? $pages : array() ),
			'notices' => snf_admin_collect_notices(),
		);
		?>
		<div class="wrap snf-admin snf-admin--vue">
			<script type="application/json" id="snf-admin-chrome-data"><?php echo wp_json_encode( $chrome, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ); ?></script>
			<div id="snf-admin-chrome"></div>
			<noscript>
				<header class="snf-admin__hero">
					<p class="snf-admin__eyebrow"><?php esc_html_e( 'Super Ninety Four', 'super-ninety-four' ); ?></p>
					<h1><?php echo esc_html( $title ); ?></h1>
					<p class="snf-admin__lede"><?php echo esc_html( $lede ); ?></p>
				</header>
			</noscript>
		<?php
		return;
	}
	?>
	<div class="wrap snf-admin">
		<header class="snf-admin__hero">
			<p class="snf-admin__eyebrow"><?php esc_html_e( 'Super Ninety Four', 'super-ninety-four' ); ?></p>
			<h1><?php echo esc_html( $title ); ?></h1>
			<p class="snf-admin__lede"><?php echo esc_html( $lede ); ?></p>
		</header>
		<?php if ( is_array( $pages ) && $pages ) : ?>
		<nav class="snf-admin__nav" aria-label="<?php esc_attr_e( 'SNF Club sections', 'super-ninety-four' ); ?>">
			<?php foreach ( $pages as $slug => $page ) : ?>
				<?php
				if ( 'snf-club-settings' === $slug && ! current_user_can( 'manage_options' ) ) {
					continue;
				}
				if ( 'snf-stripe' === $slug && ! current_user_can( 'manage_woocommerce' ) ) {
					continue;
				}
				if ( 'snf-club-x-daily' === $slug && ! current_user_can( 'manage_options' ) ) {
					continue;
				}
				$href = ( 'snf-club' === $slug )
					? admin_url( 'index.php' )
					: admin_url( 'admin.php?page=' . $slug );
				?>
				<a class="<?php echo $active === $slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( $href ); ?>">
					<?php echo esc_html( $page['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php endif; ?>
		<?php foreach ( snf_admin_collect_notices() as $notice ) : ?>
			<?php
			$type = isset( $notice['type'] ) ? (string) $notice['type'] : 'success';
			$mod  = 'error' === $type ? 'error' : ( 'warn' === $type ? 'warn' : 'success' );
			?>
			<div class="snf-admin__notice snf-admin__notice--<?php echo esc_attr( $mod ); ?>">
				<?php echo esc_html( (string) $notice['message'] ); ?>
			</div>
		<?php endforeach; ?>
	<?php
}

/**
 * Integration health rows for Overview / Critical / Stripe.
 *
 * @param string $context overview|critical|stripe.
 * @return void
 */
function snf_admin_render_integrations_pulse( $context = 'overview' ) {
	$health  = get_option( 'snf_api_health_cache', array() );
	if ( ! is_array( $health ) ) {
		$health = array();
	}

	$stripe_ready = function_exists( 'snf_stripe_is_ready' ) && snf_stripe_is_ready();
	$stripe_probe = isset( $health['stripe'] ) ? (string) $health['stripe'] : '';
	$sdk_ok       = function_exists( 'snf_stripe_sdk_available' ) && snf_stripe_sdk_available();
	$mapbox_ok    = function_exists( 'snf_get_mapbox_token' ) && (string) snf_get_mapbox_token();
	if ( ! $mapbox_ok && defined( 'SNF_MAPBOX_TOKEN' ) && SNF_MAPBOX_TOKEN ) {
		$mapbox_ok = true;
	}
	if ( isset( $health['mapbox'] ) ) {
		$mapbox_ok = ( 'ok' === $health['mapbox'] );
	}

	$supabase_probe = isset( $health['supabase'] ) ? (string) $health['supabase'] : '';
	$supabase_ready = function_exists( 'snf_supabase_payments_is_ready' ) && snf_supabase_payments_is_ready();
	$catalog_count  = isset( $health['catalog_count'] ) ? (int) $health['catalog_count'] : 0;

	$media = function_exists( 'snf_get_media_settings' ) ? snf_get_media_settings() : array();
	$live  = ! empty( $media['enable_live_audio_rooms'] );

	$stripe_pill = 'snf-admin__pill--warn';
	$stripe_label = __( 'Needs keys', 'super-ninety-four' );
	if ( 'ok' === $stripe_probe || $stripe_ready ) {
		$stripe_pill  = 'snf-admin__pill--ok';
		$stripe_label = __( 'Ready', 'super-ninety-four' );
	} elseif ( 'fail' === $stripe_probe ) {
		$stripe_pill  = 'snf-admin__pill--bad';
		$stripe_label = __( 'Probe failed', 'super-ninety-four' );
	} elseif ( 'critical' === $context ) {
		$stripe_label = __( 'Check keys', 'super-ninety-four' );
	}

	$supabase_pill  = 'snf-admin__pill--warn';
	$supabase_label = __( 'Not probed', 'super-ninety-four' );
	if ( 'ok' === $supabase_probe ) {
		$supabase_pill  = 'snf-admin__pill--ok';
		$supabase_label = $catalog_count
			? sprintf(
				/* translators: %d: catalog price count */
				_n( '%d price', '%d prices', $catalog_count, 'super-ninety-four' ),
				$catalog_count
			)
			: __( 'Ready', 'super-ninety-four' );
	} elseif ( 'fail' === $supabase_probe ) {
		$supabase_pill  = 'snf-admin__pill--bad';
		$supabase_label = __( 'Probe failed', 'super-ninety-four' );
	} elseif ( $supabase_ready ) {
		$supabase_pill  = 'snf-admin__pill--ok';
		$supabase_label = __( 'Configured', 'super-ninety-four' );
	} elseif ( ! $supabase_ready ) {
		$supabase_label = __( 'Off / incomplete', 'super-ninety-four' );
	}
	?>
	<ul class="snf-admin__list">
		<li>
			<span><?php esc_html_e( 'Stripe', 'super-ninety-four' ); ?></span>
			<span class="snf-admin__pill <?php echo esc_attr( $stripe_pill ); ?>"><?php echo esc_html( $stripe_label ); ?></span>
		</li>
		<li>
			<span><?php esc_html_e( 'stripe/stripe-php SDK', 'super-ninety-four' ); ?></span>
			<span class="snf-admin__pill <?php echo $sdk_ok ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
				<?php echo $sdk_ok ? esc_html__( 'Loaded', 'super-ninety-four' ) : esc_html__( 'Missing', 'super-ninety-four' ); ?>
			</span>
		</li>
		<li>
			<span><?php esc_html_e( 'Supabase catalog', 'super-ninety-four' ); ?></span>
			<span class="snf-admin__pill <?php echo esc_attr( $supabase_pill ); ?>"><?php echo esc_html( $supabase_label ); ?></span>
		</li>
		<li>
			<span><?php esc_html_e( 'Mapbox', 'super-ninety-four' ); ?></span>
			<span class="snf-admin__pill <?php echo $mapbox_ok ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
				<?php echo $mapbox_ok ? esc_html__( 'Token set', 'super-ninety-four' ) : esc_html__( 'Missing', 'super-ninety-four' ); ?>
			</span>
		</li>
		<?php if ( 'overview' === $context ) : ?>
		<li>
			<span><?php esc_html_e( 'Live audio rooms', 'super-ninety-four' ); ?></span>
			<span class="snf-admin__pill <?php echo $live ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
				<?php echo $live ? esc_html__( 'Enabled', 'super-ninety-four' ) : esc_html__( 'Off', 'super-ninety-four' ); ?>
			</span>
		</li>
		<?php endif; ?>
	</ul>
	<?php
}

/**
 * Roles counted as club members (staff are shown separately in the directory).
 *
 * @return string[]
 */
function snf_admin_member_roles() {
	return (array) apply_filters( 'snf_admin_member_roles', array( 'snf_member', 'snf_reciprocal', 'snf_applicant' ) );
}

/**
 * Human label for a club role slug.
 *
 * @param string $role Role slug.
 * @return string
 */
function snf_admin_role_label( $role ) {
	$roles = function_exists( 'snf_get_club_roles' ) ? snf_get_club_roles() : array();
	if ( isset( $roles[ $role ]['name'] ) ) {
		return (string) $roles[ $role ]['name'];
	}
	return ucwords( str_replace( array( 'snf_', '_' ), array( '', ' ' ), (string) $role ) );
}

/**
 * Upcoming events from the event CPT and dated event products, soonest first.
 *
 * @param int $limit Maximum rows.
 * @return array<int, array<string,mixed>>
 */
function snf_admin_upcoming_events( $limit = 6 ) {
	$posts = get_posts(
		array(
			'post_type'      => array( 'event', 'product' ),
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array( 'key' => 'snf_event_starts_at', 'compare' => 'EXISTS' ),
				array( 'key' => '_snf_starts_at', 'compare' => 'EXISTS' ),
			),
		)
	);

	$now  = current_time( 'timestamp' );
	$rows = array();
	foreach ( $posts as $post ) {
		$start = (string) get_post_meta( $post->ID, 'snf_event_starts_at', true );
		if ( ! $start ) {
			$start = (string) get_post_meta( $post->ID, '_snf_starts_at', true );
		}
		$dt = $start ? date_create( $start, wp_timezone() ) : false;
		if ( ! $dt ) {
			continue;
		}
		$timestamp = (int) $dt->format( 'U' );
		if ( $timestamp < $now ) {
			continue;
		}
		$location = (string) get_post_meta( $post->ID, 'snf_event_location', true );
		if ( ! $location ) {
			$location = (string) get_post_meta( $post->ID, '_snf_location', true );
		}
		$rows[] = array(
			'post'      => $post,
			'timestamp' => $timestamp,
			'location'  => $location,
		);
	}

	usort(
		$rows,
		static function ( $a, $b ) {
			return $a['timestamp'] <=> $b['timestamp'];
		}
	);

	return array_slice( $rows, 0, max( 1, (int) $limit ) );
}

/**
 * Shared Club panels shown at the foot of every SNF admin dashboard:
 * upcoming events, new members, and the members directory.
 */
function snf_admin_render_shared_panels() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$member_roles = snf_admin_member_roles();
	$upcoming     = snf_admin_upcoming_events( 6 );
	$new_members  = get_users(
		array(
			'role__in' => $member_roles,
			'orderby'  => 'registered',
			'order'    => 'DESC',
			'number'   => 6,
		)
	);
	$new_count    = count(
		get_users(
			array(
				'role__in'   => $member_roles,
				'fields'     => 'ID',
				'date_query' => array(
					array(
						'after'     => '30 days ago',
						'inclusive' => true,
					),
				),
			)
		)
	);

	$user_counts = count_users();
	$counts      = isset( $user_counts['avail_roles'] ) ? (array) $user_counts['avail_roles'] : array();
	$directory   = get_users(
		array(
			'role__in' => $member_roles,
			'orderby'  => 'registered',
			'order'    => 'DESC',
			'number'   => 10,
		)
	);
	$member_total = 0;
	foreach ( $member_roles as $role ) {
		$member_total += isset( $counts[ $role ] ) ? (int) $counts[ $role ] : 0;
	}
	?>
	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Upcoming events schedule', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Next dated Select Events and event products across the club calendar.', 'super-ninety-four' ); ?></p>
			<table class="snf-admin__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'When', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Event', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Location', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'In', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $upcoming ) : ?>
						<tr><td colspan="4" class="snf-admin__muted"><?php esc_html_e( 'No upcoming dated events yet.', 'super-ninety-four' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $upcoming as $row ) : ?>
						<?php
						$event      = $row['post'];
						$now_stamp  = current_time( 'timestamp' );
						$days_away  = (int) round( ( $row['timestamp'] - $now_stamp ) / DAY_IN_SECONDS );
						$relative   = $days_away <= 0
							? __( 'today', 'super-ninety-four' )
							: sprintf(
								/* translators: %s: human-readable time difference */
								__( 'in %s', 'super-ninety-four' ),
								human_time_diff( $now_stamp, $row['timestamp'] )
							);
						?>
						<tr>
							<td>
								<?php echo esc_html( wp_date( 'M j, Y', $row['timestamp'] ) ); ?>
								<br><span class="snf-admin__muted"><?php echo esc_html( wp_date( 'g:i a', $row['timestamp'] ) ); ?></span>
							</td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $event->ID ) ); ?>"><?php echo esc_html( get_the_title( $event ) ); ?></a>
								<br><span class="snf-admin__pill"><?php echo esc_html( 'product' === $event->post_type ? __( 'Product', 'super-ninety-four' ) : __( 'Event', 'super-ninety-four' ) ); ?></span>
							</td>
							<td><?php echo $row['location'] ? esc_html( $row['location'] ) : '—'; ?></td>
							<td><span class="snf-admin__pill"><?php echo esc_html( $relative ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=event' ) ); ?>"><?php esc_html_e( 'All events', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>"><?php esc_html_e( 'Event products', 'super-ninety-four' ); ?></a>
			</div>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'New Members', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of new members in the last 30 days */
						_n( '%d new member in the last 30 days.', '%d new members in the last 30 days.', $new_count, 'super-ninety-four' ),
						$new_count
					)
				);
				?>
			</p>
			<table class="snf-admin__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Member', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Role', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Joined', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $new_members ) : ?>
						<tr><td colspan="3" class="snf-admin__muted"><?php esc_html_e( 'No club members have joined yet.', 'super-ninety-four' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $new_members as $member ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $member->ID ) ); ?>"><?php echo esc_html( $member->display_name ); ?></a>
								<br><span class="snf-admin__muted"><?php echo esc_html( $member->user_email ); ?></span>
							</td>
							<td><span class="snf-admin__pill"><?php echo esc_html( snf_admin_role_label( isset( $member->roles[0] ) ? $member->roles[0] : '' ) ); ?></span></td>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $member->user_registered ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'users.php?role=snf_member' ) ); ?>"><?php esc_html_e( 'All club members', 'super-ninety-four' ); ?></a>
			</div>
		</section>

		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Members Directory', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Role breakdown and the newest member records across the club.', 'super-ninety-four' ); ?></p>
			<ul class="snf-admin__list">
				<?php foreach ( $member_roles as $role ) : ?>
					<li>
						<span><?php echo esc_html( snf_admin_role_label( $role ) ); ?></span>
						<a class="snf-admin__pill" href="<?php echo esc_url( admin_url( 'users.php?role=' . rawurlencode( $role ) ) ); ?>"><?php echo esc_html( (string) ( isset( $counts[ $role ] ) ? (int) $counts[ $role ] : 0 ) ); ?></a>
					</li>
				<?php endforeach; ?>
				<li>
					<span><?php echo esc_html( snf_admin_role_label( 'snf_staff' ) ); ?></span>
					<a class="snf-admin__pill" href="<?php echo esc_url( admin_url( 'users.php?role=snf_staff' ) ); ?>"><?php echo esc_html( (string) ( isset( $counts['snf_staff'] ) ? (int) $counts['snf_staff'] : 0 ) ); ?></a>
				</li>
				<li>
					<span><?php esc_html_e( 'Total club members', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill snf-admin__pill--ok"><?php echo esc_html( (string) $member_total ); ?></span>
				</li>
			</ul>
			<table class="snf-admin__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Member', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Role', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Joined', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Edit', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $directory ) : ?>
						<tr><td colspan="4" class="snf-admin__muted"><?php esc_html_e( 'No member records yet.', 'super-ninety-four' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $directory as $member ) : ?>
						<tr>
							<td>
								<?php echo esc_html( $member->display_name ); ?>
								<br><span class="snf-admin__muted"><?php echo esc_html( $member->user_email ); ?></span>
							</td>
							<td><span class="snf-admin__pill"><?php echo esc_html( snf_admin_role_label( isset( $member->roles[0] ) ? $member->roles[0] : '' ) ); ?></span></td>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $member->user_registered ) ) ); ?></td>
							<td><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $member->ID ) ); ?>"><?php esc_html_e( 'Open', 'super-ninety-four' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>"><?php esc_html_e( 'All users', 'super-ninety-four' ); ?></a>
				<?php
				$community = get_page_by_path( 'community' );
				if ( $community ) :
					?>
					<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( get_permalink( $community ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Community page', 'super-ninety-four' ); ?></a>
				<?php endif; ?>
			</div>
		</section>
	</div>
	<?php
}

/**
 * Close dashboard shell.
 */
function snf_admin_render_shell_end() {
	snf_admin_render_shared_panels();
	echo '</div>';
}

/**
 * Overview dashboard (Vue + shadcn island when dist/admin.js is built).
 */
function snf_admin_render_overview() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	if ( snf_admin_vue_bundle_ready() ) {
		?>
		<div class="wrap snf-admin snf-admin--vue">
			<div id="snf-admin-overview"></div>
			<noscript>
				<p><?php esc_html_e( 'Enable JavaScript to use the Club operations dashboard.', 'super-ninety-four' ); ?></p>
			</noscript>
			<?php snf_admin_render_shared_panels(); ?>
		</div>
		<?php
		return;
	}

	// Fallback when Vite admin bundle is missing.
	snf_admin_render_shell_start(
		'snf-club',
		__( 'Club operations', 'super-ninety-four' ),
		__( 'Dashboards for Events, Circles, Videos & Audio, Marketplace RFQs, Ticket Addons & Upgrades, plus the settings those surfaces depend on.', 'super-ninety-four' )
	);

	$rfq_count = snf_admin_count_posts( 'snf_rfq', 'private' ) + snf_admin_count_posts( 'snf_rfq', 'publish' );
	$stats     = array(
		array( snf_admin_count_posts( 'event' ), __( 'Published events', 'super-ninety-four' ) ),
		array( snf_admin_count_posts( 'snf_circle' ), __( 'Circles', 'super-ninety-four' ) ),
		array( snf_admin_count_posts( 'snf_video' ), __( 'Videos', 'super-ninety-four' ) ),
		array( snf_admin_count_posts( 'snf_audio' ), __( 'Audio', 'super-ninety-four' ) ),
		array( snf_admin_count_posts( 'snf_reservation', 'publish' ) + snf_admin_count_posts( 'snf_reservation', 'pending' ), __( 'Reservations', 'super-ninety-four' ) ),
		array( $rfq_count, __( 'RFQs', 'super-ninety-four' ) ),
	);
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<?php foreach ( $stats as $stat ) : ?>
			<div class="snf-admin__stat">
				<span class="snf-admin__stat-value"><?php echo esc_html( (string) $stat[0] ); ?></span>
				<span class="snf-admin__stat-label"><?php echo esc_html( $stat[1] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Content desks', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Jump into the CPT lists or their dedicated dashboards.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-events' ) ); ?>"><?php esc_html_e( 'Events dashboard', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=event' ) ); ?>"><?php esc_html_e( 'All events', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-circles' ) ); ?>"><?php esc_html_e( 'Circles dashboard', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_circle' ) ); ?>"><?php esc_html_e( 'All circles', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-media' ) ); ?>"><?php esc_html_e( 'Media dashboard', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_video' ) ); ?>"><?php esc_html_e( 'All videos', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-subscribers' ) ); ?>"><?php esc_html_e( 'Subscribers dashboard', 'super-ninety-four' ); ?></a>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-x-daily' ) ); ?>"><?php esc_html_e( 'X Daily Post desk', 'super-ninety-four' ); ?></a>
				<?php endif; ?>
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-rfqs' ) ); ?>"><?php esc_html_e( 'RFQ dashboard', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_rfq' ) ); ?>"><?php esc_html_e( 'All RFQs', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-addons' ) ); ?>"><?php esc_html_e( 'Addons & upgrades dashboard', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=snf_ticket_addon_kind&post_type=product' ) ); ?>"><?php esc_html_e( 'All addon kinds', 'super-ninety-four' ); ?></a>
			</div>
		</section>
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Integrations pulse', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Payments, maps, and media defaults that unlock front-end features.', 'super-ninety-four' ); ?></p>
			<?php snf_admin_render_integrations_pulse( 'overview' ); ?>
			<div class="snf-admin__actions">
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-settings' ) ); ?>"><?php esc_html_e( 'Critical settings', 'super-ninety-four' ); ?></a>
				<?php endif; ?>
				<?php if ( current_user_can( 'manage_woocommerce' ) ) : ?>
					<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-stripe' ) ); ?>"><?php esc_html_e( 'SNF Stripe', 'super-ninety-four' ); ?></a>
				<?php endif; ?>
			</div>
		</section>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Events dashboard.
 */
function snf_admin_render_events() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club-events',
		__( 'Events', 'super-ninety-four' ),
		__( 'Select Events, workshops, broadcast modes, ticket experiences, and reservation intake.', 'super-ninety-four' )
	);

	$settings = snf_get_event_settings();
	$modes    = function_exists( 'snf_event_attendance_modes' ) ? snf_event_attendance_modes() : array();
	$providers = function_exists( 'snf_live_provider_choices' ) ? snf_live_provider_choices() : array();
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'event' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Published', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'event', 'draft' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Drafts', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_terms( 'snf_event_experience' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Experiences', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_terms( 'snf_broadcast_format' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Broadcast formats', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_terms( 'snf_ticket_addon_kind' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Addon kinds', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'snf_reservation' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Reservations', 'super-ninety-four' ); ?></span>
		</div>
	</div>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Quick actions', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Create content or open taxonomies used by ticket commerce and live broadcast.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=event' ) ); ?>"><?php esc_html_e( 'Add event', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=event' ) ); ?>"><?php esc_html_e( 'All events', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_reservation' ) ); ?>"><?php esc_html_e( 'Reservations', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=snf_event_experience&post_type=event' ) ); ?>"><?php esc_html_e( 'Experiences', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=snf_broadcast_format&post_type=event' ) ); ?>"><?php esc_html_e( 'Formats', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-addons' ) ); ?>"><?php esc_html_e( 'Addons & upgrades desk', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=snf_ticket_addon_kind&post_type=product' ) ); ?>"><?php esc_html_e( 'Addon kind terms', 'super-ninety-four' ); ?></a>
			</div>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Front-end surfaces', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Member and public Select Events pages plus the CPT archive.', 'super-ninety-four' ); ?></p>
			<ul class="snf-admin__list">
				<?php
				$links = array(
					array( __( 'Members Select Events', 'super-ninety-four' ), get_page_by_path( 'select-events-by-members' ) ),
					array( __( 'Public Select Events', 'super-ninety-four' ), get_page_by_path( 'select-events-by-non-members' ) ),
				);
				foreach ( $links as $row ) :
					$page = $row[1];
					?>
					<li>
						<span><?php echo esc_html( $row[0] ); ?></span>
						<?php if ( $page ) : ?>
							<a href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
						<?php else : ?>
							<span class="snf-admin__pill snf-admin__pill--bad"><?php esc_html_e( 'Missing page', 'super-ninety-four' ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				<li>
					<span><?php esc_html_e( 'Events archive', 'super-ninety-four' ); ?></span>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
				</li>
			</ul>
		</section>

		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Event defaults', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Defaults applied when new event products / CPT items lean on club broadcast settings.', 'super-ninety-four' ); ?></p>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_event_save_settings">
				<?php wp_nonce_field( 'snf_event_settings', 'snf_event_settings_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Default attendance mode', 'super-ninety-four' ); ?></th>
						<td>
							<select name="default_attendance_mode">
								<?php foreach ( $modes as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['default_attendance_mode'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default live provider', 'super-ninety-four' ); ?></th>
						<td>
							<select name="default_live_provider">
								<?php foreach ( $providers as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['default_live_provider'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Archive', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="archive_show_past" value="1" <?php checked( ! empty( $settings['archive_show_past'] ) ); ?>>
								<?php esc_html_e( 'Include past events on the archive when dates are present', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Ticketing', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="require_ticket_product" value="1" <?php checked( ! empty( $settings['require_ticket_product'] ) ); ?>>
								<?php esc_html_e( 'Prefer WooCommerce ticket products for paid Select Events', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save event settings', 'super-ninety-four' ), 'primary', 'submit', false ); ?>
			</form>
		</section>
		<?php endif; ?>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Circles dashboard.
 */
function snf_admin_render_circles() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club-circles',
		__( 'Circles', 'super-ninety-four' ),
		__( 'Member community rooms, visibility, and join settings for the Club hub.', 'super-ninety-four' )
	);

	$settings = snf_get_circle_settings();
	$circles  = get_posts(
		array(
			'post_type'      => 'snf_circle',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 50,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	global $wpdb;
	$members_table = function_exists( 'snf_community_members_table' ) ? snf_community_members_table() : '';
	$total_joins   = 0;
	if ( $members_table ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_joins = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$members_table}" );
	}
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'snf_circle' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Published circles', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) $total_joins ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Membership rows', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo get_option( 'snf_community_circles_seeded' ) ? 'OK' : '—'; ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Defaults seeded', 'super-ninety-four' ); ?></span>
		</div>
	</div>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Circle roster', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Visibility meta and join counts for each circle.', 'super-ninety-four' ); ?></p>
			<table class="snf-admin__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Circle', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Visibility', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Members', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Status', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $circles ) : ?>
						<tr><td colspan="4" class="snf-admin__muted"><?php esc_html_e( 'No circles yet.', 'super-ninety-four' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $circles as $circle ) : ?>
						<?php
						$vis = (string) get_post_meta( $circle->ID, '_snf_circle_visibility', true );
						if ( '' === $vis ) {
							$vis = 'members';
						}
						$count = 0;
						if ( $members_table ) {
							$count = (int) $wpdb->get_var(
								$wpdb->prepare(
									"SELECT COUNT(*) FROM {$members_table} WHERE circle_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
									(int) $circle->ID
								)
							);
						}
						$choices = snf_circle_visibility_choices();
						?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $circle->ID ) ); ?>"><?php echo esc_html( get_the_title( $circle ) ); ?></a></td>
							<td><span class="snf-admin__pill"><?php echo esc_html( $choices[ $vis ] ?? $vis ); ?></span></td>
							<td><?php echo esc_html( (string) $count ); ?></td>
							<td><?php echo esc_html( get_post_status( $circle ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=snf_circle' ) ); ?>"><?php esc_html_e( 'Add circle', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_circle' ) ); ?>"><?php esc_html_e( 'All circles', 'super-ninety-four' ); ?></a>
				<?php
				$community = get_page_by_path( 'community' );
				if ( $community ) :
					?>
					<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( get_permalink( $community ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Community hub', 'super-ninety-four' ); ?></a>
				<?php endif; ?>
			</div>
		</section>

		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Circle settings', 'super-ninety-four' ); ?></h2>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_circle_save_settings">
				<?php wp_nonce_field( 'snf_circle_settings', 'snf_circle_settings_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Default visibility', 'super-ninety-four' ); ?></th>
						<td>
							<select name="default_visibility">
								<?php foreach ( snf_circle_visibility_choices() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['default_visibility'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Self-join', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="allow_self_join" value="1" <?php checked( ! empty( $settings['allow_self_join'] ) ); ?>>
								<?php esc_html_e( 'Allow eligible members to join circles from the Community hub', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Hub intro', 'super-ninety-four' ); ?></th>
						<td>
							<textarea name="hub_intro" class="large-text" rows="3"><?php echo esc_textarea( (string) $settings['hub_intro'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Optional copy for the Community hub (theme can read snf_get_circle_settings).', 'super-ninety-four' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save circle settings', 'super-ninety-four' ), 'primary', 'submit', false ); ?>
			</form>
		</section>
		<?php endif; ?>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Videos & Audio dashboard (includes media settings form).
 */
function snf_admin_render_media() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club-media',
		__( 'Videos & Audio', 'super-ninety-four' ),
		__( 'YouTube library, audio channels, topics/series taxonomies, and live-room defaults.', 'super-ninety-four' )
	);

	$tax_rows = array(
		array( 'snf_media_topic', __( 'Topics', 'super-ninety-four' ), 'snf_video' ),
		array( 'snf_media_series', __( 'Video series', 'super-ninety-four' ), 'snf_video' ),
		array( 'snf_audio_channel', __( 'Audio channels', 'super-ninety-four' ), 'snf_audio' ),
		array( 'snf_broadcast_format', __( 'Broadcast formats', 'super-ninety-four' ), 'snf_video' ),
	);
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'snf_video' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Videos', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'snf_audio' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Audio', 'super-ninety-four' ); ?></span>
		</div>
		<?php foreach ( $tax_rows as $row ) : ?>
			<div class="snf-admin__stat">
				<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_terms( $row[0] ) ); ?></span>
				<span class="snf-admin__stat-label"><?php echo esc_html( $row[1] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Library actions', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Manage CPTs and taxonomies that power /videos-audio/ and library archives.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=snf_video' ) ); ?>"><?php esc_html_e( 'Add video', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=snf_audio' ) ); ?>"><?php esc_html_e( 'Add audio', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_video' ) ); ?>"><?php esc_html_e( 'All videos', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_audio' ) ); ?>"><?php esc_html_e( 'All audio', 'super-ninety-four' ); ?></a>
			</div>
			<table class="snf-admin__table" style="margin-top:1rem">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Taxonomy', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Terms', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Manage', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tax_rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row[1] ); ?></td>
							<td><?php echo esc_html( (string) snf_admin_count_terms( $row[0] ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . $row[0] . '&post_type=' . $row[2] ) ); ?>">
									<?php esc_html_e( 'Edit terms', 'super-ninety-four' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Public hubs', 'super-ninety-four' ); ?></h2>
			<ul class="snf-admin__list">
				<?php
				$hub = get_page_by_path( 'videos-audio' );
				?>
				<li>
					<span><?php esc_html_e( 'Videos & Audio page', 'super-ninety-four' ); ?></span>
					<?php if ( $hub ) : ?>
						<a href="<?php echo esc_url( get_permalink( $hub ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
					<?php else : ?>
						<span class="snf-admin__pill snf-admin__pill--bad"><?php esc_html_e( 'Missing', 'super-ninety-four' ); ?></span>
					<?php endif; ?>
				</li>
				<li>
					<span><?php esc_html_e( 'Video archive', 'super-ninety-four' ); ?></span>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'snf_video' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
				</li>
				<li>
					<span><?php esc_html_e( 'Audio archive', 'super-ninety-four' ); ?></span>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'snf_audio' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
				</li>
			</ul>
		</section>

		<?php if ( current_user_can( 'manage_options' ) && function_exists( 'snf_get_media_settings' ) ) : ?>
			<?php $s = snf_get_media_settings(); ?>
		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Media & live-room settings', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Access defaults, YouTube embeds, WebRTC rooms, and hub intro copy.', 'super-ninety-four' ); ?></p>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_media_save_settings">
				<input type="hidden" name="snf_media_redirect" value="snf-club-media">
				<?php wp_nonce_field( 'snf_media_settings', 'snf_media_settings_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Hub intro', 'super-ninety-four' ); ?></th>
						<td>
							<textarea name="hub_intro" class="large-text" rows="3"><?php echo esc_textarea( (string) $s['hub_intro'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'YouTube privacy-enhanced embeds', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="youtube_privacy_enhanced" value="1" <?php checked( ! empty( $s['youtube_privacy_enhanced'] ) ); ?>>
								<?php esc_html_e( 'Use youtube-nocookie.com', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default video access', 'super-ninety-four' ); ?></th>
						<td>
							<select name="default_video_access">
								<?php foreach ( snf_media_access_levels() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $s['default_video_access'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default audio access', 'super-ninety-four' ); ?></th>
						<td>
							<select name="default_audio_access">
								<?php foreach ( snf_media_access_levels() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $s['default_audio_access'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default live provider', 'super-ninety-four' ); ?></th>
						<td>
							<select name="live_provider">
								<?php foreach ( snf_live_provider_choices() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $s['live_provider'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'WebRTC live audio rooms', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enable_live_audio_rooms" value="1" <?php checked( ! empty( $s['enable_live_audio_rooms'] ) ); ?>>
								<?php esc_html_e( 'Enable browser audio rooms for hybrid / virtual events', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'ICE servers (one per line)', 'super-ninety-four' ); ?></th>
						<td>
							<textarea name="ice_servers" class="large-text code" rows="4"><?php echo esc_textarea( (string) $s['ice_servers'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Room signal TTL (seconds)', 'super-ninety-four' ); ?></th>
						<td>
							<input type="number" name="audio_room_ttl" value="<?php echo esc_attr( (string) $s['audio_room_ttl'] ); ?>" min="30" step="1">
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save media settings', 'super-ninety-four' ), 'primary', 'submit', false ); ?>
			</form>
		</section>
		<?php endif; ?>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Count newsletter subscribers captured within the last N days.
 *
 * @param int $days Window length in days.
 * @return int
 */
function snf_admin_subscriber_count_since( $days ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'snf_subscriber',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
			'date_query'     => array(
				array(
					'after'     => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - absint( $days ) * DAY_IN_SECONDS ),
					'inclusive' => true,
				),
			),
		)
	);
	return (int) $query->found_posts;
}

/**
 * Newsletter Subscribers dashboard.
 */
function snf_admin_render_subscribers() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club-subscribers',
		__( 'Newsletter Subscribers', 'super-ninety-four' ),
		__( 'Signups from the Video Newsletter page — capture health, recent subscribers, and a one-click CSV export.', 'super-ninety-four' )
	);

	$total      = snf_admin_count_posts( 'snf_subscriber', 'publish' );
	$last_30    = snf_admin_subscriber_count_since( 30 );
	$last_7     = snf_admin_subscriber_count_since( 7 );
	$notify     = apply_filters( 'snf_newsletter_notify_email', get_option( 'admin_email' ) );
	$notify_ok  = is_string( $notify ) && is_email( $notify );
	$newsletter = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'newsletter' ) : home_url( '/newsletter/' );
	$newsletter_page = get_page_by_path( 'newsletter' );
	$source_ids = get_posts(
		array(
			'post_type'      => 'snf_subscriber',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	$sources = array();
	foreach ( $source_ids as $subscriber_id ) {
		$source = (string) get_post_meta( $subscriber_id, '_snf_newsletter_source', true );
		if ( $source ) {
			$sources[ $source ] = true;
		}
	}

	$recent = get_posts(
		array(
			'post_type'      => 'snf_subscriber',
			'post_status'    => 'publish',
			'posts_per_page' => 25,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) $total ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Total subscribers', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) $last_30 ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Last 30 days', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) $last_7 ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Last 7 days', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) count( $sources ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Capture sources', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo $notify_ok ? esc_html__( 'ON', 'super-ninety-four' ) : esc_html__( 'ADMIN', 'super-ninety-four' ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Signup notification', 'super-ninety-four' ); ?></span>
		</div>
	</div>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Quick actions', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Open the capture page, the classic inbox, or download the list.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( $newsletter ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View newsletter page', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_subscriber&snf_classic=1' ) ); ?>"><?php esc_html_e( 'Classic list', 'super-ninety-four' ); ?></a>
				<?php if ( $newsletter_page ) : ?>
					<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( get_edit_post_link( $newsletter_page->ID ) ); ?>"><?php esc_html_e( 'Edit signup form page', 'super-ninety-four' ); ?></a>
				<?php endif; ?>
			</div>
			<form class="snf-admin__actions" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_subscribers_export">
				<?php wp_nonce_field( 'snf_subscribers_export', 'snf_subscribers_export_nonce' ); ?>
				<button type="submit" class="snf-admin__btn"><?php esc_html_e( 'Export subscribers as CSV', 'super-ninety-four' ); ?></button>
			</form>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Capture health', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'How the newsletter form is wired on the front end.', 'super-ninety-four' ); ?></p>
			<ul class="snf-admin__list">
				<li>
					<span><?php esc_html_e( 'Newsletter page', 'super-ninety-four' ); ?></span>
					<?php if ( $newsletter_page ) : ?>
						<a href="<?php echo esc_url( get_permalink( $newsletter_page ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Published', 'super-ninety-four' ); ?></a>
					<?php else : ?>
						<span class="snf-admin__pill snf-admin__pill--bad"><?php esc_html_e( 'Missing page', 'super-ninety-four' ); ?></span>
					<?php endif; ?>
				</li>
				<li>
					<span><?php esc_html_e( 'Notify email', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo $notify_ok ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>"><?php echo esc_html( $notify_ok ? (string) $notify : __( 'Admin email fallback', 'super-ninety-four' ) ); ?></span>
				</li>
				<li>
					<span><?php esc_html_e( 'Video source', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill"><?php echo esc_html( function_exists( 'snf_newsletter_youtube_url' ) ? (string) snf_newsletter_youtube_url() : '' ); ?></span>
				</li>
				<li>
					<span><?php esc_html_e( 'Spam protection', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill snf-admin__pill--ok"><?php esc_html_e( 'Nonce + honeypot + duplicate check', 'super-ninety-four' ); ?></span>
				</li>
			</ul>
		</section>

		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Recent subscribers', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Newest signups first. Emails link to the subscriber record in WordPress.', 'super-ninety-four' ); ?></p>
			<table class="snf-admin__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Email', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Phone', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Source', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'User', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Subscribed', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $recent ) : ?>
						<tr><td colspan="5" class="snf-admin__muted"><?php esc_html_e( 'No subscribers yet. New signups from the Newsletter page will appear here.', 'super-ninety-four' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $recent as $subscriber ) : ?>
						<?php
						$email      = (string) get_post_meta( $subscriber->ID, '_snf_newsletter_email', true );
						$phone      = (string) get_post_meta( $subscriber->ID, '_snf_newsletter_phone', true );
						$source     = (string) get_post_meta( $subscriber->ID, '_snf_newsletter_source', true );
						$user_id    = (int) get_post_meta( $subscriber->ID, '_snf_newsletter_user_id', true );
						$subscribed = (string) get_post_meta( $subscriber->ID, '_snf_newsletter_subscribed_at', true );
						$user_label = __( 'Guest', 'super-ninety-four' );
						if ( $user_id ) {
							$user = get_userdata( $user_id );
							$user_label = $user ? $user->display_name : sprintf( '#%d', $user_id );
						}
						?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $subscriber->ID ) ); ?>"><?php echo esc_html( $email ?: get_the_title( $subscriber ) ); ?></a></td>
							<td><?php echo $phone ? esc_html( $phone ) : '—'; ?></td>
							<td><span class="snf-admin__pill"><?php echo esc_html( $source ?: '—' ); ?></span></td>
							<td><?php echo esc_html( $user_label ); ?></td>
							<td><?php echo esc_html( $subscribed ?: get_the_date( '', $subscriber ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Marketplace RFQs dashboard.
 */
function snf_admin_render_rfqs() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club-rfqs',
		__( 'Marketplace RFQs', 'super-ninety-four' ),
		__( 'Expo / B2B quote inbox, field requirements, and staff notification settings.', 'super-ninety-four' )
	);

	$settings = function_exists( 'snf_get_rfq_settings' ) ? snf_get_rfq_settings() : array();
	$private  = snf_admin_count_posts( 'snf_rfq', 'private' );
	$publish  = snf_admin_count_posts( 'snf_rfq', 'publish' );
	$recent   = get_posts(
		array(
			'post_type'      => 'snf_rfq',
			'post_status'    => array( 'private', 'publish' ),
			'posts_per_page' => 12,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	$audiences = function_exists( 'snf_rfq_audience_choices' ) ? snf_rfq_audience_choices() : array();
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) ( $private + $publish ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Total RFQs', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) $private ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Inbox (private)', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo ! empty( $settings['enabled'] ) ? 'ON' : 'OFF'; ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Form enabled', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo ! empty( $settings['require_company'] ) ? 'REQ' : 'OPT'; ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Company field', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo ! empty( $settings['members_only'] ) ? 'YES' : 'NO'; ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Members only', 'super-ninety-four' ); ?></span>
		</div>
	</div>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Quick actions', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Open the RFQ inbox or front-end Expo surface where quotes are collected.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_rfq' ) ); ?>"><?php esc_html_e( 'All RFQs', 'super-ninety-four' ); ?></a>
				<?php
				$expo = get_page_by_path( 'expo' );
				if ( $expo ) :
					?>
					<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( get_permalink( $expo ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Expo', 'super-ninety-four' ); ?></a>
				<?php else : ?>
					<span class="snf-admin__pill snf-admin__pill--bad"><?php esc_html_e( 'Expo page missing', 'super-ninety-four' ); ?></span>
				<?php endif; ?>
			</div>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Requirements pulse', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Live validation rules enforced by REST intake and the Expo form.', 'super-ninety-four' ); ?></p>
			<ul class="snf-admin__list">
				<li>
					<span><?php esc_html_e( 'Company required', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo ! empty( $settings['require_company'] ) ? 'snf-admin__pill--ok' : ''; ?>">
						<?php echo ! empty( $settings['require_company'] ) ? esc_html__( 'Yes', 'super-ninety-four' ) : esc_html__( 'Optional', 'super-ninety-four' ); ?>
					</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Product listing required', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo ! empty( $settings['require_product'] ) ? 'snf-admin__pill--ok' : ''; ?>">
						<?php echo ! empty( $settings['require_product'] ) ? esc_html__( 'Yes', 'super-ninety-four' ) : esc_html__( 'Optional', 'super-ninety-four' ); ?>
					</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Max message length', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill"><?php echo esc_html( (string) max( 200, (int) ( $settings['max_message_length'] ?? 4000 ) ) ); ?></span>
				</li>
				<li>
					<span><?php esc_html_e( 'Notify email', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo ! empty( $settings['notify_email'] ) ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
						<?php echo ! empty( $settings['notify_email'] ) ? esc_html( (string) $settings['notify_email'] ) : esc_html__( 'Admin email', 'super-ninety-four' ); ?>
					</span>
				</li>
			</ul>
		</section>

		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Recent RFQs', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Newest quote requests from the Expo marketplace form.', 'super-ninety-four' ); ?></p>
			<table class="snf-admin__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'RFQ', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Contact', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Company', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Audience', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Date', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $recent ) : ?>
						<tr><td colspan="5" class="snf-admin__muted"><?php esc_html_e( 'No RFQs yet.', 'super-ninety-four' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $recent as $rfq ) : ?>
						<?php
						$name     = (string) get_post_meta( $rfq->ID, '_snf_rfq_name', true );
						$email    = (string) get_post_meta( $rfq->ID, '_snf_rfq_email', true );
						$company  = (string) get_post_meta( $rfq->ID, '_snf_rfq_company', true );
						$audience = (string) get_post_meta( $rfq->ID, '_snf_rfq_audience', true );
						?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $rfq->ID ) ); ?>"><?php echo esc_html( get_the_title( $rfq ) ); ?></a></td>
							<td>
								<?php echo esc_html( $name ?: '—' ); ?>
								<?php if ( $email ) : ?>
									<br><span class="snf-admin__muted"><?php echo esc_html( $email ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $company ?: '—' ); ?></td>
							<td><span class="snf-admin__pill"><?php echo esc_html( $audiences[ $audience ] ?? ( $audience ?: '—' ) ); ?></span></td>
							<td><?php echo esc_html( get_the_date( '', $rfq ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<?php if ( current_user_can( 'manage_options' ) && function_exists( 'snf_get_rfq_settings' ) ) : ?>
		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'RFQ settings & requirements', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Control who can submit, which fields are required, copy on the Expo form, and where staff get notified.', 'super-ninety-four' ); ?></p>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_rfq_save_settings">
				<?php wp_nonce_field( 'snf_rfq_settings', 'snf_rfq_settings_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Intake', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>>
								<?php esc_html_e( 'Enable marketplace RFQ form and REST endpoint', 'super-ninety-four' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="members_only" value="1" <?php checked( ! empty( $settings['members_only'] ) ); ?>>
								<?php esc_html_e( 'Members only (require club member access)', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Field requirements', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="require_company" value="1" <?php checked( ! empty( $settings['require_company'] ) ); ?>>
								<?php esc_html_e( 'Require company', 'super-ninety-four' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="require_product" value="1" <?php checked( ! empty( $settings['require_product'] ) ); ?>>
								<?php esc_html_e( 'Require a product / listing ID', 'super-ninety-four' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Name, email, and message stay required. Product requirement is for listing-scoped RFQs.', 'super-ninety-four' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="snf_rfq_max_message"><?php esc_html_e( 'Max message length', 'super-ninety-four' ); ?></label></th>
						<td>
							<input type="number" id="snf_rfq_max_message" name="max_message_length" value="<?php echo esc_attr( (string) max( 200, (int) $settings['max_message_length'] ) ); ?>" min="200" max="20000" step="1">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="snf_rfq_default_audience"><?php esc_html_e( 'Default audience', 'super-ninety-four' ); ?></label></th>
						<td>
							<select name="default_audience" id="snf_rfq_default_audience">
								<?php foreach ( $audiences as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['default_audience'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="snf_rfq_notify_email"><?php esc_html_e( 'Notify email', 'super-ninety-four' ); ?></label></th>
						<td>
							<input type="email" class="regular-text" id="snf_rfq_notify_email" name="notify_email" value="<?php echo esc_attr( (string) $settings['notify_email'] ); ?>" placeholder="<?php echo esc_attr( (string) get_option( 'admin_email' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Leave blank to use the WordPress admin email.', 'super-ninety-four' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="snf_rfq_form_title"><?php esc_html_e( 'Form title', 'super-ninety-four' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" id="snf_rfq_form_title" name="form_title" value="<?php echo esc_attr( (string) $settings['form_title'] ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="snf_rfq_form_intro"><?php esc_html_e( 'Form intro', 'super-ninety-four' ); ?></label></th>
						<td>
							<textarea name="form_intro" id="snf_rfq_form_intro" class="large-text" rows="3"><?php echo esc_textarea( (string) $settings['form_intro'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="snf_rfq_success"><?php esc_html_e( 'Success message', 'super-ninety-four' ); ?></label></th>
						<td>
							<textarea name="success_message" id="snf_rfq_success" class="large-text" rows="2"><?php echo esc_textarea( (string) $settings['success_message'] ); ?></textarea>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save RFQ settings', 'super-ninety-four' ), 'primary', 'submit', false ); ?>
			</form>
		</section>
		<?php endif; ?>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Ticket Addons & Upgrades dashboard.
 */
function snf_admin_render_addon_kinds() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club-addons',
		__( 'Ticket Addons & Upgrades', 'super-ninety-four' ),
		__( 'Classify hospitality, transport, media, guest, merch, and interactivity addons used by ticket commerce.', 'super-ninety-four' )
	);

	$settings = function_exists( 'snf_get_ticket_addon_settings' ) ? snf_get_ticket_addon_settings() : array();
	$defaults = function_exists( 'snf_ticket_commerce_default_addon_kinds' ) ? snf_ticket_commerce_default_addon_kinds() : array();
	$terms    = taxonomy_exists( 'snf_ticket_addon_kind' )
		? get_terms(
			array(
				'taxonomy'   => 'snf_ticket_addon_kind',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		)
		: array();
	if ( is_wp_error( $terms ) ) {
		$terms = array();
	}

	$missing = array();
	foreach ( $defaults as $slug => $label ) {
		$found = false;
		foreach ( $terms as $term ) {
			if ( $slug === $term->slug ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			$missing[ $slug ] = $label;
		}
	}

	$enabled_commerce = 0;
	$kind_refs        = array();
	$posts            = get_posts(
		array(
			'post_type'      => array( 'product', 'event' ),
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 200,
			'meta_key'       => function_exists( 'snf_ticket_commerce_meta_key' ) ? snf_ticket_commerce_meta_key() : '_snf_ticket_commerce',
			'fields'         => 'ids',
		)
	);
	foreach ( $posts as $pid ) {
		$config = function_exists( 'snf_get_ticket_commerce_config' ) ? snf_get_ticket_commerce_config( $pid ) : array();
		if ( ! empty( $config['enabled'] ) ) {
			++$enabled_commerce;
		}
		$slugs = function_exists( 'snf_ticket_commerce_config_kind_slugs' ) ? snf_ticket_commerce_config_kind_slugs( $config ) : array();
		foreach ( $slugs as $slug ) {
			if ( ! isset( $kind_refs[ $slug ] ) ) {
				$kind_refs[ $slug ] = 0;
			}
			++$kind_refs[ $slug ];
		}
	}
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) count( $terms ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Addon kinds', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) count( $defaults ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Default kinds', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) count( $missing ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Missing defaults', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) $enabled_commerce ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Ticket commerce on', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo ! empty( $settings['require_kind_on_addons'] ) ? 'REQ' : 'OPT'; ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Addon kind field', 'super-ninety-four' ); ?></span>
		</div>
	</div>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Quick actions', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Manage taxonomy terms or jump into Events / Products that use ticket commerce.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=snf_ticket_addon_kind&post_type=product' ) ); ?>"><?php esc_html_e( 'Edit addons & upgrades terms', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=snf_event_experience&post_type=product' ) ); ?>"><?php esc_html_e( 'Event experiences', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-events' ) ); ?>"><?php esc_html_e( 'Events dashboard', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>"><?php esc_html_e( 'Products', 'super-ninety-four' ); ?></a>
			</div>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:1rem">
					<input type="hidden" name="action" value="snf_ticket_addon_seed_terms">
					<?php wp_nonce_field( 'snf_ticket_addon_seed', 'snf_ticket_addon_seed_nonce' ); ?>
					<button type="submit" class="snf-admin__btn snf-admin__btn--ghost"><?php esc_html_e( 'Reseed missing default kinds', 'super-ninety-four' ); ?></button>
				</form>
			<?php endif; ?>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Requirements pulse', 'super-ninety-four' ); ?></h2>
			<ul class="snf-admin__list">
				<li>
					<span><?php esc_html_e( 'Require kind on addons', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo ! empty( $settings['require_kind_on_addons'] ) ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
						<?php echo ! empty( $settings['require_kind_on_addons'] ) ? esc_html__( 'Required', 'super-ninety-four' ) : esc_html__( 'Optional', 'super-ninety-four' ); ?>
					</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Require kind on upsells', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo ! empty( $settings['require_kind_on_upsells'] ) ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
						<?php echo ! empty( $settings['require_kind_on_upsells'] ) ? esc_html__( 'Required', 'super-ninety-four' ) : esc_html__( 'Optional', 'super-ninety-four' ); ?>
					</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Require kind on interactivities', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo ! empty( $settings['require_kind_on_interactivities'] ) ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
						<?php echo ! empty( $settings['require_kind_on_interactivities'] ) ? esc_html__( 'Required', 'super-ninety-four' ) : esc_html__( 'Optional', 'super-ninety-four' ); ?>
					</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Auto-assign terms on save', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo ! empty( $settings['auto_assign_terms'] ) ? 'snf-admin__pill--ok' : ''; ?>">
						<?php echo ! empty( $settings['auto_assign_terms'] ) ? esc_html__( 'On', 'super-ninety-four' ) : esc_html__( 'Off', 'super-ninety-four' ); ?>
					</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Default addon max qty', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill"><?php echo esc_html( (string) max( 1, (int) ( $settings['default_addon_max_qty'] ?? 1 ) ) ); ?></span>
				</li>
			</ul>
		</section>

		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Addons & upgrades roster', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Taxonomy terms plus how many ticket-commerce configs currently reference each kind slug.', 'super-ninety-four' ); ?></p>
			<?php if ( ! empty( $settings['desk_intro'] ) ) : ?>
				<p class="snf-admin__muted"><?php echo esc_html( (string) $settings['desk_intro'] ); ?></p>
			<?php endif; ?>
			<table class="snf-admin__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Kind', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Slug', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Assigned objects', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Config refs', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Default', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $terms ) : ?>
						<tr><td colspan="5" class="snf-admin__muted"><?php esc_html_e( 'No addon kinds yet. Reseed defaults or add terms.', 'super-ninety-four' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $terms as $term ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_term_link( $term, 'snf_ticket_addon_kind' ) ); ?>">
									<?php echo esc_html( $term->name ); ?>
								</a>
							</td>
							<td><code><?php echo esc_html( $term->slug ); ?></code></td>
							<td><?php echo esc_html( (string) (int) $term->count ); ?></td>
							<td><?php echo esc_html( (string) (int) ( $kind_refs[ $term->slug ] ?? 0 ) ); ?></td>
							<td>
								<?php if ( isset( $defaults[ $term->slug ] ) ) : ?>
									<span class="snf-admin__pill snf-admin__pill--ok"><?php esc_html_e( 'Yes', 'super-ninety-four' ); ?></span>
								<?php else : ?>
									<span class="snf-admin__pill"><?php esc_html_e( 'Custom', 'super-ninety-four' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php foreach ( $missing as $slug => $label ) : ?>
						<tr>
							<td><?php echo esc_html( $label ); ?></td>
							<td><code><?php echo esc_html( $slug ); ?></code></td>
							<td>0</td>
							<td><?php echo esc_html( (string) (int) ( $kind_refs[ $slug ] ?? 0 ) ); ?></td>
							<td><span class="snf-admin__pill snf-admin__pill--bad"><?php esc_html_e( 'Missing', 'super-ninety-four' ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<?php if ( current_user_can( 'manage_options' ) && function_exists( 'snf_get_ticket_addon_settings' ) ) : ?>
		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Addons & upgrades settings', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Control whether ticket commerce rows must declare a kind, default quantities, and automatic taxonomy assignment.', 'super-ninety-four' ); ?></p>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_ticket_addon_save_settings">
				<?php wp_nonce_field( 'snf_ticket_addon_settings', 'snf_ticket_addon_settings_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Field requirements', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="require_kind_on_addons" value="1" <?php checked( ! empty( $settings['require_kind_on_addons'] ) ); ?>>
								<?php esc_html_e( 'Require kind on addon rows (rows without a kind are dropped on save)', 'super-ninety-four' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="require_kind_on_upsells" value="1" <?php checked( ! empty( $settings['require_kind_on_upsells'] ) ); ?>>
								<?php esc_html_e( 'Require kind on upsell rows', 'super-ninety-four' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="require_kind_on_interactivities" value="1" <?php checked( ! empty( $settings['require_kind_on_interactivities'] ) ); ?>>
								<?php esc_html_e( 'Require kind on interactivity rows', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Taxonomy sync', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="auto_assign_terms" value="1" <?php checked( ! empty( $settings['auto_assign_terms'] ) ); ?>>
								<?php esc_html_e( 'Auto-assign snf_ticket_addon_kind terms from commerce config kinds when saving products/events', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="snf_addon_default_max_qty"><?php esc_html_e( 'Default addon max qty', 'super-ninety-four' ); ?></label></th>
						<td>
							<input type="number" id="snf_addon_default_max_qty" name="default_addon_max_qty" value="<?php echo esc_attr( (string) max( 1, (int) $settings['default_addon_max_qty'] ) ); ?>" min="1" max="99" step="1">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="snf_addon_desk_intro"><?php esc_html_e( 'Desk intro', 'super-ninety-four' ); ?></label></th>
						<td>
							<textarea name="desk_intro" id="snf_addon_desk_intro" class="large-text" rows="3"><?php echo esc_textarea( (string) $settings['desk_intro'] ); ?></textarea>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save addons & upgrades settings', 'super-ninety-four' ), 'primary', 'submit', false ); ?>
			</form>
		</section>
		<?php endif; ?>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Safe redirect target for shared Club / Stripe ops forms.
 *
 * @param string $fallback Default page slug.
 * @return string Admin page slug.
 */
function snf_admin_ops_redirect_page( $fallback = 'snf-club-settings' ) {
	$allowed = array( 'snf-club-settings', 'snf-stripe' );
	$page    = isset( $_POST['snf_ops_redirect'] ) ? sanitize_key( wp_unslash( $_POST['snf_ops_redirect'] ) ) : $fallback; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	return in_array( $page, $allowed, true ) ? $page : $fallback;
}

/**
 * Run production-style club bootstrap (roles, tables, circles, pages, menus).
 *
 * @return array<string, bool> Step results.
 */
function snf_admin_run_club_bootstrap() {
	$results = array(
		'roles'    => false,
		'messages' => false,
		'community'=> false,
		'circles'  => false,
		'pages'    => false,
		'menus'    => false,
	);

	if ( function_exists( 'snf_register_club_roles' ) ) {
		snf_register_club_roles();
		$results['roles'] = true;
	}
	if ( function_exists( 'snf_messages_install_tables' ) ) {
		snf_messages_install_tables();
		$results['messages'] = true;
	}
	if ( function_exists( 'snf_community_install_tables' ) ) {
		snf_community_install_tables();
		$results['community'] = true;
	}
	if ( function_exists( 'snf_community_ensure_default_circles' ) ) {
		snf_community_ensure_default_circles();
		$results['circles'] = true;
	}
	if ( function_exists( 'snf_ensure_site_pages' ) ) {
		snf_ensure_site_pages();
		$results['pages'] = true;
	}
	update_option( 'snf_theme_menus_force_resync', 1, false );
	if ( function_exists( 'snf_ensure_theme_menus' ) ) {
		snf_ensure_theme_menus();
		$results['menus'] = true;
	}

	return $results;
}

/**
 * Shared critical ops panels for Club Critical settings and SNF Stripe.
 *
 * @param string $context club|stripe.
 */
function snf_admin_render_critical_ops_panels( $context = 'club' ) {
	$context      = ( 'stripe' === $context ) ? 'stripe' : 'club';
	$redirect     = ( 'stripe' === $context ) ? 'snf-stripe' : 'snf-club-settings';
	$can_options  = current_user_can( 'manage_options' );
	$can_woo      = current_user_can( 'manage_woocommerce' );
	$has_service  = function_exists( 'snf_supabase_payments_has_service_role' ) && snf_supabase_payments_has_service_role();
	$lookup_rows  = get_transient( 'snf_admin_lookup_rows_' . get_current_user_id() );
	$lookup_email = (string) get_transient( 'snf_admin_lookup_email_' . get_current_user_id() );
	$mapbox       = function_exists( 'snf_get_mapbox_token' ) ? (string) snf_get_mapbox_token() : (string) get_option( 'snf_mapbox_token', '' );
	$x_ads        = function_exists( 'snf_get_x_ads_pixel_settings' ) ? snf_get_x_ads_pixel_settings() : array();

	if ( 'club' === $context ) :
		?>
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Mapbox', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Lead rider / Expo map token. Constant SNF_MAPBOX_TOKEN overrides the option when defined.', 'super-ninety-four' ); ?></p>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_club_save_critical">
				<?php wp_nonce_field( 'snf_club_critical', 'snf_club_critical_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Mapbox token', 'super-ninety-four' ); ?></th>
						<td>
							<input type="text" class="large-text code" name="snf_mapbox_token" value="<?php echo esc_attr( $mapbox ); ?>" autocomplete="off">
							<?php if ( defined( 'SNF_MAPBOX_TOKEN' ) && SNF_MAPBOX_TOKEN ) : ?>
								<p class="description"><?php esc_html_e( 'A wp-config constant is currently defining the live token.', 'super-ninety-four' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Mapbox token', 'super-ninety-four' ), 'primary', 'submit', false ); ?>
			</form>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'X Ads Pixel', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Universal Website Tag from your linked X Ads account. Loads site-wide; fires Purchase on Woo thank-you and Sign up / Complete registration from Apply.', 'super-ninety-four' ); ?></p>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_club_save_x_ads_pixel">
				<?php wp_nonce_field( 'snf_club_x_ads_pixel', 'snf_club_x_ads_pixel_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable pixel', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="snf_x_ads_enabled" value="1" <?php checked( ! empty( $x_ads['enabled'] ) && 'yes' === $x_ads['enabled'] ); ?>>
								<?php esc_html_e( 'Inject X Ads Universal Website Tag on the front end', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Pixel ID', 'super-ninety-four' ); ?></th>
						<td><code>pp813</code>
							<p class="description"><?php esc_html_e( 'Base tag always uses twq(\'config\',\'pp813\'). Set X_PIXEL_TOKEN in the environment or wp-config for Conversion API.', 'super-ninety-four' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="snf-x-ads-purchase"><?php esc_html_e( 'Purchase event ID', 'super-ninety-four' ); ?></label></th>
						<td>
							<input type="text" class="regular-text code" id="snf-x-ads-purchase" name="snf_x_ads_purchase_event" value="<?php echo esc_attr( isset( $x_ads['purchase_event'] ) ? (string) $x_ads['purchase_event'] : 'pp814' ); ?>" placeholder="pp814">
							<p class="description"><?php esc_html_e( 'CAPI event_id for this pixel is the website tag id (e.g. pp814 Purchase Tracker), shared with the browser event + conversion_id for dedupe.', 'super-ninety-four' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="snf-x-ads-signup"><?php esc_html_e( 'Sign up event ID', 'super-ninety-four' ); ?></label></th>
						<td>
							<input type="text" class="regular-text code" id="snf-x-ads-signup" name="snf_x_ads_signup_event" value="<?php echo esc_attr( isset( $x_ads['signup_event'] ) ? (string) $x_ads['signup_event'] : 'pp815' ); ?>" placeholder="pp815">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="snf-x-ads-checkout"><?php esc_html_e( 'Checkout event ID', 'super-ninety-four' ); ?></label></th>
						<td>
							<input type="text" class="regular-text code" id="snf-x-ads-checkout" name="snf_x_ads_checkout_event" value="<?php echo esc_attr( isset( $x_ads['checkout_event'] ) ? (string) $x_ads['checkout_event'] : 'rjdo4' ); ?>" placeholder="rjdo4">
							<p class="description"><?php esc_html_e( 'Fires once per Woo session when the customer opens checkout (browser + CAPI). Default: Checkout Initiated tag rjdo4.', 'super-ninety-four' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save X Ads Pixel', 'super-ninety-four' ), 'primary', 'submit', false ); ?>
			</form>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Payments & CRM', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Stripe / Supabase live on the WooCommerce SNF Stripe screen.', 'super-ninety-four' ); ?></p>
			<?php snf_admin_render_integrations_pulse( 'critical' ); ?>
			<div class="snf-admin__actions">
				<?php if ( $can_woo ) : ?>
					<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-stripe' ) ); ?>"><?php esc_html_e( 'Open SNF Stripe', 'super-ninety-four' ); ?></a>
				<?php endif; ?>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>"><?php esc_html_e( 'Woo payments', 'super-ninety-four' ); ?></a>
			</div>
		</section>
		<?php
	endif;

	if ( $can_woo ) :
		?>
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'API probe & catalog', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Re-check Stripe, refresh the Supabase price catalog cache, and update the shared health pills.', 'super-ninety-four' ); ?></p>
			<?php if ( 'stripe' === $context ) : ?>
				<?php snf_admin_render_integrations_pulse( 'stripe' ); ?>
			<?php endif; ?>
			<div class="snf-admin__actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="snf_ops_refresh_health">
					<input type="hidden" name="snf_ops_redirect" value="<?php echo esc_attr( $redirect ); ?>">
					<?php wp_nonce_field( 'snf_ops_refresh_health', 'snf_ops_refresh_health_nonce' ); ?>
					<button type="submit" class="snf-admin__btn"><?php esc_html_e( 'Re-probe APIs', 'super-ninety-four' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="snf_ops_refresh_catalog">
					<input type="hidden" name="snf_ops_redirect" value="<?php echo esc_attr( $redirect ); ?>">
					<?php wp_nonce_field( 'snf_ops_refresh_catalog', 'snf_ops_refresh_catalog_nonce' ); ?>
					<button type="submit" class="snf-admin__btn snf-admin__btn--ghost"><?php esc_html_e( 'Refresh catalog cache', 'super-ninety-four' ); ?></button>
				</form>
			</div>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Commerce requirements', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'SNF Club membership checkout requires WooCommerce, SUPER NINETY FOUR Payments, seeded products, and Stripe webhooks.', 'super-ninety-four' ); ?></p>
			<?php if ( function_exists( 'snf_commerce_requirements_checklist' ) ) : ?>
				<ul class="snf-admin__list">
					<?php foreach ( snf_commerce_requirements_checklist() as $req ) : ?>
						<li>
							<span><?php echo esc_html( $req['label'] ); ?></span>
							<span class="snf-admin__pill <?php echo ! empty( $req['ok'] ) ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
								<?php echo esc_html( $req['detail'] ); ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<ul class="snf-admin__list">
				<li>
					<span><?php esc_html_e( 'Payments webhook (primary)', 'super-ninety-four' ); ?></span>
					<code class="snf-admin__code"><?php echo esc_html( function_exists( 'snf_payments_webhook_url' ) ? snf_payments_webhook_url() : rest_url( 'snf-payments/v1/stripe/webhook' ) ); ?></code>
				</li>
				<li>
					<span><?php esc_html_e( 'Theme webhook (Checkout Sessions)', 'super-ninety-four' ); ?></span>
					<code class="snf-admin__code"><?php echo esc_html( function_exists( 'snf_stripe_webhook_url' ) ? snf_stripe_webhook_url() : rest_url( 'snf/v1/stripe/webhook' ) ); ?></code>
				</li>
			</ul>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Commerce catalog seed', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Create any missing membership or reciprocal Woo products without changing existing ones. Sync creates matching Stripe Products/Prices for the active SNF Stripe mode and stores Price IDs on each product.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="snf_ops_ensure_membership_products">
					<input type="hidden" name="snf_ops_redirect" value="<?php echo esc_attr( $redirect ); ?>">
					<?php wp_nonce_field( 'snf_ops_ensure_membership', 'snf_ops_ensure_membership_nonce' ); ?>
					<button type="submit" class="snf-admin__btn"><?php esc_html_e( 'Ensure membership products', 'super-ninety-four' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="snf_ops_sync_stripe_membership_catalog">
					<input type="hidden" name="snf_ops_redirect" value="<?php echo esc_attr( $redirect ); ?>">
					<?php wp_nonce_field( 'snf_ops_sync_stripe_membership', 'snf_ops_sync_stripe_membership_nonce' ); ?>
					<button type="submit" class="snf-admin__btn snf-admin__btn--ghost"><?php esc_html_e( 'Sync Stripe membership catalog', 'super-ninety-four' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="snf_ops_ensure_reciprocal_products">
					<input type="hidden" name="snf_ops_redirect" value="<?php echo esc_attr( $redirect ); ?>">
					<?php wp_nonce_field( 'snf_ops_ensure_reciprocal', 'snf_ops_ensure_reciprocal_nonce' ); ?>
					<button type="submit" class="snf-admin__btn snf-admin__btn--ghost"><?php esc_html_e( 'Ensure reciprocal products', 'super-ninety-four' ); ?></button>
				</form>
			</div>
		</section>

		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Subscription lookup', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Query Supabase Sync Engine for Stripe subscriptions by customer email (service role required).', 'super-ninety-four' ); ?></p>
			<?php if ( ! $has_service ) : ?>
				<p class="snf-admin__muted"><?php esc_html_e( 'Add a Supabase service role key on SNF Stripe (or SNF_SUPABASE_SERVICE_ROLE_KEY in wp-config) to enable lookups.', 'super-ninety-four' ); ?></p>
			<?php endif; ?>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_ops_subscription_lookup">
				<input type="hidden" name="snf_ops_redirect" value="<?php echo esc_attr( $redirect ); ?>">
				<?php wp_nonce_field( 'snf_ops_subscription_lookup', 'snf_ops_subscription_lookup_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="snf-ops-lookup-email"><?php esc_html_e( 'Customer email', 'super-ninety-four' ); ?></label></th>
						<td>
							<input type="email" class="regular-text" id="snf-ops-lookup-email" name="lookup_email" value="<?php echo esc_attr( $lookup_email ); ?>" required <?php disabled( ! $has_service ); ?>>
						</td>
					</tr>
				</table>
				<button type="submit" class="snf-admin__btn" <?php disabled( ! $has_service ); ?>><?php esc_html_e( 'Look up subscriptions', 'super-ninety-four' ); ?></button>
			</form>
			<?php if ( is_array( $lookup_rows ) && $lookup_rows ) : ?>
				<h3 class="snf-admin__subhead"><?php echo esc_html( sprintf( /* translators: %s: email */ __( 'Results for %s', 'super-ninety-four' ), $lookup_email ) ); ?></h3>
				<table class="snf-admin__table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Subscription', 'super-ninety-four' ); ?></th>
							<th><?php esc_html_e( 'Status', 'super-ninety-four' ); ?></th>
							<th><?php esc_html_e( 'Price / product', 'super-ninety-four' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'super-ninety-four' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $lookup_rows as $row ) : ?>
							<tr>
								<td><code><?php echo esc_html( (string) ( $row['id'] ?? $row['subscription_id'] ?? '' ) ); ?></code></td>
								<td><?php echo esc_html( (string) ( $row['status'] ?? '' ) ); ?></td>
								<td>
									<?php
									$price = (string) ( $row['price_id'] ?? $row['plan'] ?? '' );
									$prod  = (string) ( $row['product_name'] ?? $row['product_id'] ?? '' );
									echo esc_html( trim( $price . ( $prod ? ' — ' . $prod : '' ) ) );
									?>
								</td>
								<td><?php echo esc_html( (string) ( $row['customer_email'] ?? $row['email'] ?? $lookup_email ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>
		<?php
	endif;

	if ( 'club' === $context && $can_options ) :
		?>
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Club bootstrap', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Refresh club roles, message/community tables, default circles, auto pages, and theme menus.', 'super-ninety-four' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_ops_club_bootstrap">
				<input type="hidden" name="snf_ops_redirect" value="snf-club-settings">
				<?php wp_nonce_field( 'snf_ops_club_bootstrap', 'snf_ops_club_bootstrap_nonce' ); ?>
				<button type="submit" class="snf-admin__btn"><?php esc_html_e( 'Run club bootstrap', 'super-ninety-four' ); ?></button>
			</form>
		</section>

		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Page health', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Club pages auto-created by the theme. Missing rows usually mean bootstrap has not run.', 'super-ninety-four' ); ?></p>
			<table class="snf-admin__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Surface', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Slug', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Status', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Links', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( snf_admin_page_health_rows() as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['label'] ); ?></td>
							<td><code><?php echo esc_html( $row['slug'] ); ?></code></td>
							<td>
								<span class="snf-admin__pill <?php echo $row['ok'] ? 'snf-admin__pill--ok' : 'snf-admin__pill--bad'; ?>">
									<?php echo $row['ok'] ? esc_html__( 'Present', 'super-ninety-four' ) : esc_html__( 'Missing', 'super-ninety-four' ); ?>
								</span>
							</td>
							<td>
								<?php if ( $row['edit'] ) : ?>
									<a href="<?php echo esc_url( $row['edit'] ); ?>"><?php esc_html_e( 'Edit', 'super-ninety-four' ); ?></a>
									·
									<a href="<?php echo esc_url( $row['view'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
								<?php else : ?>
									<span class="snf-admin__muted">—</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Maintenance', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Safe ops tools after theme sync or Site Editor experiments.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="snf_club_flush_rewrites">
					<?php wp_nonce_field( 'snf_club_flush', 'snf_club_flush_nonce' ); ?>
					<button type="submit" class="snf-admin__btn"><?php esc_html_e( 'Flush permalinks', 'super-ninety-four' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="snf_club_reset_styles">
					<?php wp_nonce_field( 'snf_club_reset_styles', 'snf_club_reset_styles_nonce' ); ?>
					<button type="submit" class="snf-admin__btn snf-admin__btn--ghost"><?php esc_html_e( 'Reset Global Styles', 'super-ninety-four' ); ?></button>
				</form>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>"><?php esc_html_e( 'Site Editor', 'super-ninety-four' ); ?></a>
			</div>
		</section>
		<?php
	endif;

	if ( 'stripe' === $context && $can_options ) :
		?>
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Club ops', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Roles, pages, menus, and Global Styles tools live on Club Critical settings.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-settings' ) ); ?>"><?php esc_html_e( 'Open Critical settings', 'super-ninety-four' ); ?></a>
			</div>
		</section>
		<?php
	endif;
}

/**
 * Critical settings dashboard.
 */
function snf_admin_render_critical_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club-settings',
		__( 'Critical settings', 'super-ninety-four' ),
		__( 'Integrations, commerce seeds, and maintenance tools the Club and Stripe stacks rely on.', 'super-ninety-four' )
	);

	if ( snf_admin_vue_bundle_ready() ) {
		echo '<div id="snf-admin-settings-pulse"></div>';
	}
	?>
	<div class="snf-admin__grid snf-admin__grid--panels">
		<?php snf_admin_render_critical_ops_panels( 'club' ); ?>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Save event settings.
 */
function snf_admin_handle_event_settings_save() {
	if ( ! isset( $_POST['snf_event_settings_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_event_settings', 'snf_event_settings_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$current   = snf_get_event_settings();
	$modes     = function_exists( 'snf_event_attendance_modes' ) ? snf_event_attendance_modes() : array();
	$providers = function_exists( 'snf_live_provider_choices' ) ? snf_live_provider_choices() : array();

	$mode     = isset( $_POST['default_attendance_mode'] ) ? sanitize_key( wp_unslash( $_POST['default_attendance_mode'] ) ) : $current['default_attendance_mode'];
	$provider = isset( $_POST['default_live_provider'] ) ? sanitize_key( wp_unslash( $_POST['default_live_provider'] ) ) : $current['default_live_provider'];

	$current['default_attendance_mode'] = isset( $modes[ $mode ] ) ? $mode : 'hybrid';
	$current['default_live_provider']   = isset( $providers[ $provider ] ) ? $provider : 'youtube_live';
	$current['archive_show_past']       = isset( $_POST['archive_show_past'] ) ? 1 : 0;
	$current['require_ticket_product']  = isset( $_POST['require_ticket_product'] ) ? 1 : 0;

	update_option( SNF_EVENT_SETTINGS_OPTION, $current, false );

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-events&updated=1' ) );
	exit;
}
add_action( 'admin_post_snf_event_save_settings', 'snf_admin_handle_event_settings_save' );

/**
 * Save circle settings.
 */
function snf_admin_handle_circle_settings_save() {
	if ( ! isset( $_POST['snf_circle_settings_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_circle_settings', 'snf_circle_settings_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$current = snf_get_circle_settings();
	$choices = snf_circle_visibility_choices();
	$vis     = isset( $_POST['default_visibility'] ) ? sanitize_key( wp_unslash( $_POST['default_visibility'] ) ) : $current['default_visibility'];

	$current['default_visibility'] = isset( $choices[ $vis ] ) ? $vis : 'members';
	$current['allow_self_join']    = isset( $_POST['allow_self_join'] ) ? 1 : 0;
	$current['hub_intro']          = isset( $_POST['hub_intro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hub_intro'] ) ) : '';

	update_option( SNF_CIRCLE_SETTINGS_OPTION, $current, false );

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-circles&updated=1' ) );
	exit;
}
add_action( 'admin_post_snf_circle_save_settings', 'snf_admin_handle_circle_settings_save' );

/**
 * Save Ticket Addons & Upgrades settings.
 */
function snf_admin_handle_ticket_addon_settings_save() {
	if ( ! isset( $_POST['snf_ticket_addon_settings_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_ticket_addon_settings', 'snf_ticket_addon_settings_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}
	if ( ! function_exists( 'snf_get_ticket_addon_settings' ) || ! defined( 'SNF_TICKET_ADDON_SETTINGS_OPTION' ) ) {
		wp_die( esc_html__( 'Addon kind settings unavailable.', 'super-ninety-four' ) );
	}

	$current = snf_get_ticket_addon_settings();
	$current['require_kind_on_addons']          = isset( $_POST['require_kind_on_addons'] ) ? 1 : 0;
	$current['require_kind_on_upsells']         = isset( $_POST['require_kind_on_upsells'] ) ? 1 : 0;
	$current['require_kind_on_interactivities'] = isset( $_POST['require_kind_on_interactivities'] ) ? 1 : 0;
	$current['auto_assign_terms']               = isset( $_POST['auto_assign_terms'] ) ? 1 : 0;
	$current['default_addon_max_qty']           = isset( $_POST['default_addon_max_qty'] ) ? max( 1, min( 99, absint( $_POST['default_addon_max_qty'] ) ) ) : 1;
	$current['desk_intro']                      = isset( $_POST['desk_intro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['desk_intro'] ) ) : '';

	update_option( SNF_TICKET_ADDON_SETTINGS_OPTION, $current, false );

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-addons&updated=1' ) );
	exit;
}
add_action( 'admin_post_snf_ticket_addon_save_settings', 'snf_admin_handle_ticket_addon_settings_save' );

/**
 * Force-seed missing experience + addon-kind terms.
 */
function snf_admin_handle_ticket_addon_seed_terms() {
	if ( ! isset( $_POST['snf_ticket_addon_seed_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_ticket_addon_seed', 'snf_ticket_addon_seed_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	if ( function_exists( 'snf_ticket_commerce_seed_default_terms' ) ) {
		snf_ticket_commerce_seed_default_terms( true );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-addons&seeded=1' ) );
	exit;
}
add_action( 'admin_post_snf_ticket_addon_seed_terms', 'snf_admin_handle_ticket_addon_seed_terms' );

/**
 * Save Marketplace RFQ settings.
 */
function snf_admin_handle_rfq_settings_save() {
	if ( ! isset( $_POST['snf_rfq_settings_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_rfq_settings', 'snf_rfq_settings_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}
	if ( ! function_exists( 'snf_get_rfq_settings' ) || ! defined( 'SNF_RFQ_SETTINGS_OPTION' ) ) {
		wp_die( esc_html__( 'RFQ settings unavailable.', 'super-ninety-four' ) );
	}

	$current   = snf_get_rfq_settings();
	$audiences = function_exists( 'snf_rfq_audience_choices' ) ? snf_rfq_audience_choices() : array();
	$audience  = isset( $_POST['default_audience'] ) ? sanitize_key( wp_unslash( $_POST['default_audience'] ) ) : $current['default_audience'];

	$current['enabled']            = isset( $_POST['enabled'] ) ? 1 : 0;
	$current['members_only']       = isset( $_POST['members_only'] ) ? 1 : 0;
	$current['require_company']    = isset( $_POST['require_company'] ) ? 1 : 0;
	$current['require_product']    = isset( $_POST['require_product'] ) ? 1 : 0;
	$current['max_message_length'] = isset( $_POST['max_message_length'] ) ? max( 200, min( 20000, absint( $_POST['max_message_length'] ) ) ) : 4000;
	$current['default_audience']   = isset( $audiences[ $audience ] ) ? $audience : 'public';
	$current['notify_email']       = isset( $_POST['notify_email'] ) ? sanitize_email( wp_unslash( $_POST['notify_email'] ) ) : '';
	$current['form_title']         = isset( $_POST['form_title'] ) ? sanitize_text_field( wp_unslash( $_POST['form_title'] ) ) : $current['form_title'];
	$current['form_intro']         = isset( $_POST['form_intro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['form_intro'] ) ) : '';
	$current['success_message']    = isset( $_POST['success_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['success_message'] ) ) : $current['success_message'];

	if ( '' === $current['form_title'] ) {
		$current['form_title'] = __( 'Request a quote', 'super-ninety-four' );
	}
	if ( '' === $current['success_message'] ) {
		$current['success_message'] = __( 'Request received. Concierge will follow up.', 'super-ninety-four' );
	}

	update_option( SNF_RFQ_SETTINGS_OPTION, $current, false );

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-rfqs&updated=1' ) );
	exit;
}
add_action( 'admin_post_snf_rfq_save_settings', 'snf_admin_handle_rfq_settings_save' );

/**
 * Save critical settings (Mapbox).
 */
function snf_admin_handle_critical_save() {
	if ( ! isset( $_POST['snf_club_critical_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_club_critical', 'snf_club_critical_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$token = isset( $_POST['snf_mapbox_token'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_mapbox_token'] ) ) : '';
	update_option( 'snf_mapbox_token', $token, false );

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-settings&updated=1' ) );
	exit;
}
add_action( 'admin_post_snf_club_save_critical', 'snf_admin_handle_critical_save' );

/**
 * Save X Ads pixel settings.
 */
function snf_admin_handle_x_ads_pixel_save() {
	if ( ! isset( $_POST['snf_club_x_ads_pixel_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_club_x_ads_pixel', 'snf_club_x_ads_pixel_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$current = function_exists( 'snf_get_x_ads_pixel_settings' )
		? snf_get_x_ads_pixel_settings()
		: array();

	$next = array(
		'enabled'            => ! empty( $_POST['snf_x_ads_enabled'] ) ? 'yes' : 'no',
		'pixel_id'           => 'pp813',
		'purchase_event'     => isset( $_POST['snf_x_ads_purchase_event'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_x_ads_purchase_event'] ) ) : ( $current['purchase_event'] ?? '' ),
		'signup_event'       => isset( $_POST['snf_x_ads_signup_event'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_x_ads_signup_event'] ) ) : ( $current['signup_event'] ?? '' ),
		'add_to_cart_event'  => isset( $current['add_to_cart_event'] ) ? (string) $current['add_to_cart_event'] : '',
		'checkout_event'     => isset( $_POST['snf_x_ads_checkout_event'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_x_ads_checkout_event'] ) ) : ( $current['checkout_event'] ?? 'rjdo4' ),
		'complete_reg_event' => isset( $current['complete_reg_event'] ) ? (string) $current['complete_reg_event'] : '',
	);

	if ( defined( 'SNF_X_ADS_PIXEL_OPTION' ) ) {
		update_option( SNF_X_ADS_PIXEL_OPTION, $next, false );
	} else {
		update_option( 'snf_x_ads_pixel_settings', $next, false );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-settings&updated=1' ) );
	exit;
}
add_action( 'admin_post_snf_club_save_x_ads_pixel', 'snf_admin_handle_x_ads_pixel_save' );

/**
 * Re-probe Stripe / Supabase / Mapbox health.
 */
function snf_admin_handle_ops_refresh_health() {
	if ( ! isset( $_POST['snf_ops_refresh_health_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_ops_refresh_health', 'snf_ops_refresh_health_nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$probe = function_exists( 'snf_stripe_refresh_api_health' ) ? snf_stripe_refresh_api_health( true ) : '';
	$page  = snf_admin_ops_redirect_page( 'snf-stripe' );
	wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&updated=1' . $probe ) );
	exit;
}
add_action( 'admin_post_snf_ops_refresh_health', 'snf_admin_handle_ops_refresh_health' );

/**
 * Force-refresh Supabase catalog cache (and update health).
 */
function snf_admin_handle_ops_refresh_catalog() {
	if ( ! isset( $_POST['snf_ops_refresh_catalog_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_ops_refresh_catalog', 'snf_ops_refresh_catalog_nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	delete_transient( 'snf_supabase_stripe_catalog_v1' );
	$probe = function_exists( 'snf_stripe_refresh_api_health' ) ? snf_stripe_refresh_api_health( true ) : '&supabase_probe=fail';
	$page  = snf_admin_ops_redirect_page( 'snf-stripe' );
	wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&updated=1' . $probe ) );
	exit;
}
add_action( 'admin_post_snf_ops_refresh_catalog', 'snf_admin_handle_ops_refresh_catalog' );

/**
 * Force-ensure membership Woo products.
 */
function snf_admin_handle_ops_ensure_membership_products() {
	if ( ! isset( $_POST['snf_ops_ensure_membership_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_ops_ensure_membership', 'snf_ops_ensure_membership_nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	if ( function_exists( 'snf_ensure_membership_products' ) ) {
		snf_ensure_membership_products( true );
	}
	if ( function_exists( 'snf_ensure_application_product' ) ) {
		// Clear one-shot so missing application SKU can be created on older installs.
		delete_option( 'snf_membership_products_seeded_v2' );
		snf_ensure_application_product();
	}
	if ( function_exists( 'snf_dedupe_membership_products' ) ) {
		snf_dedupe_membership_products( true );
	}

	$page = snf_admin_ops_redirect_page( 'snf-club-settings' );
	wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&products_ok=membership' ) );
	exit;
}
add_action( 'admin_post_snf_ops_ensure_membership_products', 'snf_admin_handle_ops_ensure_membership_products' );

/**
 * Sync membership catalog to Stripe Products/Prices (active mode keys).
 */
function snf_admin_handle_ops_sync_stripe_membership_catalog() {
	if ( ! isset( $_POST['snf_ops_sync_stripe_membership_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_ops_sync_stripe_membership', 'snf_ops_sync_stripe_membership_nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$flag = 'fail';
	if ( function_exists( 'snf_ensure_membership_products' ) ) {
		snf_ensure_membership_products( true );
	}
	if ( function_exists( 'snf_stripe_sync_membership_catalog' ) ) {
		$result = snf_stripe_sync_membership_catalog();
		$flag   = ( ! empty( $result['ok'] ) ) ? 'ok' : 'fail';
		if ( ! empty( $result['errors'] ) && is_array( $result['errors'] ) ) {
			set_transient(
				'snf_ops_stripe_catalog_errors_' . get_current_user_id(),
				array_slice( array_map( 'strval', $result['errors'] ), 0, 8 ),
				MINUTE_IN_SECONDS * 5
			);
		}
	}

	$page = snf_admin_ops_redirect_page( 'snf-stripe' );
	wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&stripe_catalog=' . $flag ) );
	exit;
}
add_action( 'admin_post_snf_ops_sync_stripe_membership_catalog', 'snf_admin_handle_ops_sync_stripe_membership_catalog' );

/**
 * Force-ensure reciprocal Woo products.
 */
function snf_admin_handle_ops_ensure_reciprocal_products() {
	if ( ! isset( $_POST['snf_ops_ensure_reciprocal_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_ops_ensure_reciprocal', 'snf_ops_ensure_reciprocal_nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	if ( function_exists( 'snf_ensure_reciprocal_program_products' ) ) {
		snf_ensure_reciprocal_program_products( true );
	}

	$page = snf_admin_ops_redirect_page( 'snf-club-settings' );
	wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&products_ok=reciprocal' ) );
	exit;
}
add_action( 'admin_post_snf_ops_ensure_reciprocal_products', 'snf_admin_handle_ops_ensure_reciprocal_products' );

/**
 * Lookup Stripe subscriptions by email via Supabase.
 */
function snf_admin_handle_ops_subscription_lookup() {
	if ( ! isset( $_POST['snf_ops_subscription_lookup_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_ops_subscription_lookup', 'snf_ops_subscription_lookup_nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$email = isset( $_POST['lookup_email'] ) ? sanitize_email( wp_unslash( (string) $_POST['lookup_email'] ) ) : '';
	$user  = get_current_user_id();
	$page  = snf_admin_ops_redirect_page( 'snf-stripe' );

	set_transient( 'snf_admin_lookup_email_' . $user, $email, 10 * MINUTE_IN_SECONDS );
	delete_transient( 'snf_admin_lookup_rows_' . $user );
	delete_transient( 'snf_admin_lookup_error_' . $user );

	if ( ! function_exists( 'snf_supabase_get_subscriptions_by_email' ) ) {
		set_transient( 'snf_admin_lookup_error_' . $user, __( 'Subscription lookup helper is unavailable.', 'super-ninety-four' ), 10 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&lookup=fail' ) );
		exit;
	}

	$rows = snf_supabase_get_subscriptions_by_email( $email );
	if ( is_wp_error( $rows ) ) {
		set_transient( 'snf_admin_lookup_error_' . $user, $rows->get_error_message(), 10 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&lookup=fail' ) );
		exit;
	}

	if ( ! $rows ) {
		wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&lookup=empty' ) );
		exit;
	}

	set_transient( 'snf_admin_lookup_rows_' . $user, $rows, 10 * MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&lookup=ok' ) );
	exit;
}
add_action( 'admin_post_snf_ops_subscription_lookup', 'snf_admin_handle_ops_subscription_lookup' );

/**
 * Run club bootstrap from Critical settings.
 */
function snf_admin_handle_ops_club_bootstrap() {
	if ( ! isset( $_POST['snf_ops_club_bootstrap_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_ops_club_bootstrap', 'snf_ops_club_bootstrap_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	snf_admin_run_club_bootstrap();
	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-settings&bootstrapped=1' ) );
	exit;
}
add_action( 'admin_post_snf_ops_club_bootstrap', 'snf_admin_handle_ops_club_bootstrap' );

/**
 * Flush rewrites from critical settings.
 */
function snf_admin_handle_flush_rewrites() {
	if ( ! isset( $_POST['snf_club_flush_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_club_flush', 'snf_club_flush_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	flush_rewrite_rules( false );
	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-settings&flushed=1' ) );
	exit;
}
add_action( 'admin_post_snf_club_flush_rewrites', 'snf_admin_handle_flush_rewrites' );

/**
 * Reset Global Styles from critical settings.
 */
function snf_admin_handle_reset_styles() {
	if ( ! isset( $_POST['snf_club_reset_styles_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_club_reset_styles', 'snf_club_reset_styles_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$stylesheet = get_stylesheet();
	$template   = get_template();
	$posts      = get_posts(
		array(
			'post_type'      => 'wp_global_styles',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		)
	);

	foreach ( $posts as $post ) {
		$name = (string) $post->post_name;
		if (
			false !== strpos( $name, $stylesheet )
			|| false !== strpos( $name, $template )
			|| false !== strpos( $name, 'super-ninety-four' )
		) {
			wp_delete_post( (int) $post->ID, true );
		}
	}

	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-settings&styles_reset=1' ) );
	exit;
}
add_action( 'admin_post_snf_club_reset_styles', 'snf_admin_handle_reset_styles' );

/**
 * Circle visibility meta box.
 */
function snf_admin_circle_meta_boxes() {
	add_meta_box(
		'snf_circle_visibility',
		__( 'Circle access', 'super-ninety-four' ),
		'snf_admin_render_circle_visibility_box',
		'snf_circle',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes_snf_circle', 'snf_admin_circle_meta_boxes' );

/**
 * Render circle visibility box.
 *
 * @param WP_Post $post Post.
 */
function snf_admin_render_circle_visibility_box( $post ) {
	wp_nonce_field( 'snf_circle_visibility', 'snf_circle_visibility_nonce' );
	$value = (string) get_post_meta( $post->ID, '_snf_circle_visibility', true );
	if ( '' === $value ) {
		$settings = snf_get_circle_settings();
		$value    = (string) $settings['default_visibility'];
	}
	?>
	<p>
		<label for="snf_circle_visibility"><strong><?php esc_html_e( 'Visibility', 'super-ninety-four' ); ?></strong></label>
	</p>
	<select name="snf_circle_visibility" id="snf_circle_visibility" style="width:100%">
		<?php foreach ( snf_circle_visibility_choices() as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Save circle visibility.
 *
 * @param int $post_id Post ID.
 */
function snf_admin_save_circle_visibility( $post_id ) {
	if ( ! isset( $_POST['snf_circle_visibility_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snf_circle_visibility_nonce'] ) ), 'snf_circle_visibility' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$choices = snf_circle_visibility_choices();
	$value   = isset( $_POST['snf_circle_visibility'] ) ? sanitize_key( wp_unslash( $_POST['snf_circle_visibility'] ) ) : 'members';
	if ( ! isset( $choices[ $value ] ) ) {
		$value = 'members';
	}
	update_post_meta( $post_id, '_snf_circle_visibility', $value );
}
add_action( 'save_post_snf_circle', 'snf_admin_save_circle_visibility' );

/**
 * Default visibility when publishing a new circle.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 * @param bool    $update  Whether update.
 */
function snf_admin_circle_set_default_visibility( $post_id, $post, $update ) {
	if ( $update || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! $post || 'snf_circle' !== $post->post_type ) {
		return;
	}
	if ( get_post_meta( $post_id, '_snf_circle_visibility', true ) ) {
		return;
	}
	$settings = snf_get_circle_settings();
	update_post_meta( $post_id, '_snf_circle_visibility', $settings['default_visibility'] );
}
add_action( 'wp_insert_post', 'snf_admin_circle_set_default_visibility', 20, 3 );

/**
 * List-table columns for key CPTs.
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function snf_admin_event_columns( $columns ) {
	$columns['snf_attendance'] = __( 'Attendance', 'super-ninety-four' );
	$columns['snf_live']       = __( 'Live', 'super-ninety-four' );
	return $columns;
}
add_filter( 'manage_event_posts_columns', 'snf_admin_event_columns' );

/**
 * Drop club taxonomies from the WooCommerce products list.
 *
 * Those taxonomies also attach to products. The products table is
 * table-layout:fixed and already full, so the extra columns collapse to
 * 0 width and the header wraps one letter per line.
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function snf_admin_product_list_columns( $columns ) {
	unset(
		$columns['taxonomy-snf_event_experience'],
		$columns['taxonomy-snf_ticket_addon_kind'],
		$columns['taxonomy-snf_broadcast_format']
	);

	return $columns;
}
add_filter( 'manage_product_posts_columns', 'snf_admin_product_list_columns', 99 );

/**
 * @param string $column  Column.
 * @param int    $post_id Post ID.
 */
function snf_admin_event_column_content( $column, $post_id ) {
	if ( 'snf_attendance' === $column ) {
		$mode  = (string) get_post_meta( $post_id, '_snf_attendance_mode', true );
		$modes = function_exists( 'snf_event_attendance_modes' ) ? snf_event_attendance_modes() : array();
		echo esc_html( $modes[ $mode ] ?? ( $mode ?: '—' ) );
	}
	if ( 'snf_live' === $column ) {
		$status = (string) get_post_meta( $post_id, '_snf_live_status', true );
		echo esc_html( $status ? $status : '—' );
	}
}
add_action( 'manage_event_posts_custom_column', 'snf_admin_event_column_content', 10, 2 );

/**
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function snf_admin_circle_columns( $columns ) {
	$columns['snf_visibility'] = __( 'Visibility', 'super-ninety-four' );
	$columns['snf_members']    = __( 'Members', 'super-ninety-four' );
	return $columns;
}
add_filter( 'manage_snf_circle_posts_columns', 'snf_admin_circle_columns' );

/**
 * @param string $column  Column.
 * @param int    $post_id Post ID.
 */
function snf_admin_circle_column_content( $column, $post_id ) {
	if ( 'snf_visibility' === $column ) {
		$vis     = (string) get_post_meta( $post_id, '_snf_circle_visibility', true );
		$choices = snf_circle_visibility_choices();
		echo esc_html( $choices[ $vis ] ?? ( $vis ?: '—' ) );
	}
	if ( 'snf_members' === $column && function_exists( 'snf_community_members_table' ) ) {
		global $wpdb;
		$table = snf_community_members_table();
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE circle_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $post_id
			)
		);
		echo esc_html( (string) $count );
	}
}
add_action( 'manage_snf_circle_posts_custom_column', 'snf_admin_circle_column_content', 10, 2 );

/**
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function snf_admin_video_columns( $columns ) {
	$columns['snf_access'] = __( 'Access', 'super-ninety-four' );
	return $columns;
}
add_filter( 'manage_snf_video_posts_columns', 'snf_admin_video_columns' );
add_filter( 'manage_snf_audio_posts_columns', 'snf_admin_video_columns' );

/**
 * @param string $column  Column.
 * @param int    $post_id Post ID.
 */
function snf_admin_media_column_content( $column, $post_id ) {
	if ( 'snf_access' !== $column ) {
		return;
	}
	$access = (string) get_post_meta( $post_id, '_snf_media_access', true );
	if ( ! $access ) {
		$access = (string) get_post_meta( $post_id, '_snf_access', true );
	}
	$levels = function_exists( 'snf_media_access_levels' ) ? snf_media_access_levels() : array();
	echo esc_html( $levels[ $access ] ?? ( $access ?: '—' ) );
}
add_action( 'manage_snf_video_posts_custom_column', 'snf_admin_media_column_content', 10, 2 );
add_action( 'manage_snf_audio_posts_custom_column', 'snf_admin_media_column_content', 10, 2 );
