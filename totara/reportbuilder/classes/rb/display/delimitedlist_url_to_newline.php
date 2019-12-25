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
 * Displays a list of url's as one string per line
 * Assumes you used a custom grouping with the $this->uniquedelimiter to concatenate the fields
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class delimitedlist_url_to_newline extends base {

    /**
     * Handles the display
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $uniquedelimiter = $report->src->get_uniquedelimiter();
        $items = explode($uniquedelimiter, $value);
        $output = array();

        foreach ($items as $item) {
            $item = json_decode($item);
            if ($item === '-' || empty($item)) {
                $output[] = '-';
            } else {
                $text = s(empty($item->text) ? $item->url : format_string($item->text));
                $target = isset($item->target) ? array('target' => '_blank', 'rel' => 'noreferrer') : null;
                $output[] = \html_writer::link($item->url, $text, $target);
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
