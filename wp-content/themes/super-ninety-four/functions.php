<?php
/**
 * Super Ninety Four theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SNF_VERSION', '2.16.1' );

require_once get_template_directory() . '/inc/account-hub.php';
require_once get_template_directory() . '/inc/memberships.php';
require_once get_template_directory() . '/inc/dashboard-shell.php';
require_once get_template_directory() . '/inc/dashboard-panels.php';
require_once get_template_directory() . '/inc/buddypress-profile.php';
require_once get_template_directory() . '/inc/access-control.php';
require_once get_template_directory() . '/inc/plugin-integrations.php';
require_once get_template_directory() . '/inc/messages.php';
require_once get_template_directory() . '/inc/reciprocal.php';
require_once get_template_directory() . '/inc/membership-subscriptions.php';
require_once get_template_directory() . '/inc/supabase-payments.php';
require_once get_template_directory() . '/inc/stripe.php';
require_once get_template_directory() . '/inc/home.php';
require_once get_template_directory() . '/inc/events.php';
require_once get_template_directory() . '/inc/expo-marketplace.php';
require_once get_template_directory() . '/inc/lead-rider.php';
require_once get_template_directory() . '/inc/woocommerce-serp.php';
require_once get_template_directory() . '/inc/woocommerce-endpoints.php';
require_once get_template_directory() . '/inc/service-quotes.php';

function snf_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary header', 'super-ninety-four' ),
			'account' => __( 'Account dropdown', 'super-ninety-four' ),
		)
	);
}
add_action( 'after_setup_theme', 'snf_setup' );

function snf_register_event_cpt() {
	$labels = array(
		'name'          => __( 'Events', 'super-ninety-four' ),
		'singular_name' => __( 'Event', 'super-ninety-four' ),
		'add_new_item'  => __( 'Add New Event', 'super-ninety-four' ),
		'edit_item'     => __( 'Edit Event', 'super-ninety-four' ),
		'search_items'  => __( 'Search Events', 'super-ninety-four' ),
		'not_found'     => __( 'No events found.', 'super-ninety-four' ),
	);

	register_post_type(
		'event',
		array(
			'labels'       => $labels,
			'public'       => true,
			'has_archive'  => true,
			'rewrite'      => array( 'slug' => 'events' ),
			'menu_icon'    => 'dashicons-calendar-alt',
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		)
	);
}
add_action( 'init', 'snf_register_event_cpt' );

/**
 * Page definitions used across templates and menus.
 */
function snf_get_site_pages() {
	return array(
		'home' => array(
			'title'    => 'Home',
			'template' => 'page-templates/home.php',
		),
		'about' => array(
			'title'    => 'About',
			'template' => 'page-templates/about.php',
		),
		'inquiries' => array(
			'title'    => 'Service Inquiries',
			'template' => 'page-templates/public-content.php',
		),
		'videos-audio' => array(
			'title'    => 'Videos & Audio',
			'template' => 'page-templates/blank-canvas.php',
		),
		'contact' => array(
			'title'    => 'Contact',
			'template' => 'page-templates/public-content.php',
		),
		'newsletter' => array(
			'title'    => 'Newsletter',
			'template' => 'page-templates/blank-canvas.php',
		),
		'find-other-reciprocal-programs' => array(
			'title'    => 'Find Other Reciprocal Programs',
			'template' => 'page-templates/reciprocal-hub.php',
		),
		'messages' => array(
			'title'    => 'Messages',
			'template' => 'page-templates/messages.php',
		),
		'billing-details' => array(
			'title'    => 'Billing Details',
			'template' => 'page-templates/billing-details.php',
		),
		'member-login' => array(
			'title'    => 'Member Login',
			'template' => 'page-templates/member-login.php',
		),
		'apply' => array(
			'title'    => 'Apply',
			'template' => 'page-templates/apply.php',
		),
		'under-consideration' => array(
			'title'    => 'Under Consideration',
			'template' => 'page-templates/under-consideration.php',
		),
		'select-events-by-members' => array(
			'title'    => 'Select Events (Members)',
			'template' => 'page-templates/select-events.php',
		),
		'select-events-by-non-members' => array(
			'title'    => 'Select Events (Non-Members)',
			'template' => 'page-templates/select-events.php',
		),
		'expo' => array(
			'title'    => 'Expo',
			'template' => 'page-templates/expo-marketplace.php',
		),
	);
}

function snf_get_obsolete_page_slugs() {
	return array(
		'dashboard',
		'select-events-members-non-members',
		'select-events-non-members',
		'select-events-by-admission-for-non-members',
		'select-events-members',
		'member-concierge',
		'my-profile',
		'settings',
		'reciprocal-programs',
		'service-inquiries',
		'parking-lot-commercial-grounds-cleaning',
		'professional-line-sitting-service',
		'commercial-janitorial-cleaning-consulting',
		'grounds-litter-pickup',
		'line-sitting-service',
		'courier-service',
		'commercial-janitor-cleaning',
		'event-by-admission',
		'account-settings-for-profile',
		// Legacy page copies of Woo products (products themselves are kept).
		'the-township-adventures-event',
		'the-badlands-bra-private-gathering',
		'the-eight-hundred-plus-journeys-event',
		'the-badlands-bra-party-bus-gathering',
		'the-eight-hundred-plus-journeys',
		'the-lifestyle-concierge-club-workshop',
		'the-clubs-at-back-creek',
		'the-badlands-bra-club',
		'the-clubs-at-wyoma-square',
		'the-city-clubs-at-super-ninety-four',
		'expo-marketplace-by-members',
		'expo-marketplace-by-non-members',
	);
}

function snf_get_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return get_permalink( $page );
	}
	return home_url( '/' . trailingslashit( $slug ) );
}

