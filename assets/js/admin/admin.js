/**
 * Generates a random string.
 * @param len
 * @param arr
 * @returns {string|string}
 */
function randomStr( len, arr ) {
	let ans = '';
	for ( let i = len; 0 < i; i-- ) {
		ans +=
			arr[Math.floor( Math.random() * arr.length )];
	}
	return ans;
}

/**
 * Generates a token for an input.
 */
window.generateToken = () => {

	const el           = document.getElementById( 'grants_token' );
	const randomString = randomStr( 20, '1234567890abcdefghijklmnopqrstuvwxyz' );
	if ( !el ) {
		return false;
	}

	el.value = randomString;
	window.console.log( randomString );
	return false;
};
