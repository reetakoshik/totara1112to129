<?php

defined('MOODLE_INTERNAL') || die;

require_once __DIR__.'/lib/Settings.php';

$settings = null;

$ADMIN->add('themes', new admin_category('theme_golearningzone', 'GoLearningZone'));

new GoLearningZone\Settings\LoginPage($ADMIN);
new GoLearningZone\Settings\Footer($ADMIN);
new GoLearningZone\Settings\Header($ADMIN);
new GoLearningZone\Settings\Theme($ADMIN);
