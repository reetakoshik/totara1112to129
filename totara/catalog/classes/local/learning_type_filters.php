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

use totara_catalog\datasearch\equal;
use totara_catalog\datasearch\in_or_equal;
use totara_catalog\filter;
use totara_catalog\merge_select\multi;
use totara_catalog\merge_select\single;
use totara_catalog\provider;
use totara_catalog\provider_handler;

/**
 * Creates the learning type filters.
 */
class learning_type_filters {

    /**
     * @return filter[]
     */
    public static function create(): array {
        $filters = [];

        $optionsloader = function () {
            $options = [];
            $providers = provider_handler::instance()->get_active_providers();

            foreach ($providers as $provider) {
                /** @var provider $providername */
                $options[$provider::get_object_type()] = $provider::get_name();
            }

            return $options;
        };

        // Panel filter.
        $paneldatafilter = new in_or_equal(
            'catalog_learning_type_panel',
            'catalog'
        );

        $paneldatafilter->add_source(
            'catalog.objecttype'
        );

        $panelselector = new multi(
            'catalog_learning_type_panel',
            new \lang_string('learning_type', 'totara_catalog')
        );
        $panelselector->add_options_loader($optionsloader);

        $filters[] = new filter(
            'catalog_learning_type_panel',
            filter::REGION_PANEL,
            $paneldatafilter,
            $panelselector
        );

        // Browse filter.
        $browsedatafilter = new equal(
            'catalog_learning_type_browse',
            'catalog'
        );
        $browsedatafilter->add_source(
            'catalog.objecttype'
        );

        $browseselector = new single(
            'catalog_learning_type_browse',
            new \lang_string('learning_type', 'totara_catalog')
        );
        $browseselector->add_all_option();
        $browseselector->add_options_loader($optionsloader);

        $filters[] = new filter(
            'catalog_learning_type_browse',
            filter::REGION_BROWSE,
            $browsedatafilter,
            $browseselector
        );

        return $filters;
    }
}
