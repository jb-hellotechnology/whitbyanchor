<?php

define( 'WHITBYANCHOR_ANNOUNCEMENTS_PER_PAGE', 12 );

// ---------------------------------------------------------------------------
// 1. POST TYPE
// ---------------------------------------------------------------------------

function register_announcement_post_type() {
	register_post_type( 'announcement', [
		'labels' => [
			'name'               => 'Announcements',
			'singular_name'      => 'Announcement',
			'add_new_item'       => 'Add New Announcement',
			'edit_item'          => 'Edit Announcement',
			'view_item'          => 'View Announcement',
			'search_items'       => 'Search Announcements',
			'not_found'          => 'No announcements found.',
			'not_found_in_trash' => 'No announcements found in trash.',
		],
		'public'       => true,
		'has_archive'  => 'announcements',
		'menu_icon'    => 'dashicons-megaphone',
		'supports'     => [ 'title', 'thumbnail', 'excerpt' ],
		'rewrite'      => [ 'slug' => 'announcements' ],
		'show_in_rest' => false,
	] );
}
add_action( 'init', 'register_announcement_post_type' );

function register_announcement_taxonomies() {
	// Tags — non-hierarchical
	register_taxonomy( 'announcement_tag', 'announcement', [
		'labels' => [
			'name'          => 'Announcements Tags',
			'singular_name' => 'Announcement Tag',
		],
		'hierarchical' => false,
		'public'       => true,
		'show_in_rest' => true,
		'rewrite'      => [ 'slug' => 'announcement-tag' ],
	] );
}
add_action( 'init', 'register_announcement_taxonomies' );

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
function get_announcements( $args = [] ) {
	$defaults = [
		'search' => '',
		'tag'    => '', // ← add this
		'limit'  => WHITBYANCHOR_ANNOUNCEMENTS_PER_PAGE,
	];
	$args = wp_parse_args( $args, $defaults );
	
	$query_args = [
		'post_type'      => 'announcement',
		'posts_per_page' => intval( $args['limit'] ),
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
		'tax_query'      => [],
	];
	
	// ← add this block
	if ( ! empty( $args['tag'] ) ) {
		$query_args['tax_query'][] = [
			'taxonomy' => 'announcement_tag',
			'field'    => 'slug',
			'terms'    => sanitize_title( $args['tag'] ),
		];
	}
	
	if ( ! empty( $args['search'] ) ) {
		$query_args['s'] = sanitize_text_field( $args['search'] );
	}

	$posts     = get_posts( $query_args );
	$announcements = [];

	foreach ( $posts as $post ) {
		$announcements[] = [
			'post'    => $post
		];
	}

	return $announcements;
}

function whitbyanchor_render_announcement_article( $announcement ) {
	$post    = $announcement['post'];
	
	$has_image = has_post_thumbnail($post->ID);
	
	$tags = get_the_terms( $post->ID, 'announcement_tag' );

	ob_start();
	?>
	<article class="business flow <?php if($has_image){ echo 'premium';}?>"
		data-name="<?php echo esc_attr( strtolower( $post->post_title ) ); ?>">

		<?php if ( has_post_thumbnail( $post->ID ) ) : ?>
			<figure class="business-card__image">
				<?php echo get_the_post_thumbnail( $post->ID, 'medium', [ 'loading' => 'lazy', 'alt' => esc_attr( $post->post_title ) ] ); ?>
			</figure>
		<?php endif; ?>

		<div class="business-card__body">

			<h2 class="business-card__name">
				<?php echo esc_html( $post->post_title ); ?>
			</h2>
			
			<?php if ( $post->post_excerpt ) : ?>
				<p class="business-card__excerpt"><?php echo esc_html( $post->post_excerpt ); ?></p>
			<?php endif; ?>

			<!-- <a class="business-card__more" href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
				<span>View listing →</span>
			</a> -->

		</div>
	</article>
	<?php
	return ob_get_clean();
}

// ---------------------------------------------------------------------------
// 9. ENQUEUE announcements.js ON THE ARCHIVE PAGE
// ---------------------------------------------------------------------------

function announcement_archive_scripts() {
	wp_enqueue_script(
		'whitbyanchor-announcements', // ← was 'whitbyanchor-businesses'
		get_template_directory_uri() . '/js/announcements.js',
		[],
		'1.0.0',
		true
	);

	wp_localize_script( 'whitbyanchor-announcements', 'announcementsConfig', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'whitbyanchor_announcements' ),
		'perPage' => WHITBYANCHOR_ANNOUNCEMENTS_PER_PAGE,
	] );
}
add_action( 'wp_enqueue_scripts', 'announcement_archive_scripts' );


// ---------------------------------------------------------------------------
// 10. AJAX HANDLER
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_load_announcements',        'ajax_load_announcements' );
add_action( 'wp_ajax_nopriv_load_announcements', 'ajax_load_announcements' );

