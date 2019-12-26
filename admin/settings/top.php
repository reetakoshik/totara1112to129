<?php

// This is the first file read by the lib/adminlib.php script
// We use it to create the categories in correct order,
// since they need to exist *before* settingpages and externalpages
// are added to them.

$systemcontext = context_system::instance();
$hassiteconfig = has_capability('moodle/site:config', $systemcontext);

$ADMIN->add('root', new admin_category('systeminformation', new lang_string('systeminformation')));

$ADMIN->add('systeminformation', new admin_externalpage('adminnotifications', new lang_string('notifications'), "$CFG->wwwroot/$CFG->admin/index.php"));

// Totara: always show the registration page unless registration was disabled via config.php.
$ADMIN->add('systeminformation', new admin_externalpage('totararegistration', new lang_string('totararegistration', 'totara_core'),
    "$CFG->wwwroot/$CFG->admin/register.php", 'moodle/site:config', empty($CFG->registrationenabled)));

// Totara flavour overview.
$hidden = (isset($CFG->showflavours) and empty($CFG->showflavours));
$ADMIN->add('systeminformation', new admin_externalpage('flavouroverview', new lang_string('flavouroverview', 'totara_flavour'), "$CFG->wwwroot/totara/flavour/view.php", 'moodle/site:config', $hidden));

 // hidden upgrade script
$ADMIN->add('root', new admin_externalpage('upgradesettings', new lang_string('upgradesettings', 'admin'), "$CFG->wwwroot/$CFG->admin/upgradesettings.php", 'moodle/site:config', true));

if ($hassiteconfig) {
    $optionalsubsystems = new admin_settingpage('optionalsubsystems', new lang_string('advancedfeatures', 'admin'));
    $ADMIN->add('systeminformation', $optionalsubsystems);
}

$ADMIN->add('root', new admin_category('users', new lang_string('users','admin')));
$ADMIN->add('root', new admin_category('audiences', new lang_string('cohorts', 'totara_cohort')));
$ADMIN->add('root', new admin_category('roles', new lang_string('permissions', 'role')));
$ADMIN->add('root', new admin_category('userdata', new lang_string('pluginname', 'totara_userdata')));
$ADMIN->add('root', new admin_category('positions', get_string('positions', 'totara_hierarchy'), totara_feature_disabled('positions')));
$ADMIN->add('root', new admin_category('organisations', get_string('organisations', 'totara_hierarchy')));
$ADMIN->add('root', new admin_category('competencies', get_string('competencies', 'totara_hierarchy'), totara_feature_disabled('competencies')));
$ADMIN->add('root', new admin_category('goals', get_string('goals', 'totara_hierarchy'), totara_feature_disabled('goals')));
$ADMIN->add('root', new admin_category('totara_plan', new lang_string('learningplans', 'totara_plan'),
    totara_feature_disabled('learningplans')
));
$ADMIN->add('root', new admin_category('appraisals', new lang_string('appraisals', 'totara_appraisal'),
    (totara_feature_disabled('appraisals') && totara_feature_disabled('feedback360'))
));
$ADMIN->add('root', new admin_category('courses', new lang_string('courses','admin')));
$ADMIN->add('root', new admin_category('programs', new lang_string('programs','totara_program')));
$ADMIN->add('root', new admin_category('certifications', new lang_string('certifications','totara_certification')));
$ADMIN->add('root', new admin_category('grades', new lang_string('grades')));
// TOTARA: We removed Moodles competency code as we've had competencies for years.
// $ADMIN->add('root', new admin_category('competencies', new lang_string('competencies', 'core_competency')));
$ADMIN->add('root', new admin_category('badges', new lang_string('badges'), empty($CFG->enablebadges)));
$ADMIN->add('root', new admin_category('localisation', new lang_string('localisation','admin')));
// TOTARA: Removed these categories and moved contents to localisation
//$ADMIN->add('root', new admin_category('location', new lang_string('location','admin')));
//$ADMIN->add('root', new admin_category('language', new lang_string('language')));
$ADMIN->add('root', new admin_category('modules', new lang_string('plugins', 'admin')));
$ADMIN->add('root', new admin_category('security', new lang_string('security','admin')));
$ADMIN->add('root', new admin_category('appearance', new lang_string('appearance','admin')));
$ADMIN->add('root', new admin_category('navigationcat', new lang_string('navigation')));
// TOTARA: Removed the frontpage category
//$ADMIN->add('root', new admin_category('frontpage', new lang_string('frontpage','admin')));
$ADMIN->add('root', new admin_category('server', new lang_string('server','admin')));
$ADMIN->add('root', new admin_category('mnet', new lang_string('net','mnet'), (isset($CFG->mnet_dispatcher_mode) and $CFG->mnet_dispatcher_mode === 'off')));
$ADMIN->add('root', new admin_category('reportsmain', new lang_string('reports')));
$ADMIN->add('root', new admin_category('reports', new lang_string('systemreports', 'admin')));
$ADMIN->add('root', new admin_category('development', new lang_string('development', 'admin')));

// hidden unsupported category
$ADMIN->add('root', new admin_category('unsupported', new lang_string('unsupported', 'admin'), true));
// Experimental settings category - Totara: we need it here so that we may add stuff there from anywhere.
$ADMIN->add('development', new admin_category('experimental', new lang_string('experimental','admin')));

// hidden search script
$ADMIN->add('root', new admin_externalpage('search', new lang_string('search', 'admin'), "$CFG->wwwroot/$CFG->admin/search.php", 'moodle/site:config', true));
