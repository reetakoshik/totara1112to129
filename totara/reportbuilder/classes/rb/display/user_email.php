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
 * @author Murali Nair <murali.nair@totaralms.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Displays email text on a reportbuilder generated report.
 *
 * @author Murali Nair <murali.nair@totaralms.com>
 * @package totara_reportbuilder
 */
class user_email extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $extrafields = static::get_extrafields_row($row, $column);
        if ($extrafields->maildisplay != 1) {
            return get_string('useremailprivate', 'totara_reportbuilder');
        }

        if (!$value) {
            return '';
        }

        if (!validate_email($value)) {
            // Deleted user most likely - do not show anything.
            return '';
        }

        if ($format !== 'html') {
            return $value;
        }

        return obfuscate_mailto($value, '', (bool)$extrafields->emailstop);
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
