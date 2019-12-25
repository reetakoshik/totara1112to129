<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main administration script.
 *
 * @package    core
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Check that config.php exists, if not then call the install script
if (!file_exists('../config.php')) {
    header('Location: ../install.php');
    die();
}

// Make sure we have everything necessary for standard libraries.
require(__DIR__ . '/../lib/environmentmincheck.php');

define('NO_OUTPUT_BUFFERING', true);

if (isset($_POST['upgradekey'])) {
    // Before you start reporting issues about the collision attacks against
    // SHA-1, you should understand that we are not actually attempting to do
    // any cryptography here. This is hashed purely so that the key is not
    // that apparent in the address bar itself. Anyone who catches the HTTP
    // traffic can immediately use it as a valid admin key.
    header('Location: index.php?cache=0&upgradekeyhash='.sha1($_POST['upgradekey']));
    die();
}

if ((isset($_GET['cache']) and $_GET['cache'] === '0')
        or (isset($_POST['cache']) and $_POST['cache'] === '0')
        or (!isset($_POST['cache']) and !isset($_GET['cache']) and empty($_GET['sesskey']) and empty($_POST['sesskey']))) {
    // Prevent caching at all cost when visiting this page directly,
    // we redirect to self once we known no upgrades are necessary.
    // Note: $_GET and $_POST are used here intentionally because our param cleaning is not loaded yet.
    // Note2: the sesskey is present in all block editing hacks, we can not redirect there, so enable caching.
    define('CACHE_DISABLE_ALL', true);

    // Force OPcache reset if used, we do not want any stale caches
    // when detecting if upgrade necessary or when running upgrade.
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    $cache = 0;

} else {
    $cache = 1;
}

require('../config.php');
require_once($CFG->dirroot . '/totara/core/db/utils.php');

// Invalidate the cache of version.php in any circumstances to help core_component
// detecting if the version has changed and component cache should be reset.
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($CFG->dirroot . '/version.php', true);
}
// Make sure the component cache gets rebuilt if necessary, any method that
// indirectly calls the protected init() method is good here.
core_component::get_core_subsystems();

if (isloggedin()) {
    // A major upgrade is required.
    // Terminate the session and redirect back here before anything DB-related happens.
    redirect_if_major_upgrade_required();
}

require_once($CFG->libdir.'/adminlib.php');    // various admin-only functions
require_once($CFG->libdir.'/upgradelib.php');  // general upgrade/install related functions

$confirmupgrade = optional_param('confirmupgrade', 0, PARAM_BOOL); // Core upgrade confirmed?
$confirmrelease = optional_param('confirmrelease', 0, PARAM_BOOL); // Core release info and server checks confirmed?
$confirmplugins = optional_param('confirmplugincheck', 0, PARAM_BOOL); // Plugins check page confirmed?
$showallplugins = optional_param('showallplugins', 0, PARAM_BOOL); // Show all plugins on the plugins check page?
$agreelicense = optional_param('agreelicense', 0, PARAM_BOOL); // GPL license confirmed for installation?
$upgradekeyhash = optional_param('upgradekeyhash', null, PARAM_ALPHANUM); // Hash of provided upgrade key.

// Set up PAGE.
$url = new moodle_url('/admin/index.php');
$url->param('cache', $cache);
if (isset($upgradekeyhash)) {
    $url->param('upgradekeyhash', $upgradekeyhash);
}
$PAGE->set_url($url);
unset($url);

$PAGE->set_pagelayout('admin'); // Set a default pagelayout

$documentationlink = '<a href="http://docs.moodle.org/en/Installation">Installation docs</a>';

// Check some PHP server settings

if (ini_get_bool('session.auto_start')) {
    print_error('phpvaroff', 'debug', '', (object)array('name'=>'session.auto_start', 'link'=>$documentationlink));
}

if (!ini_get_bool('file_uploads')) {
    print_error('phpvaron', 'debug', '', (object)array('name'=>'file_uploads', 'link'=>$documentationlink));
}

if (is_float_problem()) {
    print_error('phpfloatproblem', 'admin', '', $documentationlink);
}

