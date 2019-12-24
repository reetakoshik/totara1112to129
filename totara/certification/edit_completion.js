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
 * @package totara_certification
 */
M.totara_editcertcompletion = M.totara_editcertcompletion || {

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
            throw new Error('M.totara_editcertcompletion.init()-> jQuery dependency required for this module to function.');
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

        // If there were errors on page load, force the user to select a completion state (show the best guess).
        if ($('input[name="showinitialstateinvalid"]').val() == "1") {
            $('input[name="showinitialstateinvalid"]').val("0");
            var stateelement = $('#id_state');
            var bestguessstate = $('#id_state option:selected').text();
            stateelement.val("0"); // Don't do this when submitting for confirmation.
            stateelement.after("&nbsp;" + M.util.get_string('bestguess', 'totara_program', bestguessstate));
        }

        this.updateFormState();

        $('#id_state').on('change', function(e) {
            M.totara_editcertcompletion.updateFormState();
        });

        $('#id_inprogress').on('change', function(e) {
            M.totara_editcertcompletion.updateFormState();
        });

        $('.fitem_fdate_time_selector select').on('change', function(e) {
            M.totara_editcertcompletion.updateApparentPeriods();
        });

        $('.deletecompletionhistorybutton').on('click', function(e) {
            if (!confirm(M.util.get_string('confirmdeletecompletion', 'totara_program'))) {
                e.preventDefault();
            }
        });

        $('#id_savechanges').on('click', function(e) {
            $('#form_certif_completion :input').prop('disabled', false);

            // Fix stupid timedue should be -1 for not set problem.
            if (!$('#id_timedue_enabled').prop('checked')) {
                $('input[name="timeduenotset"]').val('yes');
            } else {
                $('input[name="timeduenotset"]').val('no');
            }

            // Copy submitted dates that should be automatically set.
            var state = parseInt($('#id_state').val(), 10);
            switch(state) {
                case 2: // CERTIFCOMPLETIONSTATE_CERTIFIED.
                    M.totara_editcertcompletion.copyDate('timeexpires', 'timedue');
                    M.totara_editcertcompletion.copyDate('timecompleted', 'progtimecompleted');
                    break;
                case 3: // CERTIFCOMPLETIONSTATE_WINDOWOPEN.
                    M.totara_editcertcompletion.copyDate('timeexpires', 'timedue');
                    break;
            }
        });

        $('#id_confirmsave').on('click', function(e) {
            $('#form_certif_completion :input').prop('disabled', false);
        });
    },

    copyDate: function(from, to) {
        var fromfieldset = $('#fitem_id_' + from).find('.felement');
        var tofieldset = $('#fitem_id_' + to).find('.felement');
        tofieldset.find('select[name="' + to + '[year]"]').val(fromfieldset.find('select[name="' + from + '[year]"]').val());
        tofieldset.find('select[name="' + to + '[month]"]').val(fromfieldset.find('select[name="' + from + '[month]"]').val());
        tofieldset.find('select[name="' + to + '[day]"]').val(fromfieldset.find('select[name="' + from + '[day]"]').val());
        tofieldset.find('select[name="' + to + '[hour]"]').val(fromfieldset.find('select[name="' + from + '[hour]"]').val());
        tofieldset.find('select[name="' + to + '[minute]"]').val(fromfieldset.find('select[name="' + from + '[minute]"]').val());
    },

    updateFormState: function() {
        var state = parseInt($('#id_state').val(), 10);
        var inprogress = ($('#id_inprogress').val() == "1");

        switch(state) {
            case 0: // CERTIFCOMPLETIONSTATE_INVALID.
                $('#id_inprogress').closest('.fitem').next().hide();

                $('#id_timedue_enabled').closest('.fitem').next().hide();

                $('#id_timecompleted_enabled').closest('.fitem').next().hide();

                $('#id_timewindowopens_enabled').closest('.fitem').next().hide();

                $('#id_timeexpires_enabled').closest('.fitem').next().hide();
                $('#id_baselinetimeexpires_enabled').closest('.fitem').next().hide();

                $('#id_progtimecompleted_enabled').closest('.fitem').next().hide(); // Not applicable.
                $('#id_progtimecompleted_enabled').closest('.fitem').next().next().hide(); // Tied to cert timecompleted.

                break;
            case 1: // CERTIFCOMPLETIONSTATE_ASSIGNED.
                $('#id_inprogress').closest('.fitem').next().hide();

                if (inprogress) {
                    $('#id_status').val(2); // CERTIFSTATUS_INPROGRESS.
                } else {
                    $('#id_status').val(1); // CERTIFSTATUS_ASSIGNED.
                }
                $('#id_renewalstatus').val(0); // CERTIFRENEWALSTATUS_NOTDUE.
                $('#id_certifpath').val(1); // CERTIFPATH_CERT.

                $('#id_timedue_enabled').closest('.fitem').next().hide();

                $('#id_timecompleted_enabled').prop('checked', false);
                $('#id_timecompleted_enabled').closest('.fitem').next().show();

                $('#id_timewindowopens_enabled').prop('checked', false);
                $('#id_timewindowopens_enabled').closest('.fitem').next().show();

                $('#id_timeexpires_enabled').prop('checked', false);
                $('#id_timeexpires_enabled').closest('.fitem').next().show();

                $('#id_baselinetimeexpires_enabled').prop('checked', false);
                $('#id_baselinetimeexpires_enabled').closest('.fitem').next().show();

                $('#id_progstatus').val(0); // STATUS_PROGRAM_INCOMPLETE.

                $('#id_progtimecompleted_enabled').prop('checked', false);
                $('#id_progtimecompleted_enabled').closest('.fitem').next().show(); // Not applicable.
                $('#id_progtimecompleted_enabled').closest('.fitem').next().next().hide(); // Tied to cert timecompleted.

                break;
            case 2: // CERTIFCOMPLETIONSTATE_CERTIFIED.
                $('#id_inprogress').val(0);
                $('#id_inprogress').closest('.fitem').next().show();

                $('#id_status').val(3); // CERTIFSTATUS_COMPLETED.
                $('#id_renewalstatus').val(0); // CERTIFRENEWALSTATUS_NOTDUE.
                $('#id_certifpath').val(2); // CERTIFPATH_RECERT.

                $('#id_timedue_enabled').prop('checked', true);
                $('#id_timedue_enabled').closest('.fitem').next().show();

                $('#id_timecompleted_enabled').prop('checked', true);
                $('#id_timecompleted_enabled').closest('.fitem').next().hide();

                $('#id_timewindowopens_enabled').prop('checked', true);
                $('#id_timewindowopens_enabled').closest('.fitem').next().hide();

                $('#id_timeexpires_enabled').prop('checked', true);
                $('#id_timeexpires_enabled').closest('.fitem').next().hide();

                $('#id_baselinetimeexpires_enabled').prop('checked', true);
                $('#id_baselinetimeexpires_enabled').closest('.fitem').next().hide();

                $('#id_progstatus').val(1); // STATUS_PROGRAM_COMPLETE.

                $('#id_progtimecompleted_enabled').prop('checked', true);
                $('#id_progtimecompleted_enabled').closest('.fitem').next().hide(); // Not applicable.
                $('#id_progtimecompleted_enabled').closest('.fitem').next().next().show(); // Tied to cert timecompleted.

                break;
            case 3: // CERTIFCOMPLETIONSTATE_WINDOWOPEN.
                $('#id_inprogress').closest('.fitem').next().hide();

                if (inprogress) {
                    $('#id_status').val(2); // CERTIFSTATUS_INPROGRESS.
                } else {
                    $('#id_status').val(3); // CERTIFSTATUS_COMPLETED.
                }
                $('#id_renewalstatus').val(1); // CERTIFRENEWALSTATUS_DUE.
                $('#id_certifpath').val(2); // CERTIFPATH_RECERT.

                $('#id_timedue_enabled').prop('checked', true);
                $('#id_timedue_enabled').closest('.fitem').next().show();

                $('#id_timecompleted_enabled').prop('checked', true);
                $('#id_timecompleted_enabled').closest('.fitem').next().hide();

                $('#id_timewindowopens_enabled').prop('checked', true);
                $('#id_timewindowopens_enabled').closest('.fitem').next().hide();

                $('#id_timeexpires_enabled').prop('checked', true);
                $('#id_timeexpires_enabled').closest('.fitem').next().hide();

                $('#id_baselinetimeexpires_enabled').prop('checked', true);
                $('#id_baselinetimeexpires_enabled').closest('.fitem').next().hide();

                $('#id_progstatus').val(0); // STATUS_PROGRAM_INCOMPLETE.

                $('#id_progtimecompleted_enabled').prop('checked', false);
                $('#id_progtimecompleted_enabled').closest('.fitem').next().show(); // Not applicable.
                $('#id_progtimecompleted_enabled').closest('.fitem').next().next().hide(); // Tied to cert timecompleted.
                break;
            case 4: // CERTIFCOMPLETIONSTATE_EXPIRED.
                $('#id_inprogress').closest('.fitem').next().hide();

                if (inprogress) {
                    $('#id_status').val(2); // CERTIFSTATUS_INPROGRESS.
                } else {
                    $('#id_status').val(4); // CERTIFSTATUS_EXPIRED.
                }
                $('#id_renewalstatus').val(2); // CERTIFRENEWALSTATUS_EXPIRED.
                $('#id_certifpath').val(1); // CERTIFPATH_CERT.

                $('#id_timedue_enabled').prop('checked', true);
                $('#id_timedue_enabled').closest('.fitem').next().hide();

                $('#id_timecompleted_enabled').prop('checked', false);
                $('#id_timecompleted_enabled').closest('.fitem').next().show();

                $('#id_timewindowopens_enabled').prop('checked', false);
                $('#id_timewindowopens_enabled').closest('.fitem').next().show();

                $('#id_timeexpires_enabled').prop('checked', false);
                $('#id_timeexpires_enabled').closest('.fitem').next().show();

                $('#id_baselinetimeexpires_enabled').prop('checked', false);
                $('#id_baselinetimeexpires_enabled').closest('.fitem').next().show();

                $('#id_progstatus').val(0); // STATUS_PROGRAM_INCOMPLETE.

                $('#id_progtimecompleted_enabled').prop('checked', false);
                $('#id_progtimecompleted_enabled').closest('.fitem').next().show(); // Not applicable.
                $('#id_progtimecompleted_enabled').closest('.fitem').next().next().hide(); // Tied to cert timecompleted.

                break;
        }
        if (typeof M.form.updateFormState === 'function') {
            M.form.updateFormState("form_certif_completion"); // Required after programatically making changes to the form.
        }

        M.totara_editcertcompletion.updateApparentPeriods();

    },

    updateApparentPeriods: function() {
        var state = parseInt($('#id_state').val(), 10);

        var apparentactiveperiod = 'Error - not calculated';
        var apparentwindowperiod = 'Error - not calculated';
        switch(state) {
            case 0: // CERTIFCOMPLETIONSTATE_INVALID.
            case 2: // CERTIFCOMPLETIONSTATE_CERTIFIED.
            case 3: // CERTIFCOMPLETIONSTATE_WINDOWOPEN.
                var completionday = parseInt($('#id_timecompleted_day').val());
                var completionmonth = parseInt($('#id_timecompleted_month').val());
                var completionyear = parseInt($('#id_timecompleted_year').val());
                var windowday = parseInt($('#id_timewindowopens_day').val());
                var windowmonth = parseInt($('#id_timewindowopens_month').val());
                var windowyear = parseInt($('#id_timewindowopens_year').val());
                var expiryday = parseInt($('#id_timeexpires_day').val());
                var expirymonth = parseInt($('#id_timeexpires_month').val());
                var expiryyear = parseInt($('#id_timeexpires_year').val());

                var method = parseInt($('input[name="recertifydatetype"]').val());

                apparentwindowperiod = M.totara_editcertcompletion.getPeriod(
                    windowday, windowmonth, windowyear, expiryday, expirymonth, expiryyear);

                apparentactiveperiod = M.totara_editcertcompletion.getPeriod(
                    completionday, completionmonth, completionyear, expiryday, expirymonth, expiryyear);
                break;
            case 1: // CERTIFCOMPLETIONSTATE_ASSIGNED.
            case 4: // CERTIFCOMPLETIONSTATE_EXPIRED.
                apparentactiveperiod = M.util.get_string('notapplicable', 'totara_certification');
                apparentwindowperiod = M.util.get_string('notapplicable', 'totara_certification');
                break;
        }

        $('#preapparentactiveperiod').next().find('.fstatic').text(apparentactiveperiod);
        $('#preapparentwindowperiod').next().find('.fstatic').text(apparentwindowperiod);
    },

    getPeriod: function(fromDay, fromMonth, fromYear, toDay, toMonth, toYear) {
        if (toDay == fromDay && toMonth == fromMonth) {
            // Days and months match, so assume the period is a number of years.
            return M.util.get_string('periodyears', 'totara_certification', toYear - fromYear);

        } else if (toDay == fromDay) {
            // Days match but months don't, so assume the period is a number of months.
            var months = (toYear - fromYear) * 12 + toMonth - fromMonth;
            return M.util.get_string('periodmonths', 'totara_certification', months);

        } else {
            var fromDate = new Date(fromYear, fromMonth, fromDay);
            var toDate = new Date(toYear, toMonth, toDay);
            var days = Math.round((toDate - fromDate) / (1000 * 3600 * 24));
            if (fromDate.getDay() == toDate.getDay()) {
                // They are the same day of the week, so assume the period is a number of weeks.
                return M.util.get_string('periodweeks', 'totara_certification', days / 7);

            } else {
                // Otherwise we just have to say how many days it it.
                return M.util.get_string('perioddays', 'totara_certification', days);
            }
        }
    }
}
