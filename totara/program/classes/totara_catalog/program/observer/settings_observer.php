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

use core\event\admin_settings_changed;
use totara_catalog\local\catalog_storage;
use totara_catalog\task\provider_active_task;
use core\task\manager as task_manager;

defined('MOODLE_INTERNAL') || die();

class settings_observer {

    /**
     * Setting name. Override in subclass.
     * @var string
     */
    const OBJECT_TYPE = 'program';

    /**
     * Process the change of setting. We compare to the previous setting to decide if a change occurred and
     * check the current setting to decide what to do.
     *
     * @param admin_settings_changed $event
     */
    public static function changed(admin_settings_changed $event): void {
        if (static::is_setting_changed($event->get_data())) {
            if (static::is_module_enabled()) {
                $adhoctask = new provider_active_task();
                $adhoctask->set_custom_data(array('objecttype' => static::OBJECT_TYPE));
                $adhoctask->set_component('totara_catalog');
                task_manager::queue_adhoc_task($adhoctask);
            } else {
                catalog_storage::delete_provider_data(static::OBJECT_TYPE);
            }
        }
    }

    /**
     * Is the module that this provider belongs to enabled? Override in subclass.
     *
     * @return mixed
     */
    protected static function is_module_enabled(): bool {
        global $CFG;

        $enableprograms = (int)$CFG->enableprograms;
        if ($enableprograms != TOTARA_DISABLEFEATURE) {
            return true;
        }

        return false;
    }

    /**
     * Check setting changed. Override in subclass.
     *
     * @param array $data containing the event data
     * @return bool
     */
    protected static function is_setting_changed(array $data): bool {
        global $CFG;

        if (isset($data['other']['olddata']['s__enableprograms']) &&
            (int)$data['other']['olddata']['s__enableprograms'] != (int)$CFG->enableprograms) {
            return true;
        }

        return false;
    }
}
