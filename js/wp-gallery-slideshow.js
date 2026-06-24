/**
 * Turn core Gallery blocks (.wp-block-gallery) into Fancybox carousels.
 *
 * Progressive enhancement: if the Fancybox Carousel library is present, each
 * gallery on the page is converted from the default grid into a one-slide-at-
 * a-time slideshow with arrows + dots. Clicking a slide opens the full-size
 * image in the Fancybox lightbox (with a thumbnail strip).
 *
 * Mirrors js/gallery-init.js, which does the same for the Photo Gallery CPT.
 */
document.addEventListener( 'DOMContentLoaded', () => {
	const galleries = document.querySelectorAll( '.wp-block-gallery.has-nested-images' );
	if ( ! galleries.length || typeof Carousel === 'undefined' ) return;

	galleries.forEach( gallery => {
		// Only the gallery's own image children — not images nested deeper.
		const figures = Array.from( gallery.querySelectorAll( ':scope > .wp-block-image' ) );
		if ( figures.length < 2 ) return; // a single image isn't a slideshow

		// ── Grid → carousel ────────────────────────────────────────────────
		gallery.classList.add( 'f-carousel' );
		figures.forEach( figure => figure.classList.add( 'f-carousel__slide' ) );

		new Carousel( gallery, {
			infinite:   true,
			Navigation: true, // prev/next arrows — remove this line to drop them
			Dots:       true, // position dots    — remove this line to drop them
		} );

		// ── Lightbox items (largest available source per image) ─────────────
		const items = figures.map( figure => {
			const img = figure.querySelector( 'img' );
			const cap = figure.querySelector( 'figcaption' );
			return {
				src:     img ? getLargestSrc( img ) : '',
				thumb:   img ? img.src : '',
				caption: cap ? cap.textContent.trim() : '',
			};
		} );

		figures.forEach( ( figure, index ) => {
			figure.style.cursor = 'zoom-in';
			figure.addEventListener( 'click', () => {
				if ( typeof Fancybox === 'undefined' ) return;
				new Fancybox( items, {
					startIndex: index,
					Thumbs:  { type: 'classic' },
					Toolbar: {
						display: {
							left:   [ 'infobar' ],
							middle: [ 'caption' ],
							right:  [ 'fullscreen', 'close' ],
						},
					},
					caption: ( _fb, _slide, data ) => data.caption || '',
				} );
			} );
		} );
	} );

	function getLargestSrc( img ) {
		if ( img.srcset ) {
			const candidates = img.srcset.split( ',' ).map( entry => {
				const [ url, descriptor ] = entry.trim().split( /\s+/ );
				return { url, width: parseInt( descriptor ) || 0 };
			} );
			candidates.sort( ( a, b ) => b.width - a.width );
			if ( candidates[ 0 ]?.url ) return candidates[ 0 ].url;
		}
		return img.src;
	}
} );
