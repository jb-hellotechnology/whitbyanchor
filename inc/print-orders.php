<?php
/**
 * Photo print ordering via Stripe Checkout.
 *
 * Appends "Order this photo" links to inline image blocks on individual
 * articles. Each link hits a lightweight endpoint that creates a Stripe
 * Checkout Session for the chosen option and redirects the visitor to
 * Stripe. The image filename travels with the payment as the identifier
 * (client_reference_id + metadata) so print orders can be fulfilled
 * manually from the Stripe dashboard. Digital orders are fulfilled
 * automatically: the webhook emails the customer an expiring link to a
 * web-resolution copy of the photo.
 *
 * Requires the following constants in wp-config.php:
 *   define( 'STRIPE_SECRET_KEY',           'sk_live_...' );
 *   define( 'STRIPE_PRINT_PRICE_DIGITAL', 'price_...' );  // Web resolution digital download
 *   define( 'STRIPE_PRINT_PRICE_12X8',     'price_...' );  // 12 x 8 print
 *   define( 'STRIPE_PRINT_WEBHOOK_SECRET', 'whsec_...' );
 *
 * The webhook powers digital delivery and admin order emails, so it is
 * required. Register this URL in Stripe (event: checkout.session.completed):
 *   https://yoursite.com/?wa_print_webhook=1
 *
 * @package whitbyanchor
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/vendor/autoload.php';

/** How long a digital download link stays valid. */
define( 'WA_PRINT_DOWNLOAD_LIFETIME', 7 * DAY_IN_SECONDS );

/** Where new-order notification emails are sent. */
define( 'WA_PRINT_ORDER_NOTIFY_EMAIL', 'ceri@thewhitbyanchor.co.uk' );

/**
 * The purchase options offered on each photo. Prices live in Stripe; each
 * option maps to a Price ID constant so amounts can change without a deploy.
 * The labels are what visitors see in the figcaption.
 */
function wa_print_options(): array {
	return [
		'digital'    => [
			'label'    => __( 'Web Resolution Digital Download', 'whitbyanchor' ),
			'price'    => defined( 'STRIPE_PRINT_PRICE_DIGITAL' ) ? STRIPE_PRINT_PRICE_DIGITAL : '',
			'shipping' => false,
		],
		'print_12x8' => [
			'label'    => __( '12 x 8 Print', 'whitbyanchor' ),
			'price'    => defined( 'STRIPE_PRINT_PRICE_12X8' ) ? STRIPE_PRINT_PRICE_12X8 : '',
			'shipping' => true,
		],
	];
}

/**
 * Names of required wp-config constants that are missing or empty.
 */
function wa_print_missing_config(): array {
	$missing = [];
	foreach ( [ 'STRIPE_SECRET_KEY', 'STRIPE_PRINT_PRICE_DIGITAL', 'STRIPE_PRINT_PRICE_12X8' ] as $constant ) {
		if ( ! defined( $constant ) || ! constant( $constant ) ) {
			$missing[] = $constant;
		}
	}
	return $missing;
}

/**
 * True only when every constant needed to sell prints is configured.
 */
function wa_print_is_configured(): bool {
	return ! wa_print_missing_config();
}

// ---------------------------------------------------------------------------
// Front end: order links on inline images
// ---------------------------------------------------------------------------

/**
 * The "Order this photo" form for one attachment, or '' when print
 * ordering is unavailable. Shared by inline image blocks and the
 * featured image (see whitbyanchor_post_thumbnail in template-tags.php).
 *
 * A GET form posting to the checkout endpoint: choosing an option
 * submits straight to Stripe. The noscript button covers JS-off visitors.
 */
function wa_print_order_form_html( int $attachment_id ): string {
	if ( ! wa_print_is_configured() || ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		return '';
	}

	// The same image can appear more than once on a page, so ids need a counter.
	static $instance = 0;
	$instance++;
	$field_id = 'wa-print-order-' . $instance;

	$choices = '<option value="">' . esc_html__( 'Choose an option', 'whitbyanchor' ) . '</option>';
	foreach ( wa_print_options() as $key => $option ) {
		$choices .= sprintf(
			'<option value="%s">%s</option>',
			esc_attr( $key ),
			esc_html( $option['label'] )
		);
	}

	return sprintf(
		'<form class="wa-print-order" method="get" action="%1$s">'
		. '<label for="%2$s">%3$s</label>'
		. '<select id="%2$s" name="wa_print_order" onchange="if(this.value)this.form.submit()">%4$s</select>'
		. '<input type="hidden" name="attachment" value="%5$d">'
		. '<input type="hidden" name="article" value="%6$d">'
		. '<noscript><button type="submit">%7$s</button></noscript>'
		. '</form>',
		esc_url( home_url( '/' ) ),
		esc_attr( $field_id ),
		esc_html__( 'Order this photo', 'whitbyanchor' ),
		$choices,
		$attachment_id,
		(int) get_the_ID(),
		esc_html__( 'Order', 'whitbyanchor' )
	);
}

