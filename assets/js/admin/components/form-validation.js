import Bouncer from 'formbouncerjs';

const forms = Array.from( document.querySelectorAll( '.form--validate' ) );

let bouncer;

/**
 * Setup Forms
 */
const setupForms = () => {

	bouncer = new Bouncer( '.form--validate', {
		customValidations: {
			hasRequiredCheckboxes: ( field ) => {

				// Not a checkbox? Skip.
				if ( 'checkbox' !== field.type ) {
					return false;
				}

				// Not a required fieldset? Skip.
				if ( ! field.closest( '.fieldset--is-required' ) ) {
					return false;
				}

				// Get the checkboxes
				const { name } = field;
				const checkboxes = Array.from( document.querySelectorAll( `input[name="${name}"]` ) );
				let isError = true;

				// Loop checkboxes
				for ( let index = 0; index < checkboxes.length; index++ ) {
					const checkbox = checkboxes[index];

					if ( checkbox.checked ) {
						isError = false; // One is checked? We're valid.
						break; // And we're done.
					}
				}

				// Return validity
				return isError;
			},
			isDeadlineDateValid: ( field ) => {

				// Bail early.
				if ( ! field.matches( '[name="deadline"]' ) ) {
					return false;
				}

				const openDateInput = document.querySelector( '[name="openDate"]' );
				const deadlineInput = document.querySelector( '[name="deadline"]' );

				// Bail if either the values are missing.
				if ( ! openDateInput || ! openDateInput.value || ! deadlineInput || ! deadlineInput.value ) {
					return false;
				}

				const openDate = new Date( openDateInput.value );
				const deadline = new Date( deadlineInput.value );

				// Check the dates
				if ( openDate < deadline ) {
					return false;
				}

				// Invalid
				return true;
			},
			isRangeValid: ( field ) => {
				// Bail early.
				if ( ! field.matches( '[name*="[low]"], [name*="[high]"], [name*="[first]"], [name*="[second]"]' ) ) {
					return false;
				}

				const fieldset = field.closest( 'td' );
				let lowField = fieldset.querySelector( '[name*="[low]"]' );
				let highField = fieldset.querySelector( '[name*="[high]"]' );

				if ( ! lowField ) {
					lowField = fieldset.querySelector( '[name*="[first]"]' );
				}

				if ( ! highField ) {
					highField = fieldset.querySelector( '[name*="[second]"]' );
				}

				const low = parseInt( lowField.value );
				const high = parseInt( highField.value );

				// If we are editing the low field and the high field has an error, try to validate it again
				if ( field === lowField && highField.classList.contains( 'error' ) && ! lowField.classList.contains( 'error' ) ) {
					bouncer.validate( highField );
					// If we are editing the high field and the low field has an error, try to validate it again
				} else if ( field === highField && lowField.classList.contains( 'error' ) && ! highField.classList.contains( 'error' ) ) {
					bouncer.validate( lowField );
				}

				// Bail if the low isn't valid.
				if ( isNaN( low ) ) {
					return false;
				}

				// Bail if the high isn't valid.
				if ( isNaN( high ) ) {
					return false;
				}

				// Check if high is indeed higher than low
				if ( low < high ) {
					return false;
				}

				// Invalid
				return true;
			},
			isMethodValid: ( field ) => {

				// Bail early.
				if ( ! field.matches( '[name="submission_method_url"], [name="submission_method"]' ) ) {
					return false;
				}

				const methodField = document.querySelector( '[name="submission_method_url"]' );
				const { value } = methodField;
				const submissionMethod = document.querySelector( '[name="submission_method"]:checked' );
				const pattern = {
					// eslint-disable-next-line
					email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
					// eslint-disable-next-line
					url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/
				};


				// Check if the value is email or URL depending on the method selection
				if ( submissionMethod && value ) {
					const regex = new RegExp( pattern[submissionMethod.value] );

					// Are we switching submission method? If yes, try to revalidate
					if ( field !== submissionMethod && submissionMethod.classList.contains( 'error' ) ) {
						bouncer.validate( submissionMethod );
					}

					// Are we switching submission method and the URL/email field had an error?
					// If yes, try to revalidate
					if ( field !== methodField && methodField.classList.contains( 'error' ) ) {
						bouncer.validate( methodField );
					}

					// Check if we're using a right email or URL pattern
					return ! regex.test( value );
				} else {
					// Do not check if there's no submission method selected or value entered
					return false;
				}
			},
			isMatchingFundValid: ( field ) => {

				// Bail early.
				if ( ! field.matches( '[name="matchingFunds[required]"], [name="matchingFunds[percent]"]' ) ) {
					return false;
				}

				const { value } = field;

				const matchedFunding = document.querySelector( '[name="matchingFunds[percent]"]' );
				const required = document.querySelector( '[name="matchingFunds[required]"][value="yes"]' );

				// If matched funding is not required empty the field value
				// and make the field not required
				if ( ! required.checked && field !== matchedFunding ) {
					matchedFunding.value = '';
					matchedFunding.removeAttribute( 'required' );

					// Remove visual indicators that the field is required
					const label = document.querySelector( `label[for="${matchedFunding.id}"]` );
					if ( label ) {
						const required = label.querySelector( '.form__required' );
						if ( required ) {
							required.remove();
						}
					}

					// Matched funding is required, make the field required
					// and add visual indicators that it's required
				} else if ( required.checked && field !== matchedFunding ) {
					matchedFunding.setAttribute( 'required', 'required' );

					const label = document.querySelector( `label[for="${matchedFunding.id}"]` );
					const span = document.createElement( 'span' );
					span.classList.add( 'form__required' );
					span.setAttribute( 'aria-label', '(Required)' );
					span.innerText = '*';
					if ( label ) {
						label.insertAdjacentElement( 'afterbegin', span );
					}
				}

				// If matched funding is required and there's no matched funding percentage, invalid
				if ( required.checked && field === matchedFunding && ! value ) {
					return true; // this means invalid

					// If matched funding is not required and the matched funding field had an error, revalidate,
					// and remove error, the field is valid
				} else if ( ! required.checked && field !== matchedFunding && matchedFunding.classList.contains( 'error' ) ) {
					bouncer.validate( matchedFunding );
					return false;
				}

				// Valid
				return false;
			},
			isAwardAmountValid: ( field ) => {

				// Bail early.
				if ( ! field.matches( '[name="estimatedAmounts[same][amount]"], [name="estimatedAmounts[range][low]"], [name="estimatedAmounts[range][high]"], [name="estimatedAvailableFunds"], [name="estimatedAmounts[unknown][first]"], [name="estimatedAmounts[unknown][second]"]' ) ) {
					return false;
				}

				const totalFundingField = document.querySelector( '[name="estimatedAvailableFunds"]' );
				const estimatedAmountFields = document.querySelectorAll( '[name="estimatedAmounts[same][amount]"], [name="estimatedAmounts[range][low]"], [name="estimatedAmounts[range][high]"],[name="estimatedAmounts[unknown][first]"], [name="estimatedAmounts[unknown][second]"]' );
				const { value: totalFunding } = totalFundingField;
				const { value: estimatedAmount } = field;

				// If there's no total funding entered, bail
				if ( ! totalFunding ) {
					return false;
				}

				// If we're editing estimated award amounts and the values are higher than
				// total funding, this is invalid, return an error
				if ( field !== totalFundingField && parseInt( estimatedAmount, 10 ) > parseInt( totalFunding, 10 ) ) {
					return true;
				} else if ( field === totalFundingField ) {
					estimatedAmountFields.forEach( estimatedAmountField => {
						bouncer.validate( estimatedAmountField );
					} );
				}

				// No conditions are met, assume valid
				return false;
			},
			isValidStartDate: ( field ) => {
				// Bail early.
				if ( ! field.matches( 'input[data-max-date-id]' ) ) {
					return false;
				}

				const maxDateElem = document.getElementById( field.dataset.maxDateId );

				if ( ! maxDateElem || ! maxDateElem.value || ! field.value ) {
					return false;
				}

				const minDate = new Date( field.value );
				const maxDate = new Date( maxDateElem.value );

				return ! ( minDate <= maxDate );
			},
			isValidEndDate: ( field ) => {
				// Bail early.
				if ( ! field.matches( 'input[data-min-date-id]' ) ) {
					return false;
				}

				const minDateElem = document.getElementById( field.dataset.minDateId );

				if ( ! minDateElem || ! minDateElem.value || ! field.value ) {
					return false;
				}

				const minDate = new Date( minDateElem.value );
				const maxDate = new Date( field.value );

				return ! ( minDate <= maxDate );
			},
			isFundingSourceNotesRequired: ( field ) => {
				if ( ! field.matches( '[name="revenueSourceNotes"]' ) || field.value ) {
					return false;
				}

				const form = field.closest( 'form' );
				const fundingSource = form.querySelector( 'input[name="fundingSource"]:checked' );

				if ( ! fundingSource || 'other' !== fundingSource.value ) {
					return false;
				}

				// No conditions are met, assume invalid.
				return true;
			},
			isFundingMethodNotesRequired: ( field ) => {
				if ( ! field.matches( '[name="disbursementMethodNotes"]' ) || field.value ) {
					return false;
				}

				const form = field.closest( 'form' );
				const fundingSource = form.querySelector( 'input[name="disbursementMethod"]:checked' );

				if ( ! fundingSource || 'other' !== fundingSource.value ) {
					return false;
				}

				// No conditions are met, assume invalid.
				return true;
			},
			isMaxLimitReachedField: ( field ) => {
				// Bail
				if ( ! field.matches( 'input[data-maxlength]:not(input[pattern])' ) ) {
					return false;
				}

				const maxLength = parseInt( field.dataset.maxlength, 10 );
				const currentValueLength = parseInt( field.value.length, 10 );

				if ( currentValueLength > maxLength ) {
					return true;
				} else {
					return false;
				}
			},
			isFiscalYearValid: ( field ) => {

				if ( ! field.matches( 'select#fiscalYear' ) ) {
					return false;
				}

				if ( window.allowedFiscalYears?.includes( field.value ) ) {
					return false;
				}

				// No conditions are met, assume invalid.
				return true;
			},

		},
		messages: {
			hasRequiredCheckboxes: 'Please check at least one value.',
			isDeadlineDateValid: 'Invalid date. Please check that the deadline is after the open date.',
			isMethodValid: 'Please check that this matches submission method and that the URL or email is well formed.',
			isRangeValid: 'Invalid range. Please check that the first number is lower than the second one.',
			isMatchingFundValid: 'Please set a matched funding percentage.',
			isAwardAmountValid: 'Please check that this number is lower than the Total Estimated Available Funding.',
			isValidStartDate: 'Start date is invalid, please select start date before end date.',
			isValidEndDate: 'End date is invalid, please select end date after start date.',
			isFundingSourceNotesRequired: 'Please add funding source notes. ( Required for funding source "Other" )',
			isFundingMethodNotesRequired: 'Please add funding method notes. ( Required for funding method "Other" )',
			isMaxLimitReachedField: 'Maximum characters limit reached.',
			isFiscalYearValid: 'Fiscal year does not match the Associated Grant.',
		},
		disableSubmit: true // We need to handle some additional logic here for save/continue
	} );

	forms.forEach( form => {

		const buttons = Array.from( form.querySelectorAll( '.button[name="save"]' ) );

		buttons.forEach( button => {
			button.removeAttribute( 'name' );
			button.setAttribute( 'data-value', button.value );
			button.removeAttribute( 'name' );
		} );

		// Create default save input
		const input = document.createElement( 'input' );

		input.type = 'hidden';
		input.name = 'save';
		input.value = 0; // Default is to continue

		form.appendChild( input );

		const inputMaxLimitFields = Array.from( form.querySelectorAll( 'input[data-maxlength]:not(input[pattern])' ) );

		inputMaxLimitFields.forEach( input => {
			if ( ! input.dataset.maxlength || ! input.id ) {
				return;
			}

			input.addEventListener( 'input', handleMaxLimitReachedField );

			const span = document.createElement( 'span' );
			span.setAttribute( 'id', `${input.id}-characters` );
			span.textContent = `${input.value.length} of ${input.dataset.maxlength} characters`;
			input.parentNode.appendChild( span );
		} );
	} );
};

