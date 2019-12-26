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
 * Settings for the backups report
 *
 * @package    report
 * @subpackage backups
 * @copyright  2007 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// You only see this if you have the cap because the parent only exists if you have the cap.
if ($hassiteconfig or has_capability('moodle/backup:backupcourse', $systemcontext)) {
    $ADMIN->add('backups', new admin_externalpage('reportbackups', get_string('backupsexecutionlog', 'report_backups'), "$CFG->wwwroot/report/backups/index.php", 'moodle/backup:backupcourse'));
}

// no report settings
$settings = null;