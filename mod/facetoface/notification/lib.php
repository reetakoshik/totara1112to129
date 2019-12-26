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
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package modules
 * @subpackage facetoface
 */

use mod_facetoface\task\send_user_message_adhoc_task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/totara/message/messagelib.php');
require_once($CFG->dirroot . '/completion/data_object.php');

/**
 * Notification types
 */
define('MDL_F2F_NOTIFICATION_MANUAL',     1);
define('MDL_F2F_NOTIFICATION_SCHEDULED',  2);
define('MDL_F2F_NOTIFICATION_AUTO',       4);

/**
 * Booked recipient filters
 */
define('MDL_F2F_RECIPIENTS_ALLBOOKED',    1);
define('MDL_F2F_RECIPIENTS_ATTENDED',     2);
define('MDL_F2F_RECIPIENTS_NOSHOWS',      4);

/**
 * Notification schedule unit types
 */
define('MDL_F2F_SCHEDULE_UNIT_HOUR',     1);
define('MDL_F2F_SCHEDULE_UNIT_DAY',      2);
define('MDL_F2F_SCHEDULE_UNIT_WEEK',     4);

/**
 * Notification conditions for system generated notificaitons.
 */
define('MDL_F2F_CONDITION_BEFORE_SESSION',               1);
define('MDL_F2F_CONDITION_AFTER_SESSION',                2);
define('MDL_F2F_CONDITION_BOOKING_CONFIRMATION',         4);
define('MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION',    8);
define('MDL_F2F_CONDITION_DECLINE_CONFIRMATION',         12);
define('MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION',      16);
define('MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER',      32);
define('MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE',      64);
define('MDL_F2F_CONDITION_TRAINER_CONFIRMATION',         128);
define('MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION', 256);
define('MDL_F2F_CONDITION_TRAINER_SESSION_UNASSIGNMENT', 512);
define('MDL_F2F_CONDITION_REGISTRATION_DATE_EXPIRED',    1024);
define('MDL_F2F_CONDITION_SESSION_CANCELLATION',         2048);
define('MDL_F2F_CONDITION_RESERVATION_CANCELLED',        16384);
define('MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED',    32768);
define('MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE',         65536);
define('MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN',        131072);
define('MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS',     262144);
define('MDL_F2F_CONDITION_WAITLIST_AUTOCLEAN',           524288);
define('MDL_F2F_CONDITION_SESSION_UNDER_CAPACITY',       1048576);

/**
 * Notification sent state
 */
define('MDL_F2F_NOTIFICATION_STATE_NOT_SENT',       0);
define('MDL_F2F_NOTIFICATION_STATE_PARTIALLY_SENT', 1);
define('MDL_F2F_NOTIFICATION_STATE_FULLY_SENT',     2);

class facetoface_notification extends data_object {

    /**
     * DB Table
     * @var string $table
     */
    public $table = 'facetoface_notification';

    /**
     * Array of required table fields
     * @var array $required_fields
     */
    public $required_fields = array(
        'id', 'type', 'title', 'body', 'courseid', 'facetofaceid',
        'timemodified', 'usermodified'
    );

    /**
     * Array of text table fields
     * @var array $text_fields
     */
    public $text_fields = array('managerprefix', 'body');

    /**
     * Array of optional fields with default values - usually long text information that is not always needed.
     *
     * @access  public
     * @var     array   $optional_fields
     */
    public $optional_fields = array(
        'conditiontype' => null,
        'scheduleunit' => null,
        'scheduleamount' => null,
        'scheduletime' => null,
        'ccmanager' => 0,
        'managerprefix' => null,
        'booked' => 0,
        'waitlisted' => 0,
        'cancelled' => 0,
        'requested' => 0,
        'status' => 0,
        'issent' => 0,
        'templateid' => 0
    );

    // Required table fields.
    public $id;

    public $type = MDL_F2F_NOTIFICATION_MANUAL;

    public $title;

    public $body;

    public $courseid;

    public $facetofaceid;

    public $timemodified;

    public $usermodified;

    // Optional table fields.
    public $conditiontype;

    public $scheduleunit;

    public $scheduleamount;

    public $scheduletime;

    public $ccmanager;

    public $managerprefix;

    public $booked;

    public $waitlisted;

    public $cancelled;

    public $templateid;

    public $status;

    public $issent;

    private $_event;

    private $_facetoface;

    private $_ical_attachment = null;

    /**
     * Finds and returns a data_object instance based on params.
     * @static static
     *
     * @param array $params associative arrays varname=>value
     * @return object data_object instance or false if none found.
     */
    public static function fetch($params) {
        return self::fetch_helper('facetoface_notification', __CLASS__, $params);
    }


    /**
     * Save to database
     *
     * @access  public
     * @return  bool
     */
    public function save() {
        global $USER, $DB;

        $no_zero = array('conditiontype', 'scheduleunit', 'scheduleamount', 'scheduletime');
        foreach ($no_zero as $nz) {
            if (empty($this->$nz)) {
                $this->$nz = null;
            }
        }

        // Calculate scheduletime
        if ($this->scheduleunit) {
            $this->scheduletime = $this->_get_timestamp();
        }

        // Handle optional templateid as it cannot be null.
        $this->templateid = isset($this->templateid) ? $this->templateid : 0;

        // Set up modification data
        $this->usermodified = $USER->id;
        $this->timemodified = time();

        // Do not allow duplicates for auto notifications.
        if (!$this->id && $this->type == MDL_F2F_NOTIFICATION_AUTO) {
            $exist = $DB->get_record('facetoface_notification', array(
                'facetofaceid' => $this->facetofaceid,
                'type' => $this->type,
                'conditiontype' => $this->conditiontype
            ));
            if ($exist) {
                debugging("Attempted duplication of seminar auto notification", DEBUG_DEVELOPER);
                $this->id = $exist->id;
            }
        }

        if ($this->id) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    /**
    * Delete notification and any associated sent message data.
    *
    * @access public
    * @return bool
    */
    public function delete() {
        global $DB;
        //Delete message sent and history data.
        $DB->delete_records('facetoface_notification_sent', array('notificationid' => $this->id));
        $DB->delete_records('facetoface_notification_hist', array('notificationid' => $this->id));
        // Call main delete function in parent data_object class.
        parent::delete();
    }

    /**
     * Get timestamp from schedule data
     *
     * @access  private
     * @return  int
     */
    private function _get_timestamp() {
        switch ($this->scheduleunit) {
            case MDL_F2F_SCHEDULE_UNIT_HOUR:
                $unit = 60*60;
                break;

            case MDL_F2F_SCHEDULE_UNIT_DAY:
                $unit = 60*60*24;
                break;

            case MDL_F2F_SCHEDULE_UNIT_WEEK:
                $unit = 60*60*24*7;
                break;
        }

        return $unit * $this->scheduleamount;
    }


    /**
     * Get recipients list
     *
     * @access  private
     * @param   int     $sessionid  (optional)
     * @return  object|false    Recordset or false on error
     */
    private function _get_recipients($sessionid = null) {
        global $DB;

        // Generate WHERE-clause
        $status = array();
        if ($this->booked) {
            switch ((int) $this->booked) {
                case MDL_F2F_RECIPIENTS_ALLBOOKED:
                    $status = array_keys(\mod_facetoface\attendees_list_helper::get_status(true));
                    break;

                case MDL_F2F_RECIPIENTS_ATTENDED:
                    $status[] = \mod_facetoface\signup\state\fully_attended::get_code();
                    break;

                case MDL_F2F_RECIPIENTS_NOSHOWS:
                    $status[] = \mod_facetoface\signup\state\no_show::get_code();
                    break;
            }
        }

        if ($this->waitlisted) {
            $status[] = \mod_facetoface\signup\state\waitlisted::get_code();
        }

        if ($this->cancelled) {
            $status[] = \mod_facetoface\signup\state\user_cancelled::get_code();
        }

        if ($this->requested) {
            $status[] = \mod_facetoface\signup\state\requested::get_code();
            $status[] = \mod_facetoface\signup\state\requestedadmin::get_code();
        }

        $where = 'f.id = ? ';
        $params = array($this->facetofaceid);

        $statussql = '';
        $statusparams = array();

        if ($status) {
            list($statussql, $statusparams) = $DB->get_in_or_equal($status);
            $where .= ' AND sis.statuscode ' . $statussql;
            $params = array_merge($params, $statusparams);
        }

        if ($sessionid) {
            $where .= ' AND s.id = ? ';
            $params[] = $sessionid;
        }

        $where .= ' AND NOT EXISTS
            (SELECT id FROM
               {facetoface_notification_sent} ns
             WHERE
                 ns.userid = u.id
             AND ns.sessionid = s.id
             AND ns.notificationid = ?
            ) ';
        $params[] = $this->id;

        if (($this->type == MDL_F2F_NOTIFICATION_SCHEDULED) && ($this->conditiontype == MDL_F2F_CONDITION_BEFORE_SESSION) && isset($this->scheduletime)) {
            if ($status) {
                // For each signupid, we get the status code of the signup_status that was the last one before the scheduled time for sending the notification.
                // Then we check that this is in $statusparams.

                // We need the scheduled time that the notifications were supposed to go out at
                $scheduledtimesql = '((SELECT MIN(fsd.timestart)
                                       FROM   {facetoface_sessions_dates} fsd
                                       WHERE  fsd.sessionid = s.id) - ?)';

                // We find the latest timecreated that was less than the scheduled time
                $timecreatedsql = '(SELECT MAX(fss1.timecreated)
                                    FROM   {facetoface_signups_status} fss1
                                    WHERE  fss1.signupid = si.id
                                    AND    fss1.timecreated < '.$scheduledtimesql.')';

                // We get the status code that's in the same record as the above timestamp.
                // We use Max as booked and approved statuses can be created at the same time and this will favour booked.
                // Other statuses created at the same time are unlikely,
                // but max will prevent the subquery returning multiple values.
                $statuscodesql = '(SELECT MAX(fss2.statuscode)
                                   FROM   {facetoface_signups_status} fss2
                                   WHERE  fss2.signupid = si.id
                                   AND    fss2.timecreated = '.$timecreatedsql.')';

                // We check that the status code is a status that this notification should be sent out for.
                $where .= ' AND '.$statuscodesql.' '.$statussql;

