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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course\filter_factory;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\datasearch\equal;
use totara_catalog\datasearch\in_or_equal;
use totara_catalog\filter;
use totara_catalog\filter_factory;
use totara_catalog\merge_select\multi;
use totara_catalog\merge_select\tree;

class activity_type extends filter_factory {

    public static function get_filters(): array {
        $filters = [];

        // The panel filter can appear in the panel region.
        $paneldatafilter = new in_or_equal(
            'course_acttyp_panel',
            'catalog',
            ['objecttype', 'objectid']
        );
        $paneldatafilter->add_source(
            'course_modules.module',
            '{course_modules}',
            'course_modules',
            [
                'objecttype' => "'course'",
                'objectid' => 'course_modules.course',
            ]
        );

        $paneloptionsloader = function () {
            global $DB;

            $options = [];

            // All in-use visible modules.
            $sql = "SELECT DISTINCT m.id, m.name
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module
                     WHERE m.visible = 1";
            $modules = $DB->get_records_sql($sql);

            foreach ($modules as $module) {
                if (get_string_manager()->string_exists('pluginname', $module->name)) {
                    $options[$module->id] = new \lang_string('pluginname', $module->name);
                } else {
                    $options[$module->id] = ucfirst($module->name);
                }
            }

            return $options;
        };

        $panelselector = new multi(
            'course_acttyp_panel',
            new \lang_string('activity_type', 'totara_catalog')
        );
        $panelselector->add_options_loader($paneloptionsloader);

        $filters[] = new filter(
            'course_acttyp_panel',
            filter::REGION_PANEL,
            $paneldatafilter,
            $panelselector
        );

        // The browse filter can appear in the primary region.
        $browsedatafilter = new equal(
            'course_acttyp_browse',
            'catalog',
            ['objecttype', 'objectid']
        );
        $browsedatafilter->add_source(
            'course_modules.module',
            '{course_modules}',
            'course_modules',
            [
                'objecttype' => "'course'",
                'objectid' => 'course_modules.course',
            ]
        );

        $browseoptionsloader = function () {
            global $DB;

            $options = [];

            // All in-use visible modules.
            $sql = "SELECT DISTINCT m.id, m.name
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module
                     WHERE m.visible = 1";
            $modules = $DB->get_records_sql($sql);

            foreach ($modules as $module) {
                $option = new \stdClass();
                $option->key = $module->id;

                if (get_string_manager()->string_exists('pluginname', $module->name)) {
                    $option->name = new \lang_string('pluginname', $module->name);
                } else {
                    $option->name = ucfirst($module->name);
                }

                $options[] = $option;
            }

            return $options;
        };

        $browseselector = new tree(
            'course_acttyp_browse',
            new \lang_string('activity_type', 'totara_catalog'),
            $browseoptionsloader
        );
        $browseselector->add_all_option();

        $filters[] = new filter(
            'course_acttyp_browse',
            filter::REGION_BROWSE,
            $browsedatafilter,
            $browseselector
        );

        return $filters;
    }
}
