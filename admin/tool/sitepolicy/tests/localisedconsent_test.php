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
* Sitepolicy localised consent tests.
*/
class tool_sitepolicy_localisedconsent_test extends \advanced_testcase {

    /**
    * Test from_data
    */
    public function test_from_data() {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $sitepolicy = $generator->create_published_policy([]);
        $row = $DB->get_record('tool_sitepolicy_localised_policy', []);
        $localisedpolicy = new localisedpolicy($row->id);
        $row = $DB->get_record('tool_sitepolicy_consent_options', []);
        $consentoption = new consentoption($row->id);

        $localisedconsent = localisedconsent::from_data($localisedpolicy, $consentoption,
            'The test statement', 'The test consent text', 'The test withhold text');
        $this->assertEquals('The test statement', $localisedconsent->get_statement());
        $this->assertEquals('The test consent text', $localisedconsent->get_consentoption());
        $this->assertEquals('The test withhold text', $localisedconsent->get_nonconsentoption());
        $this->assertEquals($localisedpolicy->get_id(), $localisedconsent->get_localisedpolicy()->get_id());
        $this->assertEquals($consentoption->get_id(), $localisedconsent->get_option()->get_id());
        $this->assertFalse($localisedconsent->is_removed());
    }

    /**
     * Test get_policy_options
     */
    public function test_get_policy_options() {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'authorid' => 2,
            'languages' => 'en',
            'langprefix' => '',
            'title' => 'Test policy options',
            'statement' => 'Policy statement options',
            'numoptions' => 3,
            'consentstatement' => 'Consent statement options',
            'providetext' => 'Give consent text',
            'withheldtext' => 'Withhold consent text',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_published_policy($options);
        $row = $DB->get_record('tool_sitepolicy_localised_policy', []);
        $localisedpolicy = new localisedpolicy($row->id);
        $row = $DB->get_record('tool_sitepolicy_consent_options', ['mandatory' => true]);
        $consentoption = new consentoption($row->id);

        // Single option
        $consentoptions = localisedconsent::get_policy_options($localisedpolicy, $consentoption->get_id());
        $this->assertEquals(1, count($consentoptions));
        $option = reset($consentoptions);
        $this->assertEquals('Consent statement options 1', $option->get_statement());
        $this->assertEquals('Give consent text', $option->get_consentoption());
        $this->assertEquals('Withhold consent text', $option->get_nonconsentoption());

        // All options
        $consentoptions = localisedconsent::get_policy_options($localisedpolicy);
        $this->assertEquals(3, count($consentoptions));

        $expectedsuffix = ['1', '2', '3'];
        foreach ($consentoptions as $option) {
            $suffix = substr($option->get_statement(), -1);
            $this->assertTrue(in_array($suffix, $expectedsuffix));
            $this->assertEquals("Consent statement options $suffix", $option->get_statement());
            $this->assertEquals("Give consent text", $option->get_consentoption());
            $this->assertEquals("Withhold consent text", $option->get_nonconsentoption());

            $idx = array_search($suffix, $expectedsuffix);
            unset($expectedsuffix[$idx]);
        }

        $this->assertEquals(0, count($expectedsuffix));
    }

    /**
     * Test save with exception when localised policy not yet saved
     */
    public function test_save_exeception_unsaved_localisedpolicy() {

        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Localised policy must be saved before saving localised consent option');

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->save();

        $localisedpolicy = localisedpolicy::from_data($version, 'en', localisedpolicy::STATUS_PRIMARY);
        $consentoption = consentoption::from_data($version, true);
        $consentoption->save();
        $localisedconsent = localisedconsent::from_data($localisedpolicy, $consentoption,
            'The statement', 'The consent option text', 'The withhold option text');
        $localisedconsent->save();
    }