/**
 * Setup listeners
 */
const setupListeners = () => {

	document.addEventListener( 'bouncerRemoveError', handleBouncerRemoveFieldsetError, false );
	document.addEventListener( 'bouncerRemoveError', handleBouncerRemoveDeadlineDateError, false );
	document.addEventListener( 'bouncerRemoveError', handleBouncerRemoveRangeError, false );

	document.addEventListener( 'bouncerShowError', handleBouncerShowFieldsetError, false );

	forms.forEach( form => {
		form.addEventListener( 'click', handleFormClick );
		form.addEventListener( 'submit', handleFormSubmit );
	} );
};

/**
 * Handle input field edit/keyup event.
 *
 * @param {object} event
 */
const handleMaxLimitReachedField = ( event ) => {
	const elem = event.target;
	const maxLength = parseInt( elem.dataset.maxlength, 10 );
	const currentValueLength = parseInt( elem.value.length, 10 );
	const span = document.getElementById( `${elem.id}-characters` );
	span.textContent = `${currentValueLength} of ${maxLength} characters`;

	if ( currentValueLength > maxLength ) {
		elem.setAttribute( 'aria-invalid', true );

		const message = elem.parentNode.querySelector( '.error-message' );

		if ( ! message ) {
			const errorMessage = document.createElement( 'div' );
			errorMessage.setAttribute( 'id', `bouncer-error_${elem.name}` );
			errorMessage.classList.add( 'error-message' );
			errorMessage.textContent = 'Maximum characters limit reached.';
			elem.parentNode.appendChild( errorMessage );
		}
		span.setAttribute( 'style', 'color:red;' );
	} else {
		elem.setAttribute( 'aria-invalid', false );
		const message = elem.parentNode.querySelector( '.error-message' );

		if ( message ) {
			elem.parentNode.removeChild( message );
		}
		span.removeAttribute( 'style' );
	}
};

