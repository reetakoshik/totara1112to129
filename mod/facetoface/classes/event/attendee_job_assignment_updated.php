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
 * Event triggered when an attendee's position is updated.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - sessionid Session's ID.
 * - attendeeid Attendee's ID.
 *
 * }
 *
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */
class attendee_job_assignment_updated extends \core\event\base {

    /**
     * Init method
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'facetoface_signups';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventattendeepositionupdated', 'mod_facetoface');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $description  = "The attendee's position for User id {$this->other['attendeeid']} ";
        $description .= "for Session with the id {$this->other['sessionid']} has been edited by User with id {$this->userid}.";
        return  $description;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/facetoface/attendees/view.php', array('s' => $this->other['sessionid']));
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (!isset($this->other['sessionid'])) {
            throw new \coding_exception('sessionid must be set in $other.');
        }
        if (!isset($this->other['attendeeid'])) {
            throw new \coding_exception('attendeeid must be set in $other.');
        }

        parent::validate_data();
    }
}
