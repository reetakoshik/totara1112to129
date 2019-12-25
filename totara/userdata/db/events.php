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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

$observers = array(
    array(
        'eventname' => '\core\event\user_created',
        'callback' => 'totara_userdata\local\observer::user_created',
        'priority' => -100000,
    ),
    array(
        'eventname' => '\core\event\user_updated',
        'callback' => 'totara_userdata\local\observer::user_updated',
        'priority' => -100000,
    ),
    array(
        'eventname' => '\totara_core\event\user_suspended',
        'callback' => 'totara_userdata\local\observer::user_suspended',
        'priority' => -100000,
    ),
    array(
        'eventname' => '\core\event\user_deleted',
        'callback' => 'totara_userdata\local\observer::user_deleted',
        'priority' => -100000,
    ),
    array(
        'eventname' => '\totara_core\event\user_undeleted',
        'callback' => 'totara_userdata\local\observer::user_undeleted',
        'priority' => -100000,
    ),
);
