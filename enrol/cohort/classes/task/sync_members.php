<?php
/*
 * This file is part of Totara Learn
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package enrol_cohort
 */

namespace enrol_cohort\task;

/**
 * Task for synchronising members within the Cohort enrolment instances.
 *
 * @package enrol_cohort
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */
class sync_members extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasksyncmembers', 'enrol_cohort');
    }

    /**
     * Performs the synchronisation of cohort members into courses the cohort is enrolled in.
     */
    public function execute() {
        global $CFG;

        // Check if the enrolment plugin is disabled - isn't really necessary as the task should not run if
        // the plugin is disabled, but there is no harm in making sure core hasn't done something wrong.
        if (!enrol_is_enabled('cohort')) {
            return;
        }

        require_once("$CFG->dirroot/enrol/cohort/locallib.php");
        $trace = new \text_progress_trace();
        $trace->output('Cohort enrolment instance member synchronisation (enrol_cohort)');
        enrol_cohort_sync($trace);
        $trace->finished();
    }

}