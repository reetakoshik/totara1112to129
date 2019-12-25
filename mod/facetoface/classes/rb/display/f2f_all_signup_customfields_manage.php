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
 * Display customfield with edit action icon
 * This module requires JS already to be included
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_facetoface
 */
class f2f_all_signup_customfields_manage extends base {

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

        $value = format_string($value);

        if ($isexport) {
            return $value;
        }

        $extrafields = self::get_extrafields_row($row, $column);

        if (!$cm = get_coursemodule_from_instance('facetoface', $extrafields->facetofaceid, $extrafields->courseid)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
        }
        $context = \context_module::instance($cm->id);

        // When 'Reserve spaces for team' is used and no learners are added yet,
        // we still display attendees records with 'Reserved' status for other managers the number of reservations/bookings is used.
        if ((int)$extrafields->userid != 0 && has_capability('mod/facetoface:manageattendeesnote', $context)) {
            $url = new \moodle_url('/mod/facetoface/attendees/ajax/signup_notes.php', array(
                's' => $extrafields->sessionid,
                'userid' => $extrafields->userid,
                'return'  => $report->src->get_return_page()
            ));
            $pix = new \pix_icon('t/edit', get_string('edit'));
            $icon = $OUTPUT->action_icon($url, $pix, null, array('class' => 'js-hide action-icon attendee-add-note pull-right'));
            $notehtml = \html_writer::span($value);
            return $icon . $notehtml;
        }
        return $value;
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