function ajax_load_announcements() {
	check_ajax_referer( 'whitbyanchor_announcements', 'nonce' );

	$page     = max( 1, intval( $_POST['page']     ?? 1 ) );
	$per_page = max( 1, intval( $_POST['per_page'] ?? WHITBYANCHOR_ANNOUNCEMENTS_PER_PAGE ) );
	$search   = sanitize_text_field( $_POST['search']   ?? '' );
	$tag      = sanitize_title(      $_POST['tag']      ?? '' );

	// Fetch enough posts to cover all pages up to and including the current
	// one, plus one extra so we know whether a further page exists.
	$announcements = get_announcements( [
		'search'   => $search,
		'tag'      => $tag,
		'limit'    => ( $per_page * $page ) + 1,
	] );

	$total    = count( $announcements );
	$has_more = $total > ( $per_page * $page );
	$offset   = ( $page - 1 ) * $per_page;
	$page_items = array_slice( $announcements, $offset, $per_page );

	if ( empty( $page_items ) ) {
		wp_send_json_success( [
			'html'     => '<p class="businesses-none">No announcements found — try changing your filters.</p>',
			'has_more' => false,
		] );
	}

	$html = '';
	foreach ( $page_items as $business ) {
		$html .= whitbyanchor_render_announcement_article( $business );
	}

	wp_send_json_success( [
		'html'     => $html,
		'has_more' => $has_more,
	] );
}
// ---------------------------------------------------------------------------
// 7. SOCIAL SHARING IMAGE
// ---------------------------------------------------------------------------

/**
 * True on the announcements archive and on its tag archives.
 */
function whitbyanchor_is_announcements_archive() {
	return is_post_type_archive( 'announcement' ) || is_tax( 'announcement_tag' );
}

/**
 * The image used when the announcements archive is shared.
 *
 * Replace /images/og-announcements.jpg to change it, or filter the URL with
 * whitbyanchor_announcements_og_image (e.g. to point at a media library item).
 * Returns an empty string if the file is missing, so we never advertise a 404.
 */
function whitbyanchor_announcements_og_image() {
	$file = '/images/og-announcements.jpg';
	$url  = file_exists( get_template_directory() . $file )
		? get_template_directory_uri() . $file
		: '';

	return apply_filters( 'whitbyanchor_announcements_og_image', $url );
}

/**
 * Yoast SEO: force the fixed image on the announcements archive so the
 * crawler can't pick a featured image from one of the listed announcements.
 */
add_filter( 'wpseo_frontend_presentation', function( $presentation ) {
	if ( ! whitbyanchor_is_announcements_archive() ) {
		return $presentation;
	}

	$image = whitbyanchor_announcements_og_image();
	if ( ! $image ) {
		return $presentation;
	}

	$presentation->open_graph_images = [ [ 'url' => $image ] ];
	$presentation->twitter_image     = $image;

	return $presentation;
}, 20 );

// Older Yoast versions (pre-14) used these string filters instead.
foreach ( [ 'wpseo_opengraph_image', 'wpseo_twitter_image' ] as $whitbyanchor_og_filter ) {
	add_filter( $whitbyanchor_og_filter, function( $image ) {
		if ( ! whitbyanchor_is_announcements_archive() ) {
			return $image;
		}

		$fixed = whitbyanchor_announcements_og_image();

		return $fixed ? $fixed : $image;
	}, 20 );
}
unset( $whitbyanchor_og_filter );

/**
 * Output the sharing tags ourselves when Yoast isn't active.
 */
function whitbyanchor_announcements_social_meta() {
	if ( defined( 'WPSEO_VERSION' ) ) {
		return; // Yoast is writing these tags — see the filters above.
	}

	if ( ! whitbyanchor_is_announcements_archive() ) {
		return;
	}

	$image = whitbyanchor_announcements_og_image();
	if ( ! $image ) {
		return;
	}

	$url = is_tax( 'announcement_tag' )
		? get_term_link( get_queried_object() )
		: get_post_type_archive_link( 'announcement' );

	if ( ! $url || is_wp_error( $url ) ) {
		$url = home_url( '/announcements/' );
	}

	$description = __( 'Announcements of births, deaths and marriages from around Whitby.', 'whitbyanchor' );

	printf( '<meta property="og:type" content="website" />' . "\n" );
	printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( wp_get_document_title() ) );
	printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
	printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $url ) );
	printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
	printf( '<meta property="og:image:alt" content="%s" />' . "\n", esc_attr__( 'The Whitby Anchor — Announcements', 'whitbyanchor' ) );

	// Dimensions help Facebook render the card on first scrape.
	$size = @getimagesize( get_template_directory() . '/images/og-announcements.jpg' );
	if ( $size ) {
		printf( '<meta property="og:image:width" content="%d" />' . "\n", $size[0] );
		printf( '<meta property="og:image:height" content="%d" />' . "\n", $size[1] );
	}

	printf( '<meta name="twitter:card" content="summary_large_image" />' . "\n" );
	printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( wp_get_document_title() ) );
	printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $description ) );
	printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $image ) );
}
add_action( 'wp_head', 'whitbyanchor_announcements_social_meta', 5 );
