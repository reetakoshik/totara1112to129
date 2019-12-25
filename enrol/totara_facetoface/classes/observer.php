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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package enrol_totara_facetoface
 */

class enrol_totara_facetoface_observer {

    /**
     * Handles changes in the users signup status as far as the enrol_totara_facetoface plugin is concerned.
     *
     * @param \mod_facetoface\event\signup_status_updated $event
     */
    public static function mod_facetoface_signup_status_updated(\mod_facetoface\event\signup_status_updated $event) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/enrol/totara_facetoface/lib.php');

        $data = $event->get_data();
        $signupstatus = $DB->get_record('facetoface_signups_status', ['id' => $data['objectid']]);

        if ($signupstatus) {
            enrol_totara_facetoface_enrol_on_approval($signupstatus);
            enrol_totara_facetoface_unenrol_on_removal($signupstatus);
        }
    }

    /**
     * Handlers the deletion of a course module as far as the enrol_totara_facetoface plugin is concerned.
     *
     * @param \core\event\course_module_deleted $event
     * @return bool
     */
    public static function unenrol_users_on_module_deletion(\core\event\course_module_deleted $event) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/enrol/totara_facetoface/lib.php');

        $data = $event->get_data();
        if (!isset($data['other']['modulename']) || !isset($data['courseid'])) {
            // Hmmm thats a pain, its just about an exception, except we can't stop things now!
            debugging('Unable to cleanup Seminar enrolments as the module delete event does not contain the required information', DEBUG_DEVELOPER);
            return true;
        }
        if ($data['other']['modulename'] == 'facetoface') { // Facetoface activity deleted.
            // Find all enrolment instances in this course of type totara_facetoface with 'unenrol when removed' enabled.
            $enrols = $DB->get_records(
                'enrol',
                array(
                    'enrol' => 'totara_facetoface',
                    'courseid' => $data['courseid'],
                    enrol_totara_facetoface_plugin::SETTING_UNENROLWHENREMOVED => 1
                )
            );
            foreach ($enrols as $enrolinst) {
                if (!$userids = $DB->get_fieldset_select('user_enrolments', 'userid', 'enrolid = ?', array($enrolinst->id))) {
                    continue;
                }
                enrol_totara_facetoface_unenrol_if_no_signups($enrolinst, $userids);
            }
        }
    }

}