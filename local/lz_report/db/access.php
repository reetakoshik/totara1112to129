<?php

require_once __DIR__.'/../etc/config.php';

$capabilities = [
    VIEW_LZ_REPORT_CAPABILITY => [
        'riskbitmask'  => RISK_SPAM,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ],
];