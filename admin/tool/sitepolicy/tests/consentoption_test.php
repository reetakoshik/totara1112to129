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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy;

defined('MOODLE_INTERNAL') || die();

/**
 * Consentoption tests
 */
class tool_sitepolicy_consentoption_test extends \advanced_testcase {
    /**
     * Test from_data error condition
     */
    public function test_from_data_unsaved_sitepolicy_exception() {

        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Site policy must be saved before adding policy versions');

        $sitepolicy = new sitepolicy();
        $version = policyversion::new_policy_draft($sitepolicy);
        consentoption::from_data($version, true);
    }

    /**
     * Test from_data error condition
     */
    public function test_from_data_unsaved_version_exception() {

        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Version must be saved before adding consent options');

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        consentoption::from_data($version, true);
    }

    /**
     * Test successful from_data
     */
    public function test_from_data() {

        $this->resetAfterTest();

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->save();

        $consentoption = consentoption::from_data($version, true, 123);

        $this->assertEquals(0, $consentoption->get_id());
        $this->assertTrue($consentoption->get_mandatory());
        $this->assertEquals(123, $consentoption->get_idnumber());
        $this->assertEquals($version->get_id(), $consentoption->get_policyversion()->get_id());
    }

    /**
     * Test save error condition
     */
    public function test_save_exception_on_unsaved_sitepolicy() {
        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Site policy must be saved before adding policy versions');

        $sitepolicy = new sitepolicy();
        $version = policyversion::new_policy_draft($sitepolicy);

        $consentoption = consentoption::from_data($version, true, 123);
        $consentoption->save();
    }

    /**
     * Test save error condition
     */
    public function test_save_exception_on_unset_policyversion() {
        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Version must be saved before saving the consent option');

        $consentoption = new consentoption(0);
        $consentoption->save();
    }

    /**
     * Test save
     */
    public function test_save() {
        global $DB;

        $this->resetAfterTest();

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->save();

        // Verify no consentoption to start with
        $rows = $DB->get_records('tool_sitepolicy_consent_options');
        $this->assertEquals(0, count($rows));

        $consentoption = consentoption::from_data($version, true, '123');
        $consentoption->save();

        $rows = $DB->get_records('tool_sitepolicy_consent_options');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertTrue((bool)$row->mandatory);
        $this->assertEquals('123', $row->idnumber);
        $this->assertEquals($version->get_id(), $row->policyversionid);

        $id = $row->id;
        $this->assertEquals($id, $consentoption->get_id());

        // Make some changes and update
        $consentoption->set_mandatory(false);
        $consentoption->set_idnumber('a1');
        $consentoption->save();

        $rows = $DB->get_records('tool_sitepolicy_consent_options');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertFalse((bool)$row->mandatory);
        $this->assertEquals('a1', $row->idnumber);
        $this->assertEquals($version->get_id(), $row->policyversionid);
        $this->assertEquals($id, $consentoption->get_id());
    }

    /**
     * Test delete error condition when localisedconsent exists
     */
    public function test_delete_exception_localised_consent() {
        global $DB;

        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Consent option can't be deleted while localised_consent or user_consent entries exist");
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $sitepolicy = $generator->create_published_policy([]);
        $version = policyversion::from_policy_latest($sitepolicy);

        // Retrieve the consentoption
        $row = $DB->get_record('tool_sitepolicy_consent_options', ['policyversionid' => $version->get_id()], '*', MUST_EXIST);
        $consentoption = new consentoption($row->id);

        // Fail because localised_consent exists
        $row = $DB->get_record('tool_sitepolicy_localised_consent', [], MUST_EXIST);
        $consentoption->delete();
    }

    /**
     * Test delete error condition when userconsent exists
     */
    public function test_delete_exception_user_consent() {
        global $DB;

        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Consent option can't be deleted while localised_consent or user_consent entries exist");

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->save();
        $consentoption = consentoption::from_data($version, true, '123');
        $consentoption->save();

        // Manually insert a userconsent
        $entry = new \stdClass();
        $entry->userid = 2;
        $entry->timeconsented = time();
        $entry->hasconsented = false;
        $entry->consentoptionid = $consentoption->get_id();
        $entry->language = 'en';
        $this->id = $DB->insert_record('tool_sitepolicy_user_consent', $entry);

        // Now try to delete the consentoption
        $consentoption->delete();
    }

    /**
     * Test successful delete
     */
    public function test_delete() {
        global $DB;

        $this->resetAfterTest();

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->save();
        $consentoption = consentoption::from_data($version, true, '123');
        $consentoption->save();

        // Verify consentoption exists
        $rows = $DB->get_records('tool_sitepolicy_consent_options');
        $this->assertEquals(1, count($rows));

        // Delete
        $consentoption->delete();
        $rows = $DB->get_records('tool_sitepolicy_consent_options');
        $this->assertEquals(0, count($rows));
    }
}