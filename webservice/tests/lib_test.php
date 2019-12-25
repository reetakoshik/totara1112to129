<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the webservice component.
 *
 * @package    core_webservice
 * @category   test
 * @copyright  2016 Jun Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/lib.php');

/**
 * Unit tests for the webservice component.
 *
 * @package    core_webservice
 * @category   test
 * @copyright  2016 Jun Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_test extends advanced_testcase {

    /**
     * Setup.
     */
    public function setUp() {
        // Calling parent is good, always.
        parent::setUp();

        // Since we change a config setting here in setUp, we'll always need to reset.
        $this->resetAfterTest(true);

        // We always need enabled WS for this testcase.
        set_config('enablewebservices', '1');
    }

    /**
     * Test init_service_class().
     */
    public function test_init_service_class() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        // Set current user.
        $this->setAdminUser();

        // Add a web service.
        $webservice = new stdClass();
        $webservice->name = 'Test web service';
        $webservice->enabled = true;
        $webservice->restrictedusers = false;
        $webservice->component = 'moodle';
        $webservice->timecreated = time();
        $webservice->downloadfiles = true;
        $webservice->uploadfiles = true;
        $externalserviceid = $DB->insert_record('external_services', $webservice);

        // Add token.
        $externaltoken = new stdClass();
        $externaltoken->token = 'testtoken';
        $externaltoken->tokentype = 0;
        $externaltoken->userid = $USER->id;
        $externaltoken->externalserviceid = $externalserviceid;
        $externaltoken->contextid = 1;
        $externaltoken->creatorid = $USER->id;
        $externaltoken->timecreated = time();
        $DB->insert_record('external_tokens', $externaltoken);

        // Add a function to the service.
        $wsmethod = new stdClass();
        $wsmethod->externalserviceid = $externalserviceid;
        $wsmethod->functionname = 'core_course_get_contents';
        $DB->insert_record('external_services_functions', $wsmethod);

        // Initialise the dummy web service.
        $dummy = new webservice_dummy(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN);
        // Set the token.
        $dummy->set_token($externaltoken->token);
        // Run the web service.
        $dummy->run();
        // Get service methods and structs.
        $servicemethods = $dummy->get_service_methods();
        $servicestructs = $dummy->get_service_structs();
        $this->assertNotEmpty($servicemethods);
        // The function core_course_get_contents should be only the only web service function in the moment.
        $this->assertEquals(1, count($servicemethods));
        // The function core_course_get_contents doesn't have a struct class, so the list of service structs should be empty.
        $this->assertEmpty($servicestructs);

        // Add other functions to the service.
        // The function core_comment_get_comments has one struct class in its output.
        $wsmethod->functionname = 'core_comment_get_comments';
        $DB->insert_record('external_services_functions', $wsmethod);
        // The function core_grades_update_grades has one struct class in its input.
        $wsmethod->functionname = 'core_grades_update_grades';
        $DB->insert_record('external_services_functions', $wsmethod);

        // Run the web service again.
        $dummy->run();
        // Get service methods and structs.
        $servicemethods = $dummy->get_service_methods();
        $servicestructs = $dummy->get_service_structs();
        $this->assertEquals(3, count($servicemethods));
        $this->assertEquals(2, count($servicestructs));

        // Check the contents of service methods.
        foreach ($servicemethods as $method) {
            // Get the external function info.
            $function = external_api::external_function_info($method->name);

            // Check input params.
            foreach ($function->parameters_desc->keys as $name => $keydesc) {
                $this->check_params($method->inputparams[$name]['type'], $keydesc, $servicestructs);
            }

            // Check output params.
            $this->check_params($method->outputparams['return']['type'], $function->returns_desc, $servicestructs);

            // Check description.
            $this->assertEquals($function->description, $method->description);
        }
    }

    /**
     * Utility method that tests the parameter type of a method info's input/output parameter.
     *
     * @param string $type The parameter type that is being evaluated.
     * @param mixed $methoddesc The method description of the WS function.
     * @param array $servicestructs The list of generated service struct classes.
     */
    private function check_params($type, $methoddesc, $servicestructs) {
        if ($methoddesc instanceof external_value) {
            // Test for simple types.
            if (in_array($methoddesc->type, [PARAM_INT, PARAM_FLOAT, PARAM_BOOL])) {
                $this->assertEquals($methoddesc->type, $type);
            } else {
                $this->assertEquals('string', $type);
            }
        } else if ($methoddesc instanceof external_single_structure) {
            // Test that the class name of the struct class is in the array of service structs.
            $structinfo = $this->get_struct_info($servicestructs, $type);
            $this->assertNotNull($structinfo);
            // Test that the properties of the struct info exist in the method description.
            foreach ($structinfo->properties as $propname => $proptype) {
                $this->assertTrue($this->in_keydesc($methoddesc, $propname));
            }
        } else if ($methoddesc instanceof external_multiple_structure) {
            // Test for array types.
            $this->assertEquals('array', $type);
        }
    }

    /**
     * Gets the struct information from the list of struct classes based on the given struct class name.
     *
     * @param array $structarray The list of generated struct classes.
     * @param string $structclass The name of the struct class.
     * @return object|null The struct class info, or null if it's not found.
     */
    private function get_struct_info($structarray, $structclass) {
        foreach ($structarray as $struct) {
            if ($struct->classname === $structclass) {
                return $struct;
            }
        }
        return null;
    }

    /**
     * Searches the keys of the given external_single_structure object if it contains a certain property name.
     *
     * @param external_single_structure $keydesc
     * @param string $propertyname The property name to be searched for.
     * @return bool True if the property name is found in $keydesc. False, otherwise.
     */
    private function in_keydesc(external_single_structure $keydesc, $propertyname) {
        foreach ($keydesc->keys as $key => $desc) {
            if ($key === $propertyname) {
                return true;
            }
        }
        return false;
    }

    /**
     * TOTARA: Create a mock of the webservice server and enable webservice authentication.
     *
     * We're not using the webservice dummy class as that's already being used for
     * a different purpose and could be overridden in a way that undermines our tests.
     *
     * @param string $protocol - e.g. 'rest'
     * @return webservice_server
     */
    private function create_ws_server_for_webservice_auth($protocol) {
        global $CFG;

        if (!is_enabled_auth('webservice')) {
            $authsenabled = explode(',', $CFG->auth);
            $authsenabled [] = 'webservice';
            $CFG->auth = implode(',', $authsenabled);
        }

        /** @var webservice_server $server */
        $server = $this->getMockForAbstractClass('webservice_server', array(WEBSERVICE_AUTHMETHOD_USERNAME));

        $reflection = new ReflectionClass('webservice_server');
        $property_wsname = $reflection->getProperty('wsname');
        $property_wsname->setAccessible(true);
        $property_wsname->setValue($server, $protocol);

        return $server;
    }

    /**
     * Set the username and password values for a webservice_server object.
     *
     * This would normally be done in the parse_request method.
     *
     * @param webservice_server $server
     * @param string $username
     * @param string $password
     */
    private function set_server_username_password($server, $username, $password) {
        $reflection = new ReflectionClass('webservice_server');

        $property_username = $reflection->getProperty('username');
        $property_username->setAccessible(true);
        $property_username->setValue($server, $username);

        $property_password = $reflection->getProperty('password');
        $property_password->setAccessible(true);
        $property_password->setValue($server, $password);
    }

    /**
     * Test a valid login via webservice authentication.
     *
     * We're testing the protected method webservice_server::authenticate_user() rather
     * than the public api as this method could be used by any webservice types.
     * We want to know that this specifically is working.
     */
    public function test_webservice_server_authenticate_user_valid() {
        global $DB, $USER;

        $server = $this->create_ws_server_for_webservice_auth('rest');

        $userrole = $DB->get_record('role', array('shortname' => 'user'));
        assign_capability('webservice/rest:use', CAP_ALLOW, $userrole->id, context_system::instance()->id);

        $user = $this->getDataGenerator()->create_user(array('username' => 'userabc', 'password' => 'mypassw0rd'));

        $this->set_server_username_password($server, 'userabc', 'mypassw0rd');
        phpunit_util::call_internal_method($server, 'authenticate_user', array(), 'webservice_server');

        $this->assertEquals($user->id, $USER->id);
    }

    /**
     * Test the webservce_server::authenticate_user() protected method when the
     * given username does not exist.
     */
    public function test_webservice_server_authenticate_user_doesnt_exist() {
        global $DB, $USER;

        $server = $this->create_ws_server_for_webservice_auth('rest');

        $userrole = $DB->get_record('role', array('shortname' => 'user'));
        assign_capability('webservice/rest:use', CAP_ALLOW, $userrole->id, context_system::instance()->id);

        // Create the user anyway to make sure they're not chosen somehow, but we won't use them.
        $user = $this->getDataGenerator()->create_user(array('username' => 'userabc', 'password' => 'mypassw0rd'));

        $this->set_server_username_password($server, 'NOTuserabc', 'mypassw0rd');

        try {
            phpunit_util::call_internal_method($server, 'authenticate_user', array(), 'webservice_server');
        } catch (moodle_exception $e) {
            $message = $e->getMessage();
        }

        $this->assertEquals('Wrong username or password (Login attempted with username which does not exist: NOTuserabc)', $message);
        $this->assertEmpty($USER->id);
    }

    /**
     * Test the webservce_server::authenticate_user() protected method when the
     * given a wrong password is given.
     */
    public function test_webservice_server_authenticate_user_wrong_password() {
        global $DB, $USER;

        $server = $this->create_ws_server_for_webservice_auth('rest');

        $userrole = $DB->get_record('role', array('shortname' => 'user'));
        assign_capability('webservice/rest:use', CAP_ALLOW, $userrole->id, context_system::instance()->id);

        // Create the user anyway to make sure they're not chosen somehow, but we won't use them.
        $user = $this->getDataGenerator()->create_user(array('username' => 'userabc', 'password' => 'mypassw0rd'));

        $this->set_server_username_password($server, 'userabc', 'NOTmypassw0rd');

        try {
            phpunit_util::call_internal_method($server, 'authenticate_user', array(), 'webservice_server');
        } catch (moodle_exception $e) {
            $message = $e->getMessage();
        }

        $this->assertEquals('Wrong username or password (Wrong username or password)', $message);
        $this->assertEmpty($USER->id);
    }

    /**
     * Test the webservce_server::authenticate_user() protected method when the
     * the lockout threshold for wrong passwords is exceeded.
     */
    public function test_webservice_server_authenticate_user_lockout_threshold() {
        global $DB, $USER, $CFG;

        // After 3 incorrect password attempts, we'll lock the account.
        $CFG->lockoutthreshold = 3;

        $server = $this->create_ws_server_for_webservice_auth('rest');

        $userrole = $DB->get_record('role', array('shortname' => 'user'));
        assign_capability('webservice/rest:use', CAP_ALLOW, $userrole->id, context_system::instance()->id);

        // Create the user anyway to make sure they're not chosen somehow, but we won't use them.
        $user = $this->getDataGenerator()->create_user(array('username' => 'userabc', 'password' => 'mypassw0rd'));

        $this->set_server_username_password($server, 'userabc', 'NOTmypassw0rd');

        // The first 3 times, we get the same debug message from the exception as a normal wrong password.
        for ($i = 1; $i <= 3; $i++) {
            try {
                phpunit_util::call_internal_method($server, 'authenticate_user', array(), 'webservice_server');
            } catch (moodle_exception $e) {
                $message = $e->getMessage();
            }

            $this->assertEquals('Wrong username or password (Wrong username or password)', $message);
            $this->assertEmpty($USER->id);
        }

        try {
            phpunit_util::call_internal_method($server, 'authenticate_user', array(), 'webservice_server');
        } catch (moodle_exception $e) {
            $message = $e->getMessage();
        }

        $this->assertEquals('Wrong username or password (Login has exceeded lockout limit)', $message);
        $this->assertEmpty($USER->id);
    }

    /**
     * Tests webservice::generate_user_ws_tokens for a non-admin user with permission to create
     * a token. An external service exists.
     */
    public function test_webservice_generate_user_ws_tokens_service_exists() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        // Make sure there are no third party external services.
        $DB->delete_records('external_services');

        // Give the user the ability to create a token.
        $userrole = $DB->get_record('role', array('shortname' => 'user'));
        assign_capability('moodle/webservice:createtoken', CAP_ALLOW, $userrole->id, context_system::instance()->id);

        $externalservice = new stdClass();
        $externalservice->name = 'Test web service';
        $externalservice->enabled = true;
        $externalservice->restrictedusers = false;
        $externalservice->component = 'component1';
        $externalservice->timecreated = time();
        $externalservice->downloadfiles = true;
        $externalservice->uploadfiles = true;
        $externalserviceid = $DB->insert_record('external_services', $externalservice);

        $webservice = new webservice();
        $webservice->generate_user_ws_tokens($user->id);

        $this->assertEquals(1, $DB->count_records('external_tokens'));

        $tokenrecord = $DB->get_record('external_tokens', array());
        // The token should 32 characters long and be alphanumeric.
        $this->assertEquals(32, strlen($tokenrecord->token));
        $this->assertRegExp('/^[A-Za-z0-9]+$/', $tokenrecord->token);
        $this->assertEquals(EXTERNAL_TOKEN_PERMANENT, $tokenrecord->tokentype);
        $this->assertEquals($user->id, $tokenrecord->userid);

        $this->assertEquals($externalserviceid, $tokenrecord->externalserviceid);
    }

    /**
     * Tests webservice::generate_user_ws_tokens for a non-admin user with permission to create
     * a token. No external service exists therefore no token should be generated.
     */
    public function test_webservice_generate_user_ws_tokens_service_doesnt_exist() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        // Give the user the ability to create a token.
        $userrole = $DB->get_record('role', array('shortname' => 'user'));
        assign_capability('moodle/webservice:createtoken', CAP_ALLOW, $userrole->id, context_system::instance()->id);

        // Make sure there are no third party external services.
        $DB->delete_records('external_services');

        $webservice = new webservice();
        $webservice->generate_user_ws_tokens($user->id);

        $this->assertEquals(0, $DB->count_records('external_tokens'));
    }
}

