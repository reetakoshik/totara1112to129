<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_connect
 */

namespace totara_connect\rb\display;

/**
 * Class describing column display formatting.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_connect
 */
class client_cohorts extends \totara_reportbuilder\rb\display\base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $DB;
        if (!$value) {
            return '';
        }

        $extra = self::get_extrafields_row($row, $column);

        $sql = "SELECT c.id, c.name, c.idnumber
                  FROM {cohort} c
                  JOIN {totara_connect_client_cohorts} cc ON cc.cohortid = c.id
                 WHERE cc.clientid = ?";
        $cohorts = $DB->get_records_sql($sql, array($extra->client_id));
        foreach ($cohorts as $k => $c) {
            $cohorts[$k] = format_string($c->name) . ' (' . $c->idnumber . ')';
        }

        $result = implode(', ', $cohorts);

        if ($format !== 'html') {
            $result = static::to_plaintext($result);
        }

        return $result;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