                $params[] = $this->scheduletime;
                $params = array_merge($params, $statusparams);
            } else {
                // If statuses aren't specified. just check if the earliest status was before the scheduled time for sending the notification.
                $where .= ' AND sis.timecreated <
                                ((SELECT MIN(fsd.timestart)
                                  FROM   {facetoface_sessions_dates} fsd
                                  WHERE  fsd.sessionid = s.id) - ?) ';
                $params[] = $this->scheduletime;
            }
        }

        // Generate SQL
        $sql = '
            SELECT
                u.*,
                s.id AS sessionid
            FROM
                {user} u
            INNER JOIN
                {facetoface_signups} si
             ON si.userid = u.id
            INNER JOIN
                {facetoface_signups_status} sis
             ON si.id = sis.signupid
            AND sis.superceded = 0
            INNER JOIN
                {facetoface_sessions} s
             ON s.id = si.sessionid
            INNER JOIN
                {facetoface} f
             ON s.facetoface = f.id
            WHERE ' . $where . '
         ORDER BY u.id';

        $recordset = $DB->get_recordset_sql($sql, $params);

        return $recordset;
    }


    /**
     * Check for scheduled notifications and send
     *
     * @access  public
     * @return  void
     */
    public function send_scheduled() {
        global $CFG, $DB;

        $notificationdisable = get_config(null, 'facetoface_notificationdisable');
        if (!empty($notificationdisable)) {
            return false;
        }

        if (!PHPUNIT_TEST) {
            mtrace("Checking for sessions to send notification to\n");
        }

        // Find due scheduled notifications
        $sql = '
            SELECT
                s.id,
                s.registrationtimefinish,
                sd.timestart,
                sd.timefinish
            FROM
                {facetoface_sessions} s
            INNER JOIN
                (
                    SELECT
                        sessionid,
                        MAX(timefinish) AS timefinish,
                        MIN(timestart) AS timestart
                    FROM
                        {facetoface_sessions_dates}
                    GROUP BY
                        sessionid
                ) sd
             ON sd.sessionid = s.id
             WHERE s.facetoface = ?
          ORDER BY s.id ASC, sd.timestart ASC
        ';

        $recordset = $DB->get_recordset_sql($sql, array($this->facetofaceid));
        if (!$recordset) {
            if (!PHPUNIT_TEST) {
                mtrace("No sessions found for scheduled notification\n");
            }
            return false;
        }

        $time = time();
        $sent = 0;
        $sessions = array();
        foreach ($recordset as $session) {
            // Check if we have already processed and found at least one active signup for this session that needs sending.
            if (isset($sessions[$session->id])) {
                continue;
            }
            // Check if they aren't ready to have their notification sent
            switch ($this->conditiontype) {
                case MDL_F2F_CONDITION_BEFORE_SESSION:
                    if ($session->timestart < $time ||
                       ($session->timestart - $this->scheduletime) > $time) {
                        continue 2;
                    }
                    break;
                case MDL_F2F_CONDITION_AFTER_SESSION:
                    if ($session->timefinish > $time ||
                       ($session->timefinish + $this->scheduletime) > $time) {
                        continue 2;
                    }
                    break;
                case MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS:
                    if ($session->registrationtimefinish < $time ||
                       ($session->registrationtimefinish - $this->scheduletime) > $time) {
                        continue 2;
                    }
                    break;
                default:
                    // Unexpected data, return and continue with next notification
                    return;
            }

            $sent++;
            if (!isset($sessions[$session->id])) {
                $sessions[$session->id] = $session->id;
            }
        }

        if (count($sessions) > 0) {
            foreach ($sessions as $sessionid => $session) {
                $this->send_to_users($sessionid);
            }
            if (!PHPUNIT_TEST) {
                mtrace("Sent scheduled notifications for {$sent} session(s)\n");
            }
        } else if (!PHPUNIT_TEST) {
            mtrace("No scheduled notifications need to be sent at this time\n");
        }

        $recordset->close();
    }

    /**
     * Sends messages for face to face sessions where registration has expired
     *
     * @access  public
     * @return  void
     * @param \stdClass $notif
     */
    public function send_notification_registration_expired($notif) {
        global $CFG, $DB;

        if (!empty(get_config(null, 'facetoface_notificationdisable'))) {
            return;
        }
        if (!PHPUNIT_TEST) {
            mtrace(get_string('signupexpired', 'facetoface'));
        }

        if (empty($CFG->facetoface_session_rolesnotify)) {
            // No roles set.
            return;
        }

        $roleids = explode(",", $CFG->facetoface_session_rolesnotify);
        list($sqlin, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
        $params["sessionid"] = $notif->id;

        $sql = "SELECT u.*
                FROM {user} u
                JOIN {facetoface_session_roles} sr
                    ON u.id = sr.userid
                WHERE sr.roleid $sqlin
                AND sr.sessionid = :sessionid
                AND u.suspended = 0";
        $users = $DB->get_records_sql($sql, $params);

        if (!$users) {
            return;
        }

        $notificationparams = array(
            "facetofaceid" => $notif->facetoface,
            "type" => MDL_F2F_NOTIFICATION_AUTO,
            "conditiontype" => MDL_F2F_CONDITION_REGISTRATION_DATE_EXPIRED
        );

        $facetoface = $DB->get_record('facetoface', array('id' => $notif->facetoface));

        foreach ($users as $user) {

            $notice = new facetoface_notification($notificationparams);

            // Check notification hasn't already need sent.
            $notificationhistory = $DB->get_records('facetoface_notification_sent', array('notificationid' => $notice->id, 'sessionid' => $notif->id, 'userid' => $user->id));
            if (count($notificationhistory)) {
                // Notification has already  been sent.
                continue;
            }

            $notice->set_facetoface($facetoface);
            $notice->_sessions = facetoface_get_sessions($facetoface->id);
            $notice->set_newevent($user, $notif->id);
            $notice->send_to_user($user, $notif->id);
        }
    }

    /**
     * Sends the Event under capacity notification to users
     *
     * @param $session
     */
    public function send_notification_session_under_capacity($session) {
        global $DB, $CFG;

        $roles = $CFG->facetoface_session_rolesnotify;

        $booked = facetoface_get_num_attendees($session->id, \mod_facetoface\signup\state\waitlisted::get_code());
        if ($booked >= $session->mincapacity) {
            return;
        }

        // No cutoff period means the notify bookings email checkbox was not checked.
        if (empty($session->cutoff)) {
            return;
        }

        // We've found a session that has not reached the minimum bookings by the cut-off - time to send out emails.
        $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));

        if (CLI_SCRIPT && !PHPUNIT_TEST) {
            $info = (object)array(
                'name' => format_string($facetoface->name),
                'capacity' => $session->capacity,
                'mincapacity' => $session->mincapacity,
                'booked' => $booked,
            );
            mtrace("Facetoface '{$info->name}' in course {$facetoface->course} is under minimum bookings - {$info->booked}/{$info->capacity} (min capacity {$info->mincapacity}) - emailing session roles.");
        }

        // Get all the users who need to receive the under capacity warning.
        if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $facetoface->course)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
        }
        $modcontext = context_module::instance($cm->id);

        // Note: remove the true to limit to users with the roles within the module.
        $recipients = get_role_users(explode(',', $roles), $modcontext, true, 'u.*');

        $notificationparams = array(
            "facetofaceid" => $session->facetoface,
            "type" => MDL_F2F_NOTIFICATION_AUTO,
            "conditiontype" => MDL_F2F_CONDITION_SESSION_UNDER_CAPACITY,
        );

        // And send them the notification.
        foreach ($recipients as $recipient) {
            $notice = new facetoface_notification($notificationparams);

            // Check notification hasn't already need sent.
            $notificationhistory = $DB->get_record('facetoface_notification_sent', array('notificationid' => $notice->id, 'sessionid' => $session->id, 'userid' => $recipient->id));
            if ($notificationhistory != null) {
                // Notification has already  been sent.
                continue;
            }

            $notice->set_facetoface($facetoface);
            $notice->_sessions = facetoface_get_sessions($facetoface->id);
            $notice->set_newevent($recipient, $session->id);
            $notice->send_to_user($recipient, $session->id);
        }
    }

    /**
     * Send to all matching users
     *
     * @access  public
     * @param   int     $sessionid      (optional)
     * @return  void
     */
    public function send_to_users($sessionid = null) {
        global $DB;

        $notificationdisable = get_config(null, 'facetoface_notificationdisable');
        if (!empty($notificationdisable)) {
            return false;
        }

        // Get recipients
        $recipients = $this->_get_recipients($sessionid);

        if (!$recipients->valid()) {
            if (!CLI_SCRIPT) {
                echo get_string('norecipients', 'facetoface') . "\n";
            }
        } else {
            $count = 0;
            foreach ($recipients as $recipient) {
                $count++;
                $this->set_newevent($recipient, $recipient->sessionid);
                $senttouser = $this->send_to_user($recipient, $recipient->sessionid);
                // If the message was successfully sent to the recipient then we want to ensure that it gets sent to the manager and
                // any third party users.
                // If the message was not successfully sent then we do not want to send it to the manager or third party as the
                // notification will be queued and sent again in the future.
                if ($senttouser) {
                    $this->send_to_manager($recipient, $recipient->sessionid);
                    $this->send_to_thirdparty($recipient, $recipient->sessionid);
                }
                $this->delete_ical_attachment();
            }
            if (!CLI_SCRIPT) {
                echo get_string('sentxnotifications', 'facetoface', $count) . "\n";
            }

            $recipients->close();
        }
    }

    /**
     * Set face-to-face iCal attachment object
     *
     * @param $ical_attachment
     */
    public function set_ical_attachment($ical_attachment) {
        $this->_ical_attachment = $ical_attachment;
    }

    /**
     * Generate and add face-to-face iCal attachment
     *
     * @param \stdClass $user User object
     * @param \stdClass $session Session object
     * @param int $method iCal attachment method
     * @param \stdClass|array|null $dates Array of session dates, single session date or null to get dates from session object
     * @param \stdClass|array $olddates Array or a single date to cancel
     */
    public function add_ical_attachment($user, $session, $method = MDL_F2F_INVITE, $dates = null, $olddates = []) {
        if (is_null($dates)) {
            $dates = $session->sessiondates;
        }

        $this->set_ical_attachment(
            \mod_facetoface\messaging::generate_ical($this->_facetoface, $session, $method, $user, $dates, $olddates, $this->_event->fullmessagehtml));

        $this->add_ical_attachment_data();
    }

    /**
     * Add iCal attachment if set
     */
    private function add_ical_attachment_data() {
        $ical_uids = null;
        $ical_method = '';

        if (!empty($this->_ical_attachment) && $this->conditiontype != MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION) {
            $this->_event->attachment = $this->_ical_attachment->file;

            if ($this->conditiontype == MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION ||
                $this->conditiontype == MDL_F2F_CONDITION_DECLINE_CONFIRMATION) {
                $this->_event->attachname = 'cancel.ics';
            } else {
                $this->_event->attachname = 'invite.ics';
            }

            $ical_content = $this->_ical_attachment->content;

            if (!empty($ical_content)) {
                preg_match_all('/UID:([^\r\n ]+)/si', $ical_content, $matches);
                $ical_uids = $matches[1];
                preg_match('/METHOD:([a-z]+)/si', $ical_content, $matches);
                $ical_method = $matches[1];
            }
        }
        $this->_event->ical_uids  = $ical_uids;
        $this->_event->ical_method  = $ical_method;
    }

    public function set_facetoface($facetoface) {
        $this->_facetoface = $facetoface;
    }

    public function delete_ical_attachment() {
        if (!empty($this->_ical_attachment)) {
            $this->_ical_attachment->file->delete();
        }
    }

    /**
     * Send to a signup user using adhoc task
     *
     * @param stdClass $user User object
     * @param int $sessionid
     * @param stdClass $sessiondate The specific sessiondate which this message is for.
     * @param array $icaldata   Additional data required to regenerate attachment in adhoc task
     * @return boolean true if message sent
     */
    public function send_to_user($user, $sessionid, $sessiondate = null, array $icaldata = []) {
        global $CFG;

        // Check that the notification is enabled and that all facetoface notifications are not disabled.
        if (!$this->status || !empty($CFG->facetoface_notificationdisable)) {
            return false;
        }

        $this->send_adhoc_message($this->_event, $user, $sessionid, $icaldata, $sessiondate, true);

        return true;
    }

    /**
     * Create a new event object
     *
     * @access  public
     * @param   object  $user       User object
     * @param   int     $sessionid
     * @param   int     $sessiondate The specific sessiondate which this message is for.
     * @param   object  $fromuser User object describing who the email is from.
     * @return  object
     */
    public function set_newevent($user, $sessionid, $sessiondate = null, $fromuser = null) {
        global $CFG, $USER, $DB;

        // Load facetoface object
        if (empty($this->_facetoface)) {
            $this->_facetoface = $DB->get_record_sql("SELECT f2f.*, c.fullname AS coursename
                FROM {facetoface} f2f
                INNER JOIN {course} c ON c.id = f2f.course
                WHERE f2f.id = ?", array($this->facetofaceid));
        }
        if (!isset($this->_facetoface->coursename)) {
            $course = $DB->get_record('course', array('id' => $this->_facetoface->course), 'fullname');
            $this->_facetoface->coursename = $course->fullname;
        }

        // Load session object
        if (empty($this->_sessions[$sessionid])) {
            $this->_sessions[$sessionid] = facetoface_get_session($sessionid);
        }
        $this->_sessions[$sessionid]->course = $this->_facetoface->course;
        if (!empty($sessiondate)) {
            $this->_sessions[$sessionid]->sessiondates = array($sessiondate);
        }

        if (empty($fromuser)) {
            // NOTE: this is far from optimal because nobody might be logged in.
            $fromuser = $USER;
        }

        if (empty($this->_facetoface->approvalrole)) {
            $this->_facetoface->approvalrole = (int)$DB->get_field('facetoface', 'approvalrole', array('id' => $this->_facetoface->id));
        }

        $fromuser = \mod_facetoface\facetoface_user::get_facetoface_user();
        // We need a real user id to display an attendee name in task/alert report builder.
        $fromuser->realid = $user->id;

        $options = array('context' => context_course::instance($this->_facetoface->course));
        $coursename = format_string($this->_facetoface->coursename, true, $options);

        // Note: $$text was failing randomly in PHP 5.6.0 me with undefined variable for some weird reason...
        $subject = facetoface_message_substitutions(
            $this->title,
            $coursename,
            $this->_facetoface->name,
            $user,
            $this->_sessions[$sessionid],
            $sessionid,
            $this->_facetoface->approvalrole
        );
        $body = facetoface_message_substitutions(
            $this->body,
            $coursename,
            $this->_facetoface->name,
            $user,
            $this->_sessions[$sessionid],
            $sessionid,
            $this->_facetoface->approvalrole
        );
        $managerprefix = facetoface_message_substitutions(
            $this->managerprefix,
            $coursename,
            $this->_facetoface->name,
            $user,
            $this->_sessions[$sessionid],
            $sessionid,
            $this->_facetoface->approvalrole
        );
        $plaintext = format_text_email($body, FORMAT_HTML);

        $this->_event = new stdClass();
        $this->_event->component   = 'totara_message';
        $this->_event->name        = 'alert';
        $this->_event->courseid    = $this->_facetoface->course;
        $this->_event->userto      = $user;
        $this->_event->userfrom    = $fromuser;
        $this->_event->notification = 1;
        $this->_event->roleid      = $CFG->learnerroleid;
        $this->_event->subject     = $subject;
        $this->_event->fullmessage       = $plaintext;
        $this->_event->fullmessageformat = FORMAT_PLAIN;
        $this->_event->fullmessagehtml   = $body;
        $this->_event->smallmessage      = $plaintext;

        $this->_event->icon        = 'facetoface-regular';

        $plaintext = format_text_email($managerprefix, FORMAT_HTML);
        $this->_event->manager = new stdClass();
        $this->_event->manager->fullmessage       = $plaintext;
        $this->_event->manager->fullmessagehtml   = $managerprefix;
        $this->_event->manager->smallmessage      = $plaintext;

        // Speciality icons.
        if ($this->type == MDL_F2F_NOTIFICATION_AUTO) {
            switch ($this->conditiontype) {
            case MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION:
                $this->_event->icon = 'facetoface-remove';
                break;
            case MDL_F2F_CONDITION_BOOKING_CONFIRMATION:
                $this->_event->icon = 'facetoface-add';
                break;
            case MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE:
                $this->_event->icon = 'facetoface-update';
                break;
            case MDL_F2F_CONDITION_DECLINE_CONFIRMATION://KINEO #198 ad decline message
                $this->_event->icon = 'facetoface-decline';
                break;
            }
        }

        // Override normal email processor behaviour in order to handle attachments.
        $this->_event->sendemail = TOTARA_MSG_EMAIL_YES;
        $this->_event->msgtype   = TOTARA_MSG_TYPE_FACE2FACE;
        $this->_event->urgency   = TOTARA_MSG_URGENCY_NORMAL;

        // This is needed here to preserve the original behaviour of this method.
        $this->_event->ical_uids  = null;
        $this->_event->ical_method  = '';
        if (!is_null($this->_ical_attachment)) {
            $this->add_ical_attachment_data();
        }
    }

    /**
     * Send to a manager through adhoc task
     *
     * @access  public
     * @param   object  $user       User object
     * @param   int     $sessionid
     * @return  void
     */
    public function send_to_manager($user, $sessionid) {
        global $CFG, $DB;

        // Check that the notification is enabled and that all facetoface notifications are not disabled.
        if (!$this->status || !empty($CFG->facetoface_notificationdisable)) {
            return;
        }

        $params = array('userid'=>$user->id, 'sessionid'=>$sessionid);
        $jobassignmentid = $DB->get_field('facetoface_signups', 'jobassignmentid', $params);
        $managers = facetoface_get_session_managers($user->id, $sessionid, $jobassignmentid);

        if ($this->ccmanager && !empty($managers)) {
            foreach ($managers as $manager) {
                $event = clone $this->_event;

                $event->userto = $manager;
                $event->roleid = $CFG->managerroleid;
                // Do not send iCal attachment.
                $event->attachment = $event->attachname = null;

                if ($this->conditiontype == MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER ||
                    $this->conditiontype == MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN) {
                    // Do the facetoface workflow event.
                    $strmgr = get_string_manager();
                    $onaccept = new stdClass();
                    $onaccept->action = 'facetoface';
                    $onaccept->text = $strmgr->get_string('approveinstruction', 'facetoface', null, $manager->lang);
                    $onaccept->data = array('userid' => $user->id, 'session' => $this->_sessions[$sessionid], 'facetoface' => $this->_facetoface);
                    $event->onaccept = $onaccept;
                    $onreject = new stdClass();
                    $onreject->action = 'facetoface';
                    $onreject->text = $strmgr->get_string('rejectinstruction', 'facetoface', null, $manager->lang);
                    $onreject->data = array('userid' => $user->id, 'session' => $this->_sessions[$sessionid], 'facetoface' => $this->_facetoface);
                    $event->onreject = $onreject;

                    $event->name = 'task';
                    $this->send_adhoc_manager_message($event, $user, $sessionid);
                } else {
                    $event->name = 'alert';
                    $this->send_adhoc_manager_message($event, $user, $sessionid);
                }
            }
        }
    }

    /**
     * Send to a third party
     *
     * @access  public
     * @param   object  $user       User object
     * @param   int     $sessionid
     * @return  void
     */
    public function send_to_thirdparty($user, $sessionid) {
        global $CFG;

        // Check that the notification is enabled and that all facetoface notifications are not disabled.
        if (!$this->status || !empty($CFG->facetoface_notificationdisable)) {
            return;
        }

        // Third-party notification.
        if (!empty($this->_facetoface->thirdparty) && (!empty($this->_sessions[$sessionid]->sessiondates)
                || !empty($this->_facetoface->thirdpartywaitlist))) {
            $event = clone $this->_event;
            $event->attachment = null; // Leave out the ical attachments in the third-parties notification.

            $recipients = array_map('trim', explode(',', $this->_facetoface->thirdparty));
            foreach ($recipients as $recipient) {
                $event->userto = \totara_core\totara_user::get_external_user($recipient);
                $this->send_adhoc_manager_message($event, $user, $sessionid);
            }
        }
    }

    /**
     * Send to users with the appropriate session role to approve
     * @deprecated since Totara 12
     *
     * @access  public
     * @param   object     $facetoface  The facetoface object
     * @param   object     $session     The session object
     * @return  boolean
     */
    public function send_to_roleapprovers($facetoface, $session) {
        debugging("Method send_to_roleapprovers is deprecated. Use send_to_roleapprovers_adhoc instead", DEBUG_DEVELOPER);
        if ($this->conditiontype != MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE) {
            return false;
        }

        $event = clone $this->_event;
        $event->roleid = $facetoface->approvalrole;
        $event->fullmessage       = $event->manager->fullmessage . $event->fullmessage;
        $event->fullmessagehtml   = $event->manager->fullmessagehtml . $event->fullmessagehtml;
        $event->smallmessage      = $event->manager->smallmessage . $event->smallmessage;
        $event->attachment = null; // Leave out the ical attachments for roleapprovers.

        $event->name = 'task';

        // Send the booking request to all users with the approvalrole set in the session.
        $sessionroles = facetoface_get_trainers($session->id, $facetoface->approvalrole);
        if (!empty($sessionroles)) {
            foreach ($sessionroles as $recipient) {
                if (!empty($recipient)) {
                    $event->userto = core_user::get_user($recipient->id);
                    message_send($event);
                }
            }
        }

        return true;
    }

    /**
     * Send to users with the appropriate session role to approve though adhoc task
     * Rewritten send_to_roleapprovers due to incompatible signature and data duplication in that function.
     *
     * @param object $user User object
     * @param int $sessionid
     * @return boolean
     */
    public function send_to_roleapprovers_adhoc(stdClass $user, int $sessionid) {
        if ($this->conditiontype != MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE) {
            return false;
        }

        $event = clone $this->_event;
        $event->roleid = $this->_facetoface->approvalrole;
        $event->attachment = null; // Leave out the ical attachments for roleapprovers.

        $event->name = 'task';

        // Send the booking request to all users with the approvalrole set in the session.
        $sessionroles = facetoface_get_trainers($sessionid, $this->_facetoface->approvalrole);
        if (!empty($sessionroles)) {
            foreach ($sessionroles as $recipient) {
                if (!empty($recipient)) {
                    $event->userto = core_user::get_user($recipient->id);
                    $this->send_adhoc_manager_message($event, $user, $sessionid);
                }
            }
        }

        return true;
    }

    /**
     * Send message as adhoc task to improve performance and allow user specific formatting.
     * @param stdClass $event Event message instance
     * @param stdClass $signupuser User that is subject of booking
     * @param int $sessionid Event id
     * @param array $icaldata Additional data required to regenerate attachment in adhoc task
     * @param stdClass
     */
    public function send_adhoc_message(stdClass $event, stdClass $signupuser, int $sessionid, array $icaldata = [],
                                       stdClass $sessiondate = null, bool $addhistory = false) {
        $data = new stdClass;
        $data->id = $this->id;
        $data->signupuser = $signupuser;
        $data->sessionid = $sessionid;
        $data->facetoface = $this->_facetoface;
        $data->event = $event;
        $data->sessions = $this->_sessions;
        $data->title = $this->title;
        $data->body = $this->body;
        $data->managerprefix = $this->managerprefix;
        $data->conditiontype = $this->conditiontype;
        $data->icaldata = $icaldata;
        $data->sessiondate = $sessiondate;
        $data->addhistory = $addhistory;
        $serialdata = serialize($data);

        $adhocktask = new send_user_message_adhoc_task();
        $adhocktask->set_custom_data($serialdata);
        $adhocktask->set_component('mod_facetoface');
        \core\task\manager::queue_adhoc_task($adhocktask);
    }

    /**
     * Send message as adhov task with manager prefix
     * @param stdClass $event
     * @param stdClass $signupuser
     * @param int $sessionid
     */
    public function send_adhoc_manager_message(stdClass $event, stdClass $signupuser, int $sessionid) {
        $event->addmanagerprefix = true;
        $this->send_adhoc_message($event, $signupuser, $sessionid);
    }

    /**
     * Send to users set as sitewide or facetoface approvers
     *
     * @deprecated since Totara 12
     * @access  public
     * @param   object     $facetoface  The facetoface object
     * @return  boolean
     */
    public function send_to_adminapprovers($facetoface) {
        debugging("Method send_to_adminapprovers is deprecated. Use send_to_adminapprovers_adhoc instead", DEBUG_DEVELOPER);
        if ($this->conditiontype != MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN) {
            return false;
        }

        $event = clone $this->_event;
        $event->fullmessage       = $event->manager->fullmessage . $event->fullmessage;
        $event->fullmessagehtml   = $event->manager->fullmessagehtml . $event->fullmessagehtml;
        $event->smallmessage      = $event->manager->smallmessage . $event->smallmessage;
        $event->attachment = null; // Leave out the ical attachments for adminapprovers.

        $event->name = 'task';

        // Send the booking request to all site & activity level adminapprovers.
        $systemapprovers = get_users_from_config(get_config(null, 'facetoface_adminapprovers'), 'mod/facetoface:approveanyrequest');
        foreach ($systemapprovers as $approver) {
            if (!empty($approver)) {
                $event->userto = $approver;
                message_send($event);
            }
        }

        $activityapprovers = explode(',', $facetoface->approvaladmins);
        foreach ($activityapprovers as $approver) {
            if (!empty($approver)) {
                $event->userto = core_user::get_user($approver);
                message_send($event);
            }
        }

        return true;
    }

    /**
     * Send to users with the appropriate session role to approve though adhoc task
     * Rewritten send_to_adminapprovers due to incompatible signature and data duplication in that function.
     *
     * @param object $user User object
     * @param int $sessionid
     */
    public function send_to_adminapprovers_adhoc(stdClass $user, int $sessionid) {
        if ($this->conditiontype != MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN) {
            return false;
        }

        $event = clone $this->_event;
        $event->attachment = null; // Leave out the ical attachments for adminapprovers.
        $event->name = 'task';

        // Send the booking request to all site & activity level adminapprovers.
        $systemapprovers = get_users_from_config(get_config(null, 'facetoface_adminapprovers'), 'mod/facetoface:approveanyrequest');
        foreach ($systemapprovers as $approver) {
            if (!empty($approver)) {
                $event->userto = $approver;
                $this->send_adhoc_manager_message($event, $user, $sessionid);
            }
        }

        $activityapprovers = explode(',', $this->_facetoface->approvaladmins);
        foreach ($activityapprovers as $approver) {
            if (!empty($approver)) {
                $event->userto = core_user::get_user($approver);
                $this->send_adhoc_manager_message($event, $user, $sessionid);
            }
        }
    }

    /**
     * Get desciption of notification condition
     *
     * @access  public
     * @return  string
     */
    public function get_condition_description() {
        $html = '';

        $time = $this->scheduleamount;
        if ($time == 1) {
            $unit = get_string('schedule_unit_'.$this->scheduleunit.'_singular', 'facetoface');
        } elseif ($time > 1) {
            $unit = get_string('schedule_unit_'.$this->scheduleunit, 'facetoface', $time);
        }

        // Generate note
        switch ($this->type) {
            case MDL_F2F_NOTIFICATION_MANUAL:

                if ($this->status) {
                    $html .= get_string('occuredonx', 'facetoface', userdate($this->timemodified));
                } else {
                    $html .= get_string('occurswhenenabled', 'facetoface');
                }
                break;

            case MDL_F2F_NOTIFICATION_SCHEDULED:
            case MDL_F2F_NOTIFICATION_AUTO:

                switch ($this->conditiontype) {
                    case MDL_F2F_CONDITION_BEFORE_SESSION:
                        $html .= get_string('occursxbeforesession', 'facetoface', $unit);
                        break;
                    case MDL_F2F_CONDITION_AFTER_SESSION:
                        $html .= get_string('occursxaftersession', 'facetoface', $unit);
                        break;
                    case MDL_F2F_CONDITION_BOOKING_CONFIRMATION:
                        $html .= get_string('occurswhenuserbookssession', 'facetoface');
                        break;
                    case MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION:
                        $html .= get_string('occurswhenusersbookingiscancelled', 'facetoface');
                        break;
                    case MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION:
                        $html .= get_string('occurswhenuserwaitlistssession', 'facetoface');
                        break;
                    case MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER:
                    case MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE:
                    case MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN:
                        $html .= get_string('occurswhenuserrequestssessionwithmanagerapproval', 'facetoface');
                        break;
                    case MDL_F2F_CONDITION_DECLINE_CONFIRMATION:
                        $html .= get_string('occurswhenuserrequestssessionwithmanagerdecline', 'facetoface');
                        break;
                }

                break;
        }

        return $html;
    }


    /**
     * Get desciption of recipients
     *
     * @access  public
     * @return  string
     */
    public function get_recipient_description() {
        $recips = array();
        if ($this->booked) {
            switch ($this->booked) {
                case MDL_F2F_RECIPIENTS_ALLBOOKED:
                    $recips[] = get_string('recipients_allbooked', 'facetoface');
                    break;
                case MDL_F2F_RECIPIENTS_ATTENDED:
                    $recips[] = get_string('recipients_attendedonly', 'facetoface');
                    break;
                case MDL_F2F_RECIPIENTS_NOSHOWS:
                    $recips[] = get_string('recipients_noshowsonly', 'facetoface');
                    break;
            }
        }

        if (!empty($this->waitlisted)) {
            $recips[] = get_string('status_waitlisted', 'facetoface');
        }

        if (!empty($this->cancelled)) {
            $recips[] = get_string('status_user_cancelled', 'facetoface');
        }

        return implode(', ', $recips);
    }


    /**
     * Is this notification frozen (uneditable) or not?
     *
     * It should be if it is an existing, enabled manual notification
     *
     * @access  public
     * @return  boolean
     */
    public function is_frozen() {
        return $this->id && $this->status && $this->type == MDL_F2F_NOTIFICATION_MANUAL;
    }

    /**
     * Sets notification object properties from the given user input fields.
     * Throws an exception for any invalid data.
     *
     * @param facetoface_notification $instance
     * @param stdClass $params
     * @throws moodle_exception
     */
    public static function set_from_form(facetoface_notification $instance, stdClass $params) {
        parent::set_properties($instance, $params);
    }

    /**
     * Return true if at least one notification has auto duplicate
     * This should not normally happen, but in extremily rare cases clients can get their auto notifications duplicated in
     * facetoface session. This is part of code that allows to deal with this situation.
     *
     * @param int $facetofaceid
     */
    public static function has_auto_duplicates($facetofaceid) {
        global $DB;
        $notifications = $DB->get_records('facetoface_notification', array(
            'facetofaceid' => $facetofaceid,
            'type' => MDL_F2F_NOTIFICATION_AUTO
        ));

        $list = array();
        foreach ($notifications as $notification) {
            if (!isset($list[$notification->conditiontype])) {
                $list[$notification->conditiontype] = true;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * All notification references list.
     *
     * @return array notification references
     */
    public static function get_references() {
        return array(
            'reminder' => MDL_F2F_CONDITION_BEFORE_SESSION,
            'confirmation' => MDL_F2F_CONDITION_BOOKING_CONFIRMATION,
            'cancellation' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION,
            'decline' => MDL_F2F_CONDITION_DECLINE_CONFIRMATION,
            'waitlist' => MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION,
            'request' => MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER,
            'timechange' => MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE,
            'trainerconfirm' => MDL_F2F_CONDITION_TRAINER_CONFIRMATION,
            'trainercancel' => MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION,
            'trainerunassign' => MDL_F2F_CONDITION_TRAINER_SESSION_UNASSIGNMENT,
            'registrationexpired' => MDL_F2F_CONDITION_REGISTRATION_DATE_EXPIRED,
            'sessioncancellation' => MDL_F2F_CONDITION_SESSION_CANCELLATION,
            'reservationcancel' => MDL_F2F_CONDITION_RESERVATION_CANCELLED,
            'allreservationcancel' => MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED,
            'rolerequest' => MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE,
            'adminrequest' => MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN,
            'registrationclosure' => MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS,
            'waitlistautoclean' => MDL_F2F_CONDITION_WAITLIST_AUTOCLEAN,
            'undercapacity' => MDL_F2F_CONDITION_SESSION_UNDER_CAPACITY,
        );
    }
}

/**
 * Check whether seminar notification is active
 *
 * @param int|\stdClass $notification Notification object or condition type
 * @param int|\stdClass|null $f2f Required if notification passed as condition type
 * @param bool $checkglobal A flag to check whether all notifications are disabled
 * @return bool
 * @throws coding_exception
 */
function facetoface_is_notification_active($notification, $f2f = null, $checkglobal = false) {
    global $DB, $CFG;

    if ($checkglobal && !empty($CFG->facetoface_notificationdisable)) {
        return false;
    }

    if (!($notification instanceof facetoface_notification)) {

        // If only notification type passed to the function, fetching it from the database.
        // Facetoface object is expected here we can not enforce it.
        if (is_object($f2f)) {
            $f2f = $f2f->id;
        }

        if (!is_int($notification) && !(is_string($notification))) {
            throw new coding_exception('$notification is expected to be an instance of ' .
                '"facetoface_notification" or int, "' . gettype($notification) . '" is given.');
        }

        return !!$DB->get_field('facetoface_notification', 'status', [
            'facetofaceid' => $f2f,
            'conditiontype' => intval($notification)
        ]);
    }

    return !!$notification->status;

}

/**
 * Gets the session date for the specified session. THIS FUNCTION IS NOT PUBLIC
 * AND IS MEANT FOR USE WITHIN THIS FILE ONLY.
 *
 * @param \stdClass $session session for which to get dates.
 *
 * @return \stdClass session with updated dates.
 */
function facetoface_notification_session_dates(\stdClass $session) {
    if (!isset($session->sessiondates)) {
        // Annoying, inconsistently implemented API.
        // We add the session dates to the session object as quite possibly other session lib funcs will need them also, and we don't want
        // to continuously load them.
        // This is sadly consistently inconsistent behaviour.
        $session->sessiondates = facetoface_get_session_dates($session->id);
    }

    return $session;
}

/**
 * Send a notice (all session dates in one message).
 *
 * @param class $facetoface record from the facetoface table
 * @param class $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @param array $params The parameters for the notification
 * @param int $icalattachmenttype The ical attachment type, or MDL_F2F_TEXT to disable ical attachments
 * @param int $icalattachmentmethod The ical method type: MDL_F2F_INVITE or MDL_F2F_CANCEL
 * @param object $fromuser User object describing who the email is from.
 * @param array $olddates array of previous dates
 * @return string Error message (or empty string if successful)
 */
function facetoface_send_notice($facetoface, $session, $userid, $params, $icalattachmenttype = MDL_F2F_TEXT, $icalattachmentmethod = MDL_F2F_INVITE, $fromuser = null, array $olddates = array()) {
    global $DB;

    $notificationdisable = get_config(null, 'facetoface_notificationdisable');
    if (!empty($notificationdisable)) {
        return false;
    }

    $user = $DB->get_record('user', array('id' => $userid, 'deleted' => 0));
    if (!$user) {
        return 'userdoesnotexist';
    }

    // If checkbox option is not present here then it means, perversely, that it was checked on the confirmation form.
    // This matters when we arrive here via adhoc task running in cron.
    if (!isset($session->notifyuser)) {
        $session->notifyuser = true;
    }

    if (!isset($session->notifymanager)) {
        $session->notifymanager = true;
    }

    // Make it not fail if more then one notification found. Just use one.
    // Other option is to change data_object, but so far it's facetoface issue that we hope to fix soon and remove workaround
    // code from here.
    $checkrows = $DB->get_records('facetoface_notification', $params);
    if (count($checkrows) > 1) {
        $params['id'] = reset($checkrows)->id;
        debugging("Duplicate notifications found for (excluding id): " . json_encode($params), DEBUG_DEVELOPER);
    }

    // By definition, the send one email per day feature works on sessions with
    // dates. However, the current system allows sessions to be created without
    // dates and it allows people to sign up to those sessions. In this cases,
    // the sign ups still need to get email notifications; hence the checking of
    // the existence of dates before allowing the send one email per day part.
    // Note, that's not always the case, if all dates have been deleted from a
    // seminar event we still need to send the emails to cancel the dates,
    // thus need to check whether old dates have been supplied.
    $session = facetoface_notification_session_dates($session);
    if (get_config(null, 'facetoface_oneemailperday')
        && !(empty($session->sessiondates) && empty($olddates))) {
        return facetoface_send_oneperday_notice($facetoface, $session, $userid, $params, $icalattachmenttype, $icalattachmentmethod, $fromuser, $olddates);
    }

    $notice = new facetoface_notification($params);
    if (isset($facetoface->ccmanager)) {
        $notice->ccmanager = $facetoface->ccmanager;
    }
    $notice->set_facetoface($facetoface);

    $notice->set_newevent($user, $session->id, null, $fromuser);
    $icaldata = [];
    if ((int)$icalattachmenttype == MDL_F2F_BOTH && $notice->conditiontype != MDL_F2F_CONDITION_DECLINE_CONFIRMATION) {
        $notice->add_ical_attachment($user, $session, $icalattachmentmethod, null, $olddates);
        $icaldata = [
            'dates' => $session->sessiondates,
            'olddates' => $olddates,
            'method' => $icalattachmentmethod
        ];
    }

    if ($session->notifyuser) {
        $notice->send_to_user($user, $session->id, null, $icaldata);
    }
    if ($session->notifymanager) {
        $notice->send_to_manager($user, $session->id);
    }
    $notice->send_to_thirdparty($user, $session->id);
    $notice->send_to_roleapprovers_adhoc($user, $session->id);
    $notice->send_to_adminapprovers_adhoc($user, $session->id);
    $notice->delete_ical_attachment();

    return '';
}

/**
 * Send a notice (one message per session date).
 *
 * @param stdClass $facetoface record from the facetoface table
 * @param stdClass $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @param array $params The parameters for the notification
 * @param int $icalattachmenttype The ical attachment type, or MDL_F2F_TEXT to disable ical attachments
 * @param int $icalattachmentmethod The ical method type: MDL_F2F_INVITE or MDL_F2F_CANCEL
 * @param object $fromuser User object describing who the email is from.
 * @param array $olddates array of previous dates
 * @return string Error message (or empty string if successful)
 */
function facetoface_send_oneperday_notice($facetoface, $session, $userid, $params, $icalattachmenttype = MDL_F2F_TEXT, $icalattachmentmethod = MDL_F2F_INVITE, $fromuser = null, array $olddates = []) {
    global $DB, $CFG;

    $notificationdisable = get_config(null, 'facetoface_notificationdisable');
    if (!empty($notificationdisable)) {
        return false;
    }

    $user = $DB->get_record('user', array('id' => $userid));
    if (!$user) {
        return 'userdoesnotexist';
    }

    $session = facetoface_notification_session_dates($session);

    // Filtering dates.
    // "Key by" date id.
    $get_id = function($item) {
        return $item->id;
    };
    $olds = array_combine(array_map($get_id, $olddates), $olddates);

    $dates = array_filter($session->sessiondates, function($date) use (&$olds) {
        if (isset($olds[$date->id])) {
            $old = $olds[$date->id];
            unset($olds[$date->id]);
            if ($old->sessiontimezone == $date->sessiontimezone &&
                $old->timestart == $date->timestart &&
                $old->timefinish == $date->timefinish &&
                $old->roomid == $date->roomid) {
                return false;
            }
        }

        return true;
    });

    $send = function($dates, $cancel = false) use ($facetoface, $session, $icalattachmenttype, $icalattachmentmethod, $user, $params, $CFG) {
        foreach ($dates as $date) {

            if ($cancel) {
                $params['conditiontype'] = MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION;
            }

            $sendical =  (int)$icalattachmenttype == MDL_F2F_BOTH &&
                (!$cancel || ($cancel && empty($CFG->facetoface_disableicalcancel)));

            $notice = new facetoface_notification($params);

            if (isset($facetoface->ccmanager)) {
                $notice->ccmanager = $facetoface->ccmanager;
            }
            $notice->set_facetoface($facetoface);
            // Send original notice for this date.
            $notice->set_newevent($user, $session->id, $date);
            if ($sendical) {
                $notice->add_ical_attachment($user, $session, $icalattachmentmethod, !$cancel ? $date : [], $cancel ? $date : []);
            }
            if ($session->notifyuser) {
                $notice->send_to_user($user, $session->id, $date);
            }
            if ($session->notifymanager) {
                $notice->send_to_manager($user, $session->id);
            }

            $notice->send_to_thirdparty($user, $session->id);
            $notice->send_to_roleapprovers_adhoc($user, $session->id);
            $notice->send_to_adminapprovers_adhoc($user, $session->id);

            $notice->delete_ical_attachment();
        }
    };

    $send($dates);
    $send($olds, true);

    return '';
}

/**
 * Send a confirmation email to the user and manager regarding the
 * cancellation
 *
 * @param \stdClass $facetoface record from the facetoface table
 * @param \stdClass $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @param integer $conditiontype Optional override of the standard cancellation confirmation
 * @param bool $invite flag whether to include iCal invitation
 * @returns string Error message (or empty string if successful)
 * @deprecated Since Totara 12.0
 */
function facetoface_send_cancellation_notice($facetoface, $session, $userid, $conditiontype = MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION, $invite = true) {
    global $CFG;
    debugging('facetoface_send_cancellation_notice() function has been deprecated, please use notice_sender::signup_cancellation_notice() or notice_sender::event_cancellation_notice()', DEBUG_DEVELOPER);

    $params = array(
        'facetofaceid'  => $facetoface->id,
        'type'          => MDL_F2F_NOTIFICATION_AUTO,
        'conditiontype' => $conditiontype
    );

    $includeical = empty($CFG->facetoface_disableicalcancel) && $invite;
    return facetoface_send_notice($facetoface, $session, $userid, $params, $includeical ? MDL_F2F_BOTH : MDL_F2F_TEXT, MDL_F2F_CANCEL);
}

/**
 * Send a confirmation email to the user and manager regarding the
 * cancellation
 *
 * @param class $facetoface record from the facetoface table
 * @param class $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @returns string Error message (or empty string if successful)
 * @deprecated Since Totara 12.0
 */
function facetoface_send_decline_notice($facetoface, $session, $userid) {
    global $CFG;
    debugging('facetoface_send_decline_notice() function has been deprecated, please use notice_sender::decline()', DEBUG_DEVELOPER);

    $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_DECLINE_CONFIRMATION
            );

    $includeical = empty($CFG->facetoface_disableicalcancel);
    return facetoface_send_notice($facetoface, $session, $userid, $params, $includeical ? MDL_F2F_BOTH : MDL_F2F_TEXT, MDL_F2F_CANCEL);
}

/**
 * Send a email to the user and manager regarding the
 * session date/time change
 *
 * @param \stdClass $facetoface record from the facetoface table
 * @param \stdClass $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @param array $olddates array of previous dates
 * @param bool $invite flag whether to include iCal invitation
 * @returns string Error message (or empty string if successful)
 * @deprecated Since Totara 12.0
 */
function facetoface_send_datetime_change_notice($facetoface, $session, $userid, $olddates, $invite = true) {
    debugging('facetoface_send_datetime_change_notice() function has been deprecated, please use notice_sender::event_datetime_changed() or notice_sender::signup_datetime_changed()', DEBUG_DEVELOPER);

    $params = array(
        'facetofaceid'  => $facetoface->id,
        'type'          => MDL_F2F_NOTIFICATION_AUTO,
        'conditiontype' => MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE
    );

    $invite = $invite ? MDL_F2F_BOTH : MDL_F2F_TEXT;

    return facetoface_send_notice($facetoface, $session, $userid, $params, $invite, MDL_F2F_INVITE, null, $olddates);
}


/**
 * Send a confirmation email to the user and manager
 *
 * @param class $facetoface record from the facetoface table
 * @param class $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @param integer $notificationtype Type of notifications to be sent @see {{MDL_F2F_INVITE}}
 * @param boolean $iswaitlisted If the user has been waitlisted
 * @param object $fromuser User object describing who the email is from.
 * @returns string Error message (or empty string if successful)
 * @deprecated Since Totara 12.0
 */
function facetoface_send_confirmation_notice($facetoface, $session, $userid, $notificationtype, $iswaitlisted, $fromuser = null) {
    debugging('facetoface_send_confirmation_notice() function has been deprecated, please use notice_sender::confirm_booking() or notice_sender::confirm_waitlist()', DEBUG_DEVELOPER);

    $params = array(
        'facetofaceid'  => $facetoface->id,
        'type'          => MDL_F2F_NOTIFICATION_AUTO
    );

    if ($iswaitlisted) {
        $params['conditiontype'] = MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION;
    } else {
        $params['conditiontype'] = MDL_F2F_CONDITION_BOOKING_CONFIRMATION;
    }

    return facetoface_send_notice($facetoface, $session, $userid, $params, $notificationtype, MDL_F2F_INVITE, $fromuser);
}


/**
 * Send a confirmation email to the trainer
 *
 * @param class $facetoface record from the facetoface table
 * @param class $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @param integer $notificationtype Type of notifications to be sent @see {{MDL_F2F_INVITE}}
 * @returns string Error message (or empty string if successful)
 * @deprecated Since Totara 12.0
 */
function facetoface_send_trainer_confirmation_notice($facetoface, $session, $userid) {
    debugging('facetoface_send_trainer_confirmation_notice() function has been deprecated, please use notice_sender::trainer_confirmation()', DEBUG_DEVELOPER);

    $params = array(
        'facetofaceid'  => $facetoface->id,
        'type'          => MDL_F2F_NOTIFICATION_AUTO,
        'conditiontype' => MDL_F2F_CONDITION_TRAINER_CONFIRMATION
    );

    return facetoface_send_notice($facetoface, $session, $userid, $params, MDL_F2F_BOTH, MDL_F2F_INVITE);
}


/**
 * Send a cancellation email to the trainer
 *
 * @param class $facetoface record from the facetoface table
 * @param class $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @param integer $notificationtype Type of notifications to be sent @see {{MDL_F2F_INVITE}}
 * @returns string Error message (or empty string if successful)
 * @deprecated Since Totara 12.0
 */
function facetoface_send_trainer_session_cancellation_notice($facetoface, $session, $userid) {
    debugging('facetoface_send_trainer_session_cancellation_notice() function has been deprecated, please use notice_sender::event_trainer_cancellation()', DEBUG_DEVELOPER);

    $params = array(
        'facetofaceid'  => $facetoface->id,
        'type'          => MDL_F2F_NOTIFICATION_AUTO,
        'conditiontype' => MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION
    );

    return facetoface_send_notice($facetoface, $session, $userid, $params, MDL_F2F_BOTH, MDL_F2F_CANCEL);
}


/**
 * Send a unassignment email to the trainer
 *
 * @param class $facetoface record from the facetoface table
 * @param class $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @param integer $notificationtype Type of notifications to be sent @see {{MDL_F2F_INVITE}}
 * @returns string Error message (or empty string if successful)
 * @deprecated Since Totara 12.0
 */
function facetoface_send_trainer_session_unassignment_notice($facetoface, $session, $userid) {
    debugging('facetoface_send_trainer_session_unassignment_notice() function has been deprecated, please use notice_sender::event_trainer_unassigned()', DEBUG_DEVELOPER);

    $params = array(
        'facetofaceid'  => $facetoface->id,
        'type'          => MDL_F2F_NOTIFICATION_AUTO,
        'conditiontype' => MDL_F2F_CONDITION_TRAINER_SESSION_UNASSIGNMENT
    );

    return facetoface_send_notice($facetoface, $session, $userid, $params, MDL_F2F_BOTH, MDL_F2F_CANCEL);
}

/**
 * Send booking request notice to user and their manager
 *
 * @param   object  $facetoface Facetoface instance
 * @param   object  $session    Session instance
 * @param   int     $userid     ID of user requesting booking
 * @return  string  Error string, empty on success
 * @deprecated since Totara 12.0
 */
function facetoface_send_request_notice($facetoface, $session, $userid) {
    global $DB;

    debugging('facetoface_send_request_notice() function has been deprecated, please use notice_sender::request_manager()', DEBUG_DEVELOPER);

    $params = array('userid' => $userid, 'sessionid' => $session->id);
    $jobassignmentid = $DB->get_field('facetoface_signups', 'jobassignmentid', $params);
    $managers = facetoface_get_session_managers($userid, $session->id, $jobassignmentid);
    $sent = false;

    foreach ($managers as $manager) {
        if (empty($manager->email)) {
            continue;
        }
        $sent = true;

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER
        );
        return facetoface_send_notice($facetoface, $session, $userid, $params);
    }
    return 'error:nomanagersemailset';
}

/**
 * Send booking request notice to user and all users with the specified sessionrole
 * @param object $facetoface    Facetoface instance
 * @param object $session       Session instance
 * @param int    $recipientid   The id of the user requesting a booking
 * @deprecated since Totara 12.0
 */
function facetoface_send_rolerequest_notice($facetoface, $session, $recipientid) {
    debugging('facetoface_send_rolerequest_notice() function has been deprecated, please use notice_sender::request_role()', DEBUG_DEVELOPER);

    $params = array(
        'facetofaceid'  => $facetoface->id,
        'type'          => MDL_F2F_NOTIFICATION_AUTO,
        'conditiontype' => MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE
    );

    return facetoface_send_notice($facetoface, $session, $recipientid, $params);
}

/**
 * Send booking request notice to user, manager, all session admins.
 *
 * @param object $facetoface    Facetoface instance
 * @param object $session       Session instance
 * @param array  $admins        An array of admin userids
 * @param int    $recipientid   The id of the user requesting a booking
 * @deprecated Since Totara 12.0
 */
function facetoface_send_adminrequest_notice($facetoface, $session, $recipientid) {
    debugging('facetoface_send_adminrequest_notice() function has been deprecated, please use notice_sender::request_admin()', DEBUG_DEVELOPER);

    $params = array(
        'facetofaceid'  => $facetoface->id,
        'type'          => MDL_F2F_NOTIFICATION_AUTO,
        'conditiontype' => MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN
    );

    return facetoface_send_notice($facetoface, $session, $recipientid, $params);
}

/**
 * Send registration closure notice to user, manager, all session admins.
 *
 * @param object $facetoface    Facetoface instance
 * @param object $session       Session instance
 * @param int    $recipientid   The id of the user requesting a booking
 */
function facetoface_send_registration_closure_notice($facetoface, $session, $recipientid) {
    global $DB, $USER;


    $notificationdisable = get_config(null, 'facetoface_notificationdisable');
    if (!empty($notificationdisable)) {
        return false;
    }

    $recipient = $DB->get_record('user', array('id' => $recipientid));
    if (!$recipient) {
        return 'userdoesnotexist';
    }

    $params = array(
        'facetofaceid'  => $facetoface->id,
        'type'          => MDL_F2F_NOTIFICATION_AUTO,
        'conditiontype' => MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS
    );

    $notice = new facetoface_notification($params);
    $notice->set_newevent($recipient, $session->id, null, $USER);
    $notice->send_to_user($recipient, $session->id);
    $notice->send_to_manager($recipient, $session->id);

    return '';
}

/**
 * Subsitute the placeholders in message templates for the actual data
 *
 * Expects the following parameters in the $data object:
 * - details
 * - discountcost
 * - duration
 * - normalcost
 * - sessiondates
 *
 * @access  public
 * @param   string  $msg            Email message
 * @param   string  $facetofacename F2F name
 * @param   obj     $user           The subject of the message
 * @param   obj     $data           Session data
 * @param   int     $sessionid      Session ID
 * @param   int     $approvalrole   The id of the role set to approve the facetoface (optional)
 * @return  string
 */
function facetoface_message_substitutions($msg, $coursename, $facetofacename, $user, $data, $sessionid, $approvalrole = null) {
    global $CFG, $DB;

    if (empty($msg)) {
        return '';
    }

    // Provides facetoface_cost() and facetoface_get_num_attendees()
    require_once($CFG->dirroot.'/mod/facetoface/lib.php');

    // Get timezone setting.
    $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');
    $str_unknowndate = get_string('unknowndate', 'facetoface');
    $str_unknowntime = get_string('unknowntime', 'facetoface');

    // Get number booked if possible
    $booked = facetoface_get_num_attendees($data->id, \mod_facetoface\signup\state\waitlisted::get_code());

    if (!empty($data->sessiondates)) {
        // Replacing the ['eventperiod'] here to the default [starttime]-[finishtime], [sessiondate]
        $msg = str_replace("[eventperiod]", "[starttime]-[finishtime], [sessiondate]", $msg);
        // Scheduled session
        $strftimedate = get_string('strftimedate');
        $strftimetime = get_string('strftimetime');
        $sessiontimezone = (($data->sessiondates[0]->sessiontimezone == 99 && $user->timezone) ? $user->timezone : $data->sessiondates[0]->sessiontimezone);
        $startdate = userdate($data->sessiondates[0]->timestart, $strftimedate, $sessiontimezone);
        $finishdate = userdate($data->sessiondates[0]->timefinish, $strftimedate, $sessiontimezone);
        $sessiondate = ($startdate == $finishdate) ? $startdate : $startdate . ' - ' . $finishdate;
        $starttime = userdate($data->sessiondates[0]->timestart, $strftimetime, $sessiontimezone);
        $finishtime = userdate($data->sessiondates[0]->timefinish, $strftimetime, $sessiontimezone);
        // On a session with multiple-dates, variables above are finish dates etc for first date.
        // Below variables give dates and times for last date.
        $sessiontimezone = ((end($data->sessiondates)->sessiontimezone == 99 && $user->timezone) ? $user->timezone : end($data->sessiondates)->sessiontimezone);
        $lateststarttime = userdate(end($data->sessiondates)->timestart, $strftimetime, $sessiontimezone);
        $lateststartdate = userdate(end($data->sessiondates)->timestart, $strftimedate, $sessiontimezone);
        $latestfinishtime = userdate(end($data->sessiondates)->timefinish, $strftimetime, $sessiontimezone);
        $latestfinishdate = userdate(end($data->sessiondates)->timefinish, $strftimedate, $sessiontimezone);
        $data->duration = format_time((int)$data->sessiondates[0]->timestart - (int)end($data->sessiondates)->timefinish);
    } else {
        // If it is a waitlist session, make it as a tbd title here
        $unknownperiod = get_string("locationtimetbd", 'facetoface');
        $msg = str_replace("[eventperiod]", strtolower($unknownperiod), $msg);
        // Wait-listed session
        $startdate   = $str_unknowndate;
        $finishdate  = $str_unknowndate;
        $sessiondate = $str_unknowndate;
        $starttime   = $str_unknowntime;
        $finishtime  = $str_unknowntime;
        $lateststarttime = $str_unknowntime;
        $lateststartdate = $str_unknowndate;
        $latestfinishtime = $str_unknowntime;
        $latestfinishdate = $str_unknowndate;
        $data->duration = '';
    }

    $rolename = '';
    if (!empty($approvalrole)) {
        $rolenames = role_fix_names(get_all_roles());
        $rolename = $rolenames[$approvalrole]->localname;
    }
    // Replace.
    $msg = str_replace('[sessionrole]', $rolename, $msg);
    // Legacy.
    $msg = str_replace(get_string('placeholder:sessionrole', 'facetoface'), $rolename, $msg);

    // Replace placeholders with values
    $msg = str_replace('[coursename]', $coursename, $msg);
    $msg = str_replace('[facetofacename]', $facetofacename, $msg);
    $msg = str_replace('[firstname]', $user->firstname, $msg);
    $msg = str_replace('[lastname]', $user->lastname, $msg);
    $msg = str_replace('[cost]', facetoface_cost($user->id, $sessionid, $data), $msg);
    $msg = str_replace('[sessiondate]', $sessiondate, $msg);
    $msg = str_replace('[startdate]', $startdate, $msg);
    $msg = str_replace('[finishdate]', $finishdate, $msg);
    $msg = str_replace('[starttime]', $starttime, $msg);
    $msg = str_replace('[finishtime]', $finishtime, $msg);
    $msg = str_replace('[lateststarttime]', $lateststarttime, $msg);
    $msg = str_replace('[lateststartdate]', $lateststartdate, $msg);
    $msg = str_replace('[latestfinishtime]', $latestfinishtime, $msg);
    $msg = str_replace('[latestfinishdate]', $latestfinishdate, $msg);
    $msg = str_replace('[duration]', $data->duration, $msg);
    $msg = str_replace('[booked]', $booked, $msg);
    $msg = str_replace('[capacity]', $data->capacity, $msg);
    $msg = str_replace('[mincapacity]', $data->mincapacity, $msg);
    // Legacy.
    $msg = str_replace(get_string('placeholder:coursename', 'facetoface'), $coursename, $msg);
    $msg = str_replace(get_string('placeholder:facetofacename', 'facetoface'), $facetofacename, $msg);
    $msg = str_replace(get_string('placeholder:firstname', 'facetoface'), $user->firstname, $msg);
    $msg = str_replace(get_string('placeholder:lastname', 'facetoface'), $user->lastname, $msg);
    $msg = str_replace(get_string('placeholder:cost', 'facetoface'), facetoface_cost($user->id, $sessionid, $data), $msg);
    $msg = str_replace(get_string('placeholder:sessiondate', 'facetoface'), $sessiondate, $msg);
    $msg = str_replace(get_string('placeholder:startdate', 'facetoface'), $startdate, $msg);
    $msg = str_replace(get_string('placeholder:finishdate', 'facetoface'), $finishdate, $msg);
    $msg = str_replace(get_string('placeholder:starttime', 'facetoface'), $starttime, $msg);
    $msg = str_replace(get_string('placeholder:finishtime', 'facetoface'), $finishtime, $msg);
    $msg = str_replace(get_string('placeholder:lateststarttime', 'facetoface'), $lateststarttime, $msg);
    $msg = str_replace(get_string('placeholder:lateststartdate', 'facetoface'), $lateststartdate, $msg);
    $msg = str_replace(get_string('placeholder:latestfinishtime', 'facetoface'), $latestfinishtime, $msg);
    $msg = str_replace(get_string('placeholder:latestfinishdate', 'facetoface'), $latestfinishdate, $msg);
    $msg = str_replace(get_string('placeholder:duration', 'facetoface'), $data->duration, $msg);
    $msg = str_replace(get_string('placeholder:booked', 'facetoface'), $booked, $msg);
    $msg = str_replace(get_string('placeholder:capacity', 'facetoface'), $data->capacity, $msg);
    $msg = str_replace(get_string('placeholder:mincapacity', 'facetoface'), $data->mincapacity, $msg);

    if (!empty($data->registrationtimefinish)) {
        $registrationcutoff = userdate($data->registrationtimefinish, get_string('strftimerecent'));
    } else if (!empty($data->sessiondates[0]->timestart)) {
        $registrationcutoff = userdate($data->sessiondates[0]->timestart, get_string('strftimerecent'));
    } else {
        $registrationcutoff = $str_unknowndate;
    }

    // Replace.
    $msg = str_replace('[registrationcutoff]', $registrationcutoff, $msg);
    // Legacy.
    $msg = str_replace(get_string('placeholder:registrationcutoff', 'facetoface'), $registrationcutoff, $msg);

    $roomcf = array();
    $roomlist = \mod_facetoface\room_list::get_event_rooms($data->id);
    if ($roomlist->is_empty()) {
        // If the roomlist is empty, then we start checking roomid within session dates of an event/session, because the custom
        // room would have been removed from session date in the database at this point, whenever an event is cancelled.
        if (isset($data->sessiondates) && is_array($data->sessiondates)) {
            foreach ($data->sessiondates as $sessiondate) {
                if (empty($sessiondate->roomid)) {
                    continue;
                }

                $room = \mod_facetoface\room::find($sessiondate->roomid);
                if (!$room->exists()) {
                    continue;
                }

                $roomlist->add($room);
            }
        }
    }

    foreach ($roomlist as $room) {
        $roomcf[$room->get_id()] = customfield_get_data($room->to_record(), 'facetoface_room', 'facetofaceroom', false);
    }
    $msg = facetoface_notification_loop_session_placeholders($msg, $data, $roomlist, $roomcf, $user);
    $msg = facetoface_notification_substitute_deprecated_placeholders($msg, $data, $roomlist, $roomcf);

    $details = '';
    if (!empty($data->details)) {
        if ($cm = get_coursemodule_from_instance('facetoface', $data->facetoface, $data->course)) {
            $context = context_module::instance($cm->id);
            $data->details = file_rewrite_pluginfile_urls($data->details, 'pluginfile.php', $context->id, 'mod_facetoface', 'session', $data->id);
            $details = format_text($data->details, FORMAT_HTML);
        }
    }
    // Replace.
    $msg = str_replace('[details]', $details, $msg);
    // Legacy.
    $msg = str_replace(get_string('placeholder:details', 'facetoface'), $details, $msg);

    // Replace more meta data
    $attendees_url = new moodle_url('/mod/facetoface/attendees/approvalrequired.php', array('s' => $data->id));
    $link = html_writer::link($attendees_url, $attendees_url, array('title' => get_string('attendees', 'facetoface')));
    // Replace.
    $msg = str_replace('[attendeeslink]', $link, $msg);
    // Legacy.
    $msg = str_replace(get_string('placeholder:attendeeslink', 'facetoface'), $link, $msg);

    if (strstr($msg, '[reminderperiod]') || strstr($msg, get_string('placeholder:reminderperiod', 'facetoface'))) {
        // Handle the legacy reminderperiod placeholder.
        $reminderperiod = $DB->get_field('facetoface_notification', 'MAX(scheduleamount)',
            array('facetofaceid' => $data->facetoface, 'conditiontype' => MDL_F2F_CONDITION_BEFORE_SESSION,
            'scheduleunit' => MDL_F2F_SCHEDULE_UNIT_DAY, 'status' => 1), IGNORE_MULTIPLE);
        $reminderperiod = empty($reminderperiod) ? 0 : $reminderperiod;
        // Replace.
        $msg = str_replace('[reminderperiod]', $reminderperiod, $msg);
        // Legacy.
        $msg = str_replace(get_string('placeholder:reminderperiod', 'facetoface'), $reminderperiod, $msg);
    }

    // Custom session fields (they look like "session:shortname" in the templates)
    $customfields = customfield_get_data($data, 'facetoface_session', 'facetofacesession', false);
    foreach ($customfields as $cftitle => $cfvalue) {
        $placeholder = "[session:{$cftitle}]";
        $msg = str_replace($placeholder, $cfvalue, $msg);
    }

    $sessioncancellationcustomfields = customfield_get_data($data, 'facetoface_sessioncancel', 'facetofacesessioncancel', false);
    foreach ($sessioncancellationcustomfields as $cftitle => $cfvalue) {
        $placeholder = "[sessioncancel:{$cftitle}]";
        $msg = str_replace($placeholder, $cfvalue, $msg);
    }

    $msg = facetoface_message_substitutions_userfields($msg, $user);

    return $msg;
}

/**
 * Replaces a section in a notification string that begins with the loop start tag ('[#sessions]')
 * and ends with the loop end tag ('[/sessions]') for sessions. The section will be replaced with
 * placeholders substituted and will be repeated for each session.
 *
 * Properties in $session that may be required are:
 * $session->sessiondates
 *
 * @param string $msg - the string for the notification.
 * @param stdClass $session - an object that includes the sessiondates and also room data if that's necessary.
 * @param array $rooms - array of room objects
 * @param array $roomcustomfields - array of room custom fields values with room->id as keys,
 * and for the values: customfield arrays of the format ['shortname' => value].
 * @param stdClass $user - an object of user detail information
 * @return string the message with the looped replacements added.
 */
function facetoface_notification_loop_session_placeholders($msg, $session, $rooms = null, $roomcustomfields = array(), $user = null) {
    global $DB;

    $prevendposition = 0;
    $startposition = 0;
    while($startposition !== false) {

        // Check that msg contains a start tag (e.g. '[#sessions]').
        $starttag = '[#sessions]';
        $startposition = strpos($msg, $starttag, $prevendposition);
        if (!$startposition) {
            return $msg;
        }

        // Check that msg contains an end tag (e.g. '[/sessions]').
        $endtag = '[/sessions]';
        $endposition = strpos($msg, $endtag, $startposition);
        if (!$endposition) {
            return $msg;
        }

        // Cut off sessions section.
        if (empty($session->sessiondates)) {
            $msg = substr($msg, 0, $startposition) . get_string('locationtimetbd', 'facetoface') . substr($msg, $endposition + strlen($endtag));
            continue;
        }

        if (empty($rooms)) {
            $rooms = new \mod_facetoface\room_list('', ['id' => 0]);
        }

        // Get the segment that will be repeated for each session.
        $templatesegment = substr($msg, $startposition + strlen($starttag), $endposition - $startposition - strlen($starttag));

        // Define the fixed (non-customfield) placeholders.
        $fixed_placeholders = array(
            'session:startdate' => '[session:startdate]',
            'session:starttime' => '[session:starttime]',
            'session:finishdate' => '[session:finishdate]',
            'session:finishtime' => '[session:finishtime]',
            'session:timezone' => '[session:timezone]',
            'session:duration' => '[session:duration]',
            'room:name' => '[session:room:name]',
            'room:link' => '[session:room:link]');

        foreach ($fixed_placeholders as $key => $placeholderstring) {
            // Check if the placeholder is present in this segment.
            if (strpos($templatesegment, $placeholderstring) !== false) {
                $fixed_placeholders[$key] = $placeholderstring;
            } else {
                // Remove if not present in template segment, which saves the processing involved
                // in determining the value when it's not necessary.
                unset($fixed_placeholders[$key]);
            }
        }

        // Now add the room custom field placeholders.
        $room_cf_placeholders = array();
        $room_cf_names = $DB->get_records('facetoface_room_info_field', array('hidden' => 0), '', 'shortname');
        foreach ($room_cf_names as $room_cf_name) {
            $placeholderstring = "[session:room:cf_{$room_cf_name->shortname}]";
            // Check if the placeholder is present in this segment.
            if (strpos($templatesegment, $placeholderstring) !== false) {
                $room_cf_placeholders[$room_cf_name->shortname] = $placeholderstring;
            }
        }

        $returnedsegments = array();

        $strftimedate = get_string('strftimedate');
        $strftimetime = get_string('strftimetime');

        foreach ($session->sessiondates as $key => $sessiondate) {
            $returnedsegments[$key] = $templatesegment;
            $sessiontimezone = $sessiondate->sessiontimezone;
            if (isset($user->timezone)) {
                $sessiontimezone = ($sessiondate->sessiontimezone == 99 ? $user->timezone : $sessiondate->sessiontimezone);
            }
            foreach ($fixed_placeholders as $type => $fixed_placeholder) {
                $value = '';
                switch ($type) {
                    case 'session:startdate':
                        $value = userdate($sessiondate->timestart, $strftimedate, $sessiontimezone);
                        break;
                    case 'session:starttime':
                        $value = userdate($sessiondate->timestart, $strftimetime, $sessiontimezone);
                        break;
                    case 'session:finishdate':
                        $value = userdate($sessiondate->timefinish, $strftimedate, $sessiontimezone);
                        break;
                    case 'session:finishtime':
                        $value = userdate($sessiondate->timefinish, $strftimetime, $sessiontimezone);
                        break;
                    case 'session:timezone':
                        $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');
                        $value = $displaytimezones ? core_date::get_user_timezone($sessiontimezone) : '';
                        break;
                    case 'session:duration':
                        $value = format_time((int)$sessiondate->timestart - (int)$sessiondate->timefinish);
                        break;
                    case 'room:name':
                        if (!empty($sessiondate->roomid) && $rooms->contains($sessiondate->roomid)) {
                            $room = $rooms->get($sessiondate->roomid);
                            $value = $room->get_name();
                        }
                        break;
                    case 'room:link':
                        if (!empty($sessiondate->roomid)) {
                            $roomdetailsurl = new moodle_url('/mod/facetoface/reports/rooms.php', ['roomid' => $sessiondate->roomid]);
                            $value = html_writer::link($roomdetailsurl, $roomdetailsurl, ['title' => get_string('roomdetails', 'facetoface')]);
                        }
                        break;
                    default:
                        $value = '';
                }
                // If the value for this field for this session is false or null, we'll use an empty string.
                if (is_null($value) or ($value === false)) {
                    $value = '';
                }
                $returnedsegments[$key] = str_replace($fixed_placeholder, $value, $returnedsegments[$key]);
            }
            foreach ($room_cf_placeholders as $type => $room_cf_placeholder) {
                $value = '';
                if (!empty($sessiondate->roomid) && $rooms->contains($sessiondate->roomid)) {
                    if (!empty($roomcustomfields[$sessiondate->roomid][$type])) {
                        $value = $roomcustomfields[$sessiondate->roomid][$type];
                    }
                }
                // If the value for this field for this session is false or null, we'll use an empty string.
                if (is_null($value) or ($value === false)) {
                    $value = '';
                }
                $returnedsegments[$key] = str_replace($room_cf_placeholder, $value, $returnedsegments[$key]);
            }
        }

        $prestartloop = substr($msg, 0, $startposition);
        $postendloop = substr($msg, $endposition + strlen($endtag));
        $combinedsegments = implode('', $returnedsegments);

        $msg = $prestartloop . $combinedsegments . $postendloop;
    }

    return $msg;
}

/**
 * Substitute placeholders for user fields in message templates for the actual data
 *
 * @param   string  $msg            Email message
 * @param   obj     $user           The subject of the message
 * @return  string                  Message with substitutions applied
 */
function facetoface_message_substitutions_userfields($msg, $user) {
    global $DB;
    static $customfields = null;

    $placeholders = array('username' => '[username]', 'email' => '[email]', 'institution' => '[institution]',
        'department' => '[department]', 'city' => '[city]', 'idnumber' => '[idnumber]', 'icq' => '[icq]', 'skype' => '[skype]',
        'yahoo' => '[yahoo]', 'aim' => '[aim]', 'msn' => '[msn]', 'phone1' => '[phone1]', 'phone2' => '[phone2]',
        'address' => '[address]', 'url' => '[url]', 'description' => '[description]');
    $fields = array_keys($placeholders);

    $usernamefields = get_all_user_name_fields();
    $fields = array_merge($fields, array_values($usernamefields));

    // Process basic user fields.
    foreach ($fields as $field) {
        // Replace.
        if (isset($placeholders[$field])) {
            $msg = str_replace($placeholders[$field], $user->$field, $msg);
        }
        // Legacy.
        $msg = str_replace(get_string('placeholder:'.$field, 'mod_facetoface'), $user->$field, $msg);
    }

    $fullname = fullname($user);
    // Replace.
    $msg = str_replace('[fullname]', $fullname, $msg);
    // Legacy.
    $msg = str_replace(get_string('placeholder:fullname', 'mod_facetoface'), $fullname, $msg);

    $langvalue = output_language_code($user->lang);
    // Replace.
    $msg = str_replace('[lang]', $langvalue, $msg);
    // Legacy.
    $msg = str_replace(get_string('placeholder:lang', 'mod_facetoface'), $langvalue, $msg);

    $countryvalue = output_country_code($user->country);
    // Replace.
    $msg = str_replace('[country]', $countryvalue, $msg);
    // Legacy.
    $msg = str_replace(get_string('placeholder:country', 'mod_facetoface'), $countryvalue, $msg);

    $timezone = core_date::get_user_timezone($user);
    // Replace.
    $msg = str_replace('[timezone]', $timezone, $msg);
    // Legacy.
    $msg = str_replace(get_string('placeholder:timezone', 'mod_facetoface'), $timezone, $msg);

    // Check to see if we need to load and process custom profile fields.
    if (strpos($msg, '[user:') !== false) {
        // If static fields variable isn't already populated with custom profile fields then grab them.
        if ($customfields === null) {
            $customfields = $DB->get_records('user_info_field');
        }

        $sql = "SELECT f.shortname,d.*
                      FROM {user_info_data} d
                      JOIN {user_info_field} f ON d.fieldid = f.id
                     WHERE d.userid = :userid";

        $customfielddata = $DB->get_records_sql($sql, array('userid' => $user->id));

        // Iterate through custom profile fields.
        foreach ($customfields as $field) {
            if (array_key_exists($field->shortname, $customfielddata)) {
                $value = $customfielddata[$field->shortname]->data;
            } else {
                $value = $field->defaultdata;
            }

            // Use output functions for checkbox/datatime.
            switch ($field->datatype){
                case 'checkbox':
                    $value = output_checkbox($value);
                    break;
                case 'datetime':
                    $value = output_datetime($field, $value);
                    break;
            }

            $msg = str_replace('[user:'.$field->shortname.']', $value, $msg);
        }
    }

    return $msg;
}

/**
 * Substitute values for placeholders that are deprecated since 9.0.
 *
 * These placeholders were deprecated when rooms were changed from per-session to per-sessiondate.
 * Because they may have been left in some notifications, the will subsitute in values for the room
 * corresponding to the first session date.
 *
 * Properties in $session that may be required are:
 * $session->sessiondates
 *
 * @param string $msg - the string for the notification.
 * @param stdClass $session - an object that includes the sessiondates and also room data if that's necessary.
 * @param array $rooms - array of room objects
 * @param array $roomcustomfields - array of room custom fields values with room->id as keys,
 * and for the values: customfield arrays of the format ['shortname' => value].
 * @return string the message with the looped replacements added.
 */
function facetoface_notification_substitute_deprecated_placeholders($msg, $session, $rooms = array(), $roomcustomfields = array()) {

    $roomcf = false;
    $roomnamevalue = '';
    $locationvalue = '';
    $venuevalue = '';

    // Reset returns the first value in the sessiondates array.
    $firstsessiondate = reset($session->sessiondates);
    if (!empty($firstsessiondate->roomid) && $rooms->contains($firstsessiondate->roomid)) {
        $room = $rooms->get($firstsessiondate->roomid);
        if (!empty($room->get_name())) {
            $roomnamevalue = $room->get_name();
        }

        if (isset($roomcustomfields[$firstsessiondate->roomid])) {
            $roomcf = $roomcustomfields[$firstsessiondate->roomid];
        }
    }

    if (isset($roomcf['location'])) {
        $locationvalue = $roomcf['location'];
    }

    if (isset($roomcf['building'])) {
        $venuevalue = $roomcf['building'];
    }

    // Get the deprecated placeholders.
    $roomnameplaceholder = '[session:room]';
    $locationplaceholder = '[session:location]';
    $venueplaceholder = '[session:venue]';

    $msg = str_replace($roomnameplaceholder, $roomnamevalue, $msg);
    $msg = str_replace($locationplaceholder, $locationvalue, $msg);
    $msg = str_replace($venueplaceholder, $venuevalue, $msg);

    return $msg;
}

/**
 * Write plain text yes or no for checkboxes.
 *
 * @param boolean $value
 * @return string
 */
function output_checkbox($value) {
    if ($value) {
        return get_string('yes');
    } else {
        return get_string('no');
    }
}

/**
 * Get plain text date for timestamps.
 *
 * @param int $value    Timestamp
 * @return string
 */
function output_datetime($field, $value) {
    // Variable param3 indicates wether or not to display time.
    if ($field->param3 && is_numeric($value)) {
        return userdate($value, get_string('strfdateattime', 'langconfig'));
    } else if (is_numeric($value) && $value > 0) {
        return userdate($value, get_string('strfdateshortmonth', 'langconfig'));
    } else {
        return '';
    }
}

/**
 * Get country name for country codes.
 *
 * @param string $code  Country code
 * @return string
 */
function output_country_code($code) {
    global $CFG;
    require_once($CFG->dirroot.'/lib/moodlelib.php');

    $countries = get_string_manager()->get_list_of_countries();

    if (isset($countries[$code])) {
        return $countries[$code];
    }
    return $code;
}

/**
 * Get language name for language codes
 *
 * @param string $code  Language code
 * @return string
 */
function output_language_code($code) {
    global $CFG;
    require_once($CFG->dirroot.'/lib/moodlelib.php');

    $languages = get_string_manager()->get_list_of_languages();

    if (isset($languages[$code])) {
        return $languages[$code];
    }
    return $code;
}

/**
 * Check if a notification is frozen (uneditable) or not
 *
 * @access  public
 * @param   integer     $id         Notification ID
 * @return  boolean
 */
function facetoface_is_notification_frozen($id) {
    $notification = new facetoface_notification(array('id' => $id), true);
    return $notification->is_frozen();
}


/**
 * Returns an array of all the default notifications for a
 * face-to-face activity any new notifications used by core f2f
 * functionality need to be added here.
 *
 * @param int $facetofaceid
 * @return array Array of facetoface_notification objects
 */
function facetoface_get_default_notifications($facetofaceid) {
    global $DB;

    // Get templates.
    $templaterecords = $DB->get_records('facetoface_notification_tpl');

    $templates = array();

    foreach ($templaterecords as $rec) {
        if (!empty($rec->reference)) {
            $template = new stdClass();
            $template->id = $rec->id;
            $template->title = $rec->title;
            $template->body = $rec->body;
            $template->ccmanager = $rec->ccmanager;
            $template->managerprefix = $rec->managerprefix;
            $template->status = $rec->status;
            $templates[$rec->reference] = $template;
        }
    }

    $notifications = array();
    $missingtemplates = array();

    $facetoface = $DB->get_record('facetoface', array('id' => $facetofaceid));

    // Add default notifications
    $defaults = array();
    $defaults['facetofaceid'] = $facetoface->id;
    $defaults['courseid'] = $facetoface->course;
    $defaults['type'] = MDL_F2F_NOTIFICATION_AUTO;
    $defaults['booked'] = 0;
    $defaults['waitlisted'] = 0;
    $defaults['cancelled'] = 0;
    $defaults['requested'] = 0;
    $defaults['issent'] = 0;
    $defaults['status'] = 1;
    $defaults['ccmanager'] = 0;

    // The titles are fetched from the templates which have already been truncated to the 255
    // character limit before so there is no need to truncate them again here.

    if (isset($templates['confirmation'])) {
        $template = $templates['confirmation'];
        $confirmation = new facetoface_notification($defaults, false);
        $confirmation->title = $template->title;
        $confirmation->body = $template->body;
        $confirmation->managerprefix = $template->managerprefix;
        $confirmation->conditiontype = MDL_F2F_CONDITION_BOOKING_CONFIRMATION;
        $confirmation->ccmanager = $template->ccmanager;
        $confirmation->status = $template->status;
        $confirmation->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_BOOKING_CONFIRMATION] = $confirmation;
    } else {
        $missingtemplates[] = 'confirmation';
    }

    if (isset($templates['waitlist'])) {
        $template = $templates['waitlist'];
        $waitlist = new facetoface_notification($defaults, false);
        $waitlist->title = $template->title;
        $waitlist->body = $template->body;
        $waitlist->managerprefix = $template->managerprefix;
        $waitlist->conditiontype = MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION;
        $waitlist->ccmanager = $template->ccmanager;
        $waitlist->status = $template->status;
        $waitlist->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION] = $waitlist;
    } else {
        $missingtemplates[]  = 'waitlist';
    }

    if (isset($templates['cancellation'])) {
        $template = $templates['cancellation'];
        $cancellation = new facetoface_notification($defaults, false);
        $cancellation->title = $template->title;
        $cancellation->body = $template->body;
        $cancellation->managerprefix = $template->managerprefix;
        $cancellation->conditiontype = MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION;
        $cancellation->ccmanager = $template->ccmanager;
        $cancellation->cancelled = 1;
        $cancellation->status = $template->status;
        $cancellation->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION] = $cancellation;
    } else {
        $missingtemplates[] = 'cancellation';
    }

    if (isset($templates['decline'])) {
        $template = $templates['decline'];
        $decline = new facetoface_notification($defaults, false);
        $decline->title = $template->title;
        $decline->body = $template->body;
        $decline->managerprefix = $template->managerprefix;
        $decline->conditiontype = MDL_F2F_CONDITION_DECLINE_CONFIRMATION;
        $decline->ccmanager = $template->ccmanager;
        $decline->status = $template->status;
        $decline->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_DECLINE_CONFIRMATION] = $decline;
    } else {
        $missingtemplates[] = 'decline';
    }

    if (isset($templates['reminder'])) {
        $template = $templates['reminder'];
        $reminder = new facetoface_notification($defaults, false);
        $reminder->title = $template->title;
        $reminder->body = $template->body;
        $reminder->managerprefix = $template->managerprefix;
        $reminder->conditiontype = MDL_F2F_CONDITION_BEFORE_SESSION;
        $reminder->scheduleunit = MDL_F2F_SCHEDULE_UNIT_DAY;
        $reminder->scheduleamount = 2;
        $reminder->ccmanager = $template->ccmanager;
        $reminder->booked = 1;
        $reminder->status = $template->status;
        $reminder->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_BEFORE_SESSION] = $reminder;
    } else {
        $missingtemplates[] = 'reminder';
    }

    // Manager approval request.
    if (isset($templates['request'])) {
        $template = $templates['request'];
        $request = new facetoface_notification($defaults, false);
        $request->title = $template->title;
        $request->body = $template->body;
        $request->managerprefix = $template->managerprefix;
        $request->conditiontype = MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER;
        $request->ccmanager = $template->ccmanager;
        $request->status = $template->status;
        $request->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER] = $request;
    } else {
        $missingtemplates[] = 'request';
    }

    // Role approval request.
    if (isset($templates['rolerequest'])) {
        $template = $templates['rolerequest'];
        $rolerequest = new facetoface_notification($defaults, false);
        $rolerequest->title = $template->title;
        $rolerequest->body = $template->body;
        $rolerequest->managerprefix = $template->managerprefix;
        $rolerequest->conditiontype = MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE;
        $rolerequest->ccmanager = $template->ccmanager;
        $rolerequest->status = $template->status;
        $rolerequest->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE] = $rolerequest;
    } else {
        $missingtemplates[] = 'rolerequest';
    }

    // Manager & Admin approval request.
    if (isset($templates['adminrequest'])) {
        $template = $templates['adminrequest'];
        $adminrequest = new facetoface_notification($defaults, false);
        $adminrequest->title = $template->title;
        $adminrequest->body = $template->body;
        $adminrequest->managerprefix = $template->managerprefix;
        $adminrequest->conditiontype = MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN;
        $adminrequest->ccmanager = $template->ccmanager;
        $adminrequest->status = $template->status;
        $adminrequest->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN] = $adminrequest;
    } else {
        $missingtemplates[] = 'adminrequest';
    }

    if (isset($templates['timechange'])) {
        $template = $templates['timechange'];
        $session_change = new facetoface_notification($defaults, false);
        $session_change->title = $template->title;
        $session_change->body = $template->body;
        $session_change->managerprefix = $template->managerprefix;
        $session_change->conditiontype = MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE;
        $session_change->ccmanager = $template->ccmanager;
        $session_change->booked = 1;
        $session_change->waitlisted = 1;
        $session_change->status = $template->status;
        $session_change->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE] = $session_change;
    } else {
        $missingtemplates[] = 'timechange';
    }

    if (isset($templates['trainerconfirm'])) {
        $template = $templates['trainerconfirm'];
        $trainer_confirmation = new facetoface_notification($defaults, false);
        $trainer_confirmation->title = $template->title;
        $trainer_confirmation->body = $template->body;
        $trainer_confirmation->managerprefix = $template->managerprefix;
        $trainer_confirmation->conditiontype = MDL_F2F_CONDITION_TRAINER_CONFIRMATION;
        $trainer_confirmation->ccmanager = $template->ccmanager;
        $trainer_confirmation->status = $template->status;
        $trainer_confirmation->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_TRAINER_CONFIRMATION] = $trainer_confirmation;
    } else {
        $missingtemplates[] = 'trainerconfirm';
    }

    if (isset($templates['trainercancel'])) {
        $template = $templates['trainercancel'];
        $trainer_cancellation = new facetoface_notification($defaults, false);
        $trainer_cancellation->title = $template->title;
        $trainer_cancellation->body = $template->body;
        $trainer_cancellation->managerprefix = $template->managerprefix;
        $trainer_cancellation->conditiontype = MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION;
        $trainer_cancellation->ccmanager = $template->ccmanager;
        $trainer_cancellation->status = $template->status;
        $trainer_cancellation->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION] = $trainer_cancellation;
    } else {
        $missingtemplates[] = 'trainercancel';
    }

    if (isset($templates['trainerunassign'])) {
        $template = $templates['trainerunassign'];
        $trainer_unassigned = new facetoface_notification($defaults, false);
        $trainer_unassigned->title = $template->title;
        $trainer_unassigned->body = $template->body;
        $trainer_unassigned->managerprefix = $template->managerprefix;
        $trainer_unassigned->conditiontype = MDL_F2F_CONDITION_TRAINER_SESSION_UNASSIGNMENT;
        $trainer_unassigned->ccmanager = $template->ccmanager;
        $trainer_unassigned->status = $template->status;
        $trainer_unassigned->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_TRAINER_SESSION_UNASSIGNMENT] = $trainer_unassigned;
    } else {
        $missingtemplates[] = 'trainerunassign';
    }

    if (isset($templates['reservationcancel'])) {
        $template = $templates['reservationcancel'];
        $cancelreservation = new facetoface_notification($defaults, false);
        $cancelreservation->title = $template->title;
        $cancelreservation->body = $template->body;
        $cancelreservation->managerprefix = $template->managerprefix;
        $cancelreservation->conditiontype = MDL_F2F_CONDITION_RESERVATION_CANCELLED;
        $cancelreservation->ccmanager = $template->ccmanager;
        $cancelreservation->cancelled = 1;
        $cancelreservation->status = $template->status;
        $cancelreservation->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_RESERVATION_CANCELLED] = $cancelreservation;
    } else {
        $missingtemplates[] = 'reservationcancel';
    }

    if (isset($templates['allreservationcancel'])) {
        $template = $templates['allreservationcancel'];
        $cancelallreservations = new facetoface_notification($defaults, false);
        $cancelallreservations->title = $template->title;
        $cancelallreservations->body = $template->body;
        $cancelallreservations->managerprefix = $template->managerprefix;
        $cancelallreservations->conditiontype = MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED;
        $cancelallreservations->ccmanager = $template->ccmanager;
        $cancelallreservations->cancelled = 1;
        $cancelallreservations->status = $template->status;
        $cancelallreservations->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED] = $cancelallreservations;
    } else {
        $missingtemplates[] = 'allreservationcancel';
    }

    if (isset($templates['sessioncancellation'])) {
        $template = $templates['sessioncancellation'];
        $sessioncancellation = new facetoface_notification($defaults, false);
        $sessioncancellation->title = $template->title;
        $sessioncancellation->body = $template->body;
        $sessioncancellation->managerprefix = $template->managerprefix;
        $sessioncancellation->conditiontype = MDL_F2F_CONDITION_SESSION_CANCELLATION;
        $sessioncancellation->ccmanager = $template->ccmanager;
        $sessioncancellation->cancelled = 1;
        $sessioncancellation->status = $template->status;
        $sessioncancellation->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_SESSION_CANCELLATION] = $sessioncancellation;
    } else {
        $missingtemplates[] = 'sessioncancellation';
    }

    if (isset($templates['registrationexpired'])) {
        $template = $templates['registrationexpired'];
        $registrationexpired = new facetoface_notification($defaults, false);
        $registrationexpired->title = $template->title;
        $registrationexpired->body = $template->body;
        $registrationexpired->managerprefix = $template->managerprefix;
        $registrationexpired->conditiontype = MDL_F2F_CONDITION_REGISTRATION_DATE_EXPIRED;
        $registrationexpired->ccmanager = $template->ccmanager;
        $registrationexpired->status = $template->status;
        $registrationexpired->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_REGISTRATION_DATE_EXPIRED] = $registrationexpired;
    } else {
        $missingtemplates[] = 'registrationexpired';
    }

    if (isset($templates['registrationclosure'])) {
        $template = $templates['registrationclosure'];
        $registrationclosure = new facetoface_notification($defaults, false);
        $registrationclosure->title = $template->title;
        $registrationclosure->body = $template->body;
        $registrationclosure->managerprefix = $template->managerprefix;
        $registrationclosure->conditiontype = MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS;
        $registrationclosure->ccmanager = $template->ccmanager;
        $registrationclosure->status = $template->status;
        $registrationclosure->requested = 1;
        $registrationclosure->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS] = $registrationclosure;
    } else {
        $missingtemplates[] = 'registrationclosure';
    }

    if (isset($templates['waitlistautoclean'])) {
        $template = $templates['waitlistautoclean'];
        $waitlistautoclean = new facetoface_notification($defaults, false);
        $waitlistautoclean->title = $template->title;
        $waitlistautoclean->body = $template->body;
        $waitlistautoclean->managerprefix = $template->managerprefix;
        $waitlistautoclean->conditiontype = MDL_F2F_CONDITION_WAITLIST_AUTOCLEAN;
        $waitlistautoclean->ccmanager = $template->ccmanager;
        $waitlistautoclean->status = $template->status;
        $waitlistautoclean->requested = 1;
        $waitlistautoclean->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_WAITLIST_AUTOCLEAN] = $waitlistautoclean;
    } else {
        $missingtemplates[] = 'waitlistautoclean';
    }

    if (isset($templates['undercapacity'])) {
        $template = $templates['undercapacity'];
        $undercapacity = new facetoface_notification($defaults, false);
        $undercapacity->title = $template->title;
        $undercapacity->body = $template->body;
        $undercapacity->managerprefix = $template->managerprefix;
        $undercapacity->conditiontype = MDL_F2F_CONDITION_SESSION_UNDER_CAPACITY;
        $undercapacity->ccmanager = $template->ccmanager;
        $undercapacity->status = $template->status;
        $undercapacity->requested = 1;
        $undercapacity->templateid = $template->id;
        $notifications[MDL_F2F_CONDITION_SESSION_UNDER_CAPACITY] = $undercapacity;
    } else {
        $missingtemplates[] = 'undercapacity';
    }

    return array($notifications, $missingtemplates);
}

