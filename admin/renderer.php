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
 * Renderer for core_admin subsystem
 *
 * @package    core
 * @subpackage admin
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Standard HTML output renderer for core_admin subsystem
 */
class core_admin_renderer extends plugin_renderer_base {

    /**
     * Display the 'Do you acknowledge the terms of the GPL' page. The first page
     * during install.
     * @return string HTML to output.
     */
    public function install_licence_page() {
        global $CFG;
        $output = '';

        $copyrightnotice = text_to_html(get_string('gpl3'));
        $copyrightnotice = str_replace('target="_blank"', 'onclick="this.target=\'_blank\'"', $copyrightnotice); // extremely ugly validation hack

        $continue = new single_button(new moodle_url($this->page->url, array(
            'lang' => $CFG->lang, 'agreelicense' => 1)), get_string('continue'), 'get');

        $a = new stdClass();
        $a->url = 'https://www.totaralearning.com/';
        $a->totaralearn = get_string('totaralearn', 'totara_core');
        $output .= $this->header();
        $output .= $this->heading(get_string('totaralearnlink', 'totara_core', $a), 2, 'centered');
        $output .= $this->heading(get_string('copyrightnotice'), 2, 'centered');
        $output .= $this->box($copyrightnotice, 'copyrightnotice');
        $output .= html_writer::empty_tag('br');
        $output .= $this->confirm(get_string('doyouagree'), $continue, "http://www.gnu.org/licenses/gpl-3.0.html");
        $output .= $this->footer();

        return $output;
    }

    /**
     * Display page explaining proper upgrade process,
     * there can not be any PHP file leftovers...
     *
     * @return string HTML to output.
     */
    public function upgrade_stale_php_files_page() {
        $output = '';
        $output .= $this->header();
        $output .= $this->heading(get_string('upgradestalefiles', 'admin'));
        $output .= $this->box_start('generalbox', 'notice');
        $output .= format_text(get_string('upgradestalefilesinfo', 'admin', get_docs_url('Upgrading')), FORMAT_MARKDOWN);
        $output .= html_writer::empty_tag('br');
        $output .= html_writer::tag('div', $this->single_button($this->page->url, get_string('reload'), 'get'), array('class' => 'buttons'));
        $output .= $this->box_end();
        $output .= $this->footer();

        return $output;
    }

    /**
     * Display the 'environment check' page that is displayed during install.
     * @param int $maturity
     * @param boolean $envstatus final result of the check (true/false)
     * @param array $environment_results array of results gathered
     * @param string $release moodle release
     * @return string HTML to output.
     */
    public function install_environment_page($maturity, $envstatus, $environment_results, $release) {
        global $CFG, $TOTARA;
        $output = '';

        $output .= $this->header();
        $output .= $this->maturity_warning($maturity);
        $output .= $this->heading("Totara {$TOTARA->release}");
        $output .= $this->active_flavour_notice(); // Totara flavours info.
        $output .= $this->release_notes_link();

        $output .= $this->environment_check_table($envstatus, $environment_results);

        if (!$envstatus) {
            $output .= $this->upgrade_reload(new moodle_url($this->page->url, array('agreelicense' => 1, 'lang' => $CFG->lang)));
        } else {
            $output .= $this->notification(get_string('environmentok', 'admin'), 'notifysuccess');
            $output .= $this->continue_button(new moodle_url($this->page->url, array(
                'agreelicense' => 1, 'confirmrelease' => 1, 'lang' => $CFG->lang)));
        }

        $output .= $this->footer();
        return $output;
    }

    /**
     * Displays the list of plugins with unsatisfied dependencies
     *
     * @param double|string|int $version Moodle on-disk version
     * @param array $failed list of plugins with unsatisfied dependecies
     * @param moodle_url $reloadurl URL of the page to recheck the dependencies
     * @return string HTML
     */
    public function unsatisfied_dependencies_page($version, array $failed, moodle_url $reloadurl) {
        $output = '';

        $output .= $this->header();
        $output .= $this->heading(get_string('pluginscheck', 'admin'));
        $output .= $this->warning(get_string('pluginscheckfailed', 'admin', array('pluginslist' => implode(', ', array_unique($failed)))));
        $output .= $this->plugins_check_table(core_plugin_manager::instance(), $version, array('xdep' => true));
        $output .= $this->warning(get_string('pluginschecktodo', 'admin'));
        $output .= $this->upgrade_reload($reloadurl);

        $output .= $this->footer();

        return $output;
    }

    /**
     * Display the 'You are about to upgrade Moodle' page. The first page
     * during upgrade.
     * @param stdClass $strnewversion
     * @param int $maturity
     * @param string $testsite
     * @return string HTML to output.
     */
    public function upgrade_confirm_page($strnewversion, $maturity, $testsite) {
        $output = '';

        $continueurl = new moodle_url($this->page->url, array('confirmupgrade' => 1, 'cache' => 0));
        $continue = new single_button($continueurl, get_string('continue'), 'get');
        $cancelurl = new moodle_url('/admin/index.php');

        $output .= $this->header();
        $output .= $this->maturity_warning($maturity);
        $output .= $this->test_site_warning($testsite);
        $output .= $this->confirm(get_string('upgradesure', 'admin', $strnewversion), $continue, $cancelurl);
        $output .= $this->footer();

        return $output;
    }

    /**
     * Display the environment page during the upgrade process.
     * @param string $release
     * @param boolean $envstatus final result of env check (true/false)
     * @param array $environment_results array of results gathered
     * @return string HTML to output.
     */
    public function upgrade_environment_page($release, $envstatus, $environment_results) {
        global $CFG, $TOTARA;
        $output = '';

        $output .= $this->header();
        $output .= $this->heading("Totara {$TOTARA->release}");
        $output .= $this->active_flavour_notice(); // Totara flavours info.
        $output .= $this->release_notes_link();
        $output .= $this->environment_check_table($envstatus, $environment_results);

        if (!$envstatus) {
            $output .= $this->upgrade_reload(new moodle_url($this->page->url, array('confirmupgrade' => 1, 'cache' => 0)));

        } else {
            $output .= $this->notification(get_string('environmentok', 'admin'), 'notifysuccess');

            if (empty($CFG->skiplangupgrade) and current_language() !== 'en') {
                $output .= $this->box(get_string('langpackwillbeupdated', 'admin'), 'generalbox', 'notice');
            }

            $output .= $this->continue_button(new moodle_url($this->page->url, array(
                'confirmupgrade' => 1, 'confirmrelease' => 1, 'cache' => 0)));
        }

        $output .= $this->footer();

        return $output;
    }