/**
 * Class webservice_dummy.
 *
 * Dummy webservice class for testing the webservice_base_server class and enable us to expose variables we want to test.
 *
 * @package    core_webservice
 * @category   test
 * @copyright  2016 Jun Pataleta <jun@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_dummy extends webservice_base_server {

    /**
     * webservice_dummy constructor.
     *
     * @param int $authmethod The authentication method.
     */
    public function __construct($authmethod) {
        parent::__construct($authmethod);

        // Arbitrarily naming this as REST in order not to have to register another WS protocol and set capabilities.
        $this->wsname = 'rest';
    }

    /**
     * Token setter method.
     *
     * @param string $token The web service token.
     */
    public function set_token($token) {
        $this->token = $token;
    }

    /**
     * This method parses the request input, it needs to get:
     *  1/ user authentication - username+password or token
     *  2/ function name
     *  3/ function parameters
     */
    protected function parse_request() {
        // Just a method stub. No need to implement at the moment since it's not really being used for this test case for now.
    }

    /**
     * Send the result of function call to the WS client.
     */
    protected function send_response() {
        // Just a method stub. No need to implement at the moment since it's not really being used for this test case for now.
    }

    /**
     * Send the error information to the WS client.
     *
     * @param exception $ex
     */
    protected function send_error($ex = null) {
        // Just a method stub. No need to implement at the moment since it's not really being used for this test case for now.
    }

    /**
     * run() method implementation.
     */
    public function run() {
        $this->authenticate_user();
        $this->init_service_class();
    }

    /**
     * Getter method of servicemethods array.
     *
     * @return array
     */
    public function get_service_methods() {
        return $this->servicemethods;
    }

    /**
     * Getter method of servicestructs array.
     *
     * @return array
     */
    public function get_service_structs() {
        return $this->servicestructs;
    }
}
