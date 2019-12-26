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
 * Class containing the external API functions functions for the Policy tool.
 *
 * @package    tool_policy
 * @copyright  2018 Sara Arjona (sara@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use context_system;
use context_user;
use core\invalid_persistent_exception;
use dml_exception;
use external_api;
use external_description;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use external_warnings;
use invalid_parameter_exception;
use moodle_exception;
use restricted_context_exception;
use tool_policy\api;
use tool_policy\form\accept_policy;
use tool_policy\policy_version;

/**
 * Class external.
 *
 * The external API for the Policy tool.
 *
 * @copyright   2018 Sara Arjona (sara@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Parameter description for get_policy_version_parameters().
     *
     * @return external_function_parameters
     */
    public static function get_policy_version_parameters() {
        return new external_function_parameters([
            'versionid' => new external_value(PARAM_INT, 'The policy version ID', VALUE_REQUIRED),
            'behalfid' => new external_value(PARAM_INT, 'The id of user on whose behalf the user is viewing the policy',
                VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * Fetch the details of a policy version.
     *
     * @param int $versionid The policy version ID.
     * @param int $behalfid The id of user on whose behalf the user is viewing the policy.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function get_policy_version($versionid, $behalfid = null) {
        global $PAGE;

        $result = [];
        $warnings = [];
        $params = external_api::validate_parameters(self::get_policy_version_parameters(), [
            'versionid' => $versionid,
            'behalfid' => $behalfid
        ]);
        $versionid = $params['versionid'];
        $behalfid = $params['behalfid'];

        $context = context_system::instance();
        $PAGE->set_context($context);

        try {
            // Validate if the user has access to the policy version.
            $version = api::get_policy_version($versionid);
            if (!api::can_user_view_policy_version($version, $behalfid)) {
                $warnings[] = [
                    'item' => $versionid,
                    'warningcode' => 'errorusercantviewpolicyversion',
                    'message' => get_string('errorusercantviewpolicyversion', 'tool_policy')
                ];
            } else if (!empty($version)) {
                $version = api::get_policy_version($versionid);
                $policy['name'] = $version->name;
                $policy['versionid'] = $versionid;
                list($policy['content'], $notusedformat) = external_format_text(
                    $version->content,
                    $version->contentformat,
                    SYSCONTEXTID,
                    'tool_policy',
                    'policydocumentcontent',
                    $version->id
                );
                $result['policy'] = $policy;
            }
        } catch (moodle_exception $e) {
            $warnings[] = [
                'item' => $versionid,
                'warningcode' => 'errorpolicyversionnotfound',
                'message' => get_string('errorpolicyversionnotfound', 'tool_policy')
            ];
        }

        return [
            'result' => $result,
            'warnings' => $warnings
        ];
    }

    /**
     * Parameter description for get_policy_version().
     *
     * @return external_description
     */
    public static function get_policy_version_returns() {
        return new external_single_structure([
            'result' => new external_single_structure([
                            'policy' => new external_single_structure([
                                    'name' => new external_value(PARAM_RAW, 'The policy version name', VALUE_OPTIONAL),
                                    'versionid' => new external_value(PARAM_INT, 'The policy version id', VALUE_OPTIONAL),
                                    'content' => new external_value(PARAM_RAW, 'The policy version content', VALUE_OPTIONAL)
                                    ], 'Policy information', VALUE_OPTIONAL)
                            ]),
            'warnings' => new external_warnings()
        ]);
    }

    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_accept_on_behalf_parameters() {
        return new external_function_parameters(
            array(
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the create group form.
     *
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function submit_accept_on_behalf($jsonformdata) {
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_accept_on_behalf_parameters(),
            ['jsonformdata' => $jsonformdata]);

        self::validate_context(context_system::instance());

        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        // The last param is the ajax submitted data.
        $mform = new accept_policy(null, $data, 'post', '', null, true, $data);

        // Do the action.
        $mform->process();

        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_accept_on_behalf_returns() {
        return new external_value(PARAM_BOOL, 'success');
    }


    /**
     * Parameter description for get_policy_version_parameters().
     *
     * @return external_function_parameters
     */
    public static function get_related_pcourses_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Fetch the details of a policy version.
     *
     * @param int $versionid The policy version ID.
     * @param int $behalfid The id of user on whose behalf the user is viewing the policy.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function get_related_pcourses() {
        global $PAGE, $DB;

        $result = [];
        $warnings = [];
        //$title = [];
        $addcoursestitle = get_string('addcourses', 'tool_policy');

        $coursecategories = $DB->get_records('course_categories');
        $coursearr = array();
        foreach($coursecategories as $category) {
            $courses = $DB->get_records('course', array('category' => $category->id));
            $catcourses = array();
            foreach($courses as $course) {
                $catcourses[] = array('cid' => $course->id, 'fullname' => $course->fullname);
            }
            $catcourses[] = array('title'=>$addcoursestitle);
            $coursearr[] = array('catid' => $category->id, 'catname' => $category->name, 'courses' => $catcourses);
        }
        //$coursearr[] = array('title' => 'my title')
        //$result['courses'] = $catcourses;
        $warnings[] = [
                'item' => 11,
                'warningcode' => '2111111',
                'message' => 'Not getting'
            ];

        return array('categories' => $coursearr, 'warnings' => $warnings, 'cencode' => json_encode($coursearr));
    }

    /**
     * Parameter description for get_policy_version().
     *
     * @return external_description
     */
    public static function get_related_pcourses_returns() {
       
        return new external_single_structure(
            array('categories' => new external_multiple_structure(
                  new external_single_structure(
                    array(
                        'catid' => new external_value(PARAM_INT, 'Category Id', VALUE_OPTIONAL),
                        'catname' => new external_value(PARAM_RAW, 'Category Name', VALUE_OPTIONAL)
                  
                        ,
                        'courses' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                  'cid' => new external_value(PARAM_INT, 'Course Id', VALUE_OPTIONAL),
                                   'fullname' => new external_value(PARAM_RAW, 'Course Name', VALUE_OPTIONAL),
                                   'title' => new external_value(PARAM_RAW, 'Title', VALUE_OPTIONAL)
                              ))
                        ))
                ))
        ));
    }

    /**
     * Parameter description for get_policy_version_parameters().
     *
     * @return external_function_parameters
     */
    public static function get_related_policy_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Fetch the details of a policy version.
     *
     * @param int $versionid The policy version ID.
     * @param int $behalfid The id of user on whose behalf the user is viewing the policy.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function get_related_policy() {
        global $DB, $CFG, $USER , $OUTPUT;
        require_once($CFG->libdir.'/adminlib.php');
        require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
        $policy = api::list_policies(null,false);
        $addpolicytitle = get_string('addpolicies', 'tool_policy');
        $policyarr = array();
        foreach($policy as $p) {
        if(!empty($p->currentversion)) {
        $policyarr[] = array('pid' => $p->currentversion->id, 'pname' => $p->currentversion->name);
        }
        }
        //print_r($policyarr);
        //die("I am clicked");
        $policyarr[] = array('title' =>$addpolicytitle);
        return array('policies' => $policyarr);
    }

    /**
     * Parameter description for get_policy_version().
     *
     * @return external_description
     */
    public static function get_related_policy_returns() {
       
        return new external_single_structure(
            array('policies' => new external_multiple_structure(
                  new external_single_structure(
                    array(
                      'pid' => new external_value(PARAM_INT, 'Policy Id', VALUE_OPTIONAL),
                       'pname' => new external_value(PARAM_RAW, 'Policy Name', VALUE_OPTIONAL),
                        'title' => new external_value(PARAM_RAW, 'Title', VALUE_OPTIONAL)
                    )
                ))
        ));
    }

    /**
     * Parameter description for get_policy_version_parameters().
     *
     * @return external_function_parameters
     */
    public static function get_related_audiences_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Fetch the details of a policy version.
     *
     * @param int $versionid The policy version ID.
     * @param int $behalfid The id of user on whose behalf the user is viewing the policy.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function get_related_audiences() {
        global $DB, $CFG, $USER , $OUTPUT;
        $sql = "SELECT ch.id, ch.name
                  FROM {cohort} ch";
        $audiences = $DB->get_records_sql($sql);
        $audiencetitle = get_string('addaudiences', 'tool_policy');
        $audiencearr = array();
        foreach($audiences as $ad) {
            /*$user = $DB->get_record_sql("SELECT id, firstname, lastname FROM {user} WHERE id = '".$ad->userid."'");*/
        $audiencearr[] = array('audid' => $ad->id, 'audname' => $ad->name);
        }
        $audiencearr[] = array('title' =>$audiencetitle);
        return array('audiences' => $audiencearr);
    }

    /**
     * Parameter description for get_policy_version().
     *
     * @return external_description
     */
    public static function get_related_audiences_returns() {
       
        return new external_single_structure(
            array('audiences' => new external_multiple_structure(
                  new external_single_structure(
                    array(
                      'audid' => new external_value(PARAM_INT, 'Audience Id', VALUE_OPTIONAL),
                       'audname' => new external_value(PARAM_RAW, 'Audience Name', VALUE_OPTIONAL),
                        'title' => new external_value(PARAM_RAW, 'Title', VALUE_OPTIONAL)
                ))
        )));
    }
}
