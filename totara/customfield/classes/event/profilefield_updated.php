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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara
 * @subpackage totara_customfield
 */

namespace totara_customfield\event;
defined('MOODLE_INTERNAL') || die();

// Event when a profilefield is updated.
class profilefield_updated extends \core\event\base {

    protected static $preventcreatecall = true;

    protected $info;

    /**
     * Create instance of event.
     *
     * @param stdClass $eventdata
     * @return profilefield_updated
     */
    public static function create_from_field(\stdClass $eventdata) {
        $data = array(
            'context' => \context_system::instance(),
            'objectid' => $eventdata->objectid,
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;
        $event->info = $eventdata;
        return $event;
    }

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['objecttable'] = 'user_info_field';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public function get_info() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_info is intended for event observers only');
        }

        return $this->info;
    }


    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventupdated', 'totara_customfield');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return 'custom field ' . $this->objectid . ' update';
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_field() instead.');
        }

        parent::validate_data();
    }
}
