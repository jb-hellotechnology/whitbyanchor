<?php
/**
 * whitbyanchor functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package whitbyanchor
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.'.rand() );
}

require_once get_template_directory() . '/inc/photo-gallery-cpt.php';
require_once get_template_directory() . '/inc/events-ajax.php';
require_once get_template_directory() . '/inc/events-widget.php';

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function whitbyanchor_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on whitbyanchor, use a find and replace
		* to change 'whitbyanchor' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'whitbyanchor', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'whitbyanchor' ),
			'menu-2' => esc_html__( 'Secondary', 'whitbyanchor' ),
			'menu-3' => esc_html__( 'About Us', 'whitbyanchor' ),
			'menu-4' => esc_html__( 'Contact', 'whitbyanchor' ),
			'menu-5' => esc_html__( 'Links', 'whitbyanchor' ),
			'menu-6' => esc_html__( 'Mobile Menu', 'whitbyanchor' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'whitbyanchor_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'whitbyanchor_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function whitbyanchor_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'whitbyanchor_content_width', 640 );
}
add_action( 'after_setup_theme', 'whitbyanchor_content_width', 0 );

add_filter('post_class','whitby_anchor_classes');
function whitby_anchor_classes( $classes ) {
  $classes[] = 'flow';
  return $classes;
}

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function whitbyanchor_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'whitbyanchor' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'whitbyanchor' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'whitbyanchor_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function whitbyanchor_scripts() {
	wp_enqueue_style( 'whitbyanchor-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'whitbyanchor-style', 'rtl', 'replace' );

	wp_enqueue_script( 'whitbyanchor-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );
	wp_enqueue_script( 'whitbyanchor-custom-js', get_template_directory_uri() . '/js/scripts.js', array(), _S_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'whitbyanchor_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

/**
 * Sunrise/Sunset Helper for WordPress
 * Add this to your theme's functions.php file
 */

define( 'SRS_LAT',       '54.486336' );
define( 'SRS_LNG',       '-0.613347' );
define( 'SRS_CACHE_KEY', 'srs_sunrise_sunset_data' );
define( 'SRS_API_URL',   'https://api.sunrise-sunset.org/json' );

function get_sunrise_sunset(): array {
	$cache = get_option( SRS_CACHE_KEY, [] );
	$today = current_time( 'Y-m-d' );

	if ( ! empty( $cache['date'] ) && $cache['date'] === $today && ! empty( $cache['data'] ) ) {
		return $cache['data'];
	}

	$url = add_query_arg(
		[ 'lat' => SRS_LAT, 'lng' => SRS_LNG, 'date' => $today, 'formatted' => 0 ],
		SRS_API_URL
	);

	$response = wp_remote_get( $url, [ 'timeout' => 10 ] );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return $cache['data'] ?? [ 'sunrise' => null, 'sunset' => null ];
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( empty( $body['results'] ) || $body['status'] !== 'OK' ) {
		return $cache['data'] ?? [ 'sunrise' => null, 'sunset' => null ];
	}

	$tz   = wp_timezone();
	$data = [];

	foreach ( [ 'sunrise', 'sunset' ] as $key ) {
		try {
			$dt          = new DateTime( $body['results'][ $key ], new DateTimeZone( 'UTC' ) );
			$dt->setTimezone( $tz );
			$data[ $key ] = $dt->format( 'g:i A' );
		} catch ( Exception $e ) {
			$data[ $key ] = null;
		}
	}

	update_option( SRS_CACHE_KEY, [ 'date' => $today, 'data' => $data ], false );

	return $data;
}

define( 'TIDES_LAT',       '54.486336' );
define( 'TIDES_LON',       '-0.613347' );
define( 'TIDES_API_KEY',   'cec0c29e-934b-425d-acf2-e397fb24b764' );
define( 'TIDES_CACHE_KEY', 'tides_upcoming_extremes' );
define( 'TIDES_API_URL',   'https://www.worldtides.info/api/v3' );
define( 'TIDES_CACHE_MIN', 2 ); // refetch when fewer than this many tides remain

/**
 * Fetches 2 days of tide extremes and caches the next 4.
 * Refreshes automatically when fewer than TIDES_CACHE_MIN tides remain in the future.
 */
