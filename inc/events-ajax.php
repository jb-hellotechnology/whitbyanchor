<?php
/**
 * Events AJAX — handler + shared render helper.
 *
 * Include this file from functions.php:
 *   require_once get_template_directory() . '/inc/events-ajax.php';
 */

defined( 'ABSPATH' ) || exit;

const WHITBYANCHOR_EVENTS_PER_PAGE = 12;

// ── Render helper ────────────────────────────────────────────────────────────
//
// Extracted so the template and the AJAX handler both produce identical markup.

function whitbyanchor_render_event_article( array $event ): string {
	$post = $event['post'];

	$tags        = get_the_terms( $post->ID, 'event_tag' );
	$tags_string = '';
	if ( $tags && ! is_wp_error( $tags ) ) {
		foreach ( $tags as $tag ) {
			$tags_string .= esc_attr( $tag->slug ) . ',';
		}
	}
	
	$locations        = get_the_terms( $post->ID, 'event_location' );
	$locations_string = '';
	if ( $locations && ! is_wp_error( $locations ) ) {
		foreach ( $locations as $location ) {
			$locations_string .= esc_attr( $location->slug ) . ',';
		}
	}

	ob_start();
	
	if ( ! empty( $event['end_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $event['end_date'] ) ) {
		$end_date = new DateTime( $event['end_date'] );
	}
	
	$has_image = false;
	$has_image = has_post_thumbnail( $post->ID );
	?>
	<article class="flow event <?php if ( $has_image ) : ?>premium<?php endif; ?>" data-tags="<?php echo $tags_string; ?>"  data-venues="<?php echo $locations_string; ?>">
	<?php if ( $has_image ) : ?>
		<div class="event-media__image">
			<?php echo get_the_post_thumbnail( $post->ID, 'large' ); ?>
		</div>
	<?php endif; ?>
		<div>
		<h2><?php echo esc_html( $post->post_title ); ?></h2>

		<p class="event-excerpt"><?php echo esc_html( $post->post_excerpt ); ?></p>

		<div class="meta">
			<?php if ( $event['venue'] ) : ?>
				<p class="event-venue">
					<span class="material-symbols-outlined">location_on</span>
					<?php echo esc_html( $event['venue'] ); ?>
				</p>
			<?php endif; ?>
	
			<?php if ( $event['recurring'] ) : ?>
				<p class="event-recurring"><span class="material-symbols-outlined">
					repeat
					</span>Repeats <?php echo esc_html( $event['recurring'] ); ?></p>
			<?php endif; ?>

			<p class="event-date">
				<span class="material-symbols-outlined">calendar_clock</span>
				<?php echo esc_html( $event['date_label'] ); ?>
				<?php if ( $event['date_label'] ) : ?>
					at <?php echo esc_html( date( 'g:i A', strtotime( $event['start_time'] ) ) ); ?>
					<?php if ( $end_date ) : ?>
						– <?php echo $end_date->format( 'l F j Y' ); ?>
					<?php endif; ?>
					<?php if ( $event['end_time']  ) : ?>
						<?php if($end_date){ echo 'at';}else{echo '-';}?> <?php echo esc_html( date( 'g:i A', strtotime( $event['end_time'] ) ) ); ?>
					<?php endif; ?>
				<?php endif; ?>
			</p>
		</div>

		<a class="event-link" href="<?php echo esc_url( get_permalink( $post->ID ) . '?date=' . $event['date'] ); ?>"><span>More details about<?php echo esc_html( $post->post_title ); ?></span></a>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

// ── AJAX endpoint ─────────────────────────────────────────────────────────────

function whitbyanchor_ajax_get_events(): void {
	check_ajax_referer( 'whitbyanchor_events', 'nonce' );

	$page      = max( 1, absint( $_POST['page']     ?? 1 ) );
	$per_page  = max( 1, absint( $_POST['per_page'] ?? WHITBYANCHOR_EVENTS_PER_PAGE ) );
	$tag       = sanitize_key( $_POST['tag']        ?? '' );
	$location  = sanitize_key( $_POST['location']   ?? '' );
	$date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
	$date_to   = sanitize_text_field( $_POST['date_to']   ?? '' );
	$search    = sanitize_text_field( $_POST['search']    ?? '' );

	$today = current_time( 'Y-m-d' );

	// Fetch without a from_date constraint so multi-day events that started
	// before today but haven't ended yet are included.
	$all_events = get_events( [ 'limit' => 1000 ] );

	// ── "Future or current" filter ────────────────────────────────────────────
	// Keep events whose effective end date (end_date for multi-day, start date
	// for single-day) is today or later.
	$all_events = array_values(
		array_filter( $all_events, function ( $event ) use ( $today ) {
			$effective_end = ! empty( $event['end_date'] ) ? $event['end_date'] : $event['date'];
			return $effective_end >= $today;
		} )
	);

	// ── Tag filter ────────────────────────────────────────────────────────────
	if ( $tag ) {
		$all_events = array_values(
			array_filter( $all_events, function ( $event ) use ( $tag ) {
				$terms = get_the_terms( $event['post']->ID, 'event_tag' );
				if ( ! $terms || is_wp_error( $terms ) ) return false;
				foreach ( $terms as $term ) {
					if ( $term->slug === $tag ) return true;
				}
				return false;
			} )
		);
	}

	// ── Location filter ───────────────────────────────────────────────────────
	if ( $location ) {
		$all_events = array_values(
			array_filter( $all_events, function ( $event ) use ( $location ) {
				$terms = get_the_terms( $event['post']->ID, 'event_location' );
				if ( ! $terms || is_wp_error( $terms ) ) return false;
				foreach ( $terms as $term ) {
					if ( $term->slug === $location ) return true;
				}
				return false;
			} )
		);
	}

	// ── Date from filter ──────────────────────────────────────────────────────
	// Keep events that are still running on or after date_from.
	// A multi-day event qualifies even if it started before date_from,
	// as long as its end date falls on or after it.
	if ( $date_from ) {
		$all_events = array_values(
			array_filter( $all_events, function ( $event ) use ( $date_from ) {
				$effective_end = ! empty( $event['end_date'] ) ? $event['end_date'] : $event['date'];
				return $effective_end >= $date_from;
			} )
		);
	}

	// ── Date to filter ────────────────────────────────────────────────────────
	// Keep events that have started by date_to (start date is on or before it).
	if ( $date_to ) {
		$all_events = array_values(
			array_filter( $all_events, fn( $event ) => $event['date'] <= $date_to )
		);
	}

	// ── Free-text search filter ───────────────────────────────────────────────
	// Matches against post title, excerpt, and content (case-insensitive).
	if ( $search ) {
		$search_lower = mb_strtolower( $search );

		$all_events = array_values(
			array_filter( $all_events, function ( $event ) use ( $search_lower ) {
				$post = $event['post'];

				$haystack = mb_strtolower(
					$post->post_title . ' ' .
					$post->post_excerpt . ' ' .
					wp_strip_all_tags( $post->post_content )
				);

				return str_contains( $haystack, $search_lower );
			} )
		);
	}

	// ── Paginate ──────────────────────────────────────────────────────────────
	$total       = count( $all_events );
	$offset      = ( $page - 1 ) * $per_page;
	$page_events = array_slice( $all_events, $offset, $per_page );

	// ── Render ────────────────────────────────────────────────────────────────
	$html = '';
	foreach ( $page_events as $event ) {
		$html .= whitbyanchor_render_event_article( $event );
	}

	wp_send_json_success( [
		'html'     => $html,
		'total'    => $total,
		'page'     => $page,
		'per_page' => $per_page,
		'has_more' => ( $offset + $per_page ) < $total,
	] );
}

add_action( 'wp_ajax_whitbyanchor_get_events',        'whitbyanchor_ajax_get_events' );
add_action( 'wp_ajax_nopriv_whitbyanchor_get_events', 'whitbyanchor_ajax_get_events' );