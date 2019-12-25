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
 * @author Rob Tyler <rob.tyler@totaralms.com>
 * @package totara_generator
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/generator/lib.php');
require_once($CFG->libdir . '/testing/lib.php');

class totara_generator_site_backend extends tool_generator_site_backend {

    /**
     * @var bool If the debugging level checking was skipped.
     */
    protected $bypasscheck;

    /*
     * @var List of components that that site builder will create.
    */
    protected $site_data_components = array('coursecategory',
                                            'course',
                                      );

    /**
     * @var array Multidimensional array where the first level is the course size and the second the site size.
     */
    protected $site_data_iterations = array(
        array(2, 8, 64, 256, 1024, 4096),
        array(1, 4, 8, 16, 32, 64),
        array(0, 0, 1, 4, 8, 16),
        array(0, 0, 0, 1, 0, 0),
        array(0, 0, 0, 0, 1, 0),
        array(0, 0, 0, 0, 0, 1)
    );

    /**
     * @var array Number of programs to be created
     */
    private static $paramprogramscount = array(1, 2, 5, 10, 10, 10);
    /**
     * @var array Number of certifications to be created
     */
    private static $paramcertificationscount = array(1, 2, 5, 10, 10, 10);

    /**
     * @var array Number of competencies to be used.
     */
    private static $paramcompetencycount = array(1, 2, 5, 10, 20, 50);
    /**
     * @var array Number of positions to be used.
     */
    private static $parampositionscount = array(1, 2, 5, 10, 20, 50);
    /**
     * @var array Number of goals to be used.
     */
    private static $paramgoalscount = array(1, 2, 5, 10, 10, 10);
    /**
     * @var array Number of organisations to be used.
     */
    private static $paramorganisationscount = array(1, 2, 5, 10, 20, 50);
    /**
     * @var array Number of student accounts to be created.
     */
    private static $paramusersaccount = array(10, 100, 1000, 10000, 50000, 100000);
    /**
     * @var array Number of manager accounts to be used.
     */
    private static $parammanagersaccount = array(1, 2, 5, 10, 10, 10);
    /**
     * @var array Number of audience to be used/created.
     */
    private static $paramaudience = array(1, 2, 2, 2, 4, 4);

    /** @var testing_data_generator */
    protected $generator;

    /**
     * @var totara_cohort_generator Data generator for hierarchy
     */
    protected $cohort_generator;

    /**
     * @var totara_hierarchy_generator Data generator for hierarchy
     */
    protected $hierarchy_generator;

    /**
     * @var totara_program_generator Data generator for program
     */
    protected $program_generator;

    /** @var core_completion_generator */
    protected $completion_generator;

    /**
     * @var array Array of program ids
     */
    private $programids = array();

    /**
     * @var array Array of certification ids
     */
    private $certificationids = array();

    /**
     * @var array Array of user ids
     */
    private $userids = array();

    /**
     * @var array Array of manager ids
     */
    private $managerids = array();

    /**
     * @var array Array of organisation ids
     */
    private $organisationids = array();

    /**
     * @var array Array of position ids
     */
    private $positionids = array();

    /**
     * @var array Array of goal ids
     */
    private $goalids = array();
    /**
     * @var array Array of audience ids
     */
    private $audienceids = array();

    /**
     * @var string default password for created users.
     */
    private $user_password = 'Password1!';

    /**
     * @const string To identify manager account
     */
    const MANAGER_TOOL_GENERATOR = 'manager';

    /**
     * @const string To identify student account
     */
    const USER_TOOL_GENERATOR = 'user';

    /**
     * Constructs object ready to make the site.
     *
     * @param int $size Size as numeric index
     * @param bool $bypasscheck If debugging level checking was skipped.
     * @param bool $fixeddataset To use fixed or random data
     * @param int|bool $filesizelimit The max number of bytes for a generated file
     * @param bool $progress True if progress information should be displayed
     */
    public function __construct($size, $bypasscheck, $fixeddataset = false, $filesizelimit = false, $progress = true) {

        // Set parameters.
        $this->bypasscheck = $bypasscheck;

        parent::__construct($size, $bypasscheck, $fixeddataset, $filesizelimit, $progress);
    }

