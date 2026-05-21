( function () {
	'use strict';

	const config   = window.businessesConfig || {};
	const ajaxUrl  = config.ajaxUrl;
	const nonce    = config.nonce;
	const perPage  = config.perPage || 12;

	const list     = document.getElementById( 'businesses-list' );
	const loadMore = document.getElementById( 'businesses-load-more' );
	const search   = document.getElementById( 'business-search' );
	const tagSel   = document.getElementById( 'business-tag-select' );
	const locSel   = document.getElementById( 'business-location-select' );

	if ( ! list ) return;

	// ── State ────────────────────────────────────────────────────────────────

	let currentPage     = 1;
	let currentSearch   = '';
	let currentTag      = '';
	let currentLocation = '';
	let searchTimer     = null;
	let isLoading       = false;

	// ── Helpers ──────────────────────────────────────────────────────────────

	function setLoading( loading ) {
		isLoading = loading;
		if ( loadMore ) {
			loadMore.disabled = loading;
			loadMore.textContent = loading ? 'Loading…' : 'Load more businesses';
		}
		list.style.opacity = loading ? '0.5' : '1';
	}

	function updateLoadMore( hasMore ) {
		if ( ! loadMore ) return;
		loadMore.style.display = hasMore ? '' : 'none';
	}

	// ── Core fetch ───────────────────────────────────────────────────────────

	function fetchBusinesses( { page = 1, append = false } = {} ) {
		if ( isLoading ) return;
		setLoading( true );

		const body = new FormData();
		body.append( 'action',   'load_businesses' );
		body.append( 'nonce',    nonce );
		body.append( 'page',     page );
		body.append( 'per_page', perPage );
		body.append( 'search',   currentSearch );
		body.append( 'tag',      currentTag );
		body.append( 'location', currentLocation );

		fetch( ajaxUrl, { method: 'POST', body } )
			.then( r => r.json() )
			.then( data => {
				if ( ! data.success ) {
					console.error( 'Business filter error:', data );
					return;
				}

				if ( append ) {
					list.insertAdjacentHTML( 'beforeend', data.data.html );
				} else {
					list.innerHTML = data.data.html;
					// Scroll to top of list if filtering (not appending)
					list.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				}

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
		fetchBusinesses( { page: 1, append: false } );
	}

	// ── Event listeners ──────────────────────────────────────────────────────

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

	// Category/tag select
	if ( tagSel ) {
		tagSel.addEventListener( 'change', function () {
			currentTag = tagSel.value;
			resetAndFetch();
		} );
	}

	// Location select
	if ( locSel ) {
		locSel.addEventListener( 'change', function () {
			currentLocation = locSel.value;
			resetAndFetch();
		} );
	}

	// Load more button
	if ( loadMore ) {
		loadMore.addEventListener( 'click', function () {
			fetchBusinesses( { page: currentPage + 1, append: true } );
		} );
	}

} )();