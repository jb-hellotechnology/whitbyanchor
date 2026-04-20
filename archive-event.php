<?php
get_header();

// ── Fetch all future events for tag counts, render only the first page ───────

$per_page   = WHITBYANCHOR_EVENTS_PER_PAGE;
$all_events = get_events( [
	'from_date' => current_time( 'Y-m-d' ),
	'limit'     => 1000,
] );
 
$total       = count( $all_events );
$first_page  = array_slice( $all_events, 0, $per_page );
$has_more    = $total > $per_page;

// ── Build tag counts from the full set ───────────────────────────────────────

$tag_counts = [];
foreach ( $all_events as $event ) {
	$terms = get_the_terms( $event['post']->ID, 'event_tag' );
	if ( ! $terms || is_wp_error( $terms ) ) continue;
	foreach ( $terms as $term ) {
		if ( isset( $tag_counts[ $term->slug ] ) ) {
			$tag_counts[ $term->slug ]['count']++;
		} else {
			$tag_counts[ $term->slug ] = [
				'name'  => $term->name,
				'slug'  => $term->slug,
				'count' => 1,
			];
		}
	}
}

$location_counts = [];
foreach ( $all_events as $event ) {
	$terms = get_the_terms( $event['post']->ID, 'event_location' );
	if ( ! $terms || is_wp_error( $terms ) ) continue;
	foreach ( $terms as $term ) {
		if ( isset( $location_counts[ $term->slug ] ) ) {
			$location_counts[ $term->slug ]['count']++;
		} else {
			$location_counts[ $term->slug ] = [
				'name'  => $term->name,
				'slug'  => $term->slug,
				'count' => 1,
			];
		}
	}
}
?>

<main id="primary" class="site-main events">
	<h1>Whitby Events</h1>

	<section class="events">
		<header>
			<h2>Filter Events</h2>
	
			<?php if ( $tag_counts ) : ?>
				<div>
					<label for="event-tag-select">
						<?php esc_html_e( 'Event type', 'whitbyanchor' ); ?>
					</label>
					<select id="event-tag-select" name="event_tag">
						<option value=""><?php esc_html_e( 'Everything', 'whitbyanchor' ); ?></option>
						<?php foreach ( $tag_counts as $tag ) : ?>
							<option value="<?php echo esc_attr( $tag['slug'] ); ?>">
								<?php echo esc_html( $tag['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>
			<?php if ( $location_counts ) : ?>
				<div>
					<label for="event-location-select">
						<?php esc_html_e( 'Event location', 'whitbyanchor' ); ?>
					</label>
					<select id="event-location-select" name="event_location">
						<option value=""><?php esc_html_e( 'Anywhere', 'whitbyanchor' ); ?></option>
						<?php foreach ( $location_counts as $venue ) : ?>
							<option value="<?php echo esc_attr( $venue['slug'] ); ?>">
								<?php echo esc_html( $venue['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>
			<div>
				<label for="event-date-start">
					<?php esc_html_e( 'Event start', 'whitbyanchor' ); ?>
				</label>
				<input type="date" name="start" id="event-date-start" />
			</div>
			<div>
				<label for="event-date-end">
					<?php esc_html_e( 'Event end', 'whitbyanchor' ); ?>
				</label>
				<input type="date" name="end" id="event-date-end" />
			</div>
		</header>

		<?php if ( $all_events ) : ?>

			<div id="events-list">
				<?php foreach ( $first_page as $event ) : ?>
					<?php echo whitbyanchor_render_event_article( $event ); ?>
				<?php endforeach; ?>
			</div>

			<?php if ( $has_more ) : ?>
				<button id="events-load-more" type="button"
						data-page="1"
						data-per-page="<?php echo esc_attr( $per_page ); ?>">
					<?php esc_html_e( 'Load more events', 'whitbyanchor' ); ?>
				</button>
			<?php endif; ?>

		<?php else : ?>
			<p><?php esc_html_e( 'No events found - try changing your filters.', 'whitbyanchor' ); ?></p>
		<?php endif; ?>
	</section>

	<section>
		<?php echo do_shortcode( '[newspaper_advert placement="category_top" category_id="5"]' ); ?>

		<?php
		$args = [
			'category_name'  => 'events',
			'posts_per_page' => 4,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		echo '<h2 class="category-heading">Events News</h2>';

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) :
			while ( $query->have_posts() ) :
				$query->the_post();
				echo '<article class="flow">';
				echo '<figure>';
				the_post_thumbnail('full');
				echo '</figure>';
				echo '<figcaption>';
				the_post_thumbnail_caption();
				echo '</figcaption>';
				the_title('<h2>', '</h2>');
				echo '<div class="entry-meta">';
				echo '<img src="'; echo get_stylesheet_directory_uri(); echo '/icons/apple-icon-180x180.png" alt="Whitby Anchor" />';
				whitbyanchor_posted_on();
				whitbyanchor_posted_by();
				echo '</div>';
				echo '<p class="excerpt">' . get_the_excerpt() . '</p>';
				echo '<a class="article-link" href="' . get_the_permalink() . '"><span>Read: '.get_the_title().'</a>';
				echo '</article>';
			endwhile;
			wp_reset_postdata();
		endif;
		?>
		<?php echo do_shortcode( '[newspaper_advert placement="category_bottom" category_id="5"]' ); ?>
	</section>
</main>

<?php
// ── Pass data to JS ───────────────────────────────────────────────────────────
//
// Printed inline so it's available before the external script executes.
$events_config = [
	'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
	'nonce'    => wp_create_nonce( 'whitbyanchor_events' ),
	'perPage'  => $per_page,
	'hasMore'  => $has_more,
	'total'    => $total,
];
?>
<script>
const EventsConfig = <?php echo wp_json_encode( $events_config ); ?>;
</script>

<?php
get_sidebar();
get_footer();