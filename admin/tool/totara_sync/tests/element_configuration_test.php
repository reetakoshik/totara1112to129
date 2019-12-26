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
require_once($CFG->dirroot . '/admin/tool/totara_sync/admin/forms.php');

/**
 * Class tool_totara_sync_element_configuration_testcase
 *
 * Tests behaviour relating to configuration in totara_sync_element base class.
 *
 * @group tool_totara_sync
 */
class tool_totara_sync_element_configuration_testcase extends advanced_testcase {

    /**
     * Create a mock instance of the totara_sync_element. This also uses a mock instance
     * of the dedicated scheduled task so that that doesn't trip you up.
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
            'element_configuration_mockclassname',
            true,
            true,
            true,
            ['get_dedicated_scheduled_task']
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
     * Test for when saving a configuration to use default settings.
     */
    public function test_save_configuration_use_defaults() {
        $this->resetAfterTest();

        $element = $this->create_mock_element();

        // There will be a permission check in save_configuration.
        $this->setAdminUser();

        $data = new stdClass();
        $data->source_mockname = 'asource';
        $data->fileaccess = totara_sync_element_settings_form::USE_DEFAULT;
        $data->notificationusedefaults = 1;
        $data->scheduleusedefaults = 1;

        $element->save_configuration($data);

        $this->assertEquals(1, get_config('element_configuration_mockclassname', 'fileaccessusedefaults'));
        $this->assertTrue($element->use_fileaccess_defaults());

        $this->assertEquals(1, get_config('element_configuration_mockclassname', 'notificationusedefaults'));
        $this->assertTrue($element->use_notification_defaults());

        $this->assertEquals(1, get_config('element_configuration_mockclassname', 'scheduleusedefaults'));
        $this->assertTrue($element->use_schedule_defaults());
    }

    /**
     * Test for saving configuration when overriding the defaults.
     */
    public function test_save_configuration_not_use_defaults() {
        $this->resetAfterTest();

        $element = $this->create_mock_element();

        // There will be a permission check in save_configuration.
        $this->setAdminUser();

        $data = new stdClass();
        $data->source_mockname = 'asource';
        $data->fileaccess = FILE_ACCESS_UPLOAD;
        $data->notificationusedefaults = 0;
        $data->notifymailto = 'jim@example.com, mary@example.com';
        $data->scheduleusedefaults = 0;
        $data->cronenable = 1;

        $element->save_configuration($data);

        $this->assertEquals(0, get_config('element_configuration_mockclassname', 'fileaccessusedefaults'));
        $this->assertFalse($element->use_fileaccess_defaults());
        $this->assertEquals(FILE_ACCESS_UPLOAD, get_config('element_configuration_mockclassname', 'fileaccess'));

        $this->assertEquals(0, get_config('element_configuration_mockclassname', 'notificationusedefaults'));
        $this->assertFalse($element->use_notification_defaults());
        $this->assertEquals('jim@example.com, mary@example.com', get_config('element_configuration_mockclassname', 'notifymailto'));

        $this->assertEquals(0, get_config('element_configuration_mockclassname', 'scheduleusedefaults'));
        $this->assertFalse($element->use_schedule_defaults());
    }

    /**
     * If no configuration for the element has been set, the default settings should be used.
     */
    public function test_use_defaults_by_default() {
        $this->resetAfterTest();
        $element = $this->create_mock_element();

        $this->assertTrue($element->use_fileaccess_defaults());
        $this->assertTrue($element->use_notification_defaults());
        $this->assertTrue($element->use_schedule_defaults());
    }

