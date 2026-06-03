<?php
/**
 * Template Name: Submit a Business
 */

get_header();
?>

<main id="primary" class="site-main">
	<article <?php post_class(); ?>>
		<div class="entry-content flow">
			<?php
			the_content();
			// If the page body doesn't include the shortcode, render it here.
			if ( ! has_shortcode( get_post()->post_content, 'business_submit_form' ) ) {
				echo do_shortcode( '[business_submit_form]' );
			}
			?>
		</div>
	</article>
</main>

<?php
get_footer();
