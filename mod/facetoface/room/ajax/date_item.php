<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');

$timestart = optional_param('timestart', 0, PARAM_INT);
$timefinish = optional_param('timefinish', 0, PARAM_INT);
$sesiontimezone = optional_param('sesiontimezone', '99', PARAM_TIMEZONE);

require_sesskey();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/mod/facetoface/room/ajax/date_item.php');

// Render date string.
$out = '';
if ($timestart && $timefinish) {
    $out = \mod_facetoface\event_dates::render($timestart, $timefinish, $sesiontimezone);
}

echo json_encode($out);