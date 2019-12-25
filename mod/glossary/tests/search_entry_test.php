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
* @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
* @package mod_glossary
*/

class mod_glossary_search_entry_testcase extends advanced_testcase {

    public function test_search_entry() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $glossary = $this->getDataGenerator()->create_module('glossary', array('course' => $course));
        $glossarygenerator = $this->getDataGenerator()->get_plugin_generator('mod_glossary');

        $definition1 = 'Ut semper, risus euismod vestibulum eleifend, ante mi pellentesque libero, et consequat est tortor eu sapien.';
        $entry1 = $glossarygenerator->create_content($glossary, array('definition' => $definition1));
        $entry2 = $glossarygenerator->create_content($glossary, array('concept' => 'Custom concept'), array('sapien'));
        $entry3 = $glossarygenerator->create_content($glossary, array('concept' => 'Custom concept sapien'));

        $context = context_module::instance($glossary->cmid);
        list($allentries, $count) = glossary_get_entries_by_search($glossary, $context, 'sapien', 0, 'CREATION', 'ASC', 0, 10);
        $this->assertEquals(2, $count);

        foreach ($allentries as $entry) {
            $this->assertContains($entry->id, array($entry2->id, $entry3->id));
            $this->assertNotEquals($entry->id, $entry1->id);
        }
    }

    public function test_search_entry_fulltext() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $glossary = $this->getDataGenerator()->create_module('glossary', array('course' => $course));
        $glossarygenerator = $this->getDataGenerator()->get_plugin_generator('mod_glossary');

        // Add long definitions to make sure we can search past 255 characters.
        $definition1 = 'Ut semper, risus euismod vestibulum eleifend, ante mi pellentesque libero, et consequat est tortor eu sapien. Nam lacus erat, varius et pretium id, maximus a urna. Mauris elit lorem, tristique nec consectetur sit amet, tincidunt ut nisi. Nam interdum odio eget velit consectetur, eget tincidunt felis eleifend.';
        $definition2 = 'Sed at justo in lacus mollis ultrices. Aenean maximus felis nunc, a luctus tellus varius eu. Proin ultricies pellentesque metus sed suscipit. Sed feugiat pellentesque rutrum. Nulla facilisi. Suspendisse rhoncus neque sed egestas dapibus. Aenean purus odio, ultricies eget hendrerit vel, pretium ut velit.';
        $entry1 = $glossarygenerator->create_content($glossary, array('definition' => $definition1));
        $entry2 = $glossarygenerator->create_content($glossary, array('concept' => 'Custom concept', 'definition' => $definition2), array('felis'));
        $entry3 = $glossarygenerator->create_content($glossary, array('concept' => 'Custom concept felis'));

        $context = context_module::instance($glossary->cmid);
        list($allentries, $count) = glossary_get_entries_by_search($glossary, $context, 'velit', 1, 'CREATION', 'ASC', 0, 10);
        $this->assertEquals(2, $count);
        foreach ($allentries as $entry) {
            $this->assertContains($entry->id, array($entry1->id, $entry2->id));
            $this->assertNotEquals($entry->id, $entry3->id);
        }

        list($allentries, $count) = glossary_get_entries_by_search($glossary, $context, 'felis', 1, 'CREATION', 'ASC', 0, 10);
        $this->assertEquals(3, $count);
        foreach ($allentries as $entry) {
            $this->assertContains($entry->id, array($entry1->id, $entry2->id, $entry3->id));
        }
    }
}
