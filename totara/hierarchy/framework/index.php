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

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");
require_once("{$CFG->dirroot}/totara/hierarchy/lib.php");


///
/// Setup / loading data
///

$sitecontext = context_system::instance();

// Get params.
$prefix      = required_param('prefix', PARAM_ALPHA);
$edit        = optional_param('edit', -1, PARAM_BOOL);
$hide        = optional_param('hide', 0, PARAM_INT);
$show        = optional_param('show', 0, PARAM_INT);
$moveup      = optional_param('moveup', 0, PARAM_INT);
$movedown    = optional_param('movedown', 0, PARAM_INT);
$format      = optional_param('format', '', PARAM_TEXT);

hierarchy::check_enable_hierarchy($prefix);

$hierarchy = hierarchy::load_hierarchy($prefix);

// Cache user capabilities.
extract($hierarchy->get_permissions());

if (!($canviewframeworks || $canviewscales)) {
    print_error('accessdenied', 'admin');
}

$url_params = array('prefix' => $prefix);
$baseurl = new moodle_url('/totara/hierarchy/framework/index.php', $url_params);

if ($canmanage) {
    // Setup page as admin and check permissions.
    admin_externalpage_setup($prefix.'manage', '', array('prefix' => $prefix));
} else {
    // Non admin page set up.
    $detailsstr = get_string($prefix . 'details', 'totara_hierarchy');
    $PAGE->set_url($baseurl);
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title($detailsstr);
}

///
/// Process any actions
///

if ($format != '') {
    \totara_hierarchy\event\frameworks_all_exported::create_from_instance($prefix)->trigger();
    $hierarchy->export_data($format, true);
    die;
}

if ($canupdateframeworks) {
    // Hide or show a framework.
    if ($hide or $show or $moveup or $movedown) {
        require_capability('totara/hierarchy:update'.$prefix.'frameworks', $sitecontext);
        // Hide an item.
        if ($hide) {
            $hierarchy->hide_framework($hide);
        } elseif ($show) {
            $hierarchy->show_framework($show);
        } elseif ($moveup) {
            $hierarchy->move_framework($moveup, true);
        } elseif ($movedown) {
            $hierarchy->move_framework($movedown, false);
        }
    }

} // End of editing stuff.

///
/// Load hierarchy frameworks after any changes
///

// Get frameworks for this page.
$frameworks = $hierarchy->get_frameworks(array('item_count' => 1), true);

///
/// Generate / display page
///
$str_edit     = get_string('edit');
$str_delete   = get_string('delete');
$str_moveup   = get_string('moveup');
$str_movedown = get_string('movedown');
$str_hide     = get_string('hide');
$str_show     = get_string('show');

