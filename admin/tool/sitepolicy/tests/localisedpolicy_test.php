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
* Sitepolicy localised policy tests.
*/
class tool_sitepolicy_localisedpolicy_test extends \advanced_testcase {

    /**
    * Test from_data
    */
    public function test_from_data() {
        global $DB;

        $this->resetAfterTest();

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->save();

        $localisedpolicy = localisedpolicy::from_data($version, 'en');
        $this->assertEquals(1, $localisedpolicy->is_primary());

        // Constructing a new instance doesn't persist it to the db
        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(0, count($rows));

        // Save this version and create a new one
        $localisedpolicy->save();

        // Duplicate languages are only tested on save
        $localisedpolicy = localisedpolicy::from_data($version, 'en');
        $this->assertEquals(0, $localisedpolicy->is_primary());
    }

    /**
     * Test from_version
     */
    public function test_from_version() {
        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => true,
            'numpublished' => 0,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'fr,nl,en',
            'langprefix' => 'fr,nl,en',
            'title' => 'Test policy all',
            'statement' => "Policy statement<br />all",
            'statementformat' => FORMAT_HTML,
            'numoptions' => 1,
            'consentstatement' => 'Consent statement all',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $localisedpolicy = localisedpolicy::from_version($version, ['isprimary' => localisedpolicy::STATUS_PRIMARY]);
        $this->assertEquals('fr', $localisedpolicy->get_language());
        $this->assertEquals('fr Test policy all', $localisedpolicy->get_title(false));
        $this->assertEquals('fr Test policy all', $localisedpolicy->get_title(true));
        $this->assertEquals("fr Policy statement<br />all", $localisedpolicy->get_policytext(false));
        $this->assertEquals("fr Policy statement<br />all", $localisedpolicy->get_policytext(true));
        $this->assertEquals(FORMAT_HTML, $localisedpolicy->get_policytextformat());
        $this->assertEquals(1, $localisedpolicy->is_primary());

        $localisedpolicy = localisedpolicy::from_version($version, ['language' => 'en']);
        $this->assertEquals('en', $localisedpolicy->get_language());
        $this->assertEquals('en Test policy all', $localisedpolicy->get_title(false));
        $this->assertEquals('en Test policy all', $localisedpolicy->get_title(true));
        $this->assertEquals("en Policy statement<br />all", $localisedpolicy->get_policytext(false));
        $this->assertEquals("en Policy statement<br />all", $localisedpolicy->get_policytext(true));
        $this->assertEquals(FORMAT_HTML, $localisedpolicy->get_policytextformat());
        $this->assertEquals(0, $localisedpolicy->is_primary());
    }

    /**
     * Test from_version
     */
    public function test_formatted_content() {
        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => true,
            'numpublished' => 0,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'en',
            'langprefix' => 'en',
            'title' => 'Test policy <a href="test">all</a>',
            'statement' => "A test policy statement\n============\n\nWith some **bold** markdown\n",
            'statementformat' => FORMAT_MARKDOWN,
            'numoptions' => 1,
            'consentstatement' => 'Consent statement all',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $localisedpolicy = localisedpolicy::from_version($version, ['isprimary' => localisedpolicy::STATUS_PRIMARY]);
        $this->assertEquals('en', $localisedpolicy->get_language());
        $this->assertEquals('en Test policy <a href="test">all</a>', $localisedpolicy->get_title(false));
        $this->assertEquals('en Test policy all', $localisedpolicy->get_title(true));
        $this->assertEquals("en A test policy statement\n============\n\nWith some **bold** markdown\n", $localisedpolicy->get_policytext(false));
        $this->assertEquals("<h1>en A test policy statement</h1>\n\n<p>With some <strong>bold</strong> markdown</p>\n", $localisedpolicy->get_policytext(true));
        $this->assertEquals(FORMAT_MARKDOWN, $localisedpolicy->get_policytextformat());
        $this->assertEquals(1, $localisedpolicy->is_primary());
        $this->assertEquals('', $localisedpolicy->get_whatsnew());
        // Ensure that it was created within the last 2 seconds.
        $time = time();
        $created = $localisedpolicy->get_timecreated();
        $diff = abs($time - $created);
        $this->assertTrue($diff >= 0 && $diff < 2);

        // Finally check that the primary formatting works just as well.
        $this->assertEquals('en Test policy <a href="test">all</a>', $localisedpolicy->get_primary_title(false));
        $this->assertEquals('en Test policy all', $localisedpolicy->get_primary_title(true));
    }

