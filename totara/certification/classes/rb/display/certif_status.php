<?php
/*
 * This file is part of Totara LMS
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_certification
 */

namespace totara_certification\rb\display;

/**
 * Display Certification status.
 *
 * @package mod_facetoface
 */
class certif_status extends \totara_reportbuilder\rb\display\base {
    /**
     * Displays the overall status.
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CERTIFSTATUS;

        $extrafields = self::get_extrafields_row($row, $column);

        if (isset($extrafields->active) && $extrafields->active != 1) {
            return get_string('na', 'totara_certification');
        }

        $unassigned = '';
        if (!empty($extrafields->unassigned)) {
            $unassigned = ' ' . get_string('unassigned', 'rb_source_dp_certification');
        }

        if (isset($value) && isset($CERTIFSTATUS[$value])) {
            switch ($value) {
                case CERTIFSTATUS_ASSIGNED:
                    return get_string('notcertified', 'totara_certification') . $unassigned;
                case CERTIFSTATUS_COMPLETED:
                    return get_string('certified', 'totara_certification') . $unassigned;
                default:
                    return get_string($CERTIFSTATUS[$value], 'totara_certification') . $unassigned;
            }
        } else if (isset($value)) {
            return get_string('error:invalidstatus', 'totara_program') . $unassigned;
        } else {
            return get_string('notassigned', 'totara_certification') . $unassigned;
        }
    }

    /**
     * Is this column graphable? No!
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
