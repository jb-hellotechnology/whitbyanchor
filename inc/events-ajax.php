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

	ob_start();
	
	if ( ! empty( $event['end_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $event['end_date'] ) ) {
		$end_date = new DateTime( $event['end_date'] );
	}

	?>
	<article class="flow event" data-tags="<?php echo $tags_string; ?>">
		<h2><?php echo esc_html( $post->post_title ); ?></h2>

		<p class="event-excerpt"><?php echo esc_html( $post->post_excerpt ); ?></p>

		<div>
			<?php if ( $event['venue'] ) : ?>
				<p class="event-venue">
					<span class="material-symbols-outlined">location_on</span>
					<?php echo esc_html( $event['venue'] ); ?>
				</p>
			<?php endif; ?>
	
			<?php if ( $event['recurring'] ) : ?>
				<p class="event-recurring">Repeats <?php echo esc_html( $event['recurring'] ); ?></p>
			<?php endif; ?>

			<p class="event-date">
				<span class="material-symbols-outlined">calendar_clock</span>
				<?php echo esc_html( $event['date_label'] ); ?>
				<?php if ( $event['date_label'] ) : ?>
					at <?php echo esc_html( date( 'g:i A', strtotime( $event['start_time'] ) ) ); ?>
					<?php if ( $end_date ) : ?>
						– <?php echo $end_date->format( 'D jS F Y' ); ?>
					<?php endif; ?>
					<?php if ( $event['end_time']  ) : ?>
						<?php if($end_date){ echo 'at';}else{echo '-';}?> <?php echo esc_html( date( 'g:i A', strtotime( $event['end_time'] ) ) ); ?>
					<?php endif; ?>
				<?php endif; ?>
			</p>
		</div>

		<a class="event-link" href="<?php echo esc_url( get_permalink( $post->ID ) . '?date=' . $event['date'] ); ?>"><span>More details about<?php echo esc_html( $post->post_title ); ?></span></a>
	</article>
	<?php
	return ob_get_clean();
}

// ── AJAX endpoint ─────────────────────────────────────────────────────────────

function whitbyanchor_ajax_get_events(): void {
	check_ajax_referer( 'whitbyanchor_events', 'nonce' );

	$page     = max( 1, absint( $_POST['page']     ?? 1 ) );
	$per_page = max( 1, absint( $_POST['per_page'] ?? WHITBYANCHOR_EVENTS_PER_PAGE ) );
	$tag      = sanitize_key( $_POST['tag'] ?? '' );

	// Fetch all future events. If get_events() grows tag/offset support later,
	// pass those args here instead of filtering in PHP below.
	$all_events = get_events( [
		'from_date' => current_time( 'Y-m-d' ),
		'limit'     => 1000,
	] );

	// ── Tag filter ───────────────────────────────────────────────────────────
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

	// ── Paginate ─────────────────────────────────────────────────────────────
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