<?php
/**
 * Custom memberships & subscriptions (no paid plugins).
 *
 * Hook contract:
 * - Listen: woocommerce_payment_complete, woocommerce_order_status_processing/completed,
 *   admin_post_snf_apply_membership (WC cart only).
 * - Emit: snf_membership_activated, snf_membership_ended, snf_membership_renewal_reminder_sent,
 *   snf_membership_dues_reminder_sent, snf_apply_checkout_prepared.
 * - Cron: snf_membership_daily_sweep (+ per-user expire/remind) via SNF\Support\Scheduling.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** User meta: plan slug (back-creek, badlands, …). */
const SNF_META_PLAN = 'snf_membership_plan';

/** User meta: active|pending|expired|cancelled|grace. */
const SNF_META_STATUS = 'snf_membership_status';

/** User meta: MySQL datetime membership started. */
const SNF_META_STARTED = 'snf_membership_started_at';

/** User meta: MySQL datetime term ends. */
const SNF_META_EXPIRES = 'snf_membership_expires_at';

/** User meta: MySQL datetime next dues reminder. */
const SNF_META_NEXT_DUES = 'snf_membership_next_dues_at';

/** User meta: Woo order ID that activated membership. */
const SNF_META_ORDER = 'snf_membership_order_id';

/** User meta: initiation fee paid (1|0). */
const SNF_META_INITIATION = 'snf_initiation_paid';

/** Product meta: maps a Woo product to a plan slug. */
const SNF_PRODUCT_PLAN_META = '_snf_membership_plan';

/** Product meta: mark product as initiation fee. */
const SNF_PRODUCT_INITIATION_META = '_snf_is_initiation';

/** Product meta: mark product as application fee ($97). */
const SNF_PRODUCT_APPLICATION_META = '_snf_is_application';

/** Product meta: Stripe Product id (test mode). */
const SNF_PRODUCT_STRIPE_PRODUCT_TEST = '_snf_stripe_product_id_test';

/** Product meta: Stripe Product id (live mode). */
const SNF_PRODUCT_STRIPE_PRODUCT_LIVE = '_snf_stripe_product_id_live';

/** Product meta: Stripe Price id (test mode). */
const SNF_PRODUCT_STRIPE_PRICE_TEST = '_snf_stripe_price_id_test';

/** Product meta: Stripe Price id (live mode). */
const SNF_PRODUCT_STRIPE_PRICE_LIVE = '_snf_stripe_price_id_live';

/** Option: mirrored Stripe catalog id map for admin / health. */
const SNF_OPTION_STRIPE_CATALOG_IDS = 'snf_stripe_catalog_ids_v1';

/** User meta: application fee paid (1|0). */
const SNF_META_APPLICATION = 'snf_application_paid';

/**
 * Plan catalog with numeric amounts for order matching / display.
 *
 * @return array{
 *   initiation_fee:array{amount:string,amount_raw:float,label:string,meta:string,product_slug:string},
 *   term_months:int,
 *   renewal_note:string,
 *   plans:array<int,array<string,mixed>>
 * }
 */
function snf_get_membership_plan_definitions() {
	$renewal_note = __( 'You will get a reminder before renewal.', 'super-ninety-four' );

	$plans = array(
		array(
			'slug'         => 'back-creek',
			'name'         => __( 'Back Creek Plan', 'super-ninety-four' ),
			'price'        => '$1,000.00',
			'amount_raw'   => 1000.0,
			'cadence'      => __( '/mo', 'super-ninety-four' ),
			'term'         => __( 'for 12 months', 'super-ninety-four' ),
			'note'         => $renewal_note,
			'product_slug' => 'membership-back-creek',
			'role'         => 'snf_member',
		),
		array(
			'slug'         => 'regions-ne',
			'name'         => __( 'Regions NE Plan', 'super-ninety-four' ),
			'price'        => '$2,899.00',
			'amount_raw'   => 2899.0,
			'cadence'      => __( '/mo', 'super-ninety-four' ),
			'term'         => __( 'for 12 months', 'super-ninety-four' ),
			'note'         => $renewal_note,
			'product_slug' => 'membership-regions-ne',
			'role'         => 'snf_member',
		),
		array(
			'slug'         => 'city-to-city',
			'name'         => __( 'City to City', 'super-ninety-four' ),
			'price'        => '$3,649.00',
			'amount_raw'   => 3649.0,
			'cadence'      => __( '/mo', 'super-ninety-four' ),
			'term'         => __( 'for 12 months', 'super-ninety-four' ),
			'note'         => $renewal_note,
			'product_slug' => 'membership-city-to-city',
			'role'         => 'snf_member',
		),
	);

	/**
	 * Filter membership plan definitions.
	 *
	 * @param array $catalog Catalog.
	 */
	return apply_filters(
		'snf_membership_plan_definitions',
		array(
			'application_fee' => array(
				'amount'       => '$97.00',
				'amount_raw'   => 97.0,
				'label'        => __( 'Application fee', 'super-ninety-four' ),
				'meta'         => __( '4-week experience / application fee', 'super-ninety-four' ),
				'product_slug' => 'membership-application',
			),
			'initiation_fee'  => array(
				'amount'       => '$75,000.00',
				'amount_raw'   => 75000.0,
				'label'        => __( 'Initiation fee', 'super-ninety-four' ),
				'meta'         => __( 'Required for all membership plans', 'super-ninety-four' ),
				'product_slug' => 'membership-initiation',
			),
			'term_months'     => 12,
			'renewal_note'    => $renewal_note,
			'plans'           => $plans,
		)
	);
}

/**
 * Keep snf_get_membership_plans() shape for Apply / event UI.
 *
 * @return array<string, mixed>
 */
function snf_membership_plans_compat_shape() {
	$defs = snf_get_membership_plan_definitions();
	return array(
		'initiation_fee' => array(
			'amount' => $defs['initiation_fee']['amount'],
			'label'  => $defs['initiation_fee']['label'],
			'meta'   => $defs['initiation_fee']['meta'],
		),
		'term'           => sprintf(
			/* translators: %d: months */
			_n( '%d month', '%d months', (int) $defs['term_months'], 'super-ninety-four' ),
			(int) $defs['term_months']
		),
		'renewal_note'   => $defs['renewal_note'],
		'plans'          => array_map(
			static function ( $plan ) {
				return array(
					'slug'    => $plan['slug'],
					'name'    => $plan['name'],
					'price'   => $plan['price'],
					'cadence' => $plan['cadence'],
					'term'    => $plan['term'],
					'note'    => $plan['note'],
				);
			},
			$defs['plans']
		),
	);
}

/**
 * Find a plan definition by slug.
 *
 * @param string $slug Plan slug.
 * @return array<string, mixed>|null
 */
function snf_get_membership_plan_by_slug( $slug ) {
	$slug = sanitize_title( (string) $slug );
	foreach ( snf_get_membership_plan_definitions()['plans'] as $plan ) {
		if ( $plan['slug'] === $slug ) {
			return $plan;
		}
	}
	return null;
}

/**
 * Raw membership state from user meta.
 *
 * @param int $user_id User ID.
 * @return array<string, mixed>
 */
