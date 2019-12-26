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

use totara_catalog\output\catalog;

defined('MOODLE_INTERNAL') || die();

/**
 * Process submitted data when the page is loaded directly.
 *
 * @package totara_catalog
 */
class param_processor {

    /**
     * Get the catalog template, using data from the url.
     *
     * @return catalog
     */
    public static function get_template() {
        $filterparams = [];
        foreach (filter_handler::instance()->get_active_filters() as $filter) {
            $optionalparams = $filter->selector->get_optional_params();

            foreach ($optionalparams as $optionalparam) {
                if ($optionalparam->multiplevalues) {
                    $filterparams[$optionalparam->key] = optional_param_array(
                        $optionalparam->key,
                        $optionalparam->default,
                        $optionalparam->type
                    );
                } else {
                    $filterparams[$optionalparam->key] = optional_param(
                        $optionalparam->key,
                        $optionalparam->default,
                        $optionalparam->type
                    );
                }
            }
        }

        return catalog::create(
            optional_param('itemstyle', 'narrow', PARAM_ALPHA),
            0,
            -1,
            optional_param('orderbykey', '', PARAM_ALPHA),
            0,
            optional_param('debug', false, PARAM_BOOL),
            $filterparams
        );
    }
}
