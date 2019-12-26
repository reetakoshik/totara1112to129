<?php
defined('MOODLE_INTERNAL') || die();
$messageproviders = array (
    // Notify teacher that a student has submitted a quiz attempt
    'sendpolicynotification' => array (
        'capability'  => 'tool/policy:emailnotifypolicy'
    )
);
/*$messageproviders = array (
    // Ordinary single forum posts
    'posts' => array (
    )
);*/