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
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @package totara
 * @subpackage totara_core/dialogs
 */

defined('TOTARA_DIALOG_SEARCH') || die();

require_once("{$CFG->dirroot}/totara/core/dialogs/search_form.php");
require_once("{$CFG->dirroot}/totara/core/dialogs/dialog_content_hierarchy.class.php");
require_once($CFG->dirroot . '/totara/core/searchlib.php');

global $DB, $OUTPUT, $USER;

// Get parameter values
$query      = optional_param('query', null, PARAM_TEXT); // search query
$page       = optional_param('page', 0, PARAM_INT); // results page number
$searchtype = $this->searchtype;

// Trim whitespace off search query
$query = trim($query);

// This url
$data = array(
    'search'        => true,
    'query'         => $query,
    'searchtype'    => $searchtype,
    'page'          => $page,
    'sesskey'       => sesskey()
);
$thisurl = new moodle_url(strip_querystring(qualified_me()), array_merge($data, $this->urlparams));

// Extra form data
$formdata = array(
    'hidden'        => $this->urlparams,
    'query'         => $query,
    'searchtype'    => $searchtype
);


// Generate SQL
// Search SQL information
$search_info = new stdClass();
$search_info->id = 'id';
$search_info->fullname = 'fullname';
$search_info->sql = null;
$search_info->params = null;
$search_info->extrafields=null;

// Check if user has capability to view emails.
if (isset($this->context)) {
    $context = $this->context;
} else {
    $context = context_system::instance();
}
$canviewemail = in_array('email', get_extra_user_fields($context));
// Maybe we'll use context again later, but with there being different requirements for each $searchtype,
// we're unsetting it and leaving each search type to get the context in their own way.
unset($context);

/**
 * Use whitelist for table to prevent people messing with the query
 * Required variables from each case statement:
 *  + $search_info->id: Title of id field (defaults to 'id')
 *  + $search_info->fullname: Title of fullname field (defaults to 'fullname')
 *  + $search_info->sql: SQL after "SELECT .." fragment (e,g, 'FROM ... etc'), without the ORDER BY
 *  + $search_info->order: The "ORDER BY" SQL fragment (should contain the ORDER BY text also)
 *  + $search_info->extrafields: The extra table's fields that should be added into the sql if the search sql needs the
 *                               fields for different purposes, for example: DISTINCT sql needs the fields to appear in
 *                               SELECT when fields were being used as for SORT ORDERED
 *
 *  Remember to generate and include the query SQL in your WHERE clause with:
 *     totara_dialog_get_search_clause()
 */
