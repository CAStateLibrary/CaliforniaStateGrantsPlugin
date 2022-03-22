/**
 * Repeater field add / remove button logic.
 */
const main = () => {
	const addNewButtons = Array.from( document.querySelectorAll( '.form-field-add-new-group-button' ) );
	const removeGroupButtons = Array.from( document.querySelectorAll( '.form-field-remove-group-button' ) );

	addNewButtons.forEach( button => button.addEventListener( 'click', addNewGroupInRepeaterField ) );
	removeGroupButtons.forEach( button => button.addEventListener( 'click', removeRepeaterFieldGroup ) );
};

/**
 * Increment the element suffix and return.
 *
 * @param Object elem Node element to update index.
 */
const incrementIndexElem = elem => {
	const elemIndex = elem.dataset.index ? elem.dataset.index : 0;
	const nextIndex = parseInt( elemIndex, 10 ) + 1;

	elem.setAttribute( 'data-index', nextIndex );

	const selectElems = Array.from( elem.querySelectorAll( 'select' ) );
	selectElems.forEach( elem => {
		elem.id = elem.id.replace( `-${elemIndex}`, `-${nextIndex}` );
		elem.name = elem.name.replace( `[${elemIndex}]`, `[${nextIndex}]` );
	} );

	const inputElems = Array.from( elem.querySelectorAll( 'input' ) );
	inputElems.forEach( elem => {
		elem.id = elem.id.replace( `-${elemIndex}`, `-${nextIndex}` );
		elem.name = elem.name.replace( `[${elemIndex}]`, `[${nextIndex}]` );
	} );

	const labelElems = Array.from( elem.querySelectorAll( 'label' ) );
	labelElems.forEach( elem => {
		const forAttr = elem.getAttribute( 'for' );
		elem.setAttribute( 'for', forAttr.replace( `-${elemIndex}`, `-${nextIndex}` ) );
	} );
};

/**
 * Enable disabled form fields.
 *
 * @param object elem
 */
const enableFormFields = elem => {
	const selectElems = Array.from( elem.querySelectorAll( 'select' ) );
	selectElems.forEach( elem => {
		elem.removeAttribute( 'disabled' );
	} );

	const inputElems = Array.from( elem.querySelectorAll( 'input' ) );
	inputElems.forEach( elem => {
		elem.removeAttribute( 'disabled' );
	} );
};

/**
 * Add new group when clicked on add new button for repeater field.
 *
 * @returns
 */
const addNewGroupInRepeaterField = event => {
	event.preventDefault();

	const fieldTable = event.target.closest( 'table' );
	if ( ! fieldTable ) {
		return;
	}

	const copyFieldsElem = fieldTable.querySelector( '.form-field-group-wrapper-copy' );
	if ( ! copyFieldsElem ) {
		return;
	}

	const cloneElem = copyFieldsElem.cloneNode( true );
	incrementIndexElem( copyFieldsElem );
	cloneElem.classList.add( 'form-field-group-wrapper' );
	cloneElem.classList.remove( 'form-field-group-wrapper-copy' );
	cloneElem.classList.remove( 'hidden' );
	enableFormFields( cloneElem );
	copyFieldsElem.parentNode.insertBefore( cloneElem, copyFieldsElem );

	// Reinitiate events.
	main();
};

/**
 * Remove repeater
 *
 * @param object event
 */
const removeRepeaterFieldGroup = event => {
	event.preventDefault();

	const fieldRow = event.target.closest( 'tr' );
	if ( ! fieldRow ) {
		return;
	}

	fieldRow.remove();
};

export default main;
