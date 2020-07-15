import CopyToClipboard from './components/copy-to-clipboard';
import CheckboxSelectAll from './components/checkbox-select-all';
import Tooltips from './components/tooltips';
import ConditionalRequired from './components/conditional-required';
import FormValidation from './components/form-validation';
import Wysiwyg from './components/wysiwyg';

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
} );