    /**
     * Display the upgrade page that lists all the plugins that require attention.
     * @param core_plugin_manager $pluginman provides information about the plugins.
     * @param \core\update\checker $checker provides information about available updates.
     * @param int $version the version of the Moodle code from version.php.
     * @param bool $showallplugins
     * @param moodle_url $reloadurl
     * @param moodle_url $continueurl
     * @return string HTML to output.
     */
    public function upgrade_plugin_check_page(core_plugin_manager $pluginman, \core\update\checker $checker,
            $version, $showallplugins, $reloadurl, $continueurl) {

        $output = '';

        $output .= $this->header();
        $output .= $this->box_start('generalbox', 'plugins-check-page');
        $output .= html_writer::tag('p', get_string('pluginchecknotice', 'core_plugin'), array('class' => 'page-description'));
        $output .= $this->plugins_check_table($pluginman, $version, array('full' => $showallplugins));
        $output .= $this->box_end();
        $output .= $this->upgrade_reload($reloadurl);

        $failed = array();
        if (!$pluginman->all_plugins_ok($version, $failed)) {
            $output .= $this->footer();
            return $output;
        }

        $button = new single_button($continueurl, get_string('upgradestart', 'admin'), 'get');
        $button->class = 'continuebutton';
        $output .= $this->render($button);
        $output .= $this->footer();

        return $output;
    }

    /**
     * Display a page to confirm plugin installation cancelation.
     *
     * @param array $abortable list of \core\update\plugininfo
     * @param moodle_url $continue
     * @return string
     */
    public function upgrade_confirm_abort_install_page(array $abortable, moodle_url $continue) {
        // The UI should not allow this.
        throw new moodle_exception('err_no_plugin_install_abortable', 'core_plugin');
    }

    /**
     * Display the admin notifications page.
     * @param int $maturity
     * @param bool $insecuredataroot warn dataroot is invalid
     * @param bool $errorsdisplayed warn invalid dispaly error setting
     * @param bool $cronoverdue warn cron not running
     * @param bool $dbproblems warn db has problems
     * @param bool $maintenancemode warn in maintenance mode
     * @param bool $buggyiconvnomb warn iconv problems
     * @param array|null $availableupdates array of \core\update\info objects or null
     * @param int|null $availableupdatesfetch timestamp of the most recent updates fetch or null (unknown)
     * @param string[] $cachewarnings An array containing warnings from the Cache API.
     * @param array $eventshandlers Events 1 API handlers.
     * @param bool $themedesignermode Warn about the theme designer mode.
     * @param bool $devlibdir Warn about development libs directory presence.
     * @param null $unused_latesterror Deprecated and unused since Totara 11.
     *
     * @return string HTML to output.
     */
    public function admin_notifications_page($maturity, $insecuredataroot, $errorsdisplayed,
            $cronoverdue, $dbproblems, $maintenancemode, $availableupdates, $availableupdatesfetch,
            $buggyiconvnomb, $registered, array $cachewarnings = array(), $eventshandlers = 0,
            $themedesignermode = false, $devlibdir = false, $unused_latesterror = null, $activeusers, $totara_release, $activeusers3mth = 0) {

        global $CFG, $PAGE ,$DB;
        $lzversion= $DB->get_record_sql("SELECT * FROM {config_plugins}  WHERE plugin = ? AND name= ?", array('local_lz_extension','version'));
        $t=explode(' ', $totara_release);
        $output = '';
        /** @var totara_core_renderer $totara_renderer */
        $totara_renderer = $PAGE->get_renderer('totara_core');

        $output .= $this->header();
        $output .= $this->maturity_info($maturity);
        $output .= $this->legacy_log_store_writing_error();
        $output .= $this->insecure_dataroot_warning($insecuredataroot);
        $output .= $this->development_libs_directories_warning($devlibdir);
        $output .= $this->themedesignermode_warning($themedesignermode);
        $output .= $this->display_errors_warning($errorsdisplayed);
        $output .= $this->buggy_iconv_warning($buggyiconvnomb);
        $output .= $this->cron_overdue_warning($cronoverdue);
        $output .= $this->db_problems($dbproblems);
        $output .= $this->maintenance_mode_warning($maintenancemode);
        $output .= $this->cache_warnings($cachewarnings);
        $output .= $this->events_handlers($eventshandlers);
        $output .= $totara_renderer->is_registered();

        // list count of active users
        $output .= $totara_renderer->active_users($activeusers, $activeusers3mth);
         $output .= '<div class="box copyright"><a href="http://moodle.org/">LZ</a> <a href="http://docs.moodle.org/dev/Releases" title="'.$lzversion->value.'">'.$t[0].' (Build: '.$lzversion->value.')</a></div>';
        /// Display Totara version information
        $output .= $totara_renderer->totara_print_copyright($totara_release);

        // skodak: Martin likes to threaten people, so just style his copyright notice the standard way.
        // Totara: Remove generalbox markup.
        $output .= html_writer::start_div('copyright-acknowledgements');
        $output .= get_string('totaracopyrightacknowledge', 'totara_core', get_string('totaralearn', 'totara_core'));

        //////////////////////////////////////////////////////////////////////////////////////////////////
        ////  IT IS ILLEGAL AND A VIOLATION OF THE GPL TO HIDE, REMOVE OR MODIFY THIS COPYRIGHT NOTICE ///
        $output .= $this->moodle_copyright();
        //////////////////////////////////////////////////////////////////////////////////////////////////

        // Totara: Additional acknowledgement.
        $output .= $totara_renderer->acknowledgements();

        // Totara: Remove generalbox markup.
        $output .= html_writer::end_div();

        $output .= $this->footer();

        return $output;
    }

    /**
     * Display the plugin management page (admin/plugins.php).
     *
     * The filtering options array may contain following items:
     *  bool contribonly - show only contributed extensions
     *
     * @param core_plugin_manager $pluginman
     * @param \core\update\checker $checker
     * @param array $options filtering options
     * @return string HTML to output.
     */
    public function plugin_management_page(core_plugin_manager $pluginman, \core\update\checker $checker, array $options = array()) {

        $output = '';

        $output .= $this->header();
        $output .= $this->heading(get_string('pluginsoverview', 'core_admin'));
        $output .= $this->plugins_overview_panel($pluginman, $options);
        $output .= $this->plugins_control_panel($pluginman, $options);
        $output .= $this->footer();

        return $output;
    }

    /**
     * Renders a button to fetch for available updates.
     *
     * @param \core\update\checker $checker
     * @param moodle_url $reloadurl
     * @return string HTML
     */
    public function check_for_updates_button(\core\update\checker $checker, $reloadurl) {
        return '';
    }

    /**
     * Display a page to confirm the plugin uninstallation.
     *
     * @param core_plugin_manager $pluginman
     * @param \core\plugininfo\base $pluginfo
     * @param moodle_url $continueurl URL to continue after confirmation
     * @param moodle_url $cancelurl URL to to go if cancelled
     * @return string
     */
    public function plugin_uninstall_confirm_page(core_plugin_manager $pluginman, \core\plugininfo\base $pluginfo, moodle_url $continueurl, moodle_url $cancelurl) {
        $output = '';

        $pluginname = $pluginman->plugin_name($pluginfo->component);

        $confirm = '<p>' . get_string('uninstallconfirm', 'core_plugin', array('name' => $pluginname)) . '</p>';
        if ($extraconfirm = $pluginfo->get_uninstall_extra_warning()) {
            $confirm .= $extraconfirm;
        }

        $output .= $this->output->header();
        $output .= $this->output->heading(get_string('uninstalling', 'core_plugin', array('name' => $pluginname)));
        $output .= $this->output->confirm($confirm, $continueurl, $cancelurl);
        $output .= $this->output->footer();

        return $output;
    }

