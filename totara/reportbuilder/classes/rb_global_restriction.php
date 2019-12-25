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
 * @author Rob Tyler <rob.tyler@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Global restriction object class.
 *
 * @author Rob Tyler <rob.tyler@totaralms.com>
 * @package totara_reportbuilder
 */
class rb_global_restriction {

    /**
     * @var int $id
     */
    public $id;

    /**
     * @var string $name
     */
    public $name;

    /**
     * @var string $description
     */
    public $description;

    /**
     * @var int $active
     */
    public $active = 0;

    /**
     * @var int $allrecords
     */
    public $allrecords = 0;

    /**
     * @var int $allusers
     */
    public $allusers = 0;

    /**
     * @var int $sortorder
     */
    public $sortorder;

    /**
     * @var int $timemodified
     */
    public $timemodified;

    /**
     * @var int $timecreated
     */
    public $timecreated;

    /**
     * Constructor.
     *
     * @param int $id The id of the restriction record to load.
     */
    public function __construct($id = null) {
        if ($id > 0) {
            $this->load($id);
        }
    }

    /**
     * Load restriction properties from database.
     *
     * @throws ReportBuilderException If the restriction can not be found.
     *
     * @param int $id The record id of the restriction to load,
     * @return rb_global_restriction
     */
    protected function load($id) {
        global $DB;

        // Load the restriction give the id. We use MUST_EXIST to ensure it exists.
        $restriction = $DB->get_record('report_builder_global_restriction', array('id' => $id), '*', MUST_EXIST);

        $this->id = $restriction->id;
        $this->name = $restriction->name;
        $this->description = $restriction->description;
        $this->active = $restriction->active;
        $this->allrecords = $restriction->allrecords;
        $this->allusers = $restriction->allusers;
        $this->sortorder = $restriction->sortorder;
        $this->timemodified = $restriction->timemodified;
        $this->timecreated = $restriction->timecreated;
    }

    /**
     * Get stdClass containing instance properties.
     *
     * @return stdClass
     */
    public function get_record_data() {
        $obj = new stdClass();
        $obj->id = $this->id;
        $obj->name = $this->name;
        $obj->description = $this->description;
        $obj->active = $this->active;
        $obj->allrecords = $this->allrecords;
        $obj->allusers = $this->allusers;
        $obj->sortorder = $this->sortorder;
        $obj->timecreated = $this->timecreated;
        $obj->timemodified = $this->timecreated;

        return $obj;
    }

    /**
     * Save restriction properties to the database.
     *
     * @throws coding_exception if you try to insert a restriction that already exists. Use update instead.
     *
     * @param stdClass $data new data
     * @return int new restriction id
     */
    public function insert($data) {
        global $DB;

        if ($this->id) {
            throw new coding_exception('Cannot insert over existing restriction');
        }

        // A little magic to ensure we have a stdClass object.
        $data = (object)(array)$data;

        $default = (array)$this->get_record_data();
        foreach ($default as $k => $v) {
            if (!property_exists($data, $k)) {
                $data->$k = $v;
            }
        }

        unset($data->id);
        $data->timecreated = $data->timemodified = time();

        $max = $DB->get_field('report_builder_global_restriction', 'MAX(sortorder)', array());
        $data->sortorder = ($max === null ? 0 : intval($max) + 1);

        $id = $DB->insert_record('report_builder_global_restriction', $data);

        $this->load($id);

        // Might need an event to go in here.
        //\totara_restriction\event\restriction_created::create_from_instance($this)->trigger();

        return $this->id;
    }

    /**
     * Save restriction properties to the database.
     *
     * @throws coding_exception If you try to update a restriction that does not exist yet. Use insert instead.
     *
     * @param stdClass $data new data
     */
    public function update($data) {
        global $DB;

        if (!$this->id) {
            throw new coding_exception('Cannot update non-existent restriction');
        }

        $data = (array)$data;
        $current = $this->get_record_data();
        foreach ($data as $k => $v) {
            if (!property_exists($current, $k)) {
                unset($data[$k]);;
            }
        }
        $data = (object)$data;

        $data->id = $this->id;
        unset($data->timecreated);
        $data->timemodified = time();

        $DB->update_record('report_builder_global_restriction', $data);

        $this->load($this->id);

        // Might need an event to go in here.
        //\totara_restriction\event\restriction_updated::create_from_instance($this)->trigger();
    }

