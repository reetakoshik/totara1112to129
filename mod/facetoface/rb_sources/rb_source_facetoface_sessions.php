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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */

use \mod_facetoface\signup\state\waitlisted;
use \mod_facetoface\signup\state\booked;
global $CFG;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/facetoface/rb_sources/rb_facetoface_base_source.php');

class rb_source_facetoface_sessions extends rb_facetoface_base_source {
    use \core_course\rb\source\report_trait;
    use \core_tag\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use \mod_facetoface\rb\traits\required_columns;
    use \mod_facetoface\rb\traits\post_config;
    use \totara_cohort\rb\source\report_trait;

    /** @var string $returnpage name */
    private $returnpage = 'view';

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = '{facetoface_signups}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_facetoface_sessions');
        $this->usedcomponents[] = 'totara_cohort';
        $this->add_customfields();
        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    protected function define_joinlist() {
        global $CFG;
        require_once($CFG->dirroot .'/mod/facetoface/lib.php');

        // joinlist for this source
        $joinlist = array(
            new rb_join(
                'sessions',
                'LEFT',
                '{facetoface_sessions}',
                'sessions.id = base.sessionid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'facetoface',
                'LEFT',
                '{facetoface}',
                'facetoface.id = sessions.facetoface',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'sessions'
            ),
            new rb_join(
                'sessiondate',
                'LEFT',
                '{facetoface_sessions_dates}',
                '(sessiondate.sessionid = sessions.id)',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'sessions'
            ),
            new rb_join(
                'status',
                'LEFT',
                '{facetoface_signups_status}',
                '(status.signupid = base.id AND status.superceded = 0)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'attendees',
                'LEFT',
                // subquery as table
                "(SELECT su.sessionid, count(ss.id) AS number
                    FROM {facetoface_signups} su
                    JOIN {facetoface_signups_status} ss
                        ON su.id = ss.signupid
                    WHERE ss.superceded=0 AND ss.statuscode >= 50
                    GROUP BY su.sessionid)",
                'attendees.sessionid = base.sessionid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'cancellationstatus',
                'LEFT',
                '{facetoface_signups_status}',
                '(cancellationstatus.signupid = base.id AND
                    cancellationstatus.superceded = 0 AND
                    cancellationstatus.statuscode = '.\mod_facetoface\signup\state\user_cancelled::get_code().')',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'room',
                'LEFT',
                '{facetoface_room}',
                'sessiondate.roomid = room.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'sessiondate'
            ),
            new rb_join(
                'bookedby',
                'LEFT',
                '{user}',
                'bookedby.id = CASE WHEN base.bookedby = 0 THEN base.userid ELSE base.bookedby END',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'creator',
                'LEFT',
                '{user}',
                'status.createdby = creator.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'status'
            ),
            new rb_join(
                'pos',
                'LEFT',
                '{pos}',
                'pos.id = selected_job_assignment.positionid',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'selected_job_assignment'
            ),
            new rb_join(
                'approver',
                'LEFT',
                // Only want the last approval record
                "(SELECT status.signupid, status.createdby as approverid, status.timecreated as approvaltime
                    FROM {facetoface_signups_status} status
                    JOIN (SELECT signupid, max(timecreated) as approvaltime
                            FROM {facetoface_signups_status}
                           WHERE statuscode IN (" . waitlisted::get_code() . ", " . booked::get_code() . ") 
                        GROUP BY signupid) lastapproval
                      ON status.signupid = lastapproval.signupid
                     AND status.timecreated = lastapproval.approvaltime
                  WHERE statuscode IN (" . waitlisted::get_code() . ", " . booked::get_code() . "))",
                '(base.id = approver.signupid AND approver.approverid != base.userid)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'assetdate',
                'LEFT',
                '{facetoface_asset_dates}',
                'assetdate.sessionsdateid = sessiondate.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'sessiondate'
            ),
            new rb_join(
                'asset',
                'LEFT',
                '{facetoface_asset}',
                'assetdate.assetid = asset.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'assetdate'
            ),
            new rb_join(
                'selected_job_assignment',
                'LEFT',
                '{job_assignment}',
                'selected_job_assignment.id = base.jobassignmentid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            )
        );


        // include some standard joins
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_core_course_tables($joinlist, 'facetoface', 'course', 'INNER');
        $this->add_context_tables($joinlist, 'course', 'id', CONTEXT_COURSE, 'INNER');
        // requires the course join
        $this->add_core_course_category_tables($joinlist, 'course', 'category');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_tag_tables('core', 'course', $joinlist, 'facetoface', 'course');

        $this->add_facetoface_session_roles_to_joinlist($joinlist);

        $this->add_totara_cohort_course_tables($joinlist, 'facetoface', 'course');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB, $PAGE;

        $usernamefieldscreator = totara_get_all_user_name_fields_join('creator');
        $usernamefieldsbooked  = totara_get_all_user_name_fields_join('bookedby');
        $columnoptions = array(
            new rb_column_option(
                'session',                  // Type.
                'capacity',                 // Value.
                get_string('sesscapacity', 'rb_source_facetoface_sessions'),    // Name.
                'sessions.capacity',        // Field.
                array('joins' => 'sessions', 'dbdatatype' => 'integer', 'displayfunc' => 'integer')         // Options array.
            ),
            new rb_column_option(
                'session',
                'numattendees',
                get_string('numattendees', 'rb_source_facetoface_sessions'),
                'attendees.number',
                array('joins' => 'attendees', 'dbdatatype' => 'integer', 'displayfunc' => 'integer')
            ),
            new rb_column_option(
                'session',
                'details',
                get_string('sessdetails', 'rb_source_facetoface_sessions'),
                'sessions.details',
                array(
                    'joins' => 'sessions',
                    'displayfunc' => 'editor_textarea',
                    'extrafields' => array(
                        'filearea' => '\'session\'',
                        'component' => '\'mod_facetoface\'',
                        'fileid' => 'sessions.id',
                        'context' => '\'context_module\'',
                        'recordid' => 'sessions.facetoface'
                    ),
                    'dbdatatype' => 'text',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'session',
                'signupperiod',
                get_string('signupperiod', 'rb_source_facetoface_sessions'),
                'sessions.registrationtimestart',
                array(
                    'joins' => array('sessions','sessiondate'),
                    'outputformat' => 'text',
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'event_dates_period',
                    'extrafields' => array(
                        'finishdate' => 'sessions.registrationtimefinish',
                        'timezone' => 'sessiondate.sessiontimezone'
                    )
                )
            ),
            new rb_column_option(
                'session',
                'signupstartdate',
                get_string('signupstartdate', 'rb_source_facetoface_sessions'),
                'sessions.registrationtimestart',
                array(
                    'joins' => array('sessions','sessiondate'),
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'event_date',
                    'extrafields' => array('timezone' => 'sessiondate.sessiontimezone'),
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'session',
                'signupenddate',
                get_string('signupenddate', 'rb_source_facetoface_sessions'),
                'sessions.registrationtimefinish',
                array(
                    'joins' => array('sessions','sessiondate'),
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'event_date',
                    'extrafields' => array('timezone' => 'sessiondate.sessiontimezone'),
                    'outputformat' => 'text'
)            ),
            new rb_column_option(
                'status',
                'statuscode',
                get_string('status', 'rb_source_facetoface_sessions'),
                'status.statuscode',
                array(
                    'joins' => 'status',
                    'displayfunc' => 'signup_status',
                )
            ),
            new rb_column_option(
                'facetoface',
                'name',
                get_string('ftfname', 'rb_source_facetoface_sessions'),
                'facetoface.name',
                array('joins' => 'facetoface',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'facetoface',
                'namelink',
                get_string('ftfnamelink', 'rb_source_facetoface_sessions'),
                "facetoface.name",
                array(
                    'joins' => array('facetoface','sessions'),
                    'displayfunc' => 'seminar_name_link',
                    'defaultheading' => get_string('ftfname', 'rb_source_facetoface_sessions'),
                    'extrafields' => array('activity_id' => 'sessions.facetoface'),
                )
            ),
            new rb_column_option(
                'status',
                'createdby',
                get_string('createdby', 'rb_source_facetoface_sessions'),
                $DB->sql_concat_join("' '", $usernamefieldscreator),
                array(
                    'joins' => 'creator',
                    'displayfunc' => 'f2f_user_link',
                    'extrafields' => array_merge(array('id' => 'creator.id'), $usernamefieldscreator),
                )
            ),
            new rb_column_option(
                'date',
                'sessiondate',
                get_string('sessstartdatetime', 'rb_source_facetoface_sessions'),
                'sessiondate.timestart',
                array(
                    'extrafields' => array(
                        'timezone' => 'sessiondate.sessiontimezone'),
                    'joins' =>'sessiondate',
                    'displayfunc' => 'event_date',
                    'dbdatatype' => 'timestamp'
                )
            ),
            new rb_column_option(
                'date',
                'sessiondate_link',
                get_string('sessstartdatetimelink', 'rb_source_facetoface_sessions'),
                'sessiondate.timestart',
                array(
                    'joins' => 'sessiondate',
                    'displayfunc' => 'event_date_link',
                    'defaultheading' => get_string('sessstartdatetime', 'rb_source_facetoface_sessions'),
                    'extrafields' => array(
                        'session_id' => 'base.sessionid',
                        'timezone' => 'sessiondate.sessiontimezone'),
                    'dbdatatype' => 'timestamp'
                )
            ),
            new rb_column_option(
                'date',
                'datefinish',
                get_string('sessfinishdatetime', 'rb_source_facetoface_sessions'),
                'sessiondate.timefinish',
                array(
                    'joins' => 'sessiondate',
                    'displayfunc' => 'event_date',
                    'dbdatatype' => 'timestamp',
                    'extrafields' => array(
                        'timezone' => 'sessiondate.sessiontimezone'
                    )
                )
            ),
            new rb_column_option(
                'date',
                'timestart',
                get_string('sessstarttime', 'rb_source_facetoface_sessions'),
                'sessiondate.timestart',
                array(
                    'joins' => 'sessiondate',
                    'displayfunc' => 'event_time',
                    'dbdatatype' => 'timestamp',
                    'extrafields' => array(
                        'timezone' => 'sessiondate.sessiontimezone'
                    )
                )
            ),
            new rb_column_option(
                'date',
                'timefinish',
                get_string('sessfinishtime', 'rb_source_facetoface_sessions'),
                'sessiondate.timefinish',
                array(
                    'joins' => 'sessiondate',
                    'displayfunc' => 'event_time',
                    'dbdatatype' => 'timestamp',
                    'extrafields' => array(
                        'timezone' => 'sessiondate.sessiontimezone'
                    )
                )
            ),
            new rb_column_option(
                'date',
                'localsessionstartdate',
                get_string('localsessstartdate', 'rb_source_facetoface_sessions'),
                'sessiondate.timestart',
                array(
                    'joins' => 'sessiondate',
                    'displayfunc' => 'local_event_date',
                    'defaultheading' => get_string('sessstartdatetime', 'rb_source_facetoface_sessions'),
                    'dbdatatype' => 'timestamp'
                )
            ),
            new rb_column_option(
                'date',
                'localsessionfinishdate',
                get_string('localsessfinishdate', 'rb_source_facetoface_sessions'),
                'sessiondate.timefinish',
                array(
                    'joins' => 'sessiondate',
                    'displayfunc' => 'local_event_date',
                    'defaultheading' => get_string('sessfinishdatetime', 'rb_source_facetoface_sessions'),
                    'dbdatatype' => 'timestamp'
                )
            ),
            new rb_column_option(
                'session',
                'cancellationdate',
                get_string('cancellationdate', 'rb_source_facetoface_sessions'),
                'cancellationstatus.timecreated',
                array(
                    'joins' => 'cancellationstatus',
                    'displayfunc' => 'event_date',
                    'dbdatatype' => 'timestamp',
                    'extrafields' => array(
                        'timezone' => 'sessiondate.sessiontimezone'
                    ),
                )
            ),
            new rb_column_option(
                'session',
                'bookedby',
                get_string('bookedby', 'rb_source_facetoface_sessions'),
                $DB->sql_concat_join("' '", $usernamefieldsbooked),
                array(
                    'joins' => 'bookedby',
                    'displayfunc' => 'f2f_user_link',
                    'extrafields' => array_merge(array('id' => 'bookedby.id'), $usernamefieldsbooked),
                )
            ),
            new rb_column_option(
                'session',
                'positionname',
                get_string('selectedposition', 'mod_facetoface'),
                'pos.fullname',
                array('joins' => 'pos',
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'session',
                'jobassignmentnameedit',
                get_string('selectedjobassignmentedit', 'mod_facetoface'),
                'selected_job_assignment.fullname',
                array(
                    'columngenerator' => 'job_assignment_edit',
                    'displayfunc' => 'f2f_job_assignment_edit')
                ),
            new rb_column_option(
                'status',
                'timecreated',
                get_string('timeofsignup', 'rb_source_facetoface_sessions'),
                '(SELECT MAX(timecreated)
                    FROM {facetoface_signups_status}
                    WHERE signupid = base.id AND statuscode IN ('.\mod_facetoface\signup\state\booked::get_code().', '.\mod_facetoface\signup\state\waitlisted::get_code().'))',
                array(
                    'displayfunc' => 'event_date',
                    'dbdatatype' => 'timestamp',
                )
            ),
            new rb_column_option(
                'approver',
                'approvername',
                get_string('approvername', 'mod_facetoface'),
                'approver.approverid',
                array('joins' => 'approver',
                      'displayfunc' => 'f2f_approver_name')
            ),
            new rb_column_option(
                'approver',
                'approveremail',
                get_string('approveremail', 'mod_facetoface'),
                'approver.approverid',
                array('joins' => 'approver',
                      'displayfunc' => 'f2f_approver_email')
            ),
            new rb_column_option(
                'approver',
                'approvaltime',
                get_string('approvertime', 'mod_facetoface'),
                'approver.approvaltime',
                array(
                    'joins' => 'approver',
                    'displayfunc' => 'event_date',
                    'dbdatatype' => 'timestamp',
                    'extrafields' => array(
                        'timezone' => 'sessiondate.sessiontimezone'
                    )
                )
            ),
            new rb_column_option(
                'session',
                'cancelledstatus',
                get_string('cancelledstatus', 'mod_facetoface'),
                'sessions.cancelledstatus',
                array(
                    'displayfunc' => 'f2f_session_cancelled_status',
                    'joins' => 'sessions',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'approvallink',
                get_string('approvalrequest', 'rb_source_facetoface_sessions'),
                'sessions.id',
                array(
                    'joins' => 'sessions',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'manage_approval_link',
                    'defaultheading' => get_string('approvalrequest', 'rb_source_facetoface_sessions'),
                    'noexport' => true,
                    'nosort' => true
                )
            ),
        );

        if (!get_config(null, 'facetoface_hidecost')) {
            $columnoptions[] = new rb_column_option(
                'session',
                'normalcost',
                get_string('normalcost', 'rb_source_facetoface_sessions'),
                'sessions.normalcost',
                array(
                    'joins' => 'sessions',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
            );
            if (!get_config(null, 'facetoface_hidediscount')) {
                $columnoptions[] = new rb_column_option(
                    'session',
                    'discountcost',
                    get_string('discountcost', 'rb_source_facetoface_sessions'),
                    'sessions.discountcost',
                    array(
                        'joins' => 'sessions',
                        'dbdatatype' => 'char',
                        'outputformat' => 'text',
                        'displayfunc' => 'format_string'
                    )
                );
                $columnoptions[] = new rb_column_option(
                    'session',
                    'discountcode',
                    get_string('discountcode', 'rb_source_facetoface_sessions'),
                    'base.discountcode',
                    array(
                        'dbdatatype' => 'text',
                        'outputformat' => 'text',
                        'displayfunc' => 'format_string'
                    )
                );
            }
        }

        if (has_any_capability(array('mod/facetoface:addattendees', 'mod/facetoface:removeattendees'), $PAGE->context)) {
            $columnoptions[] = new rb_column_option(
                'session',
                'waitlist_checkbox',
                get_string('selectwithdot', 'mod_facetoface'),
                'NULL',
                array(
                    'displayfunc' => 'waitlist_checkbox',
                    'extrafields' => array('userid' => 'base.userid'),
                    'customheading' => true,
                    'nosort' => true,
                    'noexport' => true
                )
            );
        }

        // include some standard columns
        $this->add_core_user_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_tag_columns('core', 'course', $columnoptions);

        $this->add_facetoface_session_roles_to_columns($columnoptions);
        $this->add_assets_fields_to_columns($columnoptions);
        $this->add_rooms_fields_to_columns($columnoptions);

        $this->add_totara_cohort_course_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'facetoface',
                'name',
                get_string('ftfname', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'status',
                'statuscode',
                get_string('status', 'rb_source_facetoface_sessions'),
                'multicheck',
                array(
                    'selectfunc' => 'session_status_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'date',
                'sessiondate',
                get_string('sessdate', 'rb_source_facetoface_sessions'),
                'date'
            ),
            new rb_filter_option(
                'date',
                'timestart',
                get_string('sessstart', 'rb_source_facetoface_sessions'),
                'date',
                array('includetime' => true)
            ),
            new rb_filter_option(
                'date',
                'timefinish',
                get_string('sessfinish', 'rb_source_facetoface_sessions'),
                'date',
                array('includetime' => true)
            ),
            new rb_filter_option(
                'session',
                'capacity',
                get_string('sesscapacity', 'rb_source_facetoface_sessions'),
                'number'
            ),
            new rb_filter_option(
                'session',
                'details',
                get_string('sessdetails', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'session',
                'bookedby',
                get_string('bookedby', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'session',
                'reserved',
                get_string('reserved', 'rb_source_facetoface_sessions'),
                'select',
                array(
                     'selectchoices' => array(
                         '0' => get_string('reserved', 'rb_source_facetoface_sessions'),
                     )
                ),
                'base.userid'
            ),
            new rb_filter_option(
                'session',
                'signupstartdate',
                get_string('signupstartdate', 'rb_source_facetoface_summary'),
                'date'
            ),
            new rb_filter_option(
                'session',
                'signupenddate',
                get_string('signupenddate', 'rb_source_facetoface_summary'),
                'date'
            ),
            new rb_filter_option(
                'room',
                'name',
                get_string('roomname', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'room',
                'capacity',
                get_string('roomcapacity', 'rb_source_facetoface_sessions'),
                'number'
            ),
            new rb_filter_option(
                'room',
                'description',
                get_string('roomdescription', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'status',
                'createdby',
                get_string('createdby', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'session',
                'cancelledstatus',
                get_string('cancelledstatus', 'facetoface'),
                'select',
                array(
                    'selectfunc' => 'cancel_status',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
        );

        if (!get_config(null, 'facetoface_hidecost')) {
            $filteroptions[] = new rb_filter_option(
                'session',
                'normalcost',
                get_string('normalcost', 'rb_source_facetoface_sessions'),
                'text'
            );
            if (!get_config(null, 'facetoface_hidediscount')) {
                $filteroptions[] = new rb_filter_option(
                    'session',
                    'discountcost',
                    get_string('discountcost', 'rb_source_facetoface_sessions'),
                    'text'
                );
                $filteroptions[] = new rb_filter_option(
                    'session',
                    'discountcode',
                    get_string('discountcode', 'rb_source_facetoface_sessions'),
                    'text'
                );
            }
        }

        // include some standard filters
        $this->add_core_user_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_core_tag_filters('core', 'course', $filteroptions);

        // add session role fields to filters
        $this->add_facetoface_session_role_fields_to_filters($filteroptions);

        $this->add_totara_cohort_course_filters($filteroptions);

        return $filteroptions;
    }

    public function rb_filter_cancel_status() {
        $selectchoices = array(
            '1' => get_string('cancelled', 'rb_source_facetoface_sessions')
        );

        return $selectchoices;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('thedate', 'rb_source_facetoface_sessions'),
            'sessiondate.timefinish',
            'sessiondate'
        );
        $contentoptions[] = new rb_content_option(
            'session_roles',
            get_string('sessionroles', 'rb_source_facetoface_sessions'),
            'base.sessionid'
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',         // parameter name
                'base.userid'     // field
            ),
            new rb_param_option(
                'courseid',
                'course.id',
                'course'
            ),
            new rb_param_option(
                'status',
                'status.statuscode',
                'status'
            ),
            new rb_param_option(
                'sessionid',
                'base.sessionid'
            ),
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
            ),
            array(
                'type' => 'date',
                'value' => 'sessiondate',
            ),
            array(
                'type' => 'session',
                'value' => 'approvallink'
            )
        );

        return $defaultcolumns;
    }

    protected function define_requiredcolumns() {
        $requiredcolumns = array();
        $this->add_audiencevisibility_columns($requiredcolumns);
        return $requiredcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
            array(
                'type' => 'course',
                'value' => 'fullname',
                'advanced' => 1,
            ),
            array(
                'type' => 'status',
                'value' => 'statuscode',
                'advanced' => 1,
            ),
            array(
                'type' => 'date',
                'value' => 'sessiondate',
                'advanced' => 1,
            ),
        );

        return $defaultfilters;
    }

    protected function add_customfields() {
        $this->columnoptions[] = new rb_column_option(
            'facetoface_signup',
            'allsignupcustomfields',
            get_string('allsignupcustomfields', 'rb_source_facetoface_sessions'),
            'facetofacesignupid',
            array(
                'columngenerator' => 'allcustomfieldssignupmanage',
                'displayfunc' => 'f2f_all_signup_customfields_manage'
            )
        );
        $this->add_totara_customfield_component('facetoface_session', 'sessions', 'facetofacesessionid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_signup', 'base', 'facetofacesignupid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_cancellation', 'base', 'facetofacecancellationid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_sessioncancel', 'sessions', 'facetofacesessioncancelid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_room', 'room', 'facetofaceroomid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_asset', 'asset', 'facetofaceassetid', $this->joinlist, $this->columnoptions, $this->filteroptions);
    }

    //
    //
    // Face-to-face specific display functions
    //
    //
    /**
     * Display customfield with edit action icon
     * This module requires JS already to be included
     *
     * @deprecated Since Totara 12.0
     * @param string $note
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_allcustomfieldssignupmanage($note, $row, $isexport = false) {
        debugging('rb_source_facetoface_sessions::rb_display_allcustomfieldssignupmanage has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_all_signup_customfields_manage::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        if ($isexport) {
            return $note;
        }

        if (!$cm = get_coursemodule_from_instance('facetoface', $row->facetofaceid, $row->courseid)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
        }
        $context = context_module::instance($cm->id);

        if (has_capability('mod/facetoface:manageattendeesnote', $context)) {
            $url = new moodle_url('/mod/facetoface/attendees/ajax/signup_notes.php', array(
                's' => $row->sessionid,
                'userid' => $row->userid,
                'sesskey'=> sesskey()
            ));
            $pix = new pix_icon('t/edit', get_string('edit'));
            $icon = $OUTPUT->action_icon($url, $pix, null, array('class' => 'js-hide action-icon attendee-add-note pull-right'));
            $notehtml = html_writer::span($note);
            return $icon . $notehtml;
        }
        return $note;
    }

    /**
     * Add control to manage signup customfields when user have rights to do so.
     * @param rb_column_option $columnoption should have public string property "type" which value is the type of customfields to show
     * @param bool $hidden should all these columns be hidden
     * @return array of rb_column
     */
    public function rb_cols_generator_allcustomfieldssignupmanage(rb_column_option $columnoption, $hidden) {
        $results = $this->rb_cols_generator_allcustomfields($columnoption, $hidden);

        if (empty($results)) {
            // No money no honey.
            return $results;
        }

        $extrafields = [
            'courseid' => 'facetoface.course',
            'sessionid' => 'sessions.id',
            'facetofaceid' => 'facetoface.id',
            'userid' => 'base.userid',
        ];


        $results[] = new rb_column(
            'facetoface_signup_manage',
            'custom_field_edit_all',
            get_string('actions', 'facetoface'),
            'NULL',
            [
                'displayfunc' => 'f2f_all_signup_customfields_manage',
                'noexport' => true,
                'dbdatatype' => 'text',
                'outputformat' => 'text',
                'style' => null,
                'class' => null,
                'extrafields' => $extrafields,
            ]
        );

        return $results;
    }

    /**
     * Position name column with edit icon
     *
     * @deprecated Since Totara 12.0
     * @param $jobassignment
     * @param $row
     * @param bool $isexport
     * @return null|string|\totara_job\job_assignment
     */
    public function rb_display_job_assignment_edit($jobassignment, $row, $isexport = false) {
        debugging('rb_source_facetoface_sessions::rb_display_job_assignment_edit has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_job_assignment_edit::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        if ($isexport) {
            return $jobassignment;
        }

        if (!$cm = get_coursemodule_from_instance('facetoface', $row->facetofaceid, $row->courseid)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
        }
        $context = context_module::instance($cm->id);
        $canchangesignedupjobassignment = has_capability('mod/facetoface:changesignedupjobassignment', $context);

        $jobassignment = \totara_job\job_assignment::get_with_id($row->jobassignmentid, false);
        if (!empty($jobassignment)) {
            $label = position::job_position_label($jobassignment);
        } else {
            $label = '';
        }
        $url = new moodle_url('/mod/facetoface/attendees/ajax/job_assignment.php', array('s' => $row->sessionid, 'id' => $row->userid));
        $pix = new pix_icon('t/edit', get_string('edit'));
        $icon = $OUTPUT->action_icon($url, $pix, null, array('class' => 'action-icon attendee-edit-job-assignment pull-right'));
        $jobassignmenthtml = html_writer::span($label, 'jobassign'.$row->userid, array('id' => 'jobassign'.$row->userid));

        if ($canchangesignedupjobassignment) {
            return $icon . $jobassignmenthtml;
        }
        return $jobassignmenthtml;
    }

    /**
     * Position name column that will be displayed only when position select settings are enabled
     *
     * @param rb_column_option $columnoption Column settings configured by user
     * @param bool $hidden should this column always be hidden
     * @return array
     */
    public function rb_cols_generator_job_assignment_edit(rb_column_option $columnoption, $hidden) {
        $result = array();

        $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
        if ($selectjobassignmentonsignupglobal) {
            $result[] = new rb_column(
                'session',
                'positionnameedit',
                format_text($columnoption->name),
                'selected_job_assignment.fullname',
                array(
                    'joins' => array('selected_job_assignment','pos'),
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'f2f_job_assignment_edit',
                    'hidden' => $hidden,
                    'extrafields' => array(
                        'jobassignmentname' => 'selected_job_assignment.fullname',
                        'jobassignmentid' => 'selected_job_assignment.id',
                        'positionname' => 'pos.fullname',
                        'userid' => 'base.userid',
                        'courseid' => 'facetoface.course',
                        'sessionid' => 'sessions.id',
                        'facetofaceid' => 'facetoface.id')
                    )
            );
        }
        return $result;
    }

    /**
     * Override user display function to show 'Reserved' for reserved spaces.
     *
     * @deprecated Since Totara 12.0
     * @param string $user
     * @param object $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_link_user($user, $row, $isexport = false) {
        debugging('rb_source_facetoface_sessions::rb_display_link_user has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_user_link::display', DEBUG_DEVELOPER);
        if (!empty($row->id)) {
            return parent::rb_display_link_user($user, $row, $isexport);
        }
        return get_string('reserved', 'rb_source_facetoface_sessions');
    }

    /**
     * Override user display function to show 'Reserved' for reserved spaces.
     *
     * @deprecated Since Totara 12.0
     * @param string $user
     * @param object $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_link_user_icon($user, $row, $isexport = false) {
        debugging('rb_source_facetoface_sessions::rb_display_link_user_icon has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_user_icon_link::display', DEBUG_DEVELOPER);
        if (!empty($row->id)) {
            return parent::rb_display_link_user_icon($user, $row, $isexport);
        }
        return get_string('reserved', 'rb_source_facetoface_sessions');
    }

    /**
     * Display the email address of the approver
     *
     * @deprecated Since Totara 12.0
     * @param int $approverid
     * @param object $row
     * @return string
     */
    function rb_display_approveremail($approverid, $row) {
        debugging('rb_source_facetoface_sessions::rb_display_approveremail has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_approver_email::display()', DEBUG_DEVELOPER);
        if (empty($approverid)) {
            return '';
        } else {
            $approver = core_user::get_user($approverid);
            return $approver->email;
        }
    }

    /**
     * Display the full name of the approver
     *
     * @deprecated Since Totara 12.0
     * @param int $approverid
     * @param object $row
     * @return string
     */
    function rb_display_approvername($approverid, $row) {
        debugging('rb_source_facetoface_sessions::rb_display_approvername has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_approver_name::display', DEBUG_DEVELOPER);
        if (empty($approverid)) {
            return '';
        } else {
            $approver = core_user::get_user($approverid);
            return fullname($approver);
        }
    }

    /**
     * Override user display function to show 'Reserved' for reserved spaces.
     *
     * @deprecated Since Totara 12.0
     * @param string $user
     * @param object $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_user($user, $row, $isexport = false) {
        debugging('rb_source_facetoface_sessions::rb_display_user has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_user::display', DEBUG_DEVELOPER);
        if (!empty($user)) {
            return parent::rb_display_user($user, $row, $isexport);
        }
        return get_string('reserved', 'rb_source_facetoface_sessions');
    }


    //
    //
    // Source specific filter display methods
    //
    //

    function rb_filter_session_status_list() {

        $output = array();
        $states = \mod_facetoface\signup\state\state::get_all_states();
        foreach ($states as $state) {
            $code = $state::get_code();
            $output[$code] = $state::get_string();
        }

        // show most completed option first in pulldown
        return array_reverse($output, true);

    }

    function rb_filter_coursedelivery_list() {
        $coursedelivery = array();
        $coursedelivery[0] = get_string('no');
        $coursedelivery[1] = get_string('yes');
        return $coursedelivery;
    }

    /**
     * Reformat a timestamp and timezone into a date, showing nothing if invalid or null
     *
     * @deprecated Since Totara 12.0
     * @param integer $date Unix timestamp
     * @param object $row Object containing all other fields for this row (which should include a timezone field)
     *
     * @return string Date in a nice format
     */
    function rb_display_show_cancelled_status($status) {
        debugging('rb_source_facetoface_sessions::rb_display_show_cancelled_status has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_session_cancelled_status::display', DEBUG_DEVELOPER);
        if ($status == 1) {
            return get_string('cancelled', 'rb_source_facetoface_sessions');
        }
        return "";
    }

    public function post_config(reportbuilder $report) {
        $this->add_audiencevisibility_config($report);
    }

    /**
     * Set extra params: customfield, add and/or remove attendees capabilities.
     *
     * @param reportbuilder $report
     */
    public function post_params(reportbuilder $report) {

        if (isset($report->embedobj->returnpage)) {
            $this->returnpage = $report->embedobj->returnpage;
        }
    }

    /**
     * Where to return when user customfield note is updated.
     *
     * @return string
     */
    public function get_return_page(): string {
        return $this->returnpage;
    }
} // end of rb_source_facetoface_sessions class