    /**
     * Display a page with results of plugin uninstallation and offer removal of plugin files.
     *
     * @param core_plugin_manager $pluginman
     * @param \core\plugininfo\base $pluginfo
     * @param progress_trace_buffer $progress
     * @param moodle_url $continueurl URL to continue to remove the plugin folder
     * @return string
     */
    public function plugin_uninstall_results_removable_page(core_plugin_manager $pluginman, \core\plugininfo\base $pluginfo,
                                                            progress_trace_buffer $progress, moodle_url $continueurl) {
        $output = '';

        $pluginname = $pluginman->plugin_name($pluginfo->component);

        // Do not show navigation here, they must click one of the buttons.
        $this->page->set_pagelayout('maintenance');
        $this->page->set_cacheable(false);

        $output .= $this->output->header();
        $output .= $this->output->heading(get_string('uninstalling', 'core_plugin', array('name' => $pluginname)));

        $output .= $this->output->box($progress->get_buffer(), 'generalbox uninstallresultmessage');

        $confirm = $this->output->container(get_string('uninstalldeleteconfirm', 'core_plugin',
            array('name' => $pluginname, 'rootdir' => $pluginfo->rootdir)), 'uninstalldeleteconfirm');

        if ($repotype = $pluginman->plugin_external_source($pluginfo->component)) {
            $confirm .= $this->output->container(get_string('uninstalldeleteconfirmexternal', 'core_plugin', $repotype),
                'uninstalldeleteconfirmexternal');
        }

        // After any uninstall we must execute full upgrade to finish the cleanup!
        $output .= $this->output->confirm($confirm, $continueurl, new moodle_url('/admin/index.php'));
        $output .= $this->output->footer();

        return $output;
    }

    /**
     * Display a page with results of plugin uninstallation and inform about the need to remove plugin files manually.
     *
     * @param core_plugin_manager $pluginman
     * @param \core\plugininfo\base $pluginfo
     * @param progress_trace_buffer $progress
     * @return string
     */
    public function plugin_uninstall_results_page(core_plugin_manager $pluginman, \core\plugininfo\base $pluginfo, progress_trace_buffer $progress) {
        $output = '';

        $pluginname = $pluginfo->component;

        $output .= $this->output->header();
        $output .= $this->output->heading(get_string('uninstalling', 'core_plugin', array('name' => $pluginname)));

        $output .= $this->output->box($progress->get_buffer(), 'generalbox uninstallresultmessage');

        $output .= $this->output->box(get_string('uninstalldelete', 'core_plugin',
            array('name' => $pluginname, 'rootdir' => $pluginfo->rootdir)), 'generalbox uninstalldelete');
        $output .= $this->output->continue_button(new moodle_url('/admin/index.php'));
        $output .= $this->output->footer();

        return $output;
    }

    /**
     * Display the plugin management page (admin/environment.php).
     * @param array $versions
     * @param string $version
     * @param boolean $envstatus final result of env check (true/false)
     * @param array $environment_results array of results gathered
     * @return string HTML to output.
     */
    public function environment_check_page($versions, $version, $envstatus, $environment_results) {
        $output = '';
        $output .= $this->header();

        // Totara: no download link!

        // Heading.
        $output .= $this->heading(get_string('environment', 'admin'));

        // Box with info and a menu to choose the version.
        $output .= $this->box_start();
        $output .= html_writer::tag('div', get_string('adminhelpenvironment'));
        $select = new single_select(new moodle_url('/admin/environment.php'), 'version', $versions, $version, null);
        $select->label = get_string('totaraversion', 'totara_core');
        $output .= $this->render($select);
        $output .= $this->box_end();

        // The results
        $output .= $this->environment_check_table($envstatus, $environment_results);

        $output .= $this->footer();
        return $output;
    }

    /**
     * Output a warning message, of the type that appears on the admin notifications page.
     * @param string $message the message to display.
     * @param string $type type class
     * @return string HTML to output.
     */
    protected function warning($message, $type = 'warning') {
        $notifytype = 'notifyproblem';
        if ($type === 'warning') {
            $notifytype = 'notifywarning';
        } else if ($type === 'error') {
            $notifytype = 'notifyproblem';
        }
        return $this->notification($message, $notifytype);
    }

    /**
     * Render an appropriate message if dataroot is insecure.
     * @param bool $insecuredataroot
     * @return string HTML to output.
     */
    protected function insecure_dataroot_warning($insecuredataroot) {
        global $CFG;

        if ($insecuredataroot == INSECURE_DATAROOT_WARNING) {
            return $this->warning(get_string('datarootsecuritywarning', 'admin', $CFG->dataroot));

        } else if ($insecuredataroot == INSECURE_DATAROOT_ERROR) {
            return $this->warning(get_string('datarootsecurityerror', 'admin', $CFG->dataroot), 'error');

        } else {
            return '';
        }
    }

    /**
     * Render a warning that a directory with development libs is present.
     *
     * @param bool $devlibdir True if the warning should be displayed.
     * @return string
     */
    protected function development_libs_directories_warning($devlibdir) {

        if ($devlibdir) {
            $moreinfo = new moodle_url('/report/security/index.php');
            $warning = get_string('devlibdirpresent', 'core_admin', ['moreinfourl' => $moreinfo->out()]);
            return $this->warning($warning, 'error');

        } else {
            return '';
        }
    }

    /**
     * Render an appropriate message if dataroot is insecure.
     * @param bool $errorsdisplayed
     * @return string HTML to output.
     */
    protected function display_errors_warning($errorsdisplayed) {
        if (!$errorsdisplayed) {
            return '';
        }

        return $this->warning(get_string('displayerrorswarning', 'admin'));
    }

    /**
     * Render an appropriate message if themdesignermode is enabled.
     * @param bool $themedesignermode true if enabled
     * @return string HTML to output.
     */
    protected function themedesignermode_warning($themedesignermode) {
        if (!$themedesignermode) {
            return '';
        }

        return $this->warning(get_string('themedesignermodewarning', 'admin'));
    }

    /**
     * Render an appropriate message if iconv is buggy and mbstring missing.
     * @param bool $buggyiconvnomb
     * @return string HTML to output.
     */
    protected function buggy_iconv_warning($buggyiconvnomb) {
        if (!$buggyiconvnomb) {
            return '';
        }

        return $this->warning(get_string('warningiconvbuggy', 'admin'));
    }

