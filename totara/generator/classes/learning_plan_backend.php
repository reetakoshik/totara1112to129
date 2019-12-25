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
 * @package totara_generator
 */

defined('MOODLE_INTERNAL') || die();

class totara_generator_learning_plan_backend extends tool_generator_backend {

    /**
     * @var string Used when ceating a learning plan.
     */
    private $name;

    /**
     * @var integer The size of data to generate.
     */
    protected $size;

    /**
     * @var testing_data_generator Moodle original data generator.
     */
    protected $generator;

    /**
     * @var totara_plan_generator Learning plan data generator.
     */
    protected $learning_plan_generator;

    /*
     * @var array Percentage chance of an action being taken.
     *            Used to randomise data generation.
     */
    private $component_chance_percentage = array('course' => 90,
                                                    'competency'=> 75,
                                                    'objective' => 50,
                                                    'program' => 25);

    /**
     * @var array integer Number of times a component is created.
     *                    based on 'maketest' size.
     */
    private $component_size_quantities = array(2, 4, 8, 16, 32, 64);

    /**
     * Constructs object ready to create learning plans and data.
     *
     * @param int $size Size as numeric index
     * @param string $name Course shortname
     * @param bool $fixeddataset To use fixed or random data
     * @param int|bool $filesizelimit The max number of bytes for a generated file
     * @param bool $progress True if progress information should be displayed
     */
    public function __construct($size, $name = NULL, $fixeddataset = false, $filesizelimit = false, $progress = true) {

        // Set parameters.
        $this->size = $size;
        $this->name = $name;

        parent::__construct($size, $fixeddataset, $filesizelimit, $progress);
    }

    /**
     * Runs the 'make' process for learning plans.
     *
     * @todo Make sure users exist and create them if not so this 'maketest'
     *       file can be used independantly of maketestsite.php (which creates users).
     * @todo Investigate if transactions should be used and implement required changes.
     */
    public function make() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');

