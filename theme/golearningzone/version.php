<?php

defined('MOODLE_INTERNAL') || die;

$plugin->version  = 2019033001;     // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2017051509;       // Requires this Moodle version.
$plugin->component = 'theme_golearningzone';   // Full name of the plugin (used for diagnostics).
$plugin->dependencies = array(
    'theme_roots' => 2018082400,
);