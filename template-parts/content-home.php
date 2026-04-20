<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package whitbyanchor
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header flow">
		<h2>The Week in Pictures</h2>
	</header><!-- .entry-header -->

	<?php whitbyanchor_post_thumbnail(); ?>

	<div class="entry-content flow week-in-pictures npg-gallery-init" id="carousel">
		<?php
		the_content(
			sprintf(
				wp_kses(
					/* translators: %s: Name of current post. Only visible to screen readers */
					__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'whitbyanchor' ),
					array(
						'span' => array(
							'class' => array(),
						),
					)
				),
				wp_kses_post( get_the_title() )
			)
		);
		?>
	</div><!-- .entry-content -->
</article>

<!-- Add these in your <head> or before </body> -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.css"/>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.umd.js"></script>

<script>
  document.addEventListener("DOMContentLoaded", () => {
	const container = document.getElementById("carousel");

	// Fancyapps Carousel expects .f-carousel__slide wrapper elements,
	// so we wrap each existing <figure> on the fly.
	container.querySelectorAll("figure").forEach((fig) => {
	  const slide = document.createElement("div");
	  slide.className = "f-carousel__slide";
	  fig.parentNode.insertBefore(slide, fig);
	  slide.appendChild(fig);
	});

	new Carousel(container, {
	  infinite: true,
	  center: true,
	  slidesPerPage: 1,
	  transition: "slide",
	  Dots: true,
	  Navigation: {
		prevTpl: "&#8592;",
		nextTpl: "&#8594;",
	  },
	});
  });
</script>
<?php newspaper_render_related_posts(); ?>