switch ($searchtype) {
    /**
     * User search
     */
    case 'user':
        // Grab data from dialog object
        if (isset($this->customdata['current_user'])) {
            $userid = $this->customdata['current_user'];
            $formdata['hidden']['userid'] = $userid;
        }

        // Generate search SQL
        $keywords = totara_search_parse_keywords($query);
        $fields = get_all_user_name_fields();

        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $canviewemail ? array_merge ($fields, array('email' => 'email')) : $fields);
        $search_info->fullnamefields = implode(',', $fields);
        if ($canviewemail) {
            $search_info->email = 'email';
        }

        // exclude deleted, guest users and self
        $guest = guest_user();

        $search_info->sql = "
            FROM
                {user}
            WHERE
                {$searchsql}
                AND deleted = 0
                AND suspended = 0
                AND id != ?
        ";
        $params[] = $guest->id;

        if (isset($this->customdata['current_user'])) {
            $search_info->sql .= " AND id <> ?";
            $params[] = $userid;
        }

        $search_info->order = " ORDER BY firstname, lastname, email";
        $search_info->params = $params;
        break;

    /**
     * All users search, including the current user.
     */
    case 'users':

        // Generate search SQL
        $keywords = totara_search_parse_keywords($query);
        $fields = get_all_user_name_fields();

        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $canviewemail ? array_merge ($fields, array('email' => 'email')) : $fields);
        $search_info->fullnamefields = implode(',', $fields);
        if ($canviewemail) {
            $search_info->email = 'email';
        }

        // exclude deleted, guest users and self
        $guest = guest_user();

        $search_info->sql = "
            FROM
                {user}
            WHERE
                {$searchsql}
                AND deleted = 0
                AND suspended = 0
                AND id != ?
        ";
        $params[] = $guest->id;

        $search_info->order = " ORDER BY firstname, lastname, email";
        $search_info->params = $params;
        break;


    /**
     * Hierarchy search
     */
    case 'hierarchy':
        if (method_exists($this, 'put_search_params')) {
            $this->put_search_params($formdata);
        }
        // Grab data from dialog object
        $prefix = $this->hierarchy->prefix;
        $frameworkid = $this->frameworkid;
        $requireevidence = $this->requireevidence;
        $shortprefix = hierarchy::get_short_prefix($this->hierarchy->prefix);
        $formdata['hierarchy'] = $this->hierarchy;
        $formdata['hidden']['prefix'] = $prefix;
        $formdata['showpicker'] = !$this->disable_picker;
        $formdata['showhidden'] = $showhidden = $this->showhidden;
        $formdata['frameworkid'] = $frameworkid;

        // Generate search SQL
        $keywords = totara_search_parse_keywords($query);
        $fields = array('i.fullname', 'i.shortname', 'i.description', 'i.idnumber');
        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $fields);

        $search_info->id = 'i.id';
        $search_info->fullname = 'CASE WHEN (i.idnumber IS NULL OR i.idnumber = \'\' OR i.idnumber = \'0\') THEN i.fullname ELSE '. $DB->sql_concat('i.fullname', "' ('", 'i.idnumber', "')'").' END';

        $search_info->sql = "
            FROM
                {{$shortprefix}} i
            JOIN
                {{$shortprefix}_framework} f
             ON i.frameworkid = f.id
            WHERE
                {$searchsql}
            AND i.visible = 1
        ";

        // Restrict by framework if required
        if ($frameworkid) {
            $search_info->sql .= " AND i.frameworkid = ? ";
            $params[] = $frameworkid;
        }

        // Don't show hidden frameworks
        if (!$showhidden) {
            $search_info->sql .= ' AND f.visible = 1 ';
        }

        // Only show hierarchy items with evidence
        if ($requireevidence) {
            $search_info->sql .= ' AND i.evidencecount > 0 ';
        }

        if (isset($this->customdata['current_item_id'])) {
            $search_info->sql .= "
                AND i.id <> ?
                ";
            $params[] = $this->customdata['current_item_id'];
        }

        $search_info->order = " ORDER BY i.frameworkid, i.sortthread";
        $search_info->params = $params;
        break;


    /**
     * Course (with completion enabled) search
     */
    case 'coursecompletion':
        // Generate search SQL
        $keywords = totara_search_parse_keywords($query);
        $fields = array('c.fullname', 'c.shortname');
        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $fields, SQL_PARAMS_NAMED);

        $search_info->id = 'c.id';
        $search_info->fullname = 'c.fullname';

        $search_info->sql = "
            FROM
                {course} c
            LEFT JOIN
                {context} ctx
              ON c.id = ctx.instanceid AND contextlevel = ". CONTEXT_COURSE . " ";

        if ($this->requirecompletioncriteria) {
            $search_info->sql .= "
                LEFT JOIN
                    {course_completion_criteria} ccc
                 ON ccc.course = c.id
            ";
        }

        $search_info->sql .= " WHERE {$searchsql} ";
        list($visibilitysql, $visibilityparams) = totara_visibility_where($USER->id, 'c.id', 'c.visible', 'c.audiencevisible');
        $search_info->sql .= " AND {$visibilitysql}";
        $params = array_merge($params, $visibilityparams);

        if ($this->requirecompletion || $this->requirecompletioncriteria) {
            $search_info->sql .= "
                AND c.enablecompletion = :enablecompletion
            ";
            $params['enablecompletion'] = COMPLETION_ENABLED;

            if ($this->requirecompletioncriteria) {
                $search_info->sql .= "
                    AND ccc.id IS NOT NULL
                ";
            }
        }
        //always exclude site course
        $search_info->sql .= " AND c.id <> :siteid";
        $params['siteid'] = SITEID;
        $search_info->order = " ORDER BY c.sortorder ASC";
        $search_info->params = $params;
        break;


    /**
     * Program or certification search.
     */
    case 'program':
    case 'certification':
        // Generate search SQL
        $search_info->id = 'p.id';
        $keywords = totara_search_parse_keywords($query);
        $fields = array('p.fullname', 'p.shortname');
        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $fields, SQL_PARAMS_NAMED);
        list($visibilitysql, $visibilityparams) = totara_visibility_where(null,
                                                                          'p.id',
                                                                          'p.visible',
                                                                          'p.audiencevisible',
                                                                          'p',
                                                                          $searchtype);
        $search_info->sql = "
            FROM
                {prog} p
            LEFT JOIN
                {context} ctx
              ON p.id = ctx.instanceid AND contextlevel = " . CONTEXT_PROGRAM . "
            WHERE
                  {$searchsql}
              AND {$visibilitysql}
        ";
        $params = array_merge($params, $visibilityparams);

        // Adjust the SQL for programs or certifications.
        $search_info->sql .= " AND certifid IS " . ($searchtype == 'program' ? 'NULL' : 'NOT NULL');

        $search_info->order = " ORDER BY p.sortorder ASC";
        $search_info->params = $params;
        break;

    /**
     * Cohort search
     */
    case 'cohort':
        if (!empty($this->customdata['instancetype'])) {
            $formdata['hidden']['instancetype'] = $this->customdata['instancetype'];
        }
        if (!empty($this->customdata['instanceid'])) {
            $formdata['hidden']['instanceid'] = $this->customdata['instanceid'];
        }
        // Generate search SQL.
        $keywords = totara_search_parse_keywords($query);
        $fields = array('idnumber', 'name');
        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $fields);

        // Include only contexts at and above the current where user has cohort:view capability.
        $context = context_system::instance();
        if (!empty($this->customdata['instancetype']) and !empty($this->customdata['instanceid'])) {
            $instancetype = $this->customdata['instancetype'];
            $instanceid = $this->customdata['instanceid'];
            if ($instancetype == COHORT_ASSN_ITEMTYPE_COURSE) {
                $context = context_course::instance($instanceid);
            } else if ($instancetype == COHORT_ASSN_ITEMTYPE_CATEGORY) {
                $context = context_coursecat::instance($instanceid);
            } else if ($instancetype == COHORT_ASSN_ITEMTYPE_PROGRAM || $instancetype == COHORT_ASSN_ITEMTYPE_CERTIF) {
                $context = context_program::instance($instanceid);
            }
        }
        $contextids = array_filter($context->get_parent_context_ids(true),
            function($a) {return has_capability("moodle/cohort:view", context::instance_by_id($a));});
        $equal = true;
        if (!isset($instanceid) && $contextids) {
            // User passed capability check, search through entire cohort including all contextids.
            $contextids = array('0');
            $equal = false;
        }

        $search_info->fullname = "(
            CASE WHEN {cohort}.idnumber IS NULL
                OR {cohort}.idnumber = ''
                OR {cohort}.idnumber = '0'
            THEN
                {cohort}.name
            ELSE " .
                $DB->sql_concat("{cohort}.name", "' ('", "{cohort}.idnumber", "')'") .
            "END)";
        $search_info->sql = "
            FROM
                {cohort}
            WHERE
                {$searchsql}
        ";

        if (!empty($contextids)) {
            list($contextssql, $contextparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_QM, 'param', $equal);
            $search_info->sql .= $searchsql ? "AND {cohort}.contextid {$contextssql}" : "{cohort}.contextid {$contextssql}";
            $params = array_merge($params, $contextparams);
        }

        if (!empty($this->customdata['current_cohort_id'])) {
            $search_info->sql .= ' AND {cohort}.id != ? ';
            $params[] = $this->customdata['current_cohort_id'];
        }
        $search_info->order = ' ORDER BY name ASC';
        $search_info->params = $params;
        break;

    /**
     * Manager search
     */
    case 'manager':
        // Generate search SQL.
        $keywords = totara_search_parse_keywords($query);
        $fields = get_all_user_name_fields(false, '','manager.');

        if ($canviewemail) {
            $allfields = array_merge ($fields, array('email' => 'manager.email'));
        } else {
            $allfields = $fields;
        }

        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $allfields);
        $search_info->id = 'managerja.userid';
        $search_info->fullnamefields = implode(',', $fields);
        if ($canviewemail) {
            $search_info->email = 'manager.email';
        }
        $search_info->sql = "
            FROM {job_assignment} managerja
            JOIN {job_assignment} staffja ON staffja.managerjaid = managerja.id
            JOIN {user} manager ON managerja.userid = manager.id
           WHERE {$searchsql}
        ";
        $search_info->order = "
           GROUP BY managerja.userid, " . implode(',', $allfields) . ", manager.id
           ORDER BY manager.firstname, manager.lastname, manager.id
        ";
        $search_info->params = $params;
        break;

    /**
     * For selecting a manager and position. This will show all users (apart from guest, deleted etc).
     * Each can be expanded to reveal their positions which are selectable.
     */
    case 'assign_manager':
        list($sql, $params) = $this->get_managers_joinsql_and_params(true);

        $fields = get_all_user_name_fields(false, 'u', null, null, true);

        if ($canviewemail) {
            $allfields = array_merge ($fields, array('email' => 'u.email'));
        } else {
            $allfields = $fields;
        }

        $search_info->id = 'COALESCE((' . $DB->sql_concat_join('\'-\'', array('u.id', 'managerja.id')) . '), '
            . $DB->sql_concat('u.id', '\'-\'') . ')';
        $search_info->fullnamefields = 'managerja.fullname, managerja.idnumber, u.id AS userid, managerja.id AS jaid, ' . implode(',', $allfields);

        $keywords = totara_search_parse_keywords($query);
        list($searchsql, $searchparams) = totara_search_get_keyword_where_clause($keywords, $allfields, SQL_PARAMS_NAMED, 'u');

        $sql .= ' AND ' . $searchsql;
        $params = array_merge($params, $searchparams);
        $search_info->sql = $sql;
        $search_info->params = $params;
        $search_info->order = ' ORDER BY ' . implode(',', $fields);
        $search_info->datakeys = array('userid', 'jaid', 'displaystring');

        break;

    /**
     * Evidence search
     */
    case 'dp_plan_evidence':
        // Generate search SQL
        $keywords = totara_search_parse_keywords($query);
        $fields = array('e.name', 'eid.data');
        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $fields);

        $search_info->id = 'e.id';
        $search_info->fullname = 'e.name';
        $search_info->sql = "
              FROM {dp_plan_evidence} e
         LEFT JOIN {dp_plan_evidence_info_field} eif
                ON eif.shortname = 'evidencedescription'
         LEFT JOIN {dp_plan_evidence_info_data} eid
                ON eid.fieldid = eif.id AND eid.evidenceid = e.id
             WHERE {$searchsql}
               AND e.userid = ?
        ";
        // This query is weird. It first joins the main table to every field record which is a description (if it exists),
        // then the second join joins on both the main record's id and the field's id.
        // TODO TL-10834 "evidencedescription" is a custom field, so can be removed or renamed, and some other field could
        // even be renamed to this name. Might make more sense to be searching all text/textarea custom fields instead?

        $search_info->order = " ORDER BY e.name";
        if (!empty($this->customdata['userid'])) {
            $params[] = $this->customdata['userid'];
        } else {
            $params[] = $USER->id;
        }

        $search_info->params = $params;
        break;

    /**
     * Facetoface asset search
     */
    case 'facetoface_asset':
        $sessionid = $this->customdata['sessionid'];

        $formdata['hidden']['facetofaceid'] = $this->customdata['facetofaceid'];
        $formdata['hidden']['sessionid'] = $sessionid;
        $formdata['hidden']['timestart'] = $this->customdata['timestart'];
        $formdata['hidden']['timefinish'] = $this->customdata['timefinish'];
        $formdata['hidden']['selected'] = $this->customdata['selected'];
        $formdata['hidden']['offset'] = $this->customdata['offset'];

        // Generate search SQL.
        $keywords = totara_search_parse_keywords($query);
        $fields = array('a.name');
        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $fields);

        // Custom assets for session id.
        $sqlsess = '';
        $joinsess = '';
        if ($sessionid) {
            $joinsess = 'LEFT JOIN {facetoface_asset_dates} fad ON (a.id = fad.assetid)
                LEFT JOIN {facetoface_sessions_dates} fsd ON (fad.sessionsdateid = fsd.id)';
            $sqlsess = 'OR a.custom > 0 AND fsd.id = ?';
            $params = array_merge($params, array($sessionid));
        }
        $search_info->id = 'DISTINCT a.id';
        $search_info->fullname = 'a.name';
        $search_info->sql = "
            FROM {facetoface_asset} a
            {$joinsess}
            WHERE
            {$searchsql}
            AND (a.custom = 0 {$sqlsess})
            AND a.hidden = 0
        ";

        $search_info->order = " ORDER BY a.name ASC";
        $search_info->params = $params;
        $search_info->extrafields = "a.name, a.custom";
        $search_info->name = 'a.name';
        $search_info->custom = 'a.custom';
        $search_info->datakeys = array('id', 'name', 'custom');
        break;

    /**
     * Facetoface room search
     */
    case 'facetoface_room':
        $formdata['hidden']['facetofaceid'] = $this->customdata['facetofaceid'];
        $formdata['hidden']['sessionid'] = $this->customdata['sessionid'];
        $formdata['hidden']['timestart'] = $this->customdata['timestart'];
        $formdata['hidden']['timefinish'] = $this->customdata['timefinish'];
        $formdata['hidden']['selected'] = $this->customdata['selected'];
        $formdata['hidden']['offset'] = $this->customdata['offset'];

        $sessionid = $this->customdata['sessionid'];
        // Generate search SQL
        $keywords = totara_search_parse_keywords($query);
        $fields = array('r.name');
        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $fields, SQL_PARAMS_NAMED);

        // Custom rooms for session id.
        $sqlsess = '';

        // The logic of checking whether the room was already within a sesison or not is working by checking the `timestart`
        // of the queried rooms to be smaller than the `timefinish` from POST data and the `timefinish` to be bigger
        // than `timestart` from POST data
        $joinsess = 'LEFT JOIN {facetoface_sessions_dates} fsd ON (r.id = fsd.roomid)
                     AND (fsd.timestart < :timestart AND fsd.timefinish > :timefinish
                     AND r.allowconflicts = 0 %sessionsql%)';

        // Since we are allowing the custom room created to be used in the same seminar but different
        // event. Therefore, we need to exclude those custom rooms that have been used by different
        // seminars.
        $joinsess .= ' LEFT JOIN (
            SELECT r2.id FROM {facetoface_room} r2
            INNER JOIN {facetoface_sessions_dates} fsd2 ON fsd2.roomid = r2.id
            INNER JOIN {facetoface_sessions} fs ON fs.id = fsd2.sessionid
            WHERE r2.custom = 1 AND fs.facetoface <> :facetofaceid
        ) AS usedrooms ON usedrooms.id = r.id ';

        // Parameter for checking against `timestart`
        $params['timestart'] = $this->customdata['timefinish'];
        // Parameter for checking against `timefinish`
        $params['timefinish'] = $this->customdata['timestart'];
        $params['facetofaceid'] = $this->customdata['facetofaceid'];

        // This is a small part of sql for getting those rooms that are not being used by the same session
        $sessionsql = "";
        if ($sessionid) {
            $sqlsess = 'OR fsd.sessionid = :sessionid1 ';
            $params['sessionid1'] = $sessionid;

            // Logic in english: The room that is being used by the same session should not be displayed as unavailable,
            // however if the room is being used by a different session, then it should have a flag of unavailable up
            $sessionsql = " AND fsd.sessionid <> :sessionid2 ";
            $params['sessionid2'] = $this->customdata['sessionid'];
        }

        // Update the sql
        $joinsess = str_replace('%sessionsql%', $sessionsql, $joinsess);

        $search_info->id = 'DISTINCT r.id';
        // This field is required within SELECT, sinc the DISTINCT keyword onlys work well with ORDER BY key word if the
        // the field is ordered also selected
        $search_info->extrafields = "r.name, r.capacity, r.custom";
        $sqlavailable = $DB->sql_concat("r.name", "' (" . get_string('capacity', 'facetoface') . ": '", "r.capacity", "')'");
        $sqlunavailable = $DB->sql_concat("r.name",
            "' (" . get_string('capacity', 'facetoface') . ": '", "r.capacity", "')'",
            "' " . get_string('roomalreadybooked', 'facetoface') . "'"
        );
        $search_info->fullname = " CASE WHEN(fsd.id IS NULL) THEN {$sqlavailable} ELSE {$sqlunavailable} END";

        $search_info->sql = "
            FROM
                {facetoface_room} r
                {$joinsess}
            WHERE
                {$searchsql}
                AND (r.custom=0 OR fsd.id IS NULL {$sqlsess})
                AND r.hidden = 0
                AND usedrooms.id IS NULL
        ";

        $search_info->order = " ORDER BY r.name ASC";
        $search_info->params = $params;
        $search_info->name = 'r.name';
        $search_info->capacity = 'r.capacity';
        $search_info->custom = 'r.custom';
        $search_info->datakeys = array('id', 'name', 'custom', 'capacity');
        break;

    case 'temporary_manager':
        // Generate search SQL.
        $keywords = totara_search_parse_keywords($query);
        $fields = get_all_user_name_fields(false, '','u.');
        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $canviewemail ? array_merge ($fields, array('email' => 'u.email')) : $fields);

        // Generate manager sql.
        $managersql = '';
        if ($CFG->tempmanagerrestrictselection) {
            // Current managers.
            $managersql = "AND u.id IN (
                                SELECT DISTINCT managerja.userid
                                  FROM {job_assignment} managerja
                                  JOIN {job_assignment} staffja ON staffja.managerjaid = managerja.id
                                  )";
        }

        $search_info->id = 'u.id';
        $search_info->fullnamefields = implode(',', $fields);
        if ($canviewemail) {
            $search_info->email = 'u.email';
        }
        $search_info->sql = "FROM {user} u
                            WHERE {$searchsql} {$managersql}
                              AND u.deleted = 0
                              AND u.suspended = 0
                              AND u.id NOT IN (?, ?)";
        $params[] = $this->customdata['current_user'];
        $params[] = $this->customdata['current_manager'];
        $search_info->order = " ORDER BY u.firstname, u.lastname";
        $search_info->params = $params;
        break;

    /**
     * Badge search
     */
    case 'badge':
        // Generate search SQL
        $keywords = totara_search_parse_keywords($query);
        $fields = array('name', 'description', 'issuername');
        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $fields);

        $search_info->fullname = "{badge}.name";
        $search_info->sql = "
            FROM
                {badge}
            WHERE
                {$searchsql}
        ";
        $search_info->order = ' ORDER BY name ASC';
        $search_info->params = $params;
        break;

    /*
     * Category search.
     */
    case 'category':
        // Generate search SQL.
        $keywords = totara_search_parse_keywords($query);
        $fields = array('name');
        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $fields);

        $search_info->fullname = 'c.name';
        $search_info->sql = "
            FROM
                {course_categories} c
            WHERE
                {$searchsql}
        ";
        $search_info->order = ' ORDER BY name ASC';
        $search_info->params = $params;
        break;

    /*
     * Course search.
     */
    case 'course':
        $formdata['hidden']['instanceid'] = $this->customdata['instanceid'];

        // Generate search SQL.
        $keywords = totara_search_parse_keywords($query);
        $fields = array('fullname', 'shortname', 'idnumber');
        list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $fields);

        $search_info->fullname = 'c.fullname';
        $search_info->sql = "
            FROM
                {course} c
            WHERE c.category > 0 AND
                {$searchsql}
        ";
        $search_info->order = ' ORDER BY c.fullname ASC';
        $search_info->params = $params;
        break;

    case 'this':
        $keywords = totara_search_parse_keywords($query);
        $this->put_search_info($search_info, $formdata, $keywords);
        break;

    default:
        print_error('invalidsearchtable', 'totara_core');
}

