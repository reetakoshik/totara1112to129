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
 * @package totara
 * @subpackage totara_coursecatalog
 */

// Require MOODLE_INTERNAL.
defined('MOODLE_INTERNAL') || die();

/**
 * Get the number of visible items in or below the selected categories
 *
 * This function counts the number of items within a set of categories, only including
 * items that are visible to the user.
 *
 * By default returns the course count, but will work for programs, certifications too.
 *
 * We need to jump through some hoops to do this efficiently:
 *
 * - To avoid having to do it recursively it relies on the context
 *   path to find courses within a category
 *
 * - To avoid having to check capabilities for every item it only
 *   checks hidden courses, and only if user isn't a siteadmin
 *
 * @param integer|array $categoryids ID or IDs of the category/categories to fetch
 * @param boolean $viewtype  - type of item to count: course,program,certification
 *
 * @return integer|array Associative array, where keys are the sub-category IDs and value is the count.
 * If $categoryids is a single integer, just returns the count as an integer
 */
function totara_get_category_item_count($categoryids, $viewtype = 'course') {
    global $CFG, $USER, $DB;
    require_once($CFG->dirroot . '/totara/cohort/lib.php');

    list($insql, $params) = $DB->get_in_or_equal(is_array($categoryids) ? $categoryids : array($categoryids));

    if (!$categories = $DB->get_records_select('course_categories', "id $insql", $params)) {
        return array();
    }

    // What items are we counting, courses, programs, or certifications?
    switch ($viewtype) {
        case 'course':
            $itemtable = "{course}";
            $itemcontext = CONTEXT_COURSE;
            $itemalias = 'c';
            $extrawhere = '';
            break;
        case 'program':
            $itemtable = "{prog}";
            $itemcontext = CONTEXT_PROGRAM;
            $itemalias = 'p';
            $extrawhere = " AND certifid IS NULL";
            break;
        case 'certification':
            $itemtable = "{prog}";
            $itemcontext = CONTEXT_PROGRAM;
            $itemalias = 'p';
            $extrawhere = " AND certifid IS NOT NULL";
            break;
        default:
            print_error('invalid viewtype');
    }

    list($insql, $inparams) = $DB->get_in_or_equal(array_keys($categories), SQL_PARAMS_NAMED);
    $sql = "SELECT instanceid, path
              FROM {context}
             WHERE contextlevel = :contextlvl
               AND instanceid {$insql}
             ORDER BY depth DESC";
    $params = array('contextlvl' => CONTEXT_COURSECAT);
    $params = array_merge($params, $inparams);

    $contextpaths = $DB->get_records_sql_menu($sql, $params);

    // Builds a WHERE snippet that matches any items inside the sub-category.
    // This won't match the category itself (because of the trailing slash),
    // But that's okay as we're only interested in the items inside.
    $contextwhere = array(); $contextparams = array();
    foreach ($contextpaths as $path) {
        $paramalias = $DB->get_unique_param('ctx');
        $contextwhere[] = $DB->sql_like('ctx.path', ":{$paramalias}");
        $contextparams[$paramalias] = $path . '/%';
    }

    // Add visibility.
    list($visibilityjoinsql, $visibilityjoinparams) = totara_visibility_join($USER->id, $viewtype, $itemalias);

    // Get context data for preload.
    $ctxfields = context_helper::get_preload_record_columns_sql('ctx');

    $sql = "SELECT {$itemalias}.id as itemid, {$itemalias}.visible, {$itemalias}.audiencevisible, ctx.path,
                   {$ctxfields}, visibilityjoin.isvisibletouser
              FROM {context} ctx
              JOIN {$itemtable} {$itemalias}
                ON {$itemalias}.id = ctx.instanceid AND contextlevel = :itemcontext
                   {$visibilityjoinsql}
             WHERE (" . implode(' OR ', $contextwhere) . ")" . $extrawhere;
    $params = array_merge(array('itemcontext' => $itemcontext), $contextparams, $visibilityjoinparams);

    // Get all items inside all the categories.
    $items = $DB->get_records_sql($sql, $params);

    // Remove items that aren't visible.
    foreach ($items as $id => $item) {
        if ($item->isvisibletouser) {
            unset($item->isvisibletouser); // Visible.
        } else {
            context_helper::preload_from_record($item);
            if ($viewtype == 'course') {
                $context = context_course::instance($id);
                if (has_capability('moodle/course:viewhiddencourses', $context) ||
                    !empty($CFG->audiencevisibility) && has_capability('totara/coursecatalog:manageaudiencevisibility', $context)) {
                    unset($item->isvisibletouser); // Visible.
                } else {
                    unset($items[$id]); // Not visible.
                }
            } else {
                $context = context_program::instance($id);
                if (($viewtype == 'program') && has_capability('totara/program:viewhiddenprograms', $context) ||
                    ($viewtype == 'certification') && has_capability('totara/certification:viewhiddencertifications', $context) ||
                    !empty($CFG->audiencevisibility) && has_capability('totara/coursecatalog:manageaudiencevisibility', $context)) {
                    unset($item->isvisibletouser); // Visible.
                } else {
                    unset($items[$id]); // Not visible.
                }
            }

        }
    }

    if (!$items) {
        // Sub-categories are all empty.
        if (is_array($categoryids)) {
            return array();
        } else {
            return 0;
        }
    }

    $results = array();
    foreach ($items as $item) {
        // Now we need to figure out which sub-category each item is a member of.
        foreach ($contextpaths as $categoryid => $contextpath) {
            // It's a member if the beginning of the contextpath's match.
            if (substr($item->path, 0, strlen($contextpath.'/')) ==
                $contextpath.'/') {
                if (array_key_exists($categoryid, $results)) {
                    $results[$categoryid]++;
                } else {
                    $results[$categoryid] = 1;
                }
                break;
            }
        }
    }

    if (empty($results)) {
        return 0;
    } else if (is_array($categoryids)) {
        return $results;
    } else {
        return current($results);
    }

}

