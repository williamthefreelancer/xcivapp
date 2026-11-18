<?php
/**
 * B2B marketplace extensions: operator orgs + RFQ intake (BuddyPress-free).
 *
 * Hook contract:
 * - User meta: snf_org_* for vendor/operator profiles.
 * - CPT: snf_rfq (admin inbox for Expo RFQs).
 * - REST: POST snf/v1/marketplace/rfq (public lead + member RFQ).
 * - Emit: snf_marketplace_rfq_submitted
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'SNF_B2B_MARKETPLACE_LOADED' ) ) {
	return;
}
define( 'SNF_B2B_MARKETPLACE_LOADED', true );

const SNF_RFQ_SETTINGS_OPTION = 'snf_rfq_settings';

/**
 * Default Marketplace RFQ settings / field requirements.
 *
 * @return array<string, mixed>
 */
function snf_rfq_default_settings() {
	return array(
		'enabled'             => 1,
		'members_only'        => 0,
		'require_company'     => 0,
		'require_product'     => 0,
		'max_message_length'  => 4000,
		'notify_email'        => '',
		'form_title'          => __( 'Request a quote', 'super-ninety-four' ),
		'success_message'     => __( 'Request received. Concierge will follow up.', 'super-ninety-four' ),
		'form_intro'          => '',
		'default_audience'    => 'public',
	);
}

/**
 * Merged RFQ settings.
 *
 * @return array<string, mixed>
 */
