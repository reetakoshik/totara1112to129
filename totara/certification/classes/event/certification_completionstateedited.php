<?php
/*
 * This file is part of Totara Learn
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_certification
 */


namespace totara_certification\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when users completion state is changed via the completion editor within a certification.
 *
 * @property-read array $other
 * Extra information about the event.
 * - oldstate The old user completion state
 * - newstate The new user completion state
 *
 */
class certification_completionstateedited extends \core\event\base {

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'certif';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('completionstateedited', 'totara_certification');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {

        $statuses = array(
            CERTIFCOMPLETIONSTATE_ASSIGNED => 'assigned',
            CERTIFCOMPLETIONSTATE_CERTIFIED => 'certified',
            CERTIFCOMPLETIONSTATE_WINDOWOPEN => 'window open',
            CERTIFCOMPLETIONSTATE_EXPIRED => 'expired',
        );

        $oldstate = isset($this->other['oldstate']) && isset($statuses[$this->other['oldstate']]) ?
            $statuses[$this->other['oldstate']] : 'unknown state';

        $newstate = isset($this->other['newstate']) && isset($statuses[$this->other['newstate']]) ?
            $statuses[$this->other['newstate']] : 'unknown state';

        $changedby = isset($this->other['changedby']) ? $this->other['changedby'] : 'unknown';

        return "The user with id '{$this->userid}' had their completion state changed from '{$oldstate}' to '{$newstate}' for certification '{$this->objectid}' via the completion editor by user with the id '{$changedby}'";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/program/view.php', array('id' => $this->objectid));
    }

    protected function validate_data() {
        global $CFG;

        if ($CFG->debugdeveloper) {
            parent::validate_data();

            if (!isset($this->other['oldstate'])) {
                throw new \coding_exception('oldstate must be set in $other.');
            }

            if (!isset($this->other['newstate'])) {
                throw new \coding_exception('newstate must be set in $other.');
            }

            if (!isset($this->other['changedby'])) {
                throw new \coding_exception('changedby must be set in $other.');
            }
        }
    }
}