function snf_ensure_site_pages() {
	$site_pages = snf_get_site_pages();

	foreach ( snf_get_obsolete_page_slugs() as $obsolete_slug ) {
		if ( isset( $site_pages[ $obsolete_slug ] ) ) {
			continue;
		}
		$obsolete = get_page_by_path( $obsolete_slug );
		if ( $obsolete ) {
			wp_delete_post( $obsolete->ID, true );
		}
	}

	foreach ( $site_pages as $slug => $config ) {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			if ( $existing->post_title !== $config['title'] ) {
				wp_update_post(
					array(
						'ID'         => $existing->ID,
						'post_title' => $config['title'],
					)
				);
			}
			update_post_meta( $existing->ID, '_wp_page_template', $config['template'] );
			continue;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'  => $config['title'],
				'post_name'   => $slug,
				'post_status' => 'publish',
				'post_type'   => 'page',
				'post_content'=> '',
			),
			true
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_wp_page_template', $config['template'] );
		}
	}

	snf_ensure_home_is_front_page();
}

/**
 * Keep the Home page assigned as the static front page.
 */
function snf_ensure_home_is_front_page() {
	$home = get_page_by_path( 'home' );
	if ( ! $home ) {
		return;
	}

	if ( get_option( 'show_on_front' ) !== 'page' ) {
		update_option( 'show_on_front', 'page' );
	}

	if ( (int) get_option( 'page_on_front' ) !== (int) $home->ID ) {
		update_option( 'page_on_front', (int) $home->ID );
	}
}
add_action( 'after_switch_theme', 'snf_ensure_site_pages' );
add_action( 'init', 'snf_ensure_site_pages', 20 );

function snf_scripts() {
	wp_enqueue_style(
		'snf-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Lato:wght@700&family=League+Gothic&family=Manrope:wght@400;500;600&family=Open+Sans:wght@600;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style(
		'snf-main',
		get_template_directory_uri() . '/assets/css/main.css',
		array(),
		SNF_VERSION
	);
	wp_enqueue_script(
		'snf-header',
		get_template_directory_uri() . '/assets/js/header.js',
		array(),
		SNF_VERSION,
		true
	);

	$uses_dashboard_script = is_page_template( 'page-templates/woocommerce-account.php' )
		|| is_page_template( 'page-templates/select-events.php' )
		|| is_page_template( 'page-templates/expo-marketplace.php' )
		|| is_page( 'select-events-by-members' )
		|| is_page( 'select-events-by-non-members' )
		|| is_page( 'expo' )
		|| ( function_exists( 'is_account_page' ) && is_account_page() )
		|| ( function_exists( 'snf_is_bp_member_profile' ) && snf_is_bp_member_profile() );

	if ( $uses_dashboard_script ) {
		wp_enqueue_script(
			'snf-dashboard',
			get_template_directory_uri() . '/assets/js/dashboard.js',
			array(),
			SNF_VERSION,
			true
		);
	}

	if ( function_exists( 'snf_is_expo_marketplace_page' ) && snf_is_expo_marketplace_page() ) {
		wp_enqueue_style(
			'mapbox-gl',
			'https://api.mapbox.com/mapbox-gl-js/v3.9.0/mapbox-gl.css',
			array(),
			'3.9.0'
		);
		wp_enqueue_script(
			'mapbox-gl',
			'https://api.mapbox.com/mapbox-gl-js/v3.9.0/mapbox-gl.js',
			array(),
			'3.9.0',
			true
		);

		$expo_audience = ( function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas() )
			? 'members'
			: 'non-members';
		wp_enqueue_script(
			'snf-expo-marketplace',
			get_template_directory_uri() . '/assets/js/expo-marketplace.js',
			array( 'mapbox-gl' ),
			SNF_VERSION,
			true
		);
		wp_localize_script(
			'snf-expo-marketplace',
			'snfExpo',
			snf_get_expo_js_bootstrap( $expo_audience )
		);
	}

	$hub_templates = array(
		'page-templates/billing-details.php',
		'page-templates/member-login.php',
		'page-templates/apply.php',
		'page-templates/messages.php',
		'page-templates/public-content.php',
		'page-templates/under-consideration.php',
	);
	$hub_slugs = array(
		'billing-details',
		'member-login',
		'apply',
		'messages',
		'inquiries',
		'contact',
		'under-consideration',
	);
	$is_hub = false;
	foreach ( $hub_templates as $hub_template ) {
		if ( is_page_template( $hub_template ) ) {
			$is_hub = true;
			break;
		}
	}
	if ( ! $is_hub ) {
		foreach ( $hub_slugs as $hub_slug ) {
			if ( is_page( $hub_slug ) ) {
				$is_hub = true;
				break;
			}
		}
	}
	if ( $is_hub ) {
		wp_enqueue_script(
			'snf-hub',
			get_template_directory_uri() . '/assets/js/hub.js',
			array(),
			SNF_VERSION,
			true
		);
		wp_localize_script(
			'snf-hub',
			'snfHub',
			array(
				'considerationUrl' => snf_get_page_url( 'under-consideration' ),
			)
		);
	}

	$is_messages = is_page_template( 'page-templates/messages.php' ) || is_page( 'messages' );
	if ( $is_messages ) {
		wp_enqueue_script(
			'snf-messages',
			get_template_directory_uri() . '/assets/js/messages.js',
			array(),
			SNF_VERSION,
			true
		);
		wp_localize_script( 'snf-messages', 'snfMessages', snf_messages_get_bootstrap() );
	}

	$is_service_quote = function_exists( 'is_product' ) && is_product() && function_exists( 'snf_is_service_quote_product' ) && snf_is_service_quote_product( get_the_ID() );
	if ( $is_service_quote ) {
		wp_enqueue_script(
			'snf-service-quote',
			get_template_directory_uri() . '/assets/js/service-quote.js',
			array(),
			SNF_VERSION,
			true
		);
	}

	$is_workshop_product = function_exists( 'is_product' ) && is_product() && snf_is_lifestyle_concierge_workshop( get_the_ID() );
	if ( $is_workshop_product ) {
		wp_enqueue_script(
			'snf-workshop',
			get_template_directory_uri() . '/assets/js/workshop.js',
			array(),
			SNF_VERSION,
			true
		);
	}

	$is_event_landing = ( function_exists( 'is_product' ) && is_product() && function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( get_the_ID() ) )
		|| ( is_singular( 'event' ) && function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( get_the_ID() ) );
	if ( $is_event_landing ) {
		wp_enqueue_script(
			'snf-event-single',
			get_template_directory_uri() . '/assets/js/event-single.js',
			array(),
			SNF_VERSION,
			true
		);
	}

}
add_action( 'wp_enqueue_scripts', 'snf_scripts' );

