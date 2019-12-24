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
 * program completion report
 *
 * @package    local_packages
 * @copyright  2014 onwards Dennis Dobrenko <dennis.dobrenko@kineo.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Loading the library.
require_once('../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('noblocks');
$PAGE->set_url('/local/lz_report/activit_search/search.php');

echo $OUTPUT->header();

$formaction = new moodle_url('/local/lz_report/activit_search/filter.php');
$mform = new MoodleQuickForm('searchactivity', 'post', $formaction, '', array('class' => 'searchactivity'));
$mform->addElement('text', 'activityname', get_string('activity'));


echo $mform->toHtml();

echo $OUTPUT->footer();
