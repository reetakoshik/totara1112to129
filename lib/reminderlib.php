<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Reminder functionality
 *
 * @package   totara
 * @copyright 2010 Catalyst IT Ltd
 * @author    Aaron Barnes <aaronb@catalyst.net.nz>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/completion/data_object.php');


/**
 * Return an array of all reminders set for a course
 *
 * @access  public
 * @param   $courseid   int
 * @return  array
 */
function get_course_reminders($courseid) {

    // Get all reminder objects
    $where = array(
        'courseid'  => $courseid,
        'deleted'   => 0
    );

    $reminders = reminder::fetch_all($where);

    // Make sure we always return an array
    if ($reminders) {
        return $reminders;
    }
    else {
        return array();
    }
}


/**
 * Reminder object, defines what the reminder
 * is tracking, it's title, etc.
 *
 * No much use by itself, but is required to
 * associate reminder_message's with
 *
 * @access  public
 */
class reminder extends data_object {

    /**
     * DB table
     * @var string  $table
     */
    public $table = 'reminder';

    /**
     * Array of required table fields, must start with 'id'.
     * @var array   $required_fields
     */
    public $required_fields = array('id', 'courseid', 'title', 'type',
        'timecreated', 'timemodified', 'modifierid', 'config', 'deleted');

    /**
     * Array of text table fields.
     * @var array   $text_fields
     */
    public $text_fields = array('title', 'config');

    /**
     * The course this reminder is associated with
     * @access  public
     * @var     int
     */
    public $courseid;

    /**
     * Reminder title, for configuration display purposes
     * @access  public
     * @var     string
     */
    public $title;

    /**
     * Reminder message type - needs to be supported in code
     * @access  public
     * @var     string
     */
    public $type;

    /**
     * Time the reminder was created
     * @access  public
     * @var     int
     */
    public $timecreated;

    /**
     * Time the reminder or it's messages were last modified
     * @access  public
     * @var     int
     */
    public $timemodified;

    /**
     * ID of the last user to modifiy the reminder or messages
     * @access  public
     * @var     int
     */
    public $modifierid;

    /**
     * Config data, used by the code handling the reminder's "type"
     * @access  public
     * @var     mixed
     */
    public $config;

    /**
     * Deleted flag
     * @access  public
     * @var     int
     */
    public $deleted;

    /**
     * Reminder period
     * @access public
     * @var int
     */
    public $period;


    /**
     * Finds and returns all data_object instances based on params.
     *
     * @param array $params associative arrays varname=>value
     * @return array array of data_object insatnces or false if none found.
     */
    public static function fetch_all($params) {
        return self::fetch_all_helper(
            'reminder',
            'reminder',
            $params
        );
    }


    /**
     * Get all associated reminder_message objects
     *
     * @access  public
     * @return  array
     */
    public function get_messages() {
        // Get any non-deleted messages
        $messages = reminder_message::fetch_all(
            array(
                'reminderid'    => $this->id,
                'deleted'       => 0
            )
        );

        // Make sure we always return an array
        if ($messages) {
            return $messages;
        }
        else {
            return array();
        }
    }


    /**
     * Return an object containing all the reminder and
     * message data in a format that suits the reminder_edit_form
     * definition
     *
     * @access  public
     * @return  object
     */
    public function get_form_data() {

        $formdata = clone $this;

        // Get tracked activity/course
        if (!empty($this->config)) {
            $config = unserialize($this->config);
            $formdata->tracking = $config['tracking'];
            $formdata->requirement = isset($config['requirement']) ? $config['requirement'] : '';
        }

        // Get an existing reminder messages
        foreach (array('invitation', 'reminder', 'escalation') as $mtype) {

            // Generate property names
            $nosend = "{$mtype}dontsend";
            $p = "{$mtype}period";
            $sm = "{$mtype}skipmanager";
            $s = "{$mtype}subject";
            $m = "{$mtype}message";

            $message = new reminder_message(
                array(
                    'reminderid'    => $this->id,
                    'deleted'       => 0,
                    'type'          => $mtype
                )
            );

            $formdata->$p = $message->period;
            $formdata->$sm = $message->copyto;
            $formdata->$s = $message->subject;
            $formdata->$m = $message->message;

            // If the message doesn't exist, and this is
            // a saved reminder - mark it as nosend
            if ($this->id && !$message->id) {
                $formdata->$nosend = 1;
            }
        }

        return $formdata;
    }

    /**
     * TOTARA - delete reminder and related reminder data with course deletion.
     *
     * @access public
     * @return void
     */
    public function delete() {
        global $DB;

        // Get all reminder_message objects
        if ($messages = reminder_message::fetch_all(array('reminderid' => $this->id))) {
            foreach ($messages as $message) {
                $message->delete();
            }
        }
        // Delete all reminder messages which are sent to users.
        $DB->delete_records('reminder_sent', array('reminderid' => $this->id));
        // Delete reminder.
        parent::delete();
    }

    /**
     * TOTARA method for checking period values from all messages linked to this
     * reminder against another number of days (such as the value of $CFG->reminder_maxtimesincecompletion).
     *
     * @param int $days for comparing against the period value of messages.
     * @return bool true if a message has period value greater than days.
     */
    public function has_message_with_period_greater_or_equal($days) {
        foreach (array('invitation', 'reminder', 'escalation') as $mtype) {
            $message = reminder_message::fetch(
                array(
                    'reminderid'    => $this->id,
                    'type'          => $mtype,
                    'deleted'       => 0
                )
            );
            if (!$message) {
                // There is no saved message.
                continue;
            }
            if ($message->period >= $days) {
                return true;
            }
        }

        return false;
    }
}


