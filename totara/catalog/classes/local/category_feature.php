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
 * @package totara_catalog
 */
namespace totara_catalog\local;

defined('MOODLE_INTERNAL') || die();

use coursecat;
use totara_catalog\datasearch\all;
use totara_catalog\feature;

/**
 * Create the catalog category feature.
 */
class category_feature {

    public static function create(): feature {
        $datafilter = new all(
            'cat_cgry_ftrd',
            'catalog',
            ['contextid'],
            'LEFT JOIN'
        );
        $datafilter->add_source(
            'notused',
            "(SELECT objcontext.id AS objcontextid, catcontext.instanceid AS catinstanceid, 1 AS featured
                      FROM {context} objcontext
                      JOIN {context} catcontext ON objcontext.path LIKE CONCAT(catcontext.path, '/%')
                                               AND catcontext.id = :cat_cgry_ftrd_id)",
            'cat_cgry_ftrd',
            ['contextid' => 'cat_cgry_ftrd.objcontextid'],
            "",
            // Could change 'featured_learning_value' to array and use 'in_or_equals' above if we want in future.
            ['cat_cgry_ftrd_id' => config::instance()->get_value('featured_learning_value')]
        );

        $feature = new feature(
            'cat_cgry_ftrd',
            new \lang_string('category'),
            $datafilter
        );

        $feature->add_options_loader(
            function () {
                $topcat = \coursecat::get(0);
                return self::make_tree_options($topcat->get_children(), '');
            }
        );

        return $feature;
    }

    /**
     * @param coursecat[] $categories
     * @param string $parentstring
     * @return string[]
     */
    private static function make_tree_options(array $categories, string $parentstring = ''): array {
        $result = [];

        foreach ($categories as $category) {
            $safecategorynamewithparent = $parentstring .
                format_string($category->name, true, ['context' => \context_system::instance()]);

            $result[$category->get_context()->id] = $safecategorynamewithparent;

            $children = $category->get_children();

            if (!empty($children)) {
                $result += self::make_tree_options($children, $safecategorynamewithparent . ' / ');
            }
        }

        return $result;
    }
}