function snf_get_user_membership( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return array(
			'plan'            => '',
			'status'          => '',
			'started_at'      => '',
			'expires_at'      => '',
			'next_dues_at'    => '',
			'order_id'        => 0,
			'initiation_paid' => false,
			'is_active'       => false,
			'plan_row'        => null,
		);
	}

	$plan   = (string) get_user_meta( $user_id, SNF_META_PLAN, true );
	$status = (string) get_user_meta( $user_id, SNF_META_STATUS, true );
	$expires = (string) get_user_meta( $user_id, SNF_META_EXPIRES, true );

	$is_active = in_array( $status, array( 'active', 'grace' ), true );
	if ( $is_active && $expires ) {
		$exp_ts = strtotime( $expires );
		if ( $exp_ts && $exp_ts < time() ) {
			$is_active = false;
		}
	}

	return array(
		'plan'            => $plan,
		'status'          => $status,
		'started_at'      => (string) get_user_meta( $user_id, SNF_META_STARTED, true ),
		'expires_at'      => $expires,
		'next_dues_at'    => (string) get_user_meta( $user_id, SNF_META_NEXT_DUES, true ),
		'order_id'        => (int) get_user_meta( $user_id, SNF_META_ORDER, true ),
		'initiation_paid' => (bool) get_user_meta( $user_id, SNF_META_INITIATION, true ),
		'is_active'       => $is_active,
		'plan_row'        => $plan ? snf_get_membership_plan_by_slug( $plan ) : null,
	);
}

/**
 * Schedule a one-off membership job via Action Scheduler or WP-Cron.
 *
 * @param int    $timestamp Unix timestamp.
 * @param string $hook      Action hook.
 * @param array  $args      Args.
 * @param string $group     AS group.
 * @return void
 */
function snf_membership_schedule_single( $timestamp, $hook, $args = array(), $group = 'snf-memberships' ) {
	\SNF\Support\Scheduling::once( (string) $hook, (int) $timestamp, (array) $args, (string) $group );
}

/**
 * Clear scheduled membership jobs for a user.
 *
 * @param int $user_id User ID.
 */
function snf_membership_clear_user_schedules( $user_id ) {
	$user_id = (int) $user_id;
	$hooks   = array(
		'snf_membership_expire_user',
		'snf_membership_renewal_reminder',
		'snf_membership_dues_reminder',
	);

	foreach ( $hooks as $hook ) {
		$args = array( $user_id );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $hook, $args, 'snf-memberships' );
		}
		wp_clear_scheduled_hook( $hook, $args );
	}
}

/**
 * Activate or extend a membership for a user.
 *
 * @param int                  $user_id  User ID.
 * @param string               $plan_slug Plan slug.
 * @param array<string, mixed> $args     Optional: order_id, initiation_paid, role, term_months.
 * @return bool
 */
function snf_activate_membership( $user_id, $plan_slug, $args = array() ) {
	$user_id   = (int) $user_id;
	$plan_slug = sanitize_title( (string) $plan_slug );
	$plan      = snf_get_membership_plan_by_slug( $plan_slug );
	if ( $user_id <= 0 || ! $plan ) {
		return false;
	}

	$defs        = snf_get_membership_plan_definitions();
	$term_months = isset( $args['term_months'] ) ? max( 1, (int) $args['term_months'] ) : (int) $defs['term_months'];
	$role        = ! empty( $args['role'] ) ? (string) $args['role'] : (string) $plan['role'];
	$order_id    = isset( $args['order_id'] ) ? (int) $args['order_id'] : 0;
	$tz          = wp_timezone();
	$now         = new DateTimeImmutable( 'now', $tz );
	$expires     = $now->modify( '+' . $term_months . ' months' );
	$next_dues   = $now->modify( '+1 month' );
	$reminder    = $expires->modify( '-14 days' );

	update_user_meta( $user_id, SNF_META_PLAN, $plan_slug );
	update_user_meta( $user_id, SNF_META_STATUS, 'active' );
	update_user_meta( $user_id, SNF_META_STARTED, $now->format( 'Y-m-d H:i:s' ) );
	update_user_meta( $user_id, SNF_META_EXPIRES, $expires->format( 'Y-m-d H:i:s' ) );
	update_user_meta( $user_id, SNF_META_NEXT_DUES, $next_dues->format( 'Y-m-d H:i:s' ) );
	if ( $order_id > 0 ) {
		update_user_meta( $user_id, SNF_META_ORDER, $order_id );
	}
	if ( ! empty( $args['initiation_paid'] ) ) {
		update_user_meta( $user_id, SNF_META_INITIATION, 1 );
	}

	if ( function_exists( 'snf_assign_club_role' ) ) {
		$user = get_userdata( $user_id );
		// Admins/staff keep their roles; club dues upgrade reciprocal → member.
		$skip = array( 'administrator', 'snf_staff' );
		if ( ! $user || ! array_intersect( $skip, (array) $user->roles ) ) {
			snf_assign_club_role( $user_id, $role );
		}
	}

	snf_membership_clear_user_schedules( $user_id );
	snf_membership_schedule_single( $expires->getTimestamp(), 'snf_membership_expire_user', array( $user_id ) );
	if ( $reminder->getTimestamp() > time() ) {
		snf_membership_schedule_single( $reminder->getTimestamp(), 'snf_membership_renewal_reminder', array( $user_id ) );
	}
	snf_membership_schedule_single( $next_dues->getTimestamp(), 'snf_membership_dues_reminder', array( $user_id ) );

	/**
	 * Fires after a membership is activated.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $plan_slug Plan slug.
	 * @param array  $args      Activation args.
	 */
	do_action( 'snf_membership_activated', $user_id, $plan_slug, $args );

	return true;
}

/**
 * Expire or cancel membership access.
 *
 * @param int    $user_id User ID.
 * @param string $status  expired|cancelled.
 * @return void
 */
function snf_end_membership( $user_id, $status = 'expired' ) {
	$user_id = (int) $user_id;
	$status  = in_array( $status, array( 'expired', 'cancelled' ), true ) ? $status : 'expired';
	if ( $user_id <= 0 ) {
		return;
	}

	update_user_meta( $user_id, SNF_META_STATUS, $status );
	snf_membership_clear_user_schedules( $user_id );

	$user = get_userdata( $user_id );
	if ( $user && in_array( 'snf_member', (array) $user->roles, true ) ) {
		$user->remove_role( 'snf_member' );
		if ( function_exists( 'snf_assign_club_role' ) ) {
			$fallback = 'snf_applicant';
			if ( function_exists( 'snf_user_has_active_reciprocal' ) && snf_user_has_active_reciprocal( $user_id ) ) {
				$fallback = 'snf_reciprocal';
			}
			snf_assign_club_role( $user_id, $fallback );
		}
	}

	/**
	 * Fires after membership ends.
	 *
	 * @param int    $user_id User ID.
	 * @param string $status  Status.
	 */
	do_action( 'snf_membership_ended', $user_id, $status );
}

/**
 * Resolve plan slug from a Woo product.
 *
 * @param int|WC_Product $product Product.
 * @return string
 */
function snf_get_plan_slug_from_product( $product ) {
	if ( is_numeric( $product ) ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( (int) $product ) : null;
	}
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return '';
	}

	$meta = (string) $product->get_meta( SNF_PRODUCT_PLAN_META, true );
	if ( $meta ) {
		return sanitize_title( $meta );
	}

	$slug = $product->get_slug();
	foreach ( snf_get_membership_plan_definitions()['plans'] as $plan ) {
		if ( $slug === $plan['product_slug'] || false !== strpos( $slug, $plan['slug'] ) ) {
			return $plan['slug'];
		}
	}

	return '';
}

