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

define(['jquery', 'core/str', 'core/yui'], function($, mdlstrings, Y) {
    var check_completion = {
        /**
         * module initialisation method called by php js_call_amd()
         */
        init : function(args) {
            $('.problemaggregation a').on('click', function (e) {
                e.preventDefault();
                modalConfirm($(this).attr('href'), 'fixconfirmsome');
            });
            $('.problemsolution a').on('click', function (e) {
                e.preventDefault();
                modalConfirm($(this).attr('href'), 'fixconfirmone');
            });
        }
    };

    function modalConfirm(url, scope) {
        var requiredstrings = [];
        requiredstrings.push({key: 'fixconfirmtitle', component: 'totara_completioneditor'});
        requiredstrings.push({key: scope, component: 'totara_completioneditor'});
        mdlstrings.get_strings(requiredstrings).done(function(strings) {
            // We need to make sure that the confirm notification is loaded.
            Y.use('moodle-core-notification-confirm', function(Y) {
                var confirm = new M.core.confirm({
                    title        : strings[0],
                    question     : strings[1],
                    width        : 500
                });
                confirm.on('complete-yes', function(){
                    window.location.href = url;
                });
                confirm.on('complete-no', function(e){
                    e.preventDefault();
                });
                confirm.show();
            });
        });
    }

    return check_completion;
});
