<?php
get_header();

// ── Fetch all announcements for tag/location counts, render only the first page ──

$per_page       = WHITBYANCHOR_ANNOUNCEMENTS_PER_PAGE;
$all_announcements = get_announcements( [ 'limit' => 1000 ] );

$total      = count( $all_announcements );
$first_page = array_slice( $all_announcements, 0, $per_page );
$has_more   = $total > $per_page;

// ── Build tag & location counts from the full set ─────────────────────────────

$tag_counts      = [];
$location_counts = [];

foreach ( $all_announcements as $announcement ) {
	$post_id = $announcement['post']->ID;
	
	$tags = get_the_terms( $post_id, 'announcement_tag' );
	if ( $tags && ! is_wp_error( $tags ) ) {
		foreach ( $tags as $term ) {
			if ( isset( $tag_counts[ $term->slug ] ) ) {
				$tag_counts[ $term->slug ]['count']++;
			} else {
				$tag_counts[ $term->slug ] = [ 'name' => $term->name, 'slug' => $term->slug, 'count' => 1 ];
			}
		}
	}
}

uasort( $tag_counts,      fn( $a, $b ) => strcasecmp( $a['name'], $b['name'] ) );
?>

<main id="primary" class="site-main business-directory">
	<div>
		<h1>Announcements</h1>
		<p>Announcements of births, deaths and marriages. Announcements are free to place, please <a href="/submit-your-announcement">submit your announcement details here</a>.</p>

		<section class="businesses announcements">
			<header>
				<h2>Filter Announcements</h2>
				
				<?php if ( $tag_counts ) : ?>
					<div>
						<label for="announcement-tag-select">
							<?php esc_html_e( 'Announcement type', 'whitbyanchor' ); ?>
						</label>
						<select id="announcement-tag-select" name="announcement_tag">
							<option value=""><?php esc_html_e( 'Everything', 'whitbyanchor' ); ?></option>
							<?php foreach ( $tag_counts as $tag ) : ?>
								<option value="<?php echo esc_attr( $tag['slug'] ); ?>">
									<?php echo esc_html( $tag['name'] ); ?> (<?php echo intval( $tag['count'] ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
				
				<div class="business-search">
					<label for="announcement-search">Search announcements</label>
					<input type="text" id="announcement-search" />
				</div>
			</header>

			<?php if ( $all_announcements ) : ?>

				<div id="announcements-list">
					<?php foreach ( $first_page as $index => $announcement ) : ?>
						<?php echo whitbyanchor_render_announcement_article( $announcement ); ?>
						<?php if ( $index === 3 ) : ?>
							<div class="ad-wide">
								<?php echo do_shortcode( '[newspaper_advert placement="category_top" category_id="241"]' ); ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>

				<?php if ( $has_more ) : ?>
					<button id="businesses-load-more" type="button"
							data-page="1"
							data-per-page="<?php echo esc_attr( $per_page ); ?>">
						<?php esc_html_e( 'Load more announcements', 'whitbyanchor' ); ?>
					</button>
				<?php endif; ?>

			<?php else : ?>
				<p><?php esc_html_e( 'No announcements found — try changing your filters.', 'whitbyanchor' ); ?></p>
			<?php endif; ?>

		</section>
	</div>

	<section>
		<?php echo do_shortcode( '[newspaper_advert placement="category_top" category_id="241"]' ); ?>

		<?php
		echo '<h2 class="category-heading">News</h2>';

		$news_query = new WP_Query( [
			'category_name'  => 'news',
			'posts_per_page' => 4,
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		if ( $news_query->have_posts() ) :
			while ( $news_query->have_posts() ) :
				$news_query->the_post();
				echo '<article class="flow">';
				echo '<figure>';
				the_post_thumbnail( 'full' );
				echo '</figure>';
				echo '<figcaption>';
				the_post_thumbnail_caption();
				echo '</figcaption>';
				the_title( '<h2>', '</h2>' );
				echo '<div class="entry-meta">';
				echo '<img src="' . get_stylesheet_directory_uri() . '/icons/apple-icon-180x180.png" alt="Whitby Anchor" />';
				whitbyanchor_posted_on();
				whitbyanchor_posted_by();
				echo '</div>';
				echo '<p class="excerpt">' . get_the_excerpt() . '</p>';
				echo '<a class="article-link" href="' . get_the_permalink() . '"><span>Read: ' . get_the_title() . '</a>';
				echo '</article>';
			endwhile;
			wp_reset_postdata();
		endif;
		?>

		<?php echo do_shortcode( '[newspaper_advert placement="category_bottom" category_id="241"]' ); ?>
	</section>
</main>

<?php
get_sidebar();
get_footer();
