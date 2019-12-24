<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara_plan
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Test evidence customfield creation.
 */
class totara_plan_evidence_customfield_testcase extends advanced_testcase {
    public function test_customfield_new_installation_creation() {
        global $DB;

        // On a fresh installation a textarea datatype field should be created.
        $fullname = get_string('evidencedescription', 'totara_plan');
        $shortname = str_replace(' ', '', get_string('evidencedescriptionshort', 'totara_plan'));
        $this->assertTrue($DB->record_exists('dp_plan_evidence_info_field', array('shortname' => $shortname, 'fullname' => $fullname)));

        // On a fresh installation a file datatype field should be created.
        $fullname = get_string('evidencefileattachments', 'totara_plan');
        $shortname = str_replace(' ', '', get_string('evidencefileattachmentsshort', 'totara_plan'));
        $this->assertTrue($DB->record_exists('dp_plan_evidence_info_field', array('shortname' => $shortname, 'fullname' => $fullname)));

        // On a fresh installation a datetime datatype field should be created.
        $fullname = get_string('evidencedatecompleted', 'totara_plan');
        $shortname = str_replace(' ', '', get_string('evidencedatecompletedshort', 'totara_plan'));
        $this->assertTrue($DB->record_exists('dp_plan_evidence_info_field', array('shortname' => $shortname, 'fullname' => $fullname)));
    }

}