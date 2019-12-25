<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 Mind Click Limited
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
 * @copyright  2015 Mind Click Limited <http://mind-click.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Joby Harding <joby@77gears.com>
 * @package    totara_message
 */

namespace totara_message\event;

use core_user;
use context_system;

defined('MOODLE_INTERNAL') || die();

/**
 * Class alert_sent
 *
 * @package totara_message
 */
class alert_sent extends \core\event\base {

    /**
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud']        = 'c';
        $this->data['edulevel']    = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'message_metadata';
    }

    /**
     * Implements get_name().
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventalertsent', 'totara_message');
    }

    /**
     * Implements get_description().
     *
     * @return string
     */
    public function get_description() {

        if (\core_user::is_real_user($this->userid)) {
            $description  = "The user with id '{$this->userid}' sent an alert of the type '{$this->other['msgtype']}'";
            $description .= " to the user with id '{$this->relateduserid}'.";

            return $description;
        }

        $description  = "An alert of type '{$this->other['msgtype']}' was sent by the system";
        $description .= " to the user with id '{$this->relateduserid}'.";

        return $description;
    }

    /**
     * Create an event instance from given message data.
     *
     * @param \stdClass $metadata Object as returned by tm_insert_metadata().
     * @return \totara_message\event\alert_send
     */
    public static function create_from_message_data(\stdClass $eventdata, $messageid) {
        global $DB;

        $message = $DB->get_record('message', array('id' => $messageid));
        $metadata = $DB->get_record('message_metadata', array('messageid' => $messageid));

        self::$preventcreatecall = false;
        $event = self::create(
            array(
                'objectid'      => $metadata->id,
                'context'       => context_system::instance(),
                'userid'        => $message->useridfrom,
                'relateduserid' => $message->useridto,
                'other'         => array(
                    'messageid'    => $messageid,
                    'msgtype'      => $eventdata->msgtype,
                ),
            )
        );
        self::$preventcreatecall = true;

        $event->add_record_snapshot('message_metadata', $metadata);
        $event->add_record_snapshot('message', $message);

        return $event;
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    public function validate_data() {

        parent::validate_data();

        if (self::$preventcreatecall) {
            throw new \coding_exception('Cannot call create() directly, use create_from_message_data() instead.');
        }
    }
}
