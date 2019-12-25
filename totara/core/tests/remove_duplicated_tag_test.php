<?php
/*
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 * @category core_tag
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group core_tag
 */
class totara_core_remove_duplicated_tag_testcase extends advanced_testcase {
    /**
     * @param int $tagid    The id associated in table {tag}
     * @return void
     */
    private function update_tag_standard(int $tagid): void {
        global $DB;

        $record = new \stdClass();
        $record->id = $tagid;
        $record->isstandard = 1;

        $DB->update_record('tag', $record);
    }

    /**
     * Test suite of removing the duplicated and invalid tags, which scenario is quite simple enough: that the course
     * is using a very invalid (tag's name that has been propagated from times to time).
     *
     * @return void
     */
    public function test_removing_duplicated_tag(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/core/db/upgradelib.php");

        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $context = \context_course::instance($course->id);
        $instanceid = \core_tag_tag::add_item_tag(
            'core',
            'course',
            $course->id,
            $context,
            'hello & world'
        );

        $this->update_tag_standard(
            $DB->get_field('tag_instance', 'tagid', ['id' => $instanceid])
        );

        for ($i = 0; $i < 5; $i++) {
            $tags = core_tag_tag::get_item_tags_array('core', 'course', $course->id);
            \core_tag_tag::set_item_tags('core', 'course', $course->id, $context, $tags);
        }

        $this->assertGreaterThan(
            1,
            $DB->count_records('tag'),
            "Expecting more than one tag to be created here, because everytimes the course is being updated, " .
            "it will create a new tag"
        );

        totara_core_core_tag_upgrade_tags();

        // After upgrading, the upgrade to remove the course
        $this->assertEquals(1, $DB->count_records('tag'));
        $taginstance = $DB->get_record(
            'tag_instance',
            [
                'component' => 'core',
                'itemtype' => 'course',
                'itemid' => $course->id,
                'contextid' => \context_course::instance($course->id)->id
            ]
        );

        $this->assertNotNull($taginstance);

        $tagrecord = $DB->get_record('tag', ['id' => $taginstance->tagid]);
        $this->assertEquals('hello & world', $tagrecord->rawname);
    }

    /**
     * @return void
     */
    public function test_detect_and_remove_tags(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/core/db/upgradelib.php");

        $this->resetAfterTest(true);

        // Preparing the tags environment
        $collectionrecord = new \stdClass();
        $collectionrecord->name = 'New collection';
        $collectionrecord->searchable = 1;

        $newcoll = \core_tag_collection::create($collectionrecord);

        // Add a few area to this tags
        $tagarea = $DB->get_record(
            'tag_area',
            [
                'component' => 'core',
                'itemtype' => 'course',
                'enabled' => 1
            ]
        );

        $tagarea->tagcollid = $newcoll->id;
        $DB->update_record('tag_area', $tagarea);


        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $tagnames = [
            'hello abcde',
            'hello world',
            'this # "means war"',
            'wololo'
        ];

        $context = \context_course::instance($course->id);
        foreach ($tagnames as $tagname) {
            $instanceid = \core_tag_tag::add_item_tag(
                'core',
                'course',
                $course->id,
                $context,
                $tagname
            );

            $this->update_tag_standard(
                $DB->get_field('tag_instance', 'tagid', ['id' => $instanceid])
            );
        }

        for ($i = 0; $i < 3; $i++) {
            $tags = \core_tag_tag::get_item_tags_array('core', 'course', $course->id);
            \core_tag_tag::set_item_tags('core', 'course', $course->id, $context, $tags);
        }

        $this->assertEquals(
            count($tagnames) + 1,
            $DB->count_records('tag'),
            'Expecting the tag system to create another invalid tag name on it'
        );

        totara_core_core_tag_upgrade_tags();

        // Invalid tag names should be removed by now
        $this->assertCount(
            count($tagnames),
            $DB->get_records('tag'),
            'Expecting the tag system after upgrade would removed the invalid one'
        );

        $instances = $DB->get_records('tag_instance');

        foreach ($instances as $instance) {
            $tag = $DB->get_record('tag', ['id' => $instance->tagid]);

            $this->assertEquals($tag->tagcollid, $newcoll->id);
            $this->assertContains(
                $tag->rawname,
                $tagnames,
                'Expecting the tag instance after upgrade to be mapped properly with the original'
            );
        }
    }

