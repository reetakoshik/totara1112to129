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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_plan
 */

namespace totara_plan\task;

/**
 * Send, dismiss queued messages
 */
class autocomplete_plans_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('autcompleteplantask', 'totara_plan');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     * At the moment all this does is update plans that are set
     * to auto complete after the end date
     */
    public function execute() {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/totara/plan/lib.php");

        $time = time();
        $approved = DP_PLAN_STATUS_APPROVED;

        // Get plans that need completing.
        $sql = "
            SELECT
                lp.id as planid
            FROM
                {dp_plan} lp
            JOIN
                {dp_plan_settings} ps
             ON lp.templateid = ps.templateid
            WHERE
                ps.autobyplandate = 1
            AND lp.enddate <= ?
            AND lp.status = ?
        ";
        $params = array($time, $approved);

        // Complete them!
        $plans = $DB->get_records_sql($sql, $params);
        foreach ($plans as $p) {
            $plan = new \development_plan($p->planid);
            mtrace("Completing plan: {$plan->name}(ID:{$plan->id})");
            $plan->set_status(DP_PLAN_STATUS_COMPLETE, DP_PLAN_REASON_AUTO_COMPLETE_DATE);
        }
    }
}