    /*
     * Set the default user password.
     *
     * @param string $password The default password to use when creating accounts.
     */
    public function set_user_password($password) {
        $this->user_password = $password;
    }
    /**
     * Gets a list of size choices supported by this backend.
     *
     * @return array List of size (int) => text description for display
     */
    public static function get_size_choices() {
        $options = array();
        for ($size = self::MIN_SIZE; $size <= self::MAX_SIZE; $size++) {
            $options[$size] = get_string('sitesize_' . $size, 'tool_generator');
        }
        return $options;
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
        $site = get_site();
        // Turn off messaging.
        set_config('messaging', null);
        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        // Get generator.
        $this->generator = phpunit_util::get_data_generator();

        // Begin site creation process.
        $prevchdir = getcwd();
        chdir($CFG->dirroot);

        //Set up basic Totara users, positions, hierarchies etc.
        $this->create_totara_objects();
        // MSSQL will hang on a locked 'DELETE [mdl_context_map]  WHERE [childid]=@1' unless we commit.
        if ($DB->get_dbfamily() == 'mssql') {
            $transaction->allow_commit();
            $transaction = $DB->start_delegated_transaction();
        }
        // Loop through the data components we want data for.
        foreach ($this->site_data_components as $component) {
            $this->make_component_data_iterations($component);
        }

        $courses = array();
        $numcourses = mt_rand(1, $this->site_data_iterations[1][$this->size]);
        $coursesenrolled = 0;
        while ($coursesenrolled < $numcourses) {
            $courseid = totara_generator_util::get_random_record_id('course');
            if ($courseid == $site->id) {
                // Do not touch site course.
                continue;
            }
            // Find some users and enrol them.
            $keys = array_rand($this->userids, mt_rand(1, (self::$paramusersaccount[$this->size]/2)));
            if (!is_array($keys)) { $keys = array($keys);}
            $users = array_values($keys);
            foreach ($users as $userid) {
                $this->generator->enrol_user($userid, $courseid);
            }
            $coursesenrolled++;
        }
        // Now we have courses and activities we can use them to create useful information.
        $this->hierarchy_generator->assign_goal(self::$paramgoalscount[$this->size]);
        $this->hierarchy_generator->assign_competency(self::$paramcompetencycount[$this->size]);
        $this->hierarchy_generator->assign_position(self::$parampositionscount[$this->size]);
        $this->hierarchy_generator->assign_organisation(self::$paramorganisationscount[$this->size]);
        $this->program_generator->create_programs(self::$paramprogramscount[$this->size]);
        $plan_generator = new totara_generator_learning_plan_backend($this->size);
        $plans = mt_rand(5, count($this->userids));
        for ($x=0; $x < $plans; $x++) {
            $plan_generator->make();
        }
        chdir($prevchdir);
        // Log total time.
        $this->log('sitecompleted', round(microtime(true) - $entirestart, 1));
        if ($this->progress && !CLI_SCRIPT) {
            echo html_writer::end_tag('ul');
        }
        // Commit transaction and finish.
        $transaction->allow_commit();
        // Turn messaging back on.
        set_config('messaging', 1);
        return true;
    }

