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
 * Display class intended for users learning items
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class user_learning_icons extends base {

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
        global $CFG, $OUTPUT;

        static $systemcontext;
        if (!isset($systemcontext)) {
            $systemcontext = \context_system::instance();
        }

        $disp = \html_writer::start_tag('span', array('style' => 'white-space:nowrap;'));

        // Learning Records icon.
        if (totara_feature_visible('recordoflearning')) {
            $disp .= \html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/totara/plan/record/index.php?userid=' . $value));
            $disp .= $OUTPUT->flex_icon('recordoflearning', ['classes' => 'ft-size-300']);
            $disp .= \html_writer::end_tag('a');
        }

        // Face To Face Bookings icon.
        if ($report->src->get_staff_f2f()) {
            $disp .= \html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/my/bookings.php?userid=' . $value));
            $disp .= $OUTPUT->flex_icon('calendar', ['classes' => 'ft-size-300']);
            $disp .= \html_writer::end_tag('a');
        }

        // Individual Development Plans icon.
        if (totara_feature_visible('learningplans')) {
            if (has_capability('totara/plan:accessplan', $systemcontext)) {
                $disp .= \html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/totara/plan/index.php?userid=' . $value));
                $disp .= $OUTPUT->flex_icon('learningplan', ['classes' => 'ft-size-300']);
                $disp .= \html_writer::end_tag('a');
            }
        }

        $disp .= \html_writer::end_tag('span');

        return $disp;
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
        return true;
    }
}
