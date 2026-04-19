<?php
get_header();
$post_id 	 = get_queried_object_id();
$start_date  = get_post_meta( $post_id, '_event_start_date', true );
$start_time  = get_post_meta( $post_id, '_event_start_time', true );
$end_date    = get_post_meta( $post_id, '_event_end_date', true );
$end_time    = get_post_meta( $post_id, '_event_end_time', true );
$venue       = get_post_meta( $post_id, '_event_venue', true );
$recurring   = get_post_meta( $post_id, '_event_recurring', true );
$recur_until = get_post_meta( $post_id, '_event_recur_until', true );
$start_date  = get_post_meta( $post_id, '_event_start_date', true );
$recurring   = get_post_meta( $post_id, '_event_recurring', true );
$recur_until = get_post_meta( $post_id, '_event_recur_until', true );
// Use the date passed in the URL if present, otherwise calculate the next occurrence
if ( ! empty( $_GET['date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ) ) {
	$current = new DateTime( sanitize_text_field( $_GET['date'] ) );
} else {
	$intervals = [
		'weekly'    => '+1 week',
		'biweekly'  => '+2 weeks',
		'monthly'   => '+1 month',
	];
	$today   = new DateTime( current_time( 'Y-m-d' ) );
	$current = new DateTime( $start_date );
	$until   = $recur_until ? new DateTime( $recur_until ) : new DateTime( '+1 year' );
	if ( $recurring && isset( $intervals[ $recurring ] ) ) {
		while ( $current < $today && $current <= $until ) {
			$current->modify( $intervals[ $recurring ] );
		}
	}
}
$date_label = $current->format( 'l jS F Y' );
$end_date = sanitize_text_field( $end_date );
if ( ! empty( $end_date ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
	$end_date = new DateTime( $end_date );
}

$lat            = get_post_meta( $post_id, '_event_lat', true );
$lng            = get_post_meta( $post_id, '_event_lng', true );
$has_map        = $lat && $lng;
$has_image      = has_post_thumbnail();
$use_two_column = $has_map && $has_image;
?>
<main id="primary" class="site-main">
	<article class="flow">
		<h1><?php the_title(); ?></h1>
		<?php if ( $date_label ) : ?>
			<p class="event-date">
				<?php echo esc_html( $date_label ); ?>
				<?php if ( $start_time ) : ?>
					at <?php echo esc_html( date( 'g:i A', strtotime( $start_time ) ) ); ?>
				<?php endif; ?>
				<?php if ( $end_date ) : ?>
					– <?php echo $end_date->format( 'l jS F Y' ); ?>
				<?php endif; ?>
				<?php if ( $end_time ) : ?>
					<?php if ( $end_date ) { echo 'at'; } else { echo '-'; } ?> <?php echo esc_html( date( 'g:i A', strtotime( $end_time ) ) ); ?>
				<?php endif; ?>
				<?php if ( $recurring ) : ?>
				  | Repeats <?php echo esc_html( $recurring ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<?php echo '<p><strong>' . get_the_excerpt() . '</strong></p>'; ?>

		<?php if ( $venue ) : ?>
			<p class="event-venue"><strong>Venue:</strong> <?php echo esc_html( $venue ); ?></p>
		<?php endif; ?>
		
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
			<p>We strive to keep event details up to date. However, changes/cancellations are beyond our control and some regular events may not take place on bank holidays. Contact the venue/organiser before attending. Please <a href="/contact-the-whitby-anchor">contact us</a> if your event details need amending.</p>
		</div>
	</article>
</main>
<?php
get_sidebar();
get_footer();