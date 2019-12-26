<?php

require_once __DIR__.'/../../../config.php';
require_once $CFG->libdir.'/completionlib.php';

// Parameters
$cmid = optional_param('id', 0, PARAM_INT);
$login = required_param('login', PARAM_TEXT);
$pass = required_param('pass', PARAM_TEXT);

$cm = get_coursemodule_from_id(null, $cmid, null, true, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_login($course, false, $cm);

$user = authenticate_user_login($login, $pass, false, $errorcode);

if (!$user) {
    header('Content-Type: application/json');

    echo json_encode([
        'Status' => 0,
        'Error'  => get_string('twofa-error', 'format_activity_strips')
    ]);

    exit();
}

$completion = new completion_info($course);
if (!$completion->is_enabled()) {
    throw new moodle_exception('completionnotenabled', 'completion');
}

if($cm->completion != COMPLETION_TRACKING_MANUAL) {
    error_or_ajax('cannotmanualctrack', $fromajax);
}

$completion->update_state($cm, 1);

header('Content-Type: application/json');

echo json_encode([
    'Status' => 1
]);

exit();

