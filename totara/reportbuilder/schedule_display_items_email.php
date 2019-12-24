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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_reportbuilder
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/core/utils.php');

$ids = required_param('ids', PARAM_SEQUENCE);
$ids = explode(',', $ids);
$emails = optional_param('emails', '', PARAM_TEXT);
$emails = explode(',', $emails);
$filtername = required_param('filtername', PARAM_TEXT);

require_login();
require_sesskey();

// Legacy Totara HTML ajax, this should be converted to json + AJAX_SCRIPT.
send_headers('text/html; charset=utf-8', false);
$context = context_system::instance();
$PAGE->set_context($context);

// Report builder render.
$renderer = $PAGE->get_renderer('totara_reportbuilder');

$items = '';
switch ($filtername) {
    case 'audiences':
        require_capability('moodle/cohort:view', $context);
        if (!empty($ids)) {
            list($insql, $params) = $DB->get_in_or_equal($ids);
            $items = $DB->get_records_select('cohort', "id {$insql}", $params, '', 'id, name');
        }
        break;
    case 'systemusers':
        require_capability('moodle/user:viewdetails', $context);
        if (!empty($ids)) {
            list($insql, $params) = $DB->get_in_or_equal($ids);
            $usernamefields = get_all_user_name_fields(true);
            $items = $DB->get_records_select('user', "id {$insql}", $params, '', 'id, ' . $usernamefields);
            foreach ($items as $item) {
                $item->fullname = fullname($item);
            }
        }
        break;
    case 'externalemails':
        if (!empty($emails)) {
            $items = array();
            foreach ($emails as $email) {
                $item = new stdClass();
                $item->id = $email;
                $item->name = $email;
                $items[] = $item;
            }
        }
        break;
}

echo html_writer::start_tag('div', array('class' => "list-{$filtername}"));
if (!empty($items)) {
    foreach ($items as $item) {
        echo $renderer->schedule_email_setting($item, $filtername);
    }
}
echo html_writer::end_tag('div');
