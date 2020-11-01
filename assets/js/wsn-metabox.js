/**
* Wocommerce In-Stock Notifier JavaScript
* @author Govind Kumar <gkprmr@gmail.com>
* @version 1.0.0
*/

jQuery( document ).ready( function ( $ ) {
    "use strict";

    $( '.waitlist_data' ).on( 'click', 'a.wsn_waitlist_send_mail_btn', function ( e ) {
        e.preventDefault( );

        var current_obj = $( this ),
            product_id  = current_obj.data( 'product_id' ),
            user_email  = current_obj.data( 'user_email' ),
            actionType  = current_obj.data( 'type'),
            wrapper     = ( actionType == 'all' ) ? current_obj.parent( 'span' ) : current_obj.parents( 'td' );

        if ( actionType == 'all' ) {
            $( document ).find( '#waitlists' + product_id + '' ).find( 'tr.old' ).addClass( 'unclickable' );
        }

        wrapper.block( {
            message: null,
            overlayCSS: {
                background: '#fff no-repeat center',
                opacity: 0.5,
                cursor: 'none'
            }
        } );

        $.ajax( {
            url: _wsn_waitlist.ajax_url,
            dataType: 'json',
            data: {
                action: 'wsn_waitlist_send_mail',
                product: product_id,
                email: user_email,
                type: actionType
            },
            success: function ( res ) {

                if ( res.send ) {
                    wrapper.html( res.msg );
                }
                else {
                    wrapper.html( res.msg );
                }
                wrapper.unblock( );

            }
        } );
    } );
} );