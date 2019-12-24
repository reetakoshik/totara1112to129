/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package core_elementlibrary
 */
define(['core/config', 'core/str'], function (mdlcfg, mdlstrings) {

    /* global totaraDialog totaraDialogs totaraDialog_handler_treeview_multiselect */

    var multiselect = {

        id: 0,

        /**
         * module initialisation method called by php js_init_call()
         */
        init: function() {
            var url = mdlcfg.wwwroot+'/totara/hierarchy/prefix/competency/related/';

            // Get moodle strings - yes their implementation isn't great.
            var requiredstrings = [];
            requiredstrings.push({key: 'save', component: 'totara_core'});
            requiredstrings.push({key: 'cancel', component: 'moodle'});
            requiredstrings.push({key: 'assignrelatedcompetencies', component: 'totara_hierarchy'});

            mdlstrings.get_strings(requiredstrings).done(function (strings) {
                var tstr = [];
                for (var i = 0; i < requiredstrings.length; i++) {
                    tstr[requiredstrings[i].key] = strings[i];
                }

                var handler = new totaraDialog_handler_treeview_multiselect();

                var buttonObj = {};
                buttonObj[tstr.save] = function() { handler._dialog.hide(); };
                buttonObj[tstr.cancel] = function() { handler._cancel(); };

                totaraDialogs[name] = new totaraDialog(
                    'related',
                    'show-related-dialog',
                    {
                        buttons: buttonObj,
                        title: '<h2>' + tstr.assignrelatedcompetencies + '</h2>'
                    },
                    url+'find.php?id=' + multiselect.id,
                    handler
                );
            });
        }
    };
    return multiselect;
});