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

global $CFG;
require_once($CFG->libdir . '/phpunit/classes/util.php');

class totara_generator_hierarchy_backend extends tool_generator_backend {

    /**
     * @var string Used when creating a learning plan.
     */
    private $name;

    /**
     * @var integer The size of data to generate.
     */
    protected $size;

    /**
     * @var string The type of hierarchy to create.
     */
    protected $type;

    /**
     * @var testing_data_generator Moodle original data generator.
     */
    protected $generator;

    /**
     * @var totara_hierarchy_generator Hierarchy data generator.
     */
    protected $totara_hierarchy_generator;

    /*
     * @var array Map of hierarchy type and prefix
     */
    private $hierarchy_type_prefix = array('competency' => 'comp',
                                           'goal'=> 'goal',
                                           'organisation' => 'org',
                                           'position' => 'pos');
    /*
     * @var array Percentage chance of a hierarchy being created.
     *            Used to randomise the creation of data.
     */
    private $hierarchy_chance_percentage = array('competency' => 75,
                                                 'goal'=> 75,
                                                 'organisation' => 75,
                                                 'position' => 75);

    /**
     * @var array integer Number of hierachies created for the 'maketest' size.
     */
    private $hierarchy_size_quantities = array(2, 4, 8, 16, 32, 64);


    /**
     * Constructs object ready to create hierarchy frameworks and items.
     *
     * @param string $type Type of hierarchy to create
     * @param int $size Size as numeric index
     * @param string $name Course shortname
     * @param bool $fixeddataset To use fixed or random data
     * @param int|bool $filesizelimit The max number of bytes for a generated file
     * @param bool $progress True if progress information should be displayed
     */
    public function __construct($type, $size, $name = NULL, $fixeddataset = false, $filesizelimit = false, $progress = true) {

        // Set parameters.
        $this->type = $type;
        $this->size = $size;
        $this->name = $name;

        parent::__construct($size, $fixeddataset, $filesizelimit, $progress);
        // Get generator.
        $this->generator = phpunit_util::get_data_generator();
        // Set custom data generators.
        $this->set_custom_generators();
    }

    /**
     * Setter helper function.
     * @param string $type
     * @return bool
     */
    public function setType($type='competency') {
        // Change type if valid.
        if (in_array($type, array_keys($this->hierarchy_type_prefix))) {
            $this->type = $type;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Assign items to all hierarchy types.
     */
    public function assign_hierarchies() {
        // Loop through each hierarchy type in turn and assign the appropriate items.
        foreach ($this->hierarchy_type_prefix as $type => $prefix) {
            $this->setType($type);
            $function = "assign_{$type}";
            $size = $this->hierarchy_size_quantities[$this->size];
            $this->totara_hierarchy_generator->$function($size);
        }
    }

    /**
     * Runs the 'make' process for hierarchies.
     */
    public function make() {
        global $DB, $CFG;

        raise_memory_limit(MEMORY_EXTRA);

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::start_tag('ul');
        }

        $entirestart = microtime(true);

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        $this->create_totara_objects();

        // Log total time.
        $log_details = new stdClass();
        $log_details->level = 'framework';
        $log_details->type = $this->type;
        $log_details->time = round(microtime(true) - $entirestart, 1);
        $this->log('completedhierarchy',$log_details);

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
        $this->totara_hierarchy_generator = $this->generator->get_plugin_generator('totara_hierarchy');
    }


    /**
     * Create Totara objects,
     */
    protected function create_totara_objects() {
        $framework = $this->create_framework();
        $hierarchy = $this->create_hierarchies($framework->id);
    }


    /**
     * Create a framework,
     *
     * @return object The framework created.
     */
    protected function create_framework() {
        // If we've received a name over the command line thenb
        // use that, otherwise use the plan generator default.
        if ($this->name) {
            $default_name = $this->name;
        } else {
            $default_name = constant('totara_hierarchy_generator::DEFAULT_NAME_FRAMEWORK_' . strtoupper($this->type));
        }

        // Create the name we want to use.
        $default_name = trim($default_name) . ' ' . totara_generator_util::get_size_name($this->size);
        $shortprefix = hierarchy::get_short_prefix($this->type);
        $default_name = $default_name . ' ' . totara_generator_util::get_next_record_number($shortprefix . '_framework', 'fullname', $default_name);
        // Output the name to the log.
        $log_details = new stdClass();
        $log_details->type = $this->type;
        $log_details->name = $default_name;
        $this->log('creatinghierarchyframework', $log_details);
        // Create the framework.
        $record = array();
        $record['fullname'] = $default_name;
        $result = $this->totara_hierarchy_generator->create_framework($this->type, $record);

        return $result;
    }

    /**
     * Create a hierarchy item within a framework,
     *
     * @param integer $framework_id The framework to create the hierarchies under.
     * @return A list of record ids for the hierarchies created.
     */
    protected function create_hierarchies($framework_id) {
        // If we've received a name over thc command line thenb
        // use that, otherwise use the generator default.
        if ($this->name) {
            $default_name = $this->name;
        } else {
            $default_name = constant('totara_hierarchy_generator::DEFAULT_NAME_HIERARCHY_' . strtoupper($this->type));
        }

        // Create the name we want to use.
        $default_name = trim($default_name) . ' ' . totara_generator_util::get_size_name($this->size);

        $log_details = new stdClass();
        $log_details->type = $this->type;
        $log_details->name = $default_name;
        $log_details->number = $this->hierarchy_size_quantities[$this->size];
        $this->log('creatinghierarchy', $log_details);
        // Create the hierarchies.
        $hierarchy_ids = $this->totara_hierarchy_generator->create_hierarchies($framework_id,
                                                                                $this->type,
                                                                                $this->hierarchy_size_quantities[$this->size],
                                                                                $default_name,
                                                                                $this->hierarchy_chance_percentage[$this->type]);
        $log_details = new stdClass();
        $log_details->type = $this->type;
        $log_details->number = $this->hierarchy_size_quantities[$this->size];
        $this->log('creatinghierarchychildren', $log_details);

        // Create a second level of hierarchies with the ones created above as parents..
        foreach ($hierarchy_ids as $hierarchy_id) {
            $hierarchy_children_ids = $this->totara_hierarchy_generator->create_hierarchies($framework_id,
                                                                                        $this->type,
                                                                                        $this->hierarchy_size_quantities[$this->size],
                                                                                        $default_name,
                                                                                        $this->hierarchy_chance_percentage[$this->type],
                                                                                        array ('parentid' => $hierarchy_id));
        }

        return isset($hierarchy_children_ids) ? array_merge($hierarchy_ids,$hierarchy_children_ids) : $hierarchy_ids;
    }

}
