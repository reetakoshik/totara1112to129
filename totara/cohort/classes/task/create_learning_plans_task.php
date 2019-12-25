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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\task;

/**
 * Create learning plans for the audience.
 */
class create_learning_plans_task extends \core\task\adhoc_task {

    /**
     * Execute the adhoc task..
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();

        $planconfig = \totara_cohort\learning_plan_config::convert($data->config);

        // Check the cohort still exists and do nothing if it's gone.
        $cohort = $DB->get_record('cohort', array('id' => $planconfig->cohortid));
        if (!$cohort) {
            return;
        }

        // Use the learning plan config object to create the plans.
        $plancount = \totara_cohort\learning_plan_helper::create_plans($planconfig, $data->userid);

        if ($plancount) {
            mtrace("Successfully created new learning plans for {$plancount} members of audience '{$cohort->name}' ({$cohort->idnumber}).'");
        } else {
            mtrace("No learning plans created for audience '{$cohort->name}' ({$cohort->idnumber}).'");
        }
    }
}
