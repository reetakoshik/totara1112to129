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
 * @package totara_program
 */

define(['jquery', 'core/str', 'core/config', 'core/yui'], function($, mdlstrings, mdlcfg, Y) {
    var check_completion = {
        /**
         * module initialisation method called by php js_call_amd()
         */
        init : function(args) {
            // We need to make sure that the confirm notification is loaded.
            Y.use('moodle-core-notification-confirm', function(Y) {
                $('.problemaggregation a').on('click', function (e) {
                    e.preventDefault();
                    modalConfirm($(this).attr('href'), 'fixconfirmsome');
                });
                $('.problemsolution a').on('click', function (e) {
                    e.preventDefault();
                    modalConfirm($(this).attr('href'), 'fixconfirmone');
                });
            });
        }
    };

    function modalConfirm(url, scope) {
        var confirm = new M.core.confirm({
            title        : M.util.get_string('fixconfirmtitle', 'totara_program'),
            question     : M.util.get_string(scope, 'totara_program'),
            width        : 500
        });
        confirm.on('complete-yes', function(){
            window.location.href = url;
        });
        confirm.on('complete-no', function(e){
            e.preventDefault();
        });
        confirm.show();
    }

    return check_completion;
});
