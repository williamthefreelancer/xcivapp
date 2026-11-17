<?php
/**
 * Super Ninety Four theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SNF_VERSION', '2.9.7' );

require_once get_template_directory() . '/inc/account-hub.php';
require_once get_template_directory() . '/inc/dashboard-shell.php';
require_once get_template_directory() . '/inc/dashboard-panels.php';
require_once get_template_directory() . '/inc/buddypress-profile.php';
require_once get_template_directory() . '/inc/access-control.php';
require_once get_template_directory() . '/inc/plugin-integrations.php';
require_once get_template_directory() . '/inc/messages.php';
require_once get_template_directory() . '/inc/reciprocal.php';
require_once get_template_directory() . '/inc/home.php';
require_once get_template_directory() . '/inc/events.php';
require_once get_template_directory() . '/inc/expo-marketplace.php';
require_once get_template_directory() . '/inc/lead-rider.php';
require_once get_template_directory() . '/inc/woocommerce-serp.php';
require_once get_template_directory() . '/inc/woocommerce-endpoints.php';

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
	);
}

function snf_get_obsolete_page_slugs() {
	return array(
		'dashboard',
		'select-events-members-non-members',
		'select-events-non-members',
		'select-events-by-admission-for-non-members',
		'select-events-members',
		'select-events-by-members',
		'select-events-by-non-members',
		'expo-marketplace-by-members',
		'expo-marketplace-by-non-members',
		'member-concierge',
		'my-profile',
		'settings',
		'reciprocal-programs',
		'the-clubs-at-wyoma-square',
		'the-city-clubs-at-super-ninety-four',
		'the-clubs-at-back-creek',
		'the-badlands-bra-club',
		'service-inquiries',
		'grounds-litter-pickup',
		'line-sitting-service',
		'commercial-janitor-cleaning',
		'about',
		'event-by-admission',
		'account-settings-for-profile',
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
		'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Manrope:wght@400;500;600&display=swap',
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
		|| is_page( 'expo-marketplace-by-members' )
		|| is_page( 'expo-marketplace-by-non-members' )
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

		$expo_audience = is_page( 'expo-marketplace-by-members' ) ? 'members' : 'non-members';
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
		'page-templates/about.php',
		'page-templates/under-consideration.php',
	);
	$hub_slugs = array(
		'billing-details',
		'member-login',
		'apply',
		'messages',
		'about',
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

	$service_slugs = array(
		'grounds-litter-pickup',
		'line-sitting-service',
		'commercial-janitor-cleaning',
	);
	$uses_service_inquiry = is_page_template( 'page-templates/service-inquiry.php' );
	if ( ! $uses_service_inquiry ) {
		foreach ( $service_slugs as $service_slug ) {
			if ( is_page( $service_slug ) ) {
				$uses_service_inquiry = true;
				break;
			}
		}
	}
	if ( $uses_service_inquiry ) {
		wp_enqueue_script(
			'snf-service-inquiry',
			get_template_directory_uri() . '/assets/js/service-inquiry.js',
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
		$cards    = array(
			array(
				'title' => __( 'The Clubs at Wyoma Square', 'super-ninety-four' ),
				'meta'  => __( 'Reciprocal club program', 'super-ninety-four' ),
				'url'   => snf_get_page_url( 'the-clubs-at-wyoma-square' ),
				'image' => $img_base . 'the-clubs-at-wyoma-square.jpg',
			),
			array(
				'title' => __( 'The City Clubs of SUPER NINETY FOUR', 'super-ninety-four' ),
				'meta'  => __( 'Reciprocal club program', 'super-ninety-four' ),
				'url'   => snf_get_page_url( 'the-city-clubs-at-super-ninety-four' ),
				'image' => $img_base . 'the-city-clubs-at-super-ninety-four.jpg',
			),
			array(
				'title' => __( 'The Clubs at Back Creek', 'super-ninety-four' ),
				'meta'  => __( 'Reciprocal club program', 'super-ninety-four' ),
				'url'   => snf_get_page_url( 'the-clubs-at-back-creek' ),
				'image' => $img_base . 'the-clubs-at-back-creek.jpg',
			),
			array(
				'title' => __( 'The Badlands Bra Club', 'super-ninety-four' ),
				'meta'  => __( 'Reciprocal club program', 'super-ninety-four' ),
				'url'   => snf_get_page_url( 'the-badlands-bra-club' ),
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
	$apply    = snf_get_page_url( 'apply' );
	$cards    = array(
		array(
			'title' => __( 'Parking Lot & Commercial Grounds Cleaning', 'super-ninety-four' ),
			'meta'  => __( 'Litter pickup service', 'super-ninety-four' ),
			'url'   => add_query_arg( 'q', 'Parking Lot & Commercial Grounds Cleaning', $apply ),
			'image' => $img_base . 'grounds-litter-pickup.png',
		),
		array(
			'title' => __( 'Professional Line Sitting Service', 'super-ninety-four' ),
			'meta'  => __( 'Busy lines · Obtaining consumer goods', 'super-ninety-four' ),
			'url'   => add_query_arg( 'q', 'Professional Line Sitting Service', $apply ),
			'image' => $img_base . 'line-sitting-service.png',
		),
		array(
			'title' => __( 'Courier Service', 'super-ninety-four' ),
			'meta'  => __( 'Shopping runs · Consumer goods delivery', 'super-ninety-four' ),
			'url'   => add_query_arg( 'q', 'Courier Service', $apply ),
			'image' => $img_base . 'line-sitting-service.png',
		),
		array(
			'title' => __( 'Commercial Janitorial Cleaning & Consulting', 'super-ninety-four' ),
			'meta'  => __( 'Suites · Bars · Property consulting', 'super-ninety-four' ),
			'url'   => add_query_arg( 'q', 'Commercial Janitorial Cleaning & Consulting', $apply ),
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
 * Lifestyle Concierge workshop landing URL for service CTAs.
 *
 * @return string
 */
