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
 * Plugin capabilities are defined here.
 *
 * @package     tool_policy
 * @category    access
 * @copyright   2019 reeta kaushik 
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_tool_policy_uninstall() {
    global $DB;

    $result = true;

    $param = array('shortname' => 'policyowner');
    if ($mrole = $DB->get_record('role', $param)) {
        $result = $result && delete_role($mrole->id);
    }

    $param = array('shortname' => 'policyeditor');
    if ($mrole = $DB->get_record('role', $param)) {
        $result = $result && delete_role($mrole->id);
    }

    return $result;
}