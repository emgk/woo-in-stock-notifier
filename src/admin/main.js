/**
 * Woo-commerce In-Stock Notifier JavaScript
 * @author Govind Kumar <gkprmr@gmail.com>
 * @version 1.0.0
 */
jQuery( document ).ready( function( $ ) {
	const total = 0;

	$( 'a#show_archived' ).on( 'click', function( e ) {
		e.preventDefault();

		const current = $( this ),
			product_id = current.data( 'product_id' );

		$.ajax( {
			url: _wsn_waitlist.ajax_url,
			dataType: 'json',
			data: { action: 'archive_function', product: product_id, type: '_show' },
			success( data ) {
				if ( data != '' ) {
					current.parents().find( '.waitlist_data#' + product_id ).hide();
					const archive_row = current.parents().find( '.archived_data_panel#' + product_id ).fadeIn( 500 ).find( '._archive_userlist' );
					archive_row.append( '<tr id="archive_table_head"><td class="wsn-user-email-col">Email</td><td id="restore_archived">Restore</td> <td id="r_archived">Remove</td></tr>' );
					$.each( data, function( key, data ) {
						archive_row.append( '<tr id="row_' + product_id + '" ><td>' + data + '</td><td id="restore_archived"><a href="javascript:void( 0 );" class="restoreEmail" data-uid="' + data + '" data-pid="' + product_id + '"><span class="dashicons dashicons-image-rotate"></span></a> </td><td id="r_archived"><a href="javascript:void( 0 );" class="removeArchivedUser" data-uid="' + data + '" data-pid="' + product_id + '"><span class="dashicons dashicons-no"></span></a></td></tr>' );
					} );
					archive_row.append( '</table>' );
				}
			},
		} );
	} );

	$( 'ul.wsn-tabs-nav li.wsn-tabs-nav-item' ).click( function() {
		const tabId = $( this ).attr( 'data-tab' );

		$( this ).closest( '.wsn-tabs' ).find( '.wsn-tabs-nav-item' ).removeClass( 'wsn-tabs-nav-item--current' );
		$( this ).closest( '.wsn-tabs' ).find( '.wsn-tabs__content' ).removeClass( 'wsn-tabs__content--current' );

		$( this ).addClass( 'wsn-tabs-nav-item--current' );
		$( '#' + tabId ).addClass( 'wsn-tabs__content--current ' );
	} );

	$( '.archived_data_panel' ).on( 'click', 'a.removeArchivedUser', function( e ) {
		e.preventDefault();

		const current_obj = $( this ),
			user_email = current_obj.data( 'uid' ),
			product_id = current_obj.data( 'pid' );

		const data = {
			action: 'archive_function',
			product: product_id,
			user_id: user_email,
			type: '_remove',
		};
		jQuery.post( ajaxurl, data, function() {
			current_obj.parent().closest( 'tr' ).fadeOut( 400 );
		} );
	} );

	$( '.archived_data_panel' ).on( 'click', 'a.restoreEmail', function( e ) {
		e.preventDefault();

		const current_obj = $( this ),
			user_email = current_obj.data( 'uid' ),
			product_id = current_obj.data( 'pid' );

		const data = {
			action: 'archive_function',
			product: product_id,
			user_id: user_email,
			type: '_restore',
		};
		jQuery.post( ajaxurl, data, function( data ) {
			current_obj.parent().closest( 'tr' ).fadeOut( 1000 );
		} );
	} );

	$( 'a.close_archived' ).click( function( e ) {
		e.preventDefault();

		const current_obj = $( this ),
			product_id = current_obj.attr( 'id' );

		$( '#form' + product_id ).hide();

		current_obj.parents().find( '.waitlist_data#' + product_id ).show();
		current_obj.parents().find( '.archived_data_panel#' + product_id ).find( '._archive_userlist' ).html( '' );
		current_obj.parents().find( '.archived_data_panel#' + product_id ).hide();
	} );

	$( '.wsn-users-list' ).on( 'click', 'a.removeUser', function( e ) {
		e.preventDefault();

		const current_obj = $( this );

		const product_id = current_obj.data( 'product_id' ),
			email = current_obj.data( 'email' ),
			uid = current_obj.data( 'uid' ),
			total = current_obj.data( 'total' ),
			nonce = current_obj.data( 'wp_nonce' ),
			action = current_obj.data( 'action' );

		const data = {
			action: 'removeUser',
			security: nonce,
			p_id: product_id,
			wsn_email: email,
			inc: total,
			wp_action: action,
		};

		jQuery.post( ajaxurl, data, function() {
			$( '#row-' + uid + '-' + product_id ).fadeOut( 1000 );
		} );
	} );

	$( 'a#wsn_add_new_user' ).on( 'click', function( e ) {
		e.preventDefault();

		const formid = $( this ).data( 'product_id' );

		$( '#wsn-add-user-' + formid ).show();
		$( '#wsn-add-tabs-' + formid ).hide();

		$( this ).parent().find( '.usrEmail#' + formid ).focus();
	} );

	$( 'a#wsn_hide_add_new_user' ).on( 'click', function( e ) {
		e.preventDefault();

		const formid = $( this ).data( 'product_id' );

		$( '#wsn-add-user-' + formid ).hide();
		$( '#wsn-add-tabs-' + formid ).show();
	} );

	$( 'button#wsn_add_btn' ).on( 'click', function( e ) {
		e.preventDefault();

		const current_obj = $( this );

		let form_id = current_obj.data( 'product_id' ),
			email = current_obj.parent().find( '.usrEmail#' + form_id ).val(),
			total = current_obj.data( 'total' ),
			uid = total + 1,
			nonce = current_obj.data( 'nonce' );

		current_obj.parent().find( '.usrEmail#' + form_id ).val( '' );
		current_obj.parent().find( '.wsn-empty' ).hide();

		const data = {
			action: 'addNewUser',
			security: nonce,
			p_id: form_id,
			inc: uid,
			email,
		};

		if ( ! email ) {
			alert( 'Please enter email address.' );
			return false;
		}

		const email_pattern = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

		if ( ! email_pattern.test( email ) ) {
			alert( 'Please enter valid email address' );
			current_obj.parent().find( '.usrEmail#' + form_id ).focus();

			return false;
		}

		jQuery.post( ajaxurl, data, function( data ) {
			const outputData = JSON.parse( data );

			if ( outputData.status == 'success' ) {
				total += 1;
				current_obj.data( 'total', total );
				current_obj.parents().find( '.no_user#' + form_id ).hide();
				$( 'table#waitlists' + form_id ).append( '<tr id="row-' + outputData.currentId + '-' + form_id + '"><td >' + outputData.email + '</td><td class="wsn-email-col">' + outputData.emailLink + '</td><td class="wsn-action-col">' + outputData.removeLink + '</td></tr>' );
				$( 'table#waitlists' + form_id + ' tr:last' ).animate( { backgroundColor: 'rgb(247, 255, 176)' }, 'slow' ).animate( { backgroundColor: '#fff' }, 'slow' );
				current_obj.parent().find( '.usrEmail#' + form_id ).focus();
			} else if ( outputData.status == 'exists' ) {
				alert( email + ' is already exist! ' );
			}
		} );
	} );

	$( '.wsn-tab-table' ).on( 'click', 'a.wsn_waitlist_send_mail_btn', function( e ) {
		e.preventDefault();

		const
			current_obj = $( this ),
			product_id = current_obj.data( 'product_id' ),
			user_email = current_obj.data( 'user_email' ),
			actionType = current_obj.data( 'type' ),
			wrapper = ( actionType == 'all' ) ? current_obj.parent( '.wsn-tab-table' ) : current_obj.parents( '.wsn-tab-table-item-col-action' );

		if ( actionType == 'all' ) {
			$( document ).find( '#waitlists' + product_id + '' ).find( 'tr.old' ).addClass( 'unclickable' );
		}

		wrapper.block( {
			message: null,
			overlayCSS: {
				background: '#fff no-repeat center',
				opacity: 0.5,
				cursor: 'none',
			},
		} );

		$.ajax( {
			url: _wsn_waitlist.ajax_url,
			dataType: 'json',
			data: {
				action: 'wsn_waitlist_send_mail',
				product: product_id,
				email: user_email,
				type: actionType,
			},
			success( res ) {
				if ( res.send ) {
					wrapper.html( res.msg );
				} else {
					wrapper.html( res.msg );
				}
				wrapper.unblock();
			},
		} );
	} );

	const wsn_waitlist_form = $( document ).find( 'form.variations_form' ),
		wsn_email = function() {
			const var_email_input = $( document ).find( '#wsn_waitlist_email' ),
				var_email_link = var_email_input.parents( '.wsn_waitlist_form' ).find( 'a.btn' );

			var_email_input.on( 'input', function( e ) {
				const link_href = var_email_link.attr( 'href' ),
					email_val = var_email_input.val(),
					variation_id = $( '.variation_id' ).val(),
					email_name = var_email_input.attr( 'name' );
				var_email_link.prop( 'href', link_href + '&' + email_name + '=' + email_val + '&var_id=' + variation_id );
			} );
		};
	wsn_waitlist_form.on( 'woocommerce_variation_has_changed', wsn_email );
} );
