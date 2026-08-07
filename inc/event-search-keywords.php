<?php
/**
 * Event search keywords — AI-generated synonyms, abbreviations, related terms,
 * and common misspellings attached to each event so the /whats-on/ free-text
 * search matches beyond the literal words in the title/excerpt/content.
 *
 * Include this file from functions.php:
 *   require_once get_template_directory() . '/inc/event-search-keywords.php';
 *
 * Keywords are stored in the '_event_search_keywords' post meta as a single
 * space-separated, lowercased string. The events AJAX search (inc/events-ajax.php)
 * appends that meta to its search haystack.
 *
 * Generation happens via the Claude API (Haiku 4.5). Requires an API key defined
 * in wp-config.php:
 *   define( 'ANTHROPIC_API_KEY', 'sk-ant-...' );
 */

defined( 'ABSPATH' ) || exit;

// Meta keys
const WHITBYANCHOR_EVENT_KEYWORDS_META      = '_event_search_keywords';
const WHITBYANCHOR_EVENT_KEYWORDS_HASH_META = '_event_search_keywords_hash';

// Cron hooks
const WHITBYANCHOR_EVENT_KEYWORDS_HOOK  = 'whitbyanchor_generate_event_keywords_event';
const WHITBYANCHOR_EVENT_BACKFILL_HOOK  = 'whitbyanchor_backfill_event_keywords';

// Claude model + tuning
const WHITBYANCHOR_EVENT_KEYWORDS_MODEL   = 'claude-haiku-4-5';
const WHITBYANCHOR_EVENT_KEYWORDS_MAX     = 40; // max stored terms per event
const WHITBYANCHOR_EVENT_BACKFILL_BATCH   = 5;  // events processed per cron run

// ---------------------------------------------------------------------------
// 1. SOURCE TEXT + GENERATION
// ---------------------------------------------------------------------------

/**
 * Build the source text an event's keywords are derived from. The md5 of this
 * string is stored so we only re-hit the API when something relevant changed.
 */
function whitbyanchor_event_keyword_source( int $post_id ): string {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}

	$locations = wp_get_object_terms( $post_id, 'event_location', [ 'fields' => 'names' ] );
	$tags      = wp_get_object_terms( $post_id, 'event_tag', [ 'fields' => 'names' ] );

	$terms = [];
	if ( ! is_wp_error( $locations ) ) {
		$terms = array_merge( $terms, $locations );
	}
	if ( ! is_wp_error( $tags ) ) {
		$terms = array_merge( $terms, $tags );
	}

	return trim(
		$post->post_title . "\n" .
		$post->post_excerpt . "\n" .
		wp_strip_all_tags( $post->post_content ) . "\n" .
		implode( ', ', $terms )
	);
}

/**
 * Generate and store search keywords for one event.
 *
 * Skips the API call when the source text is unchanged since the last run, when
 * the post isn't a published event, or when no API key is configured. Safe to
 * call directly (backfill) or via cron (on save).
 *
 * @return bool True if keywords were (re)generated and stored.
 */
function whitbyanchor_generate_event_keywords( int $post_id ): bool {
	if ( get_post_type( $post_id ) !== 'event' || get_post_status( $post_id ) !== 'publish' ) {
		return false;
	}

	$source = whitbyanchor_event_keyword_source( $post_id );
	if ( $source === '' ) {
		return false;
	}

	$hash = md5( $source );
	if ( get_post_meta( $post_id, WHITBYANCHOR_EVENT_KEYWORDS_HASH_META, true ) === $hash ) {
		return false; // unchanged since last generation
	}

	$terms = whitbyanchor_request_event_keywords( $source );
	if ( $terms === null ) {
		return false; // API/network error — leave existing keywords in place, retry next save
	}

	update_post_meta( $post_id, WHITBYANCHOR_EVENT_KEYWORDS_META, implode( ' ', $terms ) );
	update_post_meta( $post_id, WHITBYANCHOR_EVENT_KEYWORDS_HASH_META, $hash );

	return true;
}
add_action( WHITBYANCHOR_EVENT_KEYWORDS_HOOK, 'whitbyanchor_generate_event_keywords' );

/**
 * Call the Claude API to expand event source text into search keywords.
 *
 * @return array|null Array of sanitised lowercase terms, or null on error.
 */
