<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package whitbyanchor
 */
get_header();
?>
<main id="primary" class="site-main flow">
	<?php if ( have_posts() ) : ?>

		<header class="page-header">
			<h1 class="page-title">
				<?php
				/* translators: %s: search query. */
				printf( esc_html__( 'Search Results for: %s', 'whitbyanchor' ), '<span>' . get_search_query() . '</span>' );
				?>
			</h1>
		</header>

		<p class="search-events-notice">
			Looking for events? Use the <a href="<?php echo esc_url( home_url( '/whats-on/' ) ); ?>">What's On</a> page to search and filter events.
		</p>

		<section class="articles cards-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'template-parts/content', 'search' ); ?>
			<?php endwhile; ?>
		</section>

		<?php the_posts_navigation(); ?>

	<?php else : ?>
		<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<p class="search-events-notice">
			Looking for events? Use the <a href="<?php echo esc_url( home_url( '/whats-on/' ) ); ?>">What's On</a> page to search and filter events.
		</p>
	<?php endif; ?>
</main>

<?php
get_sidebar();
get_footer();