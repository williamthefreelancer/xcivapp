<?php
/**
 * Dashboard card panels: billing, event schedules, orders/tickets.
 * Figma-ready shells — swap copy/layout from designs without changing wiring.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize a panel card definition.
 *
 * @param array<string, mixed> $card Raw card.
 * @return array<string, string>
 */
function snf_normalize_panel_card( $card ) {
	return array(
		'eyebrow' => isset( $card['eyebrow'] ) ? (string) $card['eyebrow'] : '',
		'title'   => isset( $card['title'] ) ? (string) $card['title'] : '',
		'meta'    => isset( $card['meta'] ) ? (string) $card['meta'] : '',
		'status'  => isset( $card['status'] ) ? (string) $card['status'] : '',
		'tone'    => isset( $card['tone'] ) ? (string) $card['tone'] : 'neutral',
		'url'     => isset( $card['url'] ) ? (string) $card['url'] : '',
		'action'  => isset( $card['action'] ) ? (string) $card['action'] : '',
		'image'   => isset( $card['image'] ) ? (string) $card['image'] : '',
	);
}

/**
 * Billing panel payload for dashboard / account surfaces.
 *
 * @return array<string, mixed>
 */
/**
 * Woo address cards for the billing / edit-address panel.
 *
 * @return array<int, array<string, string>>
 */
function snf_get_billing_address_panel_cards() {
	$cards = array();

	if ( ! function_exists( 'wc_get_account_formatted_address' ) ) {
		return $cards;
	}

	$types = array(
		'billing' => __( 'Billing address', 'super-ninety-four' ),
	);

	if ( function_exists( 'wc_ship_to_billing_address_only' ) && function_exists( 'wc_shipping_enabled' )
		&& ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
		$types['shipping'] = __( 'Shipping address', 'super-ninety-four' );
	}

	foreach ( $types as $name => $label ) {
		$formatted = wc_get_account_formatted_address( $name );
		$plain     = $formatted ? wp_strip_all_tags( str_replace( array( '<br/>', '<br />', '<br>' ), ', ', $formatted ) ) : '';

		$cards[] = array(
			'eyebrow' => __( 'Address', 'super-ninety-four' ),
			'title'   => $label,
			'meta'    => $plain ? $plain : __( 'Not set yet — add this for checkout defaults.', 'super-ninety-four' ),
			'status'  => $plain ? __( 'On file', 'super-ninety-four' ) : __( 'Missing', 'super-ninety-four' ),
			'tone'    => $plain ? 'success' : 'warn',
			'url'     => function_exists( 'wc_get_endpoint_url' ) ? wc_get_endpoint_url( 'edit-address', $name, snf_get_woo_account_url() ) : snf_get_woo_account_url( 'edit-address' ),
			'action'  => $plain ? __( 'Edit address', 'super-ninety-four' ) : __( 'Add address', 'super-ninety-four' ),
		);
	}

	return $cards;
}

function snf_get_dashboard_billing_panel() {
	$billing = snf_get_billing_hub_data();
	$plan    = isset( $billing['plan'] ) ? $billing['plan'] : array();

	$cards = array(
		array(
			'eyebrow' => __( 'Membership', 'super-ninety-four' ),
			'title'   => isset( $plan['name'] ) ? $plan['name'] : __( 'Current plan', 'super-ninety-four' ),
			'meta'    => isset( $plan['price'] ) ? $plan['price'] : '',
			'status'  => isset( $plan['status'] ) ? $plan['status'] : '',
			'tone'    => 'success',
			'url'     => snf_get_woo_account_url( 'edit-address' ),
			'action'  => __( 'Manage billing', 'super-ninety-four' ),
		),
		array(
			'eyebrow' => __( 'Initiation', 'super-ninety-four' ),
			'title'   => isset( $billing['initiation']['label'] ) ? $billing['initiation']['label'] : __( 'Initiation fee', 'super-ninety-four' ),
			'meta'    => isset( $billing['initiation']['amount'] ) ? $billing['initiation']['amount'] : '',
			'status'  => __( 'Required', 'super-ninety-four' ),
			'tone'    => 'neutral',
			'url'     => snf_get_woo_account_url( 'orders' ),
			'action'  => __( 'View statement', 'super-ninety-four' ),
		),
		array(
			'eyebrow' => __( 'Payment method', 'super-ninety-four' ),
			'title'   => isset( $billing['methods'][0]['label'] ) ? $billing['methods'][0]['label'] : __( 'Add a card', 'super-ninety-four' ),
			'meta'    => isset( $billing['methods'][0]['meta'] ) ? $billing['methods'][0]['meta'] : '',
			'status'  => __( 'Primary', 'super-ninety-four' ),
			'tone'    => 'info',
			'url'     => snf_get_woo_account_url( 'payment-methods' ),
			'action'  => __( 'Update method', 'super-ninety-four' ),
		),
	);

	$cards = array_merge( $cards, snf_get_billing_address_panel_cards() );

	/**
	 * Filter billing dashboard panel.
	 *
	 * @param array $panel Panel definition.
	 */
	return apply_filters(
		'snf_dashboard_billing_panel',
		array(
			'id'       => 'billing',
			'title'    => __( 'Billing', 'super-ninety-four' ),
			'lede'     => __( 'Dues, initiation, payment methods, and addresses.', 'super-ninety-four' ),
			'view_all' => snf_get_woo_account_url( 'edit-address' ),
			'cards'    => array_map( 'snf_normalize_panel_card', $cards ),
		)
	);
}

