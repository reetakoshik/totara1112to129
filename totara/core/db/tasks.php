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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/* List of handlers */

$tasks = array(
    array(
        'classname' => 'totara_core\task\persistent_login',
        'blocking' => 0,
        'minute' => 'R',
        'hour' => '3',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'totara_core\task\send_registration_data_task',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',
        'day' => '*',
        'dayofweek' => '1',
        'month' => '*'
    ),
    array(
        'classname' => 'totara_core\task\tool_totara_sync_task',
        'blocking' => 1,
        'minute' => '0',
        'hour' => '0',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'totara_core\task\send_reminder_messages_task',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'totara_core\task\completion_orphans_task',
        'blocking' => 0,
        'minute' => '54',
        'hour' => '1',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'totara_core\task\analyze_table_task',
        'blocking' => 0,
        'minute' => '11',
        'hour' => '2',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
);
