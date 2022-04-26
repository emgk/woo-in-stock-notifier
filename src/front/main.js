/**
 * Woo-commerce In-Stock Notifier JavaScript
 * @author Govind Kumar <gkprmr@gmail.com>
 * @version 1.0.0
 */
jQuery( document ).ready( function( $ ) {
	$( '.product' ).on( 'keyup', '.wsn-waitlist-email-field', function( e ) {
		// get the email
		const email = $( this ).val();

		// get the form
		const form = $( this ).closest( '.wsn-form' );
		const submitBtn = form.find( '.wsn-submit-form' );

		if ( validateEmail( email ) ) {
			submitBtn.removeClass( 'wsn-submit-form--disabled' );
		} else {
			submitBtn.addClass( 'wsn-submit-form--disabled' );
		}
	} );

	$( '.product' ).on( 'click', '.wsn-submit-form', function( e ) {
		e.preventDefault();

		// get the form
		const
			form = $( this ).closest( '.wsn-form' ),
			emailField = form.find( '.wsn-waitlist-email-field' ),
			url = $( this ).attr( 'href' );

		window.location = url + '&wsn_email=' + emailField.val();
	} );
} );

function validateEmail( email ) {
	const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test( email );
}
