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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_hierarchy
 */

namespace hierarchy_competency\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Triggered when a hierarchy is deleted.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - frameworkid   The id of the framework that contained the item
 * }
 *
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_hierarchy
 */
class competency_deleted extends \totara_hierarchy\event\hierarchy_deleted {
    /**
     * Returns hierarchy prefix.
     * @return string
     */
    public function get_prefix() {
        return 'competency';
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'comp';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('eventdeleteditem', 'hierarchy_competency');
    }
}
