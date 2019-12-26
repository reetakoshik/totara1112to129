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

namespace core_course\totara_catalog;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataformatter\fts;
use totara_catalog\dataholder;
use totara_catalog\dataformatter\text;

/**
 * A class which generates course category dataholders.
 *
 * This can be used with any objects which exist within the course category hierarchy (not just courses, e.g. programs).
 */
class category_dataholder_factory {

    /**
     * Get category dataholders.
     *
     * The jointable.joinfield should contain the course_category.id of the object.
     *
     * @param string $jointable
     * @param string $joinfield
     * @return dataholder[]
     */
    public static function get_dataholders(string $jointable, string $joinfield): array {
        global $DB;

        $dataholders = [];

        // Category placeholder for tiles - display the category for the object.
        $dataholders[] = new dataholder(
            'course_category',
            new \lang_string('category'),
            [
                formatter::TYPE_PLACEHOLDER_TEXT => new text(
                    'course_category.name'
                ),
            ],
            [
                'course_category' =>
                    "JOIN {course_categories} course_category
                       ON course_category.id = {$jointable}.{$joinfield}",
            ]
        );

        // Category hierarchy placeholder for FTS index.
        $concat = $DB->sql_group_concat('path_categories.name', ' \ ');

        $pathconcat = $DB->sql_concat('path_categories.path', ":course_category_hierarchy_p");
        $targetconcat = $DB->sql_concat('target_category.path', ":course_category_hierarchy_t");
        $like = $DB->sql_like($targetconcat, $pathconcat);

        $dataholders[] = new dataholder(
            'course_category_hierarchy',
            'not used course_category_hierarchy',
            [
                formatter::TYPE_FTS => new fts(
                    'course_category_hierarchy.text'
                ),
            ],
            [
                'course_category_hierarchy' =>
                    "LEFT JOIN (SELECT {$concat} AS text, target_category.id AS categoryid
                                 FROM {course_categories} target_category
                                 JOIN {course_categories} path_categories
                                   ON {$like}
                                GROUP BY target_category.id) course_category_hierarchy
                       ON course_category_hierarchy.categoryid = {$jointable}.{$joinfield}",
            ],
            [
                'course_category_hierarchy_p' => '/%',
                'course_category_hierarchy_t' => '/',
            ]
        );

        return $dataholders;
    }
}
