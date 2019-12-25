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

defined('MOODLE_INTERNAL') || die();

use core\task\manager as task_manager;
use totara_catalog\observer\object_update_observer;
use totara_catalog\task\provider_active_task;

class category_updated extends object_update_observer {

    public function get_observer_events(): array {
        return [
            '\core\event\course_category_updated'
        ];
    }

    /**
     * init all program update objects based on change category id
     */
    protected function init_change_objects(): void {
        $adhoctask = new provider_active_task();
        $adhoctask->set_custom_data(array('objecttype' => $this->get_objecttype()));
        $adhoctask->set_component('totara_catalog');
        task_manager::queue_adhoc_task($adhoctask);
    }

    /**
     * Get the sql snippet which determines if the data should be used, override in subclasses
     *
     * @return string
     */
    protected function get_object_condition(): string {
        return "prog.certifid IS NULL";
    }

    /**
     * Check it's a applicable event change, override in subclasses
     *
     * @param int $objectid
     * @return bool
     */
    protected function is_applicable_change(int $objectid): bool {
        global $DB;

        $certifid = $DB->get_field('prog', 'certifid', ['id' => $objectid]);

        return $certifid !== false && empty($certifid);
    }
}
