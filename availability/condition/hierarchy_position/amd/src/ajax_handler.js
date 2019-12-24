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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package availability_hierarchy_position
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

            if (_cache[query] === undefined) {
                _cache[query] = $.ajax({
                    url: M.cfg.wwwroot + '/availability/condition/hierarchy_position/ajax.php',
                    type: 'POST',
                    data: {
                        filter: query
                    }
                });
            }

            _cache[query].done(function(results) {
                success(results);
            }).fail(failure);
        }
    };
});
