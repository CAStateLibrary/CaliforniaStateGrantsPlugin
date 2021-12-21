const grantTypeInputs = Array.from( document.querySelectorAll( 'input[name="isForecasted"]' ) );
const conditionalActiveFields = '.onlyActive';
const conditionalForecastedFields = '.onlyForecasted';

/**
 * Conditional requiring fields if grant is forecasted/active.
 */
const main = () => {
	if ( ! grantTypeInputs.length || ( ! getActiveFields().length && ! getForecastedFields().length ) ) {
		return;
	}

	// Update required attributes when the input changes.
	grantTypeInputs.forEach( input => input.addEventListener( 'change', refreshConditionalFields ) );

	// Kick things off.
	refreshConditionalFields();
};

/**
 * Get current grant type.
 */
const getCurrentGrantType = () => {
	const [current] = grantTypeInputs.filter( input => input.checked );

	return current ? current.value : '';
};

/**
 * Refresh all conditional fields for only Active or only Forecasted.
 */
const refreshConditionalFields = () => {
	getActiveFields().forEach( field => showHideActive( field ) );
	getForecastedFields().forEach( field => showHideForecasted( field ) );
};

/**
 * Show/hide only Active fields.
 * @param {HTMLElement} field
 */
const showHideActive = field => {
	const current = getCurrentGrantType();

	if ( 'active' === current ) {
		field.style.display = '';
	} else {
		field.style.display = 'none';
	}
};

/**
 * Show/hide only Forecasted fields.
 * @param {HTMLElement} field
 */
const showHideForecasted = field => {
	const current = getCurrentGrantType();

	if ( 'forecasted' === current ) {
		field.style.display = '';
	} else {
		field.style.display = 'none';
	}
};

/**
 * Get only Active fields.
 */
const getActiveFields = () => Array.from( document.querySelectorAll( conditionalActiveFields ) );

/**
 * Get only Forecasted fields.
 */
const getForecastedFields= () => Array.from( document.querySelectorAll( conditionalForecastedFields ) );


export default main;
