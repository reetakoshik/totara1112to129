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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

namespace auth_approved;

/**
 * Methods that do not seem to fit anywhere else.
 */
final class util {
    /**
     * Get url of the given report,
     * or fall back to pending requests.
     *
     * @param int $reportid
     * @return \moodle_url
     */
    public static function get_report_url($reportid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        if (!$reportid or !$DB->record_exists('report_builder', array('id' => $reportid))) {
            return new \moodle_url('/auth/approved/index.php');
        }

        try {
            $report = \reportbuilder::create($reportid, null, true);
        } catch (\moodle_exception $e) {
            // Most likely the silly acess control in constructor,
            // oh well..
            return new \moodle_url('/auth/approved/index.php');
        }
        return new \moodle_url($report->report_url());
    }

    /**
     * Normalise the domain list data.
     *
     * @param string $domainlist string
     * @return string space separated list of domains
     */
    public static function normalise_domain_list($domainlist) {
        // Domains are case insensitive.
        $domainlist = \core_text::strtolower($domainlist);
        // Explode using give separators, these are not allowed in any domains.
        $domains = preg_split("/[\s,;]+/", $domainlist);
        $newdomains = array();
        foreach ($domains as $k => $domain) {
            // Normalise following types:
            //  xx@example.com --> example.com
            //  *.example.com --> .example.com
            $domain = preg_replace('/^.*[@\*]/', '', $domain);
            if ($domain === '') {
                continue;
            }
            $newdomains[] = $domain;
        }
        $data = implode(' ', $newdomains);
        return trim($data);
    }

    /**
     * Checks if the given email address matches anything in the domain list.
     *
     * @param string $email email address
     * @param string $domainlist space separated list of domains
     * @return bool True if the email matches, false if it is not valid or does not match.
     */
    public static function email_matches_domain_list($email, $domainlist) {
        $domainlist = self::normalise_domain_list($domainlist);
        if (!$domainlist) {
            return false;
        }
        $email = \core_text::strtolower($email);
        if (!validate_email($email)) {
            return false;
        }

        $domains = explode(' ', $domainlist);
        foreach ($domains as $domain) {
            if (substr($domain, 0, 1) !== '.') {
                // We want exact match.
                $domain = '@' . $domain;
            }
            if (substr($email, -1 * strlen($domain)) === $domain) {
                return true;
            }
        }

        return false;
    }

    /**
     * Print request details.
     *
     * @param int $requestid
     * @return string
     */
    public static function render_request_details_view($requestid) {
        global $DB;

        $config = (new \rb_config())->set_embeddata(['requestid' => $requestid])->set_nocache(true);
        $report = \reportbuilder::create_embedded('auth_approved_request_details', $config);
        if (!$report) {
            return '';
        }

        list($sql, $params, $cache) = $report->build_query(false, false);
        $record = $DB->get_record_sql($sql, $params);

        if (!$record) {
            return get_string('error');
        }

        $request = $report->src->process_data_row($record, 'html', $report);

        $data = array();
        foreach ($report->get_columns() as $column) {
            /** @var \rb_column $column */
            if (!$column->display_column(false)) {
                continue;
            }
            $data[$column->type . '-' .$column->value] = array($report->format_column_heading($column, false), array_shift($request));
        }

        $html = '';
        $html .= '<div class="auth_approved-request-details"><dl>';
        foreach ($data as $field => $d) {
            list($heading, $value) = $d;
            if (trim($value) === '') {
                if (preg_match('/-actions$/', $field)) {
                    continue;
                }
                $value = '&nbsp;';
            }
            $html .= "<dt>$heading</dt>";
            $html .= "<dd>$value</dd>";
        }
        $html .= '</dl></div>';

        return $html;
    }

