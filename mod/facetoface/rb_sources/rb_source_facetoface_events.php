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
    use \core_course\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_trait;
    use \mod_facetoface\rb\traits\required_columns;
    use \mod_facetoface\rb\traits\post_config;
    use \totara_cohort\rb\source\report_trait;

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
        $this->usedcomponents[] = 'totara_cohort';

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
        $joinlist = array();

        $joinlist[] = new rb_join(
            'facetoface',
            'LEFT',
            '{facetoface}',
            '(facetoface.id = base.facetoface)',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );

        $joinlist[] = new rb_join(
            'sessiondate',
            'LEFT',
            '{facetoface_sessions_dates}',
            'sessiondate.sessionid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );

        $this->add_grouped_session_status_to_joinlist($joinlist, 'base', 'id');
        $this->add_core_course_tables($joinlist, 'facetoface', 'course');
        $this->add_core_course_category_tables($joinlist, 'course', 'category');
        $this->add_core_user_tables($joinlist, 'base', 'usermodified', 'modifiedby');
        $this->add_facetoface_session_roles_to_joinlist($joinlist, 'base.id');
        $this->add_facetoface_currentuserstatus_to_joinlist($joinlist);
        $this->add_context_tables($joinlist, 'course', 'id', CONTEXT_COURSE, 'INNER');
        $this->add_totara_cohort_course_tables($joinlist, 'facetoface', 'course');

        return $joinlist;
    }

    public function define_columnoptions() {
        global $DB;
        $usernamefieldscreator = totara_get_all_user_name_fields_join('modifiedby');
        $global_restriction_join_su = $this->get_global_report_restriction_join('su', 'userid');

        $columnoptions = array(
            new rb_column_option(
                'session',
                'totalnumattendees',
                get_string('totalnumattendees', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode >= " . \mod_facetoface\signup\state\requested::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'waitlistattendees',
                get_string('waitlistattendees', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode = " . \mod_facetoface\signup\state\waitlisted::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'numspaces',
                get_string('numspaces', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode >= " . \mod_facetoface\signup\state\waitlisted::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'f2f_session_spaces',
                    'extrafields' => array('overall_capacity' => 'base.capacity'),
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'cancelledattendees',
                get_string('cancelledattendees', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND (ss.statuscode = " . \mod_facetoface\signup\state\user_cancelled::get_code() . " OR ss.statuscode = " . \mod_facetoface\signup\state\event_cancelled::get_code() . ") AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'fullyattended',
                get_string('fullyattended', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode = " . \mod_facetoface\signup\state\fully_attended::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'partiallyattended',
                get_string('partiallyattended', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode = " . \mod_facetoface\signup\state\partially_attended::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'noshowattendees',
                get_string('noshowattendees', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode = " . \mod_facetoface\signup\state\no_show::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'declinedattendees',
                get_string('declinedattendees', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode = " . \mod_facetoface\signup\state\declined::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'details',
                get_string('sessdetails', 'rb_source_facetoface_sessions'),
                'base.details',
                array('displayfunc' => 'format_string')
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
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer'
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
                    'dbdatatype' => 'decimal',
                    'displayfunc' => 'format_string'
                )
            );
            if (!get_config(null, 'facetoface_hidediscount')) {
                $columnoptions[] = new rb_column_option(
                    'facetoface',
                    'discountcost',
                    get_string('discountcost', 'rb_source_facetoface_summary'),
                    'base.discountcost',
                    array(
                        'dbdatatype' => 'decimal',
                        'displayfunc' => 'format_string'
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
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'capacity',
            get_string('sesscapacity', 'rb_source_facetoface_sessions'),
            'base.capacity',
            array(
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );
        $columnoptions[] = new rb_column_option(
            'session',
            'numattendees',
            get_string('numattendees', 'rb_source_facetoface_sessions'),
            "(SELECT COUNT('x')
                FROM {facetoface_signups} su
                {$global_restriction_join_su}
                JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
               WHERE ss.superceded = 0 AND ss.statuscode >= " . \mod_facetoface\signup\state\waitlisted::get_code() ." AND su.sessionid = base.id)",
            array(
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'numattendeeslink',
            get_string('numattendeeslink', 'rb_source_facetoface_summary'),
            "(SELECT COUNT('x')
                FROM {facetoface_signups} su
                {$global_restriction_join_su}
                JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
               WHERE ss.superceded = 0 AND ss.statuscode >= " . \mod_facetoface\signup\state\waitlisted::get_code() ." AND su.sessionid = base.id)",
            array(
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_num_attendees_link',
                'defaultheading' => get_string('numattendees', 'rb_source_facetoface_sessions'),
                'extrafields' => array(
                    'session' => 'base.id'
                ),
                'iscompound' => true,
                'issubquery' => true,
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventtimecreated',
            get_string('eventtimecreated', 'rb_source_facetoface_events'),
            "base.timecreated",
            array(
                'joins' => 'sessiondate',
                'displayfunc' => 'event_date',
                'dbdatatype' => 'timestamp',
                'extrafields' => array(
                    'timezone' => 'sessiondate.sessiontimezone'
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventtimemodified',
            get_string('lastupdated', 'rb_source_facetoface_events'),
            "base.timemodified",
            array(
                'joins' => 'sessiondate',
                'displayfunc' => 'event_date',
                'dbdatatype' => 'timestamp',
                'extrafields' => array(
                    'timezone' => 'sessiondate.sessiontimezone'
                )
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
                'displayfunc' => 'f2f_user_link',
                'extrafields' => array_merge(array('id' => 'modifiedby.id'), $usernamefieldscreator),
            )
        );

        $this->add_grouped_session_status_to_columns($columnoptions, 'base');
        $this->add_facetoface_common_to_columns($columnoptions, 'base');
        $this->add_facetoface_session_roles_to_columns($columnoptions);
        $this->add_facetoface_currentuserstatus_to_columns($columnoptions);

        // Include some standard columns.
        $this->add_core_course_category_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);

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
                    ON (su.id = ss.signupid AND ss.superceded = 0 AND ss.statuscode >= " . \mod_facetoface\signup\state\booked::get_code() . ")
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
        $this->add_core_course_category_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);

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

    /**
     * Display evet actions
     *
     * @deprecated Since Totara 12.0
     * @param $session
     * @param $row
     * @param bool $isexport
     * @return null|string
     */
    public function rb_display_actions($session, $row, $isexport = false) {
        debugging('rb_source_facetoface_events::rb_display_actions has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_session_actions::display', DEBUG_DEVELOPER);
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
            new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $session)),
            $OUTPUT->pix_icon('t/cohort', get_string("attendees", "facetoface"))
        );
    }

    /**
     * Spaces left on session.
     *
     * @deprecated Since Totara 12.0
     * @param string $count Number of signups
     * @param object $row Report row
     * @return string Display html
     */
    public function rb_display_session_spaces($count, $row) {
        debugging('rb_source_facetoface_events::rb_display_session_spaces has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_session_spaces::display', DEBUG_DEVELOPER);
        $spaces = $row->overall_capacity - $count;
        return ($spaces > 0 ? $spaces : 0);
    }

    /**
     * Show if manager's approval required
     *
     * @deprecated Since Totara 12.0
     * @param bool $required True when approval required
     * @param stdClass $row
     */
    public function rb_display_approver($required, $row) {
        debugging('rb_source_facetoface_events::rb_display_approver has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
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

        $this->add_audiencevisibility_columns($requiredcolumns);

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
                    'displayfunc' => 'f2f_session_actions',
                )
            );
        }

        return $requiredcolumns;
    }

    protected function add_customfields() {
        $this->add_totara_customfield_component('facetoface_session', 'base', 'facetofacesessionid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_sessioncancel', 'base', 'facetofacesessioncancelid', $this->joinlist, $this->columnoptions, $this->filteroptions);
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
        $this->add_audiencevisibility_config($report);
    }
}
