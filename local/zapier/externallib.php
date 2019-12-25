<?php

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

require_once($CFG->libdir . "/externallib.php");
require_once(__DIR__.'/vendor/autoload.php');
require_once "{$CFG->dirroot}/totara/hierarchy/prefix/position/lib.php";
require_once "{$CFG->dirroot}/totara/hierarchy/classes/plugininfo/hierarchy.php";

class local_zapier_external extends external_api {
    
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function course_completion_parameters() {
        return new external_function_parameters(
            array()
        );
    }

    /**
     * Returns course_completion
     * @return json of completion
     */
    public static function course_completion() {
        global $USER, $DB;

        
        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }
        
        $course_completion = $DB->get_records_sql(
            'SELECT eo.id as id, cc.userid as userid, cc.course as courseid, c.fullname as coursename, c.summary as coursesummary, '
            . 'cc.status as status, from_unixtime(cc.timecompleted, \'%Y %D %M %h:%i:%s\') as timecompleted, '
            . 'from_unixtime(cc.timestarted, \'%Y %D %M %h:%i:%s\') as timestarted, from_unixtime(cc.timeenrolled, \'%Y %D %M %h:%i:%s\') as timeenrolled, cc.positionid as positionid '
            . 'FROM {local_zapier_events_observer} eo '
            . 'JOIN {course_completions} cc ON cc.id = eo.core_event_id '
            . 'LEFT JOIN {course} c ON c.id = cc.course '
            . 'WHERE eventtype = ? '
            . 'ORDER BY timecompleted', 
            array('course_completion')
        );
        
        $res = [
            'courses' =>  array_map(function($obj) {
                    return (array)$obj;
                }, array_values($course_completion)
            )
        ];

        return $res;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function course_completion_returns() {
        return  new external_function_parameters([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id'            => new external_value(PARAM_RAW, ''),
                    'userid'        => new external_value(PARAM_RAW, ''),
                    'courseid'      => new external_value(PARAM_RAW, ''),
                    'coursename'    => new external_value(PARAM_RAW, ''),
                    'coursesummary' => new external_value(PARAM_RAW, ''),
                    'status'        => new external_value(PARAM_RAW, ''),
                    'timecompleted' => new external_value(PARAM_RAW, ''),
                    'timestarted'   => new external_value(PARAM_RAW, ''),
                    'timeenrolled'  => new external_value(PARAM_RAW, ''),
                    'positionid'    => new external_value(PARAM_RAW, '')
                ])
            )
        ]);
    }
    
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function user_login_parameters() {
        return new external_function_parameters(
            array()
        );
    }

    /**
     * Returns course_completion
     * @return json of completion
     */
    public static function user_login() {
        global $USER;

        
        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);
        
        
        return json_encode(array());
        ;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function user_login_returns() {
        return new external_value(PARAM_TEXT, '');
    }


    public static function user_update_parameters()
    {
        return new external_function_parameters([
            'data' => new external_single_structure([
                'userid'         => new external_value(PARAM_RAW, '', VALUE_OPTIONAL),
                'managerid'      => new external_value(PARAM_RAW, '', VALUE_OPTIONAL),
                'positionid'     => new external_value(PARAM_RAW, '', VALUE_OPTIONAL),
                'organisationid' => new external_value(PARAM_RAW, '', VALUE_OPTIONAL),
            ])
        ]);
    }

    public static function user_update($params = array())
    {
        global $USER;

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        $result = UserPositionCommand::create()->run($params);

        self::renderJSON($result);
    }

    public static function user_update_returns() 
    {
        return new external_value(PARAM_TEXT, '');
    }

    public static function renderJSON($data)
    {
        header('Content-Type: application/json; charset: utf-8');
        echo json_encode($data);
        exit();
    }

}