    /**
     * Initialises the dialog code when editing a request.
     *
     * @codeCoverageIgnore
     */
    public static function init_job_assignment_fields() {
        global $PAGE, $CFG;
        require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

        // Setup custom javascript for job JS.
        local_js(array(
            TOTARA_JS_DIALOG,
            TOTARA_JS_TREEVIEW,
            TOTARA_JS_DATEPICKER
        ));
        $PAGE->requires->strings_for_js(array('chooseposition', 'choosemanager', 'chooseorganisation'), 'totara_job');
        $PAGE->requires->strings_for_js(array('error:positionnotselected', 'error:organisationnotselected', 'error:managernotselected'), 'totara_job');
        $jsmodule = array(
            'name' => 'totara_jobassignment',
            'fullpath' => '/totara/job/js/jobassignment.js',
            'requires' => array('json'));

        $selected_position = json_encode(dialog_display_currently_selected(get_string('selected', 'totara_job'), 'position'));
        $selected_organisation = json_encode(dialog_display_currently_selected(get_string('selected', 'totara_job'), 'organisation'));
        $selected_manager = json_encode(dialog_display_currently_selected(get_string('selected', 'totara_job'), 'manager'));
        // This is not a security feature, we do no prevent hacking of hidden fields here,
        // the purpose of the following capability here to prevent privacy of users by limiting access to the list of managers.
        $js_can_edit = has_capability('totara/hierarchy:assignuserposition', \context_system::instance()) ? 'true' : 'false';
        $user = 0;

        // Oh well, use sloppy handwritten json double encoding like the rest of jobs code.
        $args = array('args'=>'{"userid":' . $user . ',' .
            '"disablecreateempty":"true",'.
            '"can_edit":' . $js_can_edit . ','.
            '"dialog_display_position":' . $selected_position . ',' .
            '"dialog_display_organisation":' . $selected_organisation . ',' .
            '"dialog_display_manager":' . $selected_manager . '}');

        $PAGE->requires->js_init_call('M.totara_jobassignment.init', $args, false, $jsmodule);
    }

    /**
     * Given a manager jaid return the title to display as an option in the autocomplete, or false if its not valid.
     *
     * @param string $jobid
     * @return bool|string
     * @throws \coding_exception
     */
    public static function get_manager_job_assignment_option($jobid) {
        global $DB;

        $params = ['jaid' => $jobid];

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
                    throw new \coding_exception('Invalid organisation framework id');
                }
            }
            if (!$containsall) {
                list($orgwhere, $orgparams) = $DB->get_in_or_equal($organisationframeworkids, SQL_PARAMS_NAMED, 'orgframework');
                // It is possible for ja.organisationid to be null but this will cause problems if the position id belongs to a
                // valid position framework. Need to use a left out join to ensure such a ja with a valid position id gets
                // picked up.
                $params = array_merge($params, $orgparams);
                $orgjoin = 'LEFT OUTER JOIN {org} o ON o.id = ja.organisationid';
                $orgwhere = 'o.frameworkid '.$orgwhere;
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
                    throw new \coding_exception('Invalid position framework id');
                }
            }
            if (!$containsall) {
                list($poswhere, $posparams) = $DB->get_in_or_equal($positionframeworkids, SQL_PARAMS_NAMED, 'posframework');
                // It is possible for ja.positionid to be null but this will cause problems if the organisation id belongs to a
                // valid organisation framework. Need to use a left out join to ensure such a ja with a valid organisation id gets
                // picked up.
                $params = array_merge($params, $posparams);
                $posjoin = 'LEFT OUTER JOIN {pos} p ON p.id = ja.positionid';
                $poswhere = 'p.frameworkid ' . $poswhere;
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
        $userfields = get_all_user_name_fields(true, 'u');
        $sql = "SELECT ja.idnumber AS jobidnumber, ja.fullname AS jobtitle, {$userfields}
                  FROM {job_assignment} ja
                  JOIN {user} u ON u.id = ja.userid
                  {$posjoin} {$orgjoin}
                  WHERE ja.id = :jaid {$posorgwhere}";

        $job = $DB->get_record_sql($sql, $params, IGNORE_MISSING);
        if ($job === false) {
            return false;
        }
        $fullname = fullname($job);
        if (!empty($job->jobtitle)) {
            $fullname .= ' - ' . format_string($job->jobtitle);
        } else if (!empty($job->jobidnumber)) {
            $fullname .= ' - ' . get_string('jobassignmentdefaultfullname', 'totara_job', $job->jobidnumber);
        }
        return $fullname;

    }
}