/**
 * Reminder_message object, defines the reminder
 * period, and email contents
 *
 * @access  public
 */
class reminder_message extends data_object {

    /**
     * DB table
     * @var string  $table
     */
    public $table = 'reminder_message';

    /**
     * Array of required table fields, must start with 'id'.
     * @var array   $required_fields
     */
    public $required_fields = array('id', 'reminderid', 'type', 'period',
        'subject', 'message', 'copyto', 'deleted');

    /**
     * Array of text table fields.
     * @var array   $text_fields
     */
    public $text_fields = array('copyto', 'subject', 'message');

    /**
     * Reminder record this message is associated with
     * @access  public
     * @var     int
     */
    public $reminderid;

    /**
     * Reminder message type - needs to be supported in code
     * @access  public
     * @var     string
     */
    public $type;

    /**
     * # of days after the tracked event occurs the message
     * needs to be sent
     * @access  public
     * @var     int
     */
    public $period;

    /**
     * Email message subject
     *
     * Will be run through reminder_email_substitutions()
     * @access  public
     * @var     string
     */
    public $subject;

    /**
     * Email message content
     *
     * Will be run through reminder_email_substitutions()
     * @access  public
     * @var     string
     */
    public $message;

    /**
     * Toggle where the email is copied to the users manager
     *
     * Badly named at the moment, as the only time the email
     * is copied is when the message is of type "escalation" and
     * $copyto is set to 0
     *
     * @TODO FIX COL NAME
     *
     * @access  public
     * @var     int
     */
    public $copyto;

    /**
     * Deleted flag
     * @access  public
     * @var     int
     */
    public $deleted;


    /**
     * Finds and returns a data_object instance based on params.
     * @static abstract
     *
     * @param array $params associative arrays varname=>value
     * @return object data_object instance or false if none found.
     */
    public static function fetch($params) {
        return self::fetch_helper(
            'reminder_message',
            'reminder_message',
            $params
        );
    }


    /**
     * Finds and returns all data_object instances based on params.
     *
     * @param array $params associative arrays varname=>value
     * @return array array of data_object insatnces or false if none found.
     */
    public static function fetch_all($params) {
        return self::fetch_all_helper(
            'reminder_message',
            'reminder_message',
            $params
        );
    }
}

/**
 * Make placeholder substitutions to a string (for make=ing emails dynamic)
 *
 * @access  private
 * @param   $content    string  String to make substitutions to
 * @param   $user       object  Recipients details
 * @param   $course     object  The reminder's course
 * @param   $message    object  The reminder message object
 * @param   $reminder   object  The reminder object
 * @return  string
 */
function reminder_email_substitutions($content, $user, $course, $message, $reminder) {
    global $CFG;

    // Generate substitution array
    $place = array();
    $subs = array();

    // User details
    $place[] = '[firstname]';
    $subs[] = $user->firstname;
    $place[] = '[lastname]';
    $subs[] = $user->lastname;

    // Course details
    $place[] = '[coursepageurl]';
    $subs[] = "{$CFG->wwwroot}/course/view.php?id={$course->id}";
    $place[] = '[coursename]';
    $subs[] = $course->fullname;

    // Manager name
    $place[] = '[managername]';
    $subs[] = !empty($user->manager) ? fullname($user->manager) : get_string('nomanagermessage', 'totara_coursecatalog');

    // Day counts
    $place[] = '[days counter up]';
    $subs[] = $message->period;
    $place[] = '[days count down]';
    $subs[] = $message->deadline;

    // Make substitutions
    $content = str_replace($place, $subs, $content);

    return $content;
}


/**
 * Check that required time has still passed even if ignoring weekends
 *
 * @access  private
 * @param   $timestamp  int Event timestamp
 * @param   $period     int Number of days since
 * @param   $check      int Timestamp to check against (optional, used in tests)
 * @return  boolean
 */
function reminder_check_businessdays($timestamp, $period, $check = null) {
    // If no period, then it's instantaneous and has already passed
    if (!$period) {
        return true;
    }

    // Setup current time
    if (!$check) {
        $check = time();
    }

    // Loop through each day and if not a weekend, add it to the timestamp
    for ($reminderday = 1; $reminderday <= $period; $reminderday++ ) {

        // Add 24 hours to the timestamp
        $timestamp += (24 * 3600);

        // Saturdays and Sundays are not included in the
        // reminder period as entered by the user, extend
        // that period by 1
        if (!reminder_is_businessday($timestamp)) {
            $period++;
        }

        // If the timestamp move into the future after ignoring weekends,
        // return false
        if ($timestamp > $check) {
            return false;
        }
    }

    // Timestamp must still be in the past
    return true;
}

/**
 * Determines whether or not the given timestamp was on a business day.
 *
 * @param $timestamp
 * @return boolean
 */
function reminder_is_businessday($timestamp){
    // Converts the timestamp to the day of the week running from 0 = Sunday to 6 = Saturday
    //use %w instead of %u for Windows compatability
    $day = userdate($timestamp, '%w');
    return ($day != 0 && $day != 6);
}

/**
 * TOTARA - Remove all reminders and related reminder data
 *
 * @param int $courseid The course ID
 */
function delete_reminders($courseid) {

    // Get all course reminder objects
    if ($reminders = reminder::fetch_all(array('courseid' => $courseid))) {
        foreach ($reminders as $reminder) {
            $reminder->delete();
            \totara_core\event\reminder_deleted::create_from_reminder($reminder)->trigger();
        }
    }

    return true;
}