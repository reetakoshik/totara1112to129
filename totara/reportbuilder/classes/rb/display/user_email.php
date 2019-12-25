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
 * Note: this replaces the global rb_display_user_email() function which has now
 * been deprecated.
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

        // In reality, format_text() should not be called here since emails do
        // not need any such processing. However, the call still remains because
        // it was in the totara_reportbuilder\rb\display\legacy class originally
        // handling email text and removing the call could break existing sites.
        $opts = ['filter' => false];
        $email = format_text($value, FORMAT_HTML, $opts);

        return obfuscate_mailto($email, '', (bool)$extrafields->emailstop);
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
