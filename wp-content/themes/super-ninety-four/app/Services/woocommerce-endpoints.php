<?php
/**
 * Custom WooCommerce My Account endpoints: membership + tickets.
 * Pulls live data from membership plugins when present; falls back to
 * Super Ninety Four club roles + plan catalog + Woo orders.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registered custom account endpoints.
 *
 * @return array<string, string> endpoint => label
 */
function snf_get_woo_custom_endpoints() {
	return array(
		'membership' => __( 'Membership', 'super-ninety-four' ),
		'tickets'    => __( 'Tickets', 'super-ninety-four' ),
	);
}

/**
 * Register rewrite endpoints.
 */
function snf_register_woo_custom_endpoints() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	foreach ( array_keys( snf_get_woo_custom_endpoints() ) as $endpoint ) {
		add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES );
	}
}
add_action( 'init', 'snf_register_woo_custom_endpoints', 20 );

/**
 * Expose endpoints to Woo query vars.
 *
 * @param array<string, string> $vars Query vars.
 * @return array<string, string>
 */
function snf_woo_custom_endpoint_query_vars( $vars ) {
	foreach ( array_keys( snf_get_woo_custom_endpoints() ) as $endpoint ) {
		$vars[ $endpoint ] = $endpoint;
	}
	return $vars;
}
add_filter( 'woocommerce_get_query_vars', 'snf_woo_custom_endpoint_query_vars' );

/**
 * Keep endpoints listed for URL helpers (theme sidebar replaces Woo nav UI).
 *
 * @param array<string, string> $items Menu items.
 * @return array<string, string>
 */
function snf_woo_custom_endpoint_menu_items( $items ) {
	$custom = snf_get_woo_custom_endpoints();
	$out    = array();

	foreach ( $items as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'dashboard' === $key ) {
			$out['membership'] = $custom['membership'];
			$out['tickets']    = $custom['tickets'];
		}
	}

	foreach ( $custom as $key => $label ) {
		if ( ! isset( $out[ $key ] ) ) {
			$out[ $key ] = $label;
		}
	}

	return $out;
}
add_filter( 'woocommerce_account_menu_items', 'snf_woo_custom_endpoint_menu_items', 40 );

/**
 * Flush rewrites when endpoint pack version changes.
 */
function snf_maybe_flush_woo_endpoint_rewrites() {
	$version = '2.13.0-endpoints';
	if ( get_option( 'snf_woo_endpoints_version' ) === $version ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'snf_woo_endpoints_version', $version, false );
}
add_action( 'init', 'snf_maybe_flush_woo_endpoint_rewrites', 99 );

/**
 * Detect which membership engine is providing data.
 *
 * @return string plugin slug|theme
 */
function snf_detect_membership_engine() {
	if ( function_exists( 'wc_memberships_get_user_memberships' ) ) {
		return 'woocommerce-memberships';
	}
	if ( class_exists( 'MeprUser', false ) ) {
		return 'memberpress';
	}
	if ( function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
		return 'pmpro';
	}
	if ( class_exists( 'WC_Subscriptions', false ) || function_exists( 'wcs_get_users_subscriptions' ) ) {
		return 'woocommerce-subscriptions';
	}
	return 'theme';
}

/**
 * Normalize a membership plan/status card.
 *
 * @param array<string, mixed> $row Raw row.
 * @return array<string, string>
 */
function snf_normalize_membership_row( $row ) {
	return array(
		'eyebrow' => isset( $row['eyebrow'] ) ? (string) $row['eyebrow'] : __( 'Plan', 'super-ninety-four' ),
		'title'   => isset( $row['title'] ) ? (string) $row['title'] : '',
		'meta'    => isset( $row['meta'] ) ? (string) $row['meta'] : '',
		'status'  => isset( $row['status'] ) ? (string) $row['status'] : '',
		'tone'    => isset( $row['tone'] ) ? (string) $row['tone'] : 'neutral',
		'url'     => isset( $row['url'] ) ? (string) $row['url'] : '',
		'action'  => isset( $row['action'] ) ? (string) $row['action'] : '',
		'image'   => isset( $row['image'] ) ? (string) $row['image'] : '',
	);
}

/**
 * Membership rows from WooCommerce Memberships.
 *
 * @param int $user_id User ID.
 * @return array<int, array<string, string>>
 */
