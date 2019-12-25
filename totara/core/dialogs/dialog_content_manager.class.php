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
 * @author Jake Salmon <jake.salmon@kineo.com>
 * @package totara
 * @subpackage totara_core/dialogs
 */


require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content.class.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/position/lib.php');

/**
 * Class totara_dialog_content_manager
 *
 * @deprecated Since Totara 10. Use totara_job_dialog_assign_manager instead.
 *
 * Note that method get_items_by_parent in this class does not return all sub-managers for the
 * given manager. It only returns those relating to the given manager's first job assignment.
 */
class totara_dialog_content_manager extends totara_dialog_content {

    /**
     * If you are making access checks seperately, you can disable
     * the internal checks by setting this to true
     *
     * @access  public
     * @var     boolean
     */
    public $skip_access_checks = true;


    /**
     * Type of search to perform (generally relates to dialog type)
     *
     * @access  public
     * @var     string
     */
    public $searchtype = 'manager';


    /**
     * Construct
     */
    public function __construct() {

        error_log('The class totara_dialog_content_manager has been deprecated. Use totara_job_dialog_assign_manager instead');

        // Make some capability checks
        if (!$this->skip_access_checks) {
            require_login();
        }

        $this->type = self::TYPE_CHOICE_MULTI;
    }

    /**
     * Load hierarchy items to display
     *
     * @access  public
     * @param   $parentid   int
     */
    public function load_items($parentid) {
        $this->items = $this->get_items_by_parent($parentid);

        // If we are loading non-root nodes, tell the dialog_content class not to
        // return markup for the whole dialog
        if ($parentid > 0) {
            $this->show_treeview_only = true;
        }

        // Also fill parents array
        $this->parent_items = $this->get_all_parents();
    }


    /**
     * Should we show the treeview root?
     *
     * @access  protected
     * @return  boolean
     */
    protected function _show_treeview_root() {
        return !$this->show_treeview_only;
    }


    /**
     * Return all possible managers
     *
     * @return array Array of managers
     */
    function get_items() {
        global $DB;
        return $DB->get_records_sql("
            SELECT DISTINCT managerja.userid AS sortorder, managerja.userid AS id, manager.lastname
              FROM {job_assignment} staffja
              JOIN {job_assignment} managerja ON staffja.managerjaid = managerja.id
              JOIN {user} manager ON managerja.userid = manager.id
             ORDER BY manager.lastname"
        );
    }

    /**
     * Get all managers who are themselves managed by the specified parent manager
     *
     * Note that only sub-managers of the given manager's first job assignment are considered. To get all
     * sub-managers, use class totara_job_dialog_assign_manager instead of this one. As this class has been
     * deprecated, this behaviour will not be changed.
     *
     * @param int|bool $parentmanagerid
     * @return array
     */
    function get_items_by_parent($parentmanagerid = false) {
        global $DB;

        if ($parentmanagerid) {
            // Returns users who are managers, who's manager is user $parentid.
            $records = $DB->get_records_sql(
                "SELECT manager.id, " . get_all_user_name_fields(true, 'manager') . ", manager.email
                   FROM {job_assignment} parentmanagerja
                   JOIN {job_assignment} managerja ON managerja.managerjaid = parentmanagerja.id
                   JOIN {job_assignment} staffja ON staffja.managerjaid = managerja.id
                   JOIN {user} manager ON manager.id = managerja.userid
                  WHERE parentmanagerja.userid = :parentmanagerid
                    AND parentmanagerja.sortorder = 1
                  ORDER BY manager.firstname, manager.lastname, manager.id",
                array('parentmanagerid' => $parentmanagerid));

            foreach ($records as $index => $record) {
                $records[$index]->fullname = fullname($record);
            }

            return $records;
        }
        else {
            // If no parentmanagerid, grab the root node of this framework
            return $this->get_all_root_items();
        }
    }


    /**
     * Returns all users who are managers but don't have managers, e.g.
     * the top level of the management hierarchy
     *
     * @return array The records for the top level managers
     */
    function get_all_root_items() {
        global $DB;

        // Returns users who *are* managers, but don't *have* a manager
        $records = $DB->get_records_sql("
            SELECT manager.id, " . get_all_user_name_fields(true, 'manager') . ", manager.email
              FROM {job_assignment} staffja
              JOIN {job_assignment} managerja ON staffja.managerjaid = managerja.id
              JOIN {user} manager ON manager.id = managerja.userid
             WHERE managerja.managerjaid IS NULL OR managerja.managerjaid = 0
             GROUP BY manager.id, " . get_all_user_name_fields(true, 'manager') . ", manager.email
             ORDER BY manager.firstname, manager.lastname, manager.id
        ");

        foreach ($records as $index => $record) {
            $records[$index]->fullname = fullname($record);
        }

        return $records;
    }


    /**
     * Get all items that are parents
     * (Use in hierarchy treeviews to know if an item is a parent of others, and
     * therefore has children)
     *
     * @return  array
     */
    function get_all_parents() {
        global $DB;

        // Returns users who *are* managers, who also have staff who *are* managers
        $parents = $DB->get_records_sql("
            SELECT DISTINCT managerja.userid
              FROM {job_assignment} managerja
              JOIN {job_assignment} staffja ON staffja.managerjaid = managerja.id
              JOIN {job_assignment} staffstaffja ON staffstaffja.managerjaid = staffja.id
            ");

        return $parents;
    }
}