function whitbyanchor_request_event_keywords( string $source ): ?array {
	if ( ! defined( 'ANTHROPIC_API_KEY' ) || ! ANTHROPIC_API_KEY ) {
		return null;
	}

	$prompt =
		"You expand a local event listing into extra search keywords so visitors find it " .
		"even when they search with synonyms, part-words, abbreviations, or common misspellings.\n\n" .
		"Event:\n\"\"\"\n" . $source . "\n\"\"\"\n\n" .
		"Return ONLY a JSON array of 10-30 lowercase terms (each one or two words): synonyms, " .
		"related activities or themes, abbreviations, alternative phrasings, and likely misspellings. " .
		"Do not include explanations or any text outside the JSON array.";

	$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
		'timeout' => 30,
		'headers' => [
			'x-api-key'         => ANTHROPIC_API_KEY,
			'anthropic-version' => '2023-06-01',
			'content-type'      => 'application/json',
		],
		'body' => wp_json_encode( [
			'model'      => WHITBYANCHOR_EVENT_KEYWORDS_MODEL,
			'max_tokens' => 400,
			'messages'   => [
				[ 'role' => 'user', 'content' => $prompt ],
			],
		] ),
	] );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return null;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$text = $body['content'][0]['text'] ?? '';

	$terms = json_decode( $text, true );
	if ( ! is_array( $terms ) ) {
		// Model occasionally wraps the array in prose; try to recover the array.
		if ( preg_match( '/\[.*\]/s', (string) $text, $m ) ) {
			$terms = json_decode( $m[0], true );
		}
	}
	if ( ! is_array( $terms ) ) {
		return null;
	}

	// Sanitise: lowercase, trim, drop empties, dedupe, cap count.
	$clean = [];
	foreach ( $terms as $term ) {
		if ( ! is_scalar( $term ) ) {
			continue;
		}
		$term = mb_strtolower( trim( sanitize_text_field( (string) $term ) ) );
		if ( $term !== '' ) {
			$clean[ $term ] = true; // key dedupe preserves order
		}
	}

	return array_slice( array_keys( $clean ), 0, WHITBYANCHOR_EVENT_KEYWORDS_MAX );
}

// ---------------------------------------------------------------------------
// 2. TRIGGER ON SAVE (async via WP-Cron so publishing stays instant)
// ---------------------------------------------------------------------------

function whitbyanchor_schedule_event_keywords( int $post_id, WP_Post $post ): void {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( $post->post_status !== 'publish' ) {
		return;
	}
	if ( ! wp_next_scheduled( WHITBYANCHOR_EVENT_KEYWORDS_HOOK, [ $post_id ] ) ) {
		wp_schedule_single_event( time() + 10, WHITBYANCHOR_EVENT_KEYWORDS_HOOK, [ $post_id ] );
	}
}
add_action( 'save_post_event', 'whitbyanchor_schedule_event_keywords', 20, 2 );

// ---------------------------------------------------------------------------
// 3. BACKFILL — self-rescheduling batch over events missing keywords
// ---------------------------------------------------------------------------

/**
 * Process one batch of events that have no keywords yet, then reschedule itself
 * if more remain. Small batches keep each cron run short and stay well within
 * API rate limits.
 */
function whitbyanchor_run_event_keywords_backfill(): void {
	$ids = get_posts( [
		'post_type'   => 'event',
		'post_status' => 'publish',
		'fields'      => 'ids',
		'numberposts' => WHITBYANCHOR_EVENT_BACKFILL_BATCH,
		'orderby'     => 'ID',
		'order'       => 'ASC',
		'meta_query'  => [
			[
				'key'     => WHITBYANCHOR_EVENT_KEYWORDS_HASH_META,
				'compare' => 'NOT EXISTS',
			],
		],
	] );

	foreach ( $ids as $id ) {
		whitbyanchor_generate_event_keywords( $id );
	}

	// A full batch means there are probably more to do — go again shortly.
	if ( count( $ids ) === WHITBYANCHOR_EVENT_BACKFILL_BATCH ) {
		wp_schedule_single_event( time() + 60, WHITBYANCHOR_EVENT_BACKFILL_HOOK );
	}
}
add_action( WHITBYANCHOR_EVENT_BACKFILL_HOOK, 'whitbyanchor_run_event_keywords_backfill' );

/**
 * Count events still awaiting a first keyword generation.
 */
function whitbyanchor_event_keywords_pending_count(): int {
	$ids = get_posts( [
		'post_type'   => 'event',
		'post_status' => 'publish',
		'fields'      => 'ids',
		'numberposts' => -1,
		'meta_query'  => [
			[
				'key'     => WHITBYANCHOR_EVENT_KEYWORDS_HASH_META,
				'compare' => 'NOT EXISTS',
			],
		],
	] );

	return count( $ids );
}

// ---------------------------------------------------------------------------
// 4. ADMIN — "Generate search keywords" tool under the Events menu
// ---------------------------------------------------------------------------

function whitbyanchor_event_keywords_admin_menu(): void {
	add_submenu_page(
		'edit.php?post_type=event',
		'Search Keywords',
		'Search Keywords',
		'manage_options',
		'event-search-keywords',
		'whitbyanchor_event_keywords_admin_page'
	);
}
add_action( 'admin_menu', 'whitbyanchor_event_keywords_admin_menu' );

