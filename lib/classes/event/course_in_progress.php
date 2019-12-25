<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package     core
 * @author      David Curry <david.curry@totaralearning.com>
 * @author      Nathan Lewis <nathan.lewis@totaralearning.com>
 */

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Course started event class.
 *
 * @property-read int $relateduserid user who started the course
 *
 * @package    totara_core
 * @author     David Curry <david.curry@totaralearning.com>
 * @since      Totara 9.1
 */
class course_in_progress extends \core\event\base {

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['objecttable'] = 'course_completions';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcourseinprogress', 'totara_core');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->relateduserid}' was marked in progress in the course with id '{$this->courseid}'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/report/completion/index.php', array('course' => $this->courseid));
    }

    /**
     * Return name of the legacy event, which is replaced by this event.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'course_in_progress';
    }

    /**
     * Return course_in_progress legacy event data.
     *
     * @return \stdClass completion data.
     */
    protected function get_legacy_eventdata() {
        return $this->get_record_snapshot('course_completions', $this->objectid);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
    }

    public static function get_objectid_mapping() {
        // No mapping available for completion records.
        return array('db' => 'course_completions', 'restore' => base::NOT_MAPPED);
    }
}
