<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Class describing column display formatting.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */
class userfield_datetime extends base {
    use userfield_trait;

    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if (!self::is_cell_visible($value, $format, $row, $column, $report)) {
            return get_string('hiddencellvalue', 'totara_reportbuilder');
        }

        if (!empty($column->extracontext['param3'])) {
            return \totara_reportbuilder\rb\display\nice_datetime::display($value, $format, $row, $column, $report);
        } else {
            return \totara_reportbuilder\rb\display\nice_date::display($value, $format, $row, $column, $report);
        }
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
