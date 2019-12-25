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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/rb_sources/f2f_roomavailable.php');
require_once($CFG->dirroot . '/mod/facetoface/rb_sources/f2f_assetavailable.php');

abstract class rb_facetoface_base_source extends rb_base_source {
    public function __construct() {
        $this->usedcomponents[] = 'mod_facetoface';
        parent::__construct();
    }

    /**
     * Add common facetoface columns
     * Requires 'sessions' and 'facetoface' joins
     * @param array $columnoptions
     * @param string $joinsessions
     */
    public function add_facetoface_common_to_columns(&$columnoptions, $joinsessions = 'sessions') {
        $columnoptions[] = new rb_column_option(
            'facetoface',
            'facetofaceid',
            get_string('ftfid', 'rb_source_facetoface_room_assignments'),
            'facetoface.id',
            array(
                'joins' => array('facetoface'),
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'facetoface',
            'name',
            get_string('ftfname', 'rb_source_facetoface_sessions'),
            'facetoface.name',
            array(
                'joins' => array('facetoface'),
                'displayfunc' => 'format_string'
            )
        );

        $columnoptions[] = new rb_column_option(
            'facetoface',
            'namelink',
            get_string('ftfnamelink', 'rb_source_facetoface_sessions'),
            "facetoface.name",
            array(
                'joins' => array('facetoface', $joinsessions),
                'displayfunc' => 'seminar_name_link',
                'defaultheading' => get_string('ftfname', 'rb_source_facetoface_sessions'),
                'extrafields' => array('activity_id' => $joinsessions . '.facetoface'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'facetoface',
            'approvaltype',
            get_string('f2f_approvaltype', 'rb_source_facetoface_summary'),
            "facetoface.approvaltype",
            array(
                'joins' => 'facetoface',
                'displayfunc' => 'f2f_approval',
                'defaultheading' => get_string('approvaltype', 'rb_source_facetoface_sessions'),
                'extrafields' => array(
                    'approvalrole' => 'facetoface.approvalrole'
                )
            )
        );
    }

    /**
     * Add common facetoface session columns
     * Requires 'sessions' join and custom named join to {facetoface_sessions_dates} (by default 'base')
     * @param array $columnoptions
     * @param string $sessiondatejoin Join that provides {facetoface_sessions_dates}
     */
    public function add_session_common_to_columns(&$columnoptions, $sessiondatejoin = 'base') {

        $columnoptions[] = new rb_column_option(
            'facetoface',
            'sessionid',
            get_string('sessionid', 'rb_source_facetoface_room_assignments'),
            'sessions.id',
            array(
                'joins' => 'sessions',
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'capacity',
            get_string('sesscapacity', 'rb_source_facetoface_sessions'),
            'sessions.capacity',
            array(
                'joins' => 'sessions',
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );
        $columnoptions[] = new rb_column_option(
            'date',
            'sessionstartdate',
            get_string('sessstartdatetime', 'rb_source_facetoface_room_assignments'),
            "{$sessiondatejoin}.timestart",
            array(
                'joins' => array($sessiondatejoin),
                'extrafields' => array('timezone' => "{$sessiondatejoin}.sessiontimezone"),
                'displayfunc' => 'event_date',
                'dbdatatype' => 'timestamp'
            )
        );

        $columnoptions[] = new rb_column_option(
            'date',
            'sessionfinishdate',
            get_string('sessfinishdatetime', 'rb_source_facetoface_room_assignments'),
            "{$sessiondatejoin}.timefinish",
            array(
                'joins' => array($sessiondatejoin),
                'extrafields' => array('timezone' => "{$sessiondatejoin}.sessiontimezone"),
                'displayfunc' => 'event_date',
                'dbdatatype' => 'timestamp'
            )
        );

        $columnoptions[] = new rb_column_option(
            'date',
            'localsessionstartdate',
            get_string('localsessstartdate', 'rb_source_facetoface_sessions'),
            "{$sessiondatejoin}.timestart",
            array(
                'joins' => array($sessiondatejoin),
                'displayfunc' => 'local_event_date',
                'dbdatatype' => 'timestamp',
                'defaultheading' => get_string('sessstartdatetime', 'rb_source_facetoface_room_assignments'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'date',
            'localsessionfinishdate',
            get_string('localsessfinishdate', 'rb_source_facetoface_sessions'),
            "{$sessiondatejoin}.timefinish",
            array(
                'joins' => array($sessiondatejoin),
                'displayfunc' => 'local_event_date',
                'dbdatatype' => 'timestamp',
                'defaultheading' => get_string('sessfinishdatetime', 'rb_source_facetoface_room_assignments'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'numattendees',
            get_string('numattendees', 'rb_source_facetoface_sessions'),
            'attendees.number',
            array(
                'joins' => 'attendees',
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'numattendeeslink',
            get_string('numattendeeslink', 'rb_source_facetoface_summary'),
            'attendees.number',
            array(
                'joins' => array('attendees', 'sessions'),
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_num_attendees_link',
                'defaultheading' => get_string('numattendees', 'rb_source_facetoface_sessions'),
                'extrafields' => array(
                    'session' => 'sessions.id'
                )
            )
        );
    }

    /**
     * Provides 'currentuserstatus' join required for the current signed in users status
     * @param array $joinlist
     */
    public function add_facetoface_currentuserstatus_to_joinlist(&$joinlist) {
        global $USER;

        $joinlist[] = new rb_join(
            'currentuserstatus',
            'LEFT',
            "(SELECT su.sessionid, su.userid, ss.id AS ssid, ss.statuscode AS statuscode
                FROM {facetoface_signups} su
                JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                WHERE ss.superceded = 0
                AND su.userid = {$USER->id})",
            'currentuserstatus.sessionid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
    }

    /**
     * Add the current signed in users status column
     *
     * @param array $columnoptions
     */
    public function add_facetoface_currentuserstatus_to_columns(&$columnoptions) {
        $columnoptions[] =
            new rb_column_option(
                'session',
                'currentuserstatus',
                get_string('userstatus', 'rb_source_facetoface_events'),
                "CASE WHEN currentuserstatus.statuscode > 0 THEN currentuserstatus.statuscode ELSE '" . \mod_facetoface\signup\state\not_set::get_code() . "' END",
                array(
                    'joins' => array('currentuserstatus'),
                    'displayfunc' => 'signup_status',
                    'defaultheading' => get_string('userstatusdefault', 'rb_source_facetoface_events')
                )
            );
    }

    /**
     * Add the current signed-in users status filter options
     * @param array $filteroptions
     */
    protected function add_facetoface_currentuserstatus_to_filters(array &$filteroptions) {
        $filteroptions[] =
            new rb_filter_option(
                'session',
                'currentuserstatus',
                get_string('userstatus', 'rb_source_facetoface_events'),
                'select',
                array(
                    'selectchoices' => self::get_currentuserstatus_options(),
                )
            );
    }

    /**
     * Provides 'sessions', 'attendess', 'facetoface', 'room' joins to join list
     * Requires join that provides relevant "sessionid" field (by default used 'base')
     * @param array $joinlist
     * @param string $sessiondatejoin join to {facetoface_sessions_dates}
     */
    public function add_session_common_to_joinlist(&$joinlist, $sessiondatejoin = 'base') {
        $global_restriction_join_su = $this->get_global_report_restriction_join('su', 'userid');

        $joinlist[] = new rb_join(
            'sessions',
            'INNER',
            '{facetoface_sessions}',
            "(sessions.id = {$sessiondatejoin}.sessionid)",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            $sessiondatejoin
        );

        $joinlist[] = new rb_join(
            'attendees',
            'LEFT',
            "(SELECT su.sessionid, count(ss.id) AS number
                FROM {facetoface_signups} su
                {$global_restriction_join_su}
                JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                WHERE ss.superceded=0 AND ss.statuscode >= " . \mod_facetoface\signup\state\waitlisted::get_code() ."
                GROUP BY su.sessionid)",
            "attendees.sessionid = {$sessiondatejoin}.sessionid",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $sessiondatejoin
        );

        $joinlist[] = new rb_join(
            'facetoface',
            'LEFT',
            '{facetoface}',
            '(facetoface.id = sessions.facetoface)',
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            'sessions'
        );

        $joinlist[] = new rb_join(
            'room',
            'LEFT',
            '{facetoface_room}',
            "room.id = {$sessiondatejoin}.roomid",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            $sessiondatejoin
        );
    }

    /*
     * Adds any facetoface session roles to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated if
     *                         any session roles exist
     */
    public function add_facetoface_session_roles_to_joinlist(&$joinlist, $sessionidfield = 'base.sessionid') {
        global $DB;
        // add joins for the following roles as "session_role_X" and
        // "session_role_user_X"
        $sessionroles = self::get_session_roles();
        if (empty($sessionroles)) {
            return;
        }

        // Fields.
        $usernamefields = totara_get_all_user_name_fields_join('role_user', null, true);
        $userlistcolumn = $DB->sql_group_concat($DB->sql_concat_join("' '", $usernamefields), ', ');
        // Add id to fields.
        $usernamefieldsid = array_merge(array('role_user.id' => 'userid'), $usernamefields);
        // Length of resulted concatenated fields.
        $lengthfield = array('lengths' => $DB->sql_length($DB->sql_concat_join("' '", $usernamefieldsid)));
        // Final column: concat(strlen(concat(fields)),concat(fields)) so we know length of each username with id.
        $usernamefieldslink = array_merge($lengthfield, $usernamefieldsid);
        $userlistcolumnlink = $DB->sql_group_concat($DB->sql_concat_join("' '", $usernamefieldslink), ', ');

        foreach ($sessionroles as $role) {
            $field = $role->shortname;
            $roleid = $role->id;

            $sql = "(SELECT session_role.sessionid AS sessionid, session_role.roleid AS roleid, %s AS userlist
                    FROM {user} role_user
                      INNER JOIN {facetoface_session_roles} session_role ON (role_user.id = session_role.userid)
                    GROUP BY session_role.sessionid, session_role.roleid)";

            $userkey = "session_role_user_$field";
            $joinlist[] = new rb_join(
                $userkey,
                'LEFT',
                sprintf($sql, $userlistcolumn),
                "($userkey.sessionid = $sessionidfield AND $userkey.roleid = $roleid)",
                REPORT_BUILDER_RELATION_ONE_TO_MANY
            );

            $userkeylink = $userkey . 'link';
            $joinlist[] = new rb_join(
                $userkeylink,
                'LEFT',
                sprintf($sql, $userlistcolumnlink),
                "($userkeylink.sessionid = $sessionidfield AND $userkeylink.roleid = $roleid)",
                REPORT_BUILDER_RELATION_ONE_TO_MANY
            );
        }
    }

    /*
     * Adds any session role fields to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated if
     *                              any session roles exist
     * @return boolean True if session roles exist
     */
    function add_facetoface_session_roles_to_columns(&$columnoptions) {
        $sessionroles = self::get_session_roles();
        if (empty($sessionroles)) {
            return;
        }

        foreach ($sessionroles as $sessionrole) {
            $field = $sessionrole->shortname;
            $name = $sessionrole->name;
            if (empty($name)) {
                $name = role_get_name($sessionrole);
            }

            $userkey = "session_role_user_$field";

            // User name.
            $columnoptions[] = new rb_column_option(
                'role',
                $field . '_name',
                get_string('sessionrole', 'rb_source_facetoface_sessions', $name),
                "$userkey.userlist",
                array(
                    'joins' => $userkey,
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                )
            );

            // User name with link to profile.
            $userkeylink = $userkey . 'link';
            $columnoptions[] = new rb_column_option(
                'role',
                $field . '_namelink',
                get_string('sessionrolelink', 'rb_source_facetoface_sessions', $name),
                "$userkeylink.userlist",
                array(
                    'joins' => $userkeylink,
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'defaultheading' => get_string('sessionrole', 'rb_source_facetoface_sessions', $name),
                    'displayfunc' => 'f2f_coded_user_link',
                )
            );
        }
        return true;
    }

    /**
     * Add session booking and overall status columns
     * Requires 'sessions' join, and 'cntbookings' join provided by @see rb_facetoface_base_source::add_session_status_to_joinlist()
     *
     * If you call this function in order to get the correct highlighting you will need to extend the CSS rules in
     * mod/facetoface/styles.css and add a line like the following:
     *     .reportbuilder-table[data-source="rb_source_facetoface_summary"] tr
     *
     * Search for that and you'll see what you need to do.
     *
     * @param array $columnoptions
     * @param string $joindates Join name that provide {facetoface_sessions_dates}
     * @param string $joinsessions Join name that provide {facetoface_sessions}
     */
    public function add_session_status_to_columns(&$columnoptions, $joindates = 'base', $joinsessions = 'sessions') {
        $now = time();
        $columnoptions[] = new rb_column_option(
            'session',
            'overallstatus',
            get_string('overallstatus', 'rb_source_facetoface_summary'),

            "( CASE WHEN cancelledstatus <> 0 THEN 'cancelled'
                    WHEN timestart IS NULL OR timestart = 0 OR timestart > {$now} THEN 'upcoming'
                    WHEN {$now} > timestart AND {$now} < timefinish THEN 'started'
                    WHEN {$now} > timefinish THEN 'ended'
                    ELSE NULL END
             )",
            array(
                'joins' => array($joindates, $joinsessions),
                'displayfunc' => 'overall_status',
                'extrafields' => array(
                    'timestart' => "{$joindates}.timestart",
                    'timefinish' => "{$joindates}.timefinish",
                    'timezone' => "{$joindates}.sessiontimezone",
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'bookingstatus',
            get_string('bookingstatus', 'rb_source_facetoface_summary'),
            "(CASE WHEN {$now} > {$joindates}.timefinish AND cntsignups < {$joinsessions}.capacity THEN 'ended'
                   WHEN cancelledstatus <> 0 THEN 'cancelled'
                   WHEN cntsignups < {$joinsessions}.mincapacity THEN 'underbooked'
                   WHEN cntsignups < {$joinsessions}.capacity THEN 'available'
                   WHEN cntsignups = {$joinsessions}.capacity THEN 'fullybooked'
                   WHEN cntsignups > {$joinsessions}.capacity THEN 'overbooked'
                   ELSE NULL END)",
            array(
                'joins' => array($joindates, 'cntbookings', $joinsessions),
                'displayfunc' => 'booking_status',
                'dbdatatype' => 'char',
                'extrafields' => array(
                    'mincapacity' => "{$joinsessions}.mincapacity",
                    'capacity' => "{$joinsessions}.capacity"
                )
            )
        );
    }

    /**
     * Add joins required by @see rb_facetoface_base_source::add_session_status_to_columns()
     * @param array $joinlist
     * @param string $join 'sessions' table to join to
     * @param string $field 'id' field (from sessions table) to join to
     */
    public function add_session_status_to_joinlist(&$joinlist, $join = 'sessions', $field = 'id') {
        // No global restrictions here because status is absolute (e.g if it is overbooked then it is overbooked, even if user
        // cannot see all participants.
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
    }

    /**
     * Return list of user names linked to their profiles from string of concatenated user names, their ids,
     * and length of every name with id
     *
     * @deprecated Since Totara 12.0
     * @param string $name Concatenated list of names, ids, and lengths
     * @param stdClass $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_coded_link_user($name, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_coded_link_user been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_coded_user_link::display', DEBUG_DEVELOPER);
        // Concatenated names are provided as (kind of) pascal string beginning with id in the following format:
        // length_of_following_string.' '.id.' '.name.', '
        if (empty($name)) {
            return '';
        }
        $leftname = $name;
        $result = array();
        while(true) {
            $len = (int)$leftname; // Take string length.
            if (!$len) {
                break;
            }
            $idname = core_text::substr($leftname, core_text::strlen((string)$len)+1, $len, 'UTF-8');
            if (empty($idname)) {
                break;
            }
            $idendpos = core_text::strpos($idname, ' ');
            $id = (int)core_text::substr($idname, 0, $idendpos);
            if (!$id) {
                break;
            }
            $name = trim(core_text::substr($idname, $idendpos));
            $result[] = ($isexport) ? $name : html_writer::link(new moodle_url('/user/view.php', array('id' => $id)), $name);

            // length(length(idname)) + length(' ') + length(idname) + length(', ').
            $leftname = core_text::substr($leftname, core_text::strlen((string)$len)+1+$len+2);
        }
        return implode(', ', $result);
    }

    /*
     * Adds some common user field to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     */
    protected function add_facetoface_session_role_fields_to_filters(&$filteroptions) {
        // auto-generate filters for session roles fields
        $sessionroles = self::get_session_roles();
        if (empty($sessionroles)) {
            return;
        }

        foreach ($sessionroles as $sessionrole) {
            $field = $sessionrole->shortname;
            $name = $sessionrole->name;
            if (empty($name)) {
                $name = role_get_name($sessionrole);
            }

            $filteroptions[] = new rb_filter_option(
                'role',
                $field . '_name',
                get_string('sessionrole', 'rb_source_facetoface_sessions', $name),
                'text'
            );
        }
    }

    /**
     * Get session roles from list of allowed roles
     * @return array
     */
    protected static function get_session_roles() {
        global $DB;

        $allowedroles = get_config(null, 'facetoface_session_roles');
        if (!isset($allowedroles) || $allowedroles == '') {
            return array();
        }
        $allowedroles = explode(',', $allowedroles);

        list($allowedrolessql, $params) = $DB->get_in_or_equal($allowedroles);

        $sessionroles = $DB->get_records_sql("SELECT id, name, shortname FROM {role} WHERE id $allowedrolessql", $params);
        if (!$sessionroles) {
            return array();
        }
        return $sessionroles;
    }

    /**
     * Convert a f2f approvaltype into a human readable string
     *
     * @deprecated Since Totara 12.0
     * @param int $approvaltype
     * @param object $row
     * @return string
     */
    function rb_display_f2f_approval($approvaltype, $row) {
        debugging('rb_facetoface_base_source::rb_display_f2f_approval been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_approval::display', DEBUG_DEVELOPER);
        return facetoface_get_approvaltype_string($approvaltype, $row->approvalrole);
    }

    /**
     * Room name linked to room details
     *
     * @deprecated Since Totara 12.0
     * @param string $roomname
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_room_name_link($roomname, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_room_name_link been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_room_name_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return $roomname;
        }
        if (empty($roomname)) {
            return '';
        }

        if ($row->custom) {
            $roomname .= get_string("roomcustom", "mod_facetoface");
        }

        return html_writer::link(
            new moodle_url('/mod/facetoface/reports/rooms.php', array('roomid' => $row->roomid)),
            $roomname
        );
    }

    /**
     * Asset name linked to asset details
     *
     * @deprecated Since Totara 12.0
     * @param string $assetname
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_asset_name_link($assetname, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_asset_name_link been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_asset_name_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return $assetname;
        }
        if (empty($assetname)) {
            return '';
        }
        return html_writer::link(
            new moodle_url('/mod/facetoface/reports/assets.php', array('assetid' => $row->assetid)),
            $assetname
        );
    }

    /**
     * Display opposite to rb_display_yes_no. E.g. zero value will be 'yes', and non-zero 'no'
     *
     * @deprecated Since Totara 12.0
     * @param scalar $no
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_no_yes($no, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_no_yes been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_no_yes::display', DEBUG_DEVELOPER);
        return ($no) ? get_string('no') : get_string('yes');
    }

    /**
     * Display if room allows scheduling conflicts
     *
     * @deprecated Since Totara 12.0
     * @param string $allowconflicts
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_conflicts($allowconflicts, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_conflicts been deprecated since Totara 12.0. Use \totara_reportbuilder\rb\display\yes_or_no::display()', DEBUG_DEVELOPER);
        return $allowconflicts ? get_string('yes') : get_string('no');
    }

    /**
     * Display count of attendees and link to session attendees report page.
     *
     * @deprecated Since Totara 12.0
     * @param int $cntattendees
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_numattendeeslink($cntattendees, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_numattendeeslink been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_num_attendees_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return $cntattendees;
        }
        if (!$cntattendees) {
            $cntattendees = '0';
        }

        $viewattendees = get_string('viewattendees', 'mod_facetoface');

        $description = html_writer::span($viewattendees, 'sr-only');
        return html_writer::link(new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $row->session)), $cntattendees . $description, array('title' => $viewattendees));

    }

    /**
     * Get currently supported booking status filter options
     * @return array
     */
    protected static function get_bookingstatus_options() {
        $statusopts = array(
            'underbooked' => get_string('status:underbooked', 'rb_source_facetoface_summary'),
            'available' => get_string('status:available', 'rb_source_facetoface_summary'),
            'fullybooked' => get_string('status:fullybooked', 'rb_source_facetoface_summary'),
            'overbooked' => get_string('status:overbooked', 'rb_source_facetoface_summary'),
        );
        return $statusopts;
    }

    /**
     * Get currently supported user booking status filter options
     * @return array
     */
    protected static function get_currentuserstatus_options() {

        $statusopts = array();
        $states = \mod_facetoface\signup\state\state::get_all_states();
        foreach($states as $state) {
            $status = $state::get_code();
            $class = explode('\\', $state);
            $name = end($class);
            $statusopts[$status] =  get_string('userstatus:' . $name, 'rb_source_facetoface_events');
        }

        return $statusopts;
    }

    /**
     * Filter by session overall status
     * @return array of options
     */
    public function rb_filter_overallstatus() {
        $statusopts = array(
            'upcoming' => get_string('status:upcoming', 'rb_source_facetoface_summary'),
            'cancelled' => get_string('status:cancelled', 'rb_source_facetoface_summary'),
            'started' => get_string('status:started', 'rb_source_facetoface_summary'),
            'ended' => get_string('status:ended', 'rb_source_facetoface_summary'),
        );
        return $statusopts;
    }

    /**
     * Add common room column options (excluding custom fields)
     *
     * @param array $columnoptions
     * @param string $join alias of join or table that provides room fields
     */
    protected function add_rooms_fields_to_columns(array &$columnoptions, $join = 'room') {
        $columnoptions[] = new rb_column_option(
            'room',
            'id',
            get_string('roomid', 'rb_source_facetoface_rooms'),
            "$join.id",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'name',
            get_string('name', 'rb_source_facetoface_rooms'),
            "$join.name",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'format_string'
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'namelink',
            get_string('namelink', 'rb_source_facetoface_rooms'),
            "$join.name",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'f2f_room_name_link',
                'defaultheading' => get_string('name', 'rb_source_facetoface_rooms'),
                'extrafields' => array('roomid' => "$join.id", 'custom' => "{$join}.custom")
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'published',
            get_string('published', 'rb_source_facetoface_rooms'),
            "CASE WHEN $join.custom > 0 THEN 1 ELSE 0 END",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_no_yes',
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'description',
            get_string('description', 'rb_source_facetoface_rooms'),
            "$join.description",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'room_description',
                'extrafields' => array('roomid' => "$join.id")
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'visible',
            get_string('visible', 'rb_source_facetoface_rooms'),
            "$join.hidden",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_no_yes'
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'capacity',
            get_string('capacity', 'rb_source_facetoface_rooms'),
            "$join.capacity",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'allowconflicts',
            get_string('allowconflicts', 'rb_source_facetoface_rooms'),
            "$join.allowconflicts",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'yes_or_no',
            )
        );
    }

    /**
     * Add common room filter options (excluding custom fields)
     * @param array $filteroptions
     */
    protected function add_rooms_fields_to_filters(array &$filteroptions) {
        $filteroptions[] = new rb_filter_option(
            'room',
            'id',
            get_string('roomid', 'rb_source_facetoface_rooms'),
            'number'
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'name',
            get_string('name', 'rb_source_facetoface_rooms'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'published',
            get_string('published', 'rb_source_facetoface_rooms'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array('0' => get_string('yes'), '1' => get_string('no'))
            )
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'description',
            get_string('description', 'rb_source_facetoface_rooms'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'visible',
            get_string('visible', 'rb_source_facetoface_rooms'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array('0' => get_string('yes'), '1' => get_string('no'))
            )
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'allowconflicts',
            get_string('allowconflicts', 'rb_source_facetoface_rooms'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array(1 => get_string('yes'), 0 => get_string('no'))
            )
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'capacity',
            get_string('capacity', 'rb_source_facetoface_rooms'),
            'number'
        );
    }

    /**
     * Add common assets column options (excluding custom fields)
     * @param array $columnoptions
     * @param string $join alias of join or table that provides assets fields
     */
    protected function add_assets_fields_to_columns(array &$columnoptions, $join = 'asset') {
        $columnoptions[] = new rb_column_option(
            'asset',
            'id',
            get_string('assetid', 'rb_source_facetoface_asset'),
            "$join.id",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'name',
            get_string('name', 'rb_source_facetoface_asset'),
            "$join.name",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'format_string'
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'namelink',
            get_string('namelink', 'rb_source_facetoface_asset'),
            "$join.name",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'f2f_asset_name_link',
                'defaultheading' => get_string('name', 'rb_source_facetoface_asset'),
                'extrafields' => array('assetid' => "$join.id")
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'published',
            get_string('published', 'rb_source_facetoface_asset'),
            "CASE WHEN $join.custom > 0 THEN 1 ELSE 0 END",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_no_yes',
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'description',
            get_string('description', 'rb_source_facetoface_asset'),
            "$join.description",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'asset_description',
                'extrafields' => array('assetid' => "$join.id")
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'visible',
            get_string('visible', 'rb_source_facetoface_asset'),
            "$join.hidden",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_no_yes'
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'allowconflicts',
            get_string('allowconflicts', 'rb_source_facetoface_asset'),
            "$join.allowconflicts",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'yes_or_no',
            )
        );
    }

    /**
     * Add common room filter options (excluding custom fields)
     * @param array $filteroptions
     */
    protected function add_assets_fields_to_filters(array &$filteroptions) {
        $filteroptions[] = new rb_filter_option(
            'asset',
            'id',
            get_string('assetid', 'rb_source_facetoface_asset'),
            'number'
        );

        $filteroptions[] = new rb_filter_option(
            'asset',
            'name',
            get_string('name', 'rb_source_facetoface_asset'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'asset',
            'published',
            get_string('published', 'rb_source_facetoface_asset'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array('0' => get_string('yes'), '1' => get_string('no'))
            )
        );

        $filteroptions[] = new rb_filter_option(
            'asset',
            'description',
            get_string('description', 'rb_source_facetoface_asset'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'asset',
            'visible',
            get_string('visible', 'rb_source_facetoface_asset'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array('0' => get_string('yes'), '1' => get_string('no'))
            )
        );

        $filteroptions[] = new rb_filter_option(
            'asset',
            'allowconflicts',
            get_string('allowconflicts', 'rb_source_facetoface_asset'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array(1 => get_string('yes'), 0 => get_string('no'))
            )
        );
    }
}
