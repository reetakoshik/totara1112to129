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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\task;

/**
 * Enrol audience in course.
 */
class enrol_audience_in_course_task extends \core\task\adhoc_task {

    /**
     * Do the job.
     */
    public function execute() {
        global $CFG;
        require_once("$CFG->dirroot/totara/cohort/lib.php");
        require_once("$CFG->dirroot/enrol/cohort/locallib.php");

        $data = $this->get_custom_data();
        $cohortid = $data->cohortid;
        $courseid = $data->courseid;

        raise_memory_limit(MEMORY_HUGE);
        \core_php_time_limit::raise(60 * 30);

        // No output needed for unit tests.
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST === true) {
            $trace = new \null_progress_trace();
        } else {
            $trace = new \text_progress_trace();
        }

        enrol_cohort_sync($trace, $courseid, $cohortid);
    }
}

