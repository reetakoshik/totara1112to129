<?php

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname'   => '\mod_assign\event\submission_created',
        'callback'    => 'local_assignment_lz_observer::notify_direct_manager',
    ),
    array(
        'eventname'   => '\mod_assign\event\submission_updated',
        'callback'    => 'local_assignment_lz_observer::notify_direct_manager',
    ),
);