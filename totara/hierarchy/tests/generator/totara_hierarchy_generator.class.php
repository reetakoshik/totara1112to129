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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @author Rob Tyler <rob.tyler@totaralms.com>
 * @package totara_hierarchy
 * @subpackage test
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

/**
 * Hierarchy generator
 *
 * @package totara_hierarchy
 * @subpackage test
 */
class totara_hierarchy_generator extends component_generator_base {

    // Default names when created a framework.
    const DEFAULT_NAME_FRAMEWORK_COMPETENCY = 'Test Competency Framework';
    const DEFAULT_NAME_FRAMEWORK_GOAL = 'Test Goal Framework';
    const DEFAULT_NAME_FRAMEWORK_ORGANISATION = 'Test Organisation Framework';
    const DEFAULT_NAME_FRAMEWORK_POSITION = 'Test Position Framework';

    // Default names when created a hierarchy.
    const DEFAULT_NAME_HIERARCHY_COMPETENCY = 'Test Competency';
    const DEFAULT_NAME_HIERARCHY_GOAL = 'Test Goal';
    const DEFAULT_NAME_HIERARCHY_ORGANISATION = 'Test Organisation';
    const DEFAULT_NAME_HIERARCHY_POSITION = 'Test Position';

    /**
     * @var array Map of hierarchy type and prefix
     */
    private $hierarchy_type_prefix = array('competency' => 'comp',
                                           'goal'=> 'goal',
                                           'organisation' => 'org',
                                           'position' => 'pos');
    /**
     * @var array integer Number of items to be assigned.
     */
    private $hierarchy_assign_quantities = array(2, 4, 8, 16, 32, 64);

    /**
     * @var integer Keep track of how many frameworks have been created.
     */
    private $frameworkcount = array ('competency' => 0,
                                     'goal' => 0,
                                     'organisation' => 0,
                                     'position' => 0);
    /**
     * @var integer Keep track of how many hierarchies have been created.
     */
    private $hierarchycount = array ('competency' => 0,
                                     'goal' => 0,
                                     'organisation' => 0,
                                     'position' => 0);

    public function reset() {
        parent::reset();
        $this->frameworkcount = array ('competency' => 0,
            'goal' => 0,
            'organisation' => 0,
            'position' => 0);
        $this->hierarchycount = array ('competency' => 0,
            'goal' => 0,
            'organisation' => 0,
            'position' => 0);
    }

    /**
     * Redirect behat generator with appropriate prefix.
     */
    public function create_pos_frame($data) {
        return $this->create_framework('position', $data);
    }

    public function create_org_frame($data) {
        return $this->create_framework('organisation', $data);
    }

    public function create_comp_frame($data) {
        return $this->create_framework('competency', $data);
    }

    public function create_goal_frame($data) {
        return $this->create_framework('goal', $data);
    }

    /**
     * Create a framework for the given prefix.
     *
     * @param string $prefix Prefix that identifies the type of hierarchy (position, organisation, etc)
     * @param array $record
     * @return stdClass hierarchy framework
     *
     * @todo Define an array of default values then use
     *       array_merge($default_values,$record) to
     *       merge in the optional record data and reduce
     *       / remove the need for multiple statements
     *       beginning with: if (!isset($record['...
     */
    public function create_framework($prefix, $record = array()) {
        global $USER, $DB, $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');

        $record = (array) $record;
        $shortprefix = hierarchy::get_short_prefix($prefix);
        // Increment the count for the given framework.
        $i = ++$this->frameworkcount[$prefix];

        if (!isset($record['visible'])) {
            $record['visible'] = 1;
        }

        if (!isset($record['fullname'])) {
            $defaultnameconst = 'self::DEFAULT_NAME_FRAMEWORK_' . strtoupper($prefix);
            $record['fullname'] = trim(constant($defaultnameconst)) . ' ' .$i;
        }

        if (!isset($record['idnumber'])) {
            $record['idnumber'] = totara_generator_util::create_short_name($record['fullname']);
        }

        if (!isset($record['description'])) {
            $record['description'] = '<p>' . $record['fullname'] . ' description</p>';
        }

        // Get the sort order from the database.
        if (!isset($record['sortorder'])) {
            $record['sortorder'] = $DB->get_field($shortprefix.'_framework', 'MAX(sortorder) + 1', array());
        }
        // A sort order may not have been found in the database or may have an invalid 0 or NULL value.
        if (!$record['sortorder']) {
            $record['sortorder'] = 1;
        }

        if (!isset($record['hidecustomfields'])) {
            $record['hidecustomfields'] = '0';
        }

        $record['timecreated'] = time();
        $record['timemodified'] = $record['timecreated'];
        $record['usermodified'] = $USER->id;

        $framework_id = $DB->insert_record($shortprefix.'_framework', $record);
        $framework = $DB->get_record($shortprefix.'_framework', array('id' => $framework_id));

        if (!isset($record['scale'])) {
            $record['scale'] = 1;
        }

        // If this is an competency or organisation
        // framework we need to assign a scale to it.
        if ($prefix == 'competency' || $prefix == 'goal') {
            // We need to assign a scale to the
            $scale_assign = new stdClass();
            $scale_assign->scaleid = $record['scale'];
            $scale_assign->frameworkid = $framework_id;
            $scale_assign->timemodified = time();
            $scale_assign->usermodified = $USER->id;

            $scale_assign_id = $DB->insert_record($shortprefix . '_scale_assignments', $scale_assign);
        }

        return $framework;
    }

