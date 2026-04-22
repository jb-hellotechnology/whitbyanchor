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

	<main id="primary" class="site-main narrow">

		<?php
		while ( have_posts() ) :
			the_post();

			get_template_part( 'template-parts/content', 'page' );

			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;

		endwhile; // End of the loop.
		?>
		<div class="flow">
			<script async
			src="https://js.stripe.com/v3/buy-button.js">
			</script>
			
			<stripe-buy-button
			buy-button-id="buy_btn_1TP2aIQfYFXNIHgHeP9JB4up"
			publishable-key="pk_live_51TOxkcQfYFXNIHgHXS7ZdrXhOs2zDIvQPqivsuZFA2HEDRUPznk3IGcJoj1coVbA0ydI5NVzbrU5CAEasK8DLwEU005YIDWfQw"
			>
			</stripe-buy-button>
			
			<h3>Manage Your Subscription</h3>
			<p>Already have an active subscription? <a href="https://billing.stripe.com/p/login/5kQ6oJfpCduw0PA88P4Ja00">Click here</a> to manage it.</p>
		</div>
	</main><!-- #main -->

<?php
get_sidebar();
get_footer();
