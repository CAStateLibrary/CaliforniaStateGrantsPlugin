import CopyToClipboard from './components/copy-to-clipboard';
import CheckboxSelectAll from './components/checkbox-select-all';
import Tooltips from './components/tooltips';
import ConditionalRequired from './components/conditional-required';
import FormValidation from './components/form-validation';
import FormConsentAccepted from './components/form-consent-accepted';
import Wysiwyg from './components/wysiwyg';
import ConditionalFormFields from './components/conditional-form-fields';
import RepeaterFormFormFields from './components/repeater-form-field';

// polyfill closest.
if ( !Element.prototype.closest ) {
	Element.prototype.closest = function( s ) {
		let el = this;

		do {
			if ( el.matches( s ) ) return el;
			el = el.parentElement || el.parentNode;
		} while ( null !== el && 1 === el.nodeType );
		return null;
	};
}

document.addEventListener( 'DOMContentLoaded', () => {
	CopyToClipboard();
	CheckboxSelectAll();
	Tooltips();
	Wysiwyg();
	ConditionalRequired();
	FormValidation();
	FormConsentAccepted();
	ConditionalFormFields();
	RepeaterFormFormFields();
} );

// console.log( 'Haz grant id?', { grantId } );
window.addEventListener( 'updatePostFinder', async ( event ) => {
	const { ids } = event.detail;

	if ( ! ids || ! ids.length ) {
		window.allowedFiscalYears = null;
		return;
	}

	const nonce = document.querySelector( 'input[name="post_finder_nonce"]' ).value;
	const data  = new URLSearchParams( {
		action: 'get_fiscal_years_by_grant',
		grantId: ids[0],
		nonce
	} );

	let json;

	try {
		const response = await fetch( window.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data.toString(),
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'Cache-Control': 'no-cache',
			},
		} );

		if ( !response.ok ) {
			throw new Error( response.statusText );
		}

		json = await response.json();

		window.allowedFiscalYears = json;

	} catch ( error ) {
		console.log( { error } );
	}

	console.log( 'Fiscal Years', json );
} );
