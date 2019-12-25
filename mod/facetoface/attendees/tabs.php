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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage facetoface
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

// Setup tabs
$tabs = array();
$activated = array();
$currenttab = array();

if (in_array('attendees', $allowed_actions)) {
    $url = new \moodle_url('/mod/facetoface/attendees/view.php', ['s' => $seminarevent->get_id()]);
    $tabs[] = new tabobject(
            'attendees',
            $url->out(),
            get_string('attendees', 'facetoface')
    );
    unset($actionurl);
}

if (in_array('waitlist', $allowed_actions)) {
    $url = new \moodle_url('/mod/facetoface/attendees/waitlist.php', ['s' => $seminarevent->get_id()]);
    $tabs[] = new tabobject(
            'waitlist',
            $url->out(),
            get_string('wait-list', 'facetoface')
    );
    unset($actionurl);
}

if (in_array('cancellations', $allowed_actions)) {
    $url = new \moodle_url('/mod/facetoface/attendees/cancellations.php', ['s' => $seminarevent->get_id()]);
    $tabs[] = new tabobject(
            'cancellations',
            $url->out(),
            get_string('cancellations', 'facetoface')
    );
    unset($actionurl);
}

if (in_array('takeattendance', $allowed_actions)) {
    $url = new \moodle_url('/mod/facetoface/attendees/takeattendance.php', ['s' => $seminarevent->get_id()]);
    $tabs[] = new tabobject(
            'takeattendance',
            $url->out(),
            get_string('takeattendance', 'facetoface')
    );
    unset($actionurl);
}

if (in_array('approvalrequired', $allowed_actions)) {
    $url = new \moodle_url('/mod/facetoface/attendees/approvalrequired.php', ['s' => $seminarevent->get_id()]);
    $tabs[] = new tabobject(
            'approvalrequired',
            $url->out(),
            get_string('approvalreqd', 'facetoface')
    );
    unset($actionurl);
}

if (in_array('messageusers', $allowed_actions)) {
    $url = new \moodle_url('/mod/facetoface/attendees/messageusers.php', ['s' => $seminarevent->get_id()]);
    $tabs[] = new tabobject(
            'messageusers',
            $url->out(),
            get_string('messageusers', 'facetoface')
    );
    unset($actionurl);
}

$activated[] = $action;
$currenttab[] = $action;

// Inactive tabs: get difference between allowed and available tabs
$inactive = array_diff($allowed_actions, $available_actions);

print_tabs(array($tabs), $currenttab, $inactive, $activated);