/**
 * Panels for a Woo My Account endpoint (Figma shells).
 *
 * @param string $endpoint Endpoint slug: orders|billing|dashboard.
 * @return array<int, array<string, mixed>>
 */
function snf_get_account_endpoint_panels( $endpoint = 'dashboard' ) {
	switch ( $endpoint ) {
		case 'orders':
			$panels = array( snf_get_dashboard_orders_panel() );
			break;
		case 'billing':
		case 'edit-address':
			$panels = array( snf_get_dashboard_billing_panel() );
			break;
		default:
			$panels = snf_get_dashboard_design_panels();
			break;
	}

	/**
	 * Filter panels for a My Account endpoint.
	 *
	 * @param array  $panels   Panel definitions.
	 * @param string $endpoint Endpoint slug.
	 */
	return apply_filters( 'snf_account_endpoint_panels', $panels, $endpoint );
}

/**
 * Upcoming gatherings panel for My Account / headless dashboard.
 *
 * @return array<string, mixed>
 */
function snf_get_dashboard_upcoming_gatherings_panel() {
	$source = function_exists( 'snf_get_upcoming_gathering_cards' )
		? snf_get_upcoming_gathering_cards()
		: array();
	$cards  = array();

	foreach ( $source as $card ) {
		$url = isset( $card['url'] ) ? (string) $card['url'] : '';
		if ( $url && 0 === strpos( $url, '/' ) ) {
			$url = home_url( $url );
		}

		$meta_bits = array_filter(
			array(
				isset( $card['price'] ) ? (string) $card['price'] : '',
				isset( $card['body'] ) ? (string) $card['body'] : '',
			)
		);

		$cards[] = array(
			'eyebrow' => __( 'Gathering', 'super-ninety-four' ),
			'title'   => isset( $card['title'] ) ? (string) $card['title'] : '',
			'meta'    => implode( ' · ', $meta_bits ),
			'status'  => __( 'Upcoming', 'super-ninety-four' ),
			'tone'    => 'info',
			'url'     => $url,
			'action'  => isset( $card['cta'] ) && $card['cta']
				? (string) $card['cta']
				: __( 'View event', 'super-ninety-four' ),
			'image'   => isset( $card['image'] ) ? (string) $card['image'] : '',
		);
	}

	if ( ! $cards ) {
		$cards[] = array(
			'eyebrow' => __( 'Gathering', 'super-ninety-four' ),
			'title'   => __( 'No gatherings posted', 'super-ninety-four' ),
			'meta'    => __( 'Upcoming circuit seats will appear here when published.', 'super-ninety-four' ),
			'status'  => __( 'Empty', 'super-ninety-four' ),
			'tone'    => 'neutral',
			'url'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
			'action'  => __( 'Browse listings', 'super-ninety-four' ),
		);
	}

	$view_all = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'events' ) : home_url( '/events/' );
	if ( ! $view_all ) {
		$view_all = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	}

	/**
	 * Filter upcoming gatherings dashboard panel.
	 *
	 * @param array $panel Panel definition.
	 */
	return apply_filters(
		'snf_dashboard_upcoming_gatherings_panel',
		array(
			'id'       => 'upcoming-gatherings',
			'title'    => __( 'Upcoming gatherings', 'super-ninety-four' ),
			'lede'     => __( 'Three seats on the circuit. Prices as posted.', 'super-ninety-four' ),
			'view_all' => $view_all,
			'cards'    => array_map( 'snf_normalize_panel_card', $cards ),
		)
	);
}

/**
 * Event schedule panel — upcoming dated listings.
 *
 * @return array<string, mixed>
 */
