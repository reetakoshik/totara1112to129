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
 * @package totara_core
 */

namespace totara_core\event;
defined('MOODLE_INTERNAL') || die();

/**
 * User undeleted event.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string username: username of the user.
 * }
 *
 * @since   Totara 2.6
 * @package totara_core
 * @author  David Curry <david.curry@totaralms.com>
 */
class user_undeleted extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @param \stdClass $user
     * @return user_undeleted
     */
    public static function create_from_user(\stdClass $user) {
        $data = array(
            'objectid' => $user->id,
            'context' => \context_user::instance($user->id),
            'other' => array(
                'username' => $user->username,
            )
        );
        $event = self::create($data);
        $event->add_record_snapshot('user', $user);
        return $event;
    }

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['objecttable'] = 'user';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventundeleted', 'totara_core');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return 'User ' . $this->other['username'] . ' undeleted';
    }

    /**
     * Return name of the legacy event, which is replaced by this event.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'user_undeleted';
    }

    /**
     * Return user_undeleted legacy event data.
     *
     * @return \stdClass user data.
     */
    protected function get_legacy_eventdata() {
        $user = $this->get_record_snapshot('user', $this->data['objectid']);
        return $user;
    }

    /**
     * Returns array of parameters to be passed to legacy add_to_log() function.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        $user = $this->get_record_snapshot('user', $this->data['objectid']);
        return array(SITEID, 'user', 'undelete', "view.php?id=".$user->id, $user->firstname.' '.$user->lastname);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['username'])) {
            throw new \coding_exception('username must be set in $other.');
        }
    }
}
