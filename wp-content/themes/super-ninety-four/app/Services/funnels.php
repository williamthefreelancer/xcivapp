<?php
/**
 * Post-purchase WooCommerce upsell / downsell funnels.
 *
 * Trigger product meta points at Blank Canvas / Funnel Offer pages built with
 * existing theme templates and block patterns. Accept adds the offer product
 * and sends the buyer to classic checkout; decline advances to the downsell
 * page or back to the order-received thank-you.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post meta key for funnel config on trigger products.
 *
 * @return string
 */
function snf_funnel_meta_key() {
	return '_snf_funnel';
}

/**
 * Empty funnel config.
 *
 * @return array<string, mixed>
 */
function snf_funnel_empty_config() {
	return array(
		'enabled'             => false,
		'upsell_page_id'      => 0,
		'upsell_product_id'   => 0,
		'downsell_page_id'    => 0,
		'downsell_product_id' => 0,
	);
}

/**
 * Sanitize funnel config.
 *
 * @param mixed $raw Raw JSON string or array.
 * @return array<string, mixed>
 */
function snf_funnel_sanitize_config( $raw ) {
	$config = snf_funnel_empty_config();

	if ( is_string( $raw ) ) {
		$decoded = json_decode( $raw, true );
		$raw     = is_array( $decoded ) ? $decoded : array();
	}

	if ( ! is_array( $raw ) ) {
		return $config;
	}

	$config['enabled']             = ! empty( $raw['enabled'] );
	$config['upsell_page_id']      = absint( $raw['upsell_page_id'] ?? 0 );
	$config['upsell_product_id']   = absint( $raw['upsell_product_id'] ?? 0 );
	$config['downsell_page_id']    = absint( $raw['downsell_page_id'] ?? 0 );
	$config['downsell_product_id'] = absint( $raw['downsell_product_id'] ?? 0 );

	return $config;
}

/**
 * Read funnel config for a product.
 *
 * @param int $product_id Product ID.
 * @return array<string, mixed>
 */
function snf_get_funnel_config( $product_id ) {
	$product_id = absint( $product_id );
	if ( $product_id <= 0 ) {
		return snf_funnel_empty_config();
	}

	$raw = get_post_meta( $product_id, snf_funnel_meta_key(), true );
	return snf_funnel_sanitize_config( $raw );
}

/**
 * Whether a funnel config can start (enabled + upsell page + upsell product).
 *
 * @param array<string, mixed> $config Config.
 * @return bool
 */
function snf_funnel_config_is_runnable( array $config ) {
	return ! empty( $config['enabled'] )
		&& ! empty( $config['upsell_page_id'] )
		&& ! empty( $config['upsell_product_id'] )
		&& get_post_status( (int) $config['upsell_page_id'] ) === 'publish'
		&& get_post_status( (int) $config['upsell_product_id'] ) === 'publish';
}

/**
 * Resolve the first runnable funnel from a paid order's line items.
 *
 * @param WC_Order $order Order.
 * @return array{product_id:int,config:array<string,mixed>}|null
 */
function snf_funnel_resolve_for_order( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return null;
	}

	if ( $order->get_meta( '_snf_funnel_done' ) ) {
		return null;
	}

	if ( $order->get_meta( '_snf_funnel_parent_order' ) ) {
		return null;
	}

	foreach ( $order->get_items() as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}
		$product_id = (int) $item->get_product_id();
		if ( $product_id <= 0 ) {
			continue;
		}
		$config = snf_get_funnel_config( $product_id );
		if ( snf_funnel_config_is_runnable( $config ) ) {
			return array(
				'product_id' => $product_id,
				'config'     => $config,
			);
		}
	}

	return null;
}

/**
 * Build a funnel offer URL for a step.
 *
 * @param int      $page_id Page ID.
 * @param WC_Order $order   Parent order.
 * @param string   $step    upsell|downsell.
 * @return string
 */
