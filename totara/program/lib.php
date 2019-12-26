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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @package totara
 * @subpackage program
 */

require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/datalib.php');
require_once($CFG->libdir . '/ddllib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/totara/program/program.class.php');
require_once($CFG->dirroot . '/totara/certification/lib.php'); // For the constants
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

/**
 * Can logged in user view user's required learning
 *
 * @access  public
 * @param   int     $learnerid   Learner's id
 * @return  boolean
 */
function prog_can_view_users_required_learning($learnerid) {
    global $USER;

    if (!isloggedin()) {
        return false;
    }

    $systemcontext = context_system::instance();

    // If the user can view any programs
    if (has_capability('totara/program:accessanyprogram', $systemcontext)) {
        return true;
    }

    // If the user cannot view any programs
    if (!has_capability('totara/program:viewprogram', $systemcontext)) {
        return false;
    }

    // If this is the current user's own required learning
    if ($learnerid == $USER->id) {
        return true;
    }

    // If this user is their manager
    if (\totara_job\job_assignment::is_managing($USER->id, $learnerid)) {
        return true;
    }

    $usercontext = context_user::instance($learnerid);
    if (has_capability('totara/core:markusercoursecomplete', $usercontext)) {
        return true;
    }

    return false;
}

/**
 * Return a list of a user's programs or a count
 *
 * @global object $DB
 * @param int $userid
 * @param string $sort The order in which to sort the programs
 * @param mixed $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
 * @param mixed $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
 * @param bool $returncount Whether to return a count of the number of records found or the records themselves
 * @param bool $showhidden Whether to include hidden programs in records returned when using normal visibility
 * @param bool $onlyprograms Only return programs (excludes certifications)
 * @param bool $onlyactive Only return active programs.
 * @param bool $onlycertifications Only return certifications (excludes programs)
 * @return array|int
 */
function prog_get_all_programs($userid, $sort = '', $limitfrom = '', $limitnum = '', $returncount = false,
                               $showhidden = false, $onlyprograms = false, $onlyactive = true, $onlycertifications = false) {
    global $DB;

    // Construct sql query.
    $count = 'SELECT COUNT(*) ';
    $select = 'SELECT p.*, p.fullname AS progname, pc.timedue AS duedate, pc.status AS status ';
    list($insql, $params) = $DB->get_in_or_equal(array(PROGRAM_EXCEPTION_RAISED, PROGRAM_EXCEPTION_DISMISSED),
            SQL_PARAMS_NAMED, 'param', false);
    $from = "FROM {prog} p
       INNER JOIN {context} ctx ON (p.id = ctx.instanceid AND ctx.contextlevel = :contextlevel)
       INNER JOIN {prog_completion} pc
               ON p.id = pc.programid AND pc.coursesetid = 0 ";

    $where = "WHERE pc.userid = :userid
              AND EXISTS(SELECT id
                           FROM {prog_user_assignment} pua
                          WHERE pua.exceptionstatus {$insql}
                            AND pc.programid = pua.programid
                            AND pc.userid = pua.userid
                        ) ";
    if ($onlyactive) {
        $where .= " AND pc.status <> :statuscomplete";
        $params['statuscomplete'] = STATUS_PROGRAM_COMPLETE;
    }
    if ($onlyprograms) {
        $where .= " AND p.certifid IS NULL";
    }
    if ($onlycertifications) {
        $where .= " AND p.certifid IS NOT NULL";
    }

    $params['contextlevel'] = CONTEXT_PROGRAM;
    $params['userid'] = $userid;

    list($visibilitysql, $visibilityparams) = totara_visibility_where($userid,
                                                                      'p.id',
                                                                      'p.visible',
                                                                      'p.audiencevisible',
                                                                      'p',
                                                                      'certification',
                                                                      false,
                                                                      $showhidden);
    $params = array_merge($params, $visibilityparams);
    $where .= " AND {$visibilitysql} ";

    if ($returncount) {
        return $DB->count_records_sql($count.$from.$where, $params);
    } else {
        return $DB->get_records_sql($select.$from.$where.$sort, $params, $limitfrom, $limitnum);
    }
}

/**
 * Return a list of a user's required learning programs or a count
 *
 * @global object $DB
 * @param int $userid
 * @param string $sort The order in which to sort the programs
 * @param mixed $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
 * @param mixed $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
 * @param bool $returncount Whether to return a count of the number of records found or the records themselves
 * @param bool $showhidden Whether to include hidden programs in records returned when using normal visibility
 * @param bool $onlyprograms Only return programs (excludes certifications)
 * @return array|int
 */
function prog_get_required_programs($userid, $sort='', $limitfrom='', $limitnum='', $returncount=false, $showhidden=false,
                                    $onlyprograms=true) {
    return prog_get_all_programs($userid, $sort, $limitfrom, $limitnum, $returncount, $showhidden,
                                 $onlyprograms);
}

/**
 * Return a list of a user's certification programs or a count
 *
 * @global object $DB
 * @param int $userid
 * @param string $sort SQL fragment to order the programs
 * @param mixed $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
 * @param mixed $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
 * @param bool $returncount Whether to return a count of the number of records found or the records themselves
 * @param bool $showhidden Whether to include hidden programs in records returned when using normal visibility
 * @param bool $activeonly Whether to restrict to only active programs (programs where "Progress" is not "Complete")
 * @return array|int
 */
function prog_get_certification_programs($userid, $sort='', $limitfrom='', $limitnum='', $returncount=false,
                                         $showhidden=false, $activeonly=false) {
    global $DB;

    $params = array();
    $params['contextlevel'] = CONTEXT_PROGRAM;
    $params['userid'] = $userid;
    $params['comptype'] = CERTIFTYPE_PROGRAM;

    list($exceptionsql, $exceptionparams) = $DB->get_in_or_equal(array(PROGRAM_EXCEPTION_RAISED, PROGRAM_EXCEPTION_DISMISSED),
                                                                                        SQL_PARAMS_NAMED, 'exception', false);
    $params = array_merge($params, $exceptionparams);

    // Construct sql query
    $count = 'SELECT COUNT(*) ';
    $select = 'SELECT p.*, p.fullname AS progname, pc.timedue AS duedate, cfc.certifpath, cfc.status, cfc.timeexpires ';
    $from = "FROM {prog} p
            INNER JOIN {context} ctx ON (p.id = ctx.instanceid AND ctx.contextlevel = :contextlevel)
            INNER JOIN {prog_completion} pc ON p.id = pc.programid
                    AND pc.coursesetid = 0
                    AND pc.userid = :userid
            INNER JOIN {certif} cf ON cf.id = p.certifid
            INNER JOIN {certif_completion} cfc ON cfc.certifid = cf.id
                    AND cfc.userid = pc.userid ";

    // Is the user assigned? Exists is more efficient than using distinct.
    $where = "WHERE EXISTS (SELECT userid
                            FROM {prog_user_assignment} pua
                            WHERE pua.programid = pc.programid
                            AND pua.userid = pc.userid
                            AND pua.exceptionstatus {$exceptionsql})";

    list($visibilitysql, $visibilityparams) = totara_visibility_where($userid,
                                                                      'p.id',
                                                                      'p.visible',
                                                                      'p.audiencevisible',
                                                                      'p',
                                                                      'certification',
                                                                      false,
                                                                      $showhidden);
    $params = array_merge($params, $visibilityparams);
    $where .= " AND {$visibilitysql} ";

    if ($activeonly) {
        // This should only show non complete certifications and due/expired recertifications.
        $where .= "AND (cfc.status <> :cstatus OR cfc.renewalstatus <> :rstatus) ";
        $params['cstatus'] = CERTIFSTATUS_COMPLETED;
        $params['rstatus'] = CERTIFRENEWALSTATUS_NOTDUE;
    }

    if ($returncount) {
        return $DB->count_records_sql($count.$from.$where, $params);
    } else {
        return $DB->get_records_sql($select.$from.$where.$sort, $params, $limitfrom, $limitnum);
    }
}

/**
 * Return markup for displaying a table of a specified user's required programs
 * (i.e. programs that have been automatically assigned to the user)
 *
 * This includes hidden programs but excludes unavailable programs
 *
 * @access  public
 * @param   int     $userid     Program assignee
 * @return  string
 */
function prog_display_required_programs($userid) {
    global $CFG, $OUTPUT;

    $count = prog_get_required_programs($userid, '', '', '', true, true);

    // Set up table
    $tablename = 'progs-list-programs';
    $tableheaders = array(get_string('programname', 'totara_program'));
    $tablecols = array('progname');

    // Due date
    $tableheaders[] = get_string('duedate', 'totara_program');
    $tablecols[] = 'duedate';

    // Progress
    $tableheaders[] = get_string('progress', 'totara_program');
    $tablecols[] = 'progress';

    $baseurl = $CFG->wwwroot . '/totara/program/required.php?userid='.$userid;

    $table = new flexible_table($tablename);
    $table->define_headers($tableheaders);
    $table->define_columns($tablecols);
    $table->define_baseurl($baseurl);
    $table->set_attribute('class', 'fullwidth generalbox');
    $table->set_control_variables(array(
        TABLE_VAR_SORT    => 'tsort',
    ));
    $table->sortable(true);
    $table->no_sorting('progress');

    $table->setup();
    $table->pagesize(15, $count);
    $sort = $table->get_sql_sort();
    $sort = empty($sort) ? '' : ' ORDER BY '.$sort;

    // Add table data
    $programs = prog_get_required_programs($userid, $sort, $table->get_page_start(), $table->get_page_size(), false, true);

    if (!$programs) {
        return '';
    }
    $rowcount = 0;
    foreach ($programs as $p) {
        if (!prog_is_accessible($p)) {
            continue;
        }
        $row = array();
        $row[] = prog_display_summary_widget($p, $userid);
        $row[] = prog_display_duedate($p->duedate, $p->id, $userid);
        $row[] = prog_display_progress($p->id, $userid);
        $table->add_data($row);
        $rowcount++;
    }

    unset($programs);

    if ($rowcount > 0) {
        //2.2 flexible_table class no longer supports $table->data and echos directly on each call to add_data
        ob_start();
        $table->finish_html();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    } else {
        return '';
    }
}

/**
 * Return markup for displaying a table of a specified user's certification programs
 * This includes hidden programs but excludes unavailable programs
 *
 * @param   int $userid     Program assignee
 * @return  string
 */
function prog_display_certification_programs($userid) {

    $count = prog_get_certification_programs($userid, '', '', '', true, true, true);

    // Set up table
    $tablename = 'progs-list-cert';
    $tableheaders = array(get_string('certificationname', 'totara_program'));
    $tablecols = array('progname');

    // Due date
    $tableheaders[] = get_string('duedate', 'totara_program');
    $tablecols[] = 'duedate';

    // Progress
    $tableheaders[] = get_string('progress', 'totara_program');
    $tablecols[] = 'progress';

    $baseurl = new moodle_url('/totara/program/required.php', array('userid' => $userid));

    $table = new flexible_table($tablename);
    $table->define_headers($tableheaders);
    $table->define_columns($tablecols);
    $table->define_baseurl($baseurl);
    $table->set_attribute('class', 'fullwidth generalbox');
    $table->set_control_variables(array(
        TABLE_VAR_SORT    => 'tsort',
    ));
    $table->sortable(true);
    $table->no_sorting('progress');

    $table->setup();
    $table->pagesize(15, $count);
    $sort = $table->get_sql_sort();
    $sort = empty($sort) ? '' : ' ORDER BY '.$sort;

    // Add table data
    $cprograms = prog_get_certification_programs($userid, $sort, $table->get_page_start(), $table->get_page_size(),
            false, true, true);

    if (!$cprograms) {
        return '';
    }

    $rowcount = 0;
    foreach ($cprograms as $cp) {
        if (!prog_is_accessible($cp)) {
            continue;
        }
        $row = array();
        $row[] = prog_display_summary_widget($cp, $userid);
        if (!empty($cp->timeexpires)) {
            $row[] = prog_display_duedate($cp->timeexpires, $cp->id, $userid, $cp->certifpath, $cp->status);
        } else {
            $row[] = prog_display_duedate($cp->duedate, $cp->id, $userid, $cp->certifpath, $cp->status);
        }
        $row[] = prog_display_progress($cp->id, $userid, $cp->certifpath);
        $table->add_data($row);
        $rowcount++;
    }

    unset($cprograms);

    if ($rowcount > 0) {
        //2.2 flexible_table class no longer supports $table->data and echos directly on each call to add_data
        ob_start();
        $table->finish_html();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    } else {
        return '';
    }
}

/**
 * Display the user message box
 *
 * @access public
 * @param  int    $programuser the id of the user
 * @return string $out      the display code
 */
function prog_display_user_message_box($programuser) {
    global $CFG, $PAGE, $DB;
    $user = $DB->get_record('user', array('id' => $programuser));
    if (!$user) {
        return false;
    }
    $user->courseid = 1;

    $a = new stdClass();
    $a->name = fullname($user);
    $a->userid = $programuser;
    $a->site = $CFG->wwwroot;

    $renderer = $PAGE->get_renderer('totara_program');
    $out = $renderer->display_user_message_box($user, $a);
    return $out;
}

/**
 * Add lowest levels of breadcrumbs to program
 *
 * @return void
 */
function prog_add_base_navlinks() {
    global $PAGE;

    $PAGE->navbar->add(get_string('browsecategories', 'totara_program'), new moodle_url('/totara/program/index.php'));
}

/**
 * Add lowest levels of breadcrumbs to required learning
 *
 * Exact links added depends on if the require learning being viewed belongs
 * to the current user or not.
 *
 * @param array &$navlinks The navlinks array to update (passed by reference)
 * @param integer $userid ID of the required learning's owner
 *
 * @return boolean True if it is the user's own required learning
 */
function prog_add_required_learning_base_navlinks($userid) {
    global $USER, $PAGE, $DB;

    // the user is viewing their own learning
    if ($userid == $USER->id) {
        $PAGE->navbar->add(get_string('requiredlearning', 'totara_program'), new moodle_url('/totara/program/required.php'));
        return true;
    }

    // the user is viewing someone else's learning
    $user = $DB->get_record('user', array('id' => $userid));
    if ($user) {
        if (totara_feature_visible('myteam')) {
            $PAGE->navbar->add(get_string('team', 'totara_core'), new moodle_url('/my/teammembers.php'));
        }
        $PAGE->navbar->add(get_string('xsrequiredlearning', 'totara_program', fullname($user)), new moodle_url('/totara/program/required.php', array('userid' => $userid)));
    } else {
        $PAGE->navbar->add(get_string('unknownusersrequiredlearning', 'totara_program'), new moodle_url('/totara/program/required.php', array('userid' => $userid)));
    }

    return true;
}

/**
 * Returns list of programs, for whole site, or category
 *
 * Note: Cannot use p.* in $fields because MSSQL does not handle DISTINCT text fields.
 * See T-11732
 */
function prog_get_programs($categoryid="all", $sort="p.sortorder ASC",
                           $fields="p.id, p.category, p.sortorder, p.shortname, p.fullname, p.visible, p.icon, p.audiencevisible",
                           $type = 'program', $options = array()) {
    global $USER, $DB, $CFG;
    require_once($CFG->dirroot . '/totara/cohort/lib.php');

    $offset = !empty($options['offset']) ? $options['offset'] : 0;
    $limit = !empty($options['limit']) ? $options['limit'] : null;
    $userid = !empty($options['userid']) ? $options['userid'] : $USER->id;

    $params = array('contextlevel' => CONTEXT_PROGRAM);
    $isprogram = ($type === 'program');
    $wheresql = $isprogram ? " p.certifid IS NULL" : " p.certifid IS NOT NULL";

    if ((int)$categoryid > 0) {
        $wheresql .= " AND p.category = :category";
        $params['category'] = (int)$categoryid;
    }

    if (empty($sort)) {
        $sortstatement = "";
    } else {
        $sortstatement = "ORDER BY $sort";
    }

    // Manage visibility.
    list($visibilityjoinsql, $visibilityjoinparams) = totara_visibility_join($userid, $type, 'p');
    $params = array_merge($params, $visibilityjoinparams);

    // Get context data for preload.
    $ctxfields = context_helper::get_preload_record_columns_sql('ctx');
    $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = p.id AND ctx.contextlevel = :contextlevel)";

    // Get all programs matching the criteria, with additional visibility info.
    $sql = "SELECT DISTINCT {$fields}, {$ctxfields}, visibilityjoin.isvisibletouser
              FROM {prog} p
                   {$visibilityjoinsql}
                   {$ctxjoin}
             WHERE {$wheresql} {$sortstatement}";
    $programs = $DB->get_records_sql($sql, $params, $offset, $limit);

    // Remove programs that aren't visible.
    foreach ($programs as $id => $program) {
        if ($program->isvisibletouser) {
            unset($program->isvisibletouser); // Visible.
        } else {
            context_helper::preload_from_record($program);
            $context = context_program::instance($id);
            if ($isprogram && has_capability('totara/program:viewhiddenprograms', $context) ||
                !$isprogram && has_capability('totara/certification:viewhiddencertifications', $context) ||
                !empty($CFG->audiencevisibility) && has_capability('totara/coursecatalog:manageaudiencevisibility', $context)) {
                unset($program->isvisibletouser); // Visible.
            } else {
                unset($programs[$id]); // Not visible.
            }
        }
    }

    return $programs;
}