/**
 * Whether a product is the initiation fee SKU.
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function snf_product_is_initiation( $product ) {
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return false;
	}
	if ( 'yes' === $product->get_meta( SNF_PRODUCT_INITIATION_META, true ) || '1' === $product->get_meta( SNF_PRODUCT_INITIATION_META, true ) ) {
		return true;
	}
	$init = snf_get_membership_plan_definitions()['initiation_fee'];
	return $product->get_slug() === $init['product_slug'];
}

/**
 * Whether a product is the application fee SKU.
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function snf_product_is_application( $product ) {
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return false;
	}
	if ( 'yes' === $product->get_meta( SNF_PRODUCT_APPLICATION_META, true ) || '1' === $product->get_meta( SNF_PRODUCT_APPLICATION_META, true ) ) {
		return true;
	}
	$defs = snf_get_membership_plan_definitions();
	$app  = isset( $defs['application_fee'] ) ? $defs['application_fee'] : null;
	return $app && $product->get_slug() === $app['product_slug'];
}

/**
 * Extract membership signals from a Woo order.
 *
 * @param WC_Order $order Order.
 * @return array{plan:string,initiation:bool,application:bool,product_ids:int[]}
 */
function snf_membership_signals_from_order( $order ) {
	$plan         = '';
	$initiation   = false;
	$application  = false;
	$product_ids  = array();

	if ( ! $order ) {
		return compact( 'plan', 'initiation', 'application', 'product_ids' );
	}

	$order_plan = (string) $order->get_meta( '_snf_membership_plan' );
	if ( $order_plan ) {
		$plan = sanitize_title( $order_plan );
	}

	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}
		$product_ids[] = (int) $product->get_id();

		$item_plan = (string) $item->get_meta( '_snf_membership_plan' );
		if ( ! $item_plan ) {
			$item_plan = snf_get_plan_slug_from_product( $product );
		}
		if ( $item_plan && ! $plan ) {
			$plan = sanitize_title( $item_plan );
		}
		if ( snf_product_is_initiation( $product ) ) {
			$initiation = true;
		}
		if ( snf_product_is_application( $product ) ) {
			$application = true;
		}
	}

	return array(
		'plan'         => $plan,
		'initiation'   => $initiation,
		'application'  => $application,
		'product_ids'  => $product_ids,
	);
}

/**
 * Process a completed/processing order for membership activation.
 *
 * @param int $order_id Order ID.
 */
function snf_process_membership_order( $order_id ) {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$order_id = (int) $order_id;
	$order    = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	// Idempotency.
	if ( 'yes' === $order->get_meta( '_snf_membership_processed' ) ) {
		return;
	}

	$user_id = (int) $order->get_user_id();
	if ( $user_id <= 0 ) {
		return;
	}

	$signals = snf_membership_signals_from_order( $order );
	if ( ! $signals['plan'] && ! $signals['initiation'] && empty( $signals['application'] ) ) {
		// Non-membership order: leave alone (workshop / events keep their own flows).
		return;
	}

	if ( ! empty( $signals['application'] ) ) {
		update_user_meta( $user_id, SNF_META_APPLICATION, 1 );
		update_user_meta( $user_id, 'snf_application_paid_at', time() );
		update_user_meta( $user_id, 'snf_application_order_id', $order_id );
		if ( function_exists( 'snf_assign_club_role' ) ) {
			$user = get_userdata( $user_id );
			if ( $user && ! array_intersect( array( 'administrator', 'snf_staff', 'snf_reciprocal', 'snf_member' ), (array) $user->roles ) ) {
				snf_assign_club_role( $user_id, 'snf_applicant' );
			}
		}
		/**
		 * Fires when an application fee is recorded from a WooCommerce order.
		 *
		 * @param int      $user_id  User ID.
		 * @param int      $order_id Order ID.
		 * @param WC_Order $order    Order.
		 */
		do_action( 'snf_application_paid', $user_id, $order_id, $order );
		$order->add_order_note( __( 'SUPER NINETY FOUR: application fee recorded.', 'super-ninety-four' ) );
	}

	if ( $signals['plan'] ) {
		snf_activate_membership(
			$user_id,
			$signals['plan'],
			array(
				'order_id'         => $order_id,
				'initiation_paid'  => $signals['initiation'],
			)
		);
		$order->add_order_note(
			sprintf(
				/* translators: %s: plan slug */
				__( 'SUPER NINETY FOUR: membership activated (%s).', 'super-ninety-four' ),
				$signals['plan']
			)
		);
	} elseif ( $signals['initiation'] ) {
		update_user_meta( $user_id, SNF_META_INITIATION, 1 );
		if ( function_exists( 'snf_assign_club_role' ) ) {
			$user = get_userdata( $user_id );
			if ( $user && ! array_intersect( array( 'administrator', 'snf_staff', 'snf_reciprocal', 'snf_member' ), (array) $user->roles ) ) {
				snf_assign_club_role( $user_id, 'snf_applicant' );
			}
		}
		$order->add_order_note( __( 'SUPER NINETY FOUR: initiation fee recorded.', 'super-ninety-four' ) );
	}

	$order->update_meta_data( '_snf_membership_processed', 'yes' );
	if ( $signals['plan'] ) {
		$order->update_meta_data( '_snf_membership_plan', $signals['plan'] );
	}
	$order->save();
}
add_action( 'woocommerce_order_status_completed', 'snf_process_membership_order', 25 );
add_action( 'woocommerce_order_status_processing', 'snf_process_membership_order', 25 );
add_action( 'woocommerce_payment_complete', 'snf_process_membership_order', 25 );

/**
 * Persist selected plan on checkout line items when present in cart item data.
 *
 * @param WC_Order_Item_Product $item          Item.
 * @param string                $cart_item_key Key.
 * @param array                 $values        Cart values.
 * @param WC_Order              $order         Order.
 */
