<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\task;

/**
 * Sync one dynamic cohort.
 */
class sync_dynamic_cohort_task extends \core\task\adhoc_task {
    public function execute() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/totara/cohort/lib.php");

        $cohort = $DB->get_record('cohort', array('id' => $this->get_custom_data()));
        if (!$cohort) {
            // Nothing to sync.
            return;
        }

        if ($cohort->cohorttype != \cohort::TYPE_DYNAMIC) {
            // Nothing to sync.
            return;
        }

        $trace = new \text_progress_trace();
        $trace->output('Adhoc dynamic sync of cohort id ' . $cohort->id);

        self::sync_cohort($cohort, $trace);

        $trace->finished();
    }

    /**
     * Sync one dynamic cohort.
     *
     * @param \stdClass $cohort
     * @param \progress_trace|null $trace
     */
    public static function sync_cohort($cohort, \progress_trace $trace = null) {
        global $CFG;
        require_once("$CFG->dirroot/totara/cohort/lib.php");
        require_once("$CFG->dirroot/enrol/cohort/locallib.php");

        raise_memory_limit(MEMORY_HUGE);
        \core_php_time_limit::raise(60 * 30);

        if ($trace === null) {
            $trace = new \null_progress_trace();
        }

        if (enrol_is_enabled('cohort')) {
            enrol_cohort_sync($trace, null, $cohort->id);
        } else {
            totara_cohort_check_and_update_dynamic_cohort_members(null, $trace, $cohort->id);
        }
    }
}

