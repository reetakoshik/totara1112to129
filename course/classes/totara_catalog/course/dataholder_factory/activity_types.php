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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course\dataholder_factory;

defined('MOODLE_INTERNAL') || die();

use core_course\totara_catalog\course\dataformatter\activity_type_icons as activity_type_icons_dataformatter;
use core_course\totara_catalog\course\dataformatter\activity_types as activity_types_dataformatter;
use totara_catalog\dataformatter\formatter;
use totara_catalog\dataholder;
use totara_catalog\dataholder_factory;

class activity_types extends dataholder_factory {

    public static function get_dataholders(): array {
        global $DB;

        $modules = $DB->sql_group_concat_unique('activity_types_m.name', ',');
        $iconmodules = $DB->sql_group_concat_unique('activity_type_icons_m.name', ',');

        return [
            new dataholder(
                'activity_types',
                new \lang_string('activity_types', 'totara_catalog'),
                [
                    formatter::TYPE_FTS => new activity_types_dataformatter(
                        'activity_types.modules'
                    ),
                    formatter::TYPE_PLACEHOLDER_TEXT => new activity_types_dataformatter(
                        'activity_types.modules'
                    ),
                ],
                [
                    'activity_types' =>
                        "LEFT JOIN (SELECT activity_types_cm.course, {$modules} AS modules
                                      FROM {course_modules} activity_types_cm
                                 LEFT JOIN {modules} activity_types_m
                                        ON activity_types_m.id = activity_types_cm.module
                                     WHERE activity_types_cm.visible = 1
                                  GROUP BY activity_types_cm.course) activity_types
                                ON activity_types.course = base.id",
                ]
            ),
            new dataholder(
                'activity_type_icons',
                new \lang_string('activity_types', 'totara_catalog'),
                [
                    formatter::TYPE_PLACEHOLDER_ICONS => new activity_type_icons_dataformatter(
                        'activity_type_icons.modules'
                    ),
                ],
                [
                    'activity_type_icons' =>
                        "LEFT JOIN (SELECT activity_type_icons_cm.course, {$iconmodules} AS modules
                                      FROM {course_modules} activity_type_icons_cm
                                 LEFT JOIN {modules} activity_type_icons_m
                                        ON activity_type_icons_m.id = activity_type_icons_cm.module
                                     WHERE activity_type_icons_cm.visible = 1
                                  GROUP BY activity_type_icons_cm.course) activity_type_icons
                                ON activity_type_icons.course = base.id",
                ]
            ),
        ];
    }
}