/**
 * Gets the path of breadcrumbs for a category path matching $categoryid
 *
 * @param integer $categoryid The id of the current category
 * @param string $viewtype Type of the page
 * @return array Multidimensional array containing name, link, and type of breadcrumbs
 *
 */
function prog_get_category_breadcrumbs($categoryid, $viewtype = 'program') {
    global $DB;

    $category = $DB->get_record('course_categories', array('id' => $categoryid));

    if (strpos($category->path, '/') === false) {
        return array();
    }

    $bread = explode('/', substr($category->path, 1));
    list($breadinsql, $params) = $DB->get_in_or_equal($bread);
    $sql = "SELECT id, name FROM {course_categories} WHERE id {$breadinsql} ORDER BY depth";
    $cat_bread = array();

    if ($bread_info = $DB->get_records_sql($sql, $params)) {
        foreach ($bread_info as $crumb) {
            $cat_bread[] = array('name' => format_string($crumb->name),
                                 'link' => new moodle_url("/totara/program/index.php",
                                                 array('categoryid' => $crumb->id,
                                                       'viewtype' => $viewtype)),
                                 'type' => 'misc');

        }
    }
    return $cat_bread;
}

/**
 * Returns list of courses and programs, for whole site, or category
 * (This is the counterpart to get_courses_page in /lib/datalib.php)
 *
 * Similar to prog_get_programs, but allows paging
 *
 */
function prog_get_programs_page($categoryid="all", $sort="sortorder ASC",
                          $fields="p.id,p.sortorder,p.shortname,p.fullname,p.summary,p.visible",
                          &$totalcount, $limitfrom="", $limitnum="", $type = 'program') {
    global $CFG, $DB;

    $params = array();
    $categoryselect = "";
    if ($categoryid != "all" && is_numeric($categoryid)) {
        $categoryselect = " AND p.category = :cat ";
        $params['cat'] = $categoryid;
    }

    $isprogram = ($type === 'program');
    $typesql = $isprogram ? " p.certifid IS NULL" : " p.certifid IS NOT NULL";

    // Visibility.
    list($visibilityjoinsql, $visibilityjoinparams) = totara_visibility_join(null, $type, 'p');
    $params = array_merge($params, $visibilityjoinparams);

    // Get context data for preload.
    $ctxfields = context_helper::get_preload_record_columns_sql('ctx');
    $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = p.id AND ctx.contextlevel = :contextlevel)";
    $params['contextlevel'] = CONTEXT_PROGRAM;

    // Pull out all programs matching the cat.
    $visibleprograms = array();

    $progselect = "SELECT {$fields}, 'program' AS listtype, {$ctxfields}, visibilityjoin.isvisibletouser
                     FROM {prog} p
                          {$visibilityjoinsql}
                          {$ctxjoin}
                    WHERE {$typesql}";

    $select = $progselect.$categoryselect.' ORDER BY '.$sort;
    $rs = $DB->get_recordset_sql($select, $params);

    $totalcount = 0;
    $visiblecount = 0;

    if (!$limitfrom) {
        $limitfrom = 0;
    }

    // Iterate through the records until enough have been found, skipping those that are not visible.
    foreach ($rs as $program) {
        $visible = false;
        if ($program->isvisibletouser) {
            $visible = true;
        } else {
            context_helper::preload_from_record($program);
            $context = context_program::instance($program->id);
            if ($isprogram && has_capability('totara/program:viewhiddenprograms', $context) ||
                !$isprogram && has_capability('totara/certification:viewhiddencertifications', $context) ||
                !empty($CFG->audiencevisibility) && has_capability('totara/coursecatalog:manageaudiencevisibility', $context)) {
                $visible = true;
            }
        }
        if ($visible) {
            $totalcount++;
            if ($totalcount > $limitfrom && (!$limitnum || $visiblecount < $limitnum)) {
                unset($program->isvisibletouser);
                $visibleprograms [] = $program;
                $visiblecount++;
            }
        }
    }

    $rs->close();

    return $visibleprograms;
}

/**
 * Efficiently moves many programs around while maintaining
 * sortorder in order.
 * (This is the counterpart to move_courses in /course/lib.php)
 *
 * $programids is an array of program ids
 *
 **/
