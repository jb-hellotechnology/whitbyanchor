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

<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/fancybox/fancybox.css"
/>
<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/carousel/carousel.css"
/>
<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/carousel/carousel.lazyload.css"
/>
<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/carousel/carousel.arrows.css"
/>
<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/carousel/carousel.thumbs.css"
/>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/fancybox/fancybox.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/carousel/carousel.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/carousel/carousel.lazyload.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/carousel/carousel.arrows.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/carousel/carousel.thumbs.umd.js"></script>
<script>
  Carousel(
	document.getElementById("carousel"),
	{
	  // Your custom options
	},
	{
	  Lazyload,
	  Arrows,
	  Thumbs,
	}
  ).init();

  Fancybox.bind("[data-fancybox]", {
	// Your custom options
  });
</script>
<?php newspaper_render_related_posts(); ?>
