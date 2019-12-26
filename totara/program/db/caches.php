<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author  Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_program
 */

$definitions = array(
    // Cache for user's progress towards program completion
    'program_progressinfo' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 10
    ),
    // Cache containing all user keys in program_progressinfo belonging to a specific program
    // This cache is used to avoid purging the progressinfo cache when a specific program is changed
    'program_users' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 10
    ),
    // Cache containing all program keys in program_progressinfo belonging to a specific user
    // This cache is used to avoid purging the progressinfo cache when a user performs actions
    'user_programs' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 10
    ),

    // Used to store course sortorder within progammes.
    // The key is the program id (int) and the data is an array of courseids in the correct order (int[])
    // This cache is used by report source display columns in situations where the database can't sort within group concat.
    'course_order' => array(
        'mode'                   => cache_store::MODE_APPLICATION,
        'simplekeys'             => true,
        'simpledata'             => true,
        'staticacceleration'     => true,
        'staticaccelerationsize' => 100, // Small memory footprint, so make it large.
        'datasource'             => '\totara_program\rb_course_sortorder_helper',
    ),
);
