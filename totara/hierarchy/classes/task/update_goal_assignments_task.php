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
 * @author David Curry <david.curry@totaralms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\task;

/**
 * Update goal assignment task
 */
class update_goal_assignments_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('updategoaltask', 'totara_hierarchy');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

        if (totara_feature_disabled('goals')) {
            return;
        }
        $goallib = new \goal();

        // Update assignments.
        mtrace("Updating goal assignments:");

        $goals = $DB->get_records('goal', array());

        foreach ($goals as $goal) {
            $goallib->update_goal_user_assignments($goal->id);
        }

        mtrace(" Done!\n");
    }
}
