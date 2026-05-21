<?php
get_header();
$post_id 	 = get_queried_object_id();

$address        = get_post_meta( $post_id, '_business_address', true );
$phone          = get_post_meta( $post_id, '_business_phone', true );
$email          = get_post_meta( $post_id, '_business_email', true );
$website        = get_post_meta( $post_id, '_business_website', true );
$lat            = get_post_meta( $post_id, '_business_lat', true );
$lng            = get_post_meta( $post_id, '_business_lng', true );
$has_map        = $lat && $lng;
$has_image      = has_post_thumbnail();
$use_two_column = $has_map && $has_image;
?>
<main id="primary" class="site-main">
	<article class="flow">
		<h1><?php the_title(); ?></h1>
		<?php echo '<p><strong>' . get_the_excerpt() . '</strong></p>'; ?>

		
		
		<ul class="business-card__contacts">
			<?php if ( $address ) : ?>
				<li>
					<?php echo esc_html( $address ); ?>
				</li>
			<?php endif; ?>
			<?php if ( $phone ) : ?>
				<li>
					<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>">
						<?php echo esc_html( $phone ); ?>
					</a>
				</li>
			<?php endif; ?>
			<?php if ( $email ) : ?>
				<li>
					<a href="mailto:<?php echo esc_attr( $email ); ?>">
						<?php echo esc_html( $email ); ?>
					</a>
				</li>
			<?php endif; ?>
			<?php if ( $website ) : ?>
				<li>
					<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( preg_replace( '#^https?://#', '', rtrim( $website, '/' ) ) ); ?>
					</a>
				</li>
			<?php endif; ?>
		</ul>
		
		<div class="event-content flow">
			<?php the_content(); ?>
		</div>

		<?php if ( $has_map || $has_image ) : ?>
			<div class="event-media<?php echo $use_two_column ? ' event-media--two-col' : ''; ?>">

				<?php if ( $has_image ) : ?>
					<div class="event-media__image">
						<?php the_post_thumbnail( 'large' ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $has_map ) : ?>
					<div class="event-media__map">
						<div id="event-map"></div>
					</div>
					<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
					<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
					<script>
						document.addEventListener('DOMContentLoaded', function () {
							var map = L.map('event-map', { scrollWheelZoom: false }).setView([<?php echo esc_js( $lat ); ?>, <?php echo esc_js( $lng ); ?>], 15);
							L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
								attribution: '© OpenStreetMap contributors'
							}).addTo(map);
							L.marker([<?php echo esc_js( $lat ); ?>, <?php echo esc_js( $lng ); ?>]).addTo(map);
						});
					</script>
				<?php endif; ?>

			</div>
		<?php endif; ?>
		
		<div class="flow">
			<p>We strive to keep business details up to date. However, changes are beyond our control. Please <a href="/contact-the-whitby-anchor">contact us</a> if your event details need amending.</p>
		</div>
	</article>
</main>
<?php
get_sidebar();
get_footer();