/**
 * Handle bouncer remove required fieldset error
 * @param {object} event the event object
 */
const handleBouncerRemoveFieldsetError = ( event ) => {
	const { target: field } = event;
	const fieldset = field.closest( '.fieldset--is-required' );

	// Not a required fieldset? Skip.
	if ( ! fieldset ) {
		return;
	}

	// Revalidate all the required fields
	// If one in the set is valid, they'll all be validated.
	const invalidArray = bouncer.validateAll( fieldset );
	const isInvalid = ( invalidArray.length );

	// Check invalid
	if ( isInvalid ) {
		return;
	}

	// Remove our custom message
	const message = fieldset.querySelector( '.error-message' );

	// Might've already been removed. So check.
	if ( ! message ) {
		return;
	}

	message.parentNode.removeChild( message );

};

/**
 * Handle bouncer remove deadline date error
 * @param {object} event the event object
 */
const handleBouncerRemoveDeadlineDateError = ( event ) => {
	const { target: field } = event;

	// Bail early.
	if ( ! field.matches( '[name="deadline"]' ) ) {
		return;
	}

	const fieldset = field.closest( 'td' );

	bouncer.validateAll( fieldset );
};

/**
 * Handle bouncer remove range error
 * @param {object} event the event object
 */
const handleBouncerRemoveRangeError = ( event ) => {
	const { target: field } = event;

	// Bail early.
	if ( ! field.matches( '[name*="[low]"], [name*="[high]"], [name*="[first]"], [name*="[second]"]' ) ) {
		return;
	}

	const fieldset = field.closest( 'td' );

	bouncer.validateAll( fieldset );
};