    /**
     *  Set custom data generators.
    */
    protected function set_custom_generators() {
        $this->hierarchy_generator = $this->generator->get_plugin_generator('totara_hierarchy');
        $this->cohort_generator = $this->generator->get_plugin_generator('totara_cohort');
        $this->program_generator = $this->generator->get_plugin_generator('totara_program');
        $this->completion_generator = $this->generator->get_plugin_generator('core_completion');
    }
    /**
     * Create Totara objects, such as: audiences, positions, organisations, managers,
     * assign primary position to students, create programs, etc.
     *
     */
    protected function create_totara_objects() {
        // Set custom data generators.
        $this->set_custom_generators();
        // Create user accounts.
        $this->create_users(self::USER_TOOL_GENERATOR, self::$paramusersaccount[$this->size]);
        // Create manager accounts.
        $this->create_users(self::MANAGER_TOOL_GENERATOR, self::$parammanagersaccount[$this->size]);

        // Create competency, goal, position and organisation hierarchies.
        // First competency.
        $name = totara_hierarchy_generator::DEFAULT_NAME_FRAMEWORK_COMPETENCY;
        $name = trim($name) . ' ' . totara_generator_util::get_size_name($this->size);
        $name .= ' ' . totara_generator_util::get_next_record_number('comp_framework', 'fullname', $name);
        $data = array ('fullname' => $name);
        // Create competency framework.
        $compframework = $this->hierarchy_generator->create_framework('competency', $data);
        // Create a base competency hierarchy name.
        $name = totara_hierarchy_generator::DEFAULT_NAME_HIERARCHY_COMPETENCY;
        $name = trim($name) . ' ' . totara_generator_util::get_size_name($this->size);
        // Create the competency hierarchies.
        $this->competencyids = $this->hierarchy_generator->create_hierarchies($compframework->id, 'competency', self::$paramcompetencycount[$this->size], $name);

        // Then organisation.
        $name = totara_hierarchy_generator::DEFAULT_NAME_FRAMEWORK_ORGANISATION;
        $name = trim($name) . ' ' . totara_generator_util::get_size_name($this->size);
        $name .= ' ' . totara_generator_util::get_next_record_number('org_framework', 'fullname', $name);
        $data = array ('fullname' => $name);
        // Create organisation framework.
        $orgframework = $this->hierarchy_generator->create_framework('organisation', $data);
        // Create a base organisation hierarchy name.
        $name = totara_hierarchy_generator::DEFAULT_NAME_HIERARCHY_ORGANISATION;
        $name = trim($name) . ' ' . totara_generator_util::get_size_name($this->size);
        // Create the organisation hierarchies.
        $this->organisationids = $this->hierarchy_generator->create_hierarchies($orgframework->id, 'organisation', self::$paramorganisationscount[$this->size], $name);

        // Then position.
        $name = totara_hierarchy_generator::DEFAULT_NAME_FRAMEWORK_POSITION;
        $name = trim($name) . ' ' . totara_generator_util::get_size_name($this->size);
        $name .= ' ' . totara_generator_util::get_next_record_number('pos_framework', 'fullname', $name);
        $data = array ('fullname' => $name);
        // Create position framework.
        $posframework = $this->hierarchy_generator->create_framework('position', $data);
        // Create a base organisation hierarchy name.
        $name = totara_hierarchy_generator::DEFAULT_NAME_HIERARCHY_POSITION;
        $name = trim($name) . ' ' . totara_generator_util::get_size_name($this->size);
        // Create the position hierarchies.
        $this->positionids = $this->hierarchy_generator->create_hierarchies($posframework->id,'position', self::$parampositionscount[$this->size], $name);

        // Then goals.
        $name = totara_hierarchy_generator::DEFAULT_NAME_FRAMEWORK_GOAL;
        $name = trim($name) . ' ' . totara_generator_util::get_size_name($this->size);
        $name .= ' ' . totara_generator_util::get_next_record_number('goal_framework', 'fullname', $name);
        $data = array ('fullname' => $name);
        // Create goal framework.
        $goalframework = $this->hierarchy_generator->create_framework('goal', $data);
        // Create a base goal hierarchy name.
        $name = totara_hierarchy_generator::DEFAULT_NAME_HIERARCHY_GOAL;
        $name = trim($name) . ' ' . totara_generator_util::get_size_name($this->size);
        // Create the goal hierarchies.
        $this->goalids = $this->hierarchy_generator->create_hierarchies($goalframework->id,'goal', self::$paramgoalscount[$this->size], $name);

        // Create job assignments for all students.
        $this->create_job_assignments($this->userids, $this->managerids, $this->positionids, $this->organisationids);
        // Create audiences.
        $this->audienceids = $this->cohort_generator->create_audiences(self::$paramaudience[$this->size], $this->userids);

    }


    /*
     * Make the test data for the given component.
     *
     * @param string $component The component we want to create data for.
     */
    private function make_component_data_iterations($component) {
        foreach ($this->site_data_iterations as $size => $number) {
            for ($i = 1; $i <= $number[$this->size]; $i++) {
                $this->run_create_command($component, $size);
            }
        }
    }


