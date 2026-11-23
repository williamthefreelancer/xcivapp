<?php
/**
 * JSON-LD structured data for AEO / search indexing.
 *
 * Emits Organization + WebSite sitewide on public views, plus Event / Product /
 * BreadcrumbList when the current request matches. Templates live in docs/schema/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Absolute site URL used as schema @id base (no trailing slash).
 *
 * @return string
 */
function snf_schema_home_url() {
	return untrailingslashit( home_url( '/' ) );
}

/**
 * Organization @id.
 *
 * @return string
 */
function snf_schema_organization_id() {
	return snf_schema_home_url() . '/#organization';
}

/**
 * WebSite @id.
 *
 * @return string
 */
function snf_schema_website_id() {
	return snf_schema_home_url() . '/#website';
}

/**
 * Whether the current request should emit public JSON-LD.
 *
 * @return bool
 */
function snf_schema_should_emit() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return false;
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return false;
	}
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return false;
	}

	if ( is_search() || is_404() ) {
		return false;
	}

	$protected = function_exists( 'snf_get_protected_page_slugs' )
		? snf_get_protected_page_slugs()
		: array();

	if ( is_page() && $protected ) {
		$page = get_queried_object();
		if ( $page && ! empty( $page->post_name ) && in_array( $page->post_name, $protected, true ) ) {
			return false;
		}
	}

	// Member-only Select Events listing is gated — do not advertise it as public.
	if ( is_page( 'select-events-by-members' ) ) {
		return false;
	}

	/**
	 * Filter whether JSON-LD should print on this request.
	 *
	 * @param bool $emit Default decision.
	 */
	return (bool) apply_filters( 'snf_schema_should_emit', true );
}

/**
 * Brand logo URL for Organization schema.
 *
 * @return string
 */
function snf_schema_logo_url() {
	$path = get_template_directory() . '/assets/icons/logo-white.svg';
	if ( file_exists( $path ) ) {
		return get_template_directory_uri() . '/assets/icons/logo-white.svg';
	}
	return '';
}

/**
 * Parse a display price like "$497.00" into a decimal string for Offer.price.
 *
 * @param string $price_label Price label from event/product helpers.
 * @return string Empty when unknown.
 */
function snf_schema_parse_price_amount( $price_label ) {
	$price_label = html_entity_decode( wp_strip_all_tags( (string) $price_label ), ENT_QUOTES, 'UTF-8' );
	if ( ! preg_match( '/(\d+(?:[.,]\d{1,2})?)/', $price_label, $m ) ) {
		return '';
	}
	return str_replace( ',', '', $m[1] );
}

/**
 * Build Organization node.
 *
 * @return array<string, mixed>
 */
function snf_schema_organization_node() {
	$home = snf_schema_home_url();
	$desc = get_bloginfo( 'description', 'display' );
	if ( ! $desc ) {
		$desc = __( 'Super Ninety Four — private circuit events, membership, and reciprocal club programs.', 'super-ninety-four' );
	}

	$node = array(
		'@type'           => 'Organization',
		'@id'             => snf_schema_organization_id(),
		'name'            => 'Super Ninety Four',
		'alternateName'   => array( 'SNF', 'SUPER NINETY FOUR' ),
		'url'             => $home . '/',
		'description'     => wp_strip_all_tags( $desc ),
	);

	$logo = snf_schema_logo_url();
	if ( $logo ) {
		$node['logo'] = array(
			'@type' => 'ImageObject',
			'url'   => $logo,
		);
	}

	$apply = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'apply' ) : $home . '/apply/';
	$node['contactPoint'] = array(
		array(
			'@type'             => 'ContactPoint',
			'contactType'       => 'customer support',
			'url'               => $apply,
			'availableLanguage' => array( 'English' ),
		),
	);

	/**
	 * Filter Organization JSON-LD node (e.g. add sameAs social URLs).
	 *
	 * @param array<string, mixed> $node Organization node.
	 */
	return apply_filters( 'snf_schema_organization_node', $node );
}

/**
 * Build WebSite node.
 *
 * @return array<string, mixed>
 */
function snf_schema_website_node() {
	$home = snf_schema_home_url();
	$desc = get_bloginfo( 'description', 'display' );

	$node = array(
		'@type'     => 'WebSite',
		'@id'       => snf_schema_website_id(),
		'url'       => $home . '/',
		'name'      => get_bloginfo( 'name', 'display' ) ?: 'Super Ninety Four',
		'publisher' => array( '@id' => snf_schema_organization_id() ),
		'inLanguage'=> 'en-US',
	);

	if ( $desc ) {
		$node['description'] = wp_strip_all_tags( $desc );
	}

	$node['potentialAction'] = array(
		'@type'       => 'SearchAction',
		'target'      => array(
			'@type'       => 'EntryPoint',
			'urlTemplate' => $home . '/?s={search_term_string}',
		),
		'query-input' => 'required name=search_term_string',
	);

	/**
	 * Filter WebSite JSON-LD node.
	 *
	 * @param array<string, mixed> $node WebSite node.
	 */
	return apply_filters( 'snf_schema_website_node', $node );
}