/**
 * Sorts a pair of objects based on the itemcount property (high to low)
 *
 * @param object $a The first object
 * @param object $b The second object
 * @return integer Returns 1/0/-1 depending on the relative values of the objects itemcount property
 */
function totara_course_cmp_by_count($a, $b) {
    if ($a->itemcount < $b->itemcount) {
        return +1;
    } else if ($a->itemcount > $b->itemcount) {
        return -1;
    } else {
        return 0;
    }
}

/**
 * Returns the style css name for the component's visibility.
 *
 * @param stdClass $component Component (Course, Program, Certification) object
 * @param string $oldvisfield Old visibility field
 * @param string $audvisfield Audience visibility field
 * @return string $dimmed Css class name
 */
function totara_get_style_visibility($component, $oldvisfield = 'visible', $audvisfield = 'audiencevisible') {
    global $CFG;
    $dimmed = '';

    if (!is_object($component)) {
        return $dimmed;
    }

    if (empty($CFG->audiencevisibility)) {
        if (isset($component->{$oldvisfield}) && !$component->{$oldvisfield}) {
            $dimmed = 'dimmed';
        }
    } else {
        require_once($CFG->dirroot . '/totara/cohort/lib.php');
        if (isset($component->{$audvisfield}) && $component->{$audvisfield} == COHORT_VISIBLE_NOUSERS) {
            $dimmed = 'dimmed';
        }
    }

    return $dimmed;
}


/**
 * Get the where clause sql fragment and parameters needed to restrict an sql query to only those courses or
 * programs available to a user.
 *
 * sqlparams in return are SQL_PARAMS_NAMED, so queries built using this function must also use named params.
 *
 * !!! Your query must join to the context table, with alias "ctx" !!!
 *
 * Note that currently, if using normal visibility, hidden items will not show in the RoL for a learner, but
 * it will show in their Required Learning, is accessible, and they are processed for completion. All other
 * places which display learning items are limited to those that are not hidden. We may want to change this.
 * For example, f2f calendar items, appraisal questions, recent learning, user course completion report,
 * enrol_get_my_courses, ... Basically we should check every call to this function.
 *
 * @param int $userid The user that the results should be restricted for. Defaults to current user.
 * @param string $fieldbaseid The field in the base sql query which this query can link to.
 * @param string $fieldvisible The field in the base sql query which contains the visible property.
 * @param string $fieldaudvis The field in the base sql query which contains the audiencevisibile property.
 * @param string $tablealias The alias for the base table (This is used mainly for programs and cert which has available field)
 * @param string $type course, program or certification.
 * @param bool $iscached True if the fields passed comes from a report which data has been cached.
 * @param bool $showhidden If using normal visibility, show items even if they are hidden.
 * @return array(sqlstring, array(sqlparams))
 */