function whitbyanchor_event_keywords_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle the "start backfill" action.
	if (
		isset( $_POST['whitbyanchor_backfill_keywords'] ) &&
		check_admin_referer( 'whitbyanchor_backfill_keywords' )
	) {
		if ( ! wp_next_scheduled( WHITBYANCHOR_EVENT_BACKFILL_HOOK ) ) {
			wp_schedule_single_event( time() + 5, WHITBYANCHOR_EVENT_BACKFILL_HOOK );
		}
		echo '<div class="notice notice-success"><p>Backfill started. Keywords will be generated in the background over the next few minutes. Reload this page to watch the count fall.</p></div>';
	}

	$has_key = defined( 'ANTHROPIC_API_KEY' ) && ANTHROPIC_API_KEY;
	$pending = whitbyanchor_event_keywords_pending_count();
	$running = (bool) wp_next_scheduled( WHITBYANCHOR_EVENT_BACKFILL_HOOK );
	?>
	<div class="wrap">
		<h1>Event Search Keywords</h1>
		<p>Each event gets an AI-generated list of synonyms, abbreviations, related
		terms, and common misspellings (via Claude Haiku 4.5). These are added to the
		<code>/whats-on/</code> search so visitors find events even when their wording
		doesn't exactly match the listing.</p>

		<?php if ( ! $has_key ) : ?>
			<div class="notice notice-error inline">
				<p><strong>No API key configured.</strong> Add
				<code>define( 'ANTHROPIC_API_KEY', 'sk-ant-...' );</code> to
				<code>wp-config.php</code> before running the backfill.</p>
			</div>
		<?php endif; ?>

		<table class="widefat" style="max-width:480px;margin:1em 0;">
			<tbody>
				<tr>
					<td>Events awaiting keywords</td>
					<td><strong><?php echo (int) $pending; ?></strong></td>
				</tr>
				<tr>
					<td>Backfill currently running</td>
					<td><strong><?php echo $running ? 'Yes' : 'No'; ?></strong></td>
				</tr>
			</tbody>
		</table>

		<form method="post">
			<?php wp_nonce_field( 'whitbyanchor_backfill_keywords' ); ?>
			<p>
				<button type="submit" name="whitbyanchor_backfill_keywords" value="1"
					class="button button-primary"
					<?php disabled( ! $has_key || $pending === 0 || $running ); ?>>
					Generate keywords for <?php echo (int) $pending; ?> event(s)
				</button>
			</p>
		</form>

		<p class="description">Keywords are regenerated automatically whenever an
		event is saved and its title, description, location, or tags have changed.
		You can also regenerate a single event from the <strong>Events</strong> list
		using the &ldquo;Regenerate keywords&rdquo; row action.</p>
	</div>
	<?php
}

// ---------------------------------------------------------------------------
// 5. ADMIN — per-event "Regenerate keywords" row action
// ---------------------------------------------------------------------------

function whitbyanchor_event_keywords_row_action( array $actions, WP_Post $post ): array {
	if ( $post->post_type !== 'event' || ! current_user_can( 'manage_options' ) ) {
		return $actions;
	}

	$url = wp_nonce_url(
		admin_url( 'admin-post.php?action=whitbyanchor_regenerate_keywords&post=' . $post->ID ),
		'whitbyanchor_regenerate_keywords_' . $post->ID
	);

	$actions['regenerate_keywords'] = sprintf(
		'<a href="%s">%s</a>',
		esc_url( $url ),
		esc_html__( 'Regenerate keywords', 'whitbyanchor' )
	);

	return $actions;
}
add_filter( 'post_row_actions', 'whitbyanchor_event_keywords_row_action', 10, 2 );

function whitbyanchor_handle_regenerate_keywords(): void {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

	if ( ! $post_id || ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'whitbyanchor_regenerate_keywords_' . $post_id );

	// Force regeneration by clearing the stored source hash, then run synchronously.
	delete_post_meta( $post_id, WHITBYANCHOR_EVENT_KEYWORDS_HASH_META );
	$done = whitbyanchor_generate_event_keywords( $post_id );

	$redirect = add_query_arg(
		'keywords_regenerated',
		$done ? '1' : '0',
		admin_url( 'edit.php?post_type=event' )
	);
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_whitbyanchor_regenerate_keywords', 'whitbyanchor_handle_regenerate_keywords' );

function whitbyanchor_event_keywords_admin_notice(): void {
	if ( ! isset( $_GET['keywords_regenerated'] ) ) {
		return;
	}
	if ( $_GET['keywords_regenerated'] === '1' ) {
		echo '<div class="notice notice-success is-dismissible"><p>Search keywords regenerated.</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>Could not regenerate keywords — check that <code>ANTHROPIC_API_KEY</code> is set and the API is reachable.</p></div>';
	}
}
add_action( 'admin_notices', 'whitbyanchor_event_keywords_admin_notice' );

// ---------------------------------------------------------------------------
// 6. CLEANUP — clear stored keywords when an event is deleted
// ---------------------------------------------------------------------------
// (Post meta is removed automatically with the post; nothing extra needed.)
