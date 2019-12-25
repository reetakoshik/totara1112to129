<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_customfield\event
 */

namespace totara_customfield\event;

defined('MOODLE_INTERNAL') || die();

use \core\event\base;

class customfield_data_deleted extends base {


    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Set object table based on the prefix
     */
    public function set_object_table(string $tableprefix) {

        $this->data['objecttable'] = $tableprefix . '_info_data';
    }

    /**
     * @param $id
     * @param $prefix
     *
     * @return base
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function create_by_type(int $id, string $prefix, $data = []) {
        $event = self::create(
            [
                'objectid' => $id,
                'context'  => \context_system::instance(),
                'other'    => [
                    'field_data'   => $data
                ],
            ]
        );
        $event->set_object_table($prefix);
        return $event;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventdeleted', 'totara_customfield');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return 'custom field ' . $this->objectid . ' deleted';
    }

    /**
     * Return name of the legacy event, which is replaced by this event.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'coursefield_deleted';
    }

    /**
     * Return coursefield_deleted legacy event data.
     *
     * @return \stdClass user data.
     */
    protected function get_legacy_eventdata() {
        return $this->objectid;
    }

    /**
     * Returns array of parameters to be passed to legacy add_to_log() function.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return array(SITEID, 'course_info_field', 'delete', "index.php?action=deletefield&id=" . $this->objectid);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        global $CFG;

        if ($CFG->debugdeveloper) {
            parent::validate_data();
        }
    }
}
