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
 * @package totara_dashboard
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/totara/core/lib.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');

/**
 * Dashboard instance management
 */
class totara_dashboard {

    /**
     * Dashboard availability
     */
    const ALL = 2;
    const AUDIENCE = 1;
    const NONE = 0;

    /**
     * Dashboard id
     *
     * @var int
     */
    protected $id = 0;

    /**
     * Dashboard name
     *
     * @var string
     */
    public $name = '';

    /**
     * How dashboard published: 0 - hidden, 1 - to selected audiences, 2 - to all logged in users
     *
     * @var int 0|1|2
     */
    public $published = 0;

    /**
     * Can users change their dashboard
     *
     * @var int 0|1
     */
    public $locked = 0;

    /**
     * Order of dashboards display in navigation
     *
     * @var int
     */
    public $sortorder = 0;

    /**
     * Assigned cohorts
     *
     * @var array of int id's
     */
    private $cohorts = null;


    /**
     * Get List of all dashboards
     * It is not expected to have more than 100 dashboards, so no paging here.
     * Much bigger number of dashboards might reduce preformance.
     *
     * @return array of totara_dashboard
     */
    public static function get_manage_list() {
        global $DB;
        $records = $DB->get_records('totara_dashboard', null, 'sortorder', 'id');
        $dashboards = array();
        foreach ($records as $record) {
            $dashboards[] = new totara_dashboard($record->id);
        }
        return $dashboards;
    }

    /**
     * Get list of user dashboards
     *
     * @param int $userid
     * @return array of dashboard records
     */
    public static function get_user_dashboards($userid) {
        global $DB;

        // If dashboards are disabled then return an empty array
        // so redirects are done where necessary.
        if (totara_feature_disabled('totaradashboard')) {
            return array();
        }

        //Create a cache to store user dashbaords
        $cache = cache::make_from_params(cache_store::MODE_REQUEST, 'totara_core', 'dashboard');
        if ($cache->has('user_' . $userid)) {
            return $cache->get('user_' . $userid);
        }

        // Get user cohorts.
        $cohortsql = '1 = 0';
        $cohortsparams = array();
        $cohorts = totara_cohort_get_user_cohorts($userid);
        if (count($cohorts)) {
            list($cohortlistsql, $cohortsparams) = $DB->get_in_or_equal($cohorts, SQL_PARAMS_NAMED);
            $cohortsql = 'tdc.cohortid ' . $cohortlistsql;
        }
        // Check relevant dashboards.
        $sql = "SELECT DISTINCT td.*
                FROM {totara_dashboard} td
                LEFT JOIN {totara_dashboard_cohort} tdc ON (tdc.dashboardid = td.id)
                WHERE ($cohortsql OR td.published = 2)
                  AND td.published > 0
                ORDER BY td.sortorder
               ";
        $results = $DB->get_records_sql($sql, $cohortsparams);
        $cache->set('user_' . $userid, $results);
        return $results;
    }

    /**
     * Create instance of dashboard
     *
     * @param int $id
     */
    public function __construct($id = 0) {
        global $DB;

        if ($id == 0) {
            return;
        }

        $record = $DB->get_record('totara_dashboard', array('id' => $id));
        $this->id = $record->id;
        $this->name = $record->name;
        $this->published = $record->published;
        $this->locked = $record->locked;
        $this->sortorder = $record->sortorder;
    }

    /**
     * Is current dashboard first in order
     *
     * @return boolean
     */
    public function is_first() {
        global $DB;
        $record = $DB->get_record_sql('SELECT MIN(sortorder) minsort FROM {totara_dashboard}');
        if ($record->minsort == $this->sortorder) {
            return true;
        }
        return false;
    }

    /**
     * Is current dashboard last in order
     *
     * @return boolean
     */
    public function is_last() {
        global $DB;
        $record = $DB->get_record_sql('SELECT MAX(sortorder) maxsort FROM {totara_dashboard}');
        if ($record->maxsort == $this->sortorder) {
            return true;
        }
        return false;
    }

    /**
     * Change dashboard order to higher position
     */
    public function move_up() {
        db_reorder($this->id, $this->sortorder - 1, 'totara_dashboard');
    }

    /**
     * Change dashboard order to lower position
     */
    public function move_down() {
        db_reorder($this->id, $this->sortorder + 1, 'totara_dashboard');
    }

    /**
     * What level of visibility audience have totara_dashboard::NONE, totara_dashboard::AUDIENCE, totara_dashboard::ALL,
     *
     * @return int
     */
    public function get_published() {
        return $this->published;
    }

    /**
     * Are users able to change their dashboard
     *
     * @return boolean
     */
    public function is_locked() {
        return (bool)$this->locked;
    }