/**
 * Handle bouncer show fieldset error
 * @param {object} event the event object
 */
const handleBouncerShowFieldsetError = ( event ) => {
	const { target: field } = event;
	const fieldset = field.closest( '.fieldset--is-required' );

	// Not a required fieldset? Skip.
	if ( ! fieldset ) {
		return;
	}

	// Create ID based off of name (should be unique)
	const id = `bouncer-error_${field.name}`;
	const errorMessages = Array.from( fieldset.querySelectorAll( '.error-message' ) );

	// Copy the error message for later.
	const errorMessage = errorMessages[0].cloneNode( true );

	// Add our custom ID
	errorMessage.setAttribute( 'id', id );

	// Loop through all the errors and hide them.
	// We still need them for bouncer's logic to work.
	errorMessages.forEach( message => {

		// Skip our custom one though.
		if ( id === message.id ) {
			return;
		}

		message.classList.add( 'srt' );
	} );

	// Does it already exist? Bail.
	if ( document.getElementById( id ) ) {
		return;
	}

	fieldset.appendChild( errorMessage );
};

/**
 * Scroll to First Error
 * @param {object} invalidFields fields returned that are not valid.
 */
const scrollToFirstError = ( invalidFields ) => {
	const [ firstField ] = invalidFields;
	const elementToScroll = firstField.parentElement;

	elementToScroll.scrollIntoView( {
		behavior: 'smooth',
		block: 'start',
		inline: 'nearest'
	} );
};