    /**
     * Tags that are with valid name should not be removed by any chances.
     * @return void
     */
    public function test_not_removing_invalid_tag(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/core/db/upgradelib.php");

        $this->resetAfterTest(true);
        $gen = $this->getDataGenerator();

        $course = $gen->create_course();
        $tagnames = [
            'hello world',
            'שלום עולם',
        ];

        $context = \context_course::instance($course->id);
        foreach ($tagnames as $tagname) {
            \core_tag_tag::add_item_tag(
                'core',
                'course',
                $course->id,
                $context,
                $tagname
            );
        }

        for ($i = 0; $i < 3; $i++) {
            $tags = \core_tag_tag::get_item_tags_array('core', 'course', $course->id);
            \core_tag_tag::set_item_tags('core', 'course', $course->id, $context, $tags);
        }

        // There should be none on updating keywords.
        $this->assertEquals(2, $DB->count_records('tag'));

        totara_core_core_tag_upgrade_tags();

        // Nothing should be deleted after upgrade, because these tags containing no decoded special characters.
        $this->assertEquals(2, $DB->count_records('tag'));
        $instances = $DB->get_records('tag_instance');

        foreach ($instances as $instance) {
            $tag = $DB->get_record('tag', ['id' => $instance->tagid]);
            $this->assertContains($tag->rawname, $tagnames);
        }
    }

    /**
 * Test correcting HTML encoded non-standard tags.
 *
 * @return void
 */
    public function test_correcting_nonstandard_tag(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/core/db/upgradelib.php");

        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $context = \context_course::instance($course->id);
        \core_tag_tag::add_item_tag(
            'core',
            'course',
            $course->id,
            $context,
            'Love & Rockets'
        );

        for ($i = 0; $i < 5; $i++) {
            $tags = core_tag_tag::get_item_tags_array('core', 'course', $course->id);
            \core_tag_tag::set_item_tags('core', 'course', $course->id, $context, $tags);
        }

        $this->assertEquals(1, $DB->count_records('tag'));

        // Check that tag is multi-encoded.
        $taginstance = $DB->get_record(
            'tag_instance',
            [
                'component' => 'core',
                'itemtype' => 'course',
                'itemid' => $course->id,
                'contextid' => \context_course::instance($course->id)->id
            ]
        );

        $this->assertNotNull($taginstance);

        $tagrecord = $DB->get_record('tag', ['id' => $taginstance->tagid]);
        $this->assertEquals('Love &amp;amp;amp;amp;amp; Rockets', $tagrecord->rawname);
        $this->assertEquals('love &amp;amp;amp;amp;amp; rockets', $tagrecord->name);

        totara_core_core_tag_upgrade_tags();

        // After upgrading...
        $this->assertEquals(1, $DB->count_records('tag'));

        $tagrecord = $DB->get_record('tag', ['id' => $taginstance->tagid]);
        $this->assertEquals('Love & Rockets', $tagrecord->rawname);
        $this->assertEquals('love & rockets', $tagrecord->name);

    }

    /**
     * Test correcting multiple HTML encoded non-standard tags on multiple courses.
     *
     * @return void
     */
    public function test_correcting_multiple_conflicting_nonstandard_tag(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/core/db/upgradelib.php");

        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $course1 = $gen->create_course();

        $context1 = \context_course::instance($course1->id);
        \core_tag_tag::add_item_tag(
            'core',
            'course',
            $course1->id,
            $context1,
            'Love & Rockets'
        );

        for ($i = 0; $i < 5; $i++) {
            $tags = core_tag_tag::get_item_tags_array('core', 'course', $course1->id);
            \core_tag_tag::set_item_tags('core', 'course', $course1->id, $context1, $tags);
        }

        $this->assertEquals(1, $DB->count_records('tag'));

        // Create a second course, with a fresh, unencoded tag instance
        $course2 = $gen->create_course();
        $context2 = \context_course::instance($course2->id);
        \core_tag_tag::add_item_tag(
            'core',
            'course',
            $course2->id,
            $context2,
            'Love & Rockets'
        );

        $this->assertEquals(2, $DB->count_records('tag'));

        totara_core_core_tag_upgrade_tags();

        // After upgrading...
        $this->assertEquals(1, $DB->count_records('tag'));

        $taginstance1 = $DB->get_record(
            'tag_instance',
            [
                'component' => 'core',
                'itemtype' => 'course',
                'itemid' => $course1->id,
                'contextid' => \context_course::instance($course1->id)->id
            ]
        );

        $this->assertNotNull($taginstance1);

        $tagrecord = $DB->get_record('tag', ['id' => $taginstance1->tagid]);
        $this->assertEquals('Love & Rockets', $tagrecord->rawname);
        $this->assertEquals('love & rockets', $tagrecord->name);

        $taginstance2 = $DB->get_record(
            'tag_instance',
            [
                'component' => 'core',
                'itemtype' => 'course',
                'itemid' => $course2->id,
                'contextid' => \context_course::instance($course2->id)->id
            ]
        );

        $this->assertNotNull($taginstance2);
        $this->assertEquals($taginstance1->tagid, $taginstance2->tagid);
    }
}