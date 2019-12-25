YUI.add('moodle-core-jquerybehat', function (Y, NAME) {

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
 * This file contains JavaScript that should be included as part of
 * every page within Moodle.
 *
 * PLEASE PLEASE PLEASE do not add anything here unless there is no better
 * alternative.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @author Brian Barnes <brian.barnes@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

M.core = M.core || {};
M.core.jquerybehat = M.core.jquerybehat || {

    init: function () {
        /**
         * Generates a unique Totara ID.
         * @function
         */
        var totara_generate_id = (function(){
            // We are operating within a lambda style function here to establish
            // a scope for the purpose of this function.
            // This way idcount can't be influenced from the outside and we will always get a unique id.
            var idcount = 0;
            return function() {
                return 'totara-genid-'+(idcount++);
            };
        }());

        // Moodle watches all XHRHttpRequests that come through YUI.
        // This is used in behat, and potentially will be used elsewhere.
        // We want to be sure that jQuery events get monitored as well seeing as we rely on jQuery.
        if (M.util.js_pending !== 'undefined') {
            $(document).on("ajaxSend", function (ev, jqxhr, options) {
                // This is a little nasty - hopefully it stays unique.
                jqxhr.totaraxhrid = totara_generate_id();
                M.util.js_pending(jqxhr.totaraxhrid);
            }).on("ajaxComplete", function (ev, jqxhr, options) {
                M.util.js_complete(jqxhr.totaraxhrid);
            });
        }
    }
};

}, '@VERSION@', {"requires": []});
