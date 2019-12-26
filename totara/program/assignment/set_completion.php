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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @package totara
 * @subpackage program
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/totara/program/lib.php');

require_login();
$PAGE->set_context(context_system::instance());

$programid = required_param('programid', PARAM_INT);

$assignmentid = optional_param('assignmentid', 0, PARAM_INT);

$data = new stdClass();
if ($assignmentid !== 0) {
    $assignment = $DB->get_record('prog_assignment', ['id' => $assignmentid], '*', MUST_EXIST);
    // Get data for populating form when reopening
    if ($assignment->completionevent == 0) {
        // Set due date
        $notset = (int)$assignment->completiontime === 0 || (int)$assignment->completiontime === -1;
        if ($notset) {
            $hour = 0;
            $minute = 0;
            $completiontime = '';
        } else {
            $hour = (int)userdate($assignment->completiontime, '%H', 99, false);
            $minute = (int)userdate($assignment->completiontime, '%M', 99, false);
            $completiontime = $notset ? '' : trim(userdate($assignment->completiontime,
                get_string('datepickerlongyearphpuserdate', 'totara_core'), 99, false));
        }

        $data->hour = $hour;
        $data->minute = $minute;
        $data->date = $completiontime;
    } else {
        // Relative due date
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        $relative = program_utilities::duration_explode($assignment->completiontime);

        $data->num = $relative->num;
        $data->period = $relative->period;
        $data->event = $assignment->completionevent;
        $data->instance = $assignment->completioninstance;

        global $COMPLETION_EVENTS_CLASSNAMES;

        $classname = $COMPLETION_EVENTS_CLASSNAMES[$data->event];
        $event = new $classname;
        $instancename = $event->get_item_name($data->instance);

        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'event', 'value' => $data->event));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'eventinstance', 'value' => $data->instance));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'eventinstancename', 'value' => $instancename));
    }
}

echo $PAGE->get_renderer('totara_program')->display_set_completion($programid, $data);
