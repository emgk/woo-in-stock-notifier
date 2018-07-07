/**
 * Wocommerce In-Stock Notifier JavaScript
 * @author Govind Kumar <gkprmr@gmail.com>
 * @version 1.0.0
 */

jQuery(document).ready(function ($) {

    var total = 0;

    $('a#show_archived').on('click', function (e) {

        e.preventDefault();

        var current = $(this),
            product_id = current.data('product_id');

        $.ajax({
            url: _wsn_waitlist.ajax_url,
            dataType: 'json',
            data: {action: 'archive_function', product: product_id, type: '_show'},
            success: function (data) {

                if (data != "") {
                    current.parents().find('.waitlist_data#' + product_id).hide();
                    var archive_row = current.parents().find('.archived_data_panel#' + product_id).fadeIn(500).find('._archive_userlist');
                    archive_row.append('<tr id="archive_table_head"><td class="wsn-user-email-col">Email</td><td id="restore_archived">Restore</td> <td id="r_archived">Remove</td></tr>')
                    $.each(data, function (key, data) {
                        archive_row.append('<tr id="row_' + product_id + '" ><td>' + data + '</td><td id="restore_archived"><a href="javascript:void( 0 );" class="restoreEmail" data-uid="' + data + '" data-pid="' + product_id + '"><span class="dashicons dashicons-image-rotate"></span></a> </td><td id="r_archived"><a href="javascript:void( 0 );" class="removeArchivedUser" data-uid="' + data + '" data-pid="' + product_id + '"><span class="dashicons dashicons-no"></span></a></td></tr>');
                    });
                    archive_row.append('</table>');
                }
            }
        });
    });
    $('.archived_data_panel').on('click', 'a.removeArchivedUser', function (e) {

        e.preventDefault();

        var current_obj = $(this),
            user_email = current_obj.data('uid'),
            product_id = current_obj.data('pid');

        var data = {
            action: 'archive_function',
            product: product_id,
            user_id: user_email,
            type: '_remove'
        };
        $.post(ajaxurl, data, function () {
            current_obj.parent().closest('tr').fadeOut(400);
        });
    });

    $('.archived_data_panel').on('click', 'a.restoreEmail', function (e) {

        e.preventDefault();

        var current_obj = $(this),
            user_email = current_obj.data('uid'),
            product_id = current_obj.data('pid');

        var data = {
            action: 'archive_function',
            product: product_id,
            user_id: user_email,
            type: '_restore',
        };
        $.post(ajaxurl, data, function (data) {
            current_obj.parent().closest('tr').fadeOut(1000);
        });
    });

    $('a.close_archived').click(function (e) {

        e.preventDefault();

        var current_obj = $(this),
            product_id = current_obj.attr('id');

        $('#form' + product_id).hide();

        current_obj.parents().find('.waitlist_data#' + product_id).show();
        current_obj.parents().find('.archived_data_panel#' + product_id).find('._archive_userlist').html('');
        current_obj.parents().find('.archived_data_panel#' + product_id).hide();
    });

    $('.wsn-usertable').on('click', 'a.removeUser', function (e) {

        e.preventDefault();

        var current_obj = $(this);

        var product_id = current_obj.data('product_id'),
            email = current_obj.data('email'),
            uid = current_obj.data('uid'),
            total = current_obj.data('total'),
            nonce = current_obj.data('wp_nonce'),
            action = current_obj.data('action');

        var data = {
            'action': 'removeUser',
            'security': nonce,
            'p_id': product_id,
            'wsn_email': email,
            'inc': total,
            'wp_action': action
        };

        $.post(ajaxurl, data, function () {
            $("#row-" + uid + "-" + product_id).fadeOut(1000);
        });
    });

    $("a#wsn_add_new_user").on('click', function (e) {

        e.preventDefault();

        var formid = $(this).data('product_id');

        $('#form' + formid).toggle();
        $(this).parent().find('.usrEmail#' + formid).focus();
    });

    $('button#wsn_add_btn').on('click', function (e) {

        e.preventDefault();

        var current_obj = $(this);

        var form_id = current_obj.data('product_id'),
            email = current_obj.parent().find('.usrEmail#' + form_id).val(),
            total = current_obj.data('total'),
            uid = total + 1,
            nonce = current_obj.data('nonce');

        current_obj.parent().find('.usrEmail#' + form_id).val('');
        current_obj.parent().find('.wsn-empty').hide();

        var data = {
            'action': 'addNewUser',
            'security': nonce,
            'p_id': form_id,
            'inc': uid,
            'email': email
        };

        if (!email) {

            alert('Please enter email address.');
            return false;
        }

        var email_pattern = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

        if (!email_pattern.test(email)) {

            alert('Please enter valid email address');
            current_obj.parent().find('.usrEmail#' + form_id).focus();

            return false;
        }


        $.post(ajaxurl, data, function (data) {

            var outputData = JSON.parse(data);

            if (outputData.status == 'success') {
                total += 1;
                current_obj.data('total', total);
                current_obj.parents().find('.no_user#' + form_id).hide();
                $('table#waitlists' + form_id).append('<tr id="row-' + outputData.currentId + '-' + form_id + '"><td >' + outputData.email + '</td><td class="wsn-email-col">' + outputData.emailLink + '</td><td class="wsn-action-col">' + outputData.removeLink + '</td></tr>');
                $('table#waitlists' + form_id + " tr:last").animate({backgroundColor: "rgb(247, 255, 176)"}, 'slow').animate({backgroundColor: "#fff"}, 'slow');
                current_obj.parent().find('.usrEmail#' + form_id).focus();
            } else if (outputData.status == 'exists') {

                alert(email + ' is already exist! ');
            }

        });
    });
});
