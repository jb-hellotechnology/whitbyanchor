<?php
get_header();

$post_id 	 = get_queried_object_id();
$start_date  = get_post_meta( $post_id, '_event_start_date', true );
$start_time  = get_post_meta( $post_id, '_event_start_time', true );
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
?>

<main id="primary" class="site-main">
	<article class="flow">
		<h1><?php the_title(); ?></h1>

		<?php if ( $date_label ) : ?>
			<p class="event-date">
				<?php echo esc_html( $date_label ); ?>
				<?php if ( $start_time ) : ?>
					at <?php echo esc_html( date( 'g:i A', strtotime( $start_time ) ) ); ?>
					<?php if ( $end_time ) : ?>
						– <?php echo esc_html( date( 'g:i A', strtotime( $end_time ) ) ); ?>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ( $recurring ) : ?>
				  | Repeats <?php echo esc_html( $recurring ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<?php if ( $venue ) : ?>
			<p class="event-venue"><?php echo esc_html( $venue ); ?></p>
		<?php endif; ?>
		
		<?php
		$lat = get_post_meta( get_the_ID(), '_event_lat', true );
		$lng = get_post_meta( get_the_ID(), '_event_lng', true );
		
		if ( $lat && $lng ) :
		?>
			<div id="event-map" style="height:350px;"></div>
			<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
			<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
			<script>
				document.addEventListener('DOMContentLoaded', function () {
					var map = L.map('event-map').setView([<?php echo esc_js($lat); ?>, <?php echo esc_js($lng); ?>], 15);
					L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
						attribution: '© OpenStreetMap contributors'
					}).addTo(map);
					L.marker([<?php echo esc_js($lat); ?>, <?php echo esc_js($lng); ?>]).addTo(map);
				});
			</script>
		<?php endif; ?>

		<div class="event-content">
			<?php the_content(); ?>
		</div>
	</article>
</main>

<?php
get_sidebar();
get_footer();