function snf_get_dashboard_schedules_panel() {
	$cards = array();

	if ( class_exists( 'WooCommerce' ) ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 6,
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
			$product  = wc_get_product( $post->ID );
			$when     = (string) get_post_meta( $post->ID, '_snf_when', true );
			$location = (string) get_post_meta( $post->ID, '_snf_location', true );
			$image_id = $product ? $product->get_image_id() : 0;
			$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

			$cards[] = array(
				'eyebrow' => __( 'Schedule', 'super-ninety-four' ),
				'title'   => get_the_title( $post ),
				'meta'    => trim( implode( ' · ', array_filter( array( $when, $location ) ) ) ),
				'status'  => $when ? __( 'Upcoming', 'super-ninety-four' ) : __( 'TBA', 'super-ninety-four' ),
				'tone'    => $when ? 'info' : 'neutral',
				'url'     => get_permalink( $post ),
				'action'  => __( 'View schedule', 'super-ninety-four' ),
				'image'   => $image ? $image : '',
			);
		}
		wp_reset_postdata();
	}

	if ( ! $cards ) {
		$cards[] = array(
			'eyebrow' => __( 'Schedule', 'super-ninety-four' ),
			'title'   => __( 'No dated events yet', 'super-ninety-four' ),
			'meta'    => __( 'Publish Events products with a When field to populate this panel.', 'super-ninety-four' ),
			'status'  => __( 'Empty', 'super-ninety-four' ),
			'tone'    => 'neutral',
			'url'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
			'action'  => __( 'Browse listings', 'super-ninety-four' ),
		);
	}

	$events_term = get_term_by( 'slug', 'events', 'product_cat' );
	$view_all    = ( $events_term && ! is_wp_error( $events_term ) ) ? get_term_link( $events_term ) : home_url( '/shop/' );
	if ( is_wp_error( $view_all ) ) {
		$view_all = home_url( '/shop/' );
	}

	/**
	 * Filter event schedules dashboard panel.
	 *
	 * @param array $panel Panel definition.
	 */
	return apply_filters(
		'snf_dashboard_schedules_panel',
		array(
			'id'       => 'schedules',
			'title'    => __( 'Event schedules', 'super-ninety-four' ),
			'lede'     => __( 'Upcoming dates, windows, and immersion timings.', 'super-ninety-four' ),
			'view_all' => $view_all,
			'cards'    => array_map( 'snf_normalize_panel_card', $cards ),
		)
	);
}

/**
 * Orders panel — my events & tickets from WooCommerce orders.
 *
 * @return array<string, mixed>
 */
function snf_get_dashboard_orders_panel() {
	$cards   = array();
	$user_id = get_current_user_id();

	if ( $user_id && function_exists( 'wc_get_orders' ) ) {
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 8,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'status'      => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending' ),
			)
		);

		foreach ( $orders as $order ) {
			$item_names = array();
			foreach ( $order->get_items() as $item ) {
				$item_names[] = $item->get_name();
			}
			$title = $item_names ? implode( ', ', array_slice( $item_names, 0, 2 ) ) : sprintf(
				/* translators: %s: order number */
				__( 'Order #%s', 'super-ninety-four' ),
				$order->get_order_number()
			);

			$status = wc_get_order_status_name( $order->get_status() );
			$tone   = 'neutral';
			if ( in_array( $order->get_status(), array( 'completed', 'processing' ), true ) ) {
				$tone = 'success';
			} elseif ( in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ) {
				$tone = 'warn';
			}

			$cards[] = array(
				'eyebrow' => sprintf(
					/* translators: %s: order number */
					__( 'Ticket · #%s', 'super-ninety-four' ),
					$order->get_order_number()
				),
				'title'   => $title,
				'meta'    => sprintf(
					/* translators: 1: order total, 2: order date */
					__( '%1$s · %2$s', 'super-ninety-four' ),
					wp_strip_all_tags( $order->get_formatted_order_total() ),
					wc_format_datetime( $order->get_date_created() )
				),
				'status'  => $status,
				'tone'    => $tone,
				'url'     => $order->get_view_order_url(),
				'action'  => __( 'View ticket', 'super-ninety-four' ),
			);
		}
	}

	if ( ! $cards ) {
		$cards = array(
			array(
				'eyebrow' => __( 'My events', 'super-ninety-four' ),
				'title'   => __( 'No tickets yet', 'super-ninety-four' ),
				'meta'    => __( 'Orders and event tickets will appear here after checkout.', 'super-ninety-four' ),
				'status'  => __( 'Empty', 'super-ninety-four' ),
				'tone'    => 'neutral',
				'url'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
				'action'  => __( 'Find events', 'super-ninety-four' ),
			),
			array(
				'eyebrow' => __( 'Orders', 'super-ninety-four' ),
				'title'   => __( 'Order history', 'super-ninety-four' ),
				'meta'    => __( 'Open My Account for receipts and refunds.', 'super-ninety-four' ),
				'status'  => __( 'Account', 'super-ninety-four' ),
				'tone'    => 'info',
				'url'     => snf_get_woo_account_url( 'orders' ),
				'action'  => __( 'Open orders', 'super-ninety-four' ),
			),
		);
	}

	/**
	 * Filter orders / tickets dashboard panel.
	 *
	 * @param array $panel Panel definition.
	 */
	return apply_filters(
		'snf_dashboard_orders_panel',
		array(
			'id'       => 'orders',
			'title'    => __( 'My events & tickets', 'super-ninety-four' ),
			'lede'     => __( 'Purchased immersions, tickets, and order status.', 'super-ninety-four' ),
			'view_all' => snf_get_woo_account_url( 'orders' ),
			'cards'    => array_map( 'snf_normalize_panel_card', $cards ),
		)
	);
}

/**
 * All dashboard design panels in display order.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_dashboard_design_panels() {
	$panels = array(
		snf_get_dashboard_upcoming_gatherings_panel(),
		snf_get_dashboard_billing_panel(),
		snf_get_dashboard_schedules_panel(),
		snf_get_dashboard_orders_panel(),
	);

	/**
	 * Filter the full set of dashboard design panels.
	 *
	 * @param array $panels Panels.
	 */
	return apply_filters( 'snf_dashboard_design_panels', $panels );
}
