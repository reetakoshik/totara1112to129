<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_gap
 */

$watchers = [
    [
        // Called at the end of user_editadvanced_form::definition.
        // Used by Totara to add Totara specific elements to the user definition.
        'hookname' => '\core_user\hook\editadvanced_form_definition_complete',
        'callback' => 'totara_gap\watcher\core_user_editadvanced_form::extend_form',
        'priority' => 100,
    ],
    [
        // Called immediately before the user_editadvanced_form instance is displayed.
        // Used by Totara to add any required JS for the custom elements we've added.
        'hookname' => '\core_user\hook\editadvanced_form_display',
        'callback' => 'totara_gap\watcher\core_user_editadvanced_form::display_form',
        'priority' => 100,
    ],
    [
        // Called after the initial form data has been saved, before redirect.
        // Used by Totara to save data from our custom elements.
        'hookname' => '\core_user\hook\editadvanced_form_save_changes',
        'callback' => 'totara_gap\watcher\core_user_editadvanced_form::save_form',
        'priority' => 100,
    ]
];