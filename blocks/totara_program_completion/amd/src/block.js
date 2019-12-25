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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Brian Barnes <brian.barnes@totaralms.com>
 *
 * @package block
 * @subpackage totara_program_completion
 */

define(['jquery', 'core/str'], function ($, mdlstrings) {
    var blockjs = {

        /**
         * Module initialisation method called by php js_call_amd().
         *
         * @param instanceid string the blocks instance id
         */
        init: function(instanceid) {
            var requiredstrings = [];
            requiredstrings.push({key: 'more', component: 'block_totara_program_completion'});
            requiredstrings.push({key: 'less', component: 'block_totara_program_completion'});

            mdlstrings.get_strings(requiredstrings).done(function (strings) {
                var tstr = [];
                for (var i = 0; i < requiredstrings.length; i++) {
                    tstr[requiredstrings[i].key] = strings[i];
                }
                $('.block-totara-prog-completion-morelink' + instanceid).on('click', function (e) {
                    e.preventDefault();
                    $('.block-prog-completions-list .more' + instanceid).toggle();
                    if ($(this).text() == tstr.more) {
                        $(this).text(tstr.less);
                    } else {
                        $(this).text(tstr.more);
                    }
                });
            });
        }
    };

    return blockjs;
});
