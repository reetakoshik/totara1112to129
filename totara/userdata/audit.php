<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

use \totara_userdata\local\count;
use \totara_userdata\local\util;
use \totara_userdata\userdata\item;
use \totara_userdata\userdata\target_user;

define('NO_OUTPUT_BUFFERING', true);

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);
require_login();

$returnurl = new moodle_url('/totara/userdata/user_info.php', array('id' => $id));

if (!data_submitted()) {
    // This page is expensive to generate, make sure nobody bookmarks this page
    // or comes here accidentally, this is not a CSRF protection!
    redirect($returnurl);
}

$user = $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);
$syscontext = context_system::instance();
$context = context_user::instance($user->id, IGNORE_MISSING);
if (!$context) {
    $context = $syscontext;
}

require_capability('totara/userdata:viewinfo', $context);

$targetuser = new target_user($user);
$currentuser = ($user->id == $USER->id);

$PAGE->set_context($context);
$PAGE->set_url('/totara/userdata/user_info.php', array('id' => $id)); // This URL is different intentionally!
$PAGE->set_pagelayout('admin');

if ($user->deleted) {
    admin_externalpage_setup('userdatadeletedusers');
} else {
    $PAGE->set_title(get_string('audit', 'totara_userdata'));
    $PAGE->set_heading(fullname($user));

    if (!$currentuser) {
        $PAGE->navigation->extend_for_user($user);
        $PAGE->navbar->add(get_string('userinfo', 'totara_userdata'), $returnurl);
        $PAGE->navbar->add(get_string('audit', 'totara_userdata'));
    } else {
        // We are looking at our own profile.
        $myprofilenode = $PAGE->settingsnav->find('myprofile', null);
        $userinfo = $myprofilenode->add(get_string('userinfo', 'totara_userdata'), $returnurl);
        $auditnode = $userinfo->add(get_string('audit', 'totara_userdata'));
        $auditnode->make_active();}
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('audit', 'totara_userdata'));

/** @var \totara_userdata_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_userdata');
echo $renderer->user_id_card($user, true);

$stats = array(
    'itemscount' => 0,
    'successcount' => 0,
    'nonemptycount' => 0,
    'errorcount' => 0,
    'totalcount' => 0,
);

$resultstauses = \totara_userdata\userdata\manager::get_results();

$prevcomponent = null;

$groupeditems = count::get_countable_items_grouped_list();
$grouplabels = \totara_userdata\local\util::get_sorted_grouplabels(array_keys($groupeditems));
foreach ($grouplabels as $maincomponent => $grouplabel) {
    $classes = $groupeditems[$maincomponent];
    foreach ($classes as $class) {
        /** @var item $class this is not an instance, but it helps with autocomplete */

        if (!$class::is_compatible_context_level($syscontext->contextlevel)) {
            // Item not compatible with this level.
            continue;
        }

        $stats['itemscount']++;

        if ($prevcomponent !== $maincomponent) {
            if ($prevcomponent !== null) {
                echo '</dl>';
            }
            $prevcomponent = $maincomponent;
            echo $OUTPUT->heading($grouplabel, 3);
            echo '<dl class="dl-horizontal">';
        }

        echo '<dt>' . $class::get_fullname() . '</dt>';

        echo '<dd>';
        try {
            $result = $class::execute_count($targetuser, $syscontext);

            if ($result < 0) {
                echo $resultstauses[$result];
                $stats['errorcount']++;
            } else {
                echo $result;
                $stats['totalcount'] += $result;
                $stats['successcount']++;
                if ($result > 0) {
                    $stats['nonemptycount']++;
                }
            }
        } catch (\Throwable $ex) {
            echo get_string('error');
            $stats['errorcount']++;
            $message = $ex->getMessage();
            if ($ex instanceof moodle_exception) {
                $message .= ' - ' . $ex->debuginfo;
            }
            debugging('Unexpected exception: ' . $message, DEBUG_DEVELOPER, $ex->getTrace());
        }

        echo '</dd>';
    }
}
if ($prevcomponent) {
    echo '</dl>';
}

echo $OUTPUT->heading(get_string('auditsummary', 'totara_userdata'), 3);
echo '<dl class="dl-horizontal">';
echo '<dt>' . get_string('audititemsprocessed', 'totara_userdata') . '</dt>';
echo '<dd>' . $stats['itemscount'] . '</dd>';
echo '<dt>' . get_string('audititemserror', 'totara_userdata') . '</dt>';
echo '<dd>' . $stats['errorcount'] . '</dd>';
echo '<dt>' . get_string('audititemsnonemtpy', 'totara_userdata') . '</dt>';
echo '<dd>' . $stats['nonemptycount'] . '</dd>';
echo '<dt>' . get_string('audittotalcount', 'totara_userdata') . '</dt>';
echo '<dd>' . $stats['totalcount'] . '</dd>';
echo '</dl>';

echo $OUTPUT->single_button($returnurl, get_string('continue'), 'get');

echo $OUTPUT->footer();