function prog_move_programs($programids, $categoryid) {
    global $DB, $OUTPUT;

    if (!empty($programids)) {

            $programids = array_reverse($programids);

            foreach ($programids as $programid) {

                if (!$program  = $DB->get_record("prog", array("id" => $programid))) {
                    echo $OUTPUT->notification(get_string('error:findingprogram', 'totara_program'));
                } else {
                    // figure out a sortorder that we can use in the destination category
                    $sortorder = $DB->get_field_sql('SELECT MIN(sortorder)-1 AS min
                                                     FROM {prog}
                                                     WHERE category = ?', array($categoryid));
                    if (is_null($sortorder) || $sortorder === false) {
                        // the category is empty
                        // rather than let the db default to 0
                        // set it to > 100 and avoid extra work in fix_program_sortorder()
                        $sortorder = 200;
                    } else if ($sortorder < 10) {
                        prog_fix_program_sortorder($categoryid);
                    }

                    $program->category  = $categoryid;
                    $program->sortorder = $sortorder;

                    if (!$DB->update_record('prog', $program)) {
                        echo $OUTPUT->notification(get_string('error:prognotmoved', 'totara_program'));
                    }

                    $context   = context_program::instance($program->id);
                    $newparent = context_coursecat::instance($program->category);
                    $context->update_moved($newparent);
                }
            }
            prog_fix_program_sortorder();
        }
    return true;
}

/**
 * This recursive function makes sure that the program order is consecutive
 * (This is the counterpart to fix_course_sortorder in /lib/datalib.php)
 *
 * $n is the starting point, offered only for compatilibity -- will be ignored!
 * $safe (bool) prevents it from assuming category-sortorder is unique, used to upgrade
 * safely from 1.4 to 1.5
 *
 * @global $CFG
 * @param int $categoryid
 * @param int $n
 * @param int $safe
 * @param int $depth
 * @param string $path
 * @return int
 */
function prog_fix_program_sortorder($categoryid=0, $n=0, $safe=0, $depth=0, $path='') {

    global $DB;

    $counters = new stdClass();
    $counters->programcount = 0;
    $counters->certifcount = 0;
    $count = 0;

    $catgap    = 1000; // "standard" category gap
    $tolerance = 200;  // how "close" categories can get

    if ($categoryid > 0){
        // update depth and path
        $cat   = $DB->get_record('course_categories', array('id' => $categoryid));
        if ($cat->parent == 0) {
            $depth = 0;
            $path  = '';
        } else if ($depth == 0 ) { // doesn't make sense; get from DB
            // this is only called if the $depth parameter looks dodgy
            $parent = $DB->get_record('course_categories', array('id' => $cat->parent));
            $path  = $parent->path;
            $depth = $parent->depth;
        }
        $path  = $path . '/' . $categoryid;
        $depth = $depth + 1;

        if ($cat->path !== $path) {
            $DB->set_field('course_categories', 'path', $path, array('id' => $categoryid));
        }
        if ($cat->depth != $depth) {
            $DB->set_field('course_categories', 'depth', $depth, array('id' => $categoryid));
        }
    }

    // get some basic info about programs in the category
    $info = $DB->get_record_sql('SELECT MIN(sortorder) AS min,
                                        MAX(sortorder) AS max,
                                        COUNT(sortorder) AS count,
                                        COALESCE(SUM(CASE WHEN certifid IS NULL THEN 1 ELSE 0 END),0) AS programcount,
                                        COALESCE(SUM(CASE WHEN certifid IS NULL THEN 0 ELSE 1 END),0) AS certifcount
                                   FROM {prog}
                                  WHERE category = ?', array($categoryid));
    if (is_object($info)) { // no courses?
        $max   = $info->max;
        $counters->programcount = $info->programcount;
        $counters->certifcount = $info->certifcount;
        $count = $info->count;
        $min   = $info->min;
        unset($info);
    }

    if ($categoryid > 0 && $n == 0) { // only passed category so don't shift it
        $n = $min;
    }

    // $hasgap flag indicates whether there's a gap in the sequence
    $hasgap    = false;
    if ($max-$min+1 != $count) {
        $hasgap = true;
    }

    // $mustshift indicates whether the sequence must be shifted to
    // meet its range
    $mustshift = false;
    if ($min < $n-$tolerance || $min > $n+$tolerance+$catgap ) {
        $mustshift = true;
    }

    // actually sort only if there are programs,
    // and we meet one ofthe triggers:
    //  - safe flag
    //  - they are not in a continuos block
    //  - they are too close to the 'bottom'
    if ($count && ( $safe || $hasgap || $mustshift ) ) {
        // special, optimized case where all we need is to shift
        if ($mustshift && !$safe && !$hasgap) {
            $shift = $n + $catgap - $min;
            if ($shift < $count) {
                $shift = $count + $catgap;
            }

            $DB->execute("UPDATE {prog}
                          SET sortorder = sortorder + ?
                          WHERE category = ?", array($shift, $categoryid));
            $n = $n + $catgap + $count;

        } else { // do it slowly
            $n = $n + $catgap;
            // if the new sequence overlaps the current sequence, lack of transactions
            // will stop us -- shift things aside for a moment...
            if ($safe || ($n >= $min && $n+$count+1 < $min && $DB->get_dbfamily() === 'mysql')) {
                $shift = $max + $n + 1000;
                $DB->execute("UPDATE {prog}
                              SET sortorder = sortorder+$shift
                              WHERE category = ?". array($categoryid));
            }

            $programs = prog_get_programs($categoryid, 'p.sortorder ASC', 'p.id,p.sortorder');

            $transaction = $DB->start_delegated_transaction();

            $tx = true; // transaction sanity
            foreach ($programs as $program) {
                if ($tx && $program->sortorder != $n ) { // save db traffic
                    $tx = $tx && $DB->set_field('prog', 'sortorder', $n, array('id' => $program->id));
                }
                $n++;
            }
            if ($tx) {
                $transaction->allow_commit();
            } else {
                if (!$safe) {
                    // if we failed when called with !safe, try
                    // to recover calling self with safe=true
                    return prog_fix_program_sortorder($categoryid, $n, true, $depth, $path);
                }
            }
        }
    }
    if ($categoryid) {
        $counters->id = $categoryid;
        $DB->update_record('course_categories', $counters);
    }

    // $n could need updating
    $max = $DB->get_field_sql("SELECT MAX(sortorder)
                               FROM {prog}
                               WHERE category = ?", array($categoryid));
    if ($max > $n) {
        $n = $max;
    }

    if ($categories = coursecat::get($categoryid, MUST_EXIST, true)->get_children()) {
        foreach ($categories as $category) {
            $n = prog_fix_program_sortorder($category->id, $n, $safe, $depth, $path);
        }
    }

    return $n+1;
}

/**
 * Checks whether or not a user should have access to a course that belongs to a
 * program in the user's required learning.
 *
 * Note: the user will be automatically enrolled onto the course as a student.
 *
 * @global object $CFG
 * @param object $user
 * @param object $course
 * @return object $result containing properties:
 *         'enroled' (boolean: whether user is enroled on the course)
 *         'notify' (boolean: whether a new enrolment has been made so notify user)
 *         'program' (string: name of program they have obtained access through)
 */
function prog_can_enter_course($user, $course) {
    global $DB;

    $result = new stdClass();
    $result->enroled = false;
    $result->notify = false;
    $result->program = null;

    // Get program enrolment plugin class, and default role.
    $program_plugin = enrol_get_plugin('totara_program');
    $defaultrole = $program_plugin->get_config('roleid');
    if (empty($defaultrole)) {
        return $result;
    }

    // Get programs containing this course that this user is assigned to, either via learning plans or required learning
    $get_programs = "
        SELECT p.*
          FROM {prog} p
          WHERE p.available = :avl
          AND (
              p.id IN
              (
                SELECT DISTINCT pc.programid
                  FROM {dp_plan_program_assign} pc
            INNER JOIN {dp_plan} pln ON pln.id = pc.planid
             LEFT JOIN {prog_courseset} pcs ON pc.programid = pcs.programid
             LEFT JOIN {prog_courseset_course} pcsc ON pcs.id = pcsc.coursesetid AND pcsc.courseid = :cid
                 WHERE pc.approved >= :app
                   AND pln.userid = :uid
                   AND pln.status >= :stat
             )
            OR p.id IN
             (
                SELECT DISTINCT pua.programid
                  FROM {prog_user_assignment} pua
             LEFT JOIN {prog_completion} pc
                    ON pua.programid = pc.programid AND pua.userid = pc.userid
             LEFT JOIN {prog_courseset} pcs ON pua.programid = pcs.programid
             LEFT JOIN {prog_courseset_course} pcsc ON pcs.id = pcsc.coursesetid AND pcsc.courseid = :c2id
                 WHERE pua.userid = :u2id
                   AND pc.coursesetid = :csid
                   AND pua.exceptionstatus <> :raised
                   AND pua.exceptionstatus <> :dismissed
             )
        )
    ";
    $params = array(
        'avl'  => AVAILABILITY_TO_STUDENTS,
        'cid'  => $course->id,
        'app'  => DP_APPROVAL_APPROVED,
        'uid'  => $user->id,
        'stat' => DP_PLAN_STATUS_APPROVED,
        'c2id' => $course->id,
        'u2id' => $user->id,
        'csid' => 0,
        'raised' => PROGRAM_EXCEPTION_RAISED,
        'dismissed' => PROGRAM_EXCEPTION_DISMISSED
    );
    $program_records = $DB->get_records_sql($get_programs, $params);

    if (!empty($program_records)) {
        foreach ($program_records as $program_record) {
            $program = new program($program_record->id);
            if (prog_is_accessible($program_record) && $program->can_enter_course($user->id, $course->id)) {
                //check if program enrolment plugin is enabled on this course
                //should be added when coursesets are created but just in case we'll double-check
                $instance = $program_plugin->get_instance_for_course($course->id);
                if (!$instance) {
                    //add it
                    $instanceid = $program_plugin->add_instance($course);
                    $instance = $DB->get_record('enrol', array('id' => $instanceid));
                }
                // Check if user is already enroled under the program plugin.
                // We also check for suspended enrolments that need to be re-enrolled
                $ue = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $user->id));
                if (!$ue) {
                    //enrol them
                    $program_plugin->enrol_user($instance, $user->id, $defaultrole);
                    $result->enroled = true;
                    $result->notify = true;
                    $result->program = $program->fullname;
                } else if ($ue->status != ENROL_USER_ACTIVE) {
                    $program_plugin->enrol_user($instance, $user->id, $defaultrole, $ue->timestart, $ue->timeend, ENROL_USER_ACTIVE);
                    $result->enroled = true;
                    $result->notify = false;
                    $result->program = $program->fullname;
                } else {
                    //already enroled
                    $result->enroled = true;
                }
                return $result;
            }
        }
    }
    return $result;
}


/**
 * A list of programs that match a search
 *
 * @uses $DB, $USER
 * @param array $searchterms Terms to search
 * @param string $sort Sort order of the records
 * @param int $page
 * @param int $recordsperpage
 * @param int $totalcount Passed in by reference.
 * @param string $type Are we looking for programs or certifications
 * @return object {@link $COURSE} records
 */
function prog_get_programs_search($searchterms, $sort='fullname ASC', $page=0, $recordsperpage=50, &$totalcount, $type = 'program') {
    global $DB;

    $REGEXP    = $DB->sql_regex(true);
    $NOTREGEXP = $DB->sql_regex(false);

    $fullnamesearch = '';
    $summarysearch = '';
    $idnumbersearch = '';
    $shortnamesearch = '';

    $fullnamesearchparams = array();
    $summarysearchparams = array();
    $idnumbersearchparams = array();
    $shortnamesearchparams = array();
    $params = array();

    foreach ($searchterms as $searchterm) {
        if ($fullnamesearch) {
            $fullnamesearch .= ' AND ';
        }
        if ($summarysearch) {
            $summarysearch .= ' AND ';
        }
        if ($idnumbersearch) {
            $idnumbersearch .= ' AND ';
        }
        if ($shortnamesearch) {
            $shortnamesearch .= ' AND ';
        }

        if (substr($searchterm,0,1) == '+') {
            $searchterm      = substr($searchterm,1);
            $summarysearch  .= " p.summary $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $fullnamesearch .= " p.fullname $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $idnumbersearch  .= " p.idnumber $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $shortnamesearch  .= " p.shortname $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
        } else if (substr($searchterm,0,1) == "-") {
            $searchterm      = substr($searchterm,1);
            $summarysearch  .= " p.summary $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $fullnamesearch .= " p.fullname $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $idnumbersearch .= " p.idnumber $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $shortnamesearch .= " p.shortname $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
        } else {
            $summaryparam = rb_unique_param('summary');
            $summarysearch .= $DB->sql_like('summary', ":{$summaryparam}", false, true, false) . ' ';
            $summarysearchparams[$summaryparam] = '%' . $searchterm . '%';

            $fullnameparam = rb_unique_param('fullname');
            $fullnamesearch .= $DB->sql_like('fullname', ":{$fullnameparam}", false, true, false) . ' ';
            $fullnamesearchparams[$fullnameparam] = '%' . $searchterm . '%';

            $idnumberparam = rb_unique_param('idnumber');
            $idnumbersearch .= $DB->sql_like('idnumber', ":{$idnumberparam}", false, true, false) . ' ';
            $idnumbersearchparams[$idnumberparam] = '%' . $searchterm . '%';

            $shortnameparam = rb_unique_param('shortname');
            $shortnamesearch .= $DB->sql_like('shortname', ":{$shortnameparam}", false, true, false) . ' ';
            $shortnamesearchparams[$shortnameparam] = '%' . $searchterm . '%';
        }
    }

    // If search terms supplied, include in where.
    if (count($searchterms)) {
        $where = "
            WHERE (( $fullnamesearch ) OR ( $summarysearch ) OR ( $idnumbersearch ) OR ( $shortnamesearch ))
            AND category > 0
        ";
        $params = array_merge($params, $fullnamesearchparams, $summarysearchparams, $idnumbersearchparams, $shortnamesearchparams);
    } else {
        // Otherwise return everything.
        $where = " WHERE category > 0 ";
    }

    if ($type == 'program') {
        $where .= " AND p.certifid IS NULL"; // Filter out certifications.
    } else {
        $where .= " AND p.certifid IS NOT NULL";
    }

    // Add visibility query.
    list($visibilitysql, $visibilityparams) = totara_visibility_where(null, 'p.id', 'p.visible', 'p.audiencevisible', 'p', $type);
    $params = array_merge($params, $visibilityparams);
    $sql = "SELECT p.*,
                   ctx.id AS ctxid, ctx.path AS ctxpath,
                   ctx.depth AS ctxdepth, ctx.contextlevel AS ctxlevel
            FROM {prog} p
            JOIN {context} ctx ON (p.id = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_PROGRAM.")
            {$where} AND {$visibilitysql}
            ORDER BY {$sort}";

    $programs = array();

    $limitfrom = $page * $recordsperpage;
    $limitto   = $limitfrom + $recordsperpage;
    $c = 0; // Counts how many visible programs we've seen.

    $rs = $DB->get_recordset_sql($sql, $params);

    foreach ($rs as $program) {
        // Don't exit this loop till the end we need to count all the visible programs to update $totalcount.
        if ($c >= $limitfrom && $c < $limitto) {
            $programs[] = $program;
        }
        $c++;
    }

    $rs->close();

    // Our caller expects 2 bits of data - our return array, and an updated $totalcount.
    $totalcount = $c;
    return $programs;
}

/**
 * Retrieves any recurring programs and returns them in an array or an empty
 * array
 *
 * @return array
 */
function prog_get_recurring_programs() {
    global $DB;
    $recurring_programs = array();

    // get all programs
    $program_records = $DB->get_records('prog');
    foreach ($program_records as $program_record) {
        $program = new program($program_record->id);
        $content = $program->get_content();
        $coursesets = $content->get_course_sets();

        if ((count($coursesets) == 1) && ($coursesets[0]->is_recurring())) {
            $recurring_programs[] = $program;
        }
    }

    return $recurring_programs;
}


function prog_get_tab_link($userid) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    $progtable = new xmldb_table('prog');
    if ($dbman->table_exists($progtable)) {
        $programcount = prog_get_required_programs($userid, '', '', '', true, true);
        $certificationcount = prog_get_certification_programs($userid, '', '', '', true, true, true);
        $requiredlearningcount = $programcount + $certificationcount;
        if ($requiredlearningcount == 1) {
            if ($programcount == 1) {
                $program = prog_get_required_programs($userid, '', '', '', false, true);
            } else {
                $program = prog_get_certification_programs($userid, '', '', '', false, true, true);
            }
            $program = reset($program); // resets array pointer and returns value of first element
            if (!prog_is_accessible($program)) {
                return false;
            }
            return $CFG->wwwroot . '/totara/program/required.php?id=' . $program->id;
        } else if ($requiredlearningcount > 1) {
            return $CFG->wwwroot . '/totara/program/required.php';
        }
    }

    return false;
}


/*


/**
 * Processes extension request to grant or deny them given
 * an array of exceptions and the action to take
 *
 * @param array $extensionslist list of extension ids and actions in the form array(id => action)
 * @param array $reasonfordecision Reason for granting or denying the extension
 * @return array Contains count of extensions processed and number of failures
 */
function prog_process_extensions($extensionslist, $reasonfordecision = array()) {
    global $CFG, $DB, $USER;

    if (empty($CFG->enableprogramextensionrequests)) {
        print_error('error:notextensionallowed', 'totara_program');
    }

    if (!empty($extensionslist)) {
        $update_fail_count = 0;
        $update_extension_count = 0;

        // Get valid extensions to process. Extensions that are in prog_extensions and extensions for programs that allow them.
        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($extensionslist));
        $inparams[] = 1;
        $sql = "SELECT pe.*
                  FROM {prog_extension} pe
            INNER JOIN {prog} p
                    ON pe.programid = p.id
                 WHERE pe.id {$insql}
                   AND p.allowextensionrequests = ?";
        $extensions = $DB->get_records_sql($sql, $inparams);

        // Update fail count in case some of them are not valid at this point.
        $update_fail_count = count($extensionslist) - count($extensions);

        foreach ($extensions as $extension) {
            $id = $extension->id;
            $action = $extensionslist[$extension->id];

            if ($action == 0) {
                continue;
            }

            $update_extension_count++;

            // Ensure that the message is actually coming from $user's manager.
            if (\totara_job\job_assignment::is_managing($USER->id, $extension->userid)) {
                $userfrom = $USER;
            } else {
                print_error('error:notusersmanager', 'totara_program');
            }

            if ($action == PROG_EXTENSION_DENY) {

                $userto = $DB->get_record('user', array('id' => $extension->userid));
                $stringmanager = get_string_manager();

                $program = $DB->get_record('prog', array('id' => $extension->programid), 'fullname');

                $messagedata = new stdClass();
                $messagedata->userto           = $userto;
                $messagedata->userfrom         = $userfrom;
                $messagedata->subject          = $stringmanager->get_string('extensiondenied', 'totara_program', fullname($USER), $userto->lang);
                $messagedata->contexturl       = $CFG->wwwroot.'/totara/program/required.php?id='.$extension->programid;
                $messagedata->contexturlname   = $stringmanager->get_string('launchprogram', 'totara_program', null, $userto->lang);
                $messagedata->fullmessage      = $stringmanager->get_string('extensiondeniedmessage', 'totara_program', $program->fullname, $userto->lang);
                $messagedata->icon             = 'program-decline';
                $messagedata->msgtype          = TOTARA_MSG_TYPE_PROGRAM;

                if (!empty($reasonfordecision[$id])) {
                    // Add reason to the message.
                    $messagedata->fullmessage  .= html_writer::empty_tag('br') . html_writer::empty_tag('br');
                    $messagedata->fullmessage  .= $stringmanager->get_string('reasondeniedmessage', 'totara_program', $reasonfordecision[$id], $userto->lang);
                }

                $eventdata = new stdClass();
                $eventdata->message = $messagedata;

                if ($result = tm_alert_send($messagedata)) {

                    $extension_todb = new stdClass();
                    $extension_todb->id = $extension->id;
                    $extension_todb->status = PROG_EXTENSION_DENY;
                    $extension_todb->reasonfordecision = $reasonfordecision[$id];

                    if (!$DB->update_record('prog_extension', $extension_todb)) {
                        $update_fail_count++;
                    }
                    \totara_program\event\extension_denied::create_from_instance($extension)->trigger();
                } else {
                    print_error('error:failedsendextensiondenyalert', 'totara_program');
                }
            } elseif ($action == PROG_EXTENSION_GRANT) {
                // Load the program for this extension
                $extension_program = new program($extension->programid);

                $cert_completion = null;
                $prog_completion = null;

                if ($extension_program->certifid) {
                    list($cert_completion, $prog_completion) = certif_load_completion($extension->programid, $extension->userid, false);
                } else {
                    $prog_completion = prog_load_completion($extension->programid, $extension->userid, false);
                }

                if ($prog_completion) {
                    $duedate = empty($prog_completion->timedue) ? 0 : $prog_completion->timedue;

                    if ($extension->extensiondate < $duedate) {
                        $update_fail_count++;
                        continue;
                    }
                }

                $now = time();
                if ($extension->extensiondate < $now) {
                    $update_fail_count++;
                    continue;
                }

                // Try to update due date for program using extension date
                //Check whether user is on a recertification path, and update certification expiry if required
                if ($cert_completion) {
                    if ($cert_completion->certifpath == CERTIFPATH_RECERT) {
                        $cert_completion->timeexpires = $extension->extensiondate;
                    }

                    $prog_completion->timedue = $extension->extensiondate;
                    $result = certif_write_completion($cert_completion, $prog_completion, 'Due date extension granted');
                } else if ($prog_completion) {
                    $prog_completion->timedue = $extension->extensiondate;
                    $result = prog_write_completion($prog_completion, 'Due date extension granted');
                } else {
                    $update_fail_count++;
                    continue;
                }

                if (!$result) {
                    $update_fail_count++;
                    continue;
                } else {
                    $userto = $DB->get_record('user', array('id' => $extension->userid));
                    if (!$userto) {
                        print_error('error:failedtofinduser', 'totara_program', $extension->userid);
                    }

                    // Ensure the message is actually coming from $user's manager, default to support.
                    $userfrom = \totara_job\job_assignment::is_managing($USER->id, $extension->userid, $USER->id) ? $USER : core_user::get_support_user();
                    $stringmanager = get_string_manager();
                    $messagedata = new stdClass();
                    $messagedata->userto           = $userto;
                    $messagedata->userfrom         = $userfrom;
                    $messagedata->subject          = $stringmanager->get_string('extensiongranted', 'totara_program', fullname($USER), $userto->lang);
                    $messagedata->contexturl       = $CFG->wwwroot.'/totara/program/required.php?id='.$extension->programid;
                    $messagedata->contexturlname   = $stringmanager->get_string('launchprogram', 'totara_program', null, $userto->lang);
                    $messagedata->fullmessage      = $stringmanager->get_string('extensiongrantedmessage', 'totara_program',
                        userdate($extension->extensiondate, get_string('strftimedatetime', 'langconfig'), core_date::get_user_timezone($userto)),
                        $userto->lang);
                    $messagedata->icon             = 'program-approve';
                    $messagedata->msgtype          = TOTARA_MSG_TYPE_PROGRAM;

                    if (!empty($reasonfordecision[$id])) {
                        // Add reason to the message.
                        $messagedata->fullmessage  .= html_writer::empty_tag('br') . html_writer::empty_tag('br');
                        $messagedata->fullmessage  .= $stringmanager->get_string('reasonapprovedmessage', 'totara_program', $reasonfordecision[$id], $userto->lang);
                    }

                    if ($result = tm_alert_send($messagedata)) {

                        $extension_todb = new stdClass();
                        $extension_todb->id = $extension->id;
                        $extension_todb->status = PROG_EXTENSION_GRANT;
                        $extension_todb->reasonfordecision = $reasonfordecision[$id];

                        if (!$DB->update_record('prog_extension', $extension_todb)) {
                            $update_fail_count++;
                        }
                        \totara_program\event\extension_granted::create_from_instance($extension)->trigger();
                    } else {
                        print_error('error:failedsendextensiongrantalert','totara_program');
                    }
                 }
            }
        }
        return array('total' => $update_extension_count, 'failcount' => $update_fail_count, 'updatefailcount' => $update_fail_count);
    }
    return array();
}

/**
 * Update program completion status for particular user
 *
 * @param int $userid
 * @param program $program if not set - all programs will be updated
 * @param int $courseid if provided (and $program is not) then only programs related to this course will be updated
 * @param bool $useriscomplete if the users current completion status has already been fetched from the database
 *     providing it here will shortcut the completion check in this function and save you a query.
 *     This argument is ignored if no program was provided.
 */
function prog_update_completion($userid, program $program = null, $courseid = null, $useriscomplete = null) {
    global $DB;

    if (!$program) {
        // Well they can't possibly have completed it.
        $useriscomplete = null; // As noted this is ignored.
        $proglist = prog_get_all_programs($userid, '', '', '', false, true);
        $programs = array();
        foreach ($proglist as $progrow) {
            $prog = new program($progrow->id);
            // We include the program if no course filter is specified, or else if the program contains the course.
            if (!$courseid || $prog->content->contains_course($courseid)) {
                $programs[] = $prog;
            }
        }
    } else {
        $programs = array($program);
    }

    /** @var program[] $programs */
    foreach ($programs as $program) {

        // Clear the progressinfo cache to ensure it is calculated again
        \totara_program\progress\program_progress_cache::mark_progressinfo_stale($program->id, $userid);

        // First check if the program is already marked as complete for this user and do nothing if it is.
        if ($useriscomplete !== null) {
            if ($useriscomplete === true) {
                // We already know that they are complete.
                continue;
            }
        } else if (prog_is_complete($program->id, $userid)) {
            // He is already marked complete in the database, no need to proceed.
            continue;
        }

        // OK the user has not completed the program yet - lets see if they are complete.

        // Get the program content.
        $program_content = $program->get_content();

        if ($program->certifid) {
            // If this is a certification program get course sets for groups on the path the user is on.
            $path = get_certification_path_user($program->certifid, $userid);
        } else {
            // If standard program get the courseset groups (just one path).
            $path = CERTIFPATH_STD;
        }
        $courseset_groups = $program_content->get_courseset_groups($path);

        $courseset_group_completed = false;

        // Go through the course set groups to determine the user's completion status.
        foreach ($courseset_groups as $courseset_group) {
            $courseset_group_completed = prog_courseset_group_complete($courseset_group, $userid);

            if (!$courseset_group_completed) {
                // If the user has not completed the course group the program is not complete.
                // Set the timedue for the course set in this group with the shortest
                // time allowance so that course set due reminders will be triggered
                // at the appropriate time.
                $program_content->set_courseset_group_timedue($courseset_group, $userid);
                break;
            }
        }

        // Courseset_group_completed will be true if all the course groups in the program have been completed.
        if ($courseset_group_completed) {
            // Get maximum completion date of the coursesets in the current path.
            $sql = "SELECT MAX(pc.timecompleted) AS timecompleted
                      FROM {prog_completion} pc
                      JOIN {prog_courseset} pcs ON pcs.id = pc.coursesetid
                     WHERE pc.programid = ? AND pc.userid = ? AND pcs.certifpath = ?";
            $params = array($program->id, $userid, $path);
            $coursesetcompletion = $DB->get_record_sql($sql, $params);

            $completionsettings = array(
                'status'        => STATUS_PROGRAM_COMPLETE,
                'timecompleted' => $coursesetcompletion->timecompleted
            );
            $program->update_program_complete($userid, $completionsettings);
        }
    }
}

/**
 * Check if a courseset group is completed and optionally update courseset completion.
 *
 * @throws ProgramException
 * @param course_set[] $courseset_group a group of coursesets, as returned by get_courseset_groups
 * @param int $userid of the user for which completion should be checked/updated
 * @param boolean $updatecomplete also update courseset completion
 *
 * @return boolean true/false, dependent on whether the courseset group is complete or not
 */
function prog_courseset_group_complete($courseset_group, $userid, $updatecomplete = true) {

    // Keep track of the state of the last run of "and"ed courses.
    $accumulator = true;

    $last = end($courseset_group); // PHP7 do not use end() inside foreach!

    foreach ($courseset_group as $courseset) {
        // First check if the course set is already marked as complete.
        if ($courseset->is_courseset_complete($userid)) {
            $coursesetcomplete = true;
        } else if (!$updatecomplete) {
            $coursesetcomplete = false;
        } else {
            // Otherwise carry out a check to see if the course set should be marked as complete and mark it as complete if so.
            if ($courseset->check_courseset_complete($userid)) {
                $coursesetcomplete = true;
            } else {
                $coursesetcomplete = false;
            }
        }

        // Combine the current set into the "and" accumulator.
        $accumulator = $accumulator && $coursesetcomplete;

        switch ($courseset->nextsetoperator) {
            case NEXTSETOPERATOR_AND:
                // Do nothing. The current result was added to the accumulator and the next result will be added as well.
                break;
            case NEXTSETOPERATOR_OR:
                if ($accumulator) {
                    // The last run of "and"ed course sets were all true.
                    // We can stop now because the final result must be true.
                    return true;
                } else {
                    // The last run of "and"ed course sets must have had at least one incomplete.
                    // Reset the accumulator, starting a new run of "and"ed course sets.
                    $accumulator = true;
                }
                break;
            case NEXTSETOPERATOR_THEN:
            default:
                if ($courseset == $last) {
                    // This is the last course set. The final result is determined by the last run of "and"ed course sets.
                    return $accumulator;
                } else {
                    // We got THEN or no operator, but it wasn't at the end of the course set group.
                    throw new ProgramException(get_string('error:invalidcoursesetgroupoperator', 'totara_program'));
                }
                break;
        }
    }

    throw new ProgramException(get_string('error:nextcoursesetmissing', 'totara_program'));
}

/**
 * This function is to cope with program assignments set up
 * with completion deadlines 'from first login' where the
 * user had not yet logged in.
 *
 * Used by program_hourly_cron and observer for first user login event.
 *
 * @param int $user User object to check first firstlogin for
 * @return boolean True if all the update_learner_assignments() succeeded or there was nothing to do
 */
function prog_assignments_firstlogin($user) {
    global $DB;

    // NOTE: in theory this might get called multiple times at the same time,
    //       please make sure it would not result in fatal errors.

    $status = true;

    /* Future assignments for this user that can now be processed
     * (because this user has logged in)
     * we are looking for:
     * - future assignments for this user
     * - that relate to a "first login" assignment
     */
    $rs = $DB->get_recordset_sql(
        "SELECT pfua.* FROM
            {prog_future_user_assignment} pfua
        LEFT JOIN
            {prog_assignment} pa
            ON pfua.assignmentid = pa.id
        WHERE
            pfua.userid = ?
            AND pa.completionevent = ?"
    , array($user->id, COMPLETION_EVENT_FIRST_LOGIN));
    // Group the future assignments by 'programid'.
    $pending_by_program = totara_group_records($rs, 'programid');

    if ($pending_by_program) {
        foreach ($pending_by_program as $programid => $assignments) {

            // Update each program.
            $program = new program($programid);
            if ($program->update_learner_assignments()) {
                // If the update succeeded, delete the future assignments related to this program.
                $future_assignments_to_delete = array();
                foreach ($assignments as $assignment) {
                    $future_assignments_to_delete[] = $assignment->id;
                }
                if (!empty($future_assignments_to_delete)) {
                    list($deleteids_sql, $deleteids_params) = $DB->get_in_or_equal($future_assignments_to_delete);
                    $DB->delete_records_select('prog_future_user_assignment', "id {$deleteids_sql}", $deleteids_params);
                }
            } else {
                $status = false;
            }
        }
    }

    return $status;
}

/**
 * Returns an array of course objects for all the courses which
 * are part of any program.
 *
 * If an array of courseids are provided, the query is restricted
 * to only check for those courses
 *
 * @param array $courses Array of courseids to check for (optional) Defaults to all courses
 * @return array Array of course objects
 */
function prog_get_courses_associated_with_programs($courses = null) {
    global $DB;

    $limitcourses = (isset($courses) && is_array($courses) && count($courses) > 0);

    // restrict by list of courses provided
    if ($limitcourses) {
        list($insql, $inparams) = $DB->get_in_or_equal($courses);
        $insql = " AND c.id $insql";
    } else {
        $insql = '';
        $inparams = array();
    }

    // get courses mentioned in the courseset_course tab, and also any courses
    // linked to competencies used in any courseset
    // always exclude the site course and optionally restrict to a selected list of courses

    //mssql fails because of the 'ntext not comparable' issue
    //so we have to use a subquery to perform union
    $subquery = "SELECT c.id FROM {prog_courseset_course} pcc
                INNER JOIN {course} c ON c.id = pcc.courseid
                WHERE c.id <> ? $insql
            UNION
                SELECT c.id FROM {course} c
                JOIN {comp_criteria} cc ON c.id = cc.iteminstance
                AND cc.itemtype = ?
                WHERE cc.competencyid IN
                    (SELECT DISTINCT competencyid FROM {prog_courseset} WHERE competencyid <> 0)
                AND c.id <> ? $insql";
    $sql = "SELECT * FROM {course} WHERE id IN ($subquery)";

    // build up the params array
    $params = array(SITEID);
    $params = array_merge($params, $inparams);
    $params[] = 'coursecompletion';
    $params[] = SITEID;
    $params = array_merge($params, $inparams);

    return $DB->get_records_sql($sql, $params);
}

/**
 * Serves the folder files.
 *
 * @package  mod_folder
 * @category files
 * @param int $course course object
 * @param stdClass $cm course module
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function totara_program_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options=array()) {
    global $USER;

    $programid = $context->instanceid;
    $component = 'totara_program';
    $itemid = $args[0];
    $filename = $args[1];

    if (!isloggedin()) {
        send_file_not_found();
    }

    if (!has_capability("totara/program:viewprogram", $context)) {
        send_file_not_found();
    }

    $program = new program($programid);
    // If the file is in summary, overview, user is site admin or user has capability to edit the program don't worry
    // if they are assigned to the program.
    if (!(is_siteadmin($USER) ||
        has_capability('totara/program:configuredetails', $context)) &&
        $filearea != 'summary' &&
        $filearea != 'overviewfiles' &&
        $filearea != 'images')
    {
        if (!$program->user_is_assigned($USER->id)) {
            send_file_not_found();
        }
    }

    if (!$program->is_viewable($USER)) {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, $component, $filearea, $itemid, '/', $filename);

    if (empty($file)) {
        send_file_not_found();
    }

    send_stored_file($file, 60*60*24, 0, false, $options); // Enable long cache and disable forcedownload
}

/**
 * Returns options to use in program overviewfiles filemanager
 *
 * @param null|stdClass|course_in_list|int $program either object that has 'id' property or just the course id;
 *     may be empty if course does not exist yet (course create form)
 * @return array|null array of options such as maxfiles, maxbytes, accepted_types, etc.
 *     or null if overviewfiles are disabled
 */
function prog_program_overviewfiles_options($program) {
    global $CFG;
    if (empty($CFG->courseoverviewfileslimit)) {
        return null;
    }
    $accepted_types = preg_split('/\s*,\s*/', trim($CFG->courseoverviewfilesext), -1, PREG_SPLIT_NO_EMPTY);
    if (in_array('*', $accepted_types) || empty($accepted_types)) {
        $accepted_types = '*';
    } else {
        // Since config for $CFG->courseoverviewfilesext is a text box, human factor must be considered.
        // Make sure extensions are prefixed with dot unless they are valid typegroups
        foreach ($accepted_types as $i => $type) {
            if (substr($type, 0, 1) !== '.') {
                require_once($CFG->libdir. '/filelib.php');
                if (!count(file_get_typegroup('extension', $type))) {
                    // It does not start with dot and is not a valid typegroup, this is most likely extension.
                    $accepted_types[$i] = '.'. $type;
                    $corrected = true;
                }
            }
        }
        if (!empty($corrected)) {
            set_config('courseoverviewfilesext', join(',', $accepted_types));
        }
    }
    $options = array(
                    'maxfiles' => $CFG->courseoverviewfileslimit,
                    'maxbytes' => $CFG->maxbytes,
                    'subdirs' => 0,
                    'accepted_types' => $accepted_types
    );
    if (!empty($program->id)) {
        $options['context'] = context_program::instance($program->id);
    } else if (is_int($program) && $program > 0) {
        $options['context'] = context_program::instance($program);
    }
    return $options;
}

/**
 * Returns true if the category has programs in it (count does not include programs
 * in child categories)
 *
 * @param coursecat $category
 * @return bool
 */
function prog_has_programs($category) {
    global $DB;
    return $DB->record_exists_sql("SELECT 1 FROM {prog} WHERE category = :category AND certifid IS NULL",
            array('category' => $category->id));
}

/** Returns number of programs visible to the user
 *
 * @param coursecat $category
 * @param string $type Program or certification
 * @return int
 */
function prog_get_programs_count($category, $type = 'program') {
    // We have no programs at site level.
    if ($category->id == 0) {
        return 0;
    }
    $programs = prog_get_programs($category->id, '', 'p.id', $type);
    return count($programs);
}

/**
 * Can the current user delete programs in this category?
 *
 * @param int $categoryid
 * @return boolean
 */
function prog_can_delete_programs($categoryid) {
    global $DB;

    $context = context_coursecat::instance($categoryid);
    $sql = context_helper::get_preload_record_columns_sql('ctx');
    $programcontexts = $DB->get_records_sql('SELECT ctx.instanceid AS progid, '.
                    $sql. ' FROM {context} ctx '.
                    'WHERE ctx.path like :pathmask and ctx.contextlevel = :programlevel',
                    array('pathmask' => $context->path. '/%',
                          'programlevel' => CONTEXT_PROGRAM));
    foreach ($programcontexts as $ctxrecord) {
        context_helper::preload_from_record($ctxrecord);
        $programcontext = context_program::instance($ctxrecord->progid);
        if (!has_capability('totara/program:deleteprogram', $programcontext)) {
            return false;
        }
    }

    return true;
}

/**
 * Class to store information about one program in a list of programs
 *
 * Written to resemble {@link course_in_list} class in coursecatlib.php
 */
class program_in_list implements IteratorAggregate {

    /** @var stdClass record retrieved from DB, may have additional calculated property such as managers and hassummary */
    protected $record;

    /**
     * Creates an instance of the class from record
     *
     * @param stdClass $record except fields from prog table it may contain
     *     field hassummary indicating that summary field is not empty.
     *     Also it is recommended to have context fields here ready for
     *     context preloading
     */
    public function __construct(stdClass $record) {
        context_helper::preload_from_record($record);
        $this->record = new stdClass();
        foreach ($record as $key => $value) {
            $this->record->$key = $value;
        }
    }

    /**
     * Indicates if the program has non-empty summary field
     *
     * @return bool
     */
    public function has_summary() {
        if (isset($this->record->hassummary)) {
            return $this->record->hassummary;
        }
        if (!isset($this->record->summary)) {
            // We need to retrieve summary.
            $this->__get('summary');
        }
        $this->record->hassummary = !empty($this->record->summary);
        return $this->record->hassummary;
    }

    /**
     * Checks if program has any associated overview files
     *
     * @return bool
     */
    public function has_program_overviewfiles() {
        global $CFG;
        if (empty($CFG->courseoverviewfileslimit)) {
            return false;
        }
        $fs = get_file_storage();
        $context = context_program::instance($this->id);
        return !$fs->is_area_empty($context->id, 'program', 'overviewfiles');
    }

    /**
     * Returns all program overview files
     *
     * @return array array of stored_file objects
     */
    public function get_program_overviewfiles() {
        global $CFG;
        if (empty($CFG->courseoverviewfileslimit)) {
            return array();
        }
        require_once($CFG->libdir . '/filestorage/file_storage.php');
        $fs = get_file_storage();
        $context = context_program::instance($this->id);
        $files = $fs->get_area_files($context->id, 'totara_program', 'overviewfiles', false, 'filename', false);
        if (count($files)) {
            $overviewfilesoptions = prog_program_overviewfiles_options($this->id);
            $acceptedtypes = $overviewfilesoptions['accepted_types'];
            if ($acceptedtypes !== '*') {
                // Filter only files with allowed extensions.
                require_once($CFG->libdir . '/filelib.php');
                foreach ($files as $key => $file) {
                    if (!file_extension_in_typegroup($file->get_filename(), $acceptedtypes)) {
                        unset($files[$key]);
                    }
                }
            }
            if (count($files) > $CFG->courseoverviewfileslimit) {
                $files = array_slice($files, 0, $CFG->courseoverviewfileslimit, true);
            }
        }
        return $files;
    }

    public function __isset($name) {
        return isset($this->record->$name);
    }

    /**
     * Magic method to get a program property
     *
     * Returns any field from table prog (from cache or from DB) and/or special field 'hassummary'
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        global $DB;
        if (property_exists($this->record, $name)) {
            return $this->record->$name;
        } else if ($name === 'summary') {
            // retrieve fields summary and summaryformat together because they are most likely to be used together
            $record = $DB->get_record('prog', array('id' => $this->record->id), 'summary', MUST_EXIST);
            $this->record->summary = $record->summary;
            return $this->record->$name;
        } else if (array_key_exists($name, $DB->get_columns('prog'))) {
            // another field from table 'prog' that was not retrieved
            $this->record->$name = $DB->get_field('prog', $name, array('id' => $this->record->id), MUST_EXIST);
            return $this->record->$name;
        }
        debugging('Invalid program property accessed! ' . $name, DEBUG_DEVELOPER);
        return null;
    }

    /**
     * ALl properties are read only, sorry.
     * @param string $name
     */
    public function __unset($name) {
        debugging('Can not unset ' . get_class($this) . ' instance properties!', DEBUG_DEVELOPER);
    }

    /**
     * Magic setter method, we do not want anybody to modify properties from the outside
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        debugging('Can not change ' . get_class($this) . ' instance properties!', DEBUG_DEVELOPER);
    }

    /**
     * Create an iterator because magic vars can't be seen by 'foreach'.
     * Exclude context fields
     */
    public function getIterator() {
        $ret = array('id' => $this->record->id);
        foreach ($this->record as $property => $value) {
            $ret[$property] = $value;
        }
        return new ArrayIterator($ret);
    }
}

/**
 * Returns the minimum time for the program as html or returns the time string only
 *
 * @param int $seconds
 * @param boolean $timeonly false = output html, true = time string only
 * @return string
 */
function prog_format_seconds($seconds, $timeonly = false) {

    $years = floor($seconds / DURATION_YEAR);
    $str_years = get_string('xyears', 'totara_program', $years);
    $seconds = $seconds % DURATION_YEAR;

    $months = floor($seconds / DURATION_MONTH);
    $str_months = get_string('xmonths', 'totara_program', $months);
    $seconds = $seconds % DURATION_MONTH;

    $weeks = floor($seconds / DURATION_WEEK);
    $str_weeks = get_string('xweeks', 'totara_program', $weeks);
    $seconds = $seconds % DURATION_WEEK;

    $days = floor($seconds / DURATION_DAY);
    $str_days = get_string('xdays', 'totara_program', $days);

    $timestring = !empty($years) ? ' ' . $str_years : '';
    $timestring .= !empty($months) ? ' ' . $str_months : '';
    $timestring .= !empty($weeks) ? ' ' . $str_weeks : '';
    $timestring .= !empty($days) ? ' ' . $str_days : '';

    if ($timeonly) {
        return $timestring;
    }

    $output = '';
    $output .= html_writer::start_tag('div', array('id' => 'programtimerequired'));
    $output .= html_writer::start_tag('p');
    $output .= get_string('minprogramtimerequired', 'totara_program');
    $output .= $timestring;
    $output .= html_writer::end_tag('p');
    $output .= html_writer::end_tag('div');

    return $output;
}

/**
 * updates the course enrolments for a program enrolment plugin, unenrolling students if the program is unavailable
 * and re-enrol students if the program is available again.
 *
 * @param enrol_totara_program_plugin $program_plugin
 * @param int $programid If no programid is provided, then this is done for all programs - use only from cron
 * @param boolean $debugging
 * @return bool true if no problems, else false
 */
function prog_update_available_enrolments(enrol_totara_program_plugin $program_plugin, $programid = null, $debugging = false) {
    global $DB;

    // Get the default role that users will be put in if their assignment is unsuspended.
    $defaultrole = $program_plugin->get_config('roleid');
    if (empty($defaultrole)) {
        mtrace("No default role, not processing enrolment suspension!!!");
        return false;
    }

    // Get all the courses in all the coursesets of the program.
    $courseparams = array('itemtype' => 'coursecompletion');

    if (!empty($programid)) {
        $programsql1 = "AND pc.programid = :programid1";
        $programsql2 = "AND pc.programid = :programid2";
        $courseparams['programid1'] = $programid;
        $courseparams['programid2'] = $programid;
    } else {
        $programsql1 = $programsql2 = "";
    }

    $coursesql = "SELECT pcc.courseid
                    FROM {prog_courseset_course} pcc
                    JOIN {prog_courseset} pc ON pcc.coursesetid = pc.id
                   WHERE pc.competencyid = 0 {$programsql1}
                   UNION
                  SELECT cc.iteminstance AS courseid
                    FROM {prog_courseset} pc
                    JOIN {comp_criteria} cc ON cc.competencyid = pc.competencyid AND cc.itemtype = :itemtype
                   WHERE pc.competencyid != 0 {$programsql2}";
    $courseids = $DB->get_fieldset_sql($coursesql, $courseparams);

    foreach ($courseids as $courseid) {
        $userstosuspend = array();

        // Get all the users enrolled in the course through the program enrolment plugin.
        $enrolsql = "SELECT ue.*
                      FROM {user_enrolments} ue
                      JOIN {enrol} e
                        ON ue.enrolid = e.id
                     WHERE e.courseid = :cid
                       AND e.enrol = 'totara_program'";
        $enrolparams = array('cid' => $courseid);
        $enrolments = $DB->get_records_sql($enrolsql, $enrolparams);
        $instance = $program_plugin->get_instance_for_course($courseid);

        foreach ($enrolments as $enrolment) {
            // Check to see if the user should still be able to access the course.
            $userid = $enrolment->userid;

            $sql = "SELECT ppa.programid
                      FROM {dp_plan_program_assign} ppa
                      JOIN {prog} p ON p.id = ppa.programid AND p.available = :progisavailable1
                      JOIN {dp_plan} pln ON pln.id = ppa.planid
                      JOIN {prog_courseset} pcs ON ppa.programid = pcs.programid
                      JOIN {prog_courseset_course} pcsc ON pcs.id = pcsc.coursesetid AND pcsc.courseid = :courseid1
                     WHERE ppa.approved >= :ppaapproved1
                       AND pln.userid = :userid1
                       AND pln.status >= :plnstatusapproved1
                     UNION
                    SELECT ppa.programid
                      FROM {dp_plan_program_assign} ppa
                      JOIN {prog} p ON p.id = ppa.programid AND p.available = :progisavailable2
                      JOIN {dp_plan} pln ON pln.id = ppa.planid
                      JOIN {prog_courseset} pcs ON ppa.programid = pcs.programid
                      JOIN {comp_criteria} cc ON cc.competencyid = pcs.competencyid AND cc.iteminstance = :courseid2
                     WHERE ppa.approved >= :ppaapproved2
                       AND pln.userid = :userid2
                       AND pln.status >= :plnstatusapproved2
                     UNION
                    SELECT pua.programid
                      FROM {prog_user_assignment} pua
                      JOIN {prog} p ON p.id = pua.programid AND p.available = :progisavailable3
                      JOIN {prog_completion} pc ON pua.programid = pc.programid AND pua.userid = pc.userid
                      JOIN {prog_courseset} pcs ON pua.programid = pcs.programid
                      JOIN {prog_courseset_course} pcsc ON pcs.id = pcsc.coursesetid AND pcsc.courseid = :courseid3
                     WHERE pua.userid = :userid3
                       AND pc.coursesetid = 0
                     UNION
                    SELECT pua.programid
                      FROM {prog_user_assignment} pua
                      JOIN {prog} p ON p.id = pua.programid AND p.available = :progisavailable4
                      JOIN {prog_completion} pc ON pua.programid = pc.programid AND pua.userid = pc.userid
                      JOIN {prog_courseset} pcs ON pua.programid = pcs.programid
                      JOIN {comp_criteria} cc ON cc.competencyid = pcs.competencyid AND cc.iteminstance = :courseid4
                     WHERE pua.userid = :userid4
                       AND pc.coursesetid = 0";
            $params = array(
                'progisavailable1' => AVAILABILITY_TO_STUDENTS,
                'courseid1'  => $courseid,
                'ppaapproved1'  => DP_APPROVAL_APPROVED,
                'userid1'  => $userid,
                'plnstatusapproved1' => DP_PLAN_STATUS_APPROVED,
                'progisavailable2' => AVAILABILITY_TO_STUDENTS,
                'courseid2'  => $courseid,
                'ppaapproved2'  => DP_APPROVAL_APPROVED,
                'userid2'  => $userid,
                'plnstatusapproved2' => DP_PLAN_STATUS_APPROVED,
                'progisavailable3' => AVAILABILITY_TO_STUDENTS,
                'courseid3' => $courseid,
                'userid3' => $userid,
                'progisavailable4' => AVAILABILITY_TO_STUDENTS,
                'courseid4' => $courseid,
                'userid4' => $userid,
            );
            $result = $DB->get_records_sql($sql, $params);
            if (empty($result)) {
                // The user is not assigned to any program which contains the course.
                if ($enrolment->status == ENROL_USER_ACTIVE) {
                    $userstosuspend[] = $enrolment->userid;
                    if (CLI_SCRIPT && $debugging) {
                        mtrace("suspending enrolment for user-{$userid}");
                    }
                } // Else the enrolment is already suspended, so nothing to do.
            } else {
                // The user has an active assignment to a program which contains the course.
                if ($enrolment->status == ENROL_USER_SUSPENDED) {
                    // Not using bulk here, because we want to keep the timestart and timeend from before.
                    $program_plugin->enrol_user($instance, $userid, $defaultrole, $enrolment->timestart, $enrolment->timeend, ENROL_USER_ACTIVE);
                    if (CLI_SCRIPT && $debugging) {
                        mtrace("unsuspending enrolment for user-{$userid}");
                    }
                } // Else the enrolment is allready active, so nothing to do.
            }
        }
        if (!empty($userstosuspend)) {
            $program_plugin->process_program_unassignments($instance, $userstosuspend);
        }
    }

    return true;
}

/**
 * Prints an error if Program is not enabled
 *
 */
function check_program_enabled() {
    if (totara_feature_disabled('programs')) {
        print_error('programsdisabled', 'totara_program');
    }
}

/**
 * Prints an error if Certification is not enabled
 *
 */
function check_certification_enabled() {
    if (totara_feature_disabled('certifications')) {
        print_error('certificationsdisabled', 'totara_certification');
    }
}

/*
 * Checks the programs availability based off the available from/untill dates.
 *
 * @param int $availablefrom    - A time stamp of the time a program becomes available
 * @param int $availableuntil   - A time stamp of the time a program becomes unavailable
 * @return int                  - Either AVAILABILITY_NOT_TO_STUDENTS or AVAILABILITY_TO_STUDENTS
 */
function prog_check_availability($availablefrom, $availableuntil) {
    // Note: there used to be $timezone parameter which is now ignored.

    $now = time();

    if (!empty($availablefrom) && $availablefrom > $now) {
        return AVAILABILITY_NOT_TO_STUDENTS;
    }
    if (!empty($availableuntil) && $availableuntil < $now) {
        return AVAILABILITY_NOT_TO_STUDENTS;
    }

    return AVAILABILITY_TO_STUDENTS;
}

/**
 * Checks if a given program is required for a given user
 *
 * @param int $progid - The id of a program
 * @param int $userid - The id of a user
 * @return boolean
 */
function prog_required_for_user($progid, $userid) {
    global $DB;

    $countsql = "SELECT COUNT(*)
                   FROM {prog_user_assignment}
                  WHERE exceptionstatus <> :ex1
                    AND exceptionstatus <> :ex2
                    AND programid = :pid
                    AND userid = :uid";
    $countparams = array('ex1' => PROGRAM_EXCEPTION_RAISED, 'ex2' => PROGRAM_EXCEPTION_DISMISSED,
                         'pid' => $progid, 'uid' => $userid);

    if ($DB->count_records_sql($countsql, $countparams) > 0) {
        $params = array('programid' => $progid, 'userid' => $userid, 'coursesetid' => 0);
        $completion = $DB->get_field('prog_completion', 'status', $params);
        if ($completion == STATUS_PROGRAM_COMPLETE) {
            // Not required if the program is complete.
            return false;
        }

        // Required if the program is assigned but not complete.
        return true;
    }

    // Not required if there are no active assignments.
    return false;
}

/**
 * Generates the HTML to display a program icon that links to a page to view the program
 *
 * @deprecated Since Totara 12.0
 * @param int $progid               The id of a program
 * @param int $userid   optional    The id of a user, defaults to $USER if not set
 * @return html
 */
function prog_display_link_icon($progid, $userid = null) {
    debugging('The function prog_display_link_icon has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
    global $OUTPUT, $USER, $DB;

    $prog = new program($progid);
    $user = isset($userid) ? $DB->get_record('user', array('id' => $userid)) : $USER;

    $accessibility = prog_check_availability($prog->availablefrom, $prog->availableuntil);
    $accessible = $accessibility == AVAILABILITY_TO_STUDENTS;
    $assigned = $prog->user_is_assigned($user->id);

    $progicon = totara_get_icon($prog->id, TOTARA_ICON_TYPE_PROGRAM);
    $icon = html_writer::empty_tag('img', array('src' => $progicon, 'class' => 'course_icon', 'alt' => ''));

    if ($assigned && $accessible) {
        $url = new moodle_url('/totara/program/required.php', array('id' => $prog->id, 'userid' => $user->id));
        $html = $OUTPUT->action_link($url, $icon . $prog->fullname);
    } else if ($accessible) {
        $url = new moodle_url('/totara/program/view.php', array('id' => $prog->id));
        $html = $OUTPUT->action_link($url, $icon . $prog->fullname);
    } else {
        $html = $icon . $prog->fullname;
    }

    return $html;
}

/**
 * Display widget containing a program summary
 *
 * @param stdClass  $program    A program database record.
 * @param int       $userid     The userid of the record of learning.
 * @return string $out
 */
function prog_display_summary_widget($program, $userid = null) {
    global $USER;

    $params = array();
    if (($userid != null) && ($userid != $USER->id)) {
        $params['userid'] = $userid;
    }

    $params['id'] = $program->id;
    $url = new moodle_url("/totara/program/required.php", $params);
    $summary = file_rewrite_pluginfile_urls($program->summary, 'pluginfile.php',
            context_program::instance($program->id)->id, 'totara_program', 'summary', 0);

    $out = '';
    $out .= html_writer::start_tag('div', array('class' => 'cell'));
    $out .= html_writer::link($url, $program->fullname);
    $out .= html_writer::end_tag('div');
    $out .= html_writer::start_tag('div', array('class' => 'dp-summary-widget-description'));
    $out .= $summary . html_writer::end_tag('div');

    return $out;
}

/**
 * Display the due date for a program
 *
 * @param int $duedate
 * @param int $progid
 * @param int $userid
 * @param int $certifpath   Optional param telling us the path of the certification
 * @param int $certstatus   Optional param telling us the status of the certification
 * @param int $compstatus Whether the user has completed the program.
 * @param boolean $isexport Whether the output needs to be formatted for export.
 * @return string
 */
function prog_display_duedate($duedate, $progid, $userid, $certifpath = null, $certstatus = null, $compstatus = null, $isexport = false) {
    global $CFG, $PAGE;

    $renderer = $PAGE->get_renderer('totara_program');

    if (empty($duedate) || $duedate == COMPLETION_TIME_NOT_SET) {
        if ($certifpath == null && $certstatus == null) {
            // This is a program, display no due date set.
            return get_string('duedatenotset', 'totara_program');
        } else if ($certifpath == CERTIFPATH_CERT) {
            if ($certstatus == CERTIFSTATUS_EXPIRED) {
                // This certification has expired.
                return $renderer->error_text(get_string('overdue', 'totara_program'));
            } else {
                // This is the first run through of the certification and no due date was set.
                return get_string('duedatenotset', 'totara_program');
            }
        }
    }

    $out = '';
    if (!empty($duedate)) {
        $out .= userdate($duedate, get_string('strftimedatetime', 'langconfig'), 99, false);
    }

    $completed = isset($completion) ? $completion == STATUS_PROGRAM_COMPLETE : prog_is_complete($progid, $userid);
    if (!$completed && !$isexport) {
        $out .= $renderer->display_duedate_highlight_info($duedate);
    }

    return $out;
}


/**
 * Determines and displays the progress of this program for a specified user.
 *
 * Progress is determined by course set completion statuses.
 *
 * This function returns "Not assigned" if no completion data exists. This isn't an accurate criteria. Complete programs
 * will still have a completion record when unassigned, and certifications keep their prog_completion record when a user
 * is deleted in all states other than "Newly assigned".
 *
 * @access  public
 * @param int $programid
 * @param int $userid
 * @param int $certifpath (defaults to cert for programs)
 * @param bool $export
 * @return  string
 */
function prog_display_progress($programid, $userid, $certifpath = CERTIFPATH_CERT, $export = false) {
    global $DB, $PAGE ,$OUTPUT;

    $sql = "SELECT pc.*, cc.id AS ccid, prog.certifid
              FROM {prog_completion} pc
              JOIN {prog} prog ON prog.id = pc.programid
         LEFT JOIN {certif_completion} cc ON cc.certifid = prog.certifid AND cc.userid = pc.userid
             WHERE pc.programid = :programid AND pc.userid = :userid AND pc.coursesetid = 0";
    $prog_completion = $DB->get_record_sql($sql, array('programid' => $programid, 'userid' => $userid));

    if (empty($prog_completion) ||
        !empty($prog_completion->certifid) && $prog_completion->status != STATUS_PROGRAM_COMPLETE && empty($prog_completion->ccid)) {
        $out = get_string('notassigned', 'totara_program');
        return $out;
    } else {
        $program = new program($programid);
        $overall_progress = (int)$program->get_progress($userid);
    }

    if ($export) {
        return $overall_progress;
    }

    $tooltipstr = 'DEFAULTTOOLTIP';

    // Get relevant progress bar and return for display.
    $renderer = $PAGE->get_renderer('totara_core');
    $OUTPUT->render_from_template('core/progress_bar', ['progress' => $overall_progress, 'width' => '120','progresstext' => $overall_progress.'%', 'id' => 'prog_required_progress_'.$programid.'_'.$userid]);
    return $renderer->progressbar($overall_progress, 'medium', false, $tooltipstr);
}

/**
 * Checks accessiblity of the program for user if the user parameter is
 * passed to the function otherwise checks if the program is generally
 * accessible.
 *
 * @param stdClass  $program    A program database record
 * @param object    $user       If this parameter is included check availibilty to this user
 * @return boolean
 */
function prog_is_accessible($program, $user = null) {
    global $CFG;
    require_once($CFG->dirroot . '/totara/cohort/lib.php');

    // If a user is set check if they area a site admin, if so, let them have access.
    if (!empty($user->id)) {
        if (is_siteadmin($user->id)) {
            return true;
        }
    }

    // Check if this program is not available, if it's not then deny access.
    if ($program->available) {
        return true;
    }

    if (!empty($user->id)) {
        // Check capabilities.
        $context = context_program::instance($program->id);
        $isprogram = empty($program->certifid);
        if ($isprogram && has_capability('totara/program:viewhiddenprograms', $context) ||
            !$isprogram && has_capability('totara/certification:viewhiddencertifications', $context) ||
            !empty($CFG->audiencevisibility) && has_capability('totara/coursecatalog:manageaudiencevisibility', $context)) {
            return true;
        }
    }

    return false;
}


/**
 * Return true or false depending on whether or not the specified user has
 * completed a specified program
 *
 * @param int $progid   The id of a program
 * @param int $userid   The id of a user
 * @return bool
 */
function prog_is_complete($progid, $userid) {
    global $DB;

    if ($prog_completion_status = $DB->get_record('prog_completion', array('programid' => $progid, 'userid' => $userid, 'coursesetid' => 0))) {
        if ($prog_completion_status->status == STATUS_PROGRAM_COMPLETE) {
            return true;
        }
    }
    return false;
}

/**
 * Return true if the specified user has started but not completed the specified program,
 * otherwise return false.
 *
 * @param int $progid   The id of a program
 * @param int $userid   The id of a user
 * @return bool
 */
function prog_is_inprogress($progid, $userid) {
    global $DB;

    if ($prog_completion_status = $DB->get_record('prog_completion', array('programid' => $progid, 'userid' => $userid, 'coursesetid' => 0))) {
        if ($prog_completion_status->status == STATUS_PROGRAM_INCOMPLETE && $prog_completion_status->timestarted > 0) {
            return true;
        }
    }
    return false;
}

/**
 * Snippet to determine if a program is available based on the available fields.
 *
 * @param string $fieldalias Alias for the program table used in the query
 * @param string $separator Character separator between the alias and the field name
 * @param int|null $userid The user ID that wants to see the program (Unused)
 * @return array
 */
function get_programs_availability_sql($fieldalias, $separator, $userid = null) {
    $now = time();

    $availabilitysql = " (({$fieldalias}{$separator}available = :available) AND
                          ({$fieldalias}{$separator}availablefrom = 0 OR {$fieldalias}{$separator}availablefrom < :timefrom) AND
                          ({$fieldalias}{$separator}availableuntil = 0 OR {$fieldalias}{$separator}availableuntil > :timeuntil))";
    $availabilityparams = array('available' => AVAILABILITY_TO_STUDENTS, 'timefrom' => $now, 'timeuntil' => $now);

    return array($availabilitysql, $availabilityparams);
}

/**
 * Move prog_completion record to history.
 *
 * @param $record prog_completion record
 * @return bool|int Result of the insertion.
 */
function totara_prog_completion_to_history($record) {
    global $DB;

    return $DB->insert_record('prog_completion_history', $record);
}

/**
 * Get extension request setting for a particular program.
 *
 * @param int $programid The program ID
 * @return mixed
 */
function totara_prog_extension_allowed($programid) {
    global $DB;

    return $DB->get_field('prog', 'allowextensionrequests', array('id' => $programid));
}

/**
 * Get a list of current assignments to a program, taking into account any that have not yet been saved
 * to the database. The resulting array may need some extra processing, but can then be
 * passed to a totara dialog as the selected_items.
 *
 * @param int $programid - id of the program
 * @param string $selected - value of url param 'selected', will be ids separated by commas
 * @param string $removed - value of url param 'removed', will be ids separated by commas
 * @param int $assigntype - constant for assignment type, e.g. ASSIGNTYPE_INDIVIDUAL
 * @return array of ids that are assigned or selected, with $removed ids taken out.
 */
function totara_prog_removed_selected_ids($programid, $selected, $removed, $assigntype) {
    global $DB;

    $selectedids = array();

    // Get ids of items already assigned.
    $alreadyassigned = $DB->get_records('prog_assignment', array('programid' => $programid, 'assignmenttype' => $assigntype), '', 'assignmenttypeid');
    foreach ($alreadyassigned as $assignment) {
        $selectedids[$assignment->assignmenttypeid] = $assignment->assignmenttypeid;
    }

    // Add selected but not yet saved to DB.
    if (!empty($selected)) {
        $selected = explode(',', $selected);
        foreach ($selected as $selectedid) {
            $selectedids[$selectedid] = $selectedid;
        }
    }

    // Remove removed but not yet removed from DB.
    if (!empty($removed)) {
        $removed = explode(',', $removed);
        foreach ($removed as $removedid) {
            if (isset($selectedids[$removedid])) {
                unset($selectedids[$removedid]);
            }
        }
    }

    return $selectedids;
}

/**
 * Checks the state of a user's program completion record.
 *
 * When an inconsistent state is detected, this function assumes that the status is correct, and reports
 * problems with other fields relative to this. It is possible that the problem (or solution to the
 * problem) is that the status is incorrect, and the other fields are correct, but it's not possible to
 * distinguish between the two scenarios.
 *
 * @param stdClass $progcompletion as stored in the prog_completion table (not all fields are required)
 * @return array describes any problems (error key => form field)
 */
function prog_get_completion_errors($progcompletion) {
    $errors = array();

    if ($progcompletion->timedue == COMPLETION_TIME_UNKNOWN) {
        $errors['error:timedueunknown'] = 'timedue';
    }

    switch ($progcompletion->status) {
        case STATUS_PROGRAM_INCOMPLETE:
            if ($progcompletion->timecompleted > 0) {
                $errors['error:stateincomplete-timecompletednotempty'] = 'timecompleted';
            }
            break;
        case STATUS_PROGRAM_COMPLETE:
            if ($progcompletion->timecompleted <= 0) {
                $errors['error:statecomplete-timecompletedempty'] = 'timecompleted';
            }
            break;
        default:
            $errors['error:progstatusinvalid'] = 'status';
            break;
    }

    return $errors;
}

/**
 * Convert the errors returned by prog_get_completion_errors into errors that can be used for form validation.
 *
 * @param array $errors as returned by prog_get_completion_errors
 * @return array of form validation errors
 */
function prog_get_completion_form_errors($errors) {
    $formerrors = array();
    foreach ($errors as $stringkey => $formkey) {
        if (isset($formerrors[$formkey])) {
            $formerrors[$formkey] .= '<br>' . get_string($stringkey, 'totara_program');
        } else {
            $formerrors[$formkey] = get_string($stringkey, 'totara_program');
        }
    }
    return $formerrors;
}

/**
 * Given a set of errors, calculate a unique problem key (just sort and concatenate errors).
 *
 * @param array $errors as returned by prog_get_completion_errors
 * @return string
 */
function prog_get_completion_error_problemkey($errors) {
    if (empty($errors)) {
        return '';
    }

    $errorkeys = array_keys($errors);
    sort($errorkeys);
    return implode('|', $errorkeys);
}

/**
 * Given a problem key returned by prog_get_completion_error_problemkey, return any known explanation or solutions, in html format.
 *
 * @param string $problemkey as returned by prog_get_completion_error_problemkey
 * @param int $programid if provided (non-0), url should only fix problems for this program
 * @param int $userid if provided (non-0), url should only fix problems for this user
 * @param bool $returntoeditor true if you want to return to the certification editor for this user/cert, default false for checker
 * @return string html formatted, possibly including url links to activate known fixes
 */
function prog_get_completion_error_solution($problemkey, $programid = 0, $userid = 0, $returntoeditor = false) {
    if (empty($problemkey)) {
        return '';
    }

    $params = array(
        'progorcert' => 'program',
        'progid' => $programid,
        'userid' => $userid,
        'returntoeditor' => $returntoeditor,
        'sesskey' => sesskey()
    );
    $baseurl = new moodle_url('/totara/program/check_completion.php', $params);

    switch ($problemkey) {
        // See certs for examples of automated fixes. Remove this when a fix is implemented.
        case 'error:timedueunknown':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixassignedtimedueunknown');
            $html = get_string('error:info_fixtimedueunknown', 'totara_program') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:missingprogcompletion':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixmissingcompletionrecords');
            $html = get_string('error:info_fixmissingprogcompletion', 'totara_program') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:unassignedincompleteprogcompletion':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixunassignedincompletecompletionrecords');
            $html = get_string('error:info_fixunassignedincompletecompletionrecord', 'totara_program') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:orphanedexception':
            $url1 = clone($baseurl);
            $url1->param('fixkey', 'fixorphanedexceptionassign');
            $html = get_string('error:info_fixorphanedexceptionassign', 'totara_program') . '<br>' .
                html_writer::link($url1, get_string('clicktofixcompletions', 'totara_program'));
            $url2 = clone($baseurl);
            $url2->param('fixkey', 'fixorphanedexceptionrecalculate');
            $html .= '<br>' . get_string('error:info_fixorphanedexceptionrecalculate', 'totara_program') . '<br>' .
                html_writer::link($url2, get_string('clicktofixcompletions', 'totara_program'));
            break;
        default:
            $html = get_string('error:info_unknowncombination', 'totara_program');
            break;
    }

    return $html;
}

