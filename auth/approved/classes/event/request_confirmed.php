<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

namespace auth_approved\event;

/**
 * Class request_confirmed
 */
final class request_confirmed extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @param \stdClass $request
     * @return request_confirmed
     */
    public static function create_from_request(\stdClass $request) {
        $other = [
            'email' => $request->email,
            'username' => $request->username
        ];

        $data = array(
            'context' => \context_system::instance(),
            'objectid' => $request->id,
            'other' => $other
        );
        /** @var request_confirmed $event */
        $event = self::create($data);
        $event->add_record_snapshot('auth_approved_request', $request);
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $context = $this->data['other'];

        $email = $context['email'];
        $username = $context['username'];
        return "$username ($email) confirmed email address";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventrequestconfirmed', 'auth_approved');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/auth/approved/index.php');
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'auth_approved_request';
    }
}
