<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_appraisal
 */


namespace totara_appraisal\event;
defined('MOODLE_INTERNAL') || die();

use \core\event\base;

class appraisal_role_page_saved extends base {

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'appraisal';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventrolepagesaved', 'totara_appraisal');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The appraisal page {$this->other['pageid']} for learner {$this->relateduserid} in appraisal {$this->objectid} was saved by user {$this->userid} in role {$this->other['role']}";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/appraisal/myappraisal.php', array('appraisalid' => $this->objectid, 'subjectid' => $this->relateduserid));
    }

    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('relateduserid must be set.');
        }

        if (!isset($this->other['pageid'])) {
            throw new \coding_exception('pageid must be set in $other.');
        }

        if (!isset($this->other['role'])) {
            throw new \coding_exception('role must be set in $other.');
        }
    }
}