    /**
     * Render an appropriate message if cron has not been run recently.
     * @param bool $cronoverdue
     * @return string HTML to output.
     */
    public function cron_overdue_warning($cronoverdue) {
        global $CFG;
        if (!$cronoverdue) {
            return '';
        }

        if (empty($CFG->cronclionly)) {
            $url = new moodle_url('/admin/cron.php');
            if (!empty($CFG->cronremotepassword)) {
                $url = new moodle_url('/admin/cron.php', array('password' => $CFG->cronremotepassword));
            }

            return $this->warning(get_string('cronwarning', 'admin', $url->out()) . '&nbsp;' .
                    $this->help_icon('cron', 'admin'));
        }

        // $CFG->cronclionly is not empty: cron can run only from CLI.
        return $this->warning(get_string('cronwarningcli', 'admin') . '&nbsp;' .
                $this->help_icon('cron', 'admin'));
    }

    /**
     * Render an appropriate message if there are any problems with the DB set-up.
     * @param bool $dbproblems
     * @return string HTML to output.
     */
    public function db_problems($dbproblems) {
        if (!$dbproblems) {
            return '';
        }

        return $this->warning($dbproblems);
    }

    /**
     * Renders cache warnings if there are any.
     *
     * @param string[] $cachewarnings
     * @return string
     */
    public function cache_warnings(array $cachewarnings) {
        if (!count($cachewarnings)) {
            return '';
        }
        return join("\n", array_map(array($this, 'warning'), $cachewarnings));
    }

    /**
     * Renders events 1 API handlers warning.
     *
     * @param array $eventshandlers
     * @return string
     */
    public function events_handlers($eventshandlers) {
        if ($eventshandlers) {
            $components = '';
            foreach ($eventshandlers as $eventhandler) {
                $components .= $eventhandler->component . ', ';
            }
            $components = rtrim($components, ', ');
            return $this->warning(get_string('eventshandlersinuse', 'admin', $components));
        }
    }

    /**
     * Render an appropriate message if the site in in maintenance mode.
     * @param bool $maintenancemode
     * @return string HTML to output.
     */
    public function maintenance_mode_warning($maintenancemode) {
        if (!$maintenancemode) {
            return '';
        }

        $url = new moodle_url('/admin/settings.php', array('section' => 'maintenancemode'));
        $url = $url->out(); // get_string() does not support objects in params

        return $this->warning(get_string('sitemaintenancewarning2', 'admin', $url));
    }

    /**
     * Display a warning about installing development code if necesary.
     * @param int $maturity
     * @return string HTML to output.
     */
    protected function maturity_warning($maturity) {
        if ($maturity >= MATURITY_EVERGREEN) {
            return ''; // No worries.
        }

        $maturitylevel = get_string('maturity' . $maturity, 'admin');
        return $this->warning(
                    $this->container(get_string('maturitycorewarning', 'admin', $maturitylevel)) .
                    $this->container($this->doc_link('admin/versions', get_string('morehelp'))),
                'error');
    }

    /*
     * If necessary, displays a warning about upgrading a test site.
     *
     * @param string $testsite
     * @return string HTML
     */
    protected function test_site_warning($testsite) {

        if (!$testsite) {
            return '';
        }

        $warning = (get_string('testsiteupgradewarning', 'admin', $testsite));
        return $this->warning($warning, 'error');
    }

    /**
     * Output the copyright notice.
     * @return string HTML to output.
     */
    protected function moodle_copyright() {
        global $CFG;

        //////////////////////////////////////////////////////////////////////////////////////////////////
        ////  IT IS ILLEGAL AND A VIOLATION OF THE GPL TO HIDE, REMOVE OR MODIFY THIS COPYRIGHT NOTICE ///
        $copyrighttext = '<a href="http://moodle.org/">Moodle</a> '.
                         '<a href="http://docs.moodle.org/dev/Releases" title="'.$CFG->version.'">'.$CFG->release.'</a><br />'.
                         'Copyright &copy; 1999 onwards, Martin Dougiamas<br />'.
                         'and <a href="http://moodle.org/dev">many other contributors</a>.<br />'.
                         '<a href="http://docs.moodle.org/dev/License">GNU Public License</a>';
        //////////////////////////////////////////////////////////////////////////////////////////////////

        return $this->box($copyrighttext, 'copyright');
    }

    /**
     * Display a warning about installing development code if necesary.
     * @param int $maturity
     * @return string HTML to output.
     */
    protected function maturity_info($maturity) {
        if ($maturity >= MATURITY_EVERGREEN) {
            return ''; // No worries.
        }

        $level = 'warning';

        if ($maturity <= MATURITY_ALPHA) {
            $level = 'error';
        }

        $maturitylevel = get_string('maturity' . $maturity, 'admin');
        $warningtext = get_string('maturitycoreinfo', 'admin', $maturitylevel);
        $warningtext .= ' ' . $this->doc_link('admin/versions', get_string('morehelp'));
        return $this->warning($warningtext, $level);
    }

    /**
     * Display a warning about not being registered on Moodle.org if necesary.
     *
     * @deprecated
     * @param boolean $registered true if the site is registered on Moodle.org
     * @return string HTML to output.
     */
    protected function registration_warning($registered) {

        if (!$registered) {

            if (has_capability('moodle/site:config', context_system::instance())) {
                $registerbutton = $this->single_button(new moodle_url('/admin/registration/register.php',
                    array('huburl' =>  HUB_MOODLEORGHUBURL, 'hubname' => 'Moodle.net')),
                    get_string('register', 'admin'));
                $str = 'registrationwarning';
            } else {
                $registerbutton = '';
                $str = 'registrationwarningcontactadmin';
            }

            return $this->warning( get_string($str, 'admin')
                    . '&nbsp;' . $this->help_icon('registration', 'admin') . $registerbutton ,
                'error alert alert-danger');
        }

        return '';
    }

    /**
     * Return an admin page warning if site is not registered with moodle.org
     *
     * @since Moodle 3.2.5
     * @return string
     */
    public function warn_if_not_registered() {
        // Totara: we have our own registration system.
        return '';
    }

    /**
     * Display a link to the release notes.
     * @return string HTML to output.
     */
    protected function release_notes_link() {
        $releasenoteslink = get_string('releasenoteslink', 'admin', 'https://totara.community/mod/forum/view.php?id=7038');
        return $this->notification($releasenoteslink, 'notifymessage');
    }

    /**
     * Display the reload link that appears on several upgrade/install pages.
     * @return string HTML to output.
     */
    function upgrade_reload($url) {
        return html_writer::empty_tag('br') .
                html_writer::tag('div',
                    html_writer::link($url, $this->pix_icon('i/reload', '', '', array('class' => 'icon icon-pre')) .
                            get_string('reload'), array('title' => get_string('reload'))),
                array('class' => 'continuebutton')) . html_writer::empty_tag('br');
    }

