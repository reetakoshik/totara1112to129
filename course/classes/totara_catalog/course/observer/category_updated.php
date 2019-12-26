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
use core\task\manager as task_manager;
use totara_catalog\task\provider_active_task;

/**
 * update catalog data for all courses in the updated category id
 *
 */
class category_updated extends object_update_observer {

    public function get_observer_events(): array {
        return [
            '\core\event\course_category_updated'
        ];
    }

    /**
     * init all course update objects for updated category id
     */
    protected function init_change_objects(): void {
        $adhoctask = new provider_active_task();
        $adhoctask->set_custom_data(array('objecttype' => $this->get_objecttype()));
        $adhoctask->set_component('totara_catalog');
        task_manager::queue_adhoc_task($adhoctask);
    }
}
