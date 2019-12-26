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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/totaratablelib.php');
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');
require_once($CFG->dirroot.'/totara/core/searchlib.php');

define('DEFAULT_PAGE_SIZE', 50);
define('SHOW_ALL_PAGE_SIZE', 5000);

$prefix         = required_param('prefix', PARAM_ALPHA);
$frameworkid    = optional_param('frameworkid', 0, PARAM_INT);
$perpage        = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);  // How many per page.
$page           = optional_param('page', 0, PARAM_INT);
$hide           = optional_param('hide', 0, PARAM_INT);
$show           = optional_param('show', 0, PARAM_INT);
$setdisplay     = optional_param('setdisplay', -1, PARAM_INT);
$moveup         = optional_param('moveup', 0, PARAM_INT);
$movedown       = optional_param('movedown', 0, PARAM_INT);
$search         = optional_param('search', '', PARAM_TEXT);
$format         = optional_param('format', '', PARAM_TEXT);

hierarchy::check_enable_hierarchy($prefix);

$sitecontext    = context_system::instance();
$shortprefix    = hierarchy::get_short_prefix($prefix);
$searchactive = (strlen(trim($search)) > 0);
// Hide move arrows when a search active because the hierarchy.
// Is no longer properly represented.
$canmoveitems = (!$searchactive);

$hierarchy = hierarchy::load_hierarchy($prefix);

// Load framework.
$framework   = $hierarchy->get_framework($frameworkid, true);

// If no frameworks exist.
if (!$framework) {
    // Redirect to frameworks page.
    redirect(new moodle_url('index.php', array('prefix' => $prefix)));
    exit();
}

// Check if custom types exist.
$types = $hierarchy->get_types();

// Cache user capabilities.
extract($hierarchy->get_permissions());

$canview       = has_capability('totara/hierarchy:view' . $prefix . 'frameworks', $sitecontext);
$canmanagetype = (count($types) > 0) && has_capability('totara/hierarchy:update' . $prefix . 'type', $sitecontext);

// Process actions.
if ($canupdateitems) {
    require_capability('totara/hierarchy:update'.$prefix, $sitecontext);
    if ($hide && confirm_sesskey()) {
        $hierarchy->hide_item($hide);
    } elseif ($show && confirm_sesskey()) {
        $hierarchy->show_item($show);
    } elseif ($moveup && confirm_sesskey()) {
        $hierarchy->reorder_hierarchy_item($moveup, HIERARCHY_ITEM_ABOVE);
    } elseif ($movedown && confirm_sesskey()) {
        $hierarchy->reorder_hierarchy_item($movedown, HIERARCHY_ITEM_BELOW);
    }
}

// Set page context so that export can use functions like format_text.
$PAGE->set_context(context_system::instance());

if ($format != '') {
    \totara_hierarchy\event\framework_exported::create_from_instance($prefix, $framework)->trigger();
    $hierarchy->export_data($format, false);
    die;
}

// If setdisplay parameter set, update the displaymode.
if ($setdisplay != -1) {
    $DB->set_field($shortprefix.'_framework', 'hidecustomfields', $setdisplay, array('id' => $frameworkid));
    $displaymode = $setdisplay;
} else {
    $displaymode = $framework->hidecustomfields;
}

// Setup page and check permissions.
$urlparams = array('prefix' => $prefix, 'frameworkid' => $frameworkid);

if (!$canview) {
    print_error('accessdenied', 'admin');
}

if ($canmanage) {
    admin_externalpage_setup($prefix.'manage', null, $urlparams);
} else {
    $detailsstr = get_string($prefix . 'details', 'totara_hierarchy');
    $url_params = array('prefix' => $prefix, 'frameworkid' => $frameworkid);
    $PAGE->set_url(new moodle_url('/totara/hierarchy/index', $url_params));
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title($detailsstr);
}

$PAGE->navbar->add(format_string($framework->fullname));
echo $OUTPUT->header();

$table = new totara_table($prefix.'-framework-index-'.$frameworkid);
$table->define_baseurl(new moodle_url('/totara/hierarchy/index.php', array('prefix' => $prefix, 'frameworkid' => $frameworkid)));

// Some common/base sql snippets.
$from = "FROM {{$shortprefix}} hier";
$where = "WHERE hier.frameworkid = :frameworkid";
$params = array('frameworkid' => $frameworkid);
$orderby  = "ORDER BY sortthread";

