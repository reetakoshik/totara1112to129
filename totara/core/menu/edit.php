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
 * Totara navigation edit page.
 *
 * @package    totara_core
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */

use \totara_core\totara\menu\item;
use \totara_core\totara\menu\helper;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

// Item id.
$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('totaranavigation', '', null, new moodle_url('/totara/core/menu/edit.php', array('id' => $id)));
// Double check capability, the settings file is too far away.
require_capability('totara/core:editmainmenu', context_system::instance());

$returnurl = \totara_core\totara\menu\helper::get_admin_edit_return_url($id);

if ($id) {
    $record = $DB->get_record('totara_navigation', array('id' => $id));
    if (!$record) {
        // Most likely result of concurrent editing, just go back.
        redirect($returnurl, get_string('error:findingmenuitem', 'totara_core'), 0, \core\output\notification::NOTIFY_ERROR);
    }
    $node = item::create_instance($record);
    if (!$node) {
        // This should not happen.
        throw new coding_exception('Error instantiating menu item class');
    }

    $parentidoptions = helper::create_parentid_form_options(0);
    if (!isset($parentidoptions[$record->parentid])) {
        $record->parentid = helper::get_unused_container_id();
    }

    if ($record->custom) {
        if (get_class($node) === 'totara_core\totara\menu\container') {
            $form = new \totara_core\form\menu\update_custom_container($record, array('item' => $node, 'parentidoptions' => $parentidoptions));
            $record->classname = '\totara_core\totara\menu\container';
        } else {
            $form = new \totara_core\form\menu\update_custom_item($record, array('item' => $node, 'parentidoptions' => $parentidoptions));
            $record->classname = '\totara_core\totara\menu\item';
        }

    } else {
        if (!$record->customtitle and trim($record->title) === '') {
            // Prefill current value for customisation.
            $record->title = $node->get_title();
        }
        $form = new \totara_core\form\menu\update_default($record, array('item' => $node, 'parentidoptions' => $parentidoptions));
    }

} else {
    $record = new stdClass();
    $record->id = 0;
    $record->parentid = 0;
    $record->visibility = item::VISIBILITY_SHOW;
    $record->type = 'item';

    // NOTE: if we knew we are adding folder only it would be -2
    $parentidoptions = helper::create_parentid_form_options(0, item::MAX_DEPTH - 1);

    $form = new \totara_core\form\menu\add_custom($record, array('parentidoptions' => $parentidoptions));
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    ignore_user_abort(true);
    if (!$data->id) {
        // Must be a new custom item or container.
        unset($data->id);
        $record = helper::add_custom_menu_item($data);

        if ($record->visibility == item::VISIBILITY_CUSTOM) {
            $returnurl = new moodle_url('/totara/core/menu/rules.php', array('id' => $record->id));
        } else {
            $returnurl = \totara_core\totara\menu\helper::get_admin_edit_return_url($record->id);
        }

        redirect($returnurl, get_string('menuitem:updatesuccess', 'totara_core'), 0, \core\output\notification::NOTIFY_SUCCESS);

    } else {
        $oldrecord = $record;
        $data->classname = $oldrecord->classname;
        $data->custom = $oldrecord->custom;
        $record = helper::update_menu_item($data);

        if ($oldrecord->visibility != item::VISIBILITY_CUSTOM && $record->visibility == item::VISIBILITY_CUSTOM) {
            $returnurl = new moodle_url('/totara/core/menu/rules.php', array('id' => $record->id));
        }

        redirect($returnurl, get_string('menuitem:updatesuccess', 'totara_core'), 0, \core\output\notification::NOTIFY_SUCCESS);
    }
}

if ($id) {
    if ($node) {
        $title = get_string('menuitem:editingx', 'totara_core', $node->get_title());
    } else {
        $title = $record->classname;
    }
} else {
    $title = get_string('menuitem:addnew', 'totara_core');
}
$PAGE->set_title($title);
$PAGE->navbar->add($title);
$PAGE->set_heading($title);

/** @var totara_core_renderer|core_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_core');

// Display page header.
echo $renderer->header();
echo $renderer->heading($title);

// Set up tabs for access controls and detail editing.
// Don't show them when creating a new item.
if ($record->id) {
    echo $renderer->totara_menu_tabs('edit', $record);
}

echo $form->render();
echo $renderer->footer();