        raise_memory_limit(MEMORY_EXTRA);

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::start_tag('ul');
        }

        $entirestart = microtime(true);

        $transaction = $DB->start_delegated_transaction();

        // Get generator.
        $this->generator = phpunit_util::get_data_generator();

        // Set custom data generators.
        $this->set_custom_generators();

        $learning_plan = $this->create_learning_plan();
        // Assign courses, competencies, programs and create objectives.
        $this->create_totara_objects($learning_plan);

        // Log total time.
        $this->log('completedlearningplan', round(microtime(true) - $entirestart, 1));

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::end_tag('ul');
        }

        // Commit transaction and finish.
        $transaction->allow_commit();
    }

    /**
     * Set custom data generators
     */
    protected function set_custom_generators() {
        $this->learning_plan_generator = $this->generator->get_plugin_generator('totara_plan');
    }

    /**
     * Create a learning plan,
     *
     * @return object The learning plan created.
     */
    protected function create_learning_plan() {
        // If we've received a name over thc command line then
        // use that, otherwise use the plan generator default.
        if ($this->name) {
            $default_name = $this->name;
        } else {
            $default_name = totara_plan_generator::DEFAULT_NAME;
        }

        // Create the name we want to use.
        $default_name = trim($default_name) . ' ' . totara_generator_util::get_size_name($this->size);
        $default_name = $default_name . ' ' . totara_generator_util::get_next_record_number('dp_plan', 'name', $default_name);
        // Output the name to the log.
        $this->log('creatinglearningplan', $default_name);
        // Create the learning plan.
        $record = array();
        $record['name'] = $default_name;
        // Assign the learning plan to a random user, but exclude the guest and admin users.
        $record['userid'] = totara_generator_util::get_random_record_id('user', false, array(1, 2));
        $result = $this->learning_plan_generator->create_learning_plan($record);

        return $result;
    }

    /**
     * Create Totara objects, assign courses, competencies, programs and create objectives.
     *
     * @todo Competencies need to be assigned need completing.
     */
    protected function create_totara_objects($learning_plan) {
        global $DB;
        // Get our learning plan so we can add stuff to it.
        $plan = new development_plan($learning_plan->id);

        $numitems = mt_rand(0, $this->component_size_quantities[$this->size]);
        $this->log('planassigncourses', $numitems);
        // Assign some courses to the learning plan.
        for ($i = 0; $i < $numitems; $i++) {
            // Get a random course id and assign it to the learning plan.
            $course_id = totara_generator_util::get_random_record_id('course',false,array(1));
            if ($course_id) {
                if (totara_generator_util::get_random_act($this->component_chance_percentage['course'])) {
                    $component = $plan->get_component('course');
                    $component->update_assigned_items(array($course_id));
                }
            } else {
                $this->log('assignfail', 'course');
                break;
            }
        }

        $numitems = mt_rand(0, $this->component_size_quantities[$this->size]);
        $this->log('planassigncompetencies', $numitems);
        // Assign some competencies to the learning plan.
        for ($i = 0; $i < $numitems; $i++) {
            // Get a random competency and add it to the learning plan.
            $competency_id = totara_generator_util::get_random_record_id('comp');
            if ($competency_id) {
                if (totara_generator_util::get_random_act($this->component_chance_percentage['competency'])) {
                    $component = $plan->get_component('competency');
                    $component->update_assigned_items(array($competency_id));
                    // get linked courses for newly added competencies
                    $evidence = $component->get_course_evidence_items(array($competency_id));
                    foreach ($evidence as $compid => $linkedcourses) {
                        foreach ($linkedcourses as $linkedcourse) {
                            if (!$plan->get_component('course')->is_item_assigned($linkedcourse->courseid)) {
                                $plan->get_component('course')->assign_new_item($linkedcourse->courseid, true, false);
                            }
                            // Now we need to grab the assignment ID
                            $assignmentid = $DB->get_field('dp_plan_course_assign', 'id', array('planid' => $learning_plan->id, 'courseid' => $linkedcourse->courseid), MUST_EXIST);
                            // Get the competency assignment ID from the competency
                            $compassignid = $DB->get_field('dp_plan_competency_assign', 'id', array('competencyid' => $competency_id, 'planid' => $learning_plan->id), MUST_EXIST);
                            $mandatory = ($linkedcourse->linktype == PLAN_LINKTYPE_MANDATORY) ? 'course' : '';
                            // Create relation
                            $plan->add_component_relation('competency', $compassignid, 'course', $assignmentid, $mandatory);
                        }
                    }
                }
            } else {
                $this->log('assignfail', 'competency');
                break;
            }
        }

        $numitems = mt_rand(0, $this->component_size_quantities[$this->size]);
        $this->log('planassignobjectives', $numitems);
        // Create the objective name we want to use with by getting
        // the number off any previous matching records we created.
        $default_name = totara_plan_generator::DEFAULT_NAME_OBJECTIVE;
        $default_name = trim($default_name) . ' ' . totara_generator_util::get_size_name($this->size);
        $name_number = totara_generator_util::get_next_record_number('dp_plan_objective', 'fullname', $default_name);

        $objective_data = array ();
        // Create some objectives for the learning plan.
        for ($i = 0; $i < $numitems; $i++) {
            // Randomly add an objective to the learning plan.
            if (totara_generator_util::get_random_act($this->component_chance_percentage['objective'])) {
                $objective_data['fullname'] = $default_name . ' ' . $name_number++;
                $objective = $this->learning_plan_generator->create_learning_plan_objective($learning_plan->id, 2, $objective_data);
            }
        }

        $numitems = mt_rand(0, $this->component_size_quantities[$this->size]);
        $this->log('planassignprograms', $numitems);
        // Assign some programs to the learning plan.
        for ($i = 0; $i < $numitems; $i++) {
            // Get a random program id and add it to the learning plan.
            $program_id = totara_generator_util::get_random_record_id('prog');
            if ($program_id) {
                if (totara_generator_util::get_random_act($this->component_chance_percentage['program'])) {
                    $component = $plan->get_component('program');
                    $component->update_assigned_items(array($program_id));
                }
            } else {
                $this->log('assignfail', 'program');
                break;
            }
        }
    }

}
