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
 * @package core_tag
 * @category totara_catalog
 */

namespace core_tag\totara_catalog;

defined('MOODLE_INTERNAL') || die();

use core\event\admin_settings_changed;
use core\event\tag_area_updated;
use core\task\manager as task_manager;
use totara_catalog\task\refresh_catalog_adhoc;
use core_course\totara_catalog\course as course_provider;
use totara_certification\totara_catalog\certification as certification_provider;
use totara_program\totara_catalog\program as program_provider;

class tags_observer {

    /**
     * Refresh all catalog records when the tags feature is enabled or disabled.
     *
     * @param admin_settings_changed $event
     */
    public static function changed(admin_settings_changed $event): void {

        if (static::is_tags_setting_changed($event->get_data())) {
            $adhoctask = new refresh_catalog_adhoc();
            $adhoctask->set_component('totara_catalog');
            task_manager::queue_adhoc_task($adhoctask);
        }
    }

    /**
     * Check if the tags setting has changed. This event observes all admin settings changes, so we're looking for
     * just this one that is relevant.
     *
     * @param array $data
     * @return bool
     */
    private static function is_tags_setting_changed(array $data): bool {
        global $CFG;

        if (isset($data['other']['olddata']['s__usetags']) &&
            (int)$data['other']['olddata']['s__usetags'] != (int)$CFG->usetags) {
            return true;
        }

        return false;
    }

    /**
     * Refresh catalog provider records when the tags area is enabled or disabled.
     *
     * @param tag_area_updated $event
     */
    public static function tag_area_updated(tag_area_updated $event): void {
        $data = $event->get_data();
        if (in_array($data['other']['itemtype'], ['course', 'prog'])) {
            $objecttypes = array();
            if ($data['other']['itemtype'] == 'prog') {
                $objecttypes[] = certification_provider::get_object_type();
                $objecttypes[] = program_provider::get_object_type();
            } else {
                $objecttypes[] = course_provider::get_object_type();
            }
            $adhoctask = new refresh_catalog_adhoc();
            $adhoctask->set_custom_data(array('objecttypes' => $objecttypes));
            $adhoctask->set_component('totara_catalog');
            task_manager::queue_adhoc_task($adhoctask);
        }
    }
}
