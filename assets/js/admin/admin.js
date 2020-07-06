import CopyToClipboard from './components/copy-to-clipboard';
import CheckboxSelectAll from './components/checkbox-select-all';
import Tooltips from './components/tooltips';
import ConditionalRequired from './components/conditional-required';
import FormValidation from './components/form-validation';
import Wysiwyg from './components/wysiwyg';


document.addEventListener( 'DOMContentLoaded', () => {
	CopyToClipboard();
	CheckboxSelectAll();
	Tooltips();
	Wysiwyg();
	ConditionalRequired();
	FormValidation();
} );
