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
			'slug'         => 'badlands',
			'name'         => __( 'Badlands Plan', 'super-ninety-four' ),
			'price'        => '$2,129.00',
			'amount_raw'   => 2129.0,
			'cadence'      => __( '/mo', 'super-ninety-four' ),
			'term'         => __( 'for 12 months', 'super-ninety-four' ),
			'note'         => $renewal_note,
			'product_slug' => 'membership-badlands',
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
 * Ensure membership product category + catalog products exist (draft-friendly publish).
 */
function snf_ensure_membership_products() {
	if ( ! class_exists( 'WooCommerce' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	if ( get_option( 'snf_membership_products_seeded_v1' ) ) {
		return;
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
		$existing = get_page_by_path( $row['slug'], OBJECT, 'product' );
		if ( $existing ) {
			$product_id = (int) $existing->ID;
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

	$defs = snf_get_membership_plan_definitions();
	$slug = isset( $defs['application_fee']['product_slug'] ) ? $defs['application_fee']['product_slug'] : 'membership-application';
	$price = isset( $defs['application_fee']['amount_raw'] ) ? $defs['application_fee']['amount_raw'] : 97.0;

	$existing = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $existing ) {
		$product_id = (int) $existing->ID;
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

	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'snf_woocommerce_membership_product_fields', 20 );

/**
 * Save club membership product meta.
 *
 * @param int $product_id Product ID.
 */
function snf_woocommerce_save_membership_product_fields( $product_id ) {
	if ( isset( $_POST[ SNF_PRODUCT_PLAN_META ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$plan = sanitize_title( wp_unslash( $_POST[ SNF_PRODUCT_PLAN_META ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $product_id, SNF_PRODUCT_PLAN_META, $plan );
	}

	$is_init = isset( $_POST[ SNF_PRODUCT_INITIATION_META ] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	update_post_meta( $product_id, SNF_PRODUCT_INITIATION_META, $is_init );

	$is_app = isset( $_POST[ SNF_PRODUCT_APPLICATION_META ] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	update_post_meta( $product_id, SNF_PRODUCT_APPLICATION_META, $is_app );
}
add_action( 'woocommerce_process_product_meta', 'snf_woocommerce_save_membership_product_fields', 20 );

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
