<?php
/**
 * Events CSV Importer — Admin Page
 * Add to your plugin or functions.php via:  require_once 'events-csv-importer.php';
 */

add_action( 'admin_menu', 'whitbyanchor_csv_importer_menu' );
function whitbyanchor_csv_importer_menu(): void {
	add_submenu_page(
		'edit.php?post_type=event',
		'Import Events via CSV',
		'Import CSV',
		'manage_options',
		'event-csv-import',
		'whitbyanchor_csv_importer_page'
	);
}


add_action( 'admin_post_whitbyanchor_import_events_csv', 'whitbyanchor_handle_csv_upload' );
function whitbyanchor_handle_csv_upload(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorised.' );
	}

	check_admin_referer( 'whitbyanchor_csv_import' );

	if ( empty( $_FILES['events_csv']['tmp_name'] ) ) {
		wp_redirect( add_query_arg( 'import_error', 'no_file', wp_get_referer() ) );
		exit;
	}

	$file = $_FILES['events_csv']['tmp_name'];
	$handle = fopen( $file, 'r' );

	if ( ! $handle ) {
		wp_redirect( add_query_arg( 'import_error', 'unreadable', wp_get_referer() ) );
		exit;
	}

	// Read header row.
	$headers = fgetcsv( $handle );
	if ( ! $headers ) {
		wp_redirect( add_query_arg( 'import_error', 'empty', wp_get_referer() ) );
		exit;
	}
	$headers = array_map( 'trim', $headers );

	$required_columns = [ 'post_title', 'start_date' ];
	foreach ( $required_columns as $col ) {
		if ( ! in_array( $col, $headers, true ) ) {
			wp_redirect( add_query_arg( 'import_error', 'missing_col_' . $col, wp_get_referer() ) );
			exit;
		}
	}

	$imported = 0;
	$skipped  = 0;

	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		// Map row values to header keys.
		$data = array_combine( $headers, array_pad( $row, count( $headers ), '' ) );
		$data = array_map( 'trim', $data );

		if ( empty( $data['post_title'] ) ) {
			$skipped++;
			continue;
		}

		$post_id = wp_insert_post( [
			'post_title'   => sanitize_text_field( $data['post_title'] ),
			'post_excerpt' => sanitize_text_field( $data['post_excerpt'] ?? '' ),
			'post_status'  => 'publish',
			'post_type'    => 'event',
		] );

		if ( is_wp_error( $post_id ) ) {
			$skipped++;
			continue;
		}

		$meta_map = [
			'start_date'  => '_event_start_date',
			'start_time'  => '_event_start_time',
			'end_date'    => '_event_end_date',
			'end_time'    => '_event_end_time',
			'recurring'   => '_event_recurring',
			'recur_rule'  => '_event_recur_rule',
			'recur_until' => '_event_recur_until',
			'venue'       => '_event_venue',
			'lat'         => '_event_lat',
			'lng'         => '_event_lng',
		];

		foreach ( $meta_map as $csv_col => $meta_key ) {
			if ( ! empty( $data[ $csv_col ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $data[ $csv_col ] ) );
			}
		}
		
		if ( ! empty( $data['event_tag'] ) ) {
			$term_name = sanitize_text_field( $data['event_tag'] );
			
			$term = term_exists( $term_name, 'event_tag' );
			if ( ! $term ) {
				$term = wp_insert_term( $term_name, 'event_tag' );
			}
			
			if ( ! is_wp_error( $term ) ) {
				wp_set_object_terms( $post_id, (int) $term['term_id'], 'event_tag' );
			}
		}
		
		if ( ! empty( $data['event_location'] ) ) {
			$term_name = sanitize_text_field( $data['event_location'] );
		
			$term = term_exists( $term_name, 'event_location' );
			if ( ! $term ) {
				$term = wp_insert_term( $term_name, 'event_location' );
			}
		
			if ( ! is_wp_error( $term ) ) {
				wp_set_object_terms( $post_id, (int) $term['term_id'], 'event_location' );
			}
		}

		$imported++;
	}

	fclose( $handle );

	wp_redirect( add_query_arg(
		[ 'import_success' => $imported, 'import_skipped' => $skipped ],
		wp_get_referer()
	) );
	exit;
}


