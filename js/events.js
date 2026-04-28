/**
 * Events page — tag filter + load-more + free-text search.
 *
 * Depends on EventsConfig being defined inline before this script runs:
 * { ajaxUrl, nonce, perPage, hasMore, total }
 */
document.addEventListener( 'DOMContentLoaded', () => {
	const list      = document.getElementById( 'events-list' );
	const select    = document.getElementById( 'event-tag-select' );
	const location  = document.getElementById( 'event-location-select' );
	const dateStart = document.getElementById( 'event-date-start' );
	const dateEnd   = document.getElementById( 'event-date-end' );
	const loadMore  = document.getElementById( 'events-load-more' );
	const search    = document.getElementById( 'event-search' );

	if ( ! list ) return;

	// ── State ──────────────────────────────────────────────────────────────────
	let currentPage     = 1;
	let currentTag      = '';
	let currentLocation = '';
	let currentDateFrom = '';
	let currentDateTo   = '';
	let currentSearch   = '';
	let loading         = false;
	let searchTimer     = null;

	// ── Core fetch function ────────────────────────────────────────────────────

	async function fetchEvents( { page, tag, location, date_from = '', date_to = '', search = '', mode, scroll = true } ) {
		if ( loading ) return;
		loading = true;
		setLoadingState( true );

		const body = new URLSearchParams( {
			action:    'whitbyanchor_get_events',
			nonce:     EventsConfig.nonce,
			page:      page,
			per_page:  EventsConfig.perPage,
			tag:       tag,
			location:  location,
			date_from: date_from,
			date_to:   date_to,
			search:    search,
		} );

		try {
			const response = await fetch( EventsConfig.ajaxUrl, {
				method:  'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body,
			} );

			if ( ! response.ok ) throw new Error( `HTTP ${ response.status }` );

			const json = await response.json();
			if ( ! json.success ) throw new Error( json.data ?? 'Unknown error' );

			const data = json.data;

			if ( mode === 'replace' ) {
				list.innerHTML = data.html || '<p>No events found - try changing your filters.</p>';
				if ( scroll ) {
					list.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				}
			} else {
				// append
				list.insertAdjacentHTML( 'beforeend', data.html );
			}

			currentPage = data.page;
			updateLoadMore( data.has_more && !!data.html );

		} catch ( err ) {
			console.error( 'Events fetch failed:', err );
			showError();
		} finally {
			loading = false;
			setLoadingState( false );
		}
	}

	// ── Shared helper to trigger a filtered replace from page 1 ───────────────

	function resetAndFetch( scroll = true ) {
		currentPage = 1;
		fetchEvents( {
			page:      1,
			tag:       currentTag,
			location:  currentLocation,
			date_from: currentDateFrom,
			date_to:   currentDateTo,
			search:    currentSearch,
			mode:      'replace',
			scroll,
		} );
	}

	// ── Tag filter ─────────────────────────────────────────────────────────────

	if ( select ) {
		select.addEventListener( 'change', function () {
			currentTag = this.value.trim();
			resetAndFetch();
		} );
	}

	// ── Location filter ────────────────────────────────────────────────────────

	if ( location ) {
		location.addEventListener( 'change', function () {
			currentLocation = this.value.trim();
			resetAndFetch();
		} );
	}

	// ── Date filters ───────────────────────────────────────────────────────────

	if ( dateStart ) {
		dateStart.addEventListener( 'change', function () {
			currentDateFrom = this.value;
			resetAndFetch();
		} );
	}

	if ( dateEnd ) {
		dateEnd.addEventListener( 'change', function () {
			currentDateTo = this.value;
			resetAndFetch();
		} );
	}

	// ── Free-text search (debounced 350 ms) ────────────────────────────────────

	if ( search ) {
		search.addEventListener( 'input', function () {
			clearTimeout( searchTimer );
			searchTimer = setTimeout( () => {
				currentSearch = this.value.trim();
				resetAndFetch( false );
			}, 500 );
		} );
	}

	// ── Load more ──────────────────────────────────────────────────────────────

	if ( loadMore ) {
		// Hide the button if PHP already told us there's nothing more.
		if ( ! EventsConfig.hasMore ) {
			loadMore.hidden = true;
		}

		loadMore.addEventListener( 'click', () => {
			fetchEvents( {
				page:      currentPage + 1,
				tag:       currentTag,
				location:  currentLocation,
				date_from: currentDateFrom,
				date_to:   currentDateTo,
				search:    currentSearch,
				mode:      'append',
			} );
		} );
	}

	// ── UI helpers ─────────────────────────────────────────────────────────────

	function setLoadingState( isLoading ) {
		if ( loadMore ) {
			loadMore.disabled    = isLoading;
			loadMore.textContent = isLoading ? 'Loading…' : 'Load more events';
		}
		if ( select ) {
			select.disabled = isLoading;
		}
		if ( search ) {
			search.disabled = isLoading;
		}
		list.setAttribute( 'aria-busy', isLoading ? 'true' : 'false' );
	}

	function updateLoadMore( hasMore ) {
		if ( ! loadMore ) return;
		loadMore.hidden = ! hasMore;
	}

	function showError() {
		const msg = document.createElement( 'p' );
		msg.className   = 'events-error';
		msg.textContent = 'Sorry, something went wrong loading events. Please try again.';

		// Remove any previous error first.
		list.parentNode.querySelector( '.events-error' )?.remove();
		list.insertAdjacentElement( 'afterend', msg );
	}
} );