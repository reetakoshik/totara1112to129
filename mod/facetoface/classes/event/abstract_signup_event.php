<?php
/*
 * This file is part of Totara LMS
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\event;

use mod_facetoface\signup;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when users requested approval to be booked on seminar
 *
 * @property-read array $other {
 * Extra information about the event.
 * - sessionid Seminar Event ID.
 * }
 */
abstract class abstract_signup_event extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call. */
    protected static $preventcreatecall = true;

    /**
     * @var signup
     */
    protected $signup = null;

    /**
     * Create instance of event.
     *
     * @param signup $signup
     * @param \context_module $context
     * @return booking_booked
     */
    public static function create_from_signup(signup $signup, \context_module $context) {
        $data = [
            'context' => $context,
            'other'  => [
                'signupid' => $signup->get_id(),
                'sessionid' => $signup->get_seminar_event()->get_id()
            ]
        ];

        static::$preventcreatecall = false;
        $event = static::create($data);
        static::$preventcreatecall = true;
        $event->signup = $signup;

        return $event;
    }

    /**
     * Get seminar signup instance
     * @return signup
     */
    public function get_signup(): signup {
        if (!($this->signup instanceof signup)) {
            $this->signup = new signup($this->data['other']['signupid']);
        }
        return $this->signup;
    }

    /**
     * Init method
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = static::LEVEL_PARTICIPATING;
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (static::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_session() instead.');
        }

        if (!isset($this->other['sessionid'])) {
            throw new \coding_exception('sessionid must be set in $other.');
        }

        parent::validate_data();
    }
}
