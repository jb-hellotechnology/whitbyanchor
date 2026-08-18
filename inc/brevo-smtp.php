<?php
/**
 * Route all outgoing WordPress email through Brevo SMTP.
 *
 * Covers every wp_mail() call site-wide (print-order emails, business
 * submissions, admin notices) so delivery does not depend on the web
 * server's local mailer.
 *
 * Requires the following constants in wp-config.php:
 *   define( 'BREVO_SMTP_LOGIN', '9xxxxx001@smtp-brevo.com' ); // SMTP login from Brevo > SMTP & API
 *   define( 'BREVO_SMTP_KEY',   'xsmtpsib-...' );             // SMTP key (NOT the API key)
 *   define( 'BREVO_SMTP_FROM',  'news@yoursite.com' );        // a sender verified in Brevo
 *
 * Optional:
 *   define( 'BREVO_SMTP_FROM_NAME', 'The Whitby Anchor' );    // defaults to the site title
 *
 * Without the three required constants the module does nothing and
 * WordPress falls back to its default mailer.
 *
 * @package whitbyanchor
 */

defined( 'ABSPATH' ) || exit;

function wa_brevo_smtp_is_configured(): bool {
	return defined( 'BREVO_SMTP_LOGIN' ) && BREVO_SMTP_LOGIN
		&& defined( 'BREVO_SMTP_KEY' )   && BREVO_SMTP_KEY
		&& defined( 'BREVO_SMTP_FROM' )  && BREVO_SMTP_FROM;
}

/**
 * Point PHPMailer at the Brevo relay.
 */
function wa_brevo_smtp_configure( $phpmailer ): void {
	if ( ! wa_brevo_smtp_is_configured() ) {
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
	return wa_brevo_smtp_is_configured() ? BREVO_SMTP_FROM : $from;
}
add_filter( 'wp_mail_from', 'wa_brevo_smtp_from' );

function wa_brevo_smtp_from_name( string $name ): string {
	if ( ! wa_brevo_smtp_is_configured() ) {
		return $name;
	}
	return defined( 'BREVO_SMTP_FROM_NAME' ) && BREVO_SMTP_FROM_NAME
		? BREVO_SMTP_FROM_NAME
		: get_bloginfo( 'name' );
}
add_filter( 'wp_mail_from_name', 'wa_brevo_smtp_from_name' );

/**
 * Surface delivery failures in the PHP error log so silent drops
 * (e.g. an expired SMTP key) are diagnosable.
 */
function wa_brevo_smtp_log_failure( $error ): void {
	error_log( '[brevo-smtp] wp_mail failed: ' . $error->get_error_message() );
}
add_action( 'wp_mail_failed', 'wa_brevo_smtp_log_failure' );
