document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.querySelector( '.npg-gallery__images[data-gallery]' );
	if ( ! container ) return;
	const figures = Array.from( container.querySelectorAll( '.wp-block-image' ) );
	if ( ! figures.length ) return;

	// ── Caption bar ───────────────────────────────────────────────────────────
	const captionEl   = document.createElement( 'div' );
	captionEl.className = 'npg-gallery__caption-bar';
	const captionText = document.createElement( 'p' );
	captionText.className = 'npg-gallery__caption-text';
	captionEl.appendChild( captionText );
	captionEl.hidden = true;
	container.insertAdjacentElement( 'afterend', captionEl );

	function updateCaption() {
		const active     = container.querySelector( '.f-carousel__slide.is-selected' )
						|| figures[ 0 ];
		const figcaption = active ? active.querySelector( 'figcaption' ) : null;
		captionText.textContent = figcaption ? figcaption.textContent : '';
		captionEl.hidden        = ! captionText.textContent;
	}

	// ── Carousel ──────────────────────────────────────────────────────────────
	container.classList.add( 'f-carousel' );
	figures.forEach( figure => figure.classList.add( 'f-carousel__slide' ) );

	const carousel = new Carousel( container, {
		infinite:   true,
		Navigation: true,
		Dots:       true,
		on: {
			ready( c ) { if ( c === carousel ) updateCaption(); },
			change( c ) { if ( c === carousel ) updateCaption(); },
		},
	} );

	// ── Fancybox items array ───────────────────────────────────────────────────
	//
	// Build one item per figure. We try to serve the largest available image
	// from the srcset so the lightbox shows the full-resolution version,
	// while the thumbnail strip uses the smaller src already on the page.
	const items = figures.map( figure => {
		const img        = figure.querySelector( 'img' );
		const figcaption = figure.querySelector( 'figcaption' );
		return {
			src:     img ? getLargestSrc( img ) : '',
			thumb:   img ? img.src : '',
			caption: figcaption ? figcaption.textContent.trim() : '',
		};
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

	// ── Fancybox with Thumbs ───────────────────────────────────────────────────
	//
	// Clicking any figure opens the lightbox at the matching index, with the
	// full group available for navigation.
	figures.forEach( ( figure, index ) => {
		figure.style.cursor = 'zoom-in';
		figure.addEventListener( 'click', () => {
			new Fancybox( items, {
				startIndex: index,
				// Enable the thumbnail strip along the bottom
				Thumbs: {
					type: 'classic', // 'classic' = strip | 'modern' = overlaid
				},
				// Toolbar buttons
				Toolbar: {
					display: {
						left:   [ 'infobar' ],           // "1 / 6"
						middle: [ 'caption' ],
						right:  [ 'fullscreen', 'close' ],
					},
				},
				// Show caption below each image
				caption: ( _fancybox, _slide, data ) => data.caption || '',
			} );
		} );
	} );
} );