if ($frameworks) {

    // Create display table.
    $table = new html_table();
    $table->attributes['class'] = 'generaltable fullwidth edit'.$prefix;

    // Setup column headers.
    $headers = array();
    $headers[] = get_string('name', 'totara_hierarchy');
    if (!empty($CFG->showhierarchyshortnames)) {
        $headers[] = get_string('shortnameframework', 'totara_hierarchy');
    }
    $headers[] = get_string($prefix.'plural', 'totara_hierarchy');

    // Add edit column.
    if ($canupdateframeworks || $candeleteframeworks) {
        $headers[] = get_string('actions');
    }
    $table->head = $headers;

    // Add rows to table.
    $rowcount = 1;
    foreach ($frameworks as $framework) {
        $row = array();

        $cssclass = !$framework->visible ? 'dimmed' : '';

        $link_params = array('prefix' => $prefix, 'frameworkid' => $framework->id);
        $link_url = new moodle_url('/totara/hierarchy/index.php', $link_params);
        if ($canviewframeworks) {
            $row[] = $OUTPUT->action_link($link_url, format_string($framework->fullname), null, array('class' => $cssclass));
        } else {
            $row[] = format_string($framework->fullname);
        }
        if (!empty($CFG->showhierarchyshortnames)) {
            $row[] = format_string($framework->shortname);
        }
        $row[] = html_writer::tag('span', $framework->item_count, array('class' => $cssclass));

        // Add edit link.
        $buttons = array();
        if ($canupdateframeworks) {
            $buttons[] = $OUTPUT->action_icon(new moodle_url('edit.php', array('prefix' => $prefix, 'id' => $framework->id)),
                    new pix_icon('t/edit', $str_edit), null, array('title' => $str_edit));
            if ($framework->visible) {
                $buttons[] = $OUTPUT->action_icon(new moodle_url('index.php', array('prefix' => $prefix, 'hide' => $framework->id)),
                        new pix_icon('t/hide', $str_hide), null, array('title' => $str_hide));
            } else {
                $buttons[] = $OUTPUT->action_icon(new moodle_url('index.php', array('prefix' => $prefix, 'show' => $framework->id)),
                        new pix_icon('t/show', $str_show), null, array('title' => $str_show));
            }
        }
        if ($candeleteframeworks) {
            $buttons[] = $OUTPUT->action_icon(new moodle_url('delete.php', array('prefix' => $prefix, 'id' => $framework->id)),
                    new pix_icon('t/delete', $str_delete), null, array('title' => $str_delete));
        }
        if ($canupdateframeworks) {
            if ($rowcount != 1) {
                $buttons[] = $OUTPUT->action_icon(new moodle_url('index.php', array('prefix' => $prefix, 'moveup' => $framework->id)),
                        new pix_icon('t/up', $str_moveup), null, array('title' => $str_moveup));
            } else {
                $buttons[] = $OUTPUT->spacer(array('height' => 11, 'width' => 11));
            }
            if ($rowcount != count($frameworks)) {
                $buttons[] = $OUTPUT->action_icon(new moodle_url('index.php', array('prefix' => $prefix, 'movedown' => $framework->id)),
                        new pix_icon('t/down', $str_movedown), null, array('title' => $str_movedown));
            } else {
                $buttons[] = $OUTPUT->spacer(array('height' => 11, 'width' => 11));
            }
            $rowcount++;
        }

        if ($buttons) {
            $row[] = implode($buttons, '');
        }

        $table->data[] = $row;
    }
}

// Display page.

$PAGE->navbar->add(get_string("{$prefix}frameworks", 'totara_hierarchy'));

echo $OUTPUT->header();
$templatedata = new stdClass();
$templatedata->heading = get_string($prefix.'frameworks', 'totara_hierarchy') . ' '
    . $OUTPUT->help_icon($prefix.'frameworks', 'totara_hierarchy', false);

// Editing buttons.
if ($cancreateframeworks) {
    // Print button for creating new framework.
    $templatedata->createframeworkbutton = $OUTPUT->single_button(new moodle_url('edit.php', array('prefix' => $prefix)),
                get_string($prefix.'addnewframework', 'totara_hierarchy'), 'get');
}

if ($canviewframeworks) {
    if ($frameworks) {
        $templatedata->frameworks = $table->export_for_template($OUTPUT);
    } else {
        $templatedata->noframeworkmessage = get_string($prefix.'noframeworks', 'totara_hierarchy');
    }
}

$templatedata->exportbuttons = $hierarchy->export_frameworks_select_for_template($baseurl, !$frameworks);

echo $OUTPUT->render_from_template('totara_hierarchy/admin_frameworks', $templatedata);

// Display scales.
if ($hierarchyhasscales) {
    include($CFG->dirroot.'/totara/hierarchy/prefix/'.$prefix.'/scale/lib.php');
    $scales = $hierarchy->get_scales();
    call_user_func("{$prefix}_scale_display_table", $scales);
}

\totara_hierarchy\event\framework_viewed::create_from_prefix($prefix)->trigger();
echo $OUTPUT->footer();
