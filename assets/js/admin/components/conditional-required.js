const grantTypeInputs = Array.from( document.querySelectorAll( 'input[name="isForecasted"]' ) );
const conditionalValidationInputs = 'input[data-required-if],textarea[data-required-if],input[required]';
const conditionalValidationOther = '[data-required-if]:not(input):not(textarea)';
const conditionalVisibleElems = 'tr[data-visible-if]';
const grantAwardsRecipientTypes = Array.from( document.querySelectorAll( 'select[name="recipientType"]' ) );

/**
 * Conditional requiring fields if grant is forecasted/active.
 */
const main = () => {

	if ( getVisableElems().length ) {
		grantAwardsRecipientTypes.forEach( input => input.addEventListener( 'change', refreshRequiredAttributes ) );
	}

	if ( grantTypeInputs.length ) {
		// Update required attributes when the input changes.
		grantTypeInputs.forEach( input => input.addEventListener( 'change', refreshRequiredAttributes ) );
	}

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
 * Get current Recipient type.
 */
const getCurrentRecipientType = () => {
	const [current] = grantAwardsRecipientTypes.filter( input => input.selectedIndex );

	return current ? current.value : '';
};

/**
 * Refresh all the inputs with conditional required attributes.
 */
const refreshRequiredAttributes = () => {
	if ( getInputs().length ) {
		getInputs().forEach( input => maybeSetRequired( input ) );
	}

	if ( getOthers().length ) {
		getOthers().forEach( el => maybeSetRequiredClass( el ) );
	}

	if ( getVisableElems().length ) {
		getVisableElems().forEach( el => maybeSetHiddenClass( el ) );
	}
};

/**
 * Maybe set required attribute.
 * @param {HTMLElement} input
 */
const maybeSetRequired = input => {
	const { requiredIf } = input.dataset;
	const current        = getCurrentGrantType();

	input.required = ( ! requiredIf || ! current )
		? input.required
		: -1 !== requiredIf.split( ',' ).map( s => s.trim() ).indexOf( current );

	const label = input.closest( 'tr' ).querySelector( 'th' );
	if ( input.required ) {
		label.classList.add( 'required' );
	} else {
		label.classList.remove( 'required' );
	}
};

/**
 * Maybe set required class.
 * @param {HTMLElement} el
 */
const maybeSetRequiredClass = el => {
	const { requiredIf } = el.dataset;
	const current        = getCurrentGrantType();

	if ( current ) {
		if ( -1 !== requiredIf.split( ',' ).map( s => s.trim() ).indexOf( current ) ) {
			el.classList.add( 'fieldset--is-required' );
		} else {
			el.classList.remove( 'fieldset--is-required' );
		}
	}

	const label = el.closest( 'tr' ).querySelector( 'th' );
	if ( el.classList.contains( 'fieldset--is-required' ) ) {
		label.classList.add( 'required' );
	} else {
		label.classList.remove( 'required' );
	}
};

/**
 * Maybe set hidden class.
 *
 * @param {HTMLElement} el
 */
const maybeSetHiddenClass = el => {
	const { visibleIf }  = el.dataset;
	const current        = getCurrentRecipientType();
	const visibleOptions = JSON.parse( visibleIf );

	console.log( 'lol' );

	if (
		! visibleOptions
		|| 'recipientType' !== visibleOptions['fieldId']
	) {
		return;
	}

	if (
		'not_equal' === visibleOptions['compare'] && current === visibleOptions['value']
		|| 'equal' === visibleOptions['compare'] && current !== visibleOptions['value']
	) {
		el.classList.add( 'hidden' );
		el.querySelector( 'input' ).setAttribute( 'required', false );
	} else {
		el.classList.remove( 'hidden' );
		el.querySelector( 'input' ).setAttribute( 'required', true );
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

/**
 * Get data-visable-if elements.
 */
const getVisableElems = () => Array.from( document.querySelectorAll( conditionalVisibleElems ) );

export default main;
