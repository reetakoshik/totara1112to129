/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

define(['jquery', 'core/yui', 'core/str', 'core/config'], function($, Y, mdlstrings, mdlcfg) {
    var addConfirm = {
        init: function(args) {
            /**
            *  Attaches mouse events to the loaded content.
            */
            this.attachCustomClickEvents = function() {
                // Add handler to edit job assignment button.
                $('a.attendee-edit-job-assignment').on('click', function(){
                    $.get($(this).attr('href'), function(href){
                        addConfirm.editJobAssignmentModalForm(href);
                    });
                    return false;
                });
            };
            this.attachCustomClickEvents();

        },

        /**
         * Modal popup for edit jbo assignment single stage form. Requires the existence of standard mform with buttons #id_submitbutton and #id_cancel
         * This is similar to editJobAssignmentModalForm function in attendees.js
         * @param href The desired contents of the panel
         */
        editJobAssignmentModalForm: function(href) {
            Y.use('panel', function(Y) {
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
    };

    return addConfirm;
});


