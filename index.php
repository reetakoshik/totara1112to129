<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle frontpage.
 *
 * @package    core
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!file_exists('./config.php')) {
    header('Location: install.php');
    die;
}

require_once('config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

redirect_if_major_upgrade_required();

$urlparams = array();

$redirect = optional_param('redirect', null, PARAM_BOOL);
if ($redirect === null) {
    $redirect = 1;
} else {
    $urlparams['redirect'] = $redirect;
}
// If adding, editing, hiding, showing, moving or deleting a Block, we don't want to redirect.
$bui_addblock = optional_param('bui_addblock', '', PARAM_TEXT);
$bui_editid   = optional_param('bui_editid', '', PARAM_INT);
$bui_hideid   = optional_param('bui_hideid', '', PARAM_INT);
$bui_showid   = optional_param('bui_showid', '', PARAM_INT);
$bui_moveid   = optional_param('bui_moveid', '', PARAM_INT);
$bui_deleteid = optional_param('bui_deleteid', '', PARAM_INT);
if (!empty($bui_addblock) || !empty($bui_editid) || !empty($bui_hideid) || !empty($bui_showid) || !empty($bui_moveid) || !empty($bui_deleteid)) {
    $urlparams['redirect'] = 0;
    $redirect = 0;
}

$PAGE->set_url('/', $urlparams);
$PAGE->set_course($SITE);
$PAGE->set_pagelayout('frontpage');
$PAGE->set_other_editing_capability('moodle/course:update');
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$PAGE->set_other_editing_capability('moodle/course:activityvisibility');

// Prevent caching of this page to stop confusion when changing page after making AJAX changes.
$PAGE->set_cacheable(false);

if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}

$hasmaintenanceaccess = has_capability('moodle/site:maintenanceaccess', context_system::instance());

// If the site is currently under maintenance, then print a message.
if (!empty($CFG->maintenance_enabled) and !$hasmaintenanceaccess) {
    print_maintenance_message();
}

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());

if ($hassiteconfig && moodle_needs_upgrading()) {
    redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
}

// Totara: Ask for registration if necessary.
require_once("$CFG->dirroot/$CFG->admin/registerlib.php");
if (is_registration_required()) {
    redirect("$CFG->wwwroot/$CFG->admin/register.php?return=site");
}

if (get_home_page() == HOMEPAGE_TOTARA_DASHBOARD) {
    // Totara: the only other option is HOMEPAGE_SITE
    //         and only real logged in users may have dashboards.
    if (!empty($CFG->allowdefaultpageselection)) {
        if (optional_param('setdefaulthome', 0, PARAM_BOOL)) {
            require_sesskey();
            set_user_preference('user_home_page_preference', HOMEPAGE_SITE);
            $url = new moodle_url('/');
            totara_set_notification(get_string('userhomepagechanged', 'totara_dashboard'), $url, array('class' => 'notifysuccess'));
        }
        $newhomeurl = new moodle_url('/', array('setdefaulthome' => 1, 'sesskey' => sesskey()));
        $PAGE->settingsnav->add(get_string('makesitemyhomepage', 'totara_dashboard'), $newhomeurl, navigation_node::TYPE_SETTING);
    }
    // Redirect logged-in users to dashboard if required.
    if ($redirect === 1) {
        require_once($CFG->dirroot . '/totara/dashboard/lib.php');

        // Check for dashboard assignments.
        if (count(totara_dashboard::get_user_dashboards($USER->id))) {
            redirect(new moodle_url('/totara/dashboard/index.php'));
        }
    }
}
$PAGE->set_totara_menu_selected('\totara_core\totara\menu\home');

// Trigger event.
course_view(context_course::instance(SITEID));

// If the hub plugin is installed then we let it take over the homepage here.
if (file_exists($CFG->dirroot.'/local/hub/lib.php') and get_config('local_hub', 'hubenabled')) {
    require_once($CFG->dirroot.'/local/hub/lib.php');
    $hub = new local_hub();
    $continue = $hub->display_homepage();
    // Function display_homepage() returns true if the hub home page is not displayed
    // ...mostly when search form is not displayed for not logged users.
    if (empty($continue)) {
        exit;
    }
}

$PAGE->set_pagetype('site-index');
$PAGE->set_docs_path('');
$editing = $PAGE->user_is_editing();
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$courserenderer = $PAGE->get_renderer('core', 'course');
echo $OUTPUT->header();

// Print Section or custom info.
$siteformatoptions = course_get_format($SITE)->get_format_options();
$modinfo = get_fast_modinfo($SITE);
$modnames = get_module_types_names();
$modnamesplural = get_module_types_names(true);
$modnamesused = $modinfo->get_used_module_names();
$mods = $modinfo->get_cms();

if (!empty($CFG->customfrontpageinclude)) {
    include($CFG->customfrontpageinclude);

} else if ($siteformatoptions['numsections'] > 0) {
    if ($editing) {
        // Make sure section with number 1 exists.
        course_create_sections_if_missing($SITE, 1);
        // Re-request modinfo in case section was created.
        $modinfo = get_fast_modinfo($SITE);
    }
    $section = $modinfo->get_section_info(1);
    if (($section && (!empty($modinfo->sections[1]) or !empty($section->summary))) or $editing) {
        echo $OUTPUT->box_start('generalbox sitetopic');

        // If currently moving a file then show the current clipboard.
        if (ismoving($SITE->id)) {
            $stractivityclipboard = strip_tags(get_string('activityclipboard', '', $USER->activitycopyname));
            echo '<p><font size="2">';
            echo "$stractivityclipboard&nbsp;&nbsp;(<a href=\"course/mod.php?cancelcopy=true&amp;sesskey=".sesskey()."\">";
            echo get_string('cancel') . '</a>)';
            echo '</font></p>';
        }

        $context = context_course::instance(SITEID);

        // If the section name is set we show it.
        if (trim($section->name) !== '') {
            echo $OUTPUT->heading(
                format_string($section->name, true, array('context' => $context)),
                2,
                'sectionname'
            );
        }

        $summarytext = file_rewrite_pluginfile_urls($section->summary,
            'pluginfile.php',
            $context->id,
            'course',
            'section',
            $section->id);
        $summaryformatoptions = new stdClass();
        $summaryformatoptions->noclean = true;
        $summaryformatoptions->overflowdiv = true;

        echo format_text($summarytext, $section->summaryformat, $summaryformatoptions);

        if ($editing && has_capability('moodle/course:update', $context)) {
            $streditsummary = get_string('editsummary');
            echo "<a title=\"$streditsummary\" ".
                " href=\"course/editsection.php?id=$section->id\">" . $OUTPUT->flex_icon('settings', array('alt' => $streditsummary)) . "</a><br /><br />";
        }

        $courserenderer = $PAGE->get_renderer('core', 'course');
        echo $courserenderer->course_section_cm_list($SITE, $section);

        echo $courserenderer->course_section_add_cm_control($SITE, $section->section);
        echo $OUTPUT->box_end();
    }
}
// Include course AJAX.
include_course_ajax($SITE, $modnamesused);

if ($editing && get_config('core', 'frontpageaddcoursebutton') && has_capability('moodle/course:create', context_system::instance())) {
    echo $courserenderer->add_new_course_button();
}

echo $OUTPUT->footer();