/**
 * Applies the specified fix to program completion record.
 *
 * @param string $fixkey the key for the specific fix to be applied (see switch in code)
 * @param int $programid if provided (non-0), only fix problems for this program
 * @param int $userid if provided (non-0), only fix problems for this user
 */
function prog_fix_completions($fixkey, $programid = 0, $userid = 0) {
    global $DB;

    // Creating missing program completion records is handled in a separate function, just to keep things tidy.
    if ($fixkey == 'fixmissingcompletionrecords') {
        prog_fix_missing_completions($programid, $userid);
        return;
    }

    // Deleting unassigned incomplete program completion records is handled in a separate function, just to keep things tidy.
    if ($fixkey == 'fixunassignedincompletecompletionrecords') {
        prog_fix_unassigned_incomplete_completions($programid, $userid);
        return;
    }

    // Fixing orphaned exceptions is handled in a separate function, just to keep things tidy.
    if ($fixkey == 'fixorphanedexceptionassign') {
        prog_fix_orphaned_exceptions_assign($programid, $userid, 'program');
        return;
    }
    if ($fixkey == 'fixorphanedexceptionrecalculate') {
        prog_fix_orphaned_exceptions_recalculate($programid, $userid, 'program');
        return;
    }

    // Get all completion records, applying the specified filters.
    $sql = "SELECT pc.*
              FROM {prog_completion} pc
              JOIN {prog} prog
                ON prog.id = pc.programid
             WHERE pc.coursesetid = 0
               AND prog.certifid IS NULL";
    $params = array();
    if ($programid) {
        $sql .= " AND pc.programid = :programid";
        $params['programid'] = $programid;
    }
    if ($userid) {
        $sql .= " AND pc.userid = :userid";
        $params['userid'] = $userid;
    }

    $rs = $DB->get_recordset_sql($sql, $params);

    foreach ($rs as $progcompletion) {
        // Check for errors.
        $errors = prog_get_completion_errors($progcompletion);

        // Nothing wrong, so skip this record.
        if (empty($errors)) {
            continue;
        }

        $problemkey = prog_get_completion_error_problemkey($errors);
        $result = "";

        // Only fix if this is an exact match for the specified problem.
        switch ($fixkey) {
            case 'fixassignedtimedueunknown':
                if ($problemkey == 'error:timedueunknown') {
                    $result = prog_fix_timedue($progcompletion);
                }
                break;
            default:
                break;
        }

        // Nothing happened, so no need to update or log.
        if (empty($result)) {
            continue;
        }

        prog_write_completion($progcompletion, $result);
    }
}

