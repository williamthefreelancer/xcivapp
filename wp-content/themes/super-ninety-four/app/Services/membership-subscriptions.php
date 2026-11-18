<?php
/**
 * Reciprocal-program subscriptions (theme-native, no paid plugins).
 *
 * Hook contract:
 * - Listen: woocommerce_order_status_processing/completed.
 * - Emit: snf_reciprocal_subscription_activated, snf_reciprocal_subscription_expired.
 * - Cron: snf_expire_reciprocal_subscriptions via SNF\Support\Scheduling.
 *
 * Grants snf_reciprocal + user meta from WooCommerce orders for reciprocal
 * program products only. Daily cron expires lapsed programs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var string User meta: active/expired reciprocal subscriptions. */
const SNF_META_RECIPROCAL_SUBS = '_snf_reciprocal_subscriptions';

/** @var string Product meta: reciprocal program slug. */
const SNF_META_PRODUCT_PROGRAM = '_snf_reciprocal_program';

/** @var string Product meta: term length in months. */
const SNF_META_PRODUCT_TERM = '_snf_reciprocal_term_months';

/** @var string Product category for reciprocal subscription SKUs. */
const SNF_RECIPROCAL_PRODUCT_CAT = 'reciprocal';

/** @var string Cron hook. */
const SNF_CRON_RECIPROCAL_EXPIRE = 'snf_expire_reciprocal_subscriptions';

/**
 * Default term length for reciprocal program access.
 *
 * @return int
 */
function snf_get_reciprocal_default_term_months() {
	return (int) apply_filters( 'snf_reciprocal_default_term_months', 12 );
}

/**
 * Program catalog keyed by slug (from reciprocal club landings).
 *
 * @return array<string, array{slug:string,title:string}>
 */
function snf_get_reciprocal_program_catalog() {
	$programs = array();

	if ( function_exists( 'snf_get_reciprocal_clubs' ) ) {
		foreach ( snf_get_reciprocal_clubs() as $slug => $club ) {
			$programs[ $slug ] = array(
				'slug'  => $slug,
				'title' => isset( $club['title'] ) ? (string) $club['title'] : $slug,
			);
		}
	}

	/**
	 * Filter reciprocal program catalog used for products + subscriptions.
	 *
	 * @param array<string, array{slug:string,title:string}> $programs Programs.
	 */
	return apply_filters( 'snf_reciprocal_program_catalog', $programs );
}

/**
 * Whether a product grants reciprocal program access.
 *
 * @param int|WC_Product $product Product.
 * @return string Program slug or empty.
 */
function snf_get_product_reciprocal_program( $product ) {
	$product_id = 0;
	if ( $product instanceof WC_Product ) {
		$product_id = (int) $product->get_id();
	} else {
		$product_id = (int) $product;
	}

	if ( $product_id <= 0 ) {
		return '';
	}

	$slug = sanitize_title( (string) get_post_meta( $product_id, SNF_META_PRODUCT_PROGRAM, true ) );
	if ( $slug ) {
		return $slug;
	}

	// Fallback: product slug matches a known reciprocal program.
	$post = get_post( $product_id );
	if ( $post && isset( snf_get_reciprocal_program_catalog()[ $post->post_name ] ) ) {
		return $post->post_name;
	}

	return '';
}

/**
 * Term months for a reciprocal product.
 *
 * @param int $product_id Product ID.
 * @return int
 */
function snf_get_product_reciprocal_term_months( $product_id ) {
	$months = (int) get_post_meta( (int) $product_id, SNF_META_PRODUCT_TERM, true );
	if ( $months <= 0 ) {
		$months = snf_get_reciprocal_default_term_months();
	}
	return $months;
}

/**
 * Read reciprocal subscriptions for a user.
 *
 * @param int $user_id User ID.
 * @return array<string, array<string, mixed>> keyed by program slug
 */
