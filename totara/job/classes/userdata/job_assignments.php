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
 * @package totara_job
 */

namespace totara_job\userdata;

use totara_job\job_assignment;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

global $CFG;

require_once($CFG->libdir . '/formslib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Job assignments.
 */
class job_assignments extends item {
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
        global $TEXTAREA_OPTIONS;

        $fs = get_file_storage();

        $jas = job_assignment::get_all($user->id);
        // Reverse it, so that we delete from the end of the list and the delete function won't need to reorder all the items each time.
        $jas = array_reverse($jas);

        foreach ($jas as $ja) {
            // We don't trust the function to do files, so we delete them here (before, because the job assignment object is destroyed).
            $fs->delete_area_files($TEXTAREA_OPTIONS['context']->id,
                'totara_job', 'job_assignment', $ja->id);

            // This function will tidy up everything related to the job assignment, such as manager, temp manager, role assignments.
            job_assignment::delete($ja);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB, $TEXTAREA_OPTIONS;

        $export = new export();
        $export->data['job_assignments'] = [];

        $assignments = $DB->get_records('job_assignment', array('userid' => $user->id));

        $fs = get_file_storage();

        // Add the attachments.
        foreach ($assignments as $assignment) {
            $assignment = (array)$assignment;
            $assignment['files'] = [];

            $files = $fs->get_area_files(
                $TEXTAREA_OPTIONS['context']->id,
                'totara_job',
                'job_assignment',
                $assignment['id'],
                'timemodified',
                false
            );
            foreach ($files as $file) {
                $assignment['files'][] = $export->add_file($file);
            }

            $export->data['job_assignments'][] = $assignment;
        }

        return $export;
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
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        return $DB->count_records('job_assignment', array('userid' => $user->id));
    }
}