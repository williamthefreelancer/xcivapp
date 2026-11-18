<?php
/**
 * SureForms → Super Ninety Four sales-funnel bridge.
 *
 * Keeps theme WooCommerce forms (Apply, workshop checkout, quote ATC, reciprocal)
 * as the transactional path. SureForms feeds marketing / lead capture into the
 * same funnel via submit hooks, webhooks, and optional Pro native actions.
 *
 * @package SuperNinetyFour
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marker class for SureForms Pro plugin_detection.
 */
class SNF_SureForms_Funnel {
	/**
	 * Theme funnel bridge is always available when the theme loads.
	 *
	 * @return bool
	 */
	public static function is_ready() {
		return true;
	}
}

/**
 * Funnel intent slugs keyed for mapping + REST.
 *
 * @return array<string, string>
 */
function snf_get_sureforms_funnel_intents() {
	$intents = array(
		'contact'              => __( 'Contact inquiry', 'super-ninety-four' ),
		'newsletter'           => __( 'Newsletter signup', 'super-ninety-four' ),
		'service_quote'        => __( 'Service quote lead', 'super-ninety-four' ),
		'workshop_interest'    => __( 'Workshop interest', 'super-ninety-four' ),
		'reciprocal_interest'  => __( 'Reciprocal club interest', 'super-ninety-four' ),
		'apply_lead'           => __( 'Membership apply lead', 'super-ninety-four' ),
	);

	/**
	 * Filter SureForms funnel intent labels.
	 *
	 * @param array<string, string> $intents Intent slug => label.
	 */
	return apply_filters( 'snf_sureforms_funnel_intents', $intents );
}

/**
 * Embed slots that can render a mapped SureForms form ID.
 *
 * @return array<string, string>
 */
function snf_get_sureforms_embed_slots() {
	$slots = array(
		'contact'     => __( 'Contact page', 'super-ninety-four' ),
		'newsletter'  => __( 'Newsletter page', 'super-ninety-four' ),
		'reciprocal'  => __( 'Reciprocal club capture', 'super-ninety-four' ),
		'quote'       => __( 'Service quote landings (all)', 'super-ninety-four' ),
	);

	if ( function_exists( 'snf_get_service_quote_product_slugs' ) ) {
		foreach ( snf_get_service_quote_product_slugs() as $slug ) {
			$slots[ 'quote_' . sanitize_title( $slug ) ] = sprintf(
				/* translators: %s: product slug */
				__( 'Service quote: %s', 'super-ninety-four' ),
				$slug
			);
		}
	}

	/**
	 * Filter SureForms embed slot labels.
	 *
	 * @param array<string, string> $slots Slot key => label.
	 */
	return apply_filters( 'snf_sureforms_embed_slots', $slots );
}

/**
 * Form ID → intent map (option + filter).
 *
 * @return array<int, string>
 */
function snf_get_sureforms_funnel_map() {
	$stored = get_option( 'snf_sureforms_funnel_map', array() );
	$map    = array();

	if ( is_array( $stored ) ) {
		foreach ( $stored as $form_id => $intent ) {
			$form_id = absint( $form_id );
			$intent  = sanitize_key( (string) $intent );
			if ( $form_id && isset( snf_get_sureforms_funnel_intents()[ $intent ] ) ) {
				$map[ $form_id ] = $intent;
			}
		}
	}

	/**
	 * Filter SureForms form ID → funnel intent map.
	 *
	 * @param array<int, string> $map Form ID => intent.
	 */
	return apply_filters( 'snf_sureforms_funnel_map', $map );
}

/**
 * Embed slot → SureForms form ID map.
 *
 * @return array<string, int>
 */
function snf_get_sureforms_embed_ids() {
	$stored = get_option( 'snf_sureforms_embed_ids', array() );
	$ids    = array();

	if ( is_array( $stored ) ) {
		foreach ( $stored as $slot => $form_id ) {
			$slot    = sanitize_key( (string) $slot );
			$form_id = absint( $form_id );
			if ( $slot && $form_id ) {
				$ids[ $slot ] = $form_id;
			}
		}
	}

	/**
	 * Filter SureForms embed slot → form ID map.
	 *
	 * @param array<string, int> $ids Slot => form ID.
	 */
	return apply_filters( 'snf_sureforms_embed_ids', $ids );
}

/**
 * Shared webhook secret for SureForms → REST funnel posts.
 *
 * @return string
 */
function snf_get_funnel_webhook_secret() {
	if ( defined( 'SNF_FUNNEL_WEBHOOK_SECRET' ) && SNF_FUNNEL_WEBHOOK_SECRET ) {
		return (string) SNF_FUNNEL_WEBHOOK_SECRET;
	}
	return (string) get_option( 'snf_funnel_webhook_secret', '' );
}

/**
 * Resolve SureForms form ID for an embed slot (supports quote:slug fallback to quote).
 *
 * @param string $slot Slot key.
 * @return int
 */
function snf_get_sureforms_form_id_for_slot( $slot ) {
	$slot = sanitize_key( (string) $slot );
	$ids  = snf_get_sureforms_embed_ids();

	if ( ! empty( $ids[ $slot ] ) ) {
		return (int) $ids[ $slot ];
	}

	if ( 0 === strpos( $slot, 'quote_' ) ) {
		if ( ! empty( $ids['quote'] ) ) {
			return (int) $ids['quote'];
		}
	}

	return 0;
}

/**
 * Render a mapped SureForms shortcode for a slot, or empty string.
 *
 * @param string $slot      Embed slot.
 * @param bool   $show_title Whether to show the form title.
 * @return string
 */
function snf_render_sureforms_embed( $slot, $show_title = false ) {
	$form_id = snf_get_sureforms_form_id_for_slot( $slot );
	if ( ! $form_id || ! shortcode_exists( 'sureforms' ) ) {
		return '';
	}

	$post = get_post( $form_id );
	if ( ! $post || 'sureforms_form' !== $post->post_type || ! in_array( $post->post_status, array( 'publish', 'protected' ), true ) ) {
		return '';
	}

	$html = do_shortcode(
		sprintf(
			'[sureforms id="%d" show_title="%s"]',
			$form_id,
			$show_title ? 'true' : 'false'
		)
	);

	/**
	 * Filter rendered SureForms embed HTML.
	 *
	 * @param string $html    Markup.
	 * @param string $slot    Slot key.
	 * @param int    $form_id Form ID.
	 */
	return (string) apply_filters( 'snf_sureforms_embed_html', $html, $slot, $form_id );
}

