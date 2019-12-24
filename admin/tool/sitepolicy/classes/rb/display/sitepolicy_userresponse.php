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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace tool_sitepolicy\rb\display;

defined('MOODLE_INTERNAL') || die();

use \totara_reportbuilder\rb\display\base;
/**
 * Display user's sitepolicy response
 *
 * @package tool_sitepolicy
 */
class sitepolicy_userresponse extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        // Get the necessary fields out of the row.
        $extrafields = self::get_extrafields_row($row, $column);

        // user_consent.hasconsented is expected as the value (0 or 1)
        // The first etrafield value must contain the value of the non-consent option to display
        // The second extrafield value must contain the value of the consent option to display

        return $extrafields->{$value};
    }
}