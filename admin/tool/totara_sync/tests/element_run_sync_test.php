<?php
/*
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/elements/classes/element.class.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/classes/source.class.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/admin/forms.php');

/**
 * Class tool_totara_sync_element_run_sync_testcase
 *
 * Tests the totara_sync_element::run_sync() method
 *
 * @group tool_totara_sync
 */
class tool_totara_sync_element_run_sync_testcase extends advanced_testcase {

    /**
     * Create a mock instance of the totara_sync_element. This also uses a mock instance
     * of the dedicated scheduled task so that that doesn't trip you up.
     *
     * You may need to mock the get_source method as well as any abstract methods that should
     * return a value.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|totara_sync_element
     */
    private function create_mock_element() {
        global $DB;

        // We want to mock one concrete method, get_dedicated_scheduled_task.
        /** @var totara_sync_element|\PHPUnit\Framework\MockObject\MockObject $element */
        $element = $this->getMockForAbstractClass(
            'totara_sync_element',
            [],
            '',
            true,
            true,
            true,
            ['get_dedicated_scheduled_task', 'get_source']
        );

        // This would otherwise be done in the constructor, but only if has_config has been implemented.
        $element->config = new stdClass();

        $element->expects($this->any())
            ->method('get_name')
            ->will($this->returnValue('mockname'));

        /** @var \core\task\scheduled_task|\PHPUnit\Framework\MockObject\MockObject $scheduled_task */
        $scheduled_task = $this->getMockForAbstractClass('\core\task\scheduled_task', [], 'mock_task');
        $scheduled_task_record = \core\task\manager::record_from_scheduled_task($scheduled_task);
        $DB->insert_record('task_scheduled', $scheduled_task_record);
        $element->expects(($this->any()))
            ->method('get_dedicated_scheduled_task')
            ->will($this->returnValue($scheduled_task));

        return $element;
    }

    /**
     * Run a sync that completes without issues.
     *
     * Note that the sync() method which gets called internally is mocked to simply log a message (so that we can
     * make sure emails do go out as per notification settings) and then return true.
     */
    public function test_successful_sync() {
        $this->resetAfterTest();
        $element = $this->create_mock_element();

        $source = $this->getMockForAbstractClass(
            'totara_sync_source',
            [],
            'mocksource',
            false,
            false,
            true,
            ['get_config']
        );
        $sourceconfig = new stdClass();
        $sourceconfig->somesetting = 'somevalue';
        $source->expects($this->any())
            ->method('get_config')
            ->will($this->returnValue($sourceconfig));

        $element->expects($this->any())
            ->method('get_source')
            ->will(
                $this->returnValue($source)
            );

        // Here is where we say that the sync will be successful.
        $element->expects($this->any())
            ->method('sync')
            ->will($this->returnCallback(function() {
                totara_sync_log('mockname', 'Need a log item to trigger an email', 'warn', 'sync');
                return true;
            }));

        // There will be a permission check in save_configuration.
        $this->setAdminUser();

        // Defaults.
        set_config('notifymailto', 'jim@example.com, mary@example.com', 'totara_sync');
        set_config('notifytypes', 'error,warn', 'totara_sync');

        $data = new stdClass();
        $data->source_mockname = 'mocksource';
        $data->fileaccess = totara_sync_element_settings_form::USE_DEFAULT;
        $data->notificationusedefaults = 1;
        $data->notifymailto = 'tom@example.com, jane@example.com';
        $data->scheduleusedefaults = 1;

        $element->save_configuration($data);

        $event_sink = $this->redirectEvents();
        $email_sink = $this->redirectEmails();

        ob_start();
        $this->assertTrue($element->run_sync());
        ob_end_clean();

        $events = $event_sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(\tool_totara_sync\event\sync_completed::class, $event);
        $this->assertEquals('mockname', $event->get_data()['other']['element']);
        $event_sink->clear();

        $emails = $email_sink->get_messages();
        $this->assertCount(2, $emails);
        $expected = ['jim@example.com', 'mary@example.com'];
        foreach ($emails as $email) {
            $this->assertContains($email->to, $expected);
            // So in the next iteration of this loop, this same email should not be expected.
            unset($expected[array_search($email->to, $expected)]);
        }
    }

    /**
     * Test the operation of run_sync() when there are configuration errors for this element.
     */
    public function test_with_configuration_errors() {
        $this->resetAfterTest();
        $element = $this->create_mock_element();

        $element->expects($this->any())
            ->method('get_source')
            ->will(
                $this->throwException(new totara_sync_exception('mockname', 'getsource', 'sourcefilexnotfound', ''))
            );

        // There will be a permission check in save_configuration.
        $this->setAdminUser();

        $data = new stdClass();
        $data->source_mockname = 'falsesource';
        $data->fileaccess = totara_sync_element_settings_form::USE_DEFAULT;
        $data->notificationusedefaults = 1;
        $data->scheduleusedefaults = 1;

        $element->save_configuration($data);

        ob_start();
        $this->assertFalse($element->run_sync());
        ob_end_clean();
    }

    /**
     * Test the operation of run_sync() when the internally called sync() method has been mocked
     * to throw a totara_sync_exception.
     */
    public function test_sync_with_exception_thrown() {
        $this->resetAfterTest();
        $element = $this->create_mock_element();

        $source = $this->getMockForAbstractClass(
            'totara_sync_source',
            [],
            'mocksource',
            false,
            false,
            true,
            ['get_config']
        );
        $sourceconfig = new stdClass();
        $sourceconfig->somesetting = 'somevalue';
        $source->expects($this->any())
            ->method('get_config')
            ->will($this->returnValue($sourceconfig));

        $element->expects($this->any())
            ->method('get_source')
            ->will(
                $this->returnValue($source)
            );

        // Here is where we say that the sync will throw an exception.
        $element->expects($this->any())
            ->method('sync')
            ->will($this->throwException(new totara_sync_exception('mockname', 'sync', 'goneburgers')));

        // There will be a permission check in save_configuration.
        $this->setAdminUser();

        // Defaults.
        set_config('notifymailto', 'jim@example.com, mary@example.com', 'totara_sync');
        set_config('notifytypes', 'error,warn', 'totara_sync');

        $data = new stdClass();
        $data->source_mockname = 'mocksource';
        $data->fileaccess = totara_sync_element_settings_form::USE_DEFAULT;
        $data->notificationusedefaults = 0;
        $data->notifymailto = 'tom@example.com, jane@example.com';
        $data->notifytypes = ['error' => 1];
        $data->scheduleusedefaults = 1;

        $element->save_configuration($data);

        $event_sink = $this->redirectEvents();
        $email_sink = $this->redirectEmails();

        ob_start();
        $this->assertFalse($element->run_sync());
        ob_end_clean();

        $events = $event_sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(\tool_totara_sync\event\sync_completed::class, $event);
        $this->assertEquals('mockname', $event->get_data()['other']['element']);
        $event_sink->clear();

        $emails = $email_sink->get_messages();
        $this->assertCount(2, $emails);
        $expected = ['tom@example.com', 'jane@example.com'];
        foreach ($emails as $email) {
            $this->assertContains($email->to, $expected);
            // So in the next iteration of this loop, this same email should not be expected.
            unset($expected[array_search($email->to, $expected)]);
        }
    }
}