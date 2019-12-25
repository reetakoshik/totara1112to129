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
 * @author    Brian Barnes <brian.barnes@totaralearning.com>
 * @package   core_output
 */

defined('MOODLE_INTERNAL') || die();

class mustache_shortentext_helper_testcase extends advanced_testcase {
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

        $text = "Lorem ipsum dolor sit amet, consectetur adipiscing elit,";

        // Text shorter than required length.
        $loader->setTemplate('test', '{{#shortentext}}30,Lorem ipsum{{/shortentext}}');
        $this->assertEquals(
            'Lorem ipsum',
            $mustache->render('test')
        );

        // Text longer than required length with space in helper
        $loader->setTemplate('test', '{{# shortentext }}30,' . $text . '{{/ shortentext }}');
        $this->assertEquals(
            'Lorem ipsum dolor sit amet,...',
            $mustache->render('test')
        );

        // spaces around separators
        $loader->setTemplate('test', '{{# shortentext }} 30 , ' . $text . ' {{/ shortentext }}');
        $this->assertEquals(
            'Lorem ipsum dolor sit amet,...',
            $mustache->render('test')
        );

        // supplied via variable
        $loader->setTemplate('test', '{{# shortentext }} 30, {{ text }} {{/ shortentext }}');
        $this->assertEquals(
        'Lorem ipsum dolor sit amet,...',
            $mustache->render('test', ['text' => $text])
        );

        // supplied via unsaintised variable
        $loader->setTemplate('test', '{{# shortentext }} 30, {{{text}}} {{/ shortentext }}');
        $this->assertEquals(
        'Lorem ipsum dolor sit amet,...',
            $mustache->render('test', ['text' => $text])
        );

        // supplied via variable and short
        $loader->setTemplate('test', '{{# shortentext }} 30,{{text}}{{/ shortentext }}');
        $this->assertEquals(
            'Lorem ipsum',
            $mustache->render('test', ['text' => 'Lorem ipsum'])
        );

        $loader->setTemplate('test', '{{# shortentext }} 30,{{#str}} viewmyteam, totara_core {{/str}}{{/ shortentext }}');
        $this->assertEquals(
            '',
            $mustache->render('test')
        );
        $this->assertDebuggingCalled('Escaped content contains unexpected mustache processing queues. It will be lost.');

    }
}