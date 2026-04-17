<?php
/**
 * The template for displaying all pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 * @package whitbyanchor
 */
get_header();
?>
	<main id="primary" class="site-main">
		<?php

		$category    = get_queried_object();
		$slug        = $category->slug;
		$show_events = get_term_meta($category->term_id, 'show_events', true) === '1';

		echo '<h1 class="category-heading">' . $category->cat_name . '</h1>';

		echo '<section class="articles news'; if ($show_events) { echo ' has-events';} echo '">';
		
		if ($show_events) {
			echo '<section class="articles">';
		}

			$args = array(
				'category_name'  => $slug,
				'posts_per_page' => 20,
				'orderby'        => 'date',
				'order'          => 'DESC',
			);

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
					echo '<a class="article-link" href="' . get_the_permalink() . '"><span>Read: '.get_the_title().'</a>';
					echo '</article>';
				}

				wp_reset_postdata();
			}
			
			echo '</section>';

			if ($show_events) {

				if($slug=='wellbeing'){
					$events = get_events([
						'tag'  => $slug,
						'from_date' => current_time('Y-m-d'),
						'limit'     => 10,
					]);
				}else{
					$events = get_events([
						'location'  => $slug,
						'from_date' => current_time('Y-m-d'),
						'limit'     => 10,
					]);	
				}
				
				echo '<section class="events">';
				
				$category_id = $category ? $category->term_id : null;
				echo do_shortcode('[newspaper_advert placement="category_top" category_id="' . $category_id . '"]');
				
				$pinned_post = whitbyanchor_get_pinned_category_post($category->term_id);
				if ($pinned_post) {
					echo '<article class="flow">';
					the_post_thumbnail('full');
					the_title('<h2>', '</h2>');
					echo '<div class="entry-meta">';
					whitbyanchor_posted_on();
					whitbyanchor_posted_by();
					echo '</div>';
					echo '<p class="excerpt">' . get_the_excerpt() . '</p>';
					echo '<a class="article-link" href="' . get_the_permalink() . '"><span>Read: '.get_the_title().'</a>';
					echo '</article>';
				}

				echo '<h2 class="category-heading">Events in ' . $category->cat_name . '</h2>';

				if ($events) :
					foreach ($events as $event) :
						$post = $event['post'];
						?>
						<article class="flow event <?php if ( $has_image ) : ?>premium<?php endif; ?>">
							<?php if ( $has_image ) : ?>
							<div class="event-media__image">
								<?php echo get_the_post_thumbnail( $post->ID, 'large' ); ?>
							</div>
							<?php endif; ?>
							<div>
								<h2><?php echo esc_html($post->post_title); ?></h2>

								<p class="event-excerpt"><?php echo esc_html($post->post_excerpt); ?></p>
						
								<div class="meta">
									<?php if ($event['venue']) : ?>
										<p class="event-venue">
											<span class="material-symbols-outlined">location_on</span>
											<?php echo esc_html($event['venue']); ?>
										</p>
									<?php endif; ?>
		
									<?php if ($event['recurring']) : ?>
										<p class="event-recurring">Repeats <?php echo esc_html($event['recurring']); ?></p>
									<?php endif; ?>
		
									<p class="event-date">
										<span class="material-symbols-outlined">calendar_clock</span>
										<?php echo esc_html($event['date_label']); ?>
										<?php if ($event['start_time']) : ?>
											at <?php echo esc_html(date('g:i A', strtotime($event['start_time']))); ?>
											<?php if ($event['end_time']) : ?>
												&ndash; <?php echo esc_html(date('g:i A', strtotime($event['end_time']))); ?>
											<?php endif; ?>
										<?php endif; ?>
									</p>
								</div>
							</div>

							<a class="event-link" href="<?php echo esc_url(get_permalink($post->ID) . '?date=' . $event['date']); ?>"></a>
						</article>
					<?php endforeach; ?>
				<?php else : ?>
					<p>No upcoming events.</p>
				<?php endif; ?>
				<?php echo '<p><a class="button more-events" href="/events">More Events</a></p>';?>
				<br />
				<?php echo do_shortcode('[newspaper_advert placement="category_bottom" category_id="' . $category_id . '"]'); ?>
				<?php echo '</section>'; // .events 

			} // end if ($show_events)

		if ($show_events) {
			echo '</section>'; // .articles.villages
		}
		

		?>
	</main><!-- #main -->
<?php
get_sidebar();
get_footer();