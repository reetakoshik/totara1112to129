<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Lee Campbell <lee@learningpool.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when the signin sheet report is exported.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - sessionid The facetoface's session ID where the report was exported.
 *
 * }
 *
 * @author Lee Campbell <lee@learningpool.com>
 * @package mod_facetoface
 */
class session_signin_sheet_exported extends \core\event\base {

    /**
     * Create instance of event.
     *
     * @param \stdClass $session
     * @param \context_module $context
     * @return interest_report_viewed
     */
    public static function create_from_facetoface_session(\stdClass $session, \context_module $context) {
        $data = array(
            'context' => $context,
            'other' => array('sessionid' => $session->id)
        );
        return self::create($data);
    }

    /**
     * Init method
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsigninsheetexported', 'mod_facetoface');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The Sign-in sheet report for Facetoface Session with id {$this->other['sessionid']} was viewed by User with id {$this->userid}.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/facetoface/reports/signinsheet.php', array('sessionid' => $this->other['sessionid']));
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        if (!isset($this->other['sessionid'])) {
            throw new \coding_exception('sessionid must be set in $other.');
        }

        parent::validate_data();
    }
}
