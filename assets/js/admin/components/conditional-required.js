const grantTypeInputs = Array.from( document.querySelectorAll( 'input[name="isForecasted"]' ) );
const geoLocationServedElem = Array.from( document.querySelectorAll( 'select[name="geoLocationServed"]' ) );
const conditionalValidationInputs = 'input[data-required-if],textarea[data-required-if],input[required]';
const conditionalValidationOther = '[data-required-if]:not(input):not(textarea)';
const conditionalVisibleElems = 'tr[data-visible-if]';
const grantAwardsRecipientTypes = Array.from( document.querySelectorAll( 'select[name="recipientType"]' ) );
const startDateElem = Array.from( document.querySelectorAll( 'input[data-min-date-id]' ) );
const endDateElem = Array.from( document.querySelectorAll( 'input[data-max-date-id]' ) );
const requiredPostFinderDiv = Array.from( document.querySelectorAll( 'tr.post_finder_field div[data-post-finder="required"]' ) );

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

	if ( geoLocationServedElem.length ) {
		// Update required attributes when the input changes.
		geoLocationServedElem.forEach( input => input.addEventListener( 'change', refreshRequiredAttributes ) );
	}

	if ( startDateElem.length ) {
		startDateElem.forEach( input => input.addEventListener( 'change', refreshMinMaxDateAttributes ) );
	}

	if ( endDateElem.length ) {
		endDateElem.forEach( input => input.addEventListener( 'change', refreshMinMaxDateAttributes ) );
	}

	// Add required attribute to post finder input field.
	if ( requiredPostFinderDiv.length ) {
		requiredPostFinderDiv.forEach( function( elem ) {
			const inputElems = Array.from( elem.querySelectorAll( 'input[type="hidden"]' ) );
			inputElems.forEach( input => {
				input.setAttribute( 'required', 'true' );
			} );
		} );
	}

	// Kick things off.
	refreshRequiredAttributes();
	refreshMinMaxDateAttributes();
};

/**
 * Get current grant type.
 */
const getCurrentGrantType = () => {
	const [current] = grantTypeInputs.filter( input => input.checked );

	return current ? current.value : '';
};

/**
 * Get current geo location.
 */
const getCurrentGeoLocation = () => {

	if ( ! geoLocationServedElem.length ) {
		return '';
	}

	const [current] = geoLocationServedElem.filter( input => input.value );

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
 * Refresh min and max date for grant funded dates.
 */
const refreshMinMaxDateAttributes = () => {

	if ( ! startDateElem.length && ! endDateElem.length ) {
		return;
	}

	startDateElem.forEach( input => {
		const { minDateId } = input.dataset;
		const minDateElem =  document.getElementById( minDateId );
		input.setAttribute( 'min', minDateElem.value );
	} );

	endDateElem.forEach( input => {
		const { maxDateId } = input.dataset;
		const maxDateElem =  document.getElementById( maxDateId );
		input.setAttribute( 'max', maxDateElem.value );
	} );
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
	let current = '';

	if ( geoLocationServedElem.length ) {
		current = getCurrentGeoLocation();
	} else if ( grantTypeInputs.length ) {
		current = getCurrentGrantType();
	}

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

		if ( true === visibleOptions['required'] ) {
			el.querySelector( 'input' ).removeAttribute( 'required' );
		}
	} else {
		el.classList.remove( 'hidden' );

		if ( true === visibleOptions['required'] ) {
			el.querySelector( 'input' ).setAttribute( 'required', true );
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

/**
 * Get data-visable-if elements.
 */
const getVisableElems = () => Array.from( document.querySelectorAll( conditionalVisibleElems ) );

export default main;