    /**
     * Prevent changes to dashboard by users
     *
     * @return totara_dashboard $this
     */
    public function lock() {
        $this->locked = 1;
        return $this;
    }

    /**
     * Save instance to database
     */
    public function save() {
        global $DB;
        $record = $this->get_for_form();

        if ($this->id > 0) {
            $DB->update_record('totara_dashboard', $record);
        } else {
            $id = $DB->insert_record('totara_dashboard', $record);
            $this->id = $id;
            db_reorder($this->id, -1, 'totara_dashboard');

            // Add dashboard block to every new dashboard.
            $this->add_naviation_block();
        }
        $this->save_cohorts();
    }

    /**
     * Return instance data
     *
     * @return stdClass
     */
    public function get_for_form() {
        $instance = new stdClass();
        $instance->id = $this->id;
        $instance->name = $this->name;
        $instance->published = (int)$this->published;
        $instance->locked = (int)$this->locked;
        $instance->sortorder = (int)$this->sortorder;
        $instance->cohorts = implode(',', $this->get_cohorts());

        return $instance;
    }

    /**
     * Set instance fields from stdClass
     *
     * @param stdClass $data
     * @return totara_dashboard $this
     */
    public function set_from_form(stdClass $data) {
        $this->name = '';
        $this->locked = 0;
        $this->published = 0;
        $this->set_cohorts(array());

        if (isset($data->name)) {
            $this->name = $data->name;
        }
        if (isset($data->locked)) {
            $this->locked = (bool)$data->locked;
        }
        if (isset($data->published)) {
            $this->published = (int)$data->published;
        }

        if (isset($data->cohorts)) {
            if (is_array($data->cohorts)) {
                $this->cohorts = $data->cohorts;
            } else if (empty($data->cohorts)) {
                $this->cohorts = array();
            } else {
                $exploded = explode(',', $data->cohorts);
                foreach ($exploded as $check) {
                    if ((int)$check < 1) {
                        throw new coding_exception("Couldn't parse cohorts data:" . $data->cohorts);
                    }
                }
                $this->cohorts = $exploded;
            }
        }
        return $this;
    }

    /**
     * Get dashboard id
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get assigned audience id's
     *
     * @return array of cohort id's
     */
    public function get_cohorts() {
        global $DB;

        if (is_null($this->cohorts)) {
            $records = $DB->get_records('totara_dashboard_cohort', array('dashboardid' => $this->id));
            $cohortids = array();
            foreach ($records as $record) {
                $cohortids[] = $record->cohortid;
            }
            $this->cohorts = $cohortids;
        }
        return $this->cohorts;
    }

    /**
     * Set new assigned cohorts
     *
     * @param array $cohortids
     */
    public function set_cohorts(array $cohortids) {
        $this->cohorts = $cohortids;
    }

    /**
     * Save cohorts assignment to db
     */
    protected function save_cohorts() {
        global $DB;
        $cohortkeys = array_flip($this->cohorts);
        $records = $DB->get_records('totara_dashboard_cohort', array('dashboardid' => $this->id));
        foreach ($records as $record) {
            // If record is present in both, remove it from new assignemnts.
            if (isset($cohortkeys[$record->cohortid])) {
                unset($cohortkeys[$record->cohortid]);
            } else {
                // If record not in new assignments array, delete it.
                $DB->delete_records('totara_dashboard_cohort', array('id' => $record->id));
            }
        }

        // Add all new assignments to database.
        foreach ($cohortkeys as $cohortid => $unused) {
            $newcohort = new stdClass();
            $newcohort->dashboardid = $this->id;
            $newcohort->cohortid = $cohortid;
            $DB->insert_record('totara_dashboard_cohort', $newcohort);
        }
    }

    /**
     * Remove dashboard from DB
     */
    public function delete() {
        global $DB;
        if ($this->id) {
            // Reorder it to last.
            db_reorder($this->id, -1, 'totara_dashboard');

            // Delete user block instances.
            $this->reset_all();
            $DB->delete_records('totara_dashboard_user', array('dashboardid' => $this->id));

            // Delete assigned cohorts.
            $DB->delete_records('totara_dashboard_cohort', array('dashboardid' => $this->id));

            // Delete master block instances.
            $this->delete_dashboard_blocks();

            // Delete dashboard.
            $DB->delete_records('totara_dashboard', array('id' => $this->id));
        }
    }