    /**
     * Delete restriction.
     */
    public function delete() {
        global $DB;

        $DB->delete_records('reportbuilder_grp_cohort_record', array ('reportbuilderrecordid' => $this->id));
        $DB->delete_records('reportbuilder_grp_org_record', array ('reportbuilderrecordid' => $this->id));
        $DB->delete_records('reportbuilder_grp_pos_record', array ('reportbuilderrecordid' => $this->id));
        $DB->delete_records('reportbuilder_grp_user_record', array ('reportbuilderrecordid' => $this->id));

        $DB->delete_records('reportbuilder_grp_cohort_user', array ('reportbuilderuserid' => $this->id));
        $DB->delete_records('reportbuilder_grp_org_user', array ('reportbuilderuserid' => $this->id));
        $DB->delete_records('reportbuilder_grp_pos_user', array ('reportbuilderuserid' => $this->id));
        $DB->delete_records('reportbuilder_grp_user_user', array ('reportbuilderuserid' => $this->id));

        $DB->delete_records('report_builder_global_restriction', array ('id' => $this->id));
    }

    /**
     * Delete restriction.
     */
    public function activate() {
        global $DB;

        $this->active = '1';
        $DB->set_field('report_builder_global_restriction', 'active', $this->active, array('id' => $this->id));
    }

    /**
     * Delete restriction.
     */
    public function deactivate() {
        global $DB;

        $this->active = '0';
        $DB->set_field('report_builder_global_restriction', 'active', $this->active, array('id' => $this->id));
    }

    /**
     * Move down.
     */
    public function down() {
        $this->move(false);
    }

    /**
     * Move up.
     */
    public function up() {
        $this->move(true);
    }

    /**
     * Move the records and reorder if necessary.
     *
     * @param bool $up
     */
    protected function move($up) {
        global $DB;

        if (!$this->id) {
            return;
        }
        // Note: db_reorder() does not work for first and last items, hack the sort here for now.

        if ($up) {
            $sql = "SELECT id, sortorder
                      FROM {report_builder_global_restriction}
                     WHERE sortorder < ?
                  ORDER BY sortorder DESC";
        } else {
            $sql = "SELECT id, sortorder
                      FROM {report_builder_global_restriction}
                     WHERE sortorder > ?
                  ORDER BY sortorder ASC";
        }
        $switch = $DB->get_records_sql($sql, array($this->sortorder), 0, 1);
        if ($switch) {
            $switch = reset($switch);
            $DB->set_field('report_builder_global_restriction', 'sortorder', $this->sortorder, array('id' => $switch->id));
            $DB->set_field('report_builder_global_restriction', 'sortorder', $switch->sortorder, array('id' => $this->id));
        }

        // Reorder just in case.
        $sort = 0;
        $records = $DB->get_records('report_builder_global_restriction', array(), 'sortorder ASC', 'id, sortorder');
        foreach ($records as $record) {
            if ($record->sortorder != $sort) {
                $DB->set_field('report_builder_global_restriction', 'sortorder', $sort, array('id' => $record->id));
            }
            $sort++;
        }

        $this->load($this->id);
    }

    /**
     * Get all global report restrictions list
     *
     * @param int $offset Offset for paging
     * @param int $limit How much return of whole set
     * @param int $count Total records of global report restrictions
     * @return array of ordered all global report restrictions
     */
    public static function get_all($offset = 0, $limit = 0, &$count) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib/assign/lib.php');

        $restrictions = $DB->get_records('report_builder_global_restriction', array(), 'sortorder ASC', '*', $offset*$limit, $limit);
        $count = $DB->count_records('report_builder_global_restriction');

        foreach ($restrictions as $restriction) {
            // Load the collections for records to view.
            $recordstoviewdata = array();
            $restricteduserdata = array();

            // Get assigned group names and entities.
            $records = new totara_assign_reportbuilder_record('reportbuilder', $restriction);
            $users = new totara_assign_reportbuilder_user('reportbuilder', $restriction);

            $recassign = $records->get_current_assigned_groups();
            $usrassign = $users->get_current_assigned_groups();

            foreach ($recassign as $ra) {
                if (!isset($recordstoviewdata[$ra->grouptypename])) {
                    $recordstoviewdata[$ra->grouptypename] = array();
                }
                $recordstoviewdata[$ra->grouptypename][] = $ra->sourcefullname;
            }
            foreach ($usrassign as $ua) {
                if (!isset($restricteduserdata[$ua->grouptypename])) {
                    $restricteduserdata[$ua->grouptypename] = array();
                }
                $restricteduserdata[$ua->grouptypename][] = $ua->sourcefullname;
            }

            // Record the loaded information against the record sets.
            $restriction->recordstoview = $recordstoviewdata;
            $restriction->restrictedusers = $restricteduserdata;
        }

        return $restrictions;
    }

    /**
     * Return list of unconverted sources
     * @return array
     */
    public static function get_unsupported_sources() {
        $sources = reportbuilder::get_source_list();
        $unsupported = array();
        foreach ($sources as $key => $name) {
            $source = reportbuilder::get_source_object($key);
            if (!is_null($source->global_restrictions_supported())) {
                continue;
            }
            $unsupported[] = $name;
        }
        return $unsupported;
    }
}
