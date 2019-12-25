<?php

$watchers = [
    [
        'hookname'    => '\local_assignment_lz\hook\grading_table_filter_participants',
        'callback'    => '\local_assignment_lz\watcher\grading_table_filter_participants::execute',
        'includefile' => null, 
        'priority'    => 100,
    ]
];