function get_upcoming_tides_cache(): array {
	$cache = get_option( TIDES_CACHE_KEY, [] );
	$now   = time();

	// Count how many cached tides are still in the future
	$remaining = array_filter(
		$cache['data'] ?? [],
		fn( $tide ) => $tide['timestamp'] > $now
	);

	// Use the cache if we still have enough upcoming tides
	if ( count( $remaining ) >= TIDES_CACHE_MIN ) {
		return $cache['data'];
	}

	// Otherwise fetch fresh data — 2 days to ensure we always have future tides
	$url = add_query_arg(
		[
			'extremes'  => '',
			'date'      => 'today',
			'days'      => 2,
			'localtime' => '',
			'lat'       => TIDES_LAT,
			'lon'       => TIDES_LON,
			'key'       => TIDES_API_KEY,
		],
		TIDES_API_URL
	);

	$response = wp_remote_get( $url, [ 'timeout' => 10 ] );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		// API failed — return whatever we have cached, even if stale
		return $cache['data'] ?? [];
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( empty( $body['extremes'] ) || $body['status'] !== 200 ) {
		return $cache['data'] ?? [];
	}

	$tz   = wp_timezone();
	$data = [];

	foreach ( $body['extremes'] as $extreme ) {
		try {
			$dt = new DateTime( $extreme['date'] );
			$dt->setTimezone( $tz );
			$data[] = [
				'type'      => strtolower( $extreme['type'] ),
				'time'      => $dt->format( 'g:i A' ),
				'timestamp' => $dt->getTimestamp(),
				'height'    => round( $extreme['height'], 2 ),
			];
		} catch ( Exception $e ) {
			// Skip malformed entry
		}
	}

	// Sort chronologically and keep only the next 4 from now
	usort( $data, fn( $a, $b ) => $a['timestamp'] <=> $b['timestamp'] );

	$next_four = array_values(
		array_slice(
			array_filter( $data, fn( $tide ) => $tide['timestamp'] > $now ),
			0,
			4
		)
	);

	update_option( TIDES_CACHE_KEY, [ 'fetched' => $now, 'data' => $next_four ], false );

	return $next_four;
}

/**
 * Returns the next N tides from the current time.
 */
function get_next_tides( int $count = 2 ): array {
	$all = get_upcoming_tides_cache();
	$now = time();

	$upcoming = array_values(
		array_filter( $all, fn( $tide ) => $tide['timestamp'] > $now )
	);

	return array_slice( $upcoming, 0, $count );
}

add_action( 'wp_head', function() {
	if ( ! current_user_can( 'administrator' ) ) return;
	echo '<!-- ';
	echo 'is_post_type_archive: ' . ( is_post_type_archive('event') ? 'YES' : 'NO' ) . ' | ';
	echo 'is_category: ' . ( is_category() ? 'YES' : 'NO' );
	echo ' -->';
});

// Events
function register_event_post_type() {
	register_post_type( 'event', [
		'labels' => [
			'name'          => 'Events',
			'singular_name' => 'Event',
			'add_new_item'  => 'Add New Event',
			'edit_item'     => 'Edit Event',
		],
		'public'       => true,
		'has_archive'  => 'whats-on',
		'menu_icon'    => 'dashicons-calendar-alt',
		'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
		'rewrite' => [ 'slug' => 'whats-on' ],
	] );
}
add_action( 'init', 'register_event_post_type' );

// Location taxonomy (town/village)
function register_event_taxonomies() {
	register_taxonomy( 'event_location', 'event', [
		'labels' => [
			'name'          => 'Locations',
			'singular_name' => 'Location',
			'add_new_item'  => 'Add New Location',
		],
		'hierarchical' => false,
		'public'       => true,
		'rewrite'      => [
			'slug'         => 'event-location'
		],
	] );

	// Tags (non-hierarchical)
	register_taxonomy( 'event_tag', 'event', [
		'labels' => [
			'name'          => 'Event Tags',
			'singular_name' => 'Event Tag',
		],
		'hierarchical' => false,
		'public'       => true,
		'rewrite'      => [ 'slug' => 'event-tag' ],
	] );
}
add_action( 'init', 'register_event_taxonomies' );

