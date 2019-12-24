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
 * Listing of all sessions for current user.
 *
 * @package   report_usersessions
 * @copyright 2014 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

require_login(null, false);

if (isguestuser()) {
    // No guests here!
    redirect(new moodle_url('/'));
    die;
}
if (\core\session\manager::is_loggedinas()) {
    // No login-as users.
    redirect(new moodle_url('/user/index.php'));
    die;
}

$context = context_user::instance($USER->id);
require_capability('report/usersessions:manageownsessions', $context);

$delete = optional_param('delete', 0, PARAM_INT);
$deletepersistent = optional_param('deletepersistent', 0, PARAM_INT);

$PAGE->set_url('/report/usersessions/user.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('navigationlink', 'report_usersessions'));
$PAGE->set_heading(fullname($USER));
$PAGE->set_pagelayout('admin');

if ($delete and confirm_sesskey()) {
    report_usersessions_kill_session($delete);
    redirect($PAGE->url);
}

if ($deletepersistent and confirm_sesskey() and !empty($CFG->persistentloginenable)) {
    $persistentlogin = $DB->get_record('persistent_login', array('id' => $deletepersistent, 'userid' => $USER->id));
    if ($persistentlogin) {
        $DB->delete_records('persistent_login', array('id' => $persistentlogin->id));
    }
    redirect($PAGE->url);
}

// Create the breadcrumb.
$PAGE->add_report_nodes($USER->id, array(
        'name' => get_string('navigationlink', 'report_usersessions'),
        'url' => new moodle_url('/report/usersessions/user.php')
    ));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mysessions', 'report_usersessions'));

$data = array();
$sql = "SELECT s.id, s.timecreated, s.timemodified AS lastaccess, s.lastip, s.sid, pl.timecreated AS pltime
          FROM {sessions} s
     LEFT JOIN {persistent_login} pl ON pl.sid = s.sid 
         WHERE s.userid = :userid
      ORDER BY pl.timecreated DESC, s.timemodified DESC";
$params = array('userid' => $USER->id);
$sid = session_id();

$sessions = $DB->get_records_sql($sql, $params);
foreach ($sessions as $session) {
    if ($session->sid === $sid) {
        $lastaccess = get_string('thissession', 'report_usersessions');
        $deletelink = '';

    } else {
        $lastaccess = report_usersessions_format_duration(time() - $session->lastaccess);
        $url = new moodle_url($PAGE->url, array('delete' => $session->id, 'sesskey' => sesskey()));
        $deletelink = html_writer::link($url, get_string('logout'));
    }
    if (empty($CFG->persistentloginenable)) {
        $row = array(userdate($session->timecreated), $lastaccess, report_usersessions_format_ip($session->lastip), $deletelink);
    } else {
        if ($session->pltime) {
            $logintime = userdate($session->pltime);
            $persistent = get_string('yes');
        } else {
            $logintime = userdate($session->timecreated);
            $persistent = get_string('no');
        }
        $row = array($logintime, $lastaccess, report_usersessions_format_ip($session->lastip), $persistent, $deletelink);
    }
    $data[] = $row;
}

if (!empty($CFG->persistentloginenable)) {
    $sql = "SELECT pl.id, pl.timecreated, pl.lastaccess, pl.lastip
              FROM {persistent_login} pl 
         LEFT JOIN {sessions} s ON s.sid = pl.sid
             WHERE pl.userid = :userid AND s.id IS NULL AND pl.timecreated > :cutoff
          ORDER BY pl.timecreated DESC";
    $params = array('userid' => $USER->id, 'cutoff' => time() - \totara_core\persistent_login::get_cookie_lifetime());

    $persistents = $DB->get_records_sql($sql, $params);
    foreach ($persistents as $persistent) {
        if ($persistent->lastaccess) {
            $lastaccess = report_usersessions_format_duration(time() - $persistent->lastaccess);
        } else {
            $lastaccess = '';
        }
        if ($session->lastip) {
            $lastip = report_usersessions_format_ip($session->lastip);
        } else {
            $lastip = '';
        }
        $url = new moodle_url($PAGE->url, array('deletepersistent' => $persistent->id, 'sesskey' => sesskey()));
        $deletelink = html_writer::link($url, get_string('logout'));
        $row = array(userdate($persistent->timecreated), $lastaccess, $lastip, get_string('yes'), $deletelink);
        $data[] = $row;
    }
}

$table = new html_table();
if (empty($CFG->persistentloginenable)) {
    $table->head = array(get_string('login'), get_string('lastaccess'), get_string('lastip'), get_string('action'));
    $table->align = array('left', 'left', 'left', 'right');
} else {
    $table->head = array(get_string('login'), get_string('lastaccess'), get_string('lastip'), get_string('persistentloginenable', 'totara_core'), get_string('action'));
    $table->align = array('left', 'left', 'left', 'left', 'right');
}
$table->data  = $data;
echo html_writer::table($table);

echo $OUTPUT->footer();

