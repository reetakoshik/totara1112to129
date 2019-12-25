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
 * @package totara_program
 * @category totara_catalog
 */

namespace totara_program\totara_catalog\program\observer;

use \core\event\base as base_event;
use totara_catalog\task\provider_active_task;
use core\task\manager as task_manager;

defined('MOODLE_INTERNAL') || die();

class customfield_changed {

    /**
     * Process the adding or updating custom field. update program catalog data based on custom field default values
     *
     * @param base_event $event
     */
    public static function update_default_data(base_event $event): void {
        $eventdata = $event->get_data();

        if ($eventdata['other']['type'] == 'prog' &&
            !empty($eventdata['other']['data']['defaultdata'])
        ) {
            $adhoctask = new provider_active_task();
            $adhoctask->set_custom_data(array('objecttype' => static::get_object_type()));
            $adhoctask->set_component('totara_catalog');
            task_manager::queue_adhoc_task($adhoctask);
        }
    }

    /**
     * Get object type
     *
     * @return string
     */
    protected static function get_object_type(): string {
        return 'program';
    }
}