    /**
     * Assign some learners to a company goal individually
     *
     * @param int $goalid - the id of a company level goal
     * @param array(int) $userids - an array of userids to be assigned
     * @return bool - whether the users were successfully assigned or not
     */
    public function goal_assign_individuals($goalid, $userids = array()) {
        global $USER, $DB;

        $goalinfo = goal::goal_assignment_type_info(GOAL_ASSIGNMENT_INDIVIDUAL, $goalid);
        $field = $goalinfo->field;

        // Set up the default scale value for the goal.
        $sql = "SELECT s.defaultid
                FROM {goal} g
                JOIN {goal_scale_assignments} sa
                    ON g.frameworkid = sa.frameworkid
                JOIN {goal_scale} s
                    ON sa.scaleid = s.id
                WHERE g.id = :gid";
        $scale = $DB->get_record_sql($sql, array('gid' => $goalid));

        // There should always be a goal_scale, something is horribly wrong if there isn't.
        if (empty($scale)) {
            return false;
        }

        foreach ($userids as $uid) {

            $scale_default = new stdClass();
            $scale_default->goalid = $goalid;
            $scale_default->userid = $uid;
            $scale_default->scalevalueid = $scale->defaultid;

            // Add the individual assignment.
            $assignment = new stdClass();
            $assignment->assignmentid = 0;
            $assignment->$field = $uid;
            $assignment->assigntype = GOAL_ASSIGNMENT_INDIVIDUAL;
            $assignment->goalid = $goalid;
            $assignment->timemodified = time();
            $assignment->usermodified = $USER->id;
            $assignment->includechildren = 0;

            $assignment->id = $DB->insert_record($goalinfo->table, $assignment);

            $goalrecords = goal::get_goal_items(array('goalid' => $goalid, 'userid' => $uid), goal::SCOPE_COMPANY);
            if (empty($goalrecords)) {
                goal::insert_goal_item($scale_default, goal::SCOPE_COMPANY);
            }
        }
    }

    /**
     * Create a scale for the given prefix.
     *
     * We need to create the scale with a dummy default value, so we can create the values
     * with the correct scaleid, then we update the default value when we know the correct valueid.
     *
     * @param string $prefix Prefix that identifies the type of hierarchy (competency, goal)
     * @param array $scaledata - The scale item record
     * @param array $valuedata - An array of scale value items, note one value should have a default value set to 1.
     * @return stdClass scale database object
     */
    public function create_scale($prefix, $scaledata = array(), $valuedata = array()) {
        global $USER, $DB;

        // Create the scale item, filling in any missing information.
        $sdefaults = ['name' => $prefix . '_scale',
                      'description' => $prefix . '_scale',
                      'timemodified' => time(),
                      'usermodified' => $USER->id,
                      'defaultid' => 1];
        $scaledata = array_merge($scaledata, $sdefaults);
        $scaleid = $DB->insert_record("{$prefix}_scale", $scaledata);

        // Create the scale values, filling in any missing information.
        $vdefaults = ['name' => $prefix . '_scale_value',
                      'proficient' => 0,
                      'scaleid' => $scaleid,
                      'timemodified' => time(),
                      'usermodified' => $USER->id];

        // You can't have a scale without values, so if values is empty chuck in these.
        if (empty($valuedata)) {
            $valuedata = [
                1 => ['name' => 'Assigned', 'proficient' => 0, 'sortorder' => 1, 'default' => 1],
                2 => ['name' => 'Progress', 'proficient' => 0, 'sortorder' => 2, 'default' => 0],
                3 => ['name' => 'Complete', 'proficient' => 1, 'sortorder' => 3, 'default' => 0]
            ];
        }

        $value = null;
        $defaultid = null;
        foreach ($valuedata as $vdata) {
            $vdata = array_merge($vdefaults, $vdata);

            $valueid = $DB->insert_record("{$prefix}_scale_values", $vdata);

            if (!empty($vdata->default)) {
                $defaultid = $valueid;
            }
        }

        // If a default value hasn't been specified, just use the last one.
        if (empty($defaultid)) {
            $defaultid = $valueid;
        }

        // Finally update the default value and return the scale.
        $DB->set_field("{$prefix}_scale", 'defaultid', $defaultid, ['id' => $scaleid]);
        return $DB->get_record("{$prefix}_scale", array('id' => $scaleid));
    }

