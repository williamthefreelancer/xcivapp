<?php
/**
 * About + Contact / Let's Connect page content and form handling.
 *
 * Figma: XCIV Social About (2176:39) and Let's Connect (2176:55).
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * About page copy (Figma sibling layers on 2176:39).
 *
 * @return array{who:array{title:string,body:string},founder:array{title:string,name:string,body:string}}
 */
function snf_get_about_content() {
	$peer = __(
		'Unfiltered Peer Calibration: Contribute to and collaborate inside a raw, high-energy environment built exclusively for hospitality groups, event organizers, and consultancies that live this lifestyle at the highest standard.',
		'super-ninety-four'
	);

	$content = array(
		'who'     => array(
			'title' => __( 'About Who We Are', 'super-ninety-four' ),
			'body'  => $peer,
		),
		'founder' => array(
			'title' => __( 'Meet the Founder', 'super-ninety-four' ),
			'name'  => __( 'William Antwine Johnson', 'super-ninety-four' ),
			'body'  => $peer,
		),
	);

	/**
	 * Filter About page content.
	 *
	 * @param array $content Content.
	 */
	return apply_filters( 'snf_about_content', $content );
}

/**
 * Let's Connect page copy + schedule options (Figma 2176:55).
 *
 * @return array{
 *   title:string,
 *   lede:string,
 *   days:array<int,array{value:string,label:string}>,
 *   times:array<int,array{value:string,label:string}>,
 *   intents:array<int,array{value:string,label:string}>
 * }
 */
function snf_get_connect_content() {
	$content = array(
		'title'   => __( 'Schedule Your Time to Talk to William', 'super-ninety-four' ),
		'lede'    => __(
			'View available times for him to talk to you. William’s schedule is sun to wed (come first, come serve) SO, ACT NOW!',
			'super-ninety-four'
		),
		'days'    => array(
			array(
				'value' => 'sunday',
				'label' => __( 'Sunday', 'super-ninety-four' ),
			),
			array(
				'value' => 'monday',
				'label' => __( 'Monday', 'super-ninety-four' ),
			),
			array(
				'value' => 'tuesday',
				'label' => __( 'Tuesday', 'super-ninety-four' ),
			),
			array(
				'value' => 'wednesday',
				'label' => __( 'Wednesday', 'super-ninety-four' ),
			),
		),
		'times'   => array(
			array(
				'value' => '09:00-11:00',
				'label' => __( '9:00 AM – 11:00 AM ET', 'super-ninety-four' ),
			),
			array(
				'value' => '11:00-13:00',
				'label' => __( '11:00 AM – 1:00 PM ET', 'super-ninety-four' ),
			),
			array(
				'value' => '13:00-15:00',
				'label' => __( '1:00 PM – 3:00 PM ET', 'super-ninety-four' ),
			),
			array(
				'value' => '15:00-17:00',
				'label' => __( '3:00 PM – 5:00 PM ET', 'super-ninety-four' ),
			),
		),
		'intents' => array(
			array(
				'value' => 'membership',
				'label' => __( 'Membership / admissions', 'super-ninety-four' ),
			),
			array(
				'value' => 'events',
				'label' => __( 'Select events / road production', 'super-ninety-four' ),
			),
			array(
				'value' => 'reciprocal',
				'label' => __( 'Reciprocal / club desk', 'super-ninety-four' ),
			),
			array(
				'value' => 'partnership',
				'label' => __( 'Partnership / consultancy', 'super-ninety-four' ),
			),
			array(
				'value' => 'other',
				'label' => __( 'Other', 'super-ninety-four' ),
			),
		),
	);

	/**
	 * Filter Let's Connect content.
	 *
	 * @param array $content Content.
	 */
	return apply_filters( 'snf_connect_content', $content );
}

/**
 * Handle Let's Connect form POST.
 */
function snf_handle_connect_request() {
	$redirect = function_exists( 'snf_get_page_url' ) ? snf_get_page_url( 'contact' ) : home_url( '/contact/' );

	if ( ! isset( $_POST['snf_connect_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snf_connect_nonce'] ) ), 'snf_connect_request' ) ) {
		wp_safe_redirect( add_query_arg( 'snf_connect', 'error', $redirect ) );
		exit;
	}

	$name    = isset( $_POST['snf_connect_name'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_connect_name'] ) ) : '';
	$email   = isset( $_POST['snf_connect_email'] ) ? sanitize_email( wp_unslash( $_POST['snf_connect_email'] ) ) : '';
	$phone   = isset( $_POST['snf_connect_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_connect_phone'] ) ) : '';
	$org     = isset( $_POST['snf_connect_org'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_connect_org'] ) ) : '';
	$day     = isset( $_POST['snf_connect_day'] ) ? sanitize_key( wp_unslash( $_POST['snf_connect_day'] ) ) : '';
	$time    = isset( $_POST['snf_connect_time'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_connect_time'] ) ) : '';
	$intent  = isset( $_POST['snf_connect_intent'] ) ? sanitize_key( wp_unslash( $_POST['snf_connect_intent'] ) ) : '';
	$message = isset( $_POST['snf_connect_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['snf_connect_message'] ) ) : '';

	if ( '' === $name || ! is_email( $email ) || '' === $phone || '' === $day || '' === $time || '' === $intent || '' === $message ) {
		wp_safe_redirect( add_query_arg( 'snf_connect', 'error', $redirect ) );
		exit;
	}

	$payload = array(
		'name'    => $name,
		'email'   => $email,
		'phone'   => $phone,
		'org'     => $org,
		'day'     => $day,
		'time'    => $time,
		'intent'  => $intent,
		'message' => $message,
		'user_id' => get_current_user_id(),
		'created' => gmdate( 'c' ),
	);

	/**
	 * Fires after a Let's Connect request is validated.
	 *
	 * @param array $payload Sanitized payload.
	 */
	do_action( 'snf_connect_request_submitted', $payload );

	$to = (string) get_option( 'admin_email' );
	/**
	 * Filter notify email for Let's Connect requests.
	 *
	 * @param string $to Email.
	 */
	$to = (string) apply_filters( 'snf_connect_notify_email', $to );

	$subject = sprintf(
		/* translators: %s: requester name */
		__( '[SUPER NINETY FOUR] Connect request from %s', 'super-ninety-four' ),
		$name
	);

	$body = implode(
		"\n",
		array(
			'Name: ' . $name,
			'Email: ' . $email,
			'Phone: ' . $phone,
			'Organization: ' . $org,
			'Day: ' . $day,
			'Time: ' . $time,
			'Intent: ' . $intent,
			'',
			'Message:',
			$message,
		)
	);

	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );
	$sent    = $to ? wp_mail( $to, $subject, $body, $headers ) : false;

	wp_safe_redirect( add_query_arg( 'snf_connect', $sent ? 'sent' : 'error', $redirect ) );
	exit;
}
add_action( 'admin_post_nopriv_snf_connect_request', 'snf_handle_connect_request' );
add_action( 'admin_post_snf_connect_request', 'snf_handle_connect_request' );
