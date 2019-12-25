<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 *
 * This cohort is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This cohort is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this cohort.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/totara/cohort/lib.php');

// TODO - move the rest of cohorts event observers from lib.php into here.
class totara_cohort_observer {

    /**
     * Handler function called when a user_confirmed event is triggered
     *
     * @param \core\event\user_confirmed $event
     * @return bool Success status
     */
    public static function user_confirmed(\core\event\user_confirmed $event) {
        global $DB;

        $now = time();
        $cohorts = $DB->get_records('cohort', array('cohorttype' => \cohort::TYPE_DYNAMIC));
        $trace = new null_progress_trace();
        $cohortbrokenrules = totara_cohort_broken_rules(null, null, $trace);

        // Run through all dynamic audiences and attempt to add the confirmed user.
        foreach ($cohorts as $cohort) {
            if (array_key_exists($cohort->id, $cohortbrokenrules)) {
                continue;
            }

            if ((empty($cohort->startdate) || $cohort->startdate < $now) &&
                (empty($cohort->enddate) || $cohort->enddate > $now)) {
                totara_cohort_update_dynamic_cohort_members($cohort->id, $event->relateduserid, true, true);
            }
        }
    }

}
