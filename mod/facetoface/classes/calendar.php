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

final class calendar {

    /**
     * Add a link to the seminar event to the courses calendar.
     *
     * @param seminar_event $seminarevent
     * @param string   $calendartype Which calendar to add the event to (user, course, site)
     * @param int      $userid       Optional param for user calendars
     * @param string   $eventtype    Optional param for user calendar (booking/session)
     * @return bool
     */
    public static function add_seminar_event(\mod_facetoface\seminar_event $seminarevent, $calendartype = 'none', $userid = 0, $eventtype = 'session') {
        global $PAGE, $DB;

        $seminar = new \mod_facetoface\seminar($seminarevent->get_facetoface());

        if (empty($seminarevent->get_mintimestart())) {
            return true; //date unkown, can't add to calendar
        }

        if ($seminar->get_showoncalendar() == 0 && $seminar->get_usercalentry() == 0) {
            return true; //facetoface calendar settings prevent calendar
        }

        $description = '';
        if (!empty($seminar->get_intro())) {
            $description .= \html_writer::tag('p', clean_param($seminar->get_intro(), PARAM_CLEANHTML));
        }
        /**
         * @var mod_facetoface_renderer $seminarrenderer
         */
        $seminarrenderer = $PAGE->get_renderer('mod_facetoface');
        $description .= $seminarrenderer->render_seminar_event($seminarevent, false, true);

        $linkurl = new \moodle_url('/mod/facetoface/signup.php', array('s' => $seminarevent->get_id()));
        $linktext = get_string('signupforthissession', 'facetoface');

        if ($calendartype == 'site' && $seminar->get_showoncalendar() == F2F_CAL_SITE) {
            $courseid = SITEID;
            $description .= \html_writer::link($linkurl, $linktext);
        } else if ($calendartype == 'course' && $seminar->get_showoncalendar() == F2F_CAL_COURSE) {
            $courseid = $seminar->get_course();
            $description .= \html_writer::link($linkurl, $linktext);
        } else if ($calendartype == 'user' && $seminar->get_usercalentry()) {
            $courseid = 0;
            if ($eventtype == 'session') {
                $linkurl = new \moodle_url('/mod/facetoface/attendees/view.php', array('s' => $seminarevent->get_id()));
            }
            $description .= get_string("calendareventdescription{$eventtype}", 'facetoface', $linkurl->out());
        } else {
            return true;
        }

        $shortname = $seminar->get_shortname();
        if (empty($shortname)) {
            // Calendar-related constants
            if (!defined('CALENDAR_MAX_NAME_LENGTH')) {
                // Admins may override this in config.php if necessary.
                define('CALENDAR_MAX_NAME_LENGTH', 256);
            }
            $shortname = shorten_text($seminar->get_name(), CALENDAR_MAX_NAME_LENGTH);
        }

        // Remove all calendar events related to current session and user before adding new event to avoid duplication.
        self::remove_seminar_event($seminarevent, $courseid, $userid);

        $result = true;
        /**
         * @var seminar_session $date
         */
        foreach ($seminarevent->get_sessions() as $date) {
            $newevent = new \stdClass();
            $newevent->name = $shortname;
            $newevent->description = $description;
            $newevent->format = FORMAT_HTML;
            $newevent->courseid = $courseid;
            $newevent->groupid = 0;
            $newevent->userid = $userid;
            $newevent->uuid = "{$seminarevent->get_id()}";
            $newevent->instance = $seminar->get_id();
            $newevent->modulename = 'facetoface';
            $newevent->eventtype = "facetoface{$eventtype}";
            $newevent->timestart = $date->get_timestart();
            $newevent->timeduration = $date->get_timefinish() - $date->get_timestart();
            $newevent->visible = 1;
            $newevent->timemodified = time();

            $result = $result && $DB->insert_record('event', $newevent);
        }

        return $result;
    }

    /**
     * Update site/course and user calendar entries.
     *
     * @param seminar_event $seminarevent
     * @return bool
     */
    public static function update_entries(\mod_facetoface\seminar_event $seminarevent) {
        global $USER;

        // Do not re-create calendars as they already removed from cancelled session.
        if ((bool)$seminarevent->get_cancelledstatus()) {
            return true;
        }

        $seminar = new \mod_facetoface\seminar($seminarevent->get_facetoface());

        // Remove from all calendars.
        self::delete_user_events($seminarevent, 'booking');
        self::delete_user_events($seminarevent, 'session');
        self::remove_seminar_event($seminarevent, $seminar->get_course());
        self::remove_seminar_event($seminarevent, SITEID);

        if ($seminar->get_showoncalendar() == 0 && $seminar->get_usercalentry() == 0) {
            return true;
        }

        // Add to NEW calendartype.
        if ($seminar->get_usercalentry() > 0) {
            // Get ALL enrolled/booked users.
            $users  = facetoface_get_attendees($seminarevent->get_id());
            if (!in_array($USER->id, $users)) {
                self::add_seminar_event($seminarevent, 'user', $USER->id, 'session');
            }

            foreach ($users as $user) {
                $eventtype = $user->statuscode == \mod_facetoface\signup\state\booked::get_code() ? 'booking' : 'session';
                self::add_seminar_event($seminarevent, 'user', $user->id, $eventtype);
            }
        }

        if ($seminar->get_showoncalendar() == F2F_CAL_COURSE) {
            self::add_seminar_event($seminarevent, 'course');
        } else if ($seminar->get_showoncalendar() == F2F_CAL_SITE) {
            self::add_seminar_event($seminarevent, 'site');
        }

        return true;
    }