    /**
     * Create a personal goal for a user
     *
     * @param int $userid       The id of the user to create the goal for
     * @param array $goaldata   The data for the goal, anything not provided will use default data
     *                          NOTE: Customfields can be passed through goaldata with the key 'cf_<fieldshortname>'
     * @return stdClass         The database record for the created personal goal
     */
    public function create_personal_goal($userid, $goaldata = array()) {
        global $USER, $DB;

        $now = time();
        $defaultdata = ['name' =>  'Personal Goal',
                        'targetdate' => $now + (60 * DAYSECS),
                        'assigntype' => GOAL_ASSIGNMENT_SELF,
                        'timecreated' => $now,
                        'usercreated' => $USER->id,
                        'timemodified' => $now,
                        'usermodified' => $USER->id,
                        'deleted' => 0,
                        'typeid' => null];
        $goaldata = array_merge($defaultdata, $goaldata);
        $goaldata['userid'] = $userid;

        $goalid = $DB->insert_record('goal_personal', $goaldata);
        $goal = $DB->get_record('goal_personal', ['id' => $goalid]);

        if (!empty($goal->typeid)) {
            $fields = $DB->get_records('goal_user_info_field', ['typeid' => $goal->typeid]);

            // Initialize all the fields.
            foreach ($fields as $field) {

                $fieldname = 'cf_' . $field->shortname;
                if (!empty($goaldata[$fieldname]) || !empty($field->defaultdata)) {
                    $input = "customfield_{$field->datatype}{$field->typeid}";

                    $item = new \stdClass();
                    $item->id = $goal->id;
                    $item->typeid = $goal->typeid;
                    $item->{$input} = !empty($goaldata[$fieldname]) ? $goaldata[$fieldname] : $field->defaultdata;
                    customfield_save_data($item, 'goal_user', 'goal_user');
                }
            }
        }

        return $goal;
    }

    /**
     * Create a type for personal goals so custom fields can be added.
     *
     * @param array $typedata The data for the type, anything not provided will use default data
     * @return stdClass       The database record for the created type
     */
    public function create_personal_goal_type($typedata = array()) {
        global $USER, $DB;

        $defaultdata = ['fullname' => 'Personal Goal Type',
                        'shortname' => 'pgoaltype',
                        'idnumber' => 'pgtype123',
                        'timecreated' => time(),
                        'timemodified' => time(),
                        'usermodified' => $USER->id,
                        'audience' => 0];
        $typedata = array_merge($defaultdata, $typedata);

        $typeid = $DB->insert_record('goal_user_type', $typedata);

        return $DB->get_record('goal_user_type', ['id' => $typeid]);
    }

    /**
     * Stub function to call create_personal_goal_type_customfield() with the correct
     * variables to create a menu type custom field
     *
     * @param array $data - The basic data to create the customfield with
     * @return void
     */
    public function create_personal_goal_type_menu($data) {
        $customfield = $data;
        $customfield['param1'] = "1234"."\n"."2345"."\n"."3456"."\n"."4567";
        $this->create_personal_goal_type_customfield('menu', $customfield);
    }

    /**
     * Stub function to call create_personal_goal_type_customfield() with the correct
     * variables to create a text type custom field
     *
     * @param array $data - The basic data to create the customfield with
     * @return void
     */
    public function create_personal_goal_type_text($data) {
        $customfield = $data;
        $customfield['param1'] = 30;
        $customfield['param2'] = 2048;
        $this->create_personal_goal_type_customfield('text', $customfield);
    }

