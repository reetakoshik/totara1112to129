<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin rols are defined here.
 *
 * @package     tool_policy
 * @category    access
 * @copyright   2019 reeta kaushik 
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_tool_policy_install() {
    global $DB;

    // The commented out code is waiting for a fix for MDL-25709
    $result = true;
    $timenow = time();
    $sysctx = context_system::instance();
    $mrole = new stdClass();
    $levels = array(CONTEXT_SYSTEM, CONTEXT_COURSE, CONTEXT_MODULE);
    $param = array('shortname' => 'manager');
    $manager = $DB->get_records('role', $param, 'id ASC', 'id', 0, 1);
    if (empty($manager)) {
        $param = array('archetype' => 'manager');
        $manager = $DB->get_records('role', $param, 'id ASC', 'id', 0, 1);
    }
    $managerid = array_shift($manager);
    

    // Fully setup the policyowner role.
    $param = array('shortname' => 'policyowner');
    if (!$mrole = $DB->get_record('role', $param)) {

        if ($rid = create_role(get_string('policyowner', 'policy'), 'policyowner',
                               get_string('policyownerdescription', 'policy'), 'policyowner')) {

            $mrole = new stdClass();
            $mrole->id = $rid;
            $result = $result && assign_capability('tool/policy:policyowner', CAP_ALLOW, $mrole->id, $sysctx->id);

            set_role_contextlevels($mrole->id, $levels);
        } else {
            $result = false;
        }
    }
    if (isset($managerid->id)) {
        $param = array('allowassign' => $mrole->id, 'roleid' => $managerid->id);
        if (!$DB->get_record('role_allow_assign', $param)) {
            allow_assign($managerid->id, $mrole->id);
        }
    }
    

    // Fully setup the policyeditor role.
    $param = array('shortname' => 'policyeditor');

    if ($result && !($mrole = $DB->get_record('role', $param))) {

        if ($rid = create_role(get_string('policyeditor', 'policy'), 'policyeditor',
                               get_string('policyeditordescription', 'policy'), 'policyeditor')) {

            $mrole = new stdClass();
            $mrole->id  = $rid;
            $result = $result && assign_capability('tool/policy:managedocs', CAP_ALLOW, $mrole->id, $sysctx->id);
            set_role_contextlevels($mrole->id, $levels);
        } else {
            $result = false;
        }
    }
    if (isset($managerid->id)) {
        $param = array('allowassign' => $mrole->id, 'roleid' => $managerid->id);
        if (!$DB->get_record('role_allow_assign', $param)) {
            allow_assign($managerid->id, $mrole->id);
        }
    }

    return $result;
}