/**
 * Map legacy event CPT slugs to current WooCommerce product slugs.
 *
 * @param string $slug Requested slug.
 * @return string
 */
function snf_resolve_listing_product_slug( $slug ) {
	$slug    = sanitize_title( (string) $slug );
	$aliases = array(
		'the-badlands-bra-party-bus-gathering' => 'the-badlands-bra-private-gathering',
		'the-eight-hundred-plus-journeys'      => 'the-eight-hundred-plus-journeys-event',
	);

	return isset( $aliases[ $slug ] ) ? $aliases[ $slug ] : $slug;
}

/**
 * Build a dashboard card from a Woo product or event slug.
 *
 * @param string $slug         Product or legacy event slug.
 * @param string $image_file   Optional filename under assets/images/events/.
 * @param string $fallback_url Fallback URL if the listing is missing.
 * @return array<string, string>|null
 */
function snf_get_dashboard_event_card_by_slug( $slug, $image_file = '', $fallback_url = '' ) {
	$slug = snf_resolve_listing_product_slug( $slug );

	// Workshop cards always deep-link to the product landing (never a legacy event/page).
	if ( snf_get_workshop_product_slug() === $slug ) {
		$product_post = get_page_by_path( $slug, OBJECT, 'product' );
		$product      = ( $product_post && class_exists( 'WooCommerce' ) ) ? wc_get_product( $product_post->ID ) : null;
		$location     = $product_post ? (string) get_post_meta( $product_post->ID, '_snf_location', true ) : '';
		$timing       = $product_post ? (string) get_post_meta( $product_post->ID, '_snf_when', true ) : '';
		$image        = $image_file
			? get_template_directory_uri() . '/assets/images/events/' . ltrim( $image_file, '/' )
			: '';

		if ( ! $image && $product && $product->get_image_id() ) {
			$thumb = wp_get_attachment_image_url( $product->get_image_id(), 'medium_large' );
			if ( $thumb ) {
				$image = $thumb;
			}
		}

		return array(
			'title' => $product ? $product->get_name() : __( 'The Lifestyle Concierge Club Workshop', 'super-ninety-four' ),
			'meta'  => trim( implode( ' · ', array_filter( array( $location, $timing ) ) ) ),
			'url'   => snf_get_workshop_landing_url(),
			'image' => $image,
		);
	}

	$product_post = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $product_post && class_exists( 'WooCommerce' ) ) {
		$product = wc_get_product( $product_post->ID );
		if ( $product ) {
			$location = (string) get_post_meta( $product_post->ID, '_snf_location', true );
			$timing   = (string) get_post_meta( $product_post->ID, '_snf_when', true );
			$image    = '';

			if ( $image_file ) {
				$image = get_template_directory_uri() . '/assets/images/events/' . ltrim( $image_file, '/' );
			} else {
				$image_id = $product->get_image_id();
				if ( $image_id ) {
					$thumb = wp_get_attachment_image_url( $image_id, 'medium_large' );
					if ( $thumb ) {
						$image = $thumb;
					}
				}
			}

			return array(
				'title' => $product->get_name(),
				'meta'  => trim( implode( ' · ', array_filter( array( $location, $timing ) ) ) ),
				'url'   => get_permalink( $product_post->ID ),
				'image' => $image,
			);
		}
	}

	$event = get_page_by_path( $slug, OBJECT, 'event' );
	if ( ! $event ) {
		if ( $fallback_url ) {
			return array(
				'title' => $slug,
				'meta'  => '',
				'url'   => $fallback_url,
				'image' => $image_file
					? get_template_directory_uri() . '/assets/images/events/' . ltrim( $image_file, '/' )
					: '',
			);
		}
		return null;
	}

	$location = get_post_meta( $event->ID, 'snf_event_location', true );
	$timing   = get_post_meta( $event->ID, 'snf_event_when', true );
	$image    = '';

	if ( $image_file ) {
		$image = get_template_directory_uri() . '/assets/images/events/' . ltrim( $image_file, '/' );
	} else {
		$thumb = get_the_post_thumbnail_url( $event->ID, 'medium_large' );
		if ( $thumb ) {
			$image = $thumb;
		}
	}

	return array(
		'title' => get_the_title( $event ),
		'meta'  => trim( implode( ' · ', array_filter( array( $location, $timing ) ) ) ),
		'url'   => snf_get_event_action_url( $event->ID ),
		'image' => $image,
	);
}

/**
 * Select events (members) cards.
 *
 * @return array<int, array<string, string>>
 */
function snf_get_dashboard_member_event_cards() {
	$cards = array_values(
		array_filter(
			array(
				snf_get_dashboard_event_card_by_slug( 'the-badlands-bra-private-gathering', 'badlands-bra-event.jpg' ),
				snf_get_dashboard_event_card_by_slug( 'the-eight-hundred-plus-journeys-event', 'eight-hundred-journeys-event.jpg' ),
				snf_get_dashboard_event_card_by_slug( 'the-township-adventures-event', 'township-adventures-event.jpg' ),
			)
		)
	);

	/**
	 * Filter member event cards on the dashboard.
	 *
	 * @param array $cards Member event listing cards.
	 */
	return apply_filters( 'snf_dashboard_member_event_cards', $cards );
}

/**
 * Events (non-members) cards — Lifestyle Concierge Club Workshop only.
 *
 * @return array<int, array<string, string>>
 */
function snf_get_dashboard_non_member_event_cards() {
	$card = snf_get_dashboard_event_card_by_slug( 'the-lifestyle-concierge-club-workshop', 'lcc-workshop.jpg' );
	$cards = $card ? array( $card ) : array();

	/**
	 * Filter non-member event cards on the dashboard.
	 *
	 * @param array $cards Non-member event listing cards.
	 */
	return apply_filters( 'snf_dashboard_non_member_event_cards', $cards );
}

/**
 * Reciprocal club program cards.
 *
 * @return array<int, array<string, string>>
 */