    /**
     * Stub function to call create_personal_goal_type_customfield() with the correct
     * variables to create a datetime type custom field
     *
     * @param array $data - The basic data to create the customfield with
     * @return void
     */
    public function create_personal_goal_type_datetime($data) {
        $customfield = $data;
        $customfield['param1'] = date("Y")-1; // Start year.
        $customfield['param2'] = date("Y")+5; // End year.
        $this->create_personal_goal_type_customfield('datetime', $customfield);
    }

    /**
     * Stub function to call create_personal_goal_type_customfield() with the correct
     * variables to create a checkbox type custom field
     *
     * @param array $data - The basic data to create the customfield with
     * @return void
     */
    public function create_personal_goal_type_checkbox($data) {
        $this->create_personal_goal_type_customfield('checkbox', $data);
    }

    /**
     * Stub function to call create_personal_goal_type_customfield() with the correct
     * variables to create a generic menu type custom field
     *
     * @param array $data - The basic data to create the customfield with
     * @return void
     */
    public function create_personal_goal_type_generic_menu($data) {
        $customfield = $data;
        $customfield['param1'] = str_replace(',', "\n", $data['value']);
        $customfield['value'] = '';
        $this->create_personal_goal_type_customfield('menu', $customfield);
    }

    /**
     * Create a custom field for a personal goal type
     * Note: While this can be called directly, it's easier to go through the
     *       setup functions above create_personal_goal_type_<cftype>()
     *
     * @param string $fieldtype  The type of customfield
     * @param array $customfield The data for the customfield, anything not provided will use default data
     */
    private function create_personal_goal_type_customfield($fieldtype, $customfield) {
        global $CFG, $DB;

        if (!$typeid = $DB->get_field('goal_user_type', 'id', array('idnumber' => $customfield['typeidnumber']))) {
            throw new coding_exception('Unknown personal_goal type idnumber '.$customfield['typeidnumber'].' in personal_goal definition');
        }

        $data = new \stdClass();
        $data->id = 0;
        $data->shortname = $fieldtype . $typeid;
        $data->typeid = $typeid;
        $data->datatype = $fieldtype;
        $data->description_editor = array('text' => '', 'format' => '1', 'itemid' => time());
        $data->hidden   = 0;
        $data->locked   = 0;
        $data->required = 0;
        $data->forceunique = 0;
        $data->defaultdata = $customfield['value'];
        if (isset($customfield['param1'])) {
            $data->param1 = $customfield['param1'];
        }
        if (isset($customfield['param2'])) {
            $data->param2 = $customfield['param2'];
        }
        if (isset($customfield['param3'])) {
            $data->param3 = $customfield['param3'];
        }
        if (isset($customfield['param4'])) {
            $data->param4 = $customfield['param4'];
        }
        if (isset($customfield['param5'])) {
            $data->param5 = $customfield['param5'];
        }
        $data->fullname  = 'Personal Goal ' . $fieldtype;

        require_once($CFG->dirroot . '/totara/customfield/field/' . $fieldtype . '/define.class.php');
        $customfieldclass = 'customfield_define_' . $fieldtype;
        $field = new $customfieldclass();
        $field->define_save($data, 'goal_user');
    }

    /**
     * Update a users scale value for an existing company goal assignment, and create
     * an associated history record for the change.
     *
     * @param int $userid
     * @param int $goalid
     * @param int $valueid - The id of the goal scale value record
     * @return boolean
     */
    public function update_company_goal_user_scale_value($userid, $goalid, $valueid) {
        global $DB, $USER;

        if (!$todb = $DB->get_record('goal_record', ['userid' => $userid, 'goalid' => $goalid])) {
            // You can't update something that isn't there.
            return false;
        }

        // Update the goal record with the new valueid.
        $todb->scalevalueid = $valueid;
        $DB->update_record('goal_record', $todb);

        // Create a history record for the change.
        $history = new \stdClass();
        $history->scope = \goal::SCOPE_COMPANY;
        $history->itemid = $todb->id;
        $history->scalevalueid = $valueid;
        $history->timemodified = time();
        $history->usermodified = $USER->id;

        return $DB->insert_record('goal_item_history', $history);

    }

    /**
     * Redirect behat generator with appropriate prefix.
     */
    public function create_pos($data) {
        return $this->create_hierarchy($data['frameworkid'], 'position', $data);
    }

    public function create_org($data) {
        return $this->create_hierarchy($data['frameworkid'], 'organisation', $data);
    }

