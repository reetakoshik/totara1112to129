<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package block_totara_certifications
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/totara/program/lib.php');
require_once($CFG->dirroot.'/totara/certification/lib.php');

class block_totara_certifications_testcase extends advanced_testcase {

    /**
     * Create a data object for the tests
     *
     * @return stdClass
     */
    public function create_data() {
        $program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $data = new stdClass();
        $data->user1 = $this->getDataGenerator()->create_user(['fullname' => 'user1']);
        $data->user2 = $this->getDataGenerator()->create_user(['fullname' => 'user2']);
        $data->programid = $program_generator->create_certification(['fullname' => 'Test Certification 1']);
        $program_generator->assign_program($data->programid, [$data->user1->id]);

        return $data;
    }

    /**
     * Create a totara_certifications block instance
     *
     * @return block_totara_certifications
     */
    public function create_totara_certifications_block_instance() {
        global $DB;

        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        $page->blocks->get_regions();
        $page->blocks->add_block('totara_certifications', BLOCK_POS_LEFT, 0, false, '*', null);

        $block = $DB->get_record('block_instances', ['blockname' => 'totara_certifications'], '*', IGNORE_MULTIPLE);
        $DB->set_field('block_instances', 'configdata', '', ['id' => $block->id]);

        return block_instance('totara_certifications', $block);
    }

    /**
     * Test the block only displays when Certifications are visible on the site.
     */
    public function test_ensure_block_only_displays_when_feature_visible() {
        // Setup the data.
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        // By default Certification should be enabled / visible.
        $blockinstance = $this->create_totara_certifications_block_instance();
        $content = $blockinstance->get_content()->text;
        $this->assertContains('The following certifications are due for renewal or require completing', $content);
        $this->assertContains('Test Certification 1', $content);

        // Now disable Certifications. The block should not show.
        set_config('enablecertifications', TOTARA_DISABLEFEATURE);

        $blockinstance = $this->create_totara_certifications_block_instance();
        $content = $blockinstance->get_content();
        $this->assertEmpty($content);
    }

    /**
     * Test the block only display when Certifications are visible.
     */
    public function test_ensure_block_checks_if_certification_hidden() {
        global $DB;

        // Setup the data.
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        // The certification is visible, the content should show.
        $blockinstance = $this->create_totara_certifications_block_instance();
        $content = $blockinstance->get_content()->text;
        $this->assertContains('The following certifications are due for renewal or require completing', $content);
        $this->assertContains('Test Certification 1', $content);

        // Mark the certification as hidden, the content should not be displayed.
        $cert = $DB->get_record('prog', ['id' => $data->programid]);
        $cert->visible = 0;
        $DB->update_record('prog', $cert);

        $blockinstance = $this->create_totara_certifications_block_instance();
        $content = $blockinstance->get_content()->text;

        $this->assertContains('No certifications due', $content);
        $this->assertNotContains('Test Certification 1', $content);
    }

    /**
     * Test the block only displays when Certifications are visible using audience based visibility.
     */
    public function test_ensure_block_checks_totara_visibility() {
        global $DB, $CFG;

        // Enable audience based visibility.
        $CFG->audiencevisibility = 1;

        // Setup the data.
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        // Get the certification record.
        $cert = $DB->get_record('prog', ['id' => $data->programid]);

        // Mark the certification visibility as all users
        $cert->audiencevisible = COHORT_VISIBLE_ALL;
        $DB->update_record('prog', $cert);

        // The certification is visible, the content should show.
        $blockinstance = $this->create_totara_certifications_block_instance();
        $content = $blockinstance->get_content()->text;
        $this->assertContains('The following certifications are due for renewal or require completing', $content);
        $this->assertContains('Test Certification 1', $content);

        // Mark the certification visibility as no users
        $cert->audiencevisible = COHORT_VISIBLE_NOUSERS;
        $DB->update_record('prog', $cert);

        // The certification content should not show.
        $blockinstance = $this->create_totara_certifications_block_instance();
        $content = $blockinstance->get_content()->text;
        $this->assertContains('No certifications due', $content);
        $this->assertNotContains('Test Certification 1', $content);

        // Mark the certification visibility to Enrolled users only.
        $cert->audiencevisible = COHORT_VISIBLE_ENROLLED;
        $DB->update_record('prog', $cert);

        // The certification content should show, the user is enrolled.
        $blockinstance = $this->create_totara_certifications_block_instance();
        $content = $blockinstance->get_content()->text;
        $this->assertContains('The following certifications are due for renewal or require completing', $content);
        $this->assertContains('Test Certification 1', $content);

        // Mark the certification visibility to Enrolled users and member of audience.
        $cert->audiencevisible = COHORT_VISIBLE_AUDIENCE;
        $DB->update_record('prog', $cert);

        // The certification content should show, the user is enrolled. (They don't actually need to be a member of
        // an audience as the slq is checking record exists in prog_user_assignment).
        $blockinstance = $this->create_totara_certifications_block_instance();
        $content = $blockinstance->get_content()->text;
        $this->assertContains('The following certifications are due for renewal or require completing', $content);
        $this->assertContains('Test Certification 1', $content);
    }
}
