<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$activityname = required_param('activityname', PARAM_TEXT);

$reportid = $DB->get_field('report_builder', 'id', ['shortname' => 'activities']);

if ((!isset($SESSION->reportbuilder)) || (!is_array($SESSION->reportbuilder))) {
    $SESSION->reportbuilder = [];
}

$SESSION->reportbuilder[$reportid] = [
	'activity-modulename' => [
		'operator' => 0,
		'value'    => $activityname
	]
];

redirect(new moodle_url('/local/lz_report/activity_search/embedded.php'));
