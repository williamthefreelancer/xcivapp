<?php
/**
 * Custom SNF checkout fields (Apply / event / workshop).
 *
 * @package SNF_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkout field registration, validation, and order meta.
 */
class SNF_Payments_Checkout_Fields {

	/**
	 * Session key for Apply form stash.
	 */
	const SESSION_APPLY = 'snf_apply_checkout_fields';

	/**
	 * Hook into WooCommerce.
	 */
	public static function init() {
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'register_fields' ), 20 );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'sync_billing_from_snf' ), 5 );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_fields' ), 10 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_to_order' ), 20, 2 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_display' ) );
		add_filter( 'woocommerce_checkout_get_value', array( __CLASS__, 'prefill_value' ), 20, 2 );
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'after_order_meta' ), 20, 1 );
	}

	/**
	 * Copy SNF field POST into Woo billing keys before Woo validates.
	 */
	public static function sync_billing_from_snf() {
		$name  = isset( $_POST['snf_apply_name'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$email = isset( $_POST['snf_apply_email'] ) ? sanitize_email( wp_unslash( $_POST['snf_apply_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$phone = isset( $_POST['snf_apply_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$zip   = isset( $_POST['snf_apply_billing_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_billing_zip'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$city  = isset( $_POST['snf_apply_city'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_city'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$org   = isset( $_POST['snf_apply_org'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_org'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( $name ) {
			$parts = preg_split( '/\s+/', $name, 2 );
			$_POST['billing_first_name'] = $parts[0] ?? $name;
			$_POST['billing_last_name']  = $parts[1] ?? '-';
		}
		if ( $email ) {
			$_POST['billing_email'] = $email;
		}
		if ( $phone ) {
			$_POST['billing_phone'] = $phone;
		}
		if ( $zip ) {
			$_POST['billing_postcode'] = $zip;
		}
		if ( $city ) {
			$_POST['billing_city'] = $city;
		}
		if ( $org ) {
			$_POST['billing_company'] = $org;
		}
		if ( empty( $_POST['billing_country'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$_POST['billing_country'] = WC()->countries->get_base_country();
		}
	}

	/**
	 * Detect checkout context from cart.
	 *
	 * @return string membership|event|workshop|generic
	 */
	public static function cart_context() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return 'generic';
		}

		foreach ( WC()->cart->get_cart() as $item ) {
			if ( ! empty( $item['snf_membership_plan'] ) || ! empty( $item['snf_is_membership'] ) ) {
				return 'membership';
			}
			if ( isset( $item['data'] ) && is_object( $item['data'] ) && method_exists( $item['data'], 'get_id' ) ) {
				$product = $item['data'];
				if ( $product->get_meta( '_snf_membership_plan' ) || $product->get_meta( '_snf_is_initiation' ) || 'application' === $product->get_meta( '_snf_membership_plan' ) ) {
					return 'membership';
				}
				$cats = wc_get_product_term_ids( $product->get_id(), 'product_cat' );
				foreach ( $cats as $term_id ) {
					$term = get_term( $term_id, 'product_cat' );
					if ( $term && ! is_wp_error( $term ) && 'memberships' === $term->slug ) {
						return 'membership';
					}
				}
			}
			if ( ! empty( $item['snf_workshop_email'] ) || ! empty( $item['snf_workshop_name'] ) ) {
				return 'workshop';
			}
			if ( ! empty( $item['snf_ticket_tier'] ) || ! empty( $item['snf_ticket_commerce'] ) ) {
				return 'event';
			}
		}

		$session = self::session_apply_fields();
		if ( ! empty( $session['snf_apply_plan'] ) ) {
			return 'membership';
		}

		return 'generic';
	}

	/**
	 * Apply fields from WC session.
	 *
	 * @return array<string, string>
	 */
	public static function session_apply_fields() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return array();
		}
		$data = WC()->session->get( self::SESSION_APPLY );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Stash Apply POST into session (called from theme).
	 *
	 * @param array<string, mixed> $fields Fields.
	 */
	public static function stash_apply_fields( array $fields ) {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		if ( function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
		if ( ! WC()->session ) {
			return;
		}

		$clean = array();
		$map   = array(
			'snf_apply_name',
			'snf_apply_email',
			'snf_apply_phone',
			'snf_apply_org',
			'snf_apply_city',
			'snf_apply_referral',
			'snf_apply_plan',
			'snf_apply_card_name',
			'snf_apply_billing_zip',
			'snf_apply_billing_notes',
			'snf_apply_event',
		);
		foreach ( $map as $key ) {
			if ( ! isset( $fields[ $key ] ) ) {
				continue;
			}
			if ( 'snf_apply_email' === $key ) {
				$clean[ $key ] = sanitize_email( (string) $fields[ $key ] );
			} elseif ( 'snf_apply_billing_notes' === $key ) {
				$clean[ $key ] = sanitize_textarea_field( (string) $fields[ $key ] );
			} elseif ( 'snf_apply_event' === $key ) {
				$clean[ $key ] = (string) absint( $fields[ $key ] );
			} else {
				$clean[ $key ] = sanitize_text_field( (string) $fields[ $key ] );
			}
		}

		WC()->session->set( self::SESSION_APPLY, $clean );

		if ( ! empty( $clean['snf_apply_email'] ) && WC()->customer ) {
			WC()->customer->set_billing_email( $clean['snf_apply_email'] );
		}
		if ( ! empty( $clean['snf_apply_phone'] ) && WC()->customer ) {
			WC()->customer->set_billing_phone( $clean['snf_apply_phone'] );
		}
		if ( ! empty( $clean['snf_apply_name'] ) && WC()->customer ) {
			$parts = preg_split( '/\s+/', $clean['snf_apply_name'], 2 );
			WC()->customer->set_billing_first_name( $parts[0] ?? $clean['snf_apply_name'] );
			WC()->customer->set_billing_last_name( $parts[1] ?? '' );
		}
		if ( ! empty( $clean['snf_apply_billing_zip'] ) && WC()->customer ) {
			WC()->customer->set_billing_postcode( $clean['snf_apply_billing_zip'] );
		}
		if ( ! empty( $clean['snf_apply_city'] ) && WC()->customer ) {
			WC()->customer->set_billing_city( $clean['snf_apply_city'] );
		}
	}

	/**
	 * Register custom checkout fields.
	 *
	 * @param array<string, array<string, mixed>> $fields Fields.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_fields( $fields ) {
		$context = self::cart_context();

		if ( ! isset( $fields['billing'] ) ) {
			$fields['billing'] = array();
		}
		if ( ! isset( $fields['order'] ) ) {
			$fields['order'] = array();
		}

		// Map SNF names onto billing where Woo expects them; keep snf_* mirrors for UI.
		$fields['snf'] = array();

		$fields['snf']['snf_apply_name'] = array(
			'type'         => 'text',
			'label'        => __( 'Full name', 'snf-payments' ),
			'required'     => true,
			'class'        => array( 'form-row-wide', 'snf-checkout-field' ),
			'autocomplete' => 'name',
			'priority'     => 10,
		);
		$fields['snf']['snf_apply_email'] = array(
			'type'         => 'email',
			'label'        => __( 'Email', 'snf-payments' ),
			'required'     => true,
			'class'        => array( 'form-row-wide', 'snf-checkout-field' ),
			'autocomplete' => 'email',
			'priority'     => 20,
		);
		$fields['snf']['snf_apply_phone'] = array(
			'type'         => 'tel',
			'label'        => __( 'Phone', 'snf-payments' ),
			'required'     => true,
			'class'        => array( 'form-row-wide', 'snf-checkout-field' ),
			'autocomplete' => 'tel',
			'priority'     => 30,
		);

		if ( in_array( $context, array( 'membership', 'generic' ), true ) ) {
			$fields['snf']['snf_apply_org'] = array(
				'type'         => 'text',
				'label'        => __( 'Organization / firm', 'snf-payments' ),
				'required'     => false,
				'class'        => array( 'form-row-wide', 'snf-checkout-field' ),
				'autocomplete' => 'organization',
				'priority'     => 40,
			);
			$fields['snf']['snf_apply_city'] = array(
				'type'         => 'text',
				'label'        => __( 'City / State', 'snf-payments' ),
				'required'     => ( 'membership' === $context ),
				'class'        => array( 'form-row-wide', 'snf-checkout-field' ),
				'autocomplete' => 'address-level2',
				'priority'     => 50,
			);
			$fields['snf']['snf_apply_referral'] = array(
				'type'     => 'text',
				'label'    => __( 'How did you hear about us?', 'snf-payments' ),
				'required' => false,
				'class'    => array( 'form-row-wide', 'snf-checkout-field' ),
				'priority' => 60,
			);
			$fields['snf']['snf_apply_plan'] = array(
				'type'     => 'text',
				'label'    => __( 'Preferred membership plan', 'snf-payments' ),
				'required' => false,
				'class'    => array( 'form-row-wide', 'snf-checkout-field', 'snf-checkout-field--plan' ),
				'priority' => 70,
			);
		}

		$fields['snf']['snf_apply_card_name'] = array(
			'type'         => 'text',
			'label'        => __( 'Cardholder name', 'snf-payments' ),
			'required'     => true,
			'class'        => array( 'form-row-wide', 'snf-checkout-field' ),
			'autocomplete' => 'cc-name',
			'priority'     => 80,
		);
		$fields['snf']['snf_apply_billing_zip'] = array(
			'type'         => 'text',
			'label'        => __( 'Billing ZIP / postal', 'snf-payments' ),
			'required'     => true,
			'class'        => array( 'form-row-wide', 'snf-checkout-field' ),
			'autocomplete' => 'postal-code',
			'priority'     => 90,
		);
		$fields['snf']['snf_apply_billing_notes'] = array(
			'type'     => 'textarea',
			'label'    => __( 'Billing notes', 'snf-payments' ),
			'required' => false,
			'class'    => array( 'form-row-wide', 'snf-checkout-field' ),
			'priority' => 100,
		);

		// Soften default Woo address requirements; SNF template collects essentials.
		foreach ( array( 'billing_address_1', 'billing_city', 'billing_state', 'billing_postcode', 'billing_country' ) as $key ) {
			if ( isset( $fields['billing'][ $key ] ) ) {
				$fields['billing'][ $key ]['required'] = false;
				$fields['billing'][ $key ]['class'][]  = 'snf-checkout-woo-hidden';
			}
		}

		return $fields;
	}

	/**
	 * Prefill from session / customer / cart.
	 *
	 * @param mixed  $value Value.
	 * @param string $input Input key.
	 * @return mixed
	 */
	public static function prefill_value( $value, $input ) {
		if ( '' !== $value && null !== $value ) {
			return $value;
		}

		$session = self::session_apply_fields();
		if ( isset( $session[ $input ] ) && '' !== $session[ $input ] ) {
			return $session[ $input ];
		}

		// Map billing_* from session.
		$map = array(
			'billing_email'     => 'snf_apply_email',
			'billing_phone'     => 'snf_apply_phone',
			'billing_postcode'  => 'snf_apply_billing_zip',
			'billing_city'      => 'snf_apply_city',
			'billing_company'   => 'snf_apply_org',
		);
		if ( isset( $map[ $input ] ) && ! empty( $session[ $map[ $input ] ] ) ) {
			return $session[ $map[ $input ] ];
		}
		if ( 'billing_first_name' === $input && ! empty( $session['snf_apply_name'] ) ) {
			$parts = preg_split( '/\s+/', $session['snf_apply_name'], 2 );
			return $parts[0] ?? $session['snf_apply_name'];
		}
		if ( 'billing_last_name' === $input && ! empty( $session['snf_apply_name'] ) ) {
			$parts = preg_split( '/\s+/', $session['snf_apply_name'], 2 );
			return $parts[1] ?? '';
		}

		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				$workshop_map = array(
					'snf_apply_name'  => 'snf_workshop_name',
					'snf_apply_email' => 'snf_workshop_email',
					'snf_apply_phone' => 'snf_workshop_phone',
				);
				if ( isset( $workshop_map[ $input ] ) && ! empty( $item[ $workshop_map[ $input ] ] ) ) {
					return $item[ $workshop_map[ $input ] ];
				}
				if ( 'snf_apply_plan' === $input && ! empty( $item['snf_membership_plan'] ) ) {
					return $item['snf_membership_plan'];
				}
			}
		}

		return $value;
	}

	/**
	 * Validate custom fields on checkout submit.
	 */
	public static function validate_fields() {
		$context = self::cart_context();
		$required = array(
			'snf_apply_name'         => __( 'Full name is required.', 'snf-payments' ),
			'snf_apply_email'        => __( 'A valid email is required.', 'snf-payments' ),
			'snf_apply_phone'        => __( 'Phone is required.', 'snf-payments' ),
			'snf_apply_card_name'    => __( 'Cardholder name is required.', 'snf-payments' ),
			'snf_apply_billing_zip'  => __( 'Billing ZIP / postal is required.', 'snf-payments' ),
		);

		if ( 'membership' === $context ) {
			$required['snf_apply_city'] = __( 'City / State is required.', 'snf-payments' );
		}

		foreach ( $required as $key => $message ) {
			$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( 'snf_apply_email' === $key ) {
				$email = sanitize_email( (string) $raw );
				if ( ! $email || ! is_email( $email ) ) {
					wc_add_notice( $message, 'error' );
				}
				continue;
			}
			if ( '' === trim( sanitize_text_field( (string) $raw ) ) ) {
				wc_add_notice( $message, 'error' );
			}
		}
	}

	/**
	 * Save fields onto the order while creating it.
	 *
	 * @param WC_Order             $order Order.
	 * @param array<string, mixed> $data  Posted data.
	 */
	public static function save_to_order( $order, $data ) {
		$keys = array(
			'snf_apply_name',
			'snf_apply_email',
			'snf_apply_phone',
			'snf_apply_org',
			'snf_apply_city',
			'snf_apply_referral',
			'snf_apply_plan',
			'snf_apply_card_name',
			'snf_apply_billing_zip',
			'snf_apply_billing_notes',
		);

		foreach ( $keys as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}
			$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( 'snf_apply_email' === $key ) {
				$value = sanitize_email( (string) $raw );
			} elseif ( 'snf_apply_billing_notes' === $key ) {
				$value = sanitize_textarea_field( (string) $raw );
			} else {
				$value = sanitize_text_field( (string) $raw );
			}
			if ( '' === $value ) {
				continue;
			}
			$order->update_meta_data( '_' . $key, $value );
		}

		$name  = isset( $_POST['snf_apply_name'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$email = isset( $_POST['snf_apply_email'] ) ? sanitize_email( wp_unslash( $_POST['snf_apply_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$phone = isset( $_POST['snf_apply_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$zip   = isset( $_POST['snf_apply_billing_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_billing_zip'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$city  = isset( $_POST['snf_apply_city'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_city'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$org   = isset( $_POST['snf_apply_org'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_org'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( $name ) {
			$parts = preg_split( '/\s+/', $name, 2 );
			$order->set_billing_first_name( $parts[0] ?? $name );
			$order->set_billing_last_name( $parts[1] ?? '' );
		}
		if ( $email ) {
			$order->set_billing_email( $email );
		}
		if ( $phone ) {
			$order->set_billing_phone( $phone );
		}
		if ( $zip ) {
			$order->set_billing_postcode( $zip );
		}
		if ( $city ) {
			$order->set_billing_city( $city );
		}
		if ( $org ) {
			$order->set_billing_company( $org );
		}

		$card_name = isset( $_POST['snf_apply_card_name'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_apply_card_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $card_name ) {
			$order->update_meta_data( '_snf_cardholder_name', $card_name );
		}

		$session = self::session_apply_fields();
		if ( ! empty( $session['snf_apply_event'] ) ) {
			$order->update_meta_data( '_snf_apply_event_id', absint( $session['snf_apply_event'] ) );
		}
	}

	/**
	 * After order meta: sync pending plan to user.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function after_order_meta( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$plan = (string) $order->get_meta( '_snf_apply_plan' );
		$user = $order->get_user_id();
		if ( $plan && $user ) {
			update_user_meta( $user, 'snf_pending_membership_plan', sanitize_title( $plan ) );
		}
	}

	/**
	 * Admin order screen display.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function admin_display( $order ) {
		$rows = array(
			'_snf_apply_name'          => __( 'Applicant name', 'snf-payments' ),
			'_snf_apply_org'           => __( 'Organization', 'snf-payments' ),
			'_snf_apply_city'          => __( 'City / State', 'snf-payments' ),
			'_snf_apply_referral'      => __( 'Referral', 'snf-payments' ),
			'_snf_apply_plan'          => __( 'Preferred plan', 'snf-payments' ),
			'_snf_cardholder_name'     => __( 'Cardholder name', 'snf-payments' ),
			'_snf_apply_billing_notes' => __( 'Billing notes', 'snf-payments' ),
			'_snf_payment_intent'      => __( 'Stripe PaymentIntent', 'snf-payments' ),
		);

		$out = '';
		foreach ( $rows as $key => $label ) {
			$val = $order->get_meta( $key );
			if ( '' === $val || null === $val ) {
				continue;
			}
			$out .= '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( (string) $val ) . '</p>';
		}
		if ( $out ) {
			echo '<div class="snf-payments-admin-meta">' . $out . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}
	}
}