function snf_get_user_reciprocal_subscriptions( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return array();
	}

	$raw = get_user_meta( $user_id, SNF_META_RECIPROCAL_SUBS, true );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$out = array();
	foreach ( $raw as $slug => $row ) {
		$slug = sanitize_title( (string) $slug );
		if ( ! $slug || ! is_array( $row ) ) {
			continue;
		}
		$out[ $slug ] = array(
			'slug'      => $slug,
			'title'     => isset( $row['title'] ) ? (string) $row['title'] : $slug,
			'status'    => isset( $row['status'] ) ? (string) $row['status'] : 'expired',
			'started'   => isset( $row['started'] ) ? (int) $row['started'] : 0,
			'expires'   => isset( $row['expires'] ) ? (int) $row['expires'] : 0,
			'order_id'  => isset( $row['order_id'] ) ? (int) $row['order_id'] : 0,
			'product_id'=> isset( $row['product_id'] ) ? (int) $row['product_id'] : 0,
		);
	}

	return $out;
}

/**
 * Persist reciprocal subscriptions for a user.
 *
 * @param int                                  $user_id User ID.
 * @param array<string, array<string, mixed>> $subs    Subscriptions.
 */
function snf_save_user_reciprocal_subscriptions( $user_id, $subs ) {
	update_user_meta( (int) $user_id, SNF_META_RECIPROCAL_SUBS, $subs );
}

/**
 * Active reciprocal programs for a user (status active and not past expiry).
 *
 * @param int $user_id User ID.
 * @return array<string, array<string, mixed>>
 */
function snf_get_user_active_reciprocal_programs( $user_id ) {
	$now  = time();
	$subs = snf_get_user_reciprocal_subscriptions( $user_id );
	$out  = array();

	foreach ( $subs as $slug => $row ) {
		if ( 'active' !== $row['status'] ) {
			continue;
		}
		if ( $row['expires'] > 0 && $row['expires'] < $now ) {
			continue;
		}
		$out[ $slug ] = $row;
	}

	return $out;
}

/**
 * Whether the user currently has reciprocal access.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function snf_user_has_active_reciprocal( $user_id ) {
	return (bool) snf_get_user_active_reciprocal_programs( $user_id );
}

/**
 * Activate / renew a reciprocal program subscription from an order line.
 *
 * @param int    $user_id    User ID.
 * @param string $program    Program slug.
 * @param int    $product_id Product ID.
 * @param int    $order_id   Order ID.
 * @param int    $term_months Term months.
 * @return bool
 */
function snf_activate_reciprocal_subscription( $user_id, $program, $product_id, $order_id, $term_months = 0 ) {
	$user_id = (int) $user_id;
	$program = sanitize_title( (string) $program );

	if ( $user_id <= 0 || ! $program ) {
		return false;
	}

	$catalog = snf_get_reciprocal_program_catalog();
	$title   = isset( $catalog[ $program ]['title'] ) ? $catalog[ $program ]['title'] : $program;
	$months  = $term_months > 0 ? (int) $term_months : snf_get_product_reciprocal_term_months( $product_id );
	$now     = time();
	$subs    = snf_get_user_reciprocal_subscriptions( $user_id );

	$base_start = $now;
	if ( ! empty( $subs[ $program ]['expires'] ) && (int) $subs[ $program ]['expires'] > $now ) {
		// Stack renewal onto remaining term.
		$base_start = (int) $subs[ $program ]['expires'];
	}

	$expires = strtotime( '+' . $months . ' months', $base_start );
	if ( ! $expires ) {
		$expires = $base_start + ( $months * MONTH_IN_SECONDS );
	}

	$subs[ $program ] = array(
		'slug'       => $program,
		'title'      => $title,
		'status'     => 'active',
		'started'    => ! empty( $subs[ $program ]['started'] ) ? (int) $subs[ $program ]['started'] : $now,
		'expires'    => (int) $expires,
		'order_id'   => (int) $order_id,
		'product_id' => (int) $product_id,
	);

	snf_save_user_reciprocal_subscriptions( $user_id, $subs );

	$user = get_userdata( $user_id );
	if ( $user ) {
		$protected = array( 'administrator', 'snf_staff', 'snf_member' );
		if ( ! array_intersect( $protected, (array) $user->roles ) ) {
			if ( function_exists( 'snf_assign_club_role' ) ) {
				snf_assign_club_role( $user_id, 'snf_reciprocal' );
			} else {
				$user->add_role( 'snf_reciprocal' );
			}
		} elseif ( ! in_array( 'snf_reciprocal', (array) $user->roles, true ) && ! array_intersect( array( 'administrator', 'snf_staff', 'snf_member' ), (array) $user->roles ) ) {
			$user->add_role( 'snf_reciprocal' );
		}
	}

	/**
	 * Fires after a reciprocal program subscription is activated/renewed.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $program  Program slug.
	 * @param array  $row      Subscription row.
	 */
	do_action( 'snf_reciprocal_subscription_activated', $user_id, $program, $subs[ $program ] );

	return true;
}

