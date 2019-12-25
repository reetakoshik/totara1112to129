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
 * @subpackage hierarchy
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/type/change_form.php');
require_once($CFG->dirroot.'/totara/hierarchy/type/changelib.php');


// Setup / loading data.

// Get params.
$prefix        = required_param('prefix', PARAM_ALPHA);
$typeid     = required_param('typeid', PARAM_INT);
$newtypeid  = required_param('newtypeid', PARAM_INT);
$itemid      = optional_param('itemid', 0, PARAM_INT);
$page        = optional_param('page', 0, PARAM_INT);
$class      = optional_param('class', '', PARAM_ALPHA);
$shortprefix = hierarchy::get_short_prefix($prefix);

hierarchy::check_enable_hierarchy($prefix);

$hierarchy = hierarchy::load_hierarchy($prefix);

/* The form can be used to modify individual items and all items in a type
 * set some variables to manage the differences in behaviours between the two cases.
 */
if ($itemid) {
    $item = $DB->get_record($shortprefix, array('id' => $itemid));
    $affected_item_sql = "AND d.{$prefix}id = ?";
    $affected_item_param = array($itemid);
    $cf_data_sql = " AND {$prefix}id = ?";
    $cf_data_params = array($itemid);
    $item_sql = " AND id = ?";
    $item_param = array($itemid);
    $returnurl = new moodle_url("/totara/hierarchy/item/edit.php", array('prefix' => $prefix, 'id' => $itemid, 'page' => $page));
    $optype = 'item'; // used for switching lang strings
    $adminpage = $class . $prefix . 'manage';
} else {
    $affected_item_sql = $cf_data_sql = $item_sql = '';
    $affected_item_param = $cf_data_param = $item_param = array();
    $returnurl = new moodle_url("/totara/hierarchy/type/index.php", array('prefix' => $prefix, 'page' => $page, 'class' => $class));
    $optype = 'bulk';
    $adminpage = $class . $prefix . 'typemanage';
    $cf_data_params = array();
}

// Setup page and check permissions.
admin_externalpage_setup($adminpage, null, array('prefix' => $prefix));
$sitecontext = context_system::instance();
require_capability('totara/hierarchy:update' . $prefix, $sitecontext);

$typename = hierarchy_get_type_name($typeid, $shortprefix);
$newtypename = hierarchy_get_type_name($newtypeid, $shortprefix);

// Make sure the itemid is valid if provided.
if ($itemid && !$item) {
    print_error('error:invaliditemid', 'totara_hierarchy');
}

// Count How many items in the being changed.
$select = "typeid = ? {$item_sql}";
$affected_item_count = $DB->count_records_select($shortprefix, $select, array_merge(array($typeid), $item_param));

// Redirect with a message if there are no items of the type to be changed.
if ($affected_item_count == 0) {
    totara_set_notification(get_string('error:nonefound'. $optype, 'totara_hierarchy'), $returnurl);
}

// Lists the number of items with one or more custom field data record belonging to each type.
$sql = "SELECT d.fieldid, COUNT(DISTINCT d.{$prefix}id)
    FROM {{$shortprefix}_type_info_data} d
    LEFT JOIN {{$shortprefix}_type_info_field} f
    ON f.id = d.fieldid
    WHERE f.typeid = ?
    {$affected_item_sql}
    GROUP BY d.fieldid";
$affected_data_count = $DB->get_records_sql_menu($sql, array_merge(array($typeid), $affected_item_param));

// Load data for type details.

$current_type_cfs = $DB->get_records($shortprefix . '_type_info_field', array('typeid' => $typeid), 'typeid');
$new_type_cfs = $DB->get_records($shortprefix . '_type_info_field', array('typeid' => $newtypeid), 'typeid');

// Moodle form.
$changeform = new type_change_form(null, compact('prefix', 'typeid', 'newtypeid', 'itemid', 'current_type_cfs', 'new_type_cfs', 'affected_data_count', 'page', 'class'), 'post', '', array('class' => 'hierarchy-bulk-type-form'));