    /**
     * Clones the current dashboard.
     *
     * This method clones the dashboard including its blocks, their configuration, and any assigned audiences.
     * It does NOT clone any user customisations of this dashboard.
     *
     * @return int The id of the newly created dashboard.
     */
    public function clone_dashboard() {
        global $DB;

        // First create the dashboard record.
        $dashboard = $DB->get_record('totara_dashboard', array('id' => $this->id));
        unset($dashboard->id);
        $dashboard->name = $this->generate_clone_name();
        $dashboard->id = $DB->insert_record('totara_dashboard', $dashboard);
        // Move the newly created dashboard to the end of the list.
        db_reorder($dashboard->id, -1, 'totara_dashboard');

        // Now copy across the blocks and their content.
        $context = context_system::instance();
        $sql = "SELECT
                    bi.*,
                    bp.blockinstanceid,
                    bp.contextid,
                    bp.pagetype,
                    bp.subpage,
                    bp.visible,
                    bp.region,
                    bp.weight
                FROM {block_instances} bi
                LEFT JOIN {block_positions} bp ON bp.blockinstanceid = bi.id
                WHERE parentcontextid = :parentcontextid AND pagetypepattern = :pagetypepattern
                ORDER BY bi.id";
        $params = array(
            'parentcontextid' => $context->id,
            'pagetypepattern' => 'totara-dashboard-' . $this->id
        );
        $blockinstances = $DB->get_records_sql($sql, $params);
        if ($blockinstances) {
            foreach ($blockinstances as $bi) {
                // Clone block record.
                $block = new stdClass();
                // Amend the page type pattern to the newly cloned dashboard.
                $block->pagetypepattern = 'totara-dashboard-' . $dashboard->id;
                $block->blockname = $bi->blockname;
                $block->parentcontextid = $bi->parentcontextid;
                $block->showinsubcontexts = $bi->showinsubcontexts;
                $block->subpagepattern = $bi->subpagepattern;
                $block->defaultregion = $bi->defaultregion;
                $block->defaultweight = $bi->defaultweight;
                $block->configdata = $bi->configdata;
                $block->common_config = $bi->common_config;
                // Create the new block record.
                $block->id = $DB->insert_record('block_instances', $block);

                // If block position exists then clone it too.
                if ($bi->blockinstanceid) {
                    $bp = new stdClass();
                    $bp->blockinstanceid = $block->id;
                    $bp->contextid = $bi->contextid;
                    $bp->pagetype  = $block->pagetypepattern;
                    $bp->subpage   = $bi->subpage;
                    $bp->visible   = $bi->visible;
                    $bp->region    = $bi->region;
                    $bp->weight    = $bi->weight;
                    // Create the new block position record.
                    $bp->id = $DB->insert_record('block_positions', $bp);
                }

                // Force the creation of the block context.
                context_block::instance($block->id);
                // Copy the block content from one to the next.
                $block = block_instance($block->blockname, $block);
                if (!$block->instance_copy($bi->id)) {
                    debugging("Unable to copy block data for original block instance: $bi->id to new block instance: $block->id", DEBUG_DEVELOPER);
                }
            }
        }

        // Finally copy across any assigned audiences.
        $assignedcohorts = $DB->get_records('totara_dashboard_cohort', array('dashboardid' => $this->id));
        foreach ($assignedcohorts as $cohort) {
            $cohort->dashboardid = $dashboard->id;
            $DB->insert_record('totara_dashboard_cohort', $cohort);
        }

        return $dashboard->id;
    }

    /**
     * Generates a new name to use for the dashboard when it is being cloned.
     *
     * @return string
     * @throws coding_exception
     */
    protected function generate_clone_name() {
        global $DB;
        $count = 1;
        $name = get_string('clonename', 'totara_dashboard', array('name' => $this->name, 'count' => $count));
        $stop = false;
        while ($DB->record_exists('totara_dashboard', array('name' => $name)) && !$stop) {
            $count++;
            if ($count > 25) {
                // This is getting mad. 25 is plenty, we'll stop here.
                $stop = true;
                // Append a + to show that there are more. This probably won't translate perfectly but it should be a very rare
                // edge case to end up with 25 clones.
                $count = '25+';
            }
            $name = get_string('clonename', 'totara_dashboard', array('name' => $this->name, 'count' => $count));
        }
        return $name;
    }

    /**
     * Check if user has own modification of dashboard
     *
     * @param int $userid
     * @return int $userpageid
     */
    public function get_user_pageid($userid) {
        global $DB;
        $record = $DB->get_record('totara_dashboard_user', array('dashboardid' => $this->id, 'userid' => $userid));
        if ($record) {
            return $record->id;
        }
        return 0;
    }

