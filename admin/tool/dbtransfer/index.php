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
 * Transfer tool
 *
 * @package    tool_dbtransfer
 * @copyright  2008 Petr Skoda {@link http://skodak.org/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require('../../../config.php');
require_once('locallib.php');
require_once('database_transfer_form.php');

require_login();
admin_externalpage_setup('tooldbtransfer');

// Create the form.
$form = new database_transfer_form();
$problem = '';

// If we have valid input.
if ($data = $form->get_data()) {
    // Connect to the other database.
    list($dbtype, $dblibrary) = explode('/', $data->driver);
    $targetdb = moodle_database::get_driver_instance($dbtype, $dblibrary);
    $dboptions = array();
    if ($data->dbport) {
        $dboptions['dbport'] = $data->dbport;
    }
    if ($data->dbsocket) {
        $dboptions['dbsocket'] = $data->dbsocket;
    }
    try {
        $targetdb->connect($data->dbhost, $data->dbuser, $data->dbpass, $data->dbname, $data->prefix, $dboptions);
        if ($targetdb->get_tables()) {
            $problem .= get_string('targetdatabasenotempty', 'tool_dbtransfer');
        }
    } catch (moodle_exception $e) {
        $problem .= get_string('notargetconectexception', 'tool_dbtransfer').'<br />'.$e->debuginfo;
    }

    if ($problem === '') {
        // Scroll down to the bottom when finished.
        $PAGE->requires->js_init_code("window.scrollTo(0, 5000000);");

        // Enable CLI maintenance mode if requested.
        if ($data->enablemaintenance) {
            $PAGE->set_pagelayout('maintenance');
            tool_dbtransfer_create_maintenance_file();
        }

        // Start output.
        echo $OUTPUT->header();
        $data->dbtype = $dbtype;
        $data->dbtypefrom = $CFG->dbtype;
        echo $OUTPUT->heading(get_string('transferringdbto', 'tool_dbtransfer', $data));

        // Do the transfer.
        $CFG->tool_dbransfer_migration_running = true;
        try {
            $feedback = new html_list_progress_trace();
            tool_dbtransfer_transfer_database($DB, $targetdb, $feedback);
            $feedback->finished();
        } catch (Exception $e) {
            if ($data->enablemaintenance) {
                tool_dbtransfer_maintenance_callback();
            }
            unset($CFG->tool_dbransfer_migration_running);
            throw $e;
        }
        unset($CFG->tool_dbransfer_migration_running);

        // Finish up.
        echo $OUTPUT->notification(get_string('success'), 'notifysuccess');
        echo $OUTPUT->continue_button("$CFG->wwwroot/$CFG->admin/");
        echo $OUTPUT->footer();
        die;
    }
}

// Otherwise display the settings form.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('transferdbtoserver', 'tool_dbtransfer'));

$info = format_text(get_string('transferdbintro', 'tool_dbtransfer'), FORMAT_MARKDOWN);
echo $OUTPUT->box($info);

// This disclaimer is hard coded, its technical in nature, should only be seen by the admin on super rare occasions, failures are hard coded anyway.
// Really you should not run this tool presently. In 10.0 we'll look to do something about this.

$disclaimer = <<<DISCLAIMER
<p>Please be aware that the use of this tool is not supported. It is an experimental tool and should be used with extreme caution.</p>
<p>If you are using this tool please be aware that it will not run database specific installation steps which may be required by code.<br />
You will need to review the install.php files within each component and plugin, and manually run any installation steps required for the database you are migrating to.<br />
Known installation steps which must be manually run include, but may not be limited to:</p>
<ul>
<li><em>totara/reportbuilder/db/install.php</em> when migrating to PostgreSQL, installation of the GROUP_CONCAT function.</li>
<li><em>totara/mssql/install.php</em> when migrating to MSSQL, installation of the GROUP_CONCAT function.</li>
<li><em>totara/mssql/install.php</em> when migrating to MSSQL, permission checks.</li>
</ul>
<p>Please be aware that database structures created dynamically to facilitate functionality will not be copied over, this includes:</p>
<ul>
<li>Appraisals, tables created by the creation of appraisals will not be copied over.</li>
<li>Feedback 360, tables created by the creation of feedback instances will not be copied over.</li>
<li>Report builder caches, tables created to cache report data will not be copied over.</li>
</ul>
<p>Finally the case sensitivity on the new database must be identical on that of the original, otherwise the migration of data may not be possible.</p>
DISCLAIMER;

echo $OUTPUT->notification($disclaimer, 'notifymessage');

$form->display();
if ($problem !== '') {
    echo $OUTPUT->box($problem, 'generalbox error');
}
echo $OUTPUT->footer();