// Set some necessary variables during set-up to avoid PHP warnings later on this page
if (!isset($CFG->release)) {
    $CFG->release = '';
}
if (!isset($CFG->version)) {
    $CFG->version = '';
}
if (!isset($CFG->branch)) {
    $CFG->branch = '';
}

$version = null;
$release = null;
$branch = null;
$TOTARA = new stdClass(); // Prevent IDE complaints.
require("$CFG->dirroot/version.php");       // defines $version, $release, $branch and $maturity
$CFG->target_release = $release;            // used during installation and upgrades

if (!$version or !$release) {
    print_error('withoutversion', 'debug'); // without version, stop
}

// Setup totara version variables and verify upgrade is possible,
// note that lib/setup.php does upgrade checks for 1.x/2.2.x upgrade path.
$totarainfo = totara_version_info();
if (!empty($totarainfo->totaraupgradeerror)){
    $PAGE->set_pagelayout('maintenance');
    $PAGE->set_popup_notification_allowed(false);
    print_error($totarainfo->totaraupgradeerror, 'totara_core', new moodle_url('/admin/'), $totarainfo);
}

if (!core_tables_exist()) {
    $PAGE->set_pagelayout('maintenance');
    $PAGE->set_popup_notification_allowed(false);

    // fake some settings
    $CFG->docroot = 'http://docs.moodle.org';

    $strinstallation = get_string('installation', 'install');

    // remove current session content completely
    \core\session\manager::terminate_current();

    // Totara: Ask for the upgrade key even during install,
    // we do not want anybody to hijack the site before the install is finished.
    check_upgrade_key($upgradekeyhash);

    if (empty($agreelicense)) {
        $strlicense = get_string('license');

        $PAGE->navbar->add($strlicense);
        $PAGE->set_title($strinstallation.' - Totara '.$TOTARA->release);
        $PAGE->set_heading($strinstallation);
        $PAGE->set_cacheable(false);

        /** @var core_admin_renderer $output */
        $output = $PAGE->get_renderer('core', 'admin');
        echo $output->install_licence_page();
        die();
    }
    if (empty($confirmrelease)) {
        require_once($CFG->libdir.'/environmentlib.php');
        list($envstatus, $environment_results) = check_totara_environment();
        $strcurrentrelease = get_string('currentrelease');

        $PAGE->navbar->add($strcurrentrelease);
        $PAGE->set_title($strinstallation);
        $PAGE->set_heading($strinstallation . ' - Totara ' . $TOTARA->release);
        $PAGE->set_cacheable(false);

        /** @var core_admin_renderer $output */
        $output = $PAGE->get_renderer('core', 'admin');
        echo $output->install_environment_page($maturity, $envstatus, $environment_results, $TOTARA->release);
        die();
    }

    // check plugin dependencies
    $failed = array();
    if (!core_plugin_manager::instance()->all_plugins_ok($version, $failed)) {
        $PAGE->navbar->add(get_string('pluginscheck', 'admin'));
        $PAGE->set_title($strinstallation);
        $PAGE->set_heading($strinstallation . ' - Moodle ' . $CFG->target_release);

        $output = $PAGE->get_renderer('core', 'admin');
        $url = new moodle_url($PAGE->url, array('agreelicense' => 1, 'confirmrelease' => 1, 'lang' => $CFG->lang));
        echo $output->unsatisfied_dependencies_page($version, $failed, $url);
        die();
    }
    unset($failed);

    $strdatabasesetup = get_string('databasesetup');
    upgrade_init_javascript();

    $PAGE->navbar->add($strdatabasesetup);
    $PAGE->set_title($strinstallation.' - Totara '.$TOTARA->release);
    $PAGE->set_heading($strinstallation);
    $PAGE->set_cacheable(false);

    $output = $PAGE->get_renderer('core', 'admin');
    echo $output->header();

    // Totara: do not allow new installations without prefix, even MySQL needs it since 8.0.
    if (strlen($DB->get_prefix()) < 1) {
        print_error('prefixcannotbeempty', 'error', '', $DB->get_dbfamily());
    }

    if (!$DB->setup_is_unicodedb()) {
        if (!$DB->change_db_encoding()) {
            // If could not convert successfully, throw error, and prevent installation
            print_error('unicoderequired', 'admin');
        }
    }

    install_core($version, true);
}


