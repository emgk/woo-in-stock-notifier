/**
 * Wocommerce In-Stock Notifier JavaScript
 * @author Govind Kumar <gkprmr@gmail.com>
 * @version 1.0.0
 */

jQuery(document).ready(function ($) {
    "use strict";

    var wsn_waitlist_form = $(document).find('form.variations_form'),
        wsn_email = function () {

            var var_email_input = $(document).find('#wsn_waitlist_email'),
                var_email_link = var_email_input.parents('.wsn_waitlist_form').find('a.btn');

            var_email_input.on('input', function (e) {
                var link_href = var_email_link.attr('href'),
                    email_val = var_email_input.val(),
                    variation_id = $('.variation_id').val(),
                    email_name = var_email_input.attr('name');
                var_email_link.prop('href', link_href + '&' + email_name + '=' + email_val + '&var_id=' + variation_id);
            });
        };
    wsn_waitlist_form.on('woocommerce_variation_has_changed', wsn_email);
});