function totara_visibility_where($userid = null, $fieldbaseid = 'course.id', $fieldvisible = 'course.visible',
             $fieldaudvis = 'course.audiencevisible', $tablealias = 'course', $type = 'course', $iscached = false,
             $showhidden = false) {
    global $CFG, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    // Initialize availability variables, needed for programs and certifications.
    $availabilitysql = '1=1';
    $availabilityparams = array();
    $separator = ($iscached) ? '_' : '.'; // When the report is caches its fields comes in type_value form.
    $systemcontext = context_system::instance();

    // Evaluate capabilities.
    switch($type) {
        case 'course':
            $capability = 'moodle/course:viewhiddencourses';
            $instancetype = COHORT_ASSN_ITEMTYPE_COURSE;
            break;
        case 'program':
            require_once($CFG->dirroot . '/totara/program/lib.php');
            $capability = 'totara/program:viewhiddenprograms';
            $instancetype = COHORT_ASSN_ITEMTYPE_PROGRAM;
            list($availabilitysql, $availabilityparams) = get_programs_availability_sql($tablealias, $separator, $userid);
            break;
        case 'certification':
            require_once($CFG->dirroot . '/totara/program/lib.php');
            $capability = 'totara/certification:viewhiddencertifications';
            $instancetype = COHORT_ASSN_ITEMTYPE_CERTIF;
            list($availabilitysql, $availabilityparams) = get_programs_availability_sql($tablealias, $separator, $userid);
            break;
    }

    if (is_siteadmin($userid)) {
        // Admins can see all records no matter what the visibility.
        return array('1=1', array());

    } else if (empty($CFG->audiencevisibility)) {
        if ($showhidden || has_capability($capability, $systemcontext, $userid)) {
            return array('1=1', array());
        } else {
            // Normal visibility unless they have the capability to see hidden learning components.
            list($capsql, $capparams) = get_has_capability_sql($capability, "ctx{$separator}id", $userid);
            $sqlnormalvisible = "
            (({$fieldvisible} = :tcvwnormalvisible) OR
             ({$fieldvisible} = :tcvwnormalvisiblenone AND
                 {$capsql}
             ))";
            $params = array_merge([
                                      'tcvwnormalvisible'     => 1,
                                      'tcvwnormalvisiblenone' => 0
                                  ],
                                  $capparams);

            // Add availability sql.
            if ($availabilitysql != '1=1') {
                $sqlnormalvisible .= " AND {$availabilitysql} ";
                $params = array_merge($params, $availabilityparams);
            }

            return array($sqlnormalvisible, $params);
        }
    } else {
        // Audience visibility No users. Check for capabilities.
        $canmanagevisibility = has_capability('totara/coursecatalog:manageaudiencevisibility', $systemcontext, $userid);
        if ($canmanagevisibility || has_capability($capability, $systemcontext, $userid)) {
            return array('1=1', array());
        }

        $sqlnousers = "{$fieldaudvis} != :tcvwaudvisnousers";
        $paramsnousers = array('tcvwaudvisnousers' => COHORT_VISIBLE_NOUSERS);

        // Add availability sql.
        if ($availabilitysql != '1=1') {
            $sqlnousers .= " AND {$availabilitysql} ";
            $paramsnousers = array_merge($paramsnousers, $availabilityparams);
        }

        // Audience visibility all.
        $sqlall = "{$fieldaudvis} = :tcvwaudvisall";
        $paramsall = array('tcvwaudvisall' => COHORT_VISIBLE_ALL);

        // Audience visibility selected.
        $sqlselected = "({$fieldaudvis} = :tcvwaudvisaud AND
                 EXISTS (SELECT 1
                           FROM {cohort_visibility} cv
                           JOIN {cohort_members} cm ON cv.cohortid = cm.cohortid
                          WHERE cv.instanceid = {$fieldbaseid}
                            AND cv.instancetype = :tcvwinstancetypeselected
                            AND cm.userid = :tcvwreportforselected)";
        if ($instancetype == COHORT_ASSN_ITEMTYPE_COURSE) {
            $sqlselected .= " OR EXISTS (SELECT 1
                                         FROM {user_enrolments} ue
                                         JOIN {enrol} e ON e.id = ue.enrolid
                                         WHERE e.courseid = {$fieldbaseid}
                                           AND ue.userid = :tcvwreportforenrolled))";
        } else {
            $sqlselected .= " OR EXISTS (SELECT 1
                                         FROM {prog_user_assignment} pua
                                         WHERE pua.programid = {$fieldbaseid}
                                           AND pua.userid = :tcvwreportforenrolled))";
        }

        $paramsselected = array('tcvwaudvisaud' => COHORT_VISIBLE_AUDIENCE,
                'tcvwinstancetypeselected' => $instancetype,
                'tcvwreportforselected' => $userid,
                'tcvwreportforenrolled' => $userid);

        // Enrolled or assigned user.
        if ($instancetype == COHORT_ASSN_ITEMTYPE_COURSE) {
            $sqlenrolled = "({$fieldaudvis} = :tcvwaudvisenr AND EXISTS (SELECT 1
                                      FROM {user_enrolments} ue
                                      JOIN {enrol} e ON e.id = ue.enrolid
                                     WHERE e.courseid = {$fieldbaseid}
                                       AND ue.userid = :tcvwreportforenrolledonly))";
            $paramsenrolled = array('tcvwaudvisenr' => COHORT_VISIBLE_ENROLLED,
                                    'tcvwreportforenrolledonly' => $userid);
        } else {
            $sqlenrolled = "({$fieldaudvis} = :tcvwaudvisenr AND EXISTS (SELECT 1
                                      FROM {prog_user_assignment} pua
                                     WHERE pua.programid = {$fieldbaseid}
                                       AND pua.userid = :tcvwreportforenrolledonly))";
            $paramsenrolled = array('tcvwaudvisenr' => COHORT_VISIBLE_ENROLLED,
                                    'tcvwreportforenrolledonly' => $userid);
        }

        // Condition above are overridden when user have capability to see hidden content in this context
        list($capsql, $capparams) = totara_core\access::get_has_capability_sql($capability, "ctx{$separator}id", $userid);

        return [
            "({$sqlnousers} AND ({$sqlall} OR {$sqlselected} OR {$sqlenrolled}) OR {$capsql})",
            array_merge($paramsnousers, $paramsall, $paramsselected, $paramsenrolled, $capparams)
        ];
    }
}

