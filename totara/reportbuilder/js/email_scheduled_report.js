/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_reportbuilder
 */

/**
 * Javascript file containing JQuery bindings for email setting in scheduled reports.
 */

M.totara_email_scheduled_report = M.totara_email_scheduled_report || {

    Y: null,
    // Optional php params and defaults defined here, args passed to init method
    // below will override these values.
    config: {},

    /**
     * Module initialisation method called by php js_init_call().
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function (Y, args) {
        // Save a reference to the Y instance (all of its dependencies included).
        this.Y = Y;

        // If defined, parse args into this module's config object.
        if (args) {
            var jargs = Y.JSON.parse(args);
            for (var a in jargs) {
                if (Y.Object.owns(jargs, a)) {
                    this.config[a] = jargs[a];
                }
            }
        }

        // Check jQuery dependency is available.
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_reportbuilder_filterdialogs.init()-> jQuery dependency required for this module to function.');
        }

        // Do setup.
        this.rb_init_load_dialogs();
    },

    rb_init_load_dialogs: function () {
        var module = this;
        this.rb_load_cohort_dialog();
        this.rb_load_user_dialog();

        // Activate the 'delete' option next to any selected items.
        $(document).on('click', '.multiselect-selected-item a', function (event) {
            event.preventDefault();

            var container = $(this).parents('div.multiselect-selected-item');
            var filtername = container.data('filtername');
            var id = container.data('id');
            var hiddenfield = $('input[name=' + filtername + ']');

            // Take this element's ID out of the hidden form field.
            var ids = hiddenfield.val();
            var id_array = ids.split(',');
            var new_id_array = $.grep(id_array, function (n, i) {
                return n != id
            });
            var new_ids = new_id_array.join(',');
            hiddenfield.val(new_ids);

            // Remove this element from the DOM.
            container.remove();
        });

        $('#addexternalemail').click(function (event) {
            var external_emails = $('input[name=externalemails]').val();
            var email = $('.reportbuilder_scheduled_addexternal'),
                emailvalue = email.val().toLowerCase();

            if ($.trim(emailvalue).length == 0 || !M.util.validate_email(emailvalue)) {
                alert(M.util.get_string('err_email', 'form'));
            } else if (external_emails.indexOf(emailvalue) != -1) {
                alert(M.util.get_string('emailexternaluserisonthelist', 'totara_reportbuilder'));
            } else{
                module.rb_add_external_email(emailvalue);
                email.val('');
            }
        });
    },

    rb_load_cohort_dialog: function () {
        $('#show-audiences-dialog').on('click', rbShowAudienceDialog);
    },

    rb_load_user_dialog: function () {
        $('#show-systemusers-dialog').on('click', rbShowUserDialog);
    },

    rb_add_external_email: function(email) {
        var url = M.cfg.wwwroot + '/totara/reportbuilder/schedule_display_items_email.php';

        // Get external email input.
        var external_email = $('input[name=externalemails]');
        var external_value = external_email.val();
        var id_array = (external_value.length == 0) ? [] : external_value.split(',');

        // Add the new email to the external emails.
        id_array.push(email);

        // Update external emails.
        var new_ids = id_array.join(',');
        external_email.val(new_ids);

        $.ajax({
            url: url,
            type: "POST",
            data: ({
                filtername: 'externalemails',
                ids: '',
                emails: new_ids,
                sesskey: M.cfg.sesskey
            }),
            success: function (o) {
                var container = $('div.list-externalemails');
                container.replaceWith(o);
            },
            error: function () {
                alert(M.util.get_string('error:badresponsefromajax', 'totara_cohort'));
                // Reload the broken page.
                location.reload();
            }
        });
    }
}

rbShowUserDialog = function() {
    var selected = M.totara_email_scheduled_report.config.existingsyusers;
    var url = M.cfg.wwwroot + '/totara/reportbuilder/';
    var params = {
        'sesskey': M.cfg.sesskey
    };
    if (M.totara_email_scheduled_report.config.excludeself) {
        params.excludeself = '1';
    }

    rbDialog(
        'systemusers',
        M.util.get_string('addsystemusers', 'totara_reportbuilder'),
        url + 'ajax/find_user.php?' + build_querystring(params),
        url + 'schedule_display_items_email.php?filtername=systemusers' + '&sesskey=' + M.cfg.sesskey + '&ids=',
        selected
    );
},

rbShowAudienceDialog = function() {
    var selected = M.totara_email_scheduled_report.config.existingaud;
    var url = M.cfg.wwwroot + '/totara/reportbuilder/';

    rbDialog(
        'audiences',
        M.util.get_string('addcohorts', 'totara_reportbuilder'),
        url + 'ajax/find_cohort.php?sesskey=' + M.cfg.sesskey,
        url + 'schedule_display_items_email.php?filtername=audiences' + '&sesskey=' + M.cfg.sesskey + '&ids=',
        selected
    );
},

rbDialog = function(name, title, find_url, save_url, selected) {
    var handler = new totaraDialog_handler_treeview_multiselect_rb_filter();
    var buttonObj = {};
    buttonObj[M.util.get_string('save', 'totara_core')] = function() { handler._save(save_url) };
    buttonObj[M.util.get_string('cancel', 'moodle')] = function() { handler._cancel() };

    totaraDialogs[name] = new totaraDialog(
        name,
        'show-'+name+'-dialog',
        {
            buttons: buttonObj,
            title: '<h2>'+title+'</h2>'
        },
        find_url + '&selected=' + selected,
        handler
    );
}

/**
 * This function has been deprecated and will be removed in the future.
 * Please call M.util.validate_email() instead.
 * @deprecated
 * @param {string} email
 * @returns {boolean}
 */
validEmail = function(email) {
    return M.util.validate_email(email);
}
