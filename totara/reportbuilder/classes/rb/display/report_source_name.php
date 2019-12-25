<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Class describing column display formatting for the source column.
 *
 * Converts the source short name into a human readable form.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */
class report_source_name extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
        // Unfortunately, this can be expensive and is needed
        // on export too.
        // Until we refactor sources to avoid any DB queries
        // there is not much we can do - source title is
        // defined in the class and so we need to instantiate
        // the class to get it.
        $src = \reportbuilder::get_source_object($value);
        return $src->sourcetitle;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return true;
    }
}