/**
 * Append order links to core Image blocks on single articles only.
 */
function wa_print_render_image_block( string $block_content, array $block ): string {
	if ( ! is_singular( 'post' ) || ! in_the_loop() ) {
		return $block_content;
	}

	$order_html = wa_print_order_form_html( (int) ( $block['attrs']['id'] ?? 0 ) );
	if ( ! $order_html ) {
		return $block_content;
	}

	// Slot into the existing caption — wrapping the caption text in a span
	// so the figcaption can be styled as a layout container — or add a
	// caption if the image has none.
	$with_span = preg_replace(
		'#(<figcaption[^>]*>)(.+?)(</figcaption>)#s',
		'$1<span>$2</span> ' . str_replace( '$', '\\$', $order_html ) . '$3',
		$block_content,
		1,
		$count
	);
	if ( $count ) {
		return $with_span;
	}
	return str_replace( '</figure>', '<figcaption class="wp-element-caption">' . $order_html . '</figcaption></figure>', $block_content );
}
add_filter( 'render_block_core/image', 'wa_print_render_image_block', 10, 2 );

// ---------------------------------------------------------------------------
// Checkout endpoint: ?wa_print_order=<option>&attachment=<id>&article=<id>
// ---------------------------------------------------------------------------

add_action( 'init', 'wa_print_maybe_create_checkout', 1 );

function wa_print_maybe_create_checkout() {
	if ( empty( $_GET['wa_print_order'] ) ) {
		return;
	}

	$options    = wa_print_options();
	$option_key = sanitize_key( wp_unslash( $_GET['wa_print_order'] ) );

	if ( ! isset( $options[ $option_key ] ) || ! wa_print_is_configured() ) {
		$message = esc_html__( 'Print ordering is not available at the moment.', 'whitbyanchor' );

		// Tell logged-in admins exactly what is wrong; visitors get the plain message.
		if ( current_user_can( 'manage_options' ) ) {
			$details = [];
			if ( ! isset( $options[ $option_key ] ) ) {
				$details[] = sprintf(
					'Unknown option "%s" — valid options are: %s. A cached page may be linking to an old option name; clear the page cache.',
					esc_html( $option_key ),
					implode( ', ', array_keys( $options ) )
				);
			}
			$missing = wa_print_missing_config();
			if ( $missing ) {
				$details[] = 'Missing or empty wp-config.php constants: ' . implode( ', ', $missing ) . '.';
			}
			$message .= '<br><br><strong>Admin diagnostic:</strong> ' . implode( '<br>', $details );
		}

		wp_die( $message, '', 404 );
	}

	$option        = $options[ $option_key ];
	$attachment_id = (int) ( $_GET['attachment'] ?? 0 );
	$article_id    = (int) ( $_GET['article'] ?? 0 );

	if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		wp_die( esc_html__( 'Photo not found.', 'whitbyanchor' ), '', 404 );
	}

	$filename    = basename( (string) get_attached_file( $attachment_id ) );
	$article_url = ( $article_id && 'publish' === get_post_status( $article_id ) )
		? get_permalink( $article_id )
		: home_url( '/' );

	\Stripe\Stripe::setApiKey( STRIPE_SECRET_KEY );

	$params = [
		'mode'                 => 'payment',
		'line_items'           => [
			[
				'price'    => $option['price'],
				'quantity' => 1,
			],
		],
		// The filename is the order identifier, shown in the Stripe dashboard.
		// client_reference_id only allows [a-zA-Z0-9_-]; the exact filename is
		// preserved in metadata.
		'client_reference_id'  => substr( preg_replace( '/[^a-zA-Z0-9_-]/', '-', $filename ), 0, 200 ),
		'metadata'             => [
			'wa_print_order' => '1',
			'filename'       => $filename,
			'attachment_id'  => (string) $attachment_id,
			'option_key'     => $option_key,
			'option'         => $option['label'],
			'article'        => $article_url,
		],
		'success_url'          => home_url( '/photo-order/' ),
		'cancel_url'           => $article_url,
	];

	if ( $option['shipping'] ) {
		$params['shipping_address_collection'] = [ 'allowed_countries' => [ 'GB' ] ];
	}

	try {
		$session = \Stripe\Checkout\Session::create( $params );
	} catch ( \Exception $e ) {
		error_log( '[print-orders] Checkout session failed: ' . $e->getMessage() );
		wp_die( esc_html__( 'Sorry, we could not start the checkout. Please try again later.', 'whitbyanchor' ), '', 500 );
	}

	wp_redirect( $session->url, 303 );
	exit;
}

