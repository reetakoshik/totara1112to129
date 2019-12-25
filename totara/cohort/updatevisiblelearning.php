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
 * @package totara
 * @subpackage cohort
 */
/**
 * This class is an ajax back-end for updating audience learning visibility
 */
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/cohort/lib.php');

$id = required_param('id', PARAM_INT);
$type = required_param('type', PARAM_INT);
$value = required_param('value', PARAM_INT);

require_login();
require_sesskey();

$result = totara_cohort_update_audience_visibility($type, $id, $value);
$records = $DB->get_records(
    'cohort_visibility',
    array(
        'instanceid' => $id,
        'instancetype' => $type
    ),
    '',
    'id'
);

$data = array_keys($records);
$json = array(
    'id' => $id,
    'value' => $value,
    'result' => $result,
    'data' => $data,
);
if ($type == COHORT_ASSN_ITEMTYPE_COURSE) {
    $json['update'] = 'course';
} else if ($type == COHORT_ASSN_ITEMTYPE_PROGRAM || $type == COHORT_ASSN_ITEMTYPE_CERTIF) {
    $json['update'] = 'prog';
}

echo json_encode($json);
exit();
