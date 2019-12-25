<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * facetoface module PHPUnit archive test class
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit mod_facetoface_archive_testcase mod/facetoface/tests/archive_test.php
 *
 * @package    mod_facetoface
 * @subpackage phpunit
 * @author     Maria Torres <maria.torres@totaralms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/lib.php');

class mod_facetoface_customfield_testcase extends advanced_testcase {

    public function test_signup_cancellation_notes_creation() {
        global $DB;
        // In a fresh installation a signup customfield type text should be created. Verify it was created.
        $this->assertTrue($DB->record_exists('facetoface_signup_info_field', array('shortname' => 'signupnote')));

        // In a fresh installation a cancellation customfield type text should be created. Verify it was created.
        $this->assertTrue($DB->record_exists('facetoface_cancellation_info_field', array('shortname' => 'cancellationnote')));
    }
}
