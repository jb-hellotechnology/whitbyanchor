<?php

define( 'WHITBYANCHOR_BUSINESSES_PER_PAGE', 12 );

// ---------------------------------------------------------------------------
// 1. POST TYPE
// ---------------------------------------------------------------------------

function register_business_post_type() {
	register_post_type( 'business', [
		'labels' => [
			'name'               => 'Businesses',
			'singular_name'      => 'Business',
			'add_new_item'       => 'Add New Business',
			'edit_item'          => 'Edit Business',
			'view_item'          => 'View Business',
			'search_items'       => 'Search Businesses',
			'not_found'          => 'No businesses found.',
			'not_found_in_trash' => 'No businesses found in trash.',
		],
		'public'       => true,
		'has_archive'  => 'business-directory',
		'menu_icon'    => 'dashicons-store',
		'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
		'rewrite'      => [ 'slug' => 'business-directory' ],
		'show_in_rest' => false,
	] );
}
add_action( 'init', 'register_business_post_type' );


// ---------------------------------------------------------------------------
// 2. TAXONOMIES
// ---------------------------------------------------------------------------

function register_business_taxonomies() {
	// Location (town/village) — non-hierarchical
	register_taxonomy( 'business_location', 'business', [
		'labels' => [
			'name'          => 'Locations',
			'singular_name' => 'Location',
			'add_new_item'  => 'Add New Location',
		],
		'hierarchical'      => false,
		'public'            => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => [ 'slug' => 'business-location' ],
	] );

	// Category (builder, plumber, etc.) — hierarchical
	register_taxonomy( 'business_category', 'business', [
		'labels' => [
			'name'          => 'Business Categories',
			'singular_name' => 'Business Category',
			'add_new_item'  => 'Add New Category',
		],
		'hierarchical'      => true,
		'public'            => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => [ 'slug' => 'business-category' ],
	] );

	// Tags — non-hierarchical
	register_taxonomy( 'business_tag', 'business', [
		'labels' => [
			'name'          => 'Business Tags',
			'singular_name' => 'Business Tag',
		],
		'hierarchical' => false,
		'public'       => true,
		'show_in_rest' => true,
		'rewrite'      => [ 'slug' => 'business-tag' ],
	] );
}
add_action( 'init', 'register_business_taxonomies' );


// ---------------------------------------------------------------------------
// 3. META BOX
// ---------------------------------------------------------------------------