/**
 * Set the timedue to COMPLETION_TIME_NOT_SET
 *
 * @param stdClass $progcompletion a corresponding record from prog_completion to be fixed
 * @return string message for transaction log
 */
function prog_fix_timedue(&$progcompletion) {
    $progcompletion->timedue = COMPLETION_TIME_NOT_SET;

    return 'Automated fix \'prog_fix_timedue\' was applied<br>
        <ul><li>\'Program due date\' was set to ' . COMPLETION_TIME_NOT_SET . '</li></ul>';
}

/**
 * Creates missing program completion records, limited by the specified filters.
 *
 * @param int $programid if provided (non-0), only fix problems for this program
 * @param int $userid if provided (non-0), only fix problems for this user
 */
function prog_fix_missing_completions($programid = 0, $userid = 0) {
    $affectedcompletionsrs = prog_find_missing_completions($programid, $userid);

    $now = time(); // Mark them all with exactly the same time, to make it easier to debug.
    $message = 'Automated fix \'prog_fix_missing_prog_completions\' was applied - prog_completion record was created';

    foreach ($affectedcompletionsrs as $progcompletion) {
        $data = array('timecreated' => $now);
        prog_create_completion($progcompletion->programid, $progcompletion->userid, $data, $message);
    }

    $affectedcompletionsrs->close();
}

