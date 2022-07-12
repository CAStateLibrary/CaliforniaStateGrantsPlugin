import copyToClipboard from './components/copy-to-clipboard';
import checkboxSelectAll from './components/checkbox-select-all';
import tooltips from './components/tooltips';
import ConditionalRequired from './components/conditional-required';
import FormValidation from './components/form-validation';
import FormConsentAccepted from './components/form-consent-accepted';
import wysiwyg from './components/wysiwyg';
import conditionalFormFields from './components/conditional-form-fields';
import repeaterFormFormFields from './components/repeater-form-field';
import updatePostFinder from './components/update-post-finder';

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
	copyToClipboard();
	checkboxSelectAll();
	tooltips();
	wysiwyg();
	ConditionalRequired();
	FormValidation();
	FormConsentAccepted();
	conditionalFormFields();
	repeaterFormFormFields();
	updatePostFinder();
} );
