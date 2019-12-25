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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_program\rb\display;

/**
 * Class describing column display formatting.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_reportbuilder
 */
class program_completion_progress extends \totara_reportbuilder\rb\display\base {

    /* @var totara_core_renderer $totara_renderer */
    static $totara_renderer;

    /**
     * Displays the program completion progress.
     *
     * @param string $value - program status expected
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        global $PAGE;

        // Get the necessary fields out of the row.

        $isexport = ($format !== 'html');
        $extrafields = self::get_extrafields_row($row, $column);

        $percentage = false;

        if (isset($extrafields->programid) && isset($extrafields->userid)) {
            $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo_from_id($extrafields->programid, $extrafields->userid);
            $percentage = $progressinfo->get_percentagecomplete();
        }

        if ($percentage === false) {
            // Can't calculate progress, use status instead
            if (is_null($value)) {
                return '';
            }
            if ($value) {
                return get_string('complete', 'totara_program');
            } else {
                return get_string('incomplete', 'totara_program');
            }
        }

        if ($isexport) {
            if (isset($extrafields->stringexport) && $extrafields->stringexport) {
                return get_string('xpercentcomplete', 'totara_core', $percentage);
            } else {
                return $percentage;
            }
        }

        if (empty(self::$totara_renderer)) {
            self::$totara_renderer = $PAGE->get_renderer('totara_core');
        }

        // Get relevant progress bar and return for display.
        return self::$totara_renderer->progressbar($percentage, 'medium', $isexport, 'DEFAULTTOOLTIP');
    }
}