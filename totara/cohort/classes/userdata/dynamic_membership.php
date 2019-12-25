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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;
use totara_cohort\event\members_updated;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/totara/cohort/lib.php');

/**
 * Class membership
 *
 * This is for purging, exporting and counting a user's membership within dynamic audiences.
 *
 * It's important to note that if the user still meets the criteria for the audience, they will be added back when membership of
 * that audience is evaluated again. e.g. by a scheduled task on cron, or if the rule set is saved in the interface.
 *
 * Roles:
 *  - Audience roles are unassigned when a user removed from an audience as per existing behaviour within Totara.
 *  - The roles are not exported as this should be dealt with by a role assignment user data item.
 *
 * Events:
 *  - \totara_cohort\event\members_updated will be triggered when a user is removed via purging.
 *  - \core\event\role_unassigned will be triggered when purging leads to a role unassignment.
 *
 * Notifications:
 *  - No notifications are sent when audience memberships are updated.
 *  - The reason this is mentioned is that at other times when audience membership is updated in Totara, we check
 *    the setting 'alertmembers' and may subsequently send emails to the affected or to all users. This could have a large
 *    effect on performance and may not be desired when members are removed via this purge process rather than the
 *    standard audience management interface.
 */
class dynamic_membership extends membership_base {

    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $cohorts = self::get_memberships(
            'cm.id as membershipid, co.id, co.cohorttype',
            $user,
            $context,
            \cohort::TYPE_DYNAMIC
        );

        foreach ($cohorts as $cohort) {
            $DB->delete_records('cohort_members', ['id' => $cohort->membershipid]);
            members_updated::create(['objectid' => $cohort->id])->trigger();
            self::unassign_roles_in_cohort($cohort, $user);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        $export = new export();
        $export->data = self::get_memberships('cm.*, co.name', $user, $context, \cohort::TYPE_DYNAMIC);

        return $export;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        $counts = self::get_memberships('COUNT(cm.id) as count', $user, $context, \cohort::TYPE_DYNAMIC);

        return reset($counts)->count;
    }
}