/**
 * Deletes prog_completion records of users who are not assigned but have incomplete completion records, limited by the specified filters.
 *
 * @param int $programid if provided (non-0), only fix problems for this program
 * @param int $userid if provided (non-0), only fix problems for this user
 */
function prog_fix_unassigned_incomplete_completions($programid = 0, $userid = 0) {
    $affectedcompletionsrs = prog_find_unassigned_incomplete_completions($programid, $userid);

    $message = 'Automated fix \'prog_fix_unassigned_incomplete_prog_completions\' was applied - prog_completion record was deleted';

    foreach ($affectedcompletionsrs as $progcompletion) {
        prog_delete_completion($progcompletion->programid, $progcompletion->userid, $message);
    }

    $affectedcompletionsrs->close();
}

/**
 * Fixes orphaned exceptions by resolving them, assigning the user.
 *
 * @param int $programid if provided (non-0), only fix problems for this program
 * @param int $userid if provided (non-0), only fix problems for this user
 * @param string $progorcert either 'program' or 'certification'
 */
function prog_fix_orphaned_exceptions_assign($programid = 0, $userid = 0, $progorcert = 'program') {
    global $DB;

    $orphanedexceptionsrs = prog_find_orphaned_exceptions($programid, $userid, $progorcert);

    $message = 'Automated fix \'prog_fix_orphaned_exceptions_assign\' was applied';

    foreach ($orphanedexceptionsrs as $orphanedexception) {
        // We don't know what type of exception this is, so can't "handle" it through the prog_exception class. Just clear it.
        $params = array(
            'programid' => $orphanedexception->programid,
            'userid' => $orphanedexception->userid,
            'exceptionstatus' => PROGRAM_EXCEPTION_RAISED
        );
        $DB->set_field('prog_user_assignment', 'exceptionstatus', PROGRAM_EXCEPTION_RESOLVED, $params);

        prog_log_completion($orphanedexception->programid, $orphanedexception->userid, $message);

        // Trigger an event. This happens when resolving exceptions normally, so we'll do it here as well.
        $event = \totara_program\event\program_assigned::create(
            array(
                'objectid' => $orphanedexception->programid,
                'context' => context_program::instance($orphanedexception->programid),
                'userid' => $orphanedexception->userid,
            )
        );
        $event->trigger();
    }

    $orphanedexceptionsrs->close();
}

/**
 * Fixes orphaned exceptions by recalculating them, assigning the user.
 *
 * @param int $programid if provided (non-0), only fix problems for this program
 * @param int $userid if provided (non-0), only fix problems for this user
 * @param string $progorcert either 'program' or 'certification'
 */
function prog_fix_orphaned_exceptions_recalculate($programid = 0, $userid = 0, $progorcert = 'program') {
    global $DB;

    $orphanedexceptionsrs = prog_find_orphaned_exceptions($programid, $userid, $progorcert);

    $message = 'Automated fix \'prog_fix_orphaned_exceptions_recalculate\' was applied';

    foreach ($orphanedexceptionsrs as $orphanedexception) {
        $params = array(
            'programid' => $orphanedexception->programid,
            'userid' => $orphanedexception->userid,
            'exceptionstatus' => PROGRAM_EXCEPTION_RAISED
        );
        $userassignments = $DB->get_records('prog_user_assignment', $params, '', 'id, assignmentid');
        foreach ($userassignments as $userassignment) {
            $program = new program($orphanedexception->programid);
            if ($progorcert == 'program') {
                $progcompletion = prog_load_completion($orphanedexception->programid, $orphanedexception->userid);
            } else {
                list($certcompletion, $progcompletion) = certif_load_completion($orphanedexception->programid, $orphanedexception->userid);
            }

            if (empty($progcompletion)) {
                $timedue = COMPLETION_TIME_NOT_SET;
            } else {

                // Skip completed programs (includes certifications which are certified and window is not yet open).
                if ($progcompletion->status == STATUS_PROGRAM_COMPLETE) {
                    $userassignment->exceptionstatus = PROGRAM_EXCEPTION_NONE;
                    $DB->update_record('prog_user_assignment', $userassignment);
                    continue;
                }

                // Skip certifications which are on the recert path or are expired.
                if ($progorcert == 'certification') {
                    if ($certcompletion->certifpath == CERTIFPATH_RECERT ||
                        $certcompletion->status == CERTIFSTATUS_EXPIRED) {
                        $userassignment->exceptionstatus = PROGRAM_EXCEPTION_NONE;
                        $DB->update_record('prog_user_assignment', $userassignment);
                        continue;
                    }
                }
                $timedue = $progcompletion->timedue;
            }
            $progassignment = new stdClass();
            $progassignment->id = $userassignment->assignmentid;
            if ($program->update_exceptions($orphanedexception->userid, $progassignment, $timedue)) {
                $userassignment->exceptionstatus = PROGRAM_EXCEPTION_RAISED;
            } else {
                $userassignment->exceptionstatus = PROGRAM_EXCEPTION_NONE;
            }
            $DB->update_record('prog_user_assignment', $userassignment);
        }

        prog_log_completion($orphanedexception->programid, $orphanedexception->userid, $message);

        // Trigger an event. This happens when resolving exceptions normally, so we'll do it here as well.
        $event = \totara_program\event\program_assigned::create(
            array(
                'objectid' => $orphanedexception->programid,
                'context' => context_program::instance($orphanedexception->programid),
                'userid' => $orphanedexception->userid,
            )
        );
        $event->trigger();
    }

    $orphanedexceptionsrs->close();
}

/**
 * Insert or update prog_completion record. Checks are performed to ensure that the data is valid before it
 * can be written to the db.
 *
 * NOTE: $ignoreproblemkey should only be used by prog_fix_completions!!! If specified, the record will be
 *       written to the db even if the records have the specified problem, and only that exact problem, or
 *       no problem at all, otherwise the update will not occur.
 *
 * @param stdClass $progcompletion A prog_completion record to be saved, including 'id' if this is an update.
 * @param string $message If provided, will be added to the program completion log message.
 * @param mixed $ignoreproblemkey String returned by prog_get_completion_error_problemkey which can be ignored.
 * @return True if the record was successfully created or updated.
 */
