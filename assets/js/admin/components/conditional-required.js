const grantTypeInputs = Array.from( document.querySelectorAll( 'input[name="isForecasted"]' ) );
const conditionalValidationInputs = Array.from( document.querySelectorAll( 'input[data-required-if]' ) );
const conditionalValidationOther = Array.from( document.querySelectorAll( '[data-required-if]:not(input)' ) );

/**
 * Conditional requiring fields if grant is forecasted/active.
 */
const main = () => {
	if ( ! grantTypeInputs.length || ( ! conditionalValidationInputs.length && ! conditionalValidationOther.length ) ) {
		return;
	}

	// Update required attributes when the input changes.
	grantTypeInputs.forEach( input => input.addEventListener( 'change', refreshRequiredAttributes ) );

	// Kick things off.
	refreshRequiredAttributes();
};

/**
 * Get current grant type.
 */
const getCurrentGrantType = () => {
	const [current] = grantTypeInputs.filter( input => input.checked );

	return current ? current.value : '';
};

/**
 * Refresh all the inputs with conditional required attributes.
 */
const refreshRequiredAttributes = () => {
	conditionalValidationInputs.forEach( input => maybeSetRequired( input ) );
	conditionalValidationOther.forEach( el => maybeSetRequiredClass( el ) );
};

/**
 * Maybe set required attribute.
 * @param {HTMLElement} input
 */
const maybeSetRequired = input => {
	const { requiredIf } = input.dataset;
	const current        = getCurrentGrantType();

	input.required = ( ! requiredIf || ! current )
		? false
		: -1 !== requiredIf.split( ',' ).map( s => s.trim() ).indexOf( current );
};

/**
 * Maybe set required class.
 * @param {HTMLElement} el
 */
const maybeSetRequiredClass = el => {
	const { requiredIf } = el.dataset;
	const current        = getCurrentGrantType();

	if ( current ) {
		if ( requiredIf.split( ',' ).map( s => s.trim ).indexOf( current ) ) {
			el.classList.add( 'fieldset--is-required' );
		} else {
			el.classList.remove( 'fieldset--is-required' );
		}
	}
};


export default main;
