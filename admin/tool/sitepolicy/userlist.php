<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

require(__DIR__ . '/../../../config.php');

$userid = optional_param('userid', $USER->id, PARAM_INT);

require_login(null, false);

if ($userid != $USER->id) {
    require_capability('tool/sitepolicy:manage', context_system::instance());
    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    $currentuser = false;
} else {
    $user = $USER;
    $currentuser = true;
}

$userlisturl = \tool_sitepolicy\url_helper::user_sitepolicy_list($user->id);

$PAGE->set_context(context_system::instance());
$heading = get_string('userlistuserconsent', 'tool_sitepolicy');
$PAGE->set_url($userlisturl);
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

// Start the navigation off at the users branch.
$PAGE->navigation->extend_for_user($user);
if ($node = $PAGE->navigation->find('user' . $user->id, navigation_node::TYPE_USER)) {
    $node->make_active();
}
$PAGE->navbar->add($heading);

/** @var tool_sitepolicy_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy');
echo $renderer->header();
echo $renderer->heading($heading);
echo $renderer->manage_userconsents_table($user->id);
echo $renderer->footer();
