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
 * @package totara
 * @subpackage totara_hierarchy
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_hierarchy.class.php');

require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/related/lib.php');


///
/// Setup / loading data
///

// Competency id
$compid = required_param('id', PARAM_INT);

// Parent id
$parentid = optional_param('parentid', 0, PARAM_INT);

// Framework id
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

// should we show hidden frameworks?
$showhidden = optional_param('showhidden', false, PARAM_BOOL);

// Check if Competencies are enabled.
if (totara_feature_disabled('competencies')) {
    echo html_writer::tag('div', get_string('competenciesdisabled', 'totara_hierarchy'), array('class' => 'notifyproblem'));
    die();
}

// check they have permissions on hidden frameworks in case parameter is changed manually
$context = context_system::instance();
if ($showhidden && !has_capability('totara/hierarchy:updatecompetencyframeworks', $context)) {
    print_error('nopermviewhiddenframeworks', 'totara_hierarchy');
}

// show search tab instead of browse
$search = optional_param('search', false, PARAM_BOOL);

// Setup page
admin_externalpage_setup('competencymanage', '', array(), '/totara/hierarchy/prefix/competency/related/add.php');

$alreadyselected = array();
if ($alreadyrelated = comp_relation_get_relations($compid)) {
    list($alreadyrelated_sql, $alreadyrelated_params) = $DB->get_in_or_equal($alreadyrelated);
    $alreadyselected = $DB->get_records_select('comp', 'id ' . $alreadyrelated_sql, $alreadyrelated_params, '', 'id, fullname');
}
$alreadyrelated[$compid] = $compid; // competency can't be related to itself

///
/// Display page
///

// Load dialog content generator
$dialog = new totara_dialog_content_hierarchy_multi('competency', $frameworkid, $showhidden);

// Toggle treeview only display
$dialog->show_treeview_only = $treeonly;

// Load items to display
$dialog->load_items($parentid);

// Make a note of the current item (competency) id
$dialog->customdata['current_item_id'] = $compid;

// Set disabled items
$dialog->disabled_items = $alreadyrelated;

// Set title
$dialog->selected_title = 'itemstoadd';
$dialog->selected_items = $alreadyselected;
// Addition url parameters
$dialog->urlparams = array('id' => $compid);
// Display
echo $dialog->generate_markup();
