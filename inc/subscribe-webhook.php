<?php
/**
 * Stripe webhook handler for the subscribe page.
 *
 * Listens for Stripe events fired by the Buy Button on the subscribe page and
 * syncs subscribers with a Brevo mailing list.
 *
 * Requires the following constants in wp-config.php:
 *   define( 'STRIPE_SECRET_KEY',              'sk_live_...' );
 *   define( 'STRIPE_SUBSCRIBE_WEBHOOK_SECRET', 'whsec_...' );
 *   define( 'BREVO_API_KEY',                  'xkeysib-...' );
 *   define( 'BREVO_SUBSCRIBE_LIST_ID',         123 );  // integer list ID
 *
 * Webhook URL to register in Stripe:
 *   https://yoursite.com/?subscribe_stripe_webhook=1
 *
 * Events to enable in the Stripe dashboard:
 *   checkout.session.completed
 *   customer.subscription.deleted
 *   invoice.payment_failed
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/vendor/autoload.php';

add_action( 'init', 'sub_wh_maybe_handle', 1 );

function sub_wh_maybe_handle() {
	if ( empty( $_GET['subscribe_stripe_webhook'] ) ) {
		return;
	}

	$webhook_secret = defined( 'STRIPE_SUBSCRIBE_WEBHOOK_SECRET' ) ? STRIPE_SUBSCRIBE_WEBHOOK_SECRET : '';
	$sk             = defined( 'STRIPE_SECRET_KEY' )               ? STRIPE_SECRET_KEY               : '';

	if ( ! $webhook_secret || ! $sk ) {
		status_header( 500 );
		exit( 'Stripe not configured.' );
	}

	\Stripe\Stripe::setApiKey( $sk );

	$payload = @file_get_contents( 'php://input' );
	$sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

	try {
		$event = \Stripe\Webhook::constructEvent( $payload, $sig, $webhook_secret );
	} catch ( \Exception $e ) {
		status_header( 400 );
		exit( 'Webhook error: ' . $e->getMessage() );
	}

	switch ( $event->type ) {
		case 'checkout.session.completed':
			sub_wh_handle_checkout_completed( $event->data->object );
			break;

		case 'customer.subscription.deleted':
			sub_wh_handle_subscription_deleted( $event->data->object );
			break;

		case 'invoice.payment_failed':
			sub_wh_handle_payment_failed( $event->data->object );
			break;
	}

	status_header( 200 );
	exit( 'ok' );
}

// ---------------------------------------------------------------------------
// Event handlers
// ---------------------------------------------------------------------------

function sub_wh_handle_checkout_completed( $session ) {
	$email = sub_wh_email_from_session( $session );
	if ( $email ) {
		sub_wh_brevo_add( $email );
	}
}

function sub_wh_handle_subscription_deleted( $subscription ) {
	$email = sub_wh_email_from_customer_id( $subscription->customer ?? '' );
	if ( $email ) {
		sub_wh_brevo_remove( $email );
	}
}

function sub_wh_handle_payment_failed( $invoice ) {
	// Use the email on the invoice if present, otherwise look up the customer.
	$email = ! empty( $invoice->customer_email )
		? $invoice->customer_email
		: sub_wh_email_from_customer_id( $invoice->customer ?? '' );

	if ( $email ) {
		sub_wh_brevo_remove( $email );
	}
}

// ---------------------------------------------------------------------------
// Stripe helpers
// ---------------------------------------------------------------------------

function sub_wh_email_from_session( $session ): string {
	if ( ! empty( $session->customer_details->email ) ) {
		return sanitize_email( $session->customer_details->email );
	}
	if ( ! empty( $session->customer_email ) ) {
		return sanitize_email( $session->customer_email );
	}
	// Fall back to looking up the customer object
	return sub_wh_email_from_customer_id( $session->customer ?? '' );
}

function sub_wh_email_from_customer_id( string $customer_id ): string {
	if ( ! $customer_id ) {
		return '';
	}
	try {
		$customer = \Stripe\Customer::retrieve( $customer_id );
		return sanitize_email( $customer->email ?? '' );
	} catch ( \Exception $e ) {
		return '';
	}
}

// ---------------------------------------------------------------------------
// Brevo helpers
// ---------------------------------------------------------------------------

function sub_wh_brevo_add( string $email ): void {
	$list_id = defined( 'BREVO_SUBSCRIBE_LIST_ID' ) ? (int) BREVO_SUBSCRIBE_LIST_ID : 0;
	$api_key = defined( 'BREVO_API_KEY' )           ? BREVO_API_KEY                 : '';

	if ( ! $list_id || ! $api_key || ! is_email( $email ) ) {
		return;
	}

	// Upsert the contact, creating it if it doesn't exist yet.
	$headers = [
		'api-key'      => $api_key,
		'Content-Type' => 'application/json',
		'Accept'       => 'application/json',
	];

	wp_remote_post(
		'https://api.brevo.com/v3/contacts',
		[
			'headers' => $headers,
			'body'    => wp_json_encode( [
				'email'          => $email,
				'listIds'        => [ $list_id ],
				'updateEnabled'  => true,
			] ),
			'timeout' => 10,
		]
	);
}

function sub_wh_brevo_remove( string $email ): void {
	$list_id = defined( 'BREVO_SUBSCRIBE_LIST_ID' ) ? (int) BREVO_SUBSCRIBE_LIST_ID : 0;
	$api_key = defined( 'BREVO_API_KEY' )           ? BREVO_API_KEY                 : '';

	if ( ! $list_id || ! $api_key || ! is_email( $email ) ) {
		return;
	}

	$headers = [
		'api-key'      => $api_key,
		'Content-Type' => 'application/json',
		'Accept'       => 'application/json',
	];

	wp_remote_post(
		"https://api.brevo.com/v3/contacts/lists/{$list_id}/contacts/remove",
		[
			'headers' => $headers,
			'body'    => wp_json_encode( [ 'emails' => [ $email ] ] ),
			'timeout' => 10,
		]
	);
}