function snf_funnel_offer_url( $page_id, $order, $step = 'upsell' ) {
	$page_id = absint( $page_id );
	if ( $page_id <= 0 || ! $order instanceof WC_Order ) {
		return '';
	}

	$url = get_permalink( $page_id );
	if ( ! $url ) {
		return '';
	}

	return add_query_arg(
		array(
			'snf_order' => $order->get_id(),
			'snf_key'   => $order->get_order_key(),
			'snf_step'  => sanitize_key( $step ),
		),
		$url
	);
}

/**
 * Validate order + key from request.
 *
 * @param int    $order_id Order ID.
 * @param string $key      Order key.
 * @return WC_Order|null
 */
function snf_funnel_validate_order( $order_id, $key ) {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return null;
	}

	$order = wc_get_order( absint( $order_id ) );
	if ( ! $order instanceof WC_Order ) {
		return null;
	}

	if ( ! hash_equals( (string) $order->get_order_key(), (string) $key ) ) {
		return null;
	}

	if ( $order->has_status( array( 'failed', 'cancelled', 'refunded' ) ) ) {
		return null;
	}

	return $order;
}

/**
 * Current funnel context from query args (when viewing an offer page).
 *
 * @return array{order:WC_Order,step:string,trigger:array}|null
 */
function snf_funnel_current_context() {
	$order_id = isset( $_GET['snf_order'] ) ? absint( wp_unslash( $_GET['snf_order'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$key      = isset( $_GET['snf_key'] ) ? sanitize_text_field( wp_unslash( $_GET['snf_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$step     = isset( $_GET['snf_step'] ) ? sanitize_key( wp_unslash( $_GET['snf_step'] ) ) : 'upsell'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! in_array( $step, array( 'upsell', 'downsell' ), true ) ) {
		$step = 'upsell';
	}

	$order = snf_funnel_validate_order( $order_id, $key );
	if ( ! $order ) {
		return null;
	}

	$trigger = snf_funnel_resolve_for_order( $order );
	if ( ! $trigger ) {
		// Still allow actions if funnel was started (config present on meta).
		$stored_product = (int) $order->get_meta( '_snf_funnel_trigger_product' );
		if ( $stored_product > 0 ) {
			$config = snf_get_funnel_config( $stored_product );
			if ( snf_funnel_config_is_runnable( $config ) || ! empty( $config['enabled'] ) ) {
				$trigger = array(
					'product_id' => $stored_product,
					'config'     => $config,
				);
			}
		}
	}

	if ( ! $trigger ) {
		return null;
	}

	return array(
		'order'   => $order,
		'step'    => $step,
		'trigger' => $trigger,
	);
}

/**
 * Offer product ID for a step.
 *
 * @param array<string, mixed> $config Config.
 * @param string               $step   Step.
 * @return int
 */
function snf_funnel_offer_product_id( array $config, $step ) {
	return 'downsell' === $step
		? absint( $config['downsell_product_id'] ?? 0 )
		: absint( $config['upsell_product_id'] ?? 0 );
}

/**
 * Offer page ID for a step.
 *
 * @param array<string, mixed> $config Config.
 * @param string               $step   Step.
 * @return int
 */
function snf_funnel_offer_page_id( array $config, $step ) {
	return 'downsell' === $step
		? absint( $config['downsell_page_id'] ?? 0 )
		: absint( $config['upsell_page_id'] ?? 0 );
}

/**
 * Mark funnel complete and return thank-you URL.
 *
 * @param WC_Order $order Order.
 * @return string
 */
function snf_funnel_finish_url( $order ) {
	$order->update_meta_data( '_snf_funnel_done', 1 );
	$order->update_meta_data( '_snf_funnel_step', 'done' );
	$order->save();

	return $order->get_checkout_order_received_url();
}

/**
 * After successful payment, redirect to the first upsell when configured.
 */
function snf_funnel_maybe_redirect_after_purchase() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['snf_funnel_skip'] ) ) {
		return;
	}

	global $wp;
	$order_id = isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0;
	if ( $order_id <= 0 ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
	if ( $key && ! hash_equals( (string) $order->get_order_key(), $key ) ) {
		return;
	}

	if ( $order->get_meta( '_snf_funnel_done' ) || $order->get_meta( '_snf_funnel_started' ) ) {
		return;
	}

	if ( $order->get_meta( '_snf_funnel_parent_order' ) ) {
		return;
	}

	$trigger = snf_funnel_resolve_for_order( $order );
	if ( ! $trigger ) {
		return;
	}

	$config = $trigger['config'];
	$url    = snf_funnel_offer_url( (int) $config['upsell_page_id'], $order, 'upsell' );
	if ( ! $url ) {
		return;
	}

	$order->update_meta_data( '_snf_funnel_started', 1 );
	$order->update_meta_data( '_snf_funnel_trigger_product', (int) $trigger['product_id'] );
	$order->update_meta_data( '_snf_funnel_step', 'upsell' );
	$order->save();

	wp_safe_redirect( $url );
	exit;
}
add_action( 'template_redirect', 'snf_funnel_maybe_redirect_after_purchase', 20 );

/**
 * Handle accept / decline actions.
 */
function snf_funnel_handle_actions() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$action = isset( $_REQUEST['snf_funnel_action'] ) ? sanitize_key( wp_unslash( $_REQUEST['snf_funnel_action'] ) ) : '';
	if ( ! in_array( $action, array( 'accept', 'decline' ), true ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$order_id = isset( $_REQUEST['snf_order'] ) ? absint( wp_unslash( $_REQUEST['snf_order'] ) ) : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$key = isset( $_REQUEST['snf_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['snf_key'] ) ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$step = isset( $_REQUEST['snf_step'] ) ? sanitize_key( wp_unslash( $_REQUEST['snf_step'] ) ) : 'upsell';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'snf_funnel_' . $action ) ) {
		wc_add_notice( __( 'This funnel link is invalid or expired.', 'super-ninety-four' ), 'error' );
		wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
		exit;
	}

	$order = snf_funnel_validate_order( $order_id, $key );
	if ( ! $order ) {
		wc_add_notice( __( 'We could not verify that order for this offer.', 'super-ninety-four' ), 'error' );
		wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
		exit;
	}

	$product_id = (int) $order->get_meta( '_snf_funnel_trigger_product' );
	$config     = $product_id ? snf_get_funnel_config( $product_id ) : snf_funnel_empty_config();

	if ( ! $product_id ) {
		$trigger = snf_funnel_resolve_for_order( $order );
		if ( $trigger ) {
			$product_id = (int) $trigger['product_id'];
			$config     = $trigger['config'];
		}
	}

	if ( 'decline' === $action ) {
		if ( 'upsell' === $step && ! empty( $config['downsell_page_id'] ) && ! empty( $config['downsell_product_id'] ) ) {
			$order->update_meta_data( '_snf_funnel_step', 'downsell' );
			$order->save();
			$url = snf_funnel_offer_url( (int) $config['downsell_page_id'], $order, 'downsell' );
			if ( $url ) {
				wp_safe_redirect( $url );
				exit;
			}
		}

		wp_safe_redirect( snf_funnel_finish_url( $order ) );
		exit;
	}

	// Accept.
	$offer_id = snf_funnel_offer_product_id( $config, $step );
	if ( $offer_id <= 0 || 'publish' !== get_post_status( $offer_id ) ) {
		wp_safe_redirect( snf_funnel_finish_url( $order ) );
		exit;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_safe_redirect( snf_funnel_finish_url( $order ) );
		exit;
	}

	WC()->cart->empty_cart();
	$added = WC()->cart->add_to_cart(
		$offer_id,
		1,
		0,
		array(),
		array(
			'snf_funnel_parent_order' => $order->get_id(),
			'snf_funnel_step'         => $step,
		)
	);

	if ( ! $added ) {
		wc_add_notice( __( 'That offer could not be added to the cart.', 'super-ninety-four' ), 'error' );
		wp_safe_redirect( snf_funnel_finish_url( $order ) );
		exit;
	}

	$order->update_meta_data( '_snf_funnel_step', $step . '_accepted' );
	$order->save();

	wp_safe_redirect( wc_get_checkout_url() );
	exit;
}
add_action( 'template_redirect', 'snf_funnel_handle_actions', 5 );

/**
 * Stamp funnel meta when the order object is created from cart.
 *
 * @param WC_Order $order Order being created.
 */
function snf_funnel_stamp_followup_order_from_cart( $order ) {
	if ( ! $order instanceof WC_Order || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	foreach ( WC()->cart->get_cart() as $item ) {
		if ( empty( $item['snf_funnel_parent_order'] ) ) {
			continue;
		}

		$parent = absint( $item['snf_funnel_parent_order'] );
		$step   = isset( $item['snf_funnel_step'] ) ? sanitize_key( $item['snf_funnel_step'] ) : '';

		$order->update_meta_data( '_snf_funnel_parent_order', $parent );
		$order->update_meta_data( '_snf_funnel_from_step', $step );
		$order->update_meta_data( '_snf_funnel_done', 1 );

		$parent_order = wc_get_order( $parent );
		if ( $parent_order instanceof WC_Order ) {
			$parent_order->update_meta_data( '_snf_funnel_done', 1 );
			$parent_order->update_meta_data( '_snf_funnel_step', 'done' );
			$parent_order->add_order_note(
				sprintf(
					/* translators: 1: step slug */
					__( 'Buyer accepted funnel %s offer; follow-up checkout started.', 'super-ninety-four' ),
					$step ? $step : 'offer'
				)
			);
			$parent_order->save();
		}
		return;
	}
}
add_action( 'woocommerce_checkout_create_order', 'snf_funnel_stamp_followup_order_from_cart', 20, 1 );

/**
 * Copy funnel cart item data onto order line items.
 *
 * @param WC_Order_Item_Product $item          Item.
 * @param string                $cart_item_key Cart key.
 * @param array                 $values        Cart values.
 * @param WC_Order              $order         Order.
 */
function snf_funnel_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
	if ( ! empty( $values['snf_funnel_parent_order'] ) ) {
		$item->add_meta_data( '_snf_funnel_parent_order', absint( $values['snf_funnel_parent_order'] ), true );
	}
	if ( ! empty( $values['snf_funnel_step'] ) ) {
		$item->add_meta_data( '_snf_funnel_step', sanitize_key( $values['snf_funnel_step'] ), true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'snf_funnel_checkout_create_order_line_item', 20, 4 );

/**
 * After a follow-up order is paid, finish the parent funnel (no nested OTOs).
 *
 * @param int $order_id Order ID.
 */
function snf_funnel_complete_parent_on_followup( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$parent_id = (int) $order->get_meta( '_snf_funnel_parent_order' );
	if ( $parent_id <= 0 ) {
		return;
	}

	$parent = wc_get_order( $parent_id );
	if ( ! $parent instanceof WC_Order ) {
		return;
	}

	$parent->update_meta_data( '_snf_funnel_done', 1 );
	$parent->update_meta_data( '_snf_funnel_step', 'done' );
	$parent->save();
}
add_action( 'woocommerce_thankyou', 'snf_funnel_complete_parent_on_followup', 5 );

/**
 * Accept / decline button markup for offer pages.
 *
 * @param array<string, string> $atts Shortcode atts.
 * @return string
 */
function snf_funnel_actions_shortcode( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'accept_label'  => __( 'Yes — add this to my order', 'super-ninety-four' ),
			'decline_label' => __( 'No thanks', 'super-ninety-four' ),
		),
		$atts,
		'snf_funnel_actions'
	);

	$ctx = snf_funnel_current_context();
	if ( ! $ctx ) {
		return '<p class="snf-funnel-actions snf-funnel-actions--invalid">' . esc_html__( 'This offer link is missing or expired.', 'super-ninety-four' ) . '</p>';
	}

	/** @var WC_Order $order */
	$order  = $ctx['order'];
	$step   = $ctx['step'];
	$config = $ctx['trigger']['config'];

	$accept_url = wp_nonce_url(
		add_query_arg(
			array(
				'snf_funnel_action' => 'accept',
				'snf_order'         => $order->get_id(),
				'snf_key'           => $order->get_order_key(),
				'snf_step'          => $step,
			),
			home_url( '/' )
		),
		'snf_funnel_accept'
	);

	$decline_url = wp_nonce_url(
		add_query_arg(
			array(
				'snf_funnel_action' => 'decline',
				'snf_order'         => $order->get_id(),
				'snf_key'           => $order->get_order_key(),
				'snf_step'          => $step,
			),
			home_url( '/' )
		),
		'snf_funnel_decline'
	);

	$offer_id = snf_funnel_offer_product_id( $config, $step );
	$price    = '';
	if ( $offer_id && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $offer_id );
		if ( $product ) {
			$price = wp_strip_all_tags( wc_price( (float) $product->get_price() ) );
		}
	}

	ob_start();
	?>
	<div class="snf-funnel-actions" data-step="<?php echo esc_attr( $step ); ?>">
		<?php if ( $price ) : ?>
			<p class="snf-funnel-actions__price"><?php echo esc_html( $price ); ?></p>
		<?php endif; ?>
		<a class="button snf-funnel-actions__accept" href="<?php echo esc_url( $accept_url ); ?>">
			<?php echo esc_html( $atts['accept_label'] ); ?>
		</a>
		<a class="snf-funnel-actions__decline" href="<?php echo esc_url( $decline_url ); ?>">
			<?php echo esc_html( $atts['decline_label'] ); ?>
		</a>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'snf_funnel_actions', 'snf_funnel_actions_shortcode' );

/**
 * WooCommerce product data panel for funnels.
 */
function snf_funnel_product_panel() {
	global $post;
	if ( ! $post ) {
		return;
	}

	$config = snf_get_funnel_config( (int) $post->ID );
	echo '<div id="snf_funnel_data" class="panel woocommerce_options_panel hidden">';
	echo '<div class="options_group">';

	woocommerce_wp_checkbox(
		array(
			'id'          => 'snf_funnel_enabled',
			'label'       => __( 'Enable post-purchase funnel', 'super-ninety-four' ),
			'description' => __( 'After checkout, send buyers to an upsell page (then optional downsell) built with Blank Canvas or Funnel Offer templates.', 'super-ninety-four' ),
			'value'       => ! empty( $config['enabled'] ) ? 'yes' : 'no',
		)
	);

	woocommerce_wp_text_input(
		array(
			'id'          => 'snf_funnel_upsell_page_id',
			'label'       => __( 'Upsell page ID', 'super-ninety-four' ),
			'description' => __( 'Page using Funnel Offer or Blank Canvas. Insert the Funnel Upsell pattern + [snf_funnel_actions].', 'super-ninety-four' ),
			'desc_tip'    => true,
			'type'        => 'number',
			'value'       => (string) (int) $config['upsell_page_id'],
		)
	);

	woocommerce_wp_text_input(
		array(
			'id'          => 'snf_funnel_upsell_product_id',
			'label'       => __( 'Upsell product ID', 'super-ninety-four' ),
			'description' => __( 'Woo product added to cart when they accept the upsell.', 'super-ninety-four' ),
			'desc_tip'    => true,
			'type'        => 'number',
			'value'       => (string) (int) $config['upsell_product_id'],
		)
	);

	woocommerce_wp_text_input(
		array(
			'id'          => 'snf_funnel_downsell_page_id',
			'label'       => __( 'Downsell page ID', 'super-ninety-four' ),
			'description' => __( 'Shown when they decline the upsell. Optional.', 'super-ninety-four' ),
			'desc_tip'    => true,
			'type'        => 'number',
			'value'       => (string) (int) $config['downsell_page_id'],
		)
	);

	woocommerce_wp_text_input(
		array(
			'id'          => 'snf_funnel_downsell_product_id',
			'label'       => __( 'Downsell product ID', 'super-ninety-four' ),
			'description' => __( 'Woo product for the downsell accept path. Optional.', 'super-ninety-four' ),
			'desc_tip'    => true,
			'type'        => 'number',
			'value'       => (string) (int) $config['downsell_product_id'],
		)
	);

	echo '</div></div>';
}
add_action( 'woocommerce_product_data_panels', 'snf_funnel_product_panel' );

/**
 * Funnel tab on product data.
 *
 * @param array<string, array<string, mixed>> $tabs Tabs.
 * @return array<string, array<string, mixed>>
 */
function snf_funnel_product_data_tab( $tabs ) {
	$tabs['snf_funnel'] = array(
		'label'    => __( 'SNF Funnel', 'super-ninety-four' ),
		'target'   => 'snf_funnel_data',
		'class'    => array(),
		'priority' => 68,
	);
	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'snf_funnel_product_data_tab' );

/**
 * Save funnel product fields.
 *
 * @param int $product_id Product ID.
 */
function snf_funnel_save_product_fields( $product_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce product save nonce.
	$config = array(
		'enabled'             => ! empty( $_POST['snf_funnel_enabled'] ),
		'upsell_page_id'      => isset( $_POST['snf_funnel_upsell_page_id'] ) ? absint( wp_unslash( $_POST['snf_funnel_upsell_page_id'] ) ) : 0,
		'upsell_product_id'   => isset( $_POST['snf_funnel_upsell_product_id'] ) ? absint( wp_unslash( $_POST['snf_funnel_upsell_product_id'] ) ) : 0,
		'downsell_page_id'    => isset( $_POST['snf_funnel_downsell_page_id'] ) ? absint( wp_unslash( $_POST['snf_funnel_downsell_page_id'] ) ) : 0,
		'downsell_product_id' => isset( $_POST['snf_funnel_downsell_product_id'] ) ? absint( wp_unslash( $_POST['snf_funnel_downsell_product_id'] ) ) : 0,
	);

	update_post_meta( $product_id, snf_funnel_meta_key(), wp_json_encode( snf_funnel_sanitize_config( $config ) ) );
}
add_action( 'woocommerce_process_product_meta', 'snf_funnel_save_product_fields', 30 );

/**
 * Collect published funnel offer page IDs for access control.
 *
 * @return int[]
 */
function snf_funnel_public_page_ids() {
	$ids = array();

	$products = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => snf_funnel_meta_key(),
					'compare' => 'EXISTS',
				),
			),
		)
	);

	foreach ( $products as $product_id ) {
		$config = snf_get_funnel_config( (int) $product_id );
		if ( empty( $config['enabled'] ) ) {
			continue;
		}
		foreach ( array( 'upsell_page_id', 'downsell_page_id' ) as $key ) {
			$id = absint( $config[ $key ] ?? 0 );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
	}

	// Any page using the funnel-offer template.
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => 'funnel-offer', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	return array_values( array_unique( array_merge( $ids, array_map( 'intval', $pages ) ) ) );
}

/**
 * Keep funnel offer pages public.
 *
 * @param string[] $slugs Protected slugs.
 * @return string[]
 */
function snf_funnel_filter_protected_slugs( $slugs ) {
	$remove = array();
	foreach ( snf_funnel_public_page_ids() as $page_id ) {
		$slug = get_post_field( 'post_name', $page_id );
		if ( $slug ) {
			$remove[] = $slug;
		}
	}
	return array_values( array_diff( (array) $slugs, $remove ) );
}
add_filter( 'snf_protected_page_slugs', 'snf_funnel_filter_protected_slugs' );

/**
 * Expose funnel offer page slugs as public.
 *
 * @param string[] $slugs Public slugs.
 * @return string[]
 */
function snf_funnel_filter_public_slugs( $slugs ) {
	$extra = array();
	foreach ( snf_funnel_public_page_ids() as $page_id ) {
		$slug = get_post_field( 'post_name', $page_id );
		if ( $slug ) {
			$extra[] = $slug;
		}
	}
	return array_values( array_unique( array_merge( (array) $slugs, $extra ) ) );
}
add_filter( 'snf_public_page_slugs', 'snf_funnel_filter_public_slugs' );
add_filter( 'snf_marketing_host_page_slugs', 'snf_funnel_filter_public_slugs' );

/**
 * Whether the current view is a funnel offer / blank-canvas content page.
 *
 * @return bool
 */
function snf_is_funnel_offer_page() {
	if ( function_exists( 'snf_is_page_template' ) && snf_is_page_template( array( 'page-templates/funnel-offer.php', 'funnel-offer' ) ) ) {
		return true;
	}
	return (bool) snf_funnel_current_context();
}

/**
 * Funnel pattern category is registered in editor.php (snf-funnel).
 */
