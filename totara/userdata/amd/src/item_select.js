/*
 * This file is part of Totara Learn
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
 * @author  Brian Barnes <brian.barnes@totaralearning.com>
 * @package totara_userdata
 */

 define(['jquery', 'core/str'], function($, strlib) {
    var init = function(name, form) {
        var requiredstrings = [
            {key: 'selectall', component: 'core'},
            {key: 'deselectall', component: 'core'},
        ];
        strlib.get_strings(requiredstrings).done(function(strings) {
            var formid = 'tfiid_' + name + '_totara_userdata_form_' + form + '_type_edit';
            var target = $('[data-element-id="' + formid + '"] .totara_form_element_static_html');
            var togglestring = '<a href="#" class="select">' + strings[0] + '</a> / ' +
                '<a href="#" class="deselect">' + strings[1] + '</a>';
            target.append(togglestring);
            target.on('click', '.select', function(e) {
                e.preventDefault();
                $('[data-item-classification="group"] input[type="checkbox"]').prop('checked', true);
            });
            target.on('click', '.deselect', function(e) {
                e.preventDefault();
                $('[data-item-classification="group"] input[type="checkbox"]').prop('checked', false);
            });

        });
    };

    return {init: init};
 });