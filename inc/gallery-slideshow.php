<?php
/**
 * Render core Gallery blocks as slideshows on the front end.
 *
 * Enqueues the @fancyapps/ui v5 carousel + lightbox assets (the same ones the
 * Photo Gallery CPT uses) on any singular post/page that contains a core
 * Gallery block, plus a small init script that converts the gallery grid into
 * a slideshow. See js/wp-gallery-slideshow.js for the behaviour.
 *
 * @package whitbyanchor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the slideshow assets only where a Gallery block is present.
 */
function whitbyanchor_enqueue_gallery_slideshow(): void {

	// The Photo Gallery CPT has its own carousel loader, so skip it here to
	// avoid initialising twice on those screens.
	if ( ! is_singular() || is_singular( 'npg_gallery' ) || ! has_block( 'gallery' ) ) {
		return;
	}

	// Handles match inc/photo-gallery-cpt.php exactly, so WordPress de-dupes
	// and never downloads these twice if both loaders run on one page.
	wp_enqueue_style(
		'fancyapps-carousel',
		'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.css',
		[], '5'
	);
	wp_enqueue_script(
		'fancyapps-carousel',
		'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.umd.js',
		[], '5', true
	);

	wp_enqueue_style(
		'fancyapps-fancybox',
		'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.css',
		[], '5'
	);
	wp_enqueue_script(
		'fancyapps-fancybox',
		'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.umd.js',
		[], '5', true
	);

	wp_enqueue_style(
		'fancyapps-thumbs',
		'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.thumbs.css',
		[ 'fancyapps-fancybox' ], '5'
	);
	wp_enqueue_script(
		'fancyapps-thumbs',
		'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.thumbs.umd.js',
		[ 'fancyapps-fancybox' ], '5', true
	);

	wp_enqueue_script(
		'whitbyanchor-gallery-slideshow',
		get_template_directory_uri() . '/js/wp-gallery-slideshow.js',
		[ 'fancyapps-carousel', 'fancyapps-fancybox', 'fancyapps-thumbs' ],
		_S_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'whitbyanchor_enqueue_gallery_slideshow' );