    /**
     *Delete all user level calendar events for a seminar event
     *
     * @param seminar_event $seminarevent Record from the facetoface_sessions table
     * @param string $eventtype Type of the event (booking or session)
     */
    public static function delete_user_events(\mod_facetoface\seminar_event $seminarevent, $eventtype) {
        global $DB;

        // Without uuid(sessionid) param, this function deletes all events(seminar with multiple events) except the last one,
        // meaning the last event (running the update calendar) will delete all previous events created just now.
        // Usercase: Seminar has 2 events and attendee signed to the 1st event.
        $whereclause = "modulename = ? AND
                        eventtype = ? AND
                        instance = ? AND
                        uuid = ?";

        $whereparams = array('facetoface', "facetoface{$eventtype}", $seminarevent->get_facetoface(), $seminarevent->get_id());

        if ('session' == $eventtype) {
            $likestr = "%attendees.php?s={$seminarevent->get_id()}%";
            $likeold = $DB->sql_like('description', '?');
            $whereparams[] = $likestr;

            $likestr = "%view.php?s={$seminarevent->get_id()}%";
            $likenew = $DB->sql_like('description', '?');
            $whereparams[] = $likestr;

            $whereclause .= " AND ($likeold OR $likenew)";
        }

        //users calendar
        $users = $DB->get_records_sql("SELECT DISTINCT userid FROM {event} WHERE $whereclause", $whereparams);
        if ($users && count($users) > 0) {
            // Delete the existing events
            $DB->delete_records_select('event', $whereclause, $whereparams);
        }

        return $users;
    }

    /**
     * Remove all entries in the course calendar which relate to this seminar event.
     *
     * @param seminar_event $seminarevent Record from the facetoface_sessions table
     * @param integer $courseid ID of the course (courseid, SITEID, 0)
     * @param integer $userid   ID of the user
     */
    public static function remove_seminar_event(\mod_facetoface\seminar_event $seminarevent, $courseid = 0, $userid = 0) {
        global $DB;

        $params = array($seminarevent->get_facetoface(), $userid, $courseid, $seminarevent->get_id());

        return $DB->delete_records_select('event', "modulename = 'facetoface' AND
                                                instance = ? AND
                                                userid = ? AND
                                                courseid = ? AND
                                                uuid = ?", $params);
    }

    /**
     * Remove all entries in the course calendar which relate to this seminar event.
     *
     * Note: the user/course ID is nominally an integer but it is not right for the
     * code to assume its value will always > 0. This is why default values for the
     * parameters are null, NOT 0. In other words, if a caller passes in a non null
     * user ID, then the assumption is the caller wants to remove calendar entries
     * for that specific userid. It is this contract that works around a problem in
     * `calendar::remove_seminar_event` - where a course/user ID is always
     * used even if it is 0.
     *
     * @param seminar_event $seminarevent record from the facetoface_sessions table.
     * @param integer $courseid identifies the specific course whose calendar entry
     *        is to be removed. If null, it is ignored.
     * @param integer $userid identifies the specific user whose calendar entry is
     *        to be removed. If null, it is ignored.
     *
     * @return boolean true if the removal succeeded.
     */
    public static function remove_all_entries(\mod_facetoface\seminar_event $seminarevent, $courseid = null, $userid = null) {
        global $DB;

        $initial = new \stdClass();
        $initial->whereClause = "modulename = 'facetoface'";
        $initial->params = array();

        $fragments = array(
            array('instance', $seminarevent->get_facetoface()),
            array('uuid',     $seminarevent->get_id()),
            array('courseid', $courseid),
            array('userid',   $userid)
        );

        $final = array_reduce($fragments,
            function (\stdClass $accumulated, array $fragment) {

                list($field, $value) = $fragment;
                if (is_null($value)) {
                    return $accumulated;
                }

                $accumulated->whereClause = sprintf('%s AND %s = ?', $accumulated->whereClause, $field);
                $accumulated->params[] = $value;

                return $accumulated;
            },

            $initial
        );

        return $DB->delete_records_select('event', $final->whereClause, $final->params);
    }

    /**
     * Get custom field filters that are currently selected in seminar settings
     *
     * @return array Array of objects if any filter is found, empty array otherwise
     */
    public static function get_customfield_filters() {
        global $DB;

        $sessfields = array();
        $roomfields = array();
        $allsearchfields = get_config(null, 'facetoface_calendarfilters');
        if ($allsearchfields) {
            $customfieldids = array('sess' => array(), 'room' => array());
            $allsearchfields = explode(',', $allsearchfields);

            foreach ($allsearchfields as $filterkey) {
                // Customfields are prefixed with room_ and sess_ strings
                // @see settings.php refer to facetoface_calendarfilters setting for details.
                if (strpos($filterkey, 'sess_') === 0) {
                    $customfieldids['sess'][] = explode('_', $filterkey)[1];
                }
                if (strpos($filterkey, 'room_') === 0) {
                    $customfieldids['room'][] = explode('_', $filterkey)[1];
                }
            }
            if (!empty($customfieldids['sess'])) {
                list($cfids, $cfparams) = $DB->get_in_or_equal($customfieldids['sess']);
                $sql = "SELECT * FROM {facetoface_session_info_field} WHERE id $cfids";
                $sessfields = $DB->get_records_sql($sql, $cfparams);
            }
            if (!empty($customfieldids['room'])) {
                list($cfids, $cfparams) = $DB->get_in_or_equal($customfieldids['room']);
                $sql = "SELECT * FROM {facetoface_room_info_field} WHERE id $cfids";
                $roomfields = $DB->get_records_sql($sql, $cfparams);
            }
        }

        return array('sess' => $sessfields, 'room' => $roomfields);
    }
}