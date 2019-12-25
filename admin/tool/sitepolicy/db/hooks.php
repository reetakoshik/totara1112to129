<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package tool_sitepolicy
 */

defined('MOODLE_INTERNAL') || die();
$watchers = [
    [
        // Called before signing up.
        // Adds additional data to the signup request.
        // The priority of this watcher must be after the core_edit_form from totara.
        'hookname' => '\auth_approved\hook\add_request',
        'callback' => '\tool_sitepolicy\watcher\add_request_watcher::add_extra_data_to_request',
        'priority' => 200
    ],
    [
        // Called before signing up.
        // Confirms any pre-signup requirements and redirects the user if necessary.
        // The priority of this watcher must be after the core_edit_form from totara.
        'hookname' => '\totara_core\hook\presignup_redirect',
        'callback' => '\tool_sitepolicy\watcher\presignup_watcher::confirm_site_policies',
        'priority' => 200
    ],
];