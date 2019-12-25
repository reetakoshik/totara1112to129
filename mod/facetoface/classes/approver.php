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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Class manager approver
 */
final class approver {

    /**
     * Dismiss mamager
     *
     * @param seminar $seminarid
     */
    public static function dismiss(seminar $seminar) {
        $seminarevents = seminar_event_list::from_seminar($seminar);

        foreach ($seminarevents as $seminarevent) {
            $seminarevent->dismiss_approver();
        }
    }

    /**
     * Get managers who allow to be as seminar approver
     *
     * @param int $userid
     * @return array $managers
     */
    public static function get_managers(int $userid) : array {
        global $CFG, $DB;

        // Check that facetoface_managerselect is set.
        $facetoface_managerselect = isset($CFG->facetoface_managerselect) ? $CFG->facetoface_managerselect : 0;
        if ($facetoface_managerselect != 1) {
            print_error('error:approvaladminnotactive', 'facetoface');
        }

        $guest = guest_user();

        // Load potential managers for this user.
        $usernamefields = get_all_user_name_fields(true, 'u');
        $sql = "SELECT u.id, u.email, {$usernamefields}
                  FROM {user} u
                 WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND u.id != :guestid
                   AND u.id != :userid
              ORDER BY {$usernamefields}";
        $params = array(
            'guestid' => $guest->id,
            'userid' => $userid
        );

        // Limit results to 1 more than the maximum number that might be displayed
        // there is no point returning any more as we will never show them.
        $managers = $DB->get_records_sql($sql, $params, 0, TOTARA_DIALOG_MAXITEMS + 1);
        foreach ($managers as $manager) {
            $manager->fullname = fullname($manager);
        }

        return $managers;
    }

    /**
     * Check that approval_admin is active in facetoface_approvaloptions.
     *
     * @throws \moodle_exception
     */
    public static function require_active_admin() {
        global $CFG;

        $settingsoptions = isset($CFG->facetoface_approvaloptions) ? $CFG->facetoface_approvaloptions : '';
        $approvaloptions = explode(',', $settingsoptions);
        if (!in_array('approval_admin', $approvaloptions)) {
            print_error('error:approvaladminnotactive', 'facetoface');
        }
    }

    /**
     * Find potential managers for a user.
     * @param string|null $selected         String of the user's id sequence.
     * @return array
     */
    public static function find_managers(string $selected = null) {
        global $DB;

        // Get guest user for exclusion purposes.
        $guest = guest_user();

        $disable_items = array();
        $systemapprovers = get_users_from_config(get_config(null, 'facetoface_adminapprovers'), 'mod/facetoface:approveanyrequest');
        foreach ($systemapprovers as $sysapprover) {
            if (!empty($sysapprover)) {
                $disable_items[$sysapprover->id] = $sysapprover;
            }
        }

        $select_items = array();
        if (!empty($selected)) {
            $activityapprovers = explode(',', $selected);
            foreach ($activityapprovers as $actapprover) {
                $item = $DB->get_record('user', array('id' => $actapprover));
                $item->fullname = fullname($item);
                $select_items[$item->id] = $item;
            }
        }

        // Load potential managers for this user.
        $usernamefields = get_all_user_name_fields(true, 'u');
        $sql = "SELECT u.id, {$usernamefields}, u.email
                  FROM {user} u
                 WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND u.id != ?
              ORDER BY u.firstname, u.lastname";
        $availableusers = $DB->get_records_sql($sql, [$guest->id], 0, TOTARA_DIALOG_MAXITEMS + 1);
        foreach ($availableusers as $user) {
            $user->fullname = fullname($user);
        }

        return [$disable_items, $select_items, $availableusers];
    }

    /**
     * Get the right approver & approval time we will need to get the approved status record.
     *
     * @param \seminar $seminar
     * @param $attendee
     * @return array
     * @throws \dml_exception
     */
    public static function get_required(\seminar $seminar, $attendee) {
        global $DB;

        $approver = '';
        $approval_time = '';
        if ($seminar->get_approvaltype() > \mod_facetoface\seminar::APPROVAL_SELF) {
            $sql = 'SELECT fss.id, fss.signupid, fs.userid, fss.createdby, fss.timecreated
                      FROM {facetoface_signups} fs
                      JOIN {facetoface_signups_status} fss
                        ON fss.signupid = fs.id
                     WHERE fs.id = :sid
                       AND fs.userid = :uid
                       AND fss.statuscode IN (' . \mod_facetoface\signup\state\waitlisted::get_code() . ', ' . \mod_facetoface\signup\state\booked::get_code() . ')
                       AND fss.createdby != fs.userid
                  ORDER BY fss.timecreated DESC';
            $params = array('sid' => $attendee->submissionid, 'uid' => $attendee->id);
            $apprecords = $DB->get_records_sql($sql, $params);
            $apprecord = array_shift($apprecords);

            // It is possible for a seminar to start from a "no approval
            // needed" type to become a "manager approved" seminar even
            // after people have signed up. When this occurs, learners
            // will not be picked up by the SQL statement above - simply
            // because no approval record need to be created when they
            // were waitlisted or booked. Hence the check here.
            $approver = isset($apprecord->createdby) ? fullname($DB->get_record('user', array('id' => $apprecord->createdby))) : '';
            $approval_time = isset($apprecord->timecreated) ? userdate($apprecord->timecreated) : '';
        }
        return [$approver, $approval_time];
    }

    /**
     * Count of selfapprovals.
     *
     * @param int $facetofaceid
     * @return int
     */
    public static function count_selfapproval(int $facetofaceid) {
        global $DB;

        $sql = "SELECT selfapproval, count(selfapproval)
                  FROM {facetoface_sessions}
                 WHERE facetoface = :fid
              GROUP BY selfapproval";
        return count($DB->get_records_sql($sql, ['fid' => $facetofaceid]));
    }
}