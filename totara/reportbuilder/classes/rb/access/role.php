<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\access;
use reportbuilder, context_system, html_writer;

/**
 * Role based access restriction
 *
 * Limit access to reports by user role (either in system context or any context)
 */
class role extends base {

    /**
     * Get list of reports this user is allowed to access by this restriction class
     * @param int $userid reports for this user
     * @return array of permitted report ids
     */
    public function get_accessible_reports($userid) {
        global $DB, $CFG;

        $type = $this->get_type();
        $anycontextcheck = false;
        $allowedreports = array();

        $sql =  "SELECT rb.id AS reportid, rbs.value AS activeroles, rbs2.value AS context
                   FROM {report_builder} rb
        LEFT OUTER JOIN {report_builder_settings} rbs
                     ON rb.id = rbs.reportid
                    AND rbs.type = ?
                    AND rbs.name = ?
        LEFT OUTER JOIN {report_builder_settings} rbs2
                     ON (rbs.reportid = rbs2.reportid
                    AND rbs2.type = ?
                    AND rbs2.name = ?)
                  WHERE rb.embedded = ?";

        $reports = $DB->get_records_sql($sql, array($type, 'activeroles', $type, 'context', 0));

        if (count($reports) > 0) {
            // site admins no longer have records in role_assignments to check: assume access to everything
            if (is_siteadmin($userid)) {
                foreach ($reports as $rpt) {
                    $allowedreports[] = $rpt->reportid;
                }
                return $allowedreports;
            } else {
                //not a siteadmin: pass through recordset, to see if we need to get the 'any context' array for any report
                foreach ($reports as $rpt) {
                    if (isset($rpt->context) && $rpt->context == 'any') {
                        $anycontextcheck = true;
                        break;
                    }
                }
            }

            $siteuserroles = array();
            if ($userid <= 0) {
                // Not logged in - cannot have any real roles!
                if (!empty($CFG->notloggedinroleid) and !in_array((int)$CFG->notloggedinroleid, $siteuserroles)) {
                    $siteuserroles[] = $CFG->notloggedinroleid;
                }
                $anyuserroles = $siteuserroles;

            } else if (isguestuser($userid)) {
                // Guest account - cannot have any real roles!
                if (!empty($CFG->guestroleid) and !in_array((int)$CFG->guestroleid, $siteuserroles)) {
                    $siteuserroles[] = $CFG->guestroleid;
                }
                $anyuserroles = $siteuserroles;

            } else {
                // Get default site context array.
                $sql = "SELECT DISTINCT ra.roleid
                          FROM {role_assignments} ra
                     LEFT JOIN {context} c ON ra.contextid = c.id
                         WHERE ra.userid = ? AND c.contextlevel = ?";
                $siteuserroles = $DB->get_fieldset_sql($sql, array($userid, CONTEXT_SYSTEM));

                // Add defaultuserrole if necessary.
                if (!empty($CFG->defaultuserroleid) and !in_array((int)$CFG->defaultuserroleid, $siteuserroles)) {
                    $siteuserroles[] = $CFG->defaultuserroleid;
                }

                // Only get any context roles if actually needed.
                if ($anycontextcheck) {
                    $sql = "SELECT DISTINCT roleid
                              FROM {role_assignments}
                             WHERE userid = ?";
                    $anyuserroles = $DB->get_fieldset_sql($sql, array($userid));

                    // Add defaultuserrole if necessary.
                    if (!empty($CFG->defaultuserroleid) and !in_array((int)$CFG->defaultuserroleid, $anyuserroles)) {
                        $anyuserroles[] = $CFG->defaultuserroleid;
                    }
                }
            }

            // Now loop through our reports again checking role permissions.
            foreach ($reports as $rpt) {
                $allowed_roles = explode('|', $rpt->activeroles);
                $roles_to_compare = (isset($rpt->context) && $rpt->context == 'any') ? $anyuserroles : $siteuserroles;
                $matched_roles = array_intersect($allowed_roles, $roles_to_compare);
                if (!empty($matched_roles)) {
                    $allowedreports[] = $rpt->reportid;
                }
            }
        }
        return $allowedreports;
    }

    /**
     * Adds form elements required for this access restriction's settings page
     *
     * @param \MoodleQuickForm $mform Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     */
    public function form_template($mform, $reportid) {
        $type = $this->get_type();

        $activeroles = explode('|', reportbuilder::get_setting($reportid, $type, 'activeroles'));
        $context = reportbuilder::get_setting($reportid, $type, 'context');

        // generate the check boxes for the access form
        $mform->addElement('header', 'accessbyroles', get_string('accessbyrole', 'totara_reportbuilder'));

        //TODO replace with checkbox once there is more than one option
        $mform->addElement('hidden', 'role_enable', 1);
        $mform->setType('role_enable', PARAM_INT);

        $systemcontext = context_system::instance();
        $roles = role_fix_names(get_all_roles(), $systemcontext);
        if (!empty($roles)) {
            $contextoptions = array('site' => get_string('systemcontext', 'totara_reportbuilder'), 'any' => get_string('anycontext', 'totara_reportbuilder'));

            // set context for role-based access
            $mform->addElement('select', 'role_context', get_string('context', 'totara_reportbuilder'), $contextoptions);
            $mform->setDefault('role_context', $context);
            $mform->disabledIf('role_context', 'accessenabled', 'eq', 0);
            $mform->addHelpButton('role_context', 'reportbuildercontext', 'totara_reportbuilder');

            $rolesgroup = array();
            foreach ($roles as $role) {
                $rolesgroup[] = $mform->createElement('advcheckbox', "role_activeroles[{$role->id}]", '', $role->localname, null, array(0, 1));
                if (in_array($role->id, $activeroles)) {
                    $mform->setDefault("role_activeroles[{$role->id}]", 1);
                }
            }
            $mform->addGroup($rolesgroup, 'roles', get_string('roleswithaccess', 'totara_reportbuilder'), html_writer::empty_tag('br'), false);
            $mform->disabledIf('roles', 'accessenabled', 'eq', 0);
            $mform->addHelpButton('roles', 'reportbuilderrolesaccess', 'totara_reportbuilder');
        } else {
            $mform->addElement('html', html_writer::tag('p', get_string('error:norolesfound', 'totara_reportbuilder')));
        }
    }

    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @param integer $reportid ID of the report to process
     * @param \MoodleQuickForm $fromform Moodle form data received via form submission
     *
     * @return boolean True if form was successfully processed
     */
    public function form_process($reportid, $fromform) {
        // save the results of submitting the access form to
        // report_builder_settings

        $type = $this->get_type();

        // enable checkbox option
        // TODO not yet used as there is only one access criteria so far
        $enable = (isset($fromform->role_enable) && $fromform->role_enable) ? 1 : 0;
        reportbuilder::update_setting($reportid, $type, 'enable', $enable);

        if (isset($fromform->role_context)) {
            $context = $fromform->role_context;
            reportbuilder::update_setting($reportid, $type, 'context', $context);
        }

        $activeroles = array();
        if (isset($fromform->role_activeroles)) {
            foreach ($fromform->role_activeroles as $roleid => $setting) {
                if ($setting == 1) {
                    $activeroles[] = $roleid;
                }
            }
            // implode into string and update setting
            reportbuilder::update_setting($reportid, $type, 'activeroles', implode('|', $activeroles));
        }

        return true;
    }
}