// Add meta box to the event editor
function event_meta_boxes() {
	add_meta_box(
		'event_details',
		'Event Details',
		'event_meta_box_html',
		'event',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'event_meta_boxes' );

function event_meta_box_html( $post ) {
	wp_nonce_field( 'event_meta_save', 'event_meta_nonce' );

	$start_date  = get_post_meta( $post->ID, '_event_start_date', true );
	$start_time  = get_post_meta( $post->ID, '_event_start_time', true );
	$end_date 	 = get_post_meta( $post->ID, '_event_end_date', true );
	$end_time    = get_post_meta( $post->ID, '_event_end_time', true );
	$recurring   = get_post_meta( $post->ID, '_event_recurring', true );
	$recur_rule  = get_post_meta( $post->ID, '_event_recur_rule', true );
	$recur_until = get_post_meta( $post->ID, '_event_recur_until', true );
	$venue       = get_post_meta( $post->ID, '_event_venue', true );
	$lat 		 = get_post_meta( $post->ID, '_event_lat', true );
	$lng 		 = get_post_meta( $post->ID, '_event_lng', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="event_start_date">Date</label></th>
			<td><input type="date" id="event_start_date" name="event_start_date" value="<?php echo esc_attr( $start_date ); ?>"></td>
		</tr>
		<tr>
			<th><label for="event_start_time">Start Time</label></th>
			<td><input type="time" id="event_start_time" name="event_start_time" value="<?php echo esc_attr( $start_time ); ?>"></td>
		</tr>
		<tr>
			<th><label for="event_end_date">End Date</label></th>
			<td><input type="date" id="event_end_date" name="event_end_date" value="<?php echo esc_attr( $end_date ); ?>"></td>
		</tr>
		<tr>
			<th><label for="event_end_time">End Time</label></th>
			<td><input type="time" id="event_end_time" name="event_end_time" value="<?php echo esc_attr( $end_time ); ?>"></td>
		</tr>
		<?php
		$excluded = get_post_meta( $post->ID, '_event_excluded_dates', true );
		$excluded = $excluded ? json_decode( $excluded, true ) : [];
		?>
		<tr id="event-excluded-dates-row">
			<th><label>Excluded Dates</label></th>
			<td>
				<div id="event-excluded-dates">
					<?php foreach ( $excluded as $date ) : ?>
						<div class="excluded-date-entry">
							<input type="date" name="event_excluded_dates[]" value="<?php echo esc_attr( $date ); ?>">
							<button type="button" class="button remove-excluded-date">Remove</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button" id="add-excluded-date">Add Date</button>
				<p class="description">Dates on which this recurring event does not occur.</p>
			</td>
		</tr>
		<tr>
			<th><label for="event_venue">Venue Name</label></th>
			<td><input type="text" id="event_venue" name="event_venue" value="<?php echo esc_attr( $venue ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th><label for="event_recurring">Recurring</label></th>
			<td>
				<select id="event_recurring" name="event_recurring">
					<option value=""      <?php selected( $recurring, '' ); ?>>One-off</option>
					<option value="weekly"  <?php selected( $recurring, 'weekly' ); ?>>Weekly</option>
					<option value="biweekly" <?php selected( $recurring, 'biweekly' ); ?>>Fortnightly</option>
					<option value="monthly" <?php selected( $recurring, 'monthly' ); ?>>Monthly</option>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="event_recur_until">Repeat Until</label></th>
			<td><input type="date" id="event_recur_until" name="event_recur_until" value="<?php echo esc_attr( $recur_until ); ?>"></td>
		</tr>
		<tr>
			<th><label>Map Pin</label></th>
			<td>
				<div id="event-map-picker" style="height:300px; border:1px solid #ccc;"></div>
				<p class="description">Click the map to drop a pin. Drag the pin to adjust.</p>
			</td>
		</tr>
		<tr>
			<th><label for="event_lat">Latitude</label></th>
			<td><input type="text" id="event_lat" name="event_lat" value="<?php echo esc_attr( $lat ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th><label for="event_lng">Longitude</label></th>
			<td><input type="text" id="event_lng" name="event_lng" value="<?php echo esc_attr( $lng ); ?>" class="regular-text"></td>
		</tr>
	</table>
	<script>
	document.getElementById('add-excluded-date').addEventListener('click', function () {
		var div = document.createElement('div');
		div.className = 'excluded-date-entry';
		div.innerHTML = '<input type="date" name="event_excluded_dates[]"> '
					  + '<button type="button" class="button remove-excluded-date">Remove</button>';
		document.getElementById('event-excluded-dates').appendChild(div);
	});
	
	document.getElementById('event-excluded-dates').addEventListener('click', function (e) {
		if ( e.target.classList.contains('remove-excluded-date') ) {
			e.target.closest('.excluded-date-entry').remove();
		}
	});
	</script>
	<?php
}

// Save meta fields
function event_meta_save( $post_id ) {
	if ( ! isset( $_POST['event_meta_nonce'] ) || ! wp_verify_nonce( $_POST['event_meta_nonce'], 'event_meta_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$fields = [
		'_event_start_date'  => 'event_start_date',
		'_event_start_time'  => 'event_start_time',
		'_event_end_time'    => 'event_end_time',
		'_event_end_date'    => 'event_end_date',
		'_event_venue'       => 'event_venue',
		'_event_recurring'   => 'event_recurring',
		'_event_recur_until' => 'event_recur_until',
		'_event_lat'         => 'event_lat',
		'_event_lng'         => 'event_lng',
	];

	foreach ( $fields as $meta_key => $post_key ) {
		if ( isset( $_POST[ $post_key ] ) ) {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
		}
	}
	
	foreach ( [ '_event_lat' => 'event_lat', '_event_lng' => 'event_lng' ] as $meta_key => $post_key ) {
		if ( isset( $_POST[ $post_key ] ) ) {
			$val = floatval( $_POST[ $post_key ] );
			if ( $val !== 0.0 ) {
				update_post_meta( $post_id, $meta_key, $val );
			}
		}
	}
	
	$excluded_dates = [];
	if ( ! empty( $_POST['event_excluded_dates'] ) && is_array( $_POST['event_excluded_dates'] ) ) {
		foreach ( $_POST['event_excluded_dates'] as $date ) {
			$date = sanitize_text_field( $date );
			// Validate it's a real YYYY-MM-DD date before storing
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
				$excluded_dates[] = $date;
			}
		}
	}
	update_post_meta( $post_id, '_event_excluded_dates', wp_json_encode( $excluded_dates ) );
}
add_action( 'save_post_event', 'event_meta_save' );

function get_events( $args = [] ) {
	$defaults = [
		'location'  => '',
		'tag'       => '',
		'from_date' => current_time( 'Y-m-d' ),
		'to_date'   => '',   // add this
		'limit'     => 10,
	];
	$args = wp_parse_args( $args, $defaults );

	$query_args = [
		'post_type'      => 'event',
		'posts_per_page' => -1, // fetch all, we'll filter by date manually
		'meta_key'       => '_event_start_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'tax_query'      => [],
	];

	if ( $args['location'] ) {
		$query_args['tax_query'][] = [
			'taxonomy' => 'event_location',
			'field'    => 'slug',
			'terms'    => $args['location'],
		];
	}

	if ( $args['tag'] ) {
		$query_args['tax_query'][] = [
			'taxonomy' => 'event_tag',
			'field'    => 'slug',
			'terms'    => $args['tag'],
		];
	}
	
	$from         = new DateTime( $args['from_date'] );
	$until_filter = $args['to_date'] ? new DateTime( $args['to_date'] ) : null;

	$posts  = get_posts( $query_args );
	
	$events = [];

	foreach ( $posts as $post ) {
		$start_date  = get_post_meta( $post->ID, '_event_start_date', true );
		$start_time  = get_post_meta( $post->ID, '_event_start_time', true );
		$end_date    = get_post_meta( $post->ID, '_event_end_date', true );
		$end_time    = get_post_meta( $post->ID, '_event_end_time', true );
		$venue       = get_post_meta( $post->ID, '_event_venue', true );
		$recurring   = get_post_meta( $post->ID, '_event_recurring', true );
		$recur_until = get_post_meta( $post->ID, '_event_recur_until', true );
		
		$excluded_raw    = get_post_meta( $post->ID, '_event_excluded_dates', true );
		$excluded_dates  = $excluded_raw ? json_decode( $excluded_raw, true ) : [];

		if ( ! $start_date ) continue;

		$intervals = [
			'weekly'    => '+1 week',
			'biweekly'  => '+2 weeks',
			'monthly'   => '+1 month',
		];

		$current = new DateTime( $start_date );
		$until   = $recur_until ? new DateTime( $recur_until ) : new DateTime( '+1 year' );

		// For one-off events, just check the single date
		if ( ! $recurring ) {
			// One-off
			if ( $current >= $from
				&& ( ! $until_filter || $current <= $until_filter )
				&& ! in_array( $current->format( 'Y-m-d' ), $excluded_dates, true ) ) {
				$events[] = [
					'post'       => $post,
					'date'       => $current->format( 'Y-m-d' ),
					'date_label' => $current->format( 'l F j Y' ),
					'start_time' => $start_time,
					'end_time'   => $end_time,
					'end_date'   => $end_date,
					'venue'      => $venue,
					'recurring'  => $recurring, // ← missing from both branches
				];
			}
			continue;
		}

		// For recurring events, walk through each occurrence
		while ( $current <= $until ) {
			$date_str = $current->format( 'Y-m-d' );
			// Recurring
			if ( $current >= $from
				&& ( ! $until_filter || $current <= $until_filter )
				&& ! in_array( $date_str, $excluded_dates, true ) ) {
				$events[] = [
					'post'       => $post,
					'date'       => $current->format( 'Y-m-d' ),
					'date_label' => $current->format( 'l F j Y' ),
					'start_time' => $start_time,
					'end_time'   => $end_time,
					'end_date'   => $end_date,
					'venue'      => $venue,
					'recurring'  => $recurring, // ← missing from both branches
				];
			}
			$current->modify( $intervals[ $recurring ] );
		}
	}

	// Sort all expanded events by date
	usort( $events, fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );

	return array_slice( $events, 0, $args['limit'] );
}

function event_admin_scripts( $hook ) {
	global $post;

	// Only load on the event editor screen
	if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
	if ( ! $post || $post->post_type !== 'event' ) return;

	wp_enqueue_style(
		'leaflet',
		'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
		[],
		'1.9.4'
	);
	wp_enqueue_script(
		'leaflet',
		'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
		[],
		'1.9.4',
		true
	);
	wp_add_inline_script( 'leaflet', event_map_picker_js(), 'after' );
}
add_action( 'admin_enqueue_scripts', 'event_admin_scripts' );

function event_map_picker_js() {
	return <<<JS
	document.addEventListener('DOMContentLoaded', function () {
		var latInput = document.getElementById('event_lat');
		var lngInput = document.getElementById('event_lng');

		var initLat = parseFloat(latInput.value) || 54.4;
		var initLng = parseFloat(lngInput.value) || -0.6;
		var initZoom = latInput.value ? 15 : 9;

		var map = L.map('event-map-picker').setView([initLat, initLng], initZoom);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '© OpenStreetMap contributors'
		}).addTo(map);

		var marker = null;

		// Restore existing pin if lat/lng already saved
		if (latInput.value && lngInput.value) {
			marker = L.marker([initLat, initLng], { draggable: true }).addTo(map);
			bindMarkerDrag(marker);
		}

		map.on('click', function (e) {
			var lat = e.latlng.lat.toFixed(6);
			var lng = e.latlng.lng.toFixed(6);

			if (marker) {
				marker.setLatLng(e.latlng);
			} else {
				marker = L.marker(e.latlng, { draggable: true }).addTo(map);
				bindMarkerDrag(marker);
			}

			latInput.value = lat;
			lngInput.value = lng;
		});

		function bindMarkerDrag(m) {
			m.on('dragend', function () {
				var pos = m.getLatLng();
				latInput.value = pos.lat.toFixed(6);
				lngInput.value = pos.lng.toFixed(6);
			});
		}
	});
	JS;
}