    public function create_comp($data) {
        return $this->create_hierarchy($data['frameworkid'], 'competency', $data);
    }

    public function create_goal($data) {
        return $this->create_hierarchy($data['frameworkid'], 'goal', $data);
    }

    /**
     * Create hierarchy type.
     */
    public function create_pos_type($data = array()) {
        return $this->create_hierarchy_type('position', $data);
    }

    public function create_org_type($data = array()) {
        return $this->create_hierarchy_type('organisation', $data);
    }

    public function create_comp_type($data = array()) {
        return $this->create_hierarchy_type('competency', $data);
    }

    public function create_goal_type($data = array()) {
        return $this->create_hierarchy_type('goal', $data);
    }

    public function create_hierarchy_type($prefix, $data = array()) {
        global $USER, $DB;

        $shortprefix = $this->hierarchy_type_prefix[$prefix];

        $type = new \stdClass();
        $type->idnumber = (isset($data['idnumber']) ? $data['idnumber'] : $prefix.$USER->id);
        $type->fullname = (isset($data['fullname']) ? $data['fullname'] : 'Hierarchy '.ucfirst($prefix).' type');
        $type->description  = '';
        $type->timemodified = time();
        $type->usermodified = $USER->id;
        $type->timecreated  = time();
        $id = $DB->insert_record($shortprefix.'_type', $type);
        if (!$typeid = $DB->get_field($shortprefix.'_type', 'id', array('idnumber' => $type->idnumber))) {
            throw new coding_exception('Unknown hierarchy type idnumber '.$type->idnumber.' in hierarchy definition');
        }
        return $id;
    }

    public function create_hierarchy_type_menu($data) {
        $customfield = $data;
        $customfield['field']  = 'menu';
        $customfield['param1'] = "1234"."\n"."2345"."\n"."3456"."\n"."4567";
        $this->create_hierarchy_type_customfield($customfield);
    }

    public function create_hierarchy_type_text($data) {
        $customfield = $data;
        $customfield['field']  = 'text';
        $customfield['param1'] = 30;
        $customfield['param2'] = 2048;
        $this->create_hierarchy_type_customfield($customfield);
    }

    public function create_hierarchy_type_datetime($data) {
        $customfield = $data;
        $customfield['field']  = 'datetime';
        $customfield['param1'] = date("Y")-1; // Start year.
        $customfield['param2'] = date("Y")+5; // End year.
        //$customfield['param3'] = 1; // Include time. 0 for exclude.
        $this->create_hierarchy_type_customfield($customfield);
    }

    public function create_hierarchy_type_checkbox($data) {
        $customfield = $data;
        $customfield['field']  = 'checkbox';
        $this->create_hierarchy_type_customfield($customfield);
    }

    public function create_hierarchy_type_generic_menu($data) {
        $customfield = $data;
        $customfield['field']  = 'menu';
        $customfield['param1'] = str_replace(',', "\n", $data['value']);
        $customfield['value'] = '';
        $this->create_hierarchy_type_customfield($customfield);
    }

    private function create_hierarchy_type_customfield($customfield) {
        global $CFG, $DB;

        $datatype = $customfield['field'];
        $shortprefix = $this->hierarchy_type_prefix[$customfield['hierarchy']];
        $tableprefix = $shortprefix.'_type';
        if (!$typeid = $DB->get_field($tableprefix, 'id', array('idnumber' => $customfield['typeidnumber']))) {
            throw new coding_exception('Unknown hierarchy type idnumber '.$customfield['typeidnumber'].' in hierarchy definition');
        }

        $data = new \stdClass();
        $data->id = 0;
        $data->shortname = $datatype . $typeid;
        $data->typeid = $typeid;
        $data->datatype = $datatype;
        $data->description_editor = array('text' => '', 'format' => '1', 'itemid' => time());
        $data->hidden   = 0;
        $data->locked   = 0;
        $data->required = 0;
        $data->forceunique = 0;
        $data->defaultdata = $customfield['value'];
        if (isset($customfield['param1'])) {
            $data->param1 = $customfield['param1'];
        }
        if (isset($customfield['param2'])) {
            $data->param2 = $customfield['param2'];
        }
        if (isset($customfield['param3'])) {
            $data->param3 = $customfield['param3'];
        }
        if (isset($customfield['param4'])) {
            $data->param4 = $customfield['param4'];
        }
        if (isset($customfield['param5'])) {
            $data->param5 = $customfield['param5'];
        }
        $data->fullname  = ucfirst($customfield['hierarchy']).' type '.$datatype;

        require_once($CFG->dirroot.'/totara/customfield/field/'.$datatype.'/define.class.php');
        $customfieldclass = 'customfield_define_'.$datatype;
        $field = new $customfieldclass();
        $field->define_save($data, $tableprefix);
    }

