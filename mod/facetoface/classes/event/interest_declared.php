<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a user express interest in a facetoface activity.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - facetoface Facetoface's ID where the interest was expressed.
 *
 * }
 *
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */
class interest_declared extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call. */
    protected static $preventcreatecall = true;

    /** @var \stdClass */
    protected $instance;

    /**
     * Create from instance.
     *
     * @param \stdClass $facetoface_interest
     * @param \context_module $context
     * @return interest_declared
     */
    public static function create_from_instance(\stdClass $facetoface_interest, \context_module $context) {
        $data = array(
            'context'  => $context,
            'objectid' => $facetoface_interest->id,
            'other' => array(
                'facetoface' => $facetoface_interest->facetoface
            )
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;
        $event->instance = $facetoface_interest;

        return $event;
    }

    /**
     * Get instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \stdClass session
     */
    public function get_instance() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_instance is intended for event observers only');
        }

        return $this->instance;
    }

    /**
     * Init method
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'facetoface_interest';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventinterestdeclared', 'mod_facetoface');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The User with id {$this->userid} declared interest in the Face-to-face activity with the id {$this->other['facetoface']}.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/facetoface/view.php', array('f' => $this->other['facetoface']));
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_instance() instead.');
        }

        if (!isset($this->other['facetoface'])) {
            throw new \coding_exception('facetoface must be set in $other');
        }

        parent::validate_data();
    }
}