// Process the form submission/cancellation.
if ($changeform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $changeform->get_data()) {
    $status = true;
    $transaction = $DB->start_delegated_transaction();
    // Reassign data from old type if possible.
    if (isset($data->field)) {
        $errors = array();
        foreach ($data->field as $oldfieldid => $newfieldid) {
            if ($newfieldid == 0) {
                // Delete the data from all items, or just itemid if specified.
                $sql = "DELETE FROM {{$shortprefix}_type_info_data}
                    WHERE fieldid = ? {$cf_data_sql}";
                $status = $status && $DB->execute($sql, array_merge(array($oldfieldid), $cf_data_params));
                continue;
            }
            // Modify the fields of all the item's data, or just itemid if specified.
            $sql = "UPDATE {{$shortprefix}_type_info_data}
                SET fieldid = {$newfieldid}
                WHERE fieldid = ? {$cf_data_sql}";
            $status = $status && $DB->execute($sql, array_merge(array($oldfieldid), $cf_data_params));
            // Now get and validate all the new data - in a transaction so all will be rolled back if we hit a validation error.
            $field = $DB->get_record($shortprefix.'_type_info_field', array('id' => $newfieldid));
            $selectsql = "SELECT * FROM {{$shortprefix}_type_info_data} WHERE fieldid= ? {$cf_data_sql}";
            $fieldrecords = $DB->get_records_sql($selectsql, array_merge(array($newfieldid), $cf_data_params));
            foreach ($fieldrecords as $record) {
                if (isset($field->forceunique) && $field->forceunique == 1) {
                    $sql = "fieldid = ? AND " . $DB->sql_compare_text('data', 1024) . ' = ?';
                    if ($itemid) {
                        $sql .= "AND " . $prefix . "id != ?";
                    }
                    if ($DB->record_exists_select($shortprefix.'_type_info_data',
                                            $sql,
                                            array_merge(array($field->id, $record->data), $cf_data_params))) {
                        $errors["{$field->fullname}"] = get_string('valuealreadyused');
                        break;
                    }
                }
            }

        }
        if (!empty($errors)) {
            foreach ($errors as $field => $val) {
                totara_set_notification("$field : " . $val);
            }
            $a = new stdClass();
            $a->from = $typename;
            $a->to = $newtypename;
            $DB->force_transaction_rollback();
            totara_set_notification(get_string('error:couldnotreclassify' . $optype, 'totara_hierarchy', $a), "$CFG->wwwroot/totara/hierarchy/index.php?prefix=$prefix");
        }
    }

    if (!empty($itemid)) {
        $hierarchy->move_type($itemid, $typeid, $newtypeid);
    } else {
        $hierarchy->bulk_move_type($typeid, $newtypeid);
    }

    if (!$status) {
        $a = new stdClass();
        $a->from = $typename;
        $a->to = $newtypename;
        totara_set_notification(get_string('error:couldnotreclassify' . $optype,
            'totara_hierarchy', $a),
            "$CFG->wwwroot/totara/hierarchy/type/index.php?prefix=$prefix");
    }
    $transaction->allow_commit();

    $a = new stdClass();
    if ($itemid) {
        $a->name = format_string($item->fullname);
    } else {
        $a->num = $affected_item_count;
        $a->items = ($affected_item_count == 1) ?
            strtolower(get_string($prefix, 'totara_hierarchy')) :
            strtolower(get_string($prefix . 'plural', 'totara_hierarchy'));
    }
    $a->from = $typename;
    $a->to = $newtypename;

    totara_set_notification(get_string('reclassifysuccess' . $optype, 'totara_hierarchy', $a),
        $returnurl, array('class' => 'notifysuccess'));

}

// Generate / display page.

// Breadcrumbs (different if changing a single item vs. all items in a type).
if ($itemid) {
    $framework = $DB->get_record($shortprefix.'_framework', array('id' => $item->frameworkid));
    $PAGE->navbar->add(get_string("{$prefix}frameworks", 'totara_hierarchy'), new moodle_url("/totara/hierarchy/framework/index.php", array('prefix' => $prefix)));
    $PAGE->navbar->add(format_string($framework->fullname), new moodle_url("/totara/hierarchy/index.php", array('prefix' => $prefix, 'frameworkid' => $framework->id)));
    $PAGE->navbar->add(format_string($item->fullname), new moodle_url("/totara/hierarchy/item/view.php", array('prefix' => $prefix, 'id' => $item->id)));
    $PAGE->navbar->add(get_string('edit'.$prefix, 'totara_hierarchy'), new moodle_url("/totara/hierarchy/item/edit.php", array('prefix' => $prefix, 'id' => $itemid)));
    $PAGE->navbar->add(get_string('changetype', 'totara_hierarchy'));
} else {
    $PAGE->navbar->add(get_string('types', 'totara_hierarchy'), new moodle_url("/totara/hierarchy/type/index.php", array('prefix' => $prefix)));
    $PAGE->navbar->add(get_string('bulktypechanges', 'totara_hierarchy'));
}

echo $OUTPUT->header();

// Step 2 of 2 - confirm how to handle custom field data.

$a = new stdClass();
$a->from = $typename;
$a->to = $newtypename;
if ($itemid) {
    $a->name = format_string($item->fullname);
} else {
    $itemstr = ($affected_item_count == 1) ?
        strtolower(get_string($prefix, 'totara_hierarchy')) :
        strtolower(get_string($prefix.'plural', 'totara_hierarchy'));
    $a->num = $affected_item_count;
    $a->items = $itemstr;
}

echo $OUTPUT->heading(get_string('reclassifyingfromxtoy' . $optype, 'totara_hierarchy', $a), 1);

/* How we proceed depends on which types have custom fields:
 *
 * If the old type doesn't have any custom fields, there's nothing to do,
 * just confirm the change
 *
 * If the new type doesn't have any custom fields, there's nowhere for the data
 * to go, just warn that it will be deleted
 *
 * If both types have custom fields, display the form for transferring the data
 *
 * All of these possibilities are handled inside the form
 */

$changeform->display();

echo $OUTPUT->footer();