/**
 * Sync role after expiry: drop snf_reciprocal when no active programs remain.
 *
 * @param int $user_id User ID.
 */
function snf_sync_reciprocal_role_from_subscriptions( $user_id ) {
	$user_id = (int) $user_id;
	$user    = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}

	$protected = array( 'administrator', 'snf_staff', 'snf_member' );
	if ( array_intersect( $protected, (array) $user->roles ) ) {
		return;
	}

	$active = snf_get_user_active_reciprocal_programs( $user_id );
	if ( $active ) {
		if ( ! in_array( 'snf_reciprocal', (array) $user->roles, true ) ) {
			if ( function_exists( 'snf_assign_club_role' ) ) {
				snf_assign_club_role( $user_id, 'snf_reciprocal' );
			} else {
				$user->add_role( 'snf_reciprocal' );
			}
		}
		return;
	}

	if ( in_array( 'snf_reciprocal', (array) $user->roles, true ) ) {
		$user->remove_role( 'snf_reciprocal' );
		if ( function_exists( 'snf_assign_club_role' ) && ! array_intersect( array( 'snf_applicant' ), (array) $user->roles ) ) {
			// Leave them as applicant so Soft-guard can route to under-consideration.
			snf_assign_club_role( $user_id, 'snf_applicant' );
		}
	}
}

/**
 * Process a WooCommerce order for reciprocal program line items.
 *
 * @param int $order_id Order ID.
 */
function snf_process_order_for_reciprocal_subscriptions( $order_id ) {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$user_id = (int) $order->get_user_id();
	if ( $user_id <= 0 ) {
		return;
	}

	$activated = array();

	foreach ( $order->get_items() as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}
		$product_id = (int) $item->get_product_id();
		$program    = snf_get_product_reciprocal_program( $product_id );
		if ( ! $program ) {
			continue;
		}

		$months = snf_get_product_reciprocal_term_months( $product_id );
		if ( snf_activate_reciprocal_subscription( $user_id, $program, $product_id, (int) $order_id, $months ) ) {
			$activated[] = $program;
		}
	}

	if ( $activated ) {
		$order->update_meta_data( '_snf_reciprocal_programs_activated', array_values( array_unique( $activated ) ) );
		$order->save();
	}
}
add_action( 'woocommerce_order_status_processing', 'snf_process_order_for_reciprocal_subscriptions', 25 );
add_action( 'woocommerce_order_status_completed', 'snf_process_order_for_reciprocal_subscriptions', 25 );

/**
 * Expire lapsed reciprocal subscriptions (cron).
 */
