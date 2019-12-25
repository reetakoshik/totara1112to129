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
 * @package totara_generator
 */

defined('MOODLE_INTERNAL') || die();

class totara_generator_appraisal_backend extends tool_generator_backend {

    /**
     * @var string Used when creating an appraisal.
     */
    private $name;

    /**
     * @var integer The size of data to generate.
     */
    protected $size;

    /**
     * @var testing_data_generator $appraisal_generator Moodle original data generator.
     */
    protected $generator;

    /**
     * @var totara_appraisal_generator $appraisal_generator.
     */
    protected $appraisal_generator;

    /**
     * @var totara_hierarchy_generator $hierarchy_generator.
     */
    protected $hierarchy_generator;

    /**
     * @var totara_cohort_generator $cohort_generator.
     */
    protected $cohort_generator;

    /**
     * @var array Number of students to be assigned according to the size.
     */
    protected static $paramusers = array(1, 2, 3, 4, 5, 6);

    /**
     * @var array Students to be part of the group to assign to the appraisal.
     */
    protected $users;

    /**
     * @var array Cohort to assign.
     */
    protected $cohort;

    /**
     * Constructs object ready to create appraisal.
     *
     * @param int $size Size as numeric index
     * @param string $name Appraisal shortname
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
     * Runs the 'make' process for appraisals.
     */
    public function make() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');

