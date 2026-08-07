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

get_header();
?>

	<main id="primary" class="site-main">

		<?php
		
		$max_posts  = 7;
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
		echo '<div class="cards-grid">';
		if (!empty($all_posts)) {
		
			$i = 0;
			foreach ($all_posts as $post) {
				setup_postdata($post);
		
				echo '<article class="flow">';
				
				if($i==0){
					the_title('<h2>', '</h2>');
					echo '<figure>';
					if( in_category( 'advertorials' ) ):
					   echo '<span class="advertorial">Advertorial</span>';
					endif;
					the_post_thumbnail('full');
					echo '</figure>';
					echo '<figcaption>';
					the_post_thumbnail_caption();
					echo '</figcaption>';
				}else{
					echo '<figure>';
					if( in_category( 'advertorials' ) ):
					   echo '<span class="advertorial">Advertorial</span>';
					endif;
					the_post_thumbnail('full');
					echo '</figure>';
					echo '<figcaption>';
					the_post_thumbnail_caption();
					echo '</figcaption>';
					the_title('<h2>', '</h2>');
				}
				echo '<div class="entry-meta">';
				echo '<img src="'; echo get_stylesheet_directory_uri(); echo '/icons/apple-icon-180x180.png" alt="Whitby Anchor" />';
				whitbyanchor_posted_on();
				whitbyanchor_posted_by();
				echo '</div>';
				echo '<p class="excerpt">' . get_the_excerpt() . '</p>';
				echo '<a class="article-link" href="' . get_the_permalink() . '"><span>Read: '.get_the_title().'</a>';
				echo '</article>';
				$i++;
			}
		
			wp_reset_postdata();
		}
		echo '</div>';
		echo '</section>';
		
		echo '<div class="ad-wide">';
		echo do_shortcode('[newspaper_advert placement="category_top" category_id="31"]');
		echo '</div>';

		echo '<h2>The Whitby Cast</h2>';
		echo "<div id='buzzsprout-player-small'></div><script type='text/javascript' charset='utf-8' src='https://www.buzzsprout.com/2609710.js?container_id=buzzsprout-player-small&player=small&limit=1'></script>";
		
		echo '<p><strong>Subscribe to our podcast</strong></p>';
		echo '<p class="subscribe"><a href="https://open.spotify.com/show/4LUyXg2gZ1p9HbbxRT51gF" class="button icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M320 72C183 72 72 183 72 320C72 457 183 568 320 568C457 568 568 457 568 320C568 183 457 72 320 72zM420.7 436.9C416.5 436.9 413.9 435.6 410 433.3C347.6 395.7 275 394.1 203.3 408.8C199.4 409.8 194.3 411.4 191.4 411.4C181.7 411.4 175.6 403.7 175.6 395.6C175.6 385.3 181.7 380.4 189.2 378.8C271.1 360.7 354.8 362.3 426.2 405C432.3 408.9 435.9 412.4 435.9 421.5C435.9 430.6 428.8 436.9 420.7 436.9zM447.6 371.3C442.4 371.3 438.9 369 435.3 367.1C372.8 330.1 279.6 315.2 196.7 337.7C191.9 339 189.3 340.3 184.8 340.3C174.1 340.3 165.4 331.6 165.4 320.9C165.4 310.2 170.6 303.1 180.9 300.2C208.7 292.4 237.1 286.6 278.7 286.6C343.6 286.6 406.3 302.7 455.7 332.1C463.8 336.9 467 343.1 467 351.8C466.9 362.6 458.5 371.3 447.6 371.3zM478.6 295.1C473.4 295.1 470.2 293.8 465.7 291.2C394.5 248.7 267.2 238.5 184.8 261.5C181.2 262.5 176.7 264.1 171.9 264.1C158.7 264.1 148.6 253.8 148.6 240.5C148.6 226.9 157 219.2 166 216.6C201.2 206.3 240.6 201.4 283.5 201.4C356.5 201.4 433 216.6 488.9 249.2C496.7 253.7 501.8 259.9 501.8 271.8C501.8 285.4 490.8 295.1 478.6 295.1z"/></svg>Spotify</a><a href="https://podcasts.apple.com/us/podcast/the-whitby-cast/id1895754773" class="button icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M447.1 332.7C446.9 296 463.5 268.3 497.1 247.9C478.3 221 449.9 206.2 412.4 203.3C376.9 200.5 338.1 224 323.9 224C308.9 224 274.5 204.3 247.5 204.3C191.7 205.2 132.4 248.8 132.4 337.5C132.4 363.7 137.2 390.8 146.8 418.7C159.6 455.4 205.8 545.4 254 543.9C279.2 543.3 297 526 329.8 526C361.6 526 378.1 543.9 406.2 543.9C454.8 543.2 496.6 461.4 508.8 424.6C443.6 393.9 447.1 334.6 447.1 332.7zM390.5 168.5C417.8 136.1 415.3 106.6 414.5 96C390.4 97.4 362.5 112.4 346.6 130.9C329.1 150.7 318.8 175.2 321 202.8C347.1 204.8 370.9 191.4 390.5 168.5z"/></svg>Apple</a></p>';
		
		$args = array(
			'category_name' => 'features',
			'posts_per_page' => 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		
		$query = new WP_Query($args);
		
		echo '<section class="articles features">';
		echo '<header><h2 class="section-heading">Features</h2></header>';
		echo '<div class="cards-grid features">';
		if ($query->have_posts()) {
		
			while ($query->have_posts()) {
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
				if( in_category( 'advertorials' ) ):
				   echo '<span class="advertorial">Advertorial</span>';
				endif;
				echo '</article>';
			}
		
			wp_reset_postdata();
		}
		echo '</div>';
		echo '</section>';
		
		// $args = array(
		// 	'category_name' => 'villages',
		// 	'posts_per_page' => 3,
		// 	'orderby'        => 'date',
		// 	'order'          => 'DESC',
		// );
		// 
		// $query = new WP_Query($args);
		// 
		// echo '<section class="articles">';
		// echo '<header><h2 class="section-heading">The Villages</h2></header>';
		// echo '<div class="cards-grid">';
		// if ($query->have_posts()) {
		// 
		// 	while ($query->have_posts()) {
		// 		$query->the_post();
		// 		
		// 		echo '<article class="flow">';
		// 		echo '<figure>';
		// 		the_post_thumbnail('full');
		// 		echo '</figure>';
		// 		echo '<figcaption>';
		// 		the_post_thumbnail_caption();
		// 		echo '</figcaption>';
		// 		the_title('<h2>', '</h2>');
		// 		echo '<div class="entry-meta">';
		// 		echo '<img src="'; echo get_stylesheet_directory_uri(); echo '/icons/apple-icon-180x180.png" alt="Whitby Anchor" />';
		// 		whitbyanchor_posted_on();
		// 		whitbyanchor_posted_by();
		// 		echo '</div>';
		// 		echo '<p class="excerpt">' . get_the_excerpt() . '</p>';
		// 		echo '<a class="article-link" href="' . get_the_permalink() . '"><span>Read: '.get_the_title().'</a>';
		// 		echo '</article>';
		// 	}
		// 
		// 	wp_reset_postdata();
		// }
		// echo '</div>';
		// echo '</section>';
		
		echo '<div class="ad-wide">';
		echo do_shortcode('[newspaper_advert placement="category_bottom" category_id="31"]');
		echo '</div>';
		
		the_post();
		get_template_part( 'template-parts/content-home', 'page' );
		?>

	</main><!-- #main -->

<?php
get_sidebar();
get_footer();
