<?php
/**
 * Super Ninety Four theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SNF_VERSION', '2.38.3' );

$snf_autoload = get_template_directory() . '/vendor/autoload.php';
if ( file_exists( $snf_autoload ) ) {
	require_once $snf_autoload;
}

if ( class_exists( '\SNF\Support\Bootstrap' ) ) {
	\SNF\Support\Bootstrap::init();
} else {
	/**
	 * Soften missing-Composer deploys: warn admins instead of silent broken hubs.
	 */
	$snf_missing_vendor_notice = static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'Super Ninety Four: run composer install in the theme directory (vendor/autoload.php is missing). App pages will not load until Composer dependencies are installed.', 'super-ninety-four' );
		echo '</p></div>';
	};
	add_action( 'admin_notices', $snf_missing_vendor_notice );
	add_action(
		'admin_bar_menu',
		static function ( $wp_admin_bar ) {
			if ( ! current_user_can( 'manage_options' ) || ! is_object( $wp_admin_bar ) ) {
				return;
			}
			$wp_admin_bar->add_node(
				array(
					'id'    => 'snf-missing-vendor',
					'title' => __( 'SNF: run composer install', 'super-ninety-four' ),
					'href'  => admin_url( 'themes.php' ),
					'meta'  => array(
						'class' => 'snf-admin-bar-error',
					),
				)
			);
		},
		100
	);
	add_action(
		'template_redirect',
		static function () {
			if ( ! current_user_can( 'manage_options' ) || is_admin() ) {
				return;
			}
			add_action(
				'wp_footer',
				static function () {
					echo '<div class="snf-vendor-missing" role="alert" style="position:fixed;inset-inline:1rem;inset-block-end:1rem;z-index:99999;background:#111;color:#fff;margin:0;font:0.875rem/1.4 ui-sans-serif,system-ui,sans-serif;border-radius:0.375rem;">';
					echo esc_html__( 'Super Ninety Four: vendor/autoload.php is missing. Run composer install in the theme directory.', 'super-ninety-four' );
					echo '</div>';
				},
				5
			);
		}
	);
}

