<?php
/**
 * Open-admission gathering thank-you pages + post-purchase booking desk.
 *
 * After e-ticket checkout for City Club Private Party or Lifestyle Concierge
 * Workshop, buyers land on a custom thank-you page with an in-person booking
 * form (Harris Houston / Charlotte–Harrisburg, undisclosed location) and three
 * blunt open-ended questions.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Open-admission gathering product slugs (guest + member purchase without Apply).
 *
 * @return string[]
 */
function snf_get_open_admission_event_slugs() {
	$slugs = array();
	if ( function_exists( 'snf_get_city_club_private_party_slug' ) ) {
		$slugs[] = snf_get_city_club_private_party_slug();
	}
	if ( function_exists( 'snf_get_workshop_product_slug' ) ) {
		$slugs[] = snf_get_workshop_product_slug();
	}
	/**
	 * Filter open-admission gathering product slugs.
	 *
	 * @param string[] $slugs Slugs.
	 */
	return array_values( array_unique( array_filter( $slugs ) ) );
}

/**
 * Whether a post/product is an open-admission gathering.
 *
 * @param int|WP_Post|null $post Post.
 * @return bool
 */
function snf_is_open_admission_event( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	$slug = function_exists( 'snf_resolve_listing_product_slug' )
		? snf_resolve_listing_product_slug( $post->post_name )
		: $post->post_name;
	return in_array( $slug, snf_get_open_admission_event_slugs(), true );
}

/**
 * Thank-you page config keyed by product slug.
 *
 * @return array<string, array<string, mixed>>
 */
function snf_get_gathering_thankyou_catalog() {
	$catalog = array(
		'the-city-club-private-party'           => array(
			'page_slug'  => 'thank-you-city-club-private-party',
			'title'      => __( 'City Club Private Party — you’re confirmed', 'super-ninety-four' ),
			'lede'       => __( 'Your e-ticket is secured. Book an in-person conversation in the Harris Houston / Charlotte–Harrisburg area (undisclosed location) so we can hear the blunt version of what you’re dealing with.', 'super-ninety-four' ),
			'event_name' => __( 'The City Club Private Party', 'super-ninety-four' ),
		),
		'the-lifestyle-concierge-club-workshop' => array(
			'page_slug'  => 'thank-you-lifestyle-concierge-club-workshop',
			'title'      => __( 'Lifestyle Concierge Workshop — you’re confirmed', 'super-ninety-four' ),
			'lede'       => __( 'Your e-ticket is secured. Book an in-person conversation in the Harris Houston / Charlotte–Harrisburg area (undisclosed location) so we can hear the blunt version of what you’re dealing with.', 'super-ninety-four' ),
			'event_name' => __( 'The Lifestyle Concierge Club Workshop', 'super-ninety-four' ),
		),
	);

	/**
	 * Filter gathering thank-you catalog.
	 *
	 * @param array $catalog Catalog.
	 */
	return apply_filters( 'snf_gathering_thankyou_catalog', $catalog );
}

/**
 * Three blunt open-ended booking questions.
 *
 * @return array<int, array{key:string,label:string,hint:string}>
 */
function snf_get_gathering_booking_questions() {
	$questions = array(
		array(
			'key'   => 'q1_problem',
			'label' => __( 'What problem are you actually trying to solve by showing up — not the polite version?', 'super-ninety-four' ),
			'hint'  => __( 'Be blunt. Name the real pressure, not the LinkedIn answer.', 'super-ninety-four' ),
		),
		array(
			'key'   => 'q2_fear',
			'label' => __( 'What are you afraid will happen if this conversation is wasted?', 'super-ninety-four' ),
			'hint'  => __( 'Say the cost you don’t want to keep paying.', 'super-ninety-four' ),
		),
		array(
			'key'   => 'q3_tried',
			'label' => __( 'What have you already tried that failed — and why do you think it failed?', 'super-ninety-four' ),
			'hint'  => __( 'List what burned time or money. No soft-pedaling.', 'super-ninety-four' ),
		),
	);

	/**
	 * Filter gathering booking questions.
	 *
	 * @param array $questions Questions.
	 */
	return apply_filters( 'snf_gathering_booking_questions', $questions );
}

