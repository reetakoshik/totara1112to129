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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Rob Tyler <rob.tyler@totaralms.com>
 * @package totara_plan
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Plan generator.
 *
 * @package totara_plan
 */
class totara_plan_generator extends component_generator_base {

    // Default name when created a learning plan.
    const DEFAULT_NAME = 'Test Learning Plan';
    const DEFAULT_NAME_OBJECTIVE = 'Test Objective';

    /**
     * @var integer Keep track of how many learning plans have been created.
     */
    private $learningplancount = 0;

    /**
     * @var integer Keep track of how many learning plan objectives have been created.
     */
    private $learningplanobjectivecount = 0;

    /** @var int  */
    private $evidencetypecount = 0;

    /** @var int  */
    private $evidencecount = 0;
    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->learningplancount = 0;
        $this->learningplanobjectivecount = 0;
        $this->evidencetypecount = 0;
        $this->evidencecount = 0;
    }

    /**
     * Create a learning plan.
     *
     * @param  array    $record Optional record data.
     * @return stdClass Created learning plan instance.
     *
     * @todo Define an array of default values then use
     *       array_merge($default_values, $record) to
     *       merge in the optional record data and reduce
     *       / remove the need for multiple statements
     *       beginning with: if (!isset($record['...
     */
    public function create_learning_plan($record=null) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $record = (array) $record;
        // Increment the count of learning plans.
        $i = ++$this->learningplancount;

        if (!isset($record['templateid'])) {
            $record['templateid'] = 1;
        }

        // Allow Behat tests to reference the user name.
        if (isset($record['user'])) {
            $record['userid'] = $DB->get_field('user', 'id', array('username' => $record['user']), MUST_EXIST);
            unset ($record['user']);
        }

        if (!isset($record['userid'])) {
            $record['userid'] = $USER->id;
        }

        if (!isset($record['name'])) {
            $record['name'] = trim(self::DEFAULT_NAME) . ' ' .$i;
        }

        if (!isset($record['description'])) {
            $record['description'] = '<p>' . $record['name'] . ' description</p>';
        }

        if (!isset($record['startdate'])) {
            $record['startdate'] = strtotime(date('Y') . '-01-01');
        }

        if (!isset($record['enddate'])) {
            $record['enddate'] = strtotime(date('Y') . '-12-31');
        }

        if (!isset($record['status'])) {
            $record['status'] = 0;
        }

        if (!isset($record['createdby'])) {
            $record['createdby'] = PLAN_CREATE_METHOD_MANUAL;
        }

        // Create a record for the given id or one
        // with an id that's next in the sequence.
        if (isset($record['id'])) {
            $DB->import_record('dp_plan', $record);
            $DB->get_manager()->reset_sequence('dp_plan');
            $id = $record['id'];
        } else {
            $id = $DB->insert_record('dp_plan', $record);
        }

        // Make sure the plan status is set correctly.
        $plan = new development_plan($id);
        $plan->set_status(DP_PLAN_STATUS_UNAPPROVED, DP_PLAN_REASON_CREATE);

        return $DB->get_record('dp_plan', array('id' => $id));
    }

    /**
     * Add a competency to a learning plan.
     *
     * @param int $planid identifying id of a plan object
     * @param int $competencyid identifying id of a competency object
     * @return bool success
     */
    public function add_learning_plan_competency($planid, $competencyid) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        if (!has_capability('totara/plan:manageanyplan', context_system::instance())) {
            debugging('add_learning_plan_competency generator needs totara/plan:manageanyplan capability!');
            return false;
        }

        $plan = new development_plan($planid);
        $plan->viewas = $USER->id;
        $plan->load_roles();
        $plan->load_components();
        $plan->initialize_settings();
        $plan->role = $plan->get_user_role($plan->viewas);
        $componentname = 'competency';
        $component = $plan->get_component($componentname);

        // Get the currently assigned competency IDs.
        $currentcompetencies = $DB->get_records('dp_plan_competency_assign', array('planid' => $planid), '', 'competencyid');
        $currentcompetencies = array_keys($currentcompetencies);

        $comps_added = array($competencyid);

        // Get linked courses for newly added competencies.
        $evidence = $component->get_course_evidence_items($comps_added);

        // We need to give the full list of assigned competencies otherwise existing
        // competencies will be removed from the plan.
        $comps_added = array_merge($currentcompetencies, $comps_added);

        // Add them all.
        $comp_mandatory = array();
        foreach ($evidence as $compid => $linkedcourses) {
            foreach ($linkedcourses as $linkedcourse) {
                if (!isset($comp_mandatory[$competencyid])) {
                    $comp_mandatory[$competencyid] = array();
                }
                $comp_mandatory[$competencyid][] = $linkedcourse->courseid;
            }
        }
        $component->update_assigned_items($comps_added);
        foreach ($comp_mandatory as $compid => $courses) {
            foreach ($courses as $key => $course) {
                if (!$plan->get_component('course')->is_item_assigned($course)) {
                    $plan->get_component('course')->assign_new_item($course, true, false);
                }
                // Now we need to grab the assignment ID.
                $assignmentid = $DB->get_field('dp_plan_course_assign', 'id', array('planid' => $plan->id, 'courseid' => $course), MUST_EXIST);
                // Get the competency assignment ID from the competency.
                $compassignid = $DB->get_field('dp_plan_competency_assign', 'id', array('competencyid' => $competencyid, 'planid' => $plan->id), MUST_EXIST);
                $mandatory = 'course';
                // Create relation.
                $plan->add_component_relation('competency', $compassignid, 'course', $assignmentid, $mandatory);
            }
        }

        return true;
    }


    /**
     * Add a course to a learning plan.
     *
     * @param int $planid
     * @param int $courseid
     */
    public function add_learning_plan_course($planid, $courseid) {
        global $DB, $USER;

        $plan = new development_plan($planid);
        $plan->viewas = $USER->id;
        $plan->load_roles();
        $plan->load_components();
        $plan->initialize_settings();
        $plan->role = $plan->get_user_role($plan->viewas);

        $componentname = 'course';
        $component = $plan->get_component($componentname);

        // Get the currently assigned course IDs.
        $currentcourses = $DB->get_records('dp_plan_course_assign', array('planid' => $planid), '', 'courseid');
        $currentcourses = array_keys($currentcourses);

        $coursesadded = array($courseid);

        $coursesadded = array_merge($currentcourses, $coursesadded);

        // Get linked competencies for the newly added course.
        //$comps = $component->get_

        $component->update_assigned_items($coursesadded);

        return true;
    }


    /**
     * Add a program to a learning plan.
     *
     * @param int $planid
     * @param int $programid
     */
    public function add_learning_plan_program($planid, $programid) {
        global $DB, $USER;

        $plan = new development_plan($planid);
        $plan->viewas = $USER->id;
        $plan->load_roles();
        $plan->load_components();
        $plan->initialize_settings();
        $plan->role = $plan->get_user_role($plan->viewas);

        $component = $plan->get_component('program');

        // Get the currently assigned program IDs.
        $currentprograms = $DB->get_records('dp_plan_program_assign', array('planid' => $planid), '', 'programid');
        $currentprograms = array_keys($currentprograms);

        $programsadded = array($programid);
        $programsadded = array_merge($currentprograms, $programsadded);

        $component->update_assigned_items($programsadded);

        return true;
    }


    /**
     * Create an objective for a learning plan.
     *
     * @param  int $planid
     * @param  int $viewas
     * @param  array    $record Optional record data.
     * @return stdClass Created learning plan objective instance.
     */
    public function create_learning_plan_objective($planid, $viewas, $record=null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $record = (array) $record;
        // Increment the count of learning plans.
        $i = ++$this->learningplanobjectivecount;

        if (!isset($record['fullname'])) {
            $record['fullname'] = self::DEFAULT_NAME_OBJECTIVE . ' ' . $i;
        }

        if (!isset($record['description'])) {
            $record['description'] = '<p>' . $record['fullname']. ' description</p>';
        }

        if (!isset($record['priority'])) {
            // Get the default priority value from the basic priority scale created on installation.
            $record['priority'] = $DB->get_field('dp_priority_scale', 'defaultid', array('id' => 1));
        }

        if (!isset($record['scalevalueid'])) {
            // Get the default priority value from the basic priority scale created on installation.
            $record['scalevalueid'] = $DB->get_field('dp_objective_scale', 'defaultid', array('id' => 1));
        }

        $plan = new development_plan($planid, $viewas);
        $component = $plan->get_component('objective');
        $id = $component->create_objective(
                $record['fullname'],
                $record['description'],
                $record['priority'],
                NULL, // Field duedate not currently part of objective form.
                $record['scalevalueid']
        );

        return $DB->get_record('dp_plan_objective', array('id' => $id));
    }


    /**
     * Create learning plan objective for Behat.
     *
     * @param  array    $record Optional record data.
     * @return stdClass Created learning plan objective instance.
     */
    public function create_learning_plan_objective_for_behat($record = null) {
        global $DB, $USER;

        // Look up the learning plan id from the user and plan names.
        $userid = $DB->get_field('user', 'id', array('username' => $record['user']), MUST_EXIST);
        $planid = $DB->get_field('dp_plan', 'id', array('userid' => $userid, 'name' => $record['plan']), MUST_EXIST);

        // Set the data up correctly for objective creation and remove the data we no longer need.
        $record['fullname'] = $record['name'];
        unset($record['user']);
        unset($record['plan']);
        unset($record['name']);

        return $this->create_learning_plan_objective ($planid, $USER->id, $record);
    }


    /**
     * Create evidence type
     * @param array $record
     * @return stdClass
     */
    public function create_evidence_type($record = null) {
        global $DB, $USER;

        $record = (array)$record;

        $i = ++$this->evidencetypecount;

        if (!isset($record['name'])) {
            $record['name'] = 'Evidence type ' . $i;
        }

        if (!isset($record['description'])) {
            $record['description'] = 'Evidence description ' . $i;
        }

        if (!isset($record['timemodified'])) {
            $record['timemodified'] = time();
        }

        if (!isset($record['usermodified'])) {
            $record['usermodified'] = $USER->id;
        }

        if (!isset($record['sortorder'])) {
            $record['sortorder'] = $i;
        }

        $id = $DB->insert_record('dp_evidence_type', $record);

        return $DB->get_record('dp_evidence_type', array('id' => $id));
    }

    /**
     * Create evidence
     * @param array $record
     * @return stdClass
     */
    public function create_evidence($record = null) {
        global $DB, $USER;

        $record = (array)$record;

        $i = ++$this->evidencecount;

        if (empty($record['userid'])) {
            throw new coding_exception('missing userid');
        }

        if (!isset($record['evidencetypeid'])) {
            $record['evidencetypeid'] = 0;
        }

        if (!isset($record['name'])) {
            $record['name'] = 'Evidence ' . $i;
        }

        if (!isset($record['timecreated'])) {
            $record['timecreated'] = time();
        }

        if (!isset($record['timemodified'])) {
            $record['timemodified'] = time();
        }

        if (!isset($record['usermodified'])) {
            $record['usermodified'] = $USER->id;
        }

        if (!isset($record['readonly'])) {
            $record['readonly'] = 0;
        }

        $id = $DB->insert_record('dp_plan_evidence', $record);

        return $DB->get_record('dp_plan_evidence', array('id' => $id));
    }
}
