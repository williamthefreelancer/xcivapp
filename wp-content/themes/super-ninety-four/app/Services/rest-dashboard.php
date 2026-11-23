<?php
/**
 * REST API for Nuxt hybrid dashboards, events, and funnel shells.
 *
 * Namespace: snf/v1
 *
 * @package SuperNinetyFour
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permission: logged-in member.
 *
 * @return bool|WP_Error
 */
function snf_rest_require_login() {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'snf_rest_unauthorized', __( 'Authentication required.', 'super-ninety-four' ), array( 'status' => 401 ) );
	}
	return true;
}

/**
 * Permission: club admin.
 *
 * @return bool|WP_Error
 */
function snf_rest_require_club() {
	$login = snf_rest_require_login();
	if ( true !== $login ) {
		return $login;
	}
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_posts' ) ) {
		return new WP_Error( 'snf_rest_forbidden', __( 'Club capability required.', 'super-ninety-four' ), array( 'status' => 403 ) );
	}
	return true;
}

/**
 * Whether current user can manage club UI.
 *
 * @return bool
 */
function snf_user_can_manage_club() {
	return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
}

/**
 * Register dashboard / events / funnel routes.
 */
function snf_rest_dashboard_register_routes() {
	$ns = 'snf/v1';

	register_rest_route(
		$ns,
		'/me',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_me',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/account/nav',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_account_nav',
			'permission_callback' => 'snf_rest_require_login',
		)
	);

	register_rest_route(
		$ns,
		'/account/dashboard',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_account_dashboard',
			'permission_callback' => 'snf_rest_require_login',
		)
	);

	register_rest_route(
		$ns,
		'/account/orders',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_account_orders',
			'permission_callback' => 'snf_rest_require_login',
		)
	);

	register_rest_route(
		$ns,
		'/account/orders/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_account_order',
			'permission_callback' => 'snf_rest_require_login',
			'args'                => array(
				'id' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		$ns,
		'/account/tickets',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_account_tickets',
			'permission_callback' => 'snf_rest_require_login',
		)
	);

	register_rest_route(
		$ns,
		'/account/membership',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_account_membership',
			'permission_callback' => 'snf_rest_require_login',
		)
	);

	register_rest_route(
		$ns,
		'/account/addresses',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_account_addresses',
			'permission_callback' => 'snf_rest_require_login',
		)
	);

	register_rest_route(
		$ns,
		'/club/nav',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_club_nav',
			'permission_callback' => 'snf_rest_require_club',
		)
	);

	register_rest_route(
		$ns,
		'/club/overview',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_club_overview',
			'permission_callback' => 'snf_rest_require_club',
		)
	);

	register_rest_route(
		$ns,
		'/club/desk/(?P<desk>[a-z0-9_-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_club_desk',
			'permission_callback' => 'snf_rest_require_club',
		)
	);

	register_rest_route(
		$ns,
		'/events',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_events_list',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/events/(?P<slug>[a-z0-9\-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_event_single',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$ns,
		'/funnel/(?P<step>[a-z]+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'snf_rest_funnel_step',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'snf_rest_dashboard_register_routes' );

/**
 * GET /me
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function snf_rest_me( WP_REST_Request $request ) {
	unset( $request );
	$config_wp = home_url( '/' );
	$login     = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'member-login' ) : wp_login_url();
	$checkout  = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
	$cart      = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );

	// Headless: prefer Nuxt commerce + login paths when bridge is on.
	if ( function_exists( 'snf_nuxt_bridge_enabled' ) && snf_nuxt_bridge_enabled() && function_exists( 'snf_nuxt_app_url' ) ) {
		$nuxt = snf_nuxt_app_url();
		if ( $nuxt ) {
			$login    = untrailingslashit( $nuxt ) . '/login';
			$cart     = untrailingslashit( $nuxt ) . '/cart';
			$checkout = untrailingslashit( $nuxt ) . '/checkout';
		}
	}

	if ( ! is_user_logged_in() ) {
		return rest_ensure_response(
			array(
				'id'            => 0,
				'name'          => '',
				'email'         => '',
				'roles'         => array(),
				'membership'    => __( 'Guest', 'super-ninety-four' ),
				'isAdmin'       => false,
				'canManageClub' => false,
				'loginUrl'      => $login ? $login : wp_login_url(),
				'logoutUrl'     => wp_logout_url( $config_wp ),
				'checkoutUrl'   => $checkout,
				'cartUrl'       => $cart,
				'wpAdminUrl'    => admin_url(),
			)
		);
	}

	$user = wp_get_current_user();
	$hub  = function_exists( 'snf_get_hub_member_context' ) ? snf_get_hub_member_context() : array();

	return rest_ensure_response(
		array(
			'id'            => (int) $user->ID,
			'name'          => $user->display_name ? $user->display_name : $user->user_login,
			'email'         => $user->user_email,
			'roles'         => array_values( (array) $user->roles ),
			'membership'    => isset( $hub['membership'] ) ? $hub['membership'] : __( 'Member', 'super-ninety-four' ),
			'isAdmin'       => user_can( $user, 'manage_options' ),
			'canManageClub' => snf_user_can_manage_club(),
			'loginUrl'      => $login ? $login : wp_login_url(),
			'logoutUrl'     => wp_logout_url( $config_wp ),
			'checkoutUrl'   => $checkout,
			'cartUrl'       => $cart,
			'wpAdminUrl'    => admin_url(),
			'token'         => function_exists( 'snf_nuxt_issue_token' ) ? snf_nuxt_issue_token( (int) $user->ID ) : '',
		)
	);
}

/**
 * GET /account/nav
 *
 * @return WP_REST_Response
 */
function snf_rest_account_nav() {
	$items = function_exists( 'snf_get_account_hub_nav' ) ? snf_get_account_hub_nav() : array();
	return rest_ensure_response( array( 'items' => $items ) );
}

/**
 * GET /account/dashboard
 *
 * @return WP_REST_Response
 */
function snf_rest_account_dashboard() {
	$panels = function_exists( 'snf_get_account_endpoint_panels' )
		? snf_get_account_endpoint_panels( 'dashboard' )
		: array();
	return rest_ensure_response( array( 'panels' => $panels ) );
}

/**
 * Serialize a Woo order for JSON.
 *
 * @param WC_Order $order Order.
 * @return array<string, mixed>
 */
function snf_rest_serialize_order( $order ) {
	$items = array();
	foreach ( $order->get_items() as $item ) {
		$items[] = array(
			'name'  => $item->get_name(),
			'qty'   => $item->get_quantity(),
			'total' => wp_strip_all_tags( $order->get_formatted_line_subtotal( $item ) ),
		);
	}

	return array(
		'id'          => $order->get_id(),
		'number'      => $order->get_order_number(),
		'status'      => wc_get_order_status_name( $order->get_status() ),
		'date'        => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '',
		'total'       => $order->get_formatted_order_total(),
		'url'         => $order->get_view_order_url(),
		'checkoutUrl' => $order->needs_payment() ? $order->get_checkout_payment_url() : '',
		'items'       => $items,
	);
}

/**
 * GET /account/orders
 *
 * @return WP_REST_Response
 */
function snf_rest_account_orders() {
	$panels = function_exists( 'snf_get_account_endpoint_panels' )
		? snf_get_account_endpoint_panels( 'orders' )
		: array();

	$orders = array();
	if ( function_exists( 'wc_get_orders' ) ) {
		$list = wc_get_orders(
			array(
				'customer_id' => get_current_user_id(),
				'limit'       => 20,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);
		foreach ( $list as $order ) {
			$orders[] = snf_rest_serialize_order( $order );
		}
	}

	return rest_ensure_response(
		array(
			'panels' => $panels,
			'orders' => $orders,
		)
	);
}

/**
 * GET /account/orders/{id}
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_account_order( WP_REST_Request $request ) {
	$id = absint( $request['id'] );
	if ( ! function_exists( 'wc_get_order' ) ) {
		return new WP_Error( 'snf_no_woo', __( 'WooCommerce required.', 'super-ninety-four' ), array( 'status' => 500 ) );
	}
	$order = wc_get_order( $id );
	if ( ! $order || (int) $order->get_user_id() !== get_current_user_id() ) {
		return new WP_Error( 'snf_not_found', __( 'Order not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}
	return rest_ensure_response( snf_rest_serialize_order( $order ) );
}

/**
 * GET /account/tickets
 *
 * @return WP_REST_Response
 */
function snf_rest_account_tickets() {
	$panel = function_exists( 'snf_get_tickets_endpoint_panel' )
		? snf_get_tickets_endpoint_panel()
		: ( function_exists( 'snf_get_dashboard_orders_panel' ) ? snf_get_dashboard_orders_panel() : array() );
	return rest_ensure_response( array( 'panels' => $panel ? array( $panel ) : array() ) );
}

/**
 * GET /account/membership
 *
 * @return WP_REST_Response
 */
function snf_rest_account_membership() {
	$data  = function_exists( 'snf_get_membership_endpoint_data' ) ? snf_get_membership_endpoint_data() : array();
	$panel = function_exists( 'snf_get_membership_endpoint_panel' ) ? snf_get_membership_endpoint_panel() : array();
	return rest_ensure_response(
		array(
			'engine'       => isset( $data['engine'] ) ? $data['engine'] : 'theme',
			'engine_label' => isset( $data['engine_label'] ) ? $data['engine_label'] : '',
			'panels'       => $panel ? array( $panel ) : array(),
		)
	);
}

/**
 * GET /account/addresses
 *
 * @return WP_REST_Response
 */
function snf_rest_account_addresses() {
	$panels = function_exists( 'snf_get_account_endpoint_panels' )
		? snf_get_account_endpoint_panels( 'billing' )
		: array();

	$addresses = array();
	$customer  = function_exists( 'WC' ) && WC()->customer ? WC()->customer : null;
	if ( ! $customer && function_exists( 'wc_get_customer' ) ) {
		// Fallback: load via customer factory if available in future versions.
	}

	$user_id = get_current_user_id();
	foreach ( array( 'billing', 'shipping' ) as $type ) {
		$formatted = '';
		if ( function_exists( 'wc_get_account_formatted_address' ) ) {
			$formatted = wc_get_account_formatted_address( $type );
		} else {
			$lines = array();
			foreach ( array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ) as $field ) {
				$val = get_user_meta( $user_id, $type . '_' . $field, true );
				if ( $val ) {
					$lines[] = $val;
				}
			}
			$formatted = implode( "\n", $lines );
		}

		$addresses[] = array(
			'type'     => $type,
			'label'    => 'billing' === $type ? __( 'Billing address', 'super-ninety-four' ) : __( 'Shipping address', 'super-ninety-four' ),
			'formatted'=> $formatted,
			'editUrl'  => function_exists( 'wc_get_endpoint_url' )
				? wc_get_endpoint_url( 'edit-address', $type, wc_get_page_permalink( 'myaccount' ) )
				: '',
		);
	}

	return rest_ensure_response(
		array(
			'panels'    => $panels,
			'addresses' => $addresses,
		)
	);
}

/**
 * Rewrite admin desk hrefs to Nuxt paths when possible.
 *
 * @param array<string, mixed> $payload Overview payload.
 * @return array<string, mixed>
 */
function snf_rest_club_enrich_for_nuxt( array $payload ) {
	$map = array(
		'events'     => '/club/events',
		'circles'    => '/club/circles',
		'media'      => '/club/media',
		'newsletter' => '/club/newsletter',
		'rfq'        => '/club/rfq',
		'marketplace'=> '/club/rfq',
		'addons'     => '/club/addons',
	);
	if ( ! empty( $payload['desks'] ) && is_array( $payload['desks'] ) ) {
		foreach ( $payload['desks'] as &$desk ) {
			$id = isset( $desk['id'] ) ? $desk['id'] : '';
			if ( isset( $map[ $id ] ) ) {
				$desk['nuxtPath'] = $map[ $id ];
			}
		}
		unset( $desk );
	}
	return $payload;
}

/**
 * GET /club/nav
 *
 * @return WP_REST_Response
 */
function snf_rest_club_nav() {
	$nav = function_exists( 'snf_admin_vue_nav_items' ) ? snf_admin_vue_nav_items() : array();
	return rest_ensure_response( array( 'nav' => $nav ) );
}

/**
 * GET /club/overview
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function snf_rest_club_overview( WP_REST_Request $request ) {
	$page = sanitize_key( (string) $request->get_param( 'page' ) );
	if ( ! in_array( $page, array( 'overview', 'settings', 'chrome' ), true ) ) {
		$page = 'overview';
	}
	$payload = function_exists( 'snf_admin_vue_bootstrap' )
		? snf_admin_vue_bootstrap( $page )
		: array();
	return rest_ensure_response( snf_rest_club_enrich_for_nuxt( $payload ) );
}

/**
 * GET /club/desk/{desk}
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_club_desk( WP_REST_Request $request ) {
	$desk = sanitize_key( (string) $request['desk'] );
	switch ( $desk ) {
		case 'events':
			return rest_ensure_response(
				array(
					'lede'          => __( 'Manage published events and ticket products.', 'super-ninety-four' ),
					'count'         => function_exists( 'snf_admin_count_posts' ) ? snf_admin_count_posts( 'event' ) : 0,
					'dashboardHref' => admin_url( 'admin.php?page=snf-club-events' ),
					'listHref'      => admin_url( 'edit.php?post_type=event' ),
				)
			);
		case 'circles':
			return rest_ensure_response(
				array(
					'lede'          => __( 'Club circles and reciprocal groups.', 'super-ninety-four' ),
					'count'         => function_exists( 'snf_admin_count_posts' ) ? snf_admin_count_posts( 'snf_circle' ) : 0,
					'dashboardHref' => admin_url( 'admin.php?page=snf-club-circles' ),
					'listHref'      => admin_url( 'edit.php?post_type=snf_circle' ),
				)
			);
		case 'media':
			return rest_ensure_response(
				array(
					'lede'          => __( 'Media library and live rooms.', 'super-ninety-four' ),
					'videos'        => function_exists( 'snf_admin_count_posts' ) ? snf_admin_count_posts( 'snf_video' ) : 0,
					'audio'         => function_exists( 'snf_admin_count_posts' ) ? snf_admin_count_posts( 'snf_audio' ) : 0,
					'dashboardHref' => admin_url( 'admin.php?page=snf-club-media' ),
					'listHref'      => admin_url( 'edit.php?post_type=snf_video' ),
					'nuxtPath'      => '/club/media',
				)
			);
		case 'newsletter':
			return rest_ensure_response(
				array(
					'lede'          => __( 'Video newsletter subscribers and signup health.', 'super-ninety-four' ),
					'count'         => function_exists( 'snf_admin_count_posts' ) ? snf_admin_count_posts( 'snf_subscriber' ) : 0,
					'dashboardHref' => admin_url( 'edit.php?post_type=snf_subscriber' ),
					'listHref'      => admin_url( 'edit.php?post_type=snf_subscriber' ),
					'publicPath'    => '/newsletter',
					'nuxtPath'      => '/club/newsletter',
				)
			);
		case 'rfq':
		case 'marketplace':
			return rest_ensure_response(
				array(
					'lede'          => __( 'Expo marketplace RFQs and org desk.', 'super-ninety-four' ),
					'count'         => function_exists( 'snf_admin_count_posts' ) ? snf_admin_count_posts( 'snf_rfq' ) : 0,
					'dashboardHref' => admin_url( 'edit.php?post_type=snf_rfq' ),
					'listHref'      => admin_url( 'edit.php?post_type=snf_rfq' ),
					'publicPath'    => '/expo',
					'nuxtPath'      => '/club/rfq',
				)
			);
		case 'addons':
			return rest_ensure_response(
				array(
					'lede'          => __( 'Add-ons and circuit extensions managed in WordPress admin.', 'super-ninety-four' ),
					'dashboardHref' => admin_url( 'admin.php?page=snf-club' ),
					'listHref'      => admin_url( 'admin.php?page=snf-club' ),
					'nuxtPath'      => '/club/addons',
				)
			);
		default:
			return new WP_Error( 'snf_unknown_desk', __( 'Unknown desk.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}
}

/**
 * Build event card from product/post.
 *
 * @param WP_Post $post Post.
 * @return array<string, mixed>
 */
function snf_rest_event_card_from_post( WP_Post $post ) {
	$product = ( 'product' === $post->post_type && function_exists( 'wc_get_product' ) )
		? wc_get_product( $post->ID )
		: null;
	$image_id = $product ? $product->get_image_id() : get_post_thumbnail_id( $post );
	$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'medium_large' ) : '';
	$when     = (string) get_post_meta( $post->ID, '_snf_when', true );
	$location = (string) get_post_meta( $post->ID, '_snf_location', true );
	$add      = '';
	if ( $product && $product->is_purchasable() && $product->is_in_stock() && function_exists( 'wc_get_cart_url' ) ) {
		$add = esc_url_raw( add_query_arg( 'add-to-cart', $product->get_id(), wc_get_cart_url() ) );
	}

	$path = '/events/' . $post->post_name;

	return array(
		'id'           => $post->ID,
		'slug'         => $post->post_name,
		'title'        => get_the_title( $post ),
		'when'         => $when,
		'location'     => $location,
		'image'        => $image ? $image : '',
		'path'         => $path,
		'url'          => $path,
		'permalink'    => get_permalink( $post ),
		'addToCartUrl' => $add,
		'checkoutUrl'  => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
		'excerpt'      => wp_kses_post( get_the_excerpt( $post ) ),
		'content'      => wp_kses_post( apply_filters( 'the_content', $post->post_content ) ),
	);
}

