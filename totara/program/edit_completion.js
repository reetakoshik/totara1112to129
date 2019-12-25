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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_program
 */
M.totara_editprogcompletion = M.totara_editprogcompletion || {

    Y: null,
    dialog: null,

    /**
     * Module initialisation method called by php js_init_call().
     *
     * @param object    YUI instance
     * @param object    configuration data
     */
    init: function(Y, config){
        // Save a reference to the Y instance (all of its dependencies included).
        this.Y = Y;

        // Store config info.
        this.config = config;

        // Check jQuery dependency is available.
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_editprogcompletion.init()-> jQuery dependency required for this module to function.');
        }

        // Adding custom dependency checkers without changing form.js code.
        M.form.dependencyManager.prototype._dependencyEqhide = function(elements, value) {
            var result = M.form.dependencyManager.prototype._dependencyEq(elements, value);
            if (result.lock) {
                result.hide = true;
            }
            return result;
        };

        $('#id_timecompleted_enabled').closest('span').hide();
        $('#id_timewindowopens_enabled').closest('span').hide();
        $('#id_timeexpires_enabled').closest('span').hide();
        $('#id_baselinetimeexpires_enabled').closest('span').hide();
        $('#id_progtimecompleted_enabled').closest('span').hide();

        // If there were errors on page load, force the user to select a status (show the existing value).
        if ($('input[name="showinitialstateinvalid"]').val() == "1") {
            $('input[name="showinitialstateinvalid"]').val("0");
            var statuselement = $('#id_status');
            var actualstatus = $('#id_status option:selected').text();
            statuselement.val("-1"); // Don't do this when submitting for confirmation.
            statuselement.after("&nbsp;" + M.util.get_string('bestguess', 'totara_program', actualstatus));
        }

        this.updateFormState();

        $('#id_status').on('change', function(e) {
            M.totara_editprogcompletion.updateFormState();
        });

        $('.deletecompletionhistorybutton').on('click', function(e) {
            if (!confirm(M.util.get_string('confirmdeletecompletion', 'totara_program'))) {
                e.preventDefault();
            }
        });

        $('#id_savechanges').on('click', function(e) {
            // Fix stupid timedue should be -1 for not set problem.
            if (!$('#id_timedue_enabled').prop('checked')) {
                $('input[name="timeduenotset"]').val('yes');
            } else {
                $('input[name="timeduenotset"]').val('no');
            }
        });
    },

    updateFormState: function() {
        var status = parseInt($('#id_status').val(), 10);

        switch(status) {
            case -1: // Invalid.
                $('#id_timecompleted_enabled').prop('checked', false);
                $('#id_timecompleted_enabled').closest('.fitem').next().hide(); // Not applicable.
                break;
            case 0: // STATUS_PROGRAM_INCOMPLETE.
                $('#id_timecompleted_enabled').prop('checked', false);
                $('#id_timecompleted_enabled').closest('.fitem').next().show(); // Not applicable.
                break;
            case 1: // STATUS_PROGRAM_COMPLETE.
                $('#id_timecompleted_enabled').prop('checked', true);
                $('#id_timecompleted_enabled').closest('.fitem').next().hide(); // Not applicable.
                break;
            default: // Invalid.
                // ?
                break;
        }
        if (typeof M.form.updateFormState === 'function') {
            M.form.updateFormState("form_prog_completion"); // Required after programatically making changes to the form.
        }
    }
}
