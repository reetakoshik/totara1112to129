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

global $CFG;
require_once($CFG->dirroot.'/lib/coursecatlib.php');

use totara_catalog\datasearch\equal;
use totara_catalog\filter;
use totara_catalog\merge_select\tree;

/**
 * Create the catalog category filters.
 */
class category_filters {

    const FILTER_KEY_CATEGORY_BROWSE = 'catalog_cat_browse';

    public static function create(): array {
        $filters = [];

        // Panel filter.
        $paneldatafilter = new equal(
            'catalog_cat_panel',
            'catalog',
            ['contextid']
        );
        $paneldatafilter->add_source(
            'pdfcatcontext.catcontextid',
            "(SELECT objcontext.id AS objcontextid, catcontext.id AS catcontextid
                            FROM {context} objcontext
                            JOIN {context} catcontext ON objcontext.path LIKE CONCAT(catcontext.path, '/%')
                                                     AND catcontext.contextlevel = :cat_cgry_fil_clvl)",
            'pdfcatcontext',
            ['contextid' => 'pdfcatcontext.objcontextid'],
            "",
            ['cat_cgry_fil_clvl' => CONTEXT_COURSECAT]
        );

        $panelselector = new tree(
            'catalog_cat_panel',
            new \lang_string('category'),
            self::get_optionsloader()
        );
        // If we don't add the all option then the filter will always be active.
        $panelselector->add_all_option();

        $filters[] = new filter(
            'catalog_cat_panel',
            filter::REGION_PANEL,
            $paneldatafilter,
            $panelselector
        );

        // Browse filter.
        $browsedatafilter = new equal(
            'catalog_cat_browse',
            'catalog',
            ['contextid']
        );
        $browsedatafilter->add_source(
            'bdfcatcontext.catcontextid',
            "(SELECT objcontext.id AS objcontextid, catcontext.id AS catcontextid
                            FROM {context} objcontext
                            JOIN {context} catcontext ON objcontext.path LIKE CONCAT(catcontext.path, '/%'))",
            'bdfcatcontext',
            ['contextid' => 'bdfcatcontext.objcontextid']
        );

        $browseselector = new tree(
            'catalog_cat_browse',
            new \lang_string('category'),
            self::get_optionsloader()
        );
        $browseselector->add_all_option();

        $filters[] = new filter(
            self::FILTER_KEY_CATEGORY_BROWSE,
            filter::REGION_BROWSE,
            $browsedatafilter,
            $browseselector
        );

        return $filters;
    }

    /**
     * @return callable
     */
    private static function get_optionsloader(): callable {
        return function () {
            $topcat = \coursecat::get(0);

            $result = [];
            foreach ($topcat->get_children() as $child) {
                $result[] = static::make_tree_option($child);
            }

            return $result;
        };
    }

    /**
     * Creates an merge select tree option representing a category
     *
     * @param \coursecat $cat The category to create
     * @return \stdClass
     */
    private static function make_tree_option(\coursecat $cat) {
        $result = new \stdClass();
        $result->key = $cat->get_context()->id;
        $result->name = format_string($cat->name, true, ['context' => \context_system::instance()]);
        $result->children = [];

        foreach ($cat->get_children() as $child) {
            $result->children[] = static::make_tree_option($child);
        }

        return $result;
    }
}
