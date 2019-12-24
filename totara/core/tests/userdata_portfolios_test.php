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
 * @package totara_core
 */

use totara_core\userdata\portfolios;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/portfoliolib.php');

/**
 * Class totara_core_userdata_portfolios_testcase
 *
 * @group totara_userdata
 */
class totara_core_userdata_portfolios_testcase extends advanced_testcase {

    /**
     * Creates data for testing
     *
     * @param string $pluginname
     * @return array containing data within related keys
     */
    private function create_test_data() {
        global $DB, $CFG;

        $data = new class() {
            /** @var \stdClass[] */
            public $users;

            /** @var string[] */
            public $plugins;

            /** @var \stdClass[] */
            public $portfolios;
        };

        $this->resetAfterTest(true);

        $data->users = [];
        for ($i = 0; $i < 3; $i++) {
            $data->users[] = $this->getDataGenerator()->create_user();
        }

        $data->portfolios = [];
        $plugins = core_component::get_plugin_list('portfolio');
        $data->plugins = array_keys($plugins);
        $data->portfolios = [];

        // Create a portfolio_instance for each plugin
        foreach ($data->plugins as $plugin) {
            require_once($CFG->dirroot . '/portfolio/'. $plugin . '/lib.php');
            $classname = 'portfolio_plugin_' . $plugin;
            $configkeys = $classname::get_allowed_config();
            $config = [];
            foreach ($configkeys as $key) {
                $config[$key] = "$key value";
            }
            $classname::create_instance($plugin, $classname::get_name(), $config);
            $data->portfolios[$plugin] = $DB->get_record('portfolio_instance', ['plugin' => $plugin], '*', MUST_EXIST);
            $rows = $DB->get_records('portfolio_instance_config', ['instance' => $data->portfolios[$plugin]->id]);
            $this->assertEquals(count($configkeys), count($rows));
        }

        // Create user config :
        //   - user1 in each portfolio
        //   - user2 in googledocs and picasa
        //   - user3 in none
        foreach ($data->plugins as $plugin) {
            $row = (object)[
                'instance' => $data->portfolios[$plugin]->id,
                'userid' => $data->users[0]->id,
                'name' => 'visible',
                'value' => 1
            ];
            $DB->insert_record('portfolio_instance_user', $row);

            if ($plugin == 'googledocs' || $plugin == 'picasa') {
                $row = (object)[
                    'instance' => $data->portfolios[$plugin]->id,
                    'userid' => $data->users[1]->id,
                    'name' => 'visible',
                    'value' => 1
                ];
                $DB->insert_record('portfolio_instance_user', $row);
            }
        }

        $rows = $DB->get_records('portfolio_instance_user', ['userid' => $data->users[0]->id]);
        $this->assertEquals(count($data->plugins), count($rows));
        $rows = $DB->get_records('portfolio_instance_user', ['userid' => $data->users[1]->id]);
        $this->assertEquals(2, count($rows));

        // Create logs :
        //   - user1 1 in each portfolio
        //   - user2 2 in googledocs and picasa
        //   - user3 in none
        // Defaulting all to a glossary entry
        // Using simple sequence for tempdataid
        foreach ($data->plugins as $i => $plugin) {
            $row = (object)[
                'userid' => $data->users[0]->id,
                'portfolio' => $data->portfolios[$plugin]->id,
                'caller_class' => 'glossary_entry_portfolio_caller',
                'caller_file'=> '',
                'caller_component' => 'mod_glossary',
                'caller_sha1' => '123456',
                'tempdataid' => $i + 1,
                'continueurl' => 0,
                'returnurl' => 'http://some/url',
                'time' => time(),
            ];
            $DB->insert_record('portfolio_log', $row);

            if ($plugin == 'googledocs' || $plugin == 'picasa') {
                $row->time = time() - 60;
                $row->userid = $data->users[1]->id;
                $row->tempdataid = $i + count($data->plugins);
                $DB->insert_record('portfolio_log', $row);
                $row->time = time();
                $row->tempdataid += 1;
                $DB->insert_record('portfolio_log', $row);
            }
        }

        $rows = $DB->get_records('portfolio_log', ['userid' => $data->users[0]->id]);
        $this->assertEquals(count($data->plugins), count($rows));
        $rows = $DB->get_records('portfolio_log', ['userid' => $data->users[1]->id]);
        $this->assertEquals(4, count($rows));

        // Create tempdata
        //   - user1 1 in googledocs and picase
        //   - user2 1 in each plugin
        //   - user3 in none
        foreach ($data->plugins as $i => $plugin) {
            $row = (object)[
                'data' => 'the data',
                'expirytime' => time() + (60*60*24),
                'userid' => $data->users[1]->id,
                'instance' => $data->portfolios[$plugin]->id,
            ];
            $DB->insert_record('portfolio_tempdata', $row);

            if ($plugin == 'googledocs' || $plugin == 'picasa') {
                $row->userid = $data->users[0]->id;
                $DB->insert_record('portfolio_tempdata', $row);
            }
        }

        $rows = $DB->get_records('portfolio_tempdata', ['userid' => $data->users[0]->id]);
        $this->assertEquals(2, count($rows));
        $rows = $DB->get_records('portfolio_tempdata', ['userid' => $data->users[1]->id]);
        $this->assertEquals(count($data->plugins), count($rows));

        return $data;
    }

