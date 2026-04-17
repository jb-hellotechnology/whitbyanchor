<?php
/**
 * Past Events Dashboard Widget
 *
 * Flags events that are considered "in the past" under any of these conditions:
 *   1. Start date is in the past, no end date, and no repeat_until date.
 *   2. Start date exists and end date is in the past.
 *   3. Start date exists and repeat_until date is in the past.
 *
 * ASSUMPTIONS — adjust these constants to match your setup:
 *   PAST_EVENTS_CPT               – post type slug            (default: 'events')
 *   PAST_EVENTS_START_DATE_META   – meta key for start date   (default: '_event_start_date')
 *   PAST_EVENTS_END_DATE_META     – meta key for end date     (default: '_event_end_date')
 *   PAST_EVENTS_REPEAT_UNTIL_META – meta key for repeat until (default: '_event_repeat_until')
 *
 * Drop this file into your theme's functions.php via require_once, or place it
 * inside a site-specific plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Configuration ────────────────────────────────────────────────────────────

define( 'PAST_EVENTS_CPT',               'event'              );  // Your CPT slug
define( 'PAST_EVENTS_START_DATE_META',   '_event_start_date'   );  // Meta key for the start date
define( 'PAST_EVENTS_END_DATE_META',     '_event_end_date'     );  // Meta key for the end date
define( 'PAST_EVENTS_REPEAT_UNTIL_META', '_event_recur_until' );  // Meta key for the repeat until date
define( 'PAST_EVENTS_PER_PAGE',          20                    );  // Max rows shown in the widget

// ─── Register the widget ──────────────────────────────────────────────────────

add_action( 'wp_dashboard_setup', 'past_events_register_widget' );

function past_events_register_widget() {
	wp_add_dashboard_widget(
		'past_events_widget',
		'Expired Events',
		'past_events_widget_render',
		'past_events_widget_configure'   // optional config callback
	);

	// Move widget to the top of the "normal" column so it is hard to miss.
	global $wp_meta_boxes;
	$widget = $wp_meta_boxes['dashboard']['normal']['core']['past_events_widget'] ?? null;
	if ( $widget ) {
		unset( $wp_meta_boxes['dashboard']['normal']['core']['past_events_widget'] );
		array_unshift( $wp_meta_boxes['dashboard']['normal']['core'], $widget );  // prepend
	}
}

// ─── Query helpers ────────────────────────────────────────────────────────────

/**
 * Returns WP_Query args matching any of the three expired conditions:
 *   (A) Start date in the past, end date absent/empty, repeat_until absent/empty.
 *   (B) Start date present and end date is in the past.
 *   (C) Start date present and repeat_until date is in the past.
 */
function past_events_get_query_args( int $limit = PAST_EVENTS_PER_PAGE ): array {
	$today = current_time( 'Y-m-d' ); // Uses site timezone

	// Reusable sub-clause: field is absent or stored as an empty string.
	$absent = fn( string $key ) => [
		'relation' => 'OR',
		[ 'key' => $key, 'compare' => 'NOT EXISTS' ],
		[ 'key' => $key, 'value'   => '', 'compare' => '=' ],
	];

	// Reusable sub-clause: field exists and is before today.
	$expired = fn( string $key ) => [
		'key'     => $key,
		'value'   => $today,
		'compare' => '<',
		'type'    => 'DATE',
	];

	return [
		'post_type'      => PAST_EVENTS_CPT,
		'post_status'    => [ 'publish', 'draft', 'pending', 'future' ],
		'posts_per_page' => $limit,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => [
			'relation' => 'OR',

			// (A) Start in past, no end date, no repeat_until
			'start_only_past' => [
				'relation' => 'AND',
				$expired( PAST_EVENTS_START_DATE_META ),
				$absent( PAST_EVENTS_END_DATE_META ),
				$absent( PAST_EVENTS_REPEAT_UNTIL_META ),
			],

			// (B) End date is in the past
			'end_date_past' => $expired( PAST_EVENTS_END_DATE_META ),

			// (C) Repeat until date is in the past
			'repeat_until_past' => $expired( PAST_EVENTS_REPEAT_UNTIL_META ),
		],
	];
}

// ─── Inline CSS (scoped to this widget) ───────────────────────────────────────

