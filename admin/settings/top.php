<?php

// This is the first file read by the lib/adminlib.php script
// We use it to create the categories in correct order,
// since they need to exist *before* settingpages and externalpages
// are added to them.

$systemcontext = context_system::instance();
$hassiteconfig = has_capability('moodle/site:config', $systemcontext);

$ADMIN->add('root', new admin_externalpage('adminnotifications', new lang_string('notifications'), "$CFG->wwwroot/$CFG->admin/index.php"));

// Totara: always show the registration page unless registration was disabled via config.php.
$ADMIN->add('root', new admin_externalpage('totararegistration', new lang_string('totararegistration', 'totara_core'),
    "$CFG->wwwroot/$CFG->admin/register.php", 'moodle/site:config', empty($CFG->registrationenabled)));

// Totara flavour overview.
$hidden = (isset($CFG->showflavours) and empty($CFG->showflavours));
$ADMIN->add('root', new admin_externalpage('flavouroverview', new lang_string('flavouroverview', 'totara_flavour'), "$CFG->wwwroot/totara/flavour/view.php", 'moodle/site:config', $hidden));

 // hidden upgrade script
$ADMIN->add('root', new admin_externalpage('upgradesettings', new lang_string('upgradesettings', 'admin'), "$CFG->wwwroot/$CFG->admin/upgradesettings.php", 'moodle/site:config', true));

if ($hassiteconfig) {
    $optionalsubsystems = new admin_settingpage('optionalsubsystems', new lang_string('advancedfeatures', 'admin'));
    $ADMIN->add('root', $optionalsubsystems);
}

$ADMIN->add('root', new admin_category('users', new lang_string('users','admin')));
$ADMIN->add('root', new admin_category('hierarchies', new lang_string('hierarchies','totara_hierarchy')));
$ADMIN->add('root', new admin_category('totara_plan', new lang_string('learningplans', 'totara_plan'),
    totara_feature_disabled('learningplans')
));
$ADMIN->add('root', new admin_category('appraisals', new lang_string('appraisals', 'totara_appraisal'),
    (totara_feature_disabled('appraisals') && totara_feature_disabled('feedback360'))
));
$ADMIN->add('root', new admin_category('courses', new lang_string('courses','admin')));
$ADMIN->add('root', new admin_category('grades', new lang_string('grades')));
// TOTARA: We removed Moodles competency code as we've had competencies for years.
// $ADMIN->add('root', new admin_category('competencies', new lang_string('competencies', 'core_competency')));
$ADMIN->add('root', new admin_category('badges', new lang_string('badges'), empty($CFG->enablebadges)));
$ADMIN->add('root', new admin_category('location', new lang_string('location','admin')));
$ADMIN->add('root', new admin_category('language', new lang_string('language')));
$ADMIN->add('root', new admin_category('modules', new lang_string('plugins', 'admin')));
$ADMIN->add('root', new admin_category('security', new lang_string('security','admin')));
$ADMIN->add('root', new admin_category('appearance', new lang_string('appearance','admin')));
$ADMIN->add('root', new admin_category('frontpage', new lang_string('frontpage','admin')));
$ADMIN->add('root', new admin_category('server', new lang_string('server','admin')));
$ADMIN->add('root', new admin_category('mnet', new lang_string('net','mnet'), (isset($CFG->mnet_dispatcher_mode) and $CFG->mnet_dispatcher_mode === 'off')));
$ADMIN->add('root', new admin_category('reports', new lang_string('reports')));
$ADMIN->add('root', new admin_category('development', new lang_string('development', 'admin')));

// hidden unsupported category
$ADMIN->add('root', new admin_category('unsupported', new lang_string('unsupported', 'admin'), true));
// Experimental settings category - Totara: we need it here so that we may add stuff there from anywhere.
$ADMIN->add('development', new admin_category('experimental', new lang_string('experimental','admin')));

// hidden search script
$ADMIN->add('root', new admin_externalpage('search', new lang_string('search', 'admin'), "$CFG->wwwroot/$CFG->admin/search.php", 'moodle/site:config', true));
