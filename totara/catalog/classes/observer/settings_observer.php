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
 * @package totara_catalog
 */

namespace totara_catalog\observer;

defined('MOODLE_INTERNAL') || die();

use core\event\admin_settings_changed;
use core\task\manager as task_manager;
use totara_catalog\task\refresh_catalog_adhoc;

class settings_observer {

    /**
     * Process the change of setting. We compare to the previous setting to decide if a change occurred and
     * check the current setting to decide what to do.
     *
     * @param admin_settings_changed $event
     */
    public static function changed(admin_settings_changed $event): void {
        global $CFG, $DB;

        if (static::is_setting_changed($event->get_data())) {
            if ($CFG->catalogtype == 'totara') {
                $adhoctask = new refresh_catalog_adhoc();
                $adhoctask->set_component('totara_catalog');
                task_manager::queue_adhoc_task($adhoctask);
            } else {
                $DB->delete_records('catalog');
            }
        }
    }

    /**
     * Check setting changed. Override in subclass.
     *
     * @param array $data containing the event data
     *
     * @return bool
     */
    protected static function is_setting_changed(array $data): bool {
        global $CFG;

        if (isset($data['other']['olddata']['s__catalogtype']) &&
            $data['other']['olddata']['s__catalogtype'] != $CFG->catalogtype) {
            return true;
        }

        return false;
    }
}
