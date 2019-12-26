<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course\observer;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\observer\object_update_observer;

/**
 * update course catalog data based on changed course module data
 */
class module_updated extends object_update_observer {

    public function get_observer_events(): array {
        return [
            '\core\event\course_module_created',
            '\core\event\course_module_deleted',
            '\core\event\course_module_updated'
        ];
    }

    /**
     * init course update object for modified course module
     */
    protected function init_change_objects(): void {
        // Exclude the site course.
        if ($this->event->courseid == SITEID) {
            return;
        }

        $data = new \stdClass();
        $data->objectid = $this->event->courseid;
        $data->contextid = \context_course::instance($this->event->courseid)->id;

        $this->register_for_update($data);
    }
}
