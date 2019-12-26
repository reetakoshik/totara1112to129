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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\observer;

use core\event\base as base_event;
use totara_catalog\search_metadata\search_metadata_helper;

defined('MOODLE_INTERNAL') || die();


abstract class base_search_metadata_observer {
    /**
     * If $component is provided, then it will override the $event->component.
     *
     * @param base_event $event
     * @param string     $component
     *
     * @return void
     */
    final protected static function delete_search_metadata(base_event $event, string $component = ''): void {
        if ('' === $component) {
            $component = $event->component;
        }

        search_metadata_helper::remove_searchmetadata($component, $event->objectid);
    }
}