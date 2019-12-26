<?php

$watchers = [
    [
        'hookname'    => '\format_activity_strips\hook\activity_definition',
        'callback'    => '\format_activity_strips\watcher\activity_definition::execute',
        'includefile' => null, 
        'priority'    => 100,
    ],
    [
        'hookname'    => '\format_activity_strips\hook\data_preprocessing',
        'callback'    => '\format_activity_strips\watcher\data_preprocessing::execute',
        'includefile' => null, 
        'priority'    => 100,
    ],
    [
        'hookname'    => '\format_activity_strips\hook\save_twofa_option',
        'callback'    => '\format_activity_strips\watcher\save_twofa_option::execute',
        'includefile' => null, 
        'priority'    => 100,
    ],
    [
        'hookname'    => '\format_activity_strips\hook\self_completion_form',
        'callback'    => '\format_activity_strips\watcher\self_completion_form::execute',
        'includefile' => null, 
        'priority'    => 100,
    ],
    [
        'hookname'    => '\format_activity_strips\hook\advanced_feartures',
        'callback'    => '\format_activity_strips\watcher\advanced_feartures::execute',
        'includefile' => null, 
        'priority'    => 100,
    ],
];