/**
 * Build Place node from a free-text location label.
 *
 * @param string $location Location string from event meta.
 * @return array<string, mixed>|null
 */
function snf_schema_place_from_location( $location ) {
	$location = trim( wp_strip_all_tags( (string) $location ) );
	if ( '' === $location ) {
		return null;
	}

	$place = array(
		'@type' => 'Place',
		'name'  => $location,
	);

	// Light parse for "City, ST" patterns — skip multi-leg route strings.
	if ( preg_match( '/^([^,]+),\s*([A-Z]{2})(?:\s|$)/', $location, $m ) ) {
		$place['address'] = array(
			'@type'           => 'PostalAddress',
			'addressLocality' => trim( $m[1] ),
			'addressRegion'   => $m[2],
			'addressCountry'  => 'US',
		);
	}

	return $place;
}

/**
 * ISO-8601 start datetime from event meta, or empty.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function snf_schema_event_start_iso( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}

	$is_product = ( 'product' === $post->post_type );
	$starts_at  = (string) get_post_meta( $post_id, $is_product ? '_snf_starts_at' : 'snf_event_starts_at', true );
	if ( ! $starts_at && $is_product ) {
		$starts_at = (string) get_post_meta( $post_id, 'snf_event_starts_at', true );
	}
	if ( ! $starts_at ) {
		return '';
	}

	$dt = date_create( $starts_at, wp_timezone() );
	if ( ! $dt ) {
		return '';
	}

	return $dt->format( DATE_ATOM );
}

/**
 * Whether the current singular is a public event landing (CPT or product).
 *
 * @return bool
 */
