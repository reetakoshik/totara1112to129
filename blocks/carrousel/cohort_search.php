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
 *
 *  Remember to generate and include the query SQL in your WHERE clause with:
 *     totara_dialog_get_search_clause()
 */
switch ($searchtype) {
    /**
     * Cohort search
     */
    case 'cohort':
        if (!empty($this->customdata['instancetype'])) {
            $formdata['hidden']['instancetype'] = $this->customdata['instancetype'];
        }
        if (!empty($this->customdata['instanceid'])) {
            $formdata['hidden']['blockid'] = $this->customdata['instanceid'];
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
            create_function('$a', 'return true;'));
        $equal = true;
        if (!isset($instanceid) && $contextids) {
            // User passed capability check, search through entire cohort including all contextids.
            $contextids = array('0');
            $equal = false;
        }
        list($contextssql, $contextparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_QM, 'param', $equal);

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
                {$searchsql} AND {cohort}.contextid {$contextssql}
        ";
        $params = array_merge($params, $contextparams);
        if (!empty($this->customdata['current_cohort_id'])) {
            $search_info->sql .= ' AND {cohort}.id != ? ';
            $params[] = $this->customdata['current_cohort_id'];
        }
        $search_info->order = ' ORDER BY name ASC';
        $search_info->params = $params;
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
                    $item = new stdClass();

                    if (method_exists($this, 'search_can_display_result') && !$this->search_can_display_result($result->id)) {
                        continue;
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
