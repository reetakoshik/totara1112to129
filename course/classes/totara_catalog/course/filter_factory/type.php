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

class type extends filter_factory {

    public static function get_filters(): array {
        $filters = [];

        // The panel filter can appear in the panel region.
        $paneldatafilter = new in_or_equal(
            'course_type_panel',
            'catalog',
            ['objecttype', 'objectid']
        );
        $paneldatafilter->add_source(
            'course.coursetype',
            '{course}',
            'course',
            ['objectid' => 'course.id', 'objecttype' => "'course'"]
        );

        $panelselector = new multi(
            'course_type_panel',
            new \lang_string('coursetype', 'totara_core')
        );
        $panelselector->add_options_loader(self::get_multi_optionsloader());

        $filters[] = new filter(
            'course_type_panel',
            filter::REGION_PANEL,
            $paneldatafilter,
            $panelselector
        );

        // The browse filter can appear in the primary region.
        $browsedatafilter = new equal(
            'course_type_browse',
            'catalog',
            ['objecttype', 'objectid']
        );
        $browsedatafilter->add_source(
            'course.coursetype',
            '{course}',
            'course',
            ['objectid' => 'course.id', 'objecttype' => "'course'"]
        );

        $browseselector = new tree(
            'course_type_browse',
            new \lang_string('coursetype', 'totara_core'),
            self::get_tree_optionsloader()
        );
        $browseselector->add_all_option();

        $filters[] = new filter(
            'course_type_browse',
            filter::REGION_BROWSE,
            $browsedatafilter,
            $browseselector
        );

        return $filters;
    }

    /**
     * @return callable
     */
    private static function get_tree_optionsloader(): callable {
        return function () {
            global $TOTARA_COURSE_TYPES;

            $options = [];

            foreach ($TOTARA_COURSE_TYPES as $stringkey => $intkey) {
                $option = new \stdClass();
                $option->key = $intkey;
                $option->name = new \lang_string($stringkey, 'totara_core');
                $options[] = $option;
            }

            return $options;
        };
    }

    /**
     * @return callable
     */
    private static function get_multi_optionsloader(): callable {
        return function () {
            global $TOTARA_COURSE_TYPES;

            $options = [];

            foreach ($TOTARA_COURSE_TYPES as $stringkey => $intkey) {
                $options[$intkey] = new \lang_string($stringkey, 'totara_core');
            }

            return $options;
        };
    }
}