    /**
     * Check that the default value for fileaccess is returned when configured that way.
     */
    public function test_get_fileaccess_when_default() {
        $this->resetAfterTest();
        $element = $this->create_mock_element();

        // There will be a permission check in save_configuration.
        $this->setAdminUser();

        set_config('fileaccess', FILE_ACCESS_UPLOAD, 'totara_sync');

        $data = new stdClass();
        $data->source_mockname = 'asource';
        $data->fileaccess = totara_sync_element_settings_form::USE_DEFAULT;
        $data->notificationusedefaults = 0;
        $data->notifymailto = 'jim@example.com, mary@example.com';
        $data->scheduleusedefaults = 0;
        $data->cronenable = 1;

        $element->save_configuration($data);

        $this->assertEquals(FILE_ACCESS_UPLOAD, $element->get_fileaccess());
    }

    /**
     * Check that the overriding value for fileaccess is returned when configured that way.
     */
    public function test_get_fileaccess_when_overridden() {
        $this->resetAfterTest();
        $element = $this->create_mock_element();

        // There will be a permission check in save_configuration.
        $this->setAdminUser();

        set_config('fileaccess', FILE_ACCESS_UPLOAD, 'totara_sync');

        $data = new stdClass();
        $data->source_mockname = 'asource';
        $data->fileaccess = FILE_ACCESS_DIRECTORY;
        $data->filesdir = '/tmp';
        $data->notificationusedefaults = 0;
        $data->notifymailto = 'jim@example.com, mary@example.com';
        $data->scheduleusedefaults = 0;
        $data->cronenable = 1;

        $element->save_configuration($data);

        $this->assertEquals(FILE_ACCESS_DIRECTORY, $element->get_fileaccess());
    }

    /**
     * Check that an exception is thrown when there is no available setting for fileaccess.
     */
    public function test_get_fileaccess_throws_exception() {
        $this->resetAfterTest();
        $element = $this->create_mock_element();

        // There will be a permission check in save_configuration.
        $this->setAdminUser();

        // No default is set.
        // No configuration is saved.

        $this->expectException(totara_sync_exception::class);
        $this->expectExceptionMessage('No valid file access configuration found');

        $element->get_fileaccess();
    }

    /**
     * Check that the default value for filesdir is returned when configured that way.
     */
    public function test_get_filesdir_when_default() {
        $this->resetAfterTest();
        $element = $this->create_mock_element();

        // There will be a permission check in save_configuration.
        $this->setAdminUser();

        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', '/tmp/one', 'totara_sync');

        $data = new stdClass();
        $data->source_mockname = 'asource';
        $data->fileaccess = totara_sync_element_settings_form::USE_DEFAULT;
        $data->notificationusedefaults = 0;
        $data->notifymailto = 'jim@example.com, mary@example.com';
        $data->scheduleusedefaults = 0;
        $data->cronenable = 1;

        $element->save_configuration($data);

        $this->assertEquals('/tmp/one', $element->get_filesdir());
    }

    /**
     * Check that the overriding value for filesdir is returned when configured that way.
     */
    public function test_get_filesdir_when_overridden() {
        $this->resetAfterTest();
        $element = $this->create_mock_element();

        // There will be a permission check in save_configuration.
        $this->setAdminUser();

        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', '/tmp/one', 'totara_sync');

        $data = new stdClass();
        $data->source_mockname = 'asource';
        $data->fileaccess = FILE_ACCESS_DIRECTORY;
        $data->filesdir = '/tmp/two';
        $data->notificationusedefaults = 0;
        $data->notifymailto = 'jim@example.com, mary@example.com';
        $data->scheduleusedefaults = 0;
        $data->cronenable = 1;

        $element->save_configuration($data);

        $this->assertEquals('/tmp/two', $element->get_filesdir());
    }

    /**
     * Check that an exception is thrown when there is no available setting for filesdir.
     */
    public function test_get_filesdir_throws_exception() {
        $this->resetAfterTest();
        $element = $this->create_mock_element();

        // There will be a permission check in save_configuration.
        $this->setAdminUser();

        // No default is set.
        // No configuration is saved.

        $this->expectException(totara_sync_exception::class);
        $this->expectExceptionMessage('No valid file directory configuration found');

        $element->get_filesdir();
    }
}
