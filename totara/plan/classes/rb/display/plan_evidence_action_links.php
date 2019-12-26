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
 * @package totara_plan
 */

namespace totara_plan\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for evidence action links
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_plan
 */
class plan_evidence_action_links extends base {

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
        global $USER, $OUTPUT;

        $extrafields = self::get_extrafields_row($row, $column);

        $out = '';

        if (can_create_or_edit_evidence($extrafields->userid, !empty($value), $extrafields->readonly)) {
            $out .= $OUTPUT->action_icon(
                new \moodle_url('/totara/plan/record/evidence/edit.php',
                    array('id' => $value, 'userid' => $extrafields->userid)),
                new \pix_icon('t/edit', get_string('edit')));

            $out .= $OUTPUT->spacer(array('width' => 11, 'height' => 11, 'class' => 'iconsmall action-icon'));

            $out .= $OUTPUT->action_icon(
                new \moodle_url('/totara/plan/record/evidence/edit.php',
                    array('id' => $value, 'userid' => $extrafields->userid, 'd' => '1')),
                new \pix_icon('t/delete', get_string('delete')));
        } else if ($extrafields->readonly) {
            $out .= get_string('evidence_readonly', 'totara_plan');
        }

        return $out;
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
