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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_core
 */
defined('MOODLE_INTERNAL') || die();

/**
 * base cohort grouping assignment class
 * will mostly be extended by child classes in each totara module, but is generic and functional
 * enough to still be useful for simple assignment cases
 */
class totara_assign_core_grouptype_user extends totara_assign_core_grouptype {

    protected $grouptype = 'user';
    // The dialog class.
    protected $pickerclass = 'totara_assign_ui_picker_user';
    // The module name and moduleinstanceid of the base assignment object.
    protected $module, $moduleinstanceid;
    // The table assignments will be stored in - usually of the form $module_grp_$grouptype.
    protected $tablename;
    protected $params = array(
        'equal' => 1,
        'listofvalues' => 1,
        'includechildren' => 0
    );

    public function __construct($assignobject) {
        // Store the whole assignment object from totara_assign or child class of totara_assign.
        parent::__construct($assignobject);
        $this->module = $this->assignment->get_assign_module();
        $this->moduleinstanceid = $this->assignment->get_assign_moduleinstanceid();

        $this->suffix = $assignobject->get_assign_suffix();
        $suffixstr = strlen($this->suffix) ? '_' . $this->suffix : '';

        $this->tablename = "{$this->module}_grp_{$this->grouptype}{$suffixstr}";
    }

    /**
     * Get displayname for assigned groups of this type.
     * @access  public
     * @return  string
     */
    public function get_grouptype_displayname() {
        return get_string('assignuser', 'totara_core');
    }

    /**
     * Gets the name of a group based off the group assignment id
     *
     * @param  int  $instanceid The id of the group assignment record
     * @return string
     */
    public function get_instance_name($instanceid) {
        global $DB;
        $fullname = $DB->sql_concat_join("' '", totara_get_all_user_name_fields_join('u'));
        // Get users.
        $sql = "SELECT $fullname AS fullname
            FROM {user} u
            WHERE u.id = ?";

        return format_string($DB->get_field_sql($sql, array($instanceid)));
    }

    /**
     * Instantiate and display the dialog class content
     * @access public
     * @param $urlparams array Parameters to be passed to the code handling the dialog submission
     * @param $selectedids array Ids of the items already selected
     * @return void
     */
    public function generate_item_selector($urlparams = array(), $selectedids = array()) {
        // Code to generate organisation picker dialog.
        $picker = new $this->pickerclass();
        // Set parameters.
        $picker->set_parameters($this->params);
        // Get the currently assigned ids.
        $allincludedids = $this->get_assigned_group_includedids();
        // Generate dialog markup.
        $picker->generate_item_selector($urlparams, $allincludedids);
    }

    /**
     * Code to validate data from generate_item_selector().
     * @access public
     * @return bool
     */
    public function validate_item_selector() {
        return true;
    }

    /**
     * Code to accept and process dialog data from generate_item_selector().
     * @access public
     * @param $data associative array of dialog form submission values
     * @return bool
     */
    public function handle_item_selector($data) {
        global $DB;
        // Check target table exists!
        $dbman = $DB->get_manager();
        $table = new xmldb_table($this->tablename);
        if (!$dbman->table_exists($table)) {
            print_error('error:assigntablenotexist', 'totara_core', $this->tablename);
        }
        if ($this->assignment->is_locked()) {
            print_error('error:assignmentmoduleinstancelocked', 'totara_core');
        }
        $modulekeyfield = "{$this->module}{$this->suffix}id";
        $grouptypekeyfield = "{$this->grouptype}id";
        // Add only the new records.
        $existingassignedgroups = $DB->get_fieldset_select($this->tablename, $grouptypekeyfield,
                $modulekeyfield . ' = ' . $this->moduleinstanceid, array());
        foreach ($data['listofvalues'] as $assignedgroupid) {
            if (!in_array($assignedgroupid, $existingassignedgroups)) {
                $todb = new stdClass();
                $todb->$modulekeyfield = $this->moduleinstanceid;
                $todb->$grouptypekeyfield = $assignedgroupid;
                $todb->timecreated = time();
                $DB->insert_record($this->tablename, $todb);
            }
        }
        return true;
    }

