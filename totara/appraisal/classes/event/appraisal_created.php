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
 * @package totara_appraisal
 */

namespace totara_appraisal\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when an appraisal is created.
 *
 * @property-read array $other {
 *      Extra information about the event.
 * }
 *
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_appraisal
 */
class appraisal_created extends \core\event\base {

    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * The instance used to create the event.
     * @var \appraisal
     */
    protected $appraisal;

    /**
     * Create instance of event.
     *
     * @param   \appraisal $instance An appraisal class.
     * @return  appraisal_created
     */
    public static function create_from_instance(\appraisal $instance) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context_system::instance(),
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        $event->appraisal = $instance;
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Get appraisal instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \appraisal
     */
    public function get_appraisal() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_appraisal() is intended for event observers only');
        }
        return $this->appraisal;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'appraisal';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcreatedappraisal', 'totara_appraisal');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The appraisal {$this->objectid} was created";
    }

    /**
     * Returns relevant url.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/appraisal/general.php', array('id' => $this->objectid));
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    public function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_instance() instead.');
        }

        parent::validate_data();
    }
}