    /**
     * Assign the requested hierarchy type to hierarchy
     *
     * @param array $data of prefix, hierarchy type custom field, hierarchy type id number, hierarchy id number, the value of custom field
     * @throws coding_exception
     */
    public function create_hierarchy_type_assign($data) {
        global $DB;

        // Pre-process any fields that require transforming.
        $shortprefix = hierarchy::get_short_prefix($data['hierarchy']);
        if (!$typeid = $DB->get_field($shortprefix.'_type', 'id', array('idnumber' => $data['typeidnumber']))) {
            throw new coding_exception('Unknown hierarchy type idnumber '.$data['typeidnumber'].' in hierarchy definition');
        }
        $DB->set_field($shortprefix, 'typeid', $typeid, array('idnumber' => $data['idnumber']));
        if (!$hierarchyid = $DB->get_field($shortprefix, 'id', array('idnumber' => $data['idnumber']))) {
            throw new coding_exception('Unknown hierarchy idnumber '.$data['idnumber'].' in hierarchy definition');
        }
        $field = $data['field'];
        $input = "customfield_{$field}{$typeid}";

        $item = new \stdClass();
        $item->id = $hierarchyid;
        $item->typeid = $typeid;
        $item->{$input} = $data['value'];
        customfield_save_data($item, $data['hierarchy'], $shortprefix.'_type');
    }

    /**
     * Create a hierarchy based on the shortprefix and assign it to a framework.
     *
     * @param $frameworkid
     * @param $prefix
     * @param null $record
     * @return stdClass hierarchy item
     *
     * @todo Define an array of default values then use
     *       array_merge($default_values,$record) to
     *       merge in the optional record data and reduce
     *       / remove the need for multiple statements
     *       beginning with: if (!isset($record['...
     */
    public function create_hierarchy($frameworkid, $prefix, $record = null) {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');

        if (is_string($record)) {
            throw new coding_exception('$record parameter must be array or object');
        }

        $record = (array) $record;
        // Increment the count for the given hierarchy.
        $i = ++$this->hierarchycount[$prefix];

        if (!isset($record['fullname'])) {
            $defaultnameconst = 'self::DEFAULT_NAME_HIERARCHY_' . strtoupper($prefix);
            $record['fullname'] = trim(constant($defaultnameconst)) . ' ' .$i;
        }

        if (!isset($record['idnumber'])) {
            $record['idnumber'] = totara_generator_util::create_short_name($record['fullname']);
        }

        if (!isset($record['description'])) {
            $record['description'] = '<p>' . $record['fullname'] . ' description</p>';
        }

        if (!isset($record['visible'])) {
            $record['visible'] = 1;
        }

        if (!isset($record['hidecustomfields'])) {
            $record['hidecustomfields'] = 0;
        }

        if (!isset($record['parentid'])) {
            $record['parentid'] = 0;
        }

        if (!isset($record['aggregationmethod'])) {
            // Get a default value for the agreggation method.
            // This variable is used to build the select menu
            // in the hierarchy form.
            global $COMP_AGGREGATION;
            $record['aggregationmethod'] = $COMP_AGGREGATION['ALL'];
        }

        if (!isset($record['proficiencyexpected'])) {
            // The default value for proficiencyexpected
            // is hard coded in the hierarchy form.
            $record['proficiencyexpected'] = 1;
        }

        $record['frameworkid'] = $frameworkid;
        $record['timecreated'] = time();
        $record['timemodified'] = $record['timecreated'];
        $record['usermodified'] = $USER->id;

        $record = (object) $record;
        $hierarchy = hierarchy::load_hierarchy($prefix);
        $itemnew = $hierarchy->process_additional_item_form_fields($record);
        $item = $hierarchy->add_hierarchy_item($itemnew, $itemnew->parentid, $itemnew->frameworkid, false, true, false);

        return $item;
    }

    /**
     * Create some hierarchies.
     *
     * @param int $frameworkid The framework to assign the hierachies to.
     * @param string $prefix The type of hierarchy to create.
     * @param int $quantity The number of hierarchies to create.
     * @param string $name The base name of the hierarchy.
     * @param int $randomise_percent Randomly determine (by percentage) if the hierarchy is created.
     * @return array of hierarchies
     */
    public function create_hierarchies($frameworkid, $prefix, $quantity, $name = '', $randomise_percent = 0, $hierarchy_extra_data = array() ) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');

