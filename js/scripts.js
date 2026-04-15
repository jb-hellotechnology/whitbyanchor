function initTagFilter() {
	const tagLinks = document.querySelectorAll('.event-tag-cloud a');
	const events   = document.querySelectorAll('[data-tags]');
	let activeTag  = null;

	tagLinks.forEach( link => {
		link.addEventListener('click', e => {
			e.preventDefault();

			const slug = link.dataset.tag;

			// Clicking the active tag again resets the filter
			if ( activeTag === slug ) {
				activeTag = null;
				tagLinks.forEach( l => l.classList.remove('is-active') );
				events.forEach( el => el.hidden = false );
				return;
			}

			activeTag = slug;
			tagLinks.forEach( l => l.classList.toggle('is-active', l.dataset.tag === slug) );
			events.forEach( el => {
				const tags = el.dataset.tags.split(',').map( t => t.trim() ).filter(Boolean);
				el.hidden = ! tags.includes( slug );
			});
		});
	});
}

document.addEventListener('DOMContentLoaded', initTagFilter);