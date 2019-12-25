<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_plan
 */

namespace totara_plan\user_learning;

use core_course\user_learning\item as core_course;
use totara_core\user_learning\designation_subitem;
use \totara_core\user_learning\item_has_dueinfo;

class course extends core_course implements item_has_dueinfo {

    use designation_subitem;

    /**
     * Exports course data for template
     *
     * @return \stdClass Object containing course data
     */
    public function export_for_template() {
        $record = parent::export_for_template();
        $record->duetext = $this->duedate;
        return $record;
    }

    /**
     * Returns due info for this course
     *
     * @return \stdClass Due info for this course (duetext and tooltip)
     */
    public function get_dueinfo() {
        return $this->dueinfo;
    }

    /**
     * Exports the due date information for this user learning item as a context data object for use in templates.
     *
     * @return \stdClass Object containing the duetext and tooltip
     */
    public function export_dueinfo_for_template() {

        if ($this->duedate <= 0) {
            // If there is not duedate then we can't create the date for display.
            return;
        }

        $dueinfo = new \stdClass;

        $now = time();

        // Date for tooltip.
        $duedateformat = get_string('strftimedatetimeon', 'langconfig');
        $duedateformattedtooltip = userdate($this->duedate, $duedateformat);

        $duedateformatted = userdate($this->duedate, get_string('strftimedateshorttotara', 'langconfig'));
        if ($now > $this->duedate) {
            // Overdue.
            $dueinfo->duetext = get_string('userlearningoverduesincex', 'totara_core', $duedateformatted);
            $dueinfo->tooltip = get_string('userlearningoverduesincextooltip', 'totara_core', $duedateformattedtooltip);
        } else {
            // Due.
            $dueinfo->duetext = get_string('userlearningdueonx', 'totara_core', $duedateformatted);
            $dueinfo->tooltip = get_string('courseduex', 'totara_core', $duedateformattedtooltip);
        }

        return $dueinfo;
    }

    /**
     * Returns true if this user learning item has a due date.
     *
     * @return bool
     */
    public function item_has_duedate() {
        return true;
    }
}

