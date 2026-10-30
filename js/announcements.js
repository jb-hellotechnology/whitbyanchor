( function () {
	'use strict';

	const config   = window.announcementsConfig || {};
	const ajaxUrl  = config.ajaxUrl;
	const nonce    = config.nonce;
	const perPage  = config.perPage || 12;

	const list     = document.getElementById( 'announcements-list' );
	const loadMore = document.getElementById( 'announcements-load-more' );
	const tagSel   = document.getElementById( 'announcement-tag-select' );
	const search   = document.getElementById( 'announcement-search' );

	if ( ! list ) return;

	// ── State ────────────────────────────────────────────────────────────────

	let currentPage     = 1;
	let currentSearch   = '';
	let currentTag      = '';
	let searchTimer     = null;
	let isLoading       = false;
	let excerptId       = 0;

	// Read more is a phone-sized concern: above this the card puts the text in a
	// column of its own, with room to run. Matches the stacking breakpoint in
	// style.css.
	const narrow = window.matchMedia( '(max-width: 700px)' );

	// ── Helpers ──────────────────────────────────────────────────────────────

	function setLoading( loading ) {
		isLoading = loading;
		if ( loadMore ) {
			loadMore.disabled = loading;
			loadMore.textContent = loading ? 'Loading…' : 'Load more announcements';
		}
		list.style.opacity = loading ? '0.5' : '1';
	}

	function updateLoadMore( hasMore ) {
		if ( ! loadMore ) return;
		loadMore.style.display = hasMore ? '' : 'none';
	}

	// ── Read more ────────────────────────────────────────────────────────────

	// Clamp an announcement that runs past the first couple of lines and give
	// it a control to open and close it. Announcements that already fit are
	// left alone, so no card carries a link that reveals nothing.
	function addReadMore( excerpt ) {
		if ( excerpt.dataset.readMore ) return;
		excerpt.dataset.readMore = 'done';

		excerpt.classList.add( 'is-clamped' );

		if ( excerpt.scrollHeight - excerpt.clientHeight < 2 ) {
			excerpt.classList.remove( 'is-clamped' );
			return;
		}

		if ( ! excerpt.id ) {
			excerpt.id = 'announcement-excerpt-' + ( ++excerptId );
		}

		const toggle = document.createElement( 'button' );
		toggle.type = 'button';
		toggle.className = 'business-card__read-more';
		toggle.textContent = 'Read more';
		toggle.setAttribute( 'aria-expanded', 'false' );
		toggle.setAttribute( 'aria-controls', excerpt.id );

		toggle.addEventListener( 'click', function () {
			const open = ! excerpt.classList.toggle( 'is-clamped' );
			toggle.textContent = open ? 'Read less' : 'Read more';
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );

		excerpt.insertAdjacentElement( 'afterend', toggle );
	}

	// Hand the announcement back in full: the wide card has room for it.
	function removeReadMore( excerpt ) {
		const toggle = excerpt.nextElementSibling;

		if ( toggle && toggle.classList.contains( 'business-card__read-more' ) ) {
			toggle.remove();
		}

		excerpt.classList.remove( 'is-clamped' );
		delete excerpt.dataset.readMore;
	}

	function syncReadMore() {
		list.querySelectorAll( '.business-card__excerpt' )
			.forEach( narrow.matches ? addReadMore : removeReadMore );
	}

	// ── Core fetch ───────────────────────────────────────────────────────────

	function fetchannouncements( { page = 1, append = false } = {} ) {
		if ( isLoading ) return;
		setLoading( true );

		const body = new FormData();
		body.append( 'action',   'load_announcements' );
		body.append( 'nonce',    nonce );
		body.append( 'page',     page );
		body.append( 'per_page', perPage );
		body.append( 'tag',      currentTag );
		body.append( 'search',   currentSearch );

		fetch( ajaxUrl, { method: 'POST', body } )
			.then( r => r.json() )
			.then( data => {
				if ( ! data.success ) {
					console.error( 'announcement filter error:', data );
					return;
				}

				if ( append ) {
					list.insertAdjacentHTML( 'beforeend', data.data.html );
				} else {
					list.innerHTML = data.data.html;
					// Scroll to top of list if filtering (not appending)
					list.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				}

				syncReadMore();

				currentPage = page;
				updateLoadMore( data.data.has_more );

				if ( loadMore ) {
					loadMore.dataset.page = page;
				}
			} )
			.catch( err => console.error( 'Fetch error:', err ) )
			.finally( () => setLoading( false ) );
	}

	// ── Filter/search — reset to page 1 ─────────────────────────────────────

	function resetAndFetch() {
		currentPage = 1;
		fetchannouncements( { page: 1, append: false } );
	}

	// ── Event listeners ──────────────────────────────────────────────────────

	// Category/tag select
	if ( tagSel ) {
		tagSel.addEventListener( 'change', function () {
			currentTag = tagSel.value;
			resetAndFetch();
		} );
	}
	
	// Debounced search input
	if ( search ) {
		search.addEventListener( 'input', function () {
			clearTimeout( searchTimer );
			searchTimer = setTimeout( function () {
				currentSearch = search.value.trim();
				resetAndFetch();
			}, 350 );
		} );
	}

	// Measure once the webfonts are in, since they decide where lines break.
	if ( document.fonts && document.fonts.ready ) {
		document.fonts.ready.then( syncReadMore );
	} else {
		syncReadMore();
	}

	// Rotating a phone, or dragging a window across the breakpoint, switches
	// between the two: the clamp must never outlive the control that opens it.
	narrow.addEventListener( 'change', syncReadMore );

	// Load more button
	if ( loadMore ) {
		loadMore.addEventListener( 'click', function () {
			fetchannouncements( { page: currentPage + 1, append: true } );
		} );
	}

} )();