function snf_membership_rows_from_woo_memberships( $user_id ) {
	$rows = array();
	if ( ! $user_id || ! function_exists( 'wc_memberships_get_user_memberships' ) ) {
		return $rows;
	}

	$memberships = wc_memberships_get_user_memberships( $user_id );
	foreach ( (array) $memberships as $membership ) {
		$plan   = $membership->get_plan();
		$status = $membership->get_status();
		$tone   = in_array( $status, array( 'active', 'complimentary', 'pending' ), true ) ? 'success' : 'warn';
		$end    = $membership->get_end_date( 'timestamp' );

		$rows[] = snf_normalize_membership_row(
			array(
				'eyebrow' => __( 'Woo membership', 'super-ninety-four' ),
				'title'   => $plan ? $plan->get_name() : __( 'Membership', 'super-ninety-four' ),
				'meta'    => $end
					? sprintf(
						/* translators: %s: end date */
						__( 'Renews / ends %s', 'super-ninety-four' ),
						date_i18n( get_option( 'date_format' ), $end )
					)
					: __( 'No end date', 'super-ninety-four' ),
				'status'  => ucwords( str_replace( '-', ' ', (string) $status ) ),
				'tone'    => $tone,
				'url'     => snf_get_woo_account_url( 'members-area' ),
				'action'  => __( 'Manage', 'super-ninety-four' ),
			)
		);
	}

	return $rows;
}

/**
 * Membership rows from MemberPress.
 *
 * @param int $user_id User ID.
 * @return array<int, array<string, string>>
 */
function snf_membership_rows_from_memberpress( $user_id ) {
	$rows = array();
	if ( ! $user_id || ! class_exists( 'MeprUser', false ) ) {
		return $rows;
	}

	$user         = new MeprUser( $user_id );
	$active_prods = method_exists( $user, 'active_product_subscriptions' )
		? (array) $user->active_product_subscriptions( 'ids' )
		: array();

	foreach ( $active_prods as $product_id ) {
		$product = get_post( (int) $product_id );
		if ( ! $product ) {
			continue;
		}
		$rows[] = snf_normalize_membership_row(
			array(
				'eyebrow' => __( 'MemberPress', 'super-ninety-four' ),
				'title'   => get_the_title( $product ),
				'meta'    => __( 'Active subscription', 'super-ninety-four' ),
				'status'  => __( 'Active', 'super-ninety-four' ),
				'tone'    => 'success',
				'url'     => home_url( '/account/' ),
				'action'  => __( 'Manage', 'super-ninety-four' ),
			)
		);
	}

	return $rows;
}

/**
 * Membership rows from Paid Memberships Pro.
 *
 * @param int $user_id User ID.
 * @return array<int, array<string, string>>
 */
function snf_membership_rows_from_pmpro( $user_id ) {
	$rows = array();
	if ( ! $user_id || ! function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
		return $rows;
	}

	$levels = pmpro_getMembershipLevelsForUser( $user_id );
	foreach ( (array) $levels as $level ) {
		$end = ! empty( $level->enddate ) ? (int) $level->enddate : 0;
		$rows[] = snf_normalize_membership_row(
			array(
				'eyebrow' => __( 'PMPro', 'super-ninety-four' ),
				'title'   => isset( $level->name ) ? (string) $level->name : __( 'Membership', 'super-ninety-four' ),
				'meta'    => $end
					? sprintf(
						/* translators: %s: end date */
						__( 'Through %s', 'super-ninety-four' ),
						date_i18n( get_option( 'date_format' ), $end )
					)
					: __( 'Ongoing', 'super-ninety-four' ),
				'status'  => __( 'Active', 'super-ninety-four' ),
				'tone'    => 'success',
				'url'     => function_exists( 'pmpro_url' ) ? pmpro_url( 'account' ) : snf_get_woo_account_url( 'membership' ),
				'action'  => __( 'Manage', 'super-ninety-four' ),
			)
		);
	}

	return $rows;
}

/**
 * Membership-ish rows from WooCommerce Subscriptions.
 *
 * @param int $user_id User ID.
 * @return array<int, array<string, string>>
 */
function snf_membership_rows_from_subscriptions( $user_id ) {
	$rows = array();
	if ( ! $user_id || ! function_exists( 'wcs_get_users_subscriptions' ) ) {
		return $rows;
	}

	foreach ( wcs_get_users_subscriptions( $user_id ) as $subscription ) {
		$status = $subscription->get_status();
		$tone   = in_array( $status, array( 'active', 'pending-cancel' ), true ) ? 'success' : 'warn';
		$names  = array();
		foreach ( $subscription->get_items() as $item ) {
			$names[] = $item->get_name();
		}

		$rows[] = snf_normalize_membership_row(
			array(
				'eyebrow' => __( 'Subscription', 'super-ninety-four' ),
				'title'   => $names ? implode( ', ', array_slice( $names, 0, 2 ) ) : sprintf(
					/* translators: %s: subscription id */
					__( 'Subscription #%s', 'super-ninety-four' ),
					$subscription->get_id()
				),
				'meta'    => wp_strip_all_tags( $subscription->get_formatted_order_total() ),
				'status'  => wc_get_order_status_name( $status ),
				'tone'    => $tone,
				'url'     => $subscription->get_view_order_url(),
				'action'  => __( 'View', 'super-ninety-four' ),
			)
		);
	}

	return $rows;
}

