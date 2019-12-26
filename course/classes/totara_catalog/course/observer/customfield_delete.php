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
 * update course catalog data based on deleted custom field id
 */
class customfield_delete extends object_update_observer {

    public function get_observer_events(): array {
        return [
            '\totara_customfield\event\customfield_data_deleted'
        ];
    }

    /**
     * init course update object for deleted custom field
     */
    protected function init_change_objects(): void {
        $eventdata = $this->event->get_data();
        if (isset($eventdata['other']['field_data']['courseid'])) {
            $data = new \stdClass();
            $data->objectid = $eventdata['other']['field_data']['courseid'];
            $data->contextid = $this->event->contextid;
            $this->register_for_update($data);
        }
    }
}