function past_events_enqueue_styles() {
	// Only load on the dashboard page
	$screen = get_current_screen();
	if ( ! $screen || $screen->id !== 'dashboard' ) {
		return;
	}
	?>
	<style>
		#past_events_widget .inside { padding: 0; }

		.pew-summary {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 12px 16px;
			background: #fff3cd;
			border-left: 4px solid #e6a817;
			font-size: 13px;
			color: #6d4c00;
		}
		.pew-summary .pew-count {
			font-size: 22px;
			font-weight: 700;
			color: #c0392b;
			line-height: 1;
		}
		.pew-summary .pew-label { line-height: 1.3; }

		.pew-none {
			padding: 16px;
			color: #3c763d;
			background: #dff0d8;
			border-left: 4px solid #3c763d;
			font-size: 13px;
		}

		.pew-table-wrap { overflow-x: auto; }

		.pew-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 12px;
		}
		.pew-table th {
			background: #f5f5f5;
			border-bottom: 2px solid #ddd;
			padding: 8px 12px;
			text-align: left;
			font-weight: 600;
			color: #444;
			white-space: nowrap;
		}
		.pew-table td {
			padding: 8px 12px;
			border-bottom: 1px solid #eee;
			vertical-align: middle;
		}
		.pew-table tr:last-child td { border-bottom: none; }
		.pew-table tr:hover td { background: #fafafa; }

		.pew-status {
			display: inline-block;
			padding: 2px 7px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: .4px;
		}
		.pew-status-publish { background: #d4edda; color: #155724; }
		.pew-status-draft   { background: #e2e3e5; color: #383d41; }
		.pew-status-pending { background: #fff3cd; color: #856404; }
		.pew-status-future  { background: #cce5ff; color: #004085; }

		.pew-expired-days {
			font-weight: 700;
			color: #c0392b;
		}

		.pew-reason {
			display: inline-block;
			padding: 2px 7px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			white-space: nowrap;
		}
		.pew-reason-end    { background: #f8d7da; color: #721c24; }
		.pew-reason-repeat { background: #fce5cd; color: #7d3000; }
		.pew-reason-noend  { background: #e2d9f3; color: #3d1a78; }

		.pew-footer {
			padding: 10px 16px;
			border-top: 1px solid #eee;
			font-size: 12px;
			color: #777;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.pew-footer a { color: #0073aa; text-decoration: none; }
		.pew-footer a:hover { text-decoration: underline; }
	</style>
	<?php
}
add_action( 'admin_head', 'past_events_enqueue_styles' );

// ─── Widget render ────────────────────────────────────────────────────────────

function past_events_widget_render() {
	$query = new WP_Query( past_events_get_query_args() );
	$today = strtotime( current_time( 'Y-m-d' ) );

	if ( ! $query->have_posts() ) {
		echo '<div class="pew-none">✅ No events with an expired end date — everything looks current!</div>';
		wp_reset_postdata();
		return;
	}

	$total       = $query->found_posts;  // real total (may exceed the per-page cap)
	$showing     = count( $query->posts );
	$post_type   = get_post_type_object( PAST_EVENTS_CPT );
	$edit_url    = admin_url( 'edit.php?post_type=' . PAST_EVENTS_CPT );

	// ── Summary banner ──────────────────────────────────────────────────────
	echo '<div class="pew-summary">';
	echo '<span class="pew-count">' . esc_html( $total ) . '</span>';
	echo '<span class="pew-label">event' . ( $total !== 1 ? 's have' : ' has' ) . ' <strong>passed</strong> and may need updating or unpublishing.</span>';
	echo '</div>';

	// ── Table ────────────────────────────────────────────────────────────────
	echo '<div class="pew-table-wrap"><table class="pew-table">';
	echo '<thead><tr>';
	echo '<th>Event Title</th>';
	echo '<th>Start Date</th>';
	echo '<th>End Date</th>';
	echo '<th>Repeat Until</th>';
	// echo '<th>Expired</th>';
	// echo '<th>Reason</th>';
	// echo '<th>Status</th>';
	//echo '<th>Actions</th>';
	echo '</tr></thead><tbody>';

	while ( $query->have_posts() ) {
		$query->the_post();
		$post_id      = get_the_ID();
		$title        = get_the_title();
		$status       = get_post_status();
		$start_raw    = get_post_meta( $post_id, PAST_EVENTS_START_DATE_META,   true );
		$end_raw      = get_post_meta( $post_id, PAST_EVENTS_END_DATE_META,     true );
		$repeat_raw   = get_post_meta( $post_id, PAST_EVENTS_REPEAT_UNTIL_META, true );

		$start_ts     = $start_raw  ? strtotime( $start_raw )  : false;
		$end_ts       = $end_raw    ? strtotime( $end_raw )    : false;
		$repeat_ts    = $repeat_raw ? strtotime( $repeat_raw ) : false;

		$fmt = get_option( 'date_format' );
		$start_display  = $start_ts  ? date_i18n( $fmt, $start_ts )  : '—';
		$end_display    = $end_ts    ? date_i18n( $fmt, $end_ts )    : '—';
		$repeat_display = $repeat_ts ? date_i18n( $fmt, $repeat_ts ) : '—';

		// Determine which condition applies and the reference date for "X days ago".
		// Priority: repeat_until > end_date > start_date (most specific wins).
		if ( $repeat_ts && $repeat_ts < $today ) {
			$reason       = 'Repeat until passed';
			$reason_class = 'pew-reason-repeat';
			$reference_ts = $repeat_ts;
		} elseif ( $end_ts && $end_ts < $today ) {
			$reason       = 'End date passed';
			$reason_class = 'pew-reason-end';
			$reference_ts = $end_ts;
		} else {
			$reason       = 'No end / repeat date';
			$reason_class = 'pew-reason-noend';
			$reference_ts = $start_ts;
		}

		$days_ago  = $reference_ts ? (int) floor( ( $today - $reference_ts ) / DAY_IN_SECONDS ) : null;
		$edit_link = get_edit_post_link( $post_id );
		$view_link = get_permalink( $post_id );

		echo '<tr>';

		echo '<td><a href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a></td>';
		echo '<td>' . esc_html( $start_display )  . '</td>';
		echo '<td>' . esc_html( $end_display )    . '</td>';
		echo '<td>' . esc_html( $repeat_display ) . '</td>';

// 		echo '<td><span class="pew-expired-days">';
// 		echo $days_ago !== null ? esc_html( $days_ago ) . 'd ago' : '—';
// 		echo '</span></td>';
// 
// 		echo '<td><span class="pew-reason ' . esc_attr( $reason_class ) . '">' . esc_html( $reason ) . '</span></td>';
// 
// 		echo '<td><span class="pew-status pew-status-' . esc_attr( $status ) . '">' . esc_html( ucfirst( $status ) ) . '</span></td>';

		// echo '<td>';
		// echo '<a href="' . esc_url( $edit_link ) . '">Edit</a>';
		// if ( $view_link && $status === 'publish' ) {
		// 	echo ' &middot; <a href="' . esc_url( $view_link ) . '" target="_blank">View</a>';
		// }
		// echo '</td>';

		echo '</tr>';
	}

	echo '</tbody></table></div>';

	// ── Footer ───────────────────────────────────────────────────────────────
	echo '<div class="pew-footer">';
	if ( $showing < $total ) {
		echo '<span>Showing ' . esc_html( $showing ) . ' of ' . esc_html( $total ) . ' expired events.</span>';
	} else {
		echo '<span>Showing all ' . esc_html( $total ) . ' expired event' . ( $total !== 1 ? 's' : '' ) . '.</span>';
	}
	echo '<a href="' . esc_url( $edit_url ) . '">View all ' . esc_html( $post_type ? $post_type->labels->name : 'Events' ) . ' →</a>';
	echo '</div>';

	wp_reset_postdata();
}

// ─── Optional: widget configuration (saved per-user in usermeta) ──────────────

function past_events_widget_configure() {
	$user_id = get_current_user_id();
	$saved   = get_user_meta( $user_id, 'past_events_widget_limit', true );
	$limit   = $saved ? (int) $saved : PAST_EVENTS_PER_PAGE;

	if ( isset( $_POST['past_events_widget_limit'] ) ) {
		$limit = absint( $_POST['past_events_widget_limit'] );
		$limit = max( 5, min( 100, $limit ) );  // clamp 5–100
		update_user_meta( $user_id, 'past_events_widget_limit', $limit );
	}
	?>
	<p>
		<label for="past_events_widget_limit"><strong>Max rows to display:</strong></label><br>
		<input
			type="number"
			id="past_events_widget_limit"
			name="past_events_widget_limit"
			value="<?php echo esc_attr( $limit ); ?>"
			min="5" max="100" step="5"
			style="width:80px;"
		>
	</p>
	<?php
}