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
 * update course catalog data based on changed course section data
 */
class section_updated extends object_update_observer {

    public function get_observer_events(): array {
        return [
            '\core\event\course_section_updated',
            '\core\event\course_section_deleted'
        ];
    }

    /**
     * init course update object for updated or added section
     */
    protected function init_change_objects(): void {

        if ($this->event->courseid != SITEID) {
            $data = new \stdClass();
            $data->objectid = $this->event->courseid;
            $data->contextid = $this->event->contextid;
            $this->register_for_update($data);
        }
    }
}
