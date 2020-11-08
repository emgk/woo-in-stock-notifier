/**
 * Woo-commerce In-Stock Notifier JavaScript
 * @author Govind Kumar <gkprmr@gmail.com>
 * @version 1.0.0
 */
jQuery( document ).ready( function( $ ) {
	$( 'ul.wsn-tabs-nav li.wsn-tabs-nav-item' ).click( function() {
		const tabId = $( this ).attr( 'data-tab' );
		const tabType = $( this ).attr( 'data-type' );

		$( this ).closest( '.wsn-tabs' ).find( '.wsn-tabs-nav-item' ).removeClass( 'wsn-tabs-nav-item--current' );
		$( this ).closest( '.wsn-tabs' ).find( '.wsn-tabs__content' ).removeClass( 'wsn-tabs__content--current' );

		$( this ).addClass( 'wsn-tabs-nav-item--current' );
		$( '#' + tabId ).addClass( 'wsn-tabs__content--current ' );

		const actions = $( this ).closest( '.wsn-tabs' ).find( '.wsn-tabs__action' );

		if ( 'archived' === tabType ) {
			actions.addClass( 'wsn-hidden' );
		} else {
			actions.removeClass( 'wsn-hidden' );
		}
	} );

	$( '.wsn-archived-list' ).on( 'click', 'a.removeArchivedUser', function( e ) {
		e.preventDefault();

		const currentEl = $( this ),
			user_email = currentEl.data( 'uid' ),
			product_id = currentEl.data( 'pid' ),
			row = currentEl.closest( '.wsn-tab-table-item' ),
			table = currentEl.closest( '.wsn-tab-table' );

		const data = {
			action: 'archive_function',
			product: product_id,
			user_id: user_email,
			type: '_remove',
		};

		jQuery.post( ajaxurl, data, function() {
			if ( ! data ) {
				return;
			}

			row.fadeOut( 1000, function() {
				row.remove();

				if ( table.find( '.wsn-tab-table-body .wsn-tab-table-item' ).length <= 0 ) {
					table.find( '.wsn-notice' ).removeClass( 'wsn-hidden' );
				}
			} );
		} );
	} );

	$( '.wsn-archived-list' ).on( 'click', 'a.restoreEmail', function( e ) {
		e.preventDefault();

		const currentEl = $( this ),
			user_email = currentEl.data( 'uid' ),
			product_id = currentEl.data( 'pid' ),
			row = currentEl.closest( '.wsn-tab-table-item' ),
			table = currentEl.closest( '.wsn-tab-table' );

		const data = {
			action: 'archive_function',
			product: product_id,
			user_id: user_email,
			type: '_restore',
		};

		jQuery.post( ajaxurl, data, function( data ) {
			if ( ! data ) {
				return;
			}

			row.fadeOut( 1000, function() {
				row.remove();

				if ( table.find( '.wsn-tab-table-body .wsn-tab-table-item' ).length <= 0 ) {
					table.find( '.wsn-notice' ).removeClass( 'wsn-hidden' );
				}
			} );
		} );
	} );

	$( 'a.close_archived' ).click( function( e ) {
		e.preventDefault();

		const currentEl = $( this ),
			product_id = currentEl.attr( 'id' );

		$( '#form' + product_id ).hide();

		currentEl.parents().find( '.waitlist_data#' + product_id ).show();
		currentEl.parents().find( '.archived_data_panel#' + product_id ).find( '._archive_userlist' ).html( '' );
		currentEl.parents().find( '.archived_data_panel#' + product_id ).hide();
	} );

	$( '.wsn-tab-table' ).on( 'click', 'a.removeUser', function( e ) {
		e.preventDefault();

		const currentEl = $( this );

		const productId = currentEl.data( 'product_id' ),
			email = currentEl.data( 'email' ),
			uid = currentEl.data( 'uid' ),
			total = currentEl.data( 'total' ),
			nonce = currentEl.data( 'wp_nonce' ),
			action = currentEl.data( 'action' ),
			row = $( '#row-' + uid + '-' + productId ),
			table = row.closest( '.wsn-tab-table' );

		const data = {
			action: 'removeUser',
			security: nonce,
			p_id: productId,
			wsn_email: email,
			inc: total,
			wp_action: action,
		};

		jQuery.post( ajaxurl, data, function() {
			row.fadeOut( 1000, function() {
				row.remove();

				if ( table.find( '.wsn-tab-table-body .wsn-tab-table-item' ).length <= 0 ) {
					table.find( '.wsn-notice' ).removeClass( 'wsn-hidden' );
				}
			} );
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

		const currentEl = $( this );

		// get the product id
		const formID = currentEl.data( 'product_id' );

		// get email field
		const emailField = currentEl.closest( '.wsn-form' ).find( '#user-email-field-' + formID );

		// get the close button
		const closeButton = currentEl.closest( '.wsn-form' ).find( '#wsn_hide_add_new_user' );

		let
			email = emailField.val(),
			total = currentEl.data( 'total' ),
			uid = total + 1,
			nonce = currentEl.data( 'nonce' ),
			form = $( '#wsn-tab-table-' + formID );

		// remove email
		emailField.val( '' );

		const data = {
			action: 'addNewUser',
			security: nonce,
			p_id: formID,
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
			emailField.focus();

			return false;
		}

		jQuery.post( ajaxurl, data, function( data ) {
			const outputData = JSON.parse( data );

			switch ( outputData.status ) {
				case 'success':
					total += 1;
					currentEl.data( 'total', total );
					currentEl.parents().find( '.no_user#' + formID ).hide();

					const tableList = $( '#wsn-tab-table-' + formID + ' > .wsn-tab-table-body' );

					tableList.append(
						'<div class="wsn-tab-table-item" id="row-' + outputData.currentId + '-' + formID + '">\n' +
                        '<div class="wsn-tab-table-item-col">' + outputData.email + '</div>\n' +
                        '<div class="wsn-tab-table-item-col">\n' +
                        '<div class="wsn-tab-table-item-col-actions">\n' +
                        '<div class="wsn-tab-table-item-col-action">' + outputData.emailLink + '</div>\n' +
                        '<div class="wsn-tab-table-item-col-action">' + outputData.removeLink + '</div>\n' +
                        '</div>\n' +
                        '</div>\n' +
                        '</div>'
					);

					form.find( '.wsn-notice' ).addClass( 'wsn-hidden' );
					closeButton.click();

					$( '#wsn-tab-table-' + formID + ' .wsn-tab-table-item:last' ).animate( { backgroundColor: 'rgb(247, 255, 176)' }, 'slow' ).animate( { backgroundColor: '#fff' }, 'slow' );
					break;
				case 'exists':
					alert( email + ' is already exist! ' );
					emailField.focus();
					break;
			}
		} );
	} );

	$( '.wsn-tab-table' ).on( 'click', 'a.wsn_waitlist_send_mail_btn', function( e ) {
		e.preventDefault();

		const
			currentEl = $( this ),
			product_id = currentEl.data( 'product_id' ),
			user_email = currentEl.data( 'user_email' ),
			actionType = currentEl.data( 'type' ),
			wrapper = ( actionType == 'all' ) ? currentEl.parent( '.wsn-tab-table' ) : currentEl.parents( '.wsn-tab-table-item-col-action' );

		if ( actionType == 'all' ) {
			$( document ).find( '#waitlists-' + product_id + '' ).find( 'tr.old' ).addClass( 'unclickable' );
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
				wrapper.html( res.msg );
				wrapper.unblock();
			},
		} );
	} );

	$( '.wsn-tabs' ).on( 'click', 'a.wsn-send-email-all-users', function( e ) {
		e.preventDefault();

		const
			currentEl = $( this ),
			product_id = currentEl.data( 'product_id' ),
			wrapper = $( document ).find( '#wsn-tab-table-' + product_id );

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
				type: 'all',
			},
			success( res ) {
				wrapper.html( res.msg );
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
