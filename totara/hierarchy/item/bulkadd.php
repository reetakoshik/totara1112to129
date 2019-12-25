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

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/item/bulkadd_form.php');
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');

///
/// Setup / loading data
///

$prefix = required_param('prefix', PARAM_ALPHA);
$shortprefix = hierarchy::get_short_prefix($prefix);

$frameworkid = required_param('frameworkid', PARAM_INT);
$page       = optional_param('page', 0, PARAM_INT);

// Check hierarchy item is enabled.
hierarchy::check_enable_hierarchy($prefix);

$hierarchy = hierarchy::load_hierarchy($prefix);

// Make this page appear under the manage competencies admin item
admin_externalpage_setup($prefix.'manage', '', array('prefix' => $prefix));

$context = context_system::instance();

require_capability('totara/hierarchy:create'.$prefix, $context);

// Load framework
if (!$framework = $DB->get_record($shortprefix.'_framework', array('id' => $frameworkid))) {
    print_error('invalidframeworkid', 'totara_hierarchy', $prefix);
}


///
/// Display page
///

// create form
$mform = new item_bulkadd_form(null, compact('prefix', 'frameworkid', 'page'));

// cancelled
if ($mform->is_cancelled()) {

    redirect("{$CFG->wwwroot}/totara/hierarchy/index.php?prefix=$prefix&amp;frameworkid={$frameworkid}&amp;page={$page}");

// Update data
} else if ($formdata = $mform->get_data()) {

    $error = '';
    $items_to_add = hierarchy::construct_items_to_add($formdata, $error);
    if (!$items_to_add) {
        totara_set_notification(get_string('bulkaddfailed', 'totara_hierarchy', $error), "{$CFG->wwwroot}/totara/hierarchy/index.php?prefix=$prefix&amp;frameworkid={$frameworkid}&amp;page={$page}");
    }

    if ($new_ids = $hierarchy->add_multiple_hierarchy_items($formdata->parentid, $items_to_add, $frameworkid)) {
        totara_set_notification(get_string('bulkaddsuccess', 'totara_hierarchy', count($new_ids)), "{$CFG->wwwroot}/totara/hierarchy/index.php?prefix=$prefix&amp;frameworkid={$frameworkid}&amp;page={$page}", array('class' => 'notifysuccess'));
    } else {
        totara_set_notification(get_string('bulkaddfailed', 'totara_hierarchy'), "{$CFG->wwwroot}/totara/hierarchy/index.php?prefix=$prefix&amp;frameworkid={$frameworkid}&amp;page={$page}");
    }
}

$PAGE->navbar->add(get_string("{$prefix}frameworks", 'totara_hierarchy'), new moodle_url('/totara/hierarchy/framework/index.php', array('prefix' => $prefix)));
$PAGE->navbar->add(format_string($framework->fullname), new moodle_url('/totara/hierarchy/index.php', array('prefix' => $prefix, 'frameworkid' => $framework->id)));
$PAGE->navbar->add(get_string('addmultiplenew'.$prefix, 'totara_hierarchy'));

/// Display page header
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('addmultiplenew'.$prefix, 'totara_hierarchy'));

/// Finally display the form
$mform->display();

echo $OUTPUT->footer();

