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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/renderer.php');

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_renderer_testcase extends advanced_testcase {
    /**
     * Test that export select works with reportbuilder id and instance
     */
    public function test_export_select() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $PAGE->set_url('/course/find.php');

        $page = new moodle_page();
        $page->set_context(context_system::instance());
        $renderer = $page->get_renderer('totara_reportbuilder');

        // Prepare report instance.
        $shortname = 'findcourses';
        $report = reportbuilder::create_embedded($shortname);
        $report->set_filter_url_param("courseid", "2");

        // Test with id.
        ob_start();
        $renderer->export_select($report->_id, 0);
        $out = ob_get_contents();
        ob_end_clean();

        // Report id for export will ignore parameters, but still should work.
        $this->assertRegExp('/action=\"[a-z\:\/\.]*course\/find\.php\"/', $out);

        // Test with instance.
        ob_start();
        $renderer->export_select($report, 0);
        $out = ob_get_contents();
        ob_end_clean();
        // Report instance for export must keep parameters.
        $this->assertRegExp('/action=\"[a-z\:\/\.]*course\/find\.php\?courseid=2\"/', $out);
    }
}
