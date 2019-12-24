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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara_generator
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/generator/lib.php');
require_once($CFG->libdir . '/testing/lib.php');

class totara_generator_course_backend extends tool_generator_course_backend {

    // Private properties from tool_generator_course_backend.
    /**
     * @var array Number of sections in course
     */
    private static $paramsections = array(1, 10, 100, 500, 1000, 2000);
    /**
     * @var array Number of Page activities in course
     */
    private static $parampages = array(1, 50, 200, 1000, 5000, 10000);
    /**
     * @var array Number of students enrolled in course
     */
    private static $paramusers = array(1, 100, 1000, 10000, 50000, 100000);
    /**
     * Total size of small files: 1KB, 1MB, 10MB, 100MB, 1GB, 2GB.
     *
     * @var array Number of small files created in a single file activity
     */
    private static $paramsmallfilecount = array(1, 64, 128, 1024, 16384, 32768);
    /**
     * @var array Size of small files (to make the totals into nice numbers)
     */
    private static $paramsmallfilesize = array(1024, 16384, 81920, 102400, 65536, 65536);
    /**
     * Total size of big files: 8KB, 8MB, 80MB, 800MB, 8GB, 16GB.
     *
     * @var array Number of big files created as individual file activities
     */
    private static $parambigfilecount = array(1, 2, 5, 10, 10, 10);
    /**
     * @var array Size of each large file
     */
    private static $parambigfilesize = array(8192, 4194304, 16777216, 83886080,
            858993459, 1717986918);
    /**
     * @var array Number of forum discussions
     */
    private static $paramforumdiscussions = array(1, 10, 100, 500, 1000, 2000);
    /**
     * @var array Number of forum posts per discussion
     */
    private static $paramforumposts = array(2, 2, 5, 10, 10, 10);

    /**
     * @var string Course shortname
     */
    private $shortname;

    /**
     * @var testing_data_generator Data generator
     */
    protected $generator;

    /**
     * @var core_completion_generator
     */
    protected $completion_generator;

    /**
     * @var stdClass Course object
     */
    private $course;

    /**
     * @var array Number of activities to be created based on the course size
     */
    private static $paramactivitiescount = array(3, 3, 4, 5, 6, 7);
    /**
     * @var array kind of activities to generate.
     */
    private static $activities = array('assign',
                                       'book',
                                       'certificate',
                                       'chat',
                                       'choice',
                                       'data',
                                       'facetoface',
                                       'feedback',
                                       'folder',
                                       'forum',
                                       'glossary',
                                       'imscp',
                                       'lesson',
                                       'lti',
                                       'page',
                                       'quiz',
                                       'resource',
                                       'scorm',
                                       'survey',
                                       'url',
                                       'wiki',
                                       'workshop',
    );

    /**
     * @var array Array of activities created.
     */
    private $activitiescreated = array();

    const DEFAULT_NAME_COURSE = 'Test course';

    /**
     * Constructs object ready to create course.
     *
     * @param string $name Course name
     * @param int $size Size as numeric index
     * @param bool $fixeddataset To use fixed or random data
     * @param int|bool $filesizelimit The max number of bytes for a generated file
     * @param bool $progress True if progress information should be displayed
     */
    public function __construct($name, $size, $fixeddataset = false, $filesizelimit = false, $progress = true) {

        // Set parameters.
        $this->name = $name;
        parent::__construct($name, $size, $fixeddataset, $filesizelimit, $progress);
    }

    /**
     * Runs the entire 'make' process.
     *
     * @return int Course id
     */
    public function make() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');

        raise_memory_limit(MEMORY_EXTRA);

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::start_tag('ul');
        }

        $entirestart = microtime(true);

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        // Get generator.
        $this->generator = phpunit_util::get_data_generator();

        // Make course.
        $this->course = $this->create_course();
        $this->create_totara_objects();
        //$this->create_pages();
        //$this->create_small_files();
        //$this->create_big_files();
        //$this->create_forum();

        // Log total time.
        $this->log('coursecompleted', round(microtime(true) - $entirestart, 1));

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::end_tag('ul');
        }

        // Commit transaction and finish.
        $transaction->allow_commit();
        return $this->course->id;
    }

    /**
     * Set custom data generators.
     *
     */
    protected function set_customs_generators() {
        $this->completion_generator = $this->generator->get_plugin_generator('core_completion');
    }

    /**
     * Create Totara objects.
     *
     */
    protected function create_totara_objects() {
        // Set custom data generators.
        $this->set_customs_generators();
        // Enable completion tracking.
        $this->completion_generator->enable_completion_tracking($this->course);
        // Create some activities.
        $this->create_activities();
        // Set completion for the activities created.
        $this->completion_generator->set_activity_completion($this->course->id, $this->activitiescreated, COMPLETION_AGGREGATION_ALL);
    }

    /**
     * Creates the actual course, overriding the Moodle function.
     *
     * @return stdClass Course record
     */
    private function create_course() {
        // If we've received a name over the command line then
        // use that, otherwise use the plan generator default.
        if ($this->name) {
            $fullname = $this->name;
        } else {
            $fullname = self::DEFAULT_NAME_COURSE;
        }

        // Create the name we want to use.
        $fullname = trim($fullname). ' ' . totara_generator_util::get_size_name($this->size);
        $shortname = totara_generator_util::create_short_name($fullname);

        // Make sure the names are unique.
        $fullname = $fullname . ' ' . totara_generator_util::get_next_record_number('course', 'fullname', $fullname);
        $shortname = $shortname . ' ' . totara_generator_util::get_next_record_number('course', 'shortname', $shortname);

        // Output the name to the log.
        $this->log('createcourse', $fullname);
        // Create the course.
        $record = array();
        $record['fullname'] = $fullname;
        $record['shortname'] = $shortname;
        $record['numsections'] = self::$paramsections[$this->size];
        $record['category'] = totara_generator_util::get_random_record_id('course_categories', true);

        $result = $this->generator->create_course($record, array('createsections' => true));

        return $result;
    }

    /**
     * Creates activities for this course.
     *
     */
    protected function create_activities() {
        // Set up generator.
        $activitiescount = count(self::$activities) - 1;
        $number = self::$paramactivitiescount[$this->size];
        $this->log('createactivities', $number, true);
        for ($i = 1; $i <= $number; $i++) {
            $mod = 'mod_' . self::$activities[rand(0, $activitiescount)];
            /** @var testing_module_generator $modgenerator */
            $modgenerator = $this->generator->get_plugin_generator($mod);
            $record = array('course' => $this->course->id);
            $options = array(
                'section' => 1,
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionview' => COMPLETION_VIEW_REQUIRED,
                'completionusegrade' => 1,
                'completiongradeitemnumber' => 0
            );
            if ($activity = $modgenerator->create_instance($record, $options)) {
                // Some activities has content. Check if the current activity has the create_content method implemented.
                $ref = new ReflectionClass($modgenerator);
                $method = $ref->getMethod('create_content');
                if ($method->class === get_class($modgenerator)) {
                    $modgenerator->create_content($activity);
                }
                $this->activitiescreated[] = $activity;
            }
            $this->dot($i, $number);
        }
        $this->end_log();
    }

}