function snf_get_dashboard_reciprocal_cards() {
	$cards = function_exists( 'snf_get_product_brand_cards' )
		? snf_get_product_brand_cards()
		: array();

	if ( ! $cards ) {
		$img_base = get_template_directory_uri() . '/assets/images/reciprocal/';
		$url_for  = function_exists( 'snf_get_reciprocal_club_url' )
			? static function ( $slug ) {
				return snf_get_reciprocal_club_url( $slug );
			}
			: static function ( $slug ) {
				return snf_get_page_url( $slug );
			};
		$cards    = array(
			array(
				'title' => __( 'The Clubs at Wyoma Square', 'super-ninety-four' ),
				'meta'  => __( 'Reciprocal club program', 'super-ninety-four' ),
				'url'   => $url_for( 'the-clubs-at-wyoma-square' ),
				'image' => $img_base . 'the-clubs-at-wyoma-square.jpg',
			),
			array(
				'title' => __( 'The City Clubs of SUPER NINETY FOUR', 'super-ninety-four' ),
				'meta'  => __( 'Reciprocal club program', 'super-ninety-four' ),
				'url'   => $url_for( 'the-city-clubs-at-super-ninety-four' ),
				'image' => $img_base . 'the-city-clubs-at-super-ninety-four.jpg',
			),
			array(
				'title' => __( 'The Clubs at Back Creek', 'super-ninety-four' ),
				'meta'  => __( 'Reciprocal club program', 'super-ninety-four' ),
				'url'   => $url_for( 'the-clubs-at-back-creek' ),
				'image' => $img_base . 'the-clubs-at-back-creek.jpg',
			),
			array(
				'title' => __( 'The Badlands Bra Club', 'super-ninety-four' ),
				'meta'  => __( 'Reciprocal club program', 'super-ninety-four' ),
				'url'   => $url_for( 'the-badlands-bra-club' ),
				'image' => $img_base . 'the-badlands-bra-club.jpg',
			),
		);
	}

	/**
	 * Filter reciprocal program cards on the dashboard.
	 *
	 * @param array $cards Reciprocal listing cards.
	 */
	return apply_filters( 'snf_dashboard_reciprocal_cards', $cards );
}

/**
 * Service inquiry cards offered through the client portal lane.
 *
 * @return array<int, array<string, string>>
 */
function snf_get_dashboard_service_inquiry_cards() {
	$img_base = get_template_directory_uri() . '/assets/images/services/';
	$url_for  = function_exists( 'snf_get_service_quote_product_url' )
		? 'snf_get_service_quote_product_url'
		: 'snf_get_page_url';
	$cards    = array(
		array(
			'title' => __( 'Litter Pickup Service', 'super-ninety-four' ),
			'meta'  => __( 'Parking lots · Commercial grounds', 'super-ninety-four' ),
			'url'   => call_user_func( $url_for, 'litter-pickup-service' ),
			'image' => $img_base . 'litter-pickup-service-image-2.png',
		),
		array(
			'title' => __( 'Professional Line Sitting & Courier Service', 'super-ninety-four' ),
			'meta'  => __( 'Busy lines · Shopping runs · Handoffs', 'super-ninety-four' ),
			'url'   => call_user_func( $url_for, 'professional-line-sitting-courier-service' ),
			'image' => $img_base . 'lifestyle-service-image-1.png',
		),
		array(
			'title' => __( 'Janitorial Cleaning Service', 'super-ninety-four' ),
			'meta'  => __( 'Suites · Bars · Overnight reset', 'super-ninety-four' ),
			'url'   => call_user_func( $url_for, 'janitorial-cleaning-service' ),
			'image' => $img_base . 'commercial-janitor-cleaning.jpg',
		),
	);

	/**
	 * Filter service inquiry cards on the dashboard.
	 *
	 * @param array $cards Service inquiry listing cards.
	 */
	return apply_filters( 'snf_dashboard_service_inquiry_cards', $cards );
}

/**
 * Canonical Lifestyle Concierge Club Workshop product slug.
 *
 * @return string
 */
function snf_get_workshop_product_slug() {
	return 'the-lifestyle-concierge-club-workshop';
}

/**
 * Lifestyle Concierge workshop landing URL (Woo product page only).
 *
 * @return string
 */
function snf_get_workshop_landing_url() {
	$workshop = get_page_by_path( snf_get_workshop_product_slug(), OBJECT, 'product' );
	if ( $workshop instanceof WP_Post && 'publish' === $workshop->post_status ) {
		return get_permalink( $workshop );
	}

	return home_url( '/product/' . snf_get_workshop_product_slug() . '/' );
}

/**
 * One-time cleanup of legacy workshop event/page posts (product is kept).
 */
function snf_delete_legacy_workshop_landing_posts() {
	if ( get_option( 'snf_workshop_legacy_cleaned' ) ) {
		return;
	}

	$slug = snf_get_workshop_product_slug();

	foreach ( array( 'event', 'page' ) as $post_type ) {
		$legacy = get_page_by_path( $slug, OBJECT, $post_type );
		if ( ! $legacy instanceof WP_Post ) {
			continue;
		}
		// Never delete the WooCommerce product itself.
		if ( 'product' === $legacy->post_type ) {
			continue;
		}
		wp_delete_post( $legacy->ID, true );
	}

	update_option( 'snf_workshop_legacy_cleaned', 1, false );
}
add_action( 'after_switch_theme', 'snf_delete_legacy_workshop_landing_posts' );
add_action( 'admin_init', 'snf_delete_legacy_workshop_landing_posts', 30 );

/**
 * Delete duplicate page/event CPT copies of select-event products, plus inquiry pages.
 * WooCommerce products are never deleted.
 */
