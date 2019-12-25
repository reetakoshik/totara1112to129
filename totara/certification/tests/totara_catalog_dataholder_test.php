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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_certification
 * @category totara_catalog
 */

namespace totara_certification\totara_catalog\certification;

use totara_catalog\local\config;
use totara_catalog\output\{item, item_narrow};
use totara_catalog\{catalog_retrieval, provider_handler};

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_certification_totara_catalog_dataholder_testcase extends \advanced_testcase {

    /**
     * Create certification.
     */
    protected function setUp() {
        $this->resetAfterTest();
        
        // setup a specific certification with different long name and id
        $cert = array(
            'fullname' => 'Test Fullname 101',
            'shortname' => 'Test Shortname 101',
            'idnumber' => 'Test IDNumber 101'
        );
        
        $program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program_generator->create_certification($cert);
    }

    /**
     * Test that learning type data is correct.
     */
    public function test_learningtype() {
        $item_config = [
            'item_additional_text' => [
                'certification' => ['catalog_learning_type',''],
            ],
            'item_additional_text_label' => [
                'certification' => ['1','0'],
            ]
        ];
        $config = config::instance();
        $config->update($item_config);
        
        $items = $this->get_items($config);
        $text_placeholders = $items[0]->get_template_data()["text_placeholders"];
        
        $this->assertObjectHasAttribute('label', $text_placeholders[0]);
        $this->assertObjectHasAttribute('data', $text_placeholders[0]);
        $this->assertEquals('Learning type', $text_placeholders[0]->label);
        $this->assertEquals('Certifications', $text_placeholders[0]->data);
    }
    
    /**
     * Test that fullname data is correct.
     */
    public function test_fullname() {
        $item_config = [
            'item_additional_text' => [
                'certification' => ['fullname', ''],
            ],
            'item_additional_text_label' => [
                'certification' => ['1', '0'],
            ]
        ];
        $config = config::instance();
        $config->update($item_config);
        
        $items = $this->get_items($config);
        $text_placeholders = $items[0]->get_template_data()["text_placeholders"];
        
        $this->assertObjectHasAttribute('label', $text_placeholders[0]);
        $this->assertObjectHasAttribute('data', $text_placeholders[0]);
        $this->assertEquals('Full name', $text_placeholders[0]->label);
        $this->assertEquals('Test Fullname 101', $text_placeholders[0]->data);
    }
    
    /**
     * Test that shortname data is correct.
     */
    public function test_shortname() {
        $item_config = [
            'item_additional_text' => [
                'certification' => ['shortname', ''],
            ],
            'item_additional_text_label' => [
                'certification' => ['1', '0'],
            ]
        ];
        $config = config::instance();
        $config->update($item_config);
        
        $items = $this->get_items($config);
        $text_placeholders = $items[0]->get_template_data()["text_placeholders"];
        
        $this->assertObjectHasAttribute('label', $text_placeholders[0]);
        $this->assertObjectHasAttribute('data', $text_placeholders[0]);
        $this->assertEquals('Short name', $text_placeholders[0]->label);
        $this->assertEquals('Test Shortname 101', $text_placeholders[0]->data);
    }
    
    /**
     * Test that idnumber data is correct.
     */
    public function test_idnumber() {
        $item_config = [
            'item_additional_text' => [
                'certification' => ['idnumber', ''],
            ],
            'item_additional_text_label' => [
                'certification' => ['1', '0'],
            ]
        ];
        $config = config::instance();
        $config->update($item_config);
        
        $items = $this->get_items($config);
        $text_placeholders = $items[0]->get_template_data()["text_placeholders"];
        
        $this->assertObjectHasAttribute('label', $text_placeholders[0]);
        $this->assertObjectHasAttribute('data', $text_placeholders[0]);
        $this->assertEquals('ID', $text_placeholders[0]->label);
        $this->assertEquals('Test IDNumber 101', $text_placeholders[0]->data);
    }

    /**
     * Get catalog items.
     * 
     * @param config $config
     * @return item_narrow[]
     */
    private function get_items(config $config) {
        $catalog = new catalog_retrieval();
        $page = $catalog->get_page_of_objects($config->get_value('items_per_load'), 0, 20, '');
        return $this->get_item_templates($page->objects, 'narrow');
    }
    
    /**
     * Fetch all catalog items with their data holders.
     *
     * @param array $objects
     * @param string $itemstyle
     * @return item_narrow[]|item_wide[]
     */
    private function get_item_templates(array $objects, string $itemstyle) {
        $providerhandler = provider_handler::instance();

        $requireddataholders = [];
        foreach ($objects as $object) {
            if (empty($requireddataholders[$object->objecttype])) {
                $provider = $providerhandler->get_provider($object->objecttype);
                $requireddataholders[$object->objecttype] = item::get_required_dataholders($provider);
            }
        }

        $objects = $providerhandler->get_data_for_objects($objects, $requireddataholders);

        $items = [];

        foreach ($objects as $object) {
            if ($itemstyle == 'narrow') {
                $items[] = item_narrow::create($object);
            } else {
                $items[] = item_wide::create($object);
            }
        }

        return $items;
    }
}