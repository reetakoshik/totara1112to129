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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package mod_forum
 */
define([], function() {

    return {
        init: function() {
            var searchform = document.getElementById('searchform');

            var toggleDateFields = function(prefix, disabled) {
                var selects = searchform.querySelectorAll('select[name^=' + prefix + ']');
                for (var select = 0; select < selects.length; select++) {
                    selects[select].disabled = disabled;
                }

                var inputs = searchform.querySelectorAll('input[name^=' + prefix + ']');
                var value = disabled ? 1 : 0;
                for (var input = 0; input < inputs.length; input++) {
                    inputs[input].value = value;
                }

            };

            toggleDateFields('from', !searchform.querySelector("input[name='timefromrestrict']").checked);
            toggleDateFields('to', !searchform.querySelector("input[name='timetorestrict']").checked);
            searchform.addEventListener('click', function(e) {
                if (e.target.matches("input[name='timefromrestrict']")) {
                    toggleDateFields('from', !e.target.checked);
                } else if (e.target.matches("input[name='timetorestrict']")) {
                    toggleDateFields('to', !e.target.checked);
                }
            });
        }
    };
});