/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package availability_audience
 */
define(['jquery', 'core/config'], function($, mdlcfg) {

    var _cache = {};

    // Public API
    return /** @alias module:availability_condition/ajax_handler */ {

        /**
         * Process the results returned from transport
         *
         * @method processResults
         * @param {String} selector
         * @param {Array} data
         * @return {Array}
         */
        processResults: function(selector, data) {
            return data;
        },

        /**
         * Fetch results based on the current query.
         *
         * @method transport
         * @param {String} selector Selector for the original select element
         * @param {String} query Current search string
         * @param {Function} success Success handler
         * @param {Function} failure Failure handler
         */
        transport: function(selector, query, success, failure) {

            if (query.trim() === '') {
                return;
            }

            function getCourse() {
                var vars = {}, hash;
                var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');

                // Loop through all of the variables searching for course.
                for(var i = 0; i < hashes.length; i++) {
                    hash = hashes[i].split('=');
                    if (hash[0] === 'course') {
                        return hash[1];
                    }
                }

                return null;
            }

            //Get the course param if it's available
            var courseid = getCourse();

            if (_cache[query] === undefined) {
                _cache[query] = $.ajax({
                    url: mdlcfg.wwwroot + '/availability/condition/audience/ajax.php',
                    type: 'POST',
                    data: {
                        filter: query,
                        course: courseid
                    }
                });
            }

            _cache[query].done(function(results) {
                success(results);
            }).fail(failure);
        }
    };
});