function snf_membership_checkout_line_item( $item, $cart_item_key, $values, $order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	if ( ! empty( $values['snf_membership_plan'] ) ) {
		$item->add_meta_data( '_snf_membership_plan', sanitize_title( (string) $values['snf_membership_plan'] ), true );
	} elseif ( ! empty( $values['data'] ) && is_a( $values['data'], 'WC_Product' ) ) {
		$plan = snf_get_plan_slug_from_product( $values['data'] );
		if ( $plan ) {
			$item->add_meta_data( '_snf_membership_plan', $plan, true );
		}
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'snf_membership_checkout_line_item', 20, 4 );

/**
 * Expire user membership (scheduled).
 *
 * @param int $user_id User ID.
 */
function snf_membership_expire_user_action( $user_id ) {
	$user_id = (int) $user_id;
	$state   = snf_get_user_membership( $user_id );
	if ( ! $state['is_active'] && 'active' !== $state['status'] && 'grace' !== $state['status'] ) {
		return;
	}
	snf_end_membership( $user_id, 'expired' );
}
add_action( 'snf_membership_expire_user', 'snf_membership_expire_user_action', 10, 1 );

/**
 * Renewal reminder (scheduled).
 *
 * @param int $user_id User ID.
 */
function snf_membership_renewal_reminder_action( $user_id ) {
	$user_id = (int) $user_id;
	$user    = get_userdata( $user_id );
	$state   = snf_get_user_membership( $user_id );
	if ( ! $user || ! $state['is_active'] ) {
		return;
	}

	$plan_name = $state['plan_row'] ? $state['plan_row']['name'] : $state['plan'];
	$subject   = sprintf(
		/* translators: %s: site name */
		__( '[%s] Membership renewal reminder', 'super-ninety-four' ),
		wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
	);
	$message = sprintf(
		/* translators: 1: member name, 2: plan, 3: expiry date */
		__( "Hi %1\$s,\n\nYour %2\$s membership term ends on %3\$s. Renew to keep club access uninterrupted.\n\n— SUPER NINETY FOUR", 'super-ninety-four' ),
		$user->display_name,
		$plan_name,
		$state['expires_at'] ? date_i18n( get_option( 'date_format' ), strtotime( $state['expires_at'] ) ) : ''
	);
	wp_mail( $user->user_email, $subject, $message );

	do_action( 'snf_membership_renewal_reminder_sent', $user_id, $state );
}
add_action( 'snf_membership_renewal_reminder', 'snf_membership_renewal_reminder_action', 10, 1 );

/**
 * Monthly dues reminder (scheduled); reschedules next month while active.
 *
 * @param int $user_id User ID.
 */
function snf_membership_dues_reminder_action( $user_id ) {
	$user_id = (int) $user_id;
	$user    = get_userdata( $user_id );
	$state   = snf_get_user_membership( $user_id );
	if ( ! $user || ! $state['is_active'] ) {
		return;
	}

	$plan_name = $state['plan_row'] ? $state['plan_row']['name'] : $state['plan'];
	$dues      = $state['plan_row'] ? $state['plan_row']['price'] : '';
	$subject   = sprintf(
		/* translators: %s: site name */
		__( '[%s] Membership dues reminder', 'super-ninety-four' ),
		wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
	);
	$message = sprintf(
		/* translators: 1: name, 2: plan, 3: dues amount */
		__( "Hi %1\$s,\n\nThis is a reminder that %2\$s dues (%3\$s/mo) are coming due.\n\n— SUPER NINETY FOUR", 'super-ninety-four' ),
		$user->display_name,
		$plan_name,
		$dues
	);
	wp_mail( $user->user_email, $subject, $message );

	$tz        = wp_timezone();
	$next_dues = ( new DateTimeImmutable( 'now', $tz ) )->modify( '+1 month' );
	update_user_meta( $user_id, SNF_META_NEXT_DUES, $next_dues->format( 'Y-m-d H:i:s' ) );

	$expires_ts = $state['expires_at'] ? strtotime( $state['expires_at'] ) : 0;
	if ( ! $expires_ts || $next_dues->getTimestamp() < $expires_ts ) {
		snf_membership_schedule_single( $next_dues->getTimestamp(), 'snf_membership_dues_reminder', array( $user_id ) );
	}

	do_action( 'snf_membership_dues_reminder_sent', $user_id, $state );
}
add_action( 'snf_membership_dues_reminder', 'snf_membership_dues_reminder_action', 10, 1 );

/**
 * Daily sweep: expire overdue memberships (safety net for missed single events).
 */
function snf_membership_daily_sweep() {
	$q = new WP_User_Query(
		array(
			'number'     => 200,
			'fields'     => 'ID',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => SNF_META_STATUS,
					'value'   => array( 'active', 'grace' ),
					'compare' => 'IN',
				),
				array(
					'key'     => SNF_META_EXPIRES,
					'value'   => current_time( 'mysql' ),
					'compare' => '<',
					'type'    => 'DATETIME',
				),
			),
		)
	);

	foreach ( (array) $q->get_results() as $user_id ) {
		snf_end_membership( (int) $user_id, 'expired' );
	}
}
add_action( 'snf_membership_daily_sweep', 'snf_membership_daily_sweep' );

/**
 * Ensure daily sweep is scheduled.
 */
function snf_membership_schedule_cron() {
	\SNF\Support\Scheduling::recurring(
		'snf_membership_daily_sweep',
		DAY_IN_SECONDS,
		array(),
		'snf-memberships',
		time() + HOUR_IN_SECONDS
	);
}
add_action( 'init', 'snf_membership_schedule_cron', 30 );

/**
 * Flat membership commerce rows used for Woo seed + Stripe Product/Price sync.
 *
 * @return array<int, array{sku:string,title:string,amount_raw:float,kind:string,plan:string}>
 */
function snf_get_membership_catalog_rows() {
	$defs = snf_get_membership_plan_definitions();
	$rows = array(
		array(
			'sku'        => isset( $defs['application_fee']['product_slug'] ) ? $defs['application_fee']['product_slug'] : 'membership-application',
			'title'      => __( 'Club Application Fee', 'super-ninety-four' ),
			'amount_raw' => isset( $defs['application_fee']['amount_raw'] ) ? (float) $defs['application_fee']['amount_raw'] : 97.0,
			'kind'       => 'application',
			'plan'       => '',
		),
		array(
			'sku'        => $defs['initiation_fee']['product_slug'],
			'title'      => __( 'Club Initiation Fee', 'super-ninety-four' ),
			'amount_raw' => (float) $defs['initiation_fee']['amount_raw'],
			'kind'       => 'initiation',
			'plan'       => '',
		),
	);

	foreach ( $defs['plans'] as $plan ) {
		$rows[] = array(
			'sku'        => $plan['product_slug'],
			'title'      => $plan['name'],
			'amount_raw' => (float) $plan['amount_raw'],
			'kind'       => 'dues',
			'plan'       => $plan['slug'],
		);
	}

	/**
	 * Filter membership catalog rows (Woo + Stripe).
	 *
	 * @param array<int, array{sku:string,title:string,amount_raw:float,kind:string,plan:string}> $rows Rows.
	 */
	return apply_filters( 'snf_membership_catalog_rows', $rows );
}

/**
 * Persist Stripe Product/Price ids onto a Woo product + option map.
 *
 * @param int    $product_id Woo product ID.
 * @param string $sku        Catalog SKU.
 * @param string $mode       test|live.
 * @param string $product    Stripe product id.
 * @param string $price      Stripe price id.
 */
function snf_set_membership_product_stripe_ids( $product_id, $sku, $mode, $product, $price ) {
	$product_id = (int) $product_id;
	$mode       = ( 'live' === $mode ) ? 'live' : 'test';
	$sku        = sanitize_title( (string) $sku );
	$product    = sanitize_text_field( (string) $product );
	$price      = sanitize_text_field( (string) $price );

	if ( $product_id <= 0 || ! $sku ) {
		return;
	}

	if ( 'live' === $mode ) {
		update_post_meta( $product_id, SNF_PRODUCT_STRIPE_PRODUCT_LIVE, $product );
		update_post_meta( $product_id, SNF_PRODUCT_STRIPE_PRICE_LIVE, $price );
	} else {
		update_post_meta( $product_id, SNF_PRODUCT_STRIPE_PRODUCT_TEST, $product );
		update_post_meta( $product_id, SNF_PRODUCT_STRIPE_PRICE_TEST, $price );
	}

	$map = get_option( SNF_OPTION_STRIPE_CATALOG_IDS, array() );
	if ( ! is_array( $map ) ) {
		$map = array();
	}
	if ( empty( $map[ $sku ] ) || ! is_array( $map[ $sku ] ) ) {
		$map[ $sku ] = array();
	}
	$map[ $sku ][ $mode ] = array(
		'product_id'     => $product,
		'price_id'       => $price,
		'woo_product_id' => $product_id,
		'updated_at'     => time(),
	);
	update_option( SNF_OPTION_STRIPE_CATALOG_IDS, $map, false );
}

/**
 * Stripe ids stored on a Woo membership product.
 *
 * @param int $product_id Product ID.
 * @return array{test:array{product:string,price:string},live:array{product:string,price:string}}
 */
function snf_get_membership_product_stripe_ids( $product_id ) {
	$product_id = (int) $product_id;
	return array(
		'test' => array(
			'product' => (string) get_post_meta( $product_id, SNF_PRODUCT_STRIPE_PRODUCT_TEST, true ),
			'price'   => (string) get_post_meta( $product_id, SNF_PRODUCT_STRIPE_PRICE_TEST, true ),
		),
		'live' => array(
			'product' => (string) get_post_meta( $product_id, SNF_PRODUCT_STRIPE_PRODUCT_LIVE, true ),
			'price'   => (string) get_post_meta( $product_id, SNF_PRODUCT_STRIPE_PRICE_LIVE, true ),
		),
	);
}

