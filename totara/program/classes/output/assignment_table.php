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

namespace totara_program\output;

require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');

final class assignment_table extends \core\output\template {

    /**
     * Creates an assignment_table object from the supplied program assignments
     *
     * @param array $assignments The program assignments
     * 
     * @return assignment_table template object
     */
    public static function create_from_assignments(array $assignments): assignment_table {

        $data = [];
        $items = [];

        foreach ($assignments as $assignment) {
            $assignmentduedate = $assignment->get_duedate();
            $item = [
                'id' => $assignment->get_id(),
                'name' => $assignment->get_name(),
                'type' => \totara_program\assignment\helper::get_type_string($assignment->get_type()),
                'type_id' => $assignment->get_type(),
                'checkbox' => ($assignment->get_type() === ASSIGNTYPE_ORGANISATION || $assignment->get_type() === ASSIGNTYPE_POSITION),
                'dropdown' => ($assignment->get_type() === ASSIGNTYPE_MANAGERJA),
                'includechildren' => $assignment->get_includechildren(),
                'duedate' => $assignmentduedate->string,
                'duedateupdatable' => $assignmentduedate->changeable,
                'actualduedate' => $assignment->get_actual_duedate(),
                'learnercount' => $assignment->get_user_count(),
            ];

            $items[] = $item;
        }
        $data['items'] = $items;

        return new assignment_table($data);
    }
}