        // Create the objective name we want to use with by ge3tting
        // the number off any previous matching records we created.
        if (!$name) {
            $name = 'self::DEFAULT_NAME_HIERARCHY_' . strtoupper($prefix);
        }
        $shortprefix = hierarchy::get_short_prefix($prefix);
        $number = totara_generator_util::get_next_record_number($shortprefix, 'fullname', $name);

        $hierarchy_data = array ();
        $hierarchy_ids = array ();
        // Create the quantity of hierarchies we need.
        for ($i = 1; $i <= $quantity; $i++) {
            // Create a hierarchy, or apply randomisation and create if required.
            if ($randomise_percent == 0 || ($randomise_percent && get_random_act($randomise_percent))) {
                $hierarchy_data['fullname'] = $name . ' ' . $number++;
                $create_data = array_merge ($hierarchy_data, $hierarchy_extra_data);
                $hierarchy = $this->create_hierarchy($frameworkid, $prefix, $create_data);
                $hierarchy_ids[$i] = $hierarchy->id;
            }
        }

        return $hierarchy_ids;
    }

    /**
     * Assign linked course to a competency.
     *
     * @param stdClass $competency Competency to add linked course to
     * @param stdClass $course Course to add
     *
     * @return int
     */
    public function assign_linked_course_to_competency($competency, $course) {
        global $CFG, $DB;

        $evidence = competency_evidence_type::factory(array('itemtype' => 'coursecompletion'));

        $evidence->iteminstance = $course->id;
        $newevidenceid = $evidence->add($competency);

        return $newevidenceid;
    }

    /**
     * Remove linked course from a competency.
     *
     * @param stdClass $competency Competency to remove linked course from
     * @param stdClass $course Course to remove
     *
     * @return true
     */
    public function remove_linked_course_from_competency($competency, $evidenceid) {
        /** @var competency_evidence_type $evidence */
        $evidence = competency_evidence_type::factory($evidenceid);
        $evidence->delete($competency);

        return true;
    }


    /**
     * Assigns random courses to competencies.
     *
     * @param $size int number of items to process.
     */
    public function assign_competency($size) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');

        // Do not assign the site course!
        $site = get_site();
        // Track the ones we have already assigned.
        $assignedhierarchies = array();
        // Get $size competencies.
        for ($x=0; $x < $size; $x++) {
            // Find one we have not already used.
            $uniquehierarchy = false;
            while (!$uniquehierarchy) {
                $hierarchyid = totara_generator_util::get_random_record_id($this->hierarchy_type_prefix['competency']);
                if (!in_array($hierarchyid, $assignedhierarchies)) {
                    $assignedhierarchies[] = $hierarchyid;
                    $uniquehierarchy = true;
                }
            }
            // Load competency
            if ($competency = $DB->get_record('comp', array('id' => $hierarchyid))) {
                // Assign random number of courses up to $size.
                $coursesassigned = 0;
                $coursestoassign = mt_rand(0, $size);
                while ($coursesassigned < $coursestoassign) {
                    // Set up the completion evidence type.
                    $evidence = competency_evidence_type::factory(array('itemtype' => 'coursecompletion'));
                    $evidence->iteminstance = totara_generator_util::get_random_record_id('course');
                    if ($evidence->iteminstance != $site->id && !$DB->record_exists('comp_criteria', array('competencyid' => $hierarchyid, 'itemtype' => 'coursecompletion', 'iteminstance' => $evidence->iteminstance))) {
                        // Randomise mandatory or optional.
                        $evidence->linktype = mt_rand(0,1);
                        // Assign courses to competency.
                        $newevidenceid = $evidence->add($competency);
                        $coursesassigned++;
                    }
                }
            }
        }
        echo "\n" . get_string('progress_assigncoursecompetencies', 'totara_generator');
    }

    /**
     * Assigns random competencies and goals to organisations.
     *
     * @param $size int number of items to process.
     */
    public function assign_organisation($size) {
        $this->assign_competency_to_hierarchy('organisation', $size);
    }

    /**
     * Assigns random competencies and goals to positions
     *
     * @param $size int number of items to process.
     */
    public function assign_position($size) {
        $this->assign_competency_to_hierarchy('position', $size);
    }

    /**
     * Assigns random user groups to goals.
     *
     * @param $size int number of items to process.
     */
    public function assign_goal($size) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/assign/lib.php');
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

        // Track the ones we have already assigned.
        $assignedhierarchies = array();
        // Get all goals to assign at least one group.
        for ($x=0; $x < $size; $x++) {
            // Find one we have not already used.
            $uniquehierarchy = false;
            while (!$uniquehierarchy) {
                $hierarchyid = totara_generator_util::get_random_record_id($this->hierarchy_type_prefix['goal']);
                if (!in_array($hierarchyid, $assignedhierarchies)) {
                    $assignedhierarchies[] = $hierarchyid;
                    $uniquehierarchy = true;
                }
            }
            // Get the base goal item.
            $item = $DB->get_record('goal', array('id' => $hierarchyid));
            $baseclassname = "totara_assign_goal";
            $baseclass = new $baseclassname('goal', $item);
            // Assign random pos, org or cohort groups to this goal.
            $grouptypes = array('pos', 'org', 'cohort');
            $groupstoassign = mt_rand(1,3);
            for ($i=0; $i < $groupstoassign; $i++) {
                $grouptype = $grouptypes[mt_rand(0,2)];
                $grouptypeobj = $baseclass->load_grouptype($grouptype);
                // Get a random record from the groups.
                $groupid = totara_generator_util::get_random_record_id($grouptype);
                $grouptypeobj->validate_item_selector($groupid);
                $urlparams = array('module' => 'group',
                        'grouptype' => $grouptype,
                        'itemid' => $hierarchyid,
                        'add' => 1,
                        'listofvalues' => array($groupid),
                        'includechildren' => 0
                );
                $grouptypeobj->handle_item_selector($urlparams);
            }
        }
        echo "\n" . get_string('progress_assigngoalusergroups', 'totara_generator');
    }

    /**
     * Assigns a random number of competencies from 1 to $size to each item in the hierarchy type
     *
     * @param $hierarchytype string usually 'position' or 'organisation'
     * @param $size int number of items to process.
     */
    private function assign_competency_to_hierarchy($hierarchytype, $size) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/' . $hierarchytype . '/lib.php');

        $time = time();
        // Track the ones we have already assigned.
        $assignedhierarchies = array();
        // Assign something to every hierarchy item.
        for ($x=0; $x < $size; $x++) {
            // Find one we have not already used.
            $uniquehierarchy = false;
            while (!$uniquehierarchy) {
                $hierarchyid = totara_generator_util::get_random_record_id($this->hierarchy_type_prefix[$hierarchytype]);
                if (!in_array($hierarchyid, $assignedhierarchies)) {
                    $assignedhierarchies[] = $hierarchyid;
                    $uniquehierarchy = true;
                }
            }
            // Setup hierarchy objects
            $competencies = new competency();
            $hierarchies = new $hierarchytype();
            // Load position
            if (!$hierarchy = $hierarchies->get_item($hierarchyid)) {
                print_error("{$hierarchytype}notfound", 'totara_hierarchy');
            }
            // Currently assigned competencies
            if (!$currentlyassigned = $hierarchies->get_assigned_competencies($hierarchyid)) {
                $currentlyassigned = array();
            }
            $addcompetencies = 0;
            $add = array();
            $competencytoassign = mt_rand(1, $size);
            while ($addcompetencies < $competencytoassign) {
                $newcomp = totara_generator_util::get_random_record_id ($this->hierarchy_type_prefix['competency']);
                if (!in_array($newcomp, $currentlyassigned)) {
                    $add[] = $newcomp;
                    // Add it to currently assigned too - on small sites it may try to add the same competency twice.
                    $currentlyassigned[] = $newcomp;
                    $addcompetencies++;
                }
            }
            foreach ($add as $addition) {
                // Add relationship
                $related = $competencies->get_item($addition);
                $relationship = new stdClass();
                $field = "{$hierarchytype}id";
                $relationship->$field = $hierarchy->id;
                $relationship->competencyid = $related->id;
                $relationship->timecreated = $time;
                $relationship->usermodified = $USER->id;
                $relationship->linktype = mt_rand(0,1);
                $relationship->id = $DB->insert_record($this->hierarchy_type_prefix[$hierarchytype] . '_competencies', $relationship);
            }
        }
        echo "\n" . get_string('progress_assigncompetenciestohierarchy', 'totara_generator', get_string($hierarchytype, 'totara_hierarchy'));
    }
}
