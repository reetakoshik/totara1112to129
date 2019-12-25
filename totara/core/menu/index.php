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
 * Totara navigation page.
 *
 * @package    totara_core
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */

use \totara_core\totara\menu\helper;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

// Actions to manage categories.
$moveup   = optional_param('moveup',   0, PARAM_INT);
$movedown = optional_param('movedown', 0, PARAM_INT);
$hideid   = optional_param('hideid',   0, PARAM_INT);
$showid   = optional_param('showid',   0, PARAM_INT);
$reset    = optional_param('reset',    0, PARAM_INT);
$confirm  = optional_param('confirm',  0, PARAM_BOOL);

admin_externalpage_setup('totaranavigation');
// Double check capability, the settings file is too far away.
require_capability('totara/core:editmainmenu', context_system::instance());

$url = new moodle_url('/totara/core/menu/index.php');
if ($movedown or $moveup) {
    require_sesskey();

    // The screen direction is reversed, up means lower sortorder.
    if ($movedown) {
        $up = true;
        $id = $movedown;
    } else {
        $up = false;
        $id = $moveup;
    }

    $returnurl = \totara_core\totara\menu\helper::get_admin_edit_return_url($id);

    ignore_user_abort(true);
    helper::change_sortorder($id, $up);
    redirect($returnurl, get_string('menuitem:movesuccess', 'totara_core'), 0, \core\output\notification::NOTIFY_SUCCESS);
}

if ($hideid or $showid) {
    require_sesskey();

    if ($hideid) {
        $visible = false;
        $id = $hideid;
    } else {
        $visible = true;
        $id = $showid;
    }

    $returnurl = \totara_core\totara\menu\helper::get_admin_edit_return_url($id);

    ignore_user_abort(true);
    helper::change_visibility($id, $visible);
    redirect($returnurl, get_string('menuitem:updatesuccess', 'totara_core'), 0, \core\output\notification::NOTIFY_SUCCESS);
}

if (!empty($reset)) {
    $currentdata = new \stdClass();
    $currentdata->reset = 1;
    $resetform = new \totara_core\form\menu\reset($currentdata);

    if ($resetform->is_cancelled()) {
        redirect($url);
    }
    if ($data = $resetform->get_data()) {
        ignore_user_abort(true);
        helper::reset_menu($data->backupcustom);
        redirect($url, get_string('menuitem:resettodefaultcomplete', 'totara_core'), 0, \core\output\notification::NOTIFY_SUCCESS);
    }

    $title = get_string('menuitem:resettodefault', 'totara_core');
    $PAGE->set_title($title);
    $PAGE->navbar->add($title);
    $PAGE->set_heading($title);

    echo $OUTPUT->header();
    echo $resetform->render();
    echo $OUTPUT->footer();
    die;
}

$event = \totara_core\event\menuadmin_viewed::create(array('context' => \context_system::instance()));
$event->trigger();

// Display page header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('menuitem:mainmenu', 'totara_core'));

$editurl = new moodle_url('/totara/core/menu/edit.php', array('id' => '0'));
echo $OUTPUT->single_button($editurl, get_string('menuitem:addnew', 'totara_core'), 'get');

// Print table header.
$table = new html_table;
$table->id = 'totaramenutable'; // Must not be same as the id of real totara menu!
$table->attributes['class'] = 'admintable generaltable editcourse';

$table->head = array(
                get_string('menuitem:title', 'totara_core'),
                get_string('menuitem:type', 'totara_core'),
                get_string('menuitem:url', 'totara_core'),
                get_string('menuitem:visibility', 'totara_core'),
                get_string('edit'),
);
$table->colclasses = array(
                'leftalign name',
                'centeralign count',
                'centeralign icons',
                'leftalign actions'
);
$table->data = array();

totara_menu_table_load($table);
echo html_writer::table($table);

$editurl = new moodle_url('/totara/core/menu/edit.php', array('id' => '0'));
echo $OUTPUT->single_button($editurl, get_string('menuitem:addnew', 'totara_core'), 'get');
$reseturl = new moodle_url('/totara/core/menu/index.php', array('reset' => 1));
echo $OUTPUT->single_button($reseturl, get_string('menuitem:resettodefault', 'totara_core'), 'get');

echo $OUTPUT->footer();
