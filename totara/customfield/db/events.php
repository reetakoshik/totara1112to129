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
 * @package totara_customfield
 */

/**
 * This lists event observers.
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array(
        'eventname' => '\core\event\course_deleted',
        'callback'  => 'totara_customfield_observer::course_deleted',
    ),
    array(
        'eventname' => '\totara_program\event\program_deleted',
        'callback'  => 'totara_customfield_observer::program_deleted',
    ),
);
