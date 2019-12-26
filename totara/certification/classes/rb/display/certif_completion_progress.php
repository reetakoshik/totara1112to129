<?php
/*
 * This file is part of Totara LMS
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_certification
 */

namespace totara_certification\rb\display;

/**
 * Display Certification progress.
 *
 * @package mod_facetoface
 */
class certif_completion_progress extends \totara_reportbuilder\rb\display\base {

    /* @var totara_core_renderer $totara_renderer */
    static $totara_renderer;

    /**
     * Displays the overall status.
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $PAGE;

        $isexport = ($format !== 'html');
        $extrafields = self::get_extrafields_row($row, $column);

        $now = time();

        if ($extrafields->window < $now) {
            // The window is open, use the current record.
            $vals = explode('|', $value);
            $programid = $vals[0];
            $userid = $vals[1];

            $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo_from_id($programid, $userid);
            $percentage = $progressinfo->get_percentagecomplete();

            if ($percentage === false) {
                // No tracking, default to 0
                $percentage = 0;
            }
        } else {
            // The window is not open
            if (!empty($extrafields->histcompletion) || !empty($extrafields->completion)) {
                // But they have previously completed or currently completed.
                $percentage = 100;
            } else {
                // They havent had a chance to do anything yet, or did not previously complete.
                $percentage = 0;
            }
        }

        if ($isexport) {
            if (isset($extrafields->stringexport) && $extrafields->stringexport)  {
                return get_string('xpercentcomplete', 'totara_core', $percentage);
            } else {
                return $percentage;
            }
        }

        if (empty(self::$totara_renderer)) {
            self::$totara_renderer = $PAGE->get_renderer('totara_core');
        }

        // Get relevant progress bar and return for display.
        return self::$totara_renderer->progressbar($percentage, 'medium', $isexport, $percentage . '%');
    }

    /**
     * Is this column graphable? No!
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