function snf_get_rfq_settings() {
	$stored = get_option( SNF_RFQ_SETTINGS_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	return array_merge( snf_rfq_default_settings(), $stored );
}

/**
 * Audience choices for RFQ intake.
 *
 * @return array<string, string>
 */
function snf_rfq_audience_choices() {
	return array(
		'public'  => __( 'Public / Expo', 'super-ninety-four' ),
		'members' => __( 'Members', 'super-ninety-four' ),
	);
}

/**
 * Register RFQ CPT for staff review.
 */
function snf_b2b_register_rfq_cpt() {
	register_post_type(
		'snf_rfq',
		array(
			'labels'              => array(
				'name'          => __( 'Marketplace RFQs', 'super-ninety-four' ),
				'singular_name' => __( 'RFQ', 'super-ninety-four' ),
				'edit_item'     => __( 'Review RFQ', 'super-ninety-four' ),
				'search_items'  => __( 'Search RFQs', 'super-ninety-four' ),
				'not_found'     => __( 'No RFQs found.', 'super-ninety-four' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title', 'editor' ),
			'exclude_from_search' => true,
		)
	);
}
add_action( 'init', 'snf_b2b_register_rfq_cpt', 8 );

/**
 * Org profile fields on a user.
 *
 * @param int $user_id User ID.
 * @return array<string, string>
 */
function snf_b2b_get_org_profile( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return array(
			'name'     => '',
			'website'  => '',
			'category' => '',
			'city'     => '',
			'blurb'    => '',
		);
	}

	return array(
		'name'     => (string) get_user_meta( $user_id, 'snf_org_name', true ),
		'website'  => (string) get_user_meta( $user_id, 'snf_org_website', true ),
		'category' => (string) get_user_meta( $user_id, 'snf_org_category', true ),
		'city'     => (string) get_user_meta( $user_id, 'snf_org_city', true ),
		'blurb'    => (string) get_user_meta( $user_id, 'snf_org_blurb', true ),
	);
}

/**
 * Save org profile from account form / REST.
 *
 * @param int   $user_id User ID.
 * @param array $data    Field map.
 * @return array<string, string>
 */
function snf_b2b_save_org_profile( $user_id, $data ) {
	$user_id = absint( $user_id );
	$map     = array(
		'name'     => 'snf_org_name',
		'website'  => 'snf_org_website',
		'category' => 'snf_org_category',
		'city'     => 'snf_org_city',
		'blurb'    => 'snf_org_blurb',
	);

	foreach ( $map as $key => $meta ) {
		if ( ! array_key_exists( $key, $data ) ) {
			continue;
		}
		$value = $data[ $key ];
		if ( 'website' === $key ) {
			$value = esc_url_raw( (string) $value );
		} elseif ( 'blurb' === $key ) {
			$value = sanitize_textarea_field( (string) $value );
		} else {
			$value = sanitize_text_field( (string) $value );
		}
		update_user_meta( $user_id, $meta, $value );
	}

	return snf_b2b_get_org_profile( $user_id );
}

/**
 * Create an RFQ post from marketplace intake.
 *
 * @param array $payload Request fields.
 * @return int|WP_Error Post ID.
 */
function snf_b2b_create_rfq( $payload ) {
	$settings = snf_get_rfq_settings();

	if ( empty( $settings['enabled'] ) ) {
		return new WP_Error( 'snf_rfq_disabled', __( 'RFQ intake is currently disabled.', 'super-ninety-four' ), array( 'status' => 403 ) );
	}

	if ( ! empty( $settings['members_only'] ) ) {
		if ( ! is_user_logged_in() || ! function_exists( 'snf_user_can_access_member_areas' ) || ! snf_user_can_access_member_areas() ) {
			return new WP_Error( 'snf_rfq_members', __( 'Sign in as a member to request a quote.', 'super-ninety-four' ), array( 'status' => 403 ) );
		}
	}

	$name     = isset( $payload['name'] ) ? sanitize_text_field( (string) $payload['name'] ) : '';
	$email    = isset( $payload['email'] ) ? sanitize_email( (string) $payload['email'] ) : '';
	$company  = isset( $payload['company'] ) ? sanitize_text_field( (string) $payload['company'] ) : '';
	$product  = isset( $payload['productId'] ) ? absint( $payload['productId'] ) : 0;
	$message  = isset( $payload['message'] ) ? sanitize_textarea_field( (string) $payload['message'] ) : '';
	$audience = isset( $payload['audience'] ) ? sanitize_key( (string) $payload['audience'] ) : (string) $settings['default_audience'];
	$audiences = snf_rfq_audience_choices();
	if ( ! isset( $audiences[ $audience ] ) ) {
		$audience = (string) $settings['default_audience'];
	}

	if ( '' === $name || '' === $email || '' === $message ) {
		return new WP_Error( 'snf_rfq_invalid', __( 'Name, email, and message are required.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}
	if ( ! empty( $settings['require_company'] ) && '' === $company ) {
		return new WP_Error( 'snf_rfq_company', __( 'Company is required.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}
	if ( ! empty( $settings['require_product'] ) && ! $product ) {
		return new WP_Error( 'snf_rfq_product', __( 'Select a listing before requesting a quote.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'snf_rfq_email', __( 'Enter a valid email.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$max_len = max( 200, (int) $settings['max_message_length'] );
	if ( strlen( $message ) > $max_len ) {
		return new WP_Error( 'snf_rfq_long', __( 'Message is too long.', 'super-ninety-four' ), array( 'status' => 400 ) );
	}

	$product_title = '';
	if ( $product ) {
		$p = get_post( $product );
		if ( $p && 'product' === $p->post_type ) {
			$product_title = get_the_title( $p );
		}
	}

	$title = sprintf(
		/* translators: 1: company or name, 2: product title */
		__( 'RFQ · %1$s%2$s', 'super-ninety-four' ),
		$company ? $company : $name,
		$product_title ? ' · ' . $product_title : ''
	);

	$body = "Name: {$name}\nEmail: {$email}\nCompany: {$company}\nAudience: {$audience}\nProduct ID: {$product}\n\n{$message}";

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'snf_rfq',
			'post_status'  => 'private',
			'post_title'   => $title,
			'post_content' => $body,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	update_post_meta( $post_id, '_snf_rfq_name', $name );
	update_post_meta( $post_id, '_snf_rfq_email', $email );
	update_post_meta( $post_id, '_snf_rfq_company', $company );
	update_post_meta( $post_id, '_snf_rfq_product_id', $product );
	update_post_meta( $post_id, '_snf_rfq_audience', $audience );
	update_post_meta( $post_id, '_snf_rfq_user_id', get_current_user_id() );

	$payload_out = array(
		'name'      => $name,
		'email'     => $email,
		'company'   => $company,
		'productId' => $product,
		'message'   => $message,
		'audience'  => $audience,
		'userId'    => get_current_user_id(),
	);

	/**
	 * Fires after an Expo / marketplace RFQ is stored.
	 *
	 * @param int   $post_id RFQ post ID.
	 * @param array $payload Sanitized payload.
	 */
	do_action( 'snf_marketplace_rfq_submitted', $post_id, $payload_out );

	snf_b2b_notify_rfq_staff( (int) $post_id, $payload_out );

	return (int) $post_id;
}

/**
 * Email staff when a new RFQ arrives (optional notify_email setting).
 *
 * @param int                  $post_id RFQ ID.
 * @param array<string, mixed> $payload Sanitized payload.
 */
function snf_b2b_notify_rfq_staff( $post_id, $payload ) {
	$settings = snf_get_rfq_settings();
	$to       = isset( $settings['notify_email'] ) ? sanitize_email( (string) $settings['notify_email'] ) : '';
	if ( ! $to ) {
		$to = (string) get_option( 'admin_email' );
	}
	if ( ! is_email( $to ) ) {
		return;
	}

	$subject = sprintf(
		/* translators: %s: RFQ title */
		__( '[SNF] New marketplace RFQ: %s', 'super-ninety-four' ),
		get_the_title( $post_id )
	);
	$edit = get_edit_post_link( $post_id, 'raw' );
	$body = sprintf(
		"Name: %s\nEmail: %s\nCompany: %s\nAudience: %s\nProduct ID: %s\n\n%s\n\nReview: %s\n",
		(string) ( $payload['name'] ?? '' ),
		(string) ( $payload['email'] ?? '' ),
		(string) ( $payload['company'] ?? '' ),
		(string) ( $payload['audience'] ?? '' ),
		(string) ( $payload['productId'] ?? '' ),
		(string) ( $payload['message'] ?? '' ),
		$edit ? $edit : ''
	);

	wp_mail( $to, $subject, $body );
}

/**
 * REST: submit RFQ (public allowed; rate-light via nonce when logged in).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_b2b_rest_submit_rfq( WP_REST_Request $request ) {
	$result = snf_b2b_create_rfq(
		array(
			'name'      => $request->get_param( 'name' ),
			'email'     => $request->get_param( 'email' ),
			'company'   => $request->get_param( 'company' ),
			'productId' => $request->get_param( 'productId' ),
			'message'   => $request->get_param( 'message' ),
			'audience'  => $request->get_param( 'audience' ),
		)
	);

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$settings = snf_get_rfq_settings();

	return rest_ensure_response(
		array(
			'ok'      => true,
			'rfqId'   => $result,
			'message' => (string) $settings['success_message'],
		)
	);
}

/**
 * REST: get/save org profile (members only).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function snf_b2b_rest_org_profile( WP_REST_Request $request ) {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'snf_auth', __( 'Sign in required.', 'super-ninety-four' ), array( 'status' => 401 ) );
	}
	if ( ! function_exists( 'snf_user_can_access_member_areas' ) || ! snf_user_can_access_member_areas() ) {
		return new WP_Error( 'snf_forbidden', __( 'Members only.', 'super-ninety-four' ), array( 'status' => 403 ) );
	}

	$user_id = get_current_user_id();
	if ( 'POST' === $request->get_method() || 'PUT' === $request->get_method() ) {
		$profile = snf_b2b_save_org_profile(
			$user_id,
			array(
				'name'     => $request->get_param( 'name' ),
				'website'  => $request->get_param( 'website' ),
				'category' => $request->get_param( 'category' ),
				'city'     => $request->get_param( 'city' ),
				'blurb'    => $request->get_param( 'blurb' ),
			)
		);
	} else {
		$profile = snf_b2b_get_org_profile( $user_id );
	}

	return rest_ensure_response( array( 'org' => $profile ) );
}

/**
 * Register B2B REST routes.
 */
function snf_b2b_register_rest_routes() {
	register_rest_route(
		'snf/v1',
		'/marketplace/rfq',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'snf_b2b_rest_submit_rfq',
			'permission_callback' => '__return_true',
			'args'                => array(
				'name'      => array(
					'type'     => 'string',
					'required' => true,
				),
				'email'     => array(
					'type'     => 'string',
					'required' => true,
				),
				'company'   => array(
					'type'    => 'string',
					'default' => '',
				),
				'productId' => array(
					'type'    => 'integer',
					'default' => 0,
				),
				'message'   => array(
					'type'     => 'string',
					'required' => true,
				),
				'audience'  => array(
					'type'    => 'string',
					'default' => 'public',
				),
			),
		)
	);

	register_rest_route(
		'snf/v1',
		'/marketplace/org',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'snf_b2b_rest_org_profile',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'snf_b2b_rest_org_profile',
				'permission_callback' => '__return_true',
			),
		)
	);
}
add_action( 'rest_api_init', 'snf_b2b_register_rest_routes' );

/**
 * JS bootstrap extras for Expo RFQ form.
 *
 * @param array $boot Existing expo/marketplace boot.
 * @return array
 */
function snf_b2b_filter_expo_bootstrap( $boot ) {
	if ( ! is_array( $boot ) ) {
		$boot = array();
	}

	$settings = snf_get_rfq_settings();

	$boot['rfqUrl']   = esc_url_raw( rest_url( 'snf/v1/marketplace/rfq' ) );
	$boot['orgUrl']   = esc_url_raw( rest_url( 'snf/v1/marketplace/org' ) );
	$boot['rfqNonce'] = wp_create_nonce( 'wp_rest' );
	$boot['org']      = ( is_user_logged_in() && function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas() )
		? snf_b2b_get_org_profile( get_current_user_id() )
		: null;
	$boot['rfq']      = array(
		'enabled'            => ! empty( $settings['enabled'] ),
		'membersOnly'        => ! empty( $settings['members_only'] ),
		'requireCompany'     => ! empty( $settings['require_company'] ),
		'requireProduct'     => ! empty( $settings['require_product'] ),
		'maxMessageLength'   => max( 200, (int) $settings['max_message_length'] ),
		'intro'              => (string) $settings['form_intro'],
		'defaultAudience'    => (string) $settings['default_audience'],
	);
	$boot['rfqI18n']  = array(
		'title'   => (string) $settings['form_title'],
		'submit'  => __( 'Send RFQ', 'super-ninety-four' ),
		'success' => (string) $settings['success_message'],
		'error'   => __( 'Could not send RFQ. Try again.', 'super-ninety-four' ),
		'company' => __( 'Company', 'super-ninety-four' ),
		'name'    => __( 'Name', 'super-ninety-four' ),
		'email'   => __( 'Email', 'super-ninety-four' ),
		'message' => __( 'Message', 'super-ninety-four' ),
	);

	return $boot;
}
add_filter( 'snf_expo_js_bootstrap', 'snf_b2b_filter_expo_bootstrap' );
add_filter( 'snf_marketplace_js_bootstrap', 'snf_b2b_filter_expo_bootstrap' );

/**
 * RFQ list-table columns.
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function snf_b2b_rfq_columns( $columns ) {
	return array(
		'cb'           => $columns['cb'] ?? '',
		'title'        => __( 'RFQ', 'super-ninety-four' ),
		'snf_contact'  => __( 'Contact', 'super-ninety-four' ),
		'snf_company'  => __( 'Company', 'super-ninety-four' ),
		'snf_audience' => __( 'Audience', 'super-ninety-four' ),
		'snf_product'  => __( 'Product', 'super-ninety-four' ),
		'date'         => __( 'Date', 'super-ninety-four' ),
	);
}
add_filter( 'manage_snf_rfq_posts_columns', 'snf_b2b_rfq_columns' );

/**
 * @param string $column  Column.
 * @param int    $post_id Post ID.
 */
function snf_b2b_rfq_column_content( $column, $post_id ) {
	if ( 'snf_contact' === $column ) {
		$name  = (string) get_post_meta( $post_id, '_snf_rfq_name', true );
		$email = (string) get_post_meta( $post_id, '_snf_rfq_email', true );
		echo esc_html( $name );
		if ( $email ) {
			echo '<br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
		}
	}
	if ( 'snf_company' === $column ) {
		$company = (string) get_post_meta( $post_id, '_snf_rfq_company', true );
		echo esc_html( $company ? $company : '—' );
	}
	if ( 'snf_audience' === $column ) {
		$audience  = (string) get_post_meta( $post_id, '_snf_rfq_audience', true );
		$choices   = snf_rfq_audience_choices();
		echo esc_html( $choices[ $audience ] ?? ( $audience ?: '—' ) );
	}
	if ( 'snf_product' === $column ) {
		$product_id = absint( get_post_meta( $post_id, '_snf_rfq_product_id', true ) );
		if ( $product_id ) {
			$edit = get_edit_post_link( $product_id );
			$title = get_the_title( $product_id );
			if ( $edit ) {
				echo '<a href="' . esc_url( $edit ) . '">' . esc_html( $title ? $title : '#' . $product_id ) . '</a>';
			} else {
				echo esc_html( $title ? $title : '#' . $product_id );
			}
		} else {
			echo '—';
		}
	}
}
add_action( 'manage_snf_rfq_posts_custom_column', 'snf_b2b_rfq_column_content', 10, 2 );

/**
 * Meta box summarizing RFQ fields.
 */
function snf_b2b_rfq_meta_boxes() {
	add_meta_box(
		'snf_rfq_details',
		__( 'RFQ details', 'super-ninety-four' ),
		'snf_b2b_render_rfq_details_box',
		'snf_rfq',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes_snf_rfq', 'snf_b2b_rfq_meta_boxes' );

/**
 * @param WP_Post $post Post.
 */
function snf_b2b_render_rfq_details_box( $post ) {
	$name       = (string) get_post_meta( $post->ID, '_snf_rfq_name', true );
	$email      = (string) get_post_meta( $post->ID, '_snf_rfq_email', true );
	$company    = (string) get_post_meta( $post->ID, '_snf_rfq_company', true );
	$audience   = (string) get_post_meta( $post->ID, '_snf_rfq_audience', true );
	$product_id = absint( get_post_meta( $post->ID, '_snf_rfq_product_id', true ) );
	$user_id    = absint( get_post_meta( $post->ID, '_snf_rfq_user_id', true ) );
	$choices    = snf_rfq_audience_choices();
	?>
	<p><strong><?php esc_html_e( 'Name', 'super-ninety-four' ); ?>:</strong> <?php echo esc_html( $name ?: '—' ); ?></p>
	<p><strong><?php esc_html_e( 'Email', 'super-ninety-four' ); ?>:</strong>
		<?php if ( $email ) : ?>
			<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
		<?php else : ?>
			—
		<?php endif; ?>
	</p>
	<p><strong><?php esc_html_e( 'Company', 'super-ninety-four' ); ?>:</strong> <?php echo esc_html( $company ?: '—' ); ?></p>
	<p><strong><?php esc_html_e( 'Audience', 'super-ninety-four' ); ?>:</strong> <?php echo esc_html( $choices[ $audience ] ?? ( $audience ?: '—' ) ); ?></p>
	<p><strong><?php esc_html_e( 'Product', 'super-ninety-four' ); ?>:</strong>
		<?php
		if ( $product_id ) {
			$edit = get_edit_post_link( $product_id );
			$label = get_the_title( $product_id ) ?: ( '#' . $product_id );
			if ( $edit ) {
				echo '<a href="' . esc_url( $edit ) . '">' . esc_html( $label ) . '</a>';
			} else {
				echo esc_html( $label );
			}
		} else {
			echo '—';
		}
		?>
	</p>
	<p><strong><?php esc_html_e( 'User', 'super-ninety-four' ); ?>:</strong>
		<?php
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			$edit = get_edit_user_link( $user_id );
			$label = $user ? $user->user_login : ( '#' . $user_id );
			if ( $edit ) {
				echo '<a href="' . esc_url( $edit ) . '">' . esc_html( $label ) . '</a>';
			} else {
				echo esc_html( $label );
			}
		} else {
			esc_html_e( 'Guest', 'super-ninety-four' );
		}
		?>
	</p>
	<?php
}