    /**
     * Displays all known plugins and information about their installation or upgrade
     *
     * This default implementation renders all plugins into one big table. The rendering
     * options support:
     *     (bool)full = false: whether to display up-to-date plugins, too
     *     (bool)xdep = false: display the plugins with unsatisified dependecies only
     *
     * @param core_plugin_manager $pluginman provides information about the plugins.
     * @param int $version the version of the Moodle code from version.php.
     * @param array $options rendering options
     * @return string HTML code
     */
    public function plugins_check_table(core_plugin_manager $pluginman, $version, array $options = array()) {

        $plugininfo = $pluginman->get_plugins();

        if (empty($plugininfo)) {
            return '';
        }

        $options['full'] = isset($options['full']) ? (bool)$options['full'] : false;
        $options['xdep'] = isset($options['xdep']) ? (bool)$options['xdep'] : false;

        $table = new html_table();
        $table->id = 'plugins-check';
        $table->head = array(
            get_string('displayname', 'core_plugin').' / '.get_string('rootdir', 'core_plugin'),
            get_string('versiondb', 'core_plugin'),
            get_string('versiondisk', 'core_plugin'),
            get_string('requires', 'core_plugin'),
            get_string('source', 'core_plugin').' / '.get_string('status', 'core_plugin'),
        );
        $table->colclasses = array(
            'displayname', 'versiondb', 'versiondisk', 'requires', 'status',
        );
        $table->data = array();

        // Number of displayed plugins per type.
        $numdisplayed = array();
        // Number of plugins known to the plugin manager.
        $sumtotal = 0;
        // Number of plugins requiring attention.
        $sumattention = 0;

        foreach ($plugininfo as $type => $plugins) {

            $header = new html_table_cell($pluginman->plugintype_name_plural($type));
            $header->header = true;
            $header->colspan = count($table->head);
            $header = new html_table_row(array($header));
            $header->attributes['class'] = 'plugintypeheader type-' . $type;

            $numdisplayed[$type] = 0;

            if (empty($plugins) and $options['full']) {
                $msg = new html_table_cell(get_string('noneinstalled', 'core_plugin'));
                $msg->colspan = count($table->head);
                $row = new html_table_row(array($msg));
                $row->attributes['class'] .= 'msg msg-noneinstalled';
                $table->data[] = $header;
                $table->data[] = $row;
                continue;
            }

            $plugintyperows = array();

            foreach ($plugins as $name => $plugin) {
                $sumtotal++;
                $row = new html_table_row();
                $row->attributes['class'] = 'type-' . $plugin->type . ' name-' . $plugin->type . '_' . $plugin->name;

                if ($this->page->theme->resolve_image_location('icon', $plugin->type . '_' . $plugin->name, null)) {
                    $icon = $this->output->pix_icon('icon', '', $plugin->type . '_' . $plugin->name, array('class' => 'smallicon pluginicon'));
                } else {
                    $icon = '';
                }

                $displayname = new html_table_cell(
                    $icon.
                    html_writer::span($plugin->displayname, 'pluginname').
                    html_writer::div($plugin->get_dir(), 'plugindir')
                );

                $versiondb = new html_table_cell($plugin->versiondb);
                $versiondisk = new html_table_cell($plugin->versiondisk);

                if ($isstandard = $plugin->is_standard()) {
                    $row->attributes['class'] .= ' standard';
                    $sourcelabel = html_writer::span(get_string('sourcestd', 'core_plugin'), 'sourcetext label label-default');
                } else {
                    $row->attributes['class'] .= ' extension';
                    $sourcelabel = html_writer::span(get_string('sourceext', 'core_plugin'), 'sourcetext label label-default');
                }

                $coredependency = $plugin->is_core_dependency_satisfied($version);
                $otherpluginsdependencies = $pluginman->are_dependencies_satisfied($plugin->get_other_required_plugins());
                $dependenciesok = $coredependency && $otherpluginsdependencies;

                $statuscode = $plugin->get_status();
                $row->attributes['class'] .= ' status-' . $statuscode;
                // Totara: add Bootstrap 3 classes
                if ($statuscode === 'missing') {
                    $row->attributes['class'] .= ' danger';
                } else if ($statuscode === 'downgrade') {
                    $row->attributes['class'] .= ' danger';
                } else if ($statuscode === 'upgrade') {
                    $row->attributes['class'] .= ' info';
                } else if ($statuscode === 'delete') {
                    $row->attributes['class'] .= ' info';
                } else if ($statuscode === 'new') {
                    $row->attributes['class'] .= ' success';
                }
                $statusclass = 'statustext label label-default ';
                switch ($statuscode) {
                    case core_plugin_manager::PLUGIN_STATUS_NEW:
                        $statusclass .= $dependenciesok ? 'label-success' : 'label-warning';
                        break;
                    case core_plugin_manager::PLUGIN_STATUS_UPGRADE:
                        $statusclass .= $dependenciesok ? 'label-info' : 'label-warning';
                        break;
                    case core_plugin_manager::PLUGIN_STATUS_MISSING:
                    case core_plugin_manager::PLUGIN_STATUS_DOWNGRADE:
                    case core_plugin_manager::PLUGIN_STATUS_DELETE:
                        $statusclass .= 'label-danger';
                        break;
                    case core_plugin_manager::PLUGIN_STATUS_NODB:
                    case core_plugin_manager::PLUGIN_STATUS_UPTODATE:
                        $statusclass .= $dependenciesok ? '' : 'label-warning';
                        break;
                }
                $status = html_writer::span(get_string('status_' . $statuscode, 'core_plugin'), $statusclass);

                $status = new html_table_cell($sourcelabel.' '.$status);

                $requires = new html_table_cell($this->required_column($plugin, $pluginman, $version));

                $statusisboring = in_array($statuscode, array(
                        core_plugin_manager::PLUGIN_STATUS_NODB, core_plugin_manager::PLUGIN_STATUS_UPTODATE));

                if ($options['xdep']) {
                    // we want to see only plugins with failed dependencies
                    if ($dependenciesok) {
                        continue;
                    }

                } else if ($statusisboring and $dependenciesok) {
                    // no change is going to happen to the plugin - display it only
                    // if the user wants to see the full list
                    if (empty($options['full'])) {
                        continue;
                    }

                } else {
                    $sumattention++;
                }

                // The plugin should be displayed.
                $numdisplayed[$type]++;
                $row->cells = array($displayname, $versiondb, $versiondisk, $requires, $status);
                $plugintyperows[] = $row;
            }

            if (empty($numdisplayed[$type]) and empty($options['full'])) {
                continue;
            }

            $table->data[] = $header;
            $table->data = array_merge($table->data, $plugintyperows);
        }

        // Total number of displayed plugins.
        $sumdisplayed = array_sum($numdisplayed);

        if ($options['xdep']) {
            // At the plugins dependencies check page, display the table only.
            return html_writer::table($table);
        }

        $out = $this->output->container_start('', 'plugins-check-info');

        if ($sumdisplayed == 0) {
            $out .= $this->output->heading(get_string('pluginchecknone', 'core_plugin'));

        } else {
            if (empty($options['full'])) {
                $out .= $this->output->heading(get_string('plugincheckattention', 'core_plugin'));
            } else {
                $out .= $this->output->heading(get_string('plugincheckall', 'core_plugin'));
            }
        }

        $out .= $this->output->container_start('actions');

        $out .= html_writer::div(html_writer::link(new moodle_url($this->page->url, array('showallplugins' => 0)),
            get_string('plugincheckattention', 'core_plugin')).' '.html_writer::span($sumattention, 'badge'));

        $out .= html_writer::div(html_writer::link(new moodle_url($this->page->url, array('showallplugins' => 1)),
            get_string('plugincheckall', 'core_plugin')).' '.html_writer::span($sumtotal, 'badge'));

        $out .= $this->output->container_end(); // End of .actions container.
        $out .= $this->output->container_end(); // End of #plugins-check-info container.

        if ($sumdisplayed > 0 or $options['full']) {
            $out .= html_writer::table($table);
        }

        return $out;
    }