    /**
     * Test abilities to export, count and purge
     */
    public function test_abilities() {

        $this->assertTrue(\totara_core\userdata\portfolios::is_exportable());
        $this->assertTrue(\totara_core\userdata\portfolios::is_countable());
        $this->assertTrue(\totara_core\userdata\portfolios::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(\totara_core\userdata\portfolios::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(\totara_core\userdata\portfolios::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test which context levels this item is compatible with
     */
    public function test_compatible_context_levels() {
        $this->assertEquals([CONTEXT_SYSTEM], \totara_core\userdata\portfolios::get_compatible_context_levels());
    }

    /**
     * Test export
     */
    public function test_export() {
        $data = $this->create_test_data();

        $export = portfolios::execute_export(
            new target_user($data->users[0]),
            context_system::instance()
        );
        $this->assertEmpty($export->files);
        $this->assertEquals(count($data->plugins), count($export->data['instances']));
        $this->assertEquals(count($data->plugins), count($export->data['log']));

        $export = portfolios::execute_export(
            new target_user($data->users[1]),
            context_system::instance()
        );
        $this->assertEmpty($export->files);
        $this->assertEquals(2, count($export->data['instances']));
        $this->assertEquals(4, count($export->data['log']));

        $export = portfolios::execute_export(
            new target_user($data->users[2]),
            context_system::instance()
        );
        $this->assertEmpty($export->files);
        $this->assertEquals(0, count($export->data['instances']));
        $this->assertEquals(0, count($export->data['log']));
    }

    /**
     * Test count
     */
    public function test_count() {
        $data = $this->create_test_data();

        $count = portfolios::execute_count(
            new target_user($data->users[0]),
            context_system::instance()
        );
        $this->assertEquals(2 * count($data->plugins), $count); // Instances + logs

        $count = portfolios::execute_count(
            new target_user($data->users[1]),
            context_system::instance()
        );
        $this->assertEquals(2 + 4, $count); // Instances + logs

        $count = portfolios::execute_count(
            new target_user($data->users[2]),
            context_system::instance()
        );
        $this->assertEquals(0, $count);
    }

    /**
     * Test purge
     */
    public function test_purge() {
        global $DB;

        $data = $this->create_test_data();

        // Start conditions asserted in data generation method
        $result = portfolios::execute_purge(
            new target_user($data->users[1]),
            context_system::instance()
        );
        $this->assertEquals(portfolios::RESULT_STATUS_SUCCESS, $result);

        $this->assertEquals(count($data->plugins), $DB->count_records('portfolio_instance_user', ['userid' => $data->users[0]->id]));
        $this->assertEquals(0, $DB->count_records('portfolio_instance_user', ['userid' => $data->users[1]->id]));
        $this->assertEquals(0, $DB->count_records('portfolio_instance_user', ['userid' => $data->users[2]->id]));

        $this->assertEquals(count($data->plugins), $DB->count_records('portfolio_log', ['userid' => $data->users[0]->id]));
        $this->assertEquals(0, $DB->count_records('portfolio_log', ['userid' => $data->users[1]->id]));
        $this->assertEquals(0, $DB->count_records('portfolio_log', ['userid' => $data->users[2]->id]));

        $this->assertEquals(2, $DB->count_records('portfolio_tempdata', ['userid' => $data->users[0]->id]));
        $this->assertEquals(0, $DB->count_records('portfolio_tempdata', ['userid' => $data->users[1]->id]));
        $this->assertEquals(0, $DB->count_records('portfolio_tempdata', ['userid' => $data->users[2]->id]));
    }

}