<?php
get_header();

$events = get_events( [
	'from_date' => current_time( 'Y-m-d' ),
	'limit'     => 1000,
] );
?>

<main id="primary" class="site-main events">
	<h1>Whitby Events</h1>
	<?php if ( $events ) : ?>
	<?php
	// Build tag counts from the visible events
	$tag_counts = [];
	foreach ( $events as $event ) {
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
					'url'   => get_term_link( $term ),
				];
			}
		}
	}
	?>
	<section class="events">
		<h2>Filter by Tag</h2>
		<?php if ( $tag_counts ) : ?>
		<label for="event-tag-select" class="screen-reader-text"><?php esc_html_e( 'Filter by tag', 'whitbyanchor' ); ?></label>
		<select id="event-tag-select" name="event_tag">
			<option value=""><?php esc_html_e( 'All Tags', 'whitbyanchor' ); ?></option>
			<?php foreach ( $tag_counts as $tag ) : ?>
				<option value="<?php echo esc_attr( $tag['slug'] ); ?>">
					<?php echo esc_html( $tag['name'] ); ?> (<?php echo absint( $tag['count'] ); ?>)
				</option>
			<?php endforeach; ?>
		</select>
		<?php endif; ?>
			<?php foreach ( $events as $event ) :
				$post = $event['post'];
				$tags = get_the_terms( $post->ID, 'event_tag' );
				
				$tags_string = '';
				if ( $tags && ! is_wp_error( $tags ) ) :
					foreach ( $tags as $tag ) :
						$tags_string .= esc_attr( $tag->slug ) . ',';
					endforeach;
				endif;
			?>
			<article class="flow event" data-tags="<?= $tags_string ?>">
				<h2><?php echo esc_html( $post->post_title ); ?></h2>
				
				<p class="event-excerpt"><?php echo esc_html( $post->post_excerpt ); ?></p>

				<?php if ( $event['venue'] ) : ?>
					<p class="event-venue">
					<span class="material-symbols-outlined">
					location_on
					</span>
					<?php echo esc_html( $event['venue'] ); ?></p>
				<?php endif; ?>

				<?php if ( $event['recurring'] ) : ?>
					<p class="event-recurring">Repeats <?php echo esc_html( $event['recurring'] ); ?></p>
				<?php endif; ?>
				
				<p class="event-date">
					<span class="material-symbols-outlined">
					calendar_clock
					</span>
					<?php echo esc_html( $event['date_label'] ); ?>
					<?php if ( $event['start_time'] ) : ?>
						at <?php echo esc_html( date( 'g:i A', strtotime( $event['start_time'] ) ) ); ?>
						<?php if ( $event['end_time'] ) : ?>
							– <?php echo esc_html( date( 'g:i A', strtotime( $event['end_time'] ) ) ); ?>
						<?php endif; ?>
					<?php endif; ?>
				</p>
				
				<a class="event-link" href="<?php echo esc_url( get_permalink( $post->ID ) . '?date=' . $event['date'] ); ?>"></a>
			</article>
		<?php endforeach; ?>
	<?php else : ?>
		<p>No upcoming events.</p>
	<?php endif; ?>
	</section>
	<section>
		<?php
		echo do_shortcode('[newspaper_advert placement="category" category_id="24"]');
		
		$args = array(
			'category_name' => 'events',
			'posts_per_page' => 4,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		
		echo '<h2 class="category-heading">Events News</h2>';
		
		$query = new WP_Query($args);
		
		if ($query->have_posts()) {
		
			while ($query->have_posts()) {
				$query->the_post();
				
				echo '<article class="flow">';
				the_post_thumbnail('full');
				the_title('<h2>', '</h2>');
				echo '<div class="entry-meta">';
				whitbyanchor_posted_on();
				whitbyanchor_posted_by();
				echo '</div>';
				echo '<p class="excerpt">' . get_the_excerpt() . '</p>';
				echo '<a class="article-link flow" href="'.get_the_permalink().'"></a>';
				echo '</article>';
			}
		
			wp_reset_postdata();
		}
		?>
	</section>
</main>

<script>
document.getElementById('event-tag-select').addEventListener('change', function () {
	const selected = this.value.trim();
	const articles = document.querySelectorAll('article.event');

	articles.forEach(function (article) {
		if (!selected) {
			article.hidden = false;
			return;
		}

		const tags = article.dataset.tags
			.split(',')
			.map(t => t.trim())
			.filter(Boolean);

		article.hidden = !tags.includes(selected);
	});
});
</script>

<?php
get_sidebar();
get_footer();