function business_meta_boxes() {
	add_meta_box(
		'business_details',
		'Business Details',
		'business_meta_box_html',
		'business',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'business_meta_boxes' );

function business_meta_box_html( $post ) {
	wp_nonce_field( 'business_meta_save', 'business_meta_nonce' );

	$address = get_post_meta( $post->ID, '_business_address', true );
	$phone   = get_post_meta( $post->ID, '_business_phone',   true );
	$email   = get_post_meta( $post->ID, '_business_email',   true );
	$website = get_post_meta( $post->ID, '_business_website', true );
	$lat     = get_post_meta( $post->ID, '_business_lat',     true );
	$lng     = get_post_meta( $post->ID, '_business_lng',     true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="business_address">Address</label></th>
			<td><input type="text" id="business_address" name="business_address" value="<?php echo esc_attr( $address ); ?>" class="regular-text" placeholder="e.g. 12 High Street, Whitby, YO21 3EW"></td>
		</tr>
		<tr>
			<th><label for="business_phone">Phone</label></th>
			<td><input type="tel" id="business_phone" name="business_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" placeholder="e.g. 01947 123456"></td>
		</tr>
		<tr>
			<th><label for="business_email">Email</label></th>
			<td><input type="email" id="business_email" name="business_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" placeholder="e.g. hello@example.com"></td>
		</tr>
		<tr>
			<th><label for="business_website">Website</label></th>
			<td><input type="url" id="business_website" name="business_website" value="<?php echo esc_attr( $website ); ?>" class="regular-text" placeholder="https://example.com"></td>
		</tr>
		<tr>
			<th><label>Map Pin</label></th>
			<td>
				<div id="business-map-picker" style="height:300px; border:1px solid #ccc;"></div>
				<p class="description">Click the map to drop a pin. Drag the pin to adjust.</p>
			</td>
		</tr>
		<tr>
			<th><label for="business_lat">Latitude</label></th>
			<td><input type="text" id="business_lat" name="business_lat" value="<?php echo esc_attr( $lat ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th><label for="business_lng">Longitude</label></th>
			<td><input type="text" id="business_lng" name="business_lng" value="<?php echo esc_attr( $lng ); ?>" class="regular-text"></td>
		</tr>
	</table>
	<?php
}


// ---------------------------------------------------------------------------
// 4. SAVE META
// ---------------------------------------------------------------------------

function business_meta_save( $post_id ) {
	if ( ! isset( $_POST['business_meta_nonce'] ) || ! wp_verify_nonce( $_POST['business_meta_nonce'], 'business_meta_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	// Plain text fields
	$text_fields = [
		'_business_address' => 'business_address',
		'_business_phone'   => 'business_phone',
	];
	foreach ( $text_fields as $meta_key => $post_key ) {
		if ( isset( $_POST[ $post_key ] ) ) {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
		}
	}

	// Email
	if ( isset( $_POST['business_email'] ) ) {
		update_post_meta( $post_id, '_business_email', sanitize_email( $_POST['business_email'] ) );
	}

	// URL
	if ( isset( $_POST['business_website'] ) ) {
		update_post_meta( $post_id, '_business_website', esc_url_raw( $_POST['business_website'] ) );
	}

	// Coordinates — store as floats; skip if zero (unpopulated)
	foreach ( [ '_business_lat' => 'business_lat', '_business_lng' => 'business_lng' ] as $meta_key => $post_key ) {
		if ( isset( $_POST[ $post_key ] ) ) {
			$val = floatval( $_POST[ $post_key ] );
			if ( $val !== 0.0 ) {
				update_post_meta( $post_id, $meta_key, $val );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
		}
	}
}
add_action( 'save_post_business', 'business_meta_save' );


// ---------------------------------------------------------------------------
// 5. ADMIN SCRIPTS — Leaflet map picker
// ---------------------------------------------------------------------------

function business_admin_scripts( $hook ) {
	global $post;

	if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
	if ( ! $post || $post->post_type !== 'business' ) return; // fixed: was 'event'

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
	wp_add_inline_script( 'leaflet', business_map_picker_js(), 'after' ); // fixed: was event_map_picker_js()
}
add_action( 'admin_enqueue_scripts', 'business_admin_scripts' ); // fixed: was event_admin_scripts

function business_map_picker_js() {
	return <<<JS
	document.addEventListener('DOMContentLoaded', function () {
		var latInput = document.getElementById('business_lat');
		var lngInput = document.getElementById('business_lng');

		if ( ! latInput || ! lngInput ) return;

		var initLat  = parseFloat(latInput.value) || 54.4;
		var initLng  = parseFloat(lngInput.value) || -0.6;
		var initZoom = latInput.value ? 15 : 9;

		var map = L.map('business-map-picker').setView([initLat, initLng], initZoom);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '© OpenStreetMap contributors'
		}).addTo(map);

		var marker = null;

		if (latInput.value && lngInput.value) {
			marker = L.marker([initLat, initLng], { draggable: true }).addTo(map);
			bindMarkerDrag(marker);
		}

		map.on('click', function (e) {
			if (marker) {
				marker.setLatLng(e.latlng);
			} else {
				marker = L.marker(e.latlng, { draggable: true }).addTo(map);
				bindMarkerDrag(marker);
			}
			latInput.value = e.latlng.lat.toFixed(6);
			lngInput.value = e.latlng.lng.toFixed(6);
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


// ---------------------------------------------------------------------------
// 6. QUERY HELPER
// ---------------------------------------------------------------------------

/**
 * Replace the get_businesses() function in business-listings.php with this version.
 *
 * Returns an array of item arrays (same shape as get_events()), so that
 * archive-business.php can iterate over them and access $business['post'], etc.
 *
 * @param array $args {
 *   @type string $category  Slug of a business_category term.
 *   @type string $location  Slug of a business_location term.
 *   @type string $tag       Slug of a business_tag term.
 *   @type string $search    Free-text keyword search.
 *   @type int    $limit     Max results. Default WHITBYANCHOR_BUSINESSES_PER_PAGE.
 * }
 * @return array  Array of business item arrays, each containing 'post', 'address', etc.
 */
function get_businesses( $args = [] ) {
	$defaults = [
		'category' => '',
		'location' => '',
		'tag'      => '',
		'search'   => '',
		'limit'    => WHITBYANCHOR_BUSINESSES_PER_PAGE,
	];
	$args = wp_parse_args( $args, $defaults );

	$query_args = [
		'post_type'      => 'business',
		'posts_per_page' => intval( $args['limit'] ),
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'tax_query'      => [],
	];

	if ( ! empty( $args['category'] ) ) {
		$query_args['tax_query'][] = [
			'taxonomy' => 'business_category',
			'field'    => 'slug',
			'terms'    => sanitize_title( $args['category'] ),
		];
	}

	if ( ! empty( $args['location'] ) ) {
		$query_args['tax_query'][] = [
			'taxonomy' => 'business_location',
			'field'    => 'slug',
			'terms'    => sanitize_title( $args['location'] ),
		];
	}

	if ( ! empty( $args['tag'] ) ) {
		$query_args['tax_query'][] = [
			'taxonomy' => 'business_tag',
			'field'    => 'slug',
			'terms'    => sanitize_title( $args['tag'] ),
		];
	}

	if ( count( $query_args['tax_query'] ) > 1 ) {
		$query_args['tax_query']['relation'] = 'AND';
	}

	if ( ! empty( $args['search'] ) ) {
		$query_args['s'] = sanitize_text_field( $args['search'] );
	}

	$posts     = get_posts( $query_args );
	$businesses = [];

	foreach ( $posts as $post ) {
		$businesses[] = [
			'post'    => $post,
			'address' => get_post_meta( $post->ID, '_business_address', true ),
			'phone'   => get_post_meta( $post->ID, '_business_phone',   true ),
			'email'   => get_post_meta( $post->ID, '_business_email',   true ),
			'website' => get_post_meta( $post->ID, '_business_website', true ),
			'lat'     => get_post_meta( $post->ID, '_business_lat',     true ),
			'lng'     => get_post_meta( $post->ID, '_business_lng',     true ),
		];
	}

	return $businesses;
}

// ---------------------------------------------------------------------------
// 7. SHORTCODE  [business_directory]
// ---------------------------------------------------------------------------

/**
 * Usage:
 *   [business_directory]
 *   [business_directory category="plumber" limit="6"]
 */
function business_directory_shortcode( $atts ) {
	$atts = shortcode_atts(
		[
			'category' => '',
			'location' => '',
			'limit'    => WHITBYANCHOR_BUSINESSES_PER_PAGE,
		],
		$atts,
		'business_directory'
	);

	// Honour URL params for live filtering
	$active_category = sanitize_title( $_GET['business_category'] ?? $atts['category'] );
	$active_location = sanitize_title( $_GET['business_location'] ?? $atts['location'] );
	$active_search   = sanitize_text_field( $_GET['business_search'] ?? '' );
	$paged           = max( 1, intval( $_GET['business_page'] ?? 1 ) );

	$query = get_businesses( [
		'category' => $active_category,
		'location' => $active_location,
		'search'   => $active_search,
		'limit'    => intval( $atts['limit'] ),
		'paged'    => $paged,
	] );

	$all_categories = get_terms( [ 'taxonomy' => 'business_category', 'hide_empty' => true, 'orderby' => 'name' ] );
	$all_locations  = get_terms( [ 'taxonomy' => 'business_location',  'hide_empty' => true, 'orderby' => 'name' ] );

	$page_url = strtok( get_permalink() ?: home_url( $_SERVER['REQUEST_URI'] ), '?' );

	ob_start();
	?>
	<div class="businesses">

		<!-- Search & Filter Form -->
		<div class="business-directory__filters">
			<div class="business-directory__filters-inner">

				<div class="business-filter-group">
					<label for="business_search" class="screen-reader-text">Search businesses</label>
					<input
						form="business-filter-form"
						type="search"
						id="business_search"
						name="business_search"
						class="business-search-input"
						placeholder="Search businesses…"
						value="<?php echo esc_attr( $active_search ); ?>"
					>
				</div>

				<?php if ( ! is_wp_error( $all_categories ) && ! empty( $all_categories ) ) : ?>
				<div class="business-filter-group">
					<label for="business_category" class="screen-reader-text">Filter by category</label>
					<select form="business-filter-form" id="business_category" name="business_category" class="business-select">
						<option value="">All Categories</option>
						<?php foreach ( $all_categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $active_category, $cat->slug ); ?>>
								<?php echo esc_html( $cat->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>

				<?php if ( ! is_wp_error( $all_locations ) && ! empty( $all_locations ) ) : ?>
				<div class="business-filter-group">
					<label for="business_location" class="screen-reader-text">Filter by location</label>
					<select form="business-filter-form" id="business_location" name="business_location" class="business-select">
						<option value="">All Locations</option>
						<?php foreach ( $all_locations as $loc ) : ?>
							<option value="<?php echo esc_attr( $loc->slug ); ?>" <?php selected( $active_location, $loc->slug ); ?>>
								<?php echo esc_html( $loc->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>

				<div class="business-filter-group">
					<button form="business-filter-form" type="submit" class="business-search-btn">Search</button>
				</div>

				<?php if ( $active_search || $active_category || $active_location ) : ?>
				<div class="business-filter-group">
					<a href="<?php echo esc_url( $page_url ); ?>" class="business-clear-link">Clear filters</a>
				</div>
				<?php endif; ?>

			</div>
			<form id="business-filter-form" method="get" action="<?php echo esc_url( $page_url ); ?>"></form>
		</div>

		<!-- Results count -->
		<?php if ( $active_search || $active_category || $active_location ) : ?>
			<p class="business-directory__count">
				<?php
				$total = $query->found_posts;
				$parts = [];
				if ( $active_search )   $parts[] = 'matching <strong>' . esc_html( $active_search ) . '</strong>';
				if ( $active_category ) $parts[] = 'in category <strong>' . esc_html( get_term_by( 'slug', $active_category, 'business_category' )->name ?? $active_category ) . '</strong>';
				if ( $active_location ) $parts[] = 'in <strong>' . esc_html( get_term_by( 'slug', $active_location, 'business_location' )->name ?? $active_location ) . '</strong>';
				printf( '%d %s found%s.', $total, _n( 'business', 'businesses', $total ), $parts ? ' ' . implode( ', ', $parts ) : '' );
				?>
			</p>
		<?php endif; ?>

		<!-- Listings Grid -->
		<?php if ( $query->have_posts() ) : ?>
			<ul class="business-directory__grid">
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<?php
					$phone   = get_post_meta( get_the_ID(), '_business_phone',   true );
					$email   = get_post_meta( get_the_ID(), '_business_email',   true );
					$website = get_post_meta( get_the_ID(), '_business_website', true );
					$address = get_post_meta( get_the_ID(), '_business_address', true );
					$cats    = get_the_terms( get_the_ID(), 'business_category' );
					$locs    = get_the_terms( get_the_ID(), 'business_location' );
					?>
					<li class="business <?php if($website){echo 'premium';} ?>">

						<?php if ( has_post_thumbnail() ) : ?>
							<div class="business-card__image">
								<a href="<?php the_permalink(); ?>">
									<?php the_post_thumbnail( 'medium', [ 'loading' => 'lazy', 'alt' => get_the_title() ] ); ?>
								</a>
							</div>
						<?php endif; ?>

						<div class="business-card__body">

							<h3 class="business-card__name">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>

							<?php if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) : ?>
								<ul class="business-card__terms" aria-label="Categories">
									<?php foreach ( $cats as $cat ) : ?>
										<li>
											<a href="<?php echo esc_url( add_query_arg( 'business_category', $cat->slug, $page_url ) ); ?>">
												<?php echo esc_html( $cat->name ); ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>

							<?php if ( $address ) : ?>
								<p class="business-card__address">
									<span class="business-icon" aria-hidden="true">📍</span> <?php echo esc_html( $address ); ?>
								</p>
							<?php endif; ?>

							<?php if ( get_the_excerpt() ) : ?>
								<p class="business-card__excerpt"><?php the_excerpt(); ?></p>
							<?php endif; ?>

							<ul class="business-card__contacts">
								<?php if ( $phone ) : ?>
									<li>
										<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>">
											<span class="business-icon" aria-hidden="true">📞</span> <?php echo esc_html( $phone ); ?>
										</a>
									</li>
								<?php endif; ?>
								<?php if ( $email ) : ?>
									<li>
										<a href="mailto:<?php echo esc_attr( $email ); ?>">
											<span class="business-icon" aria-hidden="true">✉️</span> <?php echo esc_html( $email ); ?>
										</a>
									</li>
								<?php endif; ?>
								<?php if ( $website ) : ?>
									<li>
										<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer">
											<span class="business-icon" aria-hidden="true">🌐</span>
											<?php echo esc_html( preg_replace( '#^https?://#', '', rtrim( $website, '/' ) ) ); ?>
										</a>
									</li>
								<?php endif; ?>
							</ul>

							<a href="<?php the_permalink(); ?>" class="business-card__more">View listing →</a>

						</div>
					</li>
				<?php endwhile; wp_reset_postdata(); ?>
			</ul>

			<!-- Pagination -->
			<?php if ( $query->max_num_pages > 1 ) : ?>
				<nav class="business-directory__pagination" aria-label="Business listings pages">
					<?php echo paginate_links( [
						'base'     => $page_url . '%_%',
						'format'   => '?business_page=%#%',
						'add_args' => array_filter( [
							'business_search'   => $active_search,
							'business_category' => $active_category,
							'business_location' => $active_location,
						] ),
						'current'   => $paged,
						'total'     => $query->max_num_pages,
						'prev_text' => '&laquo; Previous',
						'next_text' => 'Next &raquo;',
					] ); ?>
				</nav>
			<?php endif; ?>

		<?php else : ?>
			<p class="business-directory__none">
				No businesses found<?php
					if ( $active_search )   echo ' matching <strong>' . esc_html( $active_search ) . '</strong>';
					if ( $active_category ) echo ' in this category';
					if ( $active_location ) echo ' in this location';
				?>.
				<?php if ( $active_search || $active_category || $active_location ) : ?>
					<a href="<?php echo esc_url( $page_url ); ?>">View all businesses</a>.
				<?php endif; ?>
			</p>
		<?php endif; ?>

	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'business_directory', 'business_directory_shortcode' );


// ---------------------------------------------------------------------------
// 8. FRONTEND STYLESHEET
// ---------------------------------------------------------------------------

function business_directory_maybe_enqueue_styles() {
	global $post;
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'business_directory' ) ) {
		wp_add_inline_style( 'wp-block-library', business_directory_css() );
	}
}
add_action( 'wp_enqueue_scripts', 'business_directory_maybe_enqueue_styles' );

function whitbyanchor_render_business_article( $business ) {
	$post    = $business['post'];
	$address = $business['address'];
	$phone   = $business['phone'];
	$email   = $business['email'];
	$website = $business['website'];

	$cats = get_the_terms( $post->ID, 'business_category' );
	$locs = get_the_terms( $post->ID, 'business_location' );
	$tags = get_the_terms( $post->ID, 'business_tag' );

	$cat_slugs = ( $cats && ! is_wp_error( $cats ) ) ? implode( ' ', wp_list_pluck( $cats, 'slug' ) ) : '';
	$loc_slugs = ( $locs && ! is_wp_error( $locs ) ) ? implode( ' ', wp_list_pluck( $locs, 'slug' ) ) : '';

	ob_start();
	?>
	<article class="business flow <?php if($website){ echo 'premium';}?>"
		data-categories="<?php echo esc_attr( $cat_slugs ); ?>"
		data-locations="<?php echo esc_attr( $loc_slugs ); ?>"
		data-name="<?php echo esc_attr( strtolower( $post->post_title ) ); ?>">

		<?php if ( has_post_thumbnail( $post->ID ) ) : ?>
			<figure class="business-card__image">
				<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
					<?php echo get_the_post_thumbnail( $post->ID, 'medium', [ 'loading' => 'lazy', 'alt' => esc_attr( $post->post_title ) ] ); ?>
				</a>
			</figure>
		<?php endif; ?>

		<div class="business-card__body">

			<h2 class="business-card__name">
				<?php echo esc_html( $post->post_title ); ?>
			</h2>
			
			<?php if ( $post->post_excerpt ) : ?>
				<p class="business-card__excerpt"><?php echo esc_html( $post->post_excerpt ); ?></p>
			<?php endif; ?>
			
			<div class="meta">

				<?php if ( $address ) : ?>
					<p class="business-card__address">
						<span class="material-symbols-outlined">location_on</span>
						<?php echo esc_html( $address ); ?>
					</p>
				<?php endif; ?>

				<!-- <ul class="business-card__contacts">
					<?php if ( $phone ) : ?>
						<li>
							<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>">
								📞 <?php echo esc_html( $phone ); ?>
							</a>
						</li>
					<?php endif; ?>
					<?php if ( $email ) : ?>
						<li>
							<a href="mailto:<?php echo esc_attr( $email ); ?>">
								✉️ <?php echo esc_html( $email ); ?>
							</a>
						</li>
					<?php endif; ?>
					<?php if ( $website ) : ?>
						<li>
							<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer">
								🌐 <?php echo esc_html( preg_replace( '#^https?://#', '', rtrim( $website, '/' ) ) ); ?>
							</a>
						</li>
					<?php endif; ?>
				</ul> -->
			
			</div>

			<a class="business-card__more" href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
				<span>View listing →</span>
			</a>

		</div>
	</article>
	<?php
	return ob_get_clean();
}

// ---------------------------------------------------------------------------
// 9. ENQUEUE businesses.js ON THE ARCHIVE PAGE
// ---------------------------------------------------------------------------

function business_archive_scripts() {
	// temporarily removed: if ( ! is_post_type_archive( 'business' ) ) return;

	wp_enqueue_script(
		'whitbyanchor-businesses',
		get_template_directory_uri() . '/js/businesses.js',
		[],
		'1.0.0',
		true
	);

	wp_localize_script( 'whitbyanchor-businesses', 'businessesConfig', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'whitbyanchor_businesses' ),
		'perPage' => WHITBYANCHOR_BUSINESSES_PER_PAGE,
	] );
}
add_action( 'wp_enqueue_scripts', 'business_archive_scripts' );


// ---------------------------------------------------------------------------
// 10. AJAX HANDLER
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_load_businesses',        'ajax_load_businesses' );
add_action( 'wp_ajax_nopriv_load_businesses', 'ajax_load_businesses' );

function ajax_load_businesses() {
	check_ajax_referer( 'whitbyanchor_businesses', 'nonce' );

	$page     = max( 1, intval( $_POST['page']     ?? 1 ) );
	$per_page = max( 1, intval( $_POST['per_page'] ?? WHITBYANCHOR_BUSINESSES_PER_PAGE ) );
	$search   = sanitize_text_field( $_POST['search']   ?? '' );
	$tag      = sanitize_title(      $_POST['tag']      ?? '' );
	$location = sanitize_title(      $_POST['location'] ?? '' );

	// Fetch enough posts to cover all pages up to and including the current
	// one, plus one extra so we know whether a further page exists.
	$businesses = get_businesses( [
		'search'   => $search,
		'tag'      => $tag,
		'location' => $location,
		'limit'    => ( $per_page * $page ) + 1,
	] );

	$total    = count( $businesses );
	$has_more = $total > ( $per_page * $page );
	$offset   = ( $page - 1 ) * $per_page;
	$page_items = array_slice( $businesses, $offset, $per_page );

	if ( empty( $page_items ) ) {
		wp_send_json_success( [
			'html'     => '<p class="businesses-none">No businesses found — try changing your filters.</p>',
			'has_more' => false,
		] );
	}

	$html = '';
	foreach ( $page_items as $business ) {
		$html .= whitbyanchor_render_business_article( $business );
	}

	wp_send_json_success( [
		'html'     => $html,
		'has_more' => $has_more,
	] );
}