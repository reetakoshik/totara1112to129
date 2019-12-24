<?php

$watchers = [
    [
        'hookname'    => '\local_lz_extension\hook\prog_messages_manager_construct',
        'callback'    => '\local_lz_extension\watcher\prog_messages_manager_construct::execute',
        'includefile' => null, 
        'priority'    => 100,
    ],
    [
        'hookname'    => '\local_lz_extension\hook\prog_messages_manager_display_form_element',
        'callback'    => '\local_lz_extension\watcher\prog_messages_manager_display_form_element::execute',
        'includefile' => null, 
        'priority'    => 100,
    ],
    [
        'hookname'    => '\local_lz_extension\hook\program_get_progress',
        'callback'    => '\local_lz_extension\watcher\program_get_progress::execute',
        'includefile' => null, 
        'priority'    => 100,
    ],
    [
        'hookname'    => '\local_lz_extension\hook\xlsx_export_writer',
        'callback'    => '\local_lz_extension\watcher\xlsx_export_writer::execute',
        'includefile' => null, 
        'priority'    => 100,
    ],
    [
        'hookname'    => '\local_lz_extension\hook\rb_source_dp_course_construct',
        'callback'    => '\local_lz_extension\watcher\rb_source_dp_course_construct::execute',
        'includefile' => null, 
        'priority'    => 100,
    ]
];
