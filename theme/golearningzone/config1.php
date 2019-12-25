<?php

defined('MOODLE_INTERNAL') || die();

$THEME->doctype = 'html5';
$THEME->name = 'golearningzone';
$THEME->parents = ['roots', 'base'];
$THEME->yuicssmodules = [];
$THEME->enable_dock = true;
$THEME->supportscssoptimisation = false;

$THEME->sheets = [
    'totara',
    'golearningzone-base',
    'header',
    'block',
    'panel',
    'team',
    'topics',
    'program',
    'palette_blue',
    'palette_red',
    'palette_orange',
    'palette_green',
    'palette_custom',
    'glzcss'
];

$THEME->layouts = [
    'admin' => [
        'file'          => 'admin.php',
        'regions'       => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options'       => ['fluid' => true],
    ],

    'frontpage' => [
        'file'          => 'frontpage.php',
        'regions'       => [
            'side-pre',
            // 'side-top',
            'first',
            'second-left',
            'second-right',
            'third-left',
            'third-center',
            'third-right',
            'fourth-left',
            'fourth-center',
            'fourth-right',
        ],
        'defaultregion' => 'side-pre',
        'options'       => [
            'nobreadcrumb' => true
        ]
    ],
    'login' => [
        'file'    => 'login.php',
        'regions' => [],
        'options' => [
            'nologininfo'  => true, 
            'nocustommenu' => true, 
            'nonavbar'     => false, 
            'loginextra'   => true
        ],
    ],

    'noblocks' => [
        'file'    => 'noblocks.php',
        'regions' => [],
        'options' => [],
    ],

    'popup'   => [
        'file'    => 'popup.php',
        'regions' => [],
        'options' => [],
    ],

    // 'maintenance' => [
    //     'file'    => 'default.php',
    //     'regions' => [],
    //     'options' => [],
    //  ],

    'standard' => [
        'file'          => 'default.php',
        'regions'       => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
    ],

    'course' => [
        'file'          => 'course.php',
        'regions'       => ['side-pre','side-post'],
        'defaultregion' => 'side-post',
    ],

    'secure' => [
        'file'          => 'default.php',
        'regions'       => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],

    'coursecategory' => [
        'file'          => 'default.php',
        'regions'       => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],

    'incourse' => [
        'file'          => 'report.php',
        'regions'       => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],

    'dashboard' => [
        'file'          => 'dashboard.php',
        'regions'       => [
            'side-pre',
            'side-post',
            'first',
            'second-left',
            'second-right',
            'third-left',
            'third-center',
            'third-right',
            'fourth-left',
            'fourth-center',
            'fourth-right',
        ],
        'defaultregion' => 'side-pre',
        'options'       => [
            'nobreadcrumb'  => true
        ]
    ],

    'report' => [
        'file'          => 'report.php',
        'regions'       => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    'base' => [
        'file'          => 'report.php',
        'regions'       => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    'mypublic' => [
        'file'          => 'mypublic.php',
        'regions'       => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],

    'redirect' => [
        'file'    => 'empty.php',
        'regions' => [],
        'options' => [],
    ],
];

$THEME->javascripts = [];
$THEME->javascripts_footer = [
    'utils',
    'header',
    'layout',
    // adds bootstrap modals, which does not work in roots bootstrap
    'bootstrap-fix',
];
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->csspostprocess = 'theme_golearningzone_process_css';
$THEME->parents_exclude_javascripts = [
    'roots' => ['moodlebootstrap']
];
$THEME->javascripts = array('test');
$THEME->parents_exclude_sheets = array(
    'roots' => ['totara', 'totara-rtl']
);
