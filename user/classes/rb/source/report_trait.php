<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package core_user
 */

namespace core_user\rb\source;

defined('MOODLE_INTERNAL') || die();

/**
 * Trait that adds methods for user related joins, columns and filters.
 */
trait report_trait {
    /** @var array $addeduserjoins internal tracking of user columns */
    private $addeduserjoins = array();

    /**
     * Do not call directly, to be used from rb_base_source constructor only.
     * @internal
     */
    protected function finalise_core_user_trait() {
        foreach ($this->addeduserjoins as $join => $info) {
            if (empty($info['groupname'])) {
                // Most likely somebody did not add any user columns, in that case do not add custom fields and rely on the BC fallback later.
                continue;
            }
            $this->add_core_user_customfield($this->joinlist, $this->columnoptions, $this->filteroptions, $join, $info['groupname'], $info['addtypetoheading'], empty($info['filters']));
            $this->addeduserjoins[$join]['processed'] = true;
        }
    }

    /**
     * Adds the user table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'user id' field
     * @param string $field Name of user id field to join on
     * @param string $alias Use custom user table alias
     * @return boolean True
     */
    protected function add_core_user_tables(&$joinlist, $join, $field, $alias = 'auser') {
        if (isset($this->addeduserjoins[$alias])) {
            debugging("User join '{$alias}' was already added to the source", DEBUG_DEVELOPER);
        } else {
            $this->addeduserjoins[$alias] = array('join' => $join);
            $this->add_finalisation_method('finalise_core_user_trait');
        }

        // join uses 'auser' as name because 'user' is a reserved keyword
        $joinlist[] = new \rb_join(
            $alias,
            'LEFT',
            '{user}',
            "{$alias}.id = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );

        return true;
    }


    /**
     * Adds some common user field to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the 'user' table
     * @param string $groupname The group to add fields to. If you are defining
     *                          a custom group name, you must define a language
     *                          string with the key "type_{$groupname}" in your
     *                          report source language file.
     * @param boolean $addtypetoheading Add the column type to the column heading
     *                          to differentiate between fields with the same name.
     *
     * @return True
     */
    protected function add_core_user_columns(&$columnoptions,
        $join='auser', $groupname = 'user', $addtypetoheading = false) {
        global $DB, $CFG;

        if ($join === 'base' and !isset($this->addeduserjoins['base'])) {
            $this->addeduserjoins['base'] = array('join' => 'base');
            $this->add_finalisation_method('finalise_core_user_trait');
        }

        if (!isset($this->addeduserjoins[$join])) {
            debugging("Add user join '{$join}' via add_core_user_tables() before calling add_core_user_columns()", DEBUG_DEVELOPER);
        } else if (isset($this->addeduserjoins[$join]['groupname'])) {
            debugging("User columns for {$join} were already added to the source", DEBUG_DEVELOPER);
        } else {
            $this->addeduserjoins[$join]['groupname'] = $groupname;
            $this->addeduserjoins[$join]['addtypetoheading'] = $addtypetoheading;
        }

        $usednamefields = totara_get_all_user_name_fields_join($join, null, true);
        $allnamefields = totara_get_all_user_name_fields_join($join);

        $columnoptions[] = new \rb_column_option(
            $groupname,
            'fullname',
            get_string('userfullname', 'totara_reportbuilder'),
            "CASE WHEN {$join}.id IS NULL THEN NULL ELSE " . $DB->sql_concat_join("' '", $usednamefields) . " END",
            array('joins' => $join,
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'extrafields' => $allnamefields,
                  'displayfunc' => 'user',
                  'addtypetoheading' => $addtypetoheading
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'namelink',
            get_string('usernamelink', 'totara_reportbuilder'),
            $DB->sql_concat_join("' '", $usednamefields),
            array(
                'joins' => $join,
                'displayfunc' => 'user_link',
                'defaultheading' => get_string('userfullname', 'totara_reportbuilder'),
                'extrafields' => array_merge(array('id' => "$join.id", 'deleted' => "{$join}.deleted"), $allnamefields),
                'addtypetoheading' => $addtypetoheading
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'namelinkicon',
            get_string('usernamelinkicon', 'totara_reportbuilder'),
            $DB->sql_concat_join("' '", $usednamefields),
            array(
                'joins' => $join,
                'displayfunc' => 'user_icon_link',
                'defaultheading' => get_string('userfullname', 'totara_reportbuilder'),
                'extrafields' => array_merge(array('id' => "$join.id",
                                                   'picture' => "$join.picture",
                                                   'imagealt' => "$join.imagealt",
                                                   'email' => "$join.email"),
                                             $allnamefields),
                'style' => array('white-space' => 'nowrap'),
                'addtypetoheading' => $addtypetoheading
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'email',
            get_string('useremail', 'totara_reportbuilder'),
            // use CASE to include/exclude email in SQL
            // so search won't reveal hidden results
            "CASE WHEN $join.maildisplay <> 1 THEN '-' ELSE $join.email END",
            array(
                'joins' => $join,
                'displayfunc' => 'user_email',
                'extrafields' => array(
                    'emailstop' => "$join.emailstop",
                    'maildisplay' => "$join.maildisplay",
                ),
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => $addtypetoheading
            )
        );
        // Only include this column if email is among fields allowed by showuseridentity setting or
        // if the current user has the 'moodle/site:config' capability.
        $canview = !empty($CFG->showuseridentity) && in_array('email', explode(',', $CFG->showuseridentity));
        $canview |= has_capability('moodle/site:config', \context_system::instance());
        if ($canview) {
            $columnoptions[] = new \rb_column_option(
                $groupname,
                'emailunobscured',
                get_string('useremailunobscured', 'totara_reportbuilder'),
                "$join.email",
                array(
                    'joins' => $join,
                    'displayfunc' => 'user_email_unobscured',
                    // Users must have viewuseridentity to see the
                    // unobscured email address.
                    'capability' => 'moodle/site:viewuseridentity',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'addtypetoheading' => $addtypetoheading
                )
            );
        }
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'lastlogin',
            get_string('userlastlogin', 'totara_reportbuilder'),
            // See MDL-22481 for why currentlogin is used instead of lastlogin.
            "$join.currentlogin",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp',
                'addtypetoheading' => $addtypetoheading
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'lastloginrelative',
            get_string('userlastloginrelative', 'totara_reportbuilder'),
            // See MDL-22481 for why currentlogin is used instead of lastlogin.
            "$join.currentlogin",
            array(
                'joins' => $join,
                'displayfunc' => 'relative_time_text',
                'dbdatatype' => 'timestamp',
                'addtypetoheading' => $addtypetoheading
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'firstaccess',
            get_string('userfirstaccess', 'totara_reportbuilder'),
            "$join.firstaccess",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
                'addtypetoheading' => $addtypetoheading
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'firstaccessrelative',
            get_string('userfirstaccessrelative', 'totara_reportbuilder'),
            "$join.firstaccess",
            array(
                'joins' => $join,
                'displayfunc' => 'relative_time_text',
                'dbdatatype' => 'timestamp',
                'addtypetoheading' => $addtypetoheading
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'lang',
            get_string('userlang', 'totara_reportbuilder'),
            "$join.lang",
            array(
                'joins' => $join,
                'displayfunc' => 'language_code',
                'addtypetoheading' => $addtypetoheading
            )
        );
        // auto-generate columns for user fields
        $fields = array(
            'firstname' => get_string('userfirstname', 'totara_reportbuilder'),
            'firstnamephonetic' => get_string('userfirstnamephonetic', 'totara_reportbuilder'),
            'middlename' => get_string('usermiddlename', 'totara_reportbuilder'),
            'lastname' => get_string('userlastname', 'totara_reportbuilder'),
            'lastnamephonetic' => get_string('userlastnamephonetic', 'totara_reportbuilder'),
            'alternatename' => get_string('useralternatename', 'totara_reportbuilder'),
            'username' => get_string('username', 'totara_reportbuilder'),
            'phone1' => get_string('userphone', 'totara_reportbuilder'),
            'institution' => get_string('userinstitution', 'totara_reportbuilder'),
            'department' => get_string('userdepartment', 'totara_reportbuilder'),
            'address' => get_string('useraddress', 'totara_reportbuilder'),
            'city' => get_string('usercity', 'totara_reportbuilder'),
        );
        foreach ($fields as $field => $name) {
            $columnoptions[] = new \rb_column_option(
                $groupname,
                $field,
                $name,
                "$join.$field",
                array('joins' => $join,
                      'displayfunc' => 'plaintext',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'addtypetoheading' => $addtypetoheading
                )
            );
        }

        $columnoptions[] = new \rb_column_option(
            $groupname,
            'idnumber',
            get_string('useridnumber', 'totara_reportbuilder'),
            "$join.idnumber",
            array('joins' => $join,
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'char',
                'outputformat' => 'text')
        );

        $columnoptions[] = new \rb_column_option(
            $groupname,
            'id',
            get_string('userid', 'totara_reportbuilder'),
            "$join.id",
            array('joins' => $join,
                  'displayfunc' => 'integer',
                  'addtypetoheading' => $addtypetoheading
            )
        );

        // add country option
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'country',
            get_string('usercountry', 'totara_reportbuilder'),
            "$join.country",
            array(
                'joins' => $join,
                'displayfunc' => 'country_code',
                'addtypetoheading' => $addtypetoheading
            )
        );

        // add auth option
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'auth',
            get_string('userauth', 'totara_reportbuilder'),
            "$join.auth",
            array(
                'joins' => $join,
                'displayfunc' => 'user_auth_method',
                'addtypetohead' => $addtypetoheading
            )
        );

        // add deleted option
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'deleted',
            get_string('userstatus', 'totara_reportbuilder'),
            "CASE WHEN $join.deleted = 0 AND $join.suspended = 0 AND $join.confirmed = 1 THEN 0
                WHEN $join.deleted = 1 THEN 1
                WHEN $join.suspended = 1 THEN 2
                WHEN $join.confirmed = 0 THEN 3
                ELSE 0
            END",
            array(
                'joins' => $join,
                'displayfunc' => 'user_status',
                'addtypetoheading' => $addtypetoheading
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'timecreated',
            get_string('usertimecreated', 'totara_reportbuilder'),
            "$join.timecreated",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
                'addtypetoheading' => $addtypetoheading
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'timemodified',
            get_string('usertimemodified', 'totara_reportbuilder'),
            // Check whether the user record has been updated since it was created.
            // The timecreated field is 0 for guest and admin accounts, so this guest
            // username can be used to identify them. The site admin's username can
            // be changed so this can't be relied upon.
            "CASE WHEN {$join}.username = 'guest' AND {$join}.timecreated = 0 THEN 0
                  WHEN {$join}.username != 'guest' AND {$join}.timecreated = 0 AND {$join}.firstaccess < {$join}.timemodified THEN {$join}.timemodified
                  WHEN {$join}.timecreated != 0 AND {$join}.timecreated < {$join}.timemodified THEN {$join}.timemodified
                  ELSE 0 END",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
                'addtypetoheading' => $addtypetoheading
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'totarasync',
            get_string('totarasyncenableduser', 'totara_reportbuilder'),
            "$join.totarasync",
            array(
                'joins' => $join,
                'displayfunc' => 'yes_or_no',
                'addtypetoheading' => $addtypetoheading
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'jobassignments',
            get_string('jobassignments', 'totara_job'),
            "(SELECT COUNT('x') FROM {job_assignment} ja WHERE ja.userid = $join.id)",
            array(
                'nosort' => true,
                'joins' => $join,
                'displayfunc' => 'user_jobassignments',
                'addtypetoheading' => $addtypetoheading,
                'extrafields' => array('userid' => "$join.id", 'deleted' => "$join.deleted"),
                'issubquery' => true,
                'deprecated' => true,
                'iscompound' => true,
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'jobpositionnames',
            get_string('usersposnameall', 'totara_reportbuilder'),
            "(SELECT " . $DB->sql_group_concat('p.fullname', ', ', 'p.fullname') . "
                FROM {pos} p
                JOIN {job_assignment} ja ON ja.positionid = p.id
               WHERE ja.userid = $join.id AND p.fullname IS NOT NULL)",
            array(
                'displayfunc' => 'format_string',
                'joins' => $join,
                'addtypetoheading' => $addtypetoheading,
                'issubquery' => true,
                'deprecated' => true,
                'iscompound' => true,
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'jobpositionidnumbers',
            get_string('usersposidnumberall', 'totara_reportbuilder'),
            "(SELECT " . $DB->sql_group_concat('p.idnumber', ', ', 'p.idnumber') . "
                FROM {pos} p
                JOIN {job_assignment} ja ON ja.positionid = p.id
               WHERE ja.userid = $join.id AND p.idnumber IS NOT NULL AND p.idnumber <> '')",
            array(
                'displayfunc' => 'plaintext',
                'joins' => $join,
                'addtypetoheading' => $addtypetoheading,
                'issubquery' => true,
                'deprecated' => true,
                'iscompound' => true,
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'joborganisationnames',
            get_string('usersorgnameall', 'totara_reportbuilder'),
            "(SELECT " . $DB->sql_group_concat('o.fullname', ', ', 'o.fullname') . "
                FROM {org} o
                JOIN {job_assignment} ja ON ja.organisationid = o.id
               WHERE ja.userid = $join.id AND o.fullname IS NOT NULL)",
            array(
                'displayfunc' => 'format_string',
                'joins' => $join,
                'addtypetoheading' => $addtypetoheading,
                'issubquery' => true,
                'deprecated' => true,
                'iscompound' => true,
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'joborganisationidnumbers',
            get_string('usersorgidnumberall', 'totara_reportbuilder'),
            "(SELECT " . $DB->sql_group_concat('o.idnumber', ', ', 'o.idnumber') . "
                FROM {org} o
                JOIN {job_assignment} ja ON ja.organisationid = o.id
               WHERE ja.userid = $join.id AND o.idnumber IS NOT NULL AND o.idnumber <> '')",
            array(
                'displayfunc' => 'plaintext',
                'joins' => $join,
                'addtypetoheading' => $addtypetoheading,
                'issubquery' => true,
                'deprecated' => true,
                'iscompound' => true,
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'jobmanagernames',
            get_string('usersmanagernameall', 'totara_reportbuilder'),
            "(SELECT " . $DB->sql_group_concat($DB->sql_concat_join("' '", array('m.firstname', 'm.lastname')), ', ', 'm.firstname') . "
                FROM {user} m
                JOIN {job_assignment} mja ON mja.userid = m.id
                JOIN {job_assignment} ja ON ja.managerjaid = mja.id
               WHERE ja.userid = $join.id)",
            array(
                'displayfunc' => 'plaintext',
                'joins' => $join,
                'addtypetoheading' => $addtypetoheading,
                'issubquery' => true,
                'deprecated' => true,
                'iscompound' => true,
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'jobappraisernames',
            get_string('usersappraisernameall', 'totara_reportbuilder'),
            "(SELECT " . $DB->sql_group_concat($DB->sql_concat_join("' '", array('a.firstname', 'a.lastname')), ', ', 'a.firstname') . "
                FROM {user} a
                JOIN {job_assignment} ja ON ja.appraiserid = a.id
               WHERE ja.userid = $join.id)",
            array(
                'displayfunc' => 'plaintext',
                'joins' => $join,
                'addtypetoheading' => $addtypetoheading,
                'issubquery' => true,
                'deprecated' => true,
                'iscompound' => true,
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'jobtempmanagernames',
            get_string('userstempmanagernameall', 'totara_reportbuilder'),
            "(SELECT " . $DB->sql_group_concat($DB->sql_concat_join("' '", array('m.firstname', 'm.lastname')), ', ', 'm.firstname') . "
                FROM {user} m
                JOIN {job_assignment} mja ON mja.userid = m.id
                JOIN {job_assignment} ja ON ja.tempmanagerjaid = mja.id
               WHERE ja.userid = $join.id AND ja.tempmanagerexpirydate > " . time() . ")", // This is not compatible with caching much!
            array(
                'displayfunc' => 'plaintext',
                'joins' => $join,
                'addtypetoheading' => $addtypetoheading,
                'issubquery' => true,
                'deprecated' => true,
                'iscompound' => true,
            )
        );
        $columnoptions[] = new \rb_column_option(
            $groupname,
            'usercohortids',
            get_string('usercohortids', 'totara_reportbuilder'),
            "(SELECT " . $DB->sql_group_concat('cm.cohortid', '|', 'cm.cohortid ASC') . "
                FROM {cohort_members} cm
               WHERE cm.userid = $join.id)",
            array(
                'joins' => $join,
                'selectable' => false,
                'issubquery' => true,
                'addtypetoheading' => $addtypetoheading
            )
        );
        return true;
    }

    /**
     * Adds some common user field to the $filteroptions array
     *
     * @param array  &$filteroptions   Array of current filter options
     *                                 Passed by reference and updated by
     *                                 this method
     * @param string $groupname        Name of group to filter. If you are defining
     *                                 a custom group name, you must define a language
     *                                 string with the key "type_{$groupname}" in your
     *                                 report source language file.
     * @param bool   $addtypetoheading Add the column type to the column heading
     *
     * @return True
     */
    protected function add_core_user_filters(&$filteroptions, $groupname = 'user', $addtypetoheading = false) {
        global $CFG;

        $found = false;
        foreach ($this->addeduserjoins as $join => $unused) {
            if (!isset($this->addeduserjoins[$join]['groupname'])) {
                continue;
            }
            if ($this->addeduserjoins[$join]['groupname'] === $groupname) {
                $this->addeduserjoins[$join]['filters'] = true;
                $found = true;
                break;
            }
        }
        if (!$found) {
            debugging("Add user join with group name '{$groupname}' via add_core_user_tables() before calling add_core_user_filters()", DEBUG_DEVELOPER);
            $join = null;
        }

        // auto-generate filters for user fields
        $fields = array(
            'fullname' => get_string('userfullname', 'totara_reportbuilder'),
            'firstname' => get_string('userfirstname', 'totara_reportbuilder'),
            'firstnamephonetic' => get_string('userfirstnamephonetic', 'totara_reportbuilder'),
            'middlename' => get_string('usermiddlename', 'totara_reportbuilder'),
            'lastname' => get_string('userlastname', 'totara_reportbuilder'),
            'lastnamephonetic' => get_string('userlastnamephonetic', 'totara_reportbuilder'),
            'alternatename' => get_string('useralternatename', 'totara_reportbuilder'),
            'username' => get_string('username'),
            'idnumber' => get_string('useridnumber', 'totara_reportbuilder'),
            'phone1' => get_string('userphone', 'totara_reportbuilder'),
            'institution' => get_string('userinstitution', 'totara_reportbuilder'),
            'department' => get_string('userdepartment', 'totara_reportbuilder'),
            'address' => get_string('useraddress', 'totara_reportbuilder'),
            'city' => get_string('usercity', 'totara_reportbuilder'),
            'email' => get_string('useremail', 'totara_reportbuilder'),
        );
        // Only include this filter if email is among fields allowed by showuseridentity setting or
        // if the current user has the 'moodle/site:config' capability.
        $canview = !empty($CFG->showuseridentity) && in_array('email', explode(',', $CFG->showuseridentity));
        $canview |= has_capability('moodle/site:config', \context_system::instance());
        if ($canview) {
            $fields['emailunobscured'] = get_string('useremailunobscured', 'totara_reportbuilder');
        }

        foreach ($fields as $field => $name) {
            $filteroptions[] = new \rb_filter_option(
                $groupname,
                $field,
                $name,
                'text',
                array('addtypetoheading' => $addtypetoheading)
            );
        }

        // pulldown with list of countries
        $select_width_options = \rb_filter_option::select_width_limiter();
        $filteroptions[] = new \rb_filter_option(
            $groupname,
            'country',
            get_string('usercountry', 'totara_reportbuilder'),
            'select',
            array(
                'selectchoices' => get_string_manager()->get_list_of_countries(),
                'attributes' => $select_width_options,
                'simplemode' => true,
                'addtypetoheading' => $addtypetoheading
            )
        );

        $filteroptions[] = new \rb_filter_option(
            $groupname,
            'auth',
            get_string('userauth', 'totara_reportbuilder'),
            "select",
            array(
                'selectchoices' => $this->rb_filter_auth_options(),
                'attributes' => $select_width_options,
                'addtypetoheading' => $addtypetoheading
            )
        );

        if ($this instanceof \rb_source_user) {
            // Deleted users are always excluded, we have a special deleted_users report now instead.
            $filteroptions[] = new \rb_filter_option(
                $groupname,
                'deleted',
                get_string('userstatus', 'totara_reportbuilder'),
                'select',
                array(
                    'selectchoices' => array(0 => get_string('activeuser', 'totara_reportbuilder'),
                        2 => get_string('suspendeduser', 'totara_reportbuilder'),
                        3 => get_string('unconfirmeduser', 'totara_reportbuilder'),
                    ),
                    'attributes' => $select_width_options,
                    'simplemode' => true,
                    'addtypetoheading' => $addtypetoheading
                )
            );
        } else {
            $filteroptions[] = new \rb_filter_option(
                $groupname,
                'deleted',
                get_string('userstatus', 'totara_reportbuilder'),
                'select',
                array(
                    'selectchoices' => array(0 => get_string('activeuser', 'totara_reportbuilder'),
                        1 => get_string('deleteduser', 'totara_reportbuilder'),
                        2 => get_string('suspendeduser', 'totara_reportbuilder'),
                        3 => get_string('unconfirmeduser', 'totara_reportbuilder'),
                    ),
                    'attributes' => $select_width_options,
                    'simplemode' => true,
                    'addtypetoheading' => $addtypetoheading
                )
            );
        }

        $filteroptions[] = new \rb_filter_option(
            $groupname,
            'lastlogin',
            get_string('userlastlogin', 'totara_reportbuilder'),
            'date',
            array(
                'addtypetoheading' => $addtypetoheading
            )
        );

        $filteroptions[] = new \rb_filter_option(
            $groupname,
            'lastloginrelative',
            get_string('userlastloginrelative', 'totara_reportbuilder'),
            'date',
            array(
                'includetime' => true,
                'includenotset' => true,
                'addtypetoheading' => $addtypetoheading
            )
        );

        $filteroptions[] = new \rb_filter_option(
            $groupname,
            'firstaccess',
            get_string('userfirstaccess', 'totara_reportbuilder'),
            'date',
            array(
                'includetime' => true,
                'includenotset' => true,
                'addtypetoheading' => $addtypetoheading
            )
        );

        $filteroptions[] = new \rb_filter_option(
            $groupname,
            'firstaccessrelative',
            get_string('userfirstaccessrelative', 'totara_reportbuilder'),
            'date',
            array(
                'includetime' => true,
                'includenotset' => true,
                'addtypetoheading' => $addtypetoheading
            )
        );

        $filteroptions[] = new \rb_filter_option(
            $groupname,
            'timecreated',
            get_string('usertimecreated', 'totara_reportbuilder'),
            'date',
            array(
                'includetime' => true,
                'addtypetoheading' => $addtypetoheading
            )
        );

        $filteroptions[] = new \rb_filter_option(
            $groupname,
            'timemodified',
            get_string('usertimemodified', 'totara_reportbuilder'),
            'date',
            array(
                'includetime' => true,
                'includenotset' => true,
                'addtypetoheading' => $addtypetoheading
            )
        );

        $filteroptions[] = new \rb_filter_option(
            $groupname,
            'totarasync',
            get_string('totarasyncenableduser', 'totara_reportbuilder'),
            'select',
            array(
                'selectchoices' => array(0 => get_string('no'), 1 => get_string('yes')),
                'simplemode' => true,
                'addtypetoheading' => $addtypetoheading
            )
        );

        // NOTE: this is a wrong place to use capability, anyway...
        if (has_capability('moodle/cohort:view', \context_system::instance())) {
            if ($join) {
                $filteroptions[] = new \rb_filter_option(
                    $groupname,
                    'usercohortids',
                    get_string('userincohort', 'totara_reportbuilder'),
                    'correlated_subquery_cohort',
                    array(
                        array('addtypetoheading' => $addtypetoheading),
                        'cachingcompatible' => false,
                    ),
                    "{$join}.id",
                    $join
                );
            }
        }

        return true;
    }

    /**
     * Adds user custom fields to the report.
     *
     * @param array  $joinlist         Array of current joins
     * @param array  $columnoptions    Array of current column options
     * @param array  $filteroptions    Array of current filter options
     * @param string $basejoin         Join table in joinlist used as a link to main query
     * @param string $groupname        Name of group to filter and add fields to
     * @param bool   $addtypetoheading Add the column type to the column heading
     * @param bool   $nofilter         Flag indicating if filter needs to be added
     *
     * @return boolean
     */
    protected function add_core_user_customfield(array &$joinlist, array &$columnoptions, array &$filteroptions,
                                                 $basejoin = 'auser', $groupname = 'user', $addtypetoheading = false, $nofilter = false) {
        global $DB;

        if (!empty($this->addeduserjoins[$basejoin]['processed'])) {
            // Already added.
            return false;
        }

        $jointable = false;
        if ($basejoin === 'base') {
            $jointable = $this->base;
        } else {
            foreach ($joinlist as $object) {
                if ($object->name === $basejoin) {
                    $jointable = $object->table;
                    break;
                }
            }
        }

        // Check if there are any visible custom fields of this type.
        $items = \totara_customfield\report_builder_field_loader::get_visible_fields('user');

        foreach ($items as $record) {
            $id = $record->id;
            $joinname = "{$basejoin}_cf_{$id}";
            $value = "custom_field_{$id}";
            $name = isset($record->fullname) ? $record->fullname : $record->name;

            $column_options = array();
            $column_options['joins'] = array($joinname);
            $column_options['extracontext'] = (array)$record;
            $column_options['addtypetoheading'] = $addtypetoheading;
            $column_options['displayfunc'] = 'userfield_' . $record->datatype;

            if ($record->visible != PROFILE_VISIBLE_ALL) {
                // If the field is not visible to all we need the userid to enable visibility checks.
                if ($jointable === '{user}') {
                    $column_options['extrafields'] = array('userid' => "{$basejoin}.id");
                } else {
                    $column_options['extrafields'] = array('userid' => "{$joinname}.userid");
                }
            }

            if ($record->visible == PROFILE_VISIBLE_NONE) {
                // If profile field isn't available to everyone require a capability to display the column.
                $column_options['capability'] = 'moodle/user:viewalldetails';
            }

            $filter_options = array();
            $filter_options['addtypetoheading'] = $addtypetoheading;

            $columnsql = "{$joinname}.data";

            switch ($record->datatype) {
                case 'textarea':
                    $column_options['extrafields']["format"] = "{$joinname}.dataformat";
                    $column_options['dbdatatype'] = 'text';
                    $column_options['outputformat'] = 'text';
                    $filtertype = 'textarea';
                    break;

                case 'menu':
                    $default = $record->defaultdata;
                    if ($default !== '' and $default !== null) {
                        // Note: there is no safe way to inject the default value into the query, use extra join instead.
                        $fieldjoin = $joinname . '_fielddefault';
                        $joinlist[] = new \rb_join(
                            $fieldjoin,
                            'INNER',
                            "{user_info_field}",
                            "{$fieldjoin}.id = {$id}",
                            REPORT_BUILDER_RELATION_MANY_TO_ONE
                        );
                        $columnsql = "COALESCE({$columnsql}, {$fieldjoin}.defaultdata)";
                        $column_options['joins'][] = $fieldjoin;
                    }
                    $column_options['dbdatatype'] = 'text';
                    $column_options['outputformat'] = 'text';
                    $filtertype = 'menuofchoices';
                    $filter_options['selectchoices'] = $this->list_to_array($record->param1,"\n");
                    $filter_options['simplemode'] = true;
                    break;

                case 'checkbox':
                    $default = (int)$record->defaultdata;
                    $columnsql = "CASE WHEN ( {$columnsql} IS NULL OR {$columnsql} = '' ) THEN {$default} ELSE " . $DB->sql_cast_char2int($columnsql, true) . " END";
                    $filtertype = 'select';
                    $filter_options['selectchoices'] = array(0 => get_string('no'), 1 => get_string('yes'));
                    $filter_options['simplemode'] = true;
                    break;

                case 'datetime':
                    $columnsql = "CASE WHEN {$columnsql} = '' THEN NULL ELSE " . $DB->sql_cast_char2int($columnsql, true) . " END";
                    $column_options['dbdatatype'] = 'timestamp';
                    $filtertype = 'date';
                    if ($record->param3) {
                        $filter_options['includetime'] = true;
                    }
                    break;

                case 'date': // Midday in UTC, date without timezone.
                    $columnsql = "CASE WHEN {$columnsql} = '' THEN NULL ELSE " . $DB->sql_cast_char2int($columnsql, true) . " END";
                    $column_options['dbdatatype'] = 'timestamp';
                    $filtertype = 'date';
                    break;

                case 'text':
                    $default = $record->defaultdata;
                    if ($default !== '' and $default !== null) {
                        // Note: there is no safe way to inject the default value into the query, use extra join instead.
                        $fieldjoin = $joinname . '_fielddefault';
                        $joinlist[] = new \rb_join(
                            $fieldjoin,
                            'INNER',
                            "{user_info_field}",
                            "{$fieldjoin}.id = {$id}",
                            REPORT_BUILDER_RELATION_MANY_TO_ONE
                        );
                        $columnsql = "COALESCE({$columnsql}, {$fieldjoin}.defaultdata)";
                        $column_options['joins'][] = $fieldjoin;
                    }
                    $column_options['dbdatatype'] = 'text';
                    $column_options['outputformat'] = 'text';
                    $filtertype = 'text';
                    break;

                default:
                    // Unsupported customfields.
                    continue 2;
            }

            $joinlist[] = new \rb_join(
                $joinname,
                'LEFT',
                "{user_info_data}",
                "{$joinname}.userid = {$basejoin}.id AND {$joinname}.fieldid = {$id}",
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                $basejoin
            );
            $columnoptions[] = new \rb_column_option(
                $groupname,
                $value,
                $name,
                $columnsql,
                $column_options
            );

            if (!$nofilter) {
                $filteroptions[] = new \rb_filter_option(
                    $groupname,
                    $value,
                    $name,
                    $filtertype,
                    $filter_options
                );
            }
        }

        return true;
    }

    /**
     * Adds the basic user based content options
     *      - Manager
     *      - Position
     *      - Organisation
     *
     * @param array $contentoptions     The sources content options array
     * @param string $join              The name of the user table in the report
     * @return boolean
     */
    protected function add_basic_user_content_options(&$contentoptions, $join = 'auser') {
        // Add the manager/staff content options.
        $contentoptions[] = new \rb_content_option(
            'user',
            get_string('user', 'rb_source_user'),
            "{$join}.id",
            "{$join}"
        );
        // Add the position content options.
        $contentoptions[] = new \rb_content_option(
            'current_pos',
            get_string('currentpos', 'totara_reportbuilder'),
            "{$join}.id",
            "{$join}"
        );
        // Add the organisation content options.
        $contentoptions[] = new \rb_content_option(
            'current_org',
            get_string('currentorg', 'totara_reportbuilder'),
            "{$join}.id",
            "{$join}"
        );

        // Add audience content options.
        $contentoptions[] = new \rb_content_option(
            'audience',
            get_string('audience', 'rb_source_user'),
            "{$join}.id",
            "{$join}"
        );

        return true;
    }
}