function snf_expire_reciprocal_subscriptions() {
	$now = time();

	$query = new WP_User_Query(
		array(
			'meta_key'     => SNF_META_RECIPROCAL_SUBS,
			'meta_compare' => 'EXISTS',
			'fields'       => 'ID',
			'number'       => 500,
			'count_total'  => false,
		)
	);

	foreach ( (array) $query->get_results() as $user_id ) {
		$user_id = (int) $user_id;
		$subs    = snf_get_user_reciprocal_subscriptions( $user_id );
		$changed = false;

		foreach ( $subs as $slug => $row ) {
			if ( 'active' !== $row['status'] ) {
				continue;
			}
			if ( $row['expires'] > 0 && $row['expires'] < $now ) {
				$subs[ $slug ]['status'] = 'expired';
				$changed                 = true;
				do_action( 'snf_reciprocal_subscription_expired', $user_id, $slug, $subs[ $slug ] );
			}
		}

		if ( $changed ) {
			snf_save_user_reciprocal_subscriptions( $user_id, $subs );
			snf_sync_reciprocal_role_from_subscriptions( $user_id );
		}
	}
}
add_action( SNF_CRON_RECIPROCAL_EXPIRE, 'snf_expire_reciprocal_subscriptions' );

/**
 * Schedule daily reciprocal expiry cron.
 */
function snf_schedule_reciprocal_expiry_cron() {
	\SNF\Support\Scheduling::recurring(
		SNF_CRON_RECIPROCAL_EXPIRE,
		DAY_IN_SECONDS,
		array(),
		'snf-reciprocal',
		time() + HOUR_IN_SECONDS
	);
}
add_action( 'init', 'snf_schedule_reciprocal_expiry_cron', 30 );

/**
 * Ensure reciprocal product category exists.
 */
function snf_ensure_reciprocal_product_category() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}
	if ( term_exists( SNF_RECIPROCAL_PRODUCT_CAT, 'product_cat' ) ) {
		return;
	}
	wp_insert_term(
		__( 'Reciprocal Programs', 'super-ninety-four' ),
		'product_cat',
		array( 'slug' => SNF_RECIPROCAL_PRODUCT_CAT )
	);
}
add_action( 'init', 'snf_ensure_reciprocal_product_category', 28 );

/**
 * Ensure a Woo product exists for each reciprocal program.
 */
function snf_ensure_reciprocal_program_products() {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	$version = '1.0.0-reciprocal-products';
	if ( get_option( 'snf_reciprocal_products_version' ) === $version ) {
		return;
	}

	snf_ensure_reciprocal_product_category();

	foreach ( snf_get_reciprocal_program_catalog() as $slug => $program ) {
		$existing = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $existing ) {
			$product_id = (int) $existing->ID;
		} else {
			$product = new WC_Product_Simple();
			$product->set_name( $program['title'] );
			$product->set_slug( $slug );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'hidden' );
			$product->set_regular_price( '0' );
			$product->set_virtual( true );
			$product->set_sold_individually( true );
			$product->set_short_description(
				sprintf(
					/* translators: %s: program name */
					__( '12-month reciprocal access for %s.', 'super-ninety-four' ),
					$program['title']
				)
			);
			$product_id = $product->save();
		}

		if ( ! $product_id ) {
			continue;
		}

		update_post_meta( $product_id, SNF_META_PRODUCT_PROGRAM, $slug );
		if ( '' === (string) get_post_meta( $product_id, SNF_META_PRODUCT_TERM, true ) ) {
			update_post_meta( $product_id, SNF_META_PRODUCT_TERM, snf_get_reciprocal_default_term_months() );
		}

		if ( taxonomy_exists( 'product_cat' ) && ! has_term( SNF_RECIPROCAL_PRODUCT_CAT, 'product_cat', $product_id ) ) {
			wp_set_object_terms( $product_id, SNF_RECIPROCAL_PRODUCT_CAT, 'product_cat', true );
		}

		if ( taxonomy_exists( 'product_brand' ) && ! has_term( $slug, 'product_brand', $product_id ) ) {
			if ( ! term_exists( $slug, 'product_brand' ) ) {
				wp_insert_term( $program['title'], 'product_brand', array( 'slug' => $slug ) );
			}
			wp_set_object_terms( $product_id, $slug, 'product_brand', true );
		}
	}

	update_option( 'snf_reciprocal_products_version', $version, false );
}
add_action( 'init', 'snf_ensure_reciprocal_program_products', 50 );