/**
 * Used when loading the list of facetoface notification templates.
 *
 * This will search the title, body and managerprefix of all notification templates for uses of the
 * placeholders that were deprecated in 9.0.
 *
 * @return array containing the ids of any notifications with old placeholders. Will be an empty array if none found.
 * @throws coding_exception
 */
function facetoface_notification_get_templates_with_old_placeholders() {
    global $DB;

    $cacheoptions = array(
        'simplekeys' => true,
        'simpledata' => true
    );
    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_facetoface', 'notificationtpl', array(), $cacheoptions);
    $oldnotifcations = $cache->get('oldnotifications');

    if ($oldnotifcations !== false) {
        return $oldnotifcations;
    }

    // Legacy
    $oldplaceholderroomlegacy = '%' . $DB->sql_like_escape(get_string('placeholder:room', 'facetoface')) . '%';
    $oldplaceholdervenuelegacy = '%' . $DB->sql_like_escape(get_string('placeholder:venue', 'facetoface')) . '%';
    $oldplaceholderlocationlegacy = '%' . $DB->sql_like_escape(get_string('placeholder:location', 'facetoface')) . '%';
    $oldplaceholderalldateslegacy = '%' . $DB->sql_like_escape(get_string('placeholder:alldates', 'facetoface')) . '%';

    // Replace.
    $oldplaceholderroom = '%' . $DB->sql_like_escape('[session:room]') . '%';
    $oldplaceholdervenue = '%' . $DB->sql_like_escape('[session:venue]') . '%';
    $oldplaceholderlocation = '%' . $DB->sql_like_escape('[session:location]') . '%';
    $oldplaceholderalldates = '%' . $DB->sql_like_escape('[alldates]') . '%';

    $sql = 'SELECT id
              FROM {facetoface_notification_tpl}
             WHERE ' . $DB->sql_like('title', ':titleroom') .
              ' OR ' . $DB->sql_like('title', ':titlevenue') .
              ' OR ' . $DB->sql_like('title', ':titlelocation') .
              ' OR ' . $DB->sql_like('title', ':titlealldates') .
              ' OR ' . $DB->sql_like('title', ':titleroomlegacy') .
              ' OR ' . $DB->sql_like('title', ':titlevenuelegacy') .
              ' OR ' . $DB->sql_like('title', ':titlelocationlegacy') .
              ' OR ' . $DB->sql_like('title', ':titlealldateslegacy') .
              ' OR ' . $DB->sql_like('body', ':bodyroom') .
              ' OR ' . $DB->sql_like('body', ':bodyvenue') .
              ' OR ' . $DB->sql_like('body', ':bodylocation') .
              ' OR ' . $DB->sql_like('body', ':bodyalldates') .
              ' OR ' . $DB->sql_like('body', ':bodyroomlegacy') .
              ' OR ' . $DB->sql_like('body', ':bodyvenuelegacy') .
              ' OR ' . $DB->sql_like('body', ':bodylocationlegacy') .
              ' OR ' . $DB->sql_like('body', ':bodyalldateslegacy') .
              ' OR ' . $DB->sql_like('managerprefix', ':managerprefixroom') .
              ' OR ' . $DB->sql_like('managerprefix', ':managerprefixvenue') .
              ' OR ' . $DB->sql_like('managerprefix', ':managerprefixlocation') .
              ' OR ' . $DB->sql_like('managerprefix', ':managerprefixalldates') .
              ' OR ' . $DB->sql_like('managerprefix', ':managerprefixroomlegacy') .
              ' OR ' . $DB->sql_like('managerprefix', ':managerprefixvenuelegacy') .
              ' OR ' . $DB->sql_like('managerprefix', ':managerprefixlocationlegacy') .
              ' OR ' . $DB->sql_like('managerprefix', ':managerprefixalldateslegacy');

    $params = array(
        'titleroom' => $oldplaceholderroom,
        'titlevenue' => $oldplaceholdervenue,
        'titlelocation' => $oldplaceholderlocation,
        'titlealldates' => $oldplaceholderalldates,
        'bodyroom' => $oldplaceholderroom,
        'bodyvenue' => $oldplaceholdervenue,
        'bodylocation' => $oldplaceholderlocation,
        'bodyalldates' => $oldplaceholderalldates,
        'managerprefixroom' => $oldplaceholderroom,
        'managerprefixvenue' => $oldplaceholdervenue,
        'managerprefixlocation' => $oldplaceholderlocation,
        'managerprefixalldates' => $oldplaceholderalldates,
        'titleroomlegacy' => $oldplaceholderroomlegacy,
        'titlevenuelegacy' => $oldplaceholdervenuelegacy,
        'titlelocationlegacy' => $oldplaceholderlocationlegacy,
        'titlealldateslegacy' => $oldplaceholderalldateslegacy,
        'bodyroomlegacy' => $oldplaceholderroomlegacy,
        'bodyvenuelegacy' => $oldplaceholdervenuelegacy,
        'bodylocationlegacy' => $oldplaceholderlocationlegacy,
        'bodyalldateslegacy' => $oldplaceholderalldateslegacy,
        'managerprefixroomlegacy' => $oldplaceholderroomlegacy,
        'managerprefixvenuelegacy' => $oldplaceholdervenuelegacy,
        'managerprefixlocationlegacy' => $oldplaceholderlocationlegacy,
        'managerprefixalldateslegacy' => $oldplaceholderalldateslegacy
    );

    $oldnotifcations = $DB->get_fieldset_sql($sql, $params);
    $cache->set('oldnotifications', $oldnotifcations);

    return $oldnotifcations;
}

