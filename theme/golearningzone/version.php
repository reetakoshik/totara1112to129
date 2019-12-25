<?php

defined('MOODLE_INTERNAL') || die;

$plugin->version  = 2017070404;     // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2015111606;       // Requires this Moodle version.
$plugin->component = 'theme_golearningzone';   // Full name of the plugin (used for diagnostics).
$plugin->dependencies = array(
    'theme_roots' => 2016092000,
);