    /**
     * Test save with exception when consentoption not yet saved
     */
    public function test_save_exeception_unsaved_consentoption() {

        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Consent option must be saved before saving localised consent option');

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->save();

        $localisedpolicy = localisedpolicy::from_data($version, 'en', localisedpolicy::STATUS_PRIMARY);
        $localisedpolicy->save();
        $consentoption = consentoption::from_data($version, true);
        $localisedconsent = localisedconsent::from_data($localisedpolicy, $consentoption,
            'The statement', 'The consent option text', 'The withhold option text');
        $localisedconsent->save();
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

        // Verify no localised consent in db
        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(0, count($rows));

        $localisedpolicy = localisedpolicy::from_data($version, 'en', localisedpolicy::STATUS_PRIMARY);
        $localisedpolicy->save();
        $consentoption = consentoption::from_data($version, true);
        $consentoption->save();
        $localisedconsent = localisedconsent::from_data($localisedpolicy, $consentoption,
            'The statement', 'The consent option text', 'The withhold option text');
        $localisedconsent->save();

        // Verify localised consent persisted to db
        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertEquals($localisedconsent->get_id(), $row->id);
        $this->assertEquals('The statement', $row->statement);
        $this->assertEquals('The consent option text', $row->consentoption);
        $this->assertEquals('The withhold option text', $row->nonconsentoption);
        $this->assertEquals($localisedpolicy->get_id(), $row->localisedpolicyid);
        $this->assertEquals($consentoption->get_id(), $row->consentoptionid);

        // Now update the data and save again
        $id = $localisedconsent->get_id();
        $localisedconsent->set_statement('New statement');
        $localisedconsent->set_consentoption('New consentoption');
        $localisedconsent->set_nonconsentoption('New withhold');
        $localisedconsent->save();

        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertEquals($id, $row->id);
        $this->assertEquals($localisedconsent->get_id(), $row->id);
        $this->assertEquals('New statement', $row->statement);
        $this->assertEquals('New consentoption', $row->consentoption);
        $this->assertEquals('New withhold', $row->nonconsentoption);
        $this->assertEquals($localisedpolicy->get_id(), $row->localisedpolicyid);
        $this->assertEquals($consentoption->get_id(), $row->consentoptionid);
    }

    /**
     * Test delete and delete_all
     */
    public function test_delete() {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'authorid' => 2,
            'languages' => 'en, fr, nl',
            'langprefix' => ',fr,nl',
            'title' => 'Test policy options',
            'statement' => 'Policy statement options',
            'numoptions' => 3,
            'consentstatement' => 'Consent statement options',
            'providetext' => 'Give consent text',
            'withheldtext' => 'Withhold consent text',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_published_policy($options);
        $row = $DB->get_record('tool_sitepolicy_localised_policy', ['language' => 'nl']);
        $localisedpolicy = new localisedpolicy($row->id);
        $row = $DB->get_record('tool_sitepolicy_consent_options', ['mandatory' => true]);
        $consentoption = new consentoption($row->id);

        // Verify the localised consent rows in the db
        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(3 * 3, count($rows));  // One or per language for each option
        $rows = $DB->get_records('tool_sitepolicy_localised_consent', ['consentoptionid' => $consentoption->get_id()]);
        $this->assertEquals(3, count($rows));  // One for each language
        $rows = $DB->get_records('tool_sitepolicy_localised_consent',
            ['localisedpolicyid' => $localisedpolicy->get_id(), 'consentoptionid' => $consentoption->get_id()]);
        $this->assertEquals(1, count($rows));  // Specific language and option option

        $localisedconsent = localisedconsent::from_data($localisedpolicy, $consentoption,
            'The statement', 'The consent option', 'The withhold option');
        // Deleting the specific option in the specific language
        $localisedconsent->delete();

        // Verify the rows in the db
        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(3 * 3 - 1, count($rows));  // One or per language for each option with 1 removed
        $rows = $DB->get_records('tool_sitepolicy_localised_consent', ['consentoptionid' => $consentoption->get_id()]);
        $this->assertEquals(2, count($rows));  // One language removed for this option
        $rows = $DB->get_records('tool_sitepolicy_localised_consent',
            ['localisedpolicyid' => $localisedpolicy->get_id(), 'consentoptionid' => $consentoption->get_id()]);
        $this->assertEquals(0, count($rows));  // Specific one was deleted

        // Now delete all localised consentoptions of a specific consentoption and verify the db
        localisedconsent::delete_all($consentoption->get_id());
        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(3 * 2, count($rows));  // One or per language for each option. 1 option's localised consents have been deleted
        $rows = $DB->get_records('tool_sitepolicy_localised_consent', ['consentoptionid' => $consentoption->get_id()]);
        $this->assertEquals(0, count($rows));  // All localised consents for this option have been deleted
    }
}