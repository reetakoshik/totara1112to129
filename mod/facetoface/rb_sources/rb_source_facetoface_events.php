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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_facetoface
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/rb_sources/rb_facetoface_base_source.php');

class rb_source_facetoface_events extends rb_facetoface_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $sourcetitle;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Global report restrictions are applied in define_joinlist() method.

        $this->base = '{facetoface_sessions}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->paramoptions = $this->define_paramoptions();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_facetoface_events');
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

    public function define_joinlist() {
        $global_restriction_join_su = $this->get_global_report_restriction_join('su', 'userid');
        $joinlist = array();

        $joinlist[] = new rb_join(
            'attendees',
            'LEFT',
            "(SELECT su.sessionid, count(ss.id) AS number
                FROM {facetoface_signups} su
                {$global_restriction_join_su}
                JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                WHERE ss.superceded=0 AND ss.statuscode >= " . MDL_F2F_STATUS_APPROVED ."
                GROUP BY su.sessionid)",
            "attendees.sessionid = base.id",
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        $joinlist[] = new rb_join(
            'facetoface',
            'LEFT',
            '{facetoface}',
            '(facetoface.id = base.facetoface)',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );

        $joinlist[] = new rb_join(
            'allattendees',
            'LEFT',
            "(SELECT su.sessionid, su.userid, ss.id AS ssid, ss.statuscode
                FROM {facetoface_signups} su
                {$global_restriction_join_su}
                JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                WHERE ss.superceded = 0)",
            'allattendees.sessionid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $joinlist[] = new rb_join(
            'sessiondate',
            'LEFT',
            '{facetoface_sessions_dates}',
            'sessiondate.sessionid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );

        $this->add_grouped_session_status_to_joinlist($joinlist, 'base', 'id');
        $this->add_course_table_to_joinlist($joinlist, 'facetoface', 'course');
        $this->add_course_category_table_to_joinlist($joinlist, 'course', 'category');
        $this->add_job_assignment_tables_to_joinlist($joinlist, 'allattendees', 'userid');
        $this->add_user_table_to_joinlist($joinlist, 'allattendees', 'userid');
        $this->add_user_table_to_joinlist($joinlist, 'base', 'usermodified', 'modifiedby');
        $this->add_facetoface_session_roles_to_joinlist($joinlist, 'base.id');
        $this->add_facetoface_currentuserstatus_to_joinlist($joinlist);
        $this->add_context_table_to_joinlist($joinlist, 'course', 'id', CONTEXT_COURSE, 'INNER');
        $this->add_cohort_course_tables_to_joinlist($joinlist, 'facetoface', 'course');

        return $joinlist;
    }

    public function define_columnoptions() {
        global $DB;
        $usernamefieldscreator = totara_get_all_user_name_fields_join('modifiedby');

        $columnoptions = array(
            new rb_column_option(
                'session',
                'totalnumattendees',
                get_string('totalnumattendees', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode >= ' . MDL_F2F_STATUS_REQUESTED . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'waitlistattendees',
                get_string('waitlistattendees', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode = ' . MDL_F2F_STATUS_WAITLISTED . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'numspaces',
                get_string('numspaces', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode >= ' . MDL_F2F_STATUS_APPROVED . ' THEN 1 ELSE NULL END)',
                array('joins' => array('allattendees'),
                    'grouping' => 'count',
                    'displayfunc' => 'session_spaces',
                    'extrafields' => array('overall_capacity' => 'base.capacity'),
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'cancelledattendees',
                get_string('cancelledattendees', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode IN (' . MDL_F2F_STATUS_USER_CANCELLED . ', ' . MDL_F2F_STATUS_SESSION_CANCELLED . ') THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'fullyattended',
                get_string('fullyattended', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode = ' . MDL_F2F_STATUS_FULLY_ATTENDED . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'partiallyattended',
                get_string('partiallyattended', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode = ' . MDL_F2F_STATUS_PARTIALLY_ATTENDED . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'noshowattendees',
                get_string('noshowattendees', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode = ' . MDL_F2F_STATUS_NO_SHOW . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'declinedattendees',
                get_string('declinedattendees', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode = ' . MDL_F2F_STATUS_DECLINED . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'details',
                get_string('sessdetails', 'rb_source_facetoface_sessions'),
                'base.details'
            ),
            new rb_column_option(
                'session',
                'overbookingallowed',
                get_string('overbookingallowed', 'rb_source_facetoface_summary'),
                'base.allowoverbook',
                array(
                    'displayfunc' => 'yes_or_no'
                )
            ),
            new rb_column_option(
                'facetoface',
                'minbookings',
                get_string('minbookings', 'rb_source_facetoface_summary'),
                'base.mincapacity',
                array(
                    'dbdatatype' => 'integer'
                )
            ),
        );

        if (!get_config(null, 'facetoface_hidecost')) {
            $columnoptions[] = new rb_column_option(
                'facetoface',
                'normalcost',
                get_string('normalcost', 'rb_source_facetoface_summary'),
                'base.normalcost',
                array(
                    'dbdatatype' => 'decimal'
                )
            );
            if (!get_config(null, 'facetoface_hidediscount')) {
                $columnoptions[] = new rb_column_option(
                    'facetoface',
                    'discountcost',
                    get_string('discountcost', 'rb_source_facetoface_summary'),
                    'base.discountcost',
                    array(
                        'dbdatatype' => 'decimal'
                    )
                );
            }
        }

        $columnoptions[] = new rb_column_option(
            'facetoface',
            'sessionid',
            get_string('sessionid', 'rb_source_facetoface_room_assignments'),
            'base.id',
            array(
                'dbdatatype' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'capacity',
            get_string('sesscapacity', 'rb_source_facetoface_sessions'),
            'base.capacity',
            array(
                'dbdatatype' => 'integer'
            )
        );
        $columnoptions[] = new rb_column_option(
            'session',
            'numattendees',
            get_string('numattendees', 'rb_source_facetoface_sessions'),
            'attendees.number',
            array(
                'joins' => 'attendees',
                'dbdatatype' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'numattendeeslink',
            get_string('numattendeeslink', 'rb_source_facetoface_summary'),
            'attendees.number',
            array(
                'joins' => array('attendees'),
                'dbdatatype' => 'integer',
                'displayfunc' => 'numattendeeslink',
                'defaultheading' => get_string('numattendees', 'rb_source_facetoface_sessions'),
                'extrafields' => array(
                    'session' => 'base.id'
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventtimecreated',
            get_string('eventtimecreated', 'rb_source_facetoface_events'),
            "base.timecreated",
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventtimemodified',
            get_string('lastupdated', 'rb_source_facetoface_events'),
            "base.timemodified",
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventmodifiedby',
            get_string('lastupdatedby', 'rb_source_facetoface_events'),
            "CASE WHEN base.usermodified = 0 THEN null
                  ELSE " . $DB->sql_concat_join("' '", $usernamefieldscreator) . " END",
            array(
                'joins' => 'modifiedby',
                'displayfunc' => 'link_user',
                'extrafields' => array_merge(array('id' => 'modifiedby.id'), $usernamefieldscreator),
            )
        );

        $this->add_grouped_session_status_to_columns($columnoptions, 'base');
        $this->add_facetoface_common_to_columns($columnoptions, 'base');
        $this->add_facetoface_session_roles_to_columns($columnoptions);
        $this->add_facetoface_currentuserstatus_to_columns($columnoptions);

        // Include some standard columns.
        $this->add_course_category_fields_to_columns($columnoptions);
        $this->add_course_fields_to_columns($columnoptions);

        return $columnoptions;
    }


    /**
     * Add joins required by @see rb_source_facetoface_events::add_grouped_session_status_to_columns()
     * @param array $joinlist
     * @param string $join 'sessions' table to join to
     * @param string $field 'id' field (from sessions table) to join to
     */
    protected function add_grouped_session_status_to_joinlist(&$joinlist, $join, $field) {
        // No global restrictions here because status is absolute (e.g if it is overbooked then it is overbooked, even if user
        // cannot see all participants).
        $joinlist[] =  new rb_join(
            'cntbookings',
            'LEFT',
            "(SELECT s.id sessionid, COUNT(ss.id) cntsignups
                FROM {facetoface_sessions} s
                LEFT JOIN {facetoface_signups} su ON (su.sessionid = s.id)
                LEFT JOIN {facetoface_signups_status} ss
                    ON (su.id = ss.signupid AND ss.superceded = 0 AND ss.statuscode >= " . MDL_F2F_STATUS_BOOKED . ")
                WHERE 1=1
                GROUP BY s.id)",

            "cntbookings.sessionid = {$join}.{$field}",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );

        $joinlist[] = new rb_join(
            'eventdateinfo',
            'LEFT',
            '(  SELECT  sd.sessionid,
                        sd.eventstart,
                        sd.eventfinish,
                        tzstart.sessiontimezone AS tzstart,
                        tzfinish.sessiontimezone AS tzfinish
                FROM (
                        SELECT   sessionid,
                                 MIN(timestart) AS eventstart,
                                 MAX(timefinish) AS eventfinish
                        FROM     {facetoface_sessions_dates}
                        GROUP BY sessionid
                     ) sd
                INNER JOIN {facetoface_sessions_dates} tzstart
                    ON sd.eventstart = tzstart.timestart AND sd.sessionid = tzstart.sessionid
                INNER JOIN {facetoface_sessions_dates} tzfinish
                    ON sd.eventfinish = tzfinish.timefinish AND sd.sessionid = tzfinish.sessionid )',
            "eventdateinfo.sessionid = {$join}.{$field}",
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );
    }

    /**
     * Add session booking and overall status columns for sessions (so it also groups all sessions (dates) in an event)
     * Requires 'eventdateinfo' join, and 'cntbookings' join provided by
     * @see rb_source_facetoface_events::add_grouped_session_status_to_joinlist()
     *
     * If you call this function in order to get the correct highlighting you will need to extend the CSS rules in
     * mod/facetoface/styles.css and add a line like the following:
     *     .reportbuilder-table[data-source="rb_source_facetoface_summary"] tr
     *
     * Search for that and you'll see what you need to do.
     *
     * @param array $columnoptions
     * @param string $joinsessions Join name that provide {facetoface_sessions}
     */
    protected function add_grouped_session_status_to_columns(&$columnoptions, $joinsessions = 'sessions') {
        $now = time();

        $columnoptions[] = new rb_column_option(
            'session',
            'overallstatus',
            get_string('overallstatus', 'rb_source_facetoface_summary'),
            "( CASE WHEN cancelledstatus <> 0 THEN 'cancelled'
                    WHEN eventdateinfo.eventstart IS NULL OR eventdateinfo.eventstart = 0 OR eventdateinfo.eventstart > {$now} THEN 'upcoming'
                    WHEN {$now} > eventdateinfo.eventstart AND {$now} < eventdateinfo.eventfinish THEN 'started'
                    WHEN {$now} > eventdateinfo.eventfinish THEN 'ended'
                    ELSE NULL END
             )",
            array(
                'joins' => array('eventdateinfo'),
                'displayfunc' => 'overall_status',
                'extrafields' => array(
                    'timestart' => "eventdateinfo.eventstart",
                    'timefinish' => "eventdateinfo.eventfinish",
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'bookingstatus',
            get_string('bookingstatus', 'rb_source_facetoface_summary'),
            "(CASE WHEN {$now} > eventdateinfo.eventfinish AND cntsignups < {$joinsessions}.capacity THEN 'ended'
                   WHEN cancelledstatus <> 0 THEN 'cancelled'
                   WHEN cntsignups < {$joinsessions}.mincapacity THEN 'underbooked'
                   WHEN cntsignups < {$joinsessions}.capacity THEN 'available'
                   WHEN cntsignups = {$joinsessions}.capacity THEN 'fullybooked'
                   WHEN cntsignups > {$joinsessions}.capacity THEN 'overbooked'
                   ELSE NULL END)",
            array(
                'joins' => array('eventdateinfo', 'cntbookings', $joinsessions),
                'displayfunc' => 'booking_status',
                'dbdatatype' => 'char',
                'extrafields' => array(
                    'mincapacity' => "{$joinsessions}.mincapacity",
                    'capacity' => "{$joinsessions}.capacity",
                    'timestart' => "eventdateinfo.eventstart",
                    'timefinish' => "eventdateinfo.eventfinish"
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventstartdate',
            get_string('eventstartdatetime', 'rb_source_facetoface_events'),
            "eventdateinfo.eventstart",
            array(
                'joins' => array('eventdateinfo'),
                'displayfunc' => 'event_date',
                'extrafields' => array('timezone' => 'eventdateinfo.tzstart'),
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventfinishdate',
            get_string('eventfinishdatetime', 'rb_source_facetoface_events'),
            "eventdateinfo.eventfinish",
            array(
                'joins' => array('eventdateinfo'),
                'displayfunc' => 'event_date',
                'extrafields' => array('timezone' => 'eventdateinfo.tzfinish'),
                'dbdatatype' => 'timestamp',
            )
        );
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
                'session',
                'bookingstatus',
                get_string('bookingstatus', 'rb_source_facetoface_summary'),
                'select',
                array(
                    'selectchoices' => self::get_bookingstatus_options(),
                )
            ),
            new rb_filter_option(
                'session',
                'eventtimecreated',
                get_string('eventtimecreated', 'rb_source_facetoface_events'),
                'date'
            ),
            new rb_filter_option(
                'session',
                'eventtimemodified',
                get_string('lastupdated', 'rb_source_facetoface_events'),
                'date'
            ),
            new rb_filter_option(
                'session',
                'eventmodifiedby',
                get_string('lastupdatedby', 'rb_source_facetoface_events'),
                'text'
            ),
        );

        $this->add_facetoface_session_role_fields_to_filters($filteroptions);
        $this->add_facetoface_currentuserstatus_to_filters($filteroptions);

        // Add session custom fields to filters.
        $this->add_course_category_fields_to_filters($filteroptions);
        $this->add_course_fields_to_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'session_roles',
            get_string('sessionroles', 'rb_source_facetoface_events'),
            'base.id'
        );

        return $contentoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'course',
                'value' => 'fullname',
            ),
            array(
                'type' => 'facetoface',
                'value' => 'namelink',
            ),
        );

        return $defaultcolumns;
    }

    public function rb_display_actions($session, $row, $isexport = false) {
        global $OUTPUT;

        if ($isexport) {
            return null;
        }

        $cm = get_coursemodule_from_instance('facetoface', $row->facetofaceid);
        $context = context_module::instance($cm->id);
        if (!has_capability('mod/facetoface:viewattendees', $context)) {
            return null;
        }

        return html_writer::link(
            new moodle_url('/mod/facetoface/attendees.php', array('s' => $session)),
            $OUTPUT->pix_icon('t/cohort', get_string("attendees", "facetoface"))
        );
    }

    /**
     * Spaces left on session.
     *
     * @param string $count Number of signups
     * @param object $row Report row
     * @return string Display html
     */
    public function rb_display_session_spaces($count, $row) {
        $spaces = $row->overall_capacity - $count;
        return ($spaces > 0 ? $spaces : 0);
    }

    /**
     * Show if manager's approval required
     * @param bool $required True when approval required
     * @param stdClass $row
     */
    public function rb_display_approver($required, $row) {
        if ($required) {
            return get_string('manager', 'core_role');
        } else {
            return get_string('noone', 'rb_source_facetoface_summary');
        }
    }

    /**
     * Required columns.
     */
    protected function define_requiredcolumns() {
        $requiredcolumns = array();

        $requiredcolumns[] = new rb_column(
            'visibility',
            'id',
            '',
            "course.id",
            array(
                'joins' => 'course',
                'required' => 'true',
                'hidden' => 'true'
            )
        );

        $requiredcolumns[] = new rb_column(
            'visibility',
            'visible',
            '',
            "course.visible",
            array(
                'joins' => 'course',
                'required' => 'true',
                'hidden' => 'true'
            )
        );

        $requiredcolumns[] = new rb_column(
            'visibility',
            'audiencevisible',
            '',
            "course.audiencevisible",
            array(
                'joins' => 'course',
                'required' => 'true',
                'hidden' => 'true')
        );

        $requiredcolumns[] = new rb_column(
            'ctx',
            'id',
            '',
            "ctx.id",
            array(
                'joins' => 'ctx',
                'required' => 'true',
                'hidden' => 'true'
            )
        );

        $context = context_system::instance();
        if (has_any_capability(['mod/facetoface:viewattendees'], $context)) {
            $requiredcolumns[] = new rb_column(
                'admin',
                'actions',
                get_string('actions', 'rb_source_facetoface_summary'),
                'base.id',
                array(
                    'noexport' => true,
                    'nosort' => true,
                    'extrafields' => array('facetofaceid' => 'base.facetoface'),
                    'displayfunc' => 'actions',
                )
            );
        }

        return $requiredcolumns;
    }

    protected function add_customfields() {
        $this->add_custom_fields_for('facetoface_session', 'base', 'facetofacesessionid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_custom_fields_for('facetoface_sessioncancel', 'base', 'facetofacesessioncancelid', $this->joinlist, $this->columnoptions, $this->filteroptions);
    }

    protected function define_paramoptions() {
        $paramoptions = array(
        );

        return $paramoptions;
    }

    /**
     * Report post config operations.
     *
     * @param reportbuilder $report
     */
    public function post_config(reportbuilder $report) {
        $userid = $report->reportfor;
        if (isset($report->embedobj->embeddedparams['userid'])) {
            $userid = $report->embedobj->embeddedparams['userid'];
        }
        $report->set_post_config_restrictions($report->post_config_visibility_where('course', 'course', $userid));
    }
}