function snf_delete_duplicate_event_and_inquiry_pages() {
	if ( get_option( 'snf_duplicate_event_inquiry_cleaned_v3' ) ) {
		return;
	}

	$event_slugs = array(
		'the-township-adventures-event',
		'the-badlands-bra-private-gathering',
		'the-eight-hundred-plus-journeys-event',
		'the-badlands-bra-party-bus-gathering',
		'the-eight-hundred-plus-journeys',
		'the-lifestyle-concierge-club-workshop',
	);

	// Only remove index/alias inquiry pages — canonical service landings are auto-created.
	$inquiry_slugs = array(
		'service-inquiries',
		'parking-lot-commercial-grounds-cleaning',
		'professional-line-sitting-service',
		'commercial-janitorial-cleaning-consulting',
	);

	foreach ( $event_slugs as $slug ) {
		foreach ( array( 'page', 'event' ) as $post_type ) {
			$legacy = get_page_by_path( $slug, OBJECT, $post_type );
			if ( $legacy instanceof WP_Post && 'product' !== $legacy->post_type ) {
				wp_delete_post( $legacy->ID, true );
			}
		}
	}

	foreach ( $inquiry_slugs as $slug ) {
		foreach ( array( 'page', 'event', 'post' ) as $post_type ) {
			$legacy = get_page_by_path( $slug, OBJECT, $post_type );
			if ( $legacy instanceof WP_Post && 'product' !== $legacy->post_type ) {
				wp_delete_post( $legacy->ID, true );
			}
		}
	}

	update_option( 'snf_duplicate_event_inquiry_cleaned_v3', 1, false );
}
add_action( 'after_switch_theme', 'snf_delete_duplicate_event_and_inquiry_pages' );
add_action( 'admin_init', 'snf_delete_duplicate_event_and_inquiry_pages', 31 );

/**
 * Redirect legacy workshop URLs to the product landing.
 */
