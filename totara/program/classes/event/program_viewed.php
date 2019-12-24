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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_program
 */


namespace totara_program\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a program is viewed.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - section The section of the program that is being viewed.
 *
 * }
 *
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_program
 */
class program_viewed extends \core\event\base {

    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Create event from data.
     *
     * @param   array $dataevent Array with the data needed to create the event.
     * @return  \totara_program\event\program_viewed $event
     */
    public static function create_from_data(array $dataevent) {
        $data = array(
            'objectid' => $dataevent['id'],
            'context' => \context_program::instance($dataevent['id']),
            'other' => $dataevent['other']
        );

        if (isset($dataevent['userid'])) {
           $data['userid'] = $dataevent['userid'];
        }
        if (isset($dataevent['relateduserid'])) {
            // The related user id, if the current user is viewing the program from another users perspective.
            $data['relateduserid'] = $dataevent['relateduserid'];
        }

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'prog';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventviewed', 'totara_program');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        switch ($this->other['section']) {
            case "general":
                $description = "The program {$this->objectid} was viewed by user {$this->userid}";
                break;
            case "required":
                $description = "The program {$this->objectid} was viewed by user {$this->userid}. (Required-Learning)";
                break;
            case "content":
                $description = "The content section for the program {$this->objectid} was viewed by user {$this->userid}";
                break;
            case "assignments":
                $description = "The assignments section for the program {$this->objectid} was viewed by user {$this->userid}";
                break;
            case "messages":
                $description = "The messages section for the program {$this->objectid} was viewed by user {$this->userid}";
                break;
            case "exceptions":
                $description = "The exceptions section for the program {$this->objectid} was viewed by user {$this->userid}";
                break;
            default:
                $description = 'Description for the section not specified.';
                break;
        }
        return $description;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        switch ($this->other['section']) {
            case "general":
                $url = new \moodle_url('/totara/program/view.php', array('id' => $this->objectid));
                break;
            case "required":
                $userid = $this->userid;
                if (!empty($this->relateduserid)) {
                    $userid = $this->relateduserid;
                }
                $url = new \moodle_url('/totara/program/required.php', array('id' => $this->objectid, 'userid' => $userid));
                break;
            case "content":
                $url = new \moodle_url('/totara/program/edit_content.php', array('id' => $this->objectid));
                break;
            case "assignments":
                $url = new \moodle_url('/totara/program/edit_assignments.php', array('id' => $this->objectid));
                break;
            case "messages":
                $url = new \moodle_url('/totara/program/edit_messages.php', array('id' => $this->objectid));
                break;
            case "exceptions":
                $url = new \moodle_url('/totara/program/exceptions.php', array('id' => $this->objectid));
                break;
            default:
                $url = new \moodle_url('/totara/program/view.php', array('id' => $this->objectid));
                break;
        }
        return $url;
    }


    /**
     * Return legacy data for add_to_log().
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        switch ($this->other['section']) {
            case "general":
                $data = array(SITEID, 'program', 'view', 'view.php?id=' . $this->objectid . '&amp;userid=' . $this->userid,
                    'ID: ' . $this->objectid);
                break;
            case "required":
                $data = array(SITEID, 'program', 'view required', 'required.php?userid=' . $this->userid,
                    'ID: ' . $this->objectid);
                break;
            case "content":
                $data = array(SITEID, 'program', 'view content', 'edit_content.php?id=' . $this->objectid,
                    'ID: ' . $this->objectid);
                break;
            case "assignments":
                $data = array(SITEID, 'program', 'view assignments', 'edit_assignments.php?id=' . $this->objectid,
                    'ID: ' . $this->objectid);
                break;
            case "messages":
                $data = array(SITEID, 'program', 'view messages', 'edit_messages.php?id=' . $this->objectid,
                    'ID: ' . $this->objectid);
                break;
            case "exceptions":
                $data = array(SITEID, 'program', 'view exceptions', 'exceptions.php?id=' . $this->objectid,
                    'ID: ' . $this->objectid);
                break;
            default:
                $data = array();
                break;
        }

        return $data;
    }

    /**
     * Validate data passed to this event.
     *
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_data() instead.');
        }

        parent::validate_data();
        // The section parameter indicates what part of the program the user is viewing.
        if (!isset($this->other['section'])) {
            throw new \coding_exception('section must be set in $other.');
        }

        // Check the section options has a valid value.
        $validvalues = array("general", "required", "content", "assignments", "messages", "exceptions");
        if (!in_array($this->other['section'], $validvalues)) {
            throw new \coding_exception('section must be in the valid values for this parameter');
        }
    }
}
