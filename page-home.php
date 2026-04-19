<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package whitbyanchor
 */

$current_user = wp_get_current_user();
if(!user_can( $current_user, 'administrator' )){
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>The Whitby Anchor</title>
  <style>
	  body,
	  html{
		  background:#f8fcff;
	  }
  </style>
</head>

<body>
  <iframe width="100%" height="705" src="https://55fe7bef.sibforms.com/serve/MUIFAIeXAHraeDk2IDTqXBbnTxccfwcydbERN_k-OzxGVxbQklj7ytQ1fnnaU7wrOF1NWL483bArbj6qpZDU2gMmtX6ilrUq_pZ3z93gHqkjWM1vmMkUd8q7lX42N3jbz-UTF8OXcdqK9bW37WFOta1SaQtHaT33wlWngHbYouJAo2MD2o_2ImstD5XJl1qONWSgUAd_8RLhxMvaRQ==" frameborder="0" scrolling="auto" allowfullscreen style="display: block;margin-left: auto;margin-right: auto;max-width: 100%;"></iframe>
</body>

</html>
<?php
}else{
	get_header();
?>

	<main id="primary" class="site-main">

		<?php
		
		$max_posts  = 3;
		$post_ids_displayed = array();
		
		// --- Query 1: Pinned posts in this category ---
		$pinned_args = array(
			'category_name'  => 'home',
			'posts_per_page' => $max_posts,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_is_pinned',
					'value' => '1',
				),
			),
		);
		
		$pinned_query = new WP_Query($pinned_args);
		$pinned_posts = $pinned_query->posts; // WP_Post objects
		
		foreach ($pinned_posts as $post) {
			$post_ids_displayed[] = $post->ID;
		}
		
		// --- Query 2: Fill remaining slots with latest non-pinned posts ---
		$remaining = $max_posts - count($pinned_posts);
		$regular_posts = array();
		
		if ($remaining > 0) {
			$regular_args = array(
				'category_name'  => 'home',
				'posts_per_page' => $remaining,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post__not_in'   => $post_ids_displayed, // exclude already-pinned posts
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_is_pinned',
						'value'   => '1',
						'compare' => '!=',
					),
					array(
						'key'     => '_is_pinned',
						'compare' => 'NOT EXISTS', // handles posts that were never set
					),
				),
			);
		
			$regular_query  = new WP_Query($regular_args);
			$regular_posts  = $regular_query->posts;
		}
		
		// --- Merge and display ---
		$all_posts = array_merge($pinned_posts, $regular_posts);
		
		echo '<section class="articles home">';
		echo '<header><h2 class="section-heading">Latest News</h2></header>';
		
		if (!empty($all_posts)) {
		
			foreach ($all_posts as $post) {
				setup_postdata($post);
		
				echo '<article class="flow">';
				the_post_thumbnail('full');
				the_title('<h2>', '</h2>');
				echo '<div class="entry-meta">';
				echo '<img src="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-180x180.png" alt="Whitby Anchor" />';
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
		
		echo '<div class="ad-wide">';
		echo do_shortcode('[newspaper_advert placement="category_top" category_id="31"]');
		echo '</div>';
		
		$args = array(
			'category_name' => 'features',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		
		$query = new WP_Query($args);
		
		echo '<section class="articles features">';
		echo '<header><h2 class="section-heading">Features</h2></header>';
		
		if ($query->have_posts()) {
		
			while ($query->have_posts()) {
				$query->the_post();
				
				echo '<article class="flow">';
				the_post_thumbnail('full');
				the_title('<h2>', '</h2>');
				echo '<div class="entry-meta">';
				echo '<img src="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-180x180.png" alt="Whitby Anchor" />';
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
		
		$args = array(
			'category_name' => 'villages',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		
		$query = new WP_Query($args);
		
		echo '<section class="articles">';
		echo '<header><h2 class="section-heading">The Villages</h2></header>';
		
		if ($query->have_posts()) {
		
			while ($query->have_posts()) {
				$query->the_post();
				
				echo '<article class="flow">';
				the_post_thumbnail('full');
				the_title('<h2>', '</h2>');
				echo '<div class="entry-meta">';
				echo '<img src="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-180x180.png" alt="Whitby Anchor" />';
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
		
		echo '<div class="ad-wide">';
		echo do_shortcode('[newspaper_advert placement="category_bottom" category_id="31"]');
		echo '</div>';
		
		npg_render_latest_gallery();
		?>

	</main><!-- #main -->

<?php
get_sidebar();
get_footer();
}
