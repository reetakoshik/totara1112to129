<?php
// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = [
    'Сontractor' => [
        'functions' => ['qualifications', 'trainings', 'qualificationsbydate', 'trainingsbydate'],
        'restrictedusers' => 0,
        'enabled'=>1,
    ],
];
// We defined the web service functions to install.
$functions = [
    'qualifications' => [
        'classname'   => 'local_contractor_webservice_external',
        'methodname'  => 'qualifications',
        'classpath'   => 'local/contractor_webservice/externallib.php',
        'description' => 'Returns user qualifications XML',
        'type'        => 'read',
    ],
    'trainings' => [
        'classname'   => 'local_contractor_webservice_external',
        'methodname'  => 'trainings',
        'classpath'   => 'local/contractor_webservice/externallib.php',
        'description' => 'Returns offline training XML',
        'type'        => 'read',
    ],
    'qualificationsbydate' => [
        'classname'   => 'local_contractor_webservice_external',
        'methodname'  => 'qualificationsbydate',
        'classpath'   => 'local/contractor_webservice/externallib.php',
        'description' => 'Returns user qualifications XML',
        'type'        => 'read',
    ],
    'trainingsbydate' => [
        'classname'   => 'local_contractor_webservice_external',
        'methodname'  => 'trainingsbydate',
        'classpath'   => 'local/contractor_webservice/externallib.php',
        'description' => 'Returns offline training XML',
        'type'        => 'read',
    ]
];