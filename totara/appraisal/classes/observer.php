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
 * @package totara
 * @subpackage appraisal
 */

defined('MOODLE_INTERNAL') || die();

class totara_appraisal_observer {

    /**
     * Event that is triggered when a user is deleted.
     *
     * Checks for any appraisals roles the user may have had and archives them.
     *
     * @param \core\event\user_deleted $event
     *
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        $userid = $event->objectid;
        $transaction = $DB->start_delegated_transaction();

        // Delete all user_assignments and associated data for the user.
        appraisal::delete_learner_assignments($userid);

        // Unassign all role_assignments for the user, but retain associated data.
        appraisal::unassign_user_roles($userid);

        $transaction->allow_commit();
    }

    /**
     * Stage complete message handler
     *
     * @deprecated since Totara 12.2 - use appraisal_stage_completed instead
     * @param \totara_appraisal\event\appraisal_stage_completion $event
     */
    public static function appraisal_stage_completion(\totara_appraisal\event\appraisal_stage_completion $event) {
        debugging('totara_appraisal_observer::appraisal_stage_completion has been deprecated - use totara_appraisal_observer::appraisal_stage_completed instead');
    }

    /**
     * Stage completed message handler
     *
     * @param \totara_appraisal\event\appraisal_stage_completed $event
     */
    public static function appraisal_stage_completed(\totara_appraisal\event\appraisal_stage_completed $event) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/totara/appraisal/lib.php'); // We should move all the classes into self loading ones.

        $time = $event->timecreated;
        $stageid = $event->other['stageid'];
        $sql = "SELECT id FROM {appraisal_event} WHERE event = :event AND appraisalstageid = :stageid";
        $params = array('event' => appraisal_message::EVENT_STAGE_COMPLETE, 'stageid' => $stageid);
        $events = $DB->get_records_sql($sql, $params);
        foreach ($events as $id => $eventdata) {
            $eventmessage = new appraisal_message($id);
            if ($eventmessage->is_immediate()) {
                $eventmessage->send_user_specific_message($event->relateduserid);
            } else {
                $newuserevent = new stdClass();
                $newuserevent->eventid = $id;
                $newuserevent->userid = $event->relateduserid;
                $newuserevent->timescheduled = $eventmessage->get_schedule_from($time);
                $DB->insert_record('appraisal_user_event', $newuserevent);
            }
        }
    }

    /**
     * @deprecated since Totara 11.0
     * @param \totara_appraisal\event\appraisal_activation $event
     */
    public static function appraisal_activation(\totara_appraisal\event\appraisal_activation $event) {
        debugging('totara_appraisal_observer::appraisal_activation has been deprecated, this functionality is now handled by the appraisals scheduled_messages task', DEBUG_DEVELOPER);
        return true;
    }

    /**
     * @deprecated since Totara 11.0
     * @param int $time current time
     */
    public static function send_scheduled($time) {
        debugging('totara_appraisal_observer::send_scheduled has been deprecated, please use appraisal::send_scheduled instead', DEBUG_DEVELOPER);
        return true;
    }

}
