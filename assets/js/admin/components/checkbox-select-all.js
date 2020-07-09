/**
 * Checkbox select all
*/

const SELECTOR = '.checkbox--select-all';

/**
 * Main.
 */
const main = () => {
	const elements = Array.from( document.querySelectorAll( SELECTOR ) );

	if ( elements.length ) {
		elements.forEach( element => element.addEventListener( 'click', toggleAllChecked ) );
	}
};

/**
 * Toggle Checked.
*/
const toggleAllChecked = e => {
	e.preventDefault();
	const boxes = Array.from( e.target.parentNode.parentNode.querySelectorAll( 'input[type=checkbox]' ) );

	boxes
		.filter( box => ( 'uncategorized' !== box.value && 'other' !== box.value ) )
		.forEach( box => box.checked = true );
};

export default main;