// Check version of Moodle code on disk compared with database
// and upgrade if possible.

if (!$cache) {
    // Do not try to do anything fancy in non-cached mode,
    // this prevents themes from fetching data from non-existent tables.
    $PAGE->set_pagelayout('maintenance');
    $PAGE->set_popup_notification_allowed(false);
}

$stradministration = get_string('administration');
$PAGE->set_context(context_system::instance());

if (empty($CFG->version)) {
    print_error('missingconfigversion', 'debug');
}

// Detect config cache inconsistency, this happens when you switch branches on dev servers.
if ($CFG->version != $DB->get_field('config', 'value', array('name'=>'version'))) {
    purge_all_caches();
    redirect(new moodle_url($PAGE->url), 'Config cache inconsistency detected, resetting caches...');
}

if (!$cache and $totarainfo->upgradecore) {
    // Totara: this is executed when Moodle version or Totara release changed.

    $PAGE->set_url(new moodle_url($PAGE->url, array(
        'confirmupgrade' => $confirmupgrade,
        'confirmrelease' => $confirmrelease,
        'confirmplugincheck' => $confirmplugins,
    )));

    check_upgrade_key($upgradekeyhash);

    // Warning about upgrading a test site.
    $testsite = false;
    if (defined('BEHAT_SITE_RUNNING')) {
        $testsite = 'behat';
    }

    // Totara: do not hack themerev here!

    // We purge all of MUC's caches here.
    // Caches are disabled for upgrade by CACHE_DISABLE_ALL so we must set the first arg to true.
    // This ensures a real config object is loaded and the stores will be purged.
    // This is the only way we can purge custom caches such as memcache or APC.
    // Note: all other calls to caches will still used the disabled API.
    cache_helper::purge_all(true);
    // We then purge the regular caches.
    purge_all_caches();

    // Totara: do not hack themerev here!

    /** @var core_admin_renderer $output */
    $output = $PAGE->get_renderer('core', 'admin');

    if (upgrade_stale_php_files_present()) {
        $PAGE->set_title($stradministration);
        $PAGE->set_cacheable(false);

        echo $output->upgrade_stale_php_files_page();
        die();
    }

    if (empty($confirmupgrade)) {
        $strdatabasechecking = get_string('databasechecking', '', $totarainfo);

        $PAGE->set_title($stradministration);
        $PAGE->set_heading($strdatabasechecking);
        $PAGE->set_cacheable(false);

        echo $output->upgrade_confirm_page($totarainfo, $maturity, $testsite);
        die();

    } else if (empty($confirmrelease)){
        require_once($CFG->libdir.'/environmentlib.php');
        list($envstatus, $environment_results) = check_totara_environment();
        $strcurrentrelease = get_string('currentrelease');

        $PAGE->navbar->add($strcurrentrelease);
        $PAGE->set_title($strcurrentrelease);
        $PAGE->set_heading($strcurrentrelease);
        $PAGE->set_cacheable(false);

        echo $output->upgrade_environment_page($TOTARA->release, $envstatus, $environment_results);
        die();

    } else if (empty($confirmplugins)) {
        $strplugincheck = get_string('plugincheck');

        $PAGE->navbar->add($strplugincheck);
        $PAGE->set_title($strplugincheck);
        $PAGE->set_heading($strplugincheck);
        $PAGE->set_cacheable(false);

        $pluginman = core_plugin_manager::instance();

        echo $output->upgrade_plugin_check_page(core_plugin_manager::instance(), \core\update\checker::instance(),
                $version, $showallplugins,
                new moodle_url($PAGE->url, array('confirmplugincheck' => 0, 'cache' => 0)),
                new moodle_url($PAGE->url, array('confirmplugincheck' => 1, 'cache' => 0)));
        die();

    } else {
        // Always verify plugin dependencies!
        $failed = array();
        if (!core_plugin_manager::instance()->all_plugins_ok($version, $failed)) {
            // Somebody is hacking url parameters to skip tests, no way!
            redirect(new moodle_url($PAGE->url, array('confirmplugincheck' => 0, 'cache' => 0)));
        }
        unset($failed);

        // Launch main upgrade.
        upgrade_core($version, true);
    }
} else if ($version < $CFG->version) {
    // Totara: this is not reached because we do the test above on $a->totaraupgradeerror
    // better stop here, we can not continue with plugin upgrades or anything else
    throw new moodle_exception('downgradedcore', 'error', new moodle_url('/admin/'));
}