/**
 * Presence map for all membership catalog Woo products.
 *
 * @return array{ok:bool,missing:string[],products:array<string,int>}
 */
function snf_membership_products_status() {
	$products = array();
	$missing  = array();
	foreach ( snf_get_membership_catalog_rows() as $row ) {
		$id = snf_get_membership_product_id_by_sku( $row['sku'] );
		$products[ $row['sku'] ] = $id;
		if ( $id <= 0 ) {
			$missing[] = $row['sku'];
		}
	}
	return array(
		'ok'       => empty( $missing ),
		'missing'  => $missing,
		'products' => $products,
	);
}

/**
 * Resolve Woo product ID by catalog SKU / post_name.
 *
 * @param string $sku Product slug.
 * @return int
 */
function snf_get_membership_product_id_by_sku( $sku ) {
	$sku = sanitize_title( (string) $sku );
	if ( ! $sku ) {
		return 0;
	}
	$product = get_page_by_path( $sku, OBJECT, 'product' );
	return $product ? (int) $product->ID : 0;
}

/**
 * Resolve product ID for a membership plan slug or the initiation SKU.
 *
 * @param string $key Plan slug, or 'initiation'.
 * @return int
 */
function snf_get_membership_product_id( $key ) {
	$key  = sanitize_title( (string) $key );
	$defs = snf_get_membership_plan_definitions();

	if ( 'initiation' === $key ) {
		$slug = $defs['initiation_fee']['product_slug'];
	} elseif ( 'application' === $key ) {
		$slug = isset( $defs['application_fee']['product_slug'] ) ? $defs['application_fee']['product_slug'] : 'membership-application';
	} else {
		$plan = snf_get_membership_plan_by_slug( $key );
		if ( ! $plan ) {
			return 0;
		}
		$slug = $plan['product_slug'];
	}

	$product = get_page_by_path( $slug, OBJECT, 'product' );
	return $product ? (int) $product->ID : 0;
}

/**
 * Put initiation + selected dues plan into the cart and return checkout URL.
 *
 * @param string $plan_slug Plan slug.
 * @return string|\WP_Error Checkout URL or error.
 */
function snf_membership_prepare_checkout_for_plan( $plan_slug ) {
	if ( ! function_exists( 'WC' ) ) {
		return new WP_Error( 'snf_no_cart', __( 'Cart is unavailable.', 'super-ninety-four' ) );
	}

	if ( function_exists( 'wc_load_cart' ) ) {
		wc_load_cart();
	}

	if ( ! WC()->cart ) {
		return new WP_Error( 'snf_no_cart', __( 'Cart is unavailable.', 'super-ninety-four' ) );
	}

	$plan_slug = sanitize_title( (string) $plan_slug );
	$plan_id   = snf_get_membership_product_id( $plan_slug );
	$init_id   = snf_get_membership_product_id( 'initiation' );
	$app_id    = snf_get_membership_product_id( 'application' );

	if ( ! $plan_id ) {
		return new WP_Error( 'snf_no_plan_product', __( 'Membership plan product is missing.', 'super-ninety-four' ) );
	}

	WC()->cart->empty_cart();

	$user_id = get_current_user_id();

	// Application fee ($97) first when unpaid — real WC line item, not theme Stripe.
	if ( $user_id && ! get_user_meta( $user_id, SNF_META_APPLICATION, true ) && $app_id ) {
		WC()->cart->add_to_cart( $app_id, 1 );
	}

	if ( $user_id && ! get_user_meta( $user_id, SNF_META_INITIATION, true ) && $init_id ) {
		WC()->cart->add_to_cart( $init_id, 1 );
	}

	$added = WC()->cart->add_to_cart(
		$plan_id,
		1,
		0,
		array(),
		array( 'snf_membership_plan' => $plan_slug )
	);

	if ( ! $added ) {
		return new WP_Error( 'snf_add_failed', __( 'Could not add the membership plan to the cart.', 'super-ninety-four' ) );
	}

	if ( $user_id ) {
		update_user_meta( $user_id, 'snf_pending_membership_plan', $plan_slug );
	}

	$checkout_url = wc_get_checkout_url();

	/**
	 * Fires after Apply prepares a WooCommerce cart for membership checkout.
	 *
	 * @param string $plan_slug     Plan slug.
	 * @param string $checkout_url  Checkout URL.
	 * @param int    $user_id       User ID.
	 */
	do_action( 'snf_apply_checkout_prepared', $plan_slug, $checkout_url, $user_id );

	return $checkout_url;
}

/**
 * Apply Intent → cart checkout for club dues (+ initiation when unpaid).
 */
