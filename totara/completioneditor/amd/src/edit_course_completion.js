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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_completioneditor
 */

define(['jquery', 'core/str', 'core/notification'], function($, mdlstrings, Notification) {
    var edit_course_completion = {
        /**
         * Module initialisation method called by php js_call_amd().
         */
        init : function(args) {
            $('.deletecompletionhistorybutton').on('click', function (e) {
                e.preventDefault();

                var url = $(this).attr('href');

                var requiredstrings = [];
                requiredstrings.push({key: 'coursecompletionhistorydelete', component: 'totara_completioneditor'});
                requiredstrings.push({key: 'areyousure', component: 'moodle'});
                requiredstrings.push({key: 'yes', component: 'moodle'});
                requiredstrings.push({key: 'no', component: 'moodle'});
                mdlstrings.get_strings(requiredstrings).done(function(strings) {
                    Notification.confirm(
                        strings[0],
                        strings[1],
                        strings[2],
                        strings[3],
                        function() {
                            window.location.href = url;
                        }
                    );
                });
            });

            $('.deleteorphanedcritcomplbutton').on('click', function (e) {
                e.preventDefault();

                var url = $(this).attr('href');

                var requiredstrings = [];
                requiredstrings.push({key: 'coursecompletionorphanedcritcompldelete', component: 'totara_completioneditor'});
                requiredstrings.push({key: 'areyousure', component: 'moodle'});
                requiredstrings.push({key: 'yes', component: 'moodle'});
                requiredstrings.push({key: 'no', component: 'moodle'});
                mdlstrings.get_strings(requiredstrings).done(function(strings) {
                    Notification.confirm(
                        strings[0],
                        strings[1],
                        strings[2],
                        strings[3],
                        function() {
                            window.location.href = url;
                        }
                    );
                });
            });

            $('.deletecompletionlink').on('click', function (e) {
                e.preventDefault();

                var url = $(this).attr('href');

                var requiredstrings = [];
                requiredstrings.push({key: 'areyousure', component: 'moodle'});
                requiredstrings.push({key: 'coursecompletiondelete', component: 'totara_completioneditor'});
                requiredstrings.push({key: 'yes', component: 'moodle'});
                requiredstrings.push({key: 'no', component: 'moodle'});
                mdlstrings.get_strings(requiredstrings).done(function(strings) {
                    Notification.confirm(
                        strings[0],
                        strings[1],
                        strings[2],
                        strings[3],
                        function() {
                            window.location.href = url;
                        }
                    );
                });
            });
        }
    };

    return edit_course_completion;
});
