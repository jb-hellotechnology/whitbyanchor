<?php
/**
 * The Advertising Rates page.
 *
 * Prices, packages and placements all come from inc/advertising-rates.php —
 * edit them there rather than in this template.
 *
 * @package whitbyanchor
 */

$contact_url = '/contact-the-whitby-anchor/';
$supplied    = wa_advertising_supplied_stats();
$derived     = wa_advertising_derived_stats();
$since       = wa_advertising_publishing_since();
$can_edit    = current_user_can( 'edit_pages' );

// Recent articles make the hero mosaic — real, current, and never out of date.
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

<main id="primary" class="site-main ad-rates">

	<?php
	while ( have_posts() ) :
		the_post();
		?>

	<article <?php post_class(); ?>>

		<!-- Hero -->
		<header class="ad-hero">
			<div class="ad-hero__copy flow">
				<p class="ad-eyebrow">Advertise with The Anchor</p>
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				<p class="ad-lede">Showcase your business to a captive audience. We are keen to work with and promote local businesses, with bespoke advertising packages available.</p>
				<div class="ad-hero__actions">
					<a class="button secondary" href="<?php echo esc_url( $contact_url ); ?>">Talk to us about advertising</a>
					<a class="button ad-button-ghost" href="#packages">See packages and prices</a>
				</div>
				<?php if ( $since ) : ?>
					<p class="ad-hero__since">Publishing independent local news from Whitby since <?php echo esc_html( $since ); ?>.</p>
				<?php endif; ?>
			</div>

			<?php if ( count( $recent ) >= 2 ) : ?>
				<div class="ad-hero__mosaic" aria-hidden="true">
					<?php foreach ( array_slice( $recent, 0, 3 ) as $item ) : ?>
						<figure>
							<?php echo get_the_post_thumbnail( $item->ID, 'medium_large' ); ?>
						</figure>
					<?php endforeach; ?>
					<span class="ad-hero__mosaic-label">Published this week on The Anchor</span>
				</div>
			<?php endif; ?>
		</header>

		<!-- By the numbers -->
		<?php
		$public_stats = array();
		$missing      = array();

		foreach ( $supplied as $key => $stat ) {
			if ( null !== $stat['value'] && '' !== $stat['value'] ) {
				$public_stats[ $key ] = $stat;
			} else {
				$missing[ $key ] = $stat;
			}
		}

		$public_stats = array_merge( $public_stats, $derived );
		?>

		<?php if ( $public_stats ) : ?>
			<section class="ad-stats" aria-labelledby="ad-stats-heading">
				<h2 id="ad-stats-heading">The Anchor by numbers</h2>
				<ul class="ad-stats__grid">
					<?php foreach ( $public_stats as $stat ) : ?>
						<li>
							<span class="ad-stat__value"><?php echo esc_html( number_format_i18n( (int) $stat['value'] ) ); ?></span>
							<span class="ad-stat__figure"><?php echo esc_html( $stat['figure'] ); ?></span>
							<span class="ad-stat__label"><?php echo esc_html( $stat['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $missing && $can_edit ) : ?>
			<div class="ad-editor-note">
				<p><strong>Only you can see this.</strong> These audience figures are not on the public page yet because no value has been set. Add them in <code>inc/advertising-rates.php</code> and they will appear in the band above.</p>
				<ul>
					<?php foreach ( $missing as $key => $stat ) : ?>
						<li><code><?php echo esc_html( $key ); ?></code> — <?php echo esc_html( $stat['figure'] ); ?> <em>(<?php echo esc_html( $stat['source'] ); ?>)</em></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<!-- Why advertise -->
		<section class="ad-reasons" aria-labelledby="ad-reasons-heading">
			<h2 id="ad-reasons-heading">Why advertise with The Anchor?</h2>
			<ul class="ad-reasons__grid">
				<?php foreach ( wa_advertising_reasons() as $reason ) : ?>
					<li class="ad-reason">
						<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( $reason['icon'] ); ?></span>
						<h3><?php echo esc_html( $reason['title'] ); ?></h3>
						<p><?php echo esc_html( $reason['copy'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<!-- Where your advert appears -->
		<section class="ad-placements" aria-labelledby="ad-placements-heading">
			<h2 id="ad-placements-heading">Where your advert appears</h2>
			<p class="ad-section-intro">Four slots, each doing a different job. We will advise on which suits your business — or mix them.</p>
			<ul class="ad-placements__grid">
				<?php foreach ( wa_advertising_placements() as $placement ) : ?>
					<li class="ad-placement">
						<div class="ad-wireframe ad-wireframe--<?php echo esc_attr( $placement['shape'] ); ?>" aria-hidden="true">
							<span class="ad-wireframe__bar"></span>
							<span class="ad-wireframe__bar"></span>
							<span class="ad-wireframe__slot">Your advert</span>
							<span class="ad-wireframe__bar"></span>
							<span class="ad-wireframe__bar"></span>
						</div>
						<h3><?php echo esc_html( $placement['name'] ); ?></h3>
						<p><?php echo esc_html( $placement['blurb'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<!-- Packages -->
		<section id="packages" class="ad-packages" aria-labelledby="ad-packages-heading">
			<h2 id="ad-packages-heading">Packages and prices</h2>
			<p class="ad-section-intro">All prices exclude VAT. Bespoke packages are available — if you cannot see what you need, ask.</p>

			<?php foreach ( wa_advertising_packages() as $group ) : ?>
				<div class="ad-group">
					<div class="ad-group__header flow">
						<h3><?php echo esc_html( $group['title'] ); ?></h3>
						<p><?php echo esc_html( $group['intro'] ); ?></p>
					</div>

					<ul class="ad-group__cards">
						<?php foreach ( $group['cards'] as $card ) : ?>
							<li class="ad-card<?php echo ! empty( $card['featured'] ) ? ' ad-card--featured' : ''; ?>">
								<?php if ( ! empty( $card['badge'] ) ) : ?>
									<span class="ad-card__badge"><?php echo esc_html( $card['badge'] ); ?></span>
								<?php endif; ?>

								<h4 class="ad-card__name"><?php echo esc_html( $card['name'] ); ?></h4>
								<p class="ad-card__blurb"><?php echo esc_html( $card['blurb'] ); ?></p>

								<ul class="ad-card__prices">
									<?php foreach ( $card['prices'] as $price ) : ?>
										<li<?php echo ! empty( $price['best'] ) ? ' class="is-best"' : ''; ?>>
											<span class="ad-price__term"><?php echo esc_html( $price['term'] ); ?></span>
											<span class="ad-price__value"><?php echo esc_html( $price['price'] ); ?></span>
											<?php if ( ! empty( $price['sub'] ) ) : ?>
												<span class="ad-price__sub"><?php echo esc_html( $price['sub'] ); ?></span>
											<?php endif; ?>
											<?php if ( ! empty( $price['best'] ) ) : ?>
												<span class="ad-price__best">Best value</span>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>

								<ul class="ad-card__features">
									<?php foreach ( $card['features'] as $feature ) : ?>
										<li><span class="material-symbols-outlined" aria-hidden="true">check</span><?php echo esc_html( $feature ); ?></li>
									<?php endforeach; ?>
								</ul>

								<a class="button<?php echo ! empty( $card['featured'] ) ? ' secondary' : ''; ?> ad-card__cta" href="<?php echo esc_url( $contact_url ); ?>">
									Enquire about <?php echo esc_html( $card['name'] ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</section>

		<!-- Anything the page itself still carries from the editor -->
		<?php
		$extra = trim( get_the_content() );
		if ( $extra ) :
			?>
			<section class="ad-extra entry-content flow">
				<?php the_content(); ?>
			</section>
		<?php endif; ?>

		<!-- Closing call to action -->
		<section class="ad-cta">
			<div class="flow">
				<h2>Reach engaged readers with your message</h2>
				<p>Tell us about your business and we will put together a package that fits — including placement advice, so your advert lands where it counts.</p>
				<a class="button secondary" href="<?php echo esc_url( $contact_url ); ?>">Contact The Anchor</a>
			</div>
		</section>

	</article>

	<?php endwhile; ?>

</main><!-- #main -->

<?php
get_footer();
