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
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @package totara
 * @subpackage plan
 */

header("Content-Type:text/plain");
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/evidence/lib.php');
require_once($CFG->dirroot.'/totara/plan/development_plan.class.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

// 1. Get information
$competencyid = required_param('competencyid', PARAM_INT);
$prof = required_param('prof', PARAM_INT);
$planid = required_param('planid', PARAM_INT);

// Permissions check
require_login();
require_sesskey();

// Check permission to access the plan
$plan = new development_plan($planid);
$userid = $plan->userid;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/totara/plan/components/competency/update-competency-setting.php',
    array('competencyid' => $competencyid, 'prof' => $prof, 'planid' => $planid)));

$componentname = 'competency';
$component = $plan->get_component($componentname);

$result = hierarchy_can_add_competency_evidence($plan, $component, $userid, $competencyid);

if ($result !== true) {
    die(get_string($result[0],$result[1]));
}

// Update the competency evidence
$details = new stdClass();

// Get user's current primary position and organisation (if any)
$jobassignment = \totara_job\job_assignment::get_first($plan->userid, false);
if ($jobassignment) {
    $details->positionid = $jobassignment->positionid;
    $details->organisationid = $jobassignment->organisationid;
}

$details->assessorname = fullname($USER);
$details->assessorid = $USER->id;

$result = hierarchy_add_competency_evidence($competencyid, $userid, $prof, $component, $details);

if ($result) {
    // Log it.
    $competencyname = $DB->get_field('comp', 'fullname', array('id' => $competencyid));
    $data = array(
        'objectid' => $plan->id,
        'context' => \context_system::instance(),
        'relateduserid' => $plan->userid,
        'other' => array(
            'name' => $plan->name,
            'component' => 'competencyproficiency',
            'componentid' => $competencyid,
            'componentname' => $competencyname,
            'proficiencyvalue' => $prof,
        ),
    );
    \totara_plan\event\component_updated::create($data)->trigger();

    // Check if any plans this competency belongs to are complete.
    dp_plan_item_updated($userid, 'competency', $competencyid);

    echo "OK";
} else {
    echo "FAIL";
}
