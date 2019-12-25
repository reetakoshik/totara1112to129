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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display position name with edit icon
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_facetoface
 */
class f2f_job_assignment_edit extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT;

        $isexport = ($format !== 'html');

        if ($isexport) {
            return format_string($value);
        }

        $extrafields = self::get_extrafields_row($row, $column);

        if (!$cm = get_coursemodule_from_instance('facetoface', $extrafields->facetofaceid, $extrafields->courseid)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
        }
        $context = \context_module::instance($cm->id);
        $canchangesignedupjobassignment = has_capability('mod/facetoface:changesignedupjobassignment', $context);

        $jobassignment = \totara_job\job_assignment::get_with_id($extrafields->jobassignmentid, false);
        if (!empty($jobassignment)) {
            $label = \position::job_position_label($jobassignment);
        } else {
            $label = '';
        }
        $url = new \moodle_url('/mod/facetoface/attendees/ajax/job_assignment.php', array('s' => $extrafields->sessionid, 'id' => $extrafields->userid));
        $pix = new \pix_icon('t/edit', get_string('edit'));
        $icon = $OUTPUT->action_icon($url, $pix, null, array('class' => 'action-icon attendee-edit-job-assignment pull-right'));
        $jobassignmenthtml = \html_writer::span($label, 'jobassign' . $extrafields->userid, array('id' => 'jobassign' . $extrafields->userid));

        if ($canchangesignedupjobassignment) {
            return $icon . $jobassignmenthtml;
        }
        return $jobassignmenthtml;
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