// Register the meta field
function whitbyanchor_register_pinned_meta() {
	register_post_meta('post', '_is_pinned', array(
		'show_in_rest' => true,
		'single'       => true,
		'type'         => 'boolean',
	));
}
add_action('init', 'whitbyanchor_register_pinned_meta');

// Add the meta box to the post editor
function whitbyanchor_add_pinned_meta_box() {
	add_meta_box(
		'whitbyanchor_pinned',
		'Pin to Home Page',
		'whitbyanchor_pinned_meta_box_html',
		'post',
		'side',
		'high'
	);
}
add_action('add_meta_boxes', 'whitbyanchor_add_pinned_meta_box');

// Render the checkbox
function whitbyanchor_pinned_meta_box_html($post) {
	$is_pinned = get_post_meta($post->ID, '_is_pinned', true);
	wp_nonce_field('whitbyanchor_pinned_nonce', 'whitbyanchor_pinned_nonce');
	echo '<label>';
	echo '<input type="checkbox" name="whitbyanchor_is_pinned" value="1" ' . checked($is_pinned, '1', false) . '>';
	echo ' Pin this story to the top of the home page';
	echo '</label>';
}

// Save the meta value
function whitbyanchor_save_pinned_meta($post_id) {
	// Security checks
	if (!isset($_POST['whitbyanchor_pinned_nonce'])) return;
	if (!wp_verify_nonce($_POST['whitbyanchor_pinned_nonce'], 'whitbyanchor_pinned_nonce')) return;
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!current_user_can('edit_post', $post_id)) return;

	$is_pinned = isset($_POST['whitbyanchor_is_pinned']) ? '1' : '0';
	update_post_meta($post_id, '_is_pinned', $is_pinned);
}
add_action('save_post', 'whitbyanchor_save_pinned_meta');