function snf_handle_apply_membership_checkout() {
	if ( ! isset( $_POST['snf_apply_plan'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wp_safe_redirect( snf_get_page_url( 'apply' ) );
		exit;
	}

	check_admin_referer( 'snf_apply_membership', 'snf_apply_nonce' );

	$plan_slug = sanitize_title( wp_unslash( $_POST['snf_apply_plan'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$name      = isset( $_POST['snf_apply_name'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$email     = isset( $_POST['snf_apply_email'] ) ? sanitize_email( wp_unslash( $_POST['snf_apply_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	// Stash Apply fields for SNF Payments branded checkout prefill.
	if ( function_exists( 'snf_payments_stash_apply_fields' ) ) {
		snf_payments_stash_apply_fields(
			array(
				'snf_apply_name'          => $name,
				'snf_apply_email'         => $email,
				'snf_apply_phone'         => isset( $_POST['snf_apply_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_phone'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'snf_apply_org'           => isset( $_POST['snf_apply_org'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_org'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'snf_apply_city'          => isset( $_POST['snf_apply_city'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_city'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'snf_apply_referral'      => isset( $_POST['snf_apply_referral'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_referral'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'snf_apply_plan'          => $plan_slug,
				'snf_apply_card_name'     => isset( $_POST['snf_apply_card_name'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_card_name'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'snf_apply_billing_zip'   => isset( $_POST['snf_apply_billing_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_billing_zip'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'snf_apply_billing_notes' => isset( $_POST['snf_apply_billing_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['snf_apply_billing_notes'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'snf_apply_event'         => isset( $_POST['snf_apply_event'] ) ? absint( wp_unslash( $_POST['snf_apply_event'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		);
	} elseif ( function_exists( 'WC' ) ) {
		if ( function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
		if ( WC()->session ) {
			WC()->session->set(
				'snf_apply_checkout_fields',
				array(
					'snf_apply_name'  => $name,
					'snf_apply_email' => $email,
					'snf_apply_plan'  => $plan_slug,
				)
			);
		}
	}

	if ( ! is_user_logged_in() && $email && is_email( $email ) ) {
		$existing = email_exists( $email );
		if ( $existing ) {
			// Soft path: send them to login before checkout.
			$redirect = add_query_arg(
				array(
					'redirect_to' => rawurlencode( snf_get_page_url( 'apply' ) ),
					'email'       => rawurlencode( $email ),
				),
				snf_get_page_url( 'member-login' )
			);
			wp_safe_redirect( $redirect );
			exit;
		}

		$user_login = sanitize_user( current( explode( '@', $email ) ), true );
		if ( ! $user_login ) {
			$user_login = 'member' . wp_generate_password( 6, false, false );
		}
		$base = $user_login;
		$i    = 1;
		while ( username_exists( $user_login ) ) {
			$user_login = $base . $i;
			$i++;
		}

		$user_id = wp_create_user( $user_login, wp_generate_password( 24, true, true ), $email );
		if ( ! is_wp_error( $user_id ) ) {
			if ( $name ) {
				wp_update_user(
					array(
						'ID'           => (int) $user_id,
						'display_name' => $name,
						'first_name'   => $name,
					)
				);
			}
			wp_set_current_user( (int) $user_id );
			wp_set_auth_cookie( (int) $user_id );
		}
	}

	if ( is_user_logged_in() && function_exists( 'snf_assign_club_role' ) ) {
		$user = wp_get_current_user();
		$skip = array( 'administrator', 'snf_staff', 'snf_reciprocal', 'snf_member' );
		if ( ! array_intersect( $skip, (array) $user->roles ) ) {
			snf_assign_club_role( (int) $user->ID, 'snf_applicant' );
		}
		update_user_meta( (int) $user->ID, 'snf_pending_membership_plan', $plan_slug );
	}

	// Always WooCommerce cart → checkout (application + initiation + plan products).
	$result = snf_membership_prepare_checkout_for_plan( $plan_slug );
	if ( is_wp_error( $result ) ) {
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $result->get_error_message(), 'error' );
		}
		wp_safe_redirect( snf_get_page_url( 'under-consideration' ) );
		exit;
	}

	wp_safe_redirect( $result );
	exit;
}
add_action( 'admin_post_nopriv_snf_apply_membership', 'snf_handle_apply_membership_checkout' );
add_action( 'admin_post_snf_apply_membership', 'snf_handle_apply_membership_checkout' );

/**
 * Find a product ID by slug across every non-trash status.
 *
 * get_page_by_path() can miss drafts, pending, and private products, which
 * lets the seeders create a second product for an existing slug. Querying the
 * posts table directly and returning the oldest ID keeps lookups stable.
 *
 * @param string $slug Product slug.
 * @return int Product ID, or 0 when none exists.
 */
function snf_find_product_id_by_slug( $slug ) {
	global $wpdb;

	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return 0;
	}

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_name = %s AND post_status NOT IN ( 'trash', 'auto-draft' ) ORDER BY ID ASC LIMIT 1",
			$slug
		)
	);
}

/**
 * Acquire the atomic membership seed lock.
 *
 * add_option() fails when the option already exists, so concurrent requests
 * cannot both run the seed path and insert duplicate products. Stale locks
 * older than five minutes are reclaimed.
 *
 * @return bool True when this request owns the lock.
 */
function snf_membership_seed_lock_acquire() {
	if ( add_option( 'snf_membership_seed_lock', time(), '', false ) ) {
		return true;
	}

	$locked_at = (int) get_option( 'snf_membership_seed_lock' );
	if ( $locked_at <= 0 || ( time() - $locked_at ) > 300 ) {
		delete_option( 'snf_membership_seed_lock' );
		return (bool) add_option( 'snf_membership_seed_lock', time(), '', false );
	}

	return false;
}

/**
 * Release the membership seed lock.
 */
function snf_membership_seed_lock_release() {
	delete_option( 'snf_membership_seed_lock' );
}

/**
 * Whether a product is referenced by any order item.
 *
 * Checks the legacy order-item meta table and the HPOS product lookup table
 * when it exists.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function snf_product_has_orders( $product_id ) {
	global $wpdb;

	$product_id = (int) $product_id;
	if ( $product_id <= 0 ) {
		return false;
	}

	$legacy = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND meta_value = %d",
			$product_id
		)
	);
	if ( $legacy > 0 ) {
		return true;
	}

	$lookup_table = $wpdb->prefix . 'wc_order_product_lookup';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $lookup_table ) ) === $lookup_table ) {
		$hpos = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$lookup_table} WHERE product_id = %d", $product_id )
		);
		if ( $hpos > 0 ) {
			return true;
		}
	}

	return false;
}

/**
 * Choose which duplicate membership product to keep.
 *
 * Prefers a product carrying Stripe IDs, then one with orders, then the
 * oldest product.
 *
 * @param int[] $ids Product IDs.
 * @return int
 */
function snf_pick_canonical_membership_product( array $ids ) {
	$ids = array_values( array_map( 'intval', $ids ) );

	$stripe_keys = array(
		SNF_PRODUCT_STRIPE_PRODUCT_LIVE,
		SNF_PRODUCT_STRIPE_PRICE_LIVE,
		SNF_PRODUCT_STRIPE_PRODUCT_TEST,
		SNF_PRODUCT_STRIPE_PRICE_TEST,
	);
	foreach ( $ids as $id ) {
		foreach ( $stripe_keys as $key ) {
			if ( '' !== (string) get_post_meta( $id, $key, true ) ) {
				return $id;
			}
		}
	}

	foreach ( $ids as $id ) {
		if ( snf_product_has_orders( $id ) ) {
			return $id;
		}
	}

	return isset( $ids[0] ) ? (int) $ids[0] : 0;
}

/**
 * Collapse duplicate membership products created by earlier seed races.
 *
 * Keeps the canonical product (Stripe IDs, then orders, then oldest) and
 * trashes the extras. Never trashes a product that has orders. Runs once per
 * version unless forced.
 *
 * @param bool $force Run even when the dedupe version option is already set.
 * @return int[] Product IDs that were trashed.
 */
function snf_dedupe_membership_products( $force = false ) {
	global $wpdb;

	if ( ! class_exists( 'WooCommerce' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$version = '1.0.0-membership-dedupe';
	if ( ! $force && get_option( 'snf_membership_products_dedupe' ) === $version ) {
		return array();
	}

	$defs  = snf_get_membership_plan_definitions();
	$slugs = array( 'membership-application' );
	if ( ! empty( $defs['application_fee']['product_slug'] ) ) {
		$slugs[] = (string) $defs['application_fee']['product_slug'];
	}

	$trashed = array();
	foreach ( array_unique( array_filter( $slugs ) ) as $slug ) {
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_name = %s AND post_status NOT IN ( 'trash', 'auto-draft' ) ORDER BY ID ASC",
				sanitize_title( $slug )
			)
		);
		$ids = array_map( 'intval', (array) $ids );
		if ( count( $ids ) < 2 ) {
			continue;
		}

		$canonical = snf_pick_canonical_membership_product( $ids );
		foreach ( $ids as $id ) {
			if ( $id === $canonical || snf_product_has_orders( $id ) ) {
				continue;
			}
			wp_trash_post( $id );
			$trashed[] = $id;
		}
	}

	update_option( 'snf_membership_products_dedupe', $version, false );

	return $trashed;
}
add_action( 'init', 'snf_dedupe_membership_products', 52 );

/**
 * Ensure membership product category + catalog products exist (draft-friendly publish).
 *
 * @param bool $force Re-run even when the one-shot seed option is set (creates missing only).
 * @return bool True when the seed path ran.
 */
function snf_ensure_membership_products( $force = false ) {
	if ( ! class_exists( 'WooCommerce' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return false;
	}

	if ( ! $force && get_option( 'snf_membership_products_seeded_v1' ) ) {
		return false;
	}

	if ( ! snf_membership_seed_lock_acquire() ) {
		return false;
	}

	if ( ! term_exists( 'memberships', 'product_cat' ) ) {
		wp_insert_term(
			__( 'Memberships', 'super-ninety-four' ),
			'product_cat',
			array( 'slug' => 'memberships' )
		);
	}

	$defs = snf_get_membership_plan_definitions();

	$to_create = array(
		array(
			'slug'         => isset( $defs['application_fee']['product_slug'] ) ? $defs['application_fee']['product_slug'] : 'membership-application',
			'title'        => __( 'Club Application Fee', 'super-ninety-four' ),
			'price'        => isset( $defs['application_fee']['amount_raw'] ) ? $defs['application_fee']['amount_raw'] : 97.0,
			'plan'         => '',
			'initiation'   => false,
			'application'  => true,
		),
		array(
			'slug'         => $defs['initiation_fee']['product_slug'],
			'title'        => __( 'Club Initiation Fee', 'super-ninety-four' ),
			'price'        => $defs['initiation_fee']['amount_raw'],
			'plan'         => '',
			'initiation'   => true,
			'application'  => false,
		),
	);

	foreach ( $defs['plans'] as $plan ) {
		$to_create[] = array(
			'slug'        => $plan['product_slug'],
			'title'       => $plan['name'],
			'price'       => $plan['amount_raw'],
			'plan'        => $plan['slug'],
			'initiation'  => false,
			'application' => false,
		);
	}

	foreach ( $to_create as $row ) {
		$existing_id = snf_find_product_id_by_slug( $row['slug'] );
		if ( $existing_id ) {
			$product_id = $existing_id;
		} else {
			$product_id = wp_insert_post(
				array(
					'post_title'   => $row['title'],
					'post_name'    => $row['slug'],
					'post_status'  => 'publish',
					'post_type'    => 'product',
					'post_content' => '',
				),
				true
			);
			if ( is_wp_error( $product_id ) || ! $product_id ) {
				continue;
			}
		}

		wp_set_object_terms( $product_id, 'memberships', 'product_cat', true );
		update_post_meta( $product_id, '_regular_price', (string) $row['price'] );
		update_post_meta( $product_id, '_price', (string) $row['price'] );
		update_post_meta( $product_id, '_virtual', 'yes' );
		update_post_meta( $product_id, '_sold_individually', 'yes' );
		update_post_meta( $product_id, '_manage_stock', 'no' );
		wp_set_object_terms( $product_id, 'simple', 'product_type', false );

		if ( $row['plan'] ) {
			update_post_meta( $product_id, SNF_PRODUCT_PLAN_META, $row['plan'] );
		}
		if ( $row['initiation'] ) {
			update_post_meta( $product_id, SNF_PRODUCT_INITIATION_META, 'yes' );
		}
		if ( ! empty( $row['application'] ) ) {
			update_post_meta( $product_id, SNF_PRODUCT_APPLICATION_META, 'yes' );
		}
	}

	update_option( 'snf_membership_products_seeded_v1', 1, false );
	update_option( 'snf_membership_products_seeded_v2', 1, false );

	snf_membership_seed_lock_release();

	return true;
}
add_action( 'init', 'snf_ensure_membership_products', 50 );

/**
 * Ensure the application fee WC product exists even when v1 seed already ran.
 */
function snf_ensure_application_product() {
	if ( ! class_exists( 'WooCommerce' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}
	if ( get_option( 'snf_membership_products_seeded_v2' ) ) {
		return;
	}

	if ( ! snf_membership_seed_lock_acquire() ) {
		return;
	}

	$defs = snf_get_membership_plan_definitions();
	$slug = isset( $defs['application_fee']['product_slug'] ) ? $defs['application_fee']['product_slug'] : 'membership-application';
	$price = isset( $defs['application_fee']['amount_raw'] ) ? $defs['application_fee']['amount_raw'] : 97.0;

	$existing_id = snf_find_product_id_by_slug( $slug );
	if ( $existing_id ) {
		$product_id = $existing_id;
	} else {
		$product_id = wp_insert_post(
			array(
				'post_title'   => __( 'Club Application Fee', 'super-ninety-four' ),
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'product',
				'post_content' => '',
			),
			true
		);
		if ( is_wp_error( $product_id ) || ! $product_id ) {
			snf_membership_seed_lock_release();
			return;
		}
	}

	if ( ! term_exists( 'memberships', 'product_cat' ) ) {
		wp_insert_term(
			__( 'Memberships', 'super-ninety-four' ),
			'product_cat',
			array( 'slug' => 'memberships' )
		);
	}

	wp_set_object_terms( $product_id, 'memberships', 'product_cat', true );
	update_post_meta( $product_id, '_regular_price', (string) $price );
	update_post_meta( $product_id, '_price', (string) $price );
	update_post_meta( $product_id, '_virtual', 'yes' );
	update_post_meta( $product_id, '_sold_individually', 'yes' );
	update_post_meta( $product_id, '_manage_stock', 'no' );
	update_post_meta( $product_id, SNF_PRODUCT_APPLICATION_META, 'yes' );
	wp_set_object_terms( $product_id, 'simple', 'product_type', false );

	update_option( 'snf_membership_products_seeded_v2', 1, false );

	snf_membership_seed_lock_release();
}
add_action( 'init', 'snf_ensure_application_product', 51 );

/**
 * Product admin: map SKU → club plan / initiation.
 */
function snf_woocommerce_membership_product_fields() {
	$options = array( '' => __( '— Not a club dues product —', 'super-ninety-four' ) );
	foreach ( snf_get_membership_plan_definitions()['plans'] as $plan ) {
		$options[ $plan['slug'] ] = $plan['name'];
	}

	echo '<div class="options_group">';
	echo '<input type="hidden" name="snf_membership_fields" value="1" />';

	woocommerce_wp_select(
		array(
			'id'          => SNF_PRODUCT_PLAN_META,
			'label'       => __( 'Club membership plan', 'super-ninety-four' ),
			'options'     => $options,
			'desc_tip'    => true,
			'description' => __( 'Purchasing this product activates Club Member access for the plan term (12 months).', 'super-ninety-four' ),
		)
	);

	woocommerce_wp_checkbox(
		array(
			'id'          => SNF_PRODUCT_INITIATION_META,
			'label'       => __( 'Initiation fee product', 'super-ninety-four' ),
			'description' => __( 'Marks initiation paid on the customer; does not grant full member access by itself.', 'super-ninety-four' ),
		)
	);

	woocommerce_wp_checkbox(
		array(
			'id'          => SNF_PRODUCT_APPLICATION_META,
			'label'       => __( 'Application fee product', 'super-ninety-four' ),
			'description' => __( 'Marks application fee paid on the customer via WooCommerce order hooks.', 'super-ninety-four' ),
		)
	);

	$ids = snf_get_membership_product_stripe_ids( get_the_ID() );
	woocommerce_wp_text_input(
		array(
			'id'                => SNF_PRODUCT_STRIPE_PRICE_TEST,
			'label'             => __( 'Stripe Price (test)', 'super-ninety-four' ),
			'desc_tip'          => true,
			'description'       => __( 'Filled by Club → Ensure / sync Stripe catalog. Leave blank unless overriding.', 'super-ninety-four' ),
			'custom_attributes' => array( 'placeholder' => 'price_…' ),
			'value'             => $ids['test']['price'],
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'                => SNF_PRODUCT_STRIPE_PRICE_LIVE,
			'label'             => __( 'Stripe Price (live)', 'super-ninety-four' ),
			'desc_tip'          => true,
			'description'       => __( 'Filled by Club → Ensure / sync Stripe catalog. Leave blank unless overriding.', 'super-ninety-four' ),
			'custom_attributes' => array( 'placeholder' => 'price_…' ),
			'value'             => $ids['live']['price'],
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'                => SNF_PRODUCT_STRIPE_PRODUCT_TEST,
			'label'             => __( 'Stripe Product (test)', 'super-ninety-four' ),
			'custom_attributes' => array( 'placeholder' => 'prod_…' ),
			'value'             => $ids['test']['product'],
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'                => SNF_PRODUCT_STRIPE_PRODUCT_LIVE,
			'label'             => __( 'Stripe Product (live)', 'super-ninety-four' ),
			'custom_attributes' => array( 'placeholder' => 'prod_…' ),
			'value'             => $ids['live']['product'],
		)
	);

	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'snf_woocommerce_membership_product_fields', 20 );

/**
 * Save club membership product meta.
 *
 * @param int $product_id Product ID.
 */
function snf_woocommerce_save_membership_product_fields( $product_id ) {
	if ( ! current_user_can( 'edit_post', $product_id ) ) {
		return;
	}

	// Only trust checkbox state when our panel was actually submitted.
	if ( ! isset( $_POST['snf_membership_fields'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	if ( isset( $_POST[ SNF_PRODUCT_PLAN_META ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$plan = sanitize_title( wp_unslash( $_POST[ SNF_PRODUCT_PLAN_META ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $product_id, SNF_PRODUCT_PLAN_META, $plan );
	}

	$is_init = isset( $_POST[ SNF_PRODUCT_INITIATION_META ] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	update_post_meta( $product_id, SNF_PRODUCT_INITIATION_META, $is_init );

	$is_app = isset( $_POST[ SNF_PRODUCT_APPLICATION_META ] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	update_post_meta( $product_id, SNF_PRODUCT_APPLICATION_META, $is_app );

	$stripe_fields = array(
		SNF_PRODUCT_STRIPE_PRICE_TEST,
		SNF_PRODUCT_STRIPE_PRICE_LIVE,
		SNF_PRODUCT_STRIPE_PRODUCT_TEST,
		SNF_PRODUCT_STRIPE_PRODUCT_LIVE,
	);
	foreach ( $stripe_fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $product_id, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}
}
add_action( 'woocommerce_process_product_meta', 'snf_woocommerce_save_membership_product_fields', 20 );

/**
 * End membership when a fully refunded SNF Payments order carried club dues.
 *
 * @param int      $order_id Order ID.
 * @param WC_Order $order    Order.
 */
function snf_membership_on_payments_full_refund( $order_id, $order = null ) {
	$order_id = (int) $order_id;
	if ( ! $order instanceof WC_Order && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );
	}
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$user_id = (int) $order->get_user_id();
	if ( $user_id <= 0 ) {
		return;
	}

	$signals = snf_membership_signals_from_order( $order );
	if ( empty( $signals['plan'] ) && empty( $signals['initiation'] ) && empty( $signals['application'] ) ) {
		// Fallback: order meta written at activation.
		if ( 'yes' !== $order->get_meta( '_snf_membership_processed' ) && ! $order->get_meta( '_snf_membership_plan' ) ) {
			return;
		}
	}

	snf_end_membership( $user_id, 'cancelled' );
	$order->add_order_note( __( 'SUPER NINETY FOUR: membership ended after full Stripe refund.', 'super-ninety-four' ) );
}
add_action( 'snf_payments_order_fully_refunded', 'snf_membership_on_payments_full_refund', 10, 2 );

/**
 * Live billing hub data from user meta (+ Woo payment methods when available).
 *
 * @param int $user_id User ID.
 * @return array<string, mixed>
 */
function snf_get_live_billing_hub_data( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$defs    = snf_get_membership_plan_definitions();
	$state   = snf_get_user_membership( $user_id );
	$plan    = $state['plan_row'];
	if ( ! $plan ) {
		$plan = array(
			'slug'    => '',
			'name'    => __( 'No club plan selected', 'super-ninety-four' ),
			'price'   => '—',
			'cadence' => '',
			'term'    => $defs['renewal_note'],
			'note'    => '',
		);
	}

	$status_label = __( 'Inactive', 'super-ninety-four' );
	if ( $state['status'] ) {
		$status_label = ucwords( str_replace( '-', ' ', $state['status'] ) );
	} elseif ( ! $state['plan'] ) {
		$status_label = __( 'Choose a plan', 'super-ninety-four' );
	}

	$renews = $plan['term'] . ( $plan['note'] ? ' · ' . $plan['note'] : '' );
	if ( $state['expires_at'] ) {
		$renews = sprintf(
			/* translators: 1: date, 2: renewal note */
			__( 'Term ends %1$s · %2$s', 'super-ninety-four' ),
			date_i18n( get_option( 'date_format' ), strtotime( $state['expires_at'] ) ),
			$defs['renewal_note']
		);
	}

	$methods = array();
	if ( $user_id && function_exists( 'wc_get_customer_saved_methods_list' ) ) {
		foreach ( (array) wc_get_customer_saved_methods_list( $user_id ) as $method ) {
			$methods[] = array(
				'label'  => isset( $method['method']['brand'] )
					? sprintf( '%s ···· %s', strtoupper( (string) $method['method']['brand'] ), (string) $method['method']['last4'] )
					: __( 'Saved payment method', 'super-ninety-four' ),
				'meta'   => ! empty( $method['is_default'] ) ? __( 'Primary', 'super-ninety-four' ) : __( 'On file', 'super-ninety-four' ),
				'action' => __( 'Manage', 'super-ninety-four' ),
			);
		}
	}
	if ( ! $methods ) {
		$methods[] = array(
			'label'  => __( 'No card on file', 'super-ninety-four' ),
			'meta'   => __( 'Add a payment method in My Account', 'super-ninety-four' ),
			'action' => __( 'Add', 'super-ninety-four' ),
		);
	}

	$invoices = array();
	if ( $user_id && function_exists( 'wc_get_orders' ) ) {
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 8,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'status'      => array( 'processing', 'completed' ),
			)
		);
		foreach ( $orders as $order ) {
			$invoices[] = array(
				'id'     => $order->get_order_number(),
				'label'  => sprintf(
					/* translators: %s: order number */
					__( 'Order #%s', 'super-ninety-four' ),
					$order->get_order_number()
				),
				'date'   => wc_format_datetime( $order->get_date_created() ),
				'amount' => wp_strip_all_tags( $order->get_formatted_order_total() ),
				'status' => wc_get_order_status_name( $order->get_status() ),
			);
		}
	}

	return array(
		'initiation' => $defs['initiation_fee'],
		'plans'      => $defs['plans'],
		'plan'       => array(
			'name'   => $plan['name'],
			'price'  => $plan['price'] . $plan['cadence'],
			'renews' => $renews,
			'status' => $status_label,
			'slug'   => $plan['slug'],
		),
		'membership' => $state,
		'metrics'    => array(
			array(
				'label' => __( 'Initiation fee', 'super-ninety-four' ),
				'value' => $defs['initiation_fee']['amount'],
				'hint'  => $state['initiation_paid']
					? __( 'Paid', 'super-ninety-four' )
					: __( 'All plans', 'super-ninety-four' ),
			),
			array(
				'label' => __( 'Current dues', 'super-ninety-four' ),
				'value' => ! empty( $state['plan_row']['price'] ) ? $state['plan_row']['price'] : '—',
				'hint'  => __( 'Monthly · 12-month term', 'super-ninety-four' ),
			),
			array(
				'label' => __( 'Renewal notice', 'super-ninety-four' ),
				'value' => $state['expires_at']
					? date_i18n( get_option( 'date_format' ), strtotime( $state['expires_at'] ) )
					: __( 'Before term', 'super-ninety-four' ),
				'hint'  => __( 'Reminder sent prior to renewal', 'super-ninety-four' ),
			),
		),
		'methods'    => $methods,
		'invoices'   => $invoices,
	);
}