        //raise_memory_limit(MEMORY_EXTRA);

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::start_tag('ul');
        }

        $entirestart = microtime(true);

        // Get generator.
        $this->generator = phpunit_util::get_data_generator();

        // Set custom data generators.
        $this->set_custom_generators();

        // Create user accounts.
        $this->users = $this->create_users(self::$paramusers[$this->size], $this);

        // Create a group type cohort to assign to the appraisal.
        $this->cohort = $this->create_cohort();

        // Create one appraisal.
        $appraisal = $this->create_appraisal();

        // Log total time.
        $this->log('completedcreationofappraisals', round(microtime(true) - $entirestart, 1));

        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::end_tag('ul');
        }

        return $appraisal->id;
    }

    /**
     * Creates a cohort with the users created for the appraisal.
     */
    protected function create_cohort() {
        // Create a group type cohort and assign it to the appraisal.
        $cohort = $this->cohort_generator->create_cohort();
        $this->cohort_generator->cohort_assign_users($cohort->id, $this->users);

        return $cohort;
    }

    /**
     * Creates a number of user accounts for the generator tool.
     * @todo move this function to a data generator so learning plans and other generators could call it to create/use users.
     *
     * @param int $count custom number of users, if 0 - predefined size will be used to create users
     * @param tool_generator_backend $logger
     * @return array of user ids
     */
    protected function create_users($count, $logger) {
        global $DB;
        $userids = array();

        // Get existing users in order. We will 'fill up holes' in this up to
        // the required number.
        $logger->log('checkaccounts', $count);
        $nextnumber = 1;
        $rs = $DB->get_recordset_select('user', $DB->sql_like('username', '?'),
            array('tool_generator_%'), 'username', 'id, username');
        foreach ($rs as $rec) {
            // Extract number from username.
            $matches = array();
            if (!preg_match('~^tool_generator_([0-9]{6})$~', $rec->username, $matches)) {
                continue;
            }
            $number = (int)$matches[1];

            // Create missing users in range up to this.
            if ($number != $nextnumber) {
                array_merge($userids, $this->create_user_accounts($nextnumber, min($number - 1, $count), $logger));
            } else {
                $userids[$number] = (int)$rec->id;
            }

            // Stop if we've got enough users.
            $nextnumber = $number + 1;
            if ($number >= $count) {
                break;
            }
        }
        $rs->close();

        // Create users from end of existing range.
        if ($nextnumber <= $count) {
            array_merge($userids, $this->create_user_accounts($nextnumber, $count, $logger));
        }

        $logger->end_log();

        // Sets the pointer at the beginning to be aware of the users we use.
        reset($userids);

        return $userids;

    }

    /**
     * Creates user accounts with a numeric range.
     * @todo move this function to a data generator so learning plans and other generators could call it to create/use users.
     *
     * @param int $first Number of first user
     * @param int $last Number of last user
     * @param tool_generator_backend $logger
     * @return array
     */
    protected function create_user_accounts($first, $last, tool_generator_backend $logger) {
        global $CFG;
        $userids = array();

        $logger->log('createaccounts', (object)array('from' => $first, 'to' => $last), true);
        $count = $last - $first + 1;
        $done = 0;
        for ($number = $first; $number <= $last; $number++, $done++) {
            // Work out username with 6-digit number.
            $textnumber = (string)$number;
            while (strlen($textnumber) < 6) {
                $textnumber = '0' . $textnumber;
            }
            $username = 'tool_generator_' . $textnumber;

            // Create user account.
            $record = array('username' => $username, 'idnumber' => $number);

            // We add a user password if it has been specified.
            if (!empty($CFG->tool_generator_users_password)) {
                $record['password'] = $CFG->tool_generator_users_password;
            }
            $user = $this->generator->create_user($record);
            $userids[$number] = (int)$user->id;
            $logger->dot($done, $count);
        }
        $logger->end_log();

        return $userids;
    }

    /**
     * Set custom data generators
     */
    protected function set_custom_generators() {
        $this->cohort_generator = $this->generator->get_plugin_generator('totara_cohort');
        $this->appraisal_generator = $this->generator->get_plugin_generator('totara_appraisal');
        $this->hierarchy_generator = $this->generator->get_plugin_generator('totara_hierarchy');
    }

    /**
     * Create an appraisal
     *
     * @return object The appraisal created.
     */
    protected function create_appraisal() {
        // If we've received a name over thc command line then
        // use that, otherwise use the appraisal generator default.
        $default_name = ($this->name) ? $this->name : totara_appraisal_generator::DEFAULT_NAME;

        // Create the name we want to use.
        $default_name = trim($default_name) . ' ' . totara_generator_util::get_size_name($this->size);
        $default_name = $default_name . ' ' . totara_generator_util::get_next_record_number('appraisal', 'name', $default_name);

        // Output the name to the log.
        $this->log('creatingappraisal', $default_name);

        // Create the appraisal.
        $record = array();
        $record['name'] = $default_name;
        $appraisal = $this->appraisal_generator->create_appraisal($record);
        $stage = $this->appraisal_generator->create_stage($appraisal->id, array('timedue' => time() + DAYSECS));
        $page = $this->appraisal_generator->create_page($stage->id);
        $question = $this->appraisal_generator->create_question($page->id);
        $this->appraisal_generator->create_group_assignment($appraisal, 'cohort', $this->cohort->id);
        $this->appraisal_generator->activate($appraisal->id);

        return $appraisal;
    }

    /**
     * Gets the data required to fill the test plan template with the database contents.
     * Public because it is used by @see totara_core_courses_jmeter
     *
     * @param int $appraisalid The target appraisal id
     * @return stdClass The ids required by the test appraisal
     */
    public static function get_appraisal_test_data($appraisalid) {
        global $DB;

        // Getting appraisal contents info as the current user (will be an admin).
        $data = new stdClass();
        $data->id = (string) $appraisalid;
        $data->stageid = $DB->get_field('appraisal_stage', 'id', array('appraisalid' => $appraisalid), IGNORE_MULTIPLE);
        $data->pageid = $DB->get_field('appraisal_stage_page', 'id', array('appraisalstageid' => $data->stageid), IGNORE_MULTIPLE);
        $data->questionid = $DB->get_field('appraisal_quest_field', 'id', array('appraisalstagepageid' => $data->pageid), IGNORE_MULTIPLE);

        // According to the current test appraisal.
        return $data;
    }
}