function prog_write_completion($progcompletion, $message = '', $ignoreproblemkey = false) {
    global $DB;

    // Decide if this is an insert or update.
    $isinsert = empty($progcompletion->id);

    // Ensure the record matches the database records.
    if ($isinsert) {
        $sql = "SELECT prog.id, prog.certifid, pc.id AS pcid
                  FROM {prog} prog
             LEFT JOIN {prog_completion} pc
                    ON pc.programid = prog.id AND pc.userid = :pcuserid AND pc.coursesetid = 0
                 WHERE prog.id = :programid";
        $params = array('programid' => $progcompletion->programid, 'pcuserid' => $progcompletion->userid);
        $prog = $DB->get_record_sql($sql, $params);

        if (empty($prog) || !empty($prog->pcid)) {
            print_error(get_string('error:updatinginvalidcompletionrecord', 'totara_program'));
        }

        if (!empty($prog->certifid)) {
            throw new coding_exception("prog_write_completion was used to insert a prog_completion record relating to a certification - this must never happen!");
        }

        if (empty($message)) {
            $message = "Completion record created";
        }
    } else {
        $sql = "SELECT pc.id, prog.certifid
                  FROM {prog_completion} pc
                  JOIN {prog} prog
                    ON prog.id = pc.programid
                 WHERE pc.id = :pcid
                   AND pc.programid = :programid
                   AND pc.userid = :userid
                   AND pc.coursesetid = 0";
        $params = array('pcid' => $progcompletion->id, 'programid' => $progcompletion->programid, 'userid' => $progcompletion->userid);

        $existingrecord = $DB->record_exists_sql($sql, $params);
        if (empty($existingrecord)) {
            print_error(get_string('error:updatinginvalidcompletionrecord', 'totara_program'));
        }

        if (!empty($existingrecord->certifid)) {
            throw new coding_exception("prog_write_completion was used to update a prog_completion record relating to a certification - this must never happen!");
        }

        if (empty($message)) {
            $message = "Completion record updated";
        }
    }

    // Before applying the changes, verify that the new record is in a valid state.
    $errors = prog_get_completion_errors($progcompletion);
    if (!empty($errors)) {
        $problemkey = prog_get_completion_error_problemkey($errors);
    }

    if (empty($errors) || $problemkey === $ignoreproblemkey) {
        if ($isinsert) {
            $DB->insert_record('prog_completion', $progcompletion);
        } else {
            $DB->update_record('prog_completion', $progcompletion);
        }

        prog_write_completion_log($progcompletion->programid, $progcompletion->userid, $message);

        return true;
    } else {
        // Some error was detected, and it wasn't specified in $ignoreproblemkey.
        prog_log_completion($progcompletion->programid, $progcompletion->userid,
            'An attempt was made to write changes, but the data was invalid. Message of caller was:<br>' . $message);
        return false;
    }
}

/**
 * Write a record to the program completion log.
 *
 * @param int    $programid    ID of the program.
 * @param int    $userid       ID of the user who's record is being affected, or null if it affects the whole program.
 * @param string $description  Describing what happened, including details. Can include simple html formatting.
 * @param null   $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
 */
function prog_log_completion($programid, $userid, $description, $changeuserid = null) {
    global $DB, $USER;

    if (is_null($changeuserid)) {
        $changeuserid = $USER->id;
    }

    $record = new stdClass();
    $record->programid = $programid;
    $record->userid = $userid;
    $record->changeuserid = $changeuserid;
    $record->description = $description;
    $record->timemodified = time();

    $DB->insert_record('prog_completion_log', $record);
}

/**
 * Write a log message (in the program completion log) when a program completion has been added or edited.
 *
 * @param int $programid
 * @param int $userid
 * @param string $message If provided, will be added at the start of the log message (instead of "Completion record edited")
 * @param null $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
 */
function prog_write_completion_log($programid, $userid, $message = '', $changeuserid = null) {
    $progcompletion = prog_load_completion($programid, $userid);

    $description = prog_calculate_completion_description($progcompletion, $message);

    prog_log_completion(
        $programid,
        $userid,
        $description,
        $changeuserid
    );
}

/**
 * Calculate the description string for a program completion log message.
 *
 * @param stdClass $progcompletion
 * @param string $message If provided, will be added at the start of the log message (instead of "Completion record edited")
 * @return string
 */
function prog_calculate_completion_description($progcompletion, $message = '') {
    $progstatus = '';
    switch ($progcompletion->status) {
        case STATUS_PROGRAM_INCOMPLETE:
            $progstatus = 'Not complete';
            break;
        case STATUS_PROGRAM_COMPLETE:
            $progstatus = 'Complete';
            break;
    }

    if (empty($message)) {
        $message = 'Completion record edited';
    }

    $description = $message . '<br>' .
        '<ul><li>Status: ' . $progstatus . '</li>' .
        '<li>Time started: ' . prog_format_log_date($progcompletion->timestarted) . '</li>' .
        '<li>Due date: ' . prog_format_log_date($progcompletion->timedue) . '</li>' .
        '<li>Completion date: ' . prog_format_log_date($progcompletion->timecompleted) . '</li></ul>';

    return $description;
}

/**
 * Insert or update a (non-zero id) course set completion record.
 *
 * Do not use this function for writing course completion records (course set id 0). It will fail.
 *
 * @param stdClass $cscompletion A prog_completion record to be saved, including 'id' if this is an update.
 * @param string $message If provided, will be added to the program completion log message.
 * @return True if the record was successfully created or updated.
 */
function prog_write_courseset_completion($cscompletion, $message = '') {
    global $DB;

    if (empty($cscompletion->coursesetid)) {
        print_error("Tried to use prog_write_courseset_completion with program completion data or missing course set id");
    }

    // Decide if this is an insert or update.
    $isinsert = empty($cscompletion->id);

    // Ensure the record matches the database records.
    if ($isinsert) {
        $sql = "SELECT pcs.id, pc.id AS pcid
                  FROM {prog_courseset} pcs
                  JOIN {prog} p ON p.id = pcs.programid
             LEFT JOIN {prog_completion} pc
                    ON pc.coursesetid = pcs.id AND pc.userid = :userid AND pc.coursesetid = pcs.id
                 WHERE pcs.id = :coursesetid AND p.id = :programid";
        $params = array(
            'coursesetid' => $cscompletion->coursesetid,
            'programid' => $cscompletion->programid,
            'userid' => $cscompletion->userid
        );
        $pcs = $DB->get_record_sql($sql, $params);
        if (empty($pcs) || !empty($pcs->pcid)) {
            print_error('Call to prog_write_courseset_completion insert with completion record that does not match the existing record');
        }

        if (empty($message)) {
            $message = "Course set completion record created";
        }
    } else {
        $sql = "SELECT pc.id
                  FROM {prog_completion} pc
                 WHERE pc.id = :pcid
                   AND pc.programid = :programid
                   AND pc.userid = :userid
                   AND pc.coursesetid = :coursesetid";
        $params = array(
            'pcid' => $cscompletion->id,
            'coursesetid' => $cscompletion->coursesetid,
            'programid' => $cscompletion->programid,
            'userid' => $cscompletion->userid
        );
        if (!$DB->record_exists_sql($sql, $params)) {
            print_error('Call to prog_write_courseset_completion update with completion record that does not match the existing record');
        }

        if (empty($message)) {
            $message = "Course set completion record updated";
        }
    }

    if (!in_array($cscompletion->status, array(STATUS_COURSESET_COMPLETE, STATUS_COURSESET_INCOMPLETE))) {
        prog_log_completion($cscompletion->programid, $cscompletion->userid,
            'An attempt was made to write changes, but the data was invalid. Message of caller was:<br>' . $message);
        return false;
    }

    if ($isinsert) {
        $DB->insert_record('prog_completion', $cscompletion);
    } else {
        $DB->update_record('prog_completion', $cscompletion);
    }

    prog_log_completion($cscompletion->programid, $cscompletion->userid, $message);
    return true;
}

/**
 * Delete program course set completion records, logging it in the prog completion log.
 *
 * Note that the $path param should only be used for certifications, not normal programs. This is not checked by the function.
 *
 * @param int $programid
 * @param int $userid
 * @param int $path null if course sets in all paths should be reset, else CERTIFPATH_CERT or CERTIFPATH_RECERT
 * @param string $message If provided, will override the default program completion log message.
 */
function prog_reset_course_set_completions($programid, $userid, $path = null, $message = '') {
    global $CERTIFPATH, $DB;

    // State changed from complete (progs) or before window opens (certs) to something else, so delete the related course set completion records.
    $sql = "UPDATE {prog_completion}
               SET status = :statusincomplete,
                   timestarted = 0,
                   timedue = 0,
                   timecompleted = 0
             WHERE programid = :programid1
               AND userid = :userid";
    $params = array(
        'statusincomplete' => STATUS_COURSESET_INCOMPLETE,
        'programid1' => $programid,
        'userid' => $userid
    );

    if (is_null($path)) {
        $sql .= " AND coursesetid != 0";
    } else {
        $sql .= " AND coursesetid IN (SELECT id
                                        FROM {prog_courseset}
                                       WHERE programid = :programid2
                                         AND certifpath = :path)";
        $params['programid2'] = $programid;
        $params['path'] = $path;
    }

    $DB->execute($sql, $params);

    if (empty($message)) {
        if (is_null($path)) {
            $message = 'Course set records reset';
        } else {
            $message = 'Course set records reset for path: ' . $CERTIFPATH[$path];
        }
    }

    prog_log_completion(
        $programid,
        $userid,
        $message
    );
}

/**
 * Processes completion data submitted by an admin - transforms it to look like a program completion record, suitable
 * for use in $DB->update_record().
 *
 * Note that the prog_completion record must already exist in the database (matching the user and program id
 * supplied), and their record ids will be included in the returned data. Creating new completion records should be
 * achieved automatically by assigning a user to a program, not manually in a form.
 *
 * @param object $submitted contains the data submitted by the form
 * @return object $progcompletion compatible with the corresponding database record
 */
function prog_process_submitted_edit_completion($submitted) {
    // Get existing record id.
    $existingrecord = prog_load_completion($submitted->id, $submitted->userid);

    $now = time();

    $progcompletion = new stdClass();
    $progcompletion->id = $existingrecord->id;
    $progcompletion->programid = $submitted->id;
    $progcompletion->userid = $submitted->userid;
    $progcompletion->status = $submitted->status;
    // Fix stupid timedue should be -1 for not set problem.
    $progcompletion->timedue = ($submitted->timeduenotset === 'yes') ? COMPLETION_TIME_NOT_SET : $submitted->timedue;
    $progcompletion->timecompleted = $submitted->timecompleted;
    $progcompletion->timemodified = $now;

    return $progcompletion;
}

/**
 * Load a prog_completion record out of the db.
 *
 * Use this function to make sure you don't accidentally get a course set completion record.
 *
 * @param int $programid
 * @param int $userid
 * @param bool $mustexist If records are missing, default true causes an error, false returns false
 * @return mixed
 */
function prog_load_completion($programid, $userid, $mustexist = true) {
    global $DB;

    $progcompletion = $DB->get_record('prog_completion', array('programid' => $programid, 'userid' => $userid, 'coursesetid' => 0));

    if (empty($progcompletion)) {
        if ($mustexist) {
            $a = array('programid' => $programid, 'userid' => $userid);
            print_error(get_string('error:cannotloadcompletionrecord', 'totara_program', $a));
        } else {
            return false;
        }
    }

    return $progcompletion;
}

/**
 * Load a (non-zero id) course set completion record out of the db.
 *
 * Use this function to make sure you get the correct course set completion record.
 *
 * @param int $coursesetid
 * @param int $userid
 * @param bool $mustexist If records are missing, default true causes an error, false returns false
 * @return mixed
 */
function prog_load_courseset_completion($coursesetid, $userid, $mustexist = true) {
    global $DB;

    if ($coursesetid == 0) {
        print_error("Tried to use prog_load_courseset_completion to load a program completion record");
    }

    $cscompletion = $DB->get_record('prog_completion', array('coursesetid' => $coursesetid, 'userid' => $userid));

    if (empty($cscompletion)) {
        if ($mustexist) {
            print_error("Tried to load course set completion record which doesn't exist for coursesetid: {$coursesetid}, userid: {$userid}");
        } else {
            return false;
        }
    }

    return $cscompletion;
}

/**
 * Create a (non-zero id) course set completion record for the user in the program.
 *
 * $data keys can contain status, timestarted, timecreated, timedue, timecompleted, organisationid, positionid
 * Any other data keys will prevent the record being created and will return false.
 *
 * If $data is specified, the resulting prog_completion record must be error-free, or else the record will
 * not be created! Check the result of this function to ensure that the record was successfully created.
 *
 * @param int $coursesetid
 * @param int $userid
 * @param array $data containing any field => values that should be set, overriding the defaults
 * @param string $message If provided, will override the default record creation message
 * @return true if the record was successfully created or updated.
 */
function prog_create_courseset_completion($coursesetid, $userid, $data = array(), $message = '') {
    global $DB;

    if ($coursesetid == 0) {
        print_error("Tried to use prog_create_courseset_completion to create a program completion record");
    }

    $now = time();

    $programid = $DB->get_field('prog_courseset', 'programid', array('id' => $coursesetid));

    $progcompletion = new stdClass();
    $progcompletion->programid = $programid;
    $progcompletion->userid = $userid;
    $progcompletion->coursesetid = $coursesetid;
    $progcompletion->status = STATUS_COURSESET_INCOMPLETE;
    $progcompletion->timestarted = 0;
    $progcompletion->timecreated = $now;
    $progcompletion->timedue = COMPLETION_TIME_NOT_SET;
    $progcompletion->timecompleted = 0;

    $alloweddatafields = array(
        'status',
        'timestarted',
        'timecreated',
        'timedue',
        'timecompleted',
        'organisationid',
        'positionid',
    );

    foreach ($data as $field => $value) {
        if (!in_array($field, $alloweddatafields)) {
            prog_log_completion($progcompletion->programid, $progcompletion->userid,
                'prog_create_courseset_completion was provided unknown data (' . $field . '). Message of caller was:<br>' . $message);
            return false;
        }
        $progcompletion->$field = $value;
    }

    prog_write_courseset_completion($progcompletion, $message);

    return true;
}

/**
 * Formats a date for the program completion log.
 *
 * @param int $date
 * @return string
 */
function prog_format_log_date($date) {
    if ($date > 0) {
        return userdate($date, '%d %B %Y, %H:%M', 0) . ' (' . $date . ')';
    } else if (is_null($date)) {
        return "Not set (null)";
    } else {
        return "Not set ({$date})";
    }
}

/**
 * Load all prog_completion records out of the db. Excludes certifications.
 *
 * Use this function to make sure you get the correct records.
 *
 * @param int $userid
 * @return array(stdClass)
 */
function prog_load_all_completions($userid) {
    global $DB;

    $sql = "SELECT pc.*
              FROM {prog_completion} pc
              JOIN {prog} p ON p.id = pc.programid
             WHERE pc.userid = :userid AND pc.coursesetid = 0 AND p.certifid IS NULL
    ";
    $progcompletions = $DB->get_records_sql($sql, array('userid' => $userid));

    return $progcompletions;
}

/**
 * Deletes a user's program completion record, only if the user is no longer required to complete the program.
 *
 * This should be called after a user has been removed from a program or a program is removed from a user's learning plan.
 *
 * Only call this function for programs. It does NOT protect against being passed program ids belonging to certifications!!!
 *
 * @param int $programid
 * @param int $userid
 */
function prog_conditionally_delete_completion($programid, $userid) {
    $program = new program($programid);

    // Check that the program is not still assigned to the program.
    if ($program->assigned_to_users_required_learning($userid)) {
        return;
    }

    // Check that the program is not still assigned to a Learning Plan.
    if ($program->assigned_to_users_non_required_learning($userid)) {
        return;
    }

    // Check that the program is not complete - the current prog_completion will be treated as a history record.
    if (prog_is_complete($program->id, $userid)) {
        return;
    }

    prog_delete_completion($programid, $userid, 'Current prog_completion conditionally deleted');
}

/**
 * Delete a program completion record, logging it in the prog completion log.
 *
 * Normally you should use prog_conditionally_delete_completion, which will decide what to do with the records.
 *
 * !!! Only use this function if you're absolutely sure that the record needs to be deleted. !!!
 * !!! This function should only be used by prog_conditionally_delete_completion,            !!!
 * !!! certif_conditionally_delete_completion and by the program completion editor.          !!!
 *
 * @param $programid
 * @param $userid
 * @param string $message If provided, will override the default program completion log message.
 */