/**
 * Normalize heterogeneous SureForms / webhook field payloads.
 *
 * @param array<string, mixed> $raw Raw fields.
 * @return array<string, mixed>
 */
function snf_normalize_funnel_fields( array $raw ) {
	$flat = array();

	foreach ( $raw as $key => $value ) {
		$key = is_string( $key ) ? strtolower( trim( $key ) ) : (string) $key;
		$key = preg_replace( '/[^a-z0-9_\-]/', '_', $key );
		$key = trim( (string) $key, '_' );

		if ( is_array( $value ) ) {
			if ( isset( $value['value'] ) ) {
				$value = $value['value'];
			} elseif ( isset( $value['label'] ) && count( $value ) <= 2 ) {
				$value = $value['label'];
			} else {
				$value = wp_json_encode( $value );
			}
		}

		if ( is_scalar( $value ) || null === $value ) {
			$flat[ $key ] = is_string( $value ) ? trim( $value ) : $value;
		}
	}

	$aliases = array(
		'name'          => array( 'full_name', 'fullname', 'your_name', 'contact_name', 'first_name' ),
		'email'         => array( 'your_email', 'email_address', 'e_mail', 'contact_email' ),
		'phone'         => array( 'your_phone', 'telephone', 'mobile', 'tel', 'contact_phone' ),
		'firm'          => array( 'company', 'organization', 'org', 'business' ),
		'message'       => array( 'comments', 'comment', 'notes', 'inquiry', 'details', 'body' ),
		'product_slug'  => array( 'service', 'service_slug', 'quote_slug', 'product', 'sku' ),
		'event_id'      => array( 'workshop_id', 'product_id', 'event' ),
		'club'          => array( 'club_slug', 'reciprocal', 'program', 'brand' ),
		'plan'          => array( 'plan_slug', 'membership_plan', 'snf_apply_plan' ),
		'interest'      => array( 'interests', 'interest_list' ),
	);

	$out = $flat;
	foreach ( $aliases as $canonical => $alts ) {
		if ( ! empty( $out[ $canonical ] ) ) {
			continue;
		}
		foreach ( $alts as $alt ) {
			if ( ! empty( $flat[ $alt ] ) ) {
				$out[ $canonical ] = $flat[ $alt ];
				break;
			}
		}
	}

	if ( empty( $out['name'] ) && ! empty( $flat['first_name'] ) ) {
		$last         = isset( $flat['last_name'] ) ? (string) $flat['last_name'] : '';
		$out['name']  = trim( (string) $flat['first_name'] . ' ' . $last );
	}

	if ( ! empty( $out['email'] ) ) {
		$out['email'] = sanitize_email( (string) $out['email'] );
	}
	if ( ! empty( $out['name'] ) ) {
		$out['name'] = sanitize_text_field( (string) $out['name'] );
	}
	if ( ! empty( $out['phone'] ) ) {
		$out['phone'] = sanitize_text_field( (string) $out['phone'] );
	}
	if ( ! empty( $out['product_slug'] ) ) {
		$out['product_slug'] = sanitize_title( (string) $out['product_slug'] );
	}
	if ( ! empty( $out['club'] ) ) {
		$out['club'] = sanitize_title( (string) $out['club'] );
	}
	if ( ! empty( $out['plan'] ) ) {
		$out['plan'] = sanitize_title( (string) $out['plan'] );
	}
	if ( isset( $out['event_id'] ) ) {
		$out['event_id'] = absint( $out['event_id'] );
	}

	return $out;
}

/**
 * Register private funnel lead CPT.
 */
