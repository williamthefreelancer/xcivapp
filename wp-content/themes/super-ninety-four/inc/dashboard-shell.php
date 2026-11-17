<?php
/**
 * Hybrid member dashboard: sidebar nav, Woo sliders, BP/Woo destinations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Member dashboard URL (WooCommerce My Account home).
 *
 * @return string
 */
function snf_get_dashboard_url() {
	return snf_get_woo_account_url();
}

/**
 * WooCommerce My Account URL (optional endpoint).
 *
 * @param string $endpoint Optional endpoint slug (orders, edit-account, etc).
 * @return string
 */
function snf_get_woo_account_url( $endpoint = '' ) {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return home_url( '/my-account/' );
	}

	$url = wc_get_page_permalink( 'myaccount' );
	if ( ! $url ) {
		$url = home_url( '/my-account/' );
	}

	$endpoint = sanitize_title( (string) $endpoint );
	if ( '' === $endpoint || 'dashboard' === $endpoint ) {
		return $url;
	}

	return wc_get_endpoint_url( $endpoint, '', $url );
}

/**
 * Messages hub URL — always the theme Messages page (BuddyPress-backed UI stays here).
 *
 * @return string
 */
function snf_get_bp_messages_url() {
	return snf_get_page_url( 'messages' );
}

/**
 * BuddyPress profile URL for the current (or given) user.
 *
 * @param int $user_id Optional user ID.
 * @return string
 */
function snf_get_bp_profile_url( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$ctx     = snf_get_hub_member_context();

	if ( $user_id && ! empty( $ctx['profile_url'] ) && (int) $ctx['user_id'] === $user_id ) {
		return $ctx['profile_url'];
	}

	if ( snf_buddypress_active() && $user_id && function_exists( 'bp_members_get_user_url' ) ) {
		return bp_members_get_user_url( $user_id );
	}

	return snf_get_woo_account_url( 'edit-account' );
}

/**
 * Hybrid sidebar / account navigation.
 *
 * @return array<int, array{slug:string,label:string,url:string,group?:string}>
 */
function snf_get_account_hub_nav() {
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

	$items = array(
		array(
			'slug'  => 'dashboard',
			'label' => __( 'Dashboard', 'super-ninety-four' ),
			'url'   => snf_get_dashboard_url(),
			'group' => 'home',
		),
		array(
			'slug'  => 'listings',
			'label' => __( 'Listings', 'super-ninety-four' ),
			'url'   => $shop_url ? $shop_url : home_url( '/shop/' ),
			'group' => 'catalog',
		),
		array(
			'slug'  => 'orders',
			'label' => __( 'Orders', 'super-ninety-four' ),
			'url'   => snf_get_woo_account_url( 'orders' ),
			'group' => 'commerce',
		),
		array(
			'slug'  => 'tickets',
			'label' => __( 'Tickets', 'super-ninety-four' ),
			'url'   => snf_get_woo_account_url( 'tickets' ),
			'group' => 'commerce',
		),
		array(
			'slug'  => 'membership',
			'label' => __( 'Membership', 'super-ninety-four' ),
			'url'   => snf_get_woo_account_url( 'membership' ),
			'group' => 'commerce',
		),
		array(
			'slug'  => 'billing',
			'label' => __( 'Billing', 'super-ninety-four' ),
			'url'   => snf_get_woo_account_url( 'edit-address' ),
			'group' => 'commerce',
		),
		array(
			'slug'  => 'messages',
			'label' => __( 'Messages', 'super-ninety-four' ),
			'url'   => snf_get_bp_messages_url(),
			'group' => 'social',
		),
		array(
			'slug'  => 'profile',
			'label' => __( 'Profile', 'super-ninety-four' ),
			'url'   => snf_get_bp_profile_url(),
			'group' => 'social',
		),
		array(
			'slug'  => 'apply',
			'label' => __( 'Apply', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'apply' ),
			'group' => 'funnel',
		),
	);

	/**
	 * Filter hybrid dashboard / account sidebar items.
	 *
	 * @param array $items Nav items.
	 */
	return apply_filters( 'snf_account_hub_nav', $items );
}

/**
 * Resolve the active sidebar slug for the current request.
 *
 * @return string
 */
function snf_get_account_nav_current_slug() {
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		return 'listings';
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		global $wp;
		if ( isset( $wp->query_vars['membership'] ) ) {
			return 'membership';
		}
		if ( isset( $wp->query_vars['tickets'] ) ) {
			return 'tickets';
		}
		if ( ! empty( $wp->query_vars['orders'] ) || isset( $wp->query_vars['view-order'] ) ) {
			return 'orders';
		}
		if ( isset( $wp->query_vars['edit-address'] ) || isset( $wp->query_vars['payment-methods'] ) ) {
			return 'billing';
		}
		if ( isset( $wp->query_vars['edit-account'] ) ) {
			return 'profile';
		}
		// Account home is the community dashboard.
		return 'dashboard';
	}

	if ( snf_buddypress_active() ) {
		if ( function_exists( 'bp_is_messages_component' ) && bp_is_messages_component() ) {
			return 'messages';
		}
		if ( function_exists( 'bp_is_user' ) && bp_is_user() && function_exists( 'bp_is_user_profile' ) && bp_is_user_profile() ) {
			return 'profile';
		}
		if ( function_exists( 'bp_is_user' ) && bp_is_user() ) {
			return 'profile';
		}
	}

	if ( is_page( 'apply' ) || is_page_template( 'page-templates/apply.php' ) ) {
		return 'apply';
	}

	if ( is_page( 'billing-details' ) ) {
		return 'billing';
	}

	if ( is_page( 'messages' ) ) {
		return 'messages';
	}

	return '';
}

