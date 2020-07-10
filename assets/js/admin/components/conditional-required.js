const grantTypeInputs = Array.from( document.querySelectorAll( 'input[name="isForecasted"]' ) );
const conditionalValidationInputs = 'input[data-required-if],textarea[data-required-if]';
const conditionalValidationOther = '[data-required-if]:not(input):not(textarea)';

/**
 * Conditional requiring fields if grant is forecasted/active.
 */
const main = () => {
	if ( ! grantTypeInputs.length || ( ! getInputs().length && ! getOthers().length ) ) {
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
	getInputs().forEach( input => maybeSetRequired( input ) );
	getOthers().forEach( el => maybeSetRequiredClass( el ) );
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

/**
 * Get inputs that require validation.
 */
const getInputs = () => Array.from( document.querySelectorAll( conditionalValidationInputs ) );

/**
 * Get other fields that require validation.
 */
const getOthers = () => Array.from( document.querySelectorAll( conditionalValidationOther ) );


export default main;