/**
 * Product admin fields for reciprocal program mapping.
 */
function snf_woocommerce_reciprocal_product_fields() {
	$programs = snf_get_reciprocal_program_catalog();
	$options  = array( '' => __( '— Not a reciprocal subscription —', 'super-ninety-four' ) );
	foreach ( $programs as $slug => $program ) {
		$options[ $slug ] = $program['title'];
	}

	echo '<div class="options_group">';

	woocommerce_wp_select(
		array(
			'id'          => SNF_META_PRODUCT_PROGRAM,
			'label'       => __( 'Reciprocal program', 'super-ninety-four' ),
			'options'     => $options,
			'desc_tip'    => true,
			'description' => __( 'Purchasing this product grants the Reciprocal Member role and 12-month program access.', 'super-ninety-four' ),
		)
	);

	woocommerce_wp_text_input(
		array(
			'id'                => SNF_META_PRODUCT_TERM,
			'label'             => __( 'Reciprocal term (months)', 'super-ninety-four' ),
			'type'              => 'number',
			'custom_attributes' => array(
				'min'  => '1',
				'step' => '1',
			),
			'desc_tip'          => true,
			'description'       => __( 'Defaults to 12 months when empty.', 'super-ninety-four' ),
		)
	);

	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'snf_woocommerce_reciprocal_product_fields', 25 );

/**
 * Save reciprocal product meta.
 *
 * @param int $product_id Product ID.
 */
