<?php
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_catalog
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

$observers = array(
    array(
        'eventname' => '\core\event\admin_settings_changed',
        'callback'  => 'totara_catalog\observer\settings_observer::changed',
    ),
    array(
        'eventname' => '\core\event\course_deleted',
        'callback' => '\totara_catalog\observer\course_search_metadata_observer::remove_search_metadata'
    ),
    array(
        'eventname' => '\totara_program\event\program_deleted',
        'callback' => '\totara_catalog\observer\program_search_metadata_observer::remove_search_metadata'
    )
);