/**
 * Preferred in-person windows (Harris Houston desk).
 *
 * @return array<int, array{value:string,label:string}>
 */
function snf_get_gathering_booking_windows() {
	return array(
		array(
			'value' => 'sun-0900-1100',
			'label' => __( 'Sunday · 9:00 AM – 11:00 AM ET', 'super-ninety-four' ),
		),
		array(
			'value' => 'sun-1300-1500',
			'label' => __( 'Sunday · 1:00 PM – 3:00 PM ET', 'super-ninety-four' ),
		),
		array(
			'value' => 'mon-0900-1100',
			'label' => __( 'Monday · 9:00 AM – 11:00 AM ET', 'super-ninety-four' ),
		),
		array(
			'value' => 'mon-1300-1500',
			'label' => __( 'Monday · 1:00 PM – 3:00 PM ET', 'super-ninety-four' ),
		),
		array(
			'value' => 'tue-0900-1100',
			'label' => __( 'Tuesday · 9:00 AM – 11:00 AM ET', 'super-ninety-four' ),
		),
		array(
			'value' => 'tue-1300-1500',
			'label' => __( 'Tuesday · 1:00 PM – 3:00 PM ET', 'super-ninety-four' ),
		),
		array(
			'value' => 'wed-0900-1100',
			'label' => __( 'Wednesday · 9:00 AM – 11:00 AM ET', 'super-ninety-four' ),
		),
		array(
			'value' => 'wed-1300-1500',
			'label' => __( 'Wednesday · 1:00 PM – 3:00 PM ET', 'super-ninety-four' ),
		),
	);
}

/**
 * Resolve open-admission gathering slug present on an order.
 *
 * @param WC_Order $order Order.
 * @return string Product slug or empty.
 */
function snf_order_open_admission_gathering_slug( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return '';
	}
	$open = snf_get_open_admission_event_slugs();
	foreach ( $order->get_items() as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}
		$slug = $product->get_slug();
		if ( in_array( $slug, $open, true ) ) {
			return $slug;
		}
	}
	return '';
}

/**
 * Thank-you page URL for a gathering slug + order.
 *
 * @param string   $slug  Product slug.
 * @param WC_Order $order Order.
 * @return string
 */
function snf_get_gathering_thankyou_url( $slug, $order ) {
	$slug    = sanitize_title( (string) $slug );
	$catalog = snf_get_gathering_thankyou_catalog();
	if ( empty( $catalog[ $slug ]['page_slug'] ) || ! $order instanceof WC_Order ) {
		return '';
	}

	$page = get_page_by_path( $catalog[ $slug ]['page_slug'] );
	$url  = ( $page instanceof WP_Post && 'publish' === $page->post_status )
		? get_permalink( $page )
		: home_url( '/' . $catalog[ $slug ]['page_slug'] . '/' );

	return add_query_arg(
		array(
			'order' => $order->get_id(),
			'key'   => $order->get_order_key(),
		),
		$url
	);
}

/**
 * Ensure the two custom thank-you pages exist.
 */
function snf_ensure_gathering_thankyou_pages() {
	if ( wp_installing() ) {
		return;
	}

	$version = '1.0.0-gathering-thankyou-pages';
	if ( get_option( 'snf_gathering_thankyou_pages_version' ) === $version ) {
		return;
	}

	foreach ( snf_get_gathering_thankyou_catalog() as $entry ) {
		$slug = sanitize_title( (string) $entry['page_slug'] );
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-templates/gathering-thankyou.php' );
			continue;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => (string) $entry['title'],
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $page_id ) && $page_id ) {
			update_post_meta( (int) $page_id, '_wp_page_template', 'page-templates/gathering-thankyou.php' );
		}
	}

	update_option( 'snf_gathering_thankyou_pages_version', $version, false );
}
add_action( 'after_switch_theme', 'snf_ensure_gathering_thankyou_pages' );
add_action( 'admin_init', 'snf_ensure_gathering_thankyou_pages', 36 );
add_action( 'init', 'snf_ensure_gathering_thankyou_pages', 36 );