// Get the total number of records.
$sql = "SELECT COUNT(hier.id)
          {$from}
         {$where}";
$matchcount = $DB->count_records_sql($sql, array('frameworkid' => $frameworkid));
$filteredcount = $matchcount; // Set filteredcount to the same as matchcount, and replace later if filtering.

$headerdata = array();

$row = new stdClass();
$row->type = 'name';
$row->value = new stdClass();
$row->value->fullname = get_string('name');
$headerdata[] = $row;

if ($extrafields = $hierarchy->get_extrafields()) {
    foreach ($extrafields as $extrafield) {
        $row = new stdClass();
        $row->type = 'extrafield';
        $row->extrafield = $extrafield;
        $row->value = new stdClass();
        $row->value->fullname = get_string($prefix . $extrafield, 'totara_hierarchy');
        $headerdata[] = $row;
    }
}

if ($canupdateitems || $candeleteitems) {
    $row = new stdClass();
    $row->type = 'actions';
    $row->value = new stdClass();
    $row->value->fullname = get_string('actions');
    $headerdata[] = $row;
}

$columns = array();
$headers = array();

foreach ($headerdata as $key => $head) {
    $columns[] = $head->type.$key;
    $headers[] = $head->value->fullname;
}
$table->define_headers($headers);
$table->define_columns($columns);

if ($searchactive) {
    $urlparams['search'] = $search;
}
$baseurl = new moodle_url('/totara/hierarchy/index.php', $urlparams);
$table->define_baseurl($baseurl);
$table->set_attribute('class', 'hierarchy-index fullwidth');
$table->setup();
$table->pagesize($perpage, $filteredcount);

$customfieldrss = array();

// If a search is happening, or custom fields are being displayed, also join to custom fields.
if ($searchactive || !$displaymode) {

    $allcustomfields = $DB->get_records($shortprefix.'_type_info_field');

    // Add the search criteria to the where.
    if ($searchactive) {
        // Extract quoted strings from query.
        $keywords = totara_search_parse_keywords($search);

        // Construct the sql that will search for the given keywords.
        $searchfields = array('hier.fullname', 'hier.shortname', 'hier.description', 'hier.idnumber', 'cf.data');
        list($searchsql, $searchparams) =
            totara_search_get_keyword_where_clause($keywords, $searchfields, SQL_PARAMS_NAMED, 'search');

        // Construct the full WHERE clause and add any params needed.
        $rows = "SELECT DISTINCT hier.id
                   {$from}
                   LEFT JOIN {{$shortprefix}_type_info_data} cf
                     ON hier.id = cf.{$prefix}id
                  {$where}
                    AND ($searchsql)";
        $where = "WHERE hier.id IN ($rows)";
        $params = array_merge($params, $searchparams);

        // Count how many records there are (includes search criteria, if any).
        $sql = "SELECT COUNT(hier.id)
                  {$from}
                 {$where}";
        $filteredcount = $DB->count_records_sql($sql, $params);

        // Set filtered page size.
        $table->pagesize($perpage, $filteredcount);
    }

    // Chunk the custom fields for SELECT, in case there are lots of them (MySQL has maximum 61 JOINs).
    $customfieldsbatches = array_chunk($allcustomfields, 50);

    // Get each batch of custom fields in a separate records set. They will be combined when used.
    foreach ($customfieldsbatches as $customfields) {
        $select = "SELECT hier.id";
        $joins = "";
        foreach ($customfields as $customfield) {
            // Add one join per custom field.
            $fieldid = $customfield->id;
            $select .= ", cf_{$fieldid}.id AS cf_{$fieldid}_itemid, cf_{$fieldid}.data AS cf_{$fieldid}";
            $joins .= " LEFT JOIN {{$shortprefix}_type_info_data} cf_{$fieldid}
                          ON hier.id = cf_{$fieldid}.{$prefix}id AND cf_{$fieldid}.fieldid = {$fieldid}";
        }

        $sql = "{$select}
                 {$from}
                 {$joins}
                 {$where}
                 {$orderby}";
        $customfieldrss[] = $DB->get_recordset_sql($sql, $params, $table->get_page_start(), $table->get_page_size());
    }
}

// Get the base set of columns.
$sql = "SELECT hier.*
             {$from}
             {$where}
             {$orderby}";
$records = $DB->get_recordset_sql($sql, $params, $table->get_page_start(), $table->get_page_size());