    /**
     * Display the continue / cancel widgets for the plugins management pages.
     *
     * @param null|moodle_url $continue URL for the continue button, should it be displayed
     * @param null|moodle_url $cancel URL for the cancel link, defaults to the current page
     * @return string HTML
     */
    public function plugins_management_confirm_buttons(moodle_url $continue=null, moodle_url $cancel=null) {

        $out = html_writer::start_div('plugins-management-confirm-buttons');

        if (!empty($continue)) {
            $out .= $this->output->single_button($continue, get_string('continue'), 'post', array('class' => 'continue'));
        }

        if (empty($cancel)) {
            $cancel = $this->page->url;
        }
        $out .= html_writer::div(html_writer::link($cancel, get_string('cancel')), 'cancel');

        return $out;
    }

    /**
     * Formats the information that needs to go in the 'Requires' column.
     * @param \core\plugininfo\base $plugin the plugin we are rendering the row for.
     * @param core_plugin_manager $pluginman provides data on all the plugins.
     * @param string $version
     * @return string HTML code
     */
    protected function required_column(\core\plugininfo\base $plugin, core_plugin_manager $pluginman, $version) {

        $requires = array();

        foreach ($pluginman->resolve_requirements($plugin, $version) as $reqname => $reqinfo) {
            $label = '';
            $class = '';
            if ($reqname === 'core') {
                if ($reqinfo->status == $pluginman::REQUIREMENT_STATUS_OK) {
                    $class = 'requires-ok';
                    $label = '';
                } else {
                    $class = 'requires-failed';
                    $label = html_writer::span(get_string('dependencyfails', 'core_plugin'), 'label label-danger');
                }
                $requires[] = html_writer::tag('li',
                    html_writer::span(get_string('moodleversion', 'core_plugin', $plugin->versionrequires), 'dep dep-core').
                    ' '.$label, array('class' => $class));

            } else {
                if ($reqinfo->status == $pluginman::REQUIREMENT_STATUS_OK) {
                    $label = '';
                    $class = 'requires-ok';

                } else if ($reqinfo->status == $pluginman::REQUIREMENT_STATUS_MISSING) {
                    $label = html_writer::span(get_string('dependencymissing', 'core_plugin'), 'label label-danger');
                    $class = 'requires-failed requires-missing';

                } else if ($reqinfo->status == $pluginman::REQUIREMENT_STATUS_OUTDATED) {
                    $label = html_writer::span(get_string('dependencyfails', 'core_plugin'), 'label label-danger');
                    $class = 'requires-failed requires-outdated';
                }

                if ($reqinfo->reqver != ANY_VERSION) {
                    $str = 'otherpluginversion';
                } else {
                    $str = 'otherplugin';
                }

                $requires[] = html_writer::tag('li', html_writer::span(
                    get_string($str, 'core_plugin', array('component' => $reqname, 'version' => $reqinfo->reqver)),
                    'dep dep-plugin').' '.$label,
                    array('class' => $class)
                );
            }
        }

        if (!$requires) {
            return '';
        }

        $out = html_writer::tag('ul', implode("\n", $requires));

        return $out;

    }

    /**
     * Prints an overview about the plugins - number of installed, number of extensions etc.
     *
     * @param core_plugin_manager $pluginman provides information about the plugins
     * @param array $options filtering options
     * @return string as usually
     */
    public function plugins_overview_panel(core_plugin_manager $pluginman, array $options = array()) {
        $out = '';
        if (!empty($options['contribonly'])) {
            $out .= $this->output->notification(markdown_to_html(get_string('totaraaddonnotice', 'core_plugin')), 'notifymessage');
        }

        $plugininfo = $pluginman->get_plugins();

        $numtotal = $numextension = 0;

        foreach ($plugininfo as $type => $plugins) {
            foreach ($plugins as $name => $plugin) {
                if ($plugin->get_status() === core_plugin_manager::PLUGIN_STATUS_MISSING) {
                    continue;
                }
                $numtotal++;
                if (!$plugin->is_standard()) {
                    $numextension++;
                }
            }
        }

        $infoall = get_string('overviewall', 'core_plugin');
        if (!empty($options['contribonly'])) {
            $infoall = html_writer::link(
                new moodle_url($this->page->url, array('contribonly' => 0)),
                $infoall,
                array('title' => get_string('filterall', 'core_plugin'))
            );
        }
        $infoall .= ' '.html_writer::span($numtotal, 'badge number number-all');

        $infoext = get_string('overviewext', 'core_plugin');
        if (empty($options['contribonly'])) {
            $infoext = html_writer::link(
                new moodle_url($this->page->url, array('contribonly' => 1)),
                $infoext,
                array('title' => get_string('filtercontribonly', 'core_plugin'))
            );
        }
        $infoext .= ' '.html_writer::span($numextension, 'badge number number-additional');

        $out .= html_writer::start_div('', array('id' => 'plugins-overview-panel'));

        if (!empty($options['contribonly'])) {
            $out .= $this->output->heading(get_string('overviewext', 'core_plugin'), 3);
        }

        $out .= html_writer::div($infoall, 'info info-all').
            html_writer::div($infoext, 'info info-ext');

        $out .= html_writer::end_div(); // End of #plugins-overview-panel block.

        return $out;
    }