// ---------------------------------------------------------------------------
// Webhook: digital delivery + admin order email
// ---------------------------------------------------------------------------

add_action( 'init', 'wa_print_maybe_handle_webhook', 1 );

function wa_print_maybe_handle_webhook() {
	if ( empty( $_GET['wa_print_webhook'] ) ) {
		return;
	}

	$webhook_secret = defined( 'STRIPE_PRINT_WEBHOOK_SECRET' ) ? STRIPE_PRINT_WEBHOOK_SECRET : '';
	$sk             = defined( 'STRIPE_SECRET_KEY' )           ? STRIPE_SECRET_KEY           : '';

	if ( ! $webhook_secret || ! $sk ) {
		error_log( '[print-orders] ABORT: Stripe constants not configured.' );
		status_header( 500 );
		exit( 'Stripe not configured.' );
	}

	\Stripe\Stripe::setApiKey( $sk );

	$payload = @file_get_contents( 'php://input' );
	$sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

	try {
		$event = \Stripe\Webhook::constructEvent( $payload, $sig, $webhook_secret );
	} catch ( \Exception $e ) {
		error_log( '[print-orders] Signature verification failed: ' . $e->getMessage() );
		status_header( 400 );
		exit( 'Webhook error: ' . $e->getMessage() );
	}

	if ( 'checkout.session.completed' === $event->type ) {
		wa_print_handle_checkout_completed( $event->data->object );
	}

	status_header( 200 );
	exit( 'ok' );
}

function wa_print_handle_checkout_completed( $session ): void {
	// Only act on print orders; the subscribe flow shares this Stripe account.
	if ( empty( $session->metadata->wa_print_order ) ) {
		return;
	}

	wa_print_email_admin( $session );

	if ( 'digital' === ( $session->metadata->option_key ?? '' ) ) {
		wa_print_send_download_email( $session );
	}
}

/**
 * Email the customer an expiring link to the full-resolution original.
 */
function wa_print_send_download_email( $session ): void {
	$attachment_id = (int) ( $session->metadata->attachment_id ?? 0 );
	$email         = sanitize_email( $session->customer_details->email ?? '' );

	if ( ! $attachment_id || ! is_email( $email ) ) {
		error_log( '[print-orders] Digital delivery skipped — attachment=' . $attachment_id . ' email=' . $email );
		return;
	}

	$token = bin2hex( random_bytes( 20 ) );
	set_transient( 'wa_print_dl_' . $token, $attachment_id, WA_PRINT_DOWNLOAD_LIFETIME );

	$download_url = add_query_arg( 'wa_print_download', $token, home_url( '/' ) );
	$site_name    = get_bloginfo( 'name' );

	$body = sprintf(
		/* translators: 1: photo filename, 2: download URL, 3: number of days, 4: site name */
		__(
			"Thank you for your order!\n\nYour web resolution copy of the photo (%1\$s) is ready to download:\n\n%2\$s\n\nThe link is valid for %3\$d days, so please save the file somewhere safe.\n\n%4\$s",
			'whitbyanchor'
		),
		$session->metadata->filename ?? '',
		$download_url,
		WA_PRINT_DOWNLOAD_LIFETIME / DAY_IN_SECONDS,
		$site_name
	);

	$sent = wp_mail(
		$email,
		sprintf( __( 'Your photo download from %s', 'whitbyanchor' ), $site_name ),
		$body
	);

	if ( ! $sent ) {
		error_log( '[print-orders] Digital delivery email failed for ' . $email );
	}
}

/**
 * Email the site admin the order details.
 */
