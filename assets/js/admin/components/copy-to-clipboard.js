const selector = '.copy-clipboard';

/**
 * Copy To Clipboard
 */
const main = () => {
	const elements = Array.from( document.querySelectorAll( selector ) );
	elements.forEach( el => el.addEventListener( 'click', onCopy ) );

};

/**
 * On Copy Handler.
 *
 * @param {Event} e
 */
const onCopy = e => {
	const { inputTarget } = e.target.dataset;
	const input           = document.getElementById( inputTarget );
	const { disabled }    = input;

	// Ensure input in not disabled.
	input.disabled = false;

	// Copy input text.
	input.select();
	document.execCommand( 'copy' );

	// Return to original disabled state.
	input.disabled = disabled;

	// Alert the user.
	e.target.innerText = 'Copied to clipboard';

	// Set a timeout to return the button to original state.
	setTimeout( () => {
		e.target.innerText = 'Copy';
	}, 2500 );
};

export default main;
