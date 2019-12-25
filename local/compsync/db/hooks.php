<?php

$watchers = [
    [
        'hookname'    => '\local_compsync\hook\totara_sync_get_element_files',
        'callback'    => '\local_compsync\watcher\totara_sync_get_element_files::execute',
        'includefile' => null, 
        'priority'    => 100,
    ]
];