/**
 * Theme club-role + live membership meta (Content Control gates access).
 *
 * @param int $user_id User ID.
 * @return array<int, array<string, string>>
 */
function snf_membership_rows_from_theme( $user_id ) {
	$rows     = array();
	$billing  = snf_get_billing_hub_data();
	$plan     = isset( $billing['plan'] ) ? $billing['plan'] : array();
	$user     = $user_id ? get_userdata( $user_id ) : null;
	$roles    = $user ? (array) $user->roles : array();
	$club     = snf_get_club_roles();
	$mem      = function_exists( 'snf_get_user_membership' ) ? snf_get_user_membership( $user_id ) : array();

	$role_label = __( 'Guest', 'super-ninety-four' );
	$tone       = 'neutral';
	foreach ( array( 'snf_staff', 'snf_reciprocal', 'snf_member', 'snf_applicant' ) as $role ) {
		if ( in_array( $role, $roles, true ) && isset( $club[ $role ] ) ) {
			$role_label = $club[ $role ]['name'];
			$tone       = in_array( $role, array( 'snf_member', 'snf_reciprocal', 'snf_staff' ), true ) ? 'success' : 'warn';
			break;
		}
	}
	if ( in_array( 'administrator', $roles, true ) ) {
		$role_label = __( 'Administrator', 'super-ninety-four' );
		$tone       = 'success';
	}

	$rows[] = snf_normalize_membership_row(
		array(
			'eyebrow' => __( 'Club status', 'super-ninety-four' ),
			'title'   => $role_label,
			'meta'    => __( 'Roles + Content Control. Subscriptions via theme memberships (no paid plugin).', 'super-ninety-four' ),
			'status'  => __( 'Access', 'super-ninety-four' ),
			'tone'    => $tone,
			'url'     => snf_get_page_url( 'apply' ),
			'action'  => __( 'Apply / upgrade', 'super-ninety-four' ),
		)
	);

	$plan_meta = isset( $plan['price'] ) ? (string) $plan['price'] : '';
	if ( ! empty( $mem['expires_at'] ) ) {
		$plan_meta = trim(
			$plan_meta . ' · ' . sprintf(
				/* translators: %s: date */
				__( 'Ends %s', 'super-ninety-four' ),
				date_i18n( get_option( 'date_format' ), strtotime( (string) $mem['expires_at'] ) )
			),
			' ·'
		);
	}

	$rows[] = snf_normalize_membership_row(
		array(
			'eyebrow' => __( 'Current plan', 'super-ninety-four' ),
			'title'   => isset( $plan['name'] ) ? $plan['name'] : __( 'Membership plan', 'super-ninety-four' ),
			'meta'    => $plan_meta,
			'status'  => isset( $plan['status'] ) ? $plan['status'] : __( 'Catalog', 'super-ninety-four' ),
			'tone'    => ! empty( $mem['is_active'] ) ? 'success' : 'info',
			'url'     => snf_get_woo_account_url( 'orders' ),
			'action'  => __( 'Orders', 'super-ninety-four' ),
		)
	);

	$rows[] = snf_normalize_membership_row(
		array(
			'eyebrow' => __( 'Initiation', 'super-ninety-four' ),
			'title'   => isset( $billing['initiation']['label'] ) ? $billing['initiation']['label'] : __( 'Initiation fee', 'super-ninety-four' ),
			'meta'    => isset( $billing['initiation']['amount'] ) ? $billing['initiation']['amount'] : '',
			'status'  => ! empty( $mem['initiation_paid'] ) ? __( 'Paid', 'super-ninety-four' ) : __( 'Required', 'super-ninety-four' ),
			'tone'    => ! empty( $mem['initiation_paid'] ) ? 'success' : 'neutral',
			'url'     => snf_get_woo_account_url( 'orders' ),
			'action'  => __( 'Statements', 'super-ninety-four' ),
		)
	);

	$catalog = snf_get_membership_plans();
	foreach ( array_slice( $catalog['plans'], 0, 4 ) as $catalog_plan ) {
		$product = get_page_by_path( 'membership-' . $catalog_plan['slug'], OBJECT, 'product' );
		$url     = $product ? get_permalink( $product ) : snf_get_page_url( 'apply' );
		$rows[]  = snf_normalize_membership_row(
			array(
				'eyebrow' => __( 'Available plan', 'super-ninety-four' ),
				'title'   => $catalog_plan['name'],
				'meta'    => $catalog_plan['price'] . $catalog_plan['cadence'] . ' · ' . $catalog_plan['term'],
				'status'  => __( 'Catalog', 'super-ninety-four' ),
				'tone'    => 'neutral',
				'url'     => $url,
				'action'  => __( 'Choose plan', 'super-ninety-four' ),
			)
		);
	}

	// Append live reciprocal program subscriptions when that module is loaded.
	if ( function_exists( 'snf_membership_rows_from_reciprocal_subscriptions' ) ) {
		foreach ( snf_membership_rows_from_reciprocal_subscriptions( $user_id ) as $reciprocal_row ) {
			$rows[] = snf_normalize_membership_row( $reciprocal_row );
		}
	}

	return $rows;
}

