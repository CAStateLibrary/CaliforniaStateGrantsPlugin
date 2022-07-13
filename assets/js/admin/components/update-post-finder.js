/**
 *
 */
export default function updatePostFinder() {
	// updatePostFinder event is fired from the Post Finder plugin
	window.addEventListener( 'updatePostFinder', async ( event ) => {
		const { ids } = event.detail;

		// reset the allowed fiscal years.
		window.allowedFiscalYears = null;

		if ( ! Array.isArray( ids ) || ! ids.length ) {
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

			if ( ! json.success ) {
				throw new Error( json.data );
			}

			// add allowed fiscal years to the global scope.
			window.allowedFiscalYears = json.data;

		} catch ( error ) {
			console.log( { error } );
		}

	} );
}
