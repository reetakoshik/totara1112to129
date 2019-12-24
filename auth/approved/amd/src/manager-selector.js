/*
 * This file is part of Totara LMS
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */

/**
 * Manager job assignment selector adaptor for auto-complete form element.
 * @module auth_approved/manager-selector
 */
define(['jquery', 'core/ajax', 'core/notification'], function($, ajax, notification) {
    return /** @alias module:auth_approved/manager-selector */ {
        processResults: function(selector, data) {
            // Mangle the results into an array of objects.
            var results = [];
            var i = 0, manager;

            for (i = 0; i < data.managers.length; i++) {
                manager = data.managers[i];
                results.push({
                    value: manager.jaid,
                    label: manager.displayname
                });
            }

            return results;
        },

        transport: function(selector, query, success, failure) {
            if (typeof query === "undefined") {
                query = '';
            }
            var promises = null,
                calls = [{
                    methodname: 'auth_approved_job_assignment_by_user_names',
                    args: {
                        searchquery: query,
                        page: 0,
                        perpage: 100,
                        termaggregation: 'AND'
                    }
                }];

            // Go go go!
            promises = ajax.call(calls);
            $.when.apply($.when, promises).done(function(data) {
                success(data);
            }).fail(failure);
        }
    };
});