/**
 * Unified membership rows for the account endpoint.
 *
 * @param int $user_id Optional user ID.
 * @return array<string, mixed>
 */
function snf_get_membership_endpoint_data( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$engine  = snf_detect_membership_engine();
	$rows    = array();

	switch ( $engine ) {
		case 'woocommerce-memberships':
			$rows = snf_membership_rows_from_woo_memberships( $user_id );
			break;
		case 'memberpress':
			$rows = snf_membership_rows_from_memberpress( $user_id );
			break;
		case 'pmpro':
			$rows = snf_membership_rows_from_pmpro( $user_id );
			break;
		case 'woocommerce-subscriptions':
			$rows = snf_membership_rows_from_subscriptions( $user_id );
			break;
	}

	// Always merge theme club context so Content Control roles stay visible.
	$theme_rows = snf_membership_rows_from_theme( $user_id );
	if ( 'theme' === $engine ) {
		$rows = $theme_rows;
	} else {
		$rows = array_merge( array_slice( $theme_rows, 0, 1 ), $rows );
	}

	$engine_labels = array(
		'woocommerce-memberships'  => __( 'WooCommerce Memberships', 'super-ninety-four' ),
		'memberpress'              => __( 'MemberPress', 'super-ninety-four' ),
		'pmpro'                    => __( 'Paid Memberships Pro', 'super-ninety-four' ),
		'woocommerce-subscriptions'=> __( 'WooCommerce Subscriptions', 'super-ninety-four' ),
		'theme'                    => __( 'Theme memberships (roles + user meta + Woo orders)', 'super-ninety-four' ),
	);

	return apply_filters(
		'snf_membership_endpoint_data',
		array(
			'engine'       => $engine,
			'engine_label' => isset( $engine_labels[ $engine ] ) ? $engine_labels[ $engine ] : $engine,
			'rows'         => $rows,
		),
		$user_id
	);
}

/**
 * Membership endpoint panel definition.
 *
 * @return array<string, mixed>
 */
function snf_get_membership_endpoint_panel() {
	$data = snf_get_membership_endpoint_data();

	return apply_filters(
		'snf_membership_endpoint_panel',
		array(
			'id'       => 'membership',
			'title'    => __( 'Membership', 'super-ninety-four' ),
			'lede'     => sprintf(
				/* translators: %s: membership engine label */
				__( 'Plan status via %s — ready for Figma.', 'super-ninety-four' ),
				$data['engine_label']
			),
			'view_all' => snf_get_page_url( 'apply' ),
			'cards'    => array_map( 'snf_normalize_panel_card', $data['rows'] ),
		)
	);
}

/**
 * Tickets endpoint panel (orders / events tickets).
 *
 * @return array<string, mixed>
 */
function snf_get_tickets_endpoint_panel() {
	$panel = snf_get_dashboard_orders_panel();
	$panel['id']    = 'tickets';
	$panel['title'] = __( 'Tickets', 'super-ninety-four' );
	$panel['lede']  = __( 'Event tickets and immersion admissions from your orders.', 'super-ninety-four' );

	return apply_filters( 'snf_tickets_endpoint_panel', $panel );
}

/**
 * Render membership endpoint.
 */
function snf_render_membership_endpoint() {
	wc_get_template( 'myaccount/membership.php' );
}
add_action( 'woocommerce_account_membership_endpoint', 'snf_render_membership_endpoint' );

/**
 * Render tickets endpoint.
 */
function snf_render_tickets_endpoint() {
	wc_get_template( 'myaccount/tickets.php' );
}
add_action( 'woocommerce_account_tickets_endpoint', 'snf_render_tickets_endpoint' );