// Generate forn markup
// Create form
$mform = new dialog_search_form(null, $formdata);

// Display form
$mform->display();


// Generate results
if (strlen($query)) {

    $strsearch = get_string('search');
    $strqueryerror = get_string('queryerror', 'totara_core');
    $start = $page * DIALOG_SEARCH_NUM_PER_PAGE;

    $select = "SELECT {$search_info->id} AS id ";
    if (isset($search_info->fullnamefields)) {
        $select .= ", {$search_info->fullnamefields} ";
    } else if (isset($search_info->fullname)) {
        $select .= ", {$search_info->fullname} AS fullname ";
    }
    if (isset($search_info->email)) {
        $select .= ", {$search_info->email} AS email ";
    }
    if (isset($search_info->extrafields)) {
        $select .= ", {$search_info->extrafields} ";
    }
    $count  = "SELECT COUNT({$search_info->id}) ";

    $total = $DB->count_records_sql($count.$search_info->sql, $search_info->params);
    if ($total) {
        $results = $DB->get_records_sql(
            $select.$search_info->sql.$search_info->order,
            $search_info->params,
            $start,
            DIALOG_SEARCH_NUM_PER_PAGE
        );
    }

    if ($total) {
        if ($results) {
            $pagingbar = new paging_bar($total, $page, DIALOG_SEARCH_NUM_PER_PAGE, $thisurl);
            $pagingbar->pagevar = 'page';
            $output = $OUTPUT->render($pagingbar);
            echo html_writer::tag('div',$output, array('class' => "search-paging"));

            // Generate some treeview data
            $dialog = new totara_dialog_content();
            $dialog->items = array();
            $dialog->parent_items = array();
            if (isset($search_info->datakeys)) {
                $dialog->set_datakeys($search_info->datakeys);
            }

            if (method_exists($this, 'get_search_items_array')) {
                $dialog->items = $this->get_search_items_array($results);
            } else {
                foreach ($results as $result) {
                    if (method_exists($this, 'search_can_display_result') && !$this->search_can_display_result($result->id)) {
                        continue;
                    }

                    // Add datakey attributes to item.
                    $item = new stdClass();
                    foreach ($this->datakeys as $key) {
                        $item->$key = $result->$key;
                    }

                    $item->id = $result->id;
                    if (isset($result->email)) {
                        $username = new stdClass();
                        $username->fullname = isset($result->fullname) ? $result->fullname : fullname($result);
                        $username->email = $result->email;
                        $item->fullname = get_string('assignindividual', 'totara_program', $username);
                    } else {
                        if (isset($result->fullname)) {
                            $item->fullname = format_string($result->fullname);
                        } else {
                            $item->fullname = format_string(fullname($result));
                        }
                    }

                    if (method_exists($this, 'search_get_item_hover_data')) {
                        $item->hover = $this->search_get_item_hover_data($item->id);
                    }

                    $dialog->items[$item->id] = $item;
                }
            }

            $dialog->disabled_items = $this->disabled_items;
            echo $dialog->generate_treeview();

        } else {
            // if count succeeds, query shouldn't fail
            // must be something wrong with query
            print $strqueryerror;
        }
    } else {
        $params = new stdClass();
        $params->query = $query;

        $message = get_string('noresultsfor', 'totara_core', $params);

        if (!empty($frameworkid)) {
            $params->framework = $DB->get_field($shortprefix.'_framework', 'fullname', array('id' => $frameworkid));
            $message = get_string('noresultsforinframework', 'totara_hierarchy', $params);
        }

        echo html_writer::tag('p', $message, array('class' => 'message'));
    }
}
