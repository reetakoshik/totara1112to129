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
 * @package totara_core
 */

namespace totara_core\output;

use core\output\template;

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for select region templates.
 *
 * Subclasses should call get_base_template_data during their create or constructor function.
 */
abstract class select_region extends template {

    /**
     * Construct the base template data that all select templates need.
     *
     * @param select[] $selectors
     * @return \stdClass
     */
    protected static function get_base_template_data(array $selectors) {
        $data = new \stdClass();

        $data->selectors = [];

        foreach ($selectors as $selector) {
            $selectortemplatedata = new \stdClass();

            $selectortemplatedata->template_name = $selector->get_template_name();
            $selectortemplatedata->template_data = $selector->get_template_data();

            $data->selectors[] = $selectortemplatedata;
        }

        return $data;
    }
}