const grantTypeInputs = Array.from( document.querySelectorAll( 'input[name="isForecasted"]' ) );
const conditionalValidationInputs = 'input[data-required-if],textarea[data-required-if],input[required],textarea[required]';
const conditionalValidationOther = '[data-required-if]:not(input):not(textarea)';
const conditionalVisibleElems = 'tr[data-visible-if]';
const startDateElem = Array.from( document.querySelectorAll( 'input[data-min-date-id]' ) );
const endDateElem = Array.from( document.querySelectorAll( 'input[data-max-date-id]' ) );
const requiredPostFinderDiv = Array.from( document.querySelectorAll( 'tr.post_finder_field div[data-post-finder="required"]' ) );
const fundingSources = Array.from( document.querySelectorAll( 'input[name="fundingSource"]' ) );
const disbursementMethod = Array.from( document.querySelectorAll( 'input[name="disbursementMethod"]' ) );

/**
 * Conditional requiring fields if grant is forecasted/active.
 */
const main = () => {
	const visibleElems = getVisableElems();
	if ( visibleElems.length ) {
		const visibleFields = [];
		visibleElems.forEach( input => {
			const { visibleIf }  = input.dataset;
			const visibleOptions = JSON.parse( visibleIf );

			if ( visibleOptions.fieldId && -1 === visibleFields.indexOf( visibleOptions.fieldId ) ) {
				visibleFields.push( visibleOptions.fieldId );
			}
		} );

		if ( visibleFields.length ) {
			visibleFields.forEach( field => {
				const fields = Array.from( document.querySelectorAll( `[name="${field}"]` ) );
				fields.forEach( input => input.addEventListener( 'change', function () {
					visibleElems.forEach( el => maybeSetHiddenClass( el ) );
				} ) );
			} );
		}
	}

	if ( grantTypeInputs.length ) {
		// Update required attributes when the input changes.
		grantTypeInputs.forEach( input => input.addEventListener( 'change', refreshRequiredAttributes ) );
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

	if ( fundingSources.length ) {
		fundingSources.forEach( input => input.addEventListener( 'change', refreshFundingNotesRequireClass ) );
	}

	if ( disbursementMethod.length ) {
		disbursementMethod.forEach( input => input.addEventListener( 'change', refreshFundingMethodNotesRequireClass ) );
	}

	// Kick things off.
	refreshRequiredAttributes();
	refreshMinMaxDateAttributes();
	refreshFundingNotesRequireClass();
	refreshFundingMethodNotesRequireClass();
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
 * Maybe add required class to Funding Source Notes.
 *
 * @returns
 */
const refreshFundingNotesRequireClass = () => {
	const fundingSource = document.querySelector( 'input[name="fundingSource"]:checked' );
	const fundingSouceNotes = document.querySelector( '[name="revenueSourceNotes"]' );

	if ( ! fundingSource || ! fundingSouceNotes ) {
		return;
	}

	const fundingSouceNotesHeading = fundingSouceNotes.closest( 'tr' ).querySelector( 'th' );

	if ( ! fundingSouceNotesHeading ) {
		return;
	}

	if ( ! fundingSource || 'other' !== fundingSource.value ) {
		fundingSouceNotesHeading.classList.remove( 'required' );
	} else {
		fundingSouceNotesHeading.classList.add( 'required' );
	}
};

/**
 * Maybe add required class to Funding Method Notes.
 *
 * @returns
 */
const refreshFundingMethodNotesRequireClass = () => {
	const disbursementMethod = document.querySelector( 'input[name="disbursementMethod"]:checked' );
	const fundingMethodNotes = document.querySelector( '[name="disbursementMethodNotes"]' );

	if ( ! disbursementMethod || ! fundingMethodNotes ) {
		return;
	}

	const fundingMethodNotesHeading = fundingMethodNotes.closest( 'tr' ).querySelector( 'th' );

	if ( ! fundingMethodNotesHeading ) {
		return;
	}

	if ( ! disbursementMethod || 'other' !== disbursementMethod.value ) {
		fundingMethodNotesHeading.classList.remove( 'required' );
	} else {
		fundingMethodNotesHeading.classList.add( 'required' );
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
	const current = getCurrentGrantType();

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

	if ( ! visibleIf ) {
		return;
	}

	const visibleOptions = visibleIf ? JSON.parse( visibleIf ) : null;
	let current = '';

	if ( ! visibleOptions ) {
		return;
	}

	const fields = Array.from( document.querySelectorAll( `[name="${visibleOptions['fieldId']}"]` ) );
	const [field] = fields.filter( input => {
		let value = false;
		switch( input.type ) {
				case 'radio':
					value = input.checked;
					break;
				case 'select-one':
					value = input.selectedIndex;
					break;
				case 'input':
					value = input.value ? true : false;
		}
		return value;
	} );
	current = field ? field.value : '';

	// Check if parent/depedent field has required-if set for active or forecasted grant type.
	const grantType = getCurrentGrantType();
	const { requiredIf } = field.dataset;
	const isFieldRequiredCheck = ( ! requiredIf || ! grantType )
		? true
		: -1 !== requiredIf.split( ',' ).map( s => s.trim() ).indexOf( grantType );

	if (
		'not_equal' === visibleOptions['compare'] && current === visibleOptions['value']
		|| 'equal' === visibleOptions['compare'] && current !== visibleOptions['value']
	) {
		el.classList.add( 'hidden' );

		if ( isFieldRequiredCheck && true === visibleOptions['required'] ) {
			if ( el.querySelector( 'input:not([type="checkbox"])' ) ) {
				el.querySelector( 'input:not([type="checkbox"])' ).removeAttribute( 'required' );
			}
			if ( el.querySelector( 'textarea' ) ) {
				el.querySelector( 'textarea' ).removeAttribute( 'required' );
			}
			if ( el.querySelector( 'td' ) && el.querySelectorAll( 'input[type="checkbox"]' ).length ) {
				el.querySelector( 'td' ).classList.remove( 'fieldset--is-required' );
			}
			if ( el.querySelector( 'th' ) ) {
				el.querySelector( 'th' ).classList.remove( 'required' );
			}
		}
	} else {
		el.classList.remove( 'hidden' );

		if ( isFieldRequiredCheck && true === visibleOptions['required'] ) {
			if ( el.querySelector( 'input:not([type="checkbox"])' ) ) {
				el.querySelector( 'input:not([type="checkbox"])' ).setAttribute( 'required', true );
			}
			if ( el.querySelector( 'textarea' ) ) {
				el.querySelector( 'textarea' ).setAttribute( 'required', true );
			}
			if ( el.querySelector( 'td' ) && el.querySelectorAll( 'input[type="checkbox"]' ).length ) {
				el.querySelector( 'td' ).classList.add( 'fieldset--is-required' );
			}
			if ( el.querySelector( 'th' ) ) {
				el.querySelector( 'th' ).classList.add( 'required' );
			}
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
