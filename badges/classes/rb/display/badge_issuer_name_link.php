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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package core_badges
 */

namespace core_badges\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for badge issuer name as html link
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package core_badges
 */
class badge_issuer_name_link extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CFG;

        if (empty($value)) {
            return '';
        }

        $value = s($value);

        $isexport = ($format !== 'html');
        if ($isexport) {
            return $value;
        }

        $extrafields = self::get_extrafields_row($row, $column);

        $url = parse_url($CFG->wwwroot);

        if (empty($extrafields->issuerurl) || $extrafields->issuerurl == ($url['scheme'] . '://' . $url['host']) || substr($extrafields->issuerurl, 0, 4) != 'http') {
            return $value;
        }

        return \html_writer::tag('a', $value, array('href' => $extrafields->issuerurl));
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
