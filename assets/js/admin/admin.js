/* global jQuery */

jQuery( document ).ready( function ( $ ) {
	$( '.csl-datepicker' ).datepicker( {
		dateFormat: 'MM d, yy'
	} );

	$( '.csl-datepicker' ).datepicker( 'setDate', $( '.csl-datepicker' ).val() );
} );
