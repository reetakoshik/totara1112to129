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
	'login',
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
	'animate',
	'font-open-sans',
	'glzcss',
    'glzcss125',
    'responsive'
];
$enableheadr = get_config('theme_golearningzone','rmbtitle');
if($enableheadr ==1){
array_push($THEME->sheets, 'blockheader');
}
$THEME->layouts = [
    'admin' => [
        'file'          => 'admin.php',
        'regions'       => ['side-pre'],
        //'defaultregion' => 'side-pre',
        //'options'       => ['fluid' => true],
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

    'popup'   => [
        'file'    => 'popup.php',
        'regions' => [],
        'options' => [],
    ],

    'maintenance' => [
        'file'    => 'noblocks.php',
        'regions' => [],
        'options' => [],
     ],
    
    /*'maintenance' => array(
        'file' => 'noblocks.php',
        'regions' => array(),
        'options' => array('noblocks'=>true, 'nofooter'=>true, 'nonavbar'=>true, 'nocustommenu'=>true, 'nocourseheaderfooter'=>true),
    ),*/

    /*'standard' => [
        'file'          => 'default.php',
        'regions'       => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
    ],*/

    'standard' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
	
    'standardgoal' => array(
        'file' => 'defaultgoal.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
	
    'course' => [
        'file'          => 'course.php',
        'regions'       => ['first','side-pre'],
        'defaultregion' => 'side-pre',
    ],

    'secure' => [
        'file'          => 'default.php',
        'regions'       => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    'coursecategory' => [
        'file'          => 'default.php',
        'regions'       => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    'incourse' => [
        'file'          => 'report.php',
        'regions'       => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    'dashboard' => [
        'file'          => 'dashboard.php',
        'regions'       => [
            'side-pre',
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
        'regions'       => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    'redirect' => [
        'file'    => 'empty.php',
        'regions' => [],
        'options' => [],
    ],
    'noblocks' => [
        'file'    => 'noblocks.php',
        'regions' => [],
        'options' => array('noblocks'=>true),
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
//$THEME->parents_exclude_javascripts = [
//    'roots' => ['moodlebootstrap']
//];
$THEME->parents_exclude_sheets = array(
    'roots' => ['totara', 'totara-rtl']
);