/**
 * Send open-admission gathering buyers to their custom thank-you page.
 *
 * @param string        $return_url Default return URL.
 * @param WC_Order|null $order      Order.
 * @return string
 */
function snf_gathering_filter_return_url( $return_url, $order = null ) {
	if ( ! $order instanceof WC_Order ) {
		return $return_url;
	}
	$slug = snf_order_open_admission_gathering_slug( $order );
	if ( ! $slug ) {
		return $return_url;
	}
	$custom = snf_get_gathering_thankyou_url( $slug, $order );
	return $custom ? $custom : $return_url;
}
add_filter( 'woocommerce_get_return_url', 'snf_gathering_filter_return_url', 30, 2 );

/**
 * Resolve order from thank-you query args.
 *
 * @return WC_Order|null
 */
function snf_gathering_thankyou_current_order() {
	$order_id = isset( $_GET['order'] ) ? absint( wp_unslash( $_GET['order'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$key      = isset( $_GET['key'] ) ? (string) wp_unslash( $_GET['key'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $order_id < 1 || '' === $key || ! function_exists( 'wc_get_order' ) ) {
		return null;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return null;
	}
	if ( ! hash_equals( (string) $order->get_order_key(), $key ) ) {
		return null;
	}
	return $order;
}

/**
 * Payload for a gathering thank-you page.
 *
 * @param string        $slug  Product slug.
 * @param WC_Order|null $order Order.
 * @return array<string, mixed>|null
 */
function snf_get_gathering_thankyou_payload( $slug, $order = null ) {
	$slug    = sanitize_title( (string) $slug );
	$catalog = snf_get_gathering_thankyou_catalog();
	if ( empty( $catalog[ $slug ] ) ) {
		return null;
	}

	$entry = $catalog[ $slug ];
	return array(
		'slug'       => $slug,
		'title'      => $entry['title'],
		'lede'       => $entry['lede'],
		'event_name' => $entry['event_name'],
		'location'   => __( 'Harris Houston · Charlotte / Harrisburg area · undisclosed location', 'super-ninety-four' ),
		'questions'  => snf_get_gathering_booking_questions(),
		'windows'    => snf_get_gathering_booking_windows(),
		'order'      => $order instanceof WC_Order
			? array(
				'id'     => $order->get_id(),
				'key'    => $order->get_order_key(),
				'number' => $order->get_order_number(),
				'email'  => $order->get_billing_email(),
				'name'   => trim( $order->get_formatted_billing_full_name() ),
				'phone'  => $order->get_billing_phone(),
				'total'  => $order->get_formatted_order_total(),
			)
			: null,
		'submitted'  => isset( $_GET['snf_gathering_book'] ) && 'sent' === (string) wp_unslash( $_GET['snf_gathering_book'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'error'      => isset( $_GET['snf_gathering_book'] ) && 'error' === (string) wp_unslash( $_GET['snf_gathering_book'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	);
}

/**
 * Handle post-purchase gathering booking form.
 */
function snf_handle_gathering_booking_request() {
	$redirect = isset( $_POST['snf_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['snf_redirect'] ) ) : home_url( '/' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! $redirect ) {
		$redirect = home_url( '/' );
	}

	if ( ! isset( $_POST['snf_gathering_book_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snf_gathering_book_nonce'] ) ), 'snf_gathering_book' ) ) {
		wp_safe_redirect( add_query_arg( 'snf_gathering_book', 'error', $redirect ) );
		exit;
	}

	$slug     = isset( $_POST['snf_gathering_slug'] ) ? sanitize_title( wp_unslash( $_POST['snf_gathering_slug'] ) ) : '';
	$order_id = isset( $_POST['snf_order_id'] ) ? absint( wp_unslash( $_POST['snf_order_id'] ) ) : 0;
	$order_key = isset( $_POST['snf_order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_order_key'] ) ) : '';
	$name     = isset( $_POST['snf_book_name'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_book_name'] ) ) : '';
	$email    = isset( $_POST['snf_book_email'] ) ? sanitize_email( wp_unslash( $_POST['snf_book_email'] ) ) : '';
	$phone    = isset( $_POST['snf_book_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_book_phone'] ) ) : '';
	$window   = isset( $_POST['snf_book_window'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_book_window'] ) ) : '';

	$answers = array();
	foreach ( snf_get_gathering_booking_questions() as $question ) {
		$key             = $question['key'];
		$answers[ $key ] = isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	$valid_windows = wp_list_pluck( snf_get_gathering_booking_windows(), 'value' );
	$ok            = $slug
		&& isset( snf_get_gathering_thankyou_catalog()[ $slug ] )
		&& '' !== $name
		&& is_email( $email )
		&& '' !== $phone
		&& in_array( $window, $valid_windows, true );

	foreach ( $answers as $answer ) {
		if ( '' === trim( $answer ) ) {
			$ok = false;
			break;
		}
	}

	$order = null;
	if ( $order_id && $order_key && function_exists( 'wc_get_order' ) ) {
		$candidate = wc_get_order( $order_id );
		if ( $candidate instanceof WC_Order && hash_equals( (string) $candidate->get_order_key(), $order_key ) ) {
			$order = $candidate;
		}
	}

	if ( ! $ok ) {
		wp_safe_redirect( add_query_arg( 'snf_gathering_book', 'error', $redirect ) );
		exit;
	}

	$payload = array(
		'slug'    => $slug,
		'name'    => $name,
		'email'   => $email,
		'phone'   => $phone,
		'window'  => $window,
		'answers' => $answers,
		'order'   => $order ? $order->get_id() : 0,
		'created' => gmdate( 'c' ),
		'user_id' => get_current_user_id(),
	);

	/**
	 * Fires after a gathering booking desk request is validated.
	 *
	 * @param array $payload Payload.
	 */
	do_action( 'snf_gathering_booking_submitted', $payload );

	if ( $order ) {
		$note_lines = array(
			'Gathering in-person booking request',
			'Event: ' . $slug,
			'Window: ' . $window,
			'Name: ' . $name,
			'Email: ' . $email,
			'Phone: ' . $phone,
		);
		foreach ( snf_get_gathering_booking_questions() as $question ) {
			$note_lines[] = $question['label'];
			$note_lines[] = $answers[ $question['key'] ];
			$note_lines[] = '';
		}
		$order->add_order_note( implode( "\n", $note_lines ) );
		$order->update_meta_data( '_snf_gathering_booking', $payload );
		$order->save();
	}

	$to = (string) apply_filters( 'snf_gathering_booking_notify_email', (string) get_option( 'admin_email' ) );
	$subject = sprintf(
		/* translators: %s: requester name */
		__( '[SUPER NINETY FOUR] Gathering desk booking — %s', 'super-ninety-four' ),
		$name
	);
	$body_lines = array(
		'Event slug: ' . $slug,
		'Preferred window: ' . $window,
		'Name: ' . $name,
		'Email: ' . $email,
		'Phone: ' . $phone,
		'Order: ' . ( $order ? $order->get_order_number() : 'n/a' ),
		'Location context: Harris Houston · Charlotte / Harrisburg area · undisclosed',
		'',
	);
	foreach ( snf_get_gathering_booking_questions() as $question ) {
		$body_lines[] = $question['label'];
		$body_lines[] = $answers[ $question['key'] ];
		$body_lines[] = '';
	}
	if ( $to ) {
		wp_mail( $to, $subject, implode( "\n", $body_lines ), array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );
	}

	wp_safe_redirect( add_query_arg( 'snf_gathering_book', 'sent', $redirect ) );
	exit;
}
add_action( 'admin_post_nopriv_snf_gathering_book', 'snf_handle_gathering_booking_request' );
add_action( 'admin_post_snf_gathering_book', 'snf_handle_gathering_booking_request' );
