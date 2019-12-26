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

use core\event\course_deleted;

defined('MOODLE_INTERNAL') || die();

/**
 * Observers for course CRUD events.
 */
final class course_search_metadata_observer extends base_search_metadata_observer {
    /**
     * Observer for course deleted event.
     *
     * @param course_deleted $event
     * @return void
     */
    public static function remove_search_metadata(course_deleted $event): void {
        static::delete_search_metadata($event, 'core_course');
    }
}