/**
 * Remove all whitespaces, new lines, <br> html tags
 *
 * @param $string
 * @return string
 */
function facetoface_prepare_match($string) {
    $string = preg_replace('/\s*\s+/S', '', $string);
    $string = str_replace("\n", '', $string);
    $string = preg_replace("#<br\s*/?>#i", "", $string);
    return $string;
}

/**
 * Compare 2 notifications by tile, body and mangerprefix
 *
 * @param $data1 stdClass updated activity notification
 * @param $data2 stdClass default notification template
 * @return bool
 */
function facetoface_notification_match($data1, $data2) {

    $title1 = facetoface_prepare_match($data1->title);
    $title2 = facetoface_prepare_match($data2->title);

    $body1 = facetoface_prepare_match($data1->body);
    $body2 = facetoface_prepare_match($data2->body);

    $managerprefix1 = facetoface_prepare_match($data1->managerprefix);
    $managerprefix2 = facetoface_prepare_match($data2->managerprefix);

    if ($title1 != $title2 || $body1 != $body2 || $managerprefix1 != $managerprefix2) {
        return false;
    }
    return true;
}

/**
 * Get a list reference -> conditiontype of notification templates which are not existed in active seminars.
 *
 * @return array list of notification templates
 */
function facetoface_notification_get_missing_templates() {
    global $DB;

    $type = MDL_F2F_NOTIFICATION_AUTO;
    $result = [];
    $sqlqueries = [];
    foreach (facetoface_notification::get_references() as $reference => $conditiontype) {
        $sqlqueries[] =
            "SELECT f.id as fid, fnt.reference, fnt.title
               FROM {facetoface_notification_tpl} fnt
         CROSS JOIN {facetoface} f
          LEFT JOIN {facetoface_notification} fn
                 ON (fn.type = $type AND fn.facetofaceid=f.id AND fn.conditiontype = $conditiontype)
              WHERE fnt.reference = '$reference' AND fn.id IS NULL";
    }
    $sql = implode(" UNION ", $sqlqueries);
    $records = $DB->get_recordset_sql($sql);

    foreach ($records as $record) {
        $result[$record->reference] = true;
    }
    return $result;
}