function snf_redirect_legacy_workshop_landing() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$target = snf_get_workshop_landing_url();
	if ( ! $target ) {
		return;
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( ! $request_path ) {
		return;
	}

	$slug = snf_get_workshop_product_slug();
	$legacy_patterns = array(
		'#/(?:event|events)/' . preg_quote( $slug, '#' ) . '/?$#',
		'#/' . preg_quote( $slug, '#' ) . '/?$#',
	);

	foreach ( $legacy_patterns as $pattern ) {
		if ( preg_match( $pattern, $request_path ) ) {
			// Allow the real product permalink to render.
			if ( preg_match( '#/product/' . preg_quote( $slug, '#' ) . '/?$#', $request_path ) ) {
				return;
			}
			wp_safe_redirect( $target, 301 );
			exit;
		}
	}

	// Deep-links that still pass an old workshop event id.
	$requested = snf_get_requested_event_id();
	if ( $requested && snf_is_lifestyle_concierge_workshop( $requested ) ) {
		$current = untrailingslashit( home_url( $request_path ) );
		if ( untrailingslashit( $target ) !== $current && ! is_singular( 'product' ) ) {
			wp_safe_redirect( $target, 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'snf_redirect_legacy_workshop_landing', 15 );

/**
 * Legacy alias — service inquiry pages now redirect to quote products.
 *
 * @param string $slug Legacy page slug or product slug.
 * @return array<string, mixed>|null
 */
function snf_get_service_inquiry( $slug = '' ) {
	$slug = $slug ? sanitize_title( $slug ) : '';
	if ( ! $slug || ! function_exists( 'snf_get_service_quote' ) ) {
		return null;
	}

	$map = array(
		'grounds-litter-pickup'             => 'litter-pickup-service',
		'line-sitting-service'              => 'professional-line-sitting-courier-service',
		'courier-service'                   => 'professional-line-sitting-courier-service',
		'professional-line-sitting-service' => 'professional-line-sitting-courier-service',
		'commercial-janitor-cleaning'       => 'janitorial-cleaning-service',
	);
	$product_slug = isset( $map[ $slug ] ) ? $map[ $slug ] : $slug;
	$quote        = snf_get_service_quote( $product_slug );
	if ( ! $quote ) {
		return null;
	}

	return array(
		'title'            => $quote['title'],
		'lede'             => $quote['lede'],
		'image'            => $quote['image'],
		'benefits_heading' => $quote['can_heading'],
		'benefits'         => $quote['can_items'],
		'price'            => $quote['price'],
		'card_description' => $quote['card_description'],
		'workshop_url'     => snf_get_workshop_landing_url(),
		'inquiry_url'      => $quote['cta_url'],
	);
}

/**
 * Center header navigation for logged-out visitors.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_primary_menu_items() {
	$inquiry_children = array(
		array(
			'type'    => 'heading',
			'label'   => __( 'Service inquiries', 'super-ninety-four' ),
			'url'     => snf_get_page_url( 'inquiries' ),
			'current' => is_page( 'inquiries' ),
		),
	);

	if ( function_exists( 'snf_get_dashboard_service_inquiry_cards' ) ) {
		foreach ( snf_get_dashboard_service_inquiry_cards() as $card ) {
			$card_url = isset( $card['url'] ) ? (string) $card['url'] : '';
			$inquiry_children[] = array(
				'type'    => 'link',
				'label'   => $card['title'],
				'url'     => $card_url,
				'current' => $card_url && function_exists( 'snf_url_matches_request' )
					? snf_url_matches_request( $card_url )
					: false,
			);
		}
	}

	$workshop_url = snf_get_workshop_landing_url();

	return array(
		array(
			'type'     => 'dropdown',
			'label'    => __( 'Inquiries', 'super-ninety-four' ),
			'url'      => snf_get_page_url( 'inquiries' ),
			'current'  => is_page( 'inquiries' ) || snf_primary_menu_has_current_child( $inquiry_children ),
			'children' => $inquiry_children,
		),
		array(
			'type'    => 'link',
			'label'   => __( 'Events', 'super-ninety-four' ),
			'url'     => $workshop_url,
			'current' => function_exists( 'snf_url_matches_request' )
				? snf_url_matches_request( $workshop_url )
				: false,
		),
		array(
			'type'    => 'link',
			'label'   => __( 'About', 'super-ninety-four' ),
			'url'     => snf_get_page_url( 'about' ),
			'current' => is_page( 'about' ),
		),
		array(
			'type'    => 'link',
			'label'   => __( 'Videos & Audio', 'super-ninety-four' ),
			'url'     => snf_get_page_url( 'videos-audio' ),
			'current' => is_page( 'videos-audio' ),
		),
		array(
			'type'    => 'link',
			'label'   => __( 'Contact', 'super-ninety-four' ),
			'url'     => snf_get_page_url( 'contact' ),
			'current' => is_page( 'contact' ),
		),
		array(
			'type'    => 'link',
			'label'   => __( 'Newsletter', 'super-ninety-four' ),
			'url'     => snf_get_page_url( 'newsletter' ),
			'current' => is_page( 'newsletter' ),
		),
	);
}

/**
 * Whether any child menu item is marked current.
 *
 * @param array<int, array<string, mixed>> $children Menu children.
 * @return bool
 */
function snf_primary_menu_has_current_child( $children ) {
	foreach ( $children as $child ) {
		if ( ! empty( $child['current'] ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Compare a menu URL against the current request path.
 *
 * @param string $url Absolute or relative URL.
 * @return bool
 */
function snf_url_matches_request( $url ) {
	$url = (string) $url;
	if ( '' === $url || '#' === $url ) {
		return false;
	}

	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! is_string( $path ) || '' === $path ) {
		return false;
	}

	$request = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$request_path = wp_parse_url( $request, PHP_URL_PATH );
	if ( ! is_string( $request_path ) ) {
		return false;
	}

	return untrailingslashit( $path ) === untrailingslashit( $request_path );
}

/**
 * Account dropdown items for the profile icon menu.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_account_menu_items() {
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

	return array(
		array(
			'type'        => 'search',
			'label'       => __( 'Find other reciprocal programs', 'super-ninety-four' ),
			'url'         => snf_get_page_url( 'find-other-reciprocal-programs' ),
			'placeholder' => __( 'Find other reciprocal programs', 'super-ninety-four' ),
		),
		array(
			'type'  => 'link',
			'label' => __( 'Dashboard', 'super-ninety-four' ),
			'url'   => snf_get_dashboard_url(),
		),
		array(
			'type'  => 'link',
			'label' => __( 'Search listings', 'super-ninety-four' ),
			'url'   => $shop_url ? $shop_url : home_url( '/shop/' ),
		),
		array(
			'type'  => 'link',
			'label' => __( 'Messages', 'super-ninety-four' ),
			'url'   => snf_get_bp_messages_url(),
			'icon'  => 'messages',
		),
		array(
			'type'  => 'link',
			'label' => __( 'Orders', 'super-ninety-four' ),
			'url'   => snf_get_woo_account_url( 'orders' ),
		),
		array(
			'type'  => 'link',
			'label' => __( 'Tickets', 'super-ninety-four' ),
			'url'   => snf_get_woo_account_url( 'tickets' ),
		),
		array(
			'type'  => 'link',
			'label' => __( 'Membership', 'super-ninety-four' ),
			'url'   => snf_get_woo_account_url( 'membership' ),
		),
		array(
			'type'  => 'link',
			'label' => __( 'Billing', 'super-ninety-four' ),
			'url'   => snf_get_woo_account_url( 'edit-address' ),
		),
		array(
			'type'  => 'link',
			'label' => __( 'Profile', 'super-ninety-four' ),
			'url'   => snf_get_bp_profile_url(),
		),
	);
}

/**
 * Whether an event uses the Lifestyle Concierge Club Workshop landing.
 *
 * @param int|null $post_id Event post ID.
 * @return bool
 */
function snf_is_lifestyle_concierge_workshop( $post_id = null ) {
	$post = get_post( $post_id ? $post_id : get_the_ID() );
	if ( ! $post ) {
		return false;
	}

	$post_id = (int) $post->ID;

	if ( 'lifestyle-concierge-workshop' === get_post_meta( $post_id, 'snf_event_template', true ) ) {
		return true;
	}

	return 'the-lifestyle-concierge-club-workshop' === $post->post_name;
}

/**
 * Read workshop contact fields from the add-to-cart request.
 *
 * @return array{name:string,email:string,phone:string}
 */
function snf_workshop_request_contact_fields() {
	$name  = isset( $_REQUEST['snf_cart_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['snf_cart_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$email = isset( $_REQUEST['snf_cart_email'] ) ? sanitize_email( wp_unslash( $_REQUEST['snf_cart_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$phone = isset( $_REQUEST['snf_cart_phone'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['snf_cart_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	// Fall back to visible form fields if hidden cart mirrors were empty.
	if ( '' === $name && isset( $_REQUEST['snf_workshop_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$name = sanitize_text_field( wp_unslash( $_REQUEST['snf_workshop_name'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( '' === $email && isset( $_REQUEST['snf_workshop_email'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$email = sanitize_email( wp_unslash( $_REQUEST['snf_workshop_email'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( '' === $phone && isset( $_REQUEST['snf_workshop_phone'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$phone = sanitize_text_field( wp_unslash( $_REQUEST['snf_workshop_phone'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	return array(
		'name'  => $name,
		'email' => $email,
		'phone' => $phone,
	);
}

/**
 * Block workshop add-to-cart when contact fields are incomplete.
 *
 * @param bool $passed     Whether validation passed.
 * @param int  $product_id Product ID.
 * @return bool
 */
function snf_workshop_validate_add_to_cart( $passed, $product_id ) {
	if ( ! $passed || ! snf_is_lifestyle_concierge_workshop( $product_id ) ) {
		return $passed;
	}

	$contact = snf_workshop_request_contact_fields();
	if ( '' === $contact['name'] || ! is_email( $contact['email'] ) || '' === $contact['phone'] ) {
		wc_add_notice( __( 'Please enter your full name, email, and phone before adding the workshop to your cart.', 'super-ninety-four' ), 'error' );
		return false;
	}

	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'snf_workshop_validate_add_to_cart', 10, 2 );

/**
 * Persist workshop contact fields onto cart line items.
 *
 * @param array $cart_item_data Existing cart item data.
 * @param int   $product_id    Product ID.
 * @return array
 */
function snf_workshop_add_cart_item_data( $cart_item_data, $product_id ) {
	if ( ! snf_is_lifestyle_concierge_workshop( $product_id ) ) {
		return $cart_item_data;
	}

	$contact = snf_workshop_request_contact_fields();
	foreach ( $contact as $key => $value ) {
		if ( '' !== $value ) {
			$cart_item_data[ 'snf_workshop_' . $key ] = $value;
		}
	}

	if ( ! empty( $cart_item_data['snf_workshop_email'] ) || ! empty( $cart_item_data['snf_workshop_name'] ) ) {
		$cart_item_data['unique_key'] = md5( microtime() . wp_rand() );
	}

	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'snf_workshop_add_cart_item_data', 10, 2 );

/**
 * Prefill WooCommerce customer billing from workshop contact details.
 *
 * @param string $cart_item_key Cart item key.
 * @param int    $product_id    Product ID.
 * @param int    $quantity      Quantity.
 * @param int    $variation_id  Variation ID.
 * @param array  $variation     Variation data.
 * @param array  $cart_item_data Cart item data.
 */
function snf_workshop_prefill_customer_from_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
	if ( ! snf_is_lifestyle_concierge_workshop( $product_id ) || ! function_exists( 'WC' ) || ! WC()->customer ) {
		return;
	}

	$name  = isset( $cart_item_data['snf_workshop_name'] ) ? (string) $cart_item_data['snf_workshop_name'] : '';
	$email = isset( $cart_item_data['snf_workshop_email'] ) ? (string) $cart_item_data['snf_workshop_email'] : '';
	$phone = isset( $cart_item_data['snf_workshop_phone'] ) ? (string) $cart_item_data['snf_workshop_phone'] : '';

	if ( $email ) {
		WC()->customer->set_billing_email( $email );
	}
	if ( $phone ) {
		WC()->customer->set_billing_phone( $phone );
	}
	if ( $name ) {
		$parts = preg_split( '/\s+/', trim( $name ), 2 );
		WC()->customer->set_billing_first_name( $parts[0] ?? $name );
		WC()->customer->set_billing_last_name( $parts[1] ?? '' );
	}
}
add_action( 'woocommerce_add_to_cart', 'snf_workshop_prefill_customer_from_cart', 20, 6 );

/**
 * Send workshop purchases to cart after add-to-cart.
 *
 * @param string $url Default redirect URL.
 * @return string
 */
function snf_workshop_add_to_cart_redirect( $url ) {
	$product_id = 0;
	if ( isset( $_REQUEST['add-to-cart'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$product_id = absint( $_REQUEST['add-to-cart'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} elseif ( isset( $_REQUEST['product_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$product_id = absint( $_REQUEST['product_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	if ( $product_id && snf_is_lifestyle_concierge_workshop( $product_id ) && function_exists( 'wc_get_cart_url' ) ) {
		return wc_get_cart_url();
	}

	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'snf_workshop_add_to_cart_redirect' );

/**
 * Show workshop contact meta in cart/checkout.
 *
 * @param array $item_data Display rows.
 * @param array $cart_item Cart item.
 * @return array
 */
function snf_workshop_cart_item_data( $item_data, $cart_item ) {
	$map = array(
		'snf_workshop_name'  => __( 'Full name', 'super-ninety-four' ),
		'snf_workshop_email' => __( 'Your email', 'super-ninety-four' ),
		'snf_workshop_phone' => __( 'Your phone', 'super-ninety-four' ),
	);

	foreach ( $map as $key => $label ) {
		if ( empty( $cart_item[ $key ] ) ) {
			continue;
		}
		$item_data[] = array(
			'key'   => $label,
			'value' => $cart_item[ $key ],
		);
	}

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'snf_workshop_cart_item_data', 10, 2 );

/**
 * Copy workshop contact meta onto the order line item.
 *
 * @param WC_Order_Item_Product $item          Order item.
 * @param string                $cart_item_key Cart key.
 * @param array                 $values        Cart values.
 */
function snf_workshop_checkout_create_order_line_item( $item, $cart_item_key, $values ) {
	$map = array(
		'snf_workshop_name'  => __( 'Full name', 'super-ninety-four' ),
		'snf_workshop_email' => __( 'Your email', 'super-ninety-four' ),
		'snf_workshop_phone' => __( 'Your phone', 'super-ninety-four' ),
	);

	foreach ( $map as $key => $label ) {
		if ( empty( $values[ $key ] ) ) {
			continue;
		}
		$item->add_meta_data( $label, $values[ $key ], true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'snf_workshop_checkout_create_order_line_item', 10, 3 );

/**
 * Loose text match for home/archive search (empty query matches everything).
 *
 * @param string $haystack Source text.
 * @param string $needle   User query.
 * @return bool
 */
function snf_search_text_matches( $haystack, $needle ) {
	$haystack = strtolower( wp_strip_all_tags( (string) $haystack ) );
	$needle   = strtolower( trim( (string) $needle ) );

	if ( '' === $needle ) {
		return true;
	}

	if ( false !== strpos( $haystack, $needle ) ) {
		return true;
	}

	$words = preg_split( '/\s+/', $needle );
	if ( ! is_array( $words ) || ! $words ) {
		return false;
	}

	foreach ( $words as $word ) {
		if ( strlen( $word ) < 2 ) {
			continue;
		}
		if ( false === strpos( $haystack, $word ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Service quote product results for the home search engine.
 *
 * @param string $query Search query.
 * @return array<int, array<string, string>>
 */
function snf_get_service_inquiry_search_results( $query = '' ) {
	$results = array();
	$query   = trim( (string) $query );

	if ( $query && function_exists( 'snf_get_service_quote_product_slugs' ) ) {
		foreach ( snf_get_service_quote_product_slugs() as $slug ) {
			$quote = snf_get_service_quote( $slug );
			if ( ! $quote ) {
				continue;
			}
			$haystack = strtolower( $quote['title'] . ' ' . $quote['lede'] . ' ' . $slug );
			if ( ! snf_search_text_matches( $haystack, $query ) ) {
				continue;
			}
			$results[] = array(
				'title' => $quote['title'],
				'meta'  => $quote['lede'],
				'url'   => snf_get_service_quote_product_url( $slug ),
				'image' => $quote['image'],
			);
		}
	}

	/**
	 * Filter service quote search results.
	 *
	 * @param array  $results Result rows.
	 * @param string $query   Search query.
	 */
	return apply_filters( 'snf_service_inquiry_search_results', $results, $query );
}

/**
 * Event result URL — logged-out non-members apply, workshop goes direct.
 *
 * @param int|null $post_id Event post ID.
 * @return string
 */
function snf_get_event_action_url( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$post    = get_post( $post_id );

	// Workshop always uses the dedicated product landing.
	if ( snf_is_lifestyle_concierge_workshop( $post_id ) ) {
		return snf_get_workshop_landing_url();
	}

	// WooCommerce products always deep-link to their product page / checkout path.
	if ( $post && 'product' === $post->post_type ) {
		return get_permalink( $post_id );
	}

	// Member select events keep the admissions form on the event page.
	if ( snf_is_member_select_event( $post_id ) ) {
		return get_permalink( $post_id );
	}

	$args = array(
		'q'         => get_the_title( $post_id ),
		'snf_event' => $post_id,
	);

	// Non-member select events hand off to Apply (contact → billing).
	return add_query_arg( $args, snf_get_page_url( 'apply' ) );
}

/**
 * Read a deep-linked event id from the request without using the CPT `event` query var.
 *
 * @return int
 */
function snf_get_requested_event_id() {
	if ( isset( $_GET['snf_event'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return absint( wp_unslash( $_GET['snf_event'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( isset( $_GET['event'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return absint( wp_unslash( $_GET['event'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	return 0;
}

/**
 * Whether the current request path looks like an event CPT archive/single.
 *
 * @return bool
 */
function snf_request_path_is_event_cpt() {
	$path = '';
	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
	$path = trim( (string) $path, '/' );
	return ( 'events' === $path || 0 === strpos( $path, 'events/' ) );
}

/**
 * Remap legacy `?event=` to `?snf_event=` before WP parses public query vars.
 *
 * The Event CPT registers `event` as a public query var, so `/apply/?event=13`
 * 404s. Run early so Apply and Select Events deep links keep working.
 */
function snf_remap_legacy_event_query_arg() {
	if ( snf_request_path_is_event_cpt() ) {
		return;
	}
	if ( ! isset( $_GET['event'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$event_id = absint( wp_unslash( $_GET['event'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $event_id < 1 ) {
		return;
	}

	if ( ! isset( $_GET['snf_event'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['snf_event']     = (string) $event_id;
		$_REQUEST['snf_event'] = (string) $event_id;
	}

	unset( $_GET['event'], $_REQUEST['event'] );
}
// Theme loads after plugins_loaded — run immediately, then again on init.
snf_remap_legacy_event_query_arg();
add_action( 'init', 'snf_remap_legacy_event_query_arg', 0 );

/**
 * Drop any leftover CPT `event` query var when resolving a page.
 *
 * @param array<string, mixed> $query_vars Request vars.
 * @return array<string, mixed>
 */
function snf_fix_event_query_var_collision( $query_vars ) {
	$has_page = ! empty( $query_vars['pagename'] ) || ! empty( $query_vars['page_id'] ) || ! empty( $query_vars['page'] );
	if ( ! $has_page ) {
		return $query_vars;
	}

	if ( isset( $query_vars['event'] ) ) {
		unset( $query_vars['event'] );
	}
	if ( isset( $query_vars['name'] ) && isset( $query_vars['pagename'] ) ) {
		unset( $query_vars['name'] );
	}

	$event_id = snf_get_requested_event_id();
	if ( $event_id > 0 ) {
		$query_vars['snf_event'] = $event_id;
	}

	return $query_vars;
}
add_filter( 'request', 'snf_fix_event_query_var_collision', 1 );

/**
 * CTA label for an event search/admission handoff.
 *
 * @param int|null $post_id Event post ID.
 * @return string
 */
function snf_get_event_action_label( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();

	if ( snf_is_lifestyle_concierge_workshop( $post_id ) ) {
		return __( 'Open workshop landing', 'super-ninety-four' );
	}

	if ( snf_is_member_select_event( $post_id ) ) {
		return __( 'Apply to attend', 'super-ninety-four' );
	}

	return __( 'Continue to Apply', 'super-ninety-four' );
}

/**
 * Handle Lifestyle Concierge workshop seat reservations.
 */
function snf_handle_workshop_reserve() {
	if ( ! isset( $_POST['snf_workshop_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snf_workshop_nonce'] ) ), 'snf_workshop_reserve' ) ) {
		wp_safe_redirect( add_query_arg( 'reserved', '0', wp_get_referer() ? wp_get_referer() : home_url( '/' ) ) );
		exit;
	}

	$event_id  = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
	$full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$firm      = isset( $_POST['firm'] ) ? sanitize_text_field( wp_unslash( $_POST['firm'] ) ) : '';

	$redirect = $event_id ? get_permalink( $event_id ) : home_url( '/' );

	if ( ! $full_name || ! is_email( $email ) || ! $phone ) {
		wp_safe_redirect( add_query_arg( 'reserved', '0', $redirect ) );
		exit;
	}

	$event_title = $event_id ? get_the_title( $event_id ) : 'The Lifestyle Concierge Club Workshop';
	$content     = "Name: {$full_name}\nEmail: {$email}\nPhone: {$phone}\nFirm: {$firm}\nEvent: {$event_title}";

	$reservation_id = wp_insert_post(
		array(
			'post_type'    => 'snf_reservation',
			'post_status'  => 'private',
			'post_title'   => sprintf( 'Workshop seat — %s', $full_name ),
			'post_content' => $content,
		),
		true
	);

	if ( is_wp_error( $reservation_id ) ) {
		wp_safe_redirect( add_query_arg( 'reserved', '0', $redirect ) );
		exit;
	}

	update_post_meta( $reservation_id, 'snf_event_id', $event_id );
	update_post_meta( $reservation_id, 'snf_email', $email );
	update_post_meta( $reservation_id, 'snf_phone', $phone );
	update_post_meta( $reservation_id, 'snf_firm', $firm );

	$admin_email = get_option( 'admin_email' );
	if ( $admin_email ) {
		wp_mail(
			$admin_email,
			sprintf( '[SNF] Workshop reservation — %s', $full_name ),
			$content
		);
	}

	wp_safe_redirect( add_query_arg( 'reserved', '1', $redirect ) . '#snf-workshop-reserve' );
	exit;
}
add_action( 'admin_post_nopriv_snf_workshop_reserve', 'snf_handle_workshop_reserve' );
add_action( 'admin_post_snf_workshop_reserve', 'snf_handle_workshop_reserve' );

function snf_register_reservation_cpt() {
	register_post_type(
		'snf_reservation',
		array(
			'labels'       => array(
				'name'          => __( 'Reservations', 'super-ninety-four' ),
				'singular_name' => __( 'Reservation', 'super-ninety-four' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'edit.php?post_type=event',
			'supports'     => array( 'title', 'editor' ),
			'menu_icon'    => 'dashicons-tickets-alt',
		)
	);
}
add_action( 'init', 'snf_register_reservation_cpt' );