/**
 * Handle form click
 * @param {object} event the event object
 */
const handleFormClick = ( event ) => {
	const { target } = event;

	if ( ! target.matches( '.button[type="submit"]' ) ) {
		return;
	}

	const form = target.closest( '.form--validate' );

	const invalidFields = bouncer.validateAll( form );

	// Check valid first.
	// Bail if not.
	if ( invalidFields.length ) {
		event.preventDefault();
		scrollToFirstError( invalidFields );
		return;
	}

	const { value } = event.target;
	if ( value && 'Publish' === value ) {
		const input = document.createElement( 'input' );
		input.name = 'post_status';
		input.type = 'hidden';
		input.value = 'publish';

		form.appendChild( input );
	}
};

/**
 * Handle form submit
 * @param {object} event the event object
 */
const handleFormSubmit = ( event ) => {
	const { target: form } = event;
	const { submitter } = event;

	if ( ! submitter.matches( '#save-post' ) ) {
		const invalidFields = bouncer.validateAll( form );

		if ( invalidFields.length ) {
			event.preventDefault();
			scrollToFirstError( invalidFields );
			return;
		}
	}
};

/**
 * Setup Wysiwygs
 */
const setupWysiwygs = () => {
	const requiredIfWysiwygs = Array.from( document.querySelectorAll( '.wysiwyg[data-required-if]' ) );

	// Ensure all the textareas are required.
	requiredIfWysiwygs.forEach( wysiwyg => {
		const textarea = wysiwyg.querySelector( 'textarea' );
		textarea.setAttribute( 'data-required-if', wysiwyg.getAttribute( 'data-required-if' ) );
	} );

	const requiredWysiwygs = Array.from( document.querySelectorAll( '.wysiwyg[required="true"]' ) );
	// Ensure all the textareas are required.
	requiredWysiwygs.forEach( wysiwyg => {
		const textarea = wysiwyg.querySelector( 'textarea' );
		textarea.setAttribute( 'required', wysiwyg.getAttribute( 'required' ) );
	} );
};

/**
 * Init
 */
const init = () => {
	// Bail early.
	if ( ! forms.length ) {
		return;
	}

	setupWysiwygs();
	setupForms();
	setupListeners();
};

export default init;
