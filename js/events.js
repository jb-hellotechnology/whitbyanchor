/**
 * Events page — tag filter + load-more.
 *
 * Depends on EventsConfig being defined inline before this script runs:
 * { ajaxUrl, nonce, perPage, hasMore, total }
 */
document.addEventListener( 'DOMContentLoaded', () => {
	const list     = document.getElementById( 'events-list' );
	const select   = document.getElementById( 'event-tag-select' );
	const location = document.getElementById( 'event-location-select' );
	const loadMore = document.getElementById( 'events-load-more' );

	if ( ! list ) return;

	// ── State ──────────────────────────────────────────────────────────────────
	let currentPage = 1;
	let currentTag  = '';
	let currentLocation  = '';
	let loading     = false;

	// ── Core fetch function ────────────────────────────────────────────────────

	async function fetchEvents( { page, tag, location, mode } ) {
		if ( loading ) return;
		loading = true;
		setLoadingState( true );

		const body = new URLSearchParams( {
			action:   'whitbyanchor_get_events',
			nonce:    EventsConfig.nonce,
			page:     page,
			per_page: EventsConfig.perPage,
			tag:      tag,
			location: location,
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
				list.innerHTML = data.html || '<p>No events found.</p>';
				list.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			} else {
				// append
				list.insertAdjacentHTML( 'beforeend', data.html );
			}

			currentPage = data.page;
			updateLoadMore( data.has_more );

		} catch ( err ) {
			console.error( 'Events fetch failed:', err );
			showError();
		} finally {
			loading = false;
			setLoadingState( false );
		}
	}

	// ── Tag filter ─────────────────────────────────────────────────────────────

	if ( select ) {
		select.addEventListener( 'change', function () {
			currentTag  = this.value.trim();
			currentPage = 1;
			fetchEvents( { page: 1, tag: currentTag, mode: 'replace' } );
		} );
	}
	
	// ── Location filter ─────────────────────────────────────────────────────────────
	
	if ( location ) {
		location.addEventListener( 'change', function () {
			currentLocation  = this.value.trim();
			currentPage = 1;
			fetchEvents( { page: 1, tag: currentTag, location: currentLocation, mode: 'replace' } );
		} );
	}

	// ── Load more ──────────────────────────────────────────────────────────────

	if ( loadMore ) {
		// Hide the button if PHP already told us there's nothing more.
		if ( ! EventsConfig.hasMore ) {
			loadMore.hidden = true;
		}

		loadMore.addEventListener( 'click', () => {
			fetchEvents( { page: currentPage + 1, tag: currentTag, location: currentLocation, mode: 'append' } );
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