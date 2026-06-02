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