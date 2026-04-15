<?php
get_header();

$events = get_events( [
	'from_date' => current_time( 'Y-m-d' ),
	'limit'     => 50,
] );
?>

<main id="primary" class="site-main">
	<?php npg_render_gallery_archive() ?>
</main>

<?php
get_sidebar();
get_footer();