function wa_print_email_admin( $session ): void {
	$meta     = $session->metadata;
	$customer = $session->customer_details;

	// Older Stripe API versions expose the collected address as
	// shipping_details; newer ones nest it under collected_information.
	$shipping = $session->shipping_details
		?? $session->collected_information->shipping_details
		?? null;

	$lines = [
		__( 'New photo order', 'whitbyanchor' ),
		'',
		sprintf( '%s: %s', __( 'Option', 'whitbyanchor' ),   $meta->option ?? '' ),
		sprintf( '%s: %s', __( 'Photo', 'whitbyanchor' ),    $meta->filename ?? '' ),
		sprintf( '%s: %s', __( 'Article', 'whitbyanchor' ),  $meta->article ?? '' ),
		sprintf(
			'%s: %s',
			__( 'Edit photo', 'whitbyanchor' ),
			admin_url( 'post.php?post=' . (int) ( $meta->attachment_id ?? 0 ) . '&action=edit' )
		),
		'',
		sprintf( '%s: %s', __( 'Customer', 'whitbyanchor' ), $customer->name ?? '' ),
		sprintf( '%s: %s', __( 'Email', 'whitbyanchor' ),    $customer->email ?? '' ),
	];

	if ( 'digital' === ( $meta->option_key ?? '' ) ) {
		$lines[] = '';
		$lines[] = __( 'Digital order — the download link has been emailed to the customer automatically.', 'whitbyanchor' );
	}

	$address = $shipping->address ?? null;
	if ( $address ) {
		$lines[] = '';
		$lines[] = __( 'Delivery address:', 'whitbyanchor' );
		if ( ! empty( $shipping->name ) ) {
			$lines[] = $shipping->name;
		}
		foreach ( [ 'line1', 'line2', 'city', 'state', 'postal_code', 'country' ] as $field ) {
			if ( ! empty( $address->$field ) ) {
				$lines[] = $address->$field;
			}
		}
	} elseif ( 'digital' !== ( $meta->option_key ?? '' ) ) {
		$lines[] = '';
		$lines[] = __( 'WARNING: no delivery address was found on this payment — check it in the Stripe dashboard.', 'whitbyanchor' );
	}

	$lines[] = '';
	$lines[] = sprintf(
		'%s: https://dashboard.stripe.com/payments/%s',
		__( 'Payment', 'whitbyanchor' ),
		$session->payment_intent ?? ''
	);

	wp_mail(
		WA_PRINT_ORDER_NOTIFY_EMAIL,
		sprintf( __( 'Photo order: %s', 'whitbyanchor' ), $meta->filename ?? '' ),
		implode( "\n", $lines )
	);
}

/**
 * The file served for a digital purchase: the largest web-sized rendition
 * WordPress has generated. Customers buy web resolution, so the print-grade
 * original is never handed out.
 */
function wa_print_download_file( int $attachment_id ): string {
	$uploads = wp_get_upload_dir();
	foreach ( [ '2048x2048', '1536x1536', 'large' ] as $size ) {
		$data = image_get_intermediate_size( $attachment_id, $size );
		if ( ! empty( $data['path'] ) ) {
			$file = trailingslashit( $uploads['basedir'] ) . $data['path'];
			if ( file_exists( $file ) ) {
				return $file;
			}
		}
	}
	// No web-sized rendition exists — the upload is small enough already.
	return (string) get_attached_file( $attachment_id );
}

// ---------------------------------------------------------------------------
// Download endpoint: ?wa_print_download=<token>
// ---------------------------------------------------------------------------

add_action( 'init', 'wa_print_maybe_serve_download', 1 );

function wa_print_maybe_serve_download() {
	if ( empty( $_GET['wa_print_download'] ) ) {
		return;
	}

	$token = preg_replace( '/[^a-f0-9]/', '', (string) wp_unslash( $_GET['wa_print_download'] ) );

	$attachment_id = $token ? (int) get_transient( 'wa_print_dl_' . $token ) : 0;
	if ( ! $attachment_id ) {
		wp_die(
			esc_html__( 'This download link has expired. Please contact us if you need the photo re-sent.', 'whitbyanchor' ),
			'',
			410
		);
	}

	$file = wa_print_download_file( $attachment_id );

	if ( ! $file || ! file_exists( $file ) ) {
		error_log( '[print-orders] Download file missing for attachment ' . $attachment_id );
		wp_die( esc_html__( 'Sorry, this file is no longer available. Please contact us.', 'whitbyanchor' ), '', 404 );
	}

	nocache_headers();
	header( 'Content-Type: ' . ( get_post_mime_type( $attachment_id ) ?: 'application/octet-stream' ) );
	header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '"' );
	header( 'Content-Length: ' . filesize( $file ) );
	readfile( $file );
	exit;
}
