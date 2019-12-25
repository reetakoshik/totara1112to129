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
require_once("{$CFG->libdir}/adminlib.php");
require_once("{$CFG->dirroot}/totara/hierarchy/lib.php");


// Setup / loading data.

$sitecontext = context_system::instance();

// Get params.
$prefix        = required_param('prefix', PARAM_ALPHA);
$class        = optional_param('class', '', PARAM_ALPHA);
$edit        = optional_param('edit', -1, PARAM_BOOL);
$shortprefix = hierarchy::get_short_prefix($prefix);

hierarchy::check_enable_hierarchy($prefix);

$hierarchy = hierarchy::load_hierarchy($prefix);

// @todo add capabilities.
// Cache user capabilities.
$can_add = has_capability('totara/hierarchy:create'.$prefix.'type', $sitecontext);
$can_edit = has_capability('totara/hierarchy:update'.$prefix.'type', $sitecontext);
$can_delete = has_capability('totara/hierarchy:delete'.$prefix.'type', $sitecontext);
$can_edit_custom_fields = has_capability('totara/hierarchy:'.$prefix.'managecustomfield', $sitecontext);
$can_change_type = has_capability('totara/hierarchy:update'.$prefix, $sitecontext);

// Setup page and check permissions.
if ($prefix == 'goal' && $class) {
    admin_externalpage_setup($class . $prefix . 'typemanage', null, array('prefix' => $prefix));
} else {
    admin_externalpage_setup($prefix.'typemanage', null, array('prefix' => $prefix));
}

// Load data for type details.

// Get types for this page.
if ($class == 'personal') {
    $types = $hierarchy->get_types(array('custom_field_count' => 1, 'item_count' => 1, 'personal' => 1));
} else {
    $types = $hierarchy->get_types(array('custom_field_count' => 1, 'item_count' => 1));
}


// Get count of unclassified items.
$unclassified = $hierarchy->get_unclassified_items(true);

// Generate / display page.
$str_edit     = get_string('edit');
$str_delete   = get_string('delete');

if ($types) {
    // Create display table.
    $table = new html_table();

    // Setup column headers.
    $table->head = array(get_string('name', 'totara_hierarchy'),
        get_string($prefix.'typeidnumber', 'totara_hierarchy'),
        get_string($prefix . 'plural', 'totara_hierarchy'),
        get_string("customfields", 'totara_hierarchy'));
    $table->align = array('left', 'left', 'center', 'center');

    // Add edit column.
    if ($can_edit || $can_delete) {
        $table->head[] = get_string('actions');
        $table->align[] = 'left';
    }

    // Add type rows to table.
    foreach ($types as $type) {
        $row = array();

        $cssclass = '';

        if ($can_edit_custom_fields) {
            $row[] = $OUTPUT->action_link(new moodle_url('/totara/customfield/index.php',
                array('prefix' => $prefix, 'typeid' => $type->id, 'class' => $class)), format_string($type->fullname));
        } else {
            $row[] = format_string($type->fullname);
        }
        $row[] = format_string($type->idnumber);
        $row[] = $type->item_count;
        $row[] = $type->custom_field_count;

        // Add edit link.
        $buttons = array();
        if ($can_edit) {
            $buttons[] = $OUTPUT->action_icon(
                new moodle_url('/totara/hierarchy/type/edit.php', array('prefix' => $prefix, 'id' => $type->id, 'class' => $class)),
                new pix_icon('t/edit', $str_edit, null, array('class' => 'iconsmall')),
                null,
                array('title' => $str_edit)
            );
        }
        if ($can_delete) {
            $buttons[] = $OUTPUT->action_icon(
                new moodle_url('/totara/hierarchy/type/delete.php', array('prefix' => $prefix,
                    'id' => $type->id, 'class' => $class)),
                    new pix_icon('t/delete', $str_delete, null),
                    null,
                    array('title' => $str_delete
                )
            );
        }
        if ($buttons) {
            $row[] = implode($buttons, '');
        }

        $table->data[] = $row;
    }

    // Add a row for unclassified items.
    if ($unclassified) {
        $row = array();
        $row[] = get_string('unclassified', 'totara_hierarchy');
        $row[] = '&nbsp;';
        $row[] = $unclassified;
        $row[] = '&nbsp;';
        if ($buttons) {
            $row[] = '&nbsp;';
        }
        $table->data[] = $row;
    }

}

echo $OUTPUT->header();

$templatedata = new stdClass();

$heading = '';
if ($prefix == 'goal') {
    $templatedata->heading = $OUTPUT->heading(get_string('manage' . $class . 'goaltypes', 'totara_hierarchy') . ' ' .
        $OUTPUT->help_icon($prefix.'type', 'totara_hierarchy', false));
} else {
    $templatedata->heading = $OUTPUT->heading(get_string('types', 'totara_hierarchy') . ' ' .
        $OUTPUT->help_icon($prefix.'type', 'totara_hierarchy', false));
}

// Add type button.
if ($can_add) {
    // Print button for creating new type.
    $templatedata->can_add = $hierarchy->display_add_type_button(0, $class);
}

$options = array();

if ($types) {
    $templatedata->types = $table->export_for_template($OUTPUT);

    foreach ($types as $type) {
        // Only let user select type that contain items.
        if ($type->item_count > 0) {
            $options[$type->id] = format_string($type->fullname);
        }
    }
} else {
    $templatedata->types = false;
    $templatedata->notypes = get_string($prefix.'notypes', 'totara_hierarchy');
}

// Only show bulk re-classify form if there is at least one type.
$showbulkform = (count($types) > 0);

// Add an option to change all unclassified items to a new type (if there are any).
if ($unclassified) {
    $options[0] = get_string('unclassified', 'totara_hierarchy');
}

if ($showbulkform && $can_change_type && $class != 'personal') {
    $templatedata->bulkchange = $OUTPUT->single_select(new moodle_url("change.php", array('prefix' => $prefix, 'class' => $class)),
        'typeid', $options, 'changetype', array('' => 'choosedots'), null,
        array('label' => get_string('bulktypechangesdesc', 'totara_hierarchy')));
} else {
    $templatedata->bulkchange = false;
}

echo $OUTPUT->render_from_template('totara_hierarchy/admin_types', $templatedata);

\totara_hierarchy\event\type_viewed::create_from_prefix($prefix)->trigger();

echo $OUTPUT->footer();
