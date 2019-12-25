<?php

$capabilities = [
    'theme/golearningzone:viewadminblock' => [
        'riskbitmask'  => RISK_SPAM | RISK_PERSONAL | RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager'       => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ],
];