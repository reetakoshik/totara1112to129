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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\rb\display;

use \totara_reportbuilder\rb\display\base;

/**
 * Human readable purge item name.
 */
final class purge_item_fullname extends base {
    /**
     * Display data.
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if (!$value) {
            return '';
        }

        $extra = self::get_extrafields_row($row, $column);
        $class = $extra->component . '\\' . 'userdata' . '\\' . $value;

        if (!class_exists($class)) {
            return s($class);
        }

        $callable = [$class, 'get_fullname'];

        if (!is_callable($callable)) {
            return $class;
        }

        return call_user_func($callable);
    }
}