// Add the field to the Add Category form
function whitbyanchor_add_category_fields() {
	?>
	<div class="form-field">
		<label for="show_events">
			<input type="checkbox" name="show_events" id="show_events" value="1">
			Show events section on this category page
		</label>
	</div>
	<?php
}
add_action('category_add_form_fields', 'whitbyanchor_add_category_fields');

// Add the field to the Edit Category form
function whitbyanchor_edit_category_fields($term) {
	$show_events = get_term_meta($term->term_id, 'show_events', true);
	?>
	<tr class="form-field">
		<th><label for="show_events">Events Section</label></th>
		<td>
			<label>
				<input type="checkbox" name="show_events" id="show_events" value="1"
					<?php checked($show_events, '1'); ?>>
				Show events section on this category page
			</label>
		</td>
	</tr>
	<?php
}
add_action('category_edit_form_fields', 'whitbyanchor_edit_category_fields');

// Save the meta on both add and edit
function whitbyanchor_save_category_fields($term_id) {
	$value = isset($_POST['show_events']) ? '1' : '0';
	update_term_meta($term_id, 'show_events', $value);
}
add_action('created_category', 'whitbyanchor_save_category_fields');
add_action('edited_category', 'whitbyanchor_save_category_fields');

// Render the meta box — checkboxes for each category the post belongs to
function whitbyanchor_pinned_category_meta_box_html($post) {
	$pinned_ids  = get_post_meta($post->ID, '_pinned_category_ids', true) ?: array();
	$categories  = get_the_category($post->ID);

	wp_nonce_field('whitbyanchor_pinned_category_nonce', 'whitbyanchor_pinned_category_nonce');

	if (empty($categories)) {
		echo '<p>Assign this post to a category first.</p>';
		return;
	}

	echo '<p style="margin-bottom:8px;">Pin to top of:</p>';
	foreach ($categories as $cat) {
		$checked = in_array($cat->term_id, (array) $pinned_ids);
		echo '<label style="display:block;margin-bottom:4px;">';
		echo '<input type="checkbox" name="whitbyanchor_pinned_category_ids[]" value="' . esc_attr($cat->term_id) . '" ' . checked($checked, true, false) . '>';
		echo ' ' . esc_html($cat->name);
		echo '</label>';
	}
}

