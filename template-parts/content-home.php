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

	<div class="entry-content flow">
		<?php
		if ( 'post' === get_post_type() ) :
			?>
			<div class="entry-meta">
				<img src="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-180x180.png" alt="Whitby Anchor" />
				<?php
				whitbyanchor_posted_on();
				whitbyanchor_posted_by();
				?>
			</div><!-- .entry-meta -->
		<?php endif;
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

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'whitbyanchor' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->
</article>
<?php newspaper_render_related_posts(); ?>