    /**
     * Delete an assignment by $id
     * @access public
     * @param $id int ID of assignment record in $tablename
     * @return bool
     */
    public function delete($id) {
        global $DB;
        if (!$this->assignment->is_locked()) {
            $DB->delete_records($this->tablename, array('id' => $id));
        }
    }

    /**
     * Gets array of all assignment records from $tablename
     * @access public
     * @return array of db objects
     */
    public function get_current_assigned_groups() {
        global $DB;
        $modulekeyfield = "{$this->module}{$this->suffix}id";
        $assignedgroups = $DB->get_records($this->tablename, array($modulekeyfield => $this->moduleinstanceid));
        return $assignedgroups;
    }

    /**
     * Get count of the associated users for an assignment
     * @access public
     * @param $groupids array All group ids associated with the assignment -
     *                  For a cohort just the base assignment but Hierarchy assignments may also have a series of child groups
     * @return int User count
     */
    public function get_assigned_user_count($groupids) {
        if (empty($groupids)) {
            return 0;
        }
        return count($groupids);
    }

    /**
     * Get SQL snippet to return information on current assigned groups
     * @access public
     * @param $itemid int The object these groups are assigned to
     * @return string SQL statement
     */
    public function get_current_assigned_groups_sql($itemid) {
        global $DB;
        $fullname = $DB->sql_concat_join("' '", totara_get_all_user_name_fields_join('source'));

        // The sql_concat is to ensure the id field of the records are unique if used in a multi-group query.
        return " SELECT " . $DB->sql_concat("'" . $this->grouptype . "_'", "assignedgroups.id") . " AS id,
                        assignedgroups.id AS assignedgroupid,
                        '{$this->grouptype}' AS grouptype,
                        {$this->grouptype}id AS grouptypeid,
                        0 AS includechildren,
                        source.id AS sourceid,
                        $fullname AS sourcefullname
                   FROM {{$this->tablename}} assignedgroups
             INNER JOIN {{$this->grouptype}} source ON source.id = assignedgroups.{$this->grouptype}id
                  WHERE assignedgroups.{$this->module}{$this->suffix}id = $itemid";
    }

    /**
     * Get SQL to find all users linked via this assignment.
     *
     * @access public
     * @param $assigneduser object Object containing data about a group as generated by {@link get_current_assigned_groups()}
     * @return array(sql,params) Snippet of SQL and parameters need for this assignment.
     */
    public function get_current_assigned_users_sql($assigneduser) {
        $sourceid = $assigneduser->sourceid;

        $sql = "SELECT u.id AS userid FROM {user} u WHERE u.id = ?";
        $params = array($sourceid);

        return array($sql, $params);
    }


    /**
     * Get array of the groups linked to this assignment
     * This is simple in cohorts because cohorts are not hierarchical (yet!).
     * Hierarchy types are more complex as we may have 'includechildren' so we need to find all indicated groups
     * @access public
     * @param $assignedgroup object Object containing data about a group as generated by {@link get_current_assigned_groups()}
     * @return array of ids
     */
    public function get_groupassignment_ids($assignedgroup) {
        // Users don't have children so we just return an array of the userid.
        $userid = $assignedgroup->sourceid;
        return array($userid);
    }

    /**
     * Stub function to be implemented by children.
     */
    public function duplicate($assigneduser, $newassign) {
        global $DB;

        $sql = "INSERT INTO {{$this->tablename}} ({$this->module}{$this->suffix}id, {$this->grouptype}id)
                       (SELECT {$newassign->get_assign_moduleinstanceid()}, {$this->grouptype}id
                          FROM {{$this->tablename}}
                         WHERE id = {$assigneduser->assignedgroupid})";
        $DB->execute($sql, array());
    }

}