function snf_register_funnel_lead_cpt() {
	register_post_type(
		'snf_funnel_lead',
		array(
			'labels'              => array(
				'name'          => __( 'Funnel leads', 'super-ninety-four' ),
				'singular_name' => __( 'Funnel lead', 'super-ninety-four' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'woocommerce',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title', 'editor', 'custom-fields' ),
			'has_archive'         => false,
			'exclude_from_search' => true,
		)
	);
}
add_action( 'init', 'snf_register_funnel_lead_cpt', 12 );

/**
 * Persist a funnel lead and fire OttoKit-friendly action.
 *
 * @param string               $intent Intent slug.
 * @param array<string, mixed> $fields Normalized fields.
 * @param array<string, mixed> $context Extra meta (form_id, entry_id, source).
 * @return int|\WP_Error Lead post ID.
 */
function snf_create_funnel_lead( $intent, array $fields, array $context = array() ) {
	$intent = sanitize_key( (string) $intent );
	if ( ! isset( snf_get_sureforms_funnel_intents()[ $intent ] ) ) {
		return new WP_Error( 'snf_bad_intent', __( 'Unknown funnel intent.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$name  = isset( $fields['name'] ) ? (string) $fields['name'] : '';
	$email = isset( $fields['email'] ) ? (string) $fields['email'] : '';
	$title = sprintf(
		'%s — %s',
		snf_get_sureforms_funnel_intents()[ $intent ],
		$name ? $name : ( $email ? $email : __( 'Anonymous', 'super-ninety-four' ) )
	);

	$lines = array();
	foreach ( $fields as $key => $value ) {
		if ( '' === $value || null === $value ) {
			continue;
		}
		$lines[] = ucwords( str_replace( '_', ' ', (string) $key ) ) . ': ' . ( is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) );
	}

	$lead_id = wp_insert_post(
		array(
			'post_type'    => 'snf_funnel_lead',
			'post_status'  => 'private',
			'post_title'   => $title,
			'post_content' => implode( "\n", $lines ),
		),
		true
	);

	if ( is_wp_error( $lead_id ) ) {
		return $lead_id;
	}

	update_post_meta( $lead_id, '_snf_funnel_intent', $intent );
	update_post_meta( $lead_id, '_snf_funnel_fields', $fields );
	update_post_meta( $lead_id, '_snf_funnel_context', $context );
	if ( $email ) {
		update_post_meta( $lead_id, '_snf_email', $email );
	}
	if ( $name ) {
		update_post_meta( $lead_id, '_snf_name', $name );
	}

	/**
	 * OttoKit / SureTriggers: a funnel lead was stored from SureForms or webhook.
	 *
	 * @param int                  $lead_id Lead post ID.
	 * @param string               $intent  Intent slug.
	 * @param array<string, mixed> $fields  Normalized fields.
	 * @param array<string, mixed> $context Source meta.
	 */
	do_action( 'snf_funnel_lead_created', (int) $lead_id, $intent, $fields, $context );

	return (int) $lead_id;
}

/**
 * Add a service-quote WooCommerce product to the cart when possible.
 *
 * @param string               $product_slug Product slug.
 * @param array<string, mixed> $fields       Lead fields (prefill).
 * @return array<string, mixed>|\WP_Error
 */
function snf_funnel_add_service_quote_to_cart( $product_slug, array $fields = array() ) {
	$product_slug = sanitize_title( (string) $product_slug );
	if ( ! $product_slug || ! function_exists( 'WC' ) || ! class_exists( 'WooCommerce' ) ) {
		return new WP_Error( 'snf_no_woo', __( 'WooCommerce is required for quote cart actions.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	if ( function_exists( 'snf_get_service_quote_product_slugs' )
		&& ! in_array( $product_slug, snf_get_service_quote_product_slugs(), true ) ) {
		return new WP_Error( 'snf_bad_quote', __( 'Unknown service quote product.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$posts = get_posts(
		array(
			'name'           => $product_slug,
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( empty( $posts ) ) {
		return new WP_Error( 'snf_missing_product', __( 'Quote product not found.', 'super-ninety-four' ), array( 'status' => 404 ) );
	}

	$product_id = (int) $posts[0];

	if ( null === WC()->cart ) {
		wc_load_cart();
	}

	$cart_item_data = array();
	foreach ( array( 'name', 'email', 'phone', 'firm', 'message' ) as $key ) {
		if ( ! empty( $fields[ $key ] ) ) {
			$cart_item_data[ 'snf_quote_' . $key ] = sanitize_text_field( (string) $fields[ $key ] );
		}
	}

	$added = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );
	if ( ! $added ) {
		return new WP_Error( 'snf_cart_failed', __( 'Could not add quote product to cart.', 'super-ninety-four' ), array( 'status' => 500 ) );
	}

	$url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );

	return array(
		'success'    => true,
		'product_id' => $product_id,
		'cart_key'   => $added,
		'checkout'   => $url,
		'product'    => function_exists( 'snf_get_service_quote_product_url' )
			? snf_get_service_quote_product_url( $product_slug )
			: get_permalink( $product_id ),
	);
}

/**
 * Create a workshop interest reservation from funnel data.
 *
 * @param array<string, mixed> $fields Normalized fields.
 * @return array<string, mixed>|\WP_Error
 */
function snf_funnel_create_workshop_interest( array $fields ) {
	$name  = isset( $fields['name'] ) ? (string) $fields['name'] : '';
	$email = isset( $fields['email'] ) ? (string) $fields['email'] : '';
	$phone = isset( $fields['phone'] ) ? (string) $fields['phone'] : '';
	$firm  = isset( $fields['firm'] ) ? (string) $fields['firm'] : '';
	$event = isset( $fields['event_id'] ) ? absint( $fields['event_id'] ) : 0;

	if ( ! $name || ! is_email( $email ) ) {
		return new WP_Error( 'snf_workshop_fields', __( 'Name and valid email are required.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$event_title = $event ? get_the_title( $event ) : __( 'The Lifestyle Concierge Club Workshop', 'super-ninety-four' );
	$content     = "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nFirm: {$firm}\nEvent: {$event_title}\nSource: SureForms funnel";

	$reservation_id = wp_insert_post(
		array(
			'post_type'    => 'snf_reservation',
			'post_status'  => 'private',
			'post_title'   => sprintf( 'Workshop interest — %s', $name ),
			'post_content' => $content,
		),
		true
	);

	if ( is_wp_error( $reservation_id ) ) {
		return $reservation_id;
	}

	update_post_meta( $reservation_id, 'snf_event_id', $event );
	update_post_meta( $reservation_id, 'snf_email', $email );
	update_post_meta( $reservation_id, 'snf_phone', $phone );
	update_post_meta( $reservation_id, 'snf_firm', $firm );
	update_post_meta( $reservation_id, '_snf_source', 'sureforms_funnel' );

	if ( function_exists( 'snf_fire_workshop_reservation_created' ) ) {
		snf_fire_workshop_reservation_created(
			(int) $reservation_id,
			$event,
			$email,
			array(
				'full_name' => $name,
				'phone'     => $phone,
				'firm'      => $firm,
				'event'     => $event_title,
				'source'    => 'sureforms_funnel',
			)
		);
	}

	return array(
		'success'        => true,
		'reservation_id' => (int) $reservation_id,
		'event_id'       => $event,
	);
}

/**
 * Build Apply deep-link for reciprocal / apply leads.
 *
 * @param array<string, mixed> $fields Fields.
 * @return string
 */
function snf_funnel_build_apply_url( array $fields ) {
	$base = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'apply' ) : home_url( '/apply/' );
	$args = array();

	if ( ! empty( $fields['name'] ) ) {
		$args['name'] = (string) $fields['name'];
	}
	if ( ! empty( $fields['phone'] ) ) {
		$args['phone'] = (string) $fields['phone'];
	}
	if ( ! empty( $fields['email'] ) ) {
		$args['email'] = (string) $fields['email'];
	}
	if ( ! empty( $fields['club'] ) ) {
		$args['club'] = (string) $fields['club'];
		$args['q']    = (string) $fields['club'];
	}
	if ( ! empty( $fields['plan'] ) ) {
		$args['plan'] = (string) $fields['plan'];
	}
	if ( ! empty( $fields['interest'] ) ) {
		$args['interest'] = is_array( $fields['interest'] )
			? implode( ',', array_map( 'sanitize_title', $fields['interest'] ) )
			: sanitize_text_field( (string) $fields['interest'] );
	}

	return $args ? add_query_arg( $args, $base ) : $base;
}

/**
 * Process a funnel intent end-to-end.
 *
 * @param string               $intent  Intent slug.
 * @param array<string, mixed> $fields  Raw or normalized fields.
 * @param array<string, mixed> $context Source context.
 * @return array<string, mixed>|\WP_Error
 */
function snf_process_funnel_intent( $intent, array $fields, array $context = array() ) {
	$intent = sanitize_key( (string) $intent );
	$fields = snf_normalize_funnel_fields( $fields );

	$lead_id = snf_create_funnel_lead( $intent, $fields, $context );
	if ( is_wp_error( $lead_id ) ) {
		return $lead_id;
	}

	$result = array(
		'success' => true,
		'intent'  => $intent,
		'lead_id' => $lead_id,
		'fields'  => $fields,
	);

	switch ( $intent ) {
		case 'service_quote':
			$slug = ! empty( $fields['product_slug'] ) ? (string) $fields['product_slug'] : '';
			if ( ! $slug && ! empty( $context['product_slug'] ) ) {
				$slug = sanitize_title( (string) $context['product_slug'] );
			}
			$result['product_slug'] = $slug;
			$result['product_url']  = $slug && function_exists( 'snf_get_service_quote_product_url' )
				? snf_get_service_quote_product_url( $slug )
				: '';
			$result['apply_url'] = snf_funnel_build_apply_url( $fields );

			$add_to_cart = ! empty( $context['add_to_cart'] ) || ! empty( $fields['add_to_cart'] );
			if ( $add_to_cart && $slug ) {
				$cart = snf_funnel_add_service_quote_to_cart( $slug, $fields );
				if ( ! is_wp_error( $cart ) ) {
					$result['cart'] = $cart;
				} else {
					$result['cart_error'] = $cart->get_error_message();
				}
			}

			/**
			 * OttoKit: SureForms service-quote lead processed.
			 *
			 * @param int                  $lead_id Lead ID.
			 * @param string               $slug    Product slug.
			 * @param array<string, mixed> $fields  Fields.
			 */
			do_action( 'snf_sureforms_service_quote_lead', $lead_id, $slug, $fields );
			break;

		case 'workshop_interest':
			$workshop = snf_funnel_create_workshop_interest( $fields );
			if ( is_wp_error( $workshop ) ) {
				$result['workshop_error'] = $workshop->get_error_message();
			} else {
				$result['workshop'] = $workshop;
			}
			break;

		case 'reciprocal_interest':
			$result['apply_url'] = snf_funnel_build_apply_url( $fields );
			/**
			 * OttoKit: reciprocal interest from SureForms.
			 *
			 * @param int                  $lead_id Lead ID.
			 * @param array<string, mixed> $fields  Fields.
			 */
			do_action( 'snf_sureforms_reciprocal_interest', $lead_id, $fields );
			break;

		case 'apply_lead':
			$result['apply_url'] = snf_funnel_build_apply_url( $fields );
			if ( is_user_logged_in() && function_exists( 'snf_assign_club_role' ) ) {
				$user = wp_get_current_user();
				$skip = array( 'administrator', 'snf_staff', 'snf_reciprocal', 'snf_member', 'snf_applicant' );
				if ( ! array_intersect( $skip, (array) $user->roles ) ) {
					snf_assign_club_role( (int) $user->ID, 'snf_applicant' );
					$result['role'] = 'snf_applicant';
				}
			}
			/**
			 * OttoKit: apply lead from SureForms.
			 *
			 * @param int                  $lead_id Lead ID.
			 * @param array<string, mixed> $fields  Fields.
			 */
			do_action( 'snf_sureforms_apply_lead', $lead_id, $fields );
			break;

		case 'contact':
		case 'newsletter':
		default:
			/**
			 * OttoKit: marketing form lead from SureForms.
			 *
			 * @param int                  $lead_id Lead ID.
			 * @param string               $intent  Intent.
			 * @param array<string, mixed> $fields  Fields.
			 */
			do_action( 'snf_sureforms_marketing_lead', $lead_id, $intent, $fields );
			break;
	}

	/**
	 * After any SureForms funnel intent is processed.
	 *
	 * @param array<string, mixed> $result  Result payload.
	 * @param string               $intent  Intent.
	 * @param array<string, mixed> $fields  Fields.
	 * @param array<string, mixed> $context Context.
	 */
	do_action( 'snf_funnel_intent_processed', $result, $intent, $fields, $context );

	return $result;
}

/**
 * Extract field map from SureForms submit response / after-process payload.
 *
 * @param array<string, mixed> $payload Hook payload.
 * @return array{form_id:int,entry_id:int,fields:array<string,mixed>}
 */
function snf_extract_sureforms_submission( array $payload ) {
	$form_id  = 0;
	$entry_id = 0;
	$fields   = array();

	if ( isset( $payload['form_id'] ) ) {
		$form_id = absint( $payload['form_id'] );
	} elseif ( isset( $payload['form-id'] ) ) {
		$form_id = absint( $payload['form-id'] );
	}

	if ( isset( $payload['entry_id'] ) ) {
		$entry_id = absint( $payload['entry_id'] );
	} elseif ( isset( $payload['submission_id'] ) ) {
		$entry_id = absint( $payload['submission_id'] );
	}

	if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
		$fields = $payload['data'];
	} elseif ( isset( $payload['form_data'] ) && is_array( $payload['form_data'] ) ) {
		$fields = $payload['form_data'];
	} else {
		$skip = array( 'form_id', 'form-id', 'entry_id', 'submission_id', 'success', 'message', 'form_name', 'to_emails', 'srfm-submit-token', 'srfm-form-language' );
		foreach ( $payload as $key => $value ) {
			if ( in_array( $key, $skip, true ) ) {
				continue;
			}
			$fields[ $key ] = $value;
		}
	}

	return array(
		'form_id'  => $form_id,
		'entry_id' => $entry_id,
		'fields'   => is_array( $fields ) ? $fields : array(),
	);
}

/**
 * Whether a funnel lead already exists for a SureForms entry.
 *
 * @param int $entry_id Entry ID.
 * @return bool
 */
function snf_funnel_lead_exists_for_entry( $entry_id ) {
	$entry_id = absint( $entry_id );
	if ( ! $entry_id ) {
		return false;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'snf_funnel_lead',
			'post_status'    => 'private',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_snf_srfm_entry_id',
					'value'   => $entry_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	return ! empty( $existing );
}

/**
 * Handle SureForms submission with real field payload.
 *
 * @param int                  $entry_id  Entry ID (0 when entries are not stored).
 * @param int                  $form_id   Form ID.
 * @param array<string, mixed> $form_data Submitted fields.
 */
function snf_on_sureforms_submission_accepted( $entry_id, $form_id, $form_data ) {
	$form_id  = absint( $form_id );
	$entry_id = absint( $entry_id );
	$map      = snf_get_sureforms_funnel_map();

	if ( ! $form_id || empty( $map[ $form_id ] ) ) {
		return;
	}

	if ( $entry_id && snf_funnel_lead_exists_for_entry( $entry_id ) ) {
		return;
	}

	$fields = is_array( $form_data ) ? $form_data : array();
	$result = snf_process_funnel_intent(
		$map[ $form_id ],
		$fields,
		array(
			'source'   => 'srfm_submission_accepted',
			'form_id'  => $form_id,
			'entry_id' => $entry_id,
		)
	);

	if ( ! is_wp_error( $result ) && ! empty( $result['lead_id'] ) && $entry_id ) {
		update_post_meta( (int) $result['lead_id'], '_snf_srfm_entry_id', $entry_id );
	}
}
add_action( 'srfm_submission_accepted', 'snf_on_sureforms_submission_accepted', 20, 3 );

/**
 * Fallback when entries are skipped but after-process still runs.
 *
 * @param array<string, mixed> $form_data Form data.
 */
function snf_on_sureforms_after_submission_process( $form_data ) {
	if ( ! is_array( $form_data ) ) {
		return;
	}

	$extracted = snf_extract_sureforms_submission( $form_data );
	$form_id   = $extracted['form_id'];
	$map       = snf_get_sureforms_funnel_map();

	if ( ! $form_id || empty( $map[ $form_id ] ) ) {
		return;
	}

	if ( $extracted['entry_id'] && snf_funnel_lead_exists_for_entry( $extracted['entry_id'] ) ) {
		return;
	}

	$result = snf_process_funnel_intent(
		$map[ $form_id ],
		$extracted['fields'],
		array(
			'source'   => 'srfm_after_submission_process',
			'form_id'  => $form_id,
			'entry_id' => $extracted['entry_id'],
		)
	);

	if ( ! is_wp_error( $result ) && ! empty( $result['lead_id'] ) && $extracted['entry_id'] ) {
		update_post_meta( (int) $result['lead_id'], '_snf_srfm_entry_id', $extracted['entry_id'] );
	}
}
add_action( 'srfm_after_submission_process', 'snf_on_sureforms_after_submission_process', 20 );

/**
 * REST permission: manage_options, or matching funnel webhook secret.
 *
 * @param WP_REST_Request $request Request.
 * @return bool|\WP_Error
 */
function snf_funnel_rest_permission( $request ) {
	if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) ) {
		return true;
	}

	$secret = snf_get_funnel_webhook_secret();
	if ( '' === $secret ) {
		return new WP_Error(
			'snf_funnel_no_secret',
			__( 'Set SNF_FUNNEL_WEBHOOK_SECRET or snf_funnel_webhook_secret before accepting public funnel webhooks.', 'super-ninety-four' ),
			array( 'status' => 401 )
		);
	}

	$provided = $request->get_header( 'x-snf-funnel-secret' );
	if ( ! $provided ) {
		$provided = $request->get_param( 'secret' );
	}

	if ( ! is_string( $provided ) || ! hash_equals( $secret, $provided ) ) {
		return new WP_Error( 'snf_funnel_forbidden', __( 'Invalid funnel webhook secret.', 'super-ninety-four' ), array( 'status' => 403 ) );
	}

	return true;
}

/**
 * REST: process funnel intent from SureForms webhook / OttoKit / custom clients.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|\WP_Error
 */
function snf_funnel_rest_process( WP_REST_Request $request ) {
	$intent = sanitize_key( (string) $request->get_param( 'intent' ) );
	if ( ! $intent ) {
		$intent = sanitize_key( (string) $request->get_param( 'route_intent' ) );
	}

	$body = $request->get_json_params();
	if ( ! is_array( $body ) ) {
		$body = array();
	}

	$fields = $request->get_param( 'fields' );
	if ( ! is_array( $fields ) ) {
		$fields = isset( $body['fields'] ) && is_array( $body['fields'] ) ? $body['fields'] : $body;
	}

	unset( $fields['intent'], $fields['secret'], $fields['route_intent'], $fields['add_to_cart'] );

	$context = array(
		'source'       => 'rest',
		'add_to_cart'  => (bool) $request->get_param( 'add_to_cart' ),
		'product_slug' => sanitize_title( (string) $request->get_param( 'product_slug' ) ),
	);

	$result = snf_process_funnel_intent( $intent, $fields, $context );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return rest_ensure_response( $result );
}

/**
 * Register funnel REST routes.
 */
function snf_funnel_register_rest_routes() {
	$intents = array_keys( snf_get_sureforms_funnel_intents() );

	register_rest_route(
		'snf/v1',
		'/funnel/lead',
		array(
			'methods'             => 'POST',
			'callback'            => 'snf_funnel_rest_process',
			'permission_callback' => 'snf_funnel_rest_permission',
			'args'                => array(
				'intent' => array(
					'required' => true,
					'type'     => 'string',
					'enum'     => $intents,
				),
			),
		)
	);

	foreach ( $intents as $intent ) {
		register_rest_route(
			'snf/v1',
			'/funnel/' . $intent,
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) use ( $intent ) {
					$request->set_param( 'route_intent', $intent );
					if ( ! $request->get_param( 'intent' ) ) {
						$request->set_param( 'intent', $intent );
					}
					return snf_funnel_rest_process( $request );
				},
				'permission_callback' => 'snf_funnel_rest_permission',
			)
		);
	}

	register_rest_route(
		'snf/v1',
		'/funnel/catalog',
		array(
			'methods'             => 'GET',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
			},
			'callback'            => function () {
				return rest_ensure_response(
					array(
						'intents'   => snf_get_sureforms_funnel_intents(),
						'map'       => snf_get_sureforms_funnel_map(),
						'embeds'    => snf_get_sureforms_embed_ids(),
						'slots'     => snf_get_sureforms_embed_slots(),
						'endpoints' => array(
							'lead' => rest_url( 'snf/v1/funnel/lead' ),
						),
						'ottokit'   => function_exists( 'snf_get_ottokit_action_catalog' )
							? snf_get_ottokit_action_catalog()
							: array(),
					)
				);
			},
		)
	);
}
add_action( 'rest_api_init', 'snf_funnel_register_rest_routes' );

/**
 * SureForms Pro: JSON native-integration config for Woo funnel actions.
 *
 * @param array<int, string> $json_configs Encoded configs.
 * @return array<int, string>
 */
function snf_register_sureforms_pro_json_config( $json_configs ) {
	$config = array(
		'integrations' => array(
			array(
				'id'               => 'snf-funnel',
				'integration'      => array(
					'name'         => 'Super Ninety Four Funnel',
					'icon'         => '',
					'website_url'  => home_url( '/' ),
					'docs_url'     => home_url( '/' ),
					'category'     => 'Sales Funnel',
					'description'  => 'Bridge SureForms submissions into Super Ninety Four WooCommerce funnel leads (quotes, workshop, reciprocal, apply).',
				),
				'provider'         => 'wordpress',
				'plugin_detection' => array(
					'class' => 'SNF_SureForms_Funnel',
				),
				'auth'             => array(
					'type'          => 'plugin_detection',
					'test_endpoint' => array(
						'method'           => 'plugin_check',
						'success_criteria' => array(
							'plugin_active' => true,
						),
					),
				),
				'actions'          => array(
					array(
						'name'        => 'Create funnel lead',
						'description' => 'Store a Super Ninety Four funnel lead from mapped form fields.',
						'provider'    => 'wordpress',
						'handler'     => array(
							'type'   => 'action',
							'action' => 'snf_funnel_create_lead',
						),
						'fields'      => array(
							array(
								'key'      => 'intent',
								'label'    => 'Intent',
								'type'     => 'dropdown',
								'required' => true,
								'options'  => array_map(
									static function ( $label, $value ) {
										return array(
											'label' => $label,
											'value' => $value,
										);
									},
									array_values( snf_get_sureforms_funnel_intents() ),
									array_keys( snf_get_sureforms_funnel_intents() )
								),
							),
							array(
								'key'      => 'name',
								'label'    => 'Name',
								'type'     => 'text',
								'required' => false,
							),
							array(
								'key'      => 'email',
								'label'    => 'Email',
								'type'     => 'email',
								'required' => true,
							),
							array(
								'key'      => 'phone',
								'label'    => 'Phone',
								'type'     => 'text',
								'required' => false,
							),
							array(
								'key'      => 'product_slug',
								'label'    => 'Quote product slug',
								'type'     => 'text',
								'required' => false,
							),
							array(
								'key'      => 'club',
								'label'    => 'Reciprocal club slug',
								'type'     => 'text',
								'required' => false,
							),
							array(
								'key'      => 'message',
								'label'    => 'Message',
								'type'     => 'textarea',
								'required' => false,
							),
						),
					),
					array(
						'name'        => 'Add service quote to cart',
						'description' => 'Create a quote lead and add the WooCommerce quote product to cart.',
						'provider'    => 'wordpress',
						'handler'     => array(
							'type'   => 'action',
							'action' => 'snf_funnel_add_quote_to_cart',
						),
						'fields'      => array(
							array(
								'key'      => 'product_slug',
								'label'    => 'Quote product slug',
								'type'     => 'text',
								'required' => true,
							),
							array(
								'key'      => 'email',
								'label'    => 'Email',
								'type'     => 'email',
								'required' => true,
							),
							array(
								'key'      => 'name',
								'label'    => 'Name',
								'type'     => 'text',
								'required' => false,
							),
							array(
								'key'      => 'phone',
								'label'    => 'Phone',
								'type'     => 'text',
								'required' => false,
							),
						),
					),
					array(
						'name'        => 'Create workshop interest',
						'description' => 'Store a Lifestyle Concierge workshop interest reservation.',
						'provider'    => 'wordpress',
						'handler'     => array(
							'type'   => 'action',
							'action' => 'snf_funnel_workshop_interest',
						),
						'fields'      => array(
							array(
								'key'      => 'name',
								'label'    => 'Full name',
								'type'     => 'text',
								'required' => true,
							),
							array(
								'key'      => 'email',
								'label'    => 'Email',
								'type'     => 'email',
								'required' => true,
							),
							array(
								'key'      => 'phone',
								'label'    => 'Phone',
								'type'     => 'text',
								'required' => false,
							),
							array(
								'key'      => 'firm',
								'label'    => 'Firm',
								'type'     => 'text',
								'required' => false,
							),
							array(
								'key'      => 'event_id',
								'label'    => 'Event / product ID',
								'type'     => 'number',
								'required' => false,
							),
						),
					),
					array(
						'name'        => 'Create reciprocal interest',
						'description' => 'Store reciprocal interest and prepare Apply deep-link fields.',
						'provider'    => 'wordpress',
						'handler'     => array(
							'type'   => 'action',
							'action' => 'snf_funnel_reciprocal_interest',
						),
						'fields'      => array(
							array(
								'key'      => 'name',
								'label'    => 'Name',
								'type'     => 'text',
								'required' => true,
							),
							array(
								'key'      => 'phone',
								'label'    => 'Phone',
								'type'     => 'text',
								'required' => true,
							),
							array(
								'key'      => 'email',
								'label'    => 'Email',
								'type'     => 'email',
								'required' => false,
							),
							array(
								'key'      => 'club',
								'label'    => 'Club slug',
								'type'     => 'text',
								'required' => false,
							),
						),
					),
				),
			),
		),
	);

	$json_configs[] = wp_json_encode( $config );
	return $json_configs;
}
add_filter( 'srfm_pro_native_integrations_json_configs', 'snf_register_sureforms_pro_json_config' );

/**
 * SureForms Pro: register PHP handler file path (callbacks, no Pro base class required).
 *
 * @param array<string, array<string, string>> $plugin_integrations Integrations.
 * @return array<string, array<string, string>>
 */
function snf_register_sureforms_pro_php_handler( $plugin_integrations ) {
	$plugin_integrations['snf-funnel'] = array(
		'file'      => get_template_directory() . '/app/Integrations/sureforms/actions.php',
		'detection' => 'SNF_SureForms_Funnel',
	);
	return $plugin_integrations;
}
add_filter( 'srfm_pro_wordpress_plugin_integrations', 'snf_register_sureforms_pro_php_handler' );

/**
 * Pro native action: create funnel lead.
 *
 * @param array<string, mixed> $data Mapped fields.
 */
function snf_srfm_funnel_create_lead( $data ) {
	if ( ! is_array( $data ) ) {
		return;
	}
	$intent = isset( $data['intent'] ) ? sanitize_key( (string) $data['intent'] ) : 'contact';
	unset( $data['intent'] );
	snf_process_funnel_intent( $intent, $data, array( 'source' => 'srfm_pro_action' ) );
}
add_action( 'srfm_snf_funnel_create_lead', 'snf_srfm_funnel_create_lead' );

/**
 * Pro native action: quote → cart.
 *
 * @param array<string, mixed> $data Mapped fields.
 */
function snf_srfm_funnel_add_quote_to_cart( $data ) {
	if ( ! is_array( $data ) ) {
		return;
	}
	snf_process_funnel_intent(
		'service_quote',
		$data,
		array(
			'source'      => 'srfm_pro_action',
			'add_to_cart' => true,
		)
	);
}
add_action( 'srfm_snf_funnel_add_quote_to_cart', 'snf_srfm_funnel_add_quote_to_cart' );

/**
 * Pro native action: workshop interest.
 *
 * @param array<string, mixed> $data Mapped fields.
 */
function snf_srfm_funnel_workshop_interest( $data ) {
	if ( ! is_array( $data ) ) {
		return;
	}
	snf_process_funnel_intent( 'workshop_interest', $data, array( 'source' => 'srfm_pro_action' ) );
}
add_action( 'srfm_snf_funnel_workshop_interest', 'snf_srfm_funnel_workshop_interest' );

/**
 * Pro native action: reciprocal interest.
 *
 * @param array<string, mixed> $data Mapped fields.
 */
function snf_srfm_funnel_reciprocal_interest( $data ) {
	if ( ! is_array( $data ) ) {
		return;
	}
	snf_process_funnel_intent( 'reciprocal_interest', $data, array( 'source' => 'srfm_pro_action' ) );
}
add_action( 'srfm_snf_funnel_reciprocal_interest', 'snf_srfm_funnel_reciprocal_interest' );

/**
 * Admin submenu for funnel ↔ SureForms mapping.
 */
function snf_funnel_admin_menu() {
	add_submenu_page(
		'woocommerce',
		__( 'SNF Funnel / SureForms', 'super-ninety-four' ),
		__( 'SNF Funnel', 'super-ninety-four' ),
		'manage_woocommerce',
		'snf-funnel-sureforms',
		'snf_funnel_admin_render'
	);
}
add_action( 'admin_menu', 'snf_funnel_admin_menu', 56 );

/**
 * Save funnel admin settings.
 */
function snf_funnel_admin_save() {
	if ( ! isset( $_POST['snf_funnel_settings_nonce'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	check_admin_referer( 'snf_funnel_settings', 'snf_funnel_settings_nonce' );

	$map = array();
	if ( isset( $_POST['snf_funnel_map'] ) && is_array( $_POST['snf_funnel_map'] ) ) {
		foreach ( wp_unslash( $_POST['snf_funnel_map'] ) as $form_id => $intent ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$form_id = absint( $form_id );
			$intent  = sanitize_key( (string) $intent );
			if ( $form_id && $intent && isset( snf_get_sureforms_funnel_intents()[ $intent ] ) ) {
				$map[ $form_id ] = $intent;
			}
		}
	}
	update_option( 'snf_sureforms_funnel_map', $map, false );

	$embeds = array();
	if ( isset( $_POST['snf_funnel_embeds'] ) && is_array( $_POST['snf_funnel_embeds'] ) ) {
		foreach ( wp_unslash( $_POST['snf_funnel_embeds'] ) as $slot => $form_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$slot    = sanitize_key( (string) $slot );
			$form_id = absint( $form_id );
			if ( $slot && $form_id ) {
				$embeds[ $slot ] = $form_id;
			}
		}
	}
	update_option( 'snf_sureforms_embed_ids', $embeds, false );

	if ( isset( $_POST['snf_funnel_webhook_secret'] ) ) {
		update_option(
			'snf_funnel_webhook_secret',
			sanitize_text_field( wp_unslash( $_POST['snf_funnel_webhook_secret'] ) ),
			false
		);
	}

	add_settings_error( 'snf_funnel', 'saved', __( 'Funnel / SureForms settings saved.', 'super-ninety-four' ), 'updated' );
}
add_action( 'admin_init', 'snf_funnel_admin_save' );

/**
 * List published SureForms forms for admin selects.
 *
 * @return array<int, string>
 */
function snf_get_sureforms_form_choices() {
	$choices = array( 0 => __( '— Select form —', 'super-ninety-four' ) );

	if ( ! post_type_exists( 'sureforms_form' ) ) {
		return $choices;
	}

	$forms = get_posts(
		array(
			'post_type'      => 'sureforms_form',
			'post_status'    => array( 'publish', 'protected' ),
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	foreach ( $forms as $form ) {
		$choices[ (int) $form->ID ] = sprintf( '%s (#%d)', $form->post_title, $form->ID );
	}

	return $choices;
}

/**
 * Render funnel admin page.
 */
function snf_funnel_admin_render() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$forms    = snf_get_sureforms_form_choices();
	$map      = snf_get_sureforms_funnel_map();
	$embeds   = snf_get_sureforms_embed_ids();
	$intents  = snf_get_sureforms_funnel_intents();
	$slots    = snf_get_sureforms_embed_slots();
	$secret   = snf_get_funnel_webhook_secret();
	$endpoint = rest_url( 'snf/v1/funnel/lead' );

	settings_errors( 'snf_funnel' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SNF Funnel / SureForms', 'super-ninety-four' ); ?></h1>
		<p><?php esc_html_e( 'Map SureForms forms into the Super Ninety Four sales funnel. Theme WooCommerce forms (Apply, workshop, quote checkout) stay transactional; SureForms feeds leads and optional cart handoffs.', 'super-ninety-four' ); ?></p>

		<form method="post">
			<?php wp_nonce_field( 'snf_funnel_settings', 'snf_funnel_settings_nonce' ); ?>

			<h2><?php esc_html_e( 'Webhook API', 'super-ninety-four' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'REST endpoint', 'super-ninety-four' ); ?></th>
					<td><code><?php echo esc_html( $endpoint ); ?></code>
						<p class="description"><?php esc_html_e( 'Point a SureForms webhook (JSON POST) here, or use /snf/v1/funnel/{intent}. Send header X-SNF-Funnel-Secret.', 'super-ninety-four' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="snf_funnel_webhook_secret"><?php esc_html_e( 'Webhook secret', 'super-ninety-four' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="snf_funnel_webhook_secret" name="snf_funnel_webhook_secret" value="<?php echo esc_attr( $secret ); ?>" autocomplete="off" />
						<p class="description"><?php esc_html_e( 'Or define SNF_FUNNEL_WEBHOOK_SECRET in wp-config.php.', 'super-ninety-four' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Form → intent map', 'super-ninety-four' ); ?></h2>
			<p class="description"><?php esc_html_e( 'On submit, mapped forms create funnel leads and fire OttoKit actions.', 'super-ninety-four' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'SureForms form', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Funnel intent', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$form_ids = array_keys( $forms );
					$form_ids = array_filter( $form_ids );
					if ( ! $form_ids ) :
						?>
						<tr><td colspan="2"><?php esc_html_e( 'No SureForms forms found. Create Contact / Newsletter / quote forms in SureForms first.', 'super-ninety-four' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $form_ids as $form_id ) : ?>
							<tr>
								<td><?php echo esc_html( $forms[ $form_id ] ); ?></td>
								<td>
									<select name="snf_funnel_map[<?php echo esc_attr( (string) $form_id ); ?>]">
										<option value=""><?php esc_html_e( '— Not mapped —', 'super-ninety-four' ); ?></option>
										<?php foreach ( $intents as $intent => $label ) : ?>
											<option value="<?php echo esc_attr( $intent ); ?>" <?php selected( isset( $map[ $form_id ] ) ? $map[ $form_id ] : '', $intent ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Embed SureForms into theme surfaces', 'super-ninety-four' ); ?></h2>
			<p class="description"><?php esc_html_e( 'When set, reciprocal capture and quote landings render the SureForms form alongside / instead of the native capture UI.', 'super-ninety-four' ); ?></p>
			<table class="form-table" role="presentation">
				<?php foreach ( $slots as $slot => $label ) : ?>
					<tr>
						<th scope="row"><label for="snf_embed_<?php echo esc_attr( $slot ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<select id="snf_embed_<?php echo esc_attr( $slot ); ?>" name="snf_funnel_embeds[<?php echo esc_attr( $slot ); ?>]">
								<?php foreach ( $forms as $form_id => $title ) : ?>
									<option value="<?php echo esc_attr( (string) $form_id ); ?>" <?php selected( isset( $embeds[ $slot ] ) ? (int) $embeds[ $slot ] : 0, (int) $form_id ); ?>>
										<?php echo esc_html( $title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<?php submit_button( __( 'Save funnel settings', 'super-ninety-four' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Auto-map SureForms forms by title heuristics once.
 */
function snf_sureforms_funnel_bootstrap_map() {
	if ( get_option( 'snf_sureforms_funnel_bootstrapped_v1' ) ) {
		return;
	}
	if ( ! post_type_exists( 'sureforms_form' ) ) {
		return;
	}

	$heuristics = array(
		'contact'    => 'contact',
		'newsletter' => 'newsletter',
		'quote'      => 'service_quote',
		'service'    => 'service_quote',
		'workshop'   => 'workshop_interest',
		'reciprocal' => 'reciprocal_interest',
		'apply'      => 'apply_lead',
		'membership' => 'apply_lead',
	);

	$map   = snf_get_sureforms_funnel_map();
	$forms = get_posts(
		array(
			'post_type'      => 'sureforms_form',
			'post_status'    => array( 'publish', 'protected' ),
			'posts_per_page' => 50,
		)
	);

	foreach ( $forms as $form ) {
		$title = strtolower( $form->post_title );
		foreach ( $heuristics as $needle => $intent ) {
			if ( false !== strpos( $title, $needle ) && empty( $map[ (int) $form->ID ] ) ) {
				$map[ (int) $form->ID ] = $intent;
				break;
			}
		}
	}

	update_option( 'snf_sureforms_funnel_map', $map, false );
	update_option( 'snf_sureforms_funnel_bootstrapped_v1', 1, false );
}
add_action( 'init', 'snf_sureforms_funnel_bootstrap_map', 40 );
