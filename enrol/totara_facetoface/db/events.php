<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Andrew Hancox <andrewdchancox@googlemail.com> on behalf of Synergy Learning
 * @package totara
 * @subpackage enrol_totara_facetoface
 */

$handlers = array (
    // There are none, handlers have been deprecated, you must use observers.
);

$observers = array(
    array(
        'eventname' => '\mod_facetoface\event\signup_status_updated',
        'callback' => 'enrol_totara_facetoface_observer::mod_facetoface_signup_status_updated',
    ),
    array(
        'eventname' => '\core\event\course_module_deleted',
        'callback' => 'enrol_totara_facetoface_observer::unenrol_users_on_module_deletion',
    ),
);