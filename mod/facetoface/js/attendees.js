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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @package totara
 * @subpackage facetoface
 */

M.totara_f2f_attendees = M.totara_f2f_attendees || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},
    // public handler reference for the dialog
    totaraDialog_handler_preRequisite: null,

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args){
        var module = this;

        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_f2f_attendees.init()-> jQuery dependency required for this module to function.');
        }

        $('.selectall').click(function(){
            $('[name="userid"]').prop("checked", true);
        });

        $('.selectnone').click(function(){
            $('[name="userid"]').prop("checked", false);
        });

        // if defined, parse args into this module's config object
        if (args) {
            var jargs = Y.JSON.parse(args);
            for (var a in jargs) {
                if (Y.Object.owns(jargs, a)) {
                    this.config[a] = jargs[a];
                }
            }
        }

        var notsetoption = M.totara_f2f_attendees.config.notsetop.toString();

        // Id is removed when link is sent as part of notification.
        // This workaround sets id back to link using its class.
        $('a.f2f-import-results:first').attr('id', 'f2f-import-results');

        /**
         * Upload background page
         */

        totaraDialog_handler_form.prototype._updatePage = function(response) {
            // Get all root elements in response
            var newtable = $(response);
            if (M.totara_f2f_attendees.config.approvalreqd == "1") {
                if ($('<div></div>').append(newtable).find('div.addedattendees').length > 0) {
                    //find the approval tab
                    var tab = $('span:contains("' + M.util.get_string('approvalreqd','facetoface') + '")');
                    if (tab.length > 0) {
                        //remove the nolink class if present and set up the link attributes
                        tab.parent('a').removeClass('nolink');
                        tab.parent('a').attr("href", M.cfg.wwwroot + '/mod/facetoface/attendees.php?s=' + M.totara_f2f_attendees.config.sessionid + '&action=approvalrequired');
                        tab.parent('a').attr("title", M.util.get_string('approvalreqd','facetoface'));
                    }
                }
            }

            // Replace any items on the main page with their content (if IDs match)
            $('div.f2f-attendees-table').empty();
            $('div.f2f-attendees-table').append(newtable);

            M.totara_f2f_attendees.attachCustomClickEvents();

            // Close dialog
            this._dialog.hide();
        };

        (function() {
            var handler = new totaraDialog_handler_form();
            var name = 'bulkaddresults';

            var buttonsObj = {};
            buttonsObj[M.util.get_string('cancel','moodle')] = function() { handler._cancel(); };

            totaraDialogs[name] = new totaraDialog(
                name,
                'f2f-import-results',
                {
                    buttons: buttonsObj,
                    title: '<h2>' + M.util.get_string('bulkaddattendeesresults', 'facetoface') + '</h2>'
                },
                M.cfg.wwwroot + '/mod/facetoface/attendees/bulkadd_results.php?s=' + M.totara_f2f_attendees.config.sessionid,
                handler
                );
        })();

        function mark_set_unset(val, operator) {
            // Reset all checkboxes.
            $('.selectedcheckboxes').prop('checked', false);
            $(":checkbox").filter(function() {
                var selectid = $(this).data('selectid');
                if (operator == 'EQ') {
                    return $('#'+selectid).val() == val;
                } else {
                    return $('#'+selectid).val() != val;
                }
            }).prop("checked", "true");
        }

        // Set error (boolean).
        function set_error(error) {
            if (error) {
                $('select#menubulk_select').addClass('error');
                $('#selectoptionbefore').removeClass('hide');
            } else {
                $('select#menubulk_select').removeClass('error');
                $('#selectoptionbefore').addClass('hide');
            }
        }

        // Print notice of operation (boolean: true =>success false=>failure).
        function print_notice(success) {
            var notice = M.util.get_string('updateattendeessuccessful','facetoface');
            var classname = 'notifysuccess';
            if (!success) {
                notice = M.util.get_string('updateattendeesunsuccessful','facetoface');
                classname = 'notifyproblem';
            }
            $('div#noticeupdate').removeClass('hide').addClass(classname).text(notice);
        }

        function options_validated(selectbulk) {
            var proceed = false;

            if (!selectbulk) {
                set_error(false);
            } else if ($(':checkbox:checked').length == 0) {
                set_error(true);
            } else {
                proceed = true;
            }

            return proceed;
        }

        // Handle select list.
        $('select#menubulk_select').change(function() {
            var selected = $(this).val();
            set_error(false); // Delete error if any.

            switch(selected) {
                case M.totara_f2f_attendees.config.selectall.toString():
                    $('.selectedcheckboxes').prop('checked', true);
                    break;
                case M.totara_f2f_attendees.config.selectnone.toString():
                    $('.selectedcheckboxes').prop('checked', false);
                    break;
                case M.totara_f2f_attendees.config.selectset.toString():
                    mark_set_unset(notsetoption, 'NE');
                    break;
                case M.totara_f2f_attendees.config.selectnotset.toString():
                    mark_set_unset(notsetoption, 'EQ');
                    break;
                default:
                    $('.selectedcheckboxes').prop('checked', false);
                    break;
            }

            // Reset drop-down bulk list.
            $('select#menubulkattendanceop').prop('selectedIndex', 0);
        });

        // Handle drop-down bulk attendance actions.
        $('select#menubulkattendanceop').change(function() {
            var selected = $(this).val();
            var idchecked = 0;
            if (selected != notsetoption && options_validated(selected)) {
                $(':checkbox:checked').each(function(index) {
                    idchecked = (this.name).substring(19); // Checkbox id.
                    $(this).val(selected); // Mark value of this checkbox with the selected option.
                    $('select#menusubmissionid_'+idchecked+' option[value='+selected+']').prop('selected', true);
                });
            }
        });


        /**
        *  Attaches mouse events to the loaded content.
        */
        this.attachCustomClickEvents = function() {
            // Add attendee note button.
            $('a.attendee-add-note').show();
            $('a.attendee-add-note').on('click', function() {
                $.get($(this).attr('href'), function(data) {
                    modalForm(data);
                });
                return false;
            });
            // Add handler to edit job assignment button.
            $('a.attendee-edit-job-assignment').on('click', function(){
                $.get($(this).attr('href'), function(href){
                    editJobAssignmentModalForm(href);
                });
                return false;
            });
            // Add handler to view cancellation note.
            $('a.attendee-cancellation-note').on('click', function(){
                $.get($(this).attr('href'), function(data){
                    modalWindow(data);
                });
                return false;
            });
        }
        this.attachCustomClickEvents();

        /**
         * Modal popup to show generic info.
         */
        function modalWindow(data) {
            var bodyContent = '';
            if (typeof data.error === 'undefined') {
                bodyContent = data;
            }
            var dialog = new M.core.dialogue ({
                headerContent: null,
                bodyContent  : bodyContent,
                width        : 500,
                zIndex       : 5,
                centered     : true,
                modal        : true,
                render       : true
            });
            dialog.show();
        }

        /**
        * Modal popup for generic single stage form. Requires the existence of standard mform with buttons #id_submitbutton and #id_cancel
        * @param content The desired contents of the panel
        */
        function modalForm(data) {
            var bodyContent = '';
            if (typeof data.error === 'undefined') {
                bodyContent = data;
            }
            var dialog = new M.core.dialogue ({
                headerContent: null,
                bodyContent  : bodyContent,
                width        : 500,
                zIndex       : 5,
                centered     : true,
                modal        : true,
                render       : true
            });
            var content = $('#' + dialog.get('id'));
            if (typeof data.error !== 'undefined') {
                // Get widget div header.
                var div = content.find('div').eq(1);
                div.after('<div class="notifyproblem" style="margin:2px">' + data.error + '</div>');
            } else {
                content.find('fieldset.hidden').removeClass('hidden');
                content.find('#id_usernote').focus();
                content.find('#id_submitbutton').on('click', function() {
                    var form = content.find('form');
                    var apprObj = form.serialize();
                    apprObj += ('&submitbutton=' + $(this).attr('value'));
                    $.post(form.attr('action'), apprObj).done(function(data) {
                        if (data.result == 'success') {
                            var span = "#usernote"+data.id;
                            $(span).html(data.usernote);
                            dialog.destroy(true);
                        } else {
                            $("#attendee_note_err").text(data.error);
                        }
                    });
                    return false;
                });
                content.find('#id_cancel').on('click', function() {
                    dialog.destroy(true);
                    return false;
                });
            }
            dialog.show();
        }

        /**
         * Modal popup for edit jbo assignment single stage form. Requires the existence of standard mform with buttons #id_submitbutton and #id_cancel
         * @param href The desired contents of the panel
         */
        function editJobAssignmentModalForm(href) {
            this.Y.use('panel', function(Y) {
                var panel = new Y.Panel({
                    headerContent: null,
                    bodyContent  : href,
                    width        : 600,
                    zIndex       : 5,
                    centered     : true,
                    modal        : true,
                    render       : true
                });
                var $content = $('#' + panel.get('id'));
                $content.find('input[type="text"]').eq(0).focus();
                $content.find('#id_submitbutton').on('click', function() {
                    var $theFrm = $content.find('form.mform');
                    var apprObj = $theFrm.serialize();
                    apprObj += ('&submitbutton=' + $(this).attr('value'));
                    $.post($theFrm.attr('action'), apprObj).done(function(data){
                        if (data.result == 'success') {
                            var span = "#jobassign"+data.id;
                            $(span).html(data.jobassignmentdisplayname);
                            panel.destroy(true);
                        } else {
                            $("#attendee_job_assignment_err").text(data.error);
                        }
                    });
                    return false;
                });
                $content.find('#id_cancel').on('click', function() {
                    panel.destroy(true);
                    return false;
                });
                panel.show();
            });
        }

        // Handle actions drop down.
        $(document).on('change', 'select#menuf2f-actions', function() {
            var select = $(this);

            var data = {
                submitbutton: "1",
                ajax: "1",
                sesskey: M.totara_f2f_attendees.config.sesskey
            };

            // Get current value
            var current = select.val();
            // If its the empty or if its the same as the last action.
            if (current === '') {
                // No need to do anything here we're returning to the default value.
                return;
            }
            // Reset to default.
            // This triggers the change event for a second time but we catch it with the above check.
            select.val('');

            switch (current) {
                case "add":
                    window.location.href = M.cfg.wwwroot + '/mod/facetoface/attendees/add.php?s=' + M.totara_f2f_attendees.config.sessionid;
                    break;
                case "bulkaddfile":
                    window.location.href = M.cfg.wwwroot + '/mod/facetoface/attendees/addfile.php?s=' + M.totara_f2f_attendees.config.sessionid;
                    break;
                case "bulkaddinput":
                    window.location.href = M.cfg.wwwroot + '/mod/facetoface/attendees/addlist.php?s=' + M.totara_f2f_attendees.config.sessionid;
                    break;
                case "remove":
                    window.location.href = M.cfg.wwwroot + '/mod/facetoface/attendees/remove.php?s=' + M.totara_f2f_attendees.config.sessionid;
                    break;
            }

            // Process confirm/cancel attendees.
            if (current == "confirmattendees" || current == "cancelattendees" || current == "playlottery") {

                var users = Y.all('table.mod-facetoface-attendees.waitlist tr');
                var updateusers = [];
                var i = 0;
                users.each(function(node) {
                    if (checkbox = node.one('input[type=checkbox]')) {
                        if (checkbox._node.checked) {
                            userid = checkbox.get('value');
                            updateusers[i] = userid;
                            i++;
                        }
                    }
                });

                if (updateusers.length == 0) {
                    Y.use('panel', function (Y) {
                        var config = {
                            headerContent: M.util.get_string('updatewaitlist', 'facetoface'),
                            bodyContent: M.util.get_string('waitlistselectoneormoreusers','facetoface'),
                            draggable: true,
                            modal: true
                        };
                        dialog = new M.core.dialogue(config);

                        dialog.addButton({
                            label: M.util.get_string('close', 'facetoface'),
                            section: Y.WidgetStdMod.FOOTER,
                            action: function() {
                                dialog.destroy(true);
                                return false;
                            }
                        });

                        dialog.show();
                    });
                } else {
                    updateusers = updateusers.join();
                    update_waitlist(current, updateusers, false);
                }
            }

            // If exporting, redirect to that url
            if (current.substr(0, 6) == "export") {
                var url = M.cfg.wwwroot + '/mod/facetoface/attendees.php?s=' + M.totara_f2f_attendees.config.sessionid + '&action=' + M.totara_f2f_attendees.config.action + '&download=';
                url += current.substr(6);
                url += '&onlycontent=1';
                window.location.href = url;
            }

            // If taking attendance
            if (current.substr(0, 5) == "mark_") {
                // Set hidden form element
                $('form.f2f-takeattendance-form input.bulk_update').val(current.substr(5));

                // Submit form
                $('form.f2f-takeattendance-form').submit();
            }
        });

        function update_waitlist(action, updateusers, checked) {
            var nextaction = null;
            if (checked == false && action == 'confirmattendees') {
                nextaction = action;
                action = 'checkcapacity';
            }

            function do_post() {
                Y.io(M.cfg.wwwroot + '/mod/facetoface/updatewaitlist.php', {
                    data: {
                        courseid: M.totara_f2f_attendees.config.courseid,
                        sessionid: M.totara_f2f_attendees.config.sessionid,
                        context: this,
                        action: action,
                        datasubmission: updateusers,
                        sesskey: M.totara_f2f_attendees.config.sesskey
                    },
                    on: {
                        success: function (x, o) {
                            var parsedResponse;
                            // protected against malformed JSON response
                            try {
                                parsedResponse = Y.JSON.parse(o.responseText);
                            }
                            catch (e) {
                                alert("JSON Parse failed!");
                            }

                            if (parsedResponse.result == 'overcapacity') {
                                Y.use('moodle-core-notification-confirm', function () {
                                    var confirm = new M.core.confirm({
                                        title: M.util.get_string('confirm', 'moodle'),
                                        question: M.util.get_string('areyousureconfirmwaitlist', 'facetoface'),
                                        yesLabel: M.util.get_string('yes', 'moodle'),
                                        noLabel: M.util.get_string('cancel', 'moodle')
                                    });
                                    confirm.on('complete-yes', function () {
                                        confirm.hide();
                                        confirm.destroy();
                                        update_waitlist(nextaction, updateusers, true);
                                    }, self);
                                    confirm.show();

                                });
                            }
                            if (parsedResponse.result == 'undercapacity') {
                                update_waitlist(nextaction, updateusers, true);
                            }
                            if (parsedResponse.result == 'success') {
                                var attendees = parsedResponse.attendees;

                                $('input[name=userid]').each(function (index, elem) {
                                    var userid = parseInt(elem.value);
                                    if (attendees.indexOf(userid) > -1) {
                                        $('input[name=userid][value=' + userid + ']').parents('tr').remove();
                                    }
                                });

                                print_notice(true);
                            }
                        },
                        failure: function () {
                            print_notice(false);
                        }
                    }
                });
            }

            if (action == 'playlottery') {
                Y.use('panel', function (Y) {
                    var config = {
                        headerContent: M.util.get_string('confirmlotteryheader', 'facetoface'),
                        bodyContent: M.util.get_string('confirmlotterybody','facetoface'),
                        draggable: true,
                        modal: true,
                    };
                    dialogue = new M.core.dialogue(config);
                    dialogue.addButton({
                        label: M.util.get_string('ok', 'moodle'),
                        section: Y.WidgetStdMod.FOOTER,
                        action: function() {
                            do_post.call(this);
                            dialogue.destroy(true);
                            return false;
                        }
                    });
                    dialogue.addButton({
                        label: M.util.get_string('cancel', 'moodle'),
                        section: Y.WidgetStdMod.FOOTER,
                        action: function() {
                            dialogue.destroy(true);
                            return false;
                        }
                    });

                    dialogue.show();
                });
            } else {
                do_post.call(this);
            }
        }

        $(document).on('focus', '#removeselect', function() {
            $('form#assignform input[name=add]').attr('disabled', 'disabled');
            $('form#assignform input[name=remove]').removeAttr('disabled');
            $('#addselect').val(-1);
        });

        $(document).on('focus', '#addselect', function() {
            $('form#assignform input[name=remove]').attr('disabled', 'disabled');
            $('form#assignform input[name=add]').removeAttr('disabled');
            $('#removeselect').val(-1);
        })
    }
}