    /**
     * Test save with exception when another primary version exists
     */
    public function test_save_exeception_other_primary() {

        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Cannot save localised policy. Another primary localised policy already exists.');

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->save();

        $localisedpolicy = localisedpolicy::from_data($version, 'en', localisedpolicy::STATUS_PRIMARY);
        $localisedpolicy->save();
        $localisedpolicy = localisedpolicy::from_data($version, 'nl', localisedpolicy::STATUS_PRIMARY);
        $localisedpolicy->save();
    }

    /**
     * Test save with exception when another version with the same language exists
     */
    public function test_save_exeception_duplicate_language() {

        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Cannot save localised policy. Another policy with this language and version already exists.');

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->save();

        $localisedpolicy = localisedpolicy::from_data($version, 'nl', localisedpolicy::STATUS_PRIMARY);
        $localisedpolicy->save();
        $localisedpolicy = localisedpolicy::from_data($version, 'nl', localisedpolicy::STATUS_NOTPRIMARY);
        $localisedpolicy->save();
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

        $localisedpolicy = localisedpolicy::from_data($version, 'en');
        $localisedpolicy->save();

        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertEquals('en', $row->language);
        $this->assertEquals('', $row->title);
        $this->assertEquals('', $row->policytext);
        $this->assertEquals(FORMAT_HTML, $row->policytextformat);
        $this->assertEquals('', $row->whatsnew);
        $this->assertFalse(empty($row->timecreated));
        $this->assertEquals(localisedpolicy::STATUS_PRIMARY, $row->isprimary);
        $this->assertEquals($version->get_id(), $row->policyversionid);

        $time = time() - 2;
        $localisedpolicy->set_title('The title');
        $localisedpolicy->set_policytext('The policy text', FORMAT_PLAIN);
        $localisedpolicy->set_whatsnew('The whatsnew text', FORMAT_MARKDOWN);
        $localisedpolicy->set_timecreated($time);
        $localisedpolicy->set_isprimary(0);
        $localisedpolicy->set_authorid(2);
        $localisedpolicy->save();

        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertEquals('en', $row->language);
        $this->assertEquals('The title', $row->title);
        $this->assertEquals('The policy text', $row->policytext);
        $this->assertEquals(FORMAT_PLAIN, $row->policytextformat);
        $this->assertEquals('The whatsnew text', $row->whatsnew);
        $this->assertEquals(FORMAT_MARKDOWN, $row->whatsnewformat);
        $this->assertEquals($time, $row->timecreated);
        $this->assertEquals(localisedpolicy::STATUS_NOTPRIMARY, $row->isprimary);
        $this->assertEquals($version->get_id(), $row->policyversionid);
    }