echo $OUTPUT->container($OUTPUT->action_link(
    new moodle_url('/totara/hierarchy/framework/index.php', array('prefix' => $prefix)), '&laquo; ' .
            get_string($prefix.'backtoallframeworks', 'totara_hierarchy')), 'back-link'
);
echo $OUTPUT->heading(format_string($framework->fullname));

$framework->description = file_rewrite_pluginfile_urls($framework->description, 'pluginfile.php', $sitecontext->id,
        'totara_hierarchy', $shortprefix.'_framework', $frameworkid);
echo $OUTPUT->container($framework->description);
echo html_writer::tag('div', '', array('class' => 'clearfix'));

$table->add_toolbar_content($hierarchy->display_action_buttons($cancreateitems, $page), 'right');
$table->add_toolbar_content($hierarchy->display_bulk_actions_picker($cancreateitems, $canupdateitems, $candeleteitems,
        $canmanagetype, $page), 'left' , 'top', 1);
$table->add_toolbar_content($hierarchy->display_showhide_detail_button($displaymode, $search, $page), 'right', 'top', 1);
$placeholder = get_string('search') . ' ' . format_string($framework->fullname);
$table->add_toolbar_content($hierarchy->display_search_box($search, $placeholder), 'left');

$table->add_toolbar_pagination('right', 'top', 1);
$table->add_toolbar_pagination('left', 'bottom');
$table->set_no_records_message(get_string('no'.$prefix, 'totara_hierarchy'));

echo html_writer::tag('div', '', array('class' => 'clearfix'));

if ($searchactive) {
    if ($filteredcount > 0) {
        $a = new stdClass();
        $a->filteredcount = $filteredcount;
        $a->allcount = $matchcount;
        $a->query = $search;
        echo html_writer::start_tag('p');
        echo html_writer::tag('strong', get_string('showingxofyforsearchz', 'totara_hierarchy', $a));
    } else {
        echo html_writer::start_tag('p');
        echo html_writer::tag('strong', get_string('noresultsforsearchx', 'totara_hierarchy', $search));
    }
    echo $OUTPUT->action_link(new moodle_url('/totara/hierarchy/index.php',
            array('prefix' => $prefix, 'frameworkid' => $frameworkid)), get_string('clearsearch', 'totara_hierarchy'));
    echo html_writer::end_tag('p');
}

$num_on_page = 0;
if ($matchcount > 0) {
    if ($records) {

        $params = array();
        if ($page) {
            $params['page'] = $page;
        }
        if ($searchactive) {
            $params['query'] = urlencode($search);
        }

        // Cache this hierarchies types.
        $types = $hierarchy->get_types();

        // Figure out which custom fields are used by which types.
        $cfields = $DB->get_records($shortprefix.'_type_info_field');
        foreach ($records as $record) {

            // Process any custom fields - add them into the $record object.
            foreach ($customfieldrss as $customfieldrs) {
                // Manually get the next record from this recordset.
                $customfieldrecord = $customfieldrs->current();

                // It should have the same id as the main record.
                if ($customfieldrecord->id != $record->id) {
                    throw new exception('Hierarchy custom field record ids do not match');
                }

                // Merge the properties of the two objects.
                $record = (object) array_merge((array) $record, (array) $customfieldrecord);

                // Step to the next custom field record.
                $customfieldrs->next();
            }

            $row = array();
            // Don't display items indented by depth if it's a search.
            $showdepth = !$searchactive;

            $include_custom_fields = !$displaymode;
            $row[] = $hierarchy->display_hierarchy_item($record, $include_custom_fields,
                    $showdepth, $cfields, $types);
            if ($extrafields) {
                foreach ($extrafields as $extrafield) {
                    $row[] = $hierarchy->display_hierarchy_item_extrafield($record, $extrafield);
                }
            }
            if ($canupdateitems || $candeleteitems) {
                $row[] = $hierarchy->display_hierarchy_item_actions($record, $canupdateitems,
                        $candeleteitems, $canmoveitems, $params);
            }
            $table->add_data($row);
            ++$num_on_page;
        }

    }
}
$table->finish_html();

$records->close();
foreach ($customfieldrss as $customfieldrs) {
    $customfieldrs->close();
}

echo html_writer::start_tag('div', array('id' => 'id_exportoneframework'));
echo $OUTPUT->heading(get_string('exportframework', 'totara_hierarchy') . $OUTPUT->help_icon('exportframework', 'totara_hierarchy'), 3);
$hierarchy->export_select($baseurl);
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