    /**
     * Run a 'maketest' script to create component data.
     *
     * @param string $component The component script we want to run.
     * @param int $size The size of data we want to create.
     * @return void
     */
    protected function run_create_command($component, $size) {

        $command = new \core\command\executable('php', '=');

        // We are in $CFG->dirroot.
        $command->add_value("totara/generator/cli/maketest{$component}.php", PARAM_PATH);

        $command->add_argument('--size', get_string('shortsize_' . $size, 'tool_generator'));

        if (!$this->progress) {
            $command->add_switch('--quiet');
        }

        if ($this->filesizelimit) {
            $command->add_argument('--filesizelimit', $this->filesizelimit, PARAM_INT);
        }

        if (!empty($this->fixeddataset)) {
            $command->add_switch('--fixeddataset');
        }

        if (!empty($this->bypasscheck)) {
            $command->add_switch('--bypasscheck');
        }

        if ($this->progress) {
            $exitcode = $command->execute()->get_return_status();
        } else {
            $exitcode = $command->passthru()->get_return_status();
        }

        if ($exitcode != 0) {
            exit($exitcode);
        }
    }

    /**
     * Creates a number of user accounts.
     *
     * @param string $usertype Type of user (user, manager, teacher, etc)
     * @param int $count Number of user account to create
     */
    private function create_users($usertype, $count) {
        global $DB;

        $username = $usertype . '_tool_generator_';

        // Get highest existing number.
        $nextnumber = totara_generator_util::get_next_record_number('user', 'username', $username);
        $this->log('checkaccounts', $count);

        // Create users from end of existing range.
        if ($nextnumber <= $count) {
            $this->create_user_accounts($nextnumber, $count, $usertype);
        }

        // Sets the pointer at the beginning to be aware of the users we use.
        reset($this->{$usertype . 'ids'});

        $this->end_log();
    }

    /**
     * Creates user accounts with a numeric range.
     *
     * @param int $first Number of first user
     * @param int $last Number of last user
     * @param int $usertype Type of user: common user or manager
     */
    private function create_user_accounts($first, $last, $usertype) {
        global $CFG;

        $this->log('createaccounts', (object)array('from' => $first, 'to' => $last), true);
        $count = $last - $first + 1;
        $done = 0;
        for ($number = $first; $number <= $last; $number++, $done++) {

            $username = $usertype . '_tool_generator_';
            $username = $username . str_pad($number, 6, '0', STR_PAD_LEFT);

            // Create user account.
            $record = array('firstname' => get_string('firstname', 'totara_generator', $usertype),
                    'lastname' => get_string('lastname', 'totara_generator', $number),
                    'username' => $username);

            // We add a user password if it has been specified.
            $record['password'] = (!empty($CFG->tool_generator_users_password)) ? $CFG->tool_generator_users_password : $this->user_password;

            $user = $this->generator->create_user($record);
            $this->{$usertype . 'ids'}[$number] = (int) $user->id;
            $this->dot($done, $count);
        }
        $this->end_log();
    }

    /**
     * Creates job assignments for the given users.
     *
     * @param array $users Array of userids
     * @param array $managerids Array of managerids
     * @param array $positionids Array of positionids
     * @param array $organisationids Array of organisationids
     */
    private function create_job_assignments($users, $managerids, $positionids, $organisationids) {
        $done = 0;
        $count = count($users);
        $this->log('assignhierarchy', $count);
        $managerscount = count($managerids);
        $positionscount = count($positionids);
        $organisationscount = count($organisationids);
        foreach ($users as $user) {
            $done++;
            $managerid = $managerids[rand(1, $managerscount)];
            $managerja = \totara_job\job_assignment::get_first($managerid, false);
            if (empty($managerja)) {
                $managerja = \totara_job\job_assignment::create_default($managerid);
            }
            $data = array(
                'managerjaid' => $managerja->id,
                'positionid' => $positionids[rand(1, $positionscount)],
                'organisationid' => $organisationids[rand(1, $organisationscount)],
            );
            \totara_job\job_assignment::create_default($user, $data);
            $this->dot($done, $count);
        }
        $this->end_log();
    }

}
