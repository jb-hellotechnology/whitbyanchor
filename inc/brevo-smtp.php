<?php
/**
 * Send all outgoing WordPress email through Brevo.
 *
 * Preferred path: Brevo's transactional HTTP API (api.brevo.com over
 * HTTPS), short-circuiting wp_mail via the pre_wp_mail filter. This exists
 * because the live host transparently intercepts outbound SMTP and breaks
 * TLS certificate validation (peer cert CN *.gds.guru.net.uk), while HTTPS
 * passes through untouched — the subscribe webhook's Brevo API calls
 * already succeed from the same server.
 *
 * Fallback path: Brevo SMTP relay via phpmailer_init, used only when
 * BREVO_API_KEY is not defined but the SMTP constants are.
 *
 * wp-config.php constants:
 *   define( 'BREVO_API_KEY',   'xkeysib-...' );      // enables the API path (already used by the subscribe flow)
 *   define( 'BREVO_SMTP_FROM', 'news@yoursite.com' ); // a sender verified in Brevo — required for both paths
 *
 * Optional:
 *   define( 'BREVO_SMTP_FROM_NAME', 'The Whitby Anchor' ); // defaults to the site title
 *   define( 'BREVO_SMTP_LOGIN', '9xxxxx001@smtp-brevo.com' ); // SMTP fallback only
 *   define( 'BREVO_SMTP_KEY',   'xsmtpsib-...' );            // SMTP fallback only
 *
 * @package whitbyanchor
 */

defined( 'ABSPATH' ) || exit;

function wa_brevo_from_email(): string {
	return defined( 'BREVO_SMTP_FROM' ) && BREVO_SMTP_FROM ? BREVO_SMTP_FROM : '';
}

function wa_brevo_from_name(): string {
	return defined( 'BREVO_SMTP_FROM_NAME' ) && BREVO_SMTP_FROM_NAME
		? BREVO_SMTP_FROM_NAME
		: get_bloginfo( 'name' );
}

function wa_brevo_api_ready(): bool {
	return defined( 'BREVO_API_KEY' ) && BREVO_API_KEY
		&& 'YOUR_BREVO_API_KEY_HERE' !== BREVO_API_KEY
		&& wa_brevo_from_email();
}

// ---------------------------------------------------------------------------
// Preferred path: transactional HTTP API
// ---------------------------------------------------------------------------

/**
 * Short-circuit wp_mail and deliver via the Brevo API instead.
 *
 * Returns null (fall through to the default mailer) when the API is not
 * configured or the message uses features we do not map (attachments).
 */
function wa_brevo_api_send( $short_circuit, array $atts ) {
	if ( ! wa_brevo_api_ready() || ! empty( $atts['attachments'] ) ) {
		return $short_circuit;
	}

	$to = $atts['to'] ?? [];
	if ( ! is_array( $to ) ) {
		$to = explode( ',', $to );
	}
	$recipients = [];
	foreach ( $to as $address ) {
		$address = sanitize_email( trim( $address ) );
		if ( is_email( $address ) ) {
			$recipients[] = [ 'email' => $address ];
		}
	}
	if ( ! $recipients ) {
		error_log( '[brevo-smtp] API send skipped — no valid recipients.' );
		return false;
	}

	$headers = $atts['headers'] ?? '';
	$headers = is_array( $headers ) ? implode( "\n", $headers ) : (string) $headers;
	$is_html = false !== stripos( $headers, 'text/html' );

	$payload = [
		'sender'  => [
			'email' => wa_brevo_from_email(),
			'name'  => wa_brevo_from_name(),
		],
		'to'      => $recipients,
		'subject' => (string) ( $atts['subject'] ?? '' ),
	];
	$payload[ $is_html ? 'htmlContent' : 'textContent' ] = (string) ( $atts['message'] ?? '' );

	$response = wp_remote_post(
		'https://api.brevo.com/v3/smtp/email',
		[
			'headers' => [
				'api-key'      => BREVO_API_KEY,
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
			'body'    => wp_json_encode( $payload ),
			'timeout' => 15,
		]
	);

	if ( is_wp_error( $response ) ) {
		error_log( '[brevo-smtp] API send failed: ' . $response->get_error_message() );
		return false;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		error_log( '[brevo-smtp] API send rejected (' . $code . '): ' . wp_remote_retrieve_body( $response ) );
		return false;
	}

	return true;
}
add_filter( 'pre_wp_mail', 'wa_brevo_api_send', 10, 2 );

// ---------------------------------------------------------------------------
// Fallback path: SMTP relay (only without an API key; note the live host
// intercepts outbound SMTP, so this is for other environments)
// ---------------------------------------------------------------------------

function wa_brevo_smtp_ready(): bool {
	return ! wa_brevo_api_ready()
		&& defined( 'BREVO_SMTP_LOGIN' ) && BREVO_SMTP_LOGIN
		&& defined( 'BREVO_SMTP_KEY' )   && BREVO_SMTP_KEY
		&& wa_brevo_from_email();
}

function wa_brevo_smtp_configure( $phpmailer ): void {
	if ( ! wa_brevo_smtp_ready() ) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host       = 'smtp-relay.brevo.com';
	$phpmailer->Port       = 587;
	$phpmailer->SMTPAuth   = true;
	$phpmailer->SMTPSecure = 'tls';
	$phpmailer->Username   = BREVO_SMTP_LOGIN;
	$phpmailer->Password   = BREVO_SMTP_KEY;
}
add_action( 'phpmailer_init', 'wa_brevo_smtp_configure' );

/**
 * Brevo rejects mail from unverified senders, so replace WordPress's
 * default wordpress@<host> From address with the verified one.
 */
function wa_brevo_smtp_from( string $from ): string {
	return wa_brevo_smtp_ready() ? wa_brevo_from_email() : $from;
}
add_filter( 'wp_mail_from', 'wa_brevo_smtp_from' );

function wa_brevo_smtp_from_name( string $name ): string {
	return wa_brevo_smtp_ready() ? wa_brevo_from_name() : $name;
}
add_filter( 'wp_mail_from_name', 'wa_brevo_smtp_from_name' );

/**
 * Surface delivery failures in the PHP error log so silent drops
 * are diagnosable.
 */
function wa_brevo_smtp_log_failure( $error ): void {
	error_log( '[brevo-smtp] wp_mail failed: ' . $error->get_error_message() );
}
add_action( 'wp_mail_failed', 'wa_brevo_smtp_log_failure' );