// Register the meta box
function whitbyanchor_add_pinned_category_meta_box() {
	add_meta_box(
		'whitbyanchor_pinned_category',
		'Pin to Category Page',
		'whitbyanchor_pinned_category_meta_box_html',
		'post',
		'side',
		'high'
	);
}
add_action('add_meta_boxes', 'whitbyanchor_add_pinned_category_meta_box');

// Save the meta
function whitbyanchor_save_pinned_category_meta($post_id) {
	if (!isset($_POST['whitbyanchor_pinned_category_nonce'])) return;
	if (!wp_verify_nonce($_POST['whitbyanchor_pinned_category_nonce'], 'whitbyanchor_pinned_category_nonce')) return;
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!current_user_can('edit_post', $post_id)) return;

	$pinned_ids = isset($_POST['whitbyanchor_pinned_category_ids'])
		? array_map('intval', $_POST['whitbyanchor_pinned_category_ids'])
		: array();

	update_post_meta($post_id, '_pinned_category_ids', $pinned_ids);
}
add_action('save_post', 'whitbyanchor_save_pinned_category_meta');

function whitbyanchor_get_pinned_category_post($term_id) {
	$args = array(
		'posts_per_page' => 1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => array(
			array(
				'key'     => '_pinned_category_ids',
				'value'   => ';i:' . intval($term_id) . ';', // matches serialized integer
				'compare' => 'LIKE',
			),
		),
	);

	$query = new WP_Query($args);
	return $query->have_posts() ? $query->posts[0] : null;
}