/**
 * Build dashboard cards from Woo products in a category.
 *
 * @param string $category_slug Product category slug.
 * @param int    $limit         Max cards.
 * @return array<int, array<string, string>>
 */
function snf_get_woo_product_cards_by_category( $category_slug, $limit = 12 ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array();
	}

	$query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $limit,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => array( $category_slug ),
				),
			),
		)
	);

	$cards = array();
	foreach ( $query->posts as $post ) {
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			continue;
		}

		$location = (string) get_post_meta( $post->ID, '_snf_location', true );
		$when     = (string) get_post_meta( $post->ID, '_snf_when', true );
		$image_id = $product->get_image_id();
		$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';

		$card_url = get_permalink( $post->ID );
		if ( function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( $post->ID ) ) {
			$card_url = snf_get_workshop_landing_url();
		}

		$cards[] = array(
			'title' => $product->get_name(),
			'meta'  => trim( implode( ' · ', array_filter( array( $location, $when ) ) ) ),
			'url'   => $card_url,
			'image' => $image ? $image : '',
		);
	}
	wp_reset_postdata();

	return $cards;
}

/**
 * Services listing cards for My Account (Woo products when present, else theme inquiry cards).
 *
 * @return array<int, array<string, string>>
 */
function snf_get_dashboard_services_listing_cards() {
	$woo_cards = snf_get_woo_product_cards_by_category( 'services' );
	if ( $woo_cards ) {
		return $woo_cards;
	}

	return function_exists( 'snf_get_dashboard_service_inquiry_cards' )
		? snf_get_dashboard_service_inquiry_cards()
		: array();
}

/**
 * Dashboard listing slider sections (Woo Events / Services).
 *
 * @return array<int, array<string, mixed>>
 */
function snf_get_dashboard_event_listings() {
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$events_url = $shop_url ? add_query_arg( 'product_cat', 'events', $shop_url ) : home_url( '/shop/?product_cat=events' );
	$services_url = $shop_url ? add_query_arg( 'product_cat', 'services', $shop_url ) : home_url( '/shop/?product_cat=services' );

	// Prefer pretty category links when available.
	$events_term = get_term_by( 'slug', 'events', 'product_cat' );
	if ( $events_term && ! is_wp_error( $events_term ) ) {
		$link = get_term_link( $events_term );
		if ( ! is_wp_error( $link ) ) {
			$events_url = $link;
		}
	}
	$services_term = get_term_by( 'slug', 'services', 'product_cat' );
	if ( $services_term && ! is_wp_error( $services_term ) ) {
		$link = get_term_link( $services_term );
		if ( ! is_wp_error( $link ) ) {
			$services_url = $link;
		}
	}

	$reciprocal_url = snf_get_page_url( 'find-other-reciprocal-programs' );

	$listings = array(
		array(
			'id'    => 'events',
			'title' => __( 'Events', 'super-ninety-four' ),
			'url'   => $events_url,
			'bp'    => 'events',
			'cards' => snf_get_woo_product_cards_by_category( 'events' ),
		),
		array(
			'id'    => 'reciprocal',
			'title' => __( 'Reciprocal programs', 'super-ninety-four' ),
			'url'   => $reciprocal_url,
			'bp'    => 'friends',
			'cards' => snf_get_dashboard_reciprocal_cards(),
		),
		array(
			'id'    => 'services',
			'title' => __( 'Services', 'super-ninety-four' ),
			'url'   => $services_url,
			'bp'    => 'activity',
			'cards' => snf_get_dashboard_services_listing_cards(),
		),
	);

	/**
	 * Filter dashboard listing slider sections.
	 *
	 * @param array $listings Listing row definitions.
	 */
	return apply_filters( 'snf_dashboard_event_listings', $listings );
}

/**
 * Redirect obsolete theme hubs into Woo / BuddyPress destinations.
 */
function snf_redirect_obsolete_account_hubs() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( is_page( 'billing-details' ) ) {
		$target = snf_get_woo_account_url( 'edit-address' );
		if ( $target && untrailingslashit( $target ) !== untrailingslashit( snf_get_page_url( 'billing-details' ) ) ) {
			wp_safe_redirect( $target, 301 );
			exit;
		}
	}

	// Legacy Community Dashboard page → My Account home.
	if ( is_page( 'dashboard' ) || is_page_template( 'page-templates/dashboard.php' ) ) {
		$target = snf_get_dashboard_url();
		if ( $target ) {
			wp_safe_redirect( $target, 301 );
			exit;
		}
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( $request_path && preg_match( '#/dashboard/?$#', $request_path ) ) {
		$target = snf_get_dashboard_url();
		if ( $target ) {
			wp_safe_redirect( $target, 301 );
			exit;
		}
	}

	// Keep BuddyPress private messages on the theme Messages hub.
	if (
		snf_buddypress_active()
		&& function_exists( 'bp_is_messages_component' )
		&& bp_is_messages_component()
		&& ! is_page( 'messages' )
	) {
		wp_safe_redirect( snf_get_page_url( 'messages' ), 302 );
		exit;
	}

	if ( is_page( 'member-login' ) && is_user_logged_in() ) {
		wp_safe_redirect( snf_get_dashboard_url(), 302 );
		exit;
	}
}
add_action( 'template_redirect', 'snf_redirect_obsolete_account_hubs', 20 );
