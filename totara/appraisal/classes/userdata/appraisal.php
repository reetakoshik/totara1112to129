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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_appraisal
 */

namespace totara_appraisal\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * This is the purge-only user data item for appraisals belonging to the user.
 */
class appraisal extends item {
    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $CFG;

        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        // First delete all files, because the function is faulty.
        // TODO TL-17131 When the bug is fixed, this code can be removed.
        global $DB;
        $fs = get_file_storage();
        $sql = "SELECT ara.id, aua.appraisalid
                  FROM {appraisal_role_assignment} ara
                  JOIN {appraisal_user_assignment} aua ON ara.appraisaluserassignmentid = aua.id
                 WHERE aua.userid = :userid";
        $roleassignments = $DB->get_records_sql($sql, array('userid' => $user->id));
        foreach ($roleassignments as $roleassignment) {
            $fs->delete_area_files($context->id, 'totara_appraisal', 'snapshot_' . $roleassignment->appraisalid, $roleassignment->id);
        }

        // Delete all user_assignments and associated data for the learner.
        \appraisal::delete_learner_assignments($user->id);

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return false;
    }

    /**
     * Can user data of this item be somehow counted?
     * How much data is there?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int  integer is the count >= 0, negative number is error result self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        return $DB->count_records('appraisal_user_assignment', array('userid' => $user->id));
    }
}