/**
 * Get the join clause sql fragment and parameters needed to get the isvisibletouser column only for those courses or
 * programs available to a user.
 *
 * Use in the following form:
 *
 * list($visibilityjoinsql, $visibilityjoinparams) = totara_visibility_join($userid, 'program', 'p');
 *
 * $sql = "SELECT p.*, visibilityjoin.isvisibletouser
 *           FROM {prog} p
 *                {$visibilityjoinsql}
 *          WHERE p.certifid IS NULL";
 *
 * @param mixed $userid The user that the results should be restricted for. Defaults to current user.
 * @param string $type course, program or certification.
 * @param string $mainalias Alias of the table that contains the data you are working with.
 * @param string $joinalias Alias to give the joined table, which will contain the isvisible field.
 * @param string $jointype 'LEFT JOIN' will not restrict the rows returned from you main table,
 *                         'JOIN' will remove rows from you main table (like using totara_visibility_where).
 * @return array list(string $joinsql, array $joinparams)
 */
function totara_visibility_join($userid = null, $type = 'course', $mainalias = 'course', $joinalias = 'visibilityjoin',
                                $jointype = 'LEFT JOIN') {
    global $USER, $DB;

    // Default user.
    if ($userid === null) {
        $userid = $USER->id;
    }

    // Figure out what type of data we're dealing with.
    if ($type === 'course') {
        $basetable = 'course';
        $restrictions = '';
        $contextlevel = CONTEXT_COURSE;
    } else {
        $basetable = 'prog';
        if ($type === 'program') {
            $restrictions = ' AND tvjoinsub.certifid IS NULL';
        } else {
            $restrictions = ' AND tvjoinsub.certifid IS NOT NULL';
        }
        $contextlevel = CONTEXT_PROGRAM;
    }

    // Get the totara_visibility_where sql, which the join will be based on.
    list($visibilitysql, $visibilityparams) = totara_visibility_where($userid, 'tvjoinsub.id', 'tvjoinsub.visible',
        'tvjoinsub.audiencevisible', 'tvjoinsub', $type);

    // Construct the result.
    $one = '1';
    if ($DB->get_dbfamily() === 'mysql') {
        // Workaround for broken MariaDB - see TL-7785.
        $one = '(CASE WHEN tvjoinsub.id > 0 THEN 1 ELSE 0 END)';
    }
    $sql = "{$jointype} (SELECT tvjoinsub.id, $one AS isvisibletouser
                           FROM {{$basetable}} tvjoinsub
                           JOIN {context} ctx
                             ON tvjoinsub.id = ctx.instanceid AND ctx.contextlevel = :tvjcontextlevel
                          WHERE {$visibilitysql} {$restrictions}
                         ) {$joinalias}
                ON {$mainalias}.id = {$joinalias}.id";

    $visibilityparams['tvjcontextlevel'] = $contextlevel;

    return array($sql, $visibilityparams);
}