function snf_get_workshop_landing_url() {
	$workshop = get_page_by_path( 'the-lifestyle-concierge-club-workshop', OBJECT, 'product' );
	if ( $workshop instanceof WP_Post ) {
		return get_permalink( $workshop );
	}

	$workshop = get_page_by_path( 'the-lifestyle-concierge-club-workshop', OBJECT, 'event' );
	if ( $workshop instanceof WP_Post ) {
		return get_permalink( $workshop );
	}

	return home_url( '/' );
}

/**
 * Full landing content for a service inquiry page.
 *
 * @param string $slug Page slug.
 * @return array<string, mixed>|null
 */
function snf_get_service_inquiry( $slug = '' ) {
	$slug     = $slug ? sanitize_title( $slug ) : '';
	$img_base = get_template_directory_uri() . '/assets/images/services/';
	$workshop = snf_get_workshop_landing_url();
	$inquiry  = snf_get_page_url( 'apply' );

	$services = array(
		'grounds-litter-pickup'         => array(
			'title'            => __( 'Grounds Litter Pickup', 'super-ninety-four' ),
			'lede'             => __( 'Scheduled exterior litter control for property managers running business parks and bar-tenant corridors — walk routes, bag, and clear before guests and cameras notice.', 'super-ninety-four' ),
			'image'            => $img_base . 'grounds-litter-pickup.png',
			'benefits_heading' => __( 'What your property gets on every service cycle', 'super-ninety-four' ),
			'benefits'         => array(
				__( 'Mapped walk routes for parks, lots, and tenant frontage', 'super-ninety-four' ),
				__( 'Bagged debris removed before peak guest hours', 'super-ninety-four' ),
				__( 'Bar-tenant curb lines kept camera-ready', 'super-ninety-four' ),
				__( 'Photo log of cleared zones after each visit', 'super-ninety-four' ),
				__( 'One invoice lane for multi-site property managers', 'super-ninety-four' ),
			),
			'price'            => '$000.00/mo',
			'card_description' => __( 'Exterior litter pickup on a fixed monthly route: business-park grounds + bar-tenant frontage only — bagged, logged, and cleared to a photographed standard.', 'super-ninety-four' ),
			'workshop_url'     => $workshop,
			'inquiry_url'      => $inquiry,
		),
		'line-sitting-service'          => array(
			'title'            => __( 'Line Sitting Service', 'super-ninety-four' ),
			'lede'             => __( 'Trained sitters hold your place in high-demand consumer-goods lines so principals reclaim the hours without losing the drop, release, or reservation window.', 'super-ninety-four' ),
			'image'            => $img_base . 'line-sitting-service.png',
			'benefits_heading' => __( 'What you get when we hold the line', 'super-ninety-four' ),
			'benefits'         => array(
				__( 'On-site presence through the full queue window', 'super-ninety-four' ),
				__( 'Live status pings before your handover', 'super-ninety-four' ),
				__( 'Rules-aware holding for retail & release events', 'super-ninety-four' ),
				__( 'Proxy checkout handoff when you arrive', 'super-ninety-four' ),
				__( 'Single-request booking for one SKU or drop', 'super-ninety-four' ),
			),
			'price'            => '$000.00/mo',
			'card_description' => __( 'Line sitting for consumer-goods drops and busy retail queues: we hold your place, ping status, and hand the position to you at the agreed minute — one request, one line, one outcome.', 'super-ninety-four' ),
			'workshop_url'     => $workshop,
			'inquiry_url'      => $inquiry,
		),
		'commercial-janitor-cleaning'   => array(
			'title'            => __( 'Commercial Janitorial Cleaning & Consulting', 'super-ninety-four' ),
			'lede'             => __( 'Interior janitorial coverage plus cleaning-program consulting for business-park suites and bar tenants — restrooms, floors, and back-of-house reset on a calendar you can audit.', 'super-ninety-four' ),
			'image'            => $img_base . 'commercial-janitor-cleaning.jpg',
			'benefits_heading' => __( 'What your suites and bars get each visit', 'super-ninety-four' ),
			'benefits'         => array(
				__( 'Restroom and floor reset to a written checklist', 'super-ninety-four' ),
				__( 'Bar back-of-house wipe-down after service hours', 'super-ninety-four' ),
				__( 'Suite common areas kept tenant-ready', 'super-ninety-four' ),
				__( 'Supply restock notes for managers', 'super-ninety-four' ),
				__( 'Consulting on cadence, scopes, and multi-site standards', 'super-ninety-four' ),
				__( 'Recurring calendar with visit confirmation', 'super-ninety-four' ),
			),
			'price'            => '$000.00/mo',
			'card_description' => __( 'Commercial janitorial cleaning and consulting for business-park suites and bar tenants: checklist-driven interior reset plus program guidance on a recurring monthly cadence you can verify.', 'super-ninety-four' ),
			'workshop_url'     => $workshop,
			'inquiry_url'      => $inquiry,
		),
	);

	/**
	 * Filter the full service inquiry content map.
	 *
	 * @param array $services Service inquiry definitions keyed by slug.
	 */
	$services = apply_filters( 'snf_service_inquiry_content', $services );

	if ( $slug && isset( $services[ $slug ] ) ) {
		return $services[ $slug ];
	}

	return null;
}