/**
 * Create new seminar notifications for active seminars from notification templates.
 *
 * @param int $conditiontype seminar notification condition type
 * @return int how many records are affected
 */
function facetoface_notification_restore_missing_template($conditiontype) {
    global $DB;

    $referencelist = facetoface_notification::get_references();
    $reference = array_flip($referencelist)[$conditiontype];

    $sql = "SELECT f.id as fid, f.course as courseid, fnt.reference
              FROM {facetoface_notification_tpl} fnt
        CROSS JOIN {facetoface} f
         LEFT JOIN {facetoface_notification} fn
                ON (fn.type = :type AND fn.facetofaceid=f.id AND fn.conditiontype = :conditiontype)
             WHERE fnt.reference = :reference AND fn.id IS NULL";
    $params = ['type' => MDL_F2F_NOTIFICATION_AUTO, 'conditiontype' => $conditiontype, 'reference' => $reference];
    if (!($records = $DB->get_recordset_sql($sql, $params))) {
        return 0;
    }

    $template = $DB->get_record('facetoface_notification_tpl', ['reference' => $reference], '*', MUST_EXIST);

    $default = [];
    $default['type'] = MDL_F2F_NOTIFICATION_AUTO;
    $default['booked'] = 0;
    $default['waitlisted'] = 0;
    $default['cancelled'] = 0;
    $default['requested'] = 0;
    $default['issent'] = 0;
    $default['status'] = 1;
    $default['ccmanager'] = 0;

    $rows = 0;
    foreach ($records as $record) {
        $rows++;
        $default['facetofaceid'] = $record->fid;
        $default['courseid'] = $record->courseid;

        $notification = new facetoface_notification($default, false);
        $notification->title = $template->title;
        $notification->body = $template->body;
        $notification->managerprefix = $template->managerprefix;
        $notification->conditiontype = $conditiontype;
        $notification->ccmanager = $template->ccmanager;
        $notification->status = $template->status;
        $notification->templateid = $template->id;

        switch ($conditiontype) {
            case MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION:
            case MDL_F2F_CONDITION_RESERVATION_CANCELLED:
            case MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED:
            case MDL_F2F_CONDITION_SESSION_CANCELLATION:
                $notification->cancelled = 1;
                break;
            case MDL_F2F_CONDITION_BEFORE_SESSION:
                $notification->scheduleunit = MDL_F2F_SCHEDULE_UNIT_DAY;
                $notification->scheduleamount = 2;
                $notification->booked = 1;
                break;
            case MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE:
                $notification->booked = 1;
                $notification->waitlisted = 1;
                break;
            case MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS:
                $notification->requested = 1;
                break;
        }

        $notification->save();
    }
    return $rows;
}