function snf_woocommerce_save_reciprocal_product_fields( $product_id ) {
	if ( isset( $_POST[ SNF_META_PRODUCT_PROGRAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$program = sanitize_title( wp_unslash( $_POST[ SNF_META_PRODUCT_PROGRAM ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $product_id, SNF_META_PRODUCT_PROGRAM, $program );
	}

	if ( isset( $_POST[ SNF_META_PRODUCT_TERM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$months = absint( wp_unslash( $_POST[ SNF_META_PRODUCT_TERM ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $months > 0 ) {
			update_post_meta( $product_id, SNF_META_PRODUCT_TERM, $months );
		} else {
			delete_post_meta( $product_id, SNF_META_PRODUCT_TERM );
		}
	}
}
add_action( 'woocommerce_process_product_meta', 'snf_woocommerce_save_reciprocal_product_fields', 25 );

/**
 * Membership endpoint rows driven by live reciprocal subscriptions.
 *
 * @param int $user_id User ID.
 * @return array<int, array<string, string>>
 */
function snf_membership_rows_from_reciprocal_subscriptions( $user_id ) {
	$rows     = array();
	$subs     = snf_get_user_reciprocal_subscriptions( $user_id );
	$active   = snf_get_user_active_reciprocal_programs( $user_id );
	$catalog  = snf_get_reciprocal_program_catalog();
	$hub_url  = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'find-other-reciprocal-programs' ) : home_url( '/' );

	if ( ! $subs ) {
		$rows[] = array(
			'eyebrow' => __( 'Reciprocal', 'super-ninety-four' ),
			'title'   => __( 'No reciprocal programs yet', 'super-ninety-four' ),
			'meta'    => __( 'Purchase a reciprocal program product to unlock partner-club access.', 'super-ninety-four' ),
			'status'  => __( 'Inactive', 'super-ninety-four' ),
			'tone'    => 'neutral',
			'url'     => $hub_url,
			'action'  => __( 'Browse programs', 'super-ninety-four' ),
		);
		return $rows;
	}

	$rows[] = array(
		'eyebrow' => __( 'Reciprocal status', 'super-ninety-four' ),
		'title'   => $active
			? __( 'Reciprocal Member', 'super-ninety-four' )
			: __( 'Reciprocal expired', 'super-ninety-four' ),
		'meta'    => $active
			? sprintf(
				/* translators: %d: active program count */
				_n( '%d active program', '%d active programs', count( $active ), 'super-ninety-four' ),
				count( $active )
			)
			: __( 'Renew a program product to restore access.', 'super-ninety-four' ),
		'status'  => $active ? __( 'Active', 'super-ninety-four' ) : __( 'Expired', 'super-ninety-four' ),
		'tone'    => $active ? 'success' : 'warn',
		'url'     => $hub_url,
		'action'  => __( 'Programs', 'super-ninety-four' ),
	);

	foreach ( $subs as $slug => $row ) {
		$title = $row['title'];
		if ( isset( $catalog[ $slug ]['title'] ) ) {
			$title = $catalog[ $slug ]['title'];
		}
		$is_active = isset( $active[ $slug ] );
		$meta      = '';
		if ( ! empty( $row['expires'] ) ) {
			$meta = sprintf(
				/* translators: %s: date */
				$is_active ? __( 'Access through %s', 'super-ninety-four' ) : __( 'Ended %s', 'super-ninety-four' ),
				date_i18n( get_option( 'date_format' ), (int) $row['expires'] )
			);
		}

		$product_url = $hub_url;
		if ( ! empty( $row['product_id'] ) && function_exists( 'get_permalink' ) ) {
			$link = get_permalink( (int) $row['product_id'] );
			if ( $link ) {
				$product_url = $link;
			}
		}

		$rows[] = array(
			'eyebrow' => __( 'Program', 'super-ninety-four' ),
			'title'   => $title,
			'meta'    => $meta,
			'status'  => $is_active ? __( 'Active', 'super-ninety-four' ) : __( 'Expired', 'super-ninety-four' ),
			'tone'    => $is_active ? 'success' : 'warn',
			'url'     => $product_url,
			'action'  => $is_active ? __( 'View', 'super-ninety-four' ) : __( 'Renew', 'super-ninety-four' ),
		);
	}

	return $rows;
}

/**
 * Live reciprocal summary for billing / hub contexts.
 *
 * @param int $user_id Optional user ID.
 * @return array<string, mixed>
 */
function snf_get_reciprocal_billing_summary( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$active  = snf_get_user_active_reciprocal_programs( $user_id );
	$subs    = snf_get_user_reciprocal_subscriptions( $user_id );

	$primary = null;
	$latest  = 0;
	foreach ( $active as $row ) {
		if ( (int) $row['expires'] >= $latest ) {
			$latest  = (int) $row['expires'];
			$primary = $row;
		}
	}

	return array(
		'active_count' => count( $active ),
		'has_active'   => (bool) $active,
		'programs'     => $subs,
		'active'       => $active,
		'plan'         => $primary
			? array(
				'name'   => $primary['title'],
				'slug'   => $primary['slug'],
				'price'  => '',
				'status' => __( 'Active', 'super-ninety-four' ),
				'renews' => ! empty( $primary['expires'] )
					? sprintf(
						/* translators: %s: date */
						__( 'Through %s', 'super-ninety-four' ),
						date_i18n( get_option( 'date_format' ), (int) $primary['expires'] )
					)
					: __( 'Active reciprocal access', 'super-ninety-four' ),
			)
			: array(
				'name'   => __( 'No active reciprocal program', 'super-ninety-four' ),
				'slug'   => '',
				'price'  => '',
				'status' => __( 'Inactive', 'super-ninety-four' ),
				'renews' => __( 'Browse reciprocal programs to subscribe.', 'super-ninety-four' ),
			),
	);
}
