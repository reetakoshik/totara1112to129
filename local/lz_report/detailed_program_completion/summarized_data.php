<?php

require_once __DIR__.'/../../../config.php';
require_once $CFG->dirroot.'/totara/reportbuilder/lib.php';
require_once __DIR__.'/../summarized_data/lib/summarized_reportbuilder.php';

$utils = require_once __DIR__.'/../summarized_data/lib/utils.php';

$reportid = optional_param('reportid', '0', PARAM_INT);

$utils['setup_page']($reportid);

if (!$reportid) {
    $utils['print_json']([]);
}

$utils['print_json']([
    'users_assigned_to_program' => $utils['users_assigned_to_program']($reportid),
    'users_with_program_completion_status' => $utils['users_with_program_completion_status']($reportid)
]);