    /**
     * Test set_statements
     */
    public function test_set_statements () {
        global $DB;

        $this->resetAfterTest();

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->save();

        $localisedpolicy = localisedpolicy::from_data($version, 'en');

        // 3 new statements
        $statements = [];
        for ($i = 0; $i < 3; $i++) {
            $stmt = new statement();
            $stmt->__set('dataid', 0);
            $stmt->__set('instance', $i + 1);
            $stmt->__set('statement', "Consent statement $i");
            $stmt->__set('provided', "Yes");
            $stmt->__set('withheld', "No");
            $stmt->__set('mandatory', $i == 0 ? 1 : 0);
            $stmt->__set('removedstatement', false);
            $stmt->__set('index', $i + 1);
            $statements[$i] = $stmt;
        }

        $localisedpolicy->set_statements($statements);

        $options = $localisedpolicy->get_consentoptions();
        $this->assertSame(count($statements), count($options));
        foreach($options as $i => $localisedconsent) {
            $consentoption = $localisedconsent->get_option();
            $this->assertEquals(0, $consentoption->get_id());
            $this->assertEquals(($i == 0), $consentoption->get_mandatory());
            $this->assertEquals($statements[$i]->statement, $localisedconsent->get_statement());
            $this->assertEquals($statements[$i]->provided, $localisedconsent->get_consentoption());
            $this->assertEquals($statements[$i]->withheld, $localisedconsent->get_nonconsentoption());
            $this->assertFalse($localisedconsent->is_removed());
        }

        // 1 new removed statement
        $statements = [];
        $stmt = new statement();
        $stmt->__set('dataid', 0);
        $stmt->__set('instance', 1);
        $stmt->__set('statement', "Consent statement");
        $stmt->__set('provided', "Yes");
        $stmt->__set('withheld', "No");
        $stmt->__set('mandatory', 0);
        $stmt->__set('removedstatement', true);
        $stmt->__set('index', 1);
        $statements[$i] = $stmt;

        $localisedpolicy->set_statements($statements);
        $options = $localisedpolicy->get_consentoptions();
        $this->assertSame(0, count($options));

        // 1 existing removed statement
        $entry = new \stdClass();
        $entry->mandatory = 1;
        $entry->idnumber = 1;
        $entry->policyversionid = $version->get_id();
        $consentoptionid = $DB->insert_record('tool_sitepolicy_consent_options', $entry);

        $statements = [];
        $stmt = new statement();
        $stmt->__set('dataid', $consentoptionid);
        $stmt->__set('instance', 1);
        $stmt->__set('statement', "Consent statement");
        $stmt->__set('provided', "Yes");
        $stmt->__set('withheld', "No");
        $stmt->__set('mandatory', 0);
        $stmt->__set('removedstatement', true);
        $stmt->__set('index', 1);
        $statements[$i] = $stmt;

        $localisedpolicy->set_statements($statements);
        $options = $localisedpolicy->get_consentoptions();
        $this->assertSame(1, count($options));

        $localisedconsent = $options[0];
        $consentoption = $localisedconsent->get_option();
        $this->assertEquals($consentoptionid, $consentoption->get_id());
        $this->assertEquals(0, $consentoption->get_mandatory());
        $this->assertEquals($stmt->statement, $localisedconsent->get_statement());
        $this->assertEquals($stmt->provided, $localisedconsent->get_consentoption());
        $this->assertEquals($stmt->withheld, $localisedconsent->get_nonconsentoption());
        $this->assertTrue($localisedconsent->is_removed());
    }

    /**
     * Test saving of consent options
     */
    public function test_save_consentoptions() {
        global $DB;

        $this->resetAfterTest();

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->save();

        $localisedpolicy = localisedpolicy::from_data($version, 'en');
        $time = time();
        $localisedpolicy->set_title('The title');
        $localisedpolicy->set_policytext('The policy text');
        $localisedpolicy->set_whatsnew('The whatsnew text');
        $localisedpolicy->set_timecreated($time);
        $localisedpolicy->set_isprimary(1);
        $localisedpolicy->set_authorid(2);

        // Add 3 new options to be saved
        $statements = [];
        for ($i = 0; $i < 3; $i++) {
            $stmt = new statement();
            $stmt->__set('dataid', 0);
            $stmt->__set('instance', $i + 1);
            $stmt->__set('statement', "Consent statement $i");
            $stmt->__set('provided', "Yes");
            $stmt->__set('withheld', "No");
            $stmt->__set('mandatory', $i == 0 ? 1 : 0);
            $stmt->__set('removedstatement', false);
            $stmt->__set('index', $i + 1);
            $statements[$i] = $stmt;
        }
        $localisedpolicy->set_statements($statements);
        $localisedpolicy->save();

        $sql = "
            SELECT tsco.id,
                   tsco.mandatory,
                   tslc.statement,
                   tslc.consentoption,
                   tslc.nonconsentoption
              FROM {tool_sitepolicy_consent_options} tsco
              JOIN {tool_sitepolicy_localised_consent} tslc
                ON tsco.id = tslc.consentoptionid
             WHERE tsco.policyversionid = :policyversionid
               AND tslc.localisedpolicyid = :localisedpolicyid
            ";
        $params = ['policyversionid' => $version->get_id(),
                   'localisedpolicyid' => $localisedpolicy->get_id()];
        $optionrows = $DB->get_records_sql($sql, $params);
        $this->assertSame(3, count($optionrows));

        foreach($optionrows as $row) {
            $idx = substr($row->statement, strrpos($row->statement, ' ') + 1);
            $this->assertEquals((int)($idx == 0), $row->mandatory);
            // Set the dataids for later tests
            $statements[$idx]->dataid = $row->id;
        }

        // Remove an existing statement
        $statements[1]->removedstatement = true;
        $localisedpolicy->set_statements($statements);
        $localisedpolicy->save();

        $sql = "
            SELECT tsco.id,
                   tsco.mandatory,
                   tslc.statement,
                   tslc.consentoption,
                   tslc.nonconsentoption
              FROM {tool_sitepolicy_consent_options} tsco
              JOIN {tool_sitepolicy_localised_consent} tslc
                ON tsco.id = tslc.consentoptionid
             WHERE tsco.policyversionid = :policyversionid
               AND tslc.localisedpolicyid = :localisedpolicyid
            ";
        $params = ['policyversionid' => $version->get_id(),
                   'localisedpolicyid' => $localisedpolicy->get_id()];
        $optionrows = $DB->get_records_sql($sql, $params);
        $this->assertSame(2, count($optionrows));
        foreach($optionrows as $row) {
            $idx = substr($row->statement, strrpos($row->statement, ' ') + 1);
            $this->assertFalse($statements[$idx]->removedstatement);
        }
    }