function whitbyanchor_csv_importer_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Import Events via CSV</h1>

		<?php if ( isset( $_GET['import_success'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					Import complete: <strong><?php echo (int) $_GET['import_success']; ?> event(s) imported</strong>
					<?php if ( ! empty( $_GET['import_skipped'] ) ) : ?>
						, <?php echo (int) $_GET['import_skipped']; ?> row(s) skipped.
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( isset( $_GET['import_error'] ) ) : ?>
			<div class="notice notice-error is-dismissible">
				<p>Import failed: <code><?php echo esc_html( $_GET['import_error'] ); ?></code></p>
			</div>
		<?php endif; ?>

		<p>Upload a <code>.csv</code> file using the column format below. Only <strong>post_title</strong> and <strong>start_date</strong> are required.</p>

		<table class="widefat fixed striped" style="max-width:700px;margin-bottom:2em;">
			<thead>
				<tr>
					<th>Column</th>
					<th>Format / Notes</th>
				</tr>
			</thead>
			<tbody>
				<tr><td>post_title</td><td>Event name <em>(required)</em></td></tr>
				<tr><td>post_excerpt</td><td>Short description</td></tr>
				<tr><td>start_date</td><td>YYYY-MM-DD <em>(required)</em></td></tr>
				<tr><td>start_time</td><td>HH:MM (24-hour)</td></tr>
				<tr><td>end_date</td><td>YYYY-MM-DD</td></tr>
				<tr><td>end_time</td><td>HH:MM (24-hour)</td></tr>
				<tr><td>recurring</td><td>e.g. <code>weekly</code></td></tr>
				<tr><td>recur_rule</td><td>iCal RRULE string, e.g. <code>FREQ=WEEKLY;BYDAY=SA</code></td></tr>
				<tr><td>recur_until</td><td>YYYY-MM-DD</td></tr>
				<tr><td>venue</td><td>Venue name / address</td></tr>
				<tr><td>lat</td><td>Decimal latitude</td></tr>
				<tr><td>lng</td><td>Decimal longitude</td></tr>
				<tr><td>event_tag</td><td>Single tag slug, e.g. <code>music</code></td></tr>
				<tr><td>event_location</td><td>Town or village name, e.g. <code>Whitby</code></td></tr>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="whitbyanchor_import_events_csv">
			<?php wp_nonce_field( 'whitbyanchor_csv_import' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="events_csv">CSV File</label></th>
					<td>
						<input type="file" name="events_csv" id="events_csv" accept=".csv" required>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Import Events' ); ?>
		</form>
	</div>
	<?php
}

add_action( 'wp_dashboard_setup', 'whitbyanchor_register_csv_dashboard_widget' );
function whitbyanchor_register_csv_dashboard_widget(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_add_dashboard_widget(
		'whitbyanchor_event_csv_import',
		'Import Events via CSV',
		'whitbyanchor_csv_dashboard_widget_render'
	);
}

function whitbyanchor_csv_dashboard_widget_render(): void {
	?>
	<?php if ( isset( $_GET['import_success'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				Import complete: <strong><?php echo (int) $_GET['import_success']; ?> event(s) imported</strong>
				<?php if ( ! empty( $_GET['import_skipped'] ) ) : ?>
					, <?php echo (int) $_GET['import_skipped']; ?> row(s) skipped.
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['import_error'] ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p>Import failed: <code><?php echo esc_html( $_GET['import_error'] ); ?></code></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
		<input type="hidden" name="action" value="whitbyanchor_import_events_csv">
		<?php wp_nonce_field( 'whitbyanchor_csv_import' ); ?>
		<p>
			<label for="events_csv_dash"><strong>CSV File</strong></label><br>
			<input type="file" name="events_csv" id="events_csv_dash" accept=".csv" required style="margin-top:4px;">
		</p>
		<?php submit_button( 'Import Events', 'primary', 'submit', false ); ?>
	</form>
	<?php
}