<?php
/*
 * This file is part of Totara Learn
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */

$functions = [

    'totara_program_assignment_filter' => [
        'classname' => 'totara_program\assignment\external',
        'methodname' => 'filter_assignments',
        'classpath' => 'totara/program/classes/assignment/external.php',
        'description' => 'Filter program assignments',
        'ajax' => true,
        'type' => 'read',
    ],
    'totara_program_assignment_delete' => [
        'classname' => 'totara_program\assignment\external',
        'methodname' => 'remove_assignment',
        'classpath' => 'totara/program/classes/assignment/external.php',
        'description' => 'Delete a program assignment',
        'ajax' => true,
        'type' => 'write',
    ],
    'totara_program_assignment_set_fixed_due_date' => [
        'classname' => 'totara_program\assignment\external',
        'methodname' => 'set_fixed_due_date',
        'classpath' => 'totara/program/classes/assignment/external.php',
        'description' => 'Set due date for an assignment',
        'ajax' => true,
        'type' => 'write',
    ],
    'totara_program_assignment_set_relative_due_date' => [
        'classname' => 'totara_program\assignment\external',
        'methodname' => 'set_relative_due_date',
        'classpath' => 'totara/program/classes/assignment/external.php',
        'description' => 'Set due date for an assignment',
        'ajax' => true,
        'type' => 'write',
    ],
    'totara_program_assignment_set_include_children' => [
        'classname' => 'totara_program\assignment\external',
        'methodname' => 'set_includechildren',
        'classpath' => 'totara/program/classes/assignment/external.php',
        'description' => 'Set due date for an assignment',
        'ajax' => true,
        'type' => 'write',
    ],
    'totara_program_assignment_remove_due_date' => [
        'classname' => 'totara_program\assignment\external',
        'methodname' => 'remove_due_date',
        'classpath' => 'totara/program/classes/assignment/external.php',
        'description' => 'Remove due date from an assignment',
        'ajax' => true,
        'type' => 'write',
    ],
    'totara_program_assignment_create' => [
        'classname' => 'totara_program\assignment\external',
        'methodname' => 'add_assignments',
        'classpath' => 'totara/program/classes/assignment/external.php',
        'description' => 'Add new assignments to the program',
        'ajax' => true,
        'type' => 'write',
    ]
];