/**
 * GET /events
 *
 * @return WP_REST_Response
 */
function snf_rest_events_list() {
	$events = array();

	if ( class_exists( 'WooCommerce' ) ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 24,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'slug',
						'terms'    => array( 'events' ),
					),
				),
			)
		);
		foreach ( $query->posts as $post ) {
			$events[] = snf_rest_event_card_from_post( $post );
		}
		wp_reset_postdata();
	}

	if ( ! $events ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'event',
				'post_status'    => 'publish',
				'posts_per_page' => 24,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		foreach ( $query->posts as $post ) {
			$events[] = snf_rest_event_card_from_post( $post );
		}
		wp_reset_postdata();
	}

	$select = function_exists( 'snf_get_page_url' )
		? snf_get_page_url( 'select-events-by-members' )
		: home_url( '/select-events-by-members/' );

	return rest_ensure_response(
		array(
			'events'          => $events,
			'selectEventsUrl' => $select,
			'checkoutUrl'     => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
		)
	);
}

/**
 * GET /events/{slug}
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_event_single( WP_REST_Request $request ) {
	$slug = sanitize_title( (string) $request['slug'] );
	$post = get_page_by_path( $slug, OBJECT, array( 'product', 'event' ) );
	if ( ! $post ) {
		return new WP_Error( 'snf_not_found', __( 'Event not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}
	return rest_ensure_response( snf_rest_event_card_from_post( $post ) );
}

/**
 * GET /funnel/{step}?order=&key=
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_rest_funnel_step( WP_REST_Request $request ) {
	$step = sanitize_key( (string) $request['step'] );
	if ( ! in_array( $step, array( 'upsell', 'downsell' ), true ) ) {
		$step = 'upsell';
	}

	$order_id = absint( $request->get_param( 'order' ) );
	$key      = sanitize_text_field( (string) $request->get_param( 'key' ) );

	if ( ! $order_id && function_exists( 'snf_funnel_current_context' ) ) {
		// Allow query aliases from classic funnel URLs via request params only.
	}

	if ( ! function_exists( 'snf_funnel_validate_order' ) || ! function_exists( 'snf_get_funnel_config' ) ) {
		return new WP_Error( 'snf_funnel_unavailable', __( 'Funnels unavailable.', 'super-ninety-four' ), array( 'status' => 500 ) );
	}

	$order = snf_funnel_validate_order( $order_id, $key );
	if ( ! $order ) {
		return new WP_Error( 'snf_funnel_invalid', __( 'Invalid funnel order.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}

	$trigger = function_exists( 'snf_funnel_resolve_for_order' ) ? snf_funnel_resolve_for_order( $order ) : null;
	if ( ! $trigger ) {
		$stored = (int) $order->get_meta( '_snf_funnel_trigger_product' );
		if ( $stored > 0 ) {
			$config = snf_get_funnel_config( $stored );
			$trigger = array(
				'product_id' => $stored,
				'config'     => $config,
			);
		}
	}
	if ( ! $trigger ) {
		return new WP_Error( 'snf_funnel_missing', __( 'No funnel for this order.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}

	$config     = $trigger['config'];
	$product_id = function_exists( 'snf_funnel_offer_product_id' )
		? snf_funnel_offer_product_id( $config, $step )
		: 0;
	$product    = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

	$accept = add_query_arg(
		array(
			'snf_funnel_action' => 'accept',
			'snf_order'         => $order->get_id(),
			'snf_key'           => $order->get_order_key(),
			'snf_step'          => $step,
		),
		home_url( '/' )
	);
	$decline = add_query_arg(
		array(
			'snf_funnel_action' => 'decline',
			'snf_order'         => $order->get_id(),
			'snf_key'           => $order->get_order_key(),
			'snf_step'          => $step,
		),
		home_url( '/' )
	);

	$nuxt_downsell = '';
	if ( 'upsell' === $step && function_exists( 'snf_nuxt_app_url' ) && snf_nuxt_app_url() ) {
		$nuxt_downsell = snf_nuxt_app_url() . '/funnel/downsell?' . http_build_query(
			array(
				'order' => $order->get_id(),
				'key'   => $order->get_order_key(),
			)
		);
	}

	return rest_ensure_response(
		array(
			'step'         => $step,
			'title'        => 'upsell' === $step
				? __( 'Add this to your order', 'super-ninety-four' )
				: __( 'One more option', 'super-ninety-four' ),
			'lede'         => __( 'Accept adds the offer and sends you to WooCommerce checkout.', 'super-ninety-four' ),
			'productTitle' => $product ? $product->get_name() : '',
			'price'        => $product ? $product->get_price_html() : '',
			'acceptUrl'    => $accept,
			'declineUrl'   => $decline,
			'acceptLabel'  => __( 'Add & checkout', 'super-ninety-four' ),
			'declineLabel' => __( 'No thanks', 'super-ninety-four' ),
			'thankYouUrl'  => function_exists( 'snf_funnel_finish_url' ) ? $order->get_checkout_order_received_url() : '',
			'downsellUrl'  => $nuxt_downsell,
			'checkoutUrl'  => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
		)
	);
}
