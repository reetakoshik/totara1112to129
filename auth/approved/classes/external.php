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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package auth_approved
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

/**
 * Holds definitions of Ajax functions used in the auth_approved plugin.
 */
class auth_approved_external extends external_api {
    /**
     * Locates job assignments associated with users whose names start with the given value(s).
     *
     * @throws coding_exception if an invalid frameworkid is found in the settings.
     * @param string $searchquery names to match against.
     * @param int $page indicates the starting point from which to search.
     * @param int $perpage results per page.
     * @param string $termaggregation
     *
     * @return array of (job assignment id, user fullname + job title) tuples.
     */
    public static function job_assignment_by_user_names($searchquery, $page = 0, $perpage = 0, $termaggregation = 'OR') {
        global $CFG, $DB, $PAGE;

        $params = self::validate_parameters(
            self::job_assignment_by_user_names_parameters(), [
                'searchquery' => $searchquery,
                'page' => $page,
                'perpage' => $perpage,
                'termaggregation' => $termaggregation
            ]
        );
        $searchquery = $params['searchquery'];
        $page = $params['page'];
        $perpage = $params['perpage'];
        $termaggregation = ($params['termaggregation'] === 'AND') ? ' AND ' : ' OR ';
        unset($params);
        // DO NOT validate_context. That requires login :(
        $PAGE->reset_theme_and_output();
        $PAGE->set_context(\context_system::instance());

        $terms = preg_split('#\s+#', trim($searchquery));
        if (empty($terms)) {
            $terms = [];
        }

        $sqlparams = ['guestid' => $CFG->siteguest];
        $sqlwhere = [];
        $count = 1;

        foreach ($terms as $term) {
            $count++;
            $searchterm = '%' . $DB->sql_like_escape($term) . '%';
            $like_firstname = $DB->sql_like('u.firstname', ':firstname'.$count, false, false);
            $like_lastname = $DB->sql_like('u.lastname', ':lastname'.$count, false, false);
            $like_title = $DB->sql_like('ja.fullname', ':title'.$count, false, false);
            $sqlparams['firstname'.$count] = $searchterm;
            $sqlparams['lastname'.$count] = $searchterm;
            $sqlparams['title'.$count] = $searchterm;
            $sqlwhere[] = "({$like_firstname} OR {$like_lastname} OR {$like_title})";
        }
        $sqlwhere = join($termaggregation, $sqlwhere);

        $orgjoin = '';
        $orgwhere = '';

        $organisationframeworks = get_config('auth_approved', 'managerorganisationframeworks');
        if (!empty($organisationframeworks) && strpos($organisationframeworks, '-1') === false) {
            $organisationframeworkids = explode(',', $organisationframeworks);
            $containsall = false;
            foreach ($organisationframeworkids as $id) {
                $id = trim($id);
                if ($id === '-1') {
                    $containsall = true;
                    break;
                } else if (empty($id) || !is_numeric($id)) {
                    throw new coding_exception('Invalid organisation framework id');
                }
            }
            if (!$containsall) {
                list($orgwhere, $orgparams) = $DB->get_in_or_equal($organisationframeworkids, SQL_PARAMS_NAMED, 'orgframework');
                $sqlparams = array_merge($sqlparams, $orgparams);
                $orgjoin = 'LEFT OUTER JOIN {org} o ON o.id = ja.organisationid';
                $orgwhere = ' o.frameworkid '.$orgwhere;
            }
        }

        $posjoin = '';
        $poswhere = '';

        $positionframeworks = get_config('auth_approved', 'managerpositionframeworks');
        if (!empty($positionframeworks) && strpos($positionframeworks, '-1') === false) {
            $positionframeworkids = explode(',', $positionframeworks);
            $containsall = false;
            foreach ($positionframeworkids as $id) {
                $id = trim($id);
                if ($id === '-1') {
                    $containsall = true;
                    break;
                } else if (empty($id) || !is_numeric($id)) {
                    throw new coding_exception('Invalid position framework id');
                }
            }
            if (!$containsall) {
                list($poswhere, $posparams) = $DB->get_in_or_equal($positionframeworkids, SQL_PARAMS_NAMED, 'posframework');
                $sqlparams = array_merge($sqlparams, $posparams);
                $posjoin = 'LEFT OUTER JOIN {pos} p ON p.id = ja.positionid';
                $poswhere = ' p.frameworkid ' . $poswhere;
            }
        }

        $posorgwhere = '';
        if (!empty($poswhere) && !empty($orgwhere)) {
            $posorgwhere = "AND ({$poswhere} OR {$orgwhere})";
        } else if (!empty($poswhere)) {
            $posorgwhere = " AND {$poswhere}";
        } else if (!empty($orgwhere)) {
            $posorgwhere = " AND {$orgwhere}";
        }

        $usernamefields = get_all_user_name_fields(true, 'u');
        $sql = "SELECT u.id AS userid, ja.id AS jaid, ja.fullname AS jobtitle, ja.idnumber AS jobidnumber, {$usernamefields}
                  FROM {user} u
                  JOIN {job_assignment} ja ON ja.userid = u.id
                       {$orgjoin} {$posjoin}
                 WHERE ({$sqlwhere})
                   AND u.deleted = 0
                   AND u.id != :guestid {$posorgwhere}
              ORDER BY firstname, lastname, jobtitle";

        $rs = $DB->get_counted_recordset_sql($sql, $sqlparams, $page * $perpage, $perpage);
        $totalcount = $rs->get_count_without_limits();
        $managers = [];
        foreach ($rs as $manager) {
            $fullname = fullname($manager);
            if (!empty($manager->jobtitle)) {
                $fullname .= ' - ' . format_string($manager->jobtitle);
            } else if (!empty($manager->jobidnumber)) {
                $fullname .= ' - ' . get_string('jobassignmentdefaultfullname', 'totara_job', $manager->jobidnumber);
            }
            $managers[] = [
                'userid' => $manager->userid,
                'jaid' => $manager->jaid,
                'displayname' => $fullname
            ];
        }

        return [
          'total' => $totalcount,
          'managers' => $managers,
          'warnings' => []
        ];
    }

    /**
     * Describes the auth_approved_external::job_assignment_by_user_names parameters.
     *
     * @return external_function_parameters
     */
    public static function job_assignment_by_user_names_parameters() {
        return new external_function_parameters([
            'searchquery' => new external_value(PARAM_RAW, 'Search query'),
            'page' => new external_value(PARAM_INT, 'page number (0 based)', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'items per page', VALUE_DEFAULT, 0),
            'termaggregation' => new external_value(PARAM_ALPHA, 'Aggregation between search terms', VALUE_DEFAULT, 'OR'),
        ]);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function job_assignment_by_user_names_returns() {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Total manager count'),
            'managers' => new external_multiple_structure(
                new external_single_structure([
                    'jaid' => new external_value(PARAM_INT, 'Job assignment id'),
                    'displayname' => new external_value(PARAM_TEXT, 'Manager job assignment display name')
                ]),
                'Managers'
            ),
            'warnings' => new external_warnings()
        ]);
    }
}
