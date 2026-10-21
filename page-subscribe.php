<?php
/**
 * The Subscribe page.
 *
 * The hero, figures and reasons come from inc/subscribe-page.php. Everything
 * between them is the page's own content block, edited in WordPress — that is
 * where the monthly offer, the Stripe buy button and the free mailing list
 * button live, so it is rendered untouched.
 *
 * @package whitbyanchor
 */

$stats    = function_exists( 'wa_subscribe_stats' ) ? wa_subscribe_stats() : array();
$since    = function_exists( 'wa_advertising_publishing_since' ) ? wa_advertising_publishing_since() : '';

// Recent journalism, to show what supporting The Anchor actually pays for.
$recent = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'meta_key'       => '_thumbnail_id',
	)
);

get_header();
?>

<main id="primary" class="site-main ad-layout subscribe-page">

	<?php
	while ( have_posts() ) :
		the_post();
		?>

	<article <?php post_class(); ?>>

		<!-- Hero -->
		<header class="ad-hero">
			<div class="ad-hero__copy flow">
				<p class="ad-eyebrow">Support independent journalism</p>
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				<p class="ad-lede">Quality reporting for Whitby, free to read for everyone — no pop-ups, no obstructive advertising and no paywall. Our supporters are what keep it that way.</p>
				<div class="ad-hero__actions">
					<a class="button secondary" href="#support">Become a supporter</a>
					<button class="button ad-button-ghost mailing-list-trigger" type="button">Join the free mailing list</button>
				</div>
				<?php if ( $since ) : ?>
					<p class="ad-hero__since">Reporting on Whitby and the villages since <?php echo esc_html( $since ); ?>.</p>
				<?php endif; ?>
			</div>

			<?php if ( count( $recent ) >= 2 ) : ?>
				<div class="ad-hero__mosaic">
					<?php foreach ( array_slice( $recent, 0, 3 ) as $item ) : ?>
						<figure>
							<a href="<?php echo esc_url( get_permalink( $item->ID ) ); ?>">
								<?php echo get_the_post_thumbnail( $item->ID, 'medium_large' ); ?>
								<figcaption><span><?php echo esc_html( get_the_title( $item->ID ) ); ?></span></figcaption>
							</a>
						</figure>
					<?php endforeach; ?>
					<span class="ad-hero__mosaic-label">What your support pays for</span>
				</div>
			<?php endif; ?>
		</header>

		<!-- By the numbers -->
		<?php if ( $stats ) : ?>
			<section class="ad-stats" aria-labelledby="sub-stats-heading">
				<h2 id="sub-stats-heading">The Anchor by numbers</h2>
				<ul class="ad-stats__grid">
					<?php foreach ( $stats as $stat ) : ?>
						<li>
							<span class="ad-stat__value"><?php echo esc_html( number_format_i18n( (int) $stat['value'] ) ); ?></span>
							<span class="ad-stat__figure"><?php echo esc_html( $stat['figure'] ); ?></span>
							<span class="ad-stat__label"><?php echo esc_html( $stat['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<!-- What supporters get -->
		<section class="ad-reasons" aria-labelledby="sub-reasons-heading">
			<h2 id="sub-reasons-heading">What you get as a supporter</h2>
			<ul class="ad-reasons__grid">
				<?php foreach ( wa_subscribe_reasons() as $reason ) : ?>
					<li class="ad-reason">
						<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( $reason['icon'] ); ?></span>
						<h3><?php echo esc_html( $reason['title'] ); ?></h3>
						<p><?php echo esc_html( $reason['copy'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<!--
			The page's own content block. Edited in WordPress and rendered as-is:
			this month's offer, the pricing columns, the Stripe buy button and the
			manage-subscription link all live here.
		-->
		<section class="sub-content entry-content flow">
			<?php
			the_content();

			wp_link_pages(
				array(
					'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'whitbyanchor' ),
					'after'  => '</div>',
				)
			);
			?>
		</section>

		<!-- Closing call to action -->
		<section class="ad-cta">
			<div class="flow">
				<h2>Journalism Whitby can rely on</h2>
				<p>Every supporter helps us keep reporting on the town — accurately, independently and free for everyone to read.</p>
				<a class="button secondary" href="#support">Become a supporter</a>
			</div>
		</section>

	</article>

	<?php endwhile; ?>

</main><!-- #main -->

<?php
get_footer();