    /**
     * Test get_statements
     */
    public function test_get_statements() {
        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'authorid' => 2,
            'languages' => 'en,nl',
            'langprefix' => ',nl',
            'title' => 'Test policy get statements',
            'statement' => 'Policy statement get statements',
            'numoptions' => 3,
            'consentstatement' => 'Consent statement get statements',
            'providetext' => 'Yes',
            'withheldtext' => 'No',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_draft_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);
        $localisedpolicy = localisedpolicy::from_version($version, ['language' => 'nl']);

        $statements = $localisedpolicy->get_statements(false);
        $this->assertEquals(3, count($statements));
        $idx = 0;
        foreach ($statements as $stmt) {
            $idx += 1;
            $this->assertEquals('', $stmt->primarystatement);
            $this->assertEquals('', $stmt->primaryprovided);
            $this->assertEquals('', $stmt->primarywithheld);
            $this->assertEquals("nl Consent statement get statements $idx", $stmt->statement);
            $this->assertEquals('nl Yes', $stmt->provided);
            $this->assertEquals('nl No', $stmt->withheld);
            $this->assertEquals($idx == 1, $stmt->mandatory);
        }
    }

    /**
     * Test delete with a single language and consent option
     */
    public function test_delete_single_language_and_option() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'en',
            'langprefix' => '',
            'title' => 'Test policy delete',
            'statement' => 'Policy statement delete',
            'numoptions' => 1,
            'consentstatement' => 'Consent statement delete',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $localisedpolicy = localisedpolicy::from_version($version, ['language' => 'en']);

        // Verify database rows exist
        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(1, count($rows));

        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(1, count($rows));

        // Now delete the localised_policy. Localised_consent should also be deleted
        $localisedpolicy->delete();
        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(0, count($rows));

        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(0, count($rows));
    }

    /**
     * Test delete with a multiple languages and consent options
     */
    public function test_delete_multi_languages_and_options() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'en,nl',
            'langprefix' => ',nl',
            'title' => 'Test policy delete',
            'statement' => 'Policy statement delete',
            'numoptions' => 3,
            'consentstatement' => 'Consent statement delete',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $localisedpolicy = localisedpolicy::from_version($version, ['language' => 'en']);

        // Verify database rows exist
        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(2, count($rows));

        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(2 * 3, count($rows));

        // Now delete the localised_policy. Localised_consent should also be deleted
        $localisedpolicy->delete();
        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertEquals('nl', $row->language);
        $id = $row->id;

        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(3, count($rows));

        foreach ($rows as $row) {
            $this->assertEquals($id, $row->localisedpolicyid);
        }
    }

    /**
     * Test delete after a user has consented is not possible
     */
    public function test_delete_with_consent_not_possible() {
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Localised policy can\'t be deleted while user_consent entries exist');

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'en',
            'langprefix' => '',
            'title' => 'Test policy delete',
            'statement' => 'Policy statement delete',
            'numoptions' => 1,
            'consentstatement' => 'Consent statement delete',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $localisedpolicy = localisedpolicy::from_version($version, ['language' => 'en']);

        $userconsent = new userconsent();
        $userconsent->set_timeconsented(1523249171);
        $userconsent->set_consentoptionid(abs(key($localisedpolicy->get_statements())));
        $userconsent->set_language('en');
        $userconsent->save();

        // Now delete the localised_policy. Localised_consent should also be deleted
        $localisedpolicy->delete();
    }

    /**
     * Test clone content
     */
    public function test_clone_content() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'authorid' => 2,
            'languages' => 'en,nl',
            'langprefix' => ',nl',
            'title' => 'Test policy clone',
            'statement' => 'Policy statement clone',
            'numoptions' => 3,
            'consentstatement' => 'Consent statement clone',
            'providetext' => 'Yip',
            'withheldtext' => 'Nope',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_published_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);
        $draft = policyversion::new_policy_draft($sitepolicy);
        $draft->save();
        $draft->clone_content($version);

        $sql = "
            SELECT tslc.id,
                   tsco.mandatory,
                   tslc.statement,
                   tslc.consentoption,
                   tslc.nonconsentoption,
                   tslp.language,
                   tslp.isprimary
              FROM {tool_sitepolicy_consent_options} tsco
              JOIN {tool_sitepolicy_localised_policy} tslp
                ON tslp.policyversionid = :policyversionid
              JOIN {tool_sitepolicy_localised_consent} tslc
                ON tsco.id = tslc.consentoptionid
             WHERE tsco.policyversionid = :policyversionid2
               AND tslc.localisedpolicyid = tslp.id
            ";
        $params = ['policyversionid' => $version->get_id(), 'policyversionid2' => $version->get_id()];
        $publishedrows = $DB->get_records_sql($sql, $params);

        $params = ['policyversionid' => $draft->get_id(), 'policyversionid2' => $draft->get_id()];
        $draftrows = $DB->get_records_sql($sql, $params);

        $this->assertEquals(6, count($publishedrows));
        $this->assertEquals(3, count($draftrows));

        $primarypublished = array_filter($publishedrows, function($row) {
            return $row->isprimary;
        });
        $otherpublished = array_filter($publishedrows, function($row) {
            return !$row->isprimary;
        });

        foreach ($primarypublished as $option) {
            $fnd = array_filter($draftrows, function($draftrow) use ($option) {
                return ($draftrow->mandatory == $option->mandatory &&
                        $draftrow->statement == $option->statement &&
                        $draftrow->consentoption == $option->consentoption &&
                        $draftrow->nonconsentoption == $option->nonconsentoption &&
                        $draftrow->language == $option->language);
            });
            $this->assertEquals(1, count($fnd));
        }

        foreach ($otherpublished as $option) {
            $fnd = array_filter($draftrows, function($draftrow) use ($option) {
                return $draftrow->language == $option->language;
            });
            $this->assertEquals(0, count($fnd));
        }
    }

    /**
     * Test get primary and localised titles
     */
    public function test_get_titles() {

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'authorid' => 2,
            'languages' => 'en,nl',
            'langprefix' => ',nl',
            'title' => 'Test policy clone',
            'statement' => 'Policy statement clone',
            'numoptions' => 3,
            'consentstatement' => 'Consent statement clone',
            'providetext' => 'Yip',
            'withheldtext' => 'Nope',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_published_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);
        $localisedpolicy_en = localisedpolicy::from_version($version, ['language' => 'en']);
        $localisedpolicy_nl = localisedpolicy::from_version($version, ['language' => 'nl']);

        $this->assertEquals('Test policy clone', $localisedpolicy_en->get_primary_title());
        $this->assertEquals('Test policy clone', $localisedpolicy_nl->get_primary_title());
        $this->assertEquals('nl Test policy clone', $localisedpolicy_en->get_translated_title('nl'));
        $this->assertEquals('nl Test policy clone', $localisedpolicy_nl->get_translated_title('nl'));
    }
}