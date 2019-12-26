/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package mod_facetoface
 */

define(['jquery', 'core/modal_factory', 'core/modal_events', 'core/str'],
    function($, ModalFactory, ModalEvents, Str) {
    var addConfirm = {
        init: function(userconflicts) {
            /**
            *  Shows user scheduling conflicts.
            */
            var $form = $('#mform_seminar_event');
            if (userconflicts !== undefined && userconflicts.length) {
                addConfirm.overrideUserConflictsModalForm($form, userconflicts);
            }
        },

        /**
         * Modal to confirm the override of user scheduling conflicts.
         * @param mform The session form
         * @param content Content for the confirmation
         */
        overrideUserConflictsModalForm: function(mform, content) {

            var stringkeys = [
                { key: 'savewithuserconflicts_header', component: 'facetoface'},
                { key: 'savewithuserconflicts', component: 'facetoface'},
                { key: 'cancel', component: 'moodle'},
            ];

            Str.get_strings(stringkeys).then(function(strings) {
                var title = strings[0];
                var saveButtonText = strings[1];
                var cancelButtonText = strings[2];
                return ModalFactory.create({
                    title: title,
                    body: content,
                    type: ModalFactory.types.CONFIRM
                }, undefined, {
                    yesstr: saveButtonText,
                    nostr: cancelButtonText
                }).then(function(modal) {
                    modal.show();
                    modal.getRoot().on(ModalEvents.yes, function () {
                        var action = mform.attr('action') + '?savewithconflicts=1';
                        mform.attr('action', action);
                        $('#id_submitbutton').trigger('click');
                    });
                });
            });
        }
    };

    return addConfirm;
});


