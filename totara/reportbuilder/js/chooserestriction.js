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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */

M.totara_reportbuilder_chooserestriction = M.totara_reportbuilder_chooserestriction || {
    Y: null,
    reportid: null,
    pageurl: null,

    /**
     * Module initialisation method called by php js_init_call().
     *
     * @param Y object        YUI instance
     * @param reportid        Integer
     * @param pageurl         String
     */
    init: function (Y, reportid, pageurl) {
        // Save a reference to the Y instance (all of its dependencies included).
        this.Y = Y;

        // Add parameters.
        this.reportid = reportid;
        this.pageurl = pageurl;

        // Check jQuery dependency is available.
        if (typeof $ === 'undefined') {
            throw new Error('M.rb_init_choose_restriction_dialogs.init()-> jQuery dependency required for this module to function.');
        }

        // Do setup.
        this.rb_init_choose_restriction_dialogs();
    },

    rb_init_choose_restriction_dialogs : function () {
        var dialog = this.addDialog();
    },

    addDialog: function () {
        var selected = [];
        var baseurl = M.cfg.wwwroot + '/totara/reportbuilder/';
        var title = M.util.get_string('chooserestrictiontitle', 'totara_reportbuilder');
        var name = 'chooserestriction';
        var selected = $('#show-'+name+'-dialog').data('selected');
        var find_url = baseurl + 'ajax/find_restriction.php?sesskey=' + M.cfg.sesskey;
        var reload_url = this.pageurl;
        if (reload_url.indexOf('?') === -1) {
            reload_url = reload_url + '?sesskey=' + M.cfg.sesskey;
        } else {
            reload_url = reload_url + '&sesskey=' + M.cfg.sesskey;
        }

        var handler = new totaraDialog_handler();

        var buttonObj = {};
        buttonObj[M.util.get_string('save', 'totara_core')] = function() {
            // Get all selected values.
            var values = [];
            $(handler._container).find("form.chooserestriction input[name^='restriction']").each(function() {
                if ($(this).prop("checked")) {
                    values.push($(this).val());
                }
            });

            $('#' + name + ' .error-required').hide();
            if (values.length == 0) {
                $('#' + name + ' .error-required').show();
                return;
            }

            // Close dialog.
            handler._cancel();

            var valueparam = $.param({globalrestrictionids: values.join(',')});
            window.location.href = reload_url + '&' + valueparam;
        };
        buttonObj[M.util.get_string('cancel', 'moodle')] = function() { handler._cancel();};

        totaraDialogs[name] = new totaraDialog(
            name,
            'show-'+name+'-dialog',
            {
                buttons: buttonObj,
                title: '<h2>'+title+'</h2>',
                width: 500
            },
            find_url + '&selected=' + selected + '&reportid=' + this.reportid,
            handler
        );
    }
};
