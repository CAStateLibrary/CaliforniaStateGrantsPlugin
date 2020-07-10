/**
 * Variables
 */

const wysiwygs = Array.from( document.querySelectorAll( '.wysiwyg' ) );

/**
 * Setup the markup.
 */
const setupMarkup = () => {
	wysiwygs.forEach( wysiwyg => {
		const textarea = wysiwyg.querySelector( 'textarea' );
		const span = document.createElement( 'span' );
		const limit = wysiwyg.getAttribute( 'data-characters-limit' );

		// Create
		span.setAttribute( 'id', `${textarea.id}-characters` );
		span.classList.add( 'wysiwyg__characters' );
		span.textContent = `${textarea.value.length} of ${limit} characters`;

		// Set maxlength on the hidden textarea to make this go through form validation
		textarea.setAttribute( 'maxlength', limit );

		// Insert
		wysiwyg.appendChild( span );
	} );
};

/**
 * Setup event listeners.
 */
const setupListeners = () => {
	window.addEventListener( 'message', handleWindowMessage, false );
};

/**
 * Handle window message
 * @param {object} event the event object
 */
const handleWindowMessage = ( event ) => {
	const { data } = event;
	const { type, dataId, textContent } = ( 'string' === typeof data ) ? JSON.parse( data ) : data;

	if ( ! type || 'editor.keyup' !== type ) {
		return;
	}

	const span = document.getElementById( `${dataId}-characters` );
	const wysiwyg = span.closest( '.wysiwyg' );
	const limit = wysiwyg.getAttribute( 'data-characters-limit' );
	const textarea = wysiwyg.querySelector( 'textarea' );
	const {length} = textContent;

	// Set the textContent inside the hidden textarea
	textarea.innerText = textContent;

	// Update character limit
	span.textContent = `${length} of ${limit} characters`;

	if ( length >= limit ) {
		span.classList.add( 'wysiwyg__characters--is-danger' );
	} else {
		span.classList.remove( 'wysiwyg__characters--is-danger' );
	}

	if ( length + 100 >= limit ) {
		span.classList.add( 'wysiwyg__characters--is-warning' );
	} else {
		span.classList.remove( 'wysiwyg__characters--is-warning' );
	}
};

/**
 * Init
 */
const init = () => {

	// Bail early
	if ( ! wysiwygs.length ) {
		return;
	}

	setupMarkup();
	setupListeners();
};

export default init;
