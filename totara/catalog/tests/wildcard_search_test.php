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
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

use core_course\totara_catalog\course;
use totara_catalog\local\filter_handler;
use totara_catalog\catalog_retrieval;
use totara_program\totara_catalog\program;
use totara_catalog\filter;

class totara_catalog_wildcard_search_testcase extends advanced_testcase {
    /**
     * Test suite for searching the learning item from fts wildcard search. And this test suite is for
     * course only, plus that with wildcard search, the query should be un-accented by default.
     *
     * @return void
     */
    public function test_wildcard_search_with_single_word_for_course(): void {
        $this->resetAfterTest(true);
        $gen = static::getDataGenerator();

        $course1 = $gen->create_course(
            [
                'shortname' => 'this is shortname',
                'fullname' => 'this is an actsman',
                'summary' => 'bolobala balabolo lpth p'
            ],
            ['createsections' => true]
        );

        $course2 = $gen->create_course(
            [
                'shortname' => 'tia course2',
                'fullname' => 'this is course2',
                'summary' => 'ajdiw klobp[ve epl ve;[be; \ b\lbp[r pojmf ioep lkp0 sh0fjib'
            ]
        );

        $terms = [
            [
                'value' => 'short*',
                'count' => 1,
                'course' => $course1->id
            ],
            [
                'value' => 'bolo*',
                'count' => 1,
                'course' => $course1->id
            ],
            [
                'value' => 'ac*',
                'count' => 1,
                'course' => $course1->id
            ],
            [
                'value' => 'helloworld',
                'count' => 0,
                'course' => null
            ],
            [
                'value' => 'sh0*',
                'count' => 1,
                'course' => $course2->id
            ]
        ];

        if ($this->check_index_process_completed()) {
            foreach ($terms as $term) {
                $filterhandler = filter_handler::instance();
                $filterhandler->reset_cache();

                $filter = $filterhandler->get_full_text_search_filter();

                // Let the php reference pointer doing this update $filterhandler for us.
                $filter->selector->set_current_data(
                    ['catalog_fts' => $term['value']]
                );

                $filter->datafilter->set_current_data(
                    $filter->selector->get_data()
                );

                $catalog = new catalog_retrieval();
                $page = $catalog->get_page_of_objects(20, 0);

                if (!property_exists($page, 'objects')) {
                    static::fail("No property 'objects' defined in \$page");
                }

                static::assertCount(
                    $term['count'],
                    $page->objects,
                    "Expecting the result with the pattern as '{$term['value']}' to equal '{$term['count']}'"
                );

                if (0 == $term['count']) {
                    continue;
                }

                $item = reset($page->objects);

                static::assertEquals($term['course'], $item->objectid);
                static::assertEquals(course::get_object_type(), $item->objecttype);
            }

            return;
        }

        static::fail("FTS index not completed in time, this is an issue with MSSQL");
    }

    /**
     * Test suite of checking the wildcard filter search for program.
     *
     * @return void
     */
    public function test_wildcard_search_with_single_word_for_program(): void {
        $this->resetAfterTest(true);
        $gen = static::getDataGenerator();

        /** @var totara_program_generator $proggen */
        $proggen = $gen->get_plugin_generator('totara_program');
        $prog1 = $proggen->create_program(
            [
                'fullname' => 'This is actsman',
                'shortname' => 'This is shortname',
            ]
        );

        // Program 2, but it is not using in this test. Just in place to make sure that the query does
        // not include it.
        $proggen->create_program(
            [
                'fullname' => 'this is program2',
                'shortname' => 'tia program2'
            ]
        );

        $terms = [
            [
                'value' => 'ac*',
                'prog' => $prog1->id,
                'count' => 1
            ],
            [
                'value' => 'sho*',
                'prog' => $prog1->id,
                'count' => 1
            ],
            [
                // By default, without any asterisk, it will not searchable for record containing
                'value' => 'short',
                'prog' => null,
                'count' => 0
            ]
        ];

        if ($this->check_index_process_completed()) {
            foreach ($terms as $term) {
                $filterhandler = filter_handler::instance();
                $filterhandler->reset_cache();

                $filter = $filterhandler->get_full_text_search_filter();

                // Let the php reference pointer doing this update $filterhandler for us.
                $filter->selector->set_current_data(
                    ['catalog_fts' => $term['value']]
                );

                $filter->datafilter->set_current_data(
                    $filter->selector->get_data()
                );

                $catalog = new catalog_retrieval();
                $page = $catalog->get_page_of_objects(20, 0);

                if (!property_exists($page, 'objects')) {
                    static::fail("The object \$page does not have property 'objects'");
                }

                static::assertCount($term['count'], $page->objects);
                if (0 == $term['count']) {
                    continue;
                }

                $record = reset($page->objects);
                static::assertEquals($term['prog'], $record->objectid);
                static::assertEquals(program::get_object_type(), $record->objecttype);
            }

            return;
        }

        static::fail("FTS index not completed in time, this is an issue with MSSQL");
    }

    /**
     * When a record is inserted, the way that mssql work on it is that it does not wait for the process of
     * changing/populating the index to return the result back to PHP processor. But instead, it just return the
     * result of inserting records for php only. Therefore, we need to assure that the processes of fts
     * population are completely done, so that we can start performing test.
     *
     * @see https://docs.microsoft.com/en-us/sql/t-sql/functions/objectpropertyex-transact-sql?view=sql-server-2017
     * @return bool
     */
    private function check_index_process_completed(): bool {
        global $DB;
        if ('mssql' === $DB->get_dbvendor()) {
            $running = true;
            $attempted = 0;
            $sql = "SELECT OBJECTPROPERTYEX(OBJECT_ID(N'{$DB->get_prefix()}catalog'), N'TableFullTextPopulateStatus') as status;";

            while ($running) {
                $fts_indexing = $DB->get_record_sql($sql);
                switch ($fts_indexing->status) {
                    case '0':
                        // Idle, we're good to go.
                        $running = false;
                        break;
                    case '5':
                    case '6':
                        // Throttled, paused, or broken.
                        static::fail("FTS index cannot be completed due to an MSSQL error: TableFullTextPopulateStatus=" . $fts_indexing->status);
                        break;
                    default:
                        // Still running - Give it a several attempts,  if it exceeds 10 then MsSQL is having trouble.
                        if (10 < $attempted) {
                            return false;
                        }
                        sleep(1);
                        $attempted += 1;
                }
            }
        }

        return true;
    }
}