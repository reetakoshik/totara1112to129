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
 * @author Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Restrict content by the session roles
 * Pass in an integer list that represents the session role ids
 */
class rb_session_roles_content extends rb_base_content {

    private $cfgsessionroles;

    /**
     * @param integer $reportfor User ID to determine who the report is for
     */
    public function __construct($reportfor = null) {
        global $CFG;

        $this->cfgsessionroles = array();
        if (!empty($CFG->facetoface_session_roles)) {
            $this->cfgsessionroles = explode(',', $CFG->facetoface_session_roles);
        }
        parent::__construct($reportfor);
    }

    /**
     * Generate the SQL to apply this content restriction
     *
     * @param string $field SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid) {
        global $DB;

        $params = array();
        $norestriction = array(" 1=1 ", $params); // No restrictions.
        $restriction   = array(" 1=0 ", $params); // Restrictions.

        $type = substr(get_class($this), 3);
        $enable = reportbuilder::get_setting($reportid, $type, 'enable');
        if (!$enable) {
            return $norestriction;
        }
        $values = reportbuilder::get_setting($reportid, $type, 'roles');
        $roles = !empty($values) ? json_decode($values, true) : array();
        if (empty($roles)) {
            return $norestriction;
        }

        $allowedroles = array_intersect($this->cfgsessionroles, $roles);

        $userroles = $DB->get_fieldset_sql('SELECT DISTINCT roleid FROM {role_assignments} WHERE userid = ?',
            array($this->reportfor));
        if (empty($userroles)) {
            // This should never happened.
            return $restriction;
        }
        $allowedroles = array_intersect($allowedroles, $userroles);
        if (empty($allowedroles)) {
            return $restriction;
        }

        list($sqlin, $params) = $DB->get_in_or_equal($allowedroles, SQL_PARAMS_NAMED);
        $uqparam = rb_unique_param('user');
        $params[$uqparam] = $this->reportfor;

        $fsr = rb_unique_param('sr');
        $sql = "EXISTS (
                    SELECT *
                      FROM {facetoface_session_roles} {$fsr}
                     WHERE {$fsr}.sessionid =  {$field}
                       AND {$fsr}.userid    = :{$uqparam}
                       AND {$fsr}.roleid {$sqlin}
        )";

        $restriction = array($sql, $params);
        return $restriction;
    }

    /**
     * Generate a human-readable text string describing the restriction
     *
     * @param string $title Name of the field being restricted
     * @param integer $reportid ID of the report
     *
     * @return string Human readable description of the restriction
     */
    public function text_restriction($title, $reportid) {
        global $DB;

        $type = substr(get_class($this), 3);
        $values = reportbuilder::get_setting($reportid, $type, 'roles');
        $roles = !empty($values) ? json_decode($values, true) : array();

        $allowedroles = array_intersect($this->cfgsessionroles, $roles);

        $user = $DB->get_record('user', array('id' => $this->reportfor));
        $userroles = $DB->get_fieldset_sql('SELECT DISTINCT roleid FROM {role_assignments} WHERE userid = ?',
            array($this->reportfor));

        $ids = array_intersect($userroles, $allowedroles);
        $rolelocalnames = role_fix_names(array_flip($ids), null, ROLENAME_ORIGINAL, true);

        $a = new stdClass();
        $a->rolelocalnames = implode(', ', array_values($rolelocalnames));
        $a->title = $title;
        $a->userfullname = fullname($user);
        return get_string('sessionroles_txtrestr', 'totara_reportbuilder', $a);
    }

    /**
     * Adds form elements required for this content restriction's settings page
     *
     * @param object &$mform Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     * @param string $title Name of the field the restriction is acting on
     */
    public function form_template(&$mform, $reportid, $title) {

        if (empty($this->cfgsessionroles)) {
            return;
        }

        $type = substr(get_class($this), 3);
        $enable = reportbuilder::get_setting($reportid, $type, 'enable');
        $values = reportbuilder::get_setting($reportid, $type, 'roles');
        $roles = !empty($values) ? json_decode($values, true) : array();

        $mform->addElement('header', 'session_roles', get_string('showbyx', 'totara_reportbuilder', lcfirst($title)));
        $mform->setExpanded('session_roles');
        $mform->addElement('checkbox', 'session_roles_enable', '',
            get_string('showbasedonx', 'totara_reportbuilder', lcfirst($title)));
        $mform->setDefault('session_roles_enable', $enable);
        $mform->disabledIf('session_roles_enable', 'contentenabled', 'eq', 0);

        $rolelist = role_fix_names(array_fill_keys($this->cfgsessionroles, 'roleid'), null, ROLENAME_ORIGINAL, true);

        $checkgroup = array();
        foreach ($rolelist as $id => $title) {
            $checkgroup[] =& $mform->createElement('advcheckbox', 'role['.$id.']', '', $title, null, array(0, 1));

        }
        $mform->addGroup($checkgroup, 'session_roles_group',
            get_string('includesessionroles', 'totara_reportbuilder'), html_writer::empty_tag('br'), false);

        foreach ($this->cfgsessionroles as $rolegroup) {
            if (in_array($rolegroup, $roles)) {
                $mform->setDefault('role['.$rolegroup.']', 1);
            }
        }

        $mform->disabledIf('session_roles_group', 'contentenabled', 'eq', 0);
        $mform->disabledIf('session_roles_group', 'session_roles_enable', 'notchecked');
    }

    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @param integer $reportid ID of the report to process
     * @param object $fromform Moodle form data received via form submission
     *
     * @return boolean True if form was successfully processed
     */
    public function form_process($reportid, $fromform) {

        $status = true;
        $type = substr(get_class($this), 3);
        $enable = (isset($fromform->session_roles_enable) && $fromform->session_roles_enable) ? 1 : 0;
        $status = $status && reportbuilder::update_setting($reportid, $type, 'enable', $enable);
        if (!$enable) {
            return $status;
        }

        // Roles checkbox option.
        // Enabled options are stored as role[key] = 1 when enabled.
        // Key is a bitwise value to be summed and stored.
        $roles = isset($fromform->role) ? $fromform->role : array();
        if (empty($roles)) {
            return false;
        }
        $values = array();
        foreach ($roles as $val => $option) {
            if ($option && in_array($val, $this->cfgsessionroles)) {
                $values[] = $val;
            }
        }
        if (empty($values)) {
            return false;
        }
        $jsondata = json_encode($values, JSON_NUMERIC_CHECK);
        $status = $status && reportbuilder::update_setting($reportid, $type, 'roles', $jsondata);
        return $status;
    }
}