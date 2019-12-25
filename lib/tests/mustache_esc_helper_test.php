<?php
/*
 * This file is part of Totara LMS
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
 * @copyright 2018 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package   core_output
 */

defined('MOODLE_INTERNAL') || die();

class mustache_esc_helper_testcase extends advanced_testcase {

    /**
     * Creates a new mustache instance, cloned from the real one, ready for testing.
     *
     * @return array
     */
    private static function get_mustache() {

        $page = new \moodle_page();
        $renderer = $page->get_renderer('core');
        $reflection = new ReflectionMethod($renderer, 'get_mustache');
        $reflection->setAccessible(true);
        /** @var Mustache_Engine $mustache */
        $mustache = $reflection->invoke($renderer);
        // Clone it, we want the real mustache loader to still have access to the templates.
        $mustache = clone($mustache);
        // Set a new loader so that we can add templates for testing.
        $loader = new Mustache_Loader_ArrayLoader([]);
        $mustache->setLoader($loader);
        return [$mustache, $loader, $renderer, $page];
    }

    /**
     * Test the get_mustache method returns what we require.
     */
    public function test_get_mustache() {
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();
        self::assertInstanceOf(Mustache_Engine::class, $mustache);
        self::assertInstanceOf(Mustache_Loader_ArrayLoader::class, $loader);
        self::assertInstanceOf(core_renderer::class, $renderer);
        self::assertInstanceOf(moodle_page::class, $page);
    }

    public function test_valid_usage() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        // Plain text
        $loader->setTemplate('test', '{{#esc}}test{{/esc}}');
        $this->assertEquals(
            'test',
            $mustache->render('test')
        );

        // Plain text with spaces
        $loader->setTemplate('test', '{{#esc}} test {{/esc}}');
        $this->assertEquals(
            ' test ',
            $mustache->render('test')
        );

        // Plain text with spaces
        $loader->setTemplate('test', '{{#esc}}   {{/esc}}');
        $this->assertEquals(
            '   ',
            $mustache->render('test')
        );

        // Complex plain text
        $loader->setTemplate('test', "{{#esc}}This is a test\nof the public\tbroadcast system{{/esc}}");
        $this->assertEquals(
            "This is a test\nof the public\tbroadcast system",
            $mustache->render('test')
        );

        // Single variable
        $loader->setTemplate('test', "{{#esc}}{{var}}{{/esc}}");
        $this->assertEquals(
            'Test',
            $mustache->render('test', ['var' => 'Test'])
        );

        // Single variable escaping works
        $loader->setTemplate('test', "{{#esc}}{{var}}{{/esc}}");
        $this->assertEquals(
            '&lt;b&gt;Mustache&lt;/b&gt;',
            $mustache->render('test', ['var' => '<b>Mustache</b>'])
        );
        $loader->setTemplate('test', "{{#esc}}{{{var}}}{{/esc}}");
        $this->assertEquals(
            '<b>Mustache</b>',
            $mustache->render('test', ['var' => '<b>Mustache</b>'])
        );

        // Spaces around var.
        $loader->setTemplate('test', "{{#esc}} {{foo}} {{/esc}}");
        $this->assertEquals(
            'Foo',
            $mustache->render('test', ['foo' => 'Foo'])
        );

        // Spaces inside curly braces.
        $loader->setTemplate('test', "{{#esc}} {{ foo }} {{/esc}}");
        $this->assertEquals(
            'Foo',
            $mustache->render('test', ['foo' => 'Foo'])
        );


        // Missing var
        $loader->setTemplate('test', "{{#esc}}{{foo}}{{/esc}}");
        $this->assertEquals(
            '',
            $mustache->render('test', ['bar' => 'Bar'])
        );

        // Single variable complex string
        $loader->setTemplate('test', "{{#esc}}{{var}}{{/esc}}");
        $this->assertEquals(
            "This is a test\nof the public\tbroadcast system",
            $mustache->render('test', ['var' => "This is a test\nof the public\tbroadcast system"])
        );

        // Embedded with content
        $loader->setTemplate('test', "<a href='#' title='blah'>{{#esc}}{{var}}{{/esc}}</a>");
        $this->assertEquals(
            "<a href='#' title='blah'>Delete</a>",
            $mustache->render('test', ['var' => "Delete"])
        );
    }

    public function test_mixed_content_not_allowed() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        // Following text.
        $loader->setTemplate('test', "{{#esc}}{{foo}}Bar{{/esc}}");
        $this->assertEquals(
            '',
            $mustache->render('test', ['foo' => 'Foo'])
        );
        $this->assertDebuggingCalled('Escaped content contains unexpected mustache processing queues. It will be lost.');

        // Preceding text.
        $loader->setTemplate('test', "{{#esc}}Bar{{foo}}{{/esc}}");
        $this->assertEquals(
            '',
            $mustache->render('test', ['foo' => 'Foo'])
        );
        $this->assertDebuggingCalled('Escaped content contains unexpected mustache processing queues. It will be lost.');
    }

    public function test_multiple_vars_not_allowed() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        // Multiple variables
        $loader->setTemplate('test', "{{#esc}}{{foo}}{{bar}}{{/esc}}");
        $this->assertEquals(
            '',
            $mustache->render('test', ['foo' => 'Foo', 'bar' => 'Bar'])
        );
        $this->assertDebuggingCalled('Escaped content contains unexpected mustache processing queues. It will be lost.');
    }

    public function test_helpers_not_allowed() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        // Single variable complex string
        $loader->setTemplate('test', "{{#esc}}{{#str}}delete{{/str}}{{/esc}}");
        $this->assertEquals(
            '',
            $mustache->render('test')
        );
        $this->assertDebuggingCalled('Escaped content contains unexpected mustache processing queues. It will be lost.');
    }

    public function test_recursive_vars_not_allowed() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        $loader->setTemplate('test', "{{#esc}}{{foo}}{{/esc}}");
        $this->assertEquals(
            '[[bar]]',
            $mustache->render('test', ['foo' => '{{bar}}', 'bar' => 'Bar'])
        );
        $this->assertDebuggingCalled('Mustache processing quotes converted to square brackets for safety.');
    }

    public function test_pattern_mismatch() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        $loader->setTemplate('test', "{{#esc}}{{{{foo}}}}{{/esc}}");
        $this->assertEquals(
            '',
            $mustache->render('test', ['foo' => 'test'])
        );
        $this->assertDebuggingCalled('Escaped content contains unexpected mustache processing queues. It will be lost.');
    }
}