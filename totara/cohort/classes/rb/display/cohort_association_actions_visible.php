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
 * Display class intended for action links for "visible learning"
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */
class cohort_association_actions_visible extends base {

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

        static $canedit = null;
        if ($canedit === null) {
            $canedit = has_capability('moodle/cohort:manage', \context_system::instance());
        }

        if ($canedit) {
            static $strdelete = false;
            if ($strdelete === false) {
                $strdelete = get_string('deletelearningitem', 'totara_cohort');
            }
            $delurl = new \moodle_url('/totara/cohort/dialog/updatelearning.php',
                array('cohortid' => $extrafields->cohortid,
                    'type' => $extrafields->type,
                    'd' => $value,
                    'v' => COHORT_ASSN_VALUE_VISIBLE,
                    'sesskey' => sesskey()));

            $attributes = array(
                'title' => $strdelete,
                'class' => 'learning-delete'
            );

            if (empty($report->embedded)) {
                // If it is not an embedded, add a custom class here to keep it distinguish from
                // the embbeded report one
                $attributes['class'] = 'learning-delete cohort-association-visible-delete';
            }

            return $OUTPUT->action_icon($delurl, new \pix_icon('t/delete', $strdelete), null, $attributes);
        }

        return '';
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