function snf_schema_is_event_context() {
	if ( is_singular( 'event' ) ) {
		return true;
	}

	if ( function_exists( 'is_product' ) && is_product() ) {
		$post_id = get_the_ID();
		if ( function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( $post_id ) ) {
			return true;
		}
		if ( function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( $post_id ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Build Event node for the current event/product.
 *
 * @param int|null $post_id Post ID.
 * @return array<string, mixed>|null
 */
function snf_schema_event_node( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( $post_id < 1 || ! function_exists( 'snf_get_event_single_data' ) ) {
		return null;
	}

	$data = snf_get_event_single_data( $post_id );
	if ( ! $data || empty( $data['title'] ) ) {
		return null;
	}

	$url = get_permalink( $post_id );
	if ( ! $url ) {
		return null;
	}

	$node = array(
		'@type'               => 'Event',
		'@id'                 => trailingslashit( $url ) . '#event',
		'name'                => wp_strip_all_tags( (string) $data['title'] ),
		'url'                 => $url,
		'eventStatus'         => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		'organizer'           => array( '@id' => snf_schema_organization_id() ),
		'isAccessibleForFree' => false,
	);

	if ( ! empty( $data['description'] ) ) {
		$node['description'] = wp_strip_all_tags( (string) $data['description'] );
	}

	if ( ! empty( $data['image'] ) ) {
		$node['image'] = array( (string) $data['image'] );
	}

	$start = snf_schema_event_start_iso( $post_id );
	if ( $start ) {
		$node['startDate'] = $start;
	}

	$place = snf_schema_place_from_location( isset( $data['location'] ) ? $data['location'] : '' );
	if ( $place ) {
		$node['location'] = $place;
	}

	$offer_url = ! empty( $data['action_url'] ) ? (string) $data['action_url'] : $url;
	$amount    = snf_schema_parse_price_amount( isset( $data['price'] ) ? $data['price'] : '' );
	$offer     = array(
		'@type'        => 'Offer',
		'url'          => $offer_url,
		'priceCurrency'=> 'USD',
		'availability' => 'https://schema.org/InStock',
	);
	if ( '' !== $amount ) {
		$offer['price'] = $amount;
	}
	$node['offers'] = $offer;

	/**
	 * Filter Event JSON-LD node.
	 *
	 * @param array<string, mixed> $node    Event node.
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $data    snf_get_event_single_data payload.
	 */
	return apply_filters( 'snf_schema_event_node', $node, $post_id, $data );
}

/**
 * BreadcrumbList for event landings.
 *
 * @param int|null $post_id Post ID.
 * @return array<string, mixed>|null
 */
function snf_schema_event_breadcrumb_node( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$post    = get_post( $post_id );
	if ( ! $post ) {
		return null;
	}

	$home = snf_schema_home_url() . '/';
	$url  = get_permalink( $post_id );
	if ( ! $url ) {
		return null;
	}

	if ( function_exists( 'snf_is_member_select_event' ) && snf_is_member_select_event( $post ) ) {
		$select_slug = 'select-events-by-members';
	} elseif ( function_exists( 'snf_is_public_select_event' ) && snf_is_public_select_event( $post ) ) {
		$select_slug = 'select-events-by-non-members';
	} else {
		$select_slug = 'select-events-by-non-members';
	}

	$select_url   = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( $select_slug ) : $home;
	$select_label = __( 'Select Events', 'super-ninety-four' );

	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => __( 'Home', 'super-ninety-four' ),
			'item'     => $home,
		),
		array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => $select_label,
			'item'     => $select_url,
		),
		array(
			'@type'    => 'ListItem',
			'position' => 3,
			'name'     => wp_strip_all_tags( get_the_title( $post ) ),
			'item'     => $url,
		),
	);

	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
}

/**
 * Product node for public reciprocal / service quote products (non-event).
 *
 * @param int|null $post_id Product ID.
 * @return array<string, mixed>|null
 */
function snf_schema_product_node( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( $post_id < 1 || ! function_exists( 'wc_get_product' ) ) {
		return null;
	}

	// Events use Event schema instead.
	if ( function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( $post_id ) ) {
		return null;
	}
	if ( function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( $post_id ) ) {
		return null;
	}

	$product = wc_get_product( $post_id );
	if ( ! $product ) {
		return null;
	}

	$url = get_permalink( $post_id );
	if ( ! $url ) {
		return null;
	}

	$desc = $product->get_short_description();
	if ( ! $desc ) {
		$desc = $product->get_description();
	}
	$desc = wp_strip_all_tags( (string) $desc );

	$node = array(
		'@type' => 'Product',
		'@id'   => trailingslashit( $url ) . '#product',
		'name'  => wp_strip_all_tags( $product->get_name() ),
		'url'   => $url,
		'brand' => array(
			'@type' => 'Brand',
			'name'  => 'Super Ninety Four',
		),
	);

	if ( $desc ) {
		$node['description'] = wp_trim_words( $desc, 90 );
	}

	$image = get_the_post_thumbnail_url( $post_id, 'full' );
	if ( $image ) {
		$node['image'] = array( $image );
	}

	$price = $product->get_price();
	$offer = array(
		'@type'         => 'Offer',
		'url'           => $url,
		'priceCurrency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
		'availability'  => $product->is_in_stock()
			? 'https://schema.org/InStock'
			: 'https://schema.org/OutOfStock',
		'seller'        => array( '@id' => snf_schema_organization_id() ),
	);
	if ( '' !== (string) $price && null !== $price ) {
		$offer['price'] = (string) $price;
	}
	$node['offers'] = $offer;

	/**
	 * Filter Product JSON-LD node.
	 *
	 * @param array<string, mixed> $node    Product node.
	 * @param int                  $post_id Product ID.
	 */
	return apply_filters( 'snf_schema_product_node', $node, $post_id );
}

/**
 * FAQPage JSON-LD from home content accordion.
 *
 * @return array<string, mixed>|null
 */
function snf_schema_home_faq_node() {
	if ( ! is_front_page() || ! function_exists( 'snf_get_home_content' ) ) {
		return null;
	}

	$home = snf_get_home_content();
	$faq  = isset( $home['rules_faq']['faq'] ) ? $home['rules_faq']['faq'] : array();
	if ( ! $faq ) {
		return null;
	}

	$entities = array();
	foreach ( $faq as $item ) {
		if ( empty( $item['q'] ) || empty( $item['a'] ) ) {
			continue;
		}
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $item['q'] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( $item['a'] ),
			),
		);
	}

	if ( ! $entities ) {
		return null;
	}

	$node = array(
		'@type'      => 'FAQPage',
		'@id'        => snf_schema_home_url() . '/#faq',
		'url'        => snf_schema_home_url() . '/',
		'mainEntity' => $entities,
	);

	/**
	 * Filter home FAQPage JSON-LD node.
	 *
	 * @param array<string, mixed> $node FAQ node.
	 */
	return apply_filters( 'snf_schema_home_faq_node', $node );
}

/**
 * Assemble @graph for the current request.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_schema_build_graph() {
	$graph = array(
		snf_schema_organization_node(),
		snf_schema_website_node(),
	);

	if ( snf_schema_is_event_context() ) {
		$event = snf_schema_event_node();
		if ( $event ) {
			$graph[] = $event;
		}
		$crumbs = snf_schema_event_breadcrumb_node();
		if ( $crumbs ) {
			$graph[] = $crumbs;
		}
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$product = snf_schema_product_node();
		if ( $product ) {
			$graph[] = $product;
		}
	}

	$home_faq = snf_schema_home_faq_node();
	if ( $home_faq ) {
		$graph[] = $home_faq;
	}

	/**
	 * Filter the full JSON-LD @graph.
	 *
	 * @param array<int, array<string, mixed>> $graph Graph nodes.
	 */
	return apply_filters( 'snf_schema_graph', $graph );
}

/**
 * Print JSON-LD in wp_head.
 */
function snf_schema_print_json_ld() {
	if ( ! snf_schema_should_emit() ) {
		return;
	}

	$graph = snf_schema_build_graph();
	if ( empty( $graph ) ) {
		return;
	}

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	$json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( ! $json ) {
		return;
	}

	echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'snf_schema_print_json_ld', 5 );