function prog_delete_completion($programid, $userid, $message = '') {
    global $DB;

    $DB->delete_records('prog_completion', array('programid' => $programid, 'userid' => $userid, 'coursesetid' => 0));

    if (empty($message)) {
        $message = 'Current prog_completion deleted';
    }

    // Record the change in the program completion log.
    prog_log_completion(
        $programid,
        $userid,
        $message
    );
}

/**
 * Find all the completion records which have problems.
 *
 * The results are designed to be used by totara_program_renderer::get_completion_checker_results
 *
 * Each stdClass item in $fulllist is indexed by the problemkey and contains:
 *  ->problem string short explaination of what the problem is (obtained from the problemkey)
 *  ->userfullname string the full name of the affected user
 *  ->programname string the full name of the program
 *  ->editcompletionurl moodle_url a link to the completion editor for this user/cert
 *
 * Each stdClass item in $aggregatelist is indexed by the problemkey and contains:
 *  ->problem string short explaination of what the problem is (obtained from the problemkey)
 *  ->category string describing the general type of problem (Files, Consistentcy, etc)
 *  ->solution string long explanation and any info about consequences, how the problem can be manually fixed and/or links to automated fixes etc
 *  ->count int how many records (given the specified filters) are affected by this problem
 *
 * $totalcount reports the total number of records that are checked given the specified filters.
 *
 * @param int $programid
 * @param int $userid
 * @return array(array $fulllist, array $aggregatelist, int $totalcount)
 */
function prog_get_all_completions_with_errors($programid = 0, $userid = 0) {
    global $DB;

    $aggregatelist = array();
    $fulllist = array();
    $totalcount = 0;

    // Check existing prog_completions for inconsistency errors.
    $sql = "SELECT pc.userid, pc.programid, pc.status, pc.timedue, pc.timecompleted, prog.fullname
              FROM {prog_completion} pc
              JOIN {prog} prog ON prog.id = pc.programid
             WHERE pc.coursesetid = 0
               AND prog.certifid IS NULL";
    $params = array();
    if (!empty($userid)) {
        $sql .= " AND pc.userid = :userid";
        $params['userid'] = $userid;
    }
    if (!empty($programid)) {
        $sql .= " AND pc.programid = :programid";
        $params['programid'] = $programid;
    }
    $allcompletionsrs = $DB->get_recordset_sql($sql, $params);

    foreach ($allcompletionsrs as $progcompletion) {
        $errors = prog_get_completion_errors($progcompletion);

        if (!empty($errors)) {
            // Aggregate this combination of errors.
            $problemkey = prog_get_completion_error_problemkey($errors);
            // If the problem key doesn't exist in the aggregate list already then create it.
            if (!isset($aggregatelist[$problemkey])) {
                $newaggregate = new stdClass();
                $newaggregate->count = 0;

                $errorstrings = array();
                foreach ($errors as $errorkey => $errorfield) {
                    $errorstrings[] = get_string($errorkey, 'totara_program');
                }
                $newaggregate->problem = implode('<br>', $errorstrings);

                $newaggregate->category = get_string('problemcategoryconsistency', 'totara_program');

                // Solution is designed to fix all records affected by this problem with the given filters.
                $newaggregate->solution = prog_get_completion_error_solution($problemkey, $programid, $userid);

                $aggregatelist[$problemkey] = $newaggregate;
            }
            $aggregatelist[$problemkey]->count++;

            $affected = new stdClass();
            $affected->problem = $aggregatelist[$problemkey]->problem;
            $affected->userfullname = fullname($DB->get_record('user', array('id' => $progcompletion->userid)));
            $affected->programname = format_string($progcompletion->fullname);
            $affected->editcompletionurl = new moodle_url('/totara/program/edit_completion.php',
                array('id' => $progcompletion->programid, 'userid' => $progcompletion->userid));
            $affectedkey = $progcompletion->programid . '-' . $progcompletion->userid;

            $fulllist[$affectedkey] = $affected;
        }
        $totalcount++;
    }

    $allcompletionsrs->close();

    // Check for missing prog_completion records.
    $missingcompletionsrs = prog_find_missing_completions($programid, $userid);

    $problemkey = 'error:missingprogcompletion';
    foreach ($missingcompletionsrs as $missingcompletion) {
        $totalcount++;

        // If the problem key doesn't exist in the aggregate list already then create it.
        if (!isset($aggregatelist[$problemkey])) {
            $newaggregate = new stdClass();
            $newaggregate->count = 0;

            $newaggregate->problem = get_string($problemkey, 'totara_program');

            $newaggregate->category = get_string('problemcategoryfiles', 'totara_program');

            // Solution is designed to fix all records affected by this problem with the given filters.
            $newaggregate->solution = prog_get_completion_error_solution($problemkey, $programid, $userid);

            $aggregatelist[$problemkey] = $newaggregate;
        }
        $aggregatelist[$problemkey]->count++;

        $affected = new stdClass();
        $affected->problem = $aggregatelist[$problemkey]->problem;
        $affected->userfullname = fullname($DB->get_record('user', array('id' => $missingcompletion->userid)));
        $affected->programname = format_string($missingcompletion->fullname);
        $affected->editcompletionurl = new moodle_url('/totara/program/edit_completion.php',
            array('id' => $missingcompletion->programid, 'userid' => $missingcompletion->userid));
        $affectedkey = $missingcompletion->programid . '-' . $missingcompletion->userid;

        $fulllist[$affectedkey] = $affected;
    }

    $missingcompletionsrs->close();

    // Check for unassigned incomplete prog_completion records.
    $unassignedincompletecompletionsrs = prog_find_unassigned_incomplete_completions($programid, $userid);

    $problemkey = 'error:unassignedincompleteprogcompletion';
    foreach ($unassignedincompletecompletionsrs as $unassignedincompletecompletion) {
        // Don't increment total count, because these have already been counted earlier.

        // If the problem key doesn't exist in the aggregate list already then create it.
        if (!isset($aggregatelist[$problemkey])) {
            $newaggregate = new stdClass();
            $newaggregate->count = 0;

            $newaggregate->problem = get_string($problemkey, 'totara_program');

            $newaggregate->category = get_string('problemcategoryfiles', 'totara_program');

            // Solution is designed to fix all records affected by this problem with the given filters.
            $newaggregate->solution = prog_get_completion_error_solution($problemkey, $programid, $userid);

            $aggregatelist[$problemkey] = $newaggregate;
        }
        $aggregatelist[$problemkey]->count++;

        $affected = new stdClass();
        $affected->problem = $aggregatelist[$problemkey]->problem;
        $affected->userfullname = fullname($DB->get_record('user', array('id' => $unassignedincompletecompletion->userid)));
        $affected->programname = format_string($unassignedincompletecompletion->fullname);
        $affected->editcompletionurl = new moodle_url('/totara/program/edit_completion.php',
            array('id' => $unassignedincompletecompletion->programid, 'userid' => $unassignedincompletecompletion->userid));
        $affectedkey = $unassignedincompletecompletion->programid . '-' . $unassignedincompletecompletion->userid;

        $fulllist[$affectedkey] = $affected;
    }

    $unassignedincompletecompletionsrs->close();

    // Check for orphaned exceptions.
    $orphanedexceptionrs = prog_find_orphaned_exceptions($programid, $userid, 'program');

    $problemkey = 'error:orphanedexception';
    foreach ($orphanedexceptionrs as $orphanedexception) {
        // If the problem key doesn't exist in the aggregate list already then create it.
        if (!isset($aggregatelist[$problemkey])) {
            $newaggregate = new stdClass();
            $newaggregate->count = 0;

            $newaggregate->problem = get_string($problemkey, 'totara_program');

            $newaggregate->category = get_string('problemcategoryexceptions', 'totara_program');

            // Solution is designed to fix all records affected by this problem with the given filters.
            $newaggregate->solution = prog_get_completion_error_solution($problemkey, $programid, $userid);

            $aggregatelist[$problemkey] = $newaggregate;
        }
        $aggregatelist[$problemkey]->count++;

        $affected = new stdClass();
        $affected->problem = $aggregatelist[$problemkey]->problem;
        $affected->userfullname = fullname($DB->get_record('user', array('id' => $orphanedexception->userid)));
        $affected->programname = format_string($orphanedexception->fullname);
        $affected->editcompletionurl = new moodle_url('/totara/program/edit_completion.php',
            array('id' => $orphanedexception->programid, 'userid' => $orphanedexception->userid));
        $affectedkey = $orphanedexception->programid . '-' . $orphanedexception->userid;

        $fulllist[$affectedkey] = $affected;
    }

    $orphanedexceptionrs->close();

    return array($fulllist, $aggregatelist, $totalcount);
}

/**
 * Returns a recordset containing all users who are assigned to programs but are missing a prog_completion record.
 * These records should exist!
 *
 * @param int $programid if provided (non-0), only find problems for this program
 * @param int $userid if provided (non-0), only find problems for this user
 * @return moodle_recordset containing programid, userid and program's fullname
 */
function prog_find_missing_completions($programid = 0, $userid = 0) {
    global $DB;

    $params = array();
    $progwhere = "";
    $dpwhere = "";

    if (!empty($userid)) {
        $progwhere .= " AND pua.userid = :userid1";
        $dpwhere .= " AND dpp.userid = :userid2";
        $params['userid1'] = $userid;
        $params['userid2'] = $userid;
    }

    if (!empty($programid)) {
        $progwhere .= " AND pua.programid = :programid1";
        $dpwhere .= " AND dpppa.programid = :programid2";
        $params['programid1'] = $programid;
        $params['programid2'] = $programid;
    }

    $sql = "SELECT pua.programid, pua.userid, p.fullname
              FROM {prog_user_assignment} pua
              JOIN {prog} p ON p.id = pua.programid AND p.certifid IS NULL
         LEFT JOIN {prog_completion} pc ON pc.programid = pua.programid AND pc.userid = pua.userid AND pc.coursesetid = 0
             WHERE pc.id IS NULL {$progwhere}
             UNION
            SELECT dpppa.programid, dpp.userid, p.fullname
              FROM {dp_plan_program_assign} dpppa
              JOIN {prog} p ON p.id = dpppa.programid AND p.certifid IS NULL
              JOIN {dp_plan} dpp ON dpp.id = dpppa.planid
         LEFT JOIN {prog_completion} pc ON pc.programid = dpppa.programid AND pc.userid = dpp.userid AND pc.coursesetid = 0
             WHERE pc.id IS NULL {$dpwhere}";

    return $DB->get_recordset_sql($sql, $params);
}

/**
 * Returns a recordset containing all users who are not assigned and have an incomplete prog_completion record.
 * These records shouldn't exist!
 *
 * @param int $programid if provided (non-0), only find problems for this program
 * @param int $userid if provided (non-0), only find problems for this user
 * @return moodle_recordset containing programid, userid and program's fullname
 */
function prog_find_unassigned_incomplete_completions($programid = 0, $userid = 0) {
    global $DB;

    $params = array('statusincomplete' => STATUS_PROGRAM_INCOMPLETE);
    $where = "";

    if (!empty($userid)) {
        $where .= " AND pc.userid = :userid";
        $params['userid'] = $userid;
    }

    if (!empty($programid)) {
        $where .= " AND pc.programid = :programid";
        $params['programid'] = $programid;
    }

    $sql = "SELECT pc.programid, pc.userid, p.fullname
              FROM {prog_completion} pc
              JOIN {prog} p ON p.id = pc.programid AND p.certifid IS NULL
         LEFT JOIN {prog_user_assignment} pua ON pua.programid = pc.programid AND pua.userid = pc.userid
         LEFT JOIN {dp_plan} dpp ON dpp.userid = pc.userid
         LEFT JOIN {dp_plan_program_assign} dpppa ON dpppa.planid = dpp.id AND dpppa.programid = pc.programid
             WHERE pc.coursesetid = 0 AND pc.status = :statusincomplete {$where}
               AND pua.id IS NULL AND dpppa.id IS NULL";

    return $DB->get_recordset_sql($sql, $params);
}

/**
 * Returns a recordset containing all users who have orphaned exceptions.
 *
 * These are prog_user_assignments which have an exception, but there is no matching prog_exception, not even
 * belonging to another prog_assignment for the same user and program.
 *
 * @param int $programid if provided (non-0), only find problems for this program/certification
 * @param int $userid if provided (non-0), only find problems for this user
 * @param string $progorcert either 'program' or 'certification'
 * @return moodle_recordset containing programid, userid and program's fullname
 */
function prog_find_orphaned_exceptions($programid = 0, $userid = 0, $progorcert = 'program') {
    global $DB;

    $params = array('exceptionstatusraised' => PROGRAM_EXCEPTION_RAISED);
    $where = "";

    if (!empty($userid)) {
        $where .= " AND pua.userid = :userid";
        $params['userid'] = $userid;
    }

    if (!empty($programid)) {
        $where .= " AND pua.programid = :programid";
        $params['programid'] = $programid;
    }

    if ($progorcert == 'program') {
        $progorcertsql = 'AND p.certifid IS NULL';
    } else {
        $progorcertsql = 'AND p.certifid IS NOT NULL';
    }

    $sql = "SELECT DISTINCT pua.programid, pua.userid, p.fullname
              FROM {prog_user_assignment} pua
              JOIN {prog} p ON p.id = pua.programid {$progorcertsql}
         LEFT JOIN {prog_exception} pe ON pe.programid = pua.programid AND pe.userid = pua.userid
             WHERE pua.exceptionstatus = :exceptionstatusraised
               AND pe.id IS NULL
                   {$where}";

    return $DB->get_recordset_sql($sql, $params);
}


/**
 * Create a program completion record for the user in the program.
 *
 * $data keys can contain status, timestarted, timecreated, timedue, timecompleted, organisationid, positionid
 * Any other data keys will prevent the record being created and will return false.
 *
 * If $data is specified, the resulting prog_completion record must be error-free, or else the record will
 * not be created! Check the result of this function to ensure that the record was successfully created.
 *
 * @param int $programid
 * @param int $userid
 * @param array $data containing any field => values that should be set, overriding the defaults
 * @param string $message If provided, will override the default record creation message
 * @return true if the record was successfully created or updated.
 */
function prog_create_completion($programid, $userid, $data = array(), $message = '') {
    $now = time();

    $progcompletion = new stdClass();
    $progcompletion->programid = $programid;
    $progcompletion->userid = $userid;
    $progcompletion->coursesetid = 0;
    $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
    $progcompletion->timestarted = 0;
    $progcompletion->timecreated = $now;
    $progcompletion->timedue = COMPLETION_TIME_NOT_SET;
    $progcompletion->timecompleted = 0;

    $alloweddatafields = array(
        'status',
        'timestarted',
        'timecreated',
        'timedue',
        'timecompleted',
        'organisationid',
        'positionid',
    );

    foreach ($data as $field => $value) {
        if (!in_array($field, $alloweddatafields)) {
            return false;
        }
        $progcompletion->$field = $value;
    }

    return prog_write_completion($progcompletion, $message);
}

/**
 * Returns programs tagged with a specified tag.
 *
 * @param core_tag_tag $tag
 * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
 *             are displayed on the page and the per-page limit may be bigger
 * @param int $fromctx context id where the link was displayed, may be used by callbacks
 *            to display items in the same context first
 * @param int $ctx context id where to search for records
 * @param bool $rec search in subcontexts as well
 * @param int $page 0-based number of page being displayed
 * @return \core_tag\output\tagindex
 */
function prog_get_tagged_programs($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0) {

    $count = $tag->count_tagged_items('totara_program', 'prog', '', array());
    $perpage = $exclusivemode ? 24 : 5;
    $content = '';
    $displaycount = 0;

    if ($count) {
        $items = array();
        $proglist = $tag->get_tagged_items('totara_program', 'prog', $page * $perpage, $perpage, '', array());
        foreach ($proglist as $prog) {
            $program = new program($prog->id);

            if (empty($ctx) || $ctx == context_system::instance()->id) {
                $ctxmatch = true;
            } else {
                $context = $program->get_context();
                $regex = '/^(\/\d+)*(\/'.$ctx.')(\/\d+)*$/';
                $ctxmatch = ($context->id == $ctx || preg_match($regex, $context->path));
            }

            if ($program->is_viewable() && $ctxmatch) {
                $displaycount++;
                $url = new moodle_url('/totara/program/view.php', array('id' => $prog->id));
                $items[] = html_writer::link($url, $prog->fullname);
            }
        }
        $content .= html_writer::alist($items);
    }
    $totalpages = ceil($displaycount / $perpage);

    return new core_tag\output\tagindex($tag, 'totara_program', 'prog', $content,
            $exclusivemode, $fromctx, $ctx, $rec, $page, $totalpages);
}
