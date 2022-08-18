/* global CAGrantPlugin */

const form = document.getElementById( 'post' );
const consentCheckStatus = [ 'Update', 'Publish', 'publish', 'Schedule' ];
const hiddenStatusField = document.getElementById( 'hidden_post_status' );

/**
 * Conditional requiring fields if grant is forecasted/active.
 */
const main = () => {
	form?.addEventListener( 'submit', function( event ) {
		// We do not need consent box on portal site.
		if ( CAGrantPlugin.isPortal ) {
			this.submit();
			return true;
		}

		if ( // Check if it's submit button click other then publish or update button.
			'submit' === document?.activeElement?.type // Form submitted with publish/update button.
				&& document?.activeElement?.value
				&& ! consentCheckStatus.includes( document?.activeElement?.value )
		) {
			this.submit();
			return true;
		}

		if ( // Check for form on enter field submit event with status other then publish.
			'submit' !== document?.activeElement?.type // Form submitted with enter click on any of the form field.
				&& hiddenStatusField?.value
				&& ! consentCheckStatus.includes( hiddenStatusField.value )
		) {
			this.submit();
			return true;
		}

		const postType = document.getElementById( 'post_type' );
		let message = '';

		if ( postType?.value && postType.value === CAGrantPlugin.grantAwardSlug ) {
			message = CAGrantPlugin.l10n.grantAwardConsent;
		} else if ( postType?.value && postType.value === CAGrantPlugin.grantSlug ) {
			const isForcasted = document.querySelector( '[name="isForecasted"]:checked' );
			message = isForcasted && ( 'forecasted' === isForcasted.value ) ? CAGrantPlugin.l10n.forcastGrantConsent : CAGrantPlugin.l10n.activeGrantConsent;
		} else { // Only need this consent for grant and grant award post type.
			this.submit();
			return true;
		}

		if ( ! message ) {
			this.submit();
			return true;
		}

		// eslint-disable-next-line no-alert
		if ( confirm( message ) ) {
			this.submit();
			return true;
		}

		// Remove the post_status hidden input that's added when the Publish button is clicked.
		const postStatus = document.querySelector( 'input[name="post_status"][value="publish"]' );
		if ( postStatus ) {
			postStatus.remove();
		}

		event.preventDefault();
		return false;
	} );
};

export default main;
