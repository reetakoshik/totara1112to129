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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara_cohort
 */

/**
 * This file is the ajax handler which adds the selected course/program/cert to a cohort's learning items
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot .'/cohort/lib.php');
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');

// this could take a while
core_php_time_limit::raise(0);

$context = context_system::instance();
require_login();
require_capability('moodle/cohort:manage', $context);
require_sesskey();

$cohortid = required_param('cohortid', PARAM_INT);
$type = required_param('type', PARAM_TEXT); // The association type, one of course, program, or certification.
$updateids = optional_param('u', 0, PARAM_SEQUENCE); // A comma separated list of {type} id's to associate with the given cohort.
$delid = optional_param('d', 0, PARAM_INT); // An id relating to an association to delete.
$value = optional_param('v', COHORT_ASSN_VALUE_ENROLLED, PARAM_INT);

$iscourses = ($type == COHORT_ASSN_ITEMTYPE_COURSE);
$isprograms = ($type == COHORT_ASSN_ITEMTYPE_PROGRAM);
$iscertifications = ($type == COHORT_ASSN_ITEMTYPE_CERTIF);

// This script only deals with courses, programs, and certifications.
if (!$iscourses && !$isprograms && !$iscertifications) {
    throw new coding_exception('Invalid type passed.');
}

// List of courses/progs/certs that have already been associated.
$knownassociations = totara_cohort_get_associations($cohortid, $type, $value);
$knowninstanceids = array();
foreach ($knownassociations as $association) {
    $knowninstanceids[$association->instanceid] = $association->id;
}
$newassociations = array();

if (!empty($type)) {
    $updateids = explode(',', $updateids);
    foreach ($updateids as $instanceid) {
        if (isset($knowninstanceids[$instanceid])) {
            // The association is already known, just continue on past it, no change here.
            continue;
        }
        if ($iscourses && !$DB->record_exists('course', array('id' => $instanceid))) {
            // Its not a real course Jim.
            continue;
        }
        if ($isprograms && !$DB->record_exists('prog', array('id' => $instanceid, 'certifid' => null))) {
            // Its not a real program Jim.
            continue;
        }
        if ($iscertifications) {
            $sql = "SELECT p.certifid
                      FROM {prog} p
                INNER JOIN {certif} c ON c.id = p.certifid
                     WHERE p.id = :instanceid";
            if (!$DB->record_exists_sql($sql, array('instanceid' => $instanceid))) {
                // Its not a real certification Jim.
                continue;
            }
        }
        // Its a new association Jim, send it to the archives.
        totara_cohort_add_association($cohortid, $instanceid, $type, $value);
    }
}

if (!empty($delid)) {
    // We don't need to check this exists in the database, we just need to check that it is a known association.
    if (!in_array($delid, $knowninstanceids)) {
        // You cannot delete an unknown association!
        throw new coding_exception('Invalid association specified for deletion.');
    }
    ignore_user_abort(true);
    totara_cohort_delete_association($cohortid, $delid, $type, $value);
}

// All new associations have been added, and if one was removed it has been so now.
// Do not wait for the membership updates, let cron do the thing asap.
$adhoctask = new \totara_cohort\task\sync_dynamic_cohort_task();
$adhoctask->set_custom_data($cohortid);
$adhoctask->set_component('totara_cohort');
\core\task\manager::queue_adhoc_task($adhoctask);

// This is an JSON script, in order for it to be valid we need to return some JSON.
echo "{}";
