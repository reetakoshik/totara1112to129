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
 * @package totara_certification
 */

namespace totara_certification\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for certification recertify date type
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_certification
 */
class certif_recertify_date_type extends base {

    /**
     * Handles the display of certification recertify date type
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        switch ($value) {
            case CERTIFRECERT_COMPLETION:
                return get_string('editdetailsrccmpl', 'totara_certification');
            case CERTIFRECERT_EXPIRY:
                return get_string('editdetailsrcexp', 'totara_certification');
            case CERTIFRECERT_FIXED:
                return get_string('editdetailsrcfixed', 'totara_certification');
        }
        return "Error - Recertification method not found";
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
