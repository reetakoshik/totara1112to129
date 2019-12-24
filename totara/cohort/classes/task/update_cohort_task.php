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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_cohort
 */

namespace totara_cohort\task;

/**
 * Update learner assignments for active appraisals.
 */
class update_cohort_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('updatecohortstask', 'totara_cohort');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/totara/cohort/rules/lib.php");

        $trace = new \text_progress_trace();

        // Clean up obsolete rule collections.
        $obsoleted = $DB->get_fieldset_select('cohort_rule_collections', 'id', 'status = ?', array(COHORT_COL_STATUS_OBSOLETE));
        if (!empty($obsoleted)) {
            $trace->output(date("H:i:s", time()).' Cleaning up obsolete rule collections...');
            foreach ($obsoleted as $obsolete) {
                cohort_rules_delete_collection($obsolete);
            }
        }

        // Sync dynamic audience members.
        $trace->output(date("H:i:s", time()).' Syncing dynamic audience members');
        totara_cohort_check_and_update_dynamic_cohort_members(null, $trace);

        $trace->output(date("H:i:s", time()).' Sending queued cohort notifications...');
        totara_cohort_send_queued_notifications();
        $trace->output(date("H:i:s", time()). ' Finished sending queued cohort notifications...');
    }
}

