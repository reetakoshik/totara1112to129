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
 * Edit/create a policy document version.
 *
 * @package     tool_policy
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//use tool_policy\form\commentview;

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/admin/tool/policy/commentview.php');

$policyid = optional_param('policyid', null, PARAM_INT);
$versionid = optional_param('versionid', null, PARAM_INT);

global $DB, $USER, $OUTPUT;

$PAGE->set_pagelayout('noblocks');
$PAGE->set_url(new moodle_url('/admin/tool/policy/editcommentview.php', array('policyid' => $policyid, 'versionid' => $versionid)));
$PAGE->navbar->add('add ekko policy comment');

echo $OUTPUT->header();

$formdata = $DB->get_record('tool_policy_versions', array('id' => $versionid));

//$form = new \tool_policy\form\commentview($PAGE->url, ['formdata' => $formdata]);
$mform = new commentview($PAGE->url, ['formdata' => $formdata]);
//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/policy/managedocs.php'));
} else if ($fromform = $mform->get_data()) {
  //In this case you process validated data. $mform->get_data() returns data posted in form.
    $record = new \stdClass();
    $record->policyversionid = $fromform->commentpolicyversionid;
    $record->commentext          = $fromform->comment['text'];
    $record->assignto            = $fromform->assignto;
    $record->cstatus             = $fromform->cstatus;
    $record->timecreated         = time();
    $record->timemodified        = time();
    $DB->insert_record('tool_policy_comment', $record);
    redirect(new moodle_url('/admin/tool/policy/managedocs.php'));
} else {
  // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
  // or on the first display of the form.
 
  //Set default data (if any)
  $mform->set_data($toform);
  //displays the form
  $mform->display();
}

 

echo $OUTPUT->footer();
?>