wp_enqueue_script(
	'whitbyanchor-events',
	get_template_directory_uri() . '/js/events.js?v='.rand(),
	[],
	'1.0',
	true // footer = true, so it runs after the inline config
);

function newspaper_render_related_posts(): void {
	if ( ! is_single() ) {
		return;
	}

	$current_post_id  = get_the_ID();
	$categories       = get_the_category( $current_post_id );

	if ( empty( $categories ) ) {
		return;
	}

	// WordPress doesn't have a native "primary category" concept, but Yoast
	// stores one. Fall back to the first assigned category if not using Yoast.
	$primary_cat_id = null;
	if ( class_exists( 'WPSEO_Primary_Term' ) ) {
		$wpseo_primary = new WPSEO_Primary_Term( 'category', $current_post_id );
		$primary_cat_id = $wpseo_primary->get_primary_term();
	}
	if ( ! $primary_cat_id ) {
		$primary_cat_id = $categories[0]->term_id;
	}

	$related = get_posts( [
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'post_status'         => 'publish',
		'post__not_in'        => [ $current_post_id ],
		'category__in'        => [ $primary_cat_id ],
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
	] );

	if ( empty( $related ) ) {
		return;
	}

	$category = get_term( $primary_cat_id, 'category' );
	?>
	<section class="more articles flow">
		<header>
			<h2 class="related-posts__heading">
				More from
				<a href="<?php echo esc_url( get_category_link( $primary_cat_id ) ); ?>">
					<?php echo esc_html( $category->name ); ?>
				</a>
			</h2>
		</header>
		<div class="cards-grid">
		<?php global $post; ?>
		<?php foreach ( $related as $post ) : setup_postdata( $post ); ?>
			<?php
				echo '<article class="flow">';
				echo '<figure>';
				the_post_thumbnail('full');
				echo '<figcaption>';
				the_post_thumbnail_caption();
				echo '</figcaption>';
				echo '</figure>';
				the_title('<h2>', '</h2>');
				echo '<div class="entry-meta">';
				echo '<img src="'; echo get_stylesheet_directory_uri(); echo '/icons/apple-icon-180x180.png" alt="Whitby Anchor" />';
				whitbyanchor_posted_on();
				whitbyanchor_posted_by();
				echo '</div>';
				echo '<p class="excerpt">' . get_the_excerpt() . '</p>';
				echo '<a class="article-link" href="' . get_the_permalink() . '"><span>Read: '.get_the_title().'</a>';
				echo '</article>';
			?>
		<?php endforeach; wp_reset_postdata(); ?>
		</div>
	</section>
	<?php
}

function whitby_anchor_login_logo() { ?>
	<style type="text/css">
		body.login{
			background:#f8fcff;
		}
		#login h1 a {
			background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/icons/ms-icon-310x310.png);
			height: 200px;
			width: 200px;
			border-radius:50%;
			background-size: 200px;
			background-repeat: no-repeat;
		}
	</style>
<?php }
add_action( 'login_enqueue_scripts', 'whitby_anchor_login_logo' );

/**
 * Change the excerpt more string
 */
 function whitbyanchor_theme_excerpt_more( $more ) {
	 return '&hellip; <span>&rarr;</span>';
 }
 add_filter( 'excerpt_more', 'whitbyanchor_theme_excerpt_more' );