    /**
     * Displays all known plugins and links to manage them
     *
     * This default implementation renders all plugins into one big table.
     *
     * @param core_plugin_manager $pluginman provides information about the plugins.
     * @param array $options filtering options
     * @return string HTML code
     */
    public function plugins_control_panel(core_plugin_manager $pluginman, array $options = array()) {

        $plugininfo = $pluginman->get_plugins();

        if (!empty($options['contribonly'])) {
            $contribs = array();
            foreach ($plugininfo as $plugintype => $pluginnames) {
                foreach ($pluginnames as $pluginname => $pluginfo) {
                    if (!$pluginfo->is_standard()) {
                        $contribs[$plugintype][$pluginname] = $pluginfo;
                    }
                }
            }
            $plugininfo = $contribs;
        }

        if (empty($plugininfo)) {
            return '';
        }

        $table = new html_table();
        $table->id = 'plugins-control-panel';
        $table->head = array(
            get_string('displayname', 'core_plugin'),
            get_string('version', 'core_plugin'),
            get_string('availability', 'core_plugin'),
            get_string('actions', 'core_plugin'),
            get_string('notes','core_plugin'),
        );
        $table->headspan = array(1, 1, 1, 2, 1);
        $table->colclasses = array(
            'pluginname', 'version', 'availability', 'settings', 'uninstall', 'notes'
        );

        foreach ($plugininfo as $type => $plugins) {
            $heading = $pluginman->plugintype_name_plural($type);
            $pluginclass = core_plugin_manager::resolve_plugininfo_class($type);
            if ($manageurl = $pluginclass::get_manage_url()) {
                $heading .= $this->output->action_icon($manageurl, new pix_icon('i/settings',
                    get_string('settings', 'core_plugin')));
            }
            $header = new html_table_cell(html_writer::tag('span', $heading, array('id'=>'plugin_type_cell_'.$type)));
            $header->header = true;
            $header->colspan = array_sum($table->headspan);
            $header = new html_table_row(array($header));
            $header->attributes['class'] = 'plugintypeheader type-' . $type;
            $table->data[] = $header;

            if (empty($plugins)) {
                $msg = new html_table_cell(get_string('noneinstalled', 'core_plugin'));
                $msg->colspan = array_sum($table->headspan);
                $row = new html_table_row(array($msg));
                $row->attributes['class'] .= 'msg msg-noneinstalled';
                $table->data[] = $row;
                continue;
            }

            foreach ($plugins as $name => $plugin) {
                $row = new html_table_row();
                $row->attributes['class'] = 'type-' . $plugin->type . ' name-' . $plugin->type . '_' . $plugin->name;

                if ($this->page->theme->resolve_image_location('icon', $plugin->type . '_' . $plugin->name, null)) {
                    $icon = $this->output->pix_icon('icon', '', $plugin->type . '_' . $plugin->name, array('class' => 'icon pluginicon'));
                } else {
                    $icon = $this->output->pix_icon('spacer', '', 'moodle', array('class' => 'icon pluginicon noicon'));
                }
                $status = $plugin->get_status();
                $row->attributes['class'] .= ' status-'.$status;
                $pluginname  = html_writer::tag('div', $icon.$plugin->displayname, array('class' => 'displayname')).
                               html_writer::tag('div', $plugin->component, array('class' => 'componentname'));
                $pluginname  = new html_table_cell($pluginname);

                $version = html_writer::div($plugin->versiondb, 'versionnumber');
                if ((string)$plugin->release !== '') {
                    $version = html_writer::div($plugin->release, 'release').$version;
                }
                $version = new html_table_cell($version);

                $isenabled = $plugin->is_enabled();
                if (is_null($isenabled)) {
                    $availability = new html_table_cell('');
                } else if ($isenabled) {
                    $row->attributes['class'] .= ' enabled';
                    $availability = new html_table_cell(get_string('pluginenabled', 'core_plugin'));
                } else {
                    $row->attributes['class'] .= ' disabled';
                    $availability = new html_table_cell(get_string('plugindisabled', 'core_plugin'));
                }

                $settingsurl = $plugin->get_settings_url();
                if (!is_null($settingsurl)) {
                    $settings = html_writer::link($settingsurl, get_string('settings', 'core_plugin'), array('class' => 'settings'));
                } else {
                    $settings = '';
                }
                $settings = new html_table_cell($settings);

                if ($uninstallurl = $pluginman->get_uninstall_url($plugin->component, 'overview')) {
                    $uninstall = html_writer::link($uninstallurl, get_string('uninstall', 'core_plugin'));
                } else {
                    $uninstall = '';
                }
                $uninstall = new html_table_cell($uninstall);

                if ($plugin->is_standard()) {
                    $row->attributes['class'] .= ' standard';
                    $source = '';
                } else {
                    $row->attributes['class'] .= ' extension';
                    $source = html_writer::div(get_string('sourceext', 'core_plugin'), 'source label label-info');
                }

                if ($status === core_plugin_manager::PLUGIN_STATUS_MISSING) {
                    $msg = html_writer::div(get_string('status_missing', 'core_plugin'), 'statusmsg label label-danger');
                } else if ($status === core_plugin_manager::PLUGIN_STATUS_NEW) {
                    $msg = html_writer::div(get_string('status_new', 'core_plugin'), 'statusmsg label label-success');
                } else {
                    $msg = '';
                }

                $requriedby = $pluginman->other_plugins_that_require($plugin->component);
                if ($requriedby) {
                    $requiredby = html_writer::tag('div', get_string('requiredby', 'core_plugin', implode(', ', $requriedby)),
                        array('class' => 'requiredby'));
                } else {
                    $requiredby = '';
                }

                $notes = new html_table_cell($source.$msg.$requiredby);

                $row->cells = array(
                    $pluginname, $version, $availability, $settings, $uninstall, $notes
                );
                $table->data[] = $row;
            }
        }

        return html_writer::table($table);
    }

    /**
     * Render active flavour notice.
     *
     * @return string html fragment, null if none
     */
    public function active_flavour_notice() {
        $flavournotice = \totara_flavour\helper::get_active_flavour_notice($this);
        if (!$flavournotice) {
            return null;
        }

        return $this->box($flavournotice, 'flavournotice');
    }