    /**
     * Make user copy of dashboard
     * If it is already exists it will be returned.
     *
     * @param int $userid
     * @return int User copy page id
     */
    public function user_copy($userid) {
        global $DB;
        // Check if it is already exists.
        $record = $DB->get_record('totara_dashboard_user', array('dashboardid' => $this->id, 'userid' => $userid));
        if ($record) {
            return $record->id;
        }

        // Make new copy.
        $newrecord = new stdClass();
        $newrecord->dashboardid = $this->id;
        $newrecord->userid = $userid;
        $userpageid = $DB->insert_record('totara_dashboard_user', $newrecord);

        // Copy block instances.
        $systemcontext = context_system::instance();
        $usercontext = context_user::instance($userid);

        // Copy instances.
        $blockinstances = $DB->get_records('block_instances', array('parentcontextid' => $systemcontext->id,
                                                                    'pagetypepattern' => 'totara-dashboard-' . $this->id,
                                                                    'subpagepattern' => 'default'));
        $clonedids = array();
        foreach ($blockinstances as $instance) {
            $originalid = $instance->id;
            unset($instance->id);
            $instance->parentcontextid = $usercontext->id;
            $instance->subpagepattern = $userpageid;
            $instance->id = $DB->insert_record('block_instances', $instance);
            context_block::instance($instance->id);  // Just creates the context record.
            $block = block_instance($instance->blockname, $instance);
            $block->instance_copy($originalid);
            $clonedids[$originalid] = $instance->id;
        }

        // Copy positions of system blocks.
        $blockpositions = $DB->get_records('block_positions', array(
            'contextid' => $systemcontext->id,
            'pagetype' => 'totara-dashboard-' . $this->id,
            'subpage' => 'default'
        ));
        if (!empty($blockpositions)) {
            foreach ($blockpositions as $position) {
                unset($position->id);
                $position->contextid = $usercontext->id;
                $position->subpage = $userpageid;
                // If new block was created, we need to change its id as well.
                if (!empty($clonedids[$position->blockinstanceid])) {
                    $position->blockinstanceid = $clonedids[$position->blockinstanceid];
                }
                $position->id = $DB->insert_record('block_positions', $position);
            }
        }

        return $userpageid;
    }


    /**
     * Remove user modifications to dashboard
     *
     * @param int $userid
     */
    public function user_reset($userid) {
        global $DB;

        $pageid = $this->get_user_pageid($userid);
        if ($pageid) {
            $context = context_user::instance($userid, IGNORE_MISSING);
            if ($context) {
                if ($blocks = $DB->get_records('block_instances', array('parentcontextid' => $context->id,
                        'pagetypepattern' => 'totara-dashboard-' . $this->id))) {
                    foreach ($blocks as $block) {
                        if (is_null($block->subpagepattern) || $block->subpagepattern == $pageid) {
                            blocks_delete_instance($block);
                        }
                    }
                }
                $DB->delete_records('block_positions', array(
                    'contextid' => $context->id,
                    'pagetype' => 'totara-dashboard-' . $this->id,
                    'subpage' => $pageid
                ));
            }

            $DB->delete_records('totara_dashboard_user', array('id' => $pageid));
        }
    }

    /**
     * Reset modifications to current dashboard for all users
     */
    public function reset_all() {
        global $DB;
        $userpages = $DB->get_records('totara_dashboard_user', array('dashboardid' => $this->id));
        if (!empty($userpages)) {
            foreach ($userpages as $page) {
                $this->user_reset($page->userid);
            }
        }
    }

    /**
     * Add "totara_dashboard" block to current dashboard.
     */
    public function add_naviation_block() {
        global $CFG;
        require_once($CFG->libdir . '/blocklib.php');

        $page = new moodle_page();
        $page->set_context(context_system::instance());
        $page->set_pagelayout('dashboard');
        $page->set_pagetype('totara-dashboard-' . $this->id);
        $page->set_subpage('default');

        $blockman = $page->blocks;
        $blockman->add_block('totara_dashboard', $blockman->get_default_region(), -1, false, null, 'default');
    }

    /**
     * Remove all blocks that related to dashboard master layout.
     */
    protected function delete_dashboard_blocks() {
        global $DB;

        if ($blocks = $DB->get_records('block_instances', array('pagetypepattern' => 'totara-dashboard-' . $this->id))) {
            foreach ($blocks as $block) {
                blocks_delete_instance($block);
            }
        }
    }

    /**
    * Prints an error if Totara Dashboard is not enabled.
    */
    public static function check_feature_enabled() {
        if (totara_feature_disabled('totaradashboard')) {
            print_error('totaradashboarddisabled', 'totara_dashboard');
        }
    }
}

/**
 * Return the block pagetype options for Totara dashboards.
 * @param string $pagetype
 * @param context $parentcontext
 * @param context $currentcontext
 * @return array
 */
function totara_dashboard_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $result = [];
    if (strpos($pagetype, 'totara-dashboard-') === 0) {
        $result[$pagetype] = get_string('pagetype-this-dashboard', 'totara_dashboard');
    }

    // Allow block to be changed back to 'Any page'
    $result['*'] = get_string('page-x', 'pagetype');

    return $result;
}
