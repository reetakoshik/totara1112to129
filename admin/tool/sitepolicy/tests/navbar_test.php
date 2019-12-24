<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/message/output/popup/lib.php');

/**
 * Sitepolicy localised policy tests.
 */
class tool_sitepolicy_navbar_test extends \advanced_testcase {
    /**
     * Confirm that admin with active consents will get navbar.
     */
    public function test_admin_gets_navbar() {
        global $CFG,$PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();

        /**
         * @var \tool_sitepolicy_generator $generator
         */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');
        $generator->create_published_policy();
        $corerenderer = $PAGE->get_renderer('core');

        $prepolicies = $CFG->enablesitepolicies;
        $CFG->enablesitepolicies = 1;

        $output = message_popup_render_navbar_output($corerenderer);
        $CFG->enablesitepolicies = $prepolicies;

        $this->assertContains("nav-message-popover-container", $output);
        $this->assertContains("nav-notification-popover-container", $output);
    }

    /**
     * Confirm that user gets navbar with site policies disabled
     */
    public function test_user_gets_navbar() {
        global $CFG, $PAGE;

        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        /**
         * @var \tool_sitepolicy_generator $generator
         */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');
        $generator->create_published_policy();

        $corerenderer = $PAGE->get_renderer('core');

        $output = message_popup_render_navbar_output($corerenderer);

        $this->assertContains("nav-message-popover-container", $output);
        $this->assertContains("nav-notification-popover-container", $output);
    }

    /**
     * Confirm that user with pending site policies doesn't get navbar
     */
    public function test_no_policy_doesnt_get_navbar() {
        global $CFG,$PAGE;

        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        /**
         * @var \tool_sitepolicy_generator $generator
         */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');
        $generator->create_published_policy();

        $corerenderer = $PAGE->get_renderer('core');

        $prepolicies = $CFG->enablesitepolicies;
        $CFG->enablesitepolicies = 1;

        $output = message_popup_render_navbar_output($corerenderer);
        $CFG->enablesitepolicies = $prepolicies;

        $this->assertNotContains("nav-message-popover-container", $output);
        $this->assertNotContains("nav-notification-popover-container", $output);
    }

    /**
     * Confirm that user with accepted site policy gets navbar
     */
    public function test_policy_agreed_does_get_navbar() {
        global $CFG,$PAGE;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /**
         * @var \tool_sitepolicy_generator $generator
         */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');
        $generator->create_published_policy([
            'hasconsented' => true,
            'consentuser' => true,
            'userid' => $user->id
        ]);

        $corerenderer = $PAGE->get_renderer('core');

        $prepolicies = $CFG->enablesitepolicies;
        $CFG->enablesitepolicies = 1;

        $output = message_popup_render_navbar_output($corerenderer);
        $CFG->enablesitepolicies = $prepolicies;

        $this->assertContains("nav-message-popover-container", $output);
        $this->assertContains("nav-notification-popover-container", $output);
    }
}