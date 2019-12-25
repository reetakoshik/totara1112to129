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
        // Used by Totara to add Totara specific elements to the seminar sing-up.
        'hookname' => '\mod_facetoface\hook\calendar_dynamic_content',
        'callback' => '\mod_facetoface\watcher\seminar_calendar_dynamic_content::signup',
        'priority' => 100,
    ]
];