function snf_setup() {
	// Block theme: core provides title-tag / html5. Keep media + nav locations
	// used by snf/site-header and snf/site-footer dynamic blocks.
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary header', 'super-ninety-four' ),
			'account' => __( 'Account dropdown', 'super-ninety-four' ),
			'footer'  => __( 'Footer legal', 'super-ninety-four' ),
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
			'show_in_menu' => false,
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
			'template' => 'home-page',
		),
		'inquiries' => array(
			'title'    => 'Service Inquiries',
			'template' => 'public-content',
		),
		'videos-audio' => array(
			'title'    => 'Videos & Audio',
			'template' => 'videos-audio',
		),
		'newsletter' => array(
			'title'    => 'Newsletter',
			'template' => 'newsletter',
		),
		'thank-you-newsletter' => array(
			'title'    => 'Newsletter Thank You',
			'template' => 'thank-you-newsletter',
		),
		'find-other-reciprocal-programs' => array(
			'title'    => 'Find Other Reciprocal Programs',
			'template' => 'reciprocal-hub',
		),
		'messages' => array(
			'title'    => 'Messages',
			'template' => 'messages',
		),
		'billing-details' => array(
			'title'    => 'Billing Details',
			'template' => 'billing-details',
		),
		'member-login' => array(
			'title'    => 'Member Login',
			'template' => 'member-login',
		),
		'apply' => array(
			'title'    => 'Apply',
			'template' => 'apply-hub',
		),
		'under-consideration' => array(
			'title'    => 'Under Consideration',
			'template' => 'under-consideration',
		),
		'select-events-by-members' => array(
			'title'    => 'Select Events (Members)',
			'template' => 'select-events',
		),
		'select-events-by-non-members' => array(
			'title'    => 'Select Events (Non-Members)',
			'template' => 'select-events',
		),
		'community' => array(
			'title'    => 'Community',
			'template' => 'community',
		),
		'privacy-policy' => array(
			'title'    => 'Privacy Policy',
			'template' => 'legal',
		),
		'terms-of-service' => array(
			'title'    => 'Terms of Service',
			'template' => 'legal',
		),
		'cookie-policy' => array(
			'title'    => 'Cookie Policy',
			'template' => 'legal',
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
		// Dropped from one-pager marketing surface (events / reciprocal / inquiries kept).
		'about',
		'contact',
		'expo',
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

/**
 * Legal / policy links shown in the site footer.
 *
 * @return array<int, array{label:string,url:string}>
 */
function snf_get_footer_legal_links() {
	$links = array(
		array(
			'label' => __( 'Privacy Policy', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'privacy-policy' ),
		),
		array(
			'label' => __( 'Terms of Service', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'terms-of-service' ),
		),
		array(
			'label' => __( 'Cookie Policy', 'super-ninety-four' ),
			'url'   => snf_get_page_url( 'cookie-policy' ),
		),
	);

	$refund = get_page_by_path( 'refund_returns' );
	if ( ! $refund && function_exists( 'wc_get_page_id' ) ) {
		$refund_id = (int) wc_get_page_id( 'refund_returns' );
		if ( $refund_id > 0 ) {
			$refund = get_post( $refund_id );
		}
	}
	if ( $refund ) {
		$links[] = array(
			'label' => __( 'Refund & Returns', 'super-ninety-four' ),
			'url'   => get_permalink( $refund ),
		);
	}

	/**
	 * Filter footer legal links.
	 *
	 * @param array<int, array{label:string,url:string}> $links Links.
	 */
	return apply_filters( 'snf_footer_legal_links', $links );
}

/**
 * Starter legal copy ? only applied when the page body is empty.
 *
 * @param string $slug Page slug.
 * @return string Block markup or empty.
 */
function snf_get_legal_starter_content( $slug ) {
	$slug = sanitize_title( (string) $slug );

	if ( 'privacy-policy' === $slug ) {
		return <<<'HTML'
<!-- wp:paragraph -->
<p>SUPER NINETY FOUR ("we", "us") collects account, membership, order, and communications data needed to run the club, Expo marketplace, events, and related services. This may include name, email, billing details processed by Stripe, and content you submit through forms or member tools.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>We use this information to provide membership access, process payments, send transactional notices, improve the site, and meet legal obligations. Payment card data is handled by Stripe; we do not store full card numbers on our servers.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>We share data with service providers that help us operate (hosting, payments, email/CRM, analytics) and when required by law. We retain records as long as needed for membership, accounting, and security. Contact us to request access, correction, or deletion where applicable.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><em>This is a starter policy for launch. Have counsel review and replace it with your final privacy policy.</em></p>
<!-- /wp:paragraph -->
HTML;
	}

	if ( 'refund_returns' === $slug ) {
		return <<<'HTML'
<!-- wp:paragraph -->
<p>Membership dues, event tickets, and digital or scheduled services are generally non-refundable once purchase is complete, except where required by law or when SUPER NINETY FOUR cancels an offering.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>If an event is postponed or cancelled, we will offer a transfer, credit, or refund according to the notice we send for that event. Chargebacks should be a last resort ? email us first so we can resolve the issue.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Physical merchandise (if offered) that arrives damaged or incorrect may be eligible for return within 14 days of delivery when unused and in original packaging. Contact us with your order number to start a return.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><em>This is a starter refund policy for launch. Update it to match your final commerce terms.</em></p>
<!-- /wp:paragraph -->
HTML;
	}

	return '';
}

/**
 * Assign privacy + refund templates; seed empty legal bodies with starter copy.
 */
function snf_ensure_legal_page_content() {
	$privacy = get_page_by_path( 'privacy-policy' );
	if ( $privacy ) {
		update_option( 'wp_page_for_privacy_policy', (int) $privacy->ID );
		update_post_meta( (int) $privacy->ID, '_wp_page_template', 'legal' );

		if ( '' === trim( (string) $privacy->post_content ) ) {
			$starter = snf_get_legal_starter_content( 'privacy-policy' );
			if ( '' !== $starter ) {
				wp_update_post(
					array(
						'ID'           => (int) $privacy->ID,
						'post_content' => $starter,
					)
				);
			}
		}
	}

	$refund = get_page_by_path( 'refund_returns' );
	if ( $refund ) {
		update_post_meta( (int) $refund->ID, '_wp_page_template', 'legal' );

		if ( '' === trim( (string) $refund->post_content ) ) {
			$starter = snf_get_legal_starter_content( 'refund_returns' );
			if ( '' !== $starter ) {
				wp_update_post(
					array(
						'ID'           => (int) $refund->ID,
						'post_content' => $starter,
					)
				);
			}
		}
	}
}

/**
 * One-time hard-delete of obsolete page slugs (not on every request).
 *
 * Runs on theme switch and once per obsolete-slug list fingerprint so recreating
 * a listed slug later is not immediately destroyed on the next front-end hit.
 */
function snf_delete_obsolete_site_pages() {
	if ( wp_installing() ) {
		return;
	}

	$site_pages = snf_get_site_pages();
	$slugs      = snf_get_obsolete_page_slugs();
	$fingerprint = md5( implode( '|', $slugs ) );
	if ( get_option( 'snf_obsolete_pages_cleaned' ) === $fingerprint ) {
		return;
	}

	foreach ( $slugs as $obsolete_slug ) {
		if ( isset( $site_pages[ $obsolete_slug ] ) ) {
			continue;
		}
		$obsolete = get_page_by_path( $obsolete_slug );
		if ( $obsolete ) {
			wp_delete_post( $obsolete->ID, true );
		}
	}

	update_option( 'snf_obsolete_pages_cleaned', $fingerprint, false );
}

function snf_ensure_site_pages() {
	$site_pages = snf_get_site_pages();

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
				'post_title'   => $config['title'],
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_wp_page_template', $config['template'] );
		}
	}

	snf_ensure_home_is_front_page();
	snf_ensure_legal_page_content();

	if ( function_exists( 'snf_wc_ensure_store_pages' ) ) {
		snf_wc_ensure_store_pages();
	}
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
add_action( 'after_switch_theme', 'snf_delete_obsolete_site_pages' );
add_action( 'admin_init', 'snf_delete_obsolete_site_pages', 25 );
add_action( 'after_switch_theme', 'snf_ensure_site_pages' );
add_action( 'init', 'snf_ensure_site_pages', 20 );



/**
 * Map legacy event CPT slugs to current WooCommerce product slugs.
 *
 * @param string $slug Requested slug.
 * @return string
 */
function snf_resolve_listing_product_slug( $slug ) {
	$slug    = sanitize_title( (string) $slug );
	$aliases = array(
		'the-eight-hundred-plus-journeys' => 'the-eight-hundred-plus-journeys-event',
	);

	return isset( $aliases[ $slug ] ) ? $aliases[ $slug ] : $slug;
}

/**
 * Resolve a card/landing image for an event slug (explicit file, product thumb, or default).
 *
 * @param string           $slug       Product / event slug.
 * @param string           $image_file Optional filename under assets/images/events/ or relative path under assets/images/.
 * @param \WC_Product|null $product    Optional Woo product.
 * @param int              $post_id    Optional post ID for thumbnail lookup.
 * @return string
 */
function snf_resolve_event_card_image( $slug, $image_file = '', $product = null, $post_id = 0 ) {
	$slug       = sanitize_title( (string) $slug );
	$image_file = (string) $image_file;

	if ( $image_file ) {
		$rel = ltrim( $image_file, '/' );
		if ( false === strpos( $rel, '/' ) ) {
			$rel = 'events/' . $rel;
		}
		return get_template_directory_uri() . '/assets/images/' . $rel;
	}

	if ( $product && $product->get_image_id() ) {
		$thumb = wp_get_attachment_image_url( $product->get_image_id(), 'medium_large' );
		if ( $thumb ) {
			return $thumb;
		}
	}

	if ( $post_id ) {
		$thumb = get_the_post_thumbnail_url( $post_id, 'medium_large' );
		if ( $thumb ) {
			return $thumb;
		}
	}

	return function_exists( 'snf_get_event_default_image_url' )
		? snf_get_event_default_image_url( $slug )
		: '';
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

		return array(
			'title' => $product ? $product->get_name() : __( 'The Lifestyle Concierge Club Workshop', 'super-ninety-four' ),
			'meta'  => trim( implode( ' · ', array_filter( array( $location, $timing ) ) ) ),
			'url'   => snf_get_workshop_landing_url(),
			'image' => snf_resolve_event_card_image( $slug, $image_file, $product, $product_post ? (int) $product_post->ID : 0 ),
		);
	}

	$product_post = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $product_post && class_exists( 'WooCommerce' ) ) {
		$product = wc_get_product( $product_post->ID );
		if ( $product ) {
			$location = (string) get_post_meta( $product_post->ID, '_snf_location', true );
			$timing   = (string) get_post_meta( $product_post->ID, '_snf_when', true );

			return array(
				'title' => $product->get_name(),
				'meta'  => trim( implode( ' · ', array_filter( array( $location, $timing ) ) ) ),
				'url'   => get_permalink( $product_post->ID ),
				'image' => snf_resolve_event_card_image( $slug, $image_file, $product, (int) $product_post->ID ),
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
				'image' => snf_resolve_event_card_image( $slug, $image_file ),
			);
		}
		return null;
	}

	$location = get_post_meta( $event->ID, 'snf_event_location', true );
	$timing   = get_post_meta( $event->ID, 'snf_event_when', true );

	return array(
		'title' => get_the_title( $event ),
		'meta'  => trim( implode( ' · ', array_filter( array( $location, $timing ) ) ) ),
		'url'   => snf_get_event_action_url( $event->ID ),
		'image' => snf_resolve_event_card_image( $slug, $image_file, null, (int) $event->ID ),
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
				snf_get_dashboard_event_card_by_slug( 'the-township-adventures-event', 'township-adventures-event.jpg' ),
				snf_get_dashboard_event_card_by_slug( 'the-eight-hundred-plus-journeys-event', 'eight-hundred-journeys-event.jpg' ),
				snf_get_dashboard_event_card_by_slug( 'the-city-club-private-party', 'city-club-private-party.webp' ),
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
 * Events (non-members) cards — public Select Events catalog.
 *
 * @return array<int, array<string, string>>
 */
function snf_get_dashboard_non_member_event_cards() {
	$slugs = function_exists( 'snf_get_public_select_event_slugs' )
		? snf_get_public_select_event_slugs()
		: array();

	$cards = array();
	foreach ( $slugs as $slug ) {
		$card = snf_get_dashboard_event_card_by_slug( $slug );
		if ( $card ) {
			$cards[] = $card;
		}
	}

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
				'meta'  => __( 'MA North Shore & New Hampshire only', 'super-ninety-four' ),
				'url'   => $url_for( 'the-clubs-at-wyoma-square' ),
				'image' => $img_base . 'the-clubs-at-wyoma-square.jpg',
			),
			array(
				'title' => __( 'The City Clubs of SUPER NINETY FOUR', 'super-ninety-four' ),
				'meta'  => __( 'Harrisburg / Back Creek → North Shore interstate', 'super-ninety-four' ),
				'url'   => $url_for( 'the-city-clubs-at-super-ninety-four' ),
				'image' => $img_base . 'the-city-clubs-at-super-ninety-four.jpg',
			),
			array(
				'title' => __( 'The Clubs at Back Creek', 'super-ninety-four' ),
				'meta'  => __( 'North Carolina only', 'super-ninety-four' ),
				'url'   => $url_for( 'the-clubs-at-back-creek' ),
				'image' => $img_base . 'the-clubs-at-back-creek.jpg',
			),
		);
	}

	$cards = array_values(
		array_filter(
			$cards,
			static function ( $card ) {
				$url = isset( $card['url'] ) ? (string) $card['url'] : '';
				$title = isset( $card['title'] ) ? (string) $card['title'] : '';
				if ( false !== stripos( $url, 'badlands-bra' ) || false !== stripos( $title, 'badlands bra' ) ) {
					return false;
				}
				return true;
			}
		)
	);

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
			'image' => $img_base . 'litter-pickup-service-image-2.jpg',
		),
		array(
			'title' => __( 'Professional Line Sitting & Courier Service', 'super-ninety-four' ),
			'meta'  => __( 'Busy lines · Shopping runs · Handoffs', 'super-ninety-four' ),
			'url'   => call_user_func( $url_for, 'professional-line-sitting-courier-service' ),
			'image' => $img_base . 'lifestyle-service-image-1.jpg',
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
 * City Club Private Party product slug (Apply landing).
 *
 * @return string
 */
function snf_get_city_club_private_party_slug() {
	return 'the-city-club-private-party';
}

/**
 * Product slugs that use stepped checkout landings (contact → ticket).
 *
 * @return array<int, string>
 */
function snf_get_workshop_landing_slugs() {
	return array(
		snf_get_workshop_product_slug(),
		snf_get_city_club_private_party_slug(),
	);
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
 * City Club Private Party product URL.
 *
 * @return string
 */
function snf_get_city_club_private_party_url() {
	$product = get_page_by_path( snf_get_city_club_private_party_slug(), OBJECT, 'product' );
	if ( $product instanceof WP_Post && 'publish' === $product->post_status ) {
		return get_permalink( $product );
	}

	return home_url( '/product/' . snf_get_city_club_private_party_slug() . '/' );
}

/**
 * Public Apply CTA URL — City Club Private Party workshop landing.
 *
 * @return string
 */
function snf_get_apply_landing_url() {
	return snf_get_city_club_private_party_url();
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
 * Sister products for the Uber-style product switcher.
 *
 * @return array<int, array{id:string,label:string,short:string,glyph:string,blurb:string,url:string,current:bool,external:bool}>
 */
function snf_product_switcher_items() {
	$items = array(
		array(
			'id'       => 'superninetyfour',
			'label'    => __( 'Super Ninety Four', 'super-ninety-four' ),
			'short'    => __( 'SN4', 'super-ninety-four' ),
			'glyph'    => '94',
			'blurb'    => __( 'Culture, drops & merch', 'super-ninety-four' ),
			'url'      => home_url( '/' ),
			'current'  => true,
			'external' => false,
		),
		array(
			'id'       => 'courier',
			'label'    => __( 'XCIV Courier', 'super-ninety-four' ),
			'short'    => __( 'Courier', 'super-ninety-four' ),
			'glyph'    => 'C',
			'blurb'    => __( 'On-demand delivery & pickup', 'super-ninety-four' ),
			'url'      => 'https://xcivcourier.com/',
			'current'  => false,
			'external' => true,
		),
		array(
			'id'       => 'xcivsocial',
			'label'    => __( 'XCIV Social', 'super-ninety-four' ),
			'short'    => __( 'Social', 'super-ninety-four' ),
			'glyph'    => 'S',
			'blurb'    => __( 'Connect with the community', 'super-ninety-four' ),
			'url'      => 'https://xcivsocial.com/',
			'current'  => false,
			'external' => true,
		),
		array(
			'id'       => 'holdings',
			'label'    => __( 'SUPR XCIV Holdings', 'super-ninety-four' ),
			'short'    => __( 'Holdings', 'super-ninety-four' ),
			'glyph'    => 'H',
			'blurb'    => __( 'Parent company & platforms', 'super-ninety-four' ),
			'url'      => 'https://www.suprxcivplatformholdings.com/',
			'current'  => false,
			'external' => true,
		),
	);

	/**
	 * Filter product switcher destinations.
	 *
	 * @param array<int, array{id:string,label:string,short:string,glyph:string,blurb:string,url:string,current:bool,external:bool}> $items Products.
	 */
	return apply_filters( 'snf_product_switcher_items', $items );
}

/**
 * Render header product switcher (SN4 / Courier / Social).
 */
function snf_render_product_switcher() {
	$items   = snf_product_switcher_items();
	$current = array(
		'id'    => 'superninetyfour',
		'short' => __( 'SN4', 'super-ninety-four' ),
		'glyph' => '94',
		'label' => __( 'Super Ninety Four', 'super-ninety-four' ),
	);

	foreach ( $items as $item ) {
		if ( ! empty( $item['current'] ) ) {
			$current['id']    = (string) $item['id'];
			$current['short'] = (string) ( $item['short'] ?? $item['label'] );
			$current['glyph'] = (string) ( $item['glyph'] ?? ( function_exists( 'mb_substr' ) ? mb_substr( $current['short'], 0, 1 ) : substr( $current['short'], 0, 1 ) ) );
			$current['label'] = (string) $item['label'];
			break;
		}
	}

	$switch_label = sprintf(
		/* translators: %s: current product short name */
		__( 'Switch product, current: %s', 'super-ninety-four' ),
		$current['short']
	);
	?>
	<div class="snf-product-switcher" data-snf-product-switcher>
		<button
			type="button"
			class="snf-product-switcher__trigger"
			data-snf-product-switcher-trigger
			aria-expanded="false"
			aria-haspopup="menu"
			aria-controls="snf-product-switcher-menu"
			aria-label="<?php echo esc_attr( $switch_label ); ?>"
		>
			<span class="snf-product-switcher__glyph" aria-hidden="true"><?php echo esc_html( $current['glyph'] ); ?></span>
			<span class="snf-product-switcher__current"><?php echo esc_html( $current['short'] ); ?></span>
			<svg class="snf-product-switcher__chevron" width="12" height="12" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</button>

		<div
			id="snf-product-switcher-menu"
			class="snf-product-switcher__menu"
			data-snf-product-switcher-menu
			role="menu"
			aria-label="<?php esc_attr_e( 'Switch product', 'super-ninety-four' ); ?>"
			hidden
		>
			<?php foreach ( $items as $item ) : ?>
				<?php
				$is_current = ! empty( $item['current'] );
				$classes    = 'snf-product-switcher__item';
				if ( $is_current ) {
					$classes .= ' is-current';
				}
				$glyph = (string) ( $item['glyph'] ?? '' );
				$blurb = (string) ( $item['blurb'] ?? '' );
				?>
				<a
					class="<?php echo esc_attr( $classes ); ?>"
					role="menuitem"
					href="<?php echo esc_url( (string) $item['url'] ); ?>"
					<?php echo $is_current ? 'aria-current="page"' : ''; ?>
					<?php echo ! empty( $item['external'] ) ? 'rel="noopener noreferrer"' : ''; ?>
					data-snf-product="<?php echo esc_attr( (string) $item['id'] ); ?>"
				>
					<span class="snf-product-switcher__item-glyph" aria-hidden="true"><?php echo esc_html( $glyph ); ?></span>
					<span class="snf-product-switcher__item-text">
						<span class="snf-product-switcher__item-label"><?php echo esc_html( (string) $item['label'] ); ?></span>
						<?php if ( $blurb !== '' ) : ?>
							<span class="snf-product-switcher__item-blurb"><?php echo esc_html( $blurb ); ?></span>
						<?php endif; ?>
					</span>
					<?php if ( $is_current ) : ?>
						<svg class="snf-product-switcher__check" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					<?php else : ?>
						<span class="snf-product-switcher__check-spacer" aria-hidden="true"></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Center header navigation (audience-aware; BuddyPress-free).
 *
 * @return array<int, array<string, mixed>>
 */
function snf_primary_menu_items() {
	$home = trailingslashit( home_url( '/' ) );
	$is_front = is_front_page();

	$apply_url = function_exists( 'snf_get_apply_landing_url' )
		? snf_get_apply_landing_url()
		: snf_get_page_url( 'apply' );
	$on_apply  = is_page( 'apply' )
		|| ( function_exists( 'is_product' ) && is_product() && function_exists( 'snf_get_city_club_private_party_slug' ) && is_singular( 'product' ) && snf_get_city_club_private_party_slug() === get_post_field( 'post_name' ) );

	$items = array(
		array(
			'type'    => 'link',
			'label'   => __( 'Help', 'super-ninety-four' ),
			'url'     => $home . '#help',
			'current' => $is_front,
		),
		array(
			'type'    => 'link',
			'label'   => __( 'Fit', 'super-ninety-four' ),
			'url'     => $home . '#fit',
			'current' => false,
		),
		array(
			'type'    => 'link',
			'label'   => __( 'Membership', 'super-ninety-four' ),
			'url'     => $home . '#pricing',
			'current' => false,
		),
		array(
			'type'    => 'link',
			'label'   => __( 'Apply', 'super-ninety-four' ),
			'url'     => $apply_url,
			'current' => $on_apply,
		),
	);

	/**
	 * Filter primary header menu items.
	 *
	 * @param array $items Menu items.
	 */
	return apply_filters( 'snf_primary_menu_items', $items );
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
 * Guests use the profile icon as a direct Member Login link (no dropdown).
 * Members see the WP "Account dropdown" location when assigned, otherwise
 * the members-area hub links.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_account_menu_items() {
	if ( ! is_user_logged_in() ) {
		return array();
	}

	$assigned = snf_account_menu_items_from_location( 'account' );
	if ( ! empty( $assigned ) ) {
		return $assigned;
	}

	return snf_account_menu_items_members_area();
}

/**
 * Build account dropdown items from a registered nav menu location.
 *
 * @param string $location Theme menu location slug.
 * @return array<int, array<string, mixed>>
 */
function snf_account_menu_items_from_location( $location ) {
	$location = (string) $location;
	if ( '' === $location || ! has_nav_menu( $location ) ) {
		return array();
	}

	$locations = get_nav_menu_locations();
	$menu_id   = isset( $locations[ $location ] ) ? (int) $locations[ $location ] : 0;
	if ( $menu_id < 1 ) {
		return array();
	}

	$raw = wp_get_nav_menu_items( $menu_id );
	if ( empty( $raw ) || ! is_array( $raw ) ) {
		return array();
	}

	$by_parent = array();
	foreach ( $raw as $item ) {
		$parent = (int) $item->menu_item_parent;
		if ( ! isset( $by_parent[ $parent ] ) ) {
			$by_parent[ $parent ] = array();
		}
		$by_parent[ $parent ][] = $item;
	}

	if ( empty( $by_parent[0] ) ) {
		return array();
	}

	$items = array();
	foreach ( $by_parent[0] as $item ) {
		$label = (string) $item->title;
		$url   = (string) $item->url;
		$id    = (int) $item->ID;
		$kids  = isset( $by_parent[ $id ] ) ? $by_parent[ $id ] : array();

		if ( ! empty( $kids ) ) {
			$children = array();
			foreach ( $kids as $child ) {
				$children[] = array(
					'type'  => 'link',
					'label' => (string) $child->title,
					'url'   => (string) $child->url,
				);
			}
			$items[] = array(
				'type'     => 'club',
				'label'    => $label,
				'url'      => $url ? $url : '#',
				'children' => $children,
			);
			continue;
		}

		$entry = array(
			'type'  => 'link',
			'label' => $label,
			'url'   => $url ? $url : '#',
		);

		if ( false !== stripos( $label, 'message' ) ) {
			$entry['icon'] = 'messages';
		}

		$items[] = $entry;
	}

	return $items;
}

/**
 * Default members-area links for the account dropdown.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_account_menu_items_members_area() {
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$items    = array(
		array(
			'type'        => 'search',
			'label'       => __( 'Find other reciprocal programs', 'super-ninety-four' ),
			'url'         => snf_get_page_url( 'find-other-reciprocal-programs' ),
			'placeholder' => __( 'Find other reciprocal programs', 'super-ninety-four' ),
		),
	);

	if ( function_exists( 'snf_get_account_hub_nav' ) ) {
		foreach ( snf_get_account_hub_nav() as $nav ) {
			$slug  = isset( $nav['slug'] ) ? (string) $nav['slug'] : '';
			$label = isset( $nav['label'] ) ? (string) $nav['label'] : '';
			$url   = isset( $nav['url'] ) ? (string) $nav['url'] : '';
			if ( '' === $label || '' === $url || 'apply' === $slug ) {
				continue;
			}

			$entry = array(
				'type'  => 'link',
				'label' => $label,
				'url'   => $url,
			);

			if ( 'messages' === $slug ) {
				$entry['icon'] = 'messages';
			}

			if ( 'listings' === $slug ) {
				$entry['label'] = __( 'Search listings', 'super-ninety-four' );
			}

			$items[] = $entry;
		}

		return $items;
	}

	$items[] = array(
		'type'  => 'link',
		'label' => __( 'Dashboard', 'super-ninety-four' ),
		'url'   => snf_get_dashboard_url(),
	);
	$items[] = array(
		'type'  => 'link',
		'label' => __( 'Search listings', 'super-ninety-four' ),
		'url'   => $shop_url ? $shop_url : home_url( '/shop/' ),
	);
	$items[] = array(
		'type'  => 'link',
		'label' => __( 'Messages', 'super-ninety-four' ),
		'url'   => snf_get_bp_messages_url(),
		'icon'  => 'messages',
	);
	$items[] = array(
		'type'  => 'link',
		'label' => __( 'Orders', 'super-ninety-four' ),
		'url'   => snf_get_woo_account_url( 'orders' ),
	);
	$items[] = array(
		'type'  => 'link',
		'label' => __( 'Tickets', 'super-ninety-four' ),
		'url'   => snf_get_woo_account_url( 'tickets' ),
	);
	$items[] = array(
		'type'  => 'link',
		'label' => __( 'Membership', 'super-ninety-four' ),
		'url'   => snf_get_woo_account_url( 'membership' ),
	);
	$items[] = array(
		'type'  => 'link',
		'label' => __( 'Billing', 'super-ninety-four' ),
		'url'   => snf_get_woo_account_url( 'edit-address' ),
	);
	$items[] = array(
		'type'  => 'link',
		'label' => __( 'Profile', 'super-ninety-four' ),
		'url'   => snf_get_bp_profile_url(),
	);

	return $items;
}

/**
 * Whether a product uses a stepped checkout landing (contact steps + custom template).
 *
 * @param int|null $post_id Product / event post ID.
 * @return bool
 */
function snf_is_gathering_landing_product( $post_id = null ) {
	$post = get_post( $post_id ? $post_id : get_the_ID() );
	if ( ! $post ) {
		return false;
	}

	$post_id  = (int) $post->ID;
	$template = (string) get_post_meta( $post_id, 'snf_event_template', true );
	if ( in_array(
		$template,
		array(
			'lifestyle-concierge-workshop',
			'city-club-private-party-gathering',
			'city-club-private-party-workshop', // legacy meta key
		),
		true
	) ) {
		return true;
	}

	return in_array( $post->post_name, snf_get_workshop_landing_slugs(), true );
}

/**
 * Stepped-checkout landing template map for a product.
 *
 * @param int|null $post_id Product ID.
 * @return array{dir:string,part:string}
 */
function snf_get_stepped_checkout_landing( $post_id = null ) {
	$post = get_post( $post_id ? $post_id : get_the_ID() );
	if ( ! $post ) {
		return array(
			'dir'  => 'workshop',
			'part' => 'lifestyle-concierge',
		);
	}

	$template = (string) get_post_meta( (int) $post->ID, 'snf_event_template', true );
	$is_city  = in_array( $template, array( 'city-club-private-party-gathering', 'city-club-private-party-workshop' ), true )
		|| snf_get_city_club_private_party_slug() === $post->post_name;

	if ( $is_city ) {
		return array(
			'dir'  => 'gathering',
			'part' => 'city-club-private-party',
		);
	}

	return array(
		'dir'  => 'workshop',
		'part' => 'lifestyle-concierge',
	);
}

/**
 * @deprecated 2.36.0 Use snf_get_stepped_checkout_landing().
 *
 * @param int|null $post_id Product ID.
 * @return string
 */
function snf_get_workshop_template_part( $post_id = null ) {
	$landing = snf_get_stepped_checkout_landing( $post_id );
	return $landing['part'];
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

	return snf_get_workshop_product_slug() === $post->post_name;
}

/**
 * Redirect /apply/ to the City Club Private Party product landing.
 */
function snf_redirect_apply_to_city_club_party() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( ! is_page( 'apply' ) ) {
		return;
	}

	$target = snf_get_apply_landing_url();
	if ( ! $target ) {
		return;
	}

	wp_safe_redirect( $target, 302 );
	exit;
}
add_action( 'template_redirect', 'snf_redirect_apply_to_city_club_party', 5 );

/**
 * Ensure City Club Private Party product meta + $497 price.
 */
function snf_ensure_city_club_private_party_product() {
	if ( wp_installing() || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$version = '1.1.0-city-club-gathering-497';
	if ( get_option( 'snf_city_club_private_party_version' ) === $version ) {
		return;
	}

	$post = get_page_by_path( snf_get_city_club_private_party_slug(), OBJECT, 'product' );
	if ( ! $post instanceof WP_Post ) {
		$product_id = wp_insert_post(
			array(
				'post_title'   => __( 'The City Club Private Party', 'super-ninety-four' ),
				'post_name'    => snf_get_city_club_private_party_slug(),
				'post_status'  => 'publish',
				'post_type'    => 'product',
				'post_content' => __( 'Private City Clubs social gathering — coach, credentials, and a closed room.', 'super-ninety-four' ),
			),
			true
		);
		if ( is_wp_error( $product_id ) || ! $product_id ) {
			return;
		}
	} else {
		$product_id = (int) $post->ID;
	}

	update_post_meta( $product_id, 'snf_event_template', 'city-club-private-party-gathering' );
	update_post_meta( $product_id, 'snf_event_price', '$497.00' );
	update_post_meta( $product_id, '_regular_price', '497' );
	update_post_meta( $product_id, '_price', '497' );
	update_post_meta( $product_id, '_virtual', 'yes' );
	update_post_meta( $product_id, '_sold_individually', 'no' );
	wp_set_object_terms( $product_id, 'simple', 'product_type' );

	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients( $product_id );
	}

	update_option( 'snf_city_club_private_party_version', $version, false );
}
add_action( 'init', 'snf_ensure_city_club_private_party_product', 35 );

/**
 * Read workshop / gathering contact + step-2 custom fields from the add-to-cart request.
 *
 * @return array{name:string,email:string,phone:string,loom:string,checkout_details:string}
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

	$loom = isset( $_REQUEST['snf_workshop_loom'] ) ? esc_url_raw( wp_unslash( $_REQUEST['snf_workshop_loom'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$details = isset( $_REQUEST['snf_workshop_checkout_details'] ) ? sanitize_textarea_field( wp_unslash( $_REQUEST['snf_workshop_checkout_details'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return array(
		'name'             => $name,
		'email'            => $email,
		'phone'            => $phone,
		'loom'             => $loom,
		'checkout_details' => $details,
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
	if ( ! $passed || ! snf_is_gathering_landing_product( $product_id ) ) {
		return $passed;
	}

	$contact = snf_workshop_request_contact_fields();
	if ( '' === $contact['name'] || ! is_email( $contact['email'] ) || '' === $contact['phone'] ) {
		wc_add_notice( __( 'Please enter your full name, email, and phone before adding this ticket to your cart.', 'super-ninety-four' ), 'error' );
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
	if ( ! snf_is_gathering_landing_product( $product_id ) ) {
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
	if ( ! snf_is_gathering_landing_product( $product_id ) || ! function_exists( 'WC' ) || ! WC()->customer ) {
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

	if ( $product_id && snf_is_gathering_landing_product( $product_id ) && function_exists( 'wc_get_cart_url' ) ) {
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
		'snf_workshop_name'             => __( 'Full name', 'super-ninety-four' ),
		'snf_workshop_email'            => __( 'Your email', 'super-ninety-four' ),
		'snf_workshop_phone'            => __( 'Your phone', 'super-ninety-four' ),
		'snf_workshop_loom'             => __( 'Your loom video link', 'super-ninety-four' ),
		'snf_workshop_checkout_details' => __( 'Keep checkout details', 'super-ninety-four' ),
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
		'snf_workshop_name'             => __( 'Full name', 'super-ninety-four' ),
		'snf_workshop_email'            => __( 'Your email', 'super-ninety-four' ),
		'snf_workshop_phone'            => __( 'Your phone', 'super-ninety-four' ),
		'snf_workshop_loom'             => __( 'Your loom video link', 'super-ninety-four' ),
		'snf_workshop_checkout_details' => __( 'Keep checkout details', 'super-ninety-four' ),
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

	// Open-admission gatherings always use their product landings (guest purchase).
	if ( function_exists( 'snf_is_open_admission_event' ) && snf_is_open_admission_event( $post_id ) ) {
		if ( snf_is_lifestyle_concierge_workshop( $post_id ) ) {
			return snf_get_workshop_landing_url();
		}
		if ( function_exists( 'snf_get_city_club_private_party_url' ) && snf_get_city_club_private_party_slug() === get_post_field( 'post_name', $post_id ) ) {
			return snf_get_city_club_private_party_url();
		}
		return get_permalink( $post_id );
	}

	// Workshop always uses the dedicated product landing.
	if ( snf_is_lifestyle_concierge_workshop( $post_id ) ) {
		return snf_get_workshop_landing_url();
	}

	// WooCommerce products always deep-link to their product page / checkout path.
	if ( $post && 'product' === $post->post_type ) {
		return get_permalink( $post_id );
	}

	// Member + public select events keep the admissions / booking form on the event page.
	if ( ( function_exists( 'snf_is_member_select_event' ) && snf_is_member_select_event( $post_id ) )
		|| ( function_exists( 'snf_is_public_select_event' ) && snf_is_public_select_event( $post_id ) ) ) {
		return get_permalink( $post_id );
	}

	$args = array(
		'q'         => get_the_title( $post_id ),
		'snf_event' => $post_id,
	);

	// Non-member select events hand off to Apply (contact ? billing).
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

	if ( function_exists( 'snf_is_member_select_event' ) && snf_is_member_select_event( $post_id ) ) {
		return __( 'Apply to attend', 'super-ninety-four' );
	}

	if ( function_exists( 'snf_is_public_select_event' ) && snf_is_public_select_event( $post_id ) ) {
		return __( 'Check Availability', 'super-ninety-four' );
	}

	return __( 'Continue to Apply', 'super-ninety-four' );
}

/**
 * Deprecated: legacy workshop reservation admin-post.
 *
 * Paid workshop flow is WooCommerce add-to-cart on the workshop product.
 * Redirects to the product landing; does not create snf_reservation posts.
 */
function snf_handle_workshop_reserve() {
	if ( function_exists( '_deprecated_function' ) ) {
		_deprecated_function( __FUNCTION__, '2.29.0', 'WooCommerce add-to-cart on the workshop product' );
	}

	$redirect = function_exists( 'snf_get_workshop_landing_url' )
		? snf_get_workshop_landing_url()
		: home_url( '/' );

	wp_safe_redirect( add_query_arg( 'snf_workshop_legacy', '1', $redirect ) );
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
			'show_in_menu' => false,
			'supports'     => array( 'title', 'editor' ),
			'menu_icon'    => 'dashicons-tickets-alt',
		)
	);
}
add_action( 'init', 'snf_register_reservation_cpt' );
