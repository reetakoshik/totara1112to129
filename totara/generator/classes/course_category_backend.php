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

class totara_generator_course_category_backend extends tool_generator_backend {

    /**
     * @var string Used when creating a course category.
     */
    private $name;

    /**
     * @var integer The size of data to generate.
     */
    protected $size;

    /**
     * @var testing_data_generator For general generator
     */
    protected $generator;

    /** @var coursecat $course_category*/
    protected $course_category;

    /**
     * @var generator For course category generator data
     */
    protected $course_category_generator;

    const DEFAULT_NAME_COURSE_CATEGORY = 'Test course category';

    /**
     * Constructs object ready to create a course category.
     *
     * @param int $size Size as numeric index
     * @param string $name Course category name
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
     * Runs the 'make' process for course categories.
     *
     * @return int category id
     */
    public function make() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/phpunit/classes/util.php');

        raise_memory_limit(MEMORY_EXTRA);

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::start_tag('ul');
        }

        $entirestart = microtime(true);

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        // Get generator.
        $this->generator = phpunit_util::get_data_generator();

        // Make course category.
        $this->course_category = $this->create_course_category();

        // Log total time.
        $this->log('completedcoursecategory', round(microtime(true) - $entirestart, 1));

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::end_tag('ul');
        }

        // Commit transaction and finish.
        $transaction->allow_commit();

        return $this->course_category->id;
    }


    /**
     * Create a course category,
     *
     * @return coursecat The course category created.
     */
    protected function create_course_category() {
        // If we've received a name over thc command line thenb
        // use that, otherwise use the plan generator default.
        if ($this->name) {
            $default_name = $this->name;
        } else {
            $default_name = self::DEFAULT_NAME_COURSE_CATEGORY;
        }

        // Create the name we want to use.
        $default_name = trim($default_name). ' ' . totara_generator_util::get_size_name($this->size);
        $default_name = $default_name . ' ' . totara_generator_util::get_next_record_number('course_categories','name',$default_name);
        // Outputthe name to the log.
        $this->log('creatingcoursecategory', $default_name);
        // Create the course category.
        $record = array('name' => $default_name);
        $result = $this->generator->create_category($record);

        return $result;
    }


}