/**
 * Center header navigation items.
 *
 * @return array<int, array{label:string,url:string}>
 */
function snf_primary_menu_items() {
	$events_url = get_post_type_archive_link( 'event' );
	if ( ! $events_url ) {
		$events_url = home_url( '/events/' );
	}

	return array(
		array(
			'label' => __( 'Events', 'super-ninety-four' ),
			'url'   => $events_url,
		),
	);
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
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( ! $post_id ) {
		return false;
	}

	if ( 'lifestyle-concierge-workshop' === get_post_meta( $post_id, 'snf_event_template', true ) ) {
		return true;
	}

	$post = get_post( $post_id );
	return $post && 'the-lifestyle-concierge-club-workshop' === $post->post_name;
}

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
 * Service inquiry results for the home search engine.
 * Returns no rows until real service listings are published.
 *
 * @param string $query Search query.
 * @return array<int, array<string, string>>
 */
function snf_get_service_inquiry_search_results( $query = '' ) {
	/**
	 * Filter service inquiry search results.
	 * Empty by default until listings are added.
	 *
	 * @param array  $results Result rows.
	 * @param string $query   Search query.
	 */
	return apply_filters( 'snf_service_inquiry_search_results', array(), $query );
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

	// WooCommerce products always deep-link to their product page / checkout path.
	if ( $post && 'product' === $post->post_type ) {
		return get_permalink( $post_id );
	}

	if ( snf_is_lifestyle_concierge_workshop( $post_id ) ) {
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