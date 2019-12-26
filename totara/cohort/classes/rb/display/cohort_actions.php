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
 * @package totara_cohort
 */

namespace totara_cohort\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended to show the "action" links for a cohort e.g. edit/clone/delete
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */
class cohort_actions extends base {

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
        global $OUTPUT;

        $extrafields = self::get_extrafields_row($row, $column);

        $contextid = $extrafields->contextid;
        if ($contextid) {
            $context = \context::instance_by_id($contextid);
        } else {
            $context = \context_system::instance();
        }

        if (!has_capability('moodle/cohort:manage', $context)) {
            return '';
        }

        $str = '';
        if (empty($extrafields->component)) {
            $editurl = new \moodle_url('/cohort/edit.php', array('id' => $value));
            $str .= \html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));
        }
        $cloneurl = new \moodle_url('/cohort/view.php', array('id' => $value, 'clone' => 1, 'cancelurl' => qualified_me()));
        $str .= \html_writer::link($cloneurl, $OUTPUT->pix_icon('t/copy', get_string('copy', 'totara_cohort')));
        $delurl = new \moodle_url('/cohort/view.php', array('id' => $value, 'delete' => 1, 'cancelurl' => qualified_me()));
        $str .= \html_writer::link($delurl, $OUTPUT->pix_icon('t/delete', get_string('delete')));

        return $str;
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
