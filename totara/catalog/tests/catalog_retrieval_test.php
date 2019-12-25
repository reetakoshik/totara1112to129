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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

use core_course\totara_catalog\course\feature_factory\format;
use totara_catalog\catalog_retrieval;
use totara_catalog\local\config;
use totara_catalog\local\filter_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_catalog_catalog_retrieval_testcase extends advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_alphabetical_sorting_enabled() {
        global $CFG;

        // Check that it is enabled when only one language is installed.
        $catalog = new catalog_retrieval();
        $this->assertEquals(true, $catalog->alphabetical_sorting_enabled());

        // Create a second language and see that it is disabled.
        make_writable_directory($CFG->dataroot . '/lang/de');
        copy(
            $CFG->dirroot . "/install/lang/de/langconfig.php",
            $CFG->dataroot . "/lang/de/langconfig.php"
        );
        get_string_manager()->reset_caches();
        $this->assertEquals(false, $catalog->alphabetical_sorting_enabled());

        // Set the config override and see that it is enabled.
        set_config('catalog_enable_alpha_sorting_with_multiple_languages', true);
        $this->assertEquals(true, $catalog->alphabetical_sorting_enabled());
    }

    /**
     * Data provider for test_get_sql_orderby().
     */
    public function td_get_sql_orderby(): array {
        $by_score = ['catalogfts.score, catalog.sorttext', 'catalogfts.score desc, catalog.sorttext asc'];
        $by_time = ['catalog.sorttime, catalog.sorttext', 'catalog.sorttime desc, catalog.sorttext asc'];
        $by_feature = ['catalog.sorttext', 'coalesce(featured, 0) desc, catalog.sorttext asc'];
        $by_default = ['catalog.sorttext', 'catalog.sorttext asc'];

        return [
            ["  ", $by_default, false, false],
            ["  ", $by_default, false, true],
            ["  ", $by_feature, true, false],
            ["  ", $by_feature, true, true],

            ["featured", $by_default, false, false],
            ["featured", $by_default, false, true],
            ["featured", $by_feature, true, false],
            ["featured", $by_feature, true, true],

            ["time", $by_time, false, false],
            ["time", $by_time, false, true],
            ["time", $by_time, true, false],
            ["time", $by_time, true, true],

            ["score", $by_default, false, false],
            ["score", $by_score, false, true],
            ["score", $by_feature, true, false],
            ["score", $by_score, true, true],

            ["text", $by_default, false, false],
            ["text", $by_default, false, true],
            ["text", $by_default, true, false],
            ["text", $by_default, false, false],

            ["invalid", $by_default, false, false],
            ["invalid", $by_default, false, true],
            ["invalid", $by_default, true, false],
            ["invalid", $by_default, true, true]
        ];
    }

    /**
     * @dataProvider td_get_sql_orderby
     *
     * @param string $ordered_by
     * @param array $snippets
     * @param bool $enable_feature
     * @param bool $enable_fts
     */
    public function test_get_sql_orderby(
        string $ordered_by, array $snippets, bool $enable_feature, bool $enable_fts
    ): void {
        $columns = 'catalog.id, catalog.objecttype, catalog.objectid, catalog.contextid';
        $joins = "";
        $wheres = "";

        if ($enable_fts) {
            $fts = filter_handler::instance()->get_full_text_search_filter()->datafilter;
            $fts->set_current_data("some search text");

            [$fts_joins, $fts_wheres, $fts_params] = $fts->make_sql();
            $joins .= $fts_joins;
            $wheres .= $fts_wheres;
        }

        if ($enable_feature) {
            $columns .= ", coalesce(featured, 0) AS featured";

            $feature = format::get_features()[0];
            config::instance()->update(
                [
                    'featured_learning_enabled' => true,
                    'featured_learning_value' => 'some test featured learning',
                    'featured_learning_source' => $feature->key
                ]
            );

            [$feature_joins, $feature_wheres, $feature_params] = $feature->datafilter->make_sql();
            $joins .= $feature_joins;
            $wheres .= $feature_wheres;
        }

        [$extra_columns, $order] = $snippets;
        $columns .= ', ' . $extra_columns;

        $expected_sql = "select distinct $columns from {catalog} catalog";
        if (!empty($joins)) {
            $expected_sql .= " $joins";
        }
        if (!empty($wheres)) {
            $expected_sql .= " where $wheres";
        }
        $expected_sql .= " order by $order";
        $expected_sql = $this->normalized($expected_sql);

        $catalog = new catalog_retrieval();
        [$actual_sql, $actual_count_sql, $params] = $catalog->get_sql($ordered_by);

        $actual_sql = $this->normalized($actual_sql);
        $this->assertSame($expected_sql, $actual_sql, "wrong generated SQL");
    }

    public function test_get_sql_orderby_with_multilang() {
        // Create a reflection of the private method that can be run directly.
        $rm = new ReflectionMethod(catalog_retrieval::class, 'get_order_by_sql');
        $rm->setAccessible(true);

        // Confirm that get_orderby_sql is using the ordering specified.
        $catalog = new catalog_retrieval();
        list($orderbycolumns, $orderbysort) = $rm->invoke($catalog, 'alpha');
        $this->assertEquals('catalog.sorttext', $orderbycolumns);
        $this->assertEquals('catalog.sorttext ASC', $orderbysort);

        // Mock the catalog retrieval class to think that alphabetical sorting is disabled.
        $mockedcatalog = $this->getMockBuilder(catalog_retrieval::class)
            ->setMethods(['alphabetical_sorting_enabled'])
            ->getMock();
        $mockedcatalog->method('alphabetical_sorting_enabled')
            ->willReturn(false);

        // See that 'alpha' is ignored'.
        list($orderbycolumns, $orderbysort) = $rm->invoke($mockedcatalog, 'alpha');
        $this->assertEquals('catalog.sorttime', $orderbycolumns);
        $this->assertEquals('catalog.sorttime DESC', $orderbysort);

        // See that 'featured' is always used when featured learning is enabled.
        $feature = format::get_features()[0];
        config::instance()->update(
            [
                'featured_learning_enabled' => true,
                'featured_learning_value' => 'some test featured learning',
                'featured_learning_source' => $feature->key
            ]
        );
        list($orderbycolumns, $orderbysort) = $rm->invoke($mockedcatalog, 'time');
        $this->assertEquals('catalog.sorttime', $orderbycolumns);
        $this->assertEquals('COALESCE(featured, 0) DESC, catalog.sorttime DESC', $orderbysort);

        // See that 'score' is always used when full text search is active.
        $fts = filter_handler::instance()->get_full_text_search_filter()->datafilter;
        $fts->set_current_data("some search text");
        list($orderbycolumns, $orderbysort) = $rm->invoke($mockedcatalog, 'featured');
        $this->assertEquals('catalogfts.score, catalog.sorttime', $orderbycolumns);
        $this->assertEquals('catalogfts.score DESC, catalog.sorttime DESC', $orderbysort);
    }

    private function normalized(string $in): string {
        // Note this *removes* whitespace because extra whitespace in a string
        // is a pain when it comes to equals matching.
        return strtolower(trim(preg_replace('/\s+/', '', $in)));
    }

    public function test_get_page_of_objects() {
        global $DB;
        $DB->delete_records('catalog');

        // Creating courses indirectly updates the catalog.
        $courses = [];
        $no_of_courses = 9;
        $generator = $this->getDataGenerator();
        for ($i = 0; $i < $no_of_courses; $i++) {
            $courses[] = $generator->create_course();
        }

        $start = $no_of_courses - 3;
        $page_size = 2;
        $catalog = new catalog_retrieval();
        $result = $catalog->get_page_of_objects($page_size, $start, -1, 'featured');

        $this->assertFalse($result->endofrecords, "end of records is true");
        $this->assertSame($no_of_courses, $result->maxcount, "wrong max count");
        $this->assertSame($start + $page_size, $result->limitfrom, "wrong limit count");
        $this->assertCount($page_size, $result->objects, "wrong no of returned objects");

        $expected_courses = array_slice($courses, $start, $page_size);

        for ($i = 0; $i < $page_size; $i++) {
            $expected = $expected_courses[$i];
            $actual = $result->objects[$i];

            $this->assertSame('course', $actual->objecttype, "wrong type");
            $this->assertSame($expected->id, $actual->objectid, "wrong object id");
            $this->assertSame($expected->fullname, $actual->sorttext, "wrong sort text");
        }
    }

    /**
     * Data provider for test_get_safe_table_alias().
     */
    public function td_get_safe_table_alias(): array {
        return [
            ["AAAbbbCC", "AAAbbbCC"],
            ["AAA123CC", "AAACC"],
            ["AAA  CC", "AAACC"],
            ["AAA\t\nCC", "AAACC"],
            ["AAA<b>testing</b>CC", "AAAbtestingbCC"]
        ];
    }

    /**
     * @dataProvider td_get_safe_table_alias
     *
     * @param string $in
     * @param string $cleaned
     */
    public function test_get_safe_table_alias(string $in, string $cleaned) {
        $actual = catalog_retrieval::get_safe_table_alias($in);
        $expected = $cleaned . '_' . substr(md5($in), 0, 5);
        $this->assertSame(strtolower($expected), $actual, "string not cleaned");
    }
}
