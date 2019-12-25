<?php
/*
 * This file is part of Totara Learn
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
 * Display class intended for titles and names that are allowed to be localised via multilang syntax.
 * Note that general HTML tags are stripped, but html entities are allowed.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 *
 * @deprecated Since Totara 12.0
 */
class formatstring extends base {

    /**
     * Handle the display
     *
     * @deprecated Since Totara 12.0
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        debugging('totara_reportbuilder\rb\display\formatstring::display has been deprecated since Totara 12.0. Use totara_reportbuilder\rb\display\format_string::display', DEBUG_DEVELOPER);
        $value = format_string($value, true, array('context' => \context_system::instance()));
        if ($format === 'html') {
            return $value;
        }
        return \core_text::entities_to_utf8($value);
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
