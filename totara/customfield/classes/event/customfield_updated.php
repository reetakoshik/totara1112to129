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

class customfield_updated extends base {

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Set object table based on the prefix
     *
     * @param string $tableprefix
     */
    public function set_object_table(string $tableprefix) {

        $this->data['objecttable'] = $tableprefix . '_info_field';
    }

    /**
     * @param int    $id
     * @param string $prefix
     * @param array  $data
     *
     * @return customfield_updated
     */
    public static function create_by_type(int $id, string $prefix, array $data = []): customfield_updated {
        $event = self::create(
            [
                'objectid' => $id,
                'context'  => \context_system::instance(),
                'other'    => [
                    'data' => $data,
                    'type' => $prefix,
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
        return get_string('eventupdated', 'totara_customfield');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return 'custom field ' . $this->objectid . ' updated';
    }
}
