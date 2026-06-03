<?php
/**
 * Business listing submission form with Stripe subscription payments.
 *
 * Requires Stripe keys stored in wp-config.php (or as WP options):
 *   define( 'STRIPE_SECRET_KEY',      'sk_live_...' );
 *   define( 'STRIPE_PUBLISHABLE_KEY', 'pk_live_...' );
 *   define( 'STRIPE_WEBHOOK_SECRET',  'whsec_...' );
 *   define( 'STRIPE_PRICE_MONTHLY',   'price_...' );  // £20/month
 *   define( 'STRIPE_PRICE_ANNUAL',    'price_...' );  // £120/year
 *
 * Shortcode: [business_submit_form]
 * Webhook URL: https://yoursite.com/?business_stripe_webhook=1
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/vendor/autoload.php';

// ---------------------------------------------------------------------------
// 1. WEBHOOK ENDPOINT — registered before WordPress sends headers
// ---------------------------------------------------------------------------

add_action( 'init', 'bsub_maybe_handle_webhook', 1 );

function bsub_maybe_handle_webhook() {
	if ( empty( $_GET['business_stripe_webhook'] ) ) {
		return;
	}

	$secret = defined( 'STRIPE_WEBHOOK_SECRET' ) ? STRIPE_WEBHOOK_SECRET : get_option( 'stripe_webhook_secret' );
	$sk     = defined( 'STRIPE_SECRET_KEY' )      ? STRIPE_SECRET_KEY      : get_option( 'stripe_secret_key' );

	if ( ! $secret || ! $sk ) {
		status_header( 500 );
		exit( 'Stripe not configured.' );
	}

	\Stripe\Stripe::setApiKey( $sk );

	$payload   = @file_get_contents( 'php://input' );
	$sig       = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

	try {
		$event = \Stripe\Webhook::constructEvent( $payload, $sig, $secret );
	} catch ( \Exception $e ) {
		status_header( 400 );
		exit( 'Webhook error: ' . $e->getMessage() );
	}

	switch ( $event->type ) {
		case 'checkout.session.completed':
			bsub_handle_checkout_completed( $event->data->object );
			break;

		case 'customer.subscription.deleted':
		case 'invoice.payment_failed':
			bsub_handle_subscription_lapsed( $event->data->object );
			break;

		case 'customer.subscription.updated':
			bsub_handle_subscription_updated( $event->data->object );
			break;
	}

	status_header( 200 );
	exit( 'ok' );
}

function bsub_handle_checkout_completed( $session ) {
	$meta = $session->metadata ?? null;
	if ( ! $meta || empty( $meta->pending_post_id ) ) {
		return;
	}

	$post_id = intval( $meta->pending_post_id );
	$post    = get_post( $post_id );
	if ( ! $post || $post->post_type !== 'business' ) {
		return;
	}

	// Store Stripe subscription/customer IDs for lifecycle management
	update_post_meta( $post_id, '_stripe_customer_id',     sanitize_text_field( $session->customer ?? '' ) );
	update_post_meta( $post_id, '_stripe_subscription_id', sanitize_text_field( $session->subscription ?? '' ) );

	// Move to pending review so the admin can approve it
	wp_update_post( [
		'ID'          => $post_id,
		'post_status' => 'pending',
	] );

	// Notify admin
	$admin_email = get_option( 'admin_email' );
	$edit_url    = admin_url( 'post.php?action=edit&post=' . $post_id );
	wp_mail(
		$admin_email,
		'New business listing awaiting approval: ' . get_the_title( $post_id ),
		"A new business listing has been submitted and paid for.\n\nReview and publish it here:\n{$edit_url}"
	);
}

function bsub_handle_subscription_lapsed( $object ) {
	$sub_id  = is_string( $object ) ? $object : ( $object->id ?? ( $object->subscription ?? '' ) );
	if ( ! $sub_id ) return;

	$posts = get_posts( [
		'post_type'      => 'business',
		'posts_per_page' => 1,
		'post_status'    => 'any',
		'meta_key'       => '_stripe_subscription_id',
		'meta_value'     => $sub_id,
	] );

	foreach ( $posts as $post ) {
		wp_update_post( [ 'ID' => $post->ID, 'post_status' => 'draft' ] );
	}
}

function bsub_handle_subscription_updated( $subscription ) {
	$sub_id = $subscription->id ?? '';
	if ( ! $sub_id ) return;

	$posts = get_posts( [
		'post_type'      => 'business',
		'posts_per_page' => 1,
		'post_status'    => 'any',
		'meta_key'       => '_stripe_subscription_id',
		'meta_value'     => $sub_id,
	] );

	foreach ( $posts as $post ) {
		$active = in_array( $subscription->status ?? '', [ 'active', 'trialing' ], true );
		if ( ! $active && $post->post_status === 'publish' ) {
			wp_update_post( [ 'ID' => $post->ID, 'post_status' => 'draft' ] );
		}
	}
}


// ---------------------------------------------------------------------------
// 2. AJAX — create Stripe Checkout session
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_bsub_create_checkout',        'bsub_create_checkout' );
add_action( 'wp_ajax_nopriv_bsub_create_checkout', 'bsub_create_checkout' );

function bsub_create_checkout() {
	check_ajax_referer( 'bsub_nonce', 'nonce' );

	$post_id = intval( $_POST['post_id'] ?? 0 );
	$plan    = sanitize_key( $_POST['plan'] ?? '' );

	if ( ! $post_id || ! in_array( $plan, [ 'monthly', 'annual' ], true ) ) {
		wp_send_json_error( 'Invalid request.' );
	}

	$post = get_post( $post_id );
	if ( ! $post || $post->post_type !== 'business' || $post->post_status !== 'draft' ) {
		wp_send_json_error( 'Post not found.' );
	}

	$sk = defined( 'STRIPE_SECRET_KEY' ) ? STRIPE_SECRET_KEY : get_option( 'stripe_secret_key' );
	if ( ! $sk ) {
		wp_send_json_error( 'Payment not configured.' );
	}

	$price_id = ( $plan === 'annual' )
		? ( defined( 'STRIPE_PRICE_ANNUAL' )  ? STRIPE_PRICE_ANNUAL  : get_option( 'stripe_price_annual' ) )
		: ( defined( 'STRIPE_PRICE_MONTHLY' ) ? STRIPE_PRICE_MONTHLY : get_option( 'stripe_price_monthly' ) );

	if ( ! $price_id ) {
		wp_send_json_error( 'Price not configured.' );
	}

	\Stripe\Stripe::setApiKey( $sk );

	$success_url = add_query_arg( [
		'bsub_success' => 1,
		'post_id'      => $post_id,
	], get_permalink( $post_id ) ?: home_url( '/business-directory/' ) );

	$cancel_url = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : home_url( '/' );

	try {
		$session = \Stripe\Checkout\Session::create( [
			'mode'                 => 'subscription',
			'line_items'           => [ [ 'price' => $price_id, 'quantity' => 1 ] ],
			'success_url'          => $success_url,
			'cancel_url'           => $cancel_url,
			'metadata'             => [ 'pending_post_id' => $post_id ],
			'subscription_data'    => [ 'metadata' => [ 'pending_post_id' => $post_id ] ],
			'allow_promotion_codes' => true,
		] );

		wp_send_json_success( [ 'url' => $session->url ] );
	} catch ( \Exception $e ) {
		wp_send_json_error( $e->getMessage() );
	}
}


// ---------------------------------------------------------------------------
// 3. AJAX — save draft post & upload logo
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_bsub_save_draft',        'bsub_save_draft' );
add_action( 'wp_ajax_nopriv_bsub_save_draft', 'bsub_save_draft' );

function bsub_save_draft() {
	check_ajax_referer( 'bsub_nonce', 'nonce' );

	// Sanitise all fields
	$title    = sanitize_text_field( $_POST['business_name']    ?? '' );
	$excerpt  = sanitize_textarea_field( $_POST['description']  ?? '' );
	$address  = sanitize_text_field( $_POST['address']          ?? '' );
	$phone    = sanitize_text_field( $_POST['phone']            ?? '' );
	$email    = sanitize_email( $_POST['email']                 ?? '' );
	$website  = esc_url_raw( $_POST['website']                  ?? '' );
	$category = intval( $_POST['category']                      ?? 0 );
	$location = intval( $_POST['location']                      ?? 0 );

	if ( ! $title ) {
		wp_send_json_error( 'Business name is required.' );
	}

	// Create a draft post
	$post_id = wp_insert_post( [
		'post_type'    => 'business',
		'post_title'   => $title,
		'post_excerpt' => $excerpt,
		'post_status'  => 'draft',
		'post_author'  => 0,
	], true );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( $post_id->get_error_message() );
	}

	// Meta
	update_post_meta( $post_id, '_business_address', $address );
	update_post_meta( $post_id, '_business_phone',   $phone );
	update_post_meta( $post_id, '_business_email',   $email );
	update_post_meta( $post_id, '_business_website', $website );

	// Taxonomies
	if ( $category ) wp_set_object_terms( $post_id, [ $category ], 'business_category' );
	if ( $location ) wp_set_object_terms( $post_id, [ $location ], 'business_location' );

	// Logo upload (optional)
	if ( ! empty( $_FILES['logo'] ) && $_FILES['logo']['error'] === UPLOAD_ERR_OK ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$allowed_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
		if ( ! in_array( $_FILES['logo']['type'], $allowed_types, true ) ) {
			wp_send_json_error( 'Logo must be a JPEG, PNG, GIF, or WebP image.' );
		}
		if ( $_FILES['logo']['size'] > 2 * 1024 * 1024 ) {
			wp_send_json_error( 'Logo must be under 2 MB.' );
		}

		$attachment_id = media_handle_upload( 'logo', $post_id );
		if ( ! is_wp_error( $attachment_id ) ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}
	}

	wp_send_json_success( [ 'post_id' => $post_id ] );
}


// ---------------------------------------------------------------------------
// 4. SHORTCODE  [business_submit_form]
// ---------------------------------------------------------------------------

add_shortcode( 'business_submit_form', 'bsub_shortcode' );

function bsub_shortcode() {
	$pk = defined( 'STRIPE_PUBLISHABLE_KEY' ) ? STRIPE_PUBLISHABLE_KEY : get_option( 'stripe_publishable_key' );

	$categories = get_terms( [ 'taxonomy' => 'business_category', 'hide_empty' => false, 'orderby' => 'name' ] );
	$locations  = get_terms( [ 'taxonomy' => 'business_location',  'hide_empty' => false, 'orderby' => 'name' ] );

	// If we're back from a successful Stripe payment
	if ( ! empty( $_GET['bsub_success'] ) ) {
		return '<div class="bsub-success"><h2>Thank you!</h2><p>Your listing has been received and is awaiting review. You\'ll be notified by email once it\'s live.</p></div>';
	}

	ob_start();
	?>
	<div class="bsub-form-wrap" id="bsub-form-wrap">

		<!-- Step 1: Business details -->
		<div class="bsub-step" id="bsub-step-1">
			<h2 class="bsub-step__title">Your business details</h2>
			<form id="bsub-details-form" enctype="multipart/form-data" novalidate>
				<?php wp_nonce_field( 'bsub_nonce', 'bsub_nonce' ); ?>

				<div class="bsub-field bsub-field--required">
					<label for="bsub_name">Business name</label>
					<input type="text" id="bsub_name" name="business_name" required maxlength="200">
				</div>

				<div class="bsub-field bsub-field--required">
					<label for="bsub_description">Short description</label>
					<textarea id="bsub_description" name="description" rows="4" required maxlength="500" placeholder="Tell visitors what you do in a sentence or two."></textarea>
				</div>

				<div class="bsub-field">
					<label for="bsub_address">Address</label>
					<input type="text" id="bsub_address" name="address" maxlength="200" placeholder="e.g. 12 High Street, Whitby, YO21 3EW">
				</div>

				<div class="bsub-field">
					<label for="bsub_phone">Phone</label>
					<input type="tel" id="bsub_phone" name="phone" maxlength="30" placeholder="e.g. 01947 123456">
				</div>

				<div class="bsub-field">
					<label for="bsub_email">Email</label>
					<input type="email" id="bsub_email" name="email" maxlength="100" placeholder="hello@example.com">
				</div>

				<div class="bsub-field">
					<label for="bsub_website">Website</label>
					<input type="url" id="bsub_website" name="website" maxlength="200" placeholder="https://example.com">
				</div>

				<?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
				<div class="bsub-field">
					<label for="bsub_category">Category</label>
					<select id="bsub_category" name="category">
						<option value="">— Select a category —</option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>

				<?php if ( ! is_wp_error( $locations ) && ! empty( $locations ) ) : ?>
				<div class="bsub-field">
					<label for="bsub_location">Location</label>
					<select id="bsub_location" name="location">
						<option value="">— Select a location —</option>
						<?php foreach ( $locations as $loc ) : ?>
							<option value="<?php echo esc_attr( $loc->term_id ); ?>"><?php echo esc_html( $loc->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>

				<div class="bsub-field">
					<label for="bsub_logo">Logo / photo <span class="bsub-hint">(JPEG, PNG, WebP — max 2 MB)</span></label>
					<input type="file" id="bsub_logo" name="logo" accept="image/jpeg,image/png,image/gif,image/webp">
				</div>

				<div class="bsub-error" id="bsub-details-error" hidden></div>

				<button type="submit" class="bsub-btn">Next: choose your plan →</button>
			</form>
		</div>

		<!-- Step 2: Plan selection -->
		<div class="bsub-step" id="bsub-step-2" hidden>
			<h2 class="bsub-step__title">Choose your plan</h2>
			<p class="bsub-intro">Your listing will be reviewed before going live. Cancel any time from your Stripe dashboard.</p>

			<div class="bsub-plans">
				<label class="bsub-plan" id="bsub-plan-monthly">
					<input type="radio" name="bsub_plan" value="monthly" checked>
					<div class="bsub-plan__inner">
						<strong class="bsub-plan__name">Monthly</strong>
						<span class="bsub-plan__price">£20 / month</span>
						<span class="bsub-plan__note">Billed monthly, cancel anytime</span>
					</div>
				</label>

				<label class="bsub-plan" id="bsub-plan-annual">
					<input type="radio" name="bsub_plan" value="annual">
					<div class="bsub-plan__inner">
						<strong class="bsub-plan__name">Annual</strong>
						<span class="bsub-plan__price">£120 / year</span>
						<span class="bsub-plan__note">Save £120 vs monthly</span>
					</div>
				</label>
			</div>

			<div class="bsub-error" id="bsub-plan-error" hidden></div>

			<div class="bsub-plan-actions">
				<button class="bsub-btn bsub-btn--secondary" id="bsub-back-btn">← Back</button>
				<button class="bsub-btn" id="bsub-pay-btn">Proceed to payment →</button>
			</div>
		</div>

		<!-- Loading overlay -->
		<div class="bsub-loading" id="bsub-loading" hidden>
			<span class="bsub-spinner"></span> Redirecting to payment…
		</div>

	</div>

	<script>
	(function () {
		var ajaxUrl  = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		var nonce    = <?php echo wp_json_encode( wp_create_nonce( 'bsub_nonce' ) ); ?>;
		var pendingPostId = null;

		var step1    = document.getElementById('bsub-step-1');
		var step2    = document.getElementById('bsub-step-2');
		var loading  = document.getElementById('bsub-loading');
		var detailsForm = document.getElementById('bsub-details-form');
		var detailsErr  = document.getElementById('bsub-details-error');
		var planErr     = document.getElementById('bsub-plan-error');

		function showError(el, msg) {
			el.textContent = msg;
			el.hidden = false;
		}
		function clearError(el) { el.hidden = true; }

		// Step 1: submit details
		detailsForm.addEventListener('submit', function (e) {
			e.preventDefault();
			clearError(detailsErr);

			var name = detailsForm.querySelector('[name="business_name"]').value.trim();
			var desc = detailsForm.querySelector('[name="description"]').value.trim();
			if (!name) { showError(detailsErr, 'Please enter your business name.'); return; }
			if (!desc) { showError(detailsErr, 'Please enter a short description.'); return; }

			var fd = new FormData(detailsForm);
			fd.set('action', 'bsub_save_draft');
			fd.set('nonce', nonce);

			var btn = detailsForm.querySelector('button[type="submit"]');
			btn.disabled = true;
			btn.textContent = 'Saving…';

			fetch(ajaxUrl, { method: 'POST', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (data) {
					btn.disabled = false;
					btn.textContent = 'Next: choose your plan →';
					if (data.success) {
						pendingPostId = data.data.post_id;
						step1.hidden = true;
						step2.hidden = false;
					} else {
						showError(detailsErr, data.data || 'Something went wrong. Please try again.');
					}
				})
				.catch(function () {
					btn.disabled = false;
					btn.textContent = 'Next: choose your plan →';
					showError(detailsErr, 'Network error. Please try again.');
				});
		});

		// Step 2: back button
		document.getElementById('bsub-back-btn').addEventListener('click', function () {
			step2.hidden = true;
			step1.hidden = false;
		});

		// Step 2: proceed to payment
		document.getElementById('bsub-pay-btn').addEventListener('click', function () {
			clearError(planErr);
			if (!pendingPostId) { showError(planErr, 'Something went wrong. Please go back and retry.'); return; }

			var plan = document.querySelector('input[name="bsub_plan"]:checked').value;
			var btn = document.getElementById('bsub-pay-btn');
			btn.disabled = true;

			var fd = new FormData();
			fd.set('action',  'bsub_create_checkout');
			fd.set('nonce',   nonce);
			fd.set('post_id', pendingPostId);
			fd.set('plan',    plan);

			step2.hidden = true;
			loading.hidden = false;

			fetch(ajaxUrl, { method: 'POST', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (data.success && data.data.url) {
						window.location.href = data.data.url;
					} else {
						loading.hidden = true;
						step2.hidden = false;
						btn.disabled = false;
						showError(planErr, data.data || 'Could not start payment. Please try again.');
					}
				})
				.catch(function () {
					loading.hidden = true;
					step2.hidden = false;
					btn.disabled = false;
					showError(planErr, 'Network error. Please try again.');
				});
		});

		// Highlight selected plan card
		document.querySelectorAll('input[name="bsub_plan"]').forEach(function (radio) {
			radio.addEventListener('change', function () {
				document.querySelectorAll('.bsub-plan').forEach(function (el) {
					el.classList.remove('bsub-plan--selected');
				});
				radio.closest('.bsub-plan').classList.add('bsub-plan--selected');
			});
		});
		document.querySelector('input[name="bsub_plan"]:checked').closest('.bsub-plan').classList.add('bsub-plan--selected');
	})();
	</script>
	<?php
	return ob_get_clean();
}


// ---------------------------------------------------------------------------
// 5. INLINE CSS
// ---------------------------------------------------------------------------

add_action( 'wp_head', 'bsub_inline_styles' );

function bsub_inline_styles() {
	global $post;
	if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'business_submit_form' ) ) {
		return;
	}
	?>
	<style>
	.bsub-form-wrap { max-width: 600px; margin: 0 auto; }
	.bsub-step__title { margin-bottom: 1rem; }
	.bsub-intro { margin-bottom: 1.5rem; color: #555; }
	.bsub-field { margin-bottom: 1.25rem; }
	.bsub-field label { display: block; font-weight: 600; margin-bottom: .35rem; }
	.bsub-hint { font-weight: 400; color: #777; font-size: .85em; }
	.bsub-field input[type="text"],
	.bsub-field input[type="email"],
	.bsub-field input[type="tel"],
	.bsub-field input[type="url"],
	.bsub-field textarea,
	.bsub-field select { width: 100%; padding: .6rem .75rem; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; box-sizing: border-box; }
	.bsub-field textarea { resize: vertical; }
	.bsub-plans { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
	.bsub-plan { flex: 1; min-width: 180px; cursor: pointer; }
	.bsub-plan input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
	.bsub-plan__inner { border: 2px solid #ddd; border-radius: 8px; padding: 1.25rem; text-align: center; transition: border-color .15s; }
	.bsub-plan--selected .bsub-plan__inner { border-color: currentColor; }
	.bsub-plan__name { display: block; font-size: 1.1rem; margin-bottom: .25rem; }
	.bsub-plan__price { display: block; font-size: 1.5rem; font-weight: 700; margin-bottom: .25rem; }
	.bsub-plan__note { display: block; font-size: .8rem; color: #777; }
	.bsub-btn { display: inline-block; padding: .7rem 1.5rem; background: var(--color-primary); color: #fff; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
	.bsub-btn:hover{background: var(--color-secondary);color:#fff;}
	.bsub-btn--secondary { background: #888; margin-right: .5rem; }
	.bsub-btn:disabled { opacity: .6; cursor: not-allowed; }
	.bsub-plan-actions { display: flex; align-items: center; }
	.bsub-error { margin-top: .75rem; padding: .65rem 1rem; background: #fff3f3; border: 1px solid #f99; border-radius: 4px; color: #c00; }
	.bsub-loading { text-align: center; padding: 2rem; }
	.bsub-spinner { display: inline-block; width: 1.25rem; height: 1.25rem; border: 3px solid #ddd; border-top-color: #333; border-radius: 50%; animation: bsub-spin .7s linear infinite; vertical-align: middle; margin-right: .5rem; }
	@keyframes bsub-spin { to { transform: rotate(360deg); } }
	.bsub-success { background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 2rem; text-align: center; }
	</style>
	<?php
}


// ---------------------------------------------------------------------------
// 6. ADMIN — subscription status column on business list
// ---------------------------------------------------------------------------

add_filter( 'manage_business_posts_columns', function ( $cols ) {
	$cols['stripe_status'] = 'Subscription';
	return $cols;
} );

add_action( 'manage_business_posts_custom_column', function ( $col, $post_id ) {
	if ( $col !== 'stripe_status' ) return;
	$sub_id = get_post_meta( $post_id, '_stripe_subscription_id', true );
	echo $sub_id ? '<span title="' . esc_attr( $sub_id ) . '">Active</span>' : '—';
}, 10, 2 );
