<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Fancy display of job assignments for one user.
 *
 * @package totara_reportbuilder
 */
class user_jobassignments extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if (!$value) {
            return '';
        }

        $extrafields = self::get_extrafields_row($row, $column);
        if ($extrafields->deleted or !$extrafields->userid) {
            return '';
        }

        $jas = self::get_job_assignments_info($extrafields->userid);
        $rows = array();
        foreach ($jas as $ja) {
            if (!isset($ja->fullname) or $ja->fullname === '') {
                $job = get_string('jobassignmentdefaultfullname', 'totara_job', $ja->idnumber);
            } else {
                $job = $ja->fullname;
            }
            $details = array();
            if ($ja->posfullname !== null and $ja->posfullname !== '') {
                $details[] = $ja->posfullname;
            }
            if ($ja->orgfullname !== null and $ja->orgfullname !== '') {
                $details[] = $ja->orgfullname;
            }
            if (trim($ja->managerfullname) !== '') {
                $details[] = $ja->managerfullname;
            }
            if ($details) {
                $job = $job . ': ' . implode(', ', $details);
            }
            $rows[] = $job;
        }
        if ($format !== 'html') {
            return implode("\n", $rows);
        }
        return implode("<br />", $rows);
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }

    public static function get_job_assignments_info($userid) {
        global $DB;

        $fullname = $DB->sql_concat_join("' '", array('m.firstname', 'm.lastname'));

        $sql = "SELECT ja.*, o.fullname AS orgfullname, p.fullname AS posfullname, m.id AS managerid, ($fullname) AS managerfullname
                  FROM {job_assignment} ja
             LEFT JOIN {pos} p ON p.id = ja.positionid
             LEFT JOIN {org} o ON o.id = ja.organisationid
             LEFT JOIN {job_assignment} mja ON mja.id = ja.managerjaid
             LEFT JOIN {user} m ON m.id = mja.userid
                 WHERE ja.userid = :userid
              ORDER BY ja.sortorder ASC";

        return $DB->get_records_sql($sql, array('userid' => $userid));
    }
}
