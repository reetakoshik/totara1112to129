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
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Displays a list of emails as one string per line
 * Assumes you used a custom grouping with the $this->uniquedelimiter to concatenate the fields
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class orderedlist_to_newline_email extends base {

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
        $isexport = ($format !== 'html');
        $uniquedelimiter = $report->src->get_uniquedelimiter();

        $output = array();
        $emails = explode($uniquedelimiter, $value);
        foreach ($emails as $email) {
            if ($isexport) {
                $output[] = $email;
            } else if ($email === '!private!') {
                $output[] = get_string('useremailprivate', 'totara_reportbuilder');
            } else if ($email !== '-') {
                // Obfuscate email to avoid spam if printing to page.
                $output[] = obfuscate_mailto($email);
            } else {
                $output[] = '-';
            }
        }

        return implode($output, "\n");
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
