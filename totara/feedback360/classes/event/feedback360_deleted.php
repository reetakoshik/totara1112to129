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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_feedback360
 */

namespace totara_feedback360\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a feedback360 deleted.
 *
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_feedback360
 */
class feedback360_deleted extends \core\event\base {

    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * The instance used to create the event.
     * @var \feedback360
     */
    protected $feedback360;

    /**
     * Create instance of event.
     *
     * @param \stdClass $instance An instance of feedback360.
     * @return feedback360_deleted
     */
    public static function create_from_instance(\feedback360 $instance) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context_system::instance(),
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        $event->feedback360 = $instance;
        $event->add_record_snapshot('feedback360', $instance->get());
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Get feedback360 instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \feedback360
     */
    public function get_feedback360() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_feedback360() is intended for event observers only');
        }
        return $this->feedback360;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'feedback360';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventdeletedfeedback', 'totara_feedback360');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
         return "The feedback360 {$this->objectid} was deleted";
    }

    /**
     * Returns relevant url.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/feedback360/manage.php');
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

        parent::validate_data();
    }
}