    /**
     * This function will render one beautiful table with all the environmental
     * configuration and how it suits Moodle needs.
     *
     * @param boolean $result final result of the check (true/false)
     * @param environment_results[] $environment_results array of results gathered
     * @return string HTML to output.
     */
    public function environment_check_table($result, $environment_results) {
        global $CFG;

        // Table headers
        $servertable = new html_table();//table for server checks
        $servertable->head  = array(
            get_string('name'),
            get_string('info'),
            get_string('report'),
            get_string('plugin'),
            get_string('status'),
        );
        $servertable->colclasses = array('centeralign name', 'centeralign info', 'leftalign report', 'leftalign plugin', 'centeralign status');
        $servertable->attributes['class'] = 'admintable environmenttable generaltable';
        $servertable->id = 'serverstatus';

        $serverdata = array('ok'=>array(), 'warn'=>array(), 'error'=>array());

        $othertable = new html_table();//table for custom checks
        $othertable->head  = array(
            get_string('info'),
            get_string('report'),
            get_string('plugin'),
            get_string('status'),
        );
        $othertable->colclasses = array('aligncenter info', 'alignleft report', 'alignleft plugin', 'aligncenter status');
        $othertable->attributes['class'] = 'admintable environmenttable generaltable';
        $othertable->id = 'otherserverstatus';

        $otherdata = array('ok'=>array(), 'warn'=>array(), 'error'=>array());

        // Iterate over each environment_result
        $continue = true;
        foreach ($environment_results as $environment_result) {
            $errorline   = false;
            $warningline = false;
            $stringtouse = '';
            if ($continue) {
                $type = $environment_result->getPart();
                $info = $environment_result->getInfo();
                $status = $environment_result->getStatus();
                $plugin = $environment_result->getPluginName();
                $error_code = $environment_result->getErrorCode();
                // Process Report field
                $rec = new stdClass();
                // Something has gone wrong at parsing time
                if ($error_code) {
                    $stringtouse = 'environmentxmlerror';
                    $rec->error_code = $error_code;
                    $status = get_string('error');
                    $errorline = true;
                    $continue = false;
                }

                if ($continue) {
                    if ($rec->needed = $environment_result->getNeededVersion()) {
                        // We are comparing versions
                        $rec->current = $environment_result->getCurrentVersion();
                        if ($environment_result->getLevel() == 'required') {
                            $stringtouse = 'environmentrequireversion';
                        } else {
                            $stringtouse = 'environmentrecommendversion';
                        }

                    } else if ($environment_result->getPart() == 'custom_check') {
                        // We are checking installed & enabled things
                        if ($environment_result->getLevel() == 'required') {
                            $stringtouse = 'environmentrequirecustomcheck';
                        } else {
                            $stringtouse = 'environmentrecommendcustomcheck';
                        }

                    } else if ($environment_result->getPart() == 'php_setting') {
                        if ($status) {
                            $stringtouse = 'environmentsettingok';
                        } else if ($environment_result->getLevel() == 'required') {
                            $stringtouse = 'environmentmustfixsetting';
                        } else {
                            $stringtouse = 'environmentshouldfixsetting';
                        }

                    } else {
                        if ($environment_result->getLevel() == 'required') {
                            $stringtouse = 'environmentrequireinstall';
                        } else {
                            $stringtouse = 'environmentrecommendinstall';
                        }
                    }

                    // Calculate the status value
                    if ($environment_result->getBypassStr() != '') {            //Handle bypassed result (warning)
                        $status = get_string('bypassed');
                        $warningline = true;
                    } else if ($environment_result->getRestrictStr() != '') {   //Handle restricted result (error)
                        $status = get_string('restricted');
                        $errorline = true;
                    } else {
                        if ($status) {                                          //Handle ok result (ok)
                            $status = get_string('ok');
                        } else {
                            if ($environment_result->getLevel() == 'optional') {//Handle check result (warning)
                                $status = get_string('check');
                                $warningline = true;
                            } else {                                            //Handle error result (error)
                                $status = get_string('check');
                                $errorline = true;
                            }
                        }
                    }
                }

                // Build the text
                $linkparts = array();
                $linkparts[] = 'admin/environment';
                $linkparts[] = $type;
                if (!empty($info)){
                   $linkparts[] = $info;
                }
                // Plugin environments do not have docs pages yet.
                if (empty($CFG->docroot) or $environment_result->plugin) {
                    $report = get_string($stringtouse, 'admin', $rec);
                } else {
                    $report = $this->doc_link(join($linkparts, '/'), get_string($stringtouse, 'admin', $rec));
                }
                // Enclose report text in div so feedback text will be displayed underneath it.
                $report = html_writer::div($report);

                // Format error or warning line
                if ($errorline || $warningline) {
                    $messagetype = $errorline? 'error':'warn';
                    $messageclass = $errorline? 'danger':'warning';
                } else {
                    $messagetype = 'ok';
                    $messageclass = 'success';
                }
                $status = '<span class="label label-'.$messageclass.'">'.$status.'</span>';
                // Here we'll store all the feedback found
                $feedbacktext = '';
                // Append the feedback if there is some
                $feedbacktext .= $environment_result->strToReport($environment_result->getFeedbackStr(), 'text-' . $messageclass);
                //Append the bypass if there is some
                $feedbacktext .= $environment_result->strToReport($environment_result->getBypassStr(), 'text-warning');
                //Append the restrict if there is some
                $feedbacktext .= $environment_result->strToReport($environment_result->getRestrictStr(), 'text-danger');

                $report .= $feedbacktext;

                // Add the row to the table
                if ($environment_result->getPart() == 'custom_check'){
                    $otherdata[$messagetype][] = array ($info, $report, $plugin, $status);
                } else {
                    if ($type === 'moodle' and !empty($CFG->totara_release)) {
                        $type = 'totara';
                        $report = str_replace($CFG->release, $CFG->totara_release, $report);
                    }
                    $serverdata[$messagetype][] = array ($type, $info, $report, $plugin, $status);
                }
            }
        }

        //put errors first in
        $servertable->data = array_merge($serverdata['error'], $serverdata['warn'], $serverdata['ok']);
        $othertable->data = array_merge($otherdata['error'], $otherdata['warn'], $otherdata['ok']);

        // Print table
        $output = '';
        $output .= $this->heading(get_string('serverchecks', 'admin'));
        $output .= html_writer::table($servertable);
        if (count($othertable->data)){
            $output .= $this->heading(get_string('customcheck', 'admin'));
            $output .= html_writer::table($othertable);
        }

        // Finally, if any error has happened, print the summary box
        if (!$result) {
            $output .= $this->box(get_string('environmenterrortodo', 'admin'), 'environmentbox errorbox');
        }

        return $output;
    }

    /**
     * Render a simple page for providing the upgrade key.
     *
     * @param moodle_url|string $url
     * @return string
     */
    public function upgradekey_form_page($url) {

        $output = '';
        $output .= $this->header();
        $output .= $this->container_start('upgradekeyreq');
        $output .= $this->heading(get_string('upgradekeyreq', 'core_admin'));
        $output .= html_writer::tag('p', get_string('upgradekeydesc', 'core_admin'));
        $output .= html_writer::start_tag('form', array('method' => 'POST', 'action' => $url));
        $output .= html_writer::tag('label', get_string('upgradekey', 'core_admin'), array('for' => 'upgradekey', 'class' => 'sr-only accesshide'));
        $output .= html_writer::empty_tag('input', array('name' => 'upgradekey', 'type' => 'password'));
        $output .= html_writer::empty_tag('input', array('value' => get_string('submit'), 'type' => 'submit'));
        $output .= html_writer::end_tag('form');
        $output .= $this->container_end();
        $output .= $this->footer();

        return $output;
    }

    /**
     * Check to see if writing to the deprecated legacy log store is enabled.
     *
     * @return string An error message if writing to the legacy log store is enabled.
     */
    protected function legacy_log_store_writing_error() {
        $enabled = get_config('logstore_legacy', 'loglegacy');
        $plugins = explode(',', get_config('tool_log', 'enabled_stores'));
        $enabled = $enabled && in_array('logstore_legacy', $plugins);

        if ($enabled) {
            return $this->warning(get_string('legacylogginginuse'));
        }
    }
}
