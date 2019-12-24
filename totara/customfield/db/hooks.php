<?php
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

$watchers = [
    [
        // Called in the \totara\customfield\field_form::set_data
        // Inject seminar custom field form definion into totara custom field form definiion
        // changing a shortname field behaviour if it is the reserved seminar custom field,
        // changing the shortname attrs to readonly, label and help text.
        'hookname' => '\totara_customfield\hook\field_form_set_data',
        'callback' => '\mod_facetoface\watcher\shortname_customfield::set_data',
        'priority' => 100,
    ],
    [
        // Called in the \totara\customfield\field_form::validation
        // Inject seminar custom field form validation into totara custom field form validation,
        // reserved seminar custom field should not be used with a new custom fields.
        'hookname' => '\totara_customfield\hook\field_form_validation',
        'callback' => '\mod_facetoface\watcher\shortname_customfield::validation',
        'priority' => 100,
    ],
    [
        // Called in the \totara\customfield\renderer::totara_customfield_print_list
        // Inject into totara_customfield_print_list functionality and
        // Check for reserved seminar custom field, set the reserved value to true if it is.
        'hookname' => '\totara_customfield\hook\field_form_render_data',
        'callback' => '\mod_facetoface\watcher\shortname_customfield::render_data',
        'priority' => 100,
    ],
    [
        // Called in the \totara\customfield\renderer::totara_customfield_print_list
        // Inject into totara_customfield_print_list functionality and
        // Check for reserved seminar custom field, set can_delete to false if it is.
        'hookname' => '\totara_customfield\hook\field_form_render_icons',
        'callback' => '\mod_facetoface\watcher\shortname_customfield::render_icons',
        'priority' => 100,
    ]
];