// Updated human-readable release version if necessary
if (!$cache and $release <> $CFG->release) {  // Update the release version
    set_config('release', $release);
}

if (!$cache and $branch <> $CFG->branch) {  // Update the branch
    set_config('branch', $branch);
}

if (!$cache and moodle_needs_upgrading()) {

    $PAGE->set_url(new moodle_url($PAGE->url, array('confirmplugincheck' => $confirmplugins)));

    check_upgrade_key($upgradekeyhash);

    if (!$PAGE->headerprinted) {
        // means core upgrade or installation was not already done

        $pluginman = core_plugin_manager::instance();
        $output = $PAGE->get_renderer('core', 'admin');

        if (!$confirmplugins) {
            $strplugincheck = get_string('plugincheck');

            $PAGE->navbar->add($strplugincheck);
            $PAGE->set_title($strplugincheck);
            $PAGE->set_heading($strplugincheck);
            $PAGE->set_cacheable(false);

            // Show plugins info.
            echo $output->upgrade_plugin_check_page($pluginman, \core\update\checker::instance(),
                    $version, $showallplugins,
                    new moodle_url($PAGE->url, array('confirmplugincheck' => 0, 'cache' => 0)),
                    new moodle_url($PAGE->url, array('confirmplugincheck' => 1, 'cache' => 0)));
            die();
        }
    }

    // Make sure plugin dependencies are always checked.
    $pluginman = core_plugin_manager::instance();
    $failed = array();
    if (!$pluginman->all_plugins_ok($version, $failed)) {
        // Somebody is hacking url parameters to skip tests, no way!
        redirect(new moodle_url($PAGE->url, array('confirmplugincheck' => 0, 'cache' => 0)));
    }
    unset($failed);

    // install/upgrade all plugins and other parts
    upgrade_noncore(true);
}

// If this is the first install, indicate that this site is fully configured
// except the admin password
if (during_initial_install()) {
    set_config('rolesactive', 1); // after this, during_initial_install will return false.
    set_config('adminsetuppending', 1);
    // we need this redirect to setup proper session
    upgrade_finished("index.php?sessionstarted=1&amp;lang=$CFG->lang");
}

// make sure admin user is created - this is the last step because we need
// session to be working properly in order to edit admin account
 if (!empty($CFG->adminsetuppending)) {
    $sessionstarted = optional_param('sessionstarted', 0, PARAM_BOOL);
    if (!$sessionstarted) {
        redirect("index.php?sessionstarted=1&lang=$CFG->lang");
    } else {
        $sessionverify = optional_param('sessionverify', 0, PARAM_BOOL);
        if (!$sessionverify) {
            $SESSION->sessionverify = 1;
            redirect("index.php?sessionstarted=1&sessionverify=1&lang=$CFG->lang");
        } else {
            if (empty($SESSION->sessionverify)) {
                print_error('installsessionerror', 'admin', "index.php?sessionstarted=1&lang=$CFG->lang");
            }
            unset($SESSION->sessionverify);
        }
    }

    // Cleanup SESSION to make sure other code does not complain in the future.
    unset($SESSION->has_timed_out);
    unset($SESSION->wantsurl);

    // at this stage there can be only one admin unless more were added by install - users may change username, so do not rely on that
    $adminids = explode(',', $CFG->siteadmins);
    $adminuser = get_complete_user_data('id', reset($adminids));

    if ($adminuser->password === 'adminsetuppending') {
        // prevent installation hijacking
        if ($adminuser->lastip !== getremoteaddr()) {
            print_error('installhijacked', 'admin');
        }
        // login user and let him set password and admin details
        $adminuser->newadminuser = 1;
        complete_user_login($adminuser);
        redirect("$CFG->wwwroot/user/editadvanced.php?id=$adminuser->id"); // Edit thyself

    } else {
        unset_config('adminsetuppending');
    }

} else {
    // just make sure upgrade logging is properly terminated
    upgrade_finished('upgradesettings.php');
}

// Now we can be sure everything was upgraded and caches work fine,
// redirect if necessary to make sure caching is enabled.
if (!$cache) {
    redirect(new moodle_url('/admin/index.php', array('cache' => 1)));
}

// Check for valid admin user - no guest autologin
require_login(0, false);
if (isguestuser()) {
    // Login as real user!
    $SESSION->wantsurl = (string)new moodle_url('/admin/index.php');
    redirect(get_login_url());
}
$context = context_system::instance();

if (!has_capability('moodle/site:config', $context)) {
    // Do not throw exception display an empty page with administration menu if visible for current user.
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->footer();
    exit;
}

// check that site is properly customized
$site = get_site();
if (empty($site->shortname)) {
    // probably new installation - lets return to frontpage after this step
    // remove settings that we want uninitialised
    //unset_config('registerauth'); // Not useful for Totara customers.
    unset_config('timezone'); // Force admin to select timezone!
    redirect('upgradesettings.php?return=site');
}

// setup critical warnings before printing admin tree block
$insecuredataroot = is_dataroot_insecure(true);
$SESSION->admin_critical_warning = ($insecuredataroot==INSECURE_DATAROOT_ERROR);

$adminroot = admin_get_root();

// Check if there are any new admin settings which have still yet to be set
if (any_new_admin_settings($adminroot)){
    redirect('upgradesettings.php');
}

// Return to original page that started the plugin uninstallation if necessary.
if (isset($SESSION->pluginuninstallreturn)) {
    $return = $SESSION->pluginuninstallreturn;
    unset($SESSION->pluginuninstallreturn);
    if ($return) {
        redirect($return);
    }
}

// Everything should now be set up, and the user is an admin
// Print default admin page with notifications.
$errorsdisplayed = defined('WARN_DISPLAY_ERRORS_ENABLED');

// We make the assumption that at least one schedule task should run once per day.
$lastcron = $DB->get_field_sql('SELECT MAX(lastruntime) FROM {task_scheduled}');
$cronoverdue = ($lastcron < time() - 3600 * 24);
$dbproblems = $DB->diagnose();
$maintenancemode = !empty($CFG->maintenance_enabled);

$buggyiconvnomb = (!function_exists('mb_convert_encoding') and @iconv('UTF-8', 'UTF-8//IGNORE', '100'.chr(130).'€') !== '100€');
// check if the site is registered on Moodle.org
// The hub registration functionality has been deprecated in version 11 and will be removed with version 12.
$registered = $DB->count_records('registration_hubs', array('huburl' => HUB_MOODLEORGHUBURL, 'confirmed' => 1));
// Check if there are any cache warnings.
$cachewarnings = cache_helper::warnings();
// Check if there are events 1 API handlers.
$eventshandlers = $DB->get_records_sql('SELECT DISTINCT component FROM {events_handlers}');
$themedesignermode = !empty($CFG->themedesignermode);

// Check if a directory with development libraries exists.
if (is_dir($CFG->dirroot.'/vendor') || is_dir($CFG->dirroot.'/node_modules')) {
    $devlibdir = true;
} else {
    $devlibdir = false;
}

admin_externalpage_setup('adminnotifications');

//get Totara specific info
require_once("$CFG->dirroot/$CFG->admin/registerlib.php");
$regdata = get_registration_data();
$activeusers = $regdata['activeusercount'];
$activeusers3mth = $regdata['activeusercount3mth'];
if (is_registration_required()) {
    redirect("$CFG->wwwroot/$CFG->admin/register.php?return=admin");
}

require_once($CFG->dirroot . '/totara/core/lib.php');
totara_site_version_tracking();

/* @var core_admin_renderer $output */
$output = $PAGE->get_renderer('core', 'admin');

echo $output->admin_notifications_page($maturity, $insecuredataroot, $errorsdisplayed, $cronoverdue, $dbproblems,
                                       $maintenancemode, null, null, $buggyiconvnomb,
                                       $registered, $cachewarnings, $eventshandlers, $themedesignermode, $devlibdir, null, $activeusers, $TOTARA->release, $activeusers3mth);
