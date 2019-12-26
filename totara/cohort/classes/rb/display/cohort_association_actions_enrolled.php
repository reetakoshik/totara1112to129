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
 * Display class intended for the action links for the "enrolled learning" page
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */
class cohort_association_actions_enrolled extends base {

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
        global $OUTPUT, $PAGE;

        $extrafields = self::get_extrafields_row($row, $column);

        static $canedit = null;
        if ($canedit === null) {
            $canedit = has_capability('moodle/cohort:manage', \context_system::instance());
        }

        if ($canedit) {

            // Require JS to intercept the delete call.
            $jsmodule = array(
                'name' => 'totara_cohortlearning',
                'fullpath' => '/totara/cohort/dialog/learningitem.js',
                'requires' => array('json'));
            $PAGE->requires->js_init_call('M.totara_cohortlearning.init', array(), false, $jsmodule);
            $PAGE->requires->strings_for_js(array('assignenrolledlearningcourse', 'assignenrolledlearningprogram',
                'assignenrolledlearningcertification', 'deletelearningconfirm', 'savinglearning', 'savingrule', 'error:badresponsefromajax'),
                'totara_cohort');

            static $strdelete = false;
            if ($strdelete === false) {
                $strdelete = get_string('deletelearningitem', 'totara_cohort');
            }
            $delurl = new \moodle_url('/totara/cohort/dialog/updatelearning.php',
                array('cohortid' => $extrafields->cohortid,
                    'type' => $extrafields->type,
                    'd' => $value,
                    'sesskey' => sesskey()));
            return \html_writer::link($delurl, $OUTPUT->pix_icon('t/delete', $strdelete), array('title' => $strdelete, 'class' => 'learning-delete'));
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
