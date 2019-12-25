// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Ticks or unticks all checkboxes when clicking the Select all or Deselect all elements when viewing the response overview.
 *
 * @module      mod_choice/select_all_choices
 * @copyright   2017 Marcus Fabriczy <marcus.fabriczy@blackboard.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        init: function() {
            var nodes = document.querySelectorAll('.path-mod-choice .selectallnone a');

            var clickListener = function(e) {
                e.preventDefault();
                var flag;
                var users = document.getElementById('attemptsform').querySelectorAll('input[type=checkbox]');

                if (e.target.classList.contains('mod_choice-selectall')) {
                    flag = true;
                } else {
                    flag = false;
                }

                for (var user = 0; user < users.length; user++) {
                    users[user].checked = flag;
                }
            };

            for (var i = 0; i < nodes.length; i++) {
                nodes[i].addEventListener('click